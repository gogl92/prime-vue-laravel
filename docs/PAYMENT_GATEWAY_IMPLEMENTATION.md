# Payment Gateway Implementation

## Overview

This document describes the complete payment gateway system that allows each branch to create public checkout pages where customers can purchase products, services, and subscriptions using Stripe Connect.

## Architecture

### Backend Components

#### Models
- **Service** (`app/Models/Service.php`) - Service offerings with pricing and duration
- **PaymentGateway** (`app/Models/PaymentGateway.php`) - Payment gateway configuration per branch
- **Order** (`app/Models/Order.php`) - Customer orders and transaction records

#### Controllers
- **ServiceController** (`app/Http/Controllers/ServiceController.php`) - CRUD operations for services
- **PaymentGatewayController** (`app/Http/Controllers/PaymentGatewayController.php`) - Gateway management
- **CheckoutController** (`app/Http/Controllers/Api/CheckoutController.php`) - Public checkout endpoints

#### Database Tables
- `services` - Service offerings
- `payment_gateways` - Gateway configurations
- `orders` - Order records

### Frontend Components

#### Admin Pages
- **Services** (`resources/js/pages/settings/Services.vue`) - Manage services
- **Payment Gateway Settings** (`resources/js/pages/settings/PaymentGatewaySettings.vue`) - Configure gateways

#### Public Checkout
- **Checkout Page** (`resources/js/pages/public/Checkout.vue`) - Customer-facing checkout
- **Shopping Cart** (`resources/js/components/checkout/ShoppingCart.vue`) - Cart management
- **Checkout Form** (`resources/js/components/checkout/CheckoutForm.vue`) - Payment processing

## Setup Instructions

### Prerequisites

1. **Stripe Connect Account**
   - Each branch must complete Stripe Connect onboarding
   - Navigate to Settings → Stripe Connect
   - Connect each branch that needs payment gateway functionality

2. **Environment Variables**
   ```env
   STRIPE_KEY=your_stripe_publishable_key
   STRIPE_SECRET=your_stripe_secret_key
   VITE_STRIPE_KEY=your_stripe_publishable_key
   ```

### Step-by-Step Configuration

#### 1. Create Services (Optional)

1. Navigate to **Settings → Services**
2. Click "Add Service"
3. Fill in service details:
   - Branch
   - Name
   - Description
   - Price
   - Duration (in minutes)
   - SKU (optional)
   - Active status
4. Click "Save"

#### 2. Configure Products

Products should already exist in the system. Make sure:
- Products have valid prices
- Products are marked as "active"
- Products belong to the appropriate branches

#### 3. Setup Payment Gateway

1. Navigate to **Settings → Payment Gateways**
2. Find the branch you want to configure
3. Ensure the branch shows "Connected" under Stripe Status
4. Click "Setup Gateway"
5. Configure the gateway:
   - **Enable Gateway**: Check to activate
   - **Business Name**: Display name for customers
   - **URL Slug**: Unique identifier for checkout URL (auto-generated)
   - **Logo URL**: Optional company logo
   - **Primary/Secondary Colors**: Customize checkout page appearance
   - **Available Products**: Select which products to offer
   - **Available Services**: Select which services to offer
   - **Terms and Conditions**: Optional terms text
   - **Success Message**: Message shown after successful payment
6. Click "Save"

#### 4. Test the Gateway

1. From the Payment Gateway Settings page, click "Test" next to configured gateway
2. This opens the public checkout page in a new tab
3. Add items to cart and proceed to checkout
4. Use Stripe test card numbers:
   - Success: `4242 4242 4242 4242`
   - Decline: `4000 0000 0000 0002`
   - Requires Authentication: `4000 0025 0000 3155`
   - Any future expiry date and any 3-digit CVC

## API Endpoints

### Protected Endpoints (Requires Authentication)

#### Services
- `GET /api/services` - List all services
- `POST /api/services` - Create a service
- `GET /api/services/{id}` - Get service details
- `PUT /api/services/{id}` - Update a service
- `DELETE /api/services/{id}` - Delete a service

#### Payment Gateways
- `GET /api/payment-gateways` - List all gateways
- `POST /api/payment-gateways` - Create a gateway
- `GET /api/payment-gateways/{id}` - Get gateway details
- `PUT /api/payment-gateways/{id}` - Update a gateway
- `DELETE /api/payment-gateways/{id}` - Delete a gateway
- `POST /api/payment-gateways/generate-slug` - Generate unique slug

### Public Endpoints (No Authentication Required)

