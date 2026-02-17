<?php
// contact.php
require_once 'includes/header.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    // In a real application, you would save this to database or send email
    $success = true; // Simulated success
    
    if ($success) {
        $alert_message = "Thank you for your message! We'll get back to you soon.";
        $alert_type = "success";
    } else {
        $alert_message = "There was an error sending your message. Please try again.";
        $alert_type = "danger";
    }
}
?>

<!-- Contact Header -->
<section class="contact-header py-5" style="background: linear-gradient(135deg, #e777d2 0%, #e447a8 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <h1 class="display-5 fw-bold mb-3" style="color: #f7f9fa;">Contact Us</h1>
                <p class="lead mb-4" style="color: #f7f7f2;">
                    Have questions? We're here to help. Get in touch with our friendly team.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Contact Content -->
<section class="py-5">
    <div class="container">
        <!-- Alert Message -->
        <?php if(isset($alert_message)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $alert_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Contact Form -->
            <div class="col-lg-8 mb-5">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 p-md-5">
                        <h3 class="card-title mb-4 fw-bold">Send us a Message</h3>
                        
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label fw-semibold">Your Name *</label>
                                    <input type="text" class="form-control form-control-lg" id="name" name="name" required
                                           placeholder="Enter your full name">
                                    <div class="invalid-feedback">
                                        Please enter your name.
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label fw-semibold">Your Email *</label>
                                    <input type="email" class="form-control form-control-lg" id="email" name="email" required
                                           placeholder="Enter your email address">
                                    <div class="invalid-feedback">
                                        Please enter a valid email address.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="subject" class="form-label fw-semibold">Subject *</label>
                                <input type="text" class="form-control form-control-lg" id="subject" name="subject" required
                                       placeholder="What is this regarding?">
                                <div class="invalid-feedback">
                                    Please enter a subject.
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="message" class="form-label fw-semibold">Your Message *</label>
                                <textarea class="form-control form-control-lg" id="message" name="message" rows="6" required
                                          placeholder="Tell us how we can help you..."></textarea>
                                <div class="invalid-feedback">
                                    Please enter your message.
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg px-5 py-3 fw-bold">
                                    <i class="fas fa-paper-plane me-2"></i>Send Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="col-lg-4">
                <!-- Contact Card -->
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h3 class="card-title mb-4 fw-bold">Contact Information</h3>
                        
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="icon-container bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-map-marker-alt fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="fw-semibold">Our Address</h5>
                                <p class="text-muted mb-0">Street 123, Sangkat Tonle Bassac<br>Khan Chamkarmon, Phnom Penh<br>Cambodia</p>
                            </div>
                        </div>
                        
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="icon-container bg-success text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-phone fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="fw-semibold">Phone Number</h5>
                                <p class="text-muted mb-0 fw-bold">+855 23 456 789</p>
                                <p class="text-muted mb-0">Mon-Sun: 8:00 AM - 8:00 PM</p>
                            </div>
                        </div>
                        
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="icon-container bg-info text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-envelope fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="fw-semibold">Email Address</h5>
                                <p class="text-muted mb-0 fw-bold">info@laflora.com</p>
                                <p class="text-muted mb-0">support@laflora.com</p>
                            </div>
                        </div>
                        
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="icon-container bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-clock fa-lg"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h5 class="fw-semibold">Business Hours</h5>
                                <p class="text-muted mb-0">Monday - Friday: 8AM - 8PM</p>
                                <p class="text-muted mb-0">Saturday - Sunday: 9AM - 6PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Social Links Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4 text-center">
                        <h4 class="card-title mb-4 fw-bold">Follow Us</h4>
                        <p class="text-muted mb-4">Stay connected for updates and special offers</p>
                        
                        <div class="d-flex justify-content-center mb-4">
                            <a href="https://facebook.com" class="social-link facebook mx-2" target="_blank" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://instagram.com" class="social-link instagram mx-2" target="_blank" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="https://twitter.com" class="social-link twitter mx-2" target="_blank" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://pinterest.com" class="social-link pinterest mx-2" target="_blank" title="Pinterest">
                                <i class="fab fa-pinterest-p"></i>
                            </a>
                            <a href="https://t.me" class="social-link telegram mx-2" target="_blank" title="Telegram">
                                <i class="fab fa-telegram-plane"></i>
                            </a>
                        </div>
                        
                        <div class="alert alert-light border" role="alert">
                            <i class="fas fa-bolt text-warning me-2"></i>
                            <span class="fw-semibold">Quick Response:</span> We typically reply within 2 hours
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-5">
                            <h2 class="fw-bold mb-2" style="color: #2c3e50;">Frequently Asked Questions</h2>
                            <p class="text-muted">Quick answers to common questions</p>
                        </div>
                        
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="faq-item p-3 rounded-3 border">
                                    <h5 class="fw-semibold mb-2">
                                        <i class="fas fa-shipping-fast me-2 text-primary"></i>
                                        What are your delivery options?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        We offer same-day delivery for orders placed before 2PM. Standard delivery takes 1-2 business days. Free delivery on orders over $50.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="faq-item p-3 rounded-3 border">
                                    <h5 class="fw-semibold mb-2">
                                        <i class="fas fa-undo me-2 text-success"></i>
                                        What is your return policy?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        We accept returns within 7 days if flowers arrive damaged. Contact us within 24 hours of delivery with photos for fastest resolution.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="faq-item p-3 rounded-3 border">
                                    <h5 class="fw-semibold mb-2">
                                        <i class="fas fa-calendar-alt me-2 text-info"></i>
                                        Can I schedule delivery for a specific date?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        Yes! During checkout, you can select your preferred delivery date up to 30 days in advance.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="faq-item p-3 rounded-3 border">
                                    <h5 class="fw-semibold mb-2">
                                        <i class="fas fa-credit-card me-2 text-warning"></i>
                                        What payment methods do you accept?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        We accept Visa, MasterCard, ABA Bank, ACLEDA Bank, and cash on delivery. All payments are securely processed.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="faq-item p-3 rounded-3 border">
                                    <h5 class="fw-semibold mb-2">
                                        <i class="fas fa-leaf me-2" style="color: #28a745;"></i>
                                        Are your flowers locally sourced?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        Yes! We work with local Cambodian growers to ensure fresh, beautiful flowers while supporting our community.
                                    </p>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="faq-item p-3 rounded-3 border">
                                    <h5 class="fw-semibold mb-2">
                                        <i class="fas fa-gift me-2" style="color: #dc3545;"></i>
                                        Do you offer gift wrapping?
                                    </h5>
                                    <p class="text-muted mb-0">
                                        Yes! We offer complimentary gift wrapping and can include a personalized message with your order.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-5">
                            <a href="faq.php" class="btn btn-outline-primary btn-lg px-4">
                                <i class="fas fa-question-circle me-2"></i>View All FAQs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<style>
    /* Custom Styles for Contact Page */
    .icon-container {
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Social Links Styles */
    .social-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        text-decoration: none;
        font-size: 20px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .social-link i {
        position: relative;
        z-index: 2;
        transition: transform 0.3s ease;
    }
    
    /* Individual Platform Colors */
    .social-link.facebook {
        background: #1877f2;
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(24, 119, 242, 0.3);
    }
    
    .social-link.instagram {
        background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(225, 48, 108, 0.3);
    }
    
    .social-link.twitter {
        background: #1da1f2;
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(29, 161, 242, 0.3);
    }
    
    .social-link.pinterest {
        background: #e60023;
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(230, 0, 35, 0.3);
    }
    
    .social-link.telegram {
        background: #0088cc;
        color: #ffffff;
        box-shadow: 0 4px 15px rgba(0, 136, 204, 0.3);
    }
    
    /* Hover Effects */
    .social-link:hover {
        transform: translateY(-5px) scale(1.1);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }
    
    .social-link:hover i {
        transform: rotate(360deg);
    }
    
    /* Ripple Effect */
    .ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.6);
        transform: scale(0);
        animation: ripple-animation 0.6s ease-out;
        pointer-events: none;
    }
    
    @keyframes ripple-animation {
        to {
            transform: scale(2);
            opacity: 0;
        }
    }
    
    /* Pulse Animation */
    .pulse {
        animation: pulse 1s ease-in-out;
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.15);
        }
    }
    
    /* FAQ Item Hover */
    .faq-item {
        transition: all 0.3s ease;
        height: 100%;
    }
    
    .faq-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border-color: #0d6efd !important;
    }
    
    /* Map Placeholder */
    .map-placeholder {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .social-link {
            width: 45px;
            height: 45px;
            font-size: 18px;
        }
        
        .faq-item {
            margin-bottom: 15px;
        }
    }
    .lead.text-muted.mb-4{
        font-size: 1.25rem;
        font-weight: bold;
    }
