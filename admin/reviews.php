<?php
// admin/reviews.php
session_start();
require_once '../includes/database.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Handle delete review
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $delete_query = "DELETE FROM reviews WHERE id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "i", $id);
    
    if (mysqli_stmt_execute($delete_stmt)) {
        header('Location: reviews.php?message=Review deleted successfully');
    } else {
        header('Location: reviews.php?error=Failed to delete review');
    }
    exit();
}

// Get filter parameters
$product_filter = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
$rating_filter = isset($_GET['rating']) ? intval($_GET['rating']) : 0;

// Build main query
$query = "SELECT r.*, 
                 p.name as product_name, 
                 p.image as product_image,
                 u.name as user_name, 
                 u.email as user_email
          FROM reviews r
          JOIN products p ON r.product_id = p.id
          JOIN users u ON r.user_id = u.id
          WHERE 1=1";

$count_query = "SELECT COUNT(*) as total FROM reviews r WHERE 1=1";
$params = [];
$types = "";

if ($product_filter > 0) {
    $query .= " AND r.product_id = ?";
    $count_query .= " AND r.product_id = ?";
    $params[] = $product_filter;
    $types .= "i";
}

if ($rating_filter > 0) {
    $query .= " AND r.rating = ?";
    $count_query .= " AND r.rating = ?";
    $params[] = $rating_filter;
    $types .= "i";
}

$query .= " ORDER BY r.created_at DESC";

// Prepare and execute main query
$stmt = mysqli_prepare($conn, $query);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$reviews = mysqli_stmt_get_result($stmt);

// Get total count for stats
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$count_row = mysqli_fetch_assoc($count_result);
$total_filtered = $count_row['total'] ?? 0;

// Get all products for filter dropdown
$products = mysqli_query($conn, "SELECT id, name FROM products ORDER BY name");

// Get rating statistics
$stats_query = "SELECT 
                    COUNT(*) as total_reviews,
                    COALESCE(AVG(rating), 0) as avg_rating,
                    SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                    SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                    SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                    SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                    SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM reviews";
