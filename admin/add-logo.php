<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $target_dir = "uploads/logo/";
    
    // Create directory if not exists with proper permissions
    if(!is_dir($target_dir)){
        mkdir($target_dir, 0755, true);
    }
    
    // Generate unique filename
    $file_extension = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
    $new_filename = 'logo_' . uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    $uploadOk = 1;
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    // Check if image file is a actual image
    $check = getimagesize($_FILES["logo"]["tmp_name"]);
    if ($check === false) {
        $error = "File is not an image.";
        $uploadOk = 0;
    }
    
    // Check MIME type
    $mime = mime_content_type($_FILES["logo"]["tmp_name"]);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])) {
        $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }
    
    // Check file extension
    // if (!in_array($imageFileType, $allowed_types)) {
    //     $error = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    //     $uploadOk = 0;
    // }
    
    // Check file size (500KB)
    if ($_FILES["logo"]["size"] > 500000) {
        $error = "Sorry, your file is too large (max 500KB).";
        $uploadOk = 0;
    }
    
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 1) {
        // Try to upload file
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            // Insert into database (store relative path)
            $relative_path = 'logo/' . $new_filename;
            $stmt = $conn->prepare("INSERT INTO logos (logo_path, uploaded_at) VALUES (?, NOW())");
            $stmt->bind_param("s", $relative_path);
            
            if ($stmt->execute()) {
                $success = "The logo has been uploaded successfully.";
            } else {
                $error = "Database error: " . $conn->error;
                // Delete the uploaded file if DB insert failed
                if (file_exists($target_file)) {
                    unlink($target_file);
                }
            }
            $stmt->close();
        } else {
            $error = "Sorry, there was an error uploading your file.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Logo Management | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
    
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

        <div class="main_content_iner ">
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h2 class="m-0">Logo Management</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="white_card_body">
                                <div class="row">
                                    <div class="col-lg-6">
                                        <div class="logo-container">
                                            <h4 class="card-title">Upload New Logo</h4>
                                            
                                            <?php if(isset($error)): ?>
                                                <div class="alert-message alert-danger">
                                                    <?php echo $error; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if(isset($success)): ?>
                                                <div class="alert-message alert-success">
                                                    <?php echo $success; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
                                                <div class="form-group">
                                                    <label for="logo" class="form-label">Select Logo Image</label>
                                                    <div class="upload-area">
                                                        <button type="button" class="upload-btn">Choose File</button>
                                                        <input type="file" name="logo" id="logo" accept="image/*" required>
                                                    </div>
                                                    <div class="file-info" id="fileInfo">No file chosen</div>
                                                    <small class="form-text text-muted">Allowed formats: JPG, PNG, JPEG, GIF. Max size: 500KB</small>
                                                </div>
                                                <button type="submit" class="btn btn-primary mt-3">Upload Logo</button>
                                            </form>
                                        </div>
                                    </div>
                                    
                                    <div class="col-lg-6">
                                        <div class="logo-container">
                                            <h4 class="card-title">Current Logo</h4>
                                            <div class="logo-preview">
                                                <?php
                                                // Fetch the logo from the database
                                                $logo_query = "SELECT * FROM `logos` ORDER BY `id` DESC LIMIT 1";
                                                $logo_res = mysqli_query($conn, $logo_query);
                                                
                                                if ($logo_res && mysqli_num_rows($logo_res) > 0) {
                                                    $logo_row = mysqli_fetch_assoc($logo_res);
                                                    $logo_image = $logo_row['logo_path'];
                                                    
                                                    if (strpos($logo_image, 'uploads/') === false) {
                                                        $logo_image = 'uploads/' . $logo_image;
                                                    }
                                                    
                                                    if (file_exists($logo_image)) {
                                                        echo '<img src="' . htmlspecialchars($logo_image) . '" alt="Website Logo" class="img-fluid">';
                                                    } else {
                                                        echo '<div class="text-center text-muted"><i class="fas fa-image fa-3x mb-2"></i><p>Logo image not found</p></div>';
                                                    }
                                                } else {
                                                    echo '<div class="text-center text-muted"><i class="fas fa-image fa-3x mb-2"></i><p>No logo uploaded yet</p></div>';
                                                }
                                                ?>
                                            </div>
                                            <div class="text-center">
                                                <small class="text-muted">This logo will be displayed on your website</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "includes/footer.php"; ?>
    </section>

    <script>
        // Display selected file name
        document.getElementById('logo').addEventListener('change', function(e) {
            var fileName = e.target.files[0] ? e.target.files[0].name : "No file chosen";
            document.getElementById('fileInfo').textContent = fileName;
        });
    </script>

</body>
</html>