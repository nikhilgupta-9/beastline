<?php
// include_once(__DIR__ . "/../config/connect.php");

// get category 
function get_category_home()
{
    global $conn;

    $sql = "SELECT * FROM `categories` WHERE `show_in_home` = 1 AND status = 1";
    $res = mysqli_query($conn, $sql);

    $categories = [];

    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $categories[] = $row;
        }
    }

    return $categories;
}

// get abouts 
function fetch_about()
{
    global $conn;

    // Fetch all about sections ordered by section_order
    $sql = "SELECT * FROM `about_sections` ORDER BY `section_order` ASC";
    $sql_query = $conn->query($sql);

    if ($sql_query && $sql_query->num_rows > 0) {
        $sections = [];
        while ($row = $sql_query->fetch_assoc()) {
            $sections[] = [
                'title' => $row['title'] ?? '',
                'content' => $row['content'] ?? '',
                'image' => $row['image_url'] ?? '',
                'order' => $row['section_order'] ?? 0
            ];
        }
        return $sections;
    } else {
        // Return a default section if no records found
        return [
            [
                'title' => 'About Us',
                'content' => 'No about us sections found. Please add some content in the admin panel.',
                'image' => '',
                'order' => 1
            ]
        ];
    }
}

// logo 
function get_header_logo()
{
    global $conn;

    $sql_logo = "SELECT * FROM `logos`  order by id desc limit 1";
    $re_logo = mysqli_query($conn, $sql_logo);
    if (mysqli_num_rows($re_logo)) {
        $row = mysqli_fetch_assoc($re_logo);

        return "admin/uploads/" . $row['logo_path'];
    }
}


function get_footer_logo()
{
    global $conn;

    $sql_logo = "SELECT * FROM `logos` where `location` = 'footer' order by id desc limit 1";
    $re_logo = mysqli_query($conn, $sql_logo);
    if (mysqli_num_rows($re_logo)) {
        $row = mysqli_fetch_assoc($re_logo);

        return "admin/backend/uploads/" . $row['logo_path'];
    }
}
// logo end 


// fetch banners 
function get_banner()
{
    global $conn;

    $banners = [];
    $sql_banner = "SELECT * FROM `banners` order by display_order";
    $res_banner = mysqli_query($conn, $sql_banner);

    if ($res_banner) {
        while ($row_banner = mysqli_fetch_assoc($res_banner)) {
            $banners[] = $row_banner;
        }
    }

    return $banners;
}


// get contact us page 
function contact_us()
{
    global $conn;

    if (!$conn || !$conn->ping()) {
        // Connection is not available or already closed
        return null;
    }

    $query = "SELECT * FROM `contacts` LIMIT 1";
    $sql_query = $conn->query($query);

    if ($sql_query && $sql_query->num_rows > 0) {
        $result = $sql_query->fetch_assoc();

        return [
            'phone' => $result['phone'] ?? '',
            'wp_number' => $result['wp_number'] ?? '',
            'telephone' => $result['telephone'] ?? '',
            'address' => $result['address'] ?? '',
            'address2' => $result['address2'] ?? '',
            'email' => $result['email'] ?? '',
            'contact_email' => $result['contact_email'] ?? '',
            'working_hours' => $result['working_hours'] ?? '',
            'facebook' => $result['facebook'] ?? '',
            'instagram' => $result['instagram'] ?? '',
            'twitter' => $result['twitter'] ?? '',
            'linkdin' => $result['linkdin'] ?? '',
            'map' => $result['map'] ?? ''
        ];
    }

    return null; // Or return [] if you prefer
}


// get gallery images 
function get_gallery()
{
    global $conn;

    $sql = "SELECT * FROM `gallery`";
    $sql_query = $conn->query($sql);

    $images = [];

    if ($sql_query && $sql_query->num_rows > 0) {
        while ($result = $sql_query->fetch_assoc()) {
            $images[] = "admin/" . ($result['image_path'] ?? '');
        }
    }

    return $images; // returns an empty array if no records
}


// get products for home page
function get_product(): array
{
    global $conn;

    $sql_pro = "SELECT * FROM `products` WHERE status = 1 ORDER BY id DESC ";
    $res_pro = mysqli_query($conn, $sql_pro);

    $products = [];

    if ($res_pro) {
        while ($row_pro = mysqli_fetch_assoc($res_pro)) {
            $products[] = $row_pro;
        }
    }

    return $products; // returns an array of 6 latest active products
}

