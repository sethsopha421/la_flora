<?php
// admin/products.php
session_start();
require_once '../includes/database.php';

// Check if user is admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || !isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

// Handle delete action
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    if ($id > 0) {
        // First delete related records
        mysqli_query($conn, "DELETE FROM cart WHERE product_id = $id");
        mysqli_query($conn, "DELETE FROM order_items WHERE product_id = $id");
        mysqli_query($conn, "DELETE FROM product_images WHERE product_id = $id");
        mysqli_query($conn, "DELETE FROM reviews WHERE product_id = $id");
        
        // Then delete the product
        mysqli_query($conn, "DELETE FROM products WHERE id = $id");
        
        header('Location: products.php?message=Product deleted successfully');
        exit();
    }
}

// Handle toggle featured
if (isset($_GET['toggle_featured'])) {
    $id = intval($_GET['toggle_featured']);
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT featured FROM products WHERE id = $id"));
    $new_value = $current['featured'] ? 0 : 1;
    mysqli_query($conn, "UPDATE products SET featured = $new_value WHERE id = $id");
    header('Location: products.php?message=Product updated successfully');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $category_id = intval($_POST['category_id']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Handle image upload
    $image = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // Generate unique filename
            $new_filename = time() . '_' . uniqid() . '.' . $ext;
            $upload_path = '../assets/images/products/' . $new_filename;
            
            // Create directory if it doesn't exist
            if (!file_exists('../assets/images/products')) {
                mkdir('../assets/images/products', 0777, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                $image = 'assets/images/products/' . $new_filename;
            }
        }
    }
    
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        // Update existing product
        $id = intval($_POST['id']);
        
        if ($image) {
            // Get old image and delete it
            $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT image FROM products WHERE id = $id"));
            if ($old && $old['image'] && file_exists('../' . $old['image'])) {
                unlink('../' . $old['image']);
            }
            
            $sql = "UPDATE products SET 
                    name='$name', 
                    category_id=$category_id, 
                    price=$price, 
                    stock=$stock, 
                    description='$description', 
                    image='$image',
                    featured=$featured 
                    WHERE id=$id";
        } else {
            $sql = "UPDATE products SET 
                    name='$name', 
                    category_id=$category_id, 
                    price=$price, 
                    stock=$stock, 
                    description='$description',
                    featured=$featured 
                    WHERE id=$id";
        }
        
        $message = "Product updated successfully";
    } else {
        // Insert new product
        if ($image) {
            $sql = "INSERT INTO products (name, category_id, price, stock, description, image, featured, created_at) 
                    VALUES ('$name', $category_id, $price, $stock, '$description', '$image', $featured, NOW())";
        } else {
            $sql = "INSERT INTO products (name, category_id, price, stock, description, featured, created_at) 
                    VALUES ('$name', $category_id, $price, $stock, '$description', $featured, NOW())";
        }
        $message = "Product added successfully";
    }
    
    if (mysqli_query($conn, $sql)) {
        header("Location: products.php?message=" . urlencode($message));
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Get current admin name
$admin_name = $_SESSION['admin_name'] ?? 'Admin';

// Get categories for dropdown
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY name");

// Get product if editing
$edit_product = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
    $edit_product = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - LA FLORA Admin</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --primary-color: #2A5934;
            --primary-dark: #1E4025;
            --primary-light: #4A7856;
            --secondary-color: #D4A373;
            --accent-color: #E9C46A;
            --danger-color: #E76F51;
            --success-color: #2A9D8F;
            --warning-color: #F4A261;
            --info-color: #2874A6;
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
        
        /* Cards */
        .admin-card {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
            overflow: hidden;
            margin-bottom: 24px;
        }
        
        .admin-card .card-header {
            background: linear-gradient(135deg, #F8F9FA, white);
            border-bottom: 1px solid #E9ECEF;
            padding: 20px 24px;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        .admin-card .card-header h5 {
            margin: 0;
            font-weight: 700;
        }
        
        .admin-card .card-body {
            padding: 24px;
        }
        
        /* Buttons */
        .btn-admin {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(42, 89, 52, 0.3);
            color: white;
        }
        
        .btn-outline-admin {
            background: white;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            padding: 8px 20px;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-outline-admin:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Form */
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border: 2px solid #E9ECEF;
            border-radius: 12px;
            padding: 12px 16px;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(42, 89, 52, 0.1);
        }
        
        .input-group-text {
            background: #F8F9FA;
            border: 2px solid #E9ECEF;
            border-radius: 12px;
            color: #6C757D;
        }
        
        /* Table */
        .table {
            margin: 0;
        }
        
        .table thead th {
            background: #F8F9FA;
            color: var(--primary-color);
            font-weight: 600;
            border-bottom: 2px solid #E9ECEF;
            padding: 16px 12px;
        }
        
        .table tbody td {
            padding: 16px 12px;
            vertical-align: middle;
            border-bottom: 1px solid #E9ECEF;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .stock-badge {
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 500;
            font-size: 0.85rem;
        }
        
        .stock-high { background: #D4EDDA; color: #155724; }
        .stock-medium { background: #FFF3CD; color: #856404; }
        .stock-low { background: #F8D7DA; color: #721C24; }
        
        .action-btns {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s;
            border: none;
        }
        
        .action-btn:hover {
            transform: scale(1.1);
        }
        
        .btn-edit { background: var(--info-color); }
        .btn-featured { background: var(--warning-color); }
        .btn-delete { background: var(--danger-color); }
        
        .featured-star {
            color: var(--warning-color);
            font-size: 1.2rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 16px;
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
            <a href="products.php" class="nav-link active">
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

    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <h1 class="page-title">
                <i class="bi bi-box-seam me-2"></i>
                Manage Products
            </h1>
            
            <div class="admin-profile">
                <div class="admin-avatar">
                    <?php echo strtoupper(substr($admin_name, 0, 1)); ?>
                </div>
                <div>
                    <strong><?php echo htmlspecialchars($admin_name); ?></strong>
                    <small class="d-block text-muted">Administrator</small>
                </div>
            </div>
        </div>
        
        <!-- Stats Cards -->
        <?php
        $total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
        $total_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock) as total FROM products"))['total'] ?? 0;
        $avg_price = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(price) as avg FROM products"))['avg'] ?? 0;
        $featured_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE featured = 1"))['count'];
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_products; ?></h3>
                    <p>Total Products</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-cart"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $total_stock; ?></h3>
                    <p>Items in Stock</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="stat-info">
                    <h3>$<?php echo number_format($avg_price, 2); ?></h3>
                    <p>Average Price</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-star-fill"></i>
                </div>
                <div class="stat-info">
                    <h3><?php echo $featured_count; ?></h3>
                    <p>Featured Items</p>
                </div>
            </div>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($_GET['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo htmlspecialchars($_GET['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Add/Edit Product Form -->
        <?php if (isset($_GET['action']) || isset($_GET['edit'])): ?>
        <div class="admin-card">
            <div class="card-header">
                <h5>
                    <i class="bi bi-<?php echo $edit_product ? 'pencil' : 'plus-circle'; ?> me-2"></i>
                    <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="products.php" enctype="multipart/form-data">
                    <?php if ($edit_product): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_product['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Name</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>" 
                                   required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php mysqli_data_seek($categories, 0); while($cat = mysqli_fetch_assoc($categories)): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php if($edit_product && $edit_product['category_id'] == $cat['id']) echo 'selected'; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Price</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" name="price" class="form-control" step="0.01" min="0"
                                       value="<?php echo $edit_product ? $edit_product['price'] : ''; ?>" required>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" name="stock" class="form-control" min="0"
                                   value="<?php echo $edit_product ? $edit_product['stock'] : ''; ?>" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Featured Product</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="featured" id="featuredSwitch" 
                                       <?php echo ($edit_product && $edit_product['featured']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="featuredSwitch">Show on homepage</label>
                            </div>
                        </div>
                        
                        <div class="col-12 mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Product Image</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Leave empty to keep current image</small>
                        </div>
                        
                        <?php if ($edit_product && $edit_product['image']): ?>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Current Image</label>
                            <div>
                                <img src="../<?php echo $edit_product['image']; ?>" 
                                     alt="Current" class="product-image" width="100">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-admin">
                            <i class="bi bi-save me-2"></i>
                            <?php echo $edit_product ? 'Update Product' : 'Save Product'; ?>
                        </button>
                        <a href="products.php" class="btn btn-outline-secondary ms-2">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Products List -->
        <div class="admin-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="bi bi-grid me-2"></i>All Products</h5>
                <a href="?action=add" class="btn btn-admin">
                    <i class="bi bi-plus-circle me-2"></i>Add New Product
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="productsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Featured</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $products = mysqli_query($conn, 
                                "SELECT p.*, c.name as category_name 
                                 FROM products p 
                                 LEFT JOIN categories c ON p.category_id = c.id 
                                 ORDER BY p.id DESC");
                            
                            while($row = mysqli_fetch_assoc($products)):
                                $stock_class = 'stock-high';
                                if ($row['stock'] <= 5) $stock_class = 'stock-low';
                                elseif ($row['stock'] <= 15) $stock_class = 'stock-medium';
                            ?>
                            <tr>
                                <td><strong>#<?php echo $row['id']; ?></strong></td>
                                <td>
                                    <?php if ($row['image'] && file_exists('../' . $row['image'])): ?>
                                        <img src="../<?php echo $row['image']; ?>" 
                                             alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                             class="product-image">
                                    <?php else: ?>
                                        <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="bi bi-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                    <small class="d-block text-muted"><?php echo substr(htmlspecialchars($row['description']), 0, 50); ?>...</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark p-2">
                                        <i class="bi bi-tag me-1"></i>
                                        <?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-primary">$<?php echo number_format($row['price'], 2); ?></strong>
                                </td>
                                <td>
                                    <span class="stock-badge <?php echo $stock_class; ?>">
                                        <?php echo $row['stock']; ?> in stock
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['featured']): ?>
                                        <i class="bi bi-star-fill featured-star"></i>
                                    <?php else: ?>
                                        <i class="bi bi-star text-muted"></i>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="?edit=<?php echo $row['id']; ?>" class="action-btn btn-edit" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="?toggle_featured=<?php echo $row['id']; ?>" 
                                           class="action-btn btn-featured" 
                                           title="<?php echo $row['featured'] ? 'Remove from featured' : 'Add to featured'; ?>">
                                            <i class="bi bi-star<?php echo $row['featured'] ? '-fill' : ''; ?>"></i>
                                        </a>
                                        <a href="?delete=<?php echo $row['id']; ?>" 
                                           class="action-btn btn-delete delete-product" 
                                           title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#productsTable').DataTable({
                pageLength: 10,
                order: [[0, 'desc']],
                language: {
                    search: "Search products:",
                    lengthMenu: "Show _MENU_ products per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ products",
                }
            });
            
            // Delete confirmation with SweetAlert
            $('.delete-product').click(function(e) {
                e.preventDefault();
                let deleteUrl = $(this).attr('href');
                
                Swal.fire({
                    title: 'Delete Product?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#E76F51',
                    cancelButtonColor: '#6C757D',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = deleteUrl;
                    }
                });
            });
            
            // Auto hide alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
        
        // Preview image before upload
        document.querySelector('input[type="file"]')?.addEventListener('change', function(e) {
            if (this.files && this.files[0]) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    // You can add image preview here if needed
                }
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
</body>
</html>