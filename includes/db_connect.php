<?php
// user/profile.php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/auth_functions.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// ================================
// FETCH USER DATA (FIXED)
// ================================
$sql = "SELECT name, email, phone, address, city, state, zip_code, country, created_at 
        FROM users WHERE id = ?";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("User not found.");
}

// ================================
// UPDATE PROFILE
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $name     = trim($_POST['name']);
    $phone    = trim($_POST['phone']);
    $address  = trim($_POST['address']);
    $city     = trim($_POST['city']);
    $state    = trim($_POST['state']);
    $zip_code = trim($_POST['zip_code']);
    $country  = trim($_POST['country']);

    if (empty($name)) {
        $error = "Full name is required.";
    } else {
        $sql = "UPDATE users 
                SET name = ?, phone = ?, address = ?, city = ?, state = ?, zip_code = ?, country = ?, updated_at = NOW()
                WHERE id = ?";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt,
            "sssssssi",
            $name,
            $phone,
            $address,
            $city,
            $state,
            $zip_code,
            $country,
            $user_id
        );

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['username'] = $name;
            $success = "Profile updated successfully.";

            // Update local array (no refresh needed)
            $user['name'] = $name;
            $user['phone'] = $phone;
            $user['address'] = $address;
            $user['city'] = $city;
            $user['state'] = $state;
            $user['zip_code'] = $zip_code;
            $user['country'] = $country;
        } else {
            $error = "Failed to update profile.";
        }
    }
}

// ================================
// CHANGE PASSWORD
// ================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $current_password = $_POST['current_password'];
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {

        // Get current password hash
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);

        if ($row && password_verify($current_password, $row['password'])) {

            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $new_hash, $user_id);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Password changed successfully.";
            } else {
                $error = "Failed to change password.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5">
    <h3>My Profile</h3>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <!-- PROFILE FORM -->
    <form method="post">
        <input type="hidden" name="update_profile">

        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="name" class="form-control"
                   value="<?= htmlspecialchars($user['name']) ?>" required>
        </div>

        <div class="mb-3">
            <label>Email</label>
            <input type="email" class="form-control"
                   value="<?= htmlspecialchars($user['email']) ?>" disabled>
        </div>

        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="phone" class="form-control"
                   value="<?= htmlspecialchars($user['phone']) ?>">
        </div>

        <div class="mb-3">
            <label>Address</label>
            <input type="text" name="address" class="form-control"
                   value="<?= htmlspecialchars($user['address']) ?>">
        </div>

        <div class="row">
            <div class="col">
                <input type="text" name="city" placeholder="City" class="form-control"
                       value="<?= htmlspecialchars($user['city']) ?>">
            </div>
            <div class="col">
                <input type="text" name="state" placeholder="State" class="form-control"
                       value="<?= htmlspecialchars($user['state']) ?>">
            </div>
        </div>

        <div class="row mt-3">
            <div class="col">
                <input type="text" name="zip_code" placeholder="ZIP" class="form-control"
                       value="<?= htmlspecialchars($user['zip_code']) ?>">
            </div>
            <div class="col">
                <input type="text" name="country" placeholder="Country" class="form-control"
                       value="<?= htmlspecialchars($user['country']) ?>">
            </div>
        </div>

        <button class="btn btn-primary mt-3">Save Profile</button>
    </form>

    <hr>

    <!-- PASSWORD FORM -->
    <form method="post">
        <input type="hidden" name="change_password">

        <h5>Change Password</h5>

        <input type="password" name="current_password" class="form-control mb-2" placeholder="Current Password" required>
        <input type="password" name="new_password" class="form-control mb-2" placeholder="New Password" required>
        <input type="password" name="confirm_password" class="form-control mb-2" placeholder="Confirm Password" required>

        <button class="btn btn-warning">Change Password</button>
    </form>

</div>

</body>
</html>
