<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once(__DIR__ . "/config/db-conn.php");

if (isset($_POST["add-categories"])) {
    // Initialize variables
    $errors = [];
    $cate_id = mt_rand(11111, 99999);
    $cate_name = mysqli_real_escape_string($conn, $_POST["cate_name"]);
    $meta_title = mysqli_real_escape_string($conn, $_POST["meta_title"]);
    $meta_key = mysqli_real_escape_string($conn, $_POST["meta_key"]);
    $meta_desc = mysqli_real_escape_string($conn, $_POST["meta_desc"]);
    $status = isset($_POST["status"]) ? (int)$_POST["status"] : 1;
    $slug_url = strtolower(str_replace(" ", "-", $cate_name));
    $image_name = "";

    // Image upload handling
    if (!empty($_FILES['imageUpload']['name'])) {
        $upload_dir = "uploads/category/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name = time() . "_" . basename($_FILES["imageUpload"]["name"]);
        $target_file = $upload_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate image
        $check = getimagesize($_FILES["imageUpload"]["tmp_name"]);
        if ($check === false) {
            $errors[] = "File is not an image.";
        }

        // Check file size (5MB max)
        if ($_FILES["imageUpload"]["size"] > 5000000) {
            $errors[] = "Image is too large (max 5MB).";
        }

        // Allow certain file formats
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($imageFileType, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
        }

        if (empty($errors)) {
            if (move_uploaded_file($_FILES["imageUpload"]["tmp_name"], $target_file)) {
                $image_name = $file_name;
            } else {
                $errors[] = "Sorry, there was an error uploading your file.";
            }
        }
    } else {
        $errors[] = "Please select a category image.";
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $sql = "INSERT INTO `categories` (`cate_id`, `categories`, `meta_title`, `meta_desc`, `meta_key`, `image`, `slug_url`, `status`, `added_on`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issssssi", $cate_id, $cate_name, $meta_title, $meta_desc, $meta_key, $image_name, $slug_url, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Category added successfully!";
            header("Location: view-categories.php");
            exit();
        } else {
            // Delete uploaded image if DB insert failed
            if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                unlink($upload_dir . $image_name);
            }
            $errors[] = "Database error: " . $conn->error;
        }
    }

    // If there were errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}



if (isset($_POST["add-sub-categories"])) {

    $uploadedImage = ''; // default empty value
    if (isset($_FILES['imageUpload']) && $_FILES['imageUpload']['error'] === UPLOAD_ERR_OK) {
        // File details
        $fileTmpPath = $_FILES['imageUpload']['tmp_name'];
        $fileName = $_FILES['imageUpload']['name'];
        // $fileSize and $fileType are retrieved but not used
        // $fileSize = $_FILES['imageUpload']['size'];
        // $fileType = $_FILES['imageUpload']['type'];

        // Get file extension
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Allowed file extensions
        $allowedExtensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');

        // Check if file type is allowed
        if (in_array($fileExtension, $allowedExtensions)) {
            // Create a unique filename
            $newFileName = uniqid('img_', true) . '.' . $fileExtension;

            // Define upload directory
            $uploadDir = 'uploads/sub-category/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // Create with full permissions
            }

            // Final file destination
            $destPath = $uploadDir . $newFileName;

            // Move uploaded file
            if (move_uploaded_file($fileTmpPath, $destPath)) {
                echo "✅ File uploaded successfully! <br>";
                echo "📂 Saved at: <a href='$destPath'>$destPath</a>";
                $uploadedImage = $newFileName; // use the new unique filename for database
            } else {
                echo "❌ Error: Could not move file!";
            }
        } else {
            echo "❌ Error: Only JPG, JPEG, PNG, GIF, and WEBP files are allowed!";
        }
    }

    // If no file was uploaded, you may want to set a default image filename or leave empty.
    if (empty($uploadedImage)) {
        // echo "<script>alert('Image is not uploaded!!')</script>";
        // $uploadedImage = 'default_image.jpg'; 
        // or empty string if you wish
    }

    $cate_id = mt_rand(11111, 99999);
    $cate_name = mysqli_real_escape_string($conn, $_POST["cate_name"]);
    $meta_title = mysqli_real_escape_string($conn, $_POST["meta_title"]);
    $meta_key = mysqli_real_escape_string($conn, $_POST["meta_key"]);
    $meta_desc = mysqli_real_escape_string($conn, $_POST["meta_desc"]);
    $added_on = date('M d, Y');
    $parent_id = mysqli_real_escape_string($conn, $_POST['parent_id']);
    $slug_url = strtolower(str_replace(" ", "-", $cate_name));

    $sql = "INSERT INTO `sub_categories`( `parent_id`,`cate_id`, `categories`, `meta_title`, `meta_desc`, `meta_key`, `sub_cat_img`, `slug_url`, `status`, `added_on`) 
            VALUES ('$parent_id','$cate_id','$cate_name','$meta_title','$meta_desc','$meta_key', '$uploadedImage', '$slug_url', 1, '$added_on')";

    $check = mysqli_query($conn, $sql);
    if ($check) {
        ?>
        <script type="text/javascript">
            alert('Add sub category Successfully!');
            window.location.href = "view-sub-categories.php";
        </script>
        <?php
    } else {
        echo "Error inserting record: " . mysqli_error($conn);
    }
}