function get_sub_category()
{
    global $conn;
    $sub_category = [];

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM `sub_categories` where parent_id = 37209";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        // Log error or handle it appropriately
        error_log("Database error: " . mysqli_error($conn));
        return $sub_category; // Return empty array on error
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $sub_category[] = $row;
    }

    return $sub_category;
}

// fetching trending product 
function get_featured_product()
{
    global $conn;

    $sql = "SELECT * FROM `products` where `trending` = 1 order by id desc limit 8";
    $res = mysqli_query($conn, $sql);


    if (!$res) {
        header("Location: 500.php");
        exit();
    }

    $trendingProducts = []; // ✅ Initialize the array before using
    while ($row = mysqli_fetch_assoc($res)) {
        $trendingProducts[] = $row;
    }

    return $trendingProducts; // ✅ Return the result
}

// blog fetch for home page 
function get_blog_home()
{
    global $conn;

    $sql_blog = "SELECT * FROM `blogs` limit 3";
    $res_blog = mysqli_query($conn, $sql_blog);

    if (!$res_blog) {
        header("Location: 500.php"); // ✅ Remove spaces around colon
        exit(); // ✅ Always add exit after header redirect
    }

    $blog = []; // ✅ Initialize the array before using
    while ($row = mysqli_fetch_assoc($res_blog)) {
        $blog[] = $row;
    }

    return $blog; // ✅ Return the result
}


// blog fetch for blog page 
function get_blog()
{
    global $conn;

    $sql_blog = "SELECT * FROM `blogs` ";
    $res_blog = mysqli_query($conn, $sql_blog);

    if (!$res_blog) {
        header("Location: 500.php"); // ✅ Remove spaces around colon
        exit(); // ✅ Always add exit after header redirect
    }

    $blog = []; // ✅ Initialize the array before using
    while ($row = mysqli_fetch_assoc($res_blog)) {
        $blog[] = $row;
    }

    return $blog; // ✅ Return the result
}

// blog details fetch 
function fetch_blog_detail($slug)
{
    global $conn;
    global $site;

    $blog_slug = mysqli_real_escape_string($conn, $slug);
    // die($slug);

    $sql_blog = "SELECT * FROM `blogs` WHERE `slug_url` = '$blog_slug' LIMIT 1";
    $res_blog = mysqli_query($conn, $sql_blog);

    if (!$res_blog) {
        header("Location: 500.php");
        exit();
    }

    $blog_det = mysqli_fetch_assoc($res_blog);

    if (!$blog_det) {
        header("Location: " . $site . "404.php");
        exit();
    }

    return $blog_det;
}

// product page fetch product 
function fetch_product_page()
{
    global $conn;

    if (!isset($_GET['alias'])) {
        header("Location: index.php");
        exit();
    }

    $alias = mysqli_real_escape_string($conn, $_GET['alias']);

    // Get subcategory information
    $sql1 = "SELECT * FROM `sub_categories` WHERE `slug_url` = '$alias'";
    $res = mysqli_query($conn, $sql1);

    if (!$res || mysqli_num_rows($res) == 0) {
        header("Location: 404.php");
        exit();
    }

    $sub_cat = mysqli_fetch_assoc($res);
    $pro_sub_cate = $sub_cat['cate_id'];
    $_SESSION['sub_cat_name'] = $sub_cat['categories'];
    $meta_title = $sub_cat['meta_title'];
    $meta_key = $sub_cat['meta_key'];
    $meta_desc = $sub_cat['meta_desc'];
}

function fetch_product_details()
{
    global $conn;

    if (!isset($_GET['alias']) || empty($_GET['alias'])) {
        die("Invalid product URL. Alias parameter is missing.");
    }

    $alias = mysqli_real_escape_string($conn, $_GET['alias']);

    $sql = "SELECT * FROM `products` WHERE `slug_url` = '$alias' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        return [
            'pro_name' => $row['pro_name'] ?? '',
            'short_desc' => $row['short_desc'] ?? '',
            'description' => $row['description'] ?? '',
            'pro_sub_cate' => $row['pro_sub_cate'] ?? '',
            'pro_img' => $row['pro_img'] ?? 'image/product-not-found.gif',
            'slug_url' => $row['slug_url'] ?? '',
            'mrp' => $row['mrp'] ?? '00',
            'selling_price' => $row['selling_price'] ?? '00',
            'meta_title' => $row['meta_title'] ?? '',
            'meta_desc' => $row['meta_desc'] ?? '',
            'meta_key' => $row['meta_key'] ?? ''
        ];
    } else {
        // If product not found, return default values
        return [
            'pro_name' => 'No Product Available',
            'short_desc' => '',
            'description' => '',
            'pro_sub_cate' => '',
            'pro_img' => 'image/product-not-found.gif',
            'slug_url' => '',
            'meta_title' => 'Product Not Found',
            'meta_desc' => '',
            'meta_key' => ''
        ];
    }
}


