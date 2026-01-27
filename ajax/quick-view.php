<?php
include_once "../config/connect.php";

if (!isset($_GET['product_id'])) {
    die('Product ID is required');
}

$product_id = intval($_GET['product_id']);

// Get product details
$sql = "SELECT p.*, 
               c.categories as category_name,
               b.brand_name,
               pi.image_url as additional_images
        FROM products p
        LEFT JOIN categories c ON p.pro_cate = c.id
        LEFT JOIN pro_brands b ON p.brand_name = b.id
        LEFT JOIN product_images pi ON p.pro_id = pi.product_id AND pi.is_main = 0
        WHERE p.pro_id = $product_id AND p.status = 1
        GROUP BY p.pro_id";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    die('Product not found');
}

$product = mysqli_fetch_assoc($result);

// Get product variants
$variants_sql = "SELECT * FROM product_variants 
                 WHERE product_id = $product_id 
                 AND status = 1 
                 ORDER BY color, size";
$variants_result = mysqli_query($conn, $variants_sql);
$variants = [];
$colors = [];
$sizes = [];

while ($variant = mysqli_fetch_assoc($variants_result)) {
    $variants[] = $variant;
    
    if (!in_array($variant['color'], $colors) && !empty($variant['color'])) {
        $colors[] = $variant['color'];
    }
    
    if (!in_array($variant['size'], $sizes) && !empty($variant['size'])) {
        $sizes[] = $variant['size'];
    }
}

// Get product images
$images_sql = "SELECT * FROM product_images 
               WHERE product_id = $product_id 
               ORDER BY is_main DESC, display_order";
$images_result = mysqli_query($conn, $images_sql);
$product_images = [];

while ($image = mysqli_fetch_assoc($images_result)) {
    $product_images[] = $image;
}

// If no additional images, use main product image
if (empty($product_images)) {
    $product_images[] = [
        'image_url' => $product['pro_img'],
        'is_main' => 1
    ];
}

// Calculate discount percentage
$discount_percentage = 0;
if ($product['mrp'] > $product['selling_price']) {
    $discount_percentage = round((($product['mrp'] - $product['selling_price']) / $product['mrp']) * 100);
}
?>

<div class="row">
    <div class="col-lg-5 col-md-5 col-sm-12">
        <div class="modal_tab">  
            <div class="tab-content product-details-large">
                <?php foreach ($product_images as $index => $image): ?>
                <div class="tab-pane fade <?= ($index == 0) ? 'show active' : '' ?>" 
                     id="tab<?= $index + 1 ?>" role="tabpanel">
                    <div class="modal_tab_img">
                        <a href="#">
                            <img src="<?= $site ?>admin/assets/img/uploads/<?= $image['image_url'] ?>" 
                                 alt="<?= htmlspecialchars($product['pro_name']) ?>">
                        </a>    
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="modal_tab_button">    
                <ul class="nav product_navactive owl-carousel" role="tablist">
                    <?php foreach ($product_images as $index => $image): ?>
                    <li>
                        <a class="nav-link <?= ($index == 0) ? 'active' : '' ?>" 
                           data-bs-toggle="tab" 
                           href="#tab<?= $index + 1 ?>" 
                           role="tab" 
                           aria-controls="tab<?= $index + 1 ?>">
                            <img src="<?= $site ?>admin/assets/img/uploads/<?= $image['image_url'] ?>" 
                                 alt="<?= htmlspecialchars($product['pro_name']) ?>">
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>    
        </div>  
    </div> 
    
    <div class="col-lg-7 col-md-7 col-sm-12">
        <div class="modal_right">
            <div class="modal_title mb-10">
                <h2><?= htmlspecialchars($product['pro_name']) ?></h2> 
            </div>
            
            <div class="modal_price mb-10">
                <span class="new_price">₹<?= number_format($product['selling_price'], 2) ?></span>    
                <?php if ($product['mrp'] > $product['selling_price']): ?>
                <span class="old_price">₹<?= number_format($product['mrp'], 2) ?></span>    
                <?php endif; ?>
                <?php if ($discount_percentage > 0): ?>
                <span class="badge bg-danger ms-2">-<?= $discount_percentage ?>%</span>
                <?php endif; ?>
            </div>
            
            <div class="modal_description mb-15">
                <p><?= htmlspecialchars($product['short_desc']) ?></p>    
            </div> 
            
            <?php if (!empty($variants)): ?>
            <div class="variants_selects">
                <?php if (!empty($colors)): ?>
                <div class="variants_color mb-3">
                   <h5>Color</h5>
                   <div class="color_options">
                       <?php foreach ($colors as $color): ?>
                       <label class="color_option me-2">
                           <input type="radio" name="color" value="<?= htmlspecialchars($color) ?>" 
                                  <?= ($color == $colors[0]) ? 'checked' : '' ?>>
                           <span class="color_dot" style="background-color: <?= strtolower($color) ?>"></span>
                           <span><?= $color ?></span>
                       </label>
                       <?php endforeach; ?>
                   </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($sizes)): ?>
                <div class="variants_size mb-3">
                   <h5>Size</h5>
                   <div class="size_options">
                       <?php foreach ($sizes as $size): ?>
                       <label class="size_option me-2">
                           <input type="radio" name="size" value="<?= htmlspecialchars($size) ?>" 
                                  <?= ($size == $sizes[0]) ? 'checked' : '' ?>>
                           <span><?= $size ?></span>
                       </label>
                       <?php endforeach; ?>
                   </div>
                </div>
                <?php endif; ?>
                
                <div class="modal_add_to_cart mb-3">
                    <form id="quickViewAddToCart">
                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                        <input type="hidden" name="variant_id" id="selectedVariant" value="">
                        <div class="quantity d-inline-block me-3">
                            <label>Quantity:</label>
                            <input type="number" name="quantity" value="1" min="1" max="100" class="form-control w-100">
                        </div>
                        <button type="submit" class="btn btn-dark">Add to cart</button>
                    </form>
                </div>   
            </div>
            <?php else: ?>
            <div class="modal_add_to_cart mb-3">
                <form id="quickViewAddToCart">
                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                    <div class="quantity d-inline-block me-3">
                        <label>Quantity:</label>
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['qty'] ?>" class="form-control w-100">
                    </div>
                    <button type="submit" class="btn btn-dark">Add to cart</button>
                </form>
            </div>
            <?php endif; ?>
            
            <div class="product_meta mb-3">
                <?php if (!empty($product['sku'])): ?>
                <p><strong>SKU:</strong> <?= htmlspecialchars($product['sku']) ?></p>
                <?php endif; ?>
                <?php if (!empty($product['category_name'])): ?>
                <p><strong>Category:</strong> <?= htmlspecialchars($product['category_name']) ?></p>
                <?php endif; ?>
                <?php if (!empty($product['brand_name'])): ?>
                <p><strong>Brand:</strong> <?= htmlspecialchars($product['brand_name']) ?></p>
                <?php endif; ?>
                <p><strong>Availability:</strong> 
                    <?php if ($product['stock'] > 0): ?>
                    <span class="text-success">In Stock (<?= $product['stock'] ?> available)</span>
                    <?php else: ?>
                    <span class="text-danger">Out of Stock</span>
                    <?php endif; ?>
                </p>
            </div>
            
            <div class="modal_social">
                <h5>Share this product</h5>
                <ul>
                    <li class="facebook">
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($site . 'product-details/' . $product['slug_url']) ?>" 
                           target="_blank">
                            <i class="fa fa-facebook"></i>
                        </a>
                    </li>
                    <li class="twitter">
                        <a href="https://twitter.com/intent/tweet?text=<?= urlencode($product['pro_name']) ?>&url=<?= urlencode($site . 'product-details/' . $product['slug_url']) ?>" 
                           target="_blank">
                            <i class="fa fa-twitter"></i>
                        </a>
                    </li>
                    <li class="pinterest">
                        <a href="https://pinterest.com/pin/create/button/?url=<?= urlencode($site . 'product-details/' . $product['slug_url']) ?>&media=<?= urlencode($site . 'admin/assets/img/uploads/' . $product['pro_img']) ?>&description=<?= urlencode($product['pro_name']) ?>" 
                           target="_blank">
                            <i class="fa fa-pinterest"></i>
                        </a>
                    </li>
                    <li class="whatsapp">
                        <a href="https://api.whatsapp.com/send?text=<?= urlencode($product['pro_name'] . ' - ' . $site . 'product-details/' . $product['slug_url']) ?>" 
                           target="_blank">
                            <i class="fa fa-whatsapp"></i>
                        </a>
                    </li>
                </ul>    
            </div>      
        </div>    
    </div>    
