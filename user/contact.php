<?php
// user/contact.php
session_start();
require_once '../includes/db_connect.php';

// ─── Create table if needed ─────────────────────────────────────────────────
mysqli_query($conn, "
    CREATE TABLE IF NOT EXISTS contact_messages (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(100)  NOT NULL,
        email       VARCHAR(100)  NOT NULL,
        subject     VARCHAR(200)  NOT NULL,
        message     TEXT          NOT NULL,
        status      ENUM('new','read','replied','closed') DEFAULT 'new',
        user_id     INT           NULL,
        created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
        updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )
");

// ─── State ──────────────────────────────────────────────────────────────────
$error   = '';
$success = '';
$name    = '';
$email   = '';
$subject = '';
$message = '';

// Pre-fill from session
if (isset($_SESSION['user_id'])) {
    $stmt = mysqli_prepare($conn, "SELECT name, email FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($row) {
        $name  = $row['name'];
        $email = $row['email'];
    }
    mysqli_stmt_close($stmt);
}

// ─── Handle POST ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    $errors = [];
    if (empty($name))                                        $errors[] = "Name is required.";
    if (empty($email))                                       $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors[] = "Please enter a valid email address.";
    if (empty($subject))                                     $errors[] = "Subject is required.";
    if (empty($message))                                     $errors[] = "Message is required.";
    elseif (strlen($message) < 10)                           $errors[] = "Message must be at least 10 characters.";

    if (empty($errors)) {
        $uid  = $_SESSION['user_id'] ?? NULL;
        $type = $uid !== NULL ? "ssssi" : "ssss";

        $sql  = $uid !== NULL
            ? "INSERT INTO contact_messages (name, email, subject, message, user_id) VALUES (?, ?, ?, ?, ?)"
            : "INSERT INTO contact_messages (name, email, subject, message)          VALUES (?, ?, ?, ?)";

        $stmt = mysqli_prepare($conn, $sql);
        if ($uid !== NULL)
            mysqli_stmt_bind_param($stmt, $type, $name, $email, $subject, $message, $uid);
        else
            mysqli_stmt_bind_param($stmt, $type, $name, $email, $subject, $message);

        if (mysqli_stmt_execute($stmt)) {
            $success = "Thank you for your message! We'll get back to you within 24–48 hours.";
            $name = $email = $subject = $message = '';
        } else {
            $error = "Something went wrong. Please try again later.";
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us – LaFlora</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Fonts: Cormorant Garamond (display) + DM Sans (body) -->
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;500;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Site CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
    /* ═══════════════════════════════════════════════════════════
       TOKENS
       ═══════════════════════════════════════════════════════════ */
    :root {
        --clr-forest     : #2c3e2d;
        --clr-sage       : #6b8f6b;
        --clr-sage-light : #a8c5a0;
        --clr-cream      : #faf8f5;
        --clr-warm       : #f0ebe3;
        --clr-text       : #3a3a3a;
        --clr-text-soft  : #7a7a7a;
        --clr-accent     : #c9a96e;
        --clr-accent-dim : rgba(201, 169, 110, 0.15);
        --clr-border     : #e4e0d8;
        --clr-white      : #ffffff;

        --font-display   : 'Cormorant Garamond', Georgia, serif;
        --font-body      : 'DM Sans', sans-serif;

        --radius-sm      : 8px;
        --radius-md      : 14px;
        --radius-lg      : 20px;
        --shadow-card    : 0 2px 20px rgba(44, 62, 45, 0.07);
        --shadow-hover   : 0 8px 32px rgba(44, 62, 45, 0.14);
        --transition     : 0.3s cubic-bezier(.4,0,.2,1);
    }

    /* ═══════════════════════════════════════════════════════════
       BASE
       ═══════════════════════════════════════════════════════════ */
    body {
        font-family : var(--font-body);
        color       : var(--clr-text);
        background  : var(--clr-cream);
        line-height : 1.65;
        -webkit-font-smoothing: antialiased;
    }
    h1, h2, h3, h4, h5 { font-family: var(--font-display); font-weight: 600; }

    /* ═══════════════════════════════════════════════════════════
       HERO
       ═══════════════════════════════════════════════════════════ */
    .contact-hero {
        position        : relative;
        background      : var(--clr-forest);
        overflow        : hidden;
        padding         : 100px 0 80px;
        text-align      : center;
    }
    /* soft organic blob behind text */
    .contact-hero::before {
        content         : '';
        position        : absolute;
        inset           : -40% -20%;
        background      : radial-gradient(ellipse 60% 55% at 50% 60%,
                            rgba(107,143,107,.35) 0%,
                            transparent 70%);
        pointer-events  : none;
    }
    .contact-hero .container { position: relative; z-index: 1; }
    .contact-hero h1 {
        color       : var(--clr-white);
        font-size   : clamp(2.4rem, 5vw, 3.6rem);
        letter-spacing: .02em;
        margin-bottom: .25rem;
    }
    .contact-hero p {
        color       : var(--clr-sage-light);
        font-size   : 1.05rem;
        max-width   : 520px;
        margin      : 0 auto;
        font-weight : 300;
    }

    /* ═══════════════════════════════════════════════════════════
       ALERTS
       ═══════════════════════════════════════════════════════════ */
    .alert {
        font-family   : var(--font-body);
        border-radius : var(--radius-sm);
        border        : none;
        font-size     : .92rem;
    }
    .alert-danger  { background: #fef2f2; color: #b91c1c; }
    .alert-success { background: #f0fdf4; color: #15803d; }

    /* ═══════════════════════════════════════════════════════════
       CARD – shared
       ═══════════════════════════════════════════════════════════ */
    .lf-card {
        background    : var(--clr-white);
        border        : 1px solid var(--clr-border);
        border-radius : var(--radius-lg);
        box-shadow    : var(--shadow-card);
        transition    : box-shadow var(--transition);
    }
    .lf-card:hover { box-shadow: var(--shadow-hover); }

    /* ═══════════════════════════════════════════════════════════
       FORM
       ═══════════════════════════════════════════════════════════ */
    .form-card h3 {
        font-size     : 1.85rem;
        color         : var(--clr-forest);
        margin-bottom : .15rem;
    }
    .form-card .subtitle {
        color    : var(--clr-text-soft);
        font-size: .93rem;
        margin-bottom: 1.6rem;
    }

    .form-label {
        font-size   : .82rem;
        font-weight : 500;
        text-transform: uppercase;
        letter-spacing: .06em;
        color       : var(--clr-text-soft);
        margin-bottom: .4rem;
    }
    .form-control,
    .form-select {
        font-family   : var(--font-body);
        background    : var(--clr-warm);
        border        : 1.5px solid var(--clr-border);
        border-radius : var(--radius-sm);
        padding       : 11px 14px;
        font-size     : .94rem;
        color         : var(--clr-text);
        transition    : border-color var(--transition), box-shadow var(--transition);
    }
    .form-control:focus,
    .form-select:focus {
        border-color  : var(--clr-sage);
        box-shadow    : 0 0 0 3px rgba(107,143,107,.18);
        background    : var(--clr-white);
        outline       : none;
    }
    textarea.form-control { resize: vertical; min-height: 140px; }

    .char-row {
        display         : flex;
        justify-content : space-between;
        align-items     : center;
        margin-top      : .35rem;
    }
    .char-hint { font-size: .78rem; color: var(--clr-text-soft); }
    .char-count {
        font-size   : .78rem;
        font-weight : 500;
        color       : var(--clr-text-soft);
        transition  : color var(--transition);
    }
    .char-count.ok   { color: var(--clr-sage); }
    .char-count.warn { color: #c9a96e; }

    .btn-send {
        font-family     : var(--font-body);
        background      : var(--clr-forest);
        color           : var(--clr-white);
        border          : none;
        border-radius   : var(--radius-sm);
        padding         : 13px 28px;
        font-size       : .94rem;
        font-weight     : 500;
        letter-spacing  : .04em;
        cursor          : pointer;
        transition      : background var(--transition), transform var(--transition), box-shadow var(--transition);
        width           : 100%;
    }
    .btn-send:hover {
        background      : var(--clr-sage);
        transform       : translateY(-1px);
        box-shadow      : 0 4px 16px rgba(107,143,107,.3);
    }
    .btn-send i { margin-right: .45rem; }

    /* ═══════════════════════════════════════════════════════════
       INFO SIDEBAR
       ═══════════════════════════════════════════════════════════ */
    .info-panel {
        background    : var(--clr-forest);
        border-radius : var(--radius-lg);
        padding       : 2rem;
        color         : var(--clr-white);
    }
    .info-panel h3 {
        font-size     : 1.6rem;
        margin-bottom : 1.6rem;
        color         : var(--clr-white);
    }

    .info-item {
        display        : flex;
        align-items    : flex-start;
        gap            : 1rem;
        margin-bottom  : 1.5rem;
    }
    .info-item:last-of-type { margin-bottom: 0; }

    .info-icon {
        width           : 42px;
        height          : 42px;
        border-radius   : 50%;
        background      : rgba(255,255,255,.1);
        display         : flex;
        align-items     : center;
        justify-content : center;
        font-size       : .95rem;
        flex-shrink     : 0;
        color           : var(--clr-sage-light);
    }
    .info-text h5 {
        font-size     : 1rem;
        margin-bottom : .15rem;
        color         : var(--clr-white);
    }
    .info-text p {
        font-size : .84rem;
        margin    : 0;
        color     : var(--clr-sage-light);
        line-height: 1.5;
    }

    /* social links */
    .social-row {
        display : flex;
        gap     : .6rem;
        margin-top: 1.6rem;
    }
    .social-row a {
        width           : 38px;
        height          : 38px;
        border-radius   : 50%;
        background      : rgba(255,255,255,.08);
        color           : var(--clr-white);
        display         : flex;
        align-items     : center;
        justify-content : center;
        font-size       : .82rem;
        text-decoration : none;
        transition      : background var(--transition), transform var(--transition);
    }
    .social-row a:hover {
        background : rgba(255,255,255,.2);
        transform  : translateY(-2px);
    }

    /* quick-response badge */
    .quick-badge {
        text-align     : center;
        padding        : 1.4rem 1.2rem;
        margin-top     : 1rem;
    }
    .quick-badge-icon {
        width           : 48px;
        height          : 48px;
        border-radius   : 50%;
        background      : var(--clr-accent-dim);
        display         : flex;
        align-items     : center;
        justify-content : center;
        margin          : 0 auto .7rem;
        color           : var(--clr-accent);
        font-size       : 1.1rem;
    }
    .quick-badge h5 {
        font-size     : 1.05rem;
        color         : var(--clr-forest);
        margin-bottom : .2rem;
    }
    .quick-badge p {
        font-size : .82rem;
        color     : var(--clr-text-soft);
        margin    : 0;
    }

    /* ═══════════════════════════════════════════════════════════
       MAP PLACEHOLDER
       ═══════════════════════════════════════════════════════════ */
    .map-section h3 {
        font-size     : 1.7rem;
        color         : var(--clr-forest);
        margin-bottom : 1rem;
    }
    .map-box {
        border-radius : var(--radius-lg);
        border        : 1px solid var(--clr-border);
        background    : var(--clr-warm);
        height        : 280px;
        display       : flex;
        align-items   : center;
        justify-content: center;
        text-align    : center;
    }
    .map-box i { color: var(--clr-sage); font-size: 2.2rem; margin-bottom: .6rem; }
    .map-box h5 { font-size: 1.2rem; color: var(--clr-forest); margin-bottom: .2rem; }
    .map-box p  { font-size: .84rem; color: var(--clr-text-soft); margin-bottom: .7rem; }
    .btn-directions {
        font-family   : var(--font-body);
        border        : 1.5px solid var(--clr-sage);
        color         : var(--clr-sage);
        background    : transparent;
        border-radius : var(--radius-sm);
        padding       : .4rem 1rem;
        font-size     : .82rem;
        font-weight   : 500;
        text-decoration: none;
        transition    : all var(--transition);
    }
    .btn-directions:hover {
        background : var(--clr-sage);
        color      : var(--clr-white);
    }

    /* ═══════════════════════════════════════════════════════════
       FAQ
       ═══════════════════════════════════════════════════════════ */
    .faq-section {
        background    : var(--clr-white);
        border        : 1px solid var(--clr-border);
        border-radius : var(--radius-lg);
        padding       : 2.4rem 2rem;
        margin-top    : 2.2rem;
    }
    .faq-section h3 {
        font-size     : 1.7rem;
        color         : var(--clr-forest);
        margin-bottom : 1.4rem;
    }
    .faq-item {
        border-bottom : 1px solid var(--clr-border);
        padding       : 1rem 0;
    }
    .faq-item:last-child { border-bottom: none; }

    .faq-q {
        font-family    : var(--font-body);
        font-size      : .94rem;
        font-weight    : 500;
        color          : var(--clr-forest);
        background     : none;
        border         : none;
        width          : 100%;
        text-align     : left;
        padding        : 0;
        cursor         : pointer;
        display        : flex;
        justify-content: space-between;
        align-items    : center;
        gap            : 1rem;
    }
    .faq-q .chevron {
        font-size   : .7rem;
        color       : var(--clr-sage);
        transition  : transform var(--transition);
        flex-shrink : 0;
    }
    .faq-q[aria-expanded="true"] .chevron { transform: rotate(180deg); }

    .faq-a {
        font-size   : .87rem;
        color       : var(--clr-text-soft);
        line-height : 1.7;
        padding-top : .6rem;
        overflow    : hidden;
        max-height  : 0;
        transition  : max-height .35s ease, padding .35s ease;
    }
    .faq-a.open { max-height: 200px; padding-top: .6rem; }

    /* ═══════════════════════════════════════════════════════════
       RESPONSIVE
       ═══════════════════════════════════════════════════════════ */
    @media (max-width: 768px) {
        .contact-hero { padding: 70px 0 55px; }
        .lf-card .card-body, .info-panel, .faq-section { padding: 1.4rem; }
    }
    </style>
</head>
<body>

<?php include_once '../includes/header.php'; ?>

<!-- ─── HERO ──────────────────────────────────────────────────────────────── -->
<section class="contact-hero">
    <div class="container">
        <h1>Contact Us</h1>
        <p>We're here to help. Reach out with any questions or concerns.</p>
    </div>
</section>

<!-- ─── MAIN CONTENT ──────────────────────────────────────────────────────── -->
<section class="py-5">
    <div class="container">

        <!-- Alerts -->
        <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <div class="row g-4">

            <!-- ── FORM ───────────────────────────────────────────────── -->
            <div class="col-lg-8">
                <div class="lf-card form-card">
                    <div class="card-body p-4 p-md-5">
                        <h3>Send us a Message</h3>
                        <p class="subtitle">Fill out the form below and we'll get back to you as soon as possible.</p>

                        <form method="POST" action="" id="contactForm" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Your Name *</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                           value="<?php echo htmlspecialchars($name); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Your Email *</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                           value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                            </div>

                            <div class="mt-3">
                                <label for="subject" class="form-label">Subject *</label>
                                <select class="form-select" id="subject" name="subject" required>
                                    <option value="">Select a subject</option>
                                    <option value="General Inquiry"    <?= $subject === 'General Inquiry'    ? 'selected' : '' ?>>General Inquiry</option>
                                    <option value="Order Support"      <?= $subject === 'Order Support'      ? 'selected' : '' ?>>Order Support</option>
                                    <option value="Product Questions"  <?= $subject === 'Product Questions'  ? 'selected' : '' ?>>Product Questions</option>
                                    <option value="Shipping & Delivery"<?= $subject === 'Shipping & Delivery'? 'selected' : '' ?>>Shipping &amp; Delivery</option>
                                    <option value="Returns & Refunds" <?= $subject === 'Returns & Refunds'  ? 'selected' : '' ?>>Returns &amp; Refunds</option>
                                    <option value="Other"             <?= $subject === 'Other'              ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>

                            <div class="mt-3">
                                <label for="message" class="form-label">Your Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="5"
                                          required placeholder="Describe your inquiry in detail…"><?php echo htmlspecialchars($message); ?></textarea>
                                <div class="char-row">
                                    <span class="char-hint">Minimum 10 characters</span>
                                    <span class="char-count" id="charCount">0 / 10</span>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-send">
                                    <i class="fas fa-paper-plane"></i>Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ── SIDEBAR ────────────────────────────────────────────── -->
            <div class="col-lg-4">
                <div class="info-panel">
                    <h3>Contact Information</h3>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="info-text">
                            <h5>Our Address</h5>
                            <p>123 Flower Street<br>Garden City, GC 12345</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-phone"></i></div>
                        <div class="info-text">
                            <h5>Phone Number</h5>
                            <p>+1 (555) 123-4567<br>Mon–Fri: 9 AM – 8 PM EST</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-envelope"></i></div>
                        <div class="info-text">
                            <h5>Email Address</h5>
                            <p>info@laflora.com<br>support@laflora.com</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-clock"></i></div>
                        <div class="info-text">
                            <h5>Business Hours</h5>
                            <p>Mon – Fri: 9 AM – 8 PM<br>Saturday: 10 AM – 6 PM<br>Sunday: 12 PM – 5 PM</p>
                        </div>
                    </div>

                    <div class="social-row">
                        <a href="#" aria-label="Facebook">   <i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram">  <i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter">    <i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Pinterest">  <i class="fab fa-pinterest"></i></a>
                    </div>
                </div>

                <!-- Quick Response -->
                <div class="lf-card quick-badge">
                    <div class="quick-badge-icon"><i class="fas fa-headset"></i></div>
                    <h5>Quick Response</h5>
                    <p>We typically respond within 24–48 hours on business days.</p>
                </div>
            </div>
        </div>

        <!-- ── MAP ──────────────────────────────────────────────────────── -->
        <div class="map-section mt-5">
            <h3>Our Location</h3>
            <div class="map-box">
                <div>
                    <i class="fas fa-map-marked-alt d-block"></i>
                    <h5>Store Location</h5>
                    <p>123 Flower Street, Garden City</p>
                    <a href="https://maps.google.com/?q=123+Flower+Street+Garden+City"
                       target="_blank" rel="noopener" class="btn-directions">
                        <i class="fas fa-directions me-1"></i>Get Directions
                    </a>
                </div>
            </div>
        </div>

        <!-- ── FAQ ──────────────────────────────────────────────────────── -->
        <div class="faq-section">
            <h3>Frequently Asked Questions</h3>

            <?php
            $faqs = [
                ['How long does delivery take?',
                 'Standard delivery takes 3–5 business days. Express delivery is available for next-day delivery in most areas — just order before 2 PM local time.'],
                ['What is your return policy?',
                 'We accept returns within 30 days of delivery. Flowers must be in their original condition and packaging. Contact our support team to start a return.'],
                ['Do you offer international shipping?',
                 'Yes, we ship to over 50 countries worldwide. Shipping rates and delivery times vary by destination — reach out for specific international inquiries.'],
                ['Can I schedule delivery for a specific date?',
                 'Absolutely. During checkout you can choose a delivery date up to 30 days ahead. We recommend ordering at least 48 hours in advance for date-specific deliveries.'],
                ['How do I care for my flowers?',
                 'Change the water every 2 days, trim stems at an angle, keep them away from direct sunlight and heat sources, and remove any wilted flowers or leaves promptly.'],
            ];
            foreach ($faqs as $i => $faq):
            ?>
            <div class="faq-item">
                <button class="faq-q" aria-expanded="<?= $i === 0 ? 'true' : 'false' ?>"
                        data-target="faq-<?= $i ?>">
                    <?= htmlspecialchars($faq[0]) ?>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <div class="faq-a <?= $i === 0 ? 'open' : '' ?>" id="faq-<?= $i ?>">
                    <?= htmlspecialchars($faq[1]) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</section>

<?php include_once '../includes/footer.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
(function () {
    'use strict';

    // ── Form validation ─────────────────────────────────────────────────
    const form    = document.getElementById('contactForm');
    const emailEl = document.getElementById('email');

    function isValidEmail(v) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    }

    form.addEventListener('submit', function (e) {
        const errors = [];
        const name    = document.getElementById('name').value.trim();
        const email   = emailEl.value.trim();
        const subject = document.getElementById('subject').value;
        const message = document.getElementById('message').value.trim();

        if (!name)                      errors.push('Name is required.');
        if (!email)                     errors.push('Email is required.');
        else if (!isValidEmail(email))  errors.push('Please enter a valid email address.');
        if (!subject)                   errors.push('Subject is required.');
        if (!message)                   errors.push('Message is required.');
        else if (message.length < 10)   errors.push('Message must be at least 10 characters.');

        if (errors.length) {
            e.preventDefault();
            alert(errors.join('\n'));
        }
    });

    // ── Character counter ───────────────────────────────────────────────
    const textarea   = document.getElementById('message');
    const charCount  = document.getElementById('charCount');
    const MIN_CHARS  = 10;

    function updateCount() {
        const len = textarea.value.length;
        charCount.textContent = len + ' / ' + MIN_CHARS;
        charCount.className   = 'char-count ' + (len === 0 ? '' : len < MIN_CHARS ? 'warn' : 'ok');
    }
    textarea.addEventListener('input', updateCount);
    updateCount();

    // ── FAQ accordion ───────────────────────────────────────────────────
    document.querySelectorAll('.faq-q').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target  = document.getElementById(this.dataset.target);
            const isOpen  = target.classList.contains('open');

            // close all
            document.querySelectorAll('.faq-a').forEach(function (a) { a.classList.remove('open'); });
            document.querySelectorAll('.faq-q').forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });

            // toggle clicked
            if (!isOpen) {
                target.classList.add('open');
                this.setAttribute('aria-expanded', 'true');
            }
        });
    });

})();
</script>
</body>
</html>