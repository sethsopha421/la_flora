// assets/js/script.js

$(document).ready(function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Add to cart functionality
    $('.add-to-cart').on('click', function(e) {
        e.preventDefault();
        
        const productId = $(this).data('id');
        const productName = $(this).closest('.product-card').find('.product-title').text();
        const productPrice = $(this).closest('.product-card').find('.product-price').text();
        
        // Show loading state
        const button = $(this);
        const originalText = button.html();
        button.html('<i class="fas fa-spinner fa-spin"></i>');
        button.prop('disabled', true);
        
        // Simulate API call
        setTimeout(() => {
            // In a real app, you would make an AJAX call here
            updateCartCount(1);
            
            // Show success message
            showToast('Success!', productName + ' added to cart', 'success');
            
            // Reset button
            button.html(originalText);
            button.prop('disabled', false);
        }, 500);
    });
    
    // Price range slider
    $('#priceRange').on('input', function() {
        const value = $(this).val();
        $('#priceValue').text('$0 - $' + value);
    });
    
    // Product quantity controls
    $('.quantity-btn').on('click', function() {
        const input = $(this).siblings('.quantity-input');
        let value = parseInt(input.val());
        
        if ($(this).hasClass('increment')) {
            value = isNaN(value) ? 1 : value + 1;
        } else {
            value = isNaN(value) ? 1 : value - 1;
            if (value < 1) value = 1;
        }
        
        input.val(value);
    });
    
    // Form validation
    $('form').on('submit', function(e) {
        let valid = true;
        
        $(this).find('input[required], select[required], textarea[required]').each(function() {
            if (!$(this).val().trim()) {
                valid = false;
                $(this).addClass('is-invalid');
                
                // Add error message if not exists
                if (!$(this).next('.invalid-feedback').length) {
                    $(this).after('<div class="invalid-feedback">This field is required</div>');
                }
            } else {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            }
        });
        
        // Email validation
        const emailInput = $(this).find('input[type="email"]');
        if (emailInput.length && emailInput.val()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailInput.val())) {
                valid = false;
                emailInput.addClass('is-invalid');
                
                if (!emailInput.next('.invalid-feedback').length) {
                    emailInput.after('<div class="invalid-feedback">Please enter a valid email address</div>');
                }
            }
        }
        
        if (!valid) {
            e.preventDefault();
            showToast('Error', 'Please fill all required fields correctly', 'danger');
        }
    });
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(e) {
        if (this.hash !== "") {
            e.preventDefault();
            
            const hash = this.hash;
            $('html, body').animate({
                scrollTop: $(hash).offset().top - 70
            }, 800);
        }
    });
    
    // Newsletter form submission
    $('#newsletterForm').on('submit', function(e) {
        e.preventDefault();
        
        const email = $(this).find('input[type="email"]').val();
        
        // Simulate API call
        setTimeout(() => {
            showToast('Thank you!', 'You have been subscribed to our newsletter', 'success');
            $(this).find('input[type="email"]').val('');
        }, 500);
    });
    
    // Image lazy loading
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.add('loaded');
                    observer.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            imageObserver.observe(img);
        });
    }
});

// Helper functions
function updateCartCount(change) {
    const cartCountElement = $('.cart-count');
    let currentCount = parseInt(cartCountElement.text()) || 0;
    
    if (change) {
        currentCount += change;
    } else {
        // Get count from server in real app
        currentCount = 0;
    }
    
    cartCountElement.text(currentCount);
    
    // Show/hide badge
    if (currentCount > 0) {
        cartCountElement.show();
    } else {
        cartCountElement.hide();
    }
}

function showToast(title, message, type = 'info') {
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <strong>${title}</strong><br>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    `;
    
    // Add to container
    $('#toastContainer').append(toastHtml);
    
    // Initialize and show
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement, {
        delay: 3000
    });
    toast.show();
    
    // Remove after hidden
    toastElement.addEventListener('hidden.bs.toast', function () {
        $(this).remove();
    });
}

// Cart page specific functions
if (window.location.pathname.includes('cart.php')) {
    $(document).ready(function() {
        // Update quantity
        $('.update-quantity').on('click', function() {
            const itemId = $(this).data('id');
            const input = $('#quantity-' + itemId);
            const newQuantity = parseInt(input.val());
            
            if (newQuantity > 0) {
                updateCartItem(itemId, newQuantity);
            }
        });
        
        // Remove item
        $('.remove-item').on('click', function() {
            const itemId = $(this).data('id');
            
            if (confirm('Are you sure you want to remove this item?')) {
                removeCartItem(itemId);
            }
        });
    });
    
    function updateCartItem(itemId, quantity) {
        // In real app, make AJAX call
        console.log(`Updating item ${itemId} to quantity ${quantity}`);
        location.reload(); // Reload to show updated totals
    }
    
    function removeCartItem(itemId) {
        // In real app, make AJAX call
        console.log(`Removing item ${itemId}`);
        location.reload(); // Reload to show updated cart
    }
}