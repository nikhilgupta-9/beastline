<?php include('auth_check.php'); ?>
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/php_errors.log');
include "db-conn.php";
include "functions.php";

// Get category id from URL
 // Ensure id is an integer and valid
 $sub_cat_id = intval($_GET['id']);
 if ($sub_cat_id <= 0) {
     die('Invalid Sub-Category ID');
 }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require 'db-conn.php'; // Ensure database connection is included

   

    // Collect form values and sanitize
    $parent_id = mysqli_real_escape_string($conn, $_POST['parent_id']);
    $cate_id = mysqli_real_escape_string($conn, $_POST['cate_id']);
    $categories = mysqli_real_escape_string($conn, $_POST['categories']);
    $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title']);
    $meta_desc = mysqli_real_escape_string($conn, $_POST['meta_desc']);
    $meta_key = mysqli_real_escape_string($conn, $_POST['meta_key']);
    $slug_url = mysqli_real_escape_string($conn, $_POST['slug_url']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $added_on = date('Y-m-d H:i:s');

    $imageName = null;
$uploadDir = 'uploads/sub-category/';

if (!empty($_FILES['imageUpload']['name']) && $_FILES['imageUpload']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['imageUpload']['tmp_name'];
    $fileExtension = strtolower(pathinfo($_FILES['imageUpload']['name'], PATHINFO_EXTENSION));
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($fileExtension, $allowedExtensions)) {
        echo "❌ Invalid file type. Allowed types: jpg, jpeg, png, gif, webp.<br>";
    } elseif (!is_writable($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        echo "❌ Upload directory is not writable and could not be created.<br>";
    } else {
        $imageName = uniqid('subcat_', true) . '.' . $fileExtension;
        $destPath = $uploadDir . $imageName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            echo "✅ Image uploaded successfully: " . $imageName . "<br>";
        } else {
            echo "❌ Failed to move uploaded file!<br>";
            echo "Temp File: " . $fileTmpPath . " (Exists: " . (file_exists($fileTmpPath) ? "Yes" : "No") . ")<br>";
            echo "Destination: " . $destPath . " (Writable: " . (is_writable($uploadDir) ? "Yes" : "No") . ")<br>";
            echo "Upload Error Code: " . $_FILES['imageUpload']['error'] . "<br>";
        }
    }
} else {
    echo "❌ No image uploaded or upload error: " . $_FILES['imageUpload']['error'] . "<br>";
}
    

    // ✅ Prepare Update Query
    $updateQuery = "UPDATE sub_categories SET 
        parent_id = '$parent_id',
        cate_id = '$cate_id',
        categories = '$categories',
        meta_title = '$meta_title',
        meta_desc = '$meta_desc',
        meta_key = '$meta_key',
        slug_url = '$slug_url',
        status = '$status',
        added_on = '$added_on'";

    // Include image if uploaded
    if ($imageName) {
        $updateQuery .= ", sub_cat_img = '$imageName'";
    }

    $updateQuery .= " WHERE cate_id = '$sub_cat_id'";

    // ✅ Execute the Query
    if (mysqli_query($conn, $updateQuery)) {
        header("Location: edit_sub_category.php?id=" . urlencode($sub_cat_id));
exit();

    } else {
        echo "❌ Error updating sub-category: " . mysqli_error($conn);
    }
}
?>






<!DOCTYPE html>
<html lang="zxx">

<!-- Mirrored from demo.dashboardpack.com/sales-html/themefy_icon.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 16 Apr 2023 14:08:14 GMT -->

<head>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Category</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
</head>

<body class="crm_body_bg">

    <?php
    include "header.php";
    ?>
    <section class="main_content dashboard_part large_header_bg">



        <div class="main_content_iner ">
            <div class="container-fluid p-0">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="white_card card_height_100 mb_30">

                            <div class="white_card_body">
                                <div class="QA_section">
                                    <div class="white_box_tittle list_header">
                                        <div class="box_right d-flex lms_block">
                                          
                                            <div class="add_button ms-2">
                                                <a href="add-categories.php" data-bs-toggle="modal"
                                                    data-bs-target="#addcategory" class="btn_1">Add New</a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="QA_table mb_30">
                                        <h3 class="mb-4">Edit Sub-Category</h3>

                                        <?php

                                        $sql2 = "SELECT * FROM `sub_categories` where `cate_id` = '$sub_cat_id'";
                                        $res2 = mysqli_query($conn, $sql2);
                                        $subcategory = mysqli_fetch_assoc($res2);
                                        ?>

                                        <form action="?id=<?= htmlspecialchars($subcategory['cate_id']) ?>"
                                            method="post" enctype="multipart/form-data">
                                            <div class="row g-4">
                                                <!-- Parent Category -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Parent Category</label>
                                                    <input type="text" name="parent_id" class="form-control"
                                                        value="<?= htmlspecialchars($subcategory['parent_id'] ?? '') ?>"
                                                        required>
                                                </div>

                                                <!-- Category ID -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Category ID</label>
                                                    <input type="text" name="cate_id" class="form-control"
                                                        value="<?= htmlspecialchars($subcategory['cate_id'] ?? '') ?>"
                                                        required>
                                                </div>

                                                <!-- Sub-Category Name -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Sub-Category Name</label>
                                                    <input type="text" name="categories" class="form-control"
                                                        value="<?= htmlspecialchars($subcategory['categories'] ?? '') ?>"
                                                        required>
                                                </div>

                                                <!-- Slug URL -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Slug URL</label>
                                                    <input type="text" name="slug_url" class="form-control"
                                                        value="<?= htmlspecialchars($subcategory['slug_url'] ?? '') ?>"
                                                        required>
                                                </div>

                                                <!-- Meta Title -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Meta Title</label>
                                                    <input type="text" name="meta_title" class="form-control"
                                                        value="<?= htmlspecialchars($subcategory['meta_title'] ?? '') ?>"
                                                        required>
                                                </div>

                                                <!-- Meta Keywords -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Meta Keywords</label>
                                                    <input type="text" name="meta_key" class="form-control"
                                                        value="<?= htmlspecialchars($subcategory['meta_key'] ?? '') ?>"
                                                        required>
                                                </div>

                                                <!-- Meta Description -->
                                                <div class="col-md-12">
                                                    <label class="form-label">Meta Description</label>
                                                    <textarea name="meta_desc" class="form-control" rows="3"
                                                        required><?= htmlspecialchars($subcategory['meta_desc'] ?? '') ?></textarea>
                                                </div>

                                                <!-- Sub-Category Image -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Sub-Category Image</label>
                                                    <?php if (!empty($subcategory['sub_cat_img'])): ?>
                                                        <div class="mb-2">
                                                            <img src="uploads/sub-category/<?= htmlspecialchars($subcategory['sub_cat_img']) ?>"
                                                                alt="Sub-Category Image" class="img-thumbnail"
                                                                style="max-height: 100px;">
                                                        </div>
                                                    <?php endif; ?>
                                                    <input type="file" name="imageUpload" class="form-control">
                                                </div>

                                                <!-- Status -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="1" <?= (isset($subcategory['status']) && $subcategory['status'] == 'Active') ? 'selected' : '' ?>>
                                                            Active</option>
                                                        <option value="0" <?= (isset($subcategory['status']) && $subcategory['status'] == 'Inactive') ? 'selected' : '' ?>>
                                                            Inactive</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="text-center mt-4">
                                                <button type="submit" class="btn btn-primary px-5">Update
                                                    Sub-Category</button>
                                            </div>
                                        </form>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                </div>
            </div>
        </div>
        </div>

        <?php include "footer.php"; ?>