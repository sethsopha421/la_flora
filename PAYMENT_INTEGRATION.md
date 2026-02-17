# Bakong KH QR Payment Integration for La Flora

This document explains how to integrate Bakong KH QR code payments into your La Flora ecommerce shop.

## Features Implemented

✅ **Bakong QR Payment Methods:**
- ABA Bank Bakong QR
- ACLEDA Bank Bakong QR

✅ **Payment Flow:**
1. Customer selects Bakong QR payment option at checkout
2. Payment modal displays with QR code image
3. Customer scans QR code with their mobile banking app
4. Payment details shown (Bank, Account, Amount)
5. Customer completes payment and confirms
6. Order status updates to "shipped" after payment

✅ **Files Added/Modified:**
- `checkout.php` - Added Bakong QR payment options and modal
- `assets/js/script.js` - Added payment handling JavaScript
- `includes/payment_functions.php` - Payment processing functions
- `admin/generate_qr_codes.html` - QR code generator tool
- `assets/images/ABA.jpg` - ABA Bank QR code placeholder
- `assets/images/ACLEDA.jpg` - ACLEDA Bank QR code placeholder

## Setup Instructions

### Step 1: Generate QR Codes

1. Open `http://localhost/la_flora/admin/generate_qr_codes.html` in your browser
2. Enter your ABA Bank account number and merchant ID
3. Enter your ACLEDA Bank account number and merchant ID
4. Click "Generate QR Code" for each bank
5. Download the generated QR codes or right-click to save

### Step 2: Upload QR Code Images

1. Save the QR codes as:
   - `assets/images/ABA.jpg`
   - `assets/images/ACLEDA.jpg`

2. Or replace the placeholder SVG files that are already in place

### Step 3: Update Payment Configuration

Edit `assets/js/script.js` and update the payment configuration:

```javascript
const bakongPayments = {
    bakong_aba: {
        name: 'ABA Bank',
        accountNumber: 'YOUR_ABA_ACCOUNT_HERE', // Update this
        accountId: 'YOUR_MERCHANT_ID@laflora.com',
    },
    bakong_acleda: {
        name: 'ACLEDA Bank',
        accountNumber: 'YOUR_ACLEDA_ACCOUNT_HERE', // Update this
        accountId: 'YOUR_MERCHANT_ID@laflora.com',
    }
};
```

### Step 4: Test the Integration

1. Add items to cart
2. Go to checkout
3. Fill in shipping information
4. Select "Bakong QR (ABA Bank)" or "Bakong QR (ACLEDA Bank)"
5. Verify QR code displays correctly in the modal

## Payment Flow Diagram

```
Customer → Add to Cart → Checkout Page
                            ↓
                    Select Payment Method
                            ↓
            [Choose Bakong QR Payment]
                            ↓
                    Payment Modal Opens
                    (QR Code Displayed)
                            ↓
            Customer Scans QR with Mobile App
                            ↓
            Customer Confirms Payment
                            ↓
            Order Status Updated to "Shipped"
                            ↓
            Confirmation Email Sent
                            ↓
            Order Appears in Dashboard
```

## Payment Modal Features

The payment modal displays:
- **QR Code Image**: Displayed on the left
- **Payment Details**: Bank name, account number, amount to pay
- **Instructions**: Step-by-step payment instructions
- **Confirm Button**: After scanning and paying, customer clicks to confirm

## Database Structure

The system automatically creates a `payments` table to track transactions:

```sql
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_method VARCHAR(50),
    amount DECIMAL(10, 2),
    transaction_id VARCHAR(255),
    status VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);
```

## Available Payment Methods

In the checkout page, customers can choose:

1. **Cash on Delivery** - Traditional COD
2. **Bakong QR (ABA Bank)** - Shows ABA QR code
3. **Bakong QR (ACLEDA Bank)** - Shows ACLEDA QR code
4. **Credit/Debit Card** - For future integration
5. **Online Payment** - For future integration

## JavaScript Functions

### `showBakongPayment(paymentType)`
Displays the Bakong payment modal with QR code

**Parameters:**
- `paymentType` - Either 'bakong_aba' or 'bakong_acleda'

### `confirmBakongPayment(paymentType)`
Confirms the payment and selects the radio button

**Parameters:**
- `paymentType` - The selected payment type

### `getTotalAmount()`
Retrieves the total amount from the checkout page

**Returns:** Total amount as string

## Future Enhancements

1. **Bank API Integration**
   - Verify payments directly with bank
   - Automatic order status update
   - Real-time payment confirmation

2. **Email Notifications**
   - Send payment confirmation email
   - Send order status updates
   - Implement SMS notifications

3. **Payment History**
   - Display payment history in customer dashboard
   - Show transaction details
   - Download invoices

4. **Webhook Support**
   - Receive payment confirmations from bank
   - Automatic order processing
   - Error handling and retries

5. **Multiple QR Code Types**
   - Dynamic QR generation
   - Amount-specific QR codes
   - Expiring QR codes for security

## Troubleshooting

### QR Code Not Displaying
- Check that image files exist: `assets/images/ABA.jpg` and `assets/images/ACLEDA.jpg`
- Verify file permissions are readable
- Clear browser cache and reload

### Payment Modal Not Opening
- Ensure Bootstrap 5 is loaded
- Check browser console for JavaScript errors
- Verify `assets/js/script.js` is included

### Images Not Loading in Modal
- Check file paths are correct
- Verify image files are in `assets/images/` folder
- Use absolute paths if relative paths don't work

## Security Considerations

1. **HTTPS**: Always use HTTPS for checkout pages
2. **Account Numbers**: Store securely, never hardcode
3. **QR Codes**: Don't expose sensitive data in QR codes
4. **Verification**: Always verify payments with bank API
5. **Transaction Logging**: Keep logs of all transactions for auditing

## Environment Variables (Optional)

You can store sensitive data in environment variables:

```bash
ABA_ACCOUNT=your_aba_account_number
ABA_MERCHANT=your_merchant_id
ACLEDA_ACCOUNT=your_acleda_account_number
ACLEDA_MERCHANT=your_merchant_id
```

Then access in PHP:
```php
$aba_account = getenv('ABA_ACCOUNT');
$acleda_account = getenv('ACLEDA_ACCOUNT');
```

## Support & Testing

For testing purposes:
- Generate test QR codes with dummy account numbers
- Test the complete checkout flow
- Verify emails are sent correctly
- Check order status updates in admin panel

## Contact & Updates

For Bakong integration details, visit: https://bakong.nbc.gov.kh/

For La Flora updates, check: `admin/generate_qr_codes.html`

---

**Last Updated:** January 16, 2026
**Integration Status:** ✅ Complete and Ready
