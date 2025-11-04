<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StripeController extends Controller
{
    /**
     * Generate Stripe Connect onboarding URL for a branch.
     * This endpoint is designed for SPA/API usage where redirects happen on the frontend.
     *
     * @param Request $request
     * @param int $id Branch ID
     * @return JsonResponse
     * @throws ValidationException
     */
    public function generateOnboardingUrl(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'returnURL' => ['required', 'url'],
            'refreshURL' => ['required', 'url'],
        ]);

        $branch = Branch::findOrFail($id);

        // Check authorization
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // If branch already has completed onboarding, return dashboard URL instead
        if ($branch->hasStripeAccount() && $branch->hasCompletedOnboarding()) {
            return response()->json([
                'url' => $branch->stripeAccountDashboardUrl(),
                'status' => 'completed',
                'message' => 'Onboarding already completed. Dashboard URL provided.',
            ]);
        }

        // Create or recreate Stripe Connect account if needed
        if (!$branch->hasStripeAccount()) {
            $branch->createAsStripeAccount('express', [
                'settings' => [
                    'payouts' => [
                        'schedule' => [
                            'interval' => 'weekly',
                            'weekly_anchor' => 'friday',
                        ],
                    ],
                ],
            ]);

            // Refresh model to get the new Stripe account data
            $branch->refresh();
        }

        // Generate onboarding URL
        $onboardingUrl = $branch->accountOnboardingUrl(
            $validated['returnURL'],
            $validated['refreshURL']
        );

        return response()->json([
            'url' => $onboardingUrl,
            'status' => 'pending',
            'message' => 'Onboarding URL generated successfully.',
        ]);
    }

    /**
     * Get the current onboarding status of a branch.
     *
     * @param int $id Branch ID
     * @return JsonResponse
     */
    public function getOnboardingStatus(int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        // Check authorization
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $hasAccount = $branch->hasStripeAccount();
        $isCompleted = $hasAccount && $branch->hasCompletedOnboarding();

        // Determine if can accept payments
        $canAcceptPayments = false;
        if ($hasAccount) {
            $mapping = $branch->stripeAccountMapping;
            $canAcceptPayments = $mapping ? (bool) $mapping->charges_enabled : false;
        }

        return response()->json([
            'hasStripeAccount' => $hasAccount,
            'onboardingCompleted' => $isCompleted,
            'stripeAccountId' => $hasAccount ? $branch->stripeAccountId() : null,
            'canAcceptPayments' => $canAcceptPayments,
        ]);
    }

    /**
     * Get Stripe account dashboard URL for a branch.
     *
     * @param int $id Branch ID
     * @return JsonResponse
     */
    public function getDashboardUrl(int $id): JsonResponse
    {
        $branch = Branch::findOrFail($id);

        // Check authorization
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        if (!$branch->hasStripeAccount()) {
            return response()->json([
                'message' => 'Branch does not have a Stripe account.',
            ], 404);
        }

        return response()->json([
            'url' => $branch->stripeAccountDashboardUrl(),
        ]);
    }

    /**
     * Delete and recreate Stripe account for a branch.
     * Useful for resetting the onboarding process.
     *
     * @param Request $request
     * @param int $id Branch ID
     * @return JsonResponse
     */
    public function resetAccount(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'returnURL' => ['required', 'url'],
            'refreshURL' => ['required', 'url'],
        ]);

        $branch = Branch::findOrFail($id);

        // Check authorization
        if (!auth()->check()) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // Delete existing account and create new one
        $branch->deleteAndCreateStripeAccount('express', [
            'settings' => [
                'payouts' => [
                    'schedule' => [
                        'interval' => 'weekly',
                        'weekly_anchor' => 'friday',
                    ],
                ],
            ],
        ]);

        // Refresh model to get the new Stripe account data
        $branch->refresh();

        // Generate onboarding URL
        $onboardingUrl = $branch->accountOnboardingUrl(
            $validated['returnURL'],
            $validated['refreshURL']
        );

        return response()->json([
            'url' => $onboardingUrl,
            'status' => 'reset',
            'message' => 'Stripe account reset successfully. New onboarding URL generated.',
        ]);
    }
}
