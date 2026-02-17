<?php
// payment_qr.php - QR Code Payment Processing
session_start();
require_once 'includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['checkout_redirect'] = 'payment_qr.php?bank=' . ($_GET['bank'] ?? '');
    header('Location: user/login.php');
    exit();
}

// Check if order data exists
if (!isset($_SESSION['order_data']) || empty($_SESSION['order_data'])) {
    header('Location: cart.php');
    exit();
}

$bank = $_GET['bank'] ?? '';
$valid_banks = ['aba', 'acleda'];

if (!in_array($bank, $valid_banks)) {
    header('Location: cart.php');
    exit();
}

$order_data = $_SESSION['order_data'];
$order_total = $order_data['total'];

// Bank information based on your images
$bank_info = [
    'aba' => [
        'name' => 'ABA Bank',
        'logo' => 'assets/images/banks/aba-logo.png',
        'account_name' => 'SOPHA SET',
        'account_khr' => '011 467 401',
        'account_usd' => '011 467 400',
        'qr_image' => 'assets/images/qr/aba-qr.jpg',
        'color' => '#0033A0',
        'hotline' => '023 994444'
    ],
    'acleda' => [
        'name' => 'ACLEDA Bank',
        'logo' => 'assets/images/banks/acleda-logo.png',
        'account_name' => 'SET SOPHA',
        'account_number' => '011 467 400',
        'qr_image' => 'assets/images/qr/acleda-qr.jpg',
        'color' => '#1A4D7E',
        'hotline' => '023 999 888'
    ]
];

$current_bank = $bank_info[$bank];

