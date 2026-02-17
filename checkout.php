<?php

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = 'checkout.php';
    header("Location: user/login.php");
    exit();
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Include database connection
require_once 'includes/db_connection.php';

// Initialize variables
$errors = [];
$success = false;
$user_id = $_SESSION['user_id'];

// Get user information
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user_info = $user_result->fetch_assoc();

// Calculate cart totals
$subtotal = 0;
$item_count = 0;

foreach ($_SESSION['cart'] as $item) {
    if (isset($item['product'])) {
        $product = $item['product'];
        $quantity = $item['quantity'] ?? 1;
    } else {
        $product = $item;
        $quantity = $item['quantity'] ?? 1;
    }
    
    if (is_array($product)) {
        $price = $product['price'] ?? 0;
        $subtotal += $price * $quantity;
        $item_count += $quantity;
    }
}

$shipping = $subtotal > 50 ? 0 : 9.99;
$tax = $subtotal * 0.08;
$total = $subtotal + $shipping + $tax;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $state = $_POST['state'] ?? '';
    $zip_code = $_POST['zip_code'] ?? '';
    $country = $_POST['country'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'visa';
    
    // Validate form data
    if (empty($first_name)) $errors['first_name'] = "First name is required";
    if (empty($last_name)) $errors['last_name'] = "Last name is required";
    if (empty($email)) $errors['email'] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = "Invalid email format";
    if (empty($phone)) $errors['phone'] = "Phone number is required";
    if (empty($address)) $errors['address'] = "Address is required";
    if (empty($city)) $errors['city'] = "City is required";
    if (empty($state)) $errors['state'] = "State is required";
    if (empty($zip_code)) $errors['zip_code'] = "ZIP code is required";
    if (empty($country)) $errors['country'] = "Country is required";
    
    // If using credit card, validate card details
    if ($payment_method == 'visa') {
        $card_number = $_POST['card_number'] ?? '';
        $expiry_date = $_POST['expiry_date'] ?? '';
        $cvv = $_POST['cvv'] ?? '';
        $card_name = $_POST['card_name'] ?? '';
        
        if (empty($card_number)) $errors['card_number'] = "Card number is required";
        if (empty($expiry_date)) $errors['expiry_date'] = "Expiry date is required";
        if (empty($cvv)) $errors['cvv'] = "CVV is required";
        if (empty($card_name)) $errors['card_name'] = "Card name is required";
        
        // Validate card number format (simplified)
        $card_number = preg_replace('/\s+/', '', $card_number);
        if (!preg_match('/^4[0-9]{12}(?:[0-9]{3})?$/', $card_number)) {
            $errors['card_number'] = "Invalid Visa card number";
        }
        
        // Validate expiry date
        if (!preg_match('/^(0[1-9]|1[0-2])\/?([0-9]{2})$/', $expiry_date)) {
            $errors['expiry_date'] = "Invalid expiry date (MM/YY)";
        }
        
        // Validate CVV
        if (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
            $errors['cvv'] = "Invalid CVV";
        }
    }
    
    // If no errors, process order
    if (empty($errors)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Generate order number
            $order_number = 'ORD' . date('Ymd') . strtoupper(uniqid());
            
            // Create order
            $order_query = "INSERT INTO orders (user_id, order_number, total_amount, shipping_amount, tax_amount, status, shipping_address, payment_method, payment_status) VALUES (?, ?, ?, ?, ?, 'pending', ?, ?, 'pending')";
            $stmt = $conn->prepare($order_query);
            
            $shipping_address = "$address, $city, $state $zip_code, $country";
            
            $stmt->bind_param("isdddss", 
                $user_id,
                $order_number,
                $total,
                $shipping,
                $tax,
                $shipping_address,
                $payment_method
            );
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating order: " . $stmt->error);
            }
            
            $order_id = $stmt->insert_id;
            
            // Add order items
            $item_query = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($item_query);
            
            foreach ($_SESSION['cart'] as $product_id => $item) {
                if (isset($item['product'])) {
                    $product = $item['product'];
                    $quantity = $item['quantity'] ?? 1;
                } else {
                    $product = $item;
                    $quantity = $item['quantity'] ?? 1;
                }
                
                if (is_array($product)) {
                    $product_name = $product['name'] ?? 'Product';
                    $price = $product['price'] ?? 0;
                    
                    $stmt->bind_param("issid", 
                        $order_id,
                        $product_id,
                        $product_name,
                        $quantity,
                        $price
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error adding order item: " . $stmt->error);
                    }
                }
            }
            
            // Process payment (simulated for demo)
            if ($payment_method == 'visa') {
                // In real implementation, integrate with payment gateway like Stripe
                $payment_success = simulateVisaPayment($card_number, $expiry_date, $cvv, $total);
                
                if ($payment_success) {
                    // Update payment status
                    $update_query = "UPDATE orders SET payment_status = 'completed', status = 'processing' WHERE id = ?";
                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param("i", $order_id);
                    $stmt->execute();
                    
                    // Create payment record
                    $payment_query = "INSERT INTO payments (order_id, payment_method, amount, status, transaction_id) VALUES (?, ?, ?, 'completed', ?)";
                    $stmt = $conn->prepare($payment_query);
                    $transaction_id = 'TRX' . strtoupper(uniqid());
                    $stmt->bind_param("isds", $order_id, $payment_method, $total, $transaction_id);
                    $stmt->execute();
                    
                    // Clear cart
                    unset($_SESSION['cart']);
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Store order info for confirmation
                    $_SESSION['last_order_id'] = $order_id;
                    $_SESSION['order_number'] = $order_number;
                    
                    // Redirect to confirmation
                    header("Location: order-confirmation.php");
                    exit();
                } else {
                    throw new Exception("Payment processing failed");
                }
            }
            
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $errors['general'] = "Error processing order: " . $e->getMessage();
        }
    }
}

