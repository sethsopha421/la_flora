<?php
// includes/database.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'laflora_db');

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8");

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to sanitize input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

// Function to get single value from database
function getSingleValue($sql) {
    global $conn;
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_array();
        return $row[0] ?? 0;
    }
    return 0;
}

// Function to get all rows from database
function fetchAll($sql) {
    global $conn;
    $result = $conn->query($sql);
    $rows = [];
    if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Function to close database connection
function closeDatabase() {
    global $conn;
    if (isset($conn) && $conn instanceof mysqli) {
        $conn = null;
    }
}

// Register shutdown function to close connection
register_shutdown_function('closeDatabase');
?>