<?php
session_start();
require_once '../includes/database.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header('Location: orders.php?error=Invalid order ID');
    exit();
}

// Fetch order details
$order_query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?";
$stmt = mysqli_prepare($conn, $order_query);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$order_result = mysqli_stmt_get_result($stmt);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    header('Location: orders.php?error=Order not found');
    exit();
}

// Fetch order items
$items_query = "SELECT oi.*, p.image as product_image 
                FROM order_items oi 
                LEFT JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = ?";
$items_stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($items_stmt, "i", $order_id);
mysqli_stmt_execute($items_stmt);
$items_result = mysqli_stmt_get_result($items_stmt);

$status_colors = [
    'pending' => 'warning',
    'processing' => 'info',
    'shipped' => 'primary',
    'delivered' => 'success',
    'cancelled' => 'danger'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?> - LA FLORA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2A5934;
            --primary-dark: #1E4025;
            --primary-light: #4A7856;
            --secondary-color: #D4A373;
            --success-color: #2A9D8F;
            --bg-light: #F8F9FA;
            --border-color: #E9ECEF;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: #333;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            padding-top: 20px;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-brand {
            padding: 0 24px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }

        .sidebar-brand h4 {
            font-weight: 700;
            margin-bottom: 4px;
            color: white;
            font-size: 1.5rem;
        }

        .sidebar-brand small {
            color: rgba(255,255,255,0.8);
            font-size: 0.85rem;
        }

        .sidebar-nav {
            padding: 0 16px;
        }

        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.3s;
            text-decoration: none;
        }

        .sidebar-nav .nav-link i {
            font-size: 1.3rem;
            width: 24px;
        }

        .sidebar-nav .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.15);
            transform: translateX(4px);
        }

        .sidebar-nav .nav-link.active {
            background: var(--secondary-color);
            color: var(--primary-dark);
            font-weight: 600;
        }

        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        .page-header {
            background: white;
            padding: 24px 30px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
        }

        .btn-back {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-back:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-2px);
        }

        .info-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .info-card h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #666;
            width: 180px;
            flex-shrink: 0;
        }

        .info-value {
            color: var(--primary-dark);
            font-weight: 500;
            flex: 1;
        }

        .badge-custom {
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-block;
        }

        .badge-pending { background: #FFF3E0; color: #E65100; }
        .badge-processing { background: #E3F2FD; color: #1976D2; }
        .badge-shipped { background: #E8EAF6; color: #5E35B1; }
        .badge-delivered { background: #E8F5E9; color: #2E7D32; }
        .badge-cancelled { background: #FFEBEE; color: #C62828; }
        .badge-completed { background: #E8F5E9; color: #2E7D32; }
        .badge-failed { background: #FFEBEE; color: #C62828; }

        .items-table {
            width: 100%;
            margin-top: 20px;
        }

        .items-table th {
            background: var(--bg-light);
            padding: 14px;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            border: none;
        }

        .items-table td {
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
        }

        .total-section {
            background: var(--bg-light);
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 1.1rem;
        }

        .total-row.grand-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            border-top: 2px solid var(--border-color);
            padding-top: 16px;
            margin-top: 10px;
        }

        @media print {
            .sidebar, .btn-back, .no-print {
                display: none !important;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar no-print">
        <div class="sidebar-brand">
            <h4><i class="bi bi-flower2 me-2"></i>LA FLORA</h4>
            <small>Admin Panel</small>
        </div>
        
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-link">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
            <a href="products.php" class="nav-link">
                <i class="bi bi-box-seam"></i>
                <span>Products</span>
            </a>
            <a href="categories.php" class="nav-link">
                <i class="bi bi-tags"></i>
                <span>Categories</span>
            </a>
            <a href="orders.php" class="nav-link active">
                <i class="bi bi-cart-check"></i>
                <span>Orders</span>
            </a>
            <a href="users.php" class="nav-link">
                <i class="bi bi-people"></i>
                <span>Users</span>
            </a>
            <a href="reviews.php" class="nav-link">
                <i class="bi bi-star"></i>
                <span>Reviews</span>
            </a>
            <a href="logout.php" class="nav-link text-danger mt-4">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="bi bi-receipt me-2"></i>Order #<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></h1>
            <div class="d-flex gap-2 no-print">
                <a href="orders.php" class="btn-back">
                    <i class="bi bi-arrow-left"></i>
                    Back to Orders
                </a>
                <button onclick="window.print()" class="btn-back" style="background: #1976D2;">
                    <i class="bi bi-printer"></i>
                    Print
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Order Items -->
                <div class="info-card">
                    <h2><i class="bi bi-box-seam"></i> Order Items</h2>
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if (!empty($item['product_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($item['product_image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                                 class="product-img"
                                                 onerror="this.src='https://via.placeholder.com/60x60/E8F5E9/2A5934?text=Flower'">
                                        <?php else: ?>
                                            <img src="https://via.placeholder.com/60x60/E8F5E9/2A5934?text=Flower" 
                                                 alt="Product" 
                                                 class="product-img">
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                            <div style="font-size: 0.85rem; color: #666;">ID: <?php echo $item['product_id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><strong>$<?php echo number_format($item['price'], 2); ?></strong></td>
                                <td><strong><?php echo $item['quantity']; ?></strong></td>
                                <td><strong style="color: var(--success-color);">$<?php echo number_format($item['subtotal'], 2); ?></strong></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <div class="total-section">
                        <div class="total-row grand-total">
                            <span>Total Amount:</span>
                            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Addresses -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-card">
                            <h2><i class="bi bi-truck"></i> Shipping Address</h2>
                            <p style="line-height: 1.8; color: #666; white-space: pre-line;"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-card">
                            <h2><i class="bi bi-credit-card"></i> Billing Address</h2>
                            <p style="line-height: 1.8; color: #666; white-space: pre-line;"><?php echo htmlspecialchars($order['billing_address']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Order Information -->
                <div class="info-card">
                    <h2><i class="bi bi-info-circle"></i> Order Information</h2>
                    <div class="info-row">
                        <div class="info-label">Order ID:</div>
                        <div class="info-value">#<?php echo str_pad($order_id, 6, '0', STR_PAD_LEFT); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Order Status:</div>
                        <div class="info-value">
                            <span class="badge-custom badge-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Payment Status:</div>
                        <div class="info-value">
                            <span class="badge-custom badge-<?php echo $order['payment_status']; ?>">
                                <?php echo ucfirst($order['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Payment Method:</div>
                        <div class="info-value"><?php echo htmlspecialchars(ucfirst($order['payment_method'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Order Date:</div>
                        <div class="info-value"><?php echo date('F d, Y - h:i A', strtotime($order['created_at'])); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Last Updated:</div>
                        <div class="info-value"><?php echo date('F d, Y - h:i A', strtotime($order['updated_at'])); ?></div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="info-card">
                    <h2><i class="bi bi-person-circle"></i> Customer Information</h2>
                    <div class="info-row">
                        <div class="info-label">Name:</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Email:</div>
                        <div class="info-value"><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">User ID:</div>
                        <div class="info-value">
                            <?php if ($order['user_id']): ?>
                                <a href="users.php" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                    #<?php echo $order['user_id']; ?>
                                </a>
                            <?php else: ?>
                                Guest Order
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>