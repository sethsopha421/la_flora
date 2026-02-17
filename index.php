<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LA FLORA - Premium Flower Boutique</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet">
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Cormorant+Garamond:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ===== MODERN FLORAL THEME ===== */
        :root {
            --primary-green: #044412;
            --light-green: #4A7856;
            --cream: #F8F5F0;
            --beige: #E8E2D6;
            --gold: #C9A96E;
            --dark: #1A1A1A;
            --light-gray: #F5F5F5;
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
            overflow-x: hidden;
        }
        
        .serif-font {
            font-family: 'Cormorant Garamond', serif;
        }
        
        /* Hero Section - Modern Design */
        
            /* ===== ELEGANT HERO SECTION ===== */
            .hero-section-elegant {
                position: relative;
                min-height: 50vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: linear-gradient(135deg, #e777d2 0%, #e447a8 100%);
                overflow: hidden;
                padding: 50px 0 80px;
                height: 500px;
            }

            .hero-background {
                position: absolute;
                inset: 0;
                overflow: hidden;
            }

            /* Decorative Flowers */
            .hero-flower-left {
                position: absolute;
                left: -20px;
                bottom: 0;
                width: 300px;
                height: 400px;
                opacity: 0.4;
                animation: floatLeft 6s ease-in-out infinite;
            }

            .hero-flower-right {
                position: absolute;
                right: -20px;
                top: 50%;
                transform: translateY(-50%);
                width: 300px;
                height: 400px;
                opacity: 0.4;
                animation: floatRight 7s ease-in-out infinite;
            }

            @keyframes floatLeft {
                0%, 100% {
                    transform: translateY(0px) rotate(0deg);
                }
                50% {
                    transform: translateY(-20px) rotate(2deg);
                }
            }

            @keyframes floatRight {
                0%, 100% {
                    transform: translateY(-50%) translateX(0px) rotate(0deg);
                }
                50% {
                    transform: translateY(-50%) translateX(-10px) rotate(-2deg);
                }
            }

            .flower-illustration {
                width: 100%;
                height: 100%;
                filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.2));
            }

            .flower-group {
                animation: gentle-sway 4s ease-in-out infinite;
                transform-origin: bottom center;
            }

            @keyframes gentle-sway {
                0%, 100% {
                    transform: rotate(0deg);
                }
                25% {
                    transform: rotate(2deg);
                }
                75% {
                    transform: rotate(-2deg);
                }
            }

            /* Hero Content */
            .hero-content-elegant {
                position: relative;
                z-index: 10;
                padding: 40px 20px;
            }

            /* Delivery Badge */
            .delivery-badge {
                display: inline-flex;
                align-items: center;
                background: rgba(255, 255, 255, 0.15);
                backdrop-filter: blur(10px);
                border: 2px solid rgba(255, 255, 255, 0.3);
                color: white;
                padding: 12px 28px;
                border-radius: 50px;
                font-size: 15px;
                font-weight: 500;
                letter-spacing: 0.5px;
                margin-bottom: 10px;
                transition: all 0.3s ease;
            }

            .delivery-badge:hover {
                background: rgba(255, 255, 255, 0.25);
                border-color: rgba(255, 255, 255, 0.5);
                transform: translateY(-2px);
                
            }

            .delivery-badge i {
                font-size: 18px;
            }

            /* Main Heading */
            .hero-heading-elegant {
                font-family: 'Playfair Display', serif;
                font-size: 4.5rem;
                font-weight: 400;
                color: white;
                line-height: 1.2;
                margin-bottom: 50px;
                letter-spacing: -1px;
            }

            .hero-subheading {
                display: block;
                font-size: 4.5rem;
                font-weight: 400;
                margin-top: 10px;
                font-style: italic;
            }

            /* CTA Button */
            .btn-browse-elegant {
                display: inline-block;
                background: rgba(248, 245, 240, 0.95);
                color: #2d5f3f;
                padding: 18px 48px;
                border-radius: 8px;
                font-family: 'Poppins', sans-serif;
                font-size: 17px;
                font-weight: 500;
                letter-spacing: 0.3px;
                text-decoration: none;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
                position: relative;
                overflow: hidden;
            }

            .btn-browse-elegant::before {
                content: '';
                position: absolute;
                top: 50%;
                left: 50%;
                width: 0;
                height: 0;
                border-radius: 50%;
                background: rgba(45, 95, 63, 0.1);
                transform: translate(-50%, -50%);
                transition: width 0.6s ease, height 0.6s ease;
            }

            .btn-browse-elegant:hover::before {
                width: 300px;
                height: 300px;
            }

            .btn-browse-elegant:hover {
                background: rgba(255, 255, 255, 1);
                transform: translateY(-3px);
                box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
                color: #1a3d28;
            }

            .btn-browse-elegant i {
                margin-left: 8px;
                transition: transform 0.3s ease;
            }

            .btn-browse-elegant:hover i {
                transform: translateX(5px);
            }

            /* Responsive adjustments */
            @media (max-width: 992px) {
                .hero-section-elegant {
                    height: auto;
                    min-height: 400px;
                    padding: 60px 0;
                }

                .hero-heading-elegant {
                    font-size: 3rem;
                }

                .hero-subheading {
                    font-size: 3rem;
                }

                .hero-flower-left,
                .hero-flower-right {
                    width: 200px;
                    height: 300px;
                }
            }

            @media (max-width: 576px) {
                .hero-heading-elegant {
                    font-size: 2.5rem;
                }

                .hero-subheading {
                    font-size: 2.5rem;
                }

                .btn-browse-elegant {
                    padding: 14px 32px;
                    font-size: 15px;
                }
            }
        
        /* Featured Products Section */
        .featured-section {
            background: linear-gradient(to bottom, #ffffff 0%, var(--cream) 100%);
            padding: 80px 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 60px;
        }
        
        .section-subtitle {
            color: var(--gold);
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        
        .section-title {
            font-size: 3rem;
            color: var(--dark);
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .section-description {
            max-width: 600px;
            margin: 0 auto;
            color: #666;
            font-size: 1.1rem;
        }
        
        /* Modern Product Card */
        .product-card-modern {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .product-card-modern:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }
        
        .product-img-container {
            position: relative;
            overflow: hidden;
            height: 280px;
        }
        
        .product-img-modern {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card-modern:hover .product-img-modern {
            transform: scale(1.1);
        }
        
        .product-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary-green);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }
        
        .product-body {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .product-category-modern {
            color: var(--gold);
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
        }
        
        .product-title-modern {
            font-size: 1.4rem;
            margin-bottom: 12px;
            color: var(--dark);
            font-weight: 600;
        }
        
        .product-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
            line-height: 1.6;
            flex: 1;
        }
        
        .product-price-modern {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-green);
        }
        
        .btn-view-details {
            background: var(--primary-green);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition);
            display: inline-block;
        }
        
        .btn-view-details:hover {
            background: var(--light-green);
            transform: translateX(5px);
            color: white;
        }
        
        /* Primary Button */
        .btn-primary-custom {
            background: var(--primary-green);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            text-decoration: none;
        }
        
        .btn-primary-custom:hover {
            background: var(--light-green);
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            color: white;
        }
        
        /* Animations */
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .slide-up {
            animation: slideUp 0.6s ease-out forwards;
            opacity: 0;
        }
        
        @media (max-width: 768px) {
            .section-title {
                font-size: 2rem;
            }
            
            .product-card-modern {
                margin-bottom: 30px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <!-- Hero Section -->
    <section class="hero-section-elegant">
        <div class="hero-background">
            <!-- Left Decorative Flower -->
            <div class="hero-flower-left">
                <svg class="flower-illustration" viewBox="0 0 300 400" xmlns="http://www.w3.org/2000/svg">
                    <g class="flower-group">
                        <!-- Stem -->
                        <path d="M150 400 Q140 250 145 100" stroke="#4a7856" stroke-width="6" fill="none" opacity="0.6"/>
                        
                        <!-- Leaves -->
                        <ellipse cx="120" cy="250" rx="30" ry="15" fill="#4a7856" opacity="0.5" transform="rotate(-30 120 250)"/>
                        <ellipse cx="170" cy="200" rx="30" ry="15" fill="#4a7856" opacity="0.5" transform="rotate(30 170 200)"/>
                        
                        <!-- Flower petals -->
                        <ellipse cx="150" cy="80" rx="25" ry="40" fill="#e777d2" opacity="0.7"/>
                        <ellipse cx="150" cy="80" rx="25" ry="40" fill="#e777d2" opacity="0.7" transform="rotate(60 150 80)"/>
                        <ellipse cx="150" cy="80" rx="25" ry="40" fill="#e777d2" opacity="0.7" transform="rotate(120 150 80)"/>
                        <ellipse cx="150" cy="80" rx="25" ry="40" fill="#e447a8" opacity="0.7" transform="rotate(30 150 80)"/>
                        <ellipse cx="150" cy="80" rx="25" ry="40" fill="#e447a8" opacity="0.7" transform="rotate(90 150 80)"/>
                        <ellipse cx="150" cy="80" rx="25" ry="40" fill="#e447a8" opacity="0.7" transform="rotate(150 150 80)"/>
                        
                        <!-- Center -->
                        <circle cx="150" cy="80" r="15" fill="#fff" opacity="0.9"/>
                        <circle cx="150" cy="80" r="10" fill="#f8d568" opacity="0.8"/>
                    </g>
                </svg>
            </div>

            <!-- Right Decorative Flower -->
            <div class="hero-flower-right">
                <svg class="flower-illustration" viewBox="0 0 300 400" xmlns="http://www.w3.org/2000/svg">
                    <g class="flower-group">
                        <!-- Stem -->
                        <path d="M150 400 Q160 250 155 120" stroke="#4a7856" stroke-width="6" fill="none" opacity="0.6"/>
                        
                        <!-- Leaves -->
                        <ellipse cx="180" cy="270" rx="30" ry="15" fill="#4a7856" opacity="0.5" transform="rotate(30 180 270)"/>
                        <ellipse cx="130" cy="220" rx="30" ry="15" fill="#4a7856" opacity="0.5" transform="rotate(-30 130 220)"/>
                        
                        <!-- Flower petals -->
                        <ellipse cx="150" cy="100" rx="30" ry="45" fill="#e447a8" opacity="0.6"/>
                        <ellipse cx="150" cy="100" rx="30" ry="45" fill="#e447a8" opacity="0.6" transform="rotate(45 150 100)"/>
                        <ellipse cx="150" cy="100" rx="30" ry="45" fill="#e447a8" opacity="0.6" transform="rotate(90 150 100)"/>
                        <ellipse cx="150" cy="100" rx="30" ry="45" fill="#e777d2" opacity="0.6" transform="rotate(135 150 100)"/>
                        
                        <!-- Center -->
                        <circle cx="150" cy="100" r="18" fill="#fff" opacity="0.9"/>
                        <circle cx="150" cy="100" r="12" fill="#f8d568" opacity="0.8"/>
                    </g>
                </svg>
            </div>
        </div>

        <div class="container hero-content-elegant">
            <div class="text-center">
                <div class="delivery-badge mb-4">
                    <i class="fas fa-truck me-2"></i>
                    Free Same-Day Delivery
                </div>
                
                <h1 class="hero-heading-elegant mb-4">
                    Fresh Flowers
                    <span class="hero-subheading d-block">Delivered Daily</span>
                </h1>
                
                <a href="#featured" class="btn-browse-elegant">
                    Browse Collection
                    <i class="fas fa-arrow-down"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-section" id="featured">
    <div class="container">
        <div class="section-header">
            <p class="section-subtitle">Our Collection</p>
            <h2 class="section-title serif-font">Featured Flowers</h2>
            <p class="section-description">
                Handpicked fresh flowers, beautifully arranged and delivered with care
            </p>
        </div>
        
        <div class="row g-4">
            <?php
            // Sample product data - In production, this would come from your database
            $featured_products = [
                [
                    'id' => 1,
                    'name' => 'Classic Red Roses',
                    'category' => 'Roses',
                    'price' => 49.99,
                    'image' => 'assets/images/products/Rose/Rose1 (2).jpg',
                    'description' => 'A dozen premium long-stem red roses, elegantly arranged',
                    'badge' => 'Bestseller'
                ],
                [
                    'id' => 2,
                    'name' => 'Elegant White Lilies',
                    'category' => 'Lilies',
                    'price' => 39.99,
                    'image' => 'assets/images/products/product 2.jpg',
                    'description' => 'Pure white lilies symbolizing peace and tranquility',
                    'badge' => 'Popular'
                ],
                [
                    'id' => 3,
                    'name' => 'Vibrant Tulip Mix',
                    'category' => 'Tulips',
                    'price' => 34.99,
                    'image' => 'assets/images/products/Tulip/Tulip (2).jpg',
                    'description' => 'Colorful assortment of fresh Dutch tulips',
                    'badge' => 'New'
                ],
                [
                    'id' => 4,
                    'name' => 'Romantic Rose Bouquet',
                    'category' => 'Bouquets',
                    'price' => 59.99,
                    'image' => 'assets/images/products/product15.jpg',
                    'description' => 'Luxurious arrangement of pink and red roses with baby\'s breath',
                    'badge' => 'Premium'
                ],
                [
                    'id' => 5,
                    'name' => 'Spring Garden Mix',
                    'category' => 'Mixed',
                    'price' => 44.99,
                    'image' => 'assets/images/products/mix.jpg',
                    'description' => 'A delightful mix of seasonal spring flowers',
                    'badge' => 'Seasonal'
                ],
                [
                    'id' => 6,
                    'name' => 'Exotic Orchids',
                    'category' => 'Orchids',
                    'price' => 69.99,
                    'image' => 'assets/images/products/Wedding.jpg',
                    'description' => 'Stunning exotic orchids in a decorative pot',
                    'badge' => 'Luxury'
                ],
                [
                    'id' => 7,
                    'name' => 'Pastel Dream Bouquet',
                    'category' => 'Bouquets',
                    'price' => 54.99,
                    'image' => 'assets/images/products/product21.jpg',
                    'description' => 'Soft pastel roses and peonies in gentle pink and cream tones',
                    'badge' => 'Trending'
                ],
                [
                    'id' => 8,
                    'name' => 'Sunflower Delight',
                    'category' => 'Sunflowers',
                    'price' => 42.99,
                    'image' => 'assets/images/products/Tulip/Tulip (20).jpg',
                    'description' => 'Bright and cheerful sunflowers to brighten any day',
                    'badge' => 'Happy'
                ],
                [
                    'id' => 9,
                    'name' => 'Mixed Rose Bouquet',
                    'category' => 'Roses',
                    'price' => 39.99,
                    'image' => 'assets/images/products/Rose/product24.jpg',
                    'description' => 'Assorted colored roses in pink, yellow, and white varieties',
                    'badge' => 'Colorful'
                ],
                [
                    'id' => 10,
                    'name' => 'Sunflower Collection',
                    'category' => 'Seasonal',
                    'price' => 29.99,
                    'image' => 'assets/images/products/product15.jpg',
                    'description' => 'Bright and cheerful sunflowers with complementary wildflowers',
                    'badge' => 'Cheerful'
                ],
                [
                    'id' => 11,
                    'name' => 'Baby Breath Flowers',
                    'category' => 'Flowers',
                    'price' => 29.99,
                    'image' => 'assets/images/products/product18.jpg',
                    'description' => 'Delicate baby breath flowers perfect for weddings and special occasions',
                    'badge' => 'Delicate'
                ],
                [
                    'id' => 12,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Seasonal',
                    'price' => 29.99,
                    'image' => 'assets/images/products/product19.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 13,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Tulip',
                    'price' => 25.9,
                    'image' => 'assets/images/products/Tulip/product22.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 14,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Rose',
                    'price' => 28.7,
                    'image' => 'assets/images/products/Rose/product25.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 15,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Tulip',
                    'price' => 25.9,
                    'image' => 'assets/images/products/Tulip/product23.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 16,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Lily',
                    'price' => 27.7,
                    'image' => 'assets/images/products/Lily/product 6.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 17,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Tulip',
                    'price' => 26.9,
                    'image' => 'assets/images/products/Tulip/product31.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 18,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Rose',
                    'price' => 27.6,
                    'image' => 'assets/images/products/Rose/product26.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 19,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Lily',
                    'price' => 28.9,
                    'image' => 'assets/images/products/Lily/product 7.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 20,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Lily',
                    'price' => 28.9,
                    'image' => 'assets/images/products/Lily/product 8.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 21,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Rose',
                    'price' => 27.6,
                    'image' => 'assets/images/products/product15.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 22,
                    'name' => 'Autumn Flower Bouquet',
                    'category' => 'Tulip',
                    'price' => 25.9,
                    'image' => 'assets/images/products/product12.jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 23,
                    'name' => 'Rose Flower Bouquet',
                    'category' => 'Lily',
                    'price' => 28.9,
                    'image' => 'assets/images/products/Rose/Rose1 (14).jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                [
                    'id' => 24,
                    'name' => 'Tulip Flower Bouquet',
                    'category' => 'Rose',
                    'price' => 27.6,
                    'image' => 'assets/images/products/Tulip/Tulip (7).jpg',
                    'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                    'badge' => 'Autumn'
                ],
                    [
                        'id' => 25,
                        'name' => 'Lily Flower Bouquet',
                        'category' => 'Lily',
                        'price' => 28.9,
                        'image' => 'assets/images/products/Lily/lily (15).jpg',
                        'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                        'badge' => 'Autumn'
                    ],
                    [
                        'id' => 26,
                        'name' => 'Lily Flower Bouquet',
                        'category' => 'Lily',
                        'price' => 28.9,
                        'image' => 'assets/images/products/Lily/lily (7).jpg',
                        'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                        'badge' => 'Autumn'
                    ],
                    [
                        'id' => 27,
                        'name' => 'Rose Bouquet',
                        'category' => 'Rose',
                        'price' => 28.9,
                        'image' => 'assets/images/products/Rose/Rose1 (2).jpg',
                        'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                        'badge' => 'Autumn'
                    ],
                    [
                        'id' => 28,
                        'name' => 'Tulip Flower Bouquet',
                        'category' => 'Tulip',
                        'price' => 28.9,
                        'image' => 'assets/images/products/Tulip/product23.jpg',
                        'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                        'badge' => 'Autumn'
                    ],
                    [
                        'id' => 29,
                        'name' => 'Lily Flower Bouquet',
                        'category' => 'Lily',
                        'price' => 28.9,
                        'image' => 'assets/images/products/Lily/lily (11).jpg',
                        'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                        'badge' => 'Autumn'
                    ],
                    [
                        'id' => 30,
                        'name' => 'Lily Flower Bouquet',
                        'category' => 'Lily',
                        'price' => 28.9,
                        'image' => 'assets/images/products/Lily/lily (12).jpg',
                        'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                        'badge' => 'Autumn'
                    ],
                    [
                        'id' => 31,
                        'name' => 'Tulip Flower Bouquet',
                        'category' => 'Tulip',
                        'price' => 28.9,
                        'image' => 'assets/images/products/Tulip/Tulip (6).jpg',
                        'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                        'badge' => 'Autumn'
                    ],
                    [
                        'id' => 32,
                        'name' => 'Rose Flower Bouquet',
                        'category' => 'Rose',
                        'price' => 28.9,
                        'image' => 'assets/images/products/Rose/Rose1 (14).jpg',
                        'description' => 'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',
                        'badge' => 'Autumn'
                    ],
            ];

            foreach ($featured_products as $index => $product):
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6">
                <div class="product-card-modern slide-up" style="animation-delay: <?php echo ($index * 0.1) + 0.1; ?>s;">
                    <div class="product-img-container">
                        <img src="<?php echo $product['image']; ?>" 
                             class="product-img-modern" 
                             alt="<?php echo $product['name']; ?>"
                             onerror="this.src='https://via.placeholder.com/400x300/E8F5E9/2A5934?text=Flower'">
                        <?php if(isset($product['badge'])): ?>
                            <span class="product-badge"><?php echo $product['badge']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="product-body">
                        <span class="product-category-modern"><?php echo $product['category']; ?></span>
                        <h5 class="product-title-modern serif-font"><?php echo $product['name']; ?></h5>
                        <p class="product-description"><?php echo $product['description']; ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="product-price-modern">$<?php echo number_format($product['price'], 2); ?></span>
                            <a href="flower-detail.php?id=<?php echo $product['id']; ?>" class="btn-view-details">View detail</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="row mt-5">
            <div class="col-12 text-center">
                <a href="shop.php" class="btn-primary-custom">
                    <span>View All Flower Collections</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>s
</section>

    
    <?php
    // Footer would be included here
    // require_once 'includes/footer.php';
    ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple animation on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                    }
                });
            }, { threshold: 0.1 });
            
            // Observe elements for animation
            document.querySelectorAll('.slide-up').forEach((el) => observer.observe(el));
        });
            
    // Add parallax effect to flowers on scroll (optional enhancement)
    document.addEventListener('DOMContentLoaded', function() {
        const heroSection = document.querySelector('.hero-section-elegant');
        
        if (heroSection && window.innerWidth >= 992) {
            const leftFlower = document.querySelector('.hero-flower-left');
            const rightFlower = document.querySelector('.hero-flower-right');
            
            window.addEventListener('scroll', function() {
                const scrolled = window.pageYOffset;
                const heroBottom = heroSection.offsetTop + heroSection.offsetHeight;
                
                if (scrolled < heroBottom) {
                    if (leftFlower) {
                        leftFlower.style.transform = `translateY(${scrolled * 0.3}px)`;
                    }
                    if (rightFlower) {
                        rightFlower.style.transform = `translateY(-50%) translateX(${-scrolled * 0.2}px)`;
                    }
                }
            });
        }
    });
    </script>
</body>
</html>