// Simulate Visa payment (for demo only - replace with real payment gateway)
function simulateVisaPayment($card_number, $expiry_date, $cvv, $amount) {
    // In real implementation, use Stripe, PayPal, or other payment gateway
    // This is just a simulation for demo purposes
    
    // Basic validation
    $card_number = preg_replace('/\s+/', '', $card_number);
    
    if (strlen($card_number) < 16) return false;
    if (!preg_match('/^4/', $card_number)) return false; // Must start with 4 for Visa
    
    // Simulate processing delay
    sleep(1);
    
    // Simulate 90% success rate for demo
    return rand(1, 10) <= 9;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - RA-FLORA</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>

        /* ===== CHECKOUT PAGE ===== */
:root {
    --primary-green: #2A5934;
    --light-green: #4A7856;
    --cream: #F8F5F0;
    --beige: #E8E2D6;
    --gold: #C9A96E;
    --dark: #1A1A1A;
    --light-gray: #f8f9fa;
}

.checkout-page {
    background-color: var(--light-gray);
    min-height: calc(100vh - 200px);
}

/* Checkout Header */
.checkout-header {
    background: linear-gradient(135deg, var(--primary-green), var(--light-green));
    padding: 30px 0;
    color: white;
}

.checkout-steps {
    display: flex;
    justify-content: space-between;
    max-width: 800px;
    margin: 0 auto;
    position: relative;
}

.checkout-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 2px;
    background: rgba(255, 255, 255, 0.3);
    z-index: 1;
}

.step {
    position: relative;
    text-align: center;
    z-index: 2;
}

.step-circle {
    width: 40px;
    height: 40px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 10px;
    font-weight: 600;
    border: 3px solid transparent;
    transition: all 0.3s ease;
}

.step.active .step-circle {
    background: white;
    color: var(--primary-green);
    border-color: white;
    transform: scale(1.1);
}

.step-label {
    font-size: 0.9rem;
    font-weight: 500;
    opacity: 0.8;
}

.step.active .step-label {
    opacity: 1;
    font-weight: 600;
}

/* Checkout Form */
.checkout-form {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
}

.checkout-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 2px solid var(--cream);
}

.checkout-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-title {
    color: var(--primary-green);
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
}

.section-title i {
    color: var(--gold);
}

/* Form Styles */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 8px;
    display: block;
}

.form-control,
.form-select {
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(42, 89, 52, 0.1);
}

.form-check-input:checked {
    background-color: var(--primary-green);
    border-color: var(--primary-green);
}

.form-check-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.form-check-label i {
    margin-right: 10px;
}

/* Payment Methods */
.payment-methods {
    margin-bottom: 25px;
}