$stats_result = mysqli_query($conn, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Initialize stats with default values if null
if (!$stats) {
    $stats = [
        'total_reviews' => 0,
        'avg_rating' => 0,
        'five_star' => 0,
        'four_star' => 0,
        'three_star' => 0,
        'two_star' => 0,
        'one_star' => 0
    ];
}

// Get unique reviewers count
$reviewers_query = "SELECT COUNT(DISTINCT user_id) as count FROM reviews";
$reviewers_result = mysqli_query($conn, $reviewers_query);
$reviewers_row = mysqli_fetch_assoc($reviewers_result);
$reviewers_count = $reviewers_row['count'] ?? 0;

// Get recent reviews for dashboard
$recent_query = "SELECT r.*, p.name as product_name, u.name as user_name 
                 FROM reviews r
                 JOIN products p ON r.product_id = p.id
                 JOIN users u ON r.user_id = u.id
                 ORDER BY r.created_at DESC
                 LIMIT 5";
$recent_reviews = mysqli_query($conn, $recent_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews Management - LA FLORA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2A5934;
            --primary-dark: #1E4025;
            --primary-light: #4A7856;
            --secondary-color: #D4A373;
            --danger-color: #E76F51;
            --success-color: #2A9D8F;
            --warning-color: #F4A261;
            --star-color: #FFC107;
        }

        body {
            background: #F8F9FA;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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

        .sidebar-nav .nav-link:hover,
        .sidebar-nav .nav-link.active {
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
            padding: 24px 32px;
            min-height: 100vh;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            border-radius: 16px;
            padding: 16px 24px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .admin-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .admin-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.2rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.8rem;
        }

        .stat-info h3 {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 0;
        }

        .stat-info p {
            color: #6C757D;
            margin: 4px 0 0;
            font-weight: 500;
        }

        /* Rating Distribution */
        .rating-distribution {
            background: white;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 30px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .rating-bars {
            margin-top: 20px;
        }

        .rating-row {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .rating-label {
            width: 60px;
            font-weight: 600;
            color: var(--primary-color);
        }

        .rating-bar-container {
            flex: 1;
            height: 10px;
            background: #E9ECEF;
            border-radius: 10px;
            overflow: hidden;
        }

        .rating-bar {
            height: 100%;
            background: var(--star-color);
            border-radius: 10px;
            transition: width 0.3s;
        }

        .rating-count {
            width: 50px;
            text-align: right;
            color: #6C757D;
            font-size: 14px;
        }

        /* Filters */
        .filters-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        .filter-label {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-outline-secondary {
            border: 2px solid #E9ECEF;
            padding: 8px 16px;
            border-radius: 12px;
        }

        /* Reviews Table */
        .reviews-table {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table thead th {
            background: #F8F9FA;
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 2px solid #E9ECEF;
            padding: 16px 12px;
            text-align: left;
        }

        .table tbody td {
            padding: 16px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #E9ECEF;
        }

        .review-rating {
            color: var(--star-color);
            font-size: 14px;
            white-space: nowrap;
        }

        .product-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid var(--primary-light);
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 600;
            color: var(--primary-color);
        }

        .user-email {
            font-size: 12px;
            color: #6C757D;
        }

        .review-comment {
            max-width: 300px;
            font-size: 14px;
            color: #2C3E50;
            line-height: 1.6;
        }

        .review-meta {
            font-size: 12px;
            color: #6C757D;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s;
            border: none;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-view {
            background: #2874A6;
        }

        .btn-delete {
            background: var(--danger-color);
        }

        .btn-action:hover {
            transform: scale(1.1);
            color: white;
        }

        /* Search and Filter Bar */
        .search-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .search-input {
            padding: 8px 16px;
            border: 2px solid #E9ECEF;
            border-radius: 12px;
            width: 300px;
            font-size: 14px;
        }

        .search-input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .items-per-page {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .items-per-page select {
            padding: 8px;
            border: 2px solid #E9ECEF;
            border-radius: 8px;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .page-btn {
            padding: 8px 12px;
            border: 1px solid #E9ECEF;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .page-btn:hover,
        .page-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Recent Reviews Cards */
        .recent-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            height: 100%;
            transition: transform 0.3s;
        }

        .recent-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .recent-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .recent-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
                padding: 16px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .search-bar {
                flex-direction: column;
                gap: 10px;
            }
            
            .search-input {
                width: 100%;
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
            <a href="users.php" class="nav-link">
                <i class="bi bi-people"></i>
                <span>Users</span>
            </a>
            <a href="reviews.php" class="nav-link active">
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
        <!-- Top Bar -->
        <div class="top-bar">
            <h1 class="page-title">
                <i class="bi bi-star me-2"></i>
                Reviews Management
            </h1>
            
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($_SESSION['admin_name'] ?? 'A', 0, 1)); ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></strong>
                    <small class="d-block text-muted">Administrator</small>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-chat-dots"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['total_reviews']); ?></h3>
                    <p>Total Reviews</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($stats['avg_rating'], 1); ?></h3>
                    <p>Average Rating</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-emoji-smile"></i>
                </div>
               <div class="stat-info">
                    <h3><?php echo isset($stats['five_star']) ? number_format($stats['five_star']) : '0'; ?></h3>
                    <p>5-Star Reviews</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo number_format($reviewers_count); ?></h3>
                    <p>Reviewers</p>
                </div>
            </div>
        </div>

        <!-- Rating Distribution -->
        <?php if ($stats['total_reviews'] > 0): ?>
        <div class="rating-distribution">
            <h5 class="mb-3"><i class="bi bi-bar-chart me-2"></i>Rating Distribution</h5>
            <div class="rating-bars">
                <?php 
                $ratings = [
                    5 => $stats['five_star'],
                    4 => $stats['four_star'],
                    3 => $stats['three_star'],
                    2 => $stats['two_star'],
                    1 => $stats['one_star']
                ];
                
                foreach ($ratings as $star => $count):
                    $percentage = ($stats['total_reviews'] > 0) ? round(($count / $stats['total_reviews']) * 100) : 0;
                ?>
                <div class="rating-row">
                    <div class="rating-label">
                        <?php echo $star; ?> <i class="bi bi-star-fill" style="color: var(--star-color);"></i>
                    </div>
                    <div class="rating-bar-container">
                        <div class="rating-bar" style="width: <?php echo $percentage; ?>%;"></div>
                    </div>
                    <div class="rating-count"><?php echo $count; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <label class="filter-label">Filter by Product</label>
                    <select name="product_id" class="form-select">
                        <option value="0">All Products</option>
                        <?php 
                        mysqli_data_seek($products, 0); 
                        while($product = mysqli_fetch_assoc($products)): 
                        ?>
                            <option value="<?php echo $product['id']; ?>" 
                                <?php echo $product_filter == $product['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-5">
                    <label class="filter-label">Filter by Rating</label>
                    <select name="rating" class="form-select">
                        <option value="0">All Ratings</option>
                        <option value="5" <?php echo $rating_filter == 5 ? 'selected' : ''; ?>>5 Stars</option>
                        <option value="4" <?php echo $rating_filter == 4 ? 'selected' : ''; ?>>4 Stars</option>
                        <option value="3" <?php echo $rating_filter == 3 ? 'selected' : ''; ?>>3 Stars</option>
                        <option value="2" <?php echo $rating_filter == 2 ? 'selected' : ''; ?>>2 Stars</option>
                        <option value="1" <?php echo $rating_filter == 1 ? 'selected' : ''; ?>>1 Star</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel me-2"></i>Filter
                    </button>
                </div>
            </form>
            
            <?php if ($product_filter > 0 || $rating_filter > 0): ?>
            <div class="mt-3">
                <a href="reviews.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-x-circle me-2"></i>Clear Filters
                </a>
                <span class="text-muted ms-2">Found <?php echo $total_filtered; ?> reviews</span>
            </div>
            <?php endif; ?>
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
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Reviews Table -->
        <div class="reviews-table">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="mb-0"><i class="bi bi-table me-2"></i>All Reviews</h5>
                <span class="text-muted"><?php echo mysqli_num_rows($reviews); ?> reviews found</span>
            </div>

            <?php if (mysqli_num_rows($reviews) > 0): ?>
                <!-- Search Bar -->
                <div class="search-bar">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search reviews...">
                    <div class="items-per-page">
                        <label>Show:</label>
                        <select id="itemsPerPage">
                            <option value="5">5</option>
                            <option value="10" selected>10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table" id="reviewsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Customer</th>
                                <th>Rating</th>
                                <th>Review</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="tableBody">
                            <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                                <tr class="review-row" data-review-id="<?php echo $review['id']; ?>">
                                    <td><strong>#<?php echo $review['id']; ?></strong></td>
                                    <td>
                                        <div class="product-info">
                                            <?php 
                                            $image_path = !empty($review['product_image']) ? '../' . $review['product_image'] : 'https://via.placeholder.com/50x50?text=Flower';
                                            ?>
                                            <img src="<?php echo $image_path; ?>" 
                                                 alt="<?php echo htmlspecialchars($review['product_name']); ?>"
                                                 class="product-image"
                                                 onerror="this.src='https://via.placeholder.com/50x50?text=Flower'">
                                            <div>
                                                <strong><?php echo htmlspecialchars($review['product_name']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <span class="user-name"><?php echo htmlspecialchars($review['user_name']); ?></span>
                                            <span class="user-email"><?php echo htmlspecialchars($review['user_email']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="review-rating">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi bi-star<?php echo $i <= $review['rating'] ? '-fill' : ''; ?>" 
                                                   style="color: <?php echo $i <= $review['rating'] ? 'var(--star-color)' : '#E9ECEF'; ?>;"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="review-comment">
                                            <?php 
                                            $comment = htmlspecialchars($review['comment'] ?? 'No comment');
                                            echo strlen($comment) > 100 ? substr($comment, 0, 100) . '...' : $comment;
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="review-meta">
                                            <i class="bi bi-calendar me-1"></i>
                                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                            <br>
                                            <small><?php echo date('h:i A', strtotime($review['created_at'])); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" onclick="viewReview(<?php echo $review['id']; ?>)" 
                                                    title="View Details">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <a href="?delete=<?php echo $review['id']; ?>" 
                                               class="btn-action btn-delete" 
                                               title="Delete Review"
                                               onclick="return confirmDelete(event, this.href)">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination" id="pagination"></div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots" style="font-size: 3rem; color: #ddd;"></i>
                    <h5 class="mt-3">No Reviews Found</h5>
                    <p class="text-muted">There are no reviews matching your criteria.</p>
                    <?php if ($product_filter > 0 || $rating_filter > 0): ?>
                        <a href="reviews.php" class="btn btn-primary mt-2">Clear Filters</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Reviews Section -->
        <?php if (mysqli_num_rows($recent_reviews) > 0): ?>
        <div class="mt-4">
            <h5 class="mb-3"><i class="bi bi-clock-history me-2"></i>Recent Reviews</h5>
            <div class="row g-4">
                <?php while($recent = mysqli_fetch_assoc($recent_reviews)): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="recent-card">
                        <div class="recent-header">
                            <div class="recent-avatar">
                                <i class="bi bi-person"></i>
                            </div>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($recent['user_name']); ?></h6>
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?php echo $i <= $recent['rating'] ? '-fill' : ''; ?>" 
                                           style="color: <?php echo $i <= $recent['rating'] ? 'var(--star-color)' : '#E9ECEF'; ?>;"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        <p class="mb-3"><?php echo htmlspecialchars(substr($recent['comment'] ?? 'No comment', 0, 100)); ?></p>
                        <div class="d-flex justify-content-between align-items-center text-muted small">
                            <span><i class="bi bi-tag me-1"></i><?php echo htmlspecialchars($recent['product_name']); ?></span>
                            <span><i class="bi bi-clock me-1"></i><?php echo date('M d, Y', strtotime($recent['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Review Details Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Review Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reviewModalBody">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <script>
        $(document).ready(function() {
            // Auto hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
            
            // Setup custom table functionality
            setupTable();
        });
        
        // Setup table with search and pagination
        function setupTable() {
            const rows = $('.review-row');
            if (rows.length === 0) return;
            
            let itemsPerPage = parseInt($('#itemsPerPage').val());
            let currentPage = 1;
            
            // Search functionality
            $('#searchInput').on('keyup', function() {
                const searchTerm = $(this).val().toLowerCase();
                
                rows.each(function() {
                    const text = $(this).text().toLowerCase();
                    if (text.includes(searchTerm)) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                
                // Reset to first page after search
                currentPage = 1;
                paginate(currentPage, itemsPerPage);
            });
            
            // Items per page change
            $('#itemsPerPage').on('change', function() {
                itemsPerPage = parseInt($(this).val());
                currentPage = 1;
                paginate(currentPage, itemsPerPage);
            });
            
            // Initial pagination
            paginate(currentPage, itemsPerPage);
        }
        
        // Pagination function
        function paginate(page, itemsPerPage) {
            const rows = $('.review-row:visible');
            const totalItems = rows.length;
            
            if (totalItems === 0) {
                $('#pagination').empty();
                return;
            }
            
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            
            // Ensure page is within bounds
            page = Math.max(1, Math.min(page, totalPages));
            
            // Hide all rows first
            rows.hide();
            
            // Show rows for current page
            const start = (page - 1) * itemsPerPage;
            const end = Math.min(start + itemsPerPage, totalItems);
            
            rows.slice(start, end).show();
            
            // Update pagination buttons
            updatePagination(page, totalPages);
        }
        
        // Update pagination buttons
        function updatePagination(currentPage, totalPages) {
            let paginationHtml = '';
            
            if (totalPages > 1) {
                // Previous button
                if (currentPage > 1) {
                    paginationHtml += `<button class="page-btn" onclick="changePage(${currentPage - 1})">Previous</button>`;
                }
                
                // Page numbers (show limited pages for better UI)
                const maxVisiblePages = 5;
                let startPage = Math.max(1, currentPage - Math.floor(maxVisiblePages / 2));
                let endPage = Math.min(totalPages, startPage + maxVisiblePages - 1);
                
                if (endPage - startPage + 1 < maxVisiblePages) {
                    startPage = Math.max(1, endPage - maxVisiblePages + 1);
                }
                
                if (startPage > 1) {
                    paginationHtml += `<button class="page-btn" onclick="changePage(1)">1</button>`;
                    if (startPage > 2) {
                        paginationHtml += `<span class="page-btn disabled">...</span>`;
                    }
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    if (i === currentPage) {
                        paginationHtml += `<button class="page-btn active" onclick="changePage(${i})">${i}</button>`;
                    } else {
                        paginationHtml += `<button class="page-btn" onclick="changePage(${i})">${i}</button>`;
                    }
                }
                
                if (endPage < totalPages) {
                    if (endPage < totalPages - 1) {
                        paginationHtml += `<span class="page-btn disabled">...</span>`;
                    }
                    paginationHtml += `<button class="page-btn" onclick="changePage(${totalPages})">${totalPages}</button>`;
                }
                
                // Next button
                if (currentPage < totalPages) {
                    paginationHtml += `<button class="page-btn" onclick="changePage(${currentPage + 1})">Next</button>`;
                }
            }
            
            $('#pagination').html(paginationHtml);
        }
        
        // Change page function
        function changePage(page) {
            const itemsPerPage = parseInt($('#itemsPerPage').val());
            paginate(page, itemsPerPage);
        }
        
        // Confirm delete function
        function confirmDelete(event, deleteUrl) {
            event.preventDefault();
            
            if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
                window.location.href = deleteUrl;
            }
            
            return false;
        }
        
        // View review details - FIXED VERSION
        function viewReview(reviewId) {
            // Find the row using data attribute (added to tr)
            const row = $(`.review-row[data-review-id="${reviewId}"]`);
            
            if (row && row.length > 0) {
                // Get product name
                let productName = row.find('.product-info div strong').text().trim();
                if (!productName) {
                    productName = row.find('.product-info div').first().text().trim();
                }
                productName = productName || 'Unknown Product';
                
                // Get user name
                let userName = row.find('.user-name').text().trim() || 'Unknown User';
                
                // Get user email
                let userEmail = row.find('.user-email').text().trim() || '';
                
                // Get rating - count filled stars
                let rating = row.find('.review-rating .bi-star-fill').length;
                if (rating === 0) {
                    // Try to get from text
                    const ratingText = row.find('td:eq(3)').text().trim();
                    const match = ratingText.match(/(\d+)/);
                    if (match) {
                        rating = parseInt(match[1]);
                    }
                }
                
                // Get comment
                let comment = row.find('.review-comment').text().trim();
                if (!comment) {
                    comment = row.find('td:eq(4)').text().trim();
                }
                comment = comment || 'No comment';
                
                // Get date
                let date = row.find('.review-meta').clone().children().remove().end().text().trim();
                if (!date) {
                    date = row.find('td:eq(5)').text().trim();
                }
                date = date || 'Unknown date';
                
                // Escape HTML to prevent XSS
                const escapeHtml = (text) => {
                    if (!text) return '';
                    const div = document.createElement('div');
                    div.textContent = text;
                    return div.innerHTML;
                };
                
                // Build modal content
                const modalBody = $('#reviewModalBody');
                modalBody.html(`
                    <div class="mb-3">
                        <strong>Product:</strong> ${escapeHtml(productName)}
                    </div>
                    <div class="mb-3">
                        <strong>Customer:</strong> ${escapeHtml(userName)}<br>
                        <small>${escapeHtml(userEmail)}</small>
                    </div>
                    <div class="mb-3">
                        <strong>Rating:</strong><br>
                        ${generateStars(rating)}
                    </div>
                    <div class="mb-3">
                        <strong>Review:</strong><br>
                        ${escapeHtml(comment)}
                    </div>
                    <div class="mb-3">
                        <strong>Date:</strong><br>
                        ${escapeHtml(date)}
                    </div>
                `);
                
                // Show modal using Bootstrap 5 API
                const reviewModal = new bootstrap.Modal(document.getElementById('reviewModal'));
                reviewModal.show();
            } else {
                alert('Review details not found');
            }
        }
        
        // Generate star rating HTML
        function generateStars(rating) {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                if (i <= rating) {
                    stars += '<i class="bi bi-star-fill" style="color: var(--star-color);"></i> ';
                } else {
                    stars += '<i class="bi bi-star" style="color: #E9ECEF;"></i> ';
                }
            }
            return stars;
        }
    </script>
</body>
</html>
<?php 
if (isset($conn)) {
    mysqli_close($conn);
}
?>