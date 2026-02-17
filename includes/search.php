<?php
// ajax/search.php
session_start();
require_once '../includes/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$response = [
    'success' => false,
    'products' => [],
    'categories' => [],
    'total_products' => 0,
    'total_categories' => 0,
    'suggestions' => [],
    'message' => ''
];

// Database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    $response['message'] = 'Database connection failed';
    echo json_encode($response);
    exit();
}

// Sanitize input function
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Check if search query exists
if (isset($_GET['q']) && !empty(trim($_GET['q']))) {
    $search_term = sanitize($_GET['q']);
    $search_pattern = "%" . mysqli_real_escape_string($conn, $search_term) . "%";
    
    // Also accept POST requests for search
} elseif (isset($_POST['q']) && !empty(trim($_POST['q']))) {
    $search_term = sanitize($_POST['q']);
    $search_pattern = "%" . mysqli_real_escape_string($conn, $search_term) . "%";
} else {
    $response['message'] = 'No search term provided';
    echo json_encode($response);
    mysqli_close($conn);
    exit();
}

try {
    // SEARCH PRODUCTS
    $product_query = "SELECT p.*, 
                             c.name as category_name,
                             c.slug as category_slug
                      FROM products p 
                      LEFT JOIN categories c ON p.category_id = c.id 
                      WHERE (p.name LIKE ? 
                             OR p.description LIKE ? 
                             OR p.tags LIKE ?
                             OR c.name LIKE ?)
                      AND p.status = 'active'
                      AND p.stock > 0
                      ORDER BY 
                          CASE 
                              WHEN p.name LIKE ? THEN 1  -- Exact match in name first
                              WHEN p.tags LIKE ? THEN 2  -- Then tags
                              WHEN c.name LIKE ? THEN 3  -- Then category
                              ELSE 4
                          END,
                          p.created_at DESC
                      LIMIT 15";
    
    $product_stmt = mysqli_prepare($conn, $product_query);
    if (!$product_stmt) {
        throw new Exception("Product query preparation failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($product_stmt, "sssssss", 
        $search_pattern,  // p.name LIKE
        $search_pattern,  // p.description LIKE
        $search_pattern,  // p.tags LIKE
        $search_pattern,  // c.name LIKE
        $search_pattern,  // p.name LIKE (for ordering)
        $search_pattern,  // p.tags LIKE (for ordering)
        $search_pattern   // c.name LIKE (for ordering)
    );
    
    mysqli_stmt_execute($product_stmt);
    $product_result = mysqli_stmt_get_result($product_stmt);
    
    $products = [];
    while ($row = mysqli_fetch_assoc($product_result)) {
        // Format price
        $row['price_formatted'] = '$' . number_format($row['price'], 2);
        $row['old_price_formatted'] = $row['old_price'] ? '$' . number_format($row['old_price'], 2) : null;
        
        // Calculate discount percentage if applicable
        if ($row['old_price'] && $row['old_price'] > $row['price']) {
            $row['discount_percent'] = round((($row['old_price'] - $row['price']) / $row['old_price']) * 100);
        } else {
            $row['discount_percent'] = 0;
        }
        
        // Format image URL
        $row['image_url'] = !empty($row['image']) ? 'images/products/' . $row['image'] : 'images/products/default.jpg';
        $row['thumbnail_url'] = !empty($row['image']) ? 'images/products/thumb_' . $row['image'] : 'images/products/thumb_default.jpg';
        
        // Add to cart URL
        $row['add_to_cart_url'] = 'add_to_cart.php?product_id=' . $row['id'];
        
        // Product detail URL
        $row['detail_url'] = 'product.php?id=' . $row['id'];
        
        // Clean data for JSON
        $row = array_map(function($value) {
            return is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
        }, $row);
        
        $products[] = $row;
    }
    
    // GET TOTAL PRODUCT COUNT
    $count_query = "SELECT COUNT(*) as total 
                   FROM products p 
                   LEFT JOIN categories c ON p.category_id = c.id 
                   WHERE (p.name LIKE ? 
                          OR p.description LIKE ? 
                          OR p.tags LIKE ?
                          OR c.name LIKE ?)
                   AND p.status = 'active'
                   AND p.stock > 0";
    
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, "ssss", 
        $search_pattern, $search_pattern, $search_pattern, $search_pattern);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $total_row = mysqli_fetch_assoc($count_result);
    $total_products = $total_row['total'];
    
    // SEARCH CATEGORIES
    $category_query = "SELECT c.*, 
                              COUNT(p.id) as product_count
                       FROM categories c
                       LEFT JOIN products p ON c.id = p.category_id 
                       WHERE c.name LIKE ? 
                       AND c.status = 'active'
                       GROUP BY c.id
                       ORDER BY 
                           CASE 
                               WHEN c.name LIKE ? THEN 1
                               ELSE 2
                           END,
                           c.name ASC
                       LIMIT 8";
    
    $category_stmt = mysqli_prepare($conn, $category_query);
    mysqli_stmt_bind_param($category_stmt, "ss", $search_pattern, $search_pattern);
    mysqli_stmt_execute($category_stmt);
    $category_result = mysqli_stmt_get_result($category_stmt);
    
    $categories = [];
    while ($row = mysqli_fetch_assoc($category_result)) {
        $row['url'] = 'shop.php?category=' . urlencode($row['slug']);
        $row = array_map(function($value) {
            return is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
        }, $row);
        $categories[] = $row;
    }
    
    // GET SUGGESTIONS (for autocomplete)
    $suggestion_query = "SELECT DISTINCT 
                                p.name as product_name,
                                c.name as category_name,
                                'product' as type
                         FROM products p
                         LEFT JOIN categories c ON p.category_id = c.id
                         WHERE (p.name LIKE ? OR c.name LIKE ?)
                         AND p.status = 'active'
                         UNION
                         SELECT DISTINCT 
                                name as product_name,
                                '' as category_name,
                                'category' as type
                         FROM categories
                         WHERE name LIKE ?
                         AND status = 'active'
                         ORDER BY 
                             CASE 
                                 WHEN product_name LIKE ? THEN 1
                                 ELSE 2
                             END
                         LIMIT 8";
    
    $suggestion_stmt = mysqli_prepare($conn, $suggestion_query);
    mysqli_stmt_bind_param($suggestion_stmt, "ssss", 
        $search_pattern, $search_pattern, $search_pattern, $search_pattern);
    mysqli_stmt_execute($suggestion_stmt);
    $suggestion_result = mysqli_stmt_get_result($suggestion_stmt);
    
    $suggestions = [];
    while ($row = mysqli_fetch_assoc($suggestion_result)) {
        $row['display'] = $row['product_name'];
        if ($row['type'] === 'product' && !empty($row['category_name'])) {
            $row['display'] .= ' (' . $row['category_name'] . ')';
        }
        $row = array_map(function($value) {
            return is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
        }, $row);
        $suggestions[] = $row;
    }
    
    // POPULAR SEARCHES (related terms)
    $popular_query = "SELECT 
                             p.tags,
                             COUNT(*) as relevance
                      FROM products p
                      WHERE (p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)
                      AND p.status = 'active'
                      GROUP BY p.tags
                      HAVING relevance > 0
                      ORDER BY relevance DESC
                      LIMIT 5";
    
    $popular_stmt = mysqli_prepare($conn, $popular_query);
    mysqli_stmt_bind_param($popular_stmt, "sss", 
        $search_pattern, $search_pattern, $search_pattern);
    mysqli_stmt_execute($popular_stmt);
    $popular_result = mysqli_stmt_get_result($popular_stmt);
    
    $popular_searches = [];
    while ($row = mysqli_fetch_assoc($popular_result)) {
        if (!empty($row['tags'])) {
            $tags = explode(',', $row['tags']);
            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (stripos($tag, $search_term) !== false && strlen($tag) > 2) {
                    $popular_searches[] = $tag;
                }
            }
        }
    }
    $popular_searches = array_unique(array_slice($popular_searches, 0, 5));
    
    // Update response
    $response['success'] = true;
    $response['products'] = $products;
    $response['categories'] = $categories;
    $response['suggestions'] = $suggestions;
    $response['popular_searches'] = $popular_searches;
    $response['total_products'] = $total_products;
    $response['total_categories'] = count($categories);
    $response['search_term'] = $search_term;
    $response['message'] = 'Search completed successfully';
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    $response['error'] = $e->getMessage();
} finally {
    if (isset($product_stmt)) mysqli_stmt_close($product_stmt);
    if (isset($count_stmt)) mysqli_stmt_close($count_stmt);
    if (isset($category_stmt)) mysqli_stmt_close($category_stmt);
    if (isset($suggestion_stmt)) mysqli_stmt_close($suggestion_stmt);
    if (isset($popular_stmt)) mysqli_stmt_close($popular_stmt);
    mysqli_close($conn);
}

// Log search in session for analytics
if ($response['success'] && !empty($search_term)) {
    if (!isset($_SESSION['search_history'])) {
        $_SESSION['search_history'] = [];
    }
    
    // Add to search history (keep last 10 searches)
    $_SESSION['search_history'][] = [
        'term' => $search_term,
        'timestamp' => time(),
        'results' => $response['total_products']
    ];
    
    // Keep only last 10 searches
    if (count($_SESSION['search_history']) > 10) {
        $_SESSION['search_history'] = array_slice($_SESSION['search_history'], -10);
    }
}

echo json_encode($response);
exit();
?>