#cardForm {
    background: var(--light-gray);
    padding: 25px;
    border-radius: 10px;
    border: 2px solid var(--beige);
}

/* Order Summary */
.order-summary {
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    position: sticky;
    top: 20px;
}

.summary-title {
    color: var(--primary-green);
    font-size: 1.3rem;
    font-weight: 600;
    margin-bottom: 25px;
    display: flex;
    align-items: center;
}

/* Cart Items in Summary */
.cart-items {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 10px;
}

.cart-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #eee;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item-img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    margin-right: 15px;
}

.cart-item-details {
    flex: 1;
}

.cart-item-name {
    font-weight: 600;
    font-size: 0.95rem;
    margin-bottom: 5px;
    color: var(--dark);
}

.cart-item-meta {
    font-size: 0.85rem;
    color: #666;
    margin: 0;
}

.cart-item-price {
    font-weight: 700;
    color: var(--primary-green);
    font-size: 1rem;
}

/* Price Breakdown */
.price-breakdown {
    border-top: 2px solid var(--cream);
    padding-top: 20px;
}

.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    font-size: 0.95rem;
}

.price-row.total {
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--dark);
    border-top: 2px solid var(--cream);
    margin-top: 10px;
    padding-top: 15px;
}

.total-price {
    color: var(--primary-green);
    font-size: 1.4rem;
}

/* Promo Code */
.promo-code .input-group {
    margin-top: 10px;
}

/* Security Badge */
.security-badge {
    text-align: center;
    padding: 20px;
    background: var(--cream);
    border-radius: 10px;
}

.security-icons {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 15px;
    font-size: 1.5rem;
    color: var(--primary-green);
}

.security-text {
    color: var(--primary-green);
    font-weight: 600;
    margin: 0;
    font-size: 0.9rem;
}

/* Buttons */
.btn-primary {
    background: linear-gradient(135deg, var(--primary-green), var(--light-green));
    border: none;
    padding: 12px 30px;
    font-weight: 600;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #23482d, #3a6647);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(42, 89, 52, 0.3);
}

.btn-outline-secondary {
    border: 2px solid #ddd;
    padding: 12px 30px;
    font-weight: 600;
    border-radius: 8px;
}

/* Loading Overlay */
.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255, 255, 255, 0.9);
    z-index: 9999;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.loading-spinner {
    width: 60px;
    height: 60px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid var(--primary-green);
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin-bottom: 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .checkout-steps {
        flex-wrap: wrap;
        gap: 20px;
    }
    
    .step {
        flex: 1;
        min-width: 100px;
    }
    
    .step-circle {
        width: 35px;
        height: 35px;
        font-size: 0.9rem;
    }
    
    .step-label {
        font-size: 0.8rem;
    }
    
    .checkout-form,
    .order-summary {
        padding: 20px;
    }
    
    .order-summary {
        position: static;
        margin-top: 20px;
    }
}