</style>

<script>
// Enhanced Social Links Interaction
document.addEventListener('DOMContentLoaded', function() {
    const socialLinks = document.querySelectorAll('.social-link');
    
    // Add ripple effect on click
    socialLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Create ripple element
            const ripple = document.createElement('span');
            ripple.classList.add('ripple');
            this.appendChild(ripple);
            
            // Position ripple at click location
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            
            // Remove ripple after animation
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // FAQ item click effect
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        item.addEventListener('click', function() {
            this.classList.toggle('active');
        });
    });
    
    // Social links analytics tracking
    socialLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const platform = this.classList[1];
            console.log(`Social link clicked: ${platform}`);
            
            // You can send this to your analytics service
            // Example: gtag('event', 'social_click', { platform: platform });
        });
    });
    
    // Add animation to social links on page load
    socialLinks.forEach((link, index) => {
        link.style.opacity = '0';
        link.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            link.style.transition = 'all 0.5s ease';
            link.style.opacity = '1';
            link.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Optional: Pulse animation for attention
function pulseSocialLinks() {
    const socialLinks = document.querySelectorAll('.social-link');
    
    socialLinks.forEach((link, index) => {
        setTimeout(() => {
            link.classList.add('pulse');
            setTimeout(() => {
                link.classList.remove('pulse');
            }, 1000);
        }, index * 200);
    });
}

// Initialize pulse animation after page load
setTimeout(pulseSocialLinks, 2000);
</script>

<?php
require_once 'includes/footer.php';
?>