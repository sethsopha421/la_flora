
<?php
// debug-product-images.php
// Professional Product Image Debug Tool - FLOWER SHOP THEME

session_start();
require_once 'includes/database.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 1;
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$document_root = $_SERVER['DOCUMENT_ROOT'];

// Fetch product details
$product = null;
$query = "SELECT * FROM products WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Handle form submissions
$message = '';
$message_type = '';

// Handle image upload (file)
if (isset($_POST['upload_image']) && isset($_FILES['image_file']) && $_FILES['image_file']['error'] == 0) {
    $upload_dir = 'assets/images/products/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Get target path from form (or generate if empty)
    $target_path = trim($_POST['target_path'] ?? '');
    
    if (empty($target_path)) {
        // Generate default path
        $product_name_slug = preg_replace('/[^a-z0-9]/', '-', strtolower($product['name'] ?? 'product'));
        $product_name_slug = trim($product_name_slug, '-');
        $file_extension = pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION);
        $unique_id = uniqid();
        $new_filename = $product_name_slug . '-' . $unique_id . '.' . $file_extension;
        $target_path = $upload_dir . $new_filename;
    }
    
    // Allowed file types
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = $_FILES['image_file']['type'];
    
    if (in_array($file_type, $allowed_types)) {
        // Check file size (max 5MB)
        if ($_FILES['image_file']['size'] <= 5 * 1024 * 1024) {
            // Move uploaded file
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target_path)) {
                // Update database with new image path
                $update_query = "UPDATE products SET image = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, "si", $target_path, $product_id);
                    
                    if (mysqli_stmt_execute($update_stmt)) {
                        $message = "🌸 Image uploaded successfully and database updated!";
                        $message_type = "success";
                        // Refresh product data
                        $product['image'] = $target_path;
                    } else {
                        $message = "❌ Database update failed: " . mysqli_error($conn);
                        $message_type = "error";
                        // Remove uploaded file if DB update failed
                        unlink($target_path);
                    }
                    mysqli_stmt_close($update_stmt);
                }
            } else {
                $message = "❌ Failed to move uploaded file.";
                $message_type = "error";
            }
        } else {
            $message = "❌ File is too large. Maximum size is 5MB.";
            $message_type = "error";
        }
    } else {
        $message = "❌ Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.";
        $message_type = "error";
    }
}


// Handle URL upload
if (isset($_POST['upload_url']) && !empty($_POST['image_url'])) {
    $image_url = trim($_POST['image_url']);
    
    // Validate URL
    if (filter_var($image_url, FILTER_VALIDATE_URL)) {
        // Update database with URL
        $update_query = "UPDATE products SET image = ? WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        
        if ($update_stmt) {
            mysqli_stmt_bind_param($update_stmt, "si", $image_url, $product_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                $message = "🌼 Image URL saved successfully!";
                $message_type = "success";
                // Refresh product data
                $product['image'] = $image_url;
            } else {
                $message = "❌ Database update failed: " . mysqli_error($conn);
                $message_type = "error";
            }
            mysqli_stmt_close($update_stmt);
        }
    } else {
        $message = "❌ Invalid URL format.";
        $message_type = "error";
    }
}

// Handle update image (manual path)
if (isset($_POST['update_image']) && !empty($_POST['new_image_path'])) {
    $new_path = trim($_POST['new_image_path']);
    $update_query = "UPDATE products SET image = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_query);
    
    if ($update_stmt) {
        mysqli_stmt_bind_param($update_stmt, "si", $new_path, $product_id);
        
        if (mysqli_stmt_execute($update_stmt)) {
            $message = "🌸 Image path updated successfully!";
            $message_type = "success";
            // Refresh product data
            if ($product) {
                $product['image'] = $new_path;
            }
        } else {
            $message = "❌ Failed to update image path: " . mysqli_error($conn);
            $message_type = "error";
        }
        mysqli_stmt_close($update_stmt);
    }
}

// Handle mass fix
if (isset($_POST['mass_fix']) && isset($_POST['fix_type'])) {
    $fix_type = $_POST['fix_type'];
    $mass_fixed = 0;
    
    if ($fix_type == 'add_prefix') {
        $mass_query = "UPDATE products 
                       SET image = CONCAT('assets/images/products/', image) 
                       WHERE image NOT LIKE 'assets/%' 
                         AND image NOT LIKE 'http%' 
                         AND image IS NOT NULL 
                         AND image != ''";
        if (mysqli_query($conn, $mass_query)) {
            $mass_fixed = mysqli_affected_rows($conn);
            $message = "🌸 Added 'assets/' prefix to $mass_fixed products";
            $message_type = "success";
        } else {
            $message = "❌ Mass fix failed: " . mysqli_error($conn);
            $message_type = "error";
        }
    } elseif ($fix_type == 'set_fallback') {
        $fallback_url = 'https://images.unsplash.com/photo-1464207687429-7505649dae38?auto=format&fit=crop&w=600&q=80';
        $fallback_query = "UPDATE products 
                          SET image = ? 
                          WHERE image IS NULL OR image = ''";
        $fallback_stmt = mysqli_prepare($conn, $fallback_query);
        if ($fallback_stmt) {
            mysqli_stmt_bind_param($fallback_stmt, "s", $fallback_url);
            if (mysqli_stmt_execute($fallback_stmt)) {
                $mass_fixed = mysqli_affected_rows($conn);
                $message = "🌸 Set fallback image for $mass_fixed products";
                $message_type = "success";
            }
            mysqli_stmt_close($fallback_stmt);
        }
    }
}

// Get all products
$all_products = [];
$all_products_query = "SELECT id, name, image, price FROM products ORDER BY id DESC LIMIT 20";
$all_products_result = mysqli_query($conn, $all_products_query);
if ($all_products_result) {
    while ($p = mysqli_fetch_assoc($all_products_result)) {
        $all_products[] = $p;
    }
}

// Calculate statistics
$total_products = count($all_products);
$valid_images = 0;
$missing_images = 0;
$broken_images = 0;
$url_images = 0;
$local_images = 0;


foreach ($all_products as $p) {
    if (empty($p['image'])) {
        $missing_images++;
    } elseif (strpos($p['image'], 'http') === 0) {
        // Online URL
        $url_images++;
        $valid_images++;
    } elseif (file_exists($p['image'])) {
        $local_images++;
        $valid_images++;
    } else {
        $broken_images++;
    }
}

// Analyze current product
$file_exists = false;
$is_readable = false;
$full_url = '';
$image_info = false;

