<?php
// user/forgot-password.php
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

// Create password_resets table if it doesn't exist
createPasswordResetsTable($conn);

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email exists in users table
        $sql = "SELECT id, name, email FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        
        if (!$stmt) {
            $error = "Database error. Please try again.";
        } else {
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if ($user = mysqli_fetch_assoc($result)) {
                // Check if user already has an active reset token
                $check_sql = "SELECT id FROM password_resets WHERE email = ? AND expires_at > NOW() AND used = 0";
                $check_stmt = mysqli_prepare($conn, $check_sql);
                mysqli_stmt_bind_param($check_stmt, "s", $email);
                mysqli_stmt_execute($check_stmt);
                mysqli_stmt_store_result($check_stmt);
                
                if (mysqli_stmt_num_rows($check_stmt) > 0) {
                    $error = "A password reset link has already been sent to this email. Please check your inbox.";
                    mysqli_stmt_close($check_stmt);
                } else {
                    mysqli_stmt_close($check_stmt);
                    
                    // Generate secure reset token
                    $token = bin2hex(random_bytes(32));
                    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store token in database
                    $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
                    $stmt = mysqli_prepare($conn, $sql);
                    
                    if (!$stmt) {
                        $error = "Database error. Please try again.";
                    } else {
                        mysqli_stmt_bind_param($stmt, "sss", $email, $token, $expires);
                        
                        if (mysqli_stmt_execute($stmt)) {
                            // Create reset link
                            $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
                            $reset_link = $base_url . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
                            
                            // Try to send email, if fails show the link
                            if (sendPasswordResetEmail($user['email'], $user['name'], $reset_link)) {
                                $success = "Password reset instructions have been sent to your email. Please check your inbox (and spam folder).";
                            } else {
                                // Demo mode - show the link
                                $success = "Password reset link generated!<br><br>";
                                $success .= "<strong>Reset Link:</strong><br>";
                                $success .= "<div class='reset-link-box'><a href='$reset_link'>$reset_link</a></div><br>";
                                $success .= "<small class='text-muted'>Click the link above to reset your password. This link expires in 1 hour.</small>";
                            }
                        } else {
                            $error = "Error generating reset token. Please try again.";
                        }
                    }
                }
            } else {
                $error = "No account found with that email address.";
            }
            
            if (isset($stmt) && $stmt) {
                mysqli_stmt_close($stmt);
            }
        }
    }
}

/**
 * Create password_resets table if it doesn't exist
 */
function createPasswordResetsTable($conn) {
    $sql = "CREATE TABLE IF NOT EXISTS password_resets (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        token VARCHAR(255) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        used TINYINT(1) DEFAULT 0,
        INDEX idx_email (email),
        INDEX idx_token (token),
        INDEX idx_expires (expires_at)
    )";
    
    if (!mysqli_query($conn, $sql)) {
        // Log error but don't show to user
        error_log("Failed to create password_resets table: " . mysqli_error($conn));
    }
}

/**
 * Send password reset email
 */
