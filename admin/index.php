<?php
session_start();
require_once '../includes/database.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';
$admin_email = $_SESSION['admin_email'] ?? '';

// Helper function for safe queries
function getSafeCount($conn, $query) {
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_row($result);
        return $row[0] ?? 0;
    }
    return 0;
}

// Get statistics
$total_products = getSafeCount($conn, "SELECT COUNT(*) FROM products");
$total_orders = getSafeCount($conn, "SELECT COUNT(*) FROM orders");
$pending_orders = getSafeCount($conn, "SELECT COUNT(*) FROM orders WHERE status='pending'");
$total_users = getSafeCount($conn, "SELECT COUNT(*) FROM users");

// Revenue calculations
$revenue_result = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status IN ('delivered', 'shipped')");
$total_revenue = $revenue_result ? mysqli_fetch_assoc($revenue_result)['total'] : 0;

$today_orders_result = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
$today_orders = $today_orders_result ? mysqli_fetch_assoc($today_orders_result)['count'] : 0;

$today_revenue_result = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(created_at) = CURDATE() AND status IN ('delivered', 'shipped')");
$today_revenue = $today_revenue_result ? mysqli_fetch_assoc($today_revenue_result)['total'] : 0;

// Get recent orders
$recent_orders_query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
                        FROM orders o 
                        LEFT JOIN users u ON o.user_id = u.id 
                        ORDER BY o.created_at DESC 
                        LIMIT 6";
$recent_orders_result = mysqli_query($conn, $recent_orders_query);
$recent_orders = [];
if ($recent_orders_result) {
    while ($row = mysqli_fetch_assoc($recent_orders_result)) {
        $recent_orders[] = $row;
    }
}

// Get top selling products
$top_products_query = "SELECT p.id, p.name, p.image, p.price, COUNT(oi.id) as sales_count, SUM(oi.quantity) as total_quantity
                       FROM products p
                       LEFT JOIN order_items oi ON p.id = oi.product_id
                       GROUP BY p.id
                       ORDER BY sales_count DESC
                       LIMIT 5";
$top_products_result = mysqli_query($conn, $top_products_query);
$top_products = [];
if ($top_products_result) {
    while ($row = mysqli_fetch_assoc($top_products_result)) {
        $top_products[] = $row;
    }
}

// Get weekly sales data
$weekly_query = "SELECT 
                    DAYNAME(created_at) as day_name,
                    DATE(created_at) as order_date,
                    COUNT(*) as orders,
                    COALESCE(SUM(total_amount), 0) as revenue
                 FROM orders 
                 WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                 GROUP BY DATE(created_at), DAYNAME(created_at)
                 ORDER BY order_date ASC";
$weekly_result = mysqli_query($conn, $weekly_query);
$weekly_data = [];
if ($weekly_result) {
    while ($row = mysqli_fetch_assoc($weekly_result)) {
        $weekly_data[$row['day_name']] = $row;
    }
}

// Get monthly comparison
$current_month_query = "SELECT COUNT(*) as count FROM orders WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$current_month_result = mysqli_query($conn, $current_month_query);
$current_month_orders = $current_month_result ? mysqli_fetch_assoc($current_month_result)['count'] : 0;

$last_month_query = "SELECT COUNT(*) as count FROM orders WHERE MONTH(created_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
$last_month_result = mysqli_query($conn, $last_month_query);
$last_month_orders = $last_month_result ? mysqli_fetch_assoc($last_month_result)['count'] : 0;

