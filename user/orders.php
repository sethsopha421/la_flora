<?php
// orders.php
session_start();
require_once '../includes/database.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Pagination variables
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filter variables
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build WHERE clause
$where_clause = "WHERE o.user_id = ?";
$params = [$user_id];
$param_types = "i";

if (!empty($status_filter) && $status_filter !== 'all') {
    $where_clause .= " AND o.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($date_from)) {
    $where_clause .= " AND DATE(o.created_at) >= ?";
    $params[] = $date_from;
    $param_types .= "s";
}

if (!empty($date_to)) {
    $where_clause .= " AND DATE(o.created_at) <= ?";
    $params[] = $date_to;
    $param_types .= "s";
}

// Get total orders count
$count_sql = "SELECT COUNT(*) as total FROM orders o $where_clause";
$count_stmt = mysqli_prepare($conn, $count_sql);

if ($count_stmt) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_data = mysqli_fetch_assoc($count_result);
    $total_orders = $total_data['total'] ?? 0;
    $total_pages = ceil($total_orders / $limit);
    mysqli_stmt_close($count_stmt);
} else {
    $total_orders = 0;
    $total_pages = 1;
}

// Get orders with pagination
$orders_sql = "SELECT o.*, 
                      COUNT(oi.id) as items_count
               FROM orders o
               LEFT JOIN order_items oi ON o.id = oi.order_id
               $where_clause
               GROUP BY o.id
               ORDER BY o.created_at DESC
               LIMIT ? OFFSET ?";

// Add limit and offset to parameters
$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

$orders_stmt = mysqli_prepare($conn, $orders_sql);
$orders = [];