function get_Category() {
    include "db-conn.php";

    $sql = "SELECT * FROM `categories` ORDER BY id DESC";
    $check = mysqli_query($conn, $sql);
    $sno = 1;
    
    while ($result = mysqli_fetch_assoc($check)) {
        // Format status with badge
        $status = $result['status'] == '1' 
            ? '<span class="badge bg-success bg-opacity-10 text-success text-light">Active</span>'
            : '<span class="badge bg-danger bg-opacity-10 text-danger text-light">Inactive</span>';
        
        // Format date
        $added_on = date('d M Y', strtotime($result['added_on']));
        
        echo "<tr>
            <td class='text-center'>" . $sno++ . "</td>
            <td class='fw-semibold'>" . $result['cate_id'] . "</td>
            <td class='text-capitalize'>" . $result['categories'] . "</td>
            <td><span class='text-muted'>" . $result['slug_url'] . "</span></td>
            <td class='text-center'>" . $status . "</td>
            <td class='text-center'>" . $added_on . "</td>
            <td class='text-center'>
                <div class='d-flex justify-content-center gap-2'>
                    <a href='edit_category.php?id=" . $result['cate_id'] . "' class='btn btn-sm btn-outline-primary rounded-circle p-2' data-bs-toggle='tooltip' title='Edit'>
                        <i class='fas fa-pen fs-6'></i>
                    </a>
                    <a href='delete-category.php?id=" . $result['cate_id'] . "' onclick='return confirm(\"Are you sure you want to delete this category?\")' class='btn btn-sm btn-outline-danger rounded-circle p-2' data-bs-toggle='tooltip' title='Delete'>
                        <i class='fas fa-trash fs-6'></i>
                    </a>
                </div>
            </td>
        </tr>";
    }
}

// if(isset($_POST["add-product"])){
//     $pro_id = mt_rand(11111, 99999);
//     $pro_name = $_POST['pro_name'];
//     $pro_cate = $_POST['pro_cate'];
//     $pro_sub_cate = $_POST['pro_sub_cate'];
//     $description = $_POST['pro_desc'];
//     $new_arrival = $_POST['new_arrival'];
//     $mrp = $_POST['mrp'];
//     $selling_price = $_POST['selling_price'];
//     $stock = $_POST['stock'];
//     $status = $_POST['status'];

//     $filename = $_FILES['pro_img']['name'];
//     $tmepname = $_FILES['pro_img']['tmp_name'];
//     $destination = 'assests/img/uploads/'.$filename;
//     move_uploaded_file($tmepname,$destination);

