<?php
// order_details.php
session_start();
require_once '../includes/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

// Get order ID from URL
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

// Fetch order details
$order_sql = "SELECT o.*, u.name as customer_name, u.email, u.phone, 
                     u.address, u.city, u.state, u.zip_code, u.country
              FROM orders o
              JOIN users u ON o.user_id = u.id
              WHERE o.id = ? AND o.user_id = ?";
$order_stmt = mysqli_prepare($conn, $order_sql);
$order = null;

if ($order_stmt) {
    mysqli_stmt_bind_param($order_stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($order_stmt);
    $order_result = mysqli_stmt_get_result($order_stmt);
    $order = mysqli_fetch_assoc($order_result);
    mysqli_stmt_close($order_stmt);
}

// If order not found or doesn't belong to user
if (!$order) {
    header('Location: orders.php?error=order_not_found');
    exit();
}

// Fetch order items
$items_sql = "SELECT oi.*, p.name, p.image, p.category_id, c.name as category_name
              FROM order_items oi
              JOIN products p ON oi.product_id = p.id
              LEFT JOIN categories c ON p.category_id = c.id
              WHERE oi.order_id = ?
              ORDER BY oi.id";
$items_stmt = mysqli_prepare($conn, $items_sql);
$order_items = [];

if ($items_stmt) {
    mysqli_stmt_bind_param($items_stmt, "i", $order_id);
    mysqli_stmt_execute($items_stmt);
    $items_result = mysqli_stmt_get_result($items_stmt);
    
    while ($row = mysqli_fetch_assoc($items_result)) {
        $order_items[] = $row;
    }
    mysqli_stmt_close($items_stmt);
}

// Calculate subtotal, tax, and shipping (example values)
$subtotal = $order['total_amount'];
$shipping = 5.99; // Fixed shipping cost
$tax = $subtotal * 0.08; // 8% tax rate
$total = $subtotal + $shipping + $tax;

// Fetch order status history (if you have a status_history table)
$history_sql = "SELECT status, updated_at, note 
                FROM order_status_history 
                WHERE order_id = ? 
                ORDER BY updated_at DESC";
$history_stmt = mysqli_prepare($conn, $history_sql);
$status_history = [];

if ($history_stmt) {
    mysqli_stmt_bind_param($history_stmt, "i", $order_id);
    mysqli_stmt_execute($history_stmt);
    $history_result = mysqli_stmt_get_result($history_stmt);
    
    while ($row = mysqli_fetch_assoc($history_result)) {
        $status_history[] = $row;
    }
    mysqli_stmt_close($history_stmt);
}

// Handle order cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    if ($order['status'] === 'pending') {
        $cancel_sql = "UPDATE orders SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
        $cancel_stmt = mysqli_prepare($conn, $cancel_sql);
        
        if ($cancel_stmt) {
            mysqli_stmt_bind_param($cancel_stmt, "i", $order_id);
            if (mysqli_stmt_execute($cancel_stmt)) {
                $success = "Order has been cancelled successfully.";
                $order['status'] = 'cancelled';
            } else {
                $error = "Failed to cancel order. Please try again.";
            }
            mysqli_stmt_close($cancel_stmt);
        }
    } else {
        $error = "Only pending orders can be cancelled.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?> - La Flora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .order-details-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .order-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .order-status-tracker {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 30px 0;
        }
        
        .order-status-tracker::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .status-step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }
        
        .status-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: white;
            border: 2px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 14px;
        }
        
        .status-step.active .status-icon {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .status-step.completed .status-icon {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .status-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .status-step.active .status-label {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .product-item {
            border-bottom: 1px solid #e9ecef;
            padding: 15px 0;
        }
        
        .product-item:last-child {
            border-bottom: none;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #e9ecef;
        }
        
        .price-breakdown {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        
        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .price-row.total {
            border-top: 2px solid #dee2e6;
            padding-top: 15px;
            margin-top: 15px;
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .action-buttons .btn {
            padding: 8px 20px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 20px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 2px solid white;
            box-shadow: 0 0 0 3px var(--primary-color);
        }
        
        .timeline-date {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        @media print {
            .action-buttons, .no-print {
                display: none !important;
            }
            
            .order-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6;
            }
        }
        
        @media (max-width: 768px) {
            .order-status-tracker {
                flex-wrap: wrap;
                gap: 20px;
            }
            
            .status-step {
                flex: 0 0 calc(50% - 10px);
            }
            
            .action-buttons .btn {
                width: 100%;
                margin-right: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <div class="order-details-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>
                        <i class="fas fa-receipt me-2"></i>
                        Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?>
                    </h1>
                    <p class="mb-0">
                        Placed on <?php echo date('F j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="orders.php" class="btn btn-light">
                        <i class="fas fa-arrow-left me-1"></i> Back to Orders
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-light ms-2">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <!-- Order Status Tracker -->
        <div class="order-card">
            <h5 class="mb-4"><i class="fas fa-truck me-2"></i>Order Status</h5>
            
            <div class="order-status-tracker">
                <?php 
                $status_steps = ['pending', 'processing', 'shipped', 'delivered'];
                $current_status = $order['status'];
                $current_index = array_search($current_status, $status_steps);
                $current_index = $current_index !== false ? $current_index : 0;
                
                foreach ($status_steps as $index => $status):
                    $step_class = '';
                    if ($status === $current_status) {
                        $step_class = 'active';
                    } elseif ($index < $current_index) {
                        $step_class = 'completed';
                    }
                    
                    $status_icons = [
                        'pending' => 'far fa-clock',
                        'processing' => 'fas fa-cog',
                        'shipped' => 'fas fa-truck',
                        'delivered' => 'fas fa-check-circle'
                    ];
                ?>
                    <div class="status-step <?php echo $step_class; ?>">
                        <div class="status-icon">
                            <i class="<?php echo $status_icons[$status]; ?>"></i>
                        </div>
                        <div class="status-label">
                            <?php echo ucfirst($status); ?>
                        </div>
                        <?php if ($step_class === 'active'): ?>
                            <small class="text-primary d-block mt-1">Current Status</small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-<?php 
                            echo $order['status'] === 'pending' ? 'warning' : 
                                ($order['status'] === 'processing' ? 'info' : 
                                ($order['status'] === 'shipped' ? 'primary' : 
                                ($order['status'] === 'delivered' ? 'success' : 'danger'))); 
                        ?> me-2" style="padding: 8px 16px; font-size: 1rem;">
                            <?php echo strtoupper($order['status']); ?>
                        </span>
                        <span class="badge bg-<?php 
                            echo $order['payment_status'] === 'pending' ? 'warning' : 
                                ($order['payment_status'] === 'completed' ? 'success' : 'danger'); 
                        ?>">
                            Payment: <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </div>
                    <p class="text-muted mb-0">
                        <i class="fas fa-credit-card me-1"></i>
                        Payment Method: <?php echo ucfirst($order['payment_method']); ?>
                    </p>
                </div>
                
                <div class="col-md-6 text-end action-buttons">
                    <?php if ($order['status'] === 'pending'): ?>
                        <form method="POST" class="d-inline">
                            <button type="submit" name="cancel_order" 
                                    class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to cancel this order? This action cannot be undone.')">
                                <i class="fas fa-times me-1"></i> Cancel Order
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <?php if ($order['status'] === 'delivered'): ?>
                        <a href="../review.php?order=<?php echo $order_id; ?>" class="btn btn-success">
                            <i class="fas fa-star me-1"></i> Write a Review
                        </a>
                    <?php endif; ?>
                    
                    <a href="../shop.php" class="btn btn-outline-primary">
                        <i class="fas fa-shopping-bag me-1"></i> Shop Again
                    </a>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Order Items -->
            <div class="col-lg-8">
                <div class="order-card">
                    <h5 class="mb-4"><i class="fas fa-boxes me-2"></i>Order Items</h5>
                    
                    <?php foreach ($order_items as $item): ?>
                        <div class="product-item">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <?php if (!empty($item['image'])): ?>
                                        <img src="../images/products/<?php echo htmlspecialchars($item['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-leaf text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-6">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <?php if (!empty($item['category_name'])): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo htmlspecialchars($item['category_name']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-2 text-center">
                                    <small class="text-muted">Quantity</small>
                                    <div class="fw-bold"><?php echo $item['quantity']; ?></div>
                                </div>
                                
                                <div class="col-md-2 text-end">
                                    <small class="text-muted">Price</small>
                                    <div class="fw-bold">$<?php echo number_format($item['price'], 2); ?></div>
                                    <small class="text-muted">
                                        Total: $<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Order Notes/Timeline -->
                <?php if (!empty($status_history)): ?>
                    <div class="order-card mt-4">
                        <h5 class="mb-4"><i class="fas fa-history me-2"></i>Order Timeline</h5>
                        
                        <div class="timeline">
                            <?php foreach ($status_history as $history): ?>
                                <div class="timeline-item">
                                    <h6 class="mb-1">Status changed to: <?php echo ucfirst($history['status']); ?></h6>
                                    <p class="mb-1"><?php echo htmlspecialchars($history['note']); ?></p>
                                    <div class="timeline-date">
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('F j, Y \a\t g:i A', strtotime($history['updated_at'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Order Summary & Shipping -->
            <div class="col-lg-4">
                <!-- Price Breakdown -->
                <div class="order-card mb-4">
                    <h5 class="mb-4"><i class="fas fa-calculator me-2"></i>Price Breakdown</h5>
                    
                    <div class="price-breakdown">
                        <div class="price-row">
                            <span>Subtotal (<?php echo count($order_items); ?> items)</span>
                            <span>$<?php echo number_format($subtotal, 2); ?></span>
                        </div>
                        
                        <div class="price-row">
                            <span>Shipping</span>
                            <span>$<?php echo number_format($shipping, 2); ?></span>
                        </div>
                        
                        <div class="price-row">
                            <span>Tax</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        
                        <div class="price-row total">
                            <span>Total</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Shipping Information -->
                <div class="order-card mb-4">
                    <h5 class="mb-4"><i class="fas fa-shipping-fast me-2"></i>Shipping Information</h5>
                    
                    <div class="mb-3">
                        <h6 class="mb-2">Shipping Address</h6>
                        <p class="mb-1"><?php echo htmlspecialchars($order['customer_name']); ?></p>
                        <p class="mb-1"><?php echo htmlspecialchars($order['address']); ?></p>
                        <p class="mb-1">
                            <?php echo htmlspecialchars($order['city']); ?>, 
                            <?php echo htmlspecialchars($order['state']); ?> 
                            <?php echo htmlspecialchars($order['zip_code']); ?>
                        </p>
                        <p class="mb-0"><?php echo htmlspecialchars($order['country']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="mb-2">Contact Information</h6>
                        <p class="mb-1">
                            <i class="fas fa-envelope me-2"></i>
                            <?php echo htmlspecialchars($order['email']); ?>
                        </p>
                        <?php if (!empty($order['phone'])): ?>
                            <p class="mb-0">
                                <i class="fas fa-phone me-2"></i>
                                <?php echo htmlspecialchars($order['phone']); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Billing Information -->
                <div class="order-card">
                    <h5 class="mb-4"><i class="fas fa-file-invoice-dollar me-2"></i>Billing Information</h5>
                    
                    <div class="mb-3">
                        <p class="mb-2">
                            <strong>Payment Method:</strong><br>
                            <?php echo strtoupper($order['payment_method']); ?>
                        </p>
                        <p class="mb-0">
                            <strong>Payment Status:</strong><br>
                            <span class="badge bg-<?php 
                                echo $order['payment_status'] === 'completed' ? 'success' : 
                                    ($order['payment_status'] === 'pending' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo strtoupper($order['payment_status']); ?>
                            </span>
                        </p>
                    </div>
                    
                    <?php if ($order['payment_status'] === 'pending'): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <small>Payment is pending. Please complete your payment to process the order.</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Order Help -->
        <div class="order-card mt-4">
            <h5 class="mb-3"><i class="fas fa-question-circle me-2"></i>Need Help With Your Order?</h5>
            <div class="row">
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <i class="fas fa-headset fa-2x text-primary mb-3"></i>
                        <h6>Contact Support</h6>
                        <p class="text-muted small">Have questions about your order?</p>
                        <a href="../contact.php" class="btn btn-outline-primary btn-sm">Contact Us</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <i class="fas fa-exchange-alt fa-2x text-primary mb-3"></i>
                        <h6>Return Policy</h6>
                        <p class="text-muted small">Learn about our return policy</p>
                        <a href="../return_policy.php" class="btn btn-outline-primary btn-sm">View Policy</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center p-3">
                        <i class="fas fa-truck fa-2x text-primary mb-3"></i>
                        <h6>Shipping Info</h6>
                        <p class="text-muted small">Track shipping & delivery times</p>
                        <a href="../shipping.php" class="btn btn-outline-primary btn-sm">Shipping Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Print functionality
        function printOrder() {
            window.print();
        }
        
        // Confirm order cancellation
        function confirmCancel() {
            return confirm('Are you sure you want to cancel this order? This action cannot be undone.');
        }
        
        // Update page title with order status
        document.addEventListener('DOMContentLoaded', function() {
            const status = "<?php echo $order['status']; ?>";
            const statusColors = {
                'pending': '#ffc107',
                'processing': '#17a2b8',
                'shipped': '#007bff',
                'delivered': '#28a745',
                'cancelled': '#dc3545'
            };
            
            // Update favicon based on status (optional)
            const favicon = document.querySelector('link[rel="icon"]');
            if (favicon && statusColors[status]) {
                // You could create status-colored favicon here
            }
        });
    </script>
</body>
</html>