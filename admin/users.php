<?php
session_start();
require_once '../includes/database.php';

// Admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Handle user status changes
if (isset($_GET['block'])) {
    $id = intval($_GET['block']);
    $stmt = mysqli_prepare($conn, "UPDATE users SET status='blocked' WHERE id=? AND role != 'admin'");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: users.php?message=User blocked successfully');
    exit();
}

if (isset($_GET['activate'])) {
    $id = intval($_GET['activate']);
    $stmt = mysqli_prepare($conn, "UPDATE users SET status='active' WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    header('Location: users.php?message=User activated successfully');
    exit();
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Prevent deleting admin users
    $check_stmt = mysqli_prepare($conn, "SELECT role FROM users WHERE id=?");
    mysqli_stmt_bind_param($check_stmt, "i", $id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);
    $user = mysqli_fetch_assoc($result);
    
    if ($user && $user['role'] != 'admin') {
        $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        header('Location: users.php?message=User deleted successfully');
        exit();
    } else {
        header('Location: users.php?error=Cannot delete admin user');
        exit();
    }
}

// Get statistics
$total_users_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM users");
mysqli_stmt_execute($total_users_stmt);
$total_users_result = mysqli_stmt_get_result($total_users_stmt);
$total_users = mysqli_fetch_assoc($total_users_result)['count'];
mysqli_stmt_close($total_users_stmt);

$active_users_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM users WHERE status='active'");
mysqli_stmt_execute($active_users_stmt);
$active_users_result = mysqli_stmt_get_result($active_users_stmt);
$active_users = mysqli_fetch_assoc($active_users_result)['count'];
mysqli_stmt_close($active_users_stmt);

$blocked_users_stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM users WHERE status='blocked'");
mysqli_stmt_execute($blocked_users_stmt);
$blocked_users_result = mysqli_stmt_get_result($blocked_users_stmt);
$blocked_users = mysqli_fetch_assoc($blocked_users_result)['count'];
mysqli_stmt_close($blocked_users_stmt);

// Check if orders table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
$orders_table_exists = mysqli_num_rows($table_check) > 0;

// Get all users with optional order count
if ($orders_table_exists) {
    // Check if total column exists in orders table
    $column_check = mysqli_query($conn, "SHOW COLUMNS FROM orders LIKE 'total'");
    $total_column_exists = mysqli_num_rows($column_check) > 0;
    
    if ($total_column_exists) {
        $sql = "SELECT u.*, 
                       COUNT(DISTINCT o.id) as order_count,
                       COALESCE(SUM(o.total), 0) as total_spent
                FROM users u 
                LEFT JOIN orders o ON u.id = o.user_id 
                GROUP BY u.id 
                ORDER BY u.created_at DESC";
    } else {
        $sql = "SELECT u.*, 
                       COUNT(DISTINCT o.id) as order_count,
                       0 as total_spent
                FROM users u 
                LEFT JOIN orders o ON u.id = o.user_id 
                GROUP BY u.id 
                ORDER BY u.created_at DESC";
    }
} else {
    $sql = "SELECT u.*, 
                   0 as order_count,
                   0 as total_spent
            FROM users u 
            ORDER BY u.created_at DESC";
}
        
$users = mysqli_query($conn, $sql);

if (!$users) {
    die("Database query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - LA FLORA Admin</title>
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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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

        .stat-icon.danger {
            background: rgba(231, 111, 81, 0.1);
            color: var(--danger-color);
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

        /* User Table Card */
        .users-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .users-card-header {
            padding: 24px 30px;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .users-card-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
        }

        .search-box {
            position: relative;
            width: 300px;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .search-box i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }

        /* Table */
        .table-responsive {
            overflow-x: auto;
        }

        .users-table {
            width: 100%;
            margin: 0;
        }

        .users-table thead {
            background: var(--bg-light);
        }

        .users-table th {
            padding: 16px 20px;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #666;
            border: none;
            white-space: nowrap;
        }

        .users-table td {
            padding: 20px;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .users-table tbody tr {
            transition: all 0.3s;
        }

        .users-table tbody tr:hover {
            background: #F8F9FA;
        }

        /* User Avatar */
        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            color: white;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .user-details h6 {
            margin: 0 0 4px 0;
            font-weight: 600;
            color: var(--primary-dark);
        }

        .user-details p {
            margin: 0;
            font-size: 0.85rem;
            color: #666;
        }

        /* Badges */
        .badge-custom {
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .badge-active {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .badge-blocked {
            background: #FFEBEE;
            color: #C62828;
        }

        .badge-admin {
            background: #F3E5F5;
            color: #7B1FA2;
        }

        .badge-user {
            background: #E3F2FD;
            color: #1976D2;
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

        .btn-activate {
            background: #E8F5E9;
            color: #2E7D32;
        }

        .btn-activate:hover {
            background: #2E7D32;
            color: white;
        }

        .btn-block {
            background: #FFF3E0;
            color: #E65100;
        }

        .btn-block:hover {
            background: #E65100;
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

        /* User Details Modal */
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 16px 16px 0 0;
            padding: 20px 24px;
            border: none;
        }

        .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        .detail-row {
            display: flex;
            padding: 16px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-row:last-child {
            border-bottom: none;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            width: 140px;
            flex-shrink: 0;
        }

        .detail-value {
            color: var(--primary-dark);
            font-weight: 500;
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

            .users-card-header {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .search-box {
                width: 100%;
            }

            .users-table {
                font-size: 0.85rem;
            }

            .users-table th,
            .users-table td {
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
            <a href="orders.php" class="nav-link">
                <i class="bi bi-cart-check"></i>
                <span>Orders</span>
            </a>
            <a href="users.php" class="nav-link active">
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
            <h1><i class="bi bi-people me-2"></i>Manage Users</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Users</li>
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
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $total_users; ?></h3>
                    <p>Total Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-person-check"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $active_users; ?></h3>
                    <p>Active Users</p>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon danger">
                    <i class="bi bi-person-x"></i>
                </div>
                <div class="stat-content">
                    <h3><?php echo $blocked_users; ?></h3>
                    <p>Blocked Users</p>
                </div>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="users-card">
            <div class="users-card-header">
                <h2><i class="bi bi-people-fill me-2"></i>All Users</h2>
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search users..." onkeyup="searchUsers()">
                    <i class="bi bi-search"></i>
                </div>
            </div>

            <div class="table-responsive">
                <table class="users-table" id="usersTable">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($users) > 0): ?>
                            <?php while($user = mysqli_fetch_assoc($users)): 
                                $phone = isset($user['phone']) && !empty($user['phone']) ? htmlspecialchars($user['phone']) : 'N/A';
                                $role = isset($user['role']) ? $user['role'] : 'user';
                                $status = isset($user['status']) ? $user['status'] : 'active';
                                $created_at = isset($user['created_at']) ? $user['created_at'] : date('Y-m-d H:i:s');
                                $order_count = isset($user['order_count']) ? $user['order_count'] : 0;
                                $total_spent = isset($user['total_spent']) ? $user['total_spent'] : 0;
                            ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                        </div>
                                        <div class="user-details">
                                            <h6><?php echo htmlspecialchars($user['name']); ?></h6>
                                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>
                                        <div class="mb-1">
                                            <i class="bi bi-telephone text-muted me-1"></i>
                                            <span><?php echo $phone; ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge-custom <?php echo $role == 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                        <i class="bi bi-<?php echo $role == 'admin' ? 'shield-check' : 'person'; ?>"></i>
                                        <?php echo ucfirst($role); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-custom <?php echo $status == 'active' ? 'badge-active' : 'badge-blocked'; ?>">
                                        <i class="bi bi-circle-fill"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="text-nowrap">
                                        <i class="bi bi-calendar text-muted me-1"></i>
                                        <?php echo date('M d, Y', strtotime($created_at)); ?>
                                    </div>
                                </td>
                                <td>
                                    <strong><?php echo $order_count; ?></strong> orders
                                </td>
                                <td>
                                    <strong class="text-success">$<?php echo number_format($total_spent, 2); ?></strong>
                                </td>
                                <td>
                                    <div class="d-flex">
                                        <button class="btn-action btn-view" 
                                                onclick="viewUserDetails(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>', '<?php echo htmlspecialchars(addslashes($user['email'])); ?>', '<?php echo $phone; ?>', '<?php echo ucfirst($role); ?>', '<?php echo ucfirst($status); ?>', '<?php echo date('M d, Y', strtotime($created_at)); ?>', <?php echo $order_count; ?>, <?php echo $total_spent; ?>)"
                                                title="View Details">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <?php if($role != 'admin'): ?>
                                            <?php if($status == 'active'): ?>
                                                <button class="btn-action btn-block" 
                                                        onclick="blockUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>')"
                                                        title="Block User">
                                                    <i class="bi bi-slash-circle"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn-action btn-activate" 
                                                        onclick="activateUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>')"
                                                        title="Activate User">
                                                    <i class="bi bi-check-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button class="btn-action btn-delete" 
                                                    onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['name'])); ?>')"
                                                    title="Delete User">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="bi bi-people"></i>
                                        <h3>No users found</h3>
                                        <p class="text-muted">There are no registered users in the system.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- User Details Modal -->
    <div class="modal fade" id="userDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-person-circle me-2"></i>User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="detail-row">
                        <div class="detail-label">Name:</div>
                        <div class="detail-value" id="modal-name"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value" id="modal-email"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Phone:</div>
                        <div class="detail-value" id="modal-phone"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Role:</div>
                        <div class="detail-value" id="modal-role"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Status:</div>
                        <div class="detail-value" id="modal-status"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Joined:</div>
                        <div class="detail-value" id="modal-joined"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Total Orders:</div>
                        <div class="detail-value" id="modal-orders"></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Total Spent:</div>
                        <div class="detail-value" id="modal-spent"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search users
        function searchUsers() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('usersTable');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell) {
                        const textValue = cell.textContent || cell.innerText;
                        if (textValue.toUpperCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }

                row.style.display = found ? '' : 'none';
            }
        }

        // View user details
        function viewUserDetails(id, name, email, phone, role, status, joined, orders, spent) {
            document.getElementById('modal-name').textContent = name;
            document.getElementById('modal-email').textContent = email;
            document.getElementById('modal-phone').textContent = phone;
            document.getElementById('modal-role').textContent = role;
            document.getElementById('modal-status').textContent = status;
            document.getElementById('modal-joined').textContent = joined;
            document.getElementById('modal-orders').textContent = orders + ' orders';
            document.getElementById('modal-spent').textContent = '$' + spent.toFixed(2);

            const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
            modal.show();
        }

        // Block user
        function blockUser(id, name) {
            if (confirm(`Are you sure you want to block "${name}"?\n\nThis user will no longer be able to access their account.`)) {
                window.location.href = `users.php?block=${id}`;
            }
        }

        // Activate user
        function activateUser(id, name) {
            if (confirm(`Are you sure you want to activate "${name}"?`)) {
                window.location.href = `users.php?activate=${id}`;
            }
        }

        // Delete user
        function deleteUser(id, name) {
            if (confirm(`⚠️ WARNING: Are you sure you want to permanently delete "${name}"?\n\nThis action cannot be undone and will delete all user data.`)) {
                window.location.href = `users.php?delete=${id}`;
            }
        }

        // Auto-hide alerts
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