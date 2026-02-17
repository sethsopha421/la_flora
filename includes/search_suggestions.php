<?php
// includes/search_suggestions.php
session_start();
require_once 'database.php';

header('Content-Type: application/json');

// Get search query
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($query) < 1) {
    echo json_encode([]);
    exit();
}

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    echo json_encode([]);
    exit();
}

// Search products by name or category
$search_query = "%" . mysqli_real_escape_string($conn, $query) . "%";
$sql = "SELECT id, name, category, price, image FROM products 
        WHERE (name LIKE ? OR category LIKE ? OR description LIKE ?)
        AND stock > 0 
        ORDER BY 
            CASE 
                WHEN name LIKE ? THEN 1
                WHEN category LIKE ? THEN 2
                ELSE 3
            END,
            name ASC
        LIMIT 10";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "sssss", 
        $search_query, 
        $search_query, 
        $search_query,
        $search_query,
        $search_query
    );
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $suggestions = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $suggestions[] = [
            'id' => $row['id'],
            'name' => htmlspecialchars($row['name']),
            'category' => htmlspecialchars($row['category']),
            'price' => number_format($row['price'], 2),
            'image' => $row['image']
        ];
    }
    
    echo json_encode($suggestions);
    mysqli_stmt_close($stmt);
} else {
    echo json_encode([]);
}

mysqli_close($conn);
?>