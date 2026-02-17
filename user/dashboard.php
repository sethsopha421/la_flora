<?php
// dashboard.php
session_start();
require_once '../includes/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

// ================================
// FETCH USER DATA - FIXED
// ================================
$sql_user = "SELECT id, name, email, role, created_at FROM users WHERE id = ?";
$stmt_user = mysqli_prepare($conn, $sql_user);

if (!$stmt_user) {
    die("Database error: " . mysqli_error($conn));
}

mysqli_stmt_bind_param($stmt_user, "i", $user_id);

if (!mysqli_stmt_execute($stmt_user)) {
    die("Execution error: " . mysqli_stmt_error($stmt_user));
}

$result_user = mysqli_stmt_get_result($stmt_user);

if (!$result_user) {
    die("Result error: " . mysqli_stmt_error($stmt_user));
}

$user = mysqli_fetch_assoc($result_user);  // FIXED LINE 23
mysqli_stmt_close($stmt_user);

if (!$user) {
    session_destroy();
    header('Location: login.php?error=user_not_found');
    exit();
}

// ================================
// DASHBOARD STATISTICS
// ================================
$stats = [];

// Total Orders
$sql_orders = "SELECT COUNT(*) as total_orders, 
                      SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                      SUM(total_amount) as total_spent
               FROM orders 
               WHERE user_id = ?";
$stmt_orders = mysqli_prepare($conn, $sql_orders);
if ($stmt_orders) {
    mysqli_stmt_bind_param($stmt_orders, "i", $user_id);
    mysqli_stmt_execute($stmt_orders);
    $result_orders = mysqli_stmt_get_result($stmt_orders);
    $stats['orders'] = mysqli_fetch_assoc($result_orders) ?? ['total_orders' => 0, 'delivered_orders' => 0, 'total_spent' => 0];
    mysqli_stmt_close($stmt_orders);
} else {
    $stats['orders'] = ['total_orders' => 0, 'delivered_orders' => 0, 'total_spent' => 0];
}

// Recent Orders (last 5)
$sql_recent_orders = "SELECT o.id, o.total_amount, o.status, o.created_at, 
                             COUNT(oi.id) as items_count
                      FROM orders o
                      LEFT JOIN order_items oi ON o.id = oi.order_id
                      WHERE o.user_id = ?
                      GROUP BY o.id
                      ORDER BY o.created_at DESC
                      LIMIT 5";
$stmt_recent = mysqli_prepare($conn, $sql_recent_orders);
$recent_orders = [];
if ($stmt_recent) {
    mysqli_stmt_bind_param($stmt_recent, "i", $user_id);
    mysqli_stmt_execute($stmt_recent);
    $result_recent = mysqli_stmt_get_result($stmt_recent);
    while ($row = mysqli_fetch_assoc($result_recent)) {
        $recent_orders[] = $row;
    }
    mysqli_stmt_close($stmt_recent);
}

// Favorite Categories (for users)
$fav_categories = [];
if ($user_role === 'user') {
    $sql_fav_categories = "SELECT c.name, COUNT(oi.id) as purchase_count
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           JOIN categories c ON p.category_id = c.id
                           JOIN orders o ON oi.order_id = o.id
                           WHERE o.user_id = ?
                           GROUP BY c.id
                           ORDER BY purchase_count DESC
                           LIMIT 3";
    $stmt_cats = mysqli_prepare($conn, $sql_fav_categories);
    if ($stmt_cats) {
        mysqli_stmt_bind_param($stmt_cats, "i", $user_id);
        mysqli_stmt_execute($stmt_cats);
        $result_cats = mysqli_stmt_get_result($stmt_cats);
        while ($row = mysqli_fetch_assoc($result_cats)) {
            $fav_categories[] = $row;
        }
        mysqli_stmt_close($stmt_cats);
    }
}

// Admin Statistics (if user is admin)
$admin_stats = [];
if ($user_role === 'admin') {
    // Total Users
    $sql_admin_stats = "SELECT 
                        (SELECT COUNT(*) FROM users WHERE role = 'user') as total_users,
                        (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()) as today_orders,
                        (SELECT SUM(total_amount) FROM orders WHERE MONTH(created_at) = MONTH(CURDATE())) as monthly_revenue,
                        (SELECT COUNT(*) FROM products WHERE stock < 10) as low_stock_items";
    $stmt_admin = mysqli_prepare($conn, $sql_admin_stats);
    if ($stmt_admin) {
        mysqli_stmt_execute($stmt_admin);
        $result_admin = mysqli_stmt_get_result($stmt_admin);
        $admin_stats = mysqli_fetch_assoc($result_admin) ?? ['total_users' => 0, 'today_orders' => 0, 'monthly_revenue' => 0, 'low_stock_items' => 0];
        mysqli_stmt_close($stmt_admin);
    }
}

