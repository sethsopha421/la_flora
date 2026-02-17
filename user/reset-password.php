<?php
// user/reset-password.php
session_start();

// Database configuration - update these according to your setup
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'laflora_db');

// Create database connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$error = '';
$success = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    if (empty($token)) {
        $error = "Invalid reset request. No token provided.";
    } elseif (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all password fields.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Validate token in database
        $sql = "SELECT email, expires_at FROM password_resets WHERE token = ? AND used = 0";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            $error = "Database error: " . mysqli_error($conn);
        } else {
            mysqli_stmt_bind_param($stmt, "s", $token);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($result)) {
                // Check if token has expired
                $expires = strtotime($row['expires_at']);
                $now = time();
                
                if ($expires < $now) {
                    $error = "Reset link has expired. Please request a new one.";
                } else {
                    // Token is valid, update password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Begin transaction
                    mysqli_begin_transaction($conn);
                    
                    try {
                        // Update password
                        $update_sql = "UPDATE users SET password = ? WHERE email = ?";
                        $update_stmt = mysqli_prepare($conn, $update_sql);
                        
                        if (!$update_stmt) {
                            throw new Exception("Failed to prepare update statement");
                        }
                        
                        mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $row['email']);
                        
                        if (!mysqli_stmt_execute($update_stmt)) {
                            throw new Exception("Failed to update password");
                        }
                        
                        // Mark token as used
                        $mark_sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
                        $mark_stmt = mysqli_prepare($conn, $mark_sql);
                        
                        if (!$mark_stmt) {
                            throw new Exception("Failed to prepare mark statement");
                        }
                        
                        mysqli_stmt_bind_param($mark_stmt, "s", $token);
                        
                        if (!mysqli_stmt_execute($mark_stmt)) {
                            throw new Exception("Failed to mark token as used");
                        }
                        
                        // Commit transaction
                        mysqli_commit($conn);
                        
                        $success = "Password updated successfully! You can now <a href='login.php'>login</a> with your new password.";
                        
                        // Clear token from URL to prevent resubmission
                        $token = '';
                        
                        mysqli_stmt_close($update_stmt);
                        mysqli_stmt_close($mark_stmt);
                        
                    } catch (Exception $e) {
                        // Rollback on error
                        mysqli_rollback($conn);
                        $error = "Error updating password: " . $e->getMessage();
                    }
                }
            } else {
                $error = "Invalid or already used reset token.";
            }
            
            mysqli_stmt_close($stmt);
        }
    }
} elseif (empty($token)) {
    $error = "No reset token provided. Please use the link from your email.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - LaFlora</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .reset-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 50px 40px;
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .reset-card h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 30px;
        }
        .form-label {
            color: #555;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .input-group {
            margin-bottom: 10px;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
            color: #667eea;
        }
        .form-control {
            border-left: none;
            padding: 12px 15px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 14px;
            font-weight: 600;
            font-size: 16px;
            transition: transform 0.2s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-outline-secondary {
            border-left: none;
        }
        .password-requirements {
            font-size: 0.875rem;
            color: #6c757d;
            margin-top: 5px;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        a:hover {
            color: #764ba2;
        }
    </style>
</head>
<body>
    <div class="reset-card">
        <h2 class="text-center">Reset Your Password</h2>
        
        <?php if($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            
            <div class="text-center mt-3">
                <a href="forgot-password.php" class="btn btn-primary">
                    <i class="fas fa-redo me-2"></i> Request New Reset Link
                </a>
            </div>
        <?php elseif($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php else: ?>
            <form method="POST" id="resetForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required 
                               placeholder="Enter new password" minlength="8">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-requirements">
                        Must be at least 8 characters long
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirm new password" minlength="8">
                        <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div id="passwordMatch" class="password-requirements"></div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        <i class="fas fa-key me-2"></i> Reset Password
                    </button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="login.php">
                <i class="fas fa-arrow-left me-1"></i> Back to Login
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        document.getElementById('toggleConfirmPassword')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Password validation
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        const submitBtn = document.getElementById('submitBtn');
        
        function validatePassword() {
            if (!password || !confirmPassword) return true;
            
            if (confirmPassword.value === '') {
                passwordMatch.innerHTML = '';
                return true;
            }
            
            if (password.value.length < 8) {
                passwordMatch.innerHTML = '<i class="fas fa-times text-danger me-1"></i> Password must be at least 8 characters';
                passwordMatch.style.color = '#dc3545';
                return false;
            }
            
            if (password.value !== confirmPassword.value) {
                passwordMatch.innerHTML = '<i class="fas fa-times text-danger me-1"></i> Passwords do not match';
                passwordMatch.style.color = '#dc3545';
                return false;
            }
            
            passwordMatch.innerHTML = '<i class="fas fa-check text-success me-1"></i> Passwords match';
            passwordMatch.style.color = '#198754';
            return true;
        }
        
        password?.addEventListener('input', validatePassword);
        confirmPassword?.addEventListener('input', validatePassword);
        
        // Form submission validation
        document.getElementById('resetForm')?.addEventListener('submit', function(e) {
            const pwd = document.getElementById('password').value;
            const confirmPwd = document.getElementById('confirm_password').value;
            
            if (pwd.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long.');
                return false;
            }
            
            if (pwd !== confirmPwd) {
                e.preventDefault();
                alert('Passwords do not match.');
                return false;
            }
            
            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Resetting...';
        });
    </script>
</body>
</html>
<?php
// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>