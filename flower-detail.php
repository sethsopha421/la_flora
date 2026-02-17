<?php
session_start();

// ─── Product Data ──────────────────────────────────────────────────────────
$products = [
    1  => ['id'=>1,  'name'=>'Classic Red Roses',        'category'=>'Roses',      'price'=>49.99, 'image'=>'assets/images/products/Rose/Rose1 (2).jpg',          'description'=>'A dozen premium long-stem red roses, elegantly arranged',                                    'badge'=>'Bestseller', 'stock'=>15],
    2  => ['id'=>2,  'name'=>'Elegant White Lilies',     'category'=>'Lilies',     'price'=>39.99, 'image'=>'assets/images/products/product 2.jpg',               'description'=>'Pure white lilies symbolizing peace and tranquility',                                        'badge'=>'Popular',    'stock'=>8],
    3  => ['id'=>3,  'name'=>'Vibrant Tulip Mix',        'category'=>'Tulips',     'price'=>34.99, 'image'=>'assets/images/products/Tulip/Tulip (2).jpg',         'description'=>'Colorful assortment of fresh Dutch tulips',                                                  'badge'=>'New',        'stock'=>20],
    4  => ['id'=>4,  'name'=>'Romantic Rose Bouquet',    'category'=>'Bouquets',   'price'=>59.99, 'image'=>'assets/images/products/product15.jpg',               'description'=>"Luxurious arrangement of pink and red roses with baby's breath",                            'badge'=>'Premium',    'stock'=>5],
    5  => ['id'=>5,  'name'=>'Spring Garden Mix',        'category'=>'Mixed',      'price'=>44.99, 'image'=>'assets/images/products/mix.jpg',                     'description'=>'A delightful mix of seasonal spring flowers',                                                'badge'=>'Seasonal',   'stock'=>12],
    6  => ['id'=>6,  'name'=>'Exotic Orchids',           'category'=>'Orchids',    'price'=>69.99, 'image'=>'assets/images/products/Wedding.jpg',                 'description'=>'Stunning exotic orchids in a decorative pot',                                                'badge'=>'Luxury',     'stock'=>7],
    7  => ['id'=>7,  'name'=>'Pastel Dream Bouquet',     'category'=>'Bouquets',   'price'=>54.99, 'image'=>'assets/images/products/product21.jpg',               'description'=>'Soft pastel roses and peonies in gentle pink and cream tones',                             'badge'=>'Trending',   'stock'=>9],
    8  => ['id'=>8,  'name'=>'Sunflower Delight',        'category'=>'Sunflowers', 'price'=>42.99, 'image'=>'assets/images/products/Tulip/Tulip (20).jpg',        'description'=>'Bright and cheerful sunflowers to brighten any day',                                         'badge'=>'Happy',      'stock'=>14],
    9  => ['id'=>9,  'name'=>'Mixed Rose Bouquet',       'category'=>'Roses',      'price'=>39.99, 'image'=>'assets/images/products/Rose/product24.jpg',          'description'=>'Assorted colored roses in pink, yellow, and white varieties',                              'badge'=>'Colorful',   'stock'=>11],
    10 => ['id'=>10, 'name'=>'Sunflower Collection',     'category'=>'Seasonal',   'price'=>29.99, 'image'=>'assets/images/products/product15.jpg',               'description'=>'Bright and cheerful sunflowers with complementary wildflowers',                            'badge'=>'Cheerful',   'stock'=>18],
    11 => ['id'=>11, 'name'=>'Baby Breath Flowers',      'category'=>'Flowers',    'price'=>29.99, 'image'=>'assets/images/products/product18.jpg',               'description'=>'Delicate baby breath flowers perfect for weddings and special occasions',                   'badge'=>'Delicate',   'stock'=>22],
    12 => ['id'=>12, 'name'=>'Autumn Flower Bouquet',    'category'=>'Seasonal',   'price'=>29.99, 'image'=>'assets/images/products/product19.jpg',               'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>16],
    13 => ['id'=>13, 'name'=>'Autumn Tulip Bouquet',     'category'=>'Tulip',      'price'=>25.90, 'image'=>'assets/images/products/Tulip/product22.jpg',         'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>10],
    14 => ['id'=>14, 'name'=>'Autumn Rose Bouquet',      'category'=>'Rose',       'price'=>28.70, 'image'=>'assets/images/products/Rose/product25.jpg',          'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>6],
    15 => ['id'=>15, 'name'=>'Autumn Tulip Delight',     'category'=>'Tulip',      'price'=>25.90, 'image'=>'assets/images/products/Tulip/product23.jpg',         'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>13],
    16 => ['id'=>16, 'name'=>'Autumn Lily Bouquet',      'category'=>'Lily',       'price'=>27.70, 'image'=>'assets/images/products/Lily/product 6.jpg',          'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>8],
    17 => ['id'=>17, 'name'=>'Tulip Fantasy',            'category'=>'Tulip',      'price'=>26.90, 'image'=>'assets/images/products/Tulip/product31.jpg',         'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>19],
    18 => ['id'=>18, 'name'=>'Rose Bliss',               'category'=>'Rose',       'price'=>27.60, 'image'=>'assets/images/products/Rose/product26.jpg',          'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>7],
    19 => ['id'=>19, 'name'=>'Golden Lily',              'category'=>'Lily',       'price'=>28.90, 'image'=>'assets/images/products/Lily/product 7.jpg',          'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>11],
    20 => ['id'=>20, 'name'=>'Ivory Lily Collection',    'category'=>'Lily',       'price'=>28.90, 'image'=>'assets/images/products/Lily/product 8.jpg',          'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>9],
    21 => ['id'=>21, 'name'=>'Velvet Rose Bouquet',      'category'=>'Rose',       'price'=>27.60, 'image'=>'assets/images/products/product15.jpg',               'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>14],
    22 => ['id'=>22, 'name'=>'Tulip Harvest',            'category'=>'Tulip',      'price'=>25.90, 'image'=>'assets/images/products/product12.jpg',               'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>17],
    23 => ['id'=>23, 'name'=>'Rose Flower Bouquet',      'category'=>'Lily',       'price'=>28.90, 'image'=>'assets/images/products/Rose/Rose1 (14).jpg',         'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>5],
    24 => ['id'=>24, 'name'=>'Tulip Flower Bouquet',     'category'=>'Rose',       'price'=>27.60, 'image'=>'assets/images/products/Tulip/Tulip (7).jpg',         'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>8],
    25 => ['id'=>25, 'name'=>'Lily Flower Bouquet',      'category'=>'Lily',       'price'=>28.90, 'image'=>'assets/images/products/Lily/lily (15).jpg',          'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>12],
    26 => ['id'=>26, 'name'=>'Lily Garden Bouquet',      'category'=>'Lily',       'price'=>28.90, 'image'=>'assets/images/products/Lily/lily (7).jpg',           'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>10],
    27 => ['id'=>27, 'name'=>'Rose Bouquet',             'category'=>'Rose',       'price'=>28.90, 'image'=>'assets/images/products/Rose/Rose1 (2).jpg',          'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>15],
    28 => ['id'=>28, 'name'=>'Tulip Flower Bouquet',     'category'=>'Tulip',      'price'=>28.90, 'image'=>'assets/images/products/Tulip/product23.jpg',         'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>6],
    29 => ['id'=>29, 'name'=>'Lily Flower Bouquet',      'category'=>'Lily',       'price'=>28.90, 'image'=>'assets/images/products/Lily/lily (11).jpg',          'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>9],
    30 => ['id'=>30, 'name'=>'Lily Bloom Bouquet',       'category'=>'Lily',       'price'=>28.90, 'image'=>'assets/images/products/Lily/lily (12).jpg',          'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>13],
    31 => ['id'=>31, 'name'=>'Tulip Flower Bouquet',     'category'=>'Tulip',      'price'=>28.90, 'image'=>'assets/images/products/Tulip/Tulip (6).jpg',         'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>7],
    32 => ['id'=>32, 'name'=>'Rose Flower Bouquet',      'category'=>'Rose',       'price'=>28.90, 'image'=>'assets/images/products/Rose/Rose1 (14).jpg',         'description'=>'Warm autumn colors with chrysanthemums, dahlias, and autumn leaves',                      'badge'=>'Autumn',     'stock'=>11],
];

// ─── FIX: Safely get product_id from GET, validate it, fallback to 1 ───────
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;
if (!isset($products[$product_id])) {
    $product_id = 1; // fallback to first product if ID not found
}
$product = $products[$product_id];

// ─── Related products (same category, exclude current) ────────────────────
$related_products = array_filter($products, function($p) use ($product, $product_id) {
    return $p['category'] === $product['category'] && $p['id'] !== $product_id;
});
$related_products = array_slice($related_products, 0, 4);

// ─── Reviews data ─────────────────────────────────────────────────────────
$reviews = [
    ['id'=>1, 'user_name'=>'Sarah Johnson', 'rating'=>5, 'comment'=>'Absolutely beautiful flowers! They lasted over a week and the presentation was stunning.', 'created_at'=>'2025-02-10 14:30:00'],
    ['id'=>2, 'user_name'=>'Michael Chen',  'rating'=>5, 'comment'=>'Perfect for my anniversary. My wife loved them! Great quality and fast delivery.',           'created_at'=>'2025-02-08 09:15:00'],
    ['id'=>3, 'user_name'=>'Emma Williams', 'rating'=>4, 'comment'=>'Very fresh flowers and beautiful arrangement. Only 4 stars because delivery was slightly delayed.', 'created_at'=>'2025-02-05 16:45:00'],
];

$total_rating = array_sum(array_column($reviews, 'rating'));
$average_rating = count($reviews) > 0 ? round($total_rating / count($reviews), 1) : 0;

// ─── Handle Add-to-Cart POST ──────────────────────────────────────────────
$cart_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
    $pid = (int)$_POST['product_id'];
    if (isset($_SESSION['cart'][$pid])) {
        $_SESSION['cart'][$pid]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$pid] = ['qty' => $qty, 'product' => $products[$pid] ?? []];
    }
    $cart_message = 'Added to cart successfully!';
}

// ─── Handle Review POST ────────────────────────────────────────────────────
$review_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $name   = htmlspecialchars(trim($_POST['reviewer_name'] ?? ''));
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = htmlspecialchars(trim($_POST['comment'] ?? ''));
    if ($name && $rating >= 1 && $rating <= 5 && $comment) {
        $review_message = 'Thank you for your review!';
        // In production: INSERT INTO reviews table
    } else {
        $review_message = 'Please fill in all fields correctly.';
    }
}

// ─── Star helper ──────────────────────────────────────────────────────────
function renderStars(float $rating, string $size = 'md'): string {
    $html = '<span class="stars stars--' . $size . '">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating)            $html .= '<span class="star star--full">★</span>';
        elseif ($i - 0.5 <= $rating)  $html .= '<span class="star star--half">★</span>';
        else                           $html .= '<span class="star star--empty">★</span>';
    }
    $html .= '</span>';
    return $html;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> — Bloom & Petal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* ── Reset & Variables ──────────────────────────────────────────── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --cream:     #faf7f2;
            --parchment: #f2ebe0;
            --blush:     #e8c4b8;
            --rose:      #c0614e;
            --rose-dk:   #9b4a3a;
            --sage:      #7a8c6e;
            --bark:      #3d2e22;
            --text:      #2a1f18;
            --muted:     #8a7a72;
            --border:    rgba(61,46,34,.12);
            --shadow:    0 4px 24px rgba(61,46,34,.10);
            --radius:    4px;
            --font-disp: 'Cormorant Garamond', Georgia, serif;
            --font-body: 'DM Sans', system-ui, sans-serif;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: var(--font-body);
            background: var(--cream);
            color: var(--text);
            font-size: 15px;
            line-height: 1.6;
        }

    

        

        /* ── Flash Messages ─────────────────────────────────────────────── */
        .flash {
            max-width: 1240px; margin: 16px auto 0;
            padding: 12px 20px;
            border-radius: var(--radius);
            font-size: .875rem;
            font-weight: 500;
        }
        .flash--success { background: #edf7ee; color: #2d6a31; border: 1px solid #b7ddb9; }
        .flash--error   { background: #fdf0ee; color: #9b3428; border: 1px solid #e8b4b0; }

        /* ── Product Detail ─────────────────────────────────────────────── */
        .product-detail {
            max-width: 1240px; margin: 48px auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            padding: 0 24px;
            align-items: start;
        }

        /* Gallery */
        .gallery { position: sticky; top: 80px; }
        .gallery__main {
            aspect-ratio: 1;
            border-radius: 2px;
            overflow: hidden;
            background: var(--parchment);
            position: relative;
        }
        .gallery__main img {
            width: 100%; height: 100%;
            object-fit: cover;
            transition: transform .6s ease;
        }
        .gallery__main:hover img { transform: scale(1.04); }
        .gallery__badge {
            position: absolute; top: 20px; left: 20px;
            background: var(--rose); color: #fff;
            font-size: .7rem; font-weight: 500;
            letter-spacing: .1em; text-transform: uppercase;
            padding: 5px 12px; border-radius: 2px;
        }

        /* Info */
        .product-info {}
        .product-info__category {
            font-size: .75rem; letter-spacing: .12em; text-transform: uppercase;
            color: var(--sage); font-weight: 500; margin-bottom: 8px;
        }
        .product-info__title {
            font-size: clamp(1.8rem, 3vw, 2.6rem);
            margin-bottom: 16px;
            color: var(--bark);
        }

        /* Stars */
        .stars { display: inline-flex; gap: 2px; }
        .star--full  { color: #c08b30; }
        .star--half  { color: #c08b30; opacity: .6; }
        .star--empty { color: var(--border); }
        .stars--lg .star { font-size: 1.3rem; }
        .stars--md .star { font-size: 1.1rem; }
        .stars--sm .star { font-size: .9rem; }

        .rating-row {
            display: flex; align-items: center; gap: 10px;
            margin-bottom: 20px; flex-wrap: wrap;
        }
        .rating-row__score {
            font-size: .875rem; color: var(--muted);
        }
        .rating-row__score a {
            color: var(--rose); text-decoration: none;
            border-bottom: 1px solid var(--blush);
        }

        .product-info__price {
            font-family: var(--font-disp);
            font-size: 2.4rem; font-weight: 300;
            color: var(--bark);
            margin-bottom: 24px;
            letter-spacing: -.01em;
        }
        .product-info__price sup { font-size: .9em; vertical-align: super; }

        .product-info__desc {
            color: var(--muted);
            line-height: 1.75;
            margin-bottom: 28px;
            font-size: .9375rem;
        }

        /* Stock */
        .stock-badge {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: .8125rem; margin-bottom: 24px;
        }
        .stock-badge__dot {
            width: 8px; height: 8px; border-radius: 50%;
        }
        .stock-badge--in .stock-badge__dot  { background: var(--sage); }
        .stock-badge--low .stock-badge__dot { background: #c08b30; }
        .stock-badge--out .stock-badge__dot { background: var(--rose); }

        /* Add to Cart form */
        .atc-form { display: flex; flex-direction: column; gap: 16px; margin-bottom: 32px; }
        .atc-row { display: flex; gap: 12px; align-items: stretch; }

        .qty-field {
            display: flex; align-items: center; border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
            background: #fff;
        }
        .qty-field button {
            width: 40px; height: 52px;
            background: none; border: none;
            font-size: 1.2rem; color: var(--muted);
            cursor: pointer; transition: color .2s;
        }
        .qty-field button:hover { color: var(--rose); }
        .qty-field input {
            width: 52px; height: 52px;
            border: none; text-align: center;
            font-family: var(--font-body); font-size: .95rem;
            color: var(--text);
            -moz-appearance: textfield;
        }
        .qty-field input::-webkit-outer-spin-button,
        .qty-field input::-webkit-inner-spin-button { -webkit-appearance: none; }

        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            padding: 0 28px; height: 52px;
            border: none; border-radius: var(--radius);
            font-family: var(--font-body); font-size: .875rem;
            font-weight: 500; letter-spacing: .04em; text-transform: uppercase;
            cursor: pointer; transition: all .25s; text-decoration: none;
        }
        .btn--primary {
            background: var(--bark); color: #fff; flex: 1;
        }
        .btn--primary:hover { background: var(--rose); }
        .btn--outline {
            background: transparent; color: var(--bark);
            border: 1px solid var(--bark);
        }
        .btn--outline:hover { background: var(--bark); color: #fff; }

        /* Perks */
        .perks {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 12px; padding: 24px;
            background: var(--parchment); border-radius: var(--radius);
        }
        .perk { display: flex; align-items: flex-start; gap: 10px; }
        .perk__icon { font-size: 1.1rem; margin-top: 2px; }
        .perk__text strong { display: block; font-size: .8125rem; font-weight: 500; color: var(--bark); }
        .perk__text span { font-size: .75rem; color: var(--muted); }

        /* ── Reviews ────────────────────────────────────────────────────── */
        .reviews-section {
            max-width: 1240px; margin: 64px auto;
            padding: 0 24px;
        }
        .section-title {
            font-family: var(--font-disp);
            font-size: clamp(1.5rem, 2.5vw, 2rem);
            font-weight: 300; color: var(--bark);
            margin-bottom: 32px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }

        /* Rating Summary */
        .rating-summary { text-align: center; padding: 32px 24px; background: var(--parchment); border-radius: var(--radius); }
        .rating-summary__score {
            font-family: var(--font-disp);
            font-size: 4rem; font-weight: 300;
            color: var(--bark); line-height: 1;
            margin-bottom: 8px;
        }
        .rating-summary__count { font-size: .8125rem; color: var(--muted); margin-top: 8px; }

        /* Review Cards */
        .reviews-list { display: flex; flex-direction: column; gap: 24px; }
        .review-card {
            padding: 24px; background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            transition: box-shadow .25s;
        }
        .review-card:hover { box-shadow: var(--shadow); }
        .review-card__header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; }
        .review-card__author { font-weight: 500; font-size: .9rem; color: var(--bark); }
        .review-card__date { font-size: .75rem; color: var(--muted); }
        .review-card__body { font-size: .9rem; color: var(--muted); line-height: 1.7; }

        /* Review Form */
        .review-form {
            background: var(--parchment);
            padding: 32px; border-radius: var(--radius);
        }
        .review-form h3 { font-family: var(--font-disp); font-size: 1.4rem; font-weight: 300; margin-bottom: 24px; color: var(--bark); }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group--full { grid-column: 1 / -1; }
        .form-group label { font-size: .8125rem; font-weight: 500; color: var(--bark); letter-spacing: .04em; text-transform: uppercase; }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            font-family: var(--font-body);
            font-size: .9rem;
            background: #fff;
            color: var(--text);
            transition: border-color .2s;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--rose);
        }
        .form-group textarea { resize: vertical; min-height: 100px; }

        /* ── Related Products ───────────────────────────────────────────── */
        .related-section {
            max-width: 1240px; margin: 64px auto 80px;
            padding: 0 24px;
        }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }
        .product-card {
            background: #fff; border: 1px solid var(--border);
            border-radius: var(--radius); overflow: hidden;
            transition: box-shadow .3s, transform .3s;
        }
        .product-card:hover {
            box-shadow: var(--shadow);
            transform: translateY(-4px);
        }
        .product-card__img {
            aspect-ratio: 1; overflow: hidden;
            background: var(--parchment); position: relative;
        }
        .product-card__img img {
            width: 100%; height: 100%; object-fit: cover;
            transition: transform .5s;
        }
        .product-card:hover .product-card__img img { transform: scale(1.06); }
        .product-card__badge {
            position: absolute; top: 12px; right: 12px;
            background: var(--bark); color: #fff;
            font-size: .65rem; font-weight: 500; letter-spacing: .08em;
            text-transform: uppercase; padding: 3px 8px; border-radius: 2px;
        }
        .product-card__body { padding: 16px; }
        .product-card__name {
            font-family: var(--font-disp); font-size: 1.05rem;
            font-weight: 400; margin-bottom: 4px;
            color: var(--bark);
        }
        .product-card__price { font-size: .9rem; font-weight: 500; color: var(--rose); }
        .product-card__link {
            display: block; text-align: center;
            padding: 10px; margin: 0 16px 16px;
            border: 1px solid var(--border); border-radius: var(--radius);
            text-decoration: none; font-size: .8125rem;
            font-weight: 500; letter-spacing: .04em; text-transform: uppercase;
            color: var(--bark); transition: all .2s;
        }
        .product-card__link:hover { background: var(--bark); color: #fff; border-color: var(--bark); }

        /* ── Footer ─────────────────────────────────────────────────────── */
        footer {
            background: var(--bark); color: rgba(255,255,255,.6);
            text-align: center; padding: 32px;
            font-size: .8rem; letter-spacing: .04em;
        }
        footer a { color: var(--blush); text-decoration: none; }

        /* ── Responsive ─────────────────────────────────────────────────── */
        @media (max-width: 900px) {
            .product-detail { grid-template-columns: 1fr; gap: 32px; }
            .gallery { position: static; }
            .reviews-grid { grid-template-columns: 1fr; }
            .related-grid { grid-template-columns: 1fr 1fr; }
            .form-grid { grid-template-columns: 1fr; }
            .perks { grid-template-columns: 1fr; }
        }
        @media (max-width: 600px) {
            .related-grid { grid-template-columns: 1fr; }
            .atc-row { flex-direction: column; }
            .navbar__links { display: none; }
        }

        /* ── Animations ─────────────────────────────────────────────────── */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .gallery      { animation: fadeUp .6s ease both; }
        .product-info { animation: fadeUp .6s ease .1s both; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

<?php if ($cart_message): ?>
    <div class="flash flash--success container"><?= htmlspecialchars($cart_message) ?></div>
<?php endif; ?>

<?php if ($review_message): ?>
    <div class="flash <?= str_contains($review_message, 'Thank') ? 'flash--success' : 'flash--error' ?> container">
        <?= htmlspecialchars($review_message) ?>
    </div>
<?php endif; ?>

<!-- ─── Product Detail ────────────────────────────────────────────────── -->
<main>
<section class="product-detail" aria-labelledby="product-title">

    <!-- Gallery -->
    <div class="gallery">
        <div class="gallery__main">
            <img src="<?= htmlspecialchars($product['image']) ?>"
                 alt="<?= htmlspecialchars($product['name']) ?>"
                 onerror="this.src='assets/images/placeholder.jpg'">
            <span class="gallery__badge"><?= htmlspecialchars($product['badge']) ?></span>
        </div>
    </div>

    <!-- Info -->
    <div class="product-info">
        <p class="product-info__category"><?= htmlspecialchars($product['category']) ?></p>
        <h1 class="product-info__title" id="product-title"><?= htmlspecialchars($product['name']) ?></h1>

        <div class="rating-row">
            <?= renderStars($average_rating, 'md') ?>
            <span class="rating-row__score">
                <?= $average_rating ?> &mdash;
                <a href="#reviews"><?= count($reviews) ?> review<?= count($reviews) !== 1 ? 's' : '' ?></a>
            </span>
        </div>

        <p class="product-info__price">
            <sup>$</sup><?= number_format($product['price'], 2) ?>
        </p>

        <p class="product-info__desc"><?= htmlspecialchars($product['description']) ?></p>

        <?php
            $stock = $product['stock'] ?? 10;
            if ($stock > 10):
                $stockClass = 'in'; $stockText = "In Stock ($stock available)";
            elseif ($stock > 0):
                $stockClass = 'low'; $stockText = "Low Stock — only $stock left!";
            else:
                $stockClass = 'out'; $stockText = 'Out of Stock';
            endif;
        ?>
        <div class="stock-badge stock-badge--<?= $stockClass ?>">
            <span class="stock-badge__dot"></span>
            <span><?= $stockText ?></span>
        </div>

        <!-- Add to Cart -->
        <form method="POST" action="flower-detail.php?id=<?= $product_id ?>" class="atc-form">
            <input type="hidden" name="product_id" value="<?= $product_id ?>">
            <div class="atc-row">
                <div class="qty-field" aria-label="Quantity">
                    <button type="button" onclick="changeQty(-1)" aria-label="Decrease">−</button>
                    <input type="number" id="quantity" name="quantity"
                           value="1" min="1" max="<?= $stock ?>"
                           <?= $stock === 0 ? 'disabled' : '' ?>>
                    <button type="button" onclick="changeQty(1)" aria-label="Increase">+</button>
                </div>
                <button type="submit" name="add_to_cart"
                        class="btn btn--primary"
                        <?= $stock === 0 ? 'disabled' : '' ?>>
                    🛒 <?= $stock === 0 ? 'Out of Stock' : 'Add to Cart' ?>
                </button>
            </div>
            <button type="button" class="btn btn--outline">♡ Save to Wishlist</button>
        </form>

        <!-- Perks -->
        <div class="perks">
            <div class="perk">
                <span class="perk__icon">🚚</span>
                <div class="perk__text">
                    <strong>Free Same-Day Delivery</strong>
                    <span>Order before 2 PM</span>
                </div>
            </div>
            <div class="perk">
                <span class="perk__icon">🌸</span>
                <div class="perk__text">
                    <strong>Fresh Guarantee</strong>
                    <span>100% fresh or money back</span>
                </div>
            </div>
            <div class="perk">
                <span class="perk__icon">🎁</span>
                <div class="perk__text">
                    <strong>Free Gift Wrap</strong>
                    <span>Beautiful presentation included</span>
                </div>
            </div>
            <div class="perk">
                <span class="perk__icon">✋</span>
                <div class="perk__text">
                    <strong>Handpicked</strong>
                    <span>Selected by our florists</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ─── Reviews ───────────────────────────────────────────────────────── -->
<section class="reviews-section" id="reviews" aria-labelledby="reviews-title">
    <h2 class="section-title" id="reviews-title">Customer Reviews</h2>

    <div class="reviews-grid">
        <!-- Summary -->
        <div class="rating-summary">
            <div class="rating-summary__score"><?= $average_rating ?></div>
            <?= renderStars($average_rating, 'lg') ?>
            <p class="rating-summary__count">Based on <?= count($reviews) ?> review<?= count($reviews) !== 1 ? 's' : '' ?></p>
        </div>

        <!-- Reviews List -->
        <div class="reviews-list">
            <?php if (empty($reviews)): ?>
                <p style="color:var(--muted)">No reviews yet. Be the first to leave one!</p>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                <article class="review-card">
                    <div class="review-card__header">
                        <div>
                            <?= renderStars($review['rating'], 'sm') ?>
                            <p class="review-card__author"><?= htmlspecialchars($review['user_name']) ?></p>
                        </div>
                        <?php if (strtotime($review['created_at'])): ?>
                        <span class="review-card__date">
                            <?= date('M j, Y', strtotime($review['created_at'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <p class="review-card__body"><?= htmlspecialchars($review['comment']) ?></p>
                </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Review Form -->
    <div class="review-form">
        <h3>Write a Review</h3>
        <form method="POST" action="flower-detail.php?id=<?= $product_id ?>">
            <div class="form-grid">
                <div class="form-group">
                    <label for="reviewer_name">Your Name</label>
                    <input type="text" id="reviewer_name" name="reviewer_name"
                           placeholder="Jane Smith" required>
                </div>
                <div class="form-group">
                    <label for="rating">Rating</label>
                    <select id="rating" name="rating" required>
                        <option value="">Select rating</option>
                        <option value="5">★★★★★ — Excellent</option>
                        <option value="4">★★★★☆ — Very Good</option>
                        <option value="3">★★★☆☆ — Good</option>
                        <option value="2">★★☆☆☆ — Fair</option>
                        <option value="1">★☆☆☆☆ — Poor</option>
                    </select>
                </div>
                <div class="form-group form-group--full">
                    <label for="comment">Your Review</label>
                    <textarea id="comment" name="comment"
                              placeholder="Share your experience with this product…" required></textarea>
                </div>
                <div class="form-group form-group--full">
                    <button type="submit" name="submit_review" class="btn btn--primary" style="width:auto;align-self:start">
                        Submit Review
                    </button>
                </div>
            </div>
        </form>
    </div>
</section>

<!-- ─── Related Products ──────────────────────────────────────────────── -->
<?php if (!empty($related_products)): ?>
<section class="related-section" aria-labelledby="related-title">
    <h2 class="section-title" id="related-title">You Might Also Like</h2>
    <div class="related-grid">
        <?php foreach ($related_products as $rp): ?>
        <article class="product-card">
            <div class="product-card__img">
                <img src="<?= htmlspecialchars($rp['image']) ?>"
                     alt="<?= htmlspecialchars($rp['name']) ?>"
                     loading="lazy"
                     onerror="this.src='assets/images/placeholder.jpg'">
                <span class="product-card__badge"><?= htmlspecialchars($rp['badge']) ?></span>
            </div>
            <div class="product-card__body">
                <h3 class="product-card__name"><?= htmlspecialchars($rp['name']) ?></h3>
                <p class="product-card__price">$<?= number_format($rp['price'], 2) ?></p>
            </div>
            <a href="flower-detail.php?id=<?= $rp['id'] ?>" class="product-card__link">View Details</a>
        </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
</main>

<!-- ─── Footer ────────────────────────────────────────────────────────── -->
<footer>
    <p>&copy; <?= date('Y') ?> Bloom &amp; Petal. All rights reserved. &nbsp;|&nbsp;
       <a href="privacy.php">Privacy</a> &nbsp;|&nbsp;
       <a href="terms.php">Terms</a>
    </p>
</footer>

<script>
function changeQty(delta) {
    const input = document.getElementById('quantity');
    const max   = parseInt(input.max)  || 99;
    const min   = parseInt(input.min)  || 1;
    const next  = parseInt(input.value) + delta;
    input.value = Math.min(Math.max(next, min), max);
}
</script>
</body>
</html>