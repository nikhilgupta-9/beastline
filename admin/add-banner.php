<?php include('auth_check.php'); ?>
<?php
include "db-conn.php";

// Check admin authentication
// session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $errors = [];
    $success = '';
    
    // Validate file upload
    if (!isset($_FILES['banner'])) {
        $errors[] = "No file was uploaded.";
    } else {
        $target_dir = "uploads/banners/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        // Generate unique filename to prevent overwrites
        $file_ext = strtolower(pathinfo($_FILES["banner"]["name"], PATHINFO_EXTENSION));
        $target_file = $target_dir . uniqid('banner_') . '.' . $file_ext;
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES["banner"]["tmp_name"]);
        if ($check === false) {
            $errors[] = "File is not an image.";
        }
        
        // Check file size (5MB limit)
        if ($_FILES["banner"]["size"] > 5000000) {
            $errors[] = "Sorry, your file is too large (max 5MB).";
        }
        
        // Allow certain file formats
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($file_ext, $allowed_types)) {
            $errors[] = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
        }
        
        // If no errors, proceed with upload
        if (empty($errors)) {
            if (move_uploaded_file($_FILES["banner"]["tmp_name"], $target_file)) {
                // Insert into database
                $stmt = $conn->prepare("INSERT INTO banners (banner_path, uploaded_at) VALUES (?, NOW())");
                $stmt->bind_param("s", $target_file);
                
                if ($stmt->execute()) {
                    $success = "The banner has been uploaded successfully.";
                } else {
                    // Delete the uploaded file if DB insert fails
                    unlink($target_file);
                    $errors[] = "Sorry, there was an error saving to the database.";
                }
                $stmt->close();
            } else {
                $errors[] = "Sorry, there was an error uploading your file.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Banner Management | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
    
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
            --danger-color: #f72585;
            --success-color: #4bb543;
        }
        
        .upload-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s;
            cursor: pointer;
            margin-bottom: 1rem;
        }
        
        .upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(67, 97, 238, 0.05);
        }
        
        .upload-area i {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        .banner-preview {
            max-width: 100%;
            max-height: 200px;
            display: none;
            margin: 1rem auto;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .banner-table img {
            max-width: 300px;
            max-height: 100px;
            object-fit: contain;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .banner-table img:hover {
            transform: scale(1.05);
        }
        
        .btn-action {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .section-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.5rem;
        }
        
        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
        }
    </style>
</head>

<body class="crm_body_bg">

    <?php include "header.php"; ?>
    
    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h2 class="m-0">Banner Management</h2>
                                    </div>
                                </div>
                            </div>
                            <div class="white_card_body">
                                <!-- Display messages -->
                                <?php if (!empty($errors)): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <ul class="mb-0">
                                            <?php foreach ($errors as $error): ?>
                                                <li><?= htmlspecialchars($error) ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (isset($success) && !empty($success)): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <?= htmlspecialchars($success) ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Upload Form -->
                                <div class="upload-card">
                                    <h3 class="section-title">Upload New Banner</h3>
                                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data" id="bannerForm">
                                        <div class="upload-area" id="uploadArea">
                                            <i class="fas fa-cloud-upload-alt"></i>
                                            <h5>Drag & Drop your banner image here</h5>
                                            <p class="text-muted">or click to browse files</p>
                                            <img id="bannerPreview" class="banner-preview" alt="Banner Preview">
                                        </div>
                                        <input type="file" name="banner" id="banner" accept="image/*" class="d-none" required>
                                        <div class="d-grid gap-2">
                                            <button type="submit" name="submit" class="btn btn-primary">
                                                <i class="fas fa-upload me-2"></i> Upload Banner
                                            </button>
                                        </div>
                                        <div class="mt-2 text-muted small">
                                            <p>Allowed formats: JPG, JPEG, PNG, GIF, WEBP | Max size: 5MB</p>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Banner List -->
                                <div class="mt-5">
                                    <h3 class="section-title">Current Banners</h3>
                                    <div class="table-responsive">
                                        <table class="table banner-table">
                                            <thead>
                                                <tr>
                                                    <th width="5%">#</th>
                                                    <th width="55%">Banner</th>
                                                    <th width="10%">status</th>
                                                    <th width="10%">Uploaded</th>
                                                    <th width="20%">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Fetch and display uploaded banners
                                                $banner_query = "SELECT * FROM banners ORDER BY uploaded_at DESC LIMIT 10";
                                                $banner_res = mysqli_query($conn, $banner_query);
                                                
                                                if ($banner_res && mysqli_num_rows($banner_res) > 0) {
                                                    $sno = 1;
                                                    while ($banner_row = mysqli_fetch_assoc($banner_res)) {
                                                        ?>
                                                        <tr>
                                                            <td><?= $sno++ ?></td>
                                                            <td>
                                                                <img src="<?= htmlspecialchars($banner_row['banner_path']) ?>" 
                                                                     alt="Banner <?= $sno-1 ?>" 
                                                                     class="img-fluid">
                                                            </td>
                                                            <td>
                                                                <?php
                                                                if($banner_row['status'] == 1){
                                                                    echo "<li class='p-2 bg-success text-center '>Active</li>";
                                                                }else{
                                                                    echo "<li class='p-2 bg-danger text-center '>Inactive</li>";
                                                                }
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?= date('M d, Y', strtotime($banner_row['uploaded_at'])) ?>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex gap-2">
                                                                    <a href="edit_banner.php?id=<?= $banner_row['id'] ?>" 
                                                                       class="btn btn-outline-primary btn-action">
                                                                        <i class="fas fa-edit"></i> Edit
                                                                    </a>
                                                                    <a href="delete_banner.php?id=<?= $banner_row['id'] ?>" 
                                                                       class="btn btn-outline-danger btn-action" 
                                                                       onclick="return confirm('Are you sure you want to delete this banner?')">
                                                                        <i class="fas fa-trash"></i> Delete
                                                                    </a>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                } else {
                                                    ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-4">
                                                            <div class="d-flex flex-column align-items-center">
                                                                <i class="fas fa-image text-muted mb-2" style="font-size: 2rem;"></i>
                                                                <p class="text-muted">No banners uploaded yet</p>
                                                                <p class="text-muted small">Upload your first banner using the form above</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </section>

    <script>
        // File upload preview and drag-drop functionality
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('banner');
            const preview = document.getElementById('bannerPreview');
            const form = document.getElementById('bannerForm');
            
            // Click on upload area triggers file input
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            // File input change event
            fileInput.addEventListener('change', function(e) {
                if (fileInput.files && fileInput.files[0]) {
                    const reader = new FileReader();
                    
                    reader.onload = function(event) {
                        preview.src = event.target.result;
                        preview.style.display = 'block';
                        uploadArea.querySelector('h5').textContent = fileInput.files[0].name;
                        uploadArea.querySelector('p').textContent = (fileInput.files[0].size / 1024 / 1024).toFixed(2) + 'MB';
                    };
                    
                    reader.readAsDataURL(fileInput.files[0]);
                }
            });
            
            // Drag and drop functionality
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
            });
            
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });
            
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            function highlight() {
                uploadArea.classList.add('bg-light');
            }
            
            function unhighlight() {
                uploadArea.classList.remove('bg-light');
            }
            
            // Handle dropped files
            uploadArea.addEventListener('drop', handleDrop, false);
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                
                if (files.length) {
                    fileInput.files = files;
                    
                    // Trigger change event manually
                    const event = new Event('change');
                    fileInput.dispatchEvent(event);
                }
            }
            
            // Form validation
            form.addEventListener('submit', function(e) {
                if (!fileInput.files || !fileInput.files[0]) {
                    e.preventDefault();
                    alert('Please select a banner image to upload');
                }
            });
        });
    </script>
</body>
</html>