function sendPasswordResetEmail($to_email, $user_name, $reset_link) {
    // For production, uncomment and configure email settings
    /*
    $to = $to_email;
    $subject = "Password Reset Request - LaFlora";
    
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #4a6fa5; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
            .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
            .btn { display: inline-block; background: #4a6fa5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 15px 0; }
            .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>LaFlora</h2>
                <p>Password Reset Request</p>
            </div>
            <div class='content'>
                <p>Hello " . htmlspecialchars($user_name) . ",</p>
                <p>You requested to reset your password. Click the button below to set a new password:</p>
                
                <div style='text-align: center;'>
                    <a href='" . $reset_link . "' class='btn'>Reset Password</a>
                </div>
                
                <p>Or copy and paste this link in your browser:<br>
                <code style='background: #f0f0f0; padding: 10px; display: block; margin: 10px 0;'>" . $reset_link . "</code></p>
                
                <p><strong>Note:</strong> This link will expire in 1 hour.</p>
                
                <p>If you didn't request a password reset, please ignore this email.</p>
                
                <div class='footer'>
                    <p>Best regards,<br>The LaFlora Team</p>
                </div>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: LaFlora <noreply@laflora.com>" . "\r\n";
    $headers .= "Reply-To: support@laflora.com" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    return mail($to, $subject, $message, $headers);
    */
    
    // For demo purposes, return false to show link on page
    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - LaFlora</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #166088;
            --accent-color: #4fc3a1;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        
        .forgot-container {
            max-width: 500px;
            width: 100%;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .forgot-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: none;
        }
        
        .forgot-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        
        .forgot-header h2 {
            font-weight: 700;
            margin: 0;
            font-size: 28px;
        }
        
        .forgot-header p {
            opacity: 0.9;
            margin: 10px 0 0;
            font-size: 16px;
        }
        
        .forgot-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-color);
        }
        
        .forgot-body {
            padding: 40px;
        }
        
        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            padding: 14px 16px;
            font-size: 16px;
            transition: all 0.3s ease;
            height: 52px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.15);
        }
        
        .btn-reset {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 16px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: all 0.3s ease;
            height: 52px;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(74, 111, 165, 0.3);
        }
        
        .btn-reset:active {
            transform: translateY(0);
        }
        
        .login-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #eef2f7;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 16px;
            margin-bottom: 24px;
        }
        
        .alert-success {
            background-color: #e8f7f1;
            color: #0a7b51;
            border-left: 4px solid #4fc3a1;
        }
        
        .alert-danger {
            background-color: #fee;
            color: #d32f2f;
            border-left: 4px solid #f44336;
        }
        
        .info-box {
            background-color: #f0f7ff;
            border-left: 4px solid var(--primary-color);
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 8px;
        }
        
        .demo-warning {
            background-color: #fff8e1;
            border-left: 4px solid #ffc107;
            padding: 12px 16px;
            margin-top: 15px;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .reset-link-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin: 12px 0;
            word-break: break-all;
            font-size: 14px;
            font-family: monospace;
        }
        
        .reset-link-box a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .reset-link-box a:hover {
            text-decoration: underline;
        }
        
        .back-to-login {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .back-to-login:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
        .logo-icon {
            font-size: 32px;
            margin-bottom: 15px;
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            width: 70px;
            height: 70px;
            line-height: 70px;
            border-radius: 50%;
            text-align: center;
        }
        
        @media (max-width: 576px) {
            .forgot-body {
                padding: 30px 20px;
            }
            
            .forgot-header {
                padding: 30px 20px;
            }
            
            .forgot-header h2 {
                font-size: 24px;
            }
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border: 2px solid #e1e5e9;
            border-right: none;
            border-radius: 10px 0 0 10px;
            padding: 0 16px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .input-group .form-control:focus {
            border-left: none;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-card">
            <div class="forgot-header">
                <div class="logo-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h2>LaFlora</h2>
                <p>Reset your password</p>
            </div>
            
            <div class="forgot-body">
                <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php 
                    echo $success; 
                    // If we're showing the link (demo mode), add a warning
                    if (strpos($success, 'Reset Link:') !== false): 
                    ?>
                        <div class="demo-warning mt-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Demo Mode:</strong> In production, this link would be sent via email. Copy the link above to test.
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="text-center mt-4">
                    <a href="login.php" class="btn btn-reset">
                        <i class="fas fa-arrow-left me-2"></i> Back to Login
                    </a>
                </div>
                <?php else: ?>
                <div class="info-box">
                    <i class="fas fa-info-circle me-2 text-primary"></i>
                    Enter your email address and we'll send you instructions to reset your password.
                </div>
                
                <form method="POST" action="" id="forgotForm" novalidate>
                    <div class="mb-4">
                        <label for="email" class="form-label fw-bold mb-2">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-envelope text-muted"></i>
                            </span>
                            <input type="email" 
                                   class="form-control" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                   required 
                                   placeholder="Enter your email address"
                                   autocomplete="email"
                                   autofocus>
                        </div>
                        <div class="form-text mt-2">
                            <i class="fas fa-lightbulb me-1"></i> Enter the email address associated with your account.
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <button type="submit" class="btn btn-reset" id="submitBtn">
                            <span id="submitText">
                                <i class="fas fa-paper-plane me-2"></i> Send Reset Instructions
                            </span>
                            <span id="loadingSpinner" class="d-none">
                                <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                Sending...
                            </span>
                        </button>
                    </div>
                </form>
                
                <div class="login-link">
                    <p class="mb-3 text-muted">Remember your password?</p>
                    <a href="login.php" class="back-to-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Back to Login
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-muted small">
                &copy; <?php echo date('Y'); ?> LaFlora. All rights reserved.
            </p>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation and submission handling
        const forgotForm = document.getElementById('forgotForm');
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const loadingSpinner = document.getElementById('loadingSpinner');
        const emailInput = document.getElementById('email');
        
        if (forgotForm) {
            forgotForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const email = emailInput.value.trim();
                
                // Clear previous errors
                clearErrors();
                
                // Validate email
                let isValid = true;
                
                if (!email) {
                    showError(emailInput, 'Please enter your email address.');
                    isValid = false;
                } else if (!isValidEmail(email)) {
                    showError(emailInput, 'Please enter a valid email address.');
                    isValid = false;
                }
                
                if (isValid) {
                    // Show loading state
                    submitBtn.disabled = true;
                    submitText.classList.add('d-none');
                    loadingSpinner.classList.remove('d-none');
                    
                    // Submit the form
                    this.submit();
                }
            });
        }
        
        // Email validation function
        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }
        
        // Show error message
        function showError(input, message) {
            const formGroup = input.closest('.mb-4');
            
            // Remove existing error
            const existingError = formGroup.querySelector('.invalid-feedback');
            if (existingError) {
                existingError.remove();
            }
            
            // Add error class to input
            input.classList.add('is-invalid');
            
            // Create error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback d-block mt-2';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i> ${message}`;
            
            formGroup.appendChild(errorDiv);
            
            // Scroll to error
            input.focus();
        }
        
        // Clear all errors
        function clearErrors() {
            // Remove invalid classes
            document.querySelectorAll('.is-invalid').forEach(input => {
                input.classList.remove('is-invalid');
            });
            
            // Remove error messages
            document.querySelectorAll('.invalid-feedback').forEach(error => {
                error.remove();
            });
        }
        
        // Clear error when user starts typing
        if (emailInput) {
            emailInput.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    this.classList.remove('is-invalid');
                    const errorMsg = this.closest('.mb-4').querySelector('.invalid-feedback');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
        }
        
        // Auto-focus email field on page load
        window.addEventListener('DOMContentLoaded', function() {
            if (emailInput && !emailInput.disabled) {
                emailInput.focus();
            }
        });
        
        // Add input validation on blur
        if (emailInput) {
            emailInput.addEventListener('blur', function() {
                const email = this.value.trim();
                if (email && !isValidEmail(email)) {
                    showError(this, 'Please enter a valid email address.');
                }
            });
        }
    </script>
</body>
</html>
<?php
// Close database connection
if (isset($conn)) {
    mysqli_close($conn);
}
?>