<?php
// product-detail.php - STANDALONE VERSION (No separate files needed!)
session_start();

// ===== PRODUCTS DATABASE (Built-in) =====
function getAllProducts() {
    return [
        [
            'id' => 1,
            'name' => 'Classic Red Roses Bouquet',
            'category' => 'Roses',
            'category_name' => 'Roses',
            'price' => 30.8,
            'image' => 'assets/images/products/Rose/product24.jpg',
            'description' => 'A timeless arrangement of 24 premium red roses with baby breath',
            'badge' => 'Best Seller',
            'stock' => 50,
            'rating' => 4.8
        ],
        [
            'id' => 2,
            'name' => 'Spring Flower Bouquet',
            'category' => 'Bouquets',
            'category_name' => 'Bouquets',
            'price' => 31.1,
            'image' => 'assets/images/products/product 1.jpg',
            'description' => 'Fresh seasonal tulips, daffodils, and daisies arranged by our master florists',
            'badge' => 'Seasonal',
            'stock' => 35,
            'rating' => 4.7
        ],
        [
            'id' => 3,
            'name' => 'Orchid Flower Arrangement',
            'category' => 'Flowers',
            'category_name' => 'Flowers',
            'price' => 28.9,
            'image' => 'assets/images/products/product 2.jpg',
            'description' => 'Exotic purple orchids in a handcrafted ceramic vase',
            'badge' => 'Premium',
            'stock' => 20,
            'rating' => 4.9
        ],
        [
            'id' => 4,
            'name' => 'White Wedding Flowers',
            'category' => 'Wedding',
            'category_name' => 'Wedding',
            'price' => 30.99,
            'image' => 'assets/images/products/Wedding.jpg',
            'description' => 'Elegant white lilies and roses perfect for wedding ceremonies',
            'badge' => 'Wedding',
            'stock' => 25,
            'rating' => 5.0
        ],
        [
            'id' => 5,
            'name' => 'Sunflower Basket',
            'category' => 'Seasonal',
            'category_name' => 'Seasonal',
            'price' => 37.99,
            'image' => 'assets/images/products/product 2.jpg',
            'description' => 'Bright sunflowers to cheer any room',
            'badge' => 'Happy',
            'stock' => 40,
            'rating' => 4.6
        ],
        [
            'id' => 6,
            'name' => 'Pink Rose Romance',
            'category' => 'Roses',
            'category_name' => 'Roses',
            'price' => 31.99,
            'image' => 'assets/images/products/product12.jpg',
            'description' => 'Delicate pink roses arranged with greenery and white accent flowers',
            'badge' => 'Romantic',
            'stock' => 45,
            'rating' => 4.7
        ],
        [
            'id' => 7,
            'name' => 'Mixed Rose Bouquet',
            'category' => 'Roses',
            'category_name' => 'Roses',
            'price' => 30.11,
            'image' => 'assets/images/products/product13).jpg',
            'description' => 'Beautiful combination of red, pink, and white roses',
            'badge' => 'Popular',
            'stock' => 38,
            'rating' => 4.8
        ],
        [
            'id' => 8,
            'name' => 'Elegant Lily Arrangement',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/Lily/product16.jpg',
            'description' => 'Stunning white lilies with eucalyptus leaves',
            'badge' => 'Elegant',
            'stock' => 30,
            'rating' => 4.9
        ],
        [
            'id' => 9,
            'name' => 'Lily Garden Mix',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/Rose/product24.jpg',
            'description' => 'Colorful lilies in various shades',
            'badge' => 'Garden Fresh',
            'stock' => 28,
            'rating' => 4.7
        ],
        [
            'id' => 10,
            'name' => 'Premium Lily Collection',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/product15.jpg',
            'description' => 'Luxurious lily arrangement with premium packaging',
            'badge' => 'Luxury',
            'stock' => 15,
            'rating' => 5.0
        ],
        [
            'id' => 11,
            'name' => 'Oriental Lily Bouquet',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/product18.jpg',
            'description' => 'Fragrant oriental lilies in rich colors',
            'badge' => 'Fragrant',
            'stock' => 32,
            'rating' => 4.8
        ],
        [
            'id' => 12,
            'name' => 'Lily Elegance',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/product19.jpg',
            'description' => 'Classic lily arrangement for any occasion',
            'badge' => 'Classic',
            'stock' => 35,
            'rating' => 4.6
        ],
        [
            'id' => 13,
            'name' => 'Summer Lily Basket',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/Tulip/product22.jpg',
            'description' => 'Bright summer lilies in a woven basket',
            'badge' => 'Summer',
            'stock' => 27,
            'rating' => 4.7
        ],
        [
            'id' => 14,
            'name' => 'White Lily Paradise',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/Rose/product25.jpg',
            'description' => 'Pure white lilies symbolizing peace and purity',
            'badge' => 'Pure',
            'stock' => 33,
            'rating' => 4.9
        ],
        [
            'id' => 15,
            'name' => 'Pink Lily Delight',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/Tulip/product23.jpg',
            'description' => 'Soft pink lilies with delicate accents',
            'badge' => 'Delicate',
            'stock' => 29,
            'rating' => 4.8
        ],
        [
            'id' => 16,
            'name' => 'Autumn Flower Bouquet',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/Lily/product 6.jpg',
            'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
            'badge' => 'Autumn',
            'stock' => 25,
            'rating' => 4.7
        ],
        [
            'id' => 17,
            'name' => 'Harvest Lily Mix',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/Tulip/product31.jpg',
            'description' => 'Mixed lilies in harvest colors',
            'badge' => 'Seasonal',
            'stock' => 26,
            'rating' => 4.6
        ],
        [
            'id' => 18,
            'name' => 'Tropical Lily Paradise',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/Rose/product26.jpg',
            'description' => 'Exotic tropical lilies with vibrant colors',
            'badge' => 'Exotic',
            'stock' => 22,
            'rating' => 4.9
        ],
        [
            'id' => 19,
            'name' => 'Sunset Lily Arrangement',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/Lily/product 7.jpg',
            'description' => 'Warm sunset-colored lilies',
            'badge' => 'Romantic',
            'stock' => 24,
            'rating' => 4.8
        ],
        [
            'id' => 20,
            'name' => 'Golden Hour Lily Bouquet',
            'category' => 'Lily',
            'category_name' => 'Lily',
            'price' => 28.9,
            'image' => 'assets/images/products/Lily/product 8.jpg',
            'description' => 'Golden and amber lilies for special moments',
            'badge' => 'Special',
            'stock' => 21,
            'rating' => 4.7
        ],
        [
                    'id' => 21,
                    'name' => 'Classic Red Roses Bouquet',
                    'category' => 'Roses',
                    'price' => 30.8,
                    'image' => 'assets/images/products/Rose/Rose1 (2).jpg',
                    'description' => 'Our signature arrangement of 24 premium long-stem red roses.',
                    'badge' => 'Best Seller'
                ],
                [
                    'id' => 22,
                    'name' => 'Spring Flower Bouquet',
                    'category' => 'Bouquets',
                    'price' => 31.1,
                    'image' => 'assets/images/products/Lily/lily (14).jpg',
                    'description' => 'Celebrate the season of renewal with this stunning spring bouquet featuring fresh tulips.',
                    'badge' => 'Seasonal'
                ],
                [
                    'id' => 23,
                    'name' => 'Orchid Flower Arrangement',
                    'category' => 'Flowers',
                    'price' => 28.9,
                    'image' => 'assets/images/products/Rose/Rose1 (3).jpg',
                    'description' => 'this low-maintenance arrangement blooms for weeks .',
                    'badge' => 'Premium'
                ],
                [
                    'id' => 24,
                    'name' => 'White Wedding Flowers',
                    'category' => 'Wedding',
                    'price' => 30.99,
                    'image' => 'assets/images/products/wedding/wed1.jpg',
                    'description' => 'Elegant white lilies and roses perfect for wedding ceremonies.',
                    'badge' => 'Wedding'
                ],
                [
                    'id' => 25,
                    'name' => 'Sunflower Basket',
                    'category' => 'Seasonal',
                    'price' => 37.99,
                    'image' => 'assets/images/products/Lily/lily (15).jpg',
                    'description' => 'Bright sunflowers to cheer any room.',
                    'badge' => 'Happy'
                ],
                 [
                    'id' => 26,
                    'name' => 'Pink Rose Romance',
                    'category' => 'Roses',
                    'price' => 31.99,
                    'image' => 'assets/images/products/Rose/Rose1 (4).jpg',
                    'description' => 'Delicate pink roses arranged with greenery and white accent flowers.',
                    'badge' => 'Romantic'
                ],
                 [
                    'id' => 27,
                    'name' => 'Mixed Rose Bouquet',
                    'category' => 'Roses',
                    'price' => 30.11,
                    'image' => 'assets/images/products/Rose/Rose1 (5).jpg',
                    'description' => 'Beautiful combination of red, pink, and white roses.',
                    'badge' => 'Popular'
                ],
                 [
                    'id' => 28,
                    'name' => 'Elegant Lily Arrangement',
                    'category' => 'Lily',
                    'price' => 28.9,
                    'image' => 'assets/images/products/Lily/Lily.jpg',
                    'description' => 'Stunning white lilies with eucalyptus leaves.',
                    'badge' => 'Elegant'
                ],
        
    ];
}
function getProductById($id) {
    $products = getAllProducts();
    foreach ($products as $product) {
        if ($product['id'] == $id) {
            return $product;
        }
    }
    return null;
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id <= 0) {
    header('Location: index.php');
    exit();
}

