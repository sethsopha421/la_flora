<?php
// includes/footer.php
?>
    </main>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <h3 class="footer-heading">LaFlora</h3>
                    <p>Bringing beauty and joy through nature's finest flowers. We deliver happiness right to your doorstep.</p>
                    <div class="social-icons mt-4">
                        <a href="https://web.facebook.com/?_rdc=1&_rdr#" target="_blank" class="social-link facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="https://x.com/i/flow/single_sign_on" target="_blank" class="social-link twitter"><i class="fab fa-twitter"></i></a>
                        <a href="https://www.instagram.com/sethsopha2828/?hl=en" target="_blank" class="social-link instagram"><i class="fab fa-instagram"></i></a>
                        <a href="https://www.pinterest.com/pin/487373990945970726/" target="_blank" class="social-link pinterest"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-6 mb-4">
                    <h4 class="footer-heading">Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="shop.php">Shop</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h4 class="footer-heading">Categories</h4>
                    <ul class="footer-links">
                        <li><a href="shop.php?category=roses">Roses</a></li>
                        <li><a href="shop.php?category=bouquets">Bouquets</a></li>
                        <li><a href="shop.php?category=wedding">Wedding Flowers</a></li>
                        <li><a href="shop.php?category=seasonal">Seasonal Flowers</a></li>
                    </ul>
                </div>
                
                <div class="col-lg-3 col-md-6 mb-4">
                    <h4 class="footer-heading">Contact Info</h4>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt me-2"></i> 282 Flower Street, Phnom Pehn City</li>
                        <li><i class="fas fa-phone me-2"></i> +855 885 626 421</li>
                        <li><i class="fas fa-envelope me-2"></i> sopha2828@gmail.com</li>
                        <li><i class="fas fa-clock me-2"></i> Mon-Sat: 8AM - 8PM</li>
                    </ul>
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <div class="copyright">
                        <p>&copy; <?php echo date('Y'); ?> LaFlora. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- MDBootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/script.js"></script>
    
    <?php if(basename($_SERVER['PHP_SELF']) == 'cart.php'): ?>
        <script src="assets/js/cart.js"></script>
    <?php endif; ?>
    
    <?php if(basename($_SERVER['PHP_SELF']) == 'checkout.php'): ?>
        <script src="https://js.stripe.com/v3/"></script>
        <script src="assets/js/checkout.js"></script>
    <?php endif; ?>
    
    <style>
        /* ===== FOOTER STYLES ===== */
        .footer {
            background: linear-gradient(135deg, #2c3e50 0%, #1a252f 100%);
            color: #fff;
            padding: 70px 0 20px;
            margin-top: auto;
            position: relative;
            overflow: hidden;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #8B4513 0%, #4CAF50 100%);
        }
        
        .footer-heading {
            color: #fff;
            font-weight: 700;
            margin-bottom: 25px;
            position: relative;
            padding-bottom: 10px;
            font-family: 'Poppins', sans-serif;
        }
        
        .footer-heading::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #8B4513, #4CAF50);
            border-radius: 2px;
        }
        
        .footer h3.footer-heading {
            font-size: 2rem;
            margin-bottom: 20px;
        }
        
        .footer h3.footer-heading::after {
            width: 70px;
        }
        
        .footer p {
            color: #bdc3c7;
            line-height: 1.8;
            margin-bottom: 0;
            font-size: 0.95rem;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
        }
        
        .footer-links li:last-child {
            margin-bottom: 0;
        }
        
        .footer-links a {
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            font-size: 0.95rem;
            position: relative;
            padding-left: 0;
        }
        
        .footer-links a:hover {
            color: #fff;
            transform: translateX(5px);
        }
        
        .footer-links a::before {
            content: '→';
            position: absolute;
            left: -15px;
            opacity: 0;
            transition: all 0.3s ease;
            color: #13138b;
        }
        
        .footer-links a:hover::before {
            opacity: 1;
            left: -10px;
        }
        
        .footer-links i {
            color: #8B4513;
            width: 20px;
            text-align: center;
            margin-right: 10px;
            font-size: 0.9rem;
        }
        
        .social-icons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }
        
        .social-icons a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
        }
        
        .social-icons a:hover {
            background: linear-gradient(135deg, #8B4513, #4CAF50);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.4);
        }
        
        .copyright {
            text-align: center;
            padding-top: 30px;
            margin-top: 50px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #95a5a6;
            font-size: 0.9rem;
        }
        
        .copyright p {
            margin: 0;
        }
        
        /* Footer background pattern */
        .footer .container {
            position: relative;
            z-index: 1;
        }
        
        .footer::after {
            content: '';
            position: absolute;
            bottom: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><path fill="%234CAF50" opacity="0.05" d="M50,20 C65,10 85,15 90,30 C95,45 80,60 65,70 C50,80 30,75 20,60 C10,45 25,30 35,20 C45,10 35,30 50,20 Z"/></svg>') repeat;
            opacity: 0.1;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .footer {
                padding: 50px 0 20px;
            }
            
            .footer .row > div {
                margin-bottom: 40px;
            }
        }
        
        @media (max-width: 768px) {
            .footer-heading {
                font-size: 1.3rem;
            }
            
            .footer h3.footer-heading {
                font-size: 1.5rem;
            }
            
            .footer-links a {
                font-size: 0.9rem;
            }
            
            .footer p {
                font-size: 0.9rem;
            }
            
            .social-icons {
                justify-content: center;
            }
        }
        
        @media (max-width: 576px) {
            .footer {
                text-align: center;
            }
            
            .footer-heading::after {
                left: 50%;
                transform: translateX(-50%);
            }
            
            .footer-links li {
                justify-content: center;
            }
            
            .footer-links a:hover::before {
                display: none;
            }
            
            .footer-links a:hover {
                transform: none;
            }
        }
    </style>
</body>
</html>