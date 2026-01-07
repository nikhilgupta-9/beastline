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


 <script>
        $(document).ready(function() {
            // Initialize price slider
            var minPrice = <?= $price_range['min_price'] ?? 0 ?>;
            var maxPrice = <?= $price_range['max_price'] ?? 10000 ?>;

            $("#slider-range").slider({
                range: true,
                min: minPrice,
                max: maxPrice,
                values: [<?= $filters['min_price'] ?? $price_range['min_price'] ?? 0 ?>, <?= $filters['max_price'] ?? $price_range['max_price'] ?? 10000 ?>],
                slide: function(event, ui) {
                    $("#amount").val("₹" + ui.values[0] + " - ₹" + ui.values[1]);
                }
            });
            $("#amount").val("₹" + $("#slider-range").slider("values", 0) + " - ₹" + $("#slider-range").slider("values", 1));

            // Cart count
            function updateCartCount() {
                $.ajax({
                    url: '<?= $site ?>ajax/get-cart-count.php',
                    method: 'GET',
                    success: function(response) {
                        $('.item_count').text(response.count);
                    }
                });
            }

            // Initialize cart count
            updateCartCount();

            // Apply filters
            function applyFilters() {
                var filters = {
                    min_price: $("#slider-range").slider("values", 0),
                    max_price: $("#slider-range").slider("values", 1),
                    brands: [],
                    colors: [],
                    sizes: [],
                    sort: $("#sortSelect").val()
                };

                // Get selected brands
                $('input[name="brand"]:checked').each(function() {
                    filters.brands.push($(this).val());
                });

                // Get selected colors
                $('input[name="color"]:checked').each(function() {
                    filters.colors.push($(this).val());
                });

                // Get selected sizes
                $('input[name="size"]:checked').each(function() {
                    filters.sizes.push($(this).val());
                });

                // Update URL without reloading page
                var url = new URL(window.location.href);
                url.searchParams.set('min_price', filters.min_price);
                url.searchParams.set('max_price', filters.max_price);
                url.searchParams.delete('brand');
                url.searchParams.delete('color');
                url.searchParams.delete('size');

                filters.brands.forEach(function(brand) {
                    url.searchParams.append('brand', brand);
                });

                filters.colors.forEach(function(color) {
                    url.searchParams.append('color', color);
                });

                filters.sizes.forEach(function(size) {
                    url.searchParams.append('size', size);
                });

                url.searchParams.set('sort', filters.sort);

                // Load products via AJAX
                $.ajax({
                    url: '<?= $site ?>ajax/load-products.php?category_id=<?= $category_id ?>&' + url.searchParams.toString(),
                    method: 'GET',
                    beforeSend: function() {
                        $('#productsContainer').html('<div class="col-12 text-center"><div class="spinner-border" role="status"></div></div>');
                    },
                    success: function(response) {
                        $('#productsContainer').html(response);
                        $('#productCount').text($('#productsContainer .single_product').length);
                        updateURL(url.toString());
                    }
                });
            }

            // Update browser URL without reload
            function updateURL(url) {
                window.history.pushState({
                    path: url
                }, '', url);
            }

            // Event listeners for filters
            $('.filter-checkbox').change(function() {
                applyFilters();
            });

            $('#sortSelect').change(function() {
                applyFilters();
            });

            $('#applyPriceFilter').click(function() {
                applyFilters();
            });

            $('#clearFilters').click(function() {
                $('.filter-checkbox').prop('checked', false);
                $("#slider-range").slider("values", [minPrice, maxPrice]);
                $("#amount").val("₹" + minPrice + " - ₹" + maxPrice);
                $("#sortSelect").val('newest');
                applyFilters();
            });

            // Add to cart AJAX
            $(document).on('click', '.add-to-cart-btn', function(e) {
                e.preventDefault();

                var productId = $(this).data('product-id');
                var hasVariants = $(this).data('has-variants');
                var button = $(this);

                // If product has variants, redirect to product page
                if (hasVariants == 1) {
                    window.location.href = '<?= $site ?>product-details/' + $(this).data('product-slug');
                    return;
                }

                $.ajax({
                    url: '<?= $site ?>ajax/add-to-cart.php',
                    method: 'POST',
                    data: {
                        product_id: productId,
                        quantity: 1,
                        action: 'add_to_cart'
                    },
                    beforeSend: function() {
                        button.html('<span class="spinner-border spinner-border-sm"></span> Adding...');
                        button.prop('disabled', true);
                    },
                    success: function(response) {
                        if (response.success) {
                            button.html('<i class="fa fa-check"></i> Added');
                            $('.item_count').text(response.cart_count);

                            // Show success toast/notification
                            showNotification(response.message, 'success');

                            // Update mini cart
                            updateMiniCart();

                            setTimeout(function() {
                                button.html('Add to cart');
                                button.prop('disabled', false);
                            }, 1500);
                        } else {
                            showNotification(response.message, 'error');
                            button.html('Add to cart');
                            button.prop('disabled', false);
                        }
                    },
                    error: function(xhr, status, error) {
                        showNotification('Error adding to cart. Please try again.', 'error');
                        button.html('Add to cart');
                        button.prop('disabled', false);
                    }
                });
            });

            // Add to wishlist AJAX
            $(document).on('click', '.add-to-wishlist', function(e) {
                e.preventDefault();
                var productId = $(this).data('product-id');
                var button = $(this);

                $.ajax({
                    url: '<?= $site ?>ajax/add-to-wishlist.php',
                    method: 'POST',
                    data: {
                        product_id: productId,
                        action: 'add_to_wishlist'
                    },
                    beforeSend: function() {
                        button.find('span').addClass('text-danger');
                    },
                    success: function(response) {
                        if (response.success) {
                            button.find('span').removeClass('pe-7s-like').addClass('fa fa-heart text-danger');
                            alert(response.message);
                        } else {
                            if (response.message.includes('login')) {
                                window.location.href = '<?= $site ?>user-login/';
                            } else {
                                alert(response.message);
                            }
                        }
                    }
                });
            });

            // Quick view modal
            $('#quickViewModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var productId = button.data('product-id');

                $.ajax({
                    url: '<?= $site ?>ajax/quick-view.php',
                    method: 'GET',
                    data: {
                        product_id: productId
                    },
                    success: function(response) {
                        $('#quickViewContent').html(response);
                    }
                });
            });

            // Update mini cart dropdown
            function updateMiniCart() {
                $.ajax({
                    url: '<?= $site ?>ajax/get-mini-cart.php',
                    method: 'GET',
                    success: function(response) {
                        $('#miniCartItems').html(response.items);
                        $('#cartTotal').text('₹' + response.total);
                    }
                });
            }

            // Toggle mini cart dropdown
            $('.mini_cart_wrapper_trigger').click(function(e) {
                e.preventDefault();
                updateMiniCart();
                $('#miniCartDropdown').toggle();
            });

            // Close dropdown when clicking outside
            $(document).click(function(e) {
                if (!$(e.target).closest('.header_account_area').length) {
                    $('#miniCartDropdown').hide();
                }
            });
        });
    </script>