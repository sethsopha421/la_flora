<?php
session_start();
require_once '../includes/database.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Handle status updates
if (isset($_GET['update_status'])) {
    $id = intval($_GET['id']);
    $status = mysqli_real_escape_string($conn, $_GET['status']);
    
    $stmt = mysqli_prepare($conn, "UPDATE orders SET status=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, "si", $status, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    header('Location: orders.php?message=Order status updated successfully');
    exit();
}

// Handle delete order
if (isset($_GET['delete'])) {
    $id = intval($_GET['id']);
    
    mysqli_begin_transaction($conn);
    
    try {
        // Delete order items first
        $stmt1 = mysqli_prepare($conn, "DELETE FROM order_items WHERE order_id=?");
        mysqli_stmt_bind_param($stmt1, "i", $id);
        mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);
        
        // Delete the order
        $stmt2 = mysqli_prepare($conn, "DELETE FROM orders WHERE id=?");
        mysqli_stmt_bind_param($stmt2, "i", $id);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);
        
        mysqli_commit($conn);
        
        header('Location: orders.php?message=Order deleted successfully');
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        header('Location: orders.php?error=Failed to delete order');
        exit();
    }
}

// Get filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with prepared statements
$query = "SELECT o.*, u.name as customer_name, u.email 
          FROM orders o 
          LEFT JOIN users u ON o.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND o.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR o.id = ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search;
    $types .= "sss";
}

$query .= " ORDER BY o.created_at DESC";

if (!empty($params)) {
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $orders = mysqli_stmt_get_result($stmt);
} else {
    $orders = mysqli_query($conn, $query);
}

// Get statistics
$total_orders = mysqli_query($conn, "SELECT COUNT(*) as count FROM orders");
$total_orders_count = mysqli_fetch_assoc($total_orders)['count'];

$total_revenue = mysqli_query($conn, "SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'cancelled'");
$total_revenue_amount = mysqli_fetch_assoc($total_revenue)['total'];

// Get status counts
$status_counts_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$status_counts_result = mysqli_query($conn, $status_counts_query);
$status_counts = [];
while ($row = mysqli_fetch_assoc($status_counts_result)) {
    $status_counts[$row['status']] = $row['count'];
}