$growth_percentage = $last_month_orders > 0 ? (($current_month_orders - $last_month_orders) / $last_month_orders) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LA FLORA Admin</title>
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
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .page-header h1 {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0 0 8px 0;
        }

        .welcome-text {
            color: #666;
            font-size: 0.95rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: var(--primary-color);
            opacity: 0.05;
            border-radius: 50%;
            transform: translate(30%, -30%);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.primary { background: rgba(42, 89, 52, 0.1); color: var(--primary-color); }
        .stat-icon.success { background: rgba(42, 157, 143, 0.1); color: var(--success-color); }
        .stat-icon.warning { background: rgba(255, 152, 0, 0.1); color: #FF9800; }
        .stat-icon.info { background: rgba(33, 150, 243, 0.1); color: #2196F3; }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 4px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stat-change {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .stat-change.positive { color: var(--success-color); }
        .stat-change.negative { color: #E76F51; }

        .dashboard-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-body {
            padding: 24px;
        }

        .table-custom {
            margin: 0;
        }

        .table-custom thead {
            background: var(--bg-light);
        }

        .table-custom th {
            padding: 14px 16px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            border: none;
        }

        .table-custom td {
            padding: 16px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .table-custom tbody tr:hover {
            background: #F8F9FA;
        }

        .badge-custom {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .badge-pending { background: #FFF3E0; color: #E65100; }
        .badge-processing { background: #E3F2FD; color: #1976D2; }
        .badge-shipped { background: #E8EAF6; color: #5E35B1; }
        .badge-delivered { background: #E8F5E9; color: #2E7D32; }
        .badge-cancelled { background: #FFEBEE; color: #C62828; }

        .product-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s;
        }

        .product-item:hover { background: var(--bg-light); }
        .product-item:last-child { border-bottom: none; }

        .product-img {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            object-fit: cover;
        }

        .product-info { flex: 1; }

        .product-name {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 4px;
        }

        .product-sales {
            font-size: 0.85rem;
            color: #666;
        }

        .product-price {
            font-weight: 700;
            color: var(--success-color);
            font-size: 1.1rem;
        }

        .chart-container {
            display: flex;
            align-items: flex-end;
            justify-content: space-around;
            height: 200px;
            padding: 20px;
        }

        .chart-bar {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .bar-container {
            width: 100%;
            height: 150px;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .bar {
            width: 40px;
            background: linear-gradient(180deg, var(--primary-color), var(--primary-light));
            border-radius: 8px 8px 0 0;
            transition: all 0.5s ease;
        }

        .bar:hover {
            opacity: 0.8;
        }

        .bar-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
        }

        .bar-value {
            font-size: 0.75rem;
            color: #999;
            text-align: center;
        }

        .btn-view-all {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
        }

        .btn-view-all:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: #E3F2FD;
            color: #1976D2;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            text-decoration: none;
        }

        .btn-action:hover {
            background: #1976D2;
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: #DDD;
            margin-bottom: 16px;
        }

        @media (max-width: 768px) {
            .sidebar { width: 0; overflow: hidden; }
            .main-content { margin-left: 0; padding: 20px; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    
    <div class="sidebar">
        <div class="sidebar-brand">
            <h4><i class="bi bi-flower2 me-2"></i>LA FLORA</h4>
            <small>Admin Panel</small>
        </div>
        
        <nav class="sidebar-nav">
            <a href="index.php" class="nav-link active">
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
            <a href="orders.php" class="nav-link">
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

    <div class="main-content">
        <div class="page-header">
            <h1><i class="bi bi-speedometer2 me-2"></i>Dashboard</h1>
            <p class="welcome-text">Welcome back, <strong><?php echo htmlspecialchars($admin_name); ?></strong>! Here's what's happening today.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                        <div class="stat-label">Total Orders</div>
                        <?php if ($growth_percentage != 0): ?>
                            <div class="stat-change <?php echo $growth_percentage > 0 ? 'positive' : 'negative'; ?>">
                                <i class="bi bi-<?php echo $growth_percentage > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                                <?php echo abs(number_format($growth_percentage, 1)); ?>% vs last month
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon primary">
                        <i class="bi bi-cart-check"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value">$<?php echo number_format($total_revenue, 2); ?></div>
                        <div class="stat-label">Total Revenue</div>
                    </div>
                    <div class="stat-icon success">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo number_format($total_products); ?></div>
                        <div class="stat-label">Total Products</div>
                    </div>
                    <div class="stat-icon info">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value"><?php echo number_format($pending_orders); ?></div>
                        <div class="stat-label">Pending Orders</div>
                        <?php if ($pending_orders > 0): ?>
                            <div class="stat-change" style="color: #FF9800;">
                                <i class="bi bi-exclamation-circle"></i>
                                Needs attention
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-icon warning">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 style="font-size: 1.1rem; color: #666; margin-bottom: 8px;">Today's Orders</h3>
                                <div style="font-size: 2.5rem; font-weight: 700; color: var(--primary-dark);">
                                    <?php echo $today_orders; ?>
                                </div>
                            </div>
                            <div class="stat-icon primary" style="width: 70px; height: 70px; font-size: 2rem;">
                                <i class="bi bi-calendar-check"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="dashboard-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 style="font-size: 1.1rem; color: #666; margin-bottom: 8px;">Today's Revenue</h3>
                                <div style="font-size: 2.5rem; font-weight: 700; color: var(--success-color);">
                                    $<?php echo number_format($today_revenue, 2); ?>
                                </div>
                            </div>
                            <div class="stat-icon success" style="width: 70px; height: 70px; font-size: 2rem;">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="bi bi-receipt"></i> Recent Orders</h3>
                        <a href="orders.php" class="btn-view-all">
                            View All <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table-custom table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_orders)): ?>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                        <td>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></div>
                                            <small style="color: #666;"><?php echo htmlspecialchars($order['customer_email'] ?? 'N/A'); ?></small>
                                        </td>
                                        <td><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td>
                                        <td><strong style="color: var(--success-color);">$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                                        <td>
                                            <span class="badge-custom badge-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order_details.php?id=<?php echo $order['id']; ?>" class="btn-action">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="bi bi-cart-x"></i>
                                                <h4>No orders yet</h4>
                                                <p>Orders will appear here</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="bi bi-star"></i> Top Products</h3>
                    </div>
                    <?php if (!empty($top_products)): ?>
                        <?php foreach ($top_products as $product): ?>
                        <div class="product-item">
                            <img src="<?php echo !empty($product['image']) ? '../' . htmlspecialchars($product['image']) : 'https://via.placeholder.com/56x56/E8F5E9/2A5934?text=Flower'; ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="product-img"
                                 onerror="this.src='https://via.placeholder.com/56x56/E8F5E9/2A5934?text=Flower'">
                            <div class="product-info">
                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                <div class="product-sales">
                                    <?php echo number_format($product['total_quantity'] ?? 0); ?> sold
                                </div>
                            </div>
                            <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="bi bi-box-seam"></i>
                            <h4>No sales data</h4>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="dashboard-card" style="margin-top: 24px;">
            <div class="card-header">
                <h3><i class="bi bi-bar-chart"></i> Weekly Sales Overview</h3>
            </div>
            <div class="chart-container">
                <?php
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                $max_orders = 1;
                foreach ($weekly_data as $data) {
                    if ($data['orders'] > $max_orders) $max_orders = $data['orders'];
                }
                
                foreach ($days as $day):
                    $data = $weekly_data[$day] ?? ['orders' => 0, 'revenue' => 0];
                    $height = $max_orders > 0 ? ($data['orders'] / $max_orders) * 100 : 0;
                ?>
                <div class="chart-bar">
                    <div class="bar-container">
                        <div class="bar" style="height: <?php echo $height; ?>%;"></div>
                    </div>
                    <div class="bar-label"><?php echo substr($day, 0, 3); ?></div>
                    <div class="bar-value">
                        <?php echo $data['orders']; ?> orders<br>
                        <strong style="color: var(--success-color);">$<?php echo number_format($data['revenue'], 0); ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
   

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>