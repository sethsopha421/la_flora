<?php
// cart.php - CLEAN KHQR DESIGN
session_start();
require_once 'includes/database.php';

$isLoggedIn = isset($_SESSION['user_id']);

$cartItems = [];
$subtotal = 0;
$total = 0;
$shipping = 0;
$tax = 0;
$discount_amount = 0;
$error = '';
$success = '';
$currentEmail = $isLoggedIn ? ($_SESSION['email'] ?? '') : '';
$item_count = 0;

// ==================== HANDLE EMAIL CHANGE ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_email'])) {
    $newEmail = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if ($newEmail) {
        $_SESSION['checkout_email'] = $newEmail;
        $success = "Email updated to: " . htmlspecialchars($newEmail);
    } else {
        $error = "Please enter a valid email address.";
    }
}

// ==================== HANDLE PAYMENT CHECKOUT ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $payment_method = $_POST['payment_method'] ?? '';

    $cart_count = $isLoggedIn
        ? (function() use ($conn) {
            $s = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
            mysqli_stmt_bind_param($s, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($s);
            return mysqli_fetch_assoc(mysqli_stmt_get_result($s))['count'];
          })()
        : count($_SESSION['cart'] ?? []);

    if ($cart_count == 0) {
        $error = "Your cart is empty.";
    } elseif (!$isLoggedIn) {
        $checkoutEmail = $_SESSION['checkout_email'] ?? '';
        if (empty($checkoutEmail)) {
            $error = "Please enter your email address.";
        } else {
            $_SESSION['guest_email']        = $checkoutEmail;
            $_SESSION['redirect_url']       = 'cart.php';
            $_SESSION['checkout_payment']   = $payment_method;
            $showLoginModal = true;
        }
    } else {
        $_SESSION['payment_method'] = $payment_method;
        $redirects = [
            'aba_qr'    => 'payment_qr.php?bank=aba',
            'acleda_qr' => 'payment_qr.php?bank=acleda',
            'paypal'    => 'payment_paypal.php',
            'cod'       => 'checkout.php?method=cod',
        ];
        if (isset($redirects[$payment_method])) {
            header('Location: ' . $redirects[$payment_method]);
            exit();
        } else {
            $error = "Please select a payment method.";
        }
    }
}

// ==================== HANDLE CART ACTIONS ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['update_cart'])) {
        foreach ($_POST['quantity'] as $product_id => $quantity) {
            $product_id = intval($product_id);
            $quantity   = intval($quantity);

            if ($quantity <= 0) {
                if ($isLoggedIn) {
                    $s = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                    mysqli_stmt_bind_param($s, "ii", $_SESSION['user_id'], $product_id);
                    mysqli_stmt_execute($s);
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }
            } else {
                $s = mysqli_prepare($conn, "SELECT stock FROM products WHERE id = ?");
                mysqli_stmt_bind_param($s, "i", $product_id);
                mysqli_stmt_execute($s);
                $stock_row = mysqli_fetch_assoc(mysqli_stmt_get_result($s));

                if ($stock_row && $quantity <= $stock_row['stock']) {
                    if ($isLoggedIn) {
                        $s = mysqli_prepare($conn, "SELECT id FROM cart WHERE user_id = ? AND product_id = ?");
                        mysqli_stmt_bind_param($s, "ii", $_SESSION['user_id'], $product_id);
                        mysqli_stmt_execute($s);
                        if (mysqli_num_rows(mysqli_stmt_get_result($s)) > 0) {
                            $s = mysqli_prepare($conn, "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                            mysqli_stmt_bind_param($s, "iii", $quantity, $_SESSION['user_id'], $product_id);
                        } else {
                            $s = mysqli_prepare($conn, "INSERT INTO cart (user_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
                            mysqli_stmt_bind_param($s, "iii", $_SESSION['user_id'], $product_id, $quantity);
                        }
                        mysqli_stmt_execute($s);
                    } else {
                        $_SESSION['cart'][$product_id] = ['product_id' => $product_id, 'quantity' => $quantity, 'added_at' => time()];
                    }
                } else {
                    $error = "Cannot update. Only " . ($stock_row['stock'] ?? 0) . " items available.";
                }
            }
        }
        if (empty($error)) $success = "Cart updated successfully!";

    } elseif (isset($_POST['remove_item'])) {
        $product_id = intval($_POST['product_id']);
        if ($isLoggedIn) {
            $s = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            mysqli_stmt_bind_param($s, "ii", $_SESSION['user_id'], $product_id);
            mysqli_stmt_execute($s);
        } else {
            unset($_SESSION['cart'][$product_id]);
        }
        $success = "Item removed from cart!";

    } elseif (isset($_POST['clear_cart'])) {
        if ($isLoggedIn) {
            $s = mysqli_prepare($conn, "DELETE FROM cart WHERE user_id = ?");
            mysqli_stmt_bind_param($s, "i", $_SESSION['user_id']);
            mysqli_stmt_execute($s);
        } else {
            $_SESSION['cart'] = [];
        }
        $success = "Cart cleared successfully!";

    } elseif (isset($_POST['apply_coupon'])) {
        $code = strtoupper(trim($_POST['coupon_code']));
        $coupons = [
            'FLOWER10' => ['type' => 'percent', 'value' => 10, 'label' => '10% off'],
            'FLOWER20' => ['type' => 'percent', 'value' => 20, 'label' => '20% off'],
            'FREESHIP' => ['type' => 'freeshipping', 'value' => 0, 'label' => 'Free shipping'],
            'WELCOME5' => ['type' => 'fixed', 'value' => 5, 'label' => '$5 off'],
        ];
        if (empty($code)) {
            $error = "Please enter a coupon code.";
        } elseif (isset($coupons[$code])) {
            $_SESSION['coupon'] = array_merge(['code' => $code], $coupons[$code]);
            $success = "Coupon '{$code}' applied! " . $coupons[$code]['label'] . ".";
        } else {
            $error = "Invalid coupon code.";
        }

    } elseif (isset($_POST['remove_coupon'])) {
        unset($_SESSION['coupon']);
        $success = "Coupon removed.";
    }
}

