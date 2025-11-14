<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Stripe\Exception\ApiErrorException;

class CheckoutController extends Controller
{
    /**
     * Get payment gateway information and available items
     *
     * @param string $slug Payment gateway slug
     * @return JsonResponse
     */
    public function getGatewayInfo(string $slug): JsonResponse
    {
        $gateway = PaymentGateway::with('branch')
            ->where('slug', $slug)
            ->where('is_enabled', true)
            ->first();

        if (!$gateway) {
            return response()->json([
                'message' => 'Payment gateway not found or disabled',
            ], 404);
        }

        // Check if branch can accept payments
        if (!$gateway->branch->canAcceptPayments()) {
            return response()->json([
                'message' => 'This branch cannot accept payments at this time',
            ], 503);
        }

        // Get available products
        $products = [];
        if (!empty($gateway->available_product_ids)) {
            $products = Product::whereIn('id', $gateway->available_product_ids)
                ->where('status', 'active')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'type' => 'product',
                        'name' => $product->name,
                        'description' => $product->description,
                        'price' => (float) $product->price,
                        'sku' => $product->sku,
                    ];
                });
        }

        // Get available services
        $services = [];
        if (!empty($gateway->available_service_ids)) {
            $services = Service::whereIn('id', $gateway->available_service_ids)
                ->where('is_active', true)
                ->get()
                ->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'type' => 'service',
                        'name' => $service->name,
                        'description' => $service->description,
                        'price' => (float) $service->price,
                        'duration' => $service->duration,
                        'sku' => $service->sku,
                    ];
                });
        }

        // TODO: Add subscriptions when implemented

        return response()->json([
            'gateway' => [
                'slug' => $gateway->slug,
                'business_name' => $gateway->business_name ?? $gateway->branch->name,
                'logo_url' => $gateway->logo_url,
                'primary_color' => $gateway->primary_color,
                'secondary_color' => $gateway->secondary_color,
                'terms_and_conditions' => $gateway->terms_and_conditions,
                'success_message' => $gateway->success_message,
            ],
            'items' => [
                'products' => $products,
                'services' => $services,
                'subscriptions' => [], // TODO: Implement when ready
            ],
        ]);
    }

    /**
     * Create a payment intent for the order
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     * @throws ValidationException
     */
    public function createPaymentIntent(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'in:product,service'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'customer_email' => ['required', 'email'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:20'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $gateway = PaymentGateway::with('branch')
            ->where('slug', $slug)
            ->where('is_enabled', true)
            ->first();

        if (!$gateway) {
            return response()->json([
                'message' => 'Payment gateway not found or disabled',
            ], 404);
        }

        // Check if branch can accept payments
        if (!$gateway->branch->canAcceptPayments()) {
            return response()->json([
                'message' => 'This branch cannot accept payments at this time',
            ], 503);
        }

        // Calculate total and prepare items
        $totalAmount = 0;
        $orderItems = [];

        foreach ($validated['items'] as $item) {
            if ($item['type'] === 'product') {
                $product = Product::find($item['id']);
                if (!$product || !in_array($product->id, $gateway->available_product_ids ?? [])) {
                    return response()->json([
                        'message' => 'Invalid product in order',
                    ], 400);
                }
                
                $itemPrice = (float) $product->price;
                $itemTotal = $itemPrice * $item['quantity'];
                $totalAmount += $itemTotal;
                
                $orderItems[] = [
                    'type' => 'product',
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $itemPrice,
                    'quantity' => $item['quantity'],
                    'total' => $itemTotal,
                ];
            } elseif ($item['type'] === 'service') {
                $service = Service::find($item['id']);
                if (!$service || !in_array($service->id, $gateway->available_service_ids ?? [])) {
                    return response()->json([
                        'message' => 'Invalid service in order',
                    ], 400);
                }
                
                $itemPrice = (float) $service->price;
                $itemTotal = $itemPrice * $item['quantity'];
                $totalAmount += $itemTotal;
                
                $orderItems[] = [
                    'type' => 'service',
                    'id' => $service->id,
                    'name' => $service->name,
                    'price' => $itemPrice,
                    'quantity' => $item['quantity'],
                    'total' => $itemTotal,
                ];
            }
        }

        if ($totalAmount <= 0) {
            return response()->json([
                'message' => 'Invalid order total',
            ], 400);
        }

        // Create order record
        $order = Order::create([
            'branch_id' => $gateway->branch_id,
            'payment_gateway_id' => $gateway->id,
            'customer_email' => $validated['customer_email'],
            'customer_name' => $validated['customer_name'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'total_amount' => $totalAmount,
            'currency' => 'USD', // TODO: Make this configurable
            'status' => 'pending',
            'items' => $orderItems,
            'customer_notes' => $validated['customer_notes'] ?? null,
        ]);

        try {
            // Create Stripe payment intent using direct charge to branch's connected account
            $amountInCents = (int) round($totalAmount * 100);
            
            // Use Cashier Connect to create direct charge
            $paymentIntent = $gateway->branch->createDirectCharge(
                $amountInCents,
                'usd', // TODO: Make this configurable
                [
                    'metadata' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'gateway_slug' => $gateway->slug,
                    ],
                    'description' => "Order {$order->order_number} - {$gateway->business_name}",
                    'receipt_email' => $validated['customer_email'],
                ]
            );

            // Update order with payment intent ID
            $order->update([
                'stripe_payment_intent_id' => $paymentIntent->id,
            ]);

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $totalAmount,
            ]);

        } catch (ApiErrorException $e) {
            // Mark order as failed
            $order->update(['status' => 'failed']);
            
            return response()->json([
                'message' => 'Failed to create payment intent',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm order after successful payment
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     * @throws ValidationException
     */
    public function confirmOrder(Request $request, string $slug): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'payment_intent_id' => ['required', 'string'],
        ]);

        $order = Order::with(['branch', 'paymentGateway'])->findOrFail($validated['order_id']);

        // Verify payment intent matches
        if ($order->stripe_payment_intent_id !== $validated['payment_intent_id']) {
            return response()->json([
                'message' => 'Payment intent mismatch',
            ], 400);
        }

        // Verify gateway slug matches
        if ($order->paymentGateway->slug !== $slug) {
            return response()->json([
                'message' => 'Gateway mismatch',
            ], 400);
        }

        try {
            // Retrieve payment intent from Stripe to verify status
            $stripeAccount = $order->branch->stripeAccountId();
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $paymentIntent = $stripe->paymentIntents->retrieve(
                $validated['payment_intent_id'],
                [],
                ['stripe_account' => $stripeAccount]
            );

            if ($paymentIntent->status === 'succeeded') {
                // Update order status
                $order->update([
                    'status' => 'completed',
                    'stripe_charge_id' => $paymentIntent->charges->data[0]->id ?? null,
                    'completed_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Order confirmed successfully',
                    'order' => [
                        'order_number' => $order->order_number,
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'customer_email' => $order->customer_email,
                    ],
                ]);
            } else {
                return response()->json([
                    'message' => 'Payment not completed',
                    'status' => $paymentIntent->status,
                ], 400);
            }

        } catch (ApiErrorException $e) {
            return response()->json([
                'message' => 'Failed to confirm payment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

