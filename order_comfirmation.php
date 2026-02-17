<?php
// order_confirmation.php
session_start();
require_once 'includes/database.php';

// Check if order exists
if (!isset($_GET['order_id']) && !isset($_SESSION['last_order_id'])) {
    header("Location: index.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : ($_SESSION['last_order_id'] ?? 0);

// Fetch order details
$order_query = "SELECT o.*, u.name as customer_name, u.email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?";
$order_stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($order_stmt, "i", $order_id);
mysqli_stmt_execute($order_stmt);
$order_result = mysqli_stmt_get_result($order_stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header("Location: index.php");
    exit();
}

// Fetch order items
$items_query = "SELECT * FROM order_items WHERE order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);
$order_items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $order_items[] = $item;
}

// Clear order session after displaying
unset($_SESSION['last_order_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - LA FLORA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A5C4A;
            --primary-dark: #1E4033;
            --primary-light: #E8F3E8;
            --secondary: #D4A373;
            --success: #2A9D8F;
            --warning: #F4A261;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .confirmation-wrapper {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .confirmation-card {
            background: white;
            border-radius: 30px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            max-width: 800px;
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .confirmation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 10px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }

        .success-animation {
            text-align: center;
            margin-bottom: 30px;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, var(--success), #34a853);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3rem;
            color: white;
            box-shadow: 0 10px 30px rgba(46, 204, 113, 0.3);
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            0% { transform: scale(0); opacity: 0; }
            70% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }

        .order-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .order-header h1 {
            color: var(--primary-dark);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .order-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .order-info {
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }

        .info-label {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
        }

        .order-number {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 15px 30px;
            border-radius: 50px;
            display: inline-block;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(42, 92, 74, 0.2);
        }

        .items-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            margin-bottom: 30px;
            border: 1px solid #e9ecef;
        }

        .items-table table {
            margin-bottom: 0;
        }

        .items-table th {
            background: var(--primary-light);
            color: var(--primary-dark);
            font-weight: 600;
            border: none;
            padding: 15px;
        }

        .items-table td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }

        .items-table tr:last-child td {
            border-bottom: none;
        }

        .total-row {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            border-top: 2px solid var(--secondary);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border: none;
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(42, 92, 74, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(42, 92, 74, 0.3);
        }

        .btn-outline {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 15px 40px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-outline:hover {
            background: var(--primary-light);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        @media (max-width: 768px) {
            .confirmation-card {
                padding: 30px;
            }

            .order-number {
                font-size: 1.2rem;
                padding: 12px 20px;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn-primary, .btn-outline {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="confirmation-wrapper">
        <div class="confirmation-card">
            <div class="success-animation">
                <div class="success-icon">
                    <i class="fas fa-check"></i>
                </div>
                <h1 class="display-6" style="color: var(--primary);">Thank You For Your Order!</h1>
                <p class="text-muted">Your order has been placed successfully and is being processed.</p>
            </div>
            
            <div class="order-number text-center">
                Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?>
            </div>
            
            <div class="order-info">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Order Date</div>
                        <div class="info-value"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value"><?php echo strtoupper($order['payment_method']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Status</div>
                        <div class="info-value">
                            <span class="badge bg-<?php echo $order['payment_status'] === 'completed' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Order Status</div>
                        <div class="info-value">
                            <span class="badge bg-<?php 
                                echo $order['status'] === 'pending' ? 'warning' : 
                                    ($order['status'] === 'processing' ? 'info' : 
                                    ($order['status'] === 'completed' ? 'success' : 'secondary')); 
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($order['customer_name'])): ?>
                <div class="text-center mt-3">
                    <p class="mb-0">
                        <i class="fas fa-envelope me-2"></i>
                        A confirmation email has been sent to <strong><?php echo htmlspecialchars($order['email']); ?></strong>
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Items -->
            <?php if (!empty($order_items)): ?>
            <div class="items-table">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Quantity</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($order_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-end">$<?php echo number_format($item['price'], 2); ?></td>
                            <td class="text-end">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3" class="text-end">Total Amount:</td>
                            <td class="text-end">$<?php echo number_format($order['total_amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="index.php" class="btn-primary">
                    <i class="fas fa-home me-2"></i>Back to Home
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="user/orders.php" class="btn-outline">
                    <i class="fas fa-history me-2"></i>View My Orders
                </a>
                <?php endif; ?>
                <a href="shop.php" class="btn-outline">
                    <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                </a>
            </div>
            
            <!-- Need Help -->
            <div class="text-center mt-4">
                <p class="text-muted small">
                    <i class="fas fa-question-circle me-1"></i>
                    Need help with your order? <a href="contact.php" class="text-decoration-none">Contact Support</a>
                </p>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
<?php mysqli_close($conn); ?>