// ================================
// FETCH RECENT ACTIVITIES (Notifications)
// ================================
$activities = [];

// Order status updates in last 7 days
$sql_activities = "SELECT 'order' as type, 
                          CONCAT('Order #', o.id, ' status updated to: ', o.status) as message,
                          o.updated_at as date
                   FROM orders o
                   WHERE o.user_id = ? 
                   AND o.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                   AND o.updated_at != o.created_at
                   UNION
                   SELECT 'system' as type,
                          'Welcome to La Flora Dashboard' as message,
                          u.created_at as date
                   FROM users u
                   WHERE u.id = ?
                   ORDER BY date DESC
                   LIMIT 10";
$stmt_act = mysqli_prepare($conn, $sql_activities);
if ($stmt_act) {
    mysqli_stmt_bind_param($stmt_act, "ii", $user_id, $user_id);
    mysqli_stmt_execute($stmt_act);
    $result_act = mysqli_stmt_get_result($stmt_act);
    while ($row = mysqli_fetch_assoc($result_act)) {
        $activities[] = $row;
    }
    mysqli_stmt_close($stmt_act);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - La Flora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --success-color: #28a745;
            --info-color: #17a2b8;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            background-color: #f5f7fb;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-container {
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            min-height: 100vh;
            position: fixed;
            width: 250px;
            transition: all 0.3s;
            box-shadow: 3px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-profile {
            text-align: center;
            padding: 20px 10px;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 32px;
            border: 3px solid white;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.1);
            text-decoration: none;
        }
        
        .nav-link i {
            width: 24px;
            margin-right: 10px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .top-navbar {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .page-title h1 {
            color: var(--dark-color);
            font-weight: 600;
            margin: 0;
        }
        
        .page-title p {
            color: #6c757d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        /* Stats Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-color);
            line-height: 1;
        }
        
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            margin-top: 5px;
        }
        
        /* Recent Orders Table */
        .recent-orders {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        
        /* Activity Feed */
        .activity-feed {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        
        .activity-item {
            padding: 15px 0;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: flex-start;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            flex-shrink: 0;
        }
        
        .activity-content {
            flex: 1;
        }
        
        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .quick-stat {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-navbar {
                flex-direction: column;
                gap: 15px;
            }
        }
        
        .badge {
            padding: 6px 12px;
            font-weight: 500;
        }
        
        .order-badge {
            font-size: 0.75rem;
            padding: 4px 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h3><i class="fas fa-seedling me-2"></i>La Flora</h3>
                <small class="text-white-50">Flower Shop Dashboard</small>
            </div>
            
            <div class="user-profile">
                <div class="user-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h5 class="mb-1"><?php echo htmlspecialchars($user['name'] ?? 'User'); ?></h5>
                <p class="text-white-50 mb-2"><?php echo htmlspecialchars($user['email'] ?? 'email@example.com'); ?></p>
                <span class="badge bg-light text-dark">
                    <i class="fas fa-user-tag me-1"></i>
                    <?php echo ucfirst($user['role'] ?? 'user'); ?>
                </span>
            </div>
            
            <nav class="nav flex-column mt-3 px-3">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="../shop.php" class="nav-link">
                    <i class="fas fa-shopping-bag"></i> Shop
                </a>
                <a href="orders.php" class="nav-link">
                    <i class="fas fa-shopping-cart"></i> My Orders
                </a>
                
                <?php if ($user_role === 'admin'): ?>
                <div class="mt-4 mb-2 px-2 text-white-50 small">ADMIN PANEL</div>
                <a href="../admin/products.php" class="nav-link">
                    <i class="fas fa-box"></i> Products
                </a>
                <a href="../admin/users.php" class="nav-link">
                    <i class="fas fa-users"></i> Users
                </a>
                <a href="../admin/orders.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i> All Orders
                </a>
                <?php endif; ?>
                
                <div class="mt-4 mb-2 px-2 text-white-50 small">ACCOUNT</div>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-cog"></i> Settings
                </a>
                <a href="logout.php" class="nav-link text-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
            
            <div class="sidebar-footer mt-4 p-3 text-center text-white-50 small">
                <p>Member since:<br><?php echo date('M Y', strtotime($user['created_at'] ?? 'now')); ?></p>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content" id="mainContent">
            <!-- Top Navbar -->
            <div class="top-navbar">
                <div class="page-title">
                    <h1><i class="fas fa-tachometer-alt me-2 text-primary"></i>Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($user['name'] ?? 'User'); ?>! Here's what's happening.</p>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-bell"></i>
                            <?php if (count($activities) > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo count($activities); ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <h6 class="dropdown-header">Recent Activities</h6>
                            <?php foreach (array_slice($activities, 0, 3) as $activity): ?>
                                <a class="dropdown-item" href="#">
                                    <small><?php echo htmlspecialchars($activity['message']); ?></small>
                                </a>
                            <?php endforeach; ?>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="orders.php">View all activities</a>
                        </div>
                    </div>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars(explode(' ', $user['name'] ?? 'User')[0]); ?>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="profile.php">
                                <i class="fas fa-user me-2"></i> My Profile
                            </a>
                            <a class="dropdown-item" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i> My Orders
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </div>
                    </div>
                    
                    <button class="btn btn-light d-md-none" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h2 class="stat-value"><?php echo $stats['orders']['total_orders'] ?? 0; ?></h2>
                        <p class="stat-label">Total Orders</p>
                        <small class="text-muted"><?php echo $stats['orders']['delivered_orders'] ?? 0; ?> delivered</small>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <h2 class="stat-value">$<?php echo number_format($stats['orders']['total_spent'] ?? 0, 2); ?></h2>
                        <p class="stat-label">Total Spent</p>
                        <small class="text-muted">Lifetime spending</small>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-info bg-opacity-10 text-info">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <h2 class="stat-value"><?php echo date('j M'); ?></h2>
                        <p class="stat-label">Today's Date</p>
                        <small class="text-muted"><?php echo date('l'); ?></small>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h2 class="stat-value"><?php echo count($recent_orders); ?></h2>
                        <p class="stat-label">Recent Orders</p>
                        <small class="text-muted">Last 5 orders</small>
                    </div>
                </div>
            </div>
            
            <!-- Main Content Row -->
            <div class="row">
                <!-- Recent Orders -->
                <div class="col-lg-8 mb-4">
                    <div class="recent-orders">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="mb-0">Recent Orders</h5>
                            <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        
                        <?php if (!empty($recent_orders)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Date</th>
                                            <th>Items</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): 
                                            $status_class = '';
                                            switch($order['status']) {
                                                case 'pending': $status_class = 'warning'; break;
                                                case 'processing': $status_class = 'info'; break;
                                                case 'shipped': $status_class = 'primary'; break;
                                                case 'delivered': $status_class = 'success'; break;
                                                case 'cancelled': $status_class = 'danger'; break;
                                                default: $status_class = 'secondary';
                                            }
                                        ?>
                                            <tr>
                                                <td><strong>#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo $order['items_count']; ?> items</td>
                                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $status_class; ?> order-badge">
                                                        <?php echo ucfirst($order['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <h5>No orders yet</h5>
                                <p class="text-muted">You haven't placed any orders</p>
                                <a href="../shop.php" class="btn btn-primary">Start Shopping</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Right Column -->
                <div class="col-lg-4">
                    <!-- Activity Feed -->
                    <div class="activity-feed mb-4">
                        <h5 class="mb-4">Recent Activity</h5>
                        
                        <?php if (!empty($activities)): ?>
                            <?php foreach (array_slice($activities, 0, 5) as $activity): ?>
                                <div class="activity-item">
                                    <div class="activity-icon bg-light text-primary">
                                        <?php if ($activity['type'] === 'order'): ?>
                                            <i class="fas fa-shopping-cart"></i>
                                        <?php else: ?>
                                            <i class="fas fa-info-circle"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="activity-content">
                                        <p class="mb-1"><?php echo htmlspecialchars($activity['message']); ?></p>
                                        <small class="text-muted">
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo date('M j, g:i A', strtotime($activity['date'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No recent activities</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Quick Stats -->
                    <div class="quick-stats">
                        <?php if ($user_role === 'user' && !empty($fav_categories)): ?>
                            <?php foreach ($fav_categories as $cat): ?>
                                <div class="quick-stat">
                                    <div class="stat-icon bg-light text-success mb-2 mx-auto" style="width: 40px; height: 40px;">
                                        <i class="fas fa-leaf"></i>
                                    </div>
                                    <h6 class="mb-1"><?php echo $cat['name']; ?></h6>
                                    <small class="text-muted"><?php echo $cat['purchase_count']; ?> purchases</small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if ($user_role === 'admin'): ?>
                            <div class="quick-stat">
                                <h6 class="mb-1"><?php echo $admin_stats['total_users'] ?? 0; ?></h6>
                                <small class="text-muted">Total Users</small>
                            </div>
                            <div class="quick-stat">
                                <h6 class="mb-1"><?php echo $admin_stats['today_orders'] ?? 0; ?></h6>
                                <small class="text-muted">Today's Orders</small>
                            </div>
                            <div class="quick-stat">
                                <h6 class="mb-1">$<?php echo number_format($admin_stats['monthly_revenue'] ?? 0, 0); ?></h6>
                                <small class="text-muted">Monthly Revenue</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="mt-5 pt-4 border-top text-center text-muted">
                <p class="mb-0">La Flora Dashboard &copy; <?php echo date('Y'); ?> 
                    | <a href="../index.php" class="text-decoration-none">Visit Shop</a>
                    | <a href="profile.php" class="text-decoration-none">My Account</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle for Mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Auto-dismiss alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>