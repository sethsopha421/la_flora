<?php
function loginUser($email, $password) {
    global $conn;
    
    if (empty($email) || empty($password)) {
        return "Please enter both email and password.";
    }
    
    // Prepare statement
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        return "Database error. Please try again.";
    }
    
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['name'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            
            mysqli_stmt_close($stmt);
            return true;
        }
    }
    
    mysqli_stmt_close($stmt);
    return "Invalid email or password.";
}

/**
 * Register new user
 */
function registerUser($name, $email, $password) {
    global $conn;
    
    // Check if email exists
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        mysqli_stmt_close($check_stmt);
        return "Email already exists.";
    }
    mysqli_stmt_close($check_stmt);
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (name, email, password, created_at) VALUES (?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        return "Database error. Please try again.";
    }
    
    mysqli_stmt_bind_param($stmt, "sss", $name, $email, $hashed_password);
    
    if (mysqli_stmt_execute($stmt)) {
        $user_id = mysqli_insert_id($conn);
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $name;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = 'customer';
        
        mysqli_stmt_close($stmt);
        return true;
    }
    
    mysqli_stmt_close($stmt);
    return "Registration failed. Please try again.";
}
?>