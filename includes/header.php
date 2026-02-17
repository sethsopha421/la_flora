<?php
    require_once 'session.php';      // First - start session
    require_once 'database.php';     // Second - database connection
// includes/header.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    
}

// Check if user is logged in
// Admin session: admin_logged_in, admin_name, admin_email, admin_id
// User session: user_id, username
$isLoggedIn = isset($_SESSION['user_id']) || isset($_SESSION['admin_id']);
$username = '';
if (isset($_SESSION['admin_name'])) {
    $username = $_SESSION['admin_name'];
} elseif (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
}
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;

// Check if user is admin - based on login.php session variables
// Admin login sets: $_SESSION['admin_logged_in'], $_SESSION['admin_id'], $_SESSION['is_admin']
// Regular user login sets: $_SESSION['user_id'], $_SESSION['username']
$isAdmin = false;
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // User logged in through admin/login.php
    $isAdmin = true;
} elseif (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    // Alternative check if is_admin flag is set
    $isAdmin = true;
} elseif ($isLoggedIn && isset($_SESSION['user_id'])) {
    // Fallback: Check database for user role
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        $isAdmin = ($userData['role'] === 'admin');
    }
    $stmt->close();
}

// Handle search form submission
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['search'])) {
    $searchTerm = trim($_GET['search']);
    // Store search term in session for persistence
    $_SESSION['last_search'] = $searchTerm;
}

// Start session for cart