// footer product 
function footer_product()
{
    global $conn;

    $sql_foot = "SELECT * FROM `products` limit 8";
    $res_foot = mysqli_query($conn, $sql_foot);

    $product = [];

    if (!$res_foot) {
        header('Location: 500.php');
    }
    while ($row = mysqli_fetch_assoc($res_foot)) {
        if (!$row) {
            header("Location: 404.php");
        } else {
            $product[] = $row;
        }
    }
    return $product;
}

function testimonial()
{
    global $conn;

    $sql_test = "SELECT * FROM `testimonials`";
    $res_test = mysqli_query($conn, $sql_test);

    $test = [];

    if (!$res_test) {
        header('Location: 500.php');
    } else {
        while ($row = mysqli_fetch_assoc($res_test)) {
            if (!$row) {
                header('Location: 404.php');
            } else {
                $test[] = $row;
            }
        }
    }
    return $test;
}

// faqs 

function faq_home()
{
    global $conn;

    $sql_test = "SELECT * FROM `faqs` WHERE `page_name` = 'home' AND `status` = 1";
    $res_test = mysqli_query($conn, $sql_test);

    $test = [];

    if (!$res_test) {
        header('Location: 500.php');
    } else {
        while ($row = mysqli_fetch_assoc($res_test)) {
            if (!$row) {
                header('Location: 404.php');
            } else {
                $test[] = $row;
            }
        }
    }
    return $test;
}


function faq_course_details()
{
    global $conn;

    $sql_test = "SELECT * FROM `faqs` WHERE `page_name` = 'course-details' AND `status` = 1";
    $res_test = mysqli_query($conn, $sql_test);

    $test = [];

    if (!$res_test) {
        header('Location: 500.php');
    } else {
        while ($row = mysqli_fetch_assoc($res_test)) {
            if (!$row) {
                header('Location: 404.php');
            } else {
                $test[] = $row;
            }
        }
    }
    return $test;
}


// get best brand 
function get_best_brand()
{
    global $conn;

    $sql_brand = "SELECT * FROM `brands`";
    $res_brand = mysqli_query($conn, $sql_brand);

    $brand = [];

    if (!$res_brand) {
        header('Location: 500.php');
    } else {
        while ($row = mysqli_fetch_assoc($res_brand)) {
            if (!$row) {
                header('Location: 404.php');
            } else {
                $brand[] = $row;
            }
        }
    }
    return $brand;
}