#### Checkout
- `GET /api/checkout/{slug}/info` - Get gateway information and available items
- `POST /api/checkout/{slug}/payment-intent` - Create payment intent
- `POST /api/checkout/{slug}/confirm` - Confirm order after payment

### Frontend Routes

- `/settings/services` - Service management
- `/settings/payment-gateways` - Gateway configuration
- `/checkout/{slug}` - Public checkout page

## Payment Flow

### Customer Journey

1. **Browse Items**
   - Customer visits `/checkout/{branch-slug}`
   - Views available products and services
   - Can browse tabs for different item types

2. **Add to Cart**
   - Customer adds items to cart with quantity
   - Can update quantities or remove items
   - Cart shows real-time total

3. **Proceed to Checkout**
   - Customer clicks "Proceed to Checkout"
   - System creates payment intent via Stripe
   - Order record created in "pending" status

4. **Enter Information**
   - Customer fills in contact details
   - Customer enters card information
   - Customer accepts terms and conditions

5. **Process Payment**
   - Payment processed through Stripe Connect
   - Direct charge to branch's connected account
   - Platform fee automatically applied (if configured)

6. **Confirmation**
   - Order status updated to "completed"
   - Success message displayed
   - Customer can continue shopping

### Behind the Scenes

1. **Payment Intent Creation**
   ```php
   $paymentIntent = $branch->createDirectCharge(
       $amountInCents,
       'usd',
       [
           'metadata' => [
               'order_id' => $order->id,
               'order_number' => $order->order_number,
           ],
       ]
   );
   ```

2. **Direct Charge to Branch**
   - Payment goes directly to branch's Stripe account
   - Branch pays Stripe processing fees
   - Platform can configure commission (in Branch model)

3. **Order Recording**
   - Order details stored in database
   - Includes customer information
   - Tracks items, quantities, and prices
   - Records Stripe payment intent and charge IDs

## Customization

### Branch Commission

Edit in `app/Models/Branch.php`:

```php
// Fixed fee per transaction
public string $commission_type = 'fixed';
public int $commission_rate = 500; // $5.00

// OR percentage-based
public string $commission_type = 'percentage';
public int $commission_rate = 5; // 5%
```

### Gateway Appearance

Admins can customize:
- Business name
- Logo URL
- Primary color (hex)
- Secondary color (hex)
- Terms and conditions
- Success message

### Available Items

Admins can select:
- Which products to offer
- Which services to offer
- Which subscriptions to offer (when implemented)

## Security Considerations

1. **Payment Processing**
   - All card data handled by Stripe
   - PCI compliance maintained through Stripe
   - No card data stored on server

2. **Branch Verification**
   - Only branches with completed Stripe onboarding can accept payments
   - Gateway checks `canAcceptPayments()` before processing

3. **Order Validation**
   - Items verified against gateway's available items
   - Prices fetched from database, not client
   - Payment intent matches order total

## Testing

### Test Cards

Use Stripe test mode cards:

**Success Cases:**
- `4242 4242 4242 4242` - Visa
- `5555 5555 5555 4444` - Mastercard
- `3782 822463 10005` - American Express

**Decline Cases:**
- `4000 0000 0000 0002` - Generic decline
- `4000 0000 0000 9995` - Insufficient funds

**Special Cases:**
- `4000 0025 0000 3155` - Requires 3D Secure authentication

### Test Procedure

1. **Setup Test Gateway**
   - Use test mode Stripe keys
   - Configure gateway with test products/services

2. **Test Customer Flow**
   - Browse items
   - Add to cart
   - Proceed to checkout
   - Enter test customer info
   - Use test card numbers
   - Verify success/failure flows

3. **Verify Order Recording**
   - Check orders table for new records
   - Verify order status updates
   - Check Stripe dashboard for charges

## Troubleshooting

### Gateway Not Loading

- Verify branch has completed Stripe Connect onboarding
- Check gateway is enabled
- Verify slug is correct in URL

### Payment Fails

- Check Stripe dashboard for errors
- Verify branch's connected account is active
- Check that items are properly configured
- Ensure Stripe keys are correct

### Orders Not Created

- Check browser console for errors
- Verify API endpoints are accessible
- Check database migrations ran successfully
- Verify branch relationships are correct

## Future Enhancements

- [ ] Subscription support
- [ ] Discount codes/coupons
- [ ] Tax calculation
- [ ] Shipping options
- [ ] Customer accounts
- [ ] Order history
- [ ] Email notifications
- [ ] Invoice generation
- [ ] Refund processing
- [ ] Analytics dashboard

## Support

For issues or questions:
1. Check Stripe Connect documentation
2. Review Laravel Cashier Connect docs
3. Check application logs
4. Contact development team