if ($orders_stmt) {
    mysqli_stmt_bind_param($orders_stmt, $param_types, ...$params);
    mysqli_stmt_execute($orders_stmt);
    $orders_result = mysqli_stmt_get_result($orders_stmt);
    
    while ($row = mysqli_fetch_assoc($orders_result)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($orders_stmt);
}

// Get order status counts
$status_counts_sql = "SELECT status, COUNT(*) as count 
                      FROM orders 
                      WHERE user_id = ? 
                      GROUP BY status";
$status_stmt = mysqli_prepare($conn, $status_counts_sql);
$status_counts = [];

if ($status_stmt) {
    mysqli_stmt_bind_param($status_stmt, "i", $user_id);
    mysqli_stmt_execute($status_stmt);
    $status_result = mysqli_stmt_get_result($status_stmt);
    
    while ($row = mysqli_fetch_assoc($status_result)) {
        $status_counts[$row['status']] = $row['count'];
    }
    mysqli_stmt_close($status_stmt);
}

// Calculate total spent
$total_spent_sql = "SELECT SUM(total_amount) as total FROM orders WHERE user_id = ? AND status = 'delivered'";
$total_spent_stmt = mysqli_prepare($conn, $total_spent_sql);
$total_spent = 0;

if ($total_spent_stmt) {
    mysqli_stmt_bind_param($total_spent_stmt, "i", $user_id);
    mysqli_stmt_execute($total_spent_stmt);
    $total_spent_result = mysqli_stmt_get_result($total_spent_stmt);
    $total_spent_row = mysqli_fetch_assoc($total_spent_result);
    $total_spent = $total_spent_row['total'] ?? 0;
    mysqli_stmt_close($total_spent_stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - La Flora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2A5C4A;
            --primary-dark: #1E4033;
            --primary-light: #E8F3E8;
            --secondary: #D4A373;
            --danger: #E76F51;
            --success: #2A9D8F;
            --warning: #F4A261;
            --info: #2874A6;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .filter-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .orders-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .order-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }

        .order-item:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-processing { background-color: #cce5ff; color: #004085; }
        .status-shipped { background-color: #d1e7dd; color: #0f5132; }
        .status-delivered { background-color: #d4edda; color: #155724; }
        .status-cancelled { background-color: #f8d7da; color: #721c24; }

        .payment-badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            display: inline-block;
        }

        .payment-pending { background-color: #fff3cd; color: #856404; }
        .payment-completed { background-color: #d4edda; color: #155724; }
        .payment-failed { background-color: #f8d7da; color: #721c24; }

        .filter-badge {
            cursor: pointer;
            transition: all 0.3s;
        }

        .filter-badge:hover {
            transform: scale(1.05);
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }

        .empty-state-icon {
            font-size: 4rem;
            color: #6c757d;
            margin-bottom: 20px;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background: var(--primary);
            color: white;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .pagination .page-link {
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .order-item {
                padding: 15px;
            }
            
            .order-actions {
                margin-top: 15px;
            }
            
            .order-actions .d-flex {
                justify-content: center !important;
            }
        }
    </style>
</head>
<body>
    
    
    <div class="orders-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1><i class="fas fa-shopping-cart me-2"></i>My Orders</h1>
                    <p class="mb-0">Track and manage all your flower orders</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="../shop.php" class="btn btn-light">
                        <i class="fas fa-shopping-bag me-1"></i> Continue Shopping
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-card">
            <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filter Orders</h5>
            
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Order Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $status_filter === 'all' || empty($status_filter) ? 'selected' : ''; ?>>All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Apply Filters
                    </button>
                </div>
            </form>
            
            <!-- Quick Filter Badges -->
            <div class="mt-4">
                <h6 class="mb-2">Quick Filters:</h6>
                <div class="d-flex flex-wrap gap-2">
                    <a href="?status=all" class="badge bg-secondary filter-badge p-2 text-decoration-none">
                        All <span class="badge bg-light text-dark ms-1"><?php echo $total_orders; ?></span>
                    </a>
                    <?php 
                    $status_colors = [
                        'pending' => 'warning',
                        'processing' => 'info',
                        'shipped' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger'
                    ];
                    foreach ($status_counts as $status => $count): 
                        $color = $status_colors[$status] ?? 'secondary';
                    ?>
                        <a href="?status=<?php echo $status; ?>" 
                           class="badge bg-<?php echo $color; ?> filter-badge p-2 text-decoration-none">
                            <?php echo ucfirst($status); ?> 
                            <span class="badge bg-light text-dark ms-1"><?php echo $count; ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Orders List -->
        <div class="orders-card">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    <?php echo $status_filter && $status_filter !== 'all' ? ucfirst($status_filter) . ' Orders' : 'All Orders'; ?>
                    <span class="badge bg-primary ms-2"><?php echo $total_orders; ?></span>
                </h5>
                
                <?php if ($total_orders > 0): ?>
                    <div class="text-muted">
                        Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $total_orders); ?> of <?php echo $total_orders; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h4>No Orders Found</h4>
                    <p class="text-muted mb-4">
                        <?php if ($status_filter && $status_filter !== 'all'): ?>
                            You don't have any <?php echo $status_filter; ?> orders.
                        <?php else: ?>
                            You haven't placed any orders yet.
                        <?php endif; ?>
                    </p>
                    <a href="../shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag me-1"></i> Start Shopping
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): 
                    $status_class = 'status-' . $order['status'];
                    $payment_class = 'payment-' . $order['payment_status'];
                ?>
                    <div class="order-item">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="d-flex align-items-center mb-2 flex-wrap gap-2">
                                    <h6 class="mb-0 me-3">Order #<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></h6>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                    <span class="payment-badge <?php echo $payment_class; ?>">
                                        Payment: <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </div>
                                
                                <div class="row text-muted small mb-2">
                                    <div class="col-auto">
                                        <i class="far fa-calendar me-1"></i>
                                        <?php echo date('F j, Y', strtotime($order['created_at'])); ?>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-box me-1"></i>
                                        <?php echo $order['items_count'] ?? 0; ?> items
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-credit-card me-1"></i>
                                        <?php echo strtoupper($order['payment_method']); ?>
                                    </div>
                                </div>
                                
                                <div class="mt-2">
                                    <strong>Total:</strong> $<?php echo number_format($order['total_amount'], 2); ?>
                                </div>
                            </div>
                            
                            <div class="col-md-4 order-actions">
                                <div class="d-flex gap-2 justify-content-md-end">
                                    <a href="order_details.php?id=<?php echo $order['id']; ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i> Details
                                    </a>
                                    
                                    <?php if ($order['status'] === 'pending'): ?>
                                        <a href="cancel_order.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-outline-danger btn-sm"
                                           onclick="return confirm('Are you sure you want to cancel this order?')">
                                            <i class="fas fa-times me-1"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['status'] === 'delivered'): ?>
                                        <a href="../review.php?order=<?php echo $order['id']; ?>" 
                                           class="btn btn-outline-success btn-sm">
                                            <i class="fas fa-star me-1"></i> Review
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($order['payment_status'] === 'pending' && $order['status'] === 'pending'): ?>
                                        <a href="../checkout.php?order=<?php echo $order['id']; ?>" 
                                           class="btn btn-outline-warning btn-sm">
                                            <i class="fas fa-credit-card me-1"></i> Pay Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $status_filter ? '&status=' . $status_filter : ''; ?><?php echo $date_from ? '&date_from=' . $date_from : ''; ?><?php echo $date_to ? '&date_to=' . $date_to : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Quick Stats -->
        <div class="row mt-4 g-3">
            <div class="col-md-3 col-6">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                        <h5><?php echo $status_counts['pending'] ?? 0; ?></h5>
                        <p class="text-muted mb-0">Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-truck fa-2x text-info mb-3"></i>
                        <h5><?php echo $status_counts['shipped'] ?? 0; ?></h5>
                        <p class="text-muted mb-0">Shipped</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                        <h5><?php echo $status_counts['delivered'] ?? 0; ?></h5>
                        <p class="text-muted mb-0">Delivered</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card text-center h-100">
                    <div class="card-body">
                        <i class="fas fa-dollar-sign fa-2x text-primary mb-3"></i>
                        <h5>$<?php echo number_format($total_spent, 2); ?></h5>
                        <p class="text-muted mb-0">Total Spent</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set max date for date filters
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const dateTo = document.querySelector('input[name="date_to"]');
            if (dateTo) dateTo.max = today;
        });
    </script>
</body>
</html>