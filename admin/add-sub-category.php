<?php include('auth_check.php'); ?>
<?php
include "db-conn.php";

$sql = "SELECT * FROM `categories` ORDER BY id DESC";
$check = mysqli_query($conn, $sql);
?>


<?php
function SlugUrl($string)
{
    $slug = preg_replace('/[^a-zA-Z0-9 -]/', '', $string);
    $slug = str_replace('', '-', $slug);
    $slug = strtolower($slug);
    return ($slug);
}


?>

<!DOCTYPE html>
<html lang="zxx">
<head>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Sales</title>
    <link rel="icon" href="img/logo.png" type="image/png">

    <?php include "links.php"; ?>
</head>

<body class="crm_body_bg">

    <?php include "header.php"; ?>
    <section class="main_content dashboard_part large_header_bg">


        <div class="main_content_iner ">
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="main_content_iner">
                            <div class="container-fluid p-0 sm_padding_15px">
                                <div class="row justify-content-center">


                                    <div class="col-lg-12">
                                        <div class="white_card card_height_100 mb_30">
                                            <div class="white_card_header">
                                                <div class="box_header m-0">
                                                    <div class="main-title">
                                                        <h2 class="m-0">Add Sub Category Details</h2>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="white_card_body">
                                                <div class="card-body">
                                                    <form id="myform" action="functions.php" method="post"
                                                        enctype="multipart/form-data">
                                                        <div class="row g-4">
                                                            <!-- Parent Category -->
                                                            <div class="col-md-6 col-lg-6">
                                                                <label class="form-label" for="parentCategory">Parent
                                                                    Category Name</label>
                                                                <select class="form-control" name="parent_id"
                                                                    id="parentCategory" required>
                                                                    <option value="">-- Select --</option>
                                                                    <?php foreach ($check as $val) { ?>
                                                                        <option value="<?= $val['cate_id'] ?>">
                                                                            <?= ucwords($val['categories']) ?>
                                                                        </option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>

                                                            <!-- Sub Category Name -->
                                                            <div class="col-md-6 col-lg-6">
                                                                <label class="form-label" for="subCategory">Sub Category
                                                                    Name</label>
                                                                <input type="text" class="form-control" name="cate_name"
                                                                    id="subCategory" placeholder="Enter category name"
                                                                    required />
                                                            </div>

                                                            <!-- Meta Title & Keywords -->
                                                            <div class="col-md-6 col-lg-6">
                                                                <label class="form-label" for="metaTitle">Meta
                                                                    Title</label>
                                                                <input type="text" class="form-control"
                                                                    name="meta_title" id="metaTitle"
                                                                    placeholder="Meta Title" />
                                                            </div>

                                                            <div class="col-md-6 col-lg-6">
                                                                <label class="form-label" for="metaKeyword">Meta
                                                                    Keyword</label>
                                                                <input type="text" class="form-control" name="meta_key"
                                                                    id="metaKeyword" placeholder="Meta Keyword" />
                                                            </div>

                                                            <!-- Meta Description -->
                                                            <div class="col-md-12 col-lg-6">
                                                                <label class="form-label" for="metaDescription">Meta
                                                                    Description</label>
                                                                <textarea class="form-control" name="meta_desc"
                                                                    id="metaDescription" placeholder="Meta Description"
                                                                    rows="3"></textarea>
                                                            </div>

                                                            <!-- Sub Category Image -->
                                                            <div class="col-md-6 col-lg-6">
                                                                <label class="form-label" for="imageUpload">Sub Category
                                                                    Image</label>
                                                                <input type="file" class="form-control"
                                                                    name="imageUpload" id="imageUpload"
                                                                    accept="image/*" />
                                                            </div>

                                                            <!-- Status -->
                                                            <div class="col-md-6 col-lg-6">
                                                                <label class="form-label" for="status">Status</label>
                                                                <select id="status" name="status" class="form-control"
                                                                    required>
                                                                    <option value="1">Active</option>
                                                                    <option value="0">Deactive</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <!-- Submit Button -->
                                                        <div class="text-center mt-4">
                                                            <button type="submit" class="btn btn-primary px-5"
                                                                name="add-sub-categories">
                                                                Add Category
                                                            </button>
                                                        </div>
                                                    </form>
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
        </div>

        <?php include "footer.php"; ?>


        <script>
            const form = document.getElementById('myForm');

            form.addEventListener('submit', function (event) {
                const select = document.getElementById('category');
                if (!select.value) {
                    alert('Please select a valid category.');
                    event.preventDefault(); // Prevent form submission
                }
            });
        </script>