if ($product && !empty($product['image'])) {
    $full_url = $base_url . '/' . ltrim($product['image'], '/');
    
    // Check if it's a URL or local file
    if (strpos($product['image'], 'http') === 0) {
        $file_exists = true; // Assume URLs are valid
        $is_readable = true;
    } else {
        $file_exists = file_exists($product['image']);
        $is_readable = $file_exists && is_readable($product['image']);
        
        if ($file_exists && function_exists('getimagesize')) {
            $image_info = @getimagesize($product['image']);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🌸 Flower Image Debug Tool - La Flora</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #FF6B8B; /* Pink from the design */
            --primary-dark: #E05574;
            --secondary: #9B5DE5; /* Purple accent */
            --light: #F8F5F0;
            --dark: #2D2D2D;
            --light-pink: #FFF0F3;
            --light-purple: #F5F0FF;
            --success: #00B894;
            --danger: #FF7675;
            --warning: #FDCB6E;
            --info: #74B9FF;
            --border: #E8D5D8;
            --card-shadow: 0 8px 32px rgba(255, 107, 139, 0.1);
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, var(--light-pink) 0%, #FFF5F7 100%);
            color: var(--dark);
            min-height: 100vh;
            padding-bottom: 40px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;


background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ff6b8b' fill-opacity='0.03' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.6;
            z-index: -1;
        }
        
        .container {
            max-width: 1400px;
        }
        
        /* Logo Header Styles */
.logo-header {
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
    padding: 25px 0;
    border-bottom: 1px solid rgba(255, 107, 139, 0.2);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
}

.logo-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: linear-gradient(90deg, #FF6B8B, #9B5DE5, #00B894);
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 20px;
}

.logo-box {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #FF6B8B, #9B5DE5);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 25px rgba(255, 107, 139, 0.3);
    position: relative;
    overflow: hidden;
    transition: all 0.3s ease;
}

.logo-box::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, rgba(255,255,255,0) 70%);
    animation: rotate 20s linear infinite;
}

