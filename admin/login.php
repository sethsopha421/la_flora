<?php
// admin/login.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/database.php';

// Check if database connection exists
if (!isset($conn)) {
    die("Database connection failed. Please check your database.php file.");
}

// ===== IMPORTANT: Redirect if ALREADY logged in =====
// If user is already logged in as admin, send them to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit();
}


$error = '';
$success = '';

// ===== HANDLE LOGIN FORM SUBMISSION =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Prepare SQL to find user
        $stmt = $conn->prepare("SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check if user has admin role
            if ($user['role'] !== 'admin') {
                $error = 'Access denied. Only administrators can login here.';
            } else {
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // ===== LOGIN SUCCESSFUL - Set session variables =====
                    session_regenerate_id(true); // Security: Regenerate session ID
                    
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id'];
                    $_SESSION['admin_name'] = $user['name'];
                    $_SESSION['admin_email'] = $user['email'];
                    $_SESSION['is_admin'] = true;
                    
                    // ===== REDIRECT TO DASHBOARD =====
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Invalid email or password';
                }
            }
        } else {
            $error = 'Invalid email or password';
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - LA FLORA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #2A5934;
            --primary-light: #4A7856;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-light) 100%);
            padding: 40px 30px 30px;
            text-align: center;
            color: white;
        }
        
        .login-header i {
            font-size: 3rem;
            margin-bottom: 15px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .login-body {
            padding: 40px;
        }
        
        .btn-login {
            background: var(--primary-color);
            color: white;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-login:hover {
            background: var(--primary-light);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(42, 89, 52, 0.25);
        }
        
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        
        .form-control {
            border-left: none;
        }
        
        .alert {
            border-radius: 10px;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-flower2"></i>
                <h2 class="mb-0">LA FLORA</h2>
                <p class="mb-0">Administrator Panel</p>
            </div>
            
            <div class="login-body">
                <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Error!</strong> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-envelope me-1"></i>Email Address
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope"></i>
                            </span>
                            <input type="email" 
                                   name="email" 
                                   class="form-control" 
                                   placeholder="admin@laflora.com" 
                                   required 
                                   autofocus
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-lock me-1"></i>Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="form-control" 
                                   placeholder="Enter your password" 
                                   required>
                            <button class="btn btn-outline-secondary" 
                                    type="button" 
                                    onclick="togglePassword()"
                                    id="toggleBtn">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Default password is "admin123"
                        </small>
                    </div>
                    
                    <div class="mb-4">
                        <button type="submit" class="btn btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>
                            Sign In to Dashboard
                        </button>
                    </div>
                    
                    <div class="text-center pt-3 border-top">
                        <a href="../index.php" class="text-decoration-none" style="color: var(--primary-color);">
                            <i class="bi bi-arrow-left me-1"></i>
                            Back to Main Site
                        </a>
                    </div>
                </form>
                
                <!-- Debug Info (Remove in production) -->
                <?php if (isset($_GET['debug'])): ?>
                <div class="alert alert-info mt-3">
                    <small>
                        <strong>Debug Mode:</strong><br>
                        Session ID: <?php echo session_id(); ?><br>
                        Admin Logged In: <?php echo isset($_SESSION['admin_logged_in']) ? 'Yes' : 'No'; ?><br>
                        Admin ID: <?php echo $_SESSION['admin_id'] ?? 'Not Set'; ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <small class="text-white">
                <i class="bi bi-shield-lock me-1"></i>
                Secure Admin Access | LA FLORA Management System
            </small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Toggle password visibility
    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const toggleIcon = document.getElementById('toggleIcon');
        
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('bi-eye');
            toggleIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('bi-eye-slash');
            toggleIcon.classList.add('bi-eye');
        }
    }
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    </script>
</body>
</html>