// course page related products 
// Get category by slug
function get_category_by_slug($slug) {
    global $conn;
    
    if (empty($slug)) {
        return null;
    }
    
    $slug = mysqli_real_escape_string($conn, $slug);
    $sql = "SELECT * FROM categories WHERE slug_url = '$slug' AND status = 1 LIMIT 1";
    $result = mysqli_query($conn, $sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    
    return null;
}

// Get products by category slug
function get_products_by_category_slug($slug) {
    global $conn;
    
    $category = get_category_by_slug($slug);
    if (!$category) {
        return [];
    }
    
    $category_id = $category['id'];
    $sql = "SELECT p.*, c.categories as category_name
            FROM products p
            LEFT JOIN categories c ON p.pro_cate = c.id
            WHERE p.pro_cate = $category_id 
            AND p.status = 1 
            ORDER BY p.created_on DESC";
    
    $result = mysqli_query($conn, $sql);
    $products = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    
    return $products;
}

// If you already have get_product_by_category function, update it like this:
function get_product_by_category($slug) {
    // For backward compatibility, call the new function
    return get_products_by_category_slug($slug);
}



// Get category hierarchy for breadcrumbs
function get_category_hierarchy($category_id) {
    global $conn;
    
    $hierarchy = [];
    $current_id = $category_id;
    
    while ($current_id > 0) {
        $sql = "SELECT id, categories, slug_url, parent_id 
                FROM categories 
                WHERE id = $current_id AND status = 1";
        $result = mysqli_query($conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $category = mysqli_fetch_assoc($result);
            $hierarchy[] = [
                'id' => $category['id'],
                'name' => $category['categories'],
                'slug' => $category['slug_url'],
                'parent_id' => $category['parent_id']
            ];
            $current_id = $category['parent_id'];
        } else {
            break;
        }
    }
    
    return array_reverse($hierarchy);
}


// set limit words 
function limit_words($string, $word_limit = 20) {
    $words = explode(" ", $string);
    if (count($words) > $word_limit) {
        return implode(" ", array_slice($words, 0, $word_limit)) . "...";
    }
    return $string;
}

// Get categories for sidebar
function get_categories_for_sidebar() {
    global $conn;
    $sql = "SELECT c.*, COUNT(p.pro_id) as product_count 
            FROM categories c 
            LEFT JOIN products p ON c.id = p.pro_cate AND p.status = 1
            WHERE c.parent_id = 0 AND c.status = 1 
            GROUP BY c.id 
            ORDER BY c.display_order";
    $result = mysqli_query($conn, $sql);
    $categories = [];
    while($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    return $categories;
}

// Get subcategories for a parent category
function get_subcategories($parent_id) {
    global $conn;
    $sql = "SELECT * FROM categories 
            WHERE parent_id = $parent_id AND status = 1 
            ORDER BY display_order";
    $result = mysqli_query($conn, $sql);
    $subcategories = [];
    while($row = mysqli_fetch_assoc($result)) {
        $subcategories[] = $row;
    }
    return $subcategories;
}

// Get brands for filter
function get_brands_for_filter() {
    global $conn;
    $sql = "SELECT b.*, COUNT(p.pro_id) as product_count 
            FROM pro_brands b 
            LEFT JOIN products p ON b.id = p.brand_name AND p.status = 1
            WHERE b.status = 1 
            GROUP BY b.id 
            ORDER BY b.brand_name";
    $result = mysqli_query($conn, $sql);
    $brands = [];
    while($row = mysqli_fetch_assoc($result)) {
        $brands[] = $row;
    }
    return $brands;
}

// Get colors for filter
function get_colors_for_filter() {
    global $conn;
    $sql = "SELECT DISTINCT color, COUNT(*) as product_count 
            FROM product_variants 
            WHERE color != '' 
            GROUP BY color 
            ORDER BY color";
    $result = mysqli_query($conn, $sql);
    $colors = [];
    while($row = mysqli_fetch_assoc($result)) {
        $colors[] = $row;
    }
    return $colors;
}

// Get sizes for filter
function get_sizes_for_filter() {
    global $conn;
    $sql = "SELECT DISTINCT size, COUNT(*) as product_count 
            FROM product_variants 
            WHERE size != '' 
            GROUP BY size 
            ORDER BY size";
    $result = mysqli_query($conn, $sql);
    $sizes = [];
    while($row = mysqli_fetch_assoc($result)) {
        $sizes[] = $row;
    }
    return $sizes;
}

// Get product by category with filters
function get_filtered_products($category_id = null, $filters = []) {
    global $conn;
    
    $where_conditions = ["p.status = 1"];
    $join_sql = "";
    $having_sql = "";
    
    // Category filter
    if ($category_id) {
        $where_conditions[] = "p.pro_cate = $category_id";
    }
    
    // Price filter
    if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
        $min_price = floatval($filters['min_price']);
        $max_price = floatval($filters['max_price']);
        $where_conditions[] = "p.selling_price BETWEEN $min_price AND $max_price";
    }
    
    // Brand filter
    if (!empty($filters['brands'])) {
        $brand_ids = implode(',', array_map('intval', $filters['brands']));
        $where_conditions[] = "p.brand_name IN ($brand_ids)";
    }
    
    // Color filter
    if (!empty($filters['colors'])) {
        $colors = array_map(function($color) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $color) . "'";
        }, $filters['colors']);
        $color_list = implode(',', $colors);
        $join_sql .= " LEFT JOIN product_variants pv ON p.pro_id = pv.product_id";
        $where_conditions[] = "pv.color IN ($color_list)";
        $having_sql = " GROUP BY p.pro_id";
    }
    
    // Size filter
    if (!empty($filters['sizes'])) {
        $sizes = array_map(function($size) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $size) . "'";
        }, $filters['sizes']);
        $size_list = implode(',', $sizes);
        if (!strpos($join_sql, "product_variants")) {
            $join_sql .= " LEFT JOIN product_variants pv ON p.pro_id = pv.product_id";
        }
        $where_conditions[] = "pv.size IN ($size_list)";
        $having_sql = " GROUP BY p.pro_id";
    }
    
    // Material filter
    if (!empty($filters['materials'])) {
        $materials = array_map(function($mat) use ($conn) {
            return "'" . mysqli_real_escape_string($conn, $mat) . "'";
        }, $filters['materials']);
        $material_list = implode(',', $materials);
        $where_conditions[] = "p.material IN ($material_list)";
    }
    
    $where_sql = implode(' AND ', $where_conditions);
    $where_sql = $where_sql ? "WHERE $where_sql" : "";
    
    // Sorting
    $order_by = "p.created_on DESC";
    if (!empty($filters['sort'])) {
        switch($filters['sort']) {
            case 'price_low_high':
                $order_by = "p.selling_price ASC";
                break;
            case 'price_high_low':
                $order_by = "p.selling_price DESC";
                break;
            case 'name_asc':
                $order_by = "p.pro_name ASC";
                break;
            case 'name_desc':
                $order_by = "p.pro_name DESC";
                break;
            case 'newest':
                $order_by = "p.added_on DESC";
                break;
            case 'popular':
                $order_by = "p.view_count DESC";
                break;
        }
    }
    
    $sql = "SELECT DISTINCT p.*, 
                   c.categories as category_name,
                   b.brand_name as brand_display
            FROM products p
            LEFT JOIN categories c ON p.pro_cate = c.id
            LEFT JOIN pro_brands b ON p.brand_name = b.id
            $join_sql
            $where_sql
            $having_sql
            ORDER BY $order_by";
    
    $result = mysqli_query($conn, $sql);
    $products = [];
    while($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    return $products;
}