@media (max-width: 576px) {
    .checkout-header {
        padding: 20px 0;
    }
    
    .step-circle {
        width: 30px;
        height: 30px;
        font-size: 0.8rem;
    }
    
    .step-label {
        font-size: 0.7rem;
    }
    
    .section-title {
        font-size: 1.1rem;
    }
    
    .btn-primary,
    .btn-outline-secondary {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
}
    </style>
    
    <!-- Stripe.js for card validation -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <?php include_once 'includes/header.php'; ?>
    
    <div class="checkout-page">
        <!-- Checkout Header -->
        <div class="checkout-header">
            <div class="container">
                <div class="checkout-steps">
                    <div class="step active">
                        <div class="step-circle">1</div>
                        <div class="step-label">Cart</div>
                    </div>
                    <div class="step active">
                        <div class="step-circle">2</div>
                        <div class="step-label">Information</div>
                    </div>
                    <div class="step active">
                        <div class="step-circle">3</div>
                        <div class="step-label">Payment</div>
                    </div>
                    <div class="step">
                        <div class="step-circle">4</div>
                        <div class="step-label">Confirmation</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="container py-5">
            <?php if (isset($errors['general'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Left Column - Checkout Form -->
                <div class="col-lg-8 mb-4">
                    <div class="checkout-form">
                        <form method="POST" action="" id="checkoutForm">
                            <!-- Shipping Information -->
                            <div class="checkout-section">
                                <h3 class="section-title">
                                    <i class="fas fa-shipping-fast me-2"></i>Shipping Information
                                </h3>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="first_name" class="form-label">First Name *</label>
                                            <input type="text" 
                                                   class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>"
                                                   id="first_name" 
                                                   name="first_name"
                                                   value="<?php echo htmlspecialchars($_POST['first_name'] ?? $user_info['first_name'] ?? ''); ?>"
                                                   required>
                                            <?php if (isset($errors['first_name'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="last_name" class="form-label">Last Name *</label>
                                            <input type="text" 
                                                   class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>"
                                                   id="last_name" 
                                                   name="last_name"
                                                   value="<?php echo htmlspecialchars($_POST['last_name'] ?? $user_info['last_name'] ?? ''); ?>"
                                                   required>
                                            <?php if (isset($errors['last_name'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="email" class="form-label">Email Address *</label>
                                            <input type="email" 
                                                   class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                                   id="email" 
                                                   name="email"
                                                   value="<?php echo htmlspecialchars($_POST['email'] ?? $user_info['email'] ?? ''); ?>"
                                                   required>
                                            <?php if (isset($errors['email'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="phone" class="form-label">Phone Number *</label>
                                            <input type="tel" 
                                                   class="form-control <?php echo isset($errors['phone']) ? 'is-invalid' : ''; ?>"
                                                   id="phone" 
                                                   name="phone"
                                                   value="<?php echo htmlspecialchars($_POST['phone'] ?? $user_info['phone'] ?? ''); ?>"
                                                   required>
                                            <?php if (isset($errors['phone'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['phone']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label for="address" class="form-label">Address *</label>
                                            <input type="text" 
                                                   class="form-control <?php echo isset($errors['address']) ? 'is-invalid' : ''; ?>"
                                                   id="address" 
                                                   name="address"
                                                   value="<?php echo htmlspecialchars($_POST['address'] ?? $user_info['address'] ?? ''); ?>"
                                                   required>
                                            <?php if (isset($errors['address'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['address']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="city" class="form-label">City *</label>
                                            <input type="text" 
                                                   class="form-control <?php echo isset($errors['city']) ? 'is-invalid' : ''; ?>"
                                                   id="city" 
                                                   name="city"
                                                   value="<?php echo htmlspecialchars($_POST['city'] ?? $user_info['city'] ?? ''); ?>"
                                                   required>
                                            <?php if (isset($errors['city'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['city']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="state" class="form-label">State *</label>
                                            <input type="text" 
                                                   class="form-control <?php echo isset($errors['state']) ? 'is-invalid' : ''; ?>"
                                                   id="state" 
                                                   name="state"
                                                   value="<?php echo htmlspecialchars($_POST['state'] ?? $user_info['state'] ?? ''); ?>"
                                                   required>
                                            <?php if (isset($errors['state'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['state']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label for="zip_code" class="form-label">ZIP Code *</label>
                                            <input type="text" 
                                                   class="form-control <?php echo isset($errors['zip_code']) ? 'is-invalid' : ''; ?>"
                                                   id="zip_code" 
                                                   name="zip_code"
                                                   value="<?php echo htmlspecialchars($_POST['zip_code'] ?? $user_info['zip_code'] ?? ''); ?>"
                                                   required>
                                            <?php if (isset($errors['zip_code'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['zip_code']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="country" class="form-label">Country *</label>
                                            <select class="form-select <?php echo isset($errors['country']) ? 'is-invalid' : ''; ?>"
                                                    id="country" 
                                                    name="country"
                                                    required>
                                                <option value="">Select Country</option>
                                                <option value="US" <?php echo ($_POST['country'] ?? $user_info['country'] ?? '') == 'US' ? 'selected' : ''; ?>>United States</option>
                                                <option value="UK" <?php echo ($_POST['country'] ?? $user_info['country'] ?? '') == 'UK' ? 'selected' : ''; ?>>United Kingdom</option>
                                                <option value="CA" <?php echo ($_POST['country'] ?? $user_info['country'] ?? '') == 'CA' ? 'selected' : ''; ?>>Canada</option>
                                                <option value="AU" <?php echo ($_POST['country'] ?? $user_info['country'] ?? '') == 'AU' ? 'selected' : ''; ?>>Australia</option>
                                                <option value="DE" <?php echo ($_POST['country'] ?? $user_info['country'] ?? '') == 'DE' ? 'selected' : ''; ?>>Germany</option>
                                            </select>
                                            <?php if (isset($errors['country'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['country']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="checkout-section">
                                <h3 class="section-title">
                                    <i class="fas fa-credit-card me-2"></i>Payment Method
                                </h3>
                                
                                <div class="payment-methods">
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="payment_method" 
                                               id="visa" 
                                               value="visa"
                                               checked>
                                        <label class="form-check-label" for="visa">
                                            <i class="fab fa-cc-visa fa-2x me-2"></i>
                                            <span class="fw-bold">Visa / MasterCard</span>
                                        </label>
                                    </div>
                                    
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" 
                                               type="radio" 
                                               name="payment_method" 
                                               id="paypal" 
                                               value="paypal">
                                        <label class="form-check-label" for="paypal">
                                            <i class="fab fa-paypal fa-2x me-2"></i>
                                            <span>PayPal</span>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Credit Card Form -->
                                <div id="cardForm">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="card_name" class="form-label">Name on Card *</label>
                                                <input type="text" 
                                                       class="form-control <?php echo isset($errors['card_name']) ? 'is-invalid' : ''; ?>"
                                                       id="card_name" 
                                                       name="card_name"
                                                       value="<?php echo htmlspecialchars($_POST['card_name'] ?? ''); ?>"
                                                       placeholder="JOHN DOE">
                                                <?php if (isset($errors['card_name'])): ?>
                                                    <div class="invalid-feedback"><?php echo $errors['card_name']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="card_number" class="form-label">Card Number *</label>
                                                <div class="input-group">
                                                    <input type="text" 
                                                           class="form-control <?php echo isset($errors['card_number']) ? 'is-invalid' : ''; ?>"
                                                           id="card_number" 
                                                           name="card_number"
                                                           value="<?php echo htmlspecialchars($_POST['card_number'] ?? ''); ?>"
                                                           placeholder="4242 4242 4242 4242"
                                                           maxlength="19">
                                                    <span class="input-group-text">
                                                        <i class="fab fa-cc-visa"></i>
                                                    </span>
                                                </div>
                                                <?php if (isset($errors['card_number'])): ?>
                                                    <div class="invalid-feedback"><?php echo $errors['card_number']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="expiry_date" class="form-label">Expiry Date *</label>
                                                <input type="text" 
                                                       class="form-control <?php echo isset($errors['expiry_date']) ? 'is-invalid' : ''; ?>"
                                                       id="expiry_date" 
                                                       name="expiry_date"
                                                       value="<?php echo htmlspecialchars($_POST['expiry_date'] ?? ''); ?>"
                                                       placeholder="MM/YY"
                                                       maxlength="5">
                                                <?php if (isset($errors['expiry_date'])): ?>
                                                    <div class="invalid-feedback"><?php echo $errors['expiry_date']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="cvv" class="form-label">CVV *</label>
                                                <div class="input-group">
                                                    <input type="password" 
                                                           class="form-control <?php echo isset($errors['cvv']) ? 'is-invalid' : ''; ?>"
                                                           id="cvv" 
                                                           name="cvv"
                                                           value="<?php echo htmlspecialchars($_POST['cvv'] ?? ''); ?>"
                                                           placeholder="123"
                                                           maxlength="4">
                                                    <button class="btn btn-outline-secondary" type="button" id="toggleCVV">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                                <?php if (isset($errors['cvv'])): ?>
                                                    <div class="invalid-feedback"><?php echo $errors['cvv']; ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- PayPal Info (Hidden by default) -->
                                <div id="paypalInfo" style="display: none;">
                                    <div class="alert alert-info">
                                        <i class="fab fa-paypal me-2"></i>
                                        You will be redirected to PayPal to complete your payment after submitting this form.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Notes -->
                            <div class="checkout-section">
                                <h3 class="section-title">
                                    <i class="fas fa-sticky-note me-2"></i>Order Notes (Optional)
                                </h3>
                                <div class="form-group">
                                    <textarea class="form-control" 
                                              id="notes" 
                                              name="notes" 
                                              rows="3"
                                              placeholder="Any special instructions for delivery, gift wrapping, etc."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="terms" 
                                       name="terms"
                                       required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> *
                                </label>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="cart.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Cart
                                </a>
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-lock me-2"></i>Complete Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Right Column - Order Summary -->
                <div class="col-lg-4 mb-4">
                    <div class="order-summary">
                        <h3 class="summary-title">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </h3>
                        
                        <!-- Cart Items -->
                        <div class="cart-items mb-4">
                            <?php foreach ($_SESSION['cart'] as $product_id => $item): 
                                if (isset($item['product'])) {
                                    $product = $item['product'];
                                    $quantity = $item['quantity'] ?? 1;
                                } else {
                                    $product = $item;
                                    $quantity = $item['quantity'] ?? 1;
                                }
                                
                                if (is_array($product)):
                                    $product_name = $product['name'] ?? 'Product';
                                    $price = $product['price'] ?? 0;
                                    $image = $product['image'] ?? 'https://via.placeholder.com/80x80';
                            ?>
                            <div class="cart-item">
                                <img src="<?php echo htmlspecialchars($image); ?>" 
                                     alt="<?php echo htmlspecialchars($product_name); ?>"
                                     class="cart-item-img"
                                     onerror="this.src='https://via.placeholder.com/80x80/E8F5E9/2A5934?text=Flower'">
                                <div class="cart-item-details">
                                    <h6 class="cart-item-name"><?php echo htmlspecialchars($product_name); ?></h6>
                                    <p class="cart-item-meta">Qty: <?php echo $quantity; ?></p>
                                </div>
                                <div class="cart-item-price">
                                    $<?php echo number_format($price * $quantity, 2); ?>
                                </div>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                        
                        <!-- Price Breakdown -->
                        <div class="price-breakdown">
                            <div class="price-row">
                                <span>Subtotal (<?php echo $item_count; ?> items)</span>
                                <span>$<?php echo number_format($subtotal, 2); ?></span>
                            </div>
                            <div class="price-row">
                                <span>Shipping</span>
                                <span>
                                    <?php if ($shipping == 0): ?>
                                        <span class="text-success">FREE</span>
                                    <?php else: ?>
                                        $<?php echo number_format($shipping, 2); ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="price-row">
                                <span>Tax</span>
                                <span>$<?php echo number_format($tax, 2); ?></span>
                            </div>
                            <div class="price-row total">
                                <span>Total</span>
                                <span class="total-price">$<?php echo number_format($total, 2); ?></span>
                            </div>
                        </div>
                        
                        <!-- Promo Code -->
                        <div class="promo-code mt-4">
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       placeholder="Promo code"
                                       id="promoCode">
                                <button class="btn btn-outline-primary" type="button" id="applyPromo">
                                    Apply
                                </button>
                            </div>
                        </div>
                        
                        <!-- Security Badge -->
                        <div class="security-badge mt-4">
                            <div class="security-icons">
                                <i class="fas fa-lock"></i>
                                <i class="fas fa-shield-alt"></i>
                                <i class="fas fa-user-shield"></i>
                            </div>
                            <p class="security-text">Secure SSL Encryption</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Terms Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <h6>1. Order Acceptance</h6>
                    <p>Your order is subject to acceptance by RA-FLORA. We reserve the right to refuse or cancel any order for any reason.</p>
                    
                    <h6>2. Pricing and Payment</h6>
                    <p>All prices are in USD. We accept Visa, MasterCard, and PayPal. Payment must be received before order processing.</p>
                    
                    <h6>3. Shipping and Delivery</h6>
                    <p>Orders are processed within 1-2 business days. Delivery times vary by location. We are not responsible for delivery delays caused by carriers.</p>
                    
                    <h6>4. Returns and Refunds</h6>
                    <p>Fresh flowers are non-returnable. For damaged items, contact us within 24 hours of delivery.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <?php include_once 'includes/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>

                    // Checkout Form Validation and Processing
            document.addEventListener('DOMContentLoaded', function() {
                // Elements
                const checkoutForm = document.getElementById('checkoutForm');
                const submitBtn = document.getElementById('submitBtn');
                const visaRadio = document.getElementById('visa');
                const paypalRadio = document.getElementById('paypal');
                const cardForm = document.getElementById('cardForm');
                const paypalInfo = document.getElementById('paypalInfo');
                const cardNumberInput = document.getElementById('card_number');
                const expiryDateInput = document.getElementById('expiry_date');
                const cvvInput = document.getElementById('cvv');
                const toggleCVVBtn = document.getElementById('toggleCVV');
                const promoCodeInput = document.getElementById('promoCode');
                const applyPromoBtn = document.getElementById('applyPromo');
                
                // Toggle payment method forms
                function togglePaymentForms() {
                    if (visaRadio.checked) {
                        cardForm.style.display = 'block';
                        paypalInfo.style.display = 'none';
                    } else if (paypalRadio.checked) {
                        cardForm.style.display = 'none';
                        paypalInfo.style.display = 'block';
                    }
                }
                
                // Format card number
                function formatCardNumber(value) {
                    const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    const matches = v.match(/\d{4,16}/g);
                    const match = matches ? matches[0] : '';
                    const parts = [];
                    
                    for (let i = 0, len = match.length; i < len; i += 4) {
                        parts.push(match.substring(i, i + 4));
                    }
                    
                    if (parts.length) {
                        return parts.join(' ');
                    } else {
                        return value;
                    }
                }
                
                // Format expiry date
                function formatExpiryDate(value) {
                    const v = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
                    if (v.length >= 2) {
                        return v.substring(0, 2) + '/' + v.substring(2, 4);
                    }
                    return v;
                }
                
                // Validate card number
                function validateCardNumber(cardNumber) {
                    cardNumber = cardNumber.replace(/\s+/g, '');
                    return /^4[0-9]{12}(?:[0-9]{3})?$/.test(cardNumber);
                }
                
                // Validate expiry date
                function validateExpiryDate(expiryDate) {
                    if (!/^\d{2}\/\d{2}$/.test(expiryDate)) return false;
                    
                    const [month, year] = expiryDate.split('/').map(Number);
                    const currentYear = new Date().getFullYear() % 100;
                    const currentMonth = new Date().getMonth() + 1;
                    
                    if (month < 1 || month > 12) return false;
                    if (year < currentYear) return false;
                    if (year === currentYear && month < currentMonth) return false;
                    
                    return true;
                }
                
                // Validate CVV
                function validateCVV(cvv) {
                    return /^[0-9]{3,4}$/.test(cvv);
                }
                
                // Toggle CVV visibility
                function toggleCVVVisibility() {
                    if (cvvInput.type === 'password') {
                        cvvInput.type = 'text';
                        toggleCVVBtn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                    } else {
                        cvvInput.type = 'password';
                        toggleCVVBtn.innerHTML = '<i class="fas fa-eye"></i>';
                    }
                }
                
                // Apply promo code
                function applyPromoCode() {
                    const promoCode = promoCodeInput.value.trim().toUpperCase();
                    const validCodes = ['WELCOME10', 'FLOWER15', 'LOVE20'];
                    
                    if (!promoCode) {
                        alert('Please enter a promo code');
                        return;
                    }
                    
                    if (validCodes.includes(promoCode)) {
                        alert(`Promo code ${promoCode} applied successfully!`);
                        // In real implementation, update cart totals here
                        promoCodeInput.disabled = true;
                        applyPromoBtn.disabled = true;
                    } else {
                        alert('Invalid promo code. Please try again.');
                    }
                }
                
                // Validate form
                function validateForm() {
                    let isValid = true;
                    const errorMessages = [];
                    
                    // Check required fields
                    const requiredFields = checkoutForm.querySelectorAll('[required]');
                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.classList.add('is-invalid');
                            isValid = false;
                        } else {
                            field.classList.remove('is-invalid');
                        }
                    });
                    
                    // If Visa is selected, validate card details
                    if (visaRadio.checked) {
                        const cardNumber = cardNumberInput.value;
                        const expiryDate = expiryDateInput.value;
                        const cvv = cvvInput.value;
                        
                        if (!validateCardNumber(cardNumber)) {
                            cardNumberInput.classList.add('is-invalid');
                            errorMessages.push('Invalid Visa card number');
                            isValid = false;
                        } else {
                            cardNumberInput.classList.remove('is-invalid');
                        }
                        
                        if (!validateExpiryDate(expiryDate)) {
                            expiryDateInput.classList.add('is-invalid');
                            errorMessages.push('Invalid expiry date');
                            isValid = false;
                        } else {
                            expiryDateInput.classList.remove('is-invalid');
                        }
                        
                        if (!validateCVV(cvv)) {
                            cvvInput.classList.add('is-invalid');
                            errorMessages.push('Invalid CVV');
                            isValid = false;
                        } else {
                            cvvInput.classList.remove('is-invalid');
                        }
                    }
                    
                    // Check terms agreement
                    const termsCheckbox = document.getElementById('terms');
                    if (!termsCheckbox.checked) {
                        alert('Please agree to the Terms and Conditions');
                        isValid = false;
                    }
                    
                    if (!isValid) {
                        if (errorMessages.length > 0) {
                            alert('Please fix the following errors:\n\n' + errorMessages.join('\n'));
                        }
                    }
                    
                    return isValid;
                }
                
                // Show loading state
                function showLoading() {
                    const loadingOverlay = document.createElement('div');
                    loadingOverlay.className = 'loading-overlay';
                    loadingOverlay.innerHTML = `
                        <div class="loading-spinner"></div>
                        <h4>Processing Payment...</h4>
                        <p>Please don't close this window</p>
                    `;
                    document.body.appendChild(loadingOverlay);
                    
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                }
                
                // Event Listeners
                
                // Toggle payment forms
                visaRadio.addEventListener('change', togglePaymentForms);
                paypalRadio.addEventListener('change', togglePaymentForms);
                
                // Format card number on input
                cardNumberInput.addEventListener('input', function() {
                    this.value = formatCardNumber(this.value);
                });
                
                // Format expiry date on input
                expiryDateInput.addEventListener('input', function() {
                    this.value = formatExpiryDate(this.value);
                });
                
                // Toggle CVV visibility
                toggleCVVBtn.addEventListener('click', toggleCVVVisibility);
                
                // Apply promo code
                applyPromoBtn.addEventListener('click', applyPromoCode);
                promoCodeInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        applyPromoCode();
                    }
                });
                
                // Form submission
                checkoutForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    if (!validateForm()) {
                        return false;
                    }
                    
                    // Show loading state
                    showLoading();
                    
                    // In real implementation, here you would:
                    // 1. Create payment intent with Stripe or other gateway
                    // 2. Process the payment
                    // 3. Handle success/failure
                    
                    // Simulate processing delay
                    setTimeout(() => {
                        // For demo purposes, we'll simulate a successful payment
                        // In production, remove this and use real payment processing
                        
                        console.log('Processing payment...');
                        console.log('Payment method:', visaRadio.checked ? 'Visa' : 'PayPal');
                        
                        // Submit the form after delay
                        checkoutForm.submit();
                    }, 2000);
                });
                
                // Initialize
                togglePaymentForms();
                
                // Real-time validation for card fields
                cardNumberInput.addEventListener('blur', function() {
                    if (this.value && !validateCardNumber(this.value)) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
                
                expiryDateInput.addEventListener('blur', function() {
                    if (this.value && !validateExpiryDate(this.value)) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
                
                cvvInput.addEventListener('blur', function() {
                    if (this.value && !validateCVV(this.value)) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
                
                // Auto-detect card type
                cardNumberInput.addEventListener('input', function() {
                    const cardNumber = this.value.replace(/\s+/g, '');
                    const cardTypeIcon = this.parentNode.querySelector('.input-group-text i');
                    
                    if (/^4/.test(cardNumber)) {
                        cardTypeIcon.className = 'fab fa-cc-visa';
                        cardTypeIcon.style.color = '#1a1f71';
                    } else if (/^5[1-5]/.test(cardNumber)) {
                        cardTypeIcon.className = 'fab fa-cc-mastercard';
                        cardTypeIcon.style.color = '#eb001b';
                    } else {
                        cardTypeIcon.className = 'far fa-credit-card';
                        cardTypeIcon.style.color = '#666';
                    }
                });
                
                // Auto-tab for expiry date
                expiryDateInput.addEventListener('input', function() {
                    const value = this.value;
                    if (value.length === 2 && !value.includes('/')) {
                        this.value = value + '/';
                    }
                });
            });
    </script>
</body>
</html>