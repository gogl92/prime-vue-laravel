# Payment Gateway Testing Guide

## Prerequisites

Before testing the payment gateway functionality, ensure:

1. **Laravel Sail is Running**
   ```bash
   ./vendor/bin/sail up -d
   ```

2. **Migrations Completed**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

3. **Frontend Built**
   ```bash
   npm install
   npm run dev
   ```

4. **Stripe Environment Variables Set**
   ```env
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   VITE_STRIPE_KEY=pk_test_...
   ```

## Test Scenario 1: Service Creation

### Steps

1. **Login as Admin**
   - Navigate to your application URL
   - Login with admin credentials

2. **Access Service Management**
   - Go to Settings → Services
   - Click "Add Service"

3. **Create Test Service**
   - Branch: Select a branch
   - Name: "Consulting Session"
   - Description: "One-hour consulting session"
   - Price: 150.00
   - Duration: 60 (minutes)
   - SKU: "CONSULT-001"
   - Active: Checked
   - Click "Save"

4. **Verify Service Created**
   - Service should appear in the table
   - Check branch association
   - Verify price displays correctly

### Expected Results
- ✅ Service created successfully
- ✅ Service appears in list
- ✅ All details display correctly

## Test Scenario 2: Stripe Connect Setup

### Steps

1. **Access Stripe Connect Settings**
   - Go to Settings → Stripe Connect

2. **Connect a Branch**
   - Find branch in the list
   - Click "Connect" button
   - Complete Stripe onboarding (use test mode)
   - Fill in business details
   - Submit

3. **Verify Connection**
   - Branch should show "Connected" status
   - Stripe account ID should be visible
   - "Can Accept Payments" should be "Yes"

### Expected Results
- ✅ Stripe onboarding completes successfully
- ✅ Branch shows as connected
- ✅ Branch can accept payments

## Test Scenario 3: Payment Gateway Configuration

### Steps

1. **Access Payment Gateway Settings**
   - Go to Settings → Payment Gateways

2. **Setup Gateway for Branch**
   - Find connected branch
   - Click "Setup Gateway"

3. **Configure Gateway**
   - Enable Gateway: Checked
   - Business Name: "Test Store"
   - Click "Generate" for slug
   - Logo URL: (optional)
   - Primary Color: Choose color
   - Secondary Color: Choose color
   - Available Products: Select test products
   - Available Services: Select "Consulting Session"
   - Terms: "By purchasing, you agree to our terms"
   - Success Message: "Thank you for your purchase!"
   - Click "Save"

4. **Verify Gateway Created**
   - Gateway should show as "Enabled"
   - Checkout URL should be visible
   - Copy checkout URL for next test

### Expected Results
- ✅ Gateway created successfully
- ✅ Slug generated automatically
- ✅ Checkout URL accessible

## Test Scenario 4: Customer Purchase Flow - Product

### Steps

1. **Access Checkout Page**
   - Open checkout URL in new tab (or click "Test" button)
   - Should see branded storefront

2. **Browse Products**
   - View Products tab
   - Should see configured products
   - Prices should display correctly

3. **Add Product to Cart**
   - Click "Add to Cart" on a product
   - Cart should update with item
   - Cart count should increase

4. **Update Quantity**
   - Use +/- buttons to change quantity
   - Total should recalculate
   - Can remove item with trash icon

5. **Proceed to Checkout**
   - Click "Proceed to Checkout"
   - Should navigate to payment form

6. **Fill Customer Information**
   - Full Name: "Test Customer"
   - Email: "test@example.com"
   - Phone: "555-1234"
   - Order Notes: "Test order"

7. **Enter Payment Details**
   - Card Number: 4242 4242 4242 4242
   - Expiry: Any future date (e.g., 12/25)
   - CVC: Any 3 digits (e.g., 123)
   - Check "I agree to terms"

8. **Submit Payment**
   - Click "Pay Now"
   - Wait for processing
   - Should see success page

9. **Verify Success**
   - Success message displays
   - Order number shown
   - Can continue shopping

### Expected Results
- ✅ Checkout page loads with branding
- ✅ Products display correctly
- ✅ Cart updates properly
- ✅ Payment processes successfully
- ✅ Success page displays
- ✅ Order recorded in database

### Database Verification

```bash
./vendor/bin/sail artisan tinker
```

```php
// Check order was created
Order::latest()->first();

// Verify order details
$order = Order::latest()->first();
echo "Order: {$order->order_number}\n";
echo "Status: {$order->status}\n";
echo "Total: \${$order->total_amount}\n";
echo "Customer: {$order->customer_name} ({$order->customer_email})\n";
echo "Stripe Payment Intent: {$order->stripe_payment_intent_id}\n";
```

## Test Scenario 5: Customer Purchase Flow - Service

### Steps

1. **Access Checkout Page**
   - Use same checkout URL

2. **Browse Services**
   - Click Services tab
   - Should see "Consulting Session"
   - Duration should display

3. **Buy Now**
   - Click "Buy Now" (skips cart)
   - Should go directly to payment form

4. **Complete Purchase**
   - Fill customer information
   - Use test card: 4242 4242 4242 4242
   - Submit payment

5. **Verify Success**
   - Check success page
   - Verify order number
   - Check database for service order

### Expected Results
- ✅ Services tab displays correctly
- ✅ Buy Now bypasses cart
- ✅ Service purchase completes
- ✅ Duration info displays

## Test Scenario 6: Payment Failures

### Test Declined Card