// Handle payment confirmation via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment'])) {
    $order_id = intval($_POST['order_id']);
    
    // Begin transaction
    mysqli_begin_transaction($conn);
    
    try {
        // Generate unique order number
        $order_number = 'QR' . time() . rand(100, 999);
        
        // Insert order
        $order_query = "INSERT INTO orders (user_id, order_number, total_amount, subtotal, shipping, tax, discount, payment_method, payment_status, status, created_at) 
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'pending', NOW())";
        
        $order_stmt = mysqli_prepare($conn, $order_query);
        $payment_method = $bank === 'aba' ? 'ABA QR' : 'ACLEDA QR';
        mysqli_stmt_bind_param($order_stmt, "isddddds", 
            $_SESSION['user_id'],
            $order_number,
            $order_data['total'],
            $order_data['subtotal'],
            $order_data['shipping'],
            $order_data['tax'],
            $order_data['discount'],
            $payment_method
        );
        
        mysqli_stmt_execute($order_stmt);
        $new_order_id = mysqli_insert_id($conn);
        
        // Insert order items
        foreach ($order_data['items'] as $item) {
            $item_query = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $item_stmt = mysqli_prepare($conn, $item_query);
            $subtotal = $item['price'] * $item['quantity'];
            mysqli_stmt_bind_param($item_stmt, "iisidd", 
                $new_order_id,
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
        
        // Return success response
        echo json_encode(['success' => true, 'order_id' => $new_order_id]);
        exit();
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode(['success' => false, 'message' => 'Payment confirmation failed']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $current_bank['name']; ?> QR Payment - LA FLORA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bank-color: <?php echo $current_bank['color']; ?>;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .qr-payment-card {
            max-width: 600px;
            width: 100%;
            background: white;
            border-radius: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bank-header {
            background: linear-gradient(135deg, var(--bank-color), #1a2a3a);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .bank-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .bank-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            padding: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .bank-logo img {
            max-width: 100%;
            max-height: 100%;
        }

        .bank-header h2 {
            font-weight: 700;
            margin-bottom: 10px;
            position: relative;
        }

        .bank-header p {
            opacity: 0.9;
            margin-bottom: 0;
            position: relative;
        }

        .qr-content {
            padding: 40px;
        }

        .order-summary {
            background: linear-gradient(135deg, #f8f9fa, white);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
            text-align: center;
        }

        .order-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--bank-color);
            margin-bottom: 10px;
        }

        .amount {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--bank-color);
            margin: 15px 0;
        }

        .qr-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .qr-wrapper {
            display: inline-block;
            padding: 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 2px solid var(--bank-color);
            transition: transform 0.3s;
        }

        .qr-wrapper:hover {
            transform: scale(1.02);
        }

        .qr-image {
            width: 250px;
            height: 250px;
            object-fit: contain;
        }

        .bank-details {
            background: #f8f9fa;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px dashed #dee2e6;
        }

        .detail-item:last-child {
            border-bottom: none;
        }

        .detail-label {
            color: #666;
            font-weight: 500;
        }

        .detail-value {
            font-weight: 600;
            color: var(--bank-color);
        }

        .instruction-box {
            background: #e8f4fd;
            border-left: 4px solid var(--bank-color);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .instruction-box h6 {
            color: var(--bank-color);
            margin-bottom: 15px;
            font-weight: 600;
        }

        .instruction-box ol {
            margin-bottom: 0;
            padding-left: 20px;
        }

        .instruction-box li {
            margin-bottom: 10px;
            color: #495057;
        }

        .timer-section {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #fff3cd, #fff9e6);
            border-radius: 50px;
        }

        .timer {
            font-size: 3rem;
            font-weight: 800;
            color: #dc3545;
            font-family: 'Courier New', monospace;
        }

        .timer-label {
            color: #856404;
            font-weight: 500;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-confirm {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            flex: 1;
        }

        .btn-confirm:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(40, 167, 69, 0.3);
        }

        .btn-cancel {
            background: white;
            color: #6c757d;
            border: 2px solid #dee2e6;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            text-decoration: none;
            flex: 1;
            text-align: center;
        }

        .btn-cancel:hover {
            background: #f8f9fa;
            color: #495057;
        }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 50px;
            color: #666;
            font-size: 0.9rem;
        }

        .hotline {
            color: var(--bank-color);
            font-weight: 600;
        }

        @media (max-width: 576px) {
            .qr-content {
                padding: 25px;
            }

            .qr-image {
                width: 200px;
                height: 200px;
            }

            .amount {
                font-size: 2rem;
            }

            .timer {
                font-size: 2rem;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="qr-payment-card">
        <div class="bank-header">
            <div class="bank-logo">
                <img src="<?php echo $current_bank['logo']; ?>" 
                     alt="<?php echo $current_bank['name']; ?>"
                     onerror="this.style.display='none'; this.parentElement.innerHTML='<i class=\'fas fa-university\' style=\'font-size: 2.5rem; color: var(--bank-color);\'></i>'">
            </div>
            <h2><?php echo $current_bank['name']; ?></h2>
            <p>Scan QR code to pay with your mobile banking app</p>
        </div>
        
        <div class="qr-content">
            <!-- Order Summary -->
            <div class="order-summary">
                <div class="order-number">
                    <i class="fas fa-receipt me-2"></i>
                    Order #<?php echo 'ORD' . time(); ?>
                </div>
                <div class="amount">
                    $<?php echo number_format($order_total, 2); ?>
                </div>
                <div class="text-muted">
                    <?php echo $order_data['item_count']; ?> item(s) • Including tax & shipping
                </div>
            </div>
            
            <!-- QR Code -->
            <div class="qr-container">
                <div class="qr-wrapper">
                    <img src="<?php echo $current_bank['qr_image']; ?>" 
                         alt="<?php echo $current_bank['name']; ?> QR Code" 
                         class="qr-image"
                         onerror="this.src='https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode($current_bank['name'] . ' - Amount: $' . $order_total); ?>'">
                </div>
            </div>
            
            <!-- Bank Account Details -->
            <div class="bank-details">
                <h6 class="mb-3"><i class="fas fa-university me-2"></i>Account Details</h6>
                
                <?php if ($bank === 'aba'): ?>
                <div class="detail-item">
                    <span class="detail-label">Account Name</span>
                    <span class="detail-value"><?php echo $current_bank['account_name']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">KHR Account</span>
                    <span class="detail-value"><?php echo $current_bank['account_khr']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">USD Account</span>
                    <span class="detail-value"><?php echo $current_bank['account_usd']; ?></span>
                </div>
                <?php else: ?>
                <div class="detail-item">
                    <span class="detail-label">Account Name</span>
                    <span class="detail-value"><?php echo $current_bank['account_name']; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Account Number</span>
                    <span class="detail-value"><?php echo $current_bank['account_number']; ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-item">
                    <span class="detail-label">Amount</span>
                    <span class="detail-value">$<?php echo number_format($order_total, 2); ?></span>
                </div>
            </div>
            
            <!-- Payment Instructions -->
            <div class="instruction-box">
                <h6><i class="fas fa-info-circle me-2"></i>How to pay:</h6>
                <ol>
                    <li>Open your <strong><?php echo $current_bank['name']; ?></strong> mobile app</li>
                    <li>Select <strong>"Scan & Pay"</strong> or <strong>"QR Payment"</strong></li>
                    <li>Scan the QR code above</li>
                    <li>Verify account name: <strong><?php echo $current_bank['account_name']; ?></strong></li>
                    <li>Enter amount: <strong>$<?php echo number_format($order_total, 2); ?></strong></li>
                    <li>Confirm payment with your PIN or fingerprint</li>
                </ol>
            </div>
            
            <!-- Timer -->
            <div class="timer-section">
                <div class="timer" id="countdown">15:00</div>
                <div class="timer-label">Time remaining to complete payment</div>
            </div>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="btn-confirm" onclick="confirmPayment()">
                    <i class="fas fa-check-circle me-2"></i>I Have Paid
                </button>
                <a href="cart.php" class="btn-cancel">
                    <i class="fas fa-times-circle me-2"></i>Cancel
                </a>
            </div>
            
            <!-- Support & Security -->
            <div class="secure-badge">
                <i class="fas fa-headset"></i>
                <span>Need help? Call </span>
                <span class="hotline"><?php echo $current_bank['hotline']; ?></span>
            </div>
            
            <div class="secure-badge">
                <i class="fas fa-lock"></i>
                <span>256-bit SSL Encrypted</span>
                <i class="fas fa-shield-alt"></i>
                <span>PCI DSS Compliant</span>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Countdown timer (15 minutes)
        let timeLeft = 15 * 60;
        const countdownElement = document.getElementById('countdown');
        
        function updateTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            
            if (timeLeft > 0) {
                timeLeft--;
                setTimeout(updateTimer, 1000);
            } else {
                countdownElement.textContent = "Expired";
                countdownElement.style.color = '#dc3545';
                document.querySelector('.btn-confirm').disabled = true;
                document.querySelector('.btn-confirm').style.opacity = '0.5';
            }
        }
        
        updateTimer();
        
        function confirmPayment() {
            if (timeLeft <= 0) {
                alert('Payment time has expired. Please try again.');
                return;
            }
            
            if (confirm('Have you completed the payment? Please verify before confirming.')) {
                // Show loading state
                const btn = document.querySelector('.btn-confirm');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Verifying...';
                btn.disabled = true;
                
                // Send confirmation
                fetch('payment_qr.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'confirm_payment=1&order_id=0'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'order_confirmation.php?order_id=' + data.order_id;
                    } else {
                        alert('Payment verification failed. Please contact support.');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                });
            }
        }
        
        // Warn user before leaving
        window.addEventListener('beforeunload', function(e) {
            if (timeLeft > 0) {
                e.preventDefault();
                e.returnValue = 'Payment in progress. Are you sure you want to leave?';
            }
        });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>