// Calculate cart item count
$cart_count = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += $item['quantity'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaFlora - Beautiful Flowers for Every Occasion</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- MDBootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/mdb-ui-kit/6.4.0/mdb.min.css" rel="stylesheet">
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/logos/favicon.ico">
    
    <style>
        /* Search Bar Styles */
        .search-container {
            position: relative;
            max-width: 400px;
            width: 100%;
        }
        
        .search-form {
            display: flex;
            width: 100%;
        }
        
        .search-input {
            flex: 1;
            border: 2px solid #e0e0e0;
            border-right: none;
            border-radius: 25px 0 0 25px;
            padding: 8px 20px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #4CAF50;
            box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.1);
        }
        
        .search-btn {
            background: linear-gradient(135deg, #3113b4, #0a2b9a);
            border: 2px solid #060683;
            border-left: none;
            border-radius: 0 25px 25px 0;
            color: white;
            padding: 8px 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background: linear-gradient(135deg, #45a049, #3d8b40);
            transform: translateY(-1px);
        }
        
        .search-btn i {
            font-size: 16px;
        }
        
        /* Mobile Search */
        .mobile-search-btn {
            display: none;
            background: none;
            border: none;
            color: #4CAF50;
            font-size: 18px;
            margin-right: 10px;
        }
        
        .mobile-search-form {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            padding: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .mobile-search-form.active {
            display: block;
        }
        
        /* Dropdown Search Suggestions */
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #e0e0e0;
            border-radius: 0 0 10px 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: none;
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .search-suggestions.active {
            display: block;
        }
        
        .suggestion-item {
            padding: 10px 15px;
            border-bottom: 1px solid #f5f5f5;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .suggestion-item:hover {
            background: #f8f9fa;
        }
        
        .suggestion-item:last-child {
            border-bottom: none;
        }
        
        .suggestion-category {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .suggestion-name {
            font-weight: 500;
            color: #333;
        }
        
        .no-results {
            padding: 15px;
            text-align: center;
            color: #666;
            font-style: italic;
        }
        
        /* Responsive Design */
        @media (max-width: 991px) {
            .search-container.desktop {
                display: none;
            }
            
            .mobile-search-btn {
                display: block;
            }
        }
        
        @media (min-width: 992px) {
            .search-container {
                margin: 0 20px;
            }
        }
        
        /* Quick Search Tags */
        .quick-search-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .quick-tag {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 20px;
            padding: 4px 12px;
            font-size: 12px;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .quick-tag:hover {
            background: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        /* Search Icon Animation */
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .search-btn:active i {
            animation: pulse 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-seedling me-2"></i>La<span style="color: #4CAF50;">Flora</span>
            </a>
            
            <!-- Mobile Search Button -->
            <button class="mobile-search-btn" type="button" id="mobileSearchBtn">
                <i class="fas fa-search"></i>
            </button>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="shop.php">Shop</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                    <?php if($isLoggedIn): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="user/dashboard.php">Dashboard</a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- Desktop Search -->
                <div class="search-container desktop">
                    <form method="GET" action="shop.php" class="search-form" id="searchForm">
                        <input type="text" 
                               class="search-input" 
                               name="search" 
                               id="searchInput"
                               placeholder="Search flowers"
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                               autocomplete="off">
                        <button type="submit" class="search-btn">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <!-- Search Suggestions Dropdown -->
                    <div class="search-suggestions" id="searchSuggestions"></div>
                </div>
                
                <div class="d-flex align-items-center">
                    <?php if($isLoggedIn): ?>
                        <div class="dropdown me-3">
                            <button class="btn btn-outline-primary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($username); ?>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="user/profile.php"><i class="fas fa-user-circle me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="user/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                <?php if($isAdmin): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-success" href="admin/index.php"><i class="fas fa-shield-alt me-2"></i>Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="user/login.php" class="btn btn-outline-primary me-2">Login</a>
                        <a href="user/signup.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                    
                    <?php if($isAdmin): ?>
                        <a href="admin/index.php" class="btn btn-success me-2" title="Admin Panel">
                            <i class="fas fa-shield-alt"></i>
                            <span class="d-none d-lg-inline ms-1">Admin</span>
                        </a>
                    <?php endif; ?>
                    
                    <a href="cart.php" class="btn btn-outline-dark position-relative ms-2">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if($cartCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cartCount; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Mobile Search Form -->
        <div class="mobile-search-form" id="mobileSearchForm">
            <form method="GET" action="shop.php" class="search-form">
                <input type="text" 
                       class="search-input" 
                       name="search" 
                       placeholder="Search flowers, bouquets..."
                       value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i>
                </button>
            </form>
            
            <!-- Quick Search Tags for Mobile -->
            <div class="quick-search-tags mt-3">
                <span class="quick-tag" data-search="roses">Roses</span>
                <span class="quick-tag" data-search="tulips">Tulips</span>
                <span class="quick-tag" data-search="bouquet">Bouquet</span>
                <span class="quick-tag" data-search="wedding">Wedding</span>
                <span class="quick-tag" data-search="anniversary">Anniversary</span>
                <span class="quick-tag" data-search="birthday">Birthday</span>
            </div>
        </div>
    </nav>
    
    <!-- JavaScript for Search Functionality -->
    <script>
        // Mobile Search Toggle
        document.getElementById('mobileSearchBtn').addEventListener('click', function() {
            const mobileSearchForm = document.getElementById('mobileSearchForm');
            mobileSearchForm.classList.toggle('active');
            
            // Focus on search input when opened
            if (mobileSearchForm.classList.contains('active')) {
                setTimeout(() => {
                    mobileSearchForm.querySelector('input[type="text"]').focus();
                }, 100);
            }
        });
        
        // Close mobile search when clicking outside
        document.addEventListener('click', function(event) {
            const mobileSearchBtn = document.getElementById('mobileSearchBtn');
            const mobileSearchForm = document.getElementById('mobileSearchForm');
            
            if (!mobileSearchBtn.contains(event.target) && !mobileSearchForm.contains(event.target)) {
                mobileSearchForm.classList.remove('active');
            }
        });
        
        // Search Suggestions
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        
        if (searchInput) {
            // Show/hide suggestions on focus
            searchInput.addEventListener('focus', function() {
                if (this.value.trim() !== '') {
                    fetchSuggestions(this.value);
                }
            });
            
            // Hide suggestions when clicking outside
            document.addEventListener('click', function(event) {
                if (!searchInput.contains(event.target) && !searchSuggestions.contains(event.target)) {
                    searchSuggestions.classList.remove('active');
                }
            });
            
            // Fetch suggestions as user types
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                if (query.length >= 2) {
                    fetchSuggestions(query);
                } else {
                    searchSuggestions.classList.remove('active');
                }
            });
            
            // Handle Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && this.value.trim() !== '') {
                    document.getElementById('searchForm').submit();
                }
            });
        }
        
        // Quick search tags
        document.querySelectorAll('.quick-tag').forEach(tag => {
            tag.addEventListener('click', function() {
                const searchTerm = this.getAttribute('data-search');
                const form = this.closest('form') || document.getElementById('searchForm');
                const input = form.querySelector('input[name="search"]');
                
                if (input) {
                    input.value = searchTerm;
                    form.submit();
                }
            });
        });
        
        // Function to fetch search suggestions
        function fetchSuggestions(query) {
            fetch('includes/search_suggestions.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        let html = '';
                        data.forEach(item => {
                            html += `
                                <div class="suggestion-item" onclick="selectSuggestion('${item.name}')">
                                    <div class="suggestion-category">${item.category}</div>
                                    <div class="suggestion-name">${item.name}</div>
                                </div>
                            `;
                        });
                        searchSuggestions.innerHTML = html;
                        searchSuggestions.classList.add('active');
                    } else {
                        searchSuggestions.innerHTML = '<div class="no-results">No matches found</div>';
                        searchSuggestions.classList.add('active');
                    }
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                    searchSuggestions.classList.remove('active');
                });
        }
        
        // Function to select a suggestion
        function selectSuggestion(term) {
            searchInput.value = term;
            searchSuggestions.classList.remove('active');
            document.getElementById('searchForm').submit();
        }
        
        // Clear search on page load if coming from non-search page
        window.addEventListener('load', function() {
            if (!window.location.search.includes('search=')) {
                const searchInputs = document.querySelectorAll('input[name="search"]');
                searchInputs.forEach(input => {
                    input.value = '';
                });
            }
        });
    </script>
    
    <!-- Main Content -->
    <main class="pt-5 mt-5">