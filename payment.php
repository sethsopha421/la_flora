<?php
// payment.php - Credit/Debit Card Payment Processing
session_start();
require_once 'includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['checkout_redirect'] = 'payment.php';
    header('Location: user/login.php');
    exit();
}

// Check if order data exists
if (!isset($_SESSION['order_data']) || empty($_SESSION['order_data'])) {
    header('Location: cart.php');
    exit();
}

$order_data = $_SESSION['order_data'];
$error = '';
$success = '';

// Handle payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_card_payment'])) {
    // Get and validate form data
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $card_name = trim($_POST['card_name'] ?? '');
    $expiry_month = $_POST['expiry_month'] ?? '';
    $expiry_year = $_POST['expiry_year'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    
    // Shipping details
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $shipping_city = trim($_POST['shipping_city'] ?? '');
    $shipping_zip = trim($_POST['shipping_zip'] ?? '');
    $shipping_phone = trim($_POST['shipping_phone'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($card_number) || strlen($card_number) < 13 || strlen($card_number) > 19) {
        $errors[] = "Invalid card number.";
    }
    if (empty($card_name)) {
        $errors[] = "Please enter cardholder name.";
    }
    if (empty($expiry_month) || empty($expiry_year)) {
        $errors[] = "Please enter expiry date.";
    }
    if (empty($cvv) || !preg_match('/^\d{3,4}$/', $cvv)) {
        $errors[] = "Invalid CVV.";
    }
    
    // Validate expiry date
    $current_year = date('Y');
    $current_month = date('m');
    if ($expiry_year < $current_year || ($expiry_year == $current_year && $expiry_month < $current_month)) {
        $errors[] = "Card has expired.";
    }
    
    // Validate shipping details
    if (empty($shipping_address)) {
        $errors[] = "Please enter shipping address.";
    }
    if (empty($shipping_city)) {
        $errors[] = "Please enter city.";
    }
    if (empty($shipping_zip)) {
        $errors[] = "Please enter ZIP code.";
    }
    if (empty($shipping_phone)) {
        $errors[] = "Please enter phone number.";
    }
    
    if (empty($errors)) {
        // Begin transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Generate unique order number
            $order_number = 'ORD' . time() . rand(100, 999);
            
            // Insert order
            $order_query = "INSERT INTO orders (user_id, order_number, total_amount, subtotal, shipping, tax, discount, payment_method, payment_status, status, shipping_address, shipping_city, shipping_zip, phone, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'credit_card', 'completed', 'processing', ?, ?, ?, ?, NOW())";
            
            $order_stmt = mysqli_prepare($conn, $order_query);
            mysqli_stmt_bind_param($order_stmt, "isdddddsssss", 
                $_SESSION['user_id'],
                $order_number,
                $order_data['total'],
                $order_data['subtotal'],
                $order_data['shipping'],
                $order_data['tax'],
                $order_data['discount'],
                $shipping_address,
                $shipping_city,
                $shipping_zip,
                $shipping_phone
            );
            
            mysqli_stmt_execute($order_stmt);
            $order_id = mysqli_insert_id($conn);
            
            // Insert order items
            foreach ($order_data['items'] as $item) {
                $item_query = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) 
                              VALUES (?, ?, ?, ?, ?, ?)";
                $item_stmt = mysqli_prepare($conn, $item_query);
                $subtotal = $item['price'] * $item['quantity'];
                mysqli_stmt_bind_param($item_stmt, "iisidd", 
                    $order_id,
                    $item['id'],
                    $item['name'],
                    $item['quantity'],
                    $item['price'],
                    $subtotal
                );
                mysqli_stmt_execute($item_stmt);
                
                // Update product stock
                $stock_query = "UPDATE products SET stock = stock - ? WHERE id = ?";
                $stock_stmt = mysqli_prepare($conn, $stock_query);
                mysqli_stmt_bind_param($stock_stmt, "ii", $item['quantity'], $item['id']);
                mysqli_stmt_execute($stock_stmt);
            }
            
            // Clear cart
            $clear_query = "DELETE FROM cart WHERE user_id = ?";
            $clear_stmt = mysqli_prepare($conn, $clear_query);
            mysqli_stmt_bind_param($clear_stmt, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($clear_stmt);
            
            // Commit transaction
            mysqli_commit($conn);
            
            // Clear session data
            unset($_SESSION['order_data']);
            unset($_SESSION['coupon']);
            
            // Store order ID for confirmation
            $_SESSION['last_order_id'] = $order_id;
            
            // Redirect to success page
            header('Location: order_confirmation.php?order_id=' . $order_id);
            exit();
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Payment processing failed. Please try again.";
        }
    } else {
        $error = implode(' ', $errors);
    }
}

