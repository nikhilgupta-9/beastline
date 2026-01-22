<?php 
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';
require_once __DIR__ . '/models/setting.php';

// Initialize Settings
$setting = new Setting($conn);
// Fetch categories
$sql = "SELECT * FROM `categories` WHERE `parent_id` = 0 AND `status` = 1 ORDER BY display_order ASC";
$categories = mysqli_query($conn, $sql);

// Fetch brands from pro_brands table
$brands_sql = "SELECT * FROM `pro_brands` WHERE `status` = 1 ORDER BY `brand_name` ASC";
$brands = mysqli_query($conn, $brands_sql);

// Predefined options for clothing and shoes
$colors = ['Black', 'White', 'Red', 'Blue', 'Green', 'Yellow', 'Purple', 'Pink', 'Orange', 'Brown', 'Grey', 'Navy', 'Maroon', 'Beige', 'Cream', 'Multi-color'];
$sizes_clothing = ['XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL', '28', '30', '32', '34', '36', '38', '40', '42'];
$sizes_shoes = ['6', '7', '8', '9', '10', '11', '12', '13'];
$material_options = ['Cotton', 'Polyester', 'Denim', 'Leather', 'Suede', 'Wool', 'Silk', 'Linen', 'Nylon', 'Spandex', 'Canvas'];
$fit_options = ['Regular', 'Slim', 'Loose', 'Athletic', 'Relaxed'];
$season_options = ['All Season', 'Summer', 'Winter', 'Spring', 'Fall'];
?>
<!DOCTYPE html>
<html lang="zxx">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Add Product | Beastline Clothing & Shoes</title>
    <link rel="icon" href="<?php echo htmlspecialchars($setting->get('favicon', 'assets/img/logo.png')); ?>" type="image/png">
    
    <?php include "links.php"; ?>
    
    <!-- Include CKEditor -->
    <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }
        
        .white_card {
            background-color: #fff;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .white_card_header .main-title h3 {
            font-weight: 600;
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 500;
            margin-bottom: 6px;
            color: #2c3e50;
            font-size: 14px;
        }
        
        .form-control, .form-select {
            border-radius: 8px;
            padding: 10px 12px;
            border: 1px solid #d1d3e2;
            box-shadow: none;
            transition: border-color 0.3s ease-in-out;
            font-size: 14px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .nav-tabs {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 25px;
        }
        
        .nav-tabs .nav-link {
            color: #6c757d;
            border: none;
            padding: 12px 25px;
            font-weight: 500;
            border-radius: 0;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: #4e73df;
            border-color: transparent;
        }
        
        .nav-tabs .nav-link.active {
            color: #4e73df;
            background-color: transparent;
            border: none;
            border-bottom: 3px solid #4e73df;
        }
        
        .tab-content {
            padding: 20px 0;
        }
        
        .variant-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #4e73df;
        }
        
        .variant-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        
        .variant-section-header h6 {
            margin: 0;
            font-weight: 600;
            color: #495057;
        }
        
        .remove-variant {
            color: #dc3545;
            background: none;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .remove-variant:hover {
            background-color: #f8d7da;
        }
        
        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
        }
        
        .image-preview-item {
            position: relative;
            width: 120px;
            height: 120px;
        }
        
        .image-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        
        .remove-image {
            position: absolute;
            top: -8px;
            right: -8px;
            width: 24px;
            height: 24px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
        }
        
        .image-upload-box {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            background: #f8f9fa;
        }
        
        .image-upload-box:hover {
            border-color: #4e73df;
            background: #e9ecef;
        }
        
        .image-upload-box i {
            font-size: 48px;
            color: #6c757d;
            margin-bottom: 10px;
        }
        
        .checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .checkbox-item {
            display: flex;
            align-items: center;
            background: #f8f9fa;
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid #dee2e6;
        }
        
        .checkbox-item input[type="checkbox"] {
            margin-right: 8px;
        }
        
       
        .price-input-group {
            display: flex;
            align-items: center;
        }
        
        .price-input-group .input-group-text {
            background-color: #f8f9fa;
            border: 1px solid #d1d3e2;
        }
        
        .discount-badge {
            background: #1cc88a;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .attribute-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 10px;
        }
        
        .tag-badge {
            background: #e9ecef;
            color: #495057;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .tag-badge i {
            cursor: pointer;
            color: #6c757d;
        }
        
        .tag-badge i:hover {
            color: #dc3545;
        }
        
        .variants-table {
            width: 100%;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .variants-table th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
        }
        
        .variants-table td {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .variant-image-cell img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            .white_card {
                padding: 15px;
            }
            
            .nav-tabs .nav-link {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .image-preview-item {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>

<body class="crm_body_bg">

    <?php include "includes/header.php"; ?>
    
    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "includes/top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="white_card">
                            <div class="white_card_header">
                                <div class="main-title">
                                    <h3>Add New Product - Clothing & Shoes</h3>
                                    <p class="mb-0 text-muted">Fill in the product details for your clothing or shoe item</p>
                                </div>
                            </div>
                            <div class="white_card_body">
                                <form id="productForm" action="functions.php" method="post" enctype="multipart/form-data">
                                    
                                    <!-- Tab Navigation -->
                                    <ul class="nav nav-tabs" id="productTab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">Basic Info</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="pricing-tab" data-bs-toggle="tab" data-bs-target="#pricing" type="button" role="tab">Pricing & Inventory</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="attributes-tab" data-bs-toggle="tab" data-bs-target="#attributes" type="button" role="tab">Attributes</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="variants-tab" data-bs-toggle="tab" data-bs-target="#variants" type="button" role="tab">Variants</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="media-tab" data-bs-toggle="tab" data-bs-target="#media" type="button" role="tab">Media</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="seo-tab" data-bs-toggle="tab" data-bs-target="#seo" type="button" role="tab">SEO & Status</button>
                                        </li>
                                    </ul>
                                    
                                    <div class="tab-content" id="productTabContent">
                                        
                                        <!-- Basic Information Tab -->
                                        <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Product Name *</label>
                                                    <input type="text" class="form-control" name="pro_name" required placeholder="e.g., Men's Cotton T-Shirt">
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">SKU (Stock Keeping Unit) *</label>
                                                    <input type="text" class="form-control" name="sku" required placeholder="e.g., BLS-TSH-001">
                                                    <small class="text-muted">Unique identifier for inventory tracking</small>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Brand *</label>
                                                    <select class="form-control" name="brand_name" required>
                                                        <option value="">Select Brand</option>
                                                        <?php if(mysqli_num_rows($brands) > 0): ?>
                                                            <?php while($brand = mysqli_fetch_assoc($brands)): ?>
                                                                <option value="<?= $brand['id'] ?>"><?= htmlspecialchars($brand['brand_name']) ?></option>
                                                            <?php endwhile; ?>
                                                        <?php endif; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Product Type *</label>
                                                    <select class="form-control" name="product_type" id="productType" required>
                                                        <option value="">Select Type</option>
                                                        <option value="clothing">Clothing</option>
                                                        <option value="shoes">Shoes</option>
                                                        <option value="accessories">Accessories</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Category *</label>
                                                    <select class="form-control" name="pro_cate" id="mainCategory" required onchange="getSubcategories(this.value)">
                                                        <option value="">Select Category</option>
                                                        <?php while($category = mysqli_fetch_assoc($categories)): ?>
                                                            <option value="<?= $category['cate_id'] ?>"><?= htmlspecialchars($category['categories']) ?></option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Sub Category</label>
                                                    <select class="form-control" name="pro_sub_cate" id="subCategory">
                                                        <option value="">Select Sub Category</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Short Description *</label>
                                                    <textarea class="form-control" name="short_desc" id="short_desc" rows="3" required placeholder="Brief description shown in product listings"></textarea>
                                                </div>
                                                
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Full Description *</label>
                                                    <textarea class="form-control" name="pro_desc" id="pro_desc" rows="6" required placeholder="Detailed product description"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Pricing & Inventory Tab -->
                                        <div class="tab-pane fade" id="pricing" role="tabpanel" aria-labelledby="pricing-tab">
                                            <div class="row">
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">MRP (Maximum Retail Price) *</label>
                                                    <div class="price-input-group">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" class="form-control" name="mrp" step="0.01" required placeholder="0.00">
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Selling Price *</label>
                                                    <div class="price-input-group">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" class="form-control" name="selling_price" step="0.01" required placeholder="0.00">
                                                    </div>
                                                    <small class="text-muted">The price customers will pay</small>
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Wholesale Price</label>
                                                    <div class="price-input-group">
                                                        <span class="input-group-text">₹</span>
                                                        <input type="number" class="form-control" name="whole_selling_price" step="0.01" placeholder="0.00">
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Weight (kg)</label>
                                                    <input type="number" class="form-control" name="weight" step="0.01" placeholder="0.00">
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Stock Status *</label>
                                                    <select class="form-control" name="stock" required>
                                                        <option value="in_stock">In Stock</option>
                                                        <option value="low_stock">Low Stock</option>
                                                        <option value="out_of_stock">Out of Stock</option>
                                                        <option value="pre_order">Pre-order</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-4 mb-3">
                                                    <label class="form-label">Initial Stock Quantity</label>
                                                    <input type="number" class="form-control" name="initial_qty" min="0" placeholder="0">
                                                </div>
                                                
                                                <div class="col-12 mb-3">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="manage_stock" id="manageStock" checked>
                                                        <label class="form-check-label" for="manageStock">
                                                            Manage stock quantities for variants
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Attributes Tab -->
                                        <div class="tab-pane fade" id="attributes" role="tabpanel" aria-labelledby="attributes-tab">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Available Colors</label>
                                                    <div class="checkbox-group" id="colorOptions">
                                                        <?php foreach($colors as $color): ?>
                                                            <label class="checkbox-item">
                                                                <input type="checkbox" name="colors[]" value="<?= htmlspecialchars($color) ?>">
                                                                <?= htmlspecialchars($color) ?>
                                                            </label>
                                                        <?php endforeach; ?>
                                                    </div>
                                                    <input type="text" class="form-control mt-2" id="customColor" placeholder="Add custom color" onkeypress="addCustomColor(event)">
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Available Sizes</label>
                                                    <div id="sizeOptionsContainer">
                                                        <!-- Sizes will be dynamically loaded based on product type -->
                                                        <p class="text-muted">Select product type first</p>
                                                    </div>
                                                    <input type="text" class="form-control mt-2" id="customSize" placeholder="Add custom size" onkeypress="addCustomSize(event)">
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Material/Fabric</label>
                                                    <select class="form-control" name="material[]" multiple>
                                                        <?php foreach($material_options as $material): ?>
                                                            <option value="<?= htmlspecialchars($material) ?>"><?= htmlspecialchars($material) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Fit Type (Clothing)</label>
                                                    <select class="form-control" name="fit_type">
                                                        <option value="">Select Fit</option>
                                                        <?php foreach($fit_options as $fit): ?>
                                                            <option value="<?= htmlspecialchars($fit) ?>"><?= htmlspecialchars($fit) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Season</label>
                                                    <select class="form-control" name="season">
                                                        <option value="">Select Season</option>
                                                        <?php foreach($season_options as $season): ?>
                                                            <option value="<?= htmlspecialchars($season) ?>"><?= htmlspecialchars($season) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Care Instructions</label>
                                                    <input type="text" class="form-control" name="care_instructions" placeholder="e.g., Machine wash cold">
                                                </div>
                                                
                                                <div class="col-12 mb-3">
                                                    <label class="form-label">Additional Attributes</label>
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="attrName" placeholder="Attribute name">
                                                        <input type="text" class="form-control" id="attrValue" placeholder="Attribute value">
                                                        <button type="button" class="btn btn-outline-primary" onclick="addAttribute()">Add</button>
                                                    </div>
                                                    <div class="attribute-tags" id="attributeTags"></div>
                                                    <input type="hidden" name="attributes_json" id="attributesJson">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Variants Tab -->
                                        <div class="tab-pane fade" id="variants" role="tabpanel" aria-labelledby="variants-tab">
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle me-2"></i>
                                                        Variants will be automatically generated based on selected colors and sizes
                                                    </div>
                                                </div>
                                                
                                                <div class="col-12">
                                                    <button type="button" class="btn btn-success mb-3" onclick="generateVariants()">
                                                        <i class="fas fa-sync-alt me-2"></i> Generate Variants
                                                    </button>
                                                    
                                                    <div id="variantsContainer">
                                                        <!-- Variants will be generated here -->
                                                        <p class="text-muted">Select colors and sizes in Attributes tab, then click "Generate Variants"</p>
                                                    </div>
                                                    
                                                    <input type="hidden" name="variants_json" id="variantsJson">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Media Tab -->
                                        <div class="tab-pane fade" id="media" role="tabpanel" aria-labelledby="media-tab">
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Main Product Image *</label>
                                                    <div class="image-upload-box" onclick="document.getElementById('mainImage').click()">
                                                        <i class="fas fa-cloud-upload-alt"></i>
                                                        <p>Click to upload main product image</p>
                                                        <small class="text-muted">Recommended: 800x800px, PNG or JPG</small>
                                                    </div>
                                                    <input type="file" class="d-none" name="main_image" id="mainImage" accept="image/*" onchange="previewMainImage(event)">
                                                    <div class="image-preview-container mt-3" id="mainImagePreview"></div>
                                                </div>
                                                
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Additional Images</label>
                                                    <div class="image-upload-box" onclick="document.getElementById('additionalImages').click()">
                                                        <i class="fas fa-images"></i>
                                                        <p>Click to upload additional product images</p>
                                                        <small class="text-muted">You can upload multiple images</small>
                                                    </div>
                                                    <input type="file" class="d-none" name="additional_images[]" id="additionalImages" accept="image/*" multiple onchange="previewAdditionalImages(event)">
                                                    <div class="image-preview-container mt-3" id="additionalImagesPreview"></div>
                                                </div>
                                                
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Product Video (YouTube/Vimeo URL)</label>
                                                    <input type="url" class="form-control" name="video_url" placeholder="https://youtube.com/watch?v=...">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- SEO & Status Tab -->
                                        <div class="tab-pane fade" id="seo" role="tabpanel" aria-labelledby="seo-tab">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Meta Title</label>
                                                    <input type="text" class="form-control" name="meta_title" placeholder="Meta title for SEO">
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Meta Keywords</label>
                                                    <input type="text" class="form-control" name="meta_key" placeholder="Keywords separated by commas">
                                                </div>
                                                
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Meta Description</label>
                                                    <textarea class="form-control" name="meta_desc" rows="3" placeholder="Meta description for SEO"></textarea>
                                                </div>
                                                
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Product Tags</label>
                                                    <input type="text" class="form-control" name="tags" placeholder="Add tags separated by comma">
                                                    <small class="text-muted">e.g., t-shirt, cotton, summer, casual</small>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Special Tags</label>
                                                    <div class="checkbox-group">
                                                        <label class="checkbox-item">
                                                            <input type="checkbox" name="new_arrival" value="1"> New Arrival
                                                        </label>
                                                        <label class="checkbox-item">
                                                            <input type="checkbox" name="trending" value="1"> Trending
                                                        </label>
                                                        <label class="checkbox-item">
                                                            <input type="checkbox" name="is_deal" value="1"> Special Deal
                                                        </label>
                                                        <label class="checkbox-item">
                                                            <input type="checkbox" name="deal_of_the_day" value="1"> Deal of the Day
                                                        </label>
                                                    </div>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Status *</label>
                                                    <select class="form-control" name="status" required>
                                                        <option value="1" selected>Active</option>
                                                        <option value="0">Inactive</option>
                                                        <option value="2">Draft</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary px-5" name="add-product">
                                                <i class="fas fa-plus-circle me-2"></i> Add Product
                                            </button>
                                            <button type="reset" class="btn btn-outline-secondary px-5">
                                                <i class="fas fa-redo me-2"></i> Reset
                                            </button>
                                            <button type="button" class="btn btn-outline-primary px-5" onclick="saveDraft()">
                                                <i class="fas fa-save me-2"></i> Save as Draft
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>
<!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Color Picker -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-colorpicker/3.4.0/js/bootstrap-colorpicker.min.js"></script>
    <!-- JavaScript -->
    <script>
        // Initialize CKEditor
        document.addEventListener('DOMContentLoaded', function() {
            CKEDITOR.replace('short_desc');
            CKEDITOR.replace('pro_desc');
            
            // Bootstrap tab initialization
            var triggerTabList = [].slice.call(document.querySelectorAll('#productTab button'))
            triggerTabList.forEach(function (triggerEl) {
                var tabTrigger = new bootstrap.Tab(triggerEl)
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault()
                    tabTrigger.show()
                })
            });
        });
        
        // Function to get subcategories
        function getSubcategories(categoryId) {
            if (!categoryId) return;
            
            $.ajax({
                url: 'functions.php',
                method: 'POST',
                data: { 
                    action: 'get_subcategories',
                    category_id: categoryId 
                },
                success: function(response) {
                    $('#subCategory').html(response);
                },
                error: function() {
                    console.error('Error loading subcategories');
                }
            });
        }
        
        // Load size options based on product type
        function loadSizeOptions(productType) {
            let sizeOptions = '';
            
            if (productType === 'clothing') {
                sizeOptions = `
                    <div class="checkbox-group" id="sizeOptions">
                        <?php foreach($sizes_clothing as $size): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="sizes[]" value="<?= htmlspecialchars($size) ?>">
                                <?= htmlspecialchars($size) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>`;
            } else if (productType === 'shoes') {
                sizeOptions = `
                    <div class="checkbox-group" id="sizeOptions">
                        <?php foreach($sizes_shoes as $size): ?>
                            <label class="checkbox-item">
                                <input type="checkbox" name="sizes[]" value="<?= htmlspecialchars($size) ?>">
                                <?= htmlspecialchars($size) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>`;
            } else {
                sizeOptions = `<p class="text-muted">Select product type first</p>`;
            }
            
            $('#sizeOptionsContainer').html(sizeOptions);
        }
        
        // Add custom color
        function addCustomColor(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const color = $('#customColor').val().trim();
                if (color) {
                    const checkboxId = 'color_' + color.replace(/\s+/g, '_');
                    const newColor = `
                        <label class="checkbox-item">
                            <input type="checkbox" name="colors[]" value="${color}" id="${checkboxId}">
                            ${color}
                        </label>`;
                    $('#colorOptions').append(newColor);
                    $('#customColor').val('');
                }
            }
        }
        
        // Add custom size
        function addCustomSize(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const size = $('#customSize').val().trim();
                if (size) {
                    const checkboxId = 'size_' + size.replace(/\s+/g, '_');
                    const newSize = `
                        <label class="checkbox-item">
                            <input type="checkbox" name="sizes[]" value="${size}" id="${checkboxId}">
                            ${size}
                        </label>`;
                    $('#sizeOptionsContainer .checkbox-group').append(newSize);
                    $('#customSize').val('');
                }
            }
        }
        
        // Add custom attribute
        let attributes = [];
        function addAttribute() {
            const name = $('#attrName').val().trim();
            const value = $('#attrValue').val().trim();
            
            if (name && value) {
                const attribute = { name, value };
                attributes.push(attribute);
                updateAttributeTags();
                $('#attrName').val('');
                $('#attrValue').val('');
            }
        }
        
        function updateAttributeTags() {
            const container = $('#attributeTags');
            container.empty();
            
            attributes.forEach((attr, index) => {
                const tag = `
                    <span class="tag-badge">
                        ${attr.name}: ${attr.value}
                        <i class="fas fa-times" onclick="removeAttribute(${index})"></i>
                    </span>`;
                container.append(tag);
            });
            
            $('#attributesJson').val(JSON.stringify(attributes));
        }
        
        function removeAttribute(index) {
            attributes.splice(index, 1);
            updateAttributeTags();
        }
        
        // Generate variants
        function generateVariants() {
            const selectedColors = $('input[name="colors[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            
            const selectedSizes = $('input[name="sizes[]"]:checked').map(function() {
                return $(this).val();
            }).get();
            
            if (selectedColors.length === 0 || selectedSizes.length === 0) {
                alert('Please select at least one color and one size');
                return;
            }
            
            let variants = [];
            let variantsHtml = `
                <div class="table-responsive">
                    <table class="variants-table">
                        <thead>
                            <tr>
                                <th>Color</th>
                                <th>Size</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Image</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            let variantIndex = 0;
            selectedColors.forEach(color => {
                selectedSizes.forEach(size => {
                    const baseSku = $('input[name="sku"]').val() || 'BL';
                    const variantSku = `${baseSku}-${color.substr(0,3).toUpperCase()}-${size}`;
                    const variant = {
                        id: variantIndex,
                        color: color,
                        size: size,
                        sku: variantSku,
                        price: $('input[name="selling_price"]').val() || 0,
                        quantity: $('input[name="initial_qty"]').val() || 0,
                        image: ''
                    };
                    
                    variants.push(variant);
                    
                    variantsHtml += `
                        <tr data-variant-id="${variantIndex}">
                            <td>${color}</td>
                            <td>${size}</td>
                            <td>
                                <input type="text" class="form-control form-control-sm" 
                                       name="variants[${variantIndex}][sku]" 
                                       value="${variantSku}">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" 
                                       name="variants[${variantIndex}][price]" 
                                       value="${variant.price}" step="0.01">
                            </td>
                            <td>
                                <input type="number" class="form-control form-control-sm" 
                                       name="variants[${variantIndex}][quantity]" 
                                       value="${variant.quantity}" min="0">
                            </td>
                            <td class="variant-image-cell">
                                <input type="file" class="form-control form-control-sm d-none" 
                                       name="variant_images[${variantIndex}]" 
                                       id="variantImage${variantIndex}" 
                                       onchange="previewVariantImage(${variantIndex}, event)">
                                <label for="variantImage${variantIndex}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-camera"></i>
                                </label>
                                <div id="variantImagePreview${variantIndex}" class="d-inline"></div>
                            </td>
                        </tr>`;
                    
                    variantIndex++;
                });
            });
            
            variantsHtml += `</tbody></table></div>`;
            $('#variantsContainer').html(variantsHtml);
            $('#variantsJson').val(JSON.stringify(variants));
        }
        
        // Image preview functions
        function previewMainImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $('#mainImagePreview').html(`
                        <div class="image-preview-item">
                            <img src="${e.target.result}" class="image-preview">
                            <div class="remove-image" onclick="removeImage('#mainImagePreview')">
                                <i class="fas fa-times"></i>
                            </div>
                        </div>`);
                };
                reader.readAsDataURL(file);
            }
        }
        
        function previewAdditionalImages(event) {
            const files = event.target.files;
            const preview = $('#additionalImagesPreview');
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = (function(file) {
                    return function(e) {
                        const imageId = 'img_' + Date.now() + i;
                        preview.append(`
                            <div class="image-preview-item" id="${imageId}">
                                <img src="${e.target.result}" class="image-preview">
                                <div class="remove-image" onclick="removeImage('#${imageId}')">
                                    <i class="fas fa-times"></i>
                                </div>
                                <input type="hidden" name="additional_images_data[]" value="${e.target.result}">
                            </div>`);
                    };
                })(file);
                
                reader.readAsDataURL(file);
            }
        }
        
        function previewVariantImage(variantIndex, event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $(`#variantImagePreview${variantIndex}`).html(`
                        <img src="${e.target.result}" width="50" height="50" class="rounded ms-2">
                    `);
                };
                reader.readAsDataURL(file);
            }
        }
        
        function removeImage(selector) {
            $(selector).remove();
        }
        
        // Save as draft
        function saveDraft() {
            $('input[name="status"]').val('2');
            $('#productForm').submit();
        }
        
        // Form validation
        $('#productForm').submit(function(e) {
            // Basic validation
            const productName = $('input[name="pro_name"]').val().trim();
            const sku = $('input[name="sku"]').val().trim();
            const sellingPrice = $('input[name="selling_price"]').val();
            const mrp = $('input[name="mrp"]').val();
            
            if (!productName) {
                alert('Product name is required');
                e.preventDefault();
                return false;
            }
            
            if (!sku) {
                alert('SKU is required');
                e.preventDefault();
                return false;
            }
            
            if (parseFloat(sellingPrice) > parseFloat(mrp)) {
                if (!confirm('Selling price is higher than MRP. Continue anyway?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });

        // Event listener for product type change
        document.getElementById('productType').addEventListener('change', function() {
            loadSizeOptions(this.value);
        });

        // Initialize size options based on default product type
        document.addEventListener('DOMContentLoaded', function() {
            const productType = document.getElementById('productType').value;
            if (productType) {
                loadSizeOptions(productType);
            }
        });
    </script>
</body>
</html>