// Get min and max price for price filter
function get_price_range($category_id = null) {
    global $conn;
    
    $where = $category_id ? "WHERE pro_cate = $category_id AND status = 1" : "WHERE status = 1";
    
    $sql = "SELECT MIN(selling_price) as min_price, MAX(selling_price) as max_price 
            FROM products $where";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

// Add to cart function
function add_to_cart($product_id, $quantity = 1, $variant_id = null) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    $cart_item_key = $variant_id ? "{$product_id}_{$variant_id}" : $product_id;
    
    if (isset($_SESSION['cart'][$cart_item_key])) {
        $_SESSION['cart'][$cart_item_key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$cart_item_key] = [
            'product_id' => $product_id,
            'variant_id' => $variant_id,
            'quantity' => $quantity
        ];
    }
    
    return count($_SESSION['cart']);
}

// Get cart items
function get_cart_items() {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    
    global $conn;
    $cart_items = [];
    $total = 0;
    
    foreach ($_SESSION['cart'] as $key => $item) {
        $sql = "SELECT p.*, pv.price as variant_price, pv.sku as variant_sku, 
                       pv.color, pv.size, pv.image as variant_image
                FROM products p
                LEFT JOIN product_variants pv ON pv.id = " . ($item['variant_id'] ? intval($item['variant_id']) : 'NULL') . "
                WHERE p.pro_id = " . intval($item['product_id']);
        $result = mysqli_query($conn, $sql);
        
        if ($product = mysqli_fetch_assoc($result)) {
            $price = $item['variant_id'] ? $product['variant_price'] : $product['selling_price'];
            $subtotal = $price * $item['quantity'];
            
            $cart_items[] = [
                'key' => $key,
                'product' => $product,
                'quantity' => $item['quantity'],
                'price' => $price,
                'subtotal' => $subtotal,
                'variant_id' => $item['variant_id']
            ];
            
            $total += $subtotal;
        }
    }
    
    return ['items' => $cart_items, 'total' => $total];
} 


/**
 * Get products by category ID
 */
function get_products_by_category($category_id, $limit = 8) {
    global $conn;
    
    $sql = "SELECT p.*, 
                   pi.image_url as product_image
            FROM products p
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.display_order = 1
            WHERE p.pro_cate = ? 
            AND p.status = 1
            ORDER BY p.added_on DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $category_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = [];
    while ($row = $result->fetch_assoc()) {
        // Format prices
        $row['formatted_price'] = '₹' . number_format($row['mrp'], 2);
        if ($row['selling_price'] > 0) {
            $row['formatted_sale_price'] = '₹' . number_format($row['selling_price'], 2);
        }
        $products[] = $row;
    }
    
    return $products;
}