//     $meta_title = $_POST["meta_title"];
//     $meta_key = $_POST["meta_key"];
//     $meta_desc = $_POST["meta_desc"];
//     $added_on = date('M d, Y');
//     $slug_url = SlugUrl($pro_name); 


//     $sql ="INSERT INTO `products`(`pro_id`, `pro_name`, `pro_cate`, `pro_sub_cate`, `short_desc`, `description`,`new_arrival`, `mrp`, `selling_price`, `stock`, `pro_img`, `status`,`slug_url`, `meta_title`, `meta_desc`, `meta_key`, `added_on`) VALUES ('$pro_id','$pro_name','$pro_cate','$pro_sub_cate','$description','$new_arrival','$mrp','$selling_price','$stock','$status','$slug_url','$filename','$meta_title','$meta_key','$meta_desc','$added_on','$added_on')";

//     $check = mysqli_query($conn, $sql);
//     if($check){
//         


if (isset($_POST["add-product"])) {
    $pro_id = mt_rand(11111, 99999);
    $pro_name = mysqli_real_escape_string($conn, $_POST['pro_name']);
    $brand_name = mysqli_real_escape_string($conn, $_POST['brand_name']);
    $pro_cate = mysqli_real_escape_string($conn, $_POST['pro_cate']);
    $pro_sub_cate = mysqli_real_escape_string($conn, $_POST['pro_sub_cate']);
    $short_description = mysqli_real_escape_string($conn, $_POST['short_desc']);
    $description = mysqli_real_escape_string($conn, $_POST['pro_desc']);
    $new_arrival = mysqli_real_escape_string($conn, $_POST['new_arrival']);
    $trending = mysqli_real_escape_string($conn, $_POST['trending']);
    $whole_sale_selling_price = mysqli_real_escape_string($conn, $_POST['whole_selling_price']);
    $qty = mysqli_real_escape_string($conn, $_POST['qty']);
    $mrp = mysqli_real_escape_string($conn, $_POST['mrp']);
    $selling_price = mysqli_real_escape_string($conn, $_POST['selling_price']);
    $stock = mysqli_real_escape_string($conn, $_POST['stock']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Ensure the folder exists and is writable
    $folder = 'assets/img/uploads/';
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    // Loop through each uploaded file
    foreach ($_FILES['pro_img']['tmp_name'] as $key => $tempname) {
        // Get the original file name for the current image
        $filename = $_FILES['pro_img']['name'][$key];
        $destination = $folder . $filename;

        // Move the uploaded file to the target directory
        if (move_uploaded_file($tempname, $destination)) {
            echo "Image uploaded successfully: " . $filename . "<br>";
        } else {
            echo "Failed to upload image: " . $filename . "<br>";
        }
    }



    $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title']);
    $meta_key = mysqli_real_escape_string($conn, $_POST["meta_key"]);
    $meta_desc = mysqli_real_escape_string($conn, $_POST["meta_desc"]);
    $added_on = date('M d, Y');
    $slug_url = strtolower(str_replace(" ", "-", $pro_name));
    // $slug_url = SlugUrl($pro_name); 
    // Assuming this function generates a valid slug

    // Corrected SQL query
    $sql = $sql = "INSERT INTO `products`(`pro_id`, `pro_name`,`brand_name` , `pro_cate`, `pro_sub_cate`, `short_desc`, `description`, `new_arrival`,`trending`, `qty`, `mrp`, `selling_price`, `whole_sale_selling_price`, `stock`, `pro_img`, `status`, `slug_url`, `meta_title`, `meta_desc`, `meta_key`, `added_on`) 
VALUES ('$pro_id', '$pro_name', '$brand_name','$pro_cate', '$pro_sub_cate', '$short_description', '$description', '$new_arrival', '$trending', '$qty','$mrp', '$selling_price', '$whole_sale_selling_price', '$stock', '$filename', '$status', '$slug_url', '$meta_title', '$meta_desc', '$meta_key', '$added_on')";


    // Execute the query
    $check = mysqli_query($conn, $sql);
    if ($check) {
        ?>
        <script type="text/javascript">
            alert('Inserted Successfully!');
            window.location.href = "add-products.php";
        </script>
        <?php
    } else {
        echo "Error: " . mysqli_error($conn);  // Optional: Display any error message from MySQL
    }
}





function get_Sub_Category() {
    include "db-conn.php";

    // Search functionality
    $searchQuery = "";
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search = mysqli_real_escape_string($conn, $_GET['search']);
        $searchQuery = " WHERE sc.`categories` LIKE '%$search%' 
                        OR sc.`slug_url` LIKE '%$search%' 
                        OR c.`categories` LIKE '%$search%'";
    }

    // Optimized query with JOIN to get parent category in one query
    $sql = "SELECT sc.*, c.categories as parent_category 
            FROM `sub_categories` sc
            LEFT JOIN `categories` c ON sc.parent_id = c.cate_id
            $searchQuery 
            ORDER BY sc.id DESC";
    
    $check = mysqli_query($conn, $sql);
    $sno = 1;

    if ($check && mysqli_num_rows($check) > 0) {
        while ($result = mysqli_fetch_assoc($check)) {
            // Status badge
            $status = $result['status'] == '1' 
                ? '<span class="badge bg-success bg-opacity-10 text-success text-light">Active</span>'
                : '<span class="badge bg-danger bg-opacity-10 text-danger text-light">Inactive</span>';
            
            // Format date
            $added_on = date('d M Y', strtotime($result['added_on']));
            
            echo "<tr>
                    <td class='text-center'>" . $sno++ . "</td>
                    <td class='fw-semibold'>" . htmlspecialchars($result['cate_id']) . "</td>
                    <td class='text-capitalize'>" . htmlspecialchars(ucwords($result['categories'])) . "</td>
                    <td>" . htmlspecialchars($result['parent_category'] ?? 'N/A') . "</td>
                    <td><span class='text-muted'>" . htmlspecialchars($result['slug_url']) . "</span></td>
                    <td class='text-center'>" . $status . "</td>
                    <td class='text-center'>" . $added_on . "</td>
                    <td class='text-center'>
                        <div class='d-flex justify-content-center gap-2'>
                            <a href='edit_sub_category.php?id=" . $result['cate_id'] . "' 
                               class='btn btn-sm btn-outline-primary rounded-circle p-2' 
                               data-bs-toggle='tooltip' title='Edit'>
                                <i class='fas fa-pen fs-6'></i>
                            </a>
                            <a href='delete_sub_category.php?id=" . $result['cate_id'] . "' 
                               onclick='return confirm(\"Are you sure you want to delete this sub-category?\")' 
                               class='btn btn-sm btn-outline-danger rounded-circle p-2'
                               data-bs-toggle='tooltip' title='Delete'>
                                <i class='fas fa-trash fs-6'></i>
                            </a>
                        </div>
                    </td>
                </tr>";
        }
    } else {
        echo "<tr>
                <td colspan='9' class='text-center py-4'>
                    <div class='d-flex flex-column align-items-center'>
                        <i class='fas fa-folder-open fs-1 text-muted mb-2'></i>
                        <span class='text-muted'>No subcategories found</span>
                    </div>
                </td>
              </tr>";
    }
}