</div>

<script>
$(document).ready(function() {
    // Handle variant selection
    var variants = <?= json_encode($variants) ?>;
    
    $('input[name="color"], input[name="size"]').change(function() {
        var selectedColor = $('input[name="color"]:checked').val() || '';
        var selectedSize = $('input[name="size"]:checked').val() || '';
        
        // Find matching variant
        var matchedVariant = variants.find(function(variant) {
            return variant.color === selectedColor && variant.size === selectedSize;
        });
        
        if (matchedVariant) {
            $('#selectedVariant').val(matchedVariant.id);
            
            // Update price if variant has different price
            if (matchedVariant.price && matchedVariant.price != <?= $product['selling_price'] ?>) {
                $('.new_price').text('₹' + parseFloat(matchedVariant.price).toFixed(2));
            }
            
            // Update stock availability
            var stockText = matchedVariant.quantity > 0 ? 
                'In Stock (' + matchedVariant.quantity + ' available)' : 
                'Out of Stock';
            $('.product_meta p:last').html('<strong>Availability:</strong> <span class="' + 
                (matchedVariant.quantity > 0 ? 'text-success' : 'text-danger') + '">' + stockText + '</span>');
            
            // Update max quantity
            $('input[name="quantity"]').attr('max', matchedVariant.quantity);
        } else {
            $('#selectedVariant').val('');
            $('.new_price').text('₹<?= number_format($product['selling_price'], 2) ?>');
            $('input[name="quantity"]').attr('max', <?= $product['qty'] ?>);
        }
    });
    
    // Initialize variant selection
    $('input[name="color"]:checked, input[name="size"]:checked').trigger('change');
    
    // Quick view add to cart
    $('#quickViewAddToCart').submit(function(e) {
        e.preventDefault();
        
        var formData = $(this).serialize();
        
        $.ajax({
            url: '<?= $site ?>ajax/add-to-cart.php',
            method: 'POST',
            data: formData + '&action=add_to_cart',
            success: function(response) {
                if (response.success) {
                    // Update cart count
                    $('.item_count').text(response.cart_count);
                    
                    // Show success message
                    alert(response.message);
                    
                    // Close modal after delay
                    setTimeout(function() {
                        $('#quickViewModal').modal('hide');
                    }, 1000);
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Error adding product to cart. Please try again.');
            }
        });
    });
    
    // Initialize image carousel
    $('.product_navactive').owlCarousel({
        loop: true,
        nav: true,
        dots: false,
        responsive: {
            0: {
                items: 3
            },
            576: {
                items: 4
            },
            768: {
                items: 4
            },
            992: {
                items: 4
            }
        }
    });
});
</script>