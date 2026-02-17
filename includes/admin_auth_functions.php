<?php
// includes/admin_auth_functions.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once 'db_connect.php';

function isAdminLoggedIn() {
    // Check if user is logged in AND is admin
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['role']) && 
           $_SESSION['role'] === 'admin';
}

function redirectIfNotAdmin($redirect_url = '../index.php') {
    if (!isAdminLoggedIn()) {
        header('Location: ' . $redirect_url);
        exit();
    }
}

function getAdminStats($conn) {
    $stats = [
        'users' => 0,
        'products' => 0,
        'orders' => 0,
        'revenue' => 0
    ];

    // Total users (excluding admin)
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'user'";
    if ($result = mysqli_query($conn, $query)) {
        $row = mysqli_fetch_assoc($result);
        $stats['users'] = $row['total'] ?? 0;
    }

    // Total products
    $query = "SELECT COUNT(*) as total FROM products";
    if ($result = mysqli_query($conn, $query)) {
        $row = mysqli_fetch_assoc($result);
        $stats['products'] = $row['total'] ?? 0;
    }

    // Total orders
    $query = "SELECT COUNT(*) as total FROM orders";
    if ($result = mysqli_query($conn, $query)) {
        $row = mysqli_fetch_assoc($result);
        $stats['orders'] = $row['total'] ?? 0;
    }

    // Total revenue from delivered orders
    $query = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'delivered'";
    if ($result = mysqli_query($conn, $query)) {
        $row = mysqli_fetch_assoc($result);
        $stats['revenue'] = $row['total'] ?? 0;
    }

    return $stats;
}

function getRecentOrders($conn, $limit = 5) {
    $orders = [];
    $query = "SELECT o.id, u.name, o.total_amount, o.status, o.created_at 
              FROM orders o 
              LEFT JOIN users u ON o.user_id = u.id 
              ORDER BY o.created_at DESC 
              LIMIT ?";
    
    $stmt = mysqli_prepare($conn, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        while ($order = mysqli_fetch_assoc($result)) {
            $orders[] = $order;
        }
        mysqli_stmt_close($stmt);
    }
    
    return $orders;
}
?>