if (isset($_POST['cate_id'])) {
    $p_id = $_POST['cate_id'];
    $sql = "SELECT * FROM `sub_categories` where `parent_id` = '$p_id' ORDER BY id DESC";
    $check = mysqli_query($conn, $sql);
    ?>
    <option value="">Select</option>
    <?php
    while ($result = mysqli_fetch_assoc($check)) {
        echo "<option value=" . $result['cate_id'] . ">" . $result['categories'] . "</option>";
    }
}

function SlugUrl($string)
{
    $slug = preg_replace('/[^a-zA-Z0-9 -]/', '', $string);
    $slug = str_replace('', '-', $slug);
    $slug = strtolower($slug);
    return ($slug);
}



// Get a single category by ID
function get_category_by_id($cat_id)
{
    global $conn;
    $cat_id = mysqli_real_escape_string($conn, $cat_id);
    $sql = "SELECT * FROM `categories` WHERE cate_id = '$cat_id' LIMIT 1";
    $result = mysqli_query($conn, $sql);
    return mysqli_fetch_assoc($result);
}

// Update a category



if (isset($_POST['action']) && $_POST['action'] === 'get_sub_category_by_id') {
    $cat_id = (int) $_POST['category_id'];
    get_sub_category_by_id($cat_id);
    exit;
}

