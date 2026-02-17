<?php
session_start();

require_once 'includes/database.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    $isLoggedIn = isset($_SESSION['user_id']);
    
    if ($isLoggedIn && $conn) {
        $check_query = "SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ii", $_SESSION['user_id'], $product_id);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $new_quantity = $row['quantity'] + $quantity;
            $update_query = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, "iii", $new_quantity, $_SESSION['user_id'], $product_id);
            mysqli_stmt_execute($update_stmt);
        } else {
            $insert_query = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "iii", $_SESSION['user_id'], $product_id, $quantity);
            mysqli_stmt_execute($insert_stmt);
        }
    } else {
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'added_at' => time()
            ];
        }
    }
    
    $_SESSION['cart_success'] = "Item added to cart successfully!";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

$cart_count = 0;
if (isset($_SESSION['user_id']) && $conn) {
    $query = "SELECT COUNT(*) as count FROM cart WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    $cart_count = $row['count'];
} else {
    $cart_count = count($_SESSION['cart']);
}

$featured_products = [
    [
        'id' => 1,
        'name' => 'Red Rose Bouquet',
        'category' => 'roses',
        'price' => 29.99,
        'badge' => 'Popular',
        'image' => 'assets/images/products/roses/rose-bouquet-red.jpg',
        'description' => 'Classic red roses arranged with baby\'s breath'
    ],
    [
        'id' => 2,
        'name' => 'Spring Mix',
        'category' => 'seasonal',
        'price' => 34.99,
        'badge' => 'New',
        'image' => 'assets/images/products/seasonal/spring-mix.jpg',
        'description' => 'Fresh seasonal flowers in vibrant colors'
    ],
    [
        'id' => 3,
        'name' => 'White Orchid',
        'category' => 'exotic',
        'price' => 49.99,
        'badge' => 'Exotic',
        'image' => 'assets/images/products/exotic/white-orchid.jpg',
        'description' => 'Elegant white orchid arrangement'
    ],
    [
        'id' => 4,
        'name' => 'Tulip Collection',
        'category' => 'seasonal',
        'price' => 32.99,
        'badge' => 'Fresh',
        'image' => 'assets/images/products/seasonal/tulip-collection.jpg',
        'description' => 'Colorful tulips in spring colors'
    ],
    [
        'id' => 5,
        'name' => 'Sunflower Basket',
        'category' => 'seasonal',
        'price' => 37.99,
        'badge' => 'Happy',
        'image' => 'assets/images/products/seasonal/sunflower-basket.jpg',
        'description' => 'Bright sunflowers to cheer any room'
    ],
    [
        'id' => 6,
        'name' => 'Lily Arrangement',
        'category' => 'bouquets',
        'price' => 39.99,
        'badge' => 'Elegant',
        'image' => 'assets/images/products/bouquets/lily-arrangement.jpg',
        'description' => 'Beautiful lilies with greenery accents'
    ],
    [
        'id' => 7,
        'name' => 'Mixed Bouquet',
        'category' => 'bouquets',
        'price' => 42.99,
        'badge' => 'Popular',
        'image' => 'assets/images/products/bouquets/mixed-bouquet.jpg',
        'description' => 'A delightful mix of seasonal flowers'
    ],
    [
        'id' => 8,
        'name' => 'Pink Rose Box',
        'category' => 'roses',
        'price' => 45.99,
        'badge' => 'Luxury',
        'image' => 'assets/images/products/roses/pink-rose-box.jpg',
        'description' => 'Luxurious pink roses in gift box'
    ]
];

$products = [];
if ($conn) {
    $query = "SELECT * FROM products ORDER BY created_at DESC";
    $result = mysqli_query($conn, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $products[] = $row;
        }
    }
}

