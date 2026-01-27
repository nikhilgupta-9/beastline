<?php
session_start();
include_once "../config/connect.php";

$cart_items = [];
$total = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $key => $item) {
        $product_id = intval($item['product_id']);
        $variant_id = isset($item['variant_id']) ? intval($item['variant_id']) : null;
        $quantity = intval($item['quantity']);
        
        // Get product details
        $sql = "SELECT p.pro_id, p.pro_name, p.pro_img, p.selling_price,
                       pv.price as variant_price, pv.color, pv.size,
                       pv.image as variant_image
                FROM products p
                LEFT JOIN product_variants pv ON pv.id = " . ($variant_id ? $variant_id : 'NULL') . "
                WHERE p.pro_id = $product_id AND p.status = 1";
        
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $product = mysqli_fetch_assoc($result);
            
            $price = $variant_id ? $product['variant_price'] : $product['selling_price'];
            $subtotal = $price * $quantity;
            $total += $subtotal;
            
            $image = $variant_id && !empty($product['variant_image']) ? 
                    $product['variant_image'] : $product['pro_img'];
            
            $cart_items[] = [
                'key' => $key,
                'product_id' => $product_id,
                'name' => $product['pro_name'],
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
                'image' => $image,
                'color' => $product['color'] ?? '',
                'size' => $product['size'] ?? ''
            ];
        }
    }
}

// If it's an AJAX request, return JSON
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode([
        'items' => $cart_items,
        'total' => number_format($total, 2),
        'count' => count($cart_items)
    ]);
    exit;
}
?>

<?php if (empty($cart_items)): ?>
<div class="empty_cart text-center py-4">
    <i class="pe-7s-cart text-muted" style="font-size: 48px;"></i>
    <p class="mt-3">Your cart is empty</p>
    <a href="<?= $site ?>shop/" class="btn btn-outline-dark mt-2">Continue Shopping</a>
</div>
<?php else: ?>
<div class="mini_cart_items">
    <?php foreach ($cart_items as $item): ?>
    <div class="mini_cart_item mb-3 pb-3 border-bottom">
        <div class="row align-items-center">
            <div class="col-3">
                <img src="<?= $site ?>admin/assets/img/uploads/<?= $item['image'] ?>" 
                     alt="<?= htmlspecialchars($item['name']) ?>" 
                     class="img-fluid rounded">
            </div>
            <div class="col-7">
                <h6 class="mb-1">
                    <a href="<?= $site ?>product-details/<?= $item['product_id'] ?>">
                        <?= htmlspecialchars($item['name']) ?>
                    </a>
                </h6>
                <?php if (!empty($item['color'])): ?>
                <small class="text-muted">Color: <?= htmlspecialchars($item['color']) ?></small>
                <?php endif; ?>
                <?php if (!empty($item['size'])): ?>
                <small class="text-muted">Size: <?= htmlspecialchars($item['size']) ?></small>
                <?php endif; ?>
                <div class="d-flex align-items-center mt-1">
                    <span class="me-2"><?= $item['quantity'] ?> ×</span>
                    <span class="fw-bold">₹<?= number_format($item['price'], 2) ?></span>
                </div>
            </div>
            <div class="col-2 text-end">
                <button class="btn btn-sm btn-outline-danger remove-cart-item" 
                        data-key="<?= $item['key'] ?>">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="mini_cart_total py-3 border-top">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <strong>Subtotal:</strong>
        <strong class="text-dark">₹<?= number_format($total, 2) ?></strong>
    </div>
    <div class="d-grid gap-2">
        <a href="<?= $site ?>cart/" class="btn btn-outline-dark">View Cart</a>
        <a href="<?= $site ?>checkout/" class="btn btn-dark">Proceed to Checkout</a>
    </div>
</div>

<script>
$(document).ready(function() {
    // Remove item from cart
    $('.remove-cart-item').click(function(e) {
        e.preventDefault();
        var key = $(this).data('key');
        var button = $(this);
        
        $.ajax({
            url: '<?= $site ?>ajax/remove-from-cart.php',
            method: 'POST',
            data: {
                cart_key: key,
                action: 'remove_from_cart'
            },
            beforeSend: function() {
                button.html('<span class="spinner-border spinner-border-sm"></span>');
            },
            success: function(response) {
                if (response.success) {
                    // Reload mini cart
                    loadMiniCart();
                    // Update cart count
                    $('.item_count').text(response.cart_count);
                } else {
                    alert(response.message);
                    button.html('<i class="fa fa-times"></i>');
                }
            }
        });
    });
    
    function loadMiniCart() {
        $.ajax({
            url: '<?= $site ?>ajax/get-mini-cart-items.php',
            method: 'GET',
            success: function(response) {
                $('#miniCartItems').html(response);
            }
        });
    }
});
</script>
<?php endif; ?>