1. **Add Item to Cart**
2. **Proceed to Checkout**
3. **Use Declined Card**
   - Card: 4000 0000 0000 0002
   - Complete form
   - Submit

4. **Verify Error Handling**
   - Error message displays
   - User can try again
   - Order status remains pending

### Expected Results
- ✅ Error message shows
- ✅ User can retry
- ✅ Order not completed

## Test Scenario 7: Multiple Items Purchase

### Steps

1. **Add Multiple Items**
   - Add 2 different products
   - Add 1 service
   - Different quantities

2. **Verify Cart**
   - All items show
   - Quantities correct
   - Total calculates properly

3. **Complete Purchase**
   - Proceed to checkout
   - Complete payment
   - Verify all items in order

### Expected Results
- ✅ Cart handles multiple items
- ✅ Total calculates correctly
- ✅ Order contains all items

## Test Scenario 8: Gateway Configuration Changes

### Steps

1. **Disable Gateway**
   - Go to Payment Gateway Settings
   - Edit gateway
   - Uncheck "Enable Gateway"
   - Save

2. **Try to Access Checkout**
   - Visit checkout URL
   - Should see error message

3. **Re-enable Gateway**
   - Edit gateway again
   - Check "Enable Gateway"
   - Save

4. **Verify Access Restored**
   - Checkout should work again

### Expected Results
- ✅ Disabled gateway blocks access
- ✅ Error message displays
- ✅ Re-enabling restores access

## Test Scenario 9: Item Availability Updates

### Steps

1. **Remove Product from Gateway**
   - Edit gateway
   - Unselect a product
   - Save

2. **Visit Checkout**
   - Product should not display
   - Other products still available

3. **Try to Purchase Removed Product**
   - If you had it in cart, it should be validated
   - Should show error if not available

### Expected Results
- ✅ Only selected items display
- ✅ Removed items not available
- ✅ Validation prevents unavailable purchases

## Test Scenario 10: Stripe Dashboard Verification

### Steps

1. **Login to Stripe Dashboard**
   - Go to dashboard.stripe.com
   - Switch to test mode

2. **View Connected Accounts**
   - Go to Connect → Accounts
   - Find branch's connected account

3. **View Payments**
   - Check branch's account
   - Should see test payments
   - Verify amounts match orders

4. **Check Platform Account**
   - Should NOT see direct charges
   - Direct charges go to branch

### Expected Results
- ✅ Connected account shows charges
- ✅ Amounts match database orders
- ✅ Payment intents succeeded

## Common Issues and Solutions

### Issue: Checkout Page Shows 404

**Cause**: Route not registered or wrong URL

**Solution**:
```bash
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan route:cache
```

### Issue: Stripe Elements Not Loading

**Cause**: Missing Stripe key in environment

**Solution**:
```env
VITE_STRIPE_KEY=pk_test_your_key_here
```
Then rebuild frontend:
```bash
npm run dev
```

### Issue: Payment Fails with "Branch Cannot Accept Payments"

**Cause**: Stripe Connect not fully configured

**Solution**:
- Complete Stripe Connect onboarding
- Verify branch shows "Connected" in Stripe Connect settings
- Check branch's connected account is active in Stripe dashboard

### Issue: Products Not Showing in Checkout

**Cause**: Products not added to gateway or inactive

**Solution**:
- Edit payment gateway
- Select products in "Available Products"
- Verify products are active in product management

### Issue: Order Created but Status Pending

**Cause**: Payment confirmation failed

**Solution**:
- Check Stripe dashboard for payment intent status
- Verify webhook endpoints configured (if using webhooks)
- Check application logs for errors

## Performance Testing

### Load Testing Checklist

- [ ] Create 10 concurrent orders
- [ ] Test with 50+ products in catalog
- [ ] Test with large cart (20+ items)
- [ ] Test slow network conditions
- [ ] Test on mobile devices

### Monitoring

Check application logs:
```bash
./vendor/bin/sail logs -f
```

Monitor database queries:
```bash
./vendor/bin/sail artisan telescope:work
```

## Security Testing

### Checklist

- [ ] Verify card data never reaches server
- [ ] Test SQL injection in form fields
- [ ] Test XSS in product names
- [ ] Verify CSRF protection
- [ ] Test authorization (can't access other company's gateways)
- [ ] Verify payment amounts can't be manipulated

## Accessibility Testing

### Checklist

- [ ] Keyboard navigation works
- [ ] Screen reader compatible
- [ ] Color contrast meets WCAG standards
- [ ] Form labels present
- [ ] Error messages clear

## Browser Compatibility

Test in:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Chrome Mobile (Android)

## Sign-Off Checklist

Before marking as production-ready:

- [ ] All test scenarios pass
- [ ] No linter errors
- [ ] Database migrations successful
- [ ] Stripe test mode works correctly
- [ ] Error handling verified
- [ ] Documentation complete
- [ ] Performance acceptable
- [ ] Security review passed
- [ ] Accessibility standards met
- [ ] Browser compatibility confirmed

## Next Steps

After testing:

1. Switch to Stripe live mode (when ready)
2. Update environment variables for production
3. Test with real bank account
4. Configure webhook endpoints
5. Set up monitoring and alerts
6. Train users on system
7. Create user documentation
8. Plan for customer support

## Support

For testing issues:
- Check application logs
- Review Stripe dashboard
- Check browser console
- Verify environment variables
- Review PAYMENT_GATEWAY_IMPLEMENTATION.md