// Get user details for pre-filling
$user_query = "SELECT name, email, phone, address, city, zip_code FROM users WHERE id = ?";
$user_stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($user_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($user_stmt);
$user_result = mysqli_stmt_get_result($user_stmt);
$user = mysqli_fetch_assoc($user_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Payment - LA FLORA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A5C4A;
            --primary-dark: #1E4033;
            --primary-light: #E8F3E8;
            --secondary: #D4A373;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            padding: 40px 0;
            font-family: 'Segoe UI', sans-serif;
        }

        .payment-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .payment-card {
            background: white;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .payment-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 40px;
            text-align: center;
        }

        .payment-header h2 {
            font-weight: 700;
            margin-bottom: 10px;
        }

        .payment-header p {
            opacity: 0.9;
            margin-bottom: 0;
        }

        .payment-body {
            padding: 40px;
        }

        /* Card Preview */
        .card-preview {
            background: linear-gradient(135deg, #1a1e2b, #2d3349);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
            min-height: 240px;
        }

        .card-preview::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect width="100" height="100" fill="none"/><path d="M10,50 L90,50 M50,10 L50,90" stroke="rgba(255,255,255,0.05)" stroke-width="2"/></svg>');
            opacity: 0.3;
        }

        .chip-icon {
            width: 50px;
            height: 40px;
            background: linear-gradient(135deg, #d4af37, #b8860b);
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .card-number-display {
            font-size: 1.8rem;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
            margin: 20px 0;
        }

        .card-name-display {
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 20px;
        }

        .card-expiry {
            position: absolute;
            bottom: 30px;
            right: 30px;
            text-align: right;
        }

        .expiry-label {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .expiry-value {
            font-size: 1.3rem;
            font-weight: 600;
        }

        .card-brand {
            position: absolute;
            top: 30px;
            right: 30px;
            font-size: 2rem;
            opacity: 0.8;
        }

        /* Form Sections */
        .form-section {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .form-section h5 {
            color: var(--primary);
            margin-bottom: 25px;
            font-weight: 600;
        }

        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(42, 92, 74, 0.1);
        }

        .input-group-custom {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary);
            font-size: 1.2rem;
            pointer-events: none;
        }

        /* Order Summary */
        .order-summary {
            background: white;
            border: 2px solid var(--primary-light);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed #e9ecef;
        }

        .summary-row.total {
            border-bottom: none;
            padding-top: 15px;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            border-top: 2px solid var(--secondary);
            margin-top: 10px;
        }

        /* Button */
        .btn-pay {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            padding: 18px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 15px;
            width: 100%;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .btn-pay:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(42, 92, 74, 0.3);
        }

        .btn-pay:active {
            transform: translateY(0);
        }

        /* CVV Tooltip */
        .cvv-hint {
            position: relative;
            display: inline-block;
            margin-left: 5px;
        }

        .cvv-hint-icon {
            color: var(--primary);
            cursor: help;
        }

        .cvv-tooltip {
            position: absolute;
            background: var(--primary-dark);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 0.85rem;
            width: 250px;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: none;
            z-index: 1000;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .cvv-tooltip::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            border-width: 10px 10px 0;
            border-style: solid;
            border-color: var(--primary-dark) transparent transparent;
        }

        .cvv-hint:hover .cvv-tooltip {
            display: block;
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 15px;
            background: #e8f5e9;
            border-radius: 12px;
            margin-top: 20px;
            color: #2e7d32;
        }

        .back-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: var(--primary-dark);
        }

        @media (max-width: 768px) {
            .payment-body {
                padding: 25px;
            }

            .card-preview {
                padding: 20px;
                min-height: 200px;
            }

            .card-number-display {
                font-size: 1.3rem;
            }

            .card-name-display {
                font-size: 1rem;
            }

            .form-section {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="payment-wrapper">
        <div class="payment-card">
            <div class="payment-header">
                <h2><i class="fas fa-lock me-2"></i>Secure Card Payment</h2>
                <p>Your payment information is encrypted with 256-bit SSL</p>
            </div>
            
            <div class="payment-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Order Summary -->
                <div class="order-summary">
                    <h5 class="mb-3"><i class="fas fa-receipt me-2"></i>Order Summary</h5>
                    <div class="summary-row">
                        <span>Subtotal (<?php echo $order_data['item_count']; ?> items)</span>
                        <span>$<?php echo number_format($order_data['subtotal'], 2); ?></span>
                    </div>
                    <?php if ($order_data['discount'] > 0): ?>
                    <div class="summary-row text-success">
                        <span>Discount</span>
                        <span>-$<?php echo number_format($order_data['discount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span><?php echo $order_data['shipping'] > 0 ? '$' . number_format($order_data['shipping'], 2) : 'FREE'; ?></span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (8%)</span>
                        <span>$<?php echo number_format($order_data['tax'], 2); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total to Pay</span>
                        <span>$<?php echo number_format($order_data['total'], 2); ?></span>
                    </div>
                </div>
                
                <!-- Card Preview -->
                <div class="card-preview">
                    <div class="chip-icon"></div>
                    <div class="card-brand">
                        <i class="fab fa-cc-visa"></i>
                    </div>
                    
                    <div class="card-number-display" id="cardNumberDisplay">
                        **** **** **** ****
                    </div>
                    
                    <div class="card-name-display" id="cardNameDisplay">
                        CARDHOLDER NAME
                    </div>
                    
                    <div class="card-expiry">
                        <div class="expiry-label">EXPIRES</div>
                        <div class="expiry-value" id="expiryDisplay">MM/YY</div>
                    </div>
                </div>
                
                <!-- Payment Form -->
                <form method="post" id="paymentForm" novalidate>
                    <!-- Card Details -->
                    <div class="form-section">
                        <h5><i class="fas fa-credit-card me-2"></i>Card Details</h5>
                        
                        <div class="mb-4">
                            <label class="form-label">Card Number</label>
                            <div class="input-group-custom">
                                <input type="text" 
                                       class="form-control" 
                                       name="card_number"
                                       id="cardNumber"
                                       placeholder="1234 5678 9012 3456"
                                       maxlength="19"
                                       value="<?php echo isset($_POST['card_number']) ? htmlspecialchars($_POST['card_number']) : ''; ?>"
                                       required>
                                <i class="fas fa-credit-card input-icon"></i>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label">Cardholder Name</label>
                            <input type="text" 
                                   class="form-control" 
                                   name="card_name"
                                   id="cardName"
                                   placeholder="As shown on card"
                                   value="<?php echo isset($_POST['card_name']) ? htmlspecialchars($_POST['card_name']) : ($user['name'] ?? ''); ?>"
                                   required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Expiry Date</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <select class="form-select" name="expiry_month" id="expiryMonth" required>
                                            <option value="">MM</option>
                                            <?php for($i=1; $i<=12; $i++): ?>
                                                <option value="<?php echo sprintf('%02d', $i); ?>" 
                                                    <?php echo (isset($_POST['expiry_month']) && $_POST['expiry_month'] == sprintf('%02d', $i)) ? 'selected' : ''; ?>>
                                                    <?php echo sprintf('%02d', $i); ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select class="form-select" name="expiry_year" id="expiryYear" required>
                                            <option value="">YYYY</option>
                                            <?php for($i=date('Y'); $i<=date('Y')+10; $i++): ?>
                                                <option value="<?php echo $i; ?>"
                                                    <?php echo (isset($_POST['expiry_year']) && $_POST['expiry_year'] == $i) ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    CVV
                                    <span class="cvv-hint">
                                        <i class="fas fa-question-circle cvv-hint-icon"></i>
                                        <span class="cvv-tooltip">
                                            <strong>CVV Number</strong><br>
                                            3-digit code on back of card<br>
                                            4-digit for American Express
                                        </span>
                                    </span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       name="cvv"
                                       placeholder="123"
                                       maxlength="4"
                                       pattern="\d{3,4}"
                                       value="<?php echo isset($_POST['cvv']) ? htmlspecialchars($_POST['cvv']) : ''; ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Shipping Address -->
                    <div class="form-section">
                        <h5><i class="fas fa-shipping-fast me-2"></i>Shipping Address</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="shipping_address" 
                                   value="<?php echo isset($_POST['shipping_address']) ? htmlspecialchars($_POST['shipping_address']) : ($user['address'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">City</label>
                                <input type="text" class="form-control" name="shipping_city"
                                       value="<?php echo isset($_POST['shipping_city']) ? htmlspecialchars($_POST['shipping_city']) : ($user['city'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" name="shipping_zip"
                                       value="<?php echo isset($_POST['shipping_zip']) ? htmlspecialchars($_POST['shipping_zip']) : ($user['zip_code'] ?? ''); ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="shipping_phone"
                                   value="<?php echo isset($_POST['shipping_phone']) ? htmlspecialchars($_POST['shipping_phone']) : ($user['phone'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="process_card_payment" class="btn-pay">
                        <i class="fas fa-lock me-2"></i>Pay $<?php echo number_format($order_data['total'], 2); ?>
                    </button>
                    
                    <div class="secure-badge">
                        <i class="fas fa-shield-alt fa-2x"></i>
                        <div>
                            <strong>256-bit SSL Encryption</strong><br>
                            <small>PCI DSS Compliant</small>
                        </div>
                    </div>
                </form>
                
                <div class="text-center mt-4">
                    <a href="cart.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Cart
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cardNumber = document.getElementById('cardNumber');
            const cardName = document.getElementById('cardName');
            const expiryMonth = document.getElementById('expiryMonth');
            const expiryYear = document.getElementById('expiryYear');
            
            // Card number formatting and preview
            if (cardNumber) {
                cardNumber.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
                    let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                    e.target.value = formattedValue;
                    
                    // Update card preview
                    const display = document.getElementById('cardNumberDisplay');
                    if (formattedValue) {
                        display.textContent = formattedValue;
                    } else {
                        display.textContent = '**** **** **** ****';
                    }
                    
                    // Limit to 16 digits
                    if (value.length > 16) {
                        e.target.value = value.substring(0, 16).match(/.{1,4}/g).join(' ');
                    }
                });
            }
            
            // Cardholder name preview
            if (cardName) {
                cardName.addEventListener('input', function(e) {
                    const display = document.getElementById('cardNameDisplay');
                    if (e.target.value) {
                        display.textContent = e.target.value.toUpperCase();
                    } else {
                        display.textContent = 'CARDHOLDER NAME';
                    }
                });
            }
            
            // Expiry date preview
            function updateExpiry() {
                const display = document.getElementById('expiryDisplay');
                const month = expiryMonth.value;
                const year = expiryYear.value;
                
                if (month && year) {
                    display.textContent = `${month}/${year.slice(-2)}`;
                } else {
                    display.textContent = 'MM/YY';
                }
            }
            
            if (expiryMonth) expiryMonth.addEventListener('change', updateExpiry);
            if (expiryYear) expiryYear.addEventListener('change', updateExpiry);
            
            // CVV validation
            document.querySelector('input[name="cvv"]').addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/\D/g, '');
            });
            
            // Form validation
            document.getElementById('paymentForm').addEventListener('submit', function(e) {
                const cardNum = cardNumber.value.replace(/\s/g, '');
                const cvv = document.querySelector('input[name="cvv"]').value;
                
                if (cardNum.length < 13 || cardNum.length > 19) {
                    e.preventDefault();
                    alert('Card number must be between 13 and 19 digits.');
                    return false;
                }
                
                if (cvv.length < 3 || cvv.length > 4) {
                    e.preventDefault();
                    alert('CVV must be 3 or 4 digits.');
                    return false;
                }
                
                return true;
            });
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>