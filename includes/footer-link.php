<?php
include_once( __DIR__ . "/../config/connect.php");
?>
<!-- JS
============================================ -->
<!--jquery min js-->
<script src="<?= $site ?>assets/js/vendor/jquery-3.4.1.min.js"></script>
<!--popper min js-->
<script src="<?= $site ?>assets/js/popper.js"></script>
<!--bootstrap min js-->
<script src="<?= $site ?>assets/js/bootstrap.min.js"></script>
<!--owl carousel min js-->
<script src="<?= $site ?>assets/js/owl.carousel.min.js"></script>
<!--slick min js-->
<script src="<?= $site ?>assets/js/slick.min.js"></script>
<!--magnific popup min js-->
<script src="<?= $site ?>assets/js/jquery.magnific-popup.min.js"></script>
<!--jquery countdown min js-->
<script src="<?= $site ?>assets/js/jquery.countdown.js"></script>
<!--jquery ui min js-->
<script src="<?= $site ?>assets/js/jquery.ui.js"></script>
<!--jquery elevatezoom min js-->
<script src="<?= $site ?>assets/js/jquery.elevatezoom.js"></script>
<!--isotope packaged min js-->
<script src="<?= $site ?>assets/js/isotope.pkgd.min.js"></script>
<!-- Plugins JS -->
<script src="<?= $site ?>assets/js/plugins.js"></script>

<!-- Main JS -->
<script src="<?= $site ?>assets/js/main.js"></script>

<!-- Custom JS for E-commerce -->
<script>
// Global cart functions
function updateCartCount() {
    $.ajax({
        url: '<?= $site ?>ajax/get-cart-count.php',
        method: 'GET',
        success: function(response) {
            if(response && response.count !== undefined) {
                $('.item_count').text(response.count);
            }
        }
    });
}

function showNotification(message, type) {
    // Remove existing notifications
    $('.custom-notification').remove();
    
    var bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
    var icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    var notification = $('<div class="custom-notification alert ' + bgClass + ' text-white alert-dismissible fade show fixed-top mt-5 mx-auto" style="max-width: 500px; z-index: 9999;">' +
        '<i class="fa ' + icon + ' me-2"></i>' + message +
        '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
        '</div>');
    
    $('body').append(notification);
    
    // Auto remove after 3 seconds
    setTimeout(function() {
        notification.alert('close');
    }, 3000);
}

// Initialize cart count on page load
$(document).ready(function() {
    // Initialize cart count
    updateCartCount();
    
    // Add to cart with variant handling
    
    
    // Wishlist functionality
    $(document).on('click', '.add-to-wishlist', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var productId = button.data('product-id');
        
        $.ajax({
            url: '<?= $site ?>ajax/add-to-wishlist.php',
            method: 'POST',
            data: {
                product_id: productId,
                action: 'add_to_wishlist'
            },
            beforeSend: function() {
                button.addClass('processing');
            },
            success: function(response) {
                if (response.success) {
                    button.find('span').removeClass('pe-7s-like').addClass('fa fa-heart text-danger');
                    button.attr('title', 'Remove from Wishlist');
                    showNotification(response.message || 'Added to wishlist!', 'success');
                } else {
                    if (response.message && response.message.includes('login')) {
                        // Store current URL for redirect back
                        sessionStorage.setItem('redirect_url', window.location.href);
                        window.location.href = '<?= $site ?>login/';
                    } else {
                        showNotification(response.message || 'Error adding to wishlist', 'error');
                    }
                }
                button.removeClass('processing');
            },
            error: function() {
                showNotification('Error adding to wishlist', 'error');
                button.removeClass('processing');
            }
        });
    });
    
    // Compare functionality
    $(document).on('click', '.add-to-compare', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var productId = button.data('product-id');
        
        $.ajax({
            url: '<?= $site ?>ajax/add-to-compare.php',
            method: 'POST',
            data: {
                product_id: productId,
                action: 'add_to_compare'
            },
            success: function(response) {
                if(response.success) {
                    showNotification(response.message || 'Added to compare!', 'success');
                } else {
                    showNotification(response.message || 'Error adding to compare', 'error');
                }
            }
        });
    });
    
    // Quick view modal
    $(document).on('click', '[data-bs-target="#quickViewModal"]', function(e) {
        var productId = $(this).data('product-id');
        var modal = $('#quickViewModal');
        
        // Show loading state
        modal.find('.modal-body').html('<div class="text-center py-5"><div class="spinner-border"></div></div>');
        
        $.ajax({
            url: '<?= $site ?>ajax/quick-view.php',
            method: 'GET',
            data: { product_id: productId },
            success: function(response) {
                modal.find('.modal-body').html(response);
                
                // Reinitialize any plugins inside modal
                if(typeof $.fn.elevateZoom !== 'undefined') {
                    modal.find('.zoom_01').elevateZoom({
                        gallery:'gallery_01', 
                        cursor: 'pointer', 
                        galleryActiveClass: 'active', 
                        imageCrossfade: true
                    });
                }
            },
            error: function() {
                modal.find('.modal-body').html('<div class="alert alert-danger">Failed to load product details.</div>');
            }
        });
    });
    
    // Close dropdowns when clicking outside
    $(document).click(function(e) {
        if (!$(e.target).closest('.header_account_area').length) {
            $('#miniCartDropdown').hide();
        }
    });
});

// Initialize product-specific features
function initProductFeatures() {
    // Product image zoom
    if(typeof $.fn.elevateZoom !== 'undefined') {
        $('.zoom_01').elevateZoom({
            gallery:'gallery_01', 
            cursor: 'pointer', 
            galleryActiveClass: 'active', 
            imageCrossfade: true
        });
    }
    
    // Product variant selection
    $('input[name="color"], input[name="size"]').change(function() {
        var color = $('input[name="color"]:checked').val();
        var size = $('input[name="size"]:checked').val();
        var productId = $('#productId').val();
        
        if(color || size) {
            $.ajax({
                url: '<?= $site ?>ajax/get-variant-details.php',
                method: 'GET',
                data: {
                    product_id: productId,
                    color: color,
                    size: size
                },
                success: function(response) {
                    if(response.success) {
                        // Update price
                        if(response.price) {
                            $('.current_price').text('₹' + response.price.toFixed(2));
                        }
                        
                        // Update stock status
                        if(response.stock !== undefined) {
                            if(response.stock > 0) {
                                $('.stock-status').text('In Stock (' + response.stock + ' available)').removeClass('text-danger').addClass('text-success');
                                $('.add-to-cart-btn').prop('disabled', false);
                            } else {
                                $('.stock-status').text('Out of Stock').addClass('text-danger').removeClass('text-success');
                                $('.add-to-cart-btn').prop('disabled', true);
                            }
                        }
                        
                        // Update selected variant ID
                        if(response.variant_id) {
                            $('.add-to-cart-btn').data('variant-id', response.variant_id);
                        }
                    }
                }
            });
        }
    });
}

// Call product features initialization when document is ready
$(document).ready(function() {
    if($('.product-details-page').length) {
        initProductFeatures();
    }
});

// AJAX error handling
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
    if(jqxhr.status === 401) {
        // Unauthorized - redirect to login
        sessionStorage.setItem('redirect_url', window.location.href);
        window.location.href = '<?= $site ?>login/';
    } else if(jqxhr.status === 0) {
        // Network error
        showNotification('Network error. Please check your connection.', 'error');
    }
});
</script>