function get_sub_category_by_id($cat_id)
{
    global $conn;

    $sql = "SELECT id, categories FROM categories WHERE parent_id = '$cat_id'";
    $result = mysqli_query($conn, $sql);

    echo '<option value="">Select Sub Category</option>';

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<option value="' . $row['id'] . '">' .
                 htmlspecialchars($row['categories']) .
                 '</option>';
        }
    }
}

function get_testimonial_by_id($id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function update_testimonial($id, $client_name, $client_title, $client_company, $client_photo, 
                          $testimonial_text, $rating, $project_name, $project_date, 
                          $featured, $display_order) {
    global $conn;
    $stmt = $conn->prepare("UPDATE testimonials SET 
                           client_name = ?, 
                           client_title = ?, 
                           client_company = ?, 
                           client_photo = ?, 
                           testimonial_text = ?, 
                           rating = ?, 
                           project_name = ?, 
                           project_date = ?, 
                           featured = ?, 
                           display_order = ?, 
                           updated_at = NOW() 
                           WHERE id = ?");
    $stmt->bind_param("sssssisssii", 
                      $client_name, $client_title, $client_company, $client_photo, 
                      $testimonial_text, $rating, $project_name, $project_date, 
                      $featured, $display_order, $id);
    return $stmt->execute();
}



// Handle add testimonial
if (isset($_POST['add-testimonial'])) {
    // Validate and sanitize input
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    $client_title = mysqli_real_escape_string($conn, $_POST['client_title']);
    $client_company = mysqli_real_escape_string($conn, $_POST['client_company']);
    $testimonial_text = mysqli_real_escape_string($conn, $_POST['testimonial_text']);
    $rating = intval($_POST['rating']);
    $project_name = mysqli_real_escape_string($conn, $_POST['project_name']);
    $project_date = mysqli_real_escape_string($conn, $_POST['project_date']);
    $featured = isset($_POST['featured']) ? 1 : 0;
    $display_order = intval($_POST['display_order']);
    
    // Handle file upload
    $client_photo = '';
    if (isset($_FILES['client_photo']) && $_FILES['client_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/testimonials/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . '_' . basename($_FILES['client_photo']['name']);
        $target_path = $upload_dir . $file_name;
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES['client_photo']['tmp_name']);
        if ($check !== false) {
            // Move the uploaded file
            if (move_uploaded_file($_FILES['client_photo']['tmp_name'], $target_path)) {
                $client_photo = $file_name;
            }
        }
    }
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO testimonials (
        client_name, 
        client_title, 
        client_company, 
        client_photo, 
        testimonial_text, 
        rating, 
        project_name, 
        project_date, 
        featured, 
        display_order,
        created_at,
        updated_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
    
    $stmt->bind_param(
        "sssssisssi", 
        $client_name, 
        $client_title, 
        $client_company, 
        $client_photo, 
        $testimonial_text, 
        $rating, 
        $project_name, 
        $project_date, 
        $featured, 
        $display_order
    );
    
    if ($stmt->execute()) {
        $testimonial_id = $stmt->insert_id;
        $_SESSION['success'] = "Testimonial added successfully!";
        header("Location: testimonials.php?edit=" . $testimonial_id);
        exit();
    } else {
        $_SESSION['error'] = "Error adding testimonial: " . $conn->error;
        header("Location: add-testimonial.php");
        exit();
    }
}

// Handle update testimonial
if (isset($_POST['update-testimonial'])) {
    $testimonial_id = intval($_POST['testimonial_id']);
    
    // Validate and sanitize input (same as above)
    $client_name = mysqli_real_escape_string($conn, $_POST['client_name']);
    // ... [all other fields same as above]
    
    // First get current photo
    $current_photo = '';
    $stmt = $conn->prepare("SELECT client_photo FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $testimonial_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $current_photo = $row['client_photo'];
    }
    
    // Handle file upload
    $client_photo = $current_photo;
    if (isset($_FILES['client_photo']) && $_FILES['client_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/testimonials/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Delete old photo if exists
        if (!empty($current_photo)) {
            $old_file = $upload_dir . $current_photo;
            if (file_exists($old_file)) {
                unlink($old_file);
            }
        }
        
        $file_name = time() . '_' . basename($_FILES['client_photo']['name']);
        $target_path = $upload_dir . $file_name;
        
        $check = getimagesize($_FILES['client_photo']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['client_photo']['tmp_name'], $target_path)) {
                $client_photo = $file_name;
            }
        }
    }
    
    // Update database
    $stmt = $conn->prepare("UPDATE testimonials SET 
        client_name = ?, 
        client_title = ?, 
        client_company = ?, 
        client_photo = ?, 
        testimonial_text = ?, 
        rating = ?, 
        project_name = ?, 
        project_date = ?, 
        featured = ?, 
        display_order = ?,
        updated_at = NOW()
        WHERE id = ?");
    
    $stmt->bind_param(
        "sssssisssii", 
        $client_name, 
        $client_title, 
        $client_company, 
        $client_photo, 
        $testimonial_text, 
        $rating, 
        $project_name, 
        $project_date, 
        $featured, 
        $display_order,
        $testimonial_id
    );
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Testimonial updated successfully!";
        header("Location: testimonials.php?edit=" . $testimonial_id);
        exit();
    } else {
        $_SESSION['error'] = "Error updating testimonial: " . $conn->error;
        header("Location: testimonials.php?edit=" . $testimonial_id);
        exit();
    }
}

function handleTestimonialSubmission($conn) {
    // Check if form was submitted
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['status' => 'error', 'message' => 'Invalid request method'];
    }

    // Initialize variables with sanitized input
    $client_name = trim(filter_input(INPUT_POST, 'client_name', FILTER_SANITIZE_STRING));
    $client_title = trim(filter_input(INPUT_POST, 'client_title', FILTER_SANITIZE_STRING));
    $client_company = trim(filter_input(INPUT_POST, 'client_company', FILTER_SANITIZE_STRING));
    $testimonial_text = trim(filter_input(INPUT_POST, 'testimonial_text', FILTER_SANITIZE_STRING));
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
    $project_name = trim(filter_input(INPUT_POST, 'project_name', FILTER_SANITIZE_STRING));
    $project_date = trim(filter_input(INPUT_POST, 'project_date', FILTER_SANITIZE_STRING));
    $featured = isset($_POST['featured']) ? 1 : 0;
    $display_order = filter_input(INPUT_POST, 'display_order', FILTER_VALIDATE_INT);
    $testimonial_id = filter_input(INPUT_POST, 'testimonial_id', FILTER_VALIDATE_INT);
    $is_edit = isset($_POST['update-testimonial']);

    // Validate required fields
    if (empty($client_name) || empty($client_title) || empty($testimonial_text) || !$rating) {
        return ['status' => 'error', 'message' => 'Please fill all required fields'];
    }

    // Handle file upload
    $client_photo = null;
    $upload_dir = '../uploads/testimonials/';
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES['client_photo']) && $_FILES['client_photo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['client_photo'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($file_ext, $allowed_ext)) {
            return ['status' => 'error', 'message' => 'Invalid file type. Only JPG, PNG, GIF are allowed'];
        }
        
        if ($file['size'] > 5242880) { // 5MB
            return ['status' => 'error', 'message' => 'File size exceeds 5MB limit'];
        }
        
        // Generate unique filename
        $client_photo = uniqid('testimonial_', true) . '.' . $file_ext;
        $destination = $upload_dir . $client_photo;
        
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['status' => 'error', 'message' => 'Failed to upload file'];
        }
        
        // If editing, delete old photo
        if ($is_edit && !empty($_POST['existing_photo'])) {
            $old_photo = $upload_dir . $_POST['existing_photo'];
            if (file_exists($old_photo)) {
                unlink($old_photo);
            }
        }
    } elseif ($is_edit) {
        // Keep existing photo if not uploading new one during edit
        $client_photo = $_POST['existing_photo'] ?? null;
    }

    // Prepare data for database
    $current_time = date('Y-m-d H:i:s');
    $project_date = !empty($project_date) ? $project_date : null;
    $display_order = $display_order !== false ? $display_order : 0;

    try {
        if ($is_edit && $testimonial_id) {
            // Update existing testimonial
            $stmt = $conn->prepare("UPDATE testimonials SET 
                client_name = ?, 
                client_title = ?, 
                client_company = ?, 
                client_photo = COALESCE(?, client_photo), 
                testimonial_text = ?, 
                rating = ?, 
                project_name = ?, 
                project_date = ?, 
                featured = ?, 
                display_order = ?, 
                updated_at = ?
                WHERE id = ?");
            
            $stmt->bind_param("sssssisssisi", 
                $client_name, 
                $client_title, 
                $client_company, 
                $client_photo, 
                $testimonial_text, 
                $rating, 
                $project_name, 
                $project_date, 
                $featured, 
                $display_order, 
                $current_time, 
                $testimonial_id);
        } else {
            // Insert new testimonial
            $stmt = $conn->prepare("INSERT INTO testimonials (
                client_name, 
                client_title, 
                client_company, 
                client_photo, 
                testimonial_text, 
                rating, 
                project_name, 
                project_date, 
                featured, 
                display_order, 
                created_at, 
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssisssiss", 
                $client_name, 
                $client_title, 
                $client_company, 
                $client_photo, 
                $testimonial_text, 
                $rating, 
                $project_name, 
                $project_date, 
                $featured, 
                $display_order, 
                $current_time, 
                $current_time);
        }

        if ($stmt->execute()) {
            return [
                'status' => 'success', 
                'message' => $is_edit ? 'Testimonial updated successfully' : 'Testimonial added successfully',
                'testimonial_id' => $is_edit ? $testimonial_id : $stmt->insert_id
            ];
        } else {
            return ['status' => 'error', 'message' => 'Database error: ' . $stmt->error];
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}

// Handle form submission
if (isset($_POST['add-testimonial']) || isset($_POST['update-testimonial'])) {
    $result = handleTestimonialSubmission($conn);
    
    // Return JSON response for AJAX or redirect for normal form submission
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else {
        // Store result in session for display after redirect
        session_start();
        $_SESSION['form_result'] = $result;
        
        $redirect_url = isset($_POST['update-testimonial']) ? 
            'testimonials.php?edit=' . $_POST['testimonial_id'] : 
            'testimonials.php';
        
        header('Location: ' . $redirect_url);
        exit;
    }
}


function get_order_by_id($order_id) {
    global $conn; // assuming $conn is your MySQLi connection

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM orders_new WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        return false;
    }
}

?>