@keyframes rotate {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.logo-box:hover {
    transform: translateY(-5px) scale(1.05);
    box-shadow: 0 12px 30px rgba(255, 107, 139, 0.4);
}

.logo-icon {
    font-size: 32px;
    color: white;
    position: relative;
    z-index: 1;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

.logo-text {
    animation: fadeIn 1s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.logo-title {
    font-family: 'Playfair Display', serif;
    font-size: 2.8rem;
    font-weight: 700;
    color: #2D2D2D;
    margin: 0;
    background: linear-gradient(135deg, #FF6B8B 0%, #9B5DE5 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    position: relative;
    display: inline-block;
}

.logo-title::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #FF6B8B, #9B5DE5);
    border-radius: 2px;
    transform: scaleX(0);
    transform-origin: left;
    animation: lineExpand 1s ease-out 0.5s forwards;
}

@keyframes lineExpand {
    to {
        transform: scaleX(1);
    }
}


.logo-subtitle {
    font-family: 'Poppins', sans-serif;
    font-size: 1.1rem;
    color: #666;
    margin: 5px 0 0 0;
    font-weight: 300;
    letter-spacing: 1px;
}

.header-actions {
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

.btn-header {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 24px;
    background: white;
    border: 2px solid rgba(255, 107, 139, 0.2);
    border-radius: 50px;
    color: #FF6B8B;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    position: relative;
    overflow: hidden;
}

.btn-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 107, 139, 0.1), transparent);
    transition: 0.5s;
}

.btn-header:hover::before {
    left: 100%;
}

.btn-header:hover {
    background: linear-gradient(135deg, #FF6B8B, #9B5DE5);
    color: white;
    border-color: transparent;
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(255, 107, 139, 0.3);
}

.btn-header i {
    font-size: 1.1rem;
    transition: transform 0.3s ease;
}

.btn-header:hover i {
    transform: translateX(-3px);
}

.btn-header span {
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

/* Responsive Design */
@media (max-width: 992px) {
    .logo-container {
        justify-content: center;
        text-align: center;
        flex-direction: column;
        gap: 15px;
    }
    
    .logo-title {
        font-size: 2.2rem;
    }
    
    .logo-subtitle {
        font-size: 1rem;
    }
    
    .header-actions {
        justify-content: center;
        margin-top: 20px;
    }
}

@media (max-width: 576px) {
    .logo-header {
        padding: 20px 0;
    }
    
    .logo-box {
        width: 60px;
        height: 60px;
    }
    
    .logo-icon {
        font-size: 28px;
    }
    
    .logo-title {
        font-size: 1.8rem;
    }
    
    .logo-subtitle {
        font-size: 0.9rem;
    }
    
    .btn-header {
        padding: 10px 20px;
        font-size: 0.9rem;
    }
    
    .header-actions {
        gap: 10px;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    .logo-header {
        background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        border-bottom: 1px solid rgba(255, 107, 139, 0.3);
    }
    
    .logo-title {
        background: linear-gradient(135deg, #FF6B8B 0%, #9B5DE5 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }
    
    .logo-subtitle {
        color: #aaa;
    }
    
    .btn-header {
        background: #2d2d2d;
        border-color: rgba(255, 107, 139, 0.3);
        color: #FF6B8B;
    }
    
    .btn-header:hover {
        background: linear-gradient(135deg, #FF6B8B, #9B5DE5);
        color: white;
    }
}


/* Print Styles */
@media print {
    .logo-header {
        background: none;
        box-shadow: none;
        border-bottom: 2px solid #ccc;
    }
    
    .logo-title {
        background: #000;
        -webkit-text-fill-color: #000;
    }
    
    .btn-header {
        display: none !important;
    }
}
        
        /* Cards */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            margin-bottom: 24px;
            overflow: hidden;
            background: white;
            position: relative;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(255, 107, 139, 0.2);
        }
        
        .card-header {
            background: white;
            border-bottom: 2px solid var(--border);
            padding: 22px 28px;
            font-weight: 600;
            font-size: 1.25rem;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 15px;
            font-family: 'Playfair Display', serif;
        }
        
        .card-body {
            padding: 28px;
        }
        
        /* Product Card */
        .product-display {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        
        .product-display::before {
            content: 'PRODUCT';
            position: absolute;
            top: 20px;
            right: -40px;
            background: var(--primary);
            color: white;
            padding: 8px 50px;
            transform: rotate(45deg);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .product-code {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 8px 20px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 20px;
            letter-spacing: 1px;
            box-shadow: 0 4px 12px rgba(255, 107, 139, 0.3);
        }
        
        .product-name {
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            font-size: 2.2rem;
            color: var(--dark);
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 15px;
        }
        
        .product-name::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary);
            border-radius: 2px;
        }
        
        .price-tag {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            color: var(--primary);
            font-weight: 700;
            margin: 25px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .price-tag::before {
            content: '$';
            font-size: 1.5rem;
            opacity: 0.7;
        }
        
        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(155, 93, 229, 0.08);


transition: var(--transition);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(155, 93, 229, 0.15);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
            color: white;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            box-shadow: 0 6px 15px rgba(255, 107, 139, 0.3);
        }
        
        .stat-count {
            font-size: 2.8rem;
            font-weight: 700;
            margin: 15px 0;
            color: var(--dark);
            font-family: 'Playfair Display', serif;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }
        
        /* Tables */
        .product-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: white;
        }
        
        .product-table th {
            background: linear-gradient(135deg, var(--light-pink), var(--light-purple));
            padding: 18px;
            font-weight: 600;
            color: var(--primary);
            border-bottom: 2px solid var(--border);
            text-align: left;
            white-space: nowrap;
            font-family: 'Playfair Display', serif;
        }
        
        .product-table td {
            padding: 18px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        
        .product-table tr:hover {
            background: var(--light-pink);
        }
        
        /* Status Badges */
        .flower-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            white-space: nowrap;
            border: 2px solid transparent;
        }
        
        .badge-fresh {
            background: linear-gradient(135deg, #e8f7f0, #d1f0e5);
            color: var(--success);
            border-color: rgba(0, 184, 148, 0.2);
        }
        
        .badge-wilted {
            background: linear-gradient(135deg, #ffeaea, #ffdbdb);
            color: var(--danger);
            border-color: rgba(255, 118, 117, 0.2);
        }
        
        .badge-budding {
            background: linear-gradient(135deg, #fff4e6, #ffe8cc);
            color: #e67e22;
            border-color: rgba(230, 126, 34, 0.2);
        }
        
        .badge-blooming {
            background: linear-gradient(135deg, #f0f7ff, #e6f0ff);
            color: var(--info);
            border-color: rgba(116, 185, 255, 0.2);
        }
        
        /* Image Previews */
        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .image-frame {
            border: 3px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            transition: var(--transition);
            background: white;
            position: relative;
        }
        
        .image-frame:hover {
            border-color: var(--primary);
            transform: translateY(-5px) scale(1.02);

Sopha 🪻💜, [2/17/2026 6:43 PM]
box-shadow: 0 10px 25px rgba(255, 107, 139, 0.2);
        }
        
        .image-frame img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            display: block;
        }
        
        .image-label {
            padding: 12px;
            background: white;
            font-size: 0.85rem;
            color: #666;
            text-align: center;
            border-top: 1px solid var(--border);
        }
        
        /* Upload Area */
        .upload-zone {
            border: 3px dashed var(--primary);
            border-radius: 16px;
            padding: 50px 30px;
            text-align: center;
            background: linear-gradient(135deg, var(--light-pink), #fff9fb);
            cursor: pointer;
            transition: var(--transition);
            margin-bottom: 25px;
            position: relative;
        }
        
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--secondary);
            background: linear-gradient(135deg, #fff0f8, #fff5f9);
            transform: translateY(-2px);
        }
        
        .upload-icon {
            font-size: 4rem;
            color: var(--primary);
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .upload-text {
            font-size: 1.3rem;
            color: var(--dark);
            margin-bottom: 12px;
            font-weight: 500;
        }
        
        .upload-hint {
            color: var(--primary);
            font-size: 0.9rem;
            background: rgba(255, 107, 139, 0.1);
            padding: 8px 16px;
            border-radius: 50px;
            display: inline-block;
        }
        
        /* Buttons */
        .flower-btn {
            padding: 14px 28px;
            border-radius: 50px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            letter-spacing: 0.5px;
            position: relative;
            overflow: hidden;
        }
        
        .flower-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: 0.5s;
        }
        
        .flower-btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 6px 20px rgba(255, 107, 139, 0.4);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary));
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255, 107, 139, 0.5);
        }
        
        .btn-secondary {
            background: linear-gradient(135deg, #9B5DE5, #7B3ED1);
            color: white;
            box-shadow: 0 6px 20px rgba(155, 93, 229, 0.4);
        }
        
        .btn-secondary:hover {
            background: linear-gradient(135deg, #7B3ED1, #9B5DE5);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(155, 93, 229, 0.5);
        }
        
        .btn-outline-pink {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
        }
        
        .btn-outline-pink:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }


.btn-sm {
            padding: 8px 18px;
            font-size: 0.9rem;
        }
        
        /* Tabs */
        .flower-tabs {
            border-bottom: 2px solid var(--border);
            margin-bottom: 25px;
        }
        
        .flower-tabs .nav-link {
            border: none;
            padding: 15px 30px;
            color: #888;
            font-weight: 600;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
            background: transparent;
            cursor: pointer;
            border-radius: 10px 10px 0 0;
            font-family: 'Poppins', sans-serif;
        }
        
        .flower-tabs .nav-link:hover {
            color: var(--primary);
            background: var(--light-pink);
        }
        
        .flower-tabs .nav-link.active {
            color: var(--primary);
            background: var(--light-pink);
            border-bottom: 3px solid var(--primary);
        }
        
        /* Alert Messages */
        .flower-alert {
            padding: 20px 25px;
            border-radius: 16px;
            margin-bottom: 25px;
            border: none;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .flower-alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 5px;
            background: currentColor;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #e8f7f0, #d1f0e5);
            color: var(--success);
        }
        
        .alert-error {
            background: linear-gradient(135deg, #ffeaea, #ffdbdb);
            color: var(--danger);
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #fff4e6, #ffe8cc);
            color: #e67e22;
        }
        
        /* Forms */
        .form-flower {
            margin-bottom: 25px;
        }
        
        .form-flower label {
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
            display: block;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-control-flower {
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 15px 20px;
            transition: var(--transition);
            width: 100%;
            font-family: 'Poppins', sans-serif;
            background: white;
        }
        
        .form-control-flower:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(255, 107, 139, 0.15);
            outline: none;
        }
        
        /* Flower Presets */
        .preset-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 15px;
        }
        
        .preset-btn {
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 12px;
            background: white;
            color: var(--dark);
            font-weight: 500;
            transition: var(--transition);
            text-align: center;
            cursor: pointer;
        }
        
        .preset-btn:hover {
            border-color: var(--primary);
            background: var(--light-pink);
            transform: translateY(-2px);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .image-gallery {
                grid-template-columns: 1fr;
            }
            
            .logo {
                font-size: 2.2rem;
            }
            
            .card-header {
                padding: 18px 22px;
                font-size: 1.1rem;
            }
            
            .card-body {
                padding: 22px;
            }


.product-name {
                font-size: 1.8rem;
            }
            
            .price-tag {
                font-size: 2rem;
            }
            
            .flower-tabs .nav-link {
                padding: 12px 20px;
                font-size: 0.9rem;
            }
            
            .upload-zone {
                padding: 40px 20px;
            }
        }
        
        /* Print Styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .flower-btn {
                border: 1px solid #ccc !important;
                background: none !important;
                color: #000 !important;
            }
        }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 10px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--light-pink);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 5px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
    </style>
</head>
<body>
   <!-- Logo Header -->
    <div class="logo-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="logo-container">
                        <div class="logo-box">
                            <div class="logo-icon">🌺</div>
                        </div>
                        <div class="logo-text">
                            <h1 class="logo-title">La Flora</h1>
                            <p class="logo-subtitle">Image Management System</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <div class="header-actions">
                        <a href="product-detail.php?id=<?php echo $product_id; ?>" class="btn-header">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back</span>
                        </a>
                        <a href="?id=<?php echo $product_id; ?>" class="btn-header">
                            <i class="fas fa-sync-alt"></i>
                            <span>Refresh</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <div class="stat-count"><?php echo $total_products; ?></div>
                <div class="stat-label">Total Bouquets</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-count"><?php echo $valid_images; ?></div>
                <div class="stat-label">Fresh Images</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-count"><?php echo $broken_images; ?></div>
                <div class="stat-label">Wilted Images</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div class="stat-count"><?php echo $missing_images; ?></div>
                <div class="stat-label">Missing Blooms</div>
            </div>
        </div>


<!-- Messages -->
        <?php if ($message): ?>
        <div class="flower-alert alert-<?php echo $message_type === 'success' ? 'success' : 'error'; ?>">
            <i class="fas <?php echo $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> fa-lg"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="row">
            <div class="col-lg-8">
                <!-- Current Product Analysis -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-search" style="color: var(--primary);"></i> Current Bouquet Analysis
                    </div>
                    <div class="card-body">
                        <?php if ($product): ?>
                            <div class="product-display mb-4">
                                <div class="product-code">
                                    <i class="fas fa-tag me-2"></i>#<?php echo $product['id']; ?>
                                </div>
                                <h2 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h2>
                                
                                <div class="price-tag">
                                    <?php echo isset($product['price']) ? '$' . number_format($product['price'], 2) : '$99.00'; ?>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <table class="product-table">
                                            <tr>
                                                <th style="width: 120px;">Image Path:</th>
                                                <td>
                                                    <code style="word-break: break-all; background: var(--light-pink); padding: 8px 12px; border-radius: 8px; display: inline-block; font-size: 0.9em;"><?php echo htmlspecialchars($product['image'] ?? 'NULL'); ?></code>
                                                    <?php if (empty($product['image'])): ?>
                                                        <span class="flower-badge badge-wilted ms-2 mt-1">
                                                            <i class="fas fa-times"></i> NO IMAGE
                                                        </span>
                                                    <?php elseif ($file_exists): ?>
                                                        <span class="flower-badge badge-fresh ms-2 mt-1">
                                                            <i class="fas fa-check"></i> FRESH
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="flower-badge badge-wilted ms-2 mt-1">
                                                            <i class="fas fa-times"></i> WILTED
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Bloom Status:</th>
                                                <td>
                                                    <?php if (empty($product['image'])): ?>
                                                        <span class="flower-badge badge-wilted">No Image Found</span>
                                                    <?php elseif (strpos($product['image'], 'http') === 0): ?>
                                                        <span class="flower-badge badge-blooming">Online Bloom</span>
                                                    <?php elseif ($file_exists): ?>


<span class="flower-badge badge-fresh">Fresh & Ready</span>
                                                        <?php if ($is_readable): ?>
                                                            <span class="flower-badge badge-budding">Viewable</span>
                                                        <?php endif; ?>
                                                        <?php if ($image_info): ?>
                                                            <span class="flower-badge badge-blooming">
                                                                <?php echo $image_info[0] . '×' . $image_info[1]; ?> px
                                                            </span>
                                                            <span class="flower-badge badge-blooming">
                                                                <?php echo round(filesize($product['image']) / 1024, 1); ?> KB
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="flower-badge badge-wilted">Bloom Missing</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Full URL:</th>
                                                <td>
                                                    <code style="font-size: 0.85em; word-break: break-all; background: var(--light-purple); padding: 8px 12px; border-radius: 8px; display: inline-block;">
                                                        <?php echo htmlspecialchars($full_url); ?>
                                                    </code>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                    <div class="col-md-6 mt-3 mt-md-0">
                                        <h6 class="mb-3" style="color: var(--primary); font-weight: 600; font-family: 'Playfair Display', serif;">
                                            <i class="fas fa-eye me-2"></i>Bloom Preview
                                        </h6>
                                        <div class="image-gallery">
                                            <div class="image-frame">
                                                <img src="<?php echo htmlspecialchars($product['image'] ?? ''); ?>" 
                                                     alt="Database Image"
                                                     onerror="this.src='https://images.unsplash.com/photo-1464207687429-7505649dae38?auto=format&fit=crop&w=400&q=80'">
                                                <div class="image-label">
                                                    <i class="fas fa-database me-1"></i> Database
                                                </div>
                                            </div>
                                            
                                            <div class="image-frame">
                                                <img src="<?php echo htmlspecialchars($full_url ?? ''); ?>" 
                                                     alt="Live URL"
                                                     onerror="this.src='https://images.unsplash.com/photo-1519378058457-4c29a0a2efac?auto=format&fit=crop&w=400&q=80'">
                                                <div class="image-label">
                                                    <i class="fas fa-globe me-1"></i> Live URL
                                                </div>
                                            </div>
                                        </div>


</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="flower-alert alert-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                Bouquet not found! Check if product ID <?php echo $product_id; ?> exists in our garden.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Bouquets Gallery -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-list" style="color: var(--primary);"></i> Recent Bouquets (Last 20)
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Bouquet Name</th>
                                        <th>Price</th>
                                        <th>Image Path</th>
                                        <th>Bloom Status</th>
                                        <th style="width: 50px;">Preview</th>
                                        <th style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_products as $p): ?>
                                    <?php 
                                    $p_is_url = strpos($p['image'] ?? '', 'http') === 0;
                                
                                    $p_status = empty($p['image']) ? 'empty' : ($p_file_exists ? 'valid' : 'missing');
                                    $price = isset($p['price']) ? '$' . number_format($p['price'], 2) : '$99.00';
                                    ?>
                                    <tr>
                                        <td><strong style="color: var(--primary);">#<?php echo $p['id']; ?></strong></td>
                                        <td>
                                            <div style="font-weight: 500; color: var(--dark);">
                                                <?php echo htmlspecialchars(mb_substr($p['name'], 0, 30)); ?><?php echo mb_strlen($p['name']) > 30 ? '...' : ''; ?>
                                            </div>
                                        </td>
                                        <td style="color: var(--primary); font-weight: 600;"><?php echo $price; ?></td>
                                        <td>
                                            <code style="font-size: 0.75rem; background: var(--light-pink); padding: 4px 8px; border-radius: 4px;">
                                                <?php echo htmlspecialchars(mb_substr($p['image'] ?? 'NULL', 0, 35)); ?><?php echo mb_strlen($p['image'] ?? '') > 35 ? '...' : ''; ?>
                                            </code>
                                        </td>
                                        <td>
                                            <?php if ($p_status == 'empty'): ?>
                                                <span class="flower-badge badge-wilted">Empty</span>
                                            <?php elseif ($p_status == 'valid'): ?>
                                                <span class="flower-badge badge-fresh">Fresh</span>
                                            <?php else: ?>
                                                <span class="flower-badge badge-wilted">Missing</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <


div style="width: 40px; height: 40px; border-radius: 8px; overflow: hidden; border: 2px solid var(--border);">
                                                <img src="<?php echo htmlspecialchars($p['image'] ?? ''); ?>" 
                                                     style="width: 100%; height: 100%; object-fit: cover;"
                                                     onerror="this.src='assets/images/products/Wedding.jpg'">
                                            </div>
                                        </td>
                                        <td>
                                            <a href="?id=<?php echo $p['id']; ?>" class="flower-btn btn-sm btn-outline-pink" style="padding: 6px 12px;">
                                                <i class="fas fa-search"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Garden Tools -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-tools" style="color: var(--primary);"></i> Garden Tools
                    </div>
                    <div class="card-body">
                        <ul class="nav flower-tabs" id="fixTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button">
                                    <i class="fas fa-upload me-2"></i>Upload
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="url-tab" data-bs-toggle="tab" data-bs-target="#url" type="button">
                                    <i class="fas fa-link me-2"></i>From URL
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="update-tab" data-bs-toggle="tab" data-bs-target="#update" type="button">
                                    <i class="fas fa-edit me-2"></i>Update
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="mass-tab" data-bs-toggle="tab" data-bs-target="#mass" type="button">
                                    <i class="fas fa-spray-can me-2"></i>Mass Fix
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content mt-4">
                            <!-- Upload Tab -->
                            <div class="tab-pane fade show active" id="upload">
                                <form method="post" enctype="multipart/form-data" id="uploadForm">
                                    <div class="form-flower">
                                        <label>Upload New Bloom Image</label>
                                        
                                        <div class="upload-zone" id="uploadArea">
                                            <div class="upload-icon">
                                                🌸
                                            </div>
                                            <div class="upload-text">
                                                Drop your flower photo here
                                            </div>
                                            <div class="upload-hint">


<button type="button" class="flower-btn btn-secondary" onclick="previewUrl()" style="border-radius: 0 12px 12px 0; padding: 15px 20px;">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <img id="urlPreview" class="url-preview mt-3" src="" alt="URL Preview" onerror="this.style.display='none'" style="width: 100%; border-radius: 12px;">
                                    </div>
                                    
                                    <div class="flower-alert alert-warning mt-3">
                                        <i class="fas fa-info-circle"></i>
                                        This bloom will remain in the cloud garden
                                    </div>
                                    
                                    <button type="submit" name="upload_url" class="flower-btn btn-primary w-100 mt-3">
                                        <i class="fas fa-cloud me-2"></i>Save Cloud Bloom
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Update Tab -->
                            <div class="tab-pane fade" id="update">
                                <form method="post">
                                    <div class="form-flower">
                                        <label>Manual Path Update</label>
                                        <input type="text" name="new_image_path" 
                                               class="form-control-flower"
                                               value="<?php echo htmlspecialchars($product['image'] ?? ''); ?>"
                                               placeholder="assets/images/products/bouquets/flower.jpg">
                                        <small class="text-muted mt-1 d-block">Relative path or full bloom URL</small>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <label class="mb-2">Quick Bloom Presets:</label>
                                        <div class="preset-grid">
                                            <div class="preset-btn" onclick="setFlowerPath('assets/images/products/bouquets/rose-bouquet.jpg')">
                                                Rose Bouquet
                                            </div>
                                            <div class="preset-btn" onclick="setFlowerPath('assets/images/products/bouquets/lily-arrangement.jpg')">
                                                Lily Arrangement
                                            </div>
                                            <div class="preset-btn" onclick="setFlowerPath('assets/images/products/seasonal/spring-mix.jpg')">
                                                Spring Mix
                                            </div>
                                            <div class="preset-btn" onclick="setFlowerPath('https://images.unsplash.com/photo-1464207687429-7505649dae38?auto=format&fit=crop&w=600&q=80')">
                                                Unsplash Bloom
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" name="update_image" class="flower-btn btn-primary w-100 mt-4">
                                        <i class="fas fa-save me-2"></i>Update Bloom Path
                                    </button>
                                </form>
                            </div>
                            
                            <!-- Mass Fix Tab -->
                            <div class="tab-pane fade" id="mass">
                                <form method="post">

<div class="form-flower">
                                        <label>Select Garden Treatment:</label>
                                        <select name="fix_type" class="form-control-flower">
                                            <option value="add_prefix">Add 'assets/' to local blooms</option>
                                            <option value="set_fallback">Set fallback for empty blooms</option>
                                        </select>
                                    </div>
                                    
                                    <div class="flower-alert alert-warning mt-3">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        This will treat multiple blooms in the garden
                                    </div>
                                    
                                    <button type="submit" name="mass_fix" class="flower-btn btn-secondary w-100 mt-3" onclick="return confirm('Careful! This will affect many blooms in our garden. Continue?')">
                                        <i class="fas fa-magic me-2"></i>Apply Garden Treatment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bloom Report -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-print" style="color: var(--primary);"></i> Bloom Report
                    </div>
                    <div class="card-body">
                        <div class="flower-alert alert-warning">
                            <i class="fas fa-file-pdf"></i>
                            Generate a beautiful report of your blooms
                        </div>
                        
                        <button class="flower-btn btn-outline-pink w-100 mb-3" onclick="generateBloomReport()">
                            <i class="fas fa-file-alt me-2"></i>Full Garden Report
                        </button>
                        
                        <button class="flower-btn btn-secondary w-100" onclick="printCurrentBloom()">
                            <i class="fas fa-print me-2"></i>Print This Bloom
                        </button>
                    </div>
                </div>

Sopha 🪻💜, [2/17/2026 6:48 PM]
<!-- Garden Tips -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-leaf" style="color: var(--primary);"></i> Garden Tips
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-start mb-3">
                            <div style="background: var(--light-pink); padding: 12px; border-radius: 12px; margin-right: 15px;">
                                <i class="fas fa-camera" style="color: var(--primary); font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <h6 style="color: var(--dark); font-weight: 600;">Optimal Bloom Size</h6>
                                <p class="text-muted mb-0" style="font-size: 0.9rem;">600×800 pixels with white background</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start mb-3">
                            <div style="background: var(--light-purple); padding: 12px; border-radius: 12px; margin-right: 15px;">
                                <i class="fas fa-folder" style="color: var(--secondary); font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <h6 style="color: var(--dark); font-weight: 600;">Bloom Organization</h6>
                                <p class="text-muted mb-0" style="font-size: 0.9rem;">Organize by: Roses, Lilies, Seasonal, Bouquets</p>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-start">
                            <div style="background: #e8f7f0; padding: 12px; border-radius: 12px; margin-right: 15px;">
                                <i class="fas fa-tag" style="color: var(--success); font-size: 1.2rem;"></i>
                            </div>
                            <div>
                                <h6 style="color: var(--dark); font-weight: 600;">Naming Convention</h6>
                                <p class="text-muted mb-0" style="font-size: 0.9rem;">flower-type-color-occasion.jpg</p>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h6 style="color: var(--primary); font-weight: 600; margin-bottom: 15px;">Quick SQL for Blooms:</h6>
                            <div style="background: var(--light-pink); padding: 15px; border-radius: 12px; font-family: monospace; font-size: 0.85rem; overflow-x: auto;">
                                -- Fix rose garden paths<br>
                                UPDATE products SET image = 'assets/...'<br>
                                WHERE name LIKE '%Rose%';
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


<!-- Action Buttons -->
        <div class="text-center mt-5 no-print">
            <div class="d-flex gap-3 justify-content-center flex-wrap">
                <a href="product-detail.php?id=<?php echo $product_id; ?>" class="flower-btn btn-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Bouquet
                </a>
                
                <a href="?id=<?php echo $product_id; ?>" class="flower-btn btn-secondary">
                    <i class="fas fa-sync-alt me-2"></i>Refresh Garden
                </a>
                
                <button class="flower-btn btn-outline-pink" onclick="generateBloomReport()">
                    <i class="fas fa-print me-2"></i>Print Bloom Report
                </button>
            </div>
            
            <p class="text-muted mt-4 mb-0" style="font-size: 0.9rem;">
                <i class="fas fa-info-circle me-1"></i>
                La Flora Bloom Debug Tool v2.0 | Today's Garden: <?php echo date('F j, Y'); ?>
            </p>
        </div>
    </div>

    <!-- Bloom Report HTML (Hidden) -->
    <div id="bloomReport" style="display: none;">
        <!-- Report content will be generated here -->
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Set flower path helper
    function setFlowerPath(path) {
        document.querySelector('[name="new_image_path"]').value = path;
    }
    
    // Set garden path helper
    function setGardenPath(path) {
        const targetInput = document.getElementById('target_path');
        const currentValue = targetInput.value;
        
        // If current value is a filename, keep the filename
        if (currentValue && currentValue.includes('/')) {
            const filename = currentValue.split('/').pop();
            targetInput.value = path + filename;
        } else {
            targetInput.value = path;
        }
    }
    
    // Preview URL image
    function previewUrl() {
        const urlInput = document.getElementById('imageUrl');
        const urlPreview = document.getElementById('urlPreview');
        const url = urlInput.value.trim();
        
        if (url) {
            urlPreview.src = url;
            urlPreview.style.display = 'block';
        }
    }
    
    // Generate bloom report
    function generateBloomReport() {
        const printContent = `
            <div style="padding: 40px; font-family: 'Poppins', sans-serif;">
                <div style="text-align: center; margin-bottom: 40px;">
                    <h1 style="color: #FF6B8B; font-family: 'Playfair Display', serif; margin-bottom: 10px;">🌺 La Flora Garden Report</h1>
                    <p style="color: #666;">Generated on ${new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    <hr style="border-color: #E8D5D8; margin: 20px 0;">
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 40px;">
                    <div style="background: white; padding: 20px; border-radius: 12px; border: 2px solid #E8D5D8; text-align: center;">
                        <div style="font-size: 2.5rem; font-weight: bold; color: #2D2D2D;"><?php echo $total_products; ?></div>
                        <div style="color: #666; font-size: 0.9rem; text-transform: uppercase;">Total Blooms</div>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 12px; border: 2px solid #E8D5D8; text-align: center;">
                        <div style="font-size: 2.5rem; font-weight: bold; color: #00B894;"><?php echo $valid_images; ?></div>
                        <div style="color: #666; font-size: 0.9rem; text-transform: uppercase;">Fresh Blooms</div>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 12px; border: 2px solid #E8D5D8; text-align: center;">

Sopha 🪻💜, [2/17/2026 6:48 PM]
<div style="font-size: 2.5rem; font-weight: bold; color: #FF7675;"><?php echo $broken_images; ?></div>
                        <div style="color: #666; font-size: 0.9rem; text-transform: uppercase;">Wilted Blooms</div>
                    </div>
                    <div style="background: white; padding: 20px; border-radius: 12px; border: 2px solid #E8D5D8; text-align: center;">
                        <div style="font-size: 2.5rem; font-weight: bold; color: #FDCB6E;"><?php echo $missing_images; ?></div>
                        <div style="color: #666; font-size: 0.9rem; text-transform: uppercase;">Missing Blooms</div>
                    </div>
                </div>
                
                <div style="background: white; padding: 30px; border-radius: 16px; border: 2px solid #E8D5D8; margin-bottom: 40px;">
                    <h3 style="color: #FF6B8B; margin-bottom: 25px; font-family: 'Playfair Display', serif;">Current Bloom Details</h3>
                    <?php if ($product): ?>
                    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
                        <div>
                            <strong style="color: #666;">Bloom ID:</strong><br>
                            <span style="font-weight: bold; color: #FF6B8B;">#<?php echo $product['id']; ?></span>
                        </div>
                        <div>
                            <strong style="color: #666;">Bloom Name:</strong><br>
                            <span style="font-weight: bold; color: #2D2D2D;"><?php echo htmlspecialchars($product['name']); ?></span>
                        </div>
                    </div>
                    <div style="margin-top: 15px;">
                        <strong style="color: #666;">Bloom Path:</strong><br>
                        <code style="background: #FFF0F3; padding: 8px 12px; border-radius: 8px; display: inline-block; margin-top: 5px;">
                            <?php echo htmlspecialchars($product['image'] ?? 'NULL'); ?>
                        </code>
                    </div>
                    <div style="margin-top: 15px;">
                        <strong style="color: #666;">Bloom Status:</strong><br>
                        <?php if (empty($product['image'])): ?>
                            <span style="background: #FFEAEA; color: #FF7675; padding: 6px 12px; border-radius: 20px; font-size: 0.9rem; display: inline-block; margin-top: 5px;">
                                NO IMAGE FOUND
                            </span>
                        <?php elseif ($file_exists): ?>
                            <span style="background: #E8F7F0; color: #00B894; padding: 6px 12px; border-radius: 20px; font-size: 0.9rem; display: inline-block; margin-top: 5px;">
                                FRESH & READY
                            </span>
                        <?php else: ?>
                            <span style="background: #FFEAEA; color: #FF7675; padding: 6px 12px; border-radius: 20px; font-size: 0.9rem; display: inline-block; margin-top: 5px;">
                                WILTED
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #E8D5D8;">
                    <p style="color: #666; font-size: 0.9rem;">
                        La Flora Garden Management System • Keep Your Blooms Fresh
                    </p>
                </div>
            </div>
        `;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>La Flora Bloom Report</title>
                <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                <style>


body { margin: 0; padding: 0; background: #F8F5F0; }
                </style>
            </head>
            <body>
                ${printContent}
                <script>
                    setTimeout(() => { window.print(); window.close(); }, 500);
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
    
    // Print current bloom
    function printCurrentBloom() {
        const printContent = `
            <div style="padding: 40px; font-family: 'Poppins', sans-serif;">
                <div style="text-align: center; margin-bottom: 30px;">
                    <h2 style="color: #FF6B8B; font-family: 'Playfair Display', serif; margin-bottom: 10px;">
                        🌸 Bloom Details Report
                    </h2>
                    <p style="color: #666;">Single Bloom Analysis</p>
                </div>
                
                <?php if ($product): ?>
                <div style="background: white; padding: 30px; border-radius: 16px; border: 2px solid #E8D5D8;">
                    <h3 style="color: #FF6B8B; margin-bottom: 20px; font-family: 'Playfair Display', serif;">
                        <?php echo htmlspecialchars($product['name']); ?>
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 25px;">
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Bloom ID:</strong>
                            <span style="font-weight: bold; color: #FF6B8B; font-size: 1.1rem;">
                                #<?php echo $product['id']; ?>
                            </span>
                        </div>
                        <div>
                            <strong style="color: #666; display: block; margin-bottom: 5px;">Price:</strong>
                            <span style="font-weight: bold; color: #FF6B8B; font-size: 1.1rem;">
                                <?php echo isset($product['price']) ? '$' . number_format($product['price'], 2) : '$99.00'; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 20px;">
                        <strong style="color: #666; display: block; margin-bottom: 5px;">Image Path:</strong>
                        <code style="background: #FFF0F3; padding: 10px 15px; border-radius: 8px; display: block; font-family: monospace; word-break: break-all;">
                            <?php echo htmlspecialchars($product['image'] ?? 'NULL'); ?>
                        </code>
                    </div>
                    
                    <div>
                        <strong style="color: #666; display: block; margin-bottom: 10px;">Status Analysis:</strong>
                        <?php if (empty($product['image'])): ?>
                            <div style="background: #FFEAEA; color: #FF7675; padding: 12px 20px; border-radius: 8px; border-left: 4px solid #FF7675;">
                                ⚠️ No image found for this bloom
                            </div>
                        <?php elseif ($file_exists): ?>
                            <div style="background: #E8F7F0; color: #00B894; padding: 12px 20px; border-radius: 8px; border-left: 4px solid #00B894;">
                                ✅ Bloom is fresh and ready
                            </div>
                        <?php else: ?>
                            <div style="background: #FFEAEA; color: #FF7675; padding: 12px 20px; border-radius: 8px; border-left: 4px solid #FF7675;">
                                ❌ Bloom image is missing
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 1px solid #E8D5D8;">

<p style="color: #666; font-size: 0.9rem;">
                        Report generated: ${new Date().toLocaleString()}
                    </p>
                </div>
            </div>
        `;
        
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>La Flora - Bloom Details</title>
                <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
                <style>
                    body { margin: 0; padding: 0; background: #F8F5F0; }
                    @media print {
                        body { padding: 20px; }
                    }
                </style>
            </head>
            <body>
                ${printContent}
                <script>
                    setTimeout(() => { window.print(); window.close(); }, 500);
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }
    
    // Upload functionality
    document.addEventListener('DOMContentLoaded', function() {
        const uploadArea = document.getElementById('uploadArea');
        const imageFileInput = document.getElementById('imageFile');
        const previewContainer = document.getElementById('previewContainer');
        const previewImage = document.getElementById('previewImage');
        const fileInfo = document.getElementById('fileInfo');
        const uploadButton = document.getElementById('uploadButton');
        const targetPathInput = document.getElementById('target_path');
        
        // Click upload area to trigger file input
        uploadArea.addEventListener('click', function() {
            imageFileInput.click();
        });
        
        // Drag and drop functionality
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            if (e.dataTransfer.files.length) {
                imageFileInput.files = e.dataTransfer.files;
                handleFileSelect(e.dataTransfer.files[0]);
            }
        });
        
        // File input change
        imageFileInput.addEventListener('change', function(e) {
            if (this.files.length) {
                handleFileSelect(this.files[0]);
            }
        });
        
        // Handle file selection
        function handleFileSelect(file) {
            // Validate file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert('Please upload a flower image (JPG, PNG, GIF, WebP).');
                return;
            }
            
            // Validate file size (5MB max)
            if (file.size > 5 * 1024 * 1024) {
                alert('Image is too large. Maximum size is 5MB.');
                return;
            }
            
            // Generate default path if target path is empty or default
            if (!targetPathInput.value  targetPathInput.value === 'assets/images/products/bouquets/') {
                const productName = "<?php echo isset($product['name']) ? preg_replace('/[^a-z0-9]/', '-', strtolower($product['name'])) : 'bouquet'; ?>";
                const uniqueId = Date.now().toString(36) + Math.random().toString(36).substr(2);
                const extension = file.name.split('.').pop();
                const newFilename = productName + '-' + uniqueId.substring(0, 6) + '.' + extension;

Sopha 🪻💜, [2/17/2026 6:48 PM]
targetPathInput.value = 'assets/images/products/bouquets/' + newFilename;
            } else if (targetPathInput.value.endsWith('/')) {
                // If path ends with slash, add filename
                const productName = "<?php echo isset($product['name']) ? preg_replace('/[^a-z0-9]/', '-', strtolower($product['name'])) : 'bouquet'; ?>";
                const uniqueId = Date.now().toString(36) + Math.random().toString(36).substr(2);
                const extension = file.name.split('.').pop();
                const newFilename = productName + '-' + uniqueId.substring(0, 6) + '.' + extension;
                targetPathInput.value += newFilename;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewContainer.style.display = 'block';
                
                // Update file info
                fileInfo.innerHTML = `
                    <strong>${file.name}</strong><br>
                    ${(file.size / 1024).toFixed(1)} KB • Will be planted at:<br>
                    <code style="background: #FFF0F3; padding: 4px 8px; border-radius: 4px; margin-top: 5px; display: inline-block;">${targetPathInput.value}</code>
                `;
                
                // Enable upload button
                uploadButton.disabled = false;
                
                // Scroll to preview
                previewContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            };
            reader.readAsDataURL(file);
        }
        
        // Update target path when user types
        targetPathInput.addEventListener('input', function() {
            if (imageFileInput.files.length) {
                const file = imageFileInput.files[0];
                fileInfo.innerHTML = `
                    <strong>${file.name}</strong><br>
                    ${(file.size / 1024).toFixed(1)} KB • Will be planted at:<br>
                    <code style="background: #FFF0F3; padding: 4px 8px; border-radius: 4px; margin-top: 5px; display: inline-block;">${targetPathInput.value}</code>
                `;
            }
        });
        
        // Show garden statistics in console
        console.log('🌺 La Flora Garden Statistics 🌺');
        console.log('Total Bouquets: <?php echo $total_products; ?>');
        console.log('Fresh Blooms: <?php echo $valid_images; ?>');
        console.log('Wilted Blooms: <?php echo $broken_images; ?>');
        console.log('Missing Blooms: <?php echo $missing_images; ?>');
        console.log('Cloud Blooms: <?php echo $url_images; ?>');
        console.log('Local Blooms: <?php echo $local_images; ?>');
        console.log('🌷 Keep your garden blooming! 🌷');
    });

document.addEventListener('DOMContentLoaded', function() {
    // Logo header interactive effects
    const logoBox = document.querySelector('.logo-box');
    const logoIcon = document.querySelector('.logo-icon');
    const headerButtons = document.querySelectorAll('.btn-header');
    
    // Logo box click animation
    if (logoBox) {
        logoBox.addEventListener('click', function() {
            // Add pulse animation
            this.style.animation = 'pulse 0.5s ease';
            
            // Change icon temporarily
            const originalIcon = logoIcon.textContent;
            logoIcon.textContent = '✨';
            
            // Reset after animation
            setTimeout(() => {
                this.style.animation = '';
                logoIcon.textContent = originalIcon;
            }, 500);
            
            // Refresh page data
            refreshPageData();
        });
        
        // Hover effect
        logoBox.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px) scale(1.05)';
            this.style.boxShadow = '0 15px 35px rgba(255, 107, 139, 0.4)';
        });
        
        logoBox.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
            this.style.boxShadow = '0 8px 25px rgba(255, 107, 139, 0.3)';
        });
    }
    
    // Header buttons enhancement
    headerButtons.forEach(button => {
        // Add ripple effect
        button.addEventListener('click', function(e) {
            // Create ripple
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.7);
                transform: scale(0);
                animation: ripple-animation 0.6s linear;
                width: ${size}px;
                height: ${size}px;
                top: ${y}px;
                left: ${x}px;
                pointer-events: none;
            `;
            
            this.appendChild(ripple);
            
            // Remove ripple after animation
            setTimeout(() => {
                ripple.remove();
            }, 600);
            
            // Icon animation
            const icon = this.querySelector('i');
            if (icon) {
                icon.style.transform = 'rotate(360deg)';
                setTimeout(() => {
                    icon.style.transform = '';
                }, 300);
            }
        });
        
        // Hover sound effect (optional)
        button.addEventListener('mouseenter', function() {
            // Play subtle hover sound (optional)
            playHoverSound();
        });
    });
    
    // Dynamic title based on time of day
    updateGreeting();
    
    // Add floating particles background
    createFloatingParticles();
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Alt + B for Back button
        if (e.altKey && e.key === 'b') {
            const backBtn = document.querySelector('.btn-header:first-child');
            if (backBtn) backBtn.click();
        }
        
        // Alt + R for Refresh button
        if (e.altKey && e.key === 'r') {
            const refreshBtn = document.querySelector('.btn-header:nth-child(2)');
            if (refreshBtn) refreshBtn.click();
        }
        
        // Alt + L for Logo click
        if (e.altKey && e.key === 'l') {
            if (logoBox) logoBox.click();
        }
    });
    
    // Functions
    function refreshPageData() {
        console.log('Refreshing page data...');
        // You can add AJAX call here to refresh data without page reload
        // For now, just show a notification

showNotification('Refreshing data...', 'info');
    }
    
    function updateGreeting() {
        const hour = new Date().getHours();
        let greeting = '';
        
        if (hour < 12) greeting = 'Morning';
        else if (hour < 18) greeting = 'Afternoon';
        else greeting = 'Evening';
        
        const subtitle = document.querySelector('.logo-subtitle');
        if (subtitle) {
            const originalText = subtitle.textContent;
            subtitle.textContent = `Good ${greeting}! ${originalText}`;
            
            // Restore original after 5 seconds
            setTimeout(() => {
                subtitle.textContent = originalText;
            }, 5000);
        }
    }
    
    function createFloatingParticles() {
        const header = document.querySelector('.logo-header');
        if (!header) return;
        
        // Create particles container
        const particlesContainer = document.createElement('div');
        particlesContainer.className = 'floating-particles';
        particlesContainer.style.cssText = `
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
        `;
        
        header.appendChild(particlesContainer);
        
        // Create particles
        const particles = ['🌸', '🌺', '🌷', '🌼', '🌻', '💮', '🏵', '🥀'];
        
        for (let i = 0; i < 15; i++) {
            const particle = document.createElement('div');
            particle.className = 'floating-particle';
            particle.textContent = particles[Math.floor(Math.random() * particles.length)];
            
            // Random position and size
            const size = Math.random() * 20 + 15;
            const left = Math.random() * 100;
            const duration = Math.random() * 20 + 20;
            const delay = Math.random() * 5;
            
            particle.style.cssText = `
                position: absolute;
                font-size: ${size}px;
                left: ${left}%;
                top: 100%;
                opacity: ${Math.random() * 0.3 + 0.1};
                animation: floatUp ${duration}s linear ${delay}s infinite;
                transform: rotate(${Math.random() * 360}deg);
            `;
            
            particlesContainer.appendChild(particle);
        }
        
        // Add CSS for floating animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes floatUp {
                0% {
                    transform: translateY(0) rotate(0deg);
                    opacity: 0;
                }
                10% {
                    opacity: 0.3;
                }
                90% {
                    opacity: 0.3;
                }
                100% {
                    transform: translateY(-100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    function playHoverSound() {
        // Optional: Add hover sound
        // This is a simple beep sound using Web Audio API
        if (window.AudioContext  window.webkitAudioContext) {
            try {
                const audioContext = new (window.AudioContext  window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();
                
                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);
                
                oscillator.frequency.value = 800;
                oscillator.type = 'sine';
                
                gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
                
                oscillator.start();
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (e) {


console.log('Audio context not supported');
            }
        }
    }
    
    function showNotification(message, type = 'info') {
        // Create notification
        const notification = document.createElement('div');
        notification.className = `header-notification ${type}`;
        notification.textContent = message;
        
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            background: linear-gradient(135deg, #FF6B8B, #9B5DE5);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            animation: slideIn 0.3s ease, fadeOut 0.3s ease 2.7s;
        `;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);
        
        // Add CSS for notification animation
        if (!document.querySelector('#notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
            style.textContent = `
                @keyframes slideIn {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes fadeOut {
                    from {
                        opacity: 1;
                    }
                    to {
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }
    }
    
    // Performance optimization
    let lastScrollTop = 0;
    const logoHeader = document.querySelector('.logo-header');
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Hide/show header on scroll
        if (scrollTop > lastScrollTop && scrollTop > 100) {
            // Scrolling down
            logoHeader.style.transform = 'translateY(-100%)';
            logoHeader.style.transition = 'transform 0.3s ease';
        } else {
            // Scrolling up
            logoHeader.style.transform = 'translateY(0)';
        }
        
        lastScrollTop = scrollTop;
    });
    
    // Initialize tooltips
    initializeTooltips();
    
    function initializeTooltips() {
        headerButtons.forEach(button => {
            const tooltip = document.createElement('div');
            tooltip.className = 'header-tooltip';
            
            if (button.querySelector('.fa-arrow-left')) {
                tooltip.textContent = 'Back to Product (Alt+B)';
            } else if (button.querySelector('.fa-sync-alt')) {
                tooltip.textContent = 'Refresh Page (Alt+R)';
            }
            
            tooltip.style.cssText = `
                position: absolute;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 6px 12px;
                border-radius: 6px;
                font-size: 0.8rem;
                white-space: nowrap;
                top: -40px;
                left: 50%;
                transform: translateX(-50%);
                opacity: 0;
                transition: opacity 0.3s ease;
                pointer-events: none;
                z-index: 100;
            `;
            
            button.appendChild(tooltip);
            
            button.addEventListener('mouseenter', () => {
                tooltip.style.opacity = '1';
            });
            
            button.addEventListener('mouseleave', () => {
                tooltip.style.opacity = '0';
            });
        });
    }
});
    </script>
</body>
</html>

<?php mysqli_close($conn); ?>