<?php
include_once "../config/connect.php";
include_once "../util/function.php";

$category_id = $_GET['category_id'] ?? null;
$filters = [
    'min_price' => $_GET['min_price'] ?? null,
    'max_price' => $_GET['max_price'] ?? null,
    'brands' => isset($_GET['brand']) ? (array)$_GET['brand'] : [],
    'colors' => isset($_GET['color']) ? (array)$_GET['color'] : [],
    'sizes' => isset($_GET['size']) ? (array)$_GET['size'] : [],
    'sort' => $_GET['sort'] ?? 'newest'
];

$products = get_filtered_products($category_id, $filters);

foreach($products as $pro): 
?>
<div class="col-lg-4 col-md-4 col-sm-6 col-12 ">
    <div class="single_product" data-product-id="<?= $pro['pro_id'] ?>">
        <div class="product_thumb">
            <a class="primary_img" href="<?= $site ?>product-details/<?= $pro['slug_url'] ?>">
                <img src="<?= $site ?>admin/assets/img/uploads/<?= $pro['pro_img'] ?>" alt="<?= htmlspecialchars($pro['pro_name']) ?>">
            </a>
            <a class="secondary_img" href="<?= $site ?>product-details/<?= $pro['slug_url'] ?>">
                <img src="<?= $site ?>assets/img/product/product2.jpg" alt="<?= htmlspecialchars($pro['pro_name']) ?>">
            </a>
            <?php if ($pro['mrp'] > $pro['selling_price']): ?>
            <div class="label_product">
                <span class="label_sale">Sale</span>
            </div>
            <?php endif; ?>
            <div class="action_links">
                <ul>
                    <li class="quick_button">
                        <a href="#" data-bs-toggle="modal" data-bs-target="#quickViewModal" 
                           data-product-id="<?= $pro['pro_id'] ?>" title="quick view">
                            <span class="pe-7s-search"></span>
                        </a>
                    </li>
                    <li class="wishlist">
                        <a href="#" class="add-to-wishlist" data-product-id="<?= $pro['pro_id'] ?>" title="Add to Wishlist">
                            <span class="pe-7s-like"></span>
                        </a>
                    </li>
                    <li class="compare">
                        <a href="#" class="add-to-compare" data-product-id="<?= $pro['pro_id'] ?>" title="Add to Compare">
                            <span class="pe-7s-edit"></span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <div class="product_content grid_content">
            <div class="product_content_inner">
                <h4 class="product_name">
                    <a href="<?= $site ?>product-details/<?= $pro['slug_url'] ?>">
                        <?= htmlspecialchars($pro['pro_name']) ?>
                    </a>
                </h4>
                <div class="price_box"> 
                    <?php if ($pro['mrp'] > $pro['selling_price']): ?>
                    <span class="old_price">₹ <?= number_format($pro['mrp'], 2) ?></span>
                    <?php endif; ?>
                    <span class="current_price">₹ <?= number_format($pro['selling_price'], 2) ?></span>
                </div>
            </div>
            <div class="add_to_cart">
                <a href="#" class="add-to-cart-btn" data-product-id="<?= $pro['pro_id'] ?>">Add to cart</a>
            </div>
        </div>
    </div>
</div>
<?php 
endforeach;

if (empty($products)): 
?>
<div class="col-12">
    <div class="alert alert-info">No products found with selected filters.</div>
</div>
<?php endif; ?>