if (empty($products)) {
    $products = $featured_products;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Floral Haven - Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ===== CSS VARIABLES ===== */
        :root {
            --primary: #e777d2;
            --primary-dark: #e447a8;
            --primary-light: #d1fae5;
            --secondary: #8b5cf6;
            --accent: #f59e0b;
            --danger: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --info: #3b82f6;
            
            --bg-light: #f9fafb;
            --bg-white: #ffffff;
            --bg-card: #ffffff;
            
            --text-dark: #1f2937;
            --text-medium: #6b7280;
            --text-light: #9ca3af;
            
            --border-light: #e5e7eb;
            --border-medium: #d1d5db;
            
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
            
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-full: 9999px;
            
            --transition-fast: 150ms ease;
            --transition-normal: 300ms ease;
            --transition-slow: 500ms ease;
            
            --z-dropdown: 1000;
            --z-sticky: 1020;
            --z-fixed: 1030;
            --z-modal: 1050;
            --z-popover: 1060;
            --z-tooltip: 1070;
        }

        /* ===== BASE STYLES ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            color: var(--text-dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        ::selection {
            background-color: var(--primary-light);
            color: var(--primary-dark);
        }

        /* ===== TYPOGRAPHY ===== */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 1rem;
        }

        .display-4 {
            font-weight: 800;
            letter-spacing: -0.025em;
        }

        .lead {
            font-size: 1.25rem;
            color: var(--text-medium);
        }

        /* ===== LAYOUT COMPONENTS ===== */
        .shop-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 4rem 0 3rem;
            margin-bottom: 3rem;
            position: relative;
            overflow: hidden;
        }

        .shop-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%2310b981' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.1;
        }

        /* ===== PRODUCT CARD ===== */
        .product-card {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            overflow: hidden;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-md);
            height: 100%;
            position: relative;
            border: 1px solid var(--border-light);
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        .product-card.featured {
            border: 2px solid var(--primary);
        }

        .product-image {
            height: 240px;
            overflow: hidden;
            position: relative;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform var(--transition-slow);
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.7), transparent);
            opacity: 0;
            transition: opacity var(--transition-normal);
        }

        .product-card:hover .product-overlay {
            opacity: 1;
        }

        .product-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 6px 12px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.025em;
            z-index: 2;
            transition: all var(--transition-fast);
            cursor: pointer;
        }

        .product-badge:hover {
            transform: scale(1.1);
        }

        .badge-popular { background: var(--accent); color: white; }
        .badge-new { background: var(--info); color: white; }
        .badge-sale { background: var(--danger); color: white; }
        .badge-exclusive { background: var(--secondary); color: white; }

        .quick-view-btn {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(10px);
            background: var(--bg-white);
            color: var(--primary);
            border: none;
            padding: 10px 24px;
            border-radius: var(--radius-full);
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            opacity: 0;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-md);
            z-index: 2;
        }

        .quick-view-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateX(-50%) translateY(0) scale(1.05);
        }

        .product-card:hover .quick-view-btn {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-category {
            display: inline-block;
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            padding: 4px 8px;
            background: var(--primary-light);
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
            cursor: pointer;
        }

        .product-category:hover {
            background: var(--primary);
            color: white;
        }

        .product-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            line-height: 1.4;
            cursor: pointer;
            transition: color var(--transition-fast);
        }

        .product-title:hover {
            color: var(--primary);
        }

        .product-description {
            color: var(--text-medium);
            font-size: 0.875rem;
            line-height: 1.6;
            margin-bottom: 1rem;
            height: 56px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
        }

        .product-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .product-price .old-price {
            font-size: 1rem;
            color: var(--text-light);
            text-decoration: line-through;
            font-weight: 500;
        }

        /* ===== QUANTITY SELECTOR ===== */
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .qty-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid var(--border-medium);
            background: var(--bg-white);
            color: var(--text-dark);
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .qty-btn::after {
            content: '';
            position: absolute;
            inset: 0;
            background: var(--primary);
            opacity: 0;
            transition: opacity var(--transition-fast);
        }

        .qty-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: scale(1.1);
        }

        .qty-btn:hover::after {
            opacity: 0.1;
        }

        .qty-input {
            width: 60px;
            height: 40px;
            text-align: center;
            border: 2px solid var(--border-light);
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-dark);
            background: var(--bg-white);
            transition: all var(--transition-fast);
        }

        .qty-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
        }

        /* ===== ADD TO CART BUTTON ===== */
        .add-to-cart-btn {
            width: 100%;
            padding: 0.875rem 1rem;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all var(--transition-normal);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
        }

        .add-to-cart-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }

        .add-to-cart-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .add-to-cart-btn:hover::before {
            left: 100%;
        }

        .add-to-cart-btn.loading {
            pointer-events: none;
            opacity: 0.8;
        }

        .add-to-cart-btn.loading span {
            display: none;
        }

        .add-to-cart-btn.loading::after {
            content: '';
            width: 20px;
            height: 20px;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
        }

        /* ===== FILTER SECTION ===== */
        .filter-card {
            background: var(--bg-white);
            border-radius: var(--radius-xl);
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2.5rem;
            border: 1px solid var(--border-light);
        }

        .filter-title {
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .filter-title i {
            color: var(--primary);
        }

        .form-select {
            border: 2px solid var(--border-light);
            border-radius: var(--radius-lg);
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            color: var(--text-dark);
            background: var(--bg-white);
            cursor: pointer;
            transition: all var(--transition-fast);
        }

        .form-select:hover {
            border-color: var(--primary-light);
        }

        .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px var(--primary-light);
            outline: none;
        }

        /* ===== CATEGORY TAGS ===== */
        .category-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .category-tag {
            padding: 0.625rem 1.25rem;
            background: var(--bg-white);
            color: var(--text-medium);
            border: 2px solid var(--border-light);
            border-radius: var(--radius-full);
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .category-tag:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .category-tag.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* ===== NOTIFICATION ===== */
        .cart-notification {
            position: fixed;
            top: 100px;
            right: 24px;
            background: var(--bg-white);
            padding: 1rem 1.25rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
            z-index: var(--z-modal);
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideInRight 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border-left: 4px solid var(--success);
            max-width: 380px;
            cursor: pointer;
        }

        .cart-notification:hover {
            transform: translateX(-4px);
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .notification-message {
            color: var(--text-medium);
            font-size: 0.875rem;
        }

        .notification-close {
            background: none;
            border: none;
            color: var(--text-light);
            font-size: 1.25rem;
            cursor: pointer;
            padding: 0.25rem;
            border-radius: var(--radius-sm);
            transition: all var(--transition-fast);
        }

        .notification-close:hover {
            color: var(--text-dark);
            background: var(--bg-light);
        }

        /* ===== MINI CART ===== */
        .mini-cart {
            position: fixed;
            bottom: 24px;
            right: 24px;
            background: var(--bg-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-xl);
            padding: 1.5rem;
            z-index: var(--z-fixed);
            transform: translateX(400px) scale(0.95);
            transition: all 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            max-width: 340px;
            width: 100%;
            border: 1px solid var(--border-light);
        }

        .mini-cart.show {
            transform: translateX(0) scale(1);
        }

        .mini-cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-light);
        }

        .mini-cart-title {
            font-weight: 700;
            font-size: 1.125rem;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cart-count-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger);
            color: white;
            border-radius: var(--radius-full);
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            animation: badgePulse 2s infinite;
        }

        .cart-info {
            margin-bottom: 1.5rem;
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }

        .cart-item-name {
            font-size: 0.875rem;
            color: var(--text-medium);
        }

        .cart-item-price {
            font-weight: 600;
            color: var(--text-dark);
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border-light);
        }

        .cart-total-label {
            font-weight: 600;
            color: var(--text-dark);
        }

        .cart-total-value {
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--primary);
        }

        .cart-actions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .cart-btn {
            padding: 0.75rem 1rem;
            border-radius: var(--radius-lg);
            font-weight: 600;
            font-size: 0.875rem;
            text-align: center;
            text-decoration: none;
            transition: all var(--transition-fast);
        }

        .cart-btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }

        .cart-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .cart-btn-secondary {
            background: var(--bg-light);
            color: var(--text-dark);
            border: 1px solid var(--border-light);
        }

        .cart-btn-secondary:hover {
            background: var(--border-light);
        }

        /* ===== QUICK VIEW MODAL ===== */
        .quick-view-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.75);
            z-index: var(--z-modal);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            backdrop-filter: blur(8px);
        }

        .quick-view-modal.show {
            display: flex;
            animation: fadeIn 0.3s ease;
        }

        .quick-view-content {
            background: var(--bg-white);
            border-radius: var(--radius-xl);
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlideUp 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            box-shadow: var(--shadow-2xl);
        }

        /* ===== LOADING STATES ===== */
        .loading-spinner {
            display: none;
            text-align: center;
            padding: 3rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid var(--border-light);
            border-top-color: var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }

        .no-products {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-medium);
        }

        .no-products-icon {
            font-size: 3rem;
            color: var(--border-medium);
            margin-bottom: 1rem;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes modalSlideUp {
            from {
                transform: translateY(50px) scale(0.95);
                opacity: 0;
            }
            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 768px) {
            .shop-header {
                padding: 3rem 0 2rem;
            }
            
            .display-4 {
                font-size: 2.5rem;
            }
            
            .product-card {
                margin-bottom: 1.5rem;
            }
            
            .filter-card {
                padding: 1.5rem;
            }
            
            .category-tag {
                padding: 0.5rem 1rem;
                font-size: 0.8125rem;
            }
            
            .mini-cart {
                bottom: 1rem;
                right: 1rem;
                max-width: 300px;
            }
            
            .cart-notification {
                right: 1rem;
                left: 1rem;
                max-width: none;
            }
        }

        @media (max-width: 576px) {
            .product-image {
                height: 200px;
            }
            
            .quantity-selector {
                gap: 0.5rem;
            }
            
            .qty-btn {
                width: 32px;
                height: 32px;
            }
            
            .qty-input {
                width: 50px;
                height: 36px;
            }
        }

        /* ===== UTILITY CLASSES ===== */
        .text-gradient {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .hover-lift {
            transition: transform var(--transition-normal);
        }

        .hover-lift:hover {
            transform: translateY(-4px);
        }

        .transition-all {
            transition: all var(--transition-normal);
        }

        .cursor-pointer {
            cursor: pointer;
        }

        .user-select-none {
            user-select: none;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <div class="shop-header">
        <div class="container position-relative">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">Discover Our Floral Collection</h1>
                    <p class="lead mb-4 opacity-90">Fresh flowers for every occasion, handcrafted with love and care</p>
                    <div class="d-flex flex-wrap gap-3">
                        <span class="badge bg-white text-dark p-3 hover-lift cursor-pointer" onclick="filterByTag('popular')">
                            <i class="fas fa-fire me-2"></i> Most Popular
                        </span>
                        <span class="badge bg-white text-dark p-3 hover-lift cursor-pointer" onclick="filterByTag('new')">
                            <i class="fas fa-seedling me-2"></i> New Arrivals
                        </span>
                        <span class="badge bg-white text-dark p-3 hover-lift cursor-pointer" onclick="filterByTag('sale')">
                            <i class="fas fa-tag me-2"></i> On Sale
                        </span>
                        <span class="badge bg-white text-dark p-3 hover-lift cursor-pointer" onclick="filterByTag('exclusive')">
                            <i class="fas fa-crown me-2"></i> Exclusive
                        </span>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <div class="position-relative d-inline-block">
                        <a href="cart.php" class="btn btn-light btn-lg px-4 hover-lift" onclick="toggleMiniCart(event)">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="cart-count-badge" id="cartBadge"><?php echo $cart_count; ?></span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cart Notification -->
    <?php if (isset($_SESSION['cart_success'])): ?>
        <div class="cart-notification" id="cartNotification" onclick="hideNotification()">
            <div class="notification-icon">
                <i class="fas fa-check-circle text-success fs-4"></i>
            </div>
            <div class="notification-content">
                <h6 class="notification-title">Added to Cart!</h6>
                <p class="notification-message"><?php echo $_SESSION['cart_success']; ?></p>
            </div>
            <button class="notification-close" onclick="event.stopPropagation(); hideNotification()">
                &times;
            </button>
        </div>
        <?php unset($_SESSION['cart_success']); ?>
    <?php endif; ?>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Filter Section -->
        <div class="filter-card glass-effect">
            <h5 class="filter-title">
                <i class="fas fa-sliders-h"></i>
                Filter & Sort
            </h5>
            
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-2">Category</label>
                        <select class="form-select" id="categoryFilter">
                            <option value="">All Categories</option>
                            <option value="roses">Roses</option>
                            <option value="seasonal">Seasonal</option>
                            <option value="bouquets">Bouquets</option>
                            <option value="exotic">Lily</option>
                            <option value="luxury">Tulips</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-2">Price Range</label>
                        <select class="form-select" id="priceFilter">
                            <option value="">All Prices</option>
                            <option value="0-30">Under $30</option>
                            <option value="30-50">$30 - $50</option>
                            <option value="50-100">$50 - $100</option>
                            <option value="100+">Over $100</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-2">Sort By</label>
                        <select class="form-select" id="sortFilter">
                            <option value="featured">Featured</option>
                            <option value="price-low">Price: Low to High</option>
                            <option value="price-high">Price: High to Low</option>
                            <option value="name-asc">Name: A to Z</option>
                            <option value="name-desc">Name: Z to A</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-2">Availability</label>
                        <select class="form-select" id="availabilityFilter">
                            <option value="all">All Items</option>
                            <option value="in-stock">In Stock</option>
                            <option value="new-arrivals">New Arrivals</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Category Tags -->
            <div class="category-tags">
                <span class="category-tag active" data-category="all">
                    <i class="fas fa-th-large"></i> All Flowers
                </span>
                <span class="category-tag" data-category="roses">
                    <i class="fas fa-rose"></i> Roses
                </span>
                <span class="category-tag" data-category="seasonal">
                    <i class="fas fa-leaf"></i> Seasonal
                </span>
                <span class="category-tag" data-category="bouquets">
                    <i class="fas fa-bouquet"></i> Bouquets
                </span>
                <span class="category-tag" data-category="exotic">
                    <i class="fas fa-spa"></i> Lily
                </span>
                <span class="category-tag" data-category="luxury">
                    <i class="fas fa-gem"></i> Tulips
                </span>
            </div>
            
            <!-- Quick Filters -->
            <div class="d-flex flex-wrap gap-2 mt-3">
                <button class="btn btn-sm btn-outline-primary" onclick="setPriceFilter('0-30')">
                    <i class="fas fa-dollar-sign me-1"></i> Under $30
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="setPriceFilter('30-50')">
                    <i class="fas fa-dollar-sign me-1"></i> $30-50
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="setPriceFilter('50-100')">
                    <i class="fas fa-dollar-sign me-1"></i> $50-100
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="setPriceFilter('100+')">
                    <i class="fas fa-crown me-1"></i> Premium
                </button>
                <button class="btn btn-sm btn-outline-secondary ms-auto" onclick="resetAllFilters()">
                    <i class="fas fa-redo me-1"></i> Reset
                </button>
            </div>
        </div>
        
        <!-- Products Grid -->
        <div class="row g-4" id="productsGrid">
            <?php foreach ($products as $product): 
                $badge_class = '';
                if (isset($product['badge'])) {
                    switch(strtolower($product['badge'])) {
                        case 'new': $badge_class = 'badge-new'; break;
                        case 'popular': $badge_class = 'badge-popular'; break;
                        case 'sale': $badge_class = 'badge-sale'; break;
                        case 'exclusive': $badge_class = 'badge-exclusive'; break;
                        default: $badge_class = 'badge-new';
                    }
                }
            ?>
                <div class="col-xl-3 col-lg-4 col-md-6 product-item" 
                     data-category="<?php echo strtolower($product['category'] ?? ''); ?>"
                     data-price="<?php echo $product['price']; ?>"
                     data-name="<?php echo htmlspecialchars($product['name']); ?>"
                     data-badge="<?php echo strtolower($product['badge'] ?? ''); ?>">
                    <div class="product-card hover-lift">
                        <div class="product-image">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 class="main-image"
                                 data-product-id="<?php echo $product['id']; ?>">
                            
                            <div class="product-overlay"></div>
                            
                            <?php if (isset($product['badge']) && $product['badge']): ?>
                                <span class="product-badge <?php echo $badge_class; ?>"
                                      onclick="filterByBadge('<?php echo strtolower($product['badge']); ?>')">
                                    <?php echo htmlspecialchars($product['badge']); ?>
                                </span>
                            <?php endif; ?>
                            
                            <button class="quick-view-btn" onclick="showQuickView(<?php echo $product['id']; ?>)">
                                <i class="fas fa-eye me-1"></i> Quick View
                            </button>
                        </div>
                        
                        <div class="product-info">
                            <span class="product-category" onclick="filterByCategory('<?php echo strtolower($product['category'] ?? ''); ?>')">
                                <?php echo htmlspecialchars($product['category'] ?? ''); ?>
                            </span>
                            
                            <h5 class="product-title" onclick="showQuickView(<?php echo $product['id']; ?>)">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </h5>
                            
                            <p class="product-description">
                                <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="product-price">
                                    $<?php echo number_format($product['price'], 2); ?>
                                </div>
                                <button class="btn btn-sm btn-outline-secondary" onclick="toggleWishlist(<?php echo $product['id']; ?>, this)">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                            
                            <form method="POST" action="" class="add-to-cart-form">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                
                                <div class="quantity-selector">
                                    <button type="button" class="qty-btn" onclick="adjustQuantity(<?php echo $product['id']; ?>, -1)">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" name="quantity" id="qty-<?php echo $product['id']; ?>" 
                                           class="qty-input" value="1" min="1" max="99">
                                    <button type="button" class="qty-btn" onclick="adjustQuantity(<?php echo $product['id']; ?>, 1)">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                                
                                <button type="submit" name="add_to_cart" class="add-to-cart-btn"
                                        onclick="return addToCartHandler(<?php echo $product['id']; ?>, '<?php echo addslashes($product['name']); ?>')">
                                    <i class="fas fa-shopping-cart me-1"></i>
                                    <span>Add to Cart</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p class="text-muted mt-2">Loading more beautiful flowers...</p>
        </div>
        
        <!-- No Products Message -->
        <div class="no-products" id="noProducts" style="display: none;">
            <div class="no-products-icon">
                <i class="fas fa-search"></i>
            </div>
            <h4 class="mb-2">No products found</h4>
            <p class="text-muted mb-3">Try adjusting your filters or search terms</p>
            <button class="btn btn-primary" onclick="resetAllFilters()">
                <i class="fas fa-redo me-1"></i> Reset All Filters
            </button>
        </div>
    </div>
    
    <!-- Mini Cart -->
    <div class="mini-cart" id="miniCart">
        <div class="mini-cart-header">
            <h5 class="mini-cart-title">
                <i class="fas fa-shopping-bag"></i>
                Your Cart
            </h5>
            <button class="btn-close" onclick="hideMiniCart()"></button>
        </div>
        
        <div class="cart-info">
            <div class="cart-item">
                <span class="cart-item-name">Items:</span>
                <span class="cart-item-price" id="miniCartCount"><?php echo $cart_count; ?></span>
            </div>
            <div class="cart-total">
                <span class="cart-total-label">Total:</span>
                <span class="cart-total-value" id="miniCartTotal">$0.00</span>
            </div>
        </div>
        
        <div class="cart-actions">
            <a href="cart.php" class="cart-btn cart-btn-primary">View Cart</a>
            <a href="checkout.php" class="cart-btn cart-btn-secondary">Continue Shopping</a>
        </div>
    </div>
    
    <!-- Quick View Modal -->
    <div class="quick-view-modal" id="quickViewModal" onclick="closeQuickView()">
        <div class="quick-view-content" onclick="event.stopPropagation()">
            <!-- Content loaded via JavaScript -->
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // ===== CATEGORY FILTERING (IMPROVED) =====
        document.addEventListener('DOMContentLoaded', function() {
            const categoryTags = document.querySelectorAll('.category-tag');
            const productItems = document.querySelectorAll('.product-item');

            categoryTags.forEach(tag => {
                tag.addEventListener('click', function() {
                    // Remove active from all
                    categoryTags.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    const selected = (this.getAttribute('data-category') || '').toLowerCase();
                    let anyVisible = false;
                    productItems.forEach(item => {
                        const itemCategory = (item.getAttribute('data-category') || '').toLowerCase();
                        if (selected === 'all' || itemCategory === selected) {
                            item.style.display = '';
                            anyVisible = true;
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    // Show/hide no products message
                    document.getElementById('noProducts').style.display = anyVisible ? 'none' : '';
                });
            });
        });
    </script>
</body>
</html>
<?php
if (isset($conn)) {
    mysqli_close($conn);
}
?>