// Status colors
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
    <title>Orders - LA FLORA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2A5934;
            --primary-dark: #1E4025;
            --primary-light: #4A7856;
            --secondary-color: #D4A373;
            --accent-color: #E9C46A;
            --danger-color: #E76F51;
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

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
        }

        /* Page Header */
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

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
        }

        .stat-icon.primary {
            background: rgba(42, 89, 52, 0.1);
            color: var(--primary-color);
        }

        .stat-icon.success {
            background: rgba(42, 157, 143, 0.1);
            color: var(--success-color);
        }

        .stat-icon.info {
            background: rgba(33, 150, 243, 0.1);
            color: #2196F3;
        }

        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: var(--primary-dark);
        }

        .stat-content p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }

        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .filter-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .filter-badge {
            padding: 10px 20px;
            border-radius: 10px;
            border: 2px solid var(--border-color);
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            color: #666;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .filter-badge:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .filter-badge.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .filter-badge .count {
            background: rgba(0,0,0,0.1);
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.85rem;
        }

        .filter-badge.active .count {
            background: rgba(255,255,255,0.2);
        }

        .search-box {
            display: flex;
            gap: 12px;
        }

        .search-box input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .btn-admin {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .btn-admin:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(42, 89, 52, 0.3);
            color: white;
        }

        /* Orders Card */
        .orders-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .orders-card-header {
            padding: 24px 30px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .orders-card-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        .orders-table {
            width: 100%;
            margin: 0;
        }

        .orders-table thead {
            background: var(--bg-light);
        }

        .orders-table th {
            padding: 16px 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            border: none;
            white-space: nowrap;
        }

        .orders-table td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .orders-table tbody tr {
            transition: all 0.3s;
        }

        .orders-table tbody tr:hover {
            background: #F8F9FA;
        }

        /* Order Number */
        .order-number {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 1.05rem;
        }

        .order-id {
            font-size: 0.8rem;
            color: #999;
        }

        /* Customer Info */
        .customer-name {
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 4px;
        }

        .customer-email {
            font-size: 0.85rem;
            color: #666;
        }

        /* Badges */
        .badge-custom {
            padding: 8px 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-block;
        }

        .badge-pending {
            background: #FFF3E0;
            color: #E65100;
        }

        .badge-processing {
            background: #E3F2FD;
            color: #1976D2;
        }

        .badge-shipped {
            background: #E8EAF6;
            color: #5E35B1;
        }

        .badge-delivered {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .badge-cancelled {
            background: #FFEBEE;
            color: #C62828;
        }

        .badge-paid {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .badge-unpaid {
            background: #FFF3E0;
            color: #E65100;
        }

        .badge-completed {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .badge-failed {
            background: #FFEBEE;
            color: #C62828;
        }

        /* Action Buttons */
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            cursor: pointer;
            margin: 0 4px;
        }

        .btn-view {
            background: #E3F2FD;
            color: #1976D2;
        }

        .btn-view:hover {
            background: #1976D2;
            color: white;
        }

        .btn-edit {
            background: #F3E5F5;
            color: #7B1FA2;
        }

        .btn-edit:hover {
            background: #7B1FA2;
            color: white;
        }

        .btn-delete {
            background: #FFEBEE;
            color: #C62828;
        }

        .btn-delete:hover {
            background: #C62828;
            color: white;
        }

        /* Dropdown */
        .dropdown-menu {
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            border: none;
            padding: 8px;
        }

        .dropdown-item {
            border-radius: 8px;
            padding: 10px 16px;
            transition: all 0.3s;
        }

        .dropdown-item:hover {
            background: var(--bg-light);
        }

        .dropdown-header {
            font-weight: 700;
            color: var(--primary-dark);
            padding: 10px 16px;
        }

        /* Alert */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .alert-danger {
            background: #FFEBEE;
            color: #C62828;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-state i {
            font-size: 4rem;
            color: #DDD;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            color: #666;
            font-weight: 600;
            margin-bottom: 12px;
        }

        /* Export Section */
        .export-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .export-card h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 16px;
        }

        .export-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn-export {
            padding: 12px 24px;
            border-radius: 10px;
            border: 2px solid;
            background: white;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-export.csv {
            border-color: #2E7D32;
            color: #2E7D32;
        }

        .btn-export.csv:hover {
            background: #2E7D32;
            color: white;
        }

        .btn-export.pdf {
            border-color: #C62828;
            color: #C62828;
        }

        .btn-export.pdf:hover {
            background: #C62828;
            color: white;
        }

        .btn-export.print {
            border-color: #1976D2;
            color: #1976D2;
        }

        .btn-export.print:hover {
            background: #1976D2;
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                overflow: hidden;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .filter-badges {
                flex-direction: column;
            }

            .search-box {
                flex-direction: column;
            }

            .orders-table {
                font-size: 0.85rem;
            }

            .orders-table th,
            .orders-table td {
                padding: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
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
            <h1><i class="bi bi-cart-check me-2"></i>Manage Orders</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Orders</li>
                </ol>
            </nav>
        </div>

        <!-- Alerts -->
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_orders_count; ?></h3>
                    <p>Total Orders</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-content">
                    <h3>$<?php echo number_format($total_revenue_amount, 2); ?></h3>
                    <p>Total Revenue</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="bi bi-clock-history"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $status_counts['pending'] ?? 0; ?></h3>
                    <p>Pending Orders</p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="filter-badges">
                <a href="orders.php" class="filter-badge <?php echo empty($status_filter) ? 'active' : ''; ?>">
                    <i class="bi bi-grid"></i>
                    All Orders
                    <span class="count"><?php echo $total_orders_count; ?></span>
                </a>
                <?php foreach ($status_colors as $status => $color): ?>
                    <a href="orders.php?status=<?php echo $status; ?>" 
                       class="filter-badge <?php echo $status_filter === $status ? 'active' : ''; ?>">
                        <i class="bi bi-circle-fill"></i>
                        <?php echo ucfirst($status); ?>
                        <span class="count"><?php echo $status_counts[$status] ?? 0; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <form method="GET" action="orders.php">
                <?php if (!empty($status_filter)): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                <?php endif; ?>
                <div class="search-box">
                    <input type="text" 
                           name="search" 
                           placeholder="Search by customer name, email, or order ID..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn-admin" type="submit">
                        <i class="bi bi-search"></i>
                        Search
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="orders.php<?php echo !empty($status_filter) ? '?status='.$status_filter : ''; ?>" class="btn-admin" style="background: #666;">
                            <i class="bi bi-x"></i>
                            Clear
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="orders-card">
            <div class="orders-card-header">
                <h2><i class="bi bi-list-ul me-2"></i>Orders List</h2>
                <span class="text-muted">
                    <?php echo mysqli_num_rows($orders); ?> order(s) found
                </span>
            </div>

            <div class="table-responsive">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Email</th>
                            <th>Amount</th>
                            <th>Payment Method</th>
                            <th>Payment Status</th>
                            <th>Order Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($orders) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($orders)): 
                                $status_class = 'badge-' . ($order['status'] ?? 'pending');
                                $payment_class = ($order['payment_status'] ?? 'pending') == 'completed' ? 'badge-paid' : 'badge-unpaid';
                            ?>
                            <tr>
                                <td>
                                    <div class="order-number">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <div class="order-id">ID: <?php echo $order['id']; ?></div>
                                </td>
                                <td>
                                    <div class="customer-name"><?php echo htmlspecialchars($order['customer_name'] ?? 'Guest'); ?></div>
                                    <?php if ($order['user_id']): ?>
                                        <div class="order-id">User ID: <?php echo $order['user_id']; ?></div>
                                    <?php else: ?>
                                        <div class="order-id">Guest Order</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="customer-email"><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></div>
                                </td>
                                <td>
                                    <strong style="font-size: 1.1rem; color: var(--success-color);">
                                        $<?php echo number_format($order['total_amount'] ?? 0, 2); ?>
                                    </strong>
                                </td>
                                <td>
                                    <div style="font-weight: 500;">
                                        <?php echo htmlspecialchars(ucfirst($order['payment_method'] ?? 'N/A')); ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-custom <?php echo $payment_class; ?>">
                                        <?php echo ucfirst($order['payment_status'] ?? 'pending'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-custom <?php echo $status_class; ?>">
                                        <?php echo ucfirst($order['status'] ?? 'pending'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?php echo date('M d, Y', strtotime($order['created_at'])); ?></div>
                                    <div class="order-id"><?php echo date('h:i A', strtotime($order['created_at'])); ?></div>
                                </td>
                                <td>
                                    <div class="d-flex">
                                        <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                           class="btn-action btn-view"
                                           title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <div class="dropdown">
                                            <button class="btn-action btn-edit dropdown-toggle" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown"
                                                    title="Update Status">
                                                <i class="bi bi-gear"></i>
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li><h6 class="dropdown-header">Update Status</h6></li>
                                                <?php foreach ($status_colors as $status => $color): ?>
                                                    <li>
                                                        <a class="dropdown-item" 
                                                           href="orders.php?update_status&id=<?php echo $order['id']; ?>&status=<?php echo $status; ?>"
                                                           onclick="return confirm('Change status to <?php echo ucfirst($status); ?>?');">
                                                            <i class="bi bi-circle-fill text-<?php echo $color; ?> me-2"></i>
                                                            <?php echo ucfirst($status); ?>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        
                                        <button class="btn-action btn-delete" 
                                                onclick="deleteOrder(<?php echo $order['id']; ?>, '<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?>')"
                                                title="Delete Order">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">
                                    <div class="empty-state">
                                        <i class="bi bi-cart-x"></i>
                                        <h3>No orders found</h3>
                                        <p class="text-muted">
                                            <?php if (!empty($search) || !empty($status_filter)): ?>
                                                No orders match your search criteria.
                                            <?php else: ?>
                                                There are no orders in the system yet.
                                            <?php endif; ?>
                                        </p>
                                        <?php if (!empty($search) || !empty($status_filter)): ?>
                                            <a href="orders.php" class="btn-admin mt-3">
                                                <i class="bi bi-arrow-counterclockwise me-2"></i>
                                                Clear Filters
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Export Section -->
        <div class="export-card">
            <h3><i class="bi bi-download me-2"></i>Export Orders</h3>
            <div class="export-buttons">
                <a href="export_orders.php?type=csv<?php echo !empty($status_filter) ? '&status='.$status_filter : ''; ?>" class="btn-export csv">
                    <i class="bi bi-file-earmark-excel"></i>
                    Export as CSV
                </a>
                <a href="export_orders.php?type=pdf<?php echo !empty($status_filter) ? '&status='.$status_filter : ''; ?>" class="btn-export pdf">
                    <i class="bi bi-file-earmark-pdf"></i>
                    Export as PDF
                </a>
                <a href="export_orders.php?type=print<?php echo !empty($status_filter) ? '&status='.$status_filter : ''; ?>" class="btn-export print" target="_blank">
                    <i class="bi bi-printer"></i>
                    Print Orders
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Delete order with confirmation
        function deleteOrder(id, orderNumber) {
            if (confirm(`⚠️ WARNING: Are you sure you want to delete order #${orderNumber}?\n\nThis will permanently delete:\n- The order record\n- All order items\n\nThis action cannot be undone.`)) {
                window.location.href = `orders.php?delete&id=${id}`;
            }
        }

        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>