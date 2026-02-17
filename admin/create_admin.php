<?php
// admin/create_admin.php - ONE-TIME USE ONLY
// ⚠️ DELETE THIS FILE AFTER USE! ⚠️

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/database.php';

// Security check - Change this secret key!
$SECRET_KEY = 'create123'; // CHANGE THIS to something unique!

// Check access
if (!isset($_GET['secret']) || $_GET['secret'] !== $SECRET_KEY) {
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Access Denied</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
            .box { background: white; padding: 40px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); text-align: center; }
            .error { color: #dc3545; font-size: 48px; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class="box">
            <div class="error">🔒</div>
            <h2>Access Denied</h2>
            <p>Invalid secret key</p>
            <small>Usage: create_admin.php?secret=YOUR_SECRET_KEY</small>
        </div>
    </body>
    </html>
    ');
}

// Check database connection
if (!isset($conn) || !$conn) {
    die("Database connection failed. Check your database.php file.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin User Manager - LA FLORA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #2A5934;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary) 0%, #4A7856 100%);
            min-height: 100vh;
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 800px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, #4A7856 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 30px;
        }
        
        .status-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .code-box {
            background: #f1f3f5;
            padding: 12px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            word-break: break-all;
            border: 1px solid #dee2e6;
        }
        
        .btn-custom {
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid var(--warning);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .success-box {
            background: #d1e7dd;
            border-left: 4px solid var(--success);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .danger-box {
            background: #f8d7da;
            border-left: 4px solid var(--danger);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .table-custom {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .badge-custom {
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0">
                    <i class="bi bi-shield-lock me-2"></i>
                    LA FLORA - Admin User Manager
                </h3>
                <small>One-time admin user creation & password reset tool</small>
            </div>
            
            <div class="card-body p-4">
                <?php
                // Check if admin already exists
                $check = $conn->query("SELECT * FROM users WHERE email = 'admin@laflora.com'");
                
                if ($check && $check->num_rows > 0) {
                    $admin = $check->fetch_assoc();
                    
                    echo '<div class="text-center">
                            <div class="status-icon">✅</div>
                            <h4>Admin User Already Exists</h4>
                          </div>';
                    
                    echo '<div class="info-box">
                            <h6><i class="bi bi-info-circle me-2"></i>Current Admin Details:</h6>
                            <table class="table table-sm mb-0">
                                <tr>
                                    <td width="120"><strong>ID:</strong></td>
                                    <td>' . htmlspecialchars($admin['id']) . '</td>
                                </tr>
                                <tr>
                                    <td><strong>Name:</strong></td>
                                    <td>' . htmlspecialchars($admin['name']) . '</td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td>' . htmlspecialchars($admin['email']) . '</td>
                                </tr>
                                <tr>
                                    <td><strong>Role:</strong></td>
                                    <td><span class="badge bg-danger">' . htmlspecialchars($admin['role']) . '</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Created:</strong></td>
                                    <td>' . (isset($admin['created_at']) ? date('M d, Y H:i', strtotime($admin['created_at'])) : 'N/A') . '</td>
                                </tr>
                            </table>
                          </div>';
                    
                    // Test password verification
                    echo '<div class="info-box">
                            <h6><i class="bi bi-key me-2"></i>Password Testing:</h6>';
                    
                    $test_passwords = ['admin123', 'password', '12345678', 'admin', 'test123'];
                    $password_works = false;
                    
                    foreach ($test_passwords as $test_pass) {
                        if (password_verify($test_pass, $admin['password'])) {
                            echo '<div class="alert alert-success mb-2">
                                    <i class="bi bi-check-circle me-2"></i>
                                    ✅ Password "<strong>' . htmlspecialchars($test_pass) . '</strong>" works!
                                  </div>';
                            $password_works = $test_pass;
                            break;
                        }
                    }
                    
                    if (!$password_works) {
                        echo '<div class="alert alert-warning mb-2">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                ⚠️ Common passwords don\'t work. Password may need to be reset.
                              </div>';
                    }
                    
                    echo '<small class="text-muted">Current password hash:</small>
                          <div class="code-box mt-2">' . htmlspecialchars($admin['password']) . '</div>
                          </div>';
                    
                    // Check if password is hashed
                    $is_hashed = (strlen($admin['password']) == 60 && substr($admin['password'], 0, 4) == '$2y$');
                    
                    if (!$is_hashed) {
                        echo '<div class="danger-box">
                                <h6><i class="bi bi-shield-exclamation me-2"></i>Security Warning!</h6>
                                <p class="mb-0">Password appears to be stored in plain text. This is a security risk!</p>
                              </div>';
                    }
                    
                    // Option to reset password
                    echo '<div class="warning-box">
                            <h6><i class="bi bi-arrow-repeat me-2"></i>Need to Reset Password?</h6>
                            <p>Click the button below to reset the admin password to "admin123"</p>
                            <form method="POST" action="" onsubmit="return confirm(\'Are you sure you want to reset the admin password?\');">
                                <button type="submit" name="reset_password" class="btn btn-warning btn-custom">
                                    <i class="bi bi-key me-2"></i>Reset Password to "admin123"
                                </button>
                            </form>
                          </div>';
                    
                    // Handle password reset
                    if (isset($_POST['reset_password'])) {
                        $new_password = 'admin123';
                        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        
                        $update = $conn->prepare("UPDATE users SET password = ?, role = 'admin' WHERE id = ?");
                        $update->bind_param("si", $new_hash, $admin['id']);
                        
                        if ($update->execute()) {
                            echo '<div class="success-box">
                                    <h5><i class="bi bi-check-circle me-2"></i>Password Reset Successful!</h5>
                                    <p class="mb-3">The admin password has been updated.</p>
                                    <div class="info-box">
                                        <strong>New Login Credentials:</strong><br>
                                        📧 Email: <code>admin@laflora.com</code><br>
                                        🔑 Password: <code>admin123</code><br>
                                        👤 Role: <code>admin</code>
                                    </div>
                                    <div class="code-box mt-3">
                                        <small>New password hash:</small><br>
                                        ' . htmlspecialchars($new_hash) . '
                                    </div>
                                    <a href="login.php" class="btn btn-success btn-custom mt-3">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login Page
                                    </a>
                                  </div>';
                        } else {
                            echo '<div class="danger-box">
                                    <i class="bi bi-x-circle me-2"></i>
                                    Failed to reset password: ' . htmlspecialchars($conn->error) . '
                                  </div>';
                        }
                    } else if ($password_works) {
                        echo '<div class="success-box">
                                <h5><i class="bi bi-check-circle me-2"></i>Ready to Login!</h5>
                                <div class="info-box">
                                    <strong>Login Credentials:</strong><br>
                                    📧 Email: <code>admin@laflora.com</code><br>
                                    🔑 Password: <code>' . htmlspecialchars($password_works) . '</code><br>
                                    👤 Role: <code>admin</code>
                                </div>
                                <a href="login.php" class="btn btn-success btn-custom mt-3">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login Page
                                </a>
                              </div>';
                    }
                    
                } else {
                    // No admin exists - create one
                    echo '<div class="text-center">
                            <div class="status-icon">⚠️</div>
                            <h4>No Admin User Found</h4>
                            <p class="text-muted">Creating new administrator account...</p>
                          </div>';
                    
                    $name = "Administrator";
                    $email = "admin@laflora.com";
                    $password = "admin123";
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $role = "admin";
                    
                    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
                    
                    if ($stmt->execute()) {
                        $new_id = $stmt->insert_id;
                        
                        echo '<div class="success-box">
                                <h5><i class="bi bi-check-circle me-2"></i>Admin User Created Successfully!</h5>
                                <p>A new administrator account has been created in the database.</p>
                                
                                <div class="info-box">
                                    <strong>Account Details:</strong><br>
                                    🆔 ID: <code>' . $new_id . '</code><br>
                                    👤 Name: <code>' . htmlspecialchars($name) . '</code><br>
                                    📧 Email: <code>' . htmlspecialchars($email) . '</code><br>
                                    🔑 Password: <code>' . htmlspecialchars($password) . '</code><br>
                                    👔 Role: <code>' . htmlspecialchars($role) . '</code>
                                </div>
                                
                                <div class="code-box mt-3">
                                    <small>Password hash:</small><br>
                                    ' . htmlspecialchars($hashed_password) . '
                                </div>
                                
                                <a href="login.php" class="btn btn-success btn-custom mt-3">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login Page
                                </a>
                              </div>';
                    } else {
                        echo '<div class="danger-box">
                                <h5><i class="bi bi-x-circle me-2"></i>Error Creating Admin</h5>
                                <p><strong>Database Error:</strong></p>
                                <div class="code-box">
                                    ' . htmlspecialchars($conn->error) . '
                                </div>
                              </div>';
                    }
                    
                    $stmt->close();
                }
                ?>
                
                <!-- Current Users Table -->
                <div class="mt-4">
                    <h5 class="mb-3">
                        <i class="bi bi-people me-2"></i>All Users in Database
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Password Hash</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $all_users = $conn->query("SELECT id, name, email, role, password, created_at FROM users ORDER BY id LIMIT 20");
                                
                                if ($all_users && $all_users->num_rows > 0) {
                                    while ($user = $all_users->fetch_assoc()) {
                                        $pass_preview = substr($user['password'], 0, 30) . '...';
                                        $role_class = $user['role'] == 'admin' ? 'danger' : 'primary';
                                        $created = isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A';
                                        
                                        echo '<tr>
                                                <td>' . htmlspecialchars($user['id']) . '</td>
                                                <td>' . htmlspecialchars($user['name']) . '</td>
                                                <td>' . htmlspecialchars($user['email']) . '</td>
                                                <td><span class="badge bg-' . $role_class . ' badge-custom">' . htmlspecialchars($user['role']) . '</span></td>
                                                <td><small><code>' . htmlspecialchars($pass_preview) . '</code></small></td>
                                                <td><small>' . $created . '</small></td>
                                              </tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="6" class="text-center text-muted py-3">No users found in database</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
            </div>
        </div>
        
        <!-- Security Warning Card -->
        <div class="card">
            <div class="card-body bg-danger text-white">
                <h5><i class="bi bi-exclamation-triangle me-2"></i>⚠️ CRITICAL SECURITY WARNING</h5>
                <p class="mb-0">
                    <strong>DELETE THIS FILE IMMEDIATELY AFTER USE!</strong><br>
                    This script provides unrestricted access to create and reset admin passwords. 
                    Leaving it on your server is a serious security risk.
                </p>
                <hr class="my-3 bg-white">
                <small>
                    <strong>To delete:</strong><br>
                    • Delete file: <code>admin/create_admin.php</code><br>
                    • Never use this on a production server without proper security
                </small>
            </div>
        </div>
        
        <div class="text-center text-white mt-3">
            <small>LA FLORA Admin Management Tool v2.0 | For development use only</small>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
$conn->close();
?>