// ==================== FETCH CART ITEMS ====================
if ($isLoggedIn) {
    $s = mysqli_prepare($conn,
        "SELECT c.product_id, c.quantity, p.id, p.name, p.price, p.image, p.stock,
                cat.name as category_name
         FROM cart c
         JOIN products p ON c.product_id = p.id
         LEFT JOIN categories cat ON p.category_id = cat.id
         WHERE c.user_id = ? ORDER BY c.created_at DESC");
    mysqli_stmt_bind_param($s, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($s);
    $cartItems = mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC);
} else {
    if (!empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        $ph  = implode(',', array_fill(0, count($ids), '?'));
        $s   = mysqli_prepare($conn,
            "SELECT p.id, p.name, p.price, p.image, p.stock, cat.name as category_name
             FROM products p LEFT JOIN categories cat ON p.category_id = cat.id
             WHERE p.id IN ($ph)");
        mysqli_stmt_bind_param($s, str_repeat('i', count($ids)), ...$ids);
        mysqli_stmt_execute($s);
        $products = [];
        foreach (mysqli_fetch_all(mysqli_stmt_get_result($s), MYSQLI_ASSOC) as $row) {
            $products[$row['id']] = $row;
        }
        foreach ($_SESSION['cart'] as $pid => $item) {
            if (isset($products[$pid])) {
                $cartItems[] = array_merge($products[$pid], ['quantity' => $item['quantity'], 'product_id' => $pid]);
            }
        }
    }
}

// ==================== CALCULATE TOTALS ====================
foreach ($cartItems as $item) {
    $subtotal   += $item['price'] * $item['quantity'];
    $item_count += $item['quantity'];
}

if (isset($_SESSION['coupon'])) {
    $c = $_SESSION['coupon'];
    $discount_amount = match($c['type']) {
        'percent' => $subtotal * ($c['value'] / 100),
        'fixed'   => min($c['value'], $subtotal),
        default   => 0
    };
}

$shipping      = ($subtotal > 50 || (($_SESSION['coupon']['type'] ?? '') === 'freeshipping')) ? 0 : 5.99;
$taxable       = $subtotal - $discount_amount;
$tax           = $taxable * 0.08;
$total         = $taxable + $shipping + $tax;

$_SESSION['order_data'] = compact('subtotal', 'discount_amount', 'shipping', 'tax', 'total', 'cartItems', 'item_count');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - La Flora Cambodia</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2A5C4A;
            --primary-dark: #1E4033;
            --primary-light: #E8F3E8;
            --secondary: #D4A373;
            --danger: #E76F51;
            --success: #2A9D8F;
            --warning: #F4A261;
            --info: #2874A6;
            --bg-light: #F8F9FA;
            --text-dark: #2C3E50;
            --text-medium: #5D6D7E;
            --text-light: #95A5A6;
            --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.12);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --radius-xl: 24px;
            
            /* Bank Colors */
            --aba-blue: #0033A0;
            --aba-light: #E6F0FF;
            --acleda-blue: #1A4D7E;
            --acleda-light: #E8F0F8;
            --bakong-gold: #B76E2C;
            --bakong-light: #FFF3E0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        /* Main Layout */
        .cart-container {
            display: flex;
            min-height: 100vh;
            background: white;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            margin: 20px;
        }

        .cart-items-panel {
            flex: 1.5;
            padding: 32px;
            background: linear-gradient(to bottom right, var(--bg-light), white);
            overflow-y: auto;
            max-height: 100vh;
        }

        .cart-summary-panel {
            flex: 1;
            padding: 32px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            min-height: 100vh;
        }

        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 600;
        }

        /* Cart Header */
        .cart-header {
            border-bottom: 2px solid var(--secondary);
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .cart-header h1 {
            color: var(--primary);
            font-size: 2rem;
        }

        /* Email Section */
        .email-section {
            background: white;
            border: 2px solid var(--primary-light);
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
        }

        .email-section h5 {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 1.1rem;
        }

        .email-form {
            display: flex;
            gap: 12px;
            width: 100%;
        }

        .email-input {
            flex: 1;
            padding: 14px 18px;
            border: 2px solid #e9ecef;
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: all 0.3s;
            height: 52px;
        }

        .email-input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(42,92,74,0.1);
            outline: none;
        }

        .btn-update {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0 28px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            height: 52px;
            white-space: nowrap;
        }

        .btn-update:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Cart Items */
        .cart-item {
            background: white;
            border-radius: var(--radius-lg);
            padding: 24px;
            margin-bottom: 16px;
            border: 1px solid #eaeef2;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s;
        }

        .cart-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .item-image {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: var(--radius-md);
            border: 2px solid var(--secondary);
        }

        .item-details {
            padding-left: 24px;
        }

        .item-name {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .item-category {
            color: var(--text-medium);
            font-size: 0.9rem;
            margin-bottom: 8px;
        }

        .item-price {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--success);
            margin-bottom: 8px;
        }

        .stock-badge {
            font-size: 0.9rem;
            margin-bottom: 12px;
        }

        .stock-badge.in-stock { color: var(--success); }
        .stock-badge.low-stock { color: var(--warning); }
        .stock-badge.out-of-stock { color: var(--danger); }

        /* Quantity Controls */
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--primary);
            background: white;
            color: var(--primary);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background: var(--primary);
            color: white;
        }

        .qty-input {
            width: 70px;
            text-align: center;
            border: 2px solid var(--primary);
            border-radius: var(--radius-sm);
            padding: 8px;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .btn-remove {
            background: var(--danger);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-left: 8px;
        }

        .btn-remove:hover {
            background: #d64b2f;
            transform: scale(1.1);
        }

        /* Action Buttons */
        .cart-actions {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 2px solid #eaeef2;
        }

        .btn-primary-action {
            padding: 14px 24px;
            font-weight: 600;
            border-radius: var(--radius-md);
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .btn-update-cart {
            background: var(--primary);
            color: white;
        }

        .btn-update-cart:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-clear-cart {
            background: white;
            color: var(--danger);
            border: 2px solid var(--danger);
        }

        .btn-clear-cart:hover {
            background: var(--danger);
            color: white;
            transform: translateY(-2px);
        }

        .btn-continue {
            background: white;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: 14px 24px;
            border-radius: var(--radius-md);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }

        .btn-continue:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        /* Empty Cart */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-cart i {
            font-size: 5rem;
            color: #ccc;
            margin-bottom: 20px;
        }

        .empty-cart h3 {
            color: var(--primary);
            margin-bottom: 10px;
        }

        /* Summary Panel */
        .summary-sticky {
            position: sticky;
            top: 32px;
        }

        .item-count-badge {
            background: var(--secondary);
            color: var(--primary-dark);
            font-weight: 600;
            padding: 8px 20px;
            border-radius: 40px;
            display: inline-block;
            margin-bottom: 20px;
            font-size: 1rem;
        }

        .order-total-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: var(--radius-lg);
            padding: 28px;
            margin: 24px 0;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .total-row {
            font-size: 1rem;
            color: rgba(255,255,255,0.8);
            margin-bottom: 8px;
            font-weight: 500;
        }

        .grand-total {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin: 12px 0;
        }

        .tax-breakdown {
            background: rgba(255,255,255,0.05);
            border-radius: var(--radius-md);
            padding: 16px;
            margin-top: 16px;
        }

        /* Coupon Section */
        .coupon-form {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .coupon-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid rgba(255,255,255,0.2);
            background: rgba(255,255,255,0.1);
            border-radius: var(--radius-md);
            color: white;
            font-size: 0.95rem;
        }

        .coupon-input::placeholder {
            color: rgba(255,255,255,0.6);
        }

        .coupon-input:focus {
            border-color: white;
            outline: none;
        }

        .btn-apply {
            background: white;
            color: var(--primary);
            border: none;
            padding: 0 24px;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-apply:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .applied-coupon {
            background: white;
            color: var(--text-dark);
            border-radius: var(--radius-md);
            padding: 10px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
        }

        .btn-remove-coupon {
            background: var(--danger);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            cursor: pointer;
        }

        /* Payment Section */
        .payment-section {
            background: white;
            border-radius: var(--radius-lg);
            padding: 28px;
            margin-top: 28px;
            color: var(--text-dark);
        }

        .payment-section h5 {
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .payment-section > p {
            color: var(--text-medium);
            margin-bottom: 24px;
        }

        .payment-options {
            border: 1px solid #eaeef2;
            border-radius: var(--radius-md);
            overflow: hidden;
            margin-bottom: 24px;
        }

        .payment-option {
            padding: 18px 20px;
            border-bottom: 1px solid #eaeef2;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .payment-option:last-child {
            border-bottom: none;
        }

        .payment-option:hover {
            background: var(--primary-light);
        }

        .payment-option.selected {
            background: var(--primary-light);
            box-shadow: inset 0 0 0 1px var(--primary);
        }

        .payment-radio {
            width: 20px;
            height: 20px;
            border: 2px solid #cbd2d9;
            border-radius: 50%;
            position: relative;
        }

        .payment-option.selected .payment-radio {
            border-color: var(--primary);
        }

        .payment-option.selected .payment-radio::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 10px;
            height: 10px;
            background: var(--primary);
            border-radius: 50%;
        }

        .payment-content {
            flex: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .payment-title {
            font-weight: 500;
            color: var(--text-dark);
        }

        .payment-desc {
            font-size: 0.875rem;
            color: var(--text-medium);
            margin: 0;
        }

        .payment-icons {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .payment-icons i {
            font-size: 28px;
        }

        .fa-cc-paypal { color: #003087; }
        .fa-cc-visa { color: #1a1f71; }
        .fa-cc-mastercard { color: #eb001b; }

        /* ===== CLEAN KHQR DESIGN ===== */
        .khqr-container {
            margin-top: 24px;
            animation: fadeIn 0.4s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Bank Header */
        .bank-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f0f4f8;
        }

        .bank-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }

        .bank-header.aba .bank-icon {
            background: var(--aba-light);
            color: var(--aba-blue);
        }

        .bank-header.acleda .bank-icon {
            background: var(--acleda-light);
            color: var(--acleda-blue);
        }

        .bank-info h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-dark);
        }

        .bank-info p {
            margin: 4px 0 0;
            color: var(--text-medium);
            font-size: 0.9rem;
        }

        /* KHQR Display */
        .khqr-display {
            background: #f8fafc;
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .qr-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .qr-image-container {
            background: white;
            padding: 16px;
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.04);
            border: 1px solid #eef2f6;
            transition: transform 0.3s;
            display: inline-block;
        }

        .qr-image-container:hover {
            transform: scale(1.02);
        }

        .qr-image {
            width: 200px;
            height: 200px;
            object-fit: contain;
            border-radius: 12px;
        }

        /* Badges Row */
        .badges-row {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin-top: 16px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 6px 16px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .badge.aba {
            background: var(--aba-light);
            color: var(--aba-blue);
        }

        .badge.acleda {
            background: var(--acleda-light);
            color: var(--acleda-blue);
        }

        .badge.bakong {
            background: var(--bakong-light);
            color: var(--bakong-gold);
        }

        .badge.khqr {
            background: #e8f0fe;
            color: #1a73e8;
        }

        /* Amount Display */
        .amount-display {
            text-align: center;
            margin: 20px 0;
        }

        .amount-pill {
            background: white;
            border: 1px dashed var(--primary-light);
            padding: 10px 24px;
            border-radius: 40px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: var(--primary);
            box-shadow: var(--shadow-sm);
        }

        .amount-pill i {
            color: var(--secondary);
        }

        /* Account Cards */
        .accounts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin: 24px 0;
        }

        .account-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: var(--shadow-sm);
            border: 1px solid #edf2f7;
        }

        .account-card.aba {
            border-top: 4px solid var(--aba-blue);
        }

        .account-card.acleda {
            border-top: 4px solid var(--acleda-blue);
        }

        .account-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-medium);
            margin-bottom: 8px;
        }

        .account-name {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 12px;
        }

        .account-number {
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .copy-btn {
            background: none;
            border: none;
            color: var(--primary);
            cursor: pointer;
            font-size: 1rem;
            padding: 0 4px;
        }

        .copy-btn:hover {
            color: var(--primary-dark);
        }

        .account-currency {
            font-size: 0.85rem;
            color: var(--text-medium);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Instructions */
        .instructions {
            background: #f8fafc;
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
        }

        .instructions h6 {
            font-weight: 600;
            margin-bottom: 16px;
            color: var(--text-dark);
        }

        .instructions ol {
            padding-left: 20px;
            margin: 0;
        }

        .instructions li {
            margin-bottom: 8px;
            color: var(--text-medium);
            line-height: 1.5;
        }

        /* Security Badge */
        .security-badge {
            background: #e8f4fd;
            color: var(--info);
            padding: 14px;
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: 0.9rem;
            margin-top: 20px;
        }

        /* Test Mode Banner */
        .test-banner {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: var(--radius-md);
            padding: 12px 16px;
            margin-bottom: 20px;
            color: #856404;
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Pay Button */
        .btn-pay {
            background: var(--secondary);
            color: var(--primary-dark);
            border: none;
            padding: 18px;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 40px;
            transition: all 0.3s;
            width: 100%;
            margin-top: 24px;
            cursor: pointer;
        }

        .btn-pay:hover:not(:disabled) {
            background: #c99a6b;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-pay:disabled {
            background: #cbd2d9;
            color: #6c757d;
            cursor: not-allowed;
        }

        /* Footer Logos */
        .card-logos {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 24px;
            padding: 16px;
            background: var(--bg-light);
            border-radius: var(--radius-md);
        }

        .card-logos i {
            font-size: 36px;
        }

        .powered-by {
            text-align: center;
            margin-top: 16px;
            font-size: 0.875rem;
            color: var(--text-medium);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .cart-container {
                flex-direction: column;
                margin: 10px;
            }
            
            .cart-items-panel,
            .cart-summary-panel {
                max-height: none;
                min-height: auto;
            }
        }

        @media (max-width: 768px) {
            .cart-container {
                margin: 5px;
                border-radius: var(--radius-lg);
            }
            
            .cart-items-panel,
            .cart-summary-panel {
                padding: 20px;
            }
            
            .grand-total {
                font-size: 2rem;
            }
            
            .email-form {
                flex-direction: column;
            }
            
            .btn-update {
                width: 100%;
            }
            
            .qr-image {
                width: 160px;
                height: 160px;
            }
            
            .accounts-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 576px) {
            .btn-continue {
                width: 100%;
                margin: 5px 0;
            }
            
            .cart-actions .d-flex {
                flex-direction: column;
            }
            
            .btn-primary-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="cart-container">
        <!-- LEFT PANEL: Cart Items -->
        <div class="cart-items-panel">
            <div class="cart-header">
                <h1>
                    <i class="fas fa-shopping-cart me-3" style="color: var(--primary);"></i>
                    Shopping Cart
                </h1>
                
                <!-- Email Section -->
                <div class="email-section">
                    <h5>
                        <i class="fas fa-envelope me-2" style="color: var(--primary);"></i>
                        Email for updates
                    </h5>
                    <form method="POST" class="email-form">
                        <input type="email" 
                               name="email" 
                               class="email-input"
                               placeholder="your@email.com"
                               value="<?php echo htmlspecialchars($_SESSION['checkout_email'] ?? $currentEmail); ?>"
                               required>
                        <button type="submit" name="change_email" class="btn-update">
                            <i class="fas fa-check"></i> Update
                        </button>
                    </form>
                    <small class="text-muted mt-2 d-block">
                        <i class="fas fa-info-circle me-1"></i>
                        Order confirmation & tracking will be sent here
                    </small>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($cartItems)): ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Your cart is empty</h3>
                    <p class="text-muted mb-4">Looks like you haven't added any flowers yet.</p>
                    <a href="shop.php" class="btn-continue">
                        <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <form method="post" action="cart.php" id="cartForm">
                    <h4 class="mb-4" style="color: var(--primary);">
                        <?php echo $item_count; ?> item<?php echo $item_count != 1 ? 's' : ''; ?>
                    </h4>
                    
                    <?php foreach ($cartItems as $item): 
                        $item_total = $item['price'] * $item['quantity'];
                        $stock_class = $item['stock'] > 10 ? 'in-stock' : ($item['stock'] > 0 ? 'low-stock' : 'out-of-stock');
                        $stock_text = $item['stock'] > 10 ? 'In Stock' : ($item['stock'] > 0 ? 'Low Stock' : 'Out of Stock');
                    ?>
                        <div class="cart-item">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <img src="<?php echo htmlspecialchars($item['image'] ?? 'assets/images/products/default.jpg'); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         class="item-image"
                                         onerror="this.src='https://images.unsplash.com/photo-1563241527-3004b7be0ffd?auto=format&fit=crop&w=300&q=80'">
                                </div>
                                
                                <div class="col-md-6 item-details">
                                    <h3 class="item-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                    <div class="item-category">
                                        <i class="fas fa-tag me-2"></i>
                                        <?php echo htmlspecialchars($item['category_name'] ?? 'Uncategorized'); ?>
                                    </div>
                                    <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="stock-badge <?php echo $stock_class; ?>">
                                        <i class="fas fa-box me-2"></i>
                                        <?php echo $stock_text; ?> (<?php echo $item['stock']; ?> available)
                                    </div>
                                    
                                    <div class="quantity-controls">
                                        <button type="button" class="qty-btn decrease-qty" data-product="<?php echo $item['id']; ?>">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        
                                        <input type="number" 
                                               name="quantity[<?php echo $item['id']; ?>]"
                                               value="<?php echo $item['quantity']; ?>"
                                               min="1" 
                                               max="<?php echo $item['stock']; ?>"
                                               class="qty-input"
                                               data-product="<?php echo $item['id']; ?>"
                                               readonly>
                                        
                                        <button type="button" class="qty-btn increase-qty" data-product="<?php echo $item['id']; ?>">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        
                                        <button type="submit" 
                                                name="remove_item" 
                                                class="btn-remove"
                                                onclick="return confirm('Remove this item?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-3 text-end">
                                    <div class="h4 fw-bold" style="color: var(--primary);">
                                        $<?php echo number_format($item_total, 2); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Cart Actions -->
                    <div class="cart-actions">
                        <div class="row">
                            <div class="col-md-8">
                                <a href="shop.php" class="btn-continue">
                                    <i class="fas fa-arrow-left"></i> Continue Shopping
                                </a>
                                <?php if ($isLoggedIn): ?>
                                <a href="orders.php" class="btn-continue ms-3">
                                    <i class="fas fa-list-alt me-2"></i>My Orders
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <div class="d-flex gap-3">
                                    <button type="submit" name="update_cart" class="btn-primary-action btn-update-cart">
                                        <i class="fas fa-sync-alt me-2"></i>Update
                                    </button>
                                    
                                    <button type="submit" name="clear_cart" 
                                            class="btn-primary-action btn-clear-cart"
                                            onclick="return confirm('Clear all items?')">
                                        <i class="fas fa-trash-alt me-2"></i>Clear
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- RIGHT PANEL: Order Summary & Payment -->
        <div class="cart-summary-panel">
            <div class="summary-sticky">
                <div class="item-count-badge">
                    <i class="fas fa-box me-2"></i>
                    <?php echo $item_count; ?> item<?php echo $item_count != 1 ? 's' : ''; ?>
                </div>
                
                <!-- Order Summary -->
                <div class="order-total-card">
                    <div class="total-row">SUBTOTAL</div>
                    <div class="h3 fw-bold">$<?php echo number_format($subtotal, 2); ?></div>
                    
                    <?php if ($discount_amount > 0): ?>
                        <div class="total-row text-warning">DISCOUNT</div>
                        <div class="h4 fw-bold text-warning">-$<?php echo number_format($discount_amount, 2); ?></div>
                    <?php endif; ?>
                    
                    <div class="total-row">SHIPPING</div>
                    <?php if ($shipping > 0): ?>
                        <div class="h5 fw-bold">$<?php echo number_format($shipping, 2); ?></div>
                    <?php else: ?>
                        <div class="h5 fw-bold text-success">FREE</div>
                    <?php endif; ?>
                    
                    <div class="tax-breakdown">
                        <div class="d-flex justify-content-between">
                            <span>Tax (8%):</span>
                            <span class="fw-bold">$<?php echo number_format($tax, 2); ?></span>
                        </div>
                    </div>
                    
                    <hr class="my-3" style="border-color: rgba(255,255,255,0.3);">
                    
                    <div class="total-row">TOTAL</div>
                    <div class="grand-total">$<?php echo number_format($total, 2); ?></div>
                    
                    <!-- Coupon -->
                    <div class="mt-3">
                        <form method="POST" class="coupon-form">
                            <input type="text" name="coupon_code" class="coupon-input" placeholder="Enter coupon code">
                            <button type="submit" name="apply_coupon" class="btn-apply">Apply</button>
                        </form>
                        
                        <?php if (isset($_SESSION['coupon'])): ?>
                            <div class="applied-coupon">
                                <span><i class="fas fa-tag me-2"></i><?php echo $_SESSION['coupon']['code']; ?></span>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="remove_coupon" class="btn-remove-coupon">Remove</button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($cartItems)): ?>
                    <!-- Payment Section -->
                    <div class="payment-section">
                        <h5>Payment</h5>
                        <p>Select your payment method:</p>
                        
                        <form method="POST" id="paymentForm">
                            <div class="payment-options">
                                <!-- PayPal -->
                                <div class="payment-option" onclick="selectPayment('paypal')" data-payment="paypal">
                                    <div class="payment-radio"></div>
                                    <div class="payment-content">
                                        <div>
                                            <div class="payment-title">PayPal</div>
                                            <p class="payment-desc">Credit Card, PayPal balance</p>
                                        </div>
                                        <div class="payment-icons">
                                            <i class="fab fa-cc-paypal"></i>
                                            <i class="fab fa-cc-visa"></i>
                                            <i class="fab fa-cc-mastercard"></i>
                                        </div>
                                    </div>
                                    <input type="radio" name="payment_method" value="paypal" class="d-none">
                                </div>
                                
                                <!-- ABA KHQR -->
                                <div class="payment-option" onclick="selectPayment('aba_qr')" data-payment="aba_qr">
                                    <div class="payment-radio"></div>
                                    <div class="payment-content">
                                        <div>
                                            <div class="payment-title">ABA Bank</div>
                                            <p class="payment-desc">Scan KHQR with ABA Mobile</p>
                                        </div>
                                        <div class="payment-icons">
                                            <i class="fas fa-university" style="color: var(--aba-blue);"></i>
                                        </div>
                                    </div>
                                    <input type="radio" name="payment_method" value="aba_qr" class="d-none">
                                </div>
                                
                                <!-- ACLEDA KHQR -->
                                <div class="payment-option" onclick="selectPayment('acleda_qr')" data-payment="acleda_qr">
                                    <div class="payment-radio"></div>
                                    <div class="payment-content">
                                        <div>
                                            <div class="payment-title">ACLEDA Bank</div>
                                            <p class="payment-desc">Scan KHQR with ACLEDA Mobile</p>
                                        </div>
                                        <div class="payment-icons">
                                            <i class="fas fa-university" style="color: var(--acleda-blue);"></i>
                                        </div>
                                    </div>
                                    <input type="radio" name="payment_method" value="acleda_qr" class="d-none">
                                </div>
                                
                                <!-- Cash on Delivery -->
                                <div class="payment-option" onclick="selectPayment('cod')" data-payment="cod">
                                    <div class="payment-radio"></div>
                                    <div class="payment-content">
                                        <div>
                                            <div class="payment-title">Cash on Delivery</div>
                                            <p class="payment-desc">Pay when you receive</p>
                                        </div>
                                        <div class="payment-icons">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </div>
                                    </div>
                                    <input type="radio" name="payment_method" value="cod" class="d-none">
                                </div>
                            </div>
                            
                            <!-- PayPal Details -->
                            <div id="paypalDetails" style="display: none;">
                                <div class="test-banner">
                                    <i class="fas fa-flask me-2"></i>TEST MODE ENABLED
                                </div>
                                <p class="mb-3">You'll be redirected to PayPal to complete your payment.</p>
                                <div class="security-badge">
                                    <i class="fab fa-paypal"></i>
                                    <span>Protected by PayPal Buyer Protection</span>
                                </div>
                            </div>
                            
                            <!-- ABA KHQR Details - CLEAN DESIGN -->
                            <div id="abaDetails" style="display: none;">
                                <div class="test-banner">
                                    <i class="fas fa-flask me-2"></i>TEST MODE - Scan with ABA Mobile
                                </div>
                                
                                <div class="khqr-container">
                                    <!-- Bank Header -->
                                    <div class="bank-header aba">
                                        <div class="bank-icon">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="bank-info">
                                            <h3>ABA Bank</h3>
                                            <p>Bakong KHQR · Scan & Pay</p>
                                        </div>
                                    </div>
                                    
                                    <!-- QR Code & Badges -->
                                    <div class="khqr-display">
                                        <div class="qr-wrapper">
                                            <div class="qr-image-container">
                                                <img src="assets/images/qr/aba-khqr.jpg" 
                                                     alt="ABA KHQR Code" 
                                                     class="qr-image"
                                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\' viewBox=\'0 0 200 200\'%3E%3Crect width=\'200\' height=\'200\' fill=\'%23f5f5f5\'/%3E%3Ctext x=\'50\' y=\'115\' font-family=\'Arial\' font-size=\'14\' fill=\'%23999\'%3EABA KHQR%3C/text%3E%3C/svg%3E'">
                                            </div>
                                            
                                            <div class="badges-row">
                                                <span class="badge aba">ABA</span>
                                                <span class="badge bakong">BAKONG</span>
                                                <span class="badge khqr">KHQR</span>
                                            </div>
                                        </div>
                                        
                                        <div class="amount-display">
                                            <div class="amount-pill">
                                                <i class="fas fa-dollar-sign"></i>
                                                Amount: <strong>$<?php echo number_format($total, 2); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Account Information -->
                                    <div class="accounts-grid">
                                        <div class="account-card aba">
                                            <div class="account-label">KHR Account</div>
                                            <div class="account-name">SOPHA SET</div>
                                            <div class="account-number">
                                                <span>011 467 401</span>
                                                <button type="button" class="copy-btn" onclick="copyText('011467401')">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </div>
                                            <div class="account-currency">
                                                <i class="fas fa-money-bill-wave"></i> Cambodian Riel
                                            </div>
                                        </div>
                                        
                                        <div class="account-card aba">
                                            <div class="account-label">USD Account</div>
                                            <div class="account-name">SOPHA SET</div>
                                            <div class="account-number">
                                                <span>011 467 400</span>
                                                <button type="button" class="copy-btn" onclick="copyText('011467400')">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </div>
                                            <div class="account-currency">
                                                <i class="fas fa-dollar-sign"></i> US Dollar
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Instructions -->
                                    <div class="instructions">
                                        <h6><i class="fas fa-info-circle me-2"></i>How to pay with ABA KHQR:</h6>
                                        <ol>
                                            <li>Open <strong>ABA Mobile</strong> app on your phone</li>
                                            <li>Tap <strong>"Scan & Pay"</strong> or <strong>"KHQR"</strong></li>
                                            <li>Scan the QR code above</li>
                                            <li>Verify account name: <strong>SOPHA SET</strong></li>
                                            <li>Enter amount: <strong>$<?php echo number_format($total, 2); ?></strong></li>
                                            <li>Confirm with your PIN or biometrics</li>
                                        </ol>
                                    </div>
                                    
                                    <!-- Security Badge -->
                                    <div class="security-badge">
                                        <i class="fas fa-lock"></i>
                                        <span>Secure Payment via ABA Bank · Bakong Certified</span>
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ACLEDA KHQR Details - CLEAN DESIGN -->
                            <div id="acledaDetails" style="display: none;">
                                <div class="test-banner">
                                    <i class="fas fa-flask me-2"></i>TEST MODE - Scan with ACLEDA Mobile
                                </div>
                                
                                <div class="khqr-container">
                                    <!-- Bank Header -->
                                    <div class="bank-header acleda">
                                        <div class="bank-icon">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="bank-info">
                                            <h3>ACLEDA Bank</h3>
                                            <p>Bakong KHQR · Scan & Pay</p>
                                        </div>
                                    </div>
                                    
                                    <!-- QR Code & Badges -->
                                    <div class="khqr-display">
                                        <div class="qr-wrapper">
                                            <div class="qr-image-container">
                                                <img src="assets/images/qr/acleda-khqr.jpg" 
                                                     alt="ACLEDA KHQR Code" 
                                                     class="qr-image"
                                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\' viewBox=\'0 0 200 200\'%3E%3Crect width=\'200\' height=\'200\' fill=\'%23f5f5f5\'/%3E%3Ctext x=\'40\' y=\'115\' font-family=\'Arial\' font-size=\'14\' fill=\'%23999\'%3EACLEDA KHQR%3C/text%3E%3C/svg%3E'">
                                            </div>
                                            
                                            <div class="badges-row">
                                                <span class="badge acleda">ACLEDA</span>
                                                <span class="badge bakong">BAKONG</span>
                                                <span class="badge khqr">KHQR</span>
                                            </div>
                                        </div>
                                        
                                        <div class="amount-display">
                                            <div class="amount-pill">
                                                <i class="fas fa-dollar-sign"></i>
                                                Amount: <strong>$<?php echo number_format($total, 2); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Account Information -->
                                    <div class="accounts-grid" style="grid-template-columns: 1fr;">
                                        <div class="account-card acleda">
                                            <div class="account-label">Account Information</div>
                                            <div class="account-name">SET SOPHA</div>
                                            <div class="account-number">
                                                <span>011 467 400</span>
                                                <button type="button" class="copy-btn" onclick="copyText('011467400')">
                                                    <i class="far fa-copy"></i>
                                                </button>
                                            </div>
                                            <div class="account-currency">
                                                <i class="fas fa-university"></i> USD/KHR · KHQR Member
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Hotline -->
                                    <div class="mt-3 p-3 bg-light rounded" style="font-size: 0.9rem;">
                                        <i class="fas fa-phone-alt me-2 text-primary"></i>
                                        Bank hotline: <strong>023 994 444</strong> | <strong>015 999 233</strong>
                                    </div>
                                    
                                    <!-- Instructions -->
                                    <div class="instructions">
                                        <h6><i class="fas fa-info-circle me-2"></i>How to pay with ACLEDA KHQR:</h6>
                                        <ol>
                                            <li>Open <strong>ACLEDA Mobile</strong> app on your phone</li>
                                            <li>Select <strong>"Scan & Pay"</strong> from the menu</li>
                                            <li>Scan the QR code above</li>
                                            <li>Verify account name: <strong>SET SOPHA</strong></li>
                                            <li>Enter amount: <strong>$<?php echo number_format($total, 2); ?></strong></li>
                                            <li>Confirm payment with your PIN</li>
                                        </ol>
                                    </div>
                                    
                                    <!-- Security Badge -->
                                    <div class="security-badge">
                                        <i class="fas fa-lock"></i>
                                        <span>Secure Payment · ACLEDA Bank · Bakong Certified</span>
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="process_payment" class="btn-pay" id="payNowBtn" disabled>
                                Proceed to Checkout
                            </button>
                            
                            <div class="card-logos">
                                <i class="fab fa-cc-paypal" style="color: #003087;"></i>
                                <i class="fab fa-cc-visa" style="color: #1a1f71;"></i>
                                <i class="fab fa-cc-mastercard" style="color: #eb001b;"></i>
                                <i class="fab fa-cc-amex" style="color: #006fcf;"></i>
                            </div>
                            
                            <div class="powered-by">
                                <small><i class="fas fa-lock me-1"></i> Secure SSL Encryption</small>
                            </div>
                            
                            <?php if (!$isLoggedIn): ?>
                                <div class="alert alert-warning mt-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Guest checkout. <a href="user/login.php?checkout=true" class="alert-link">Login</a> or 
                                    <a href="user/register.php" class="alert-link">create an account</a> for faster checkout.
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Login Modal -->
    <?php if (isset($showLoginModal)): ?>
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-sign-in-alt me-2"></i>Continue to Checkout
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Please login or create an account to complete your purchase.</p>
                    <div class="d-grid gap-3">
                        <a href="user/login.php?checkout=true" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </a>
                        <a href="user/register.php" class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </a>
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-shopping-cart me-2"></i>Continue as Guest
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new bootstrap.Modal(document.getElementById('loginModal')).show();
        });
    </script>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Quantity Controls
        document.addEventListener('DOMContentLoaded', function() {
            // Increase quantity
            document.querySelectorAll('.increase-qty').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.product;
                    const input = document.querySelector(`input[name="quantity[${productId}]"]`);
                    const max = parseInt(input.max);
                    
                    if (parseInt(input.value) < max) {
                        input.value = parseInt(input.value) + 1;
                        updateCartItem(productId, input.value);
                    }
                });
            });
            
            // Decrease quantity
            document.querySelectorAll('.decrease-qty').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.product;
                    const input = document.querySelector(`input[name="quantity[${productId}]"]`);
                    
                    if (parseInt(input.value) > 1) {
                        input.value = parseInt(input.value) - 1;
                        updateCartItem(productId, input.value);
                    }
                });
            });
        });

        // Update cart via AJAX
        function updateCartItem(productId, quantity) {
            const formData = new FormData();
            formData.append('update_cart', '1');
            formData.append(`quantity[${productId}]`, quantity);
            
            fetch('cart.php', {
                method: 'POST',
                body: formData
            })
            .then(() => window.location.reload())
            .catch(error => {
                console.error('Error updating cart:', error);
                alert('Error updating cart. Please try again.');
            });
        }

        // Copy to clipboard
        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.createElement('div');
                toast.className = 'alert alert-success position-fixed top-50 start-50 translate-middle';
                toast.style.cssText = 'z-index: 9999; min-width: 250px; text-align: center;';
                toast.innerHTML = '<i class="fas fa-check-circle me-2"></i>Account number copied!';
                document.body.appendChild(toast);
                
                setTimeout(() => {
                    toast.remove();
                }, 2000);
            }).catch(err => {
                console.error('Could not copy text: ', err);
            });
        }

        // Payment selection
        let selectedPayment = '';

        function selectPayment(method) {
            const option = event.currentTarget;
            
            // Remove selection from all options
            document.querySelectorAll('.payment-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            
            // Hide all payment details
            document.querySelectorAll('#paypalDetails, #abaDetails, #acledaDetails').forEach(el => {
                el.style.display = 'none';
            });
            
            // Select current option
            option.classList.add('selected');
            selectedPayment = method;
            
            const radio = option.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;
            
            // Show relevant details
            const detailMap = {
                'paypal': 'paypalDetails',
                'aba_qr': 'abaDetails',
                'acleda_qr': 'acledaDetails'
            };
            
            const buttonText = {
                'paypal': 'Continue with PayPal',
                'aba_qr': 'Pay with ABA Bank',
                'acleda_qr': 'Pay with ACLEDA Bank',
                'cod': 'Place Order (COD)'
            };
            
            if (detailMap[method]) {
                const detailEl = document.getElementById(detailMap[method]);
                if (detailEl) {
                    detailEl.style.display = 'block';
                    setTimeout(() => {
                        detailEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 100);
                }
            }
            
            // Update button
            const payBtn = document.getElementById('payNowBtn');
            if (payBtn) {
                payBtn.textContent = buttonText[method] || 'Proceed to Checkout';
                payBtn.disabled = false;
            }
            
            // COD validation
            if (method === 'cod') {
                const totalAmount = <?php echo $total; ?>;
                if (totalAmount > 500) {
                    alert('Cash on Delivery is only available for orders under $500. Please select another payment method.');
                    option.classList.remove('selected');
                    selectedPayment = '';
                    if (radio) radio.checked = false;
                    payBtn.disabled = true;
                }
            }
        }

        // Form validation
        document.getElementById('paymentForm')?.addEventListener('submit', function(e) {
            if (!selectedPayment) {
                e.preventDefault();
                alert('Please select a payment method.');
                return false;
            }
            
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput && !emailInput.value) {
                e.preventDefault();
                alert('Please enter your email for order updates.');
                return false;
            }
            
            if (selectedPayment === 'cod') {
                const totalAmount = <?php echo $total; ?>;
                if (totalAmount > 500) {
                    e.preventDefault();
                    alert('Cash on Delivery is only available for orders under $500.');
                    return false;
                }
            }
            
            const payBtn = document.getElementById('payNowBtn');
            if (payBtn) {
                payBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                payBtn.disabled = true;
            }
            
            return true;
        });
    </script>
</body>
</html>
<?php 
if (isset($conn)) {
    mysqli_close($conn);
}
?>