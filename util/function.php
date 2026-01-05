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

function faq_courses()
{
    global $conn;

    $sql_test = "SELECT * FROM `faqs` WHERE `page_name` = 'courses' AND `status` = 1";
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


function get_course($slug)
{
    global $conn;

    // Validate and sanitize the input
    if (empty($slug)) {
        return []; // Return empty array if no slug provided
    }

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM `sub_categories` WHERE `slug_url` = ? AND `status` = 1";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        // Log the error instead of redirecting immediately
        error_log("Database error: " . mysqli_error($conn));
        return []; // Return empty array on error
    }

    // Bind parameters
    mysqli_stmt_bind_param($stmt, "s", $slug);

    // Execute query
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Query execution failed: " . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        return [];
    }

    // Get result
    $result = mysqli_stmt_get_result($stmt);
    $course = mysqli_fetch_assoc($result); // Get single course (assuming slug is unique)

    mysqli_stmt_close($stmt);

    return $course ?: []; // Return course data or empty array if not found
}

// get course details page 
function get_course_details($slug)
{
    global $conn;
    global $site;

    // Prevent SQL Injection
    $slug = mysqli_real_escape_string($conn, $slug);

    $sql_brand = "SELECT * FROM `products` WHERE `slug_url` = '$slug' LIMIT 1";
    $res_brand = mysqli_query($conn, $sql_brand);

    if (!$res_brand) {
        header('Location: 500.php');
        exit;
    }

    $row = mysqli_fetch_assoc($res_brand);

    if (!$row) {
        header('Location: ' . $site . '404.php');
        exit;
    }

    return $row; // return single record
}



// course page related products 
function related_course($id)
{
    global $conn;

    $sql_brand = "SELECT * FROM `products` where `pro_sub_cate` = $id";
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


// get course video on product detail page 
function get_course_video($ids)
{
    global $conn;

    // Validate input
    if (!is_numeric($ids)) {
        header('Location: 404.php');
        exit;
    }

    // Initialize empty array
    $lessons = [];

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM `lessons` WHERE `course_id` = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        header('Location: 500.php');
        exit;
    }

    mysqli_stmt_bind_param($stmt, "i", $ids);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result) {
        header('Location: 500.php');
        exit;
    }

    // Fetch all lessons
    while ($row = mysqli_fetch_assoc($result)) {
        $lessons[] = $row;
    }

    // If no lessons found, you might want to handle this case
    if (empty($lessons)) {
        // Either return empty array or redirect - depends on your requirements
        header('Location: 404.php');
        exit;
    }

    mysqli_stmt_close($stmt);
    return $lessons;
}


function course_detail_first_video($id)
{
    global $conn;

    $sql_brand = "SELECT * FROM `lessons` WHERE `course_id` = $id";
    $res_brand = mysqli_query($conn, $sql_brand);

    $brand = [];

    if (!$res_brand) {
        header('Location: 500.php');
        exit;
    }

    while ($row = mysqli_fetch_assoc($res_brand)) {
        $brand[] = $row;
    }

    return $brand; // returns all lessons for this course_id
}


function course_detail_related_video($id)
{
    global $conn;

    // Ensure id is integer
    $id = (int)$id;

    // Step 1: Get main product
    $sql = "SELECT * FROM `products` WHERE `id` = $id LIMIT 1";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        header('Location: 500.php');
        exit;
    }

    $row = mysqli_fetch_assoc($res);

    if (!$row) {
        header('Location: 404.php');
        exit;
    }

    // Step 2: Get sub category id
    $course_id = (int)$row['pro_sub_cate'];

    if (!$course_id) {
        echo "No Related Video Found !";
        return [];
    }

    // Step 3: Get all related products (same sub category)
    $sql = "SELECT id FROM `products` WHERE `pro_sub_cate` = $course_id";
    $res = mysqli_query($conn, $sql);

    if (!$res) {
        header('Location: 500.php');
        exit;
    }

    $allLessons = []; // store lessons of all related products
    while ($row = mysqli_fetch_assoc($res)) {
        $productId = $row['id'];

        // 👇 Call first function here
        $lessons = course_detail_first_video($productId);

        if (!empty($lessons)) {
            $allLessons[$productId] = $lessons; 
        }
    }

    // Debug: print lessons
    // echo "<pre>";
    // print_r($allLessons);
    // echo "</pre>";

    return $allLessons; // returns all lessons grouped by product id
}




// set limit words 
function limit_words($string, $word_limit = 20) {
    $words = explode(" ", $string);
    if (count($words) > $word_limit) {
        return implode(" ", array_slice($words, 0, $word_limit)) . "...";
    }
    return $string;
}