// Fetch product from array
$product = getProductById($product_id);

// If product not found, redirect to index
if (!$product) {
    header('Location: index.php');
    exit();
}

// Set up product images
$product_images = [];
if (!empty($product['image'])) {
    $product_images[] = $product['image'];
}

// Add beautiful placeholder images
$placeholder_flowers = [
    'https://images.unsplash.com/photo-1563241527-3004b7be0ffd?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1518895949257-7621c3c786d7?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1490750967868-88aa4486c946?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1519225421980-715cb0215aed?auto=format&fit=crop&w=600&q=80'
];

while (count($product_images) < 4) {
    $product_images[] = $placeholder_flowers[count($product_images) % 4];
}

// Get related products (same category)
$all_products = getAllProducts();
$related_products = [];
foreach ($all_products as $p) {
    if ($p['category'] === $product['category'] && $p['id'] !== $product['id']) {
        $related_products[] = $p;
        if (count($related_products) >= 4) break;
    }
}

// Handle add to cart
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $quantity = intval($_POST['quantity']);
    
    if ($quantity < 1) {
        $message = "Please select a valid quantity.";
        $message_type = "error";
    } else {
        // Guest cart in session (simplified version)
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$product_id])) {
            $_SESSION['cart'][$product_id]['quantity'] += $quantity;
        } else {
            $_SESSION['cart'][$product_id] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image']
            ];
        }
        
        $message = "{$product['name']} has been added to your cart!";
        $message_type = "success";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - La Flora</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-green: #2A5934;
            --light-green: #4A7856;
            --cream: #F8F5F0;
            --beige: #E8E2D6;
            --gold: #C9A96E;
            --dark: #1A1A1A;
            --light-gray: #F5F5F0;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            color: var(--dark);
            background-color: #ffffff;
            line-height: 1.6;
        }
        
        .serif-font {
            font-family: 'Cormorant Garamond', serif;
        }
        
        .product-detail-section {
            padding: 60px 0 80px;
            background: var(--cream);
        }
        
        .product-breadcrumb {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 30px;
        }
        
        .product-breadcrumb a {
            color: var(--primary-green);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .product-breadcrumb a:hover {
            color: var(--light-green);
        }
        
        .product-gallery {
            position: relative;
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        
        .main-product-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .main-product-image:hover {
            opacity: 0.95;
        }
        
        .product-thumbnails {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding: 10px;
            flex-wrap: wrap;
        }
        
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: var(--transition);
        }
        
        .thumbnail:hover,
        .thumbnail.active {
            border-color: var(--primary-green);
            transform: translateY(-2px);
        }
        
        .product-info-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            box-shadow: var(--shadow-md);
            height: 100%;
        }
        
        .product-category-badge {
            display: inline-block;
            background: rgba(42, 89, 52, 0.1);
            color: var(--primary-green);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .product-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 20px;
            line-height: 1.2;
        }
        
        .product-price {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-green);
            margin-bottom: 25px;
        }


.product-description {
            color: #666;
            line-height: 1.7;
            margin-bottom: 30px;
            font-size: 1.05rem;
        }
        
        .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
        }
        
        .meta-item i {
            color: var(--primary-green);
            font-size: 1.1rem;
        }
        
        .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .stock-badge.in-stock {
            background: #E8F5E9;
            color: #2A5934;
        }
        
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .quantity-input {
            width: 120px;
            height: 50px;
            border: 2px solid var(--beige);
            border-radius: 8px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .quantity-input:focus {
            outline: none;
            border-color: var(--primary-green);
        }
        
        .btn-quantity {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--cream);
            border: 2px solid var(--beige);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-quantity:hover {
            background: var(--beige);
            border-color: var(--gold);
        }
        
        .btn-primary-custom {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            transition: var(--transition);
            text-decoration: none;
            cursor: pointer;
            width: 100%;
        }
        
        .btn-primary-custom:hover {
            background: var(--light-green);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .product-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .alert-custom {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success-custom {
            background: #E8F5E9;
            color: #2A5934;
            border-left: 4px solid #2A5934;
        }
        
        .alert-error-custom {
            background: #FFEBEE;
            color: #C62828;
            border-left: 4px solid #C62828;
        }
        
        .related-products-section {
            padding: 60px 0;
            background: white;
        }
        
        .section-title {
            font-family: 'Cormorant Garamond', serif;
            font-size: 2.5rem;
            font-weight: 600;
            text-align: center;
            margin-bottom: 16px;
            color: var(--primary-green);
        }
        
        .product-card-modern {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
            height: 100%;
            background: white;


}
        
        .product-card-modern:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-lg);
        }
        
        .product-img-container {
            height: 250px;
            overflow: hidden;
            position: relative;
        }
        
        .product-img-modern {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
            cursor: pointer;
        }
        
        .product-img-modern:hover {
            transform: scale(1.05);
        }
        
        .product-body {
            padding: 24px;
        }
        
        .product-category-modern {
            display: inline-block;
            color: var(--primary-green);
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .product-title-modern {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 12px;
            line-height: 1.3;
        }
        
        .product-price-modern {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-green);
        }
        
        .btn-view-details {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            color: var(--primary-green);
            border: 2px solid var(--primary-green);
            padding: 8px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: var(--transition);
            text-decoration: none;
            cursor: pointer;
        }
        
        .btn-view-details:hover {
            background: var(--primary-green);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Product Detail Section -->
    <section class="product-detail-section">
        <div class="container">
            <!-- Breadcrumb -->
            <div class="product-breadcrumb">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <span class="mx-2">/</span>
                <a href="shop.php">Shop</a>
                <span class="mx-2">/</span>
                <span><?php echo htmlspecialchars($product['name']); ?></span>
            </div>

            <!-- Success/Error Message -->
            <?php if ($message): ?>
            <div class="alert-<?php echo $message_type; ?>-custom alert-custom">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>


<div class="row g-4">
                <!-- Product Gallery -->
                <div class="col-lg-6">
                    <div class="product-gallery">
                        <img id="mainProductImage" 
                             src="<?php echo htmlspecialchars($product_images[0]); ?>" 
                             class="main-product-image" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='https://via.placeholder.com/600x500/E8F5E9/2A5934?text=<?php echo urlencode($product['name']); ?>'">
                        
                        <div class="product-thumbnails">
                            <?php foreach ($product_images as $index => $image): ?>
                            <img src="<?php echo htmlspecialchars($image); ?>" 
                                 class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 alt="Product thumbnail"
                                 onclick="changeMainImage('<?php echo htmlspecialchars($image); ?>', this)"
                                 onerror="this.src='https://via.placeholder.com/80x80/E8F5E9/2A5934?text=Img<?php echo $index + 1; ?>'">
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-lg-6">
                    <div class="product-info-card">
                        <span class="product-category-badge">
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category']); ?>
                        </span>
                        
                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                        
                        <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                        
                        <div class="product-meta">
                            <div class="meta-item">
                                <i class="fas fa-star"></i>
                                <span><?php echo number_format($product['rating'], 1); ?> Rating</span>
                            </div>
                            <div class="meta-item">
                                <span class="stock-badge in-stock">
                                    <i class="fas fa-check-circle"></i> <?php echo $product['stock']; ?> In Stock
                                </span>
                            </div>
                        </div>
                        
                        <form method="POST">
                            <div class="quantity-selector">
                                <label for="quantity"><strong>Quantity:</strong></label>
                                <button type="button" class="btn-quantity" onclick="decreaseQuantity()">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <input type="number" 
                                       id="quantity" 
                                       name="quantity" 
                                       class="quantity-input" 
                                       value="1" 
                                       min="1" 
                                       max="<?php echo $product['stock']; ?>">
                                <button type="button" class="btn-quantity" onclick="increaseQuantity()">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            
                            <div class="product-actions">
                                <button type="submit" name="add_to_cart" class="btn-primary-custom" style="flex: 1;">

<i class="fas fa-shopping-cart me-2"></i> Add to Cart
                                </button>
                            </div>
                        </form>
                        
                        <div class="mt-4 pt-4" style="border-top: 1px solid var(--beige);">
                            <p class="mb-2"><i class="fas fa-truck text-success me-2"></i> Free shipping on orders over $50</p>
                            <p class="mb-2"><i class="fas fa-undo text-success me-2"></i> 30-day return policy</p>
                            <p class="mb-0"><i class="fas fa-shield-alt text-success me-2"></i> 100% secure payment</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Related Products -->
    <?php if (!empty($related_products)): ?>
    <section class="related-products-section">
        <div class="container">
            <h2 class="section-title">Related Products</h2>
            <p class="text-center text-muted mb-5">You might also like these beautiful arrangements</p>
            
            <div class="row g-4">
                <?php foreach ($related_products as $related): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="product-card-modern">
                        <div class="product-img-container">
                            <a href="product-detail.php?id=<?php echo $related['id']; ?>">
                                <img src="<?php echo htmlspecialchars($related['image']); ?>" 
                                     class="product-img-modern" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>"
                                     onerror="this.src='https://via.placeholder.com/400x250/E8F5E9/2A5934?text=Flower'">
                            </a>
                        </div>
                        <div class="product-body">
                            <span class="product-category-modern"><?php echo htmlspecialchars($related['category']); ?></span>
                            <h5 class="product-title-modern serif-font">
                                <a href="product-detail.php?id=<?php echo $related['id']; ?>" 
                                   class="text-dark text-decoration-none">
                                    <?php echo htmlspecialchars($related['name']); ?>
                                </a>
                            </h5>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="product-price-modern">$<?php echo number_format($related['price'], 2); ?></span>
                                <a href="product-detail.php?id=<?php echo $related['id']; ?>" class="btn-view-details">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Product Image Gallery
    function changeMainImage(src, element) {
        document.getElementById('mainProductImage').src = src;
        
        // Update active thumbnail
        document.querySelectorAll('.thumbnail').forEach(thumb => {
            thumb.classList.remove('active');
        });
        element.classList.add('active');
    }

    // Quantity Control
    function increaseQuantity() {
        const input = document.getElementById('quantity');
        const max = parseInt(input.max);
        if (parseInt(input.value) < max) {
            input.value = parseInt(input.value) + 1;
        }
    }
function decreaseQuantity() {
        const input = document.getElementById('quantity');
        const min = parseInt(input.min);
        if (parseInt(input.value) > min) {
            input.value = parseInt(input.value) - 1;
        }
    }

    // Initialize quantity input validation
    document.addEventListener('DOMContentLoaded', function() {
        const quantityInput = document.getElementById('quantity');
        if (quantityInput) {
            quantityInput.addEventListener('change', function() {
                const value = parseInt(this.value);
                const min = parseInt(this.min);
                const max = parseInt(this.max);
                
                if (value < min) this.value = min;
                if (value > max) this.value = max;
            });
        }
    });
    </script>
</body>
</html>