<?php include('auth_check.php'); ?>
<?php
include "db-conn.php";

$sql = "SELECT * FROM `categories` ORDER BY id DESC";
$check = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="zxx">

<!-- Mirrored from demo.dashboardpack.com/sales-html/themefy_icon.html by HTTrack Website Copier/3.x [XR&CO'2014], Sun, 16 Apr 2023 14:08:14 GMT -->

<head>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Sales</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
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
    }

    .white_card_header .main-title h3 {
        font-weight: 600;
        font-size: 1.5rem;
        color: #2c3e50;
    }

    .form-label {
        font-weight: 500;
        margin-bottom: 6px;
        color: #2c3e50;
    }

    .form-control {
        border-radius: 8px;
        padding: 10px 12px;
        border: 1px solid #d1d3e2;
        box-shadow: none;
        transition: border-color 0.3s ease-in-out;
    }

    .form-control:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }

    textarea.form-control {
        min-height: 120px;
    }

    select.form-control {
        cursor: pointer;
    }

    button.btn-primary {
        padding: 12px 24px;
        font-weight: 600;
        font-size: 1rem;
        border-radius: 8px;
        background-color: #4e73df;
        border: none;
        transition: background-color 0.3s ease;
    }

    button.btn-primary:hover {
        background-color: #375ab7;
    }

    .card-body {
        padding-top: 15px;
    }

    .mb-3 {
        margin-bottom: 1.5rem !important;
    }

    @media (max-width: 768px) {
        .form-label {
            font-size: 0.9rem;
        }

        .form-control {
            font-size: 0.9rem;
        }

        button.btn-primary {
            width: 100%;
        }
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
                                                        <h3 class="m-0">Fill the Product details</h3>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="white_card_body">
                                                <div class="card-body">
                                                    <form id="myform" action="functions.php" method="post"
                                                        enctype="multipart/form-data">
                                                        <div class="row mb-3">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Product
                                                                    Name</label>
                                                                <input type="text" class="form-control" name="pro_name"
                                                                    id="inputEmail4" placeholder="Product name"
                                                                    required />
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Brand
                                                                    Name</label>
                                                                <input type="text" class="form-control"
                                                                    name="brand_name" id="inputEmail4"
                                                                    placeholder="Brand name" required />
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Parent
                                                                    Category Name</label>
                                                                <select class="form-control" name="pro_cate" required
                                                                    onchange="get_subcategory(this.value)">
                                                                    <option value="">--select--</option>
                                                                    <?php foreach ($check as $val) { ?>
                                                                        <option value="<?= $val['cate_id'] ?>">
                                                                            <?= ucwords($val['categories']) ?></option>
                                                                    <?php } ?>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Sub
                                                                    Category</label>
                                                                <select class="form-control" name="pro_sub_cate"
                                                                    id="subcate_id">
                                                                    <option value="">Select</option>
                                                                </select>
                                                            </div>



                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label"
                                                                    for="inputEmail4">Stock</label>
                                                                <input type="text" class="form-control" name="stock"
                                                                    id="inputEmail4" placeholder="Stock" required />
                                                            </div>


                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Product
                                                                    Image</label>
                                                                <input type="file" class="form-control" name="pro_img[]"
                                                                    id="pro_img" multiple />

                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Exclusive
                                                                    Deal & Offers</label>
                                                                <select id="inputState" name="new_arrival"
                                                                    class="form-control" required>
                                                                    <option value="0" selected>No</option>
                                                                    <!-- <option value="0">No</option> -->
                                                                    <option value="1">Yes</option>
                                                                </select>
                                                            </div>

                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Special
                                                                    Offers</label>
                                                                <select id="inputState" name="trending"
                                                                    class="form-control" required>
                                                                    <option value="0" selected>No</option>
                                                                    <!-- <option value="0">No</option> -->
                                                                    <option value="1">Yes</option>
                                                                </select>
                                                            </div>


                                                            <div class="col-md-12 mb-3">
                                                                <label class="form-label" for="inputEmail4">Short
                                                                    Description</label>
                                                                <textarea class="form-control" name="short_desc"
                                                                    required></textarea>
                                                            </div>
                                                            <div class="col-md-12 mb-3">
                                                                <label class="form-label" for="inputEmail4">Product
                                                                    Description</label>
                                                                <textarea class="form-control" name="pro_desc"
                                                                    required></textarea>
                                                            </div>

                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">MRP</label>
                                                                <input type="text" class="form-control" name="mrp"
                                                                    id="inputEmail4" placeholder="MRP" required />
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Selling
                                                                    Price</label>
                                                                <input type="text" class="form-control"
                                                                    name="selling_price" id="inputEmail4"
                                                                    placeholder="Selling Price" required />
                                                            </div>

                                                            <!-- <div class="col-md-6 mb-3">
                                                                <label class="form-label"
                                                                    for="inputEmail4">Discount in % </label>
                                                                <input type="text" class="form-control" name="whole_selling_price"
                                                                    id="inputEmail4" placeholder="Discount in percent"  />
                                                            </div>

                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label"
                                                                    for="inputEmail4">Qty.</label>
                                                                <input type="text" class="form-control" name="qty"
                                                                    id="inputEmail4" placeholder="Quantity"  />
                                                            </div> -->
                                                        </div>
                                                        <div class="row mb-3">
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Meta
                                                                    Title</label>
                                                                <input type="text" class="form-control"
                                                                    name="meta_title" id="inputEmail4"
                                                                    placeholder="Meta Title" required />
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Meta
                                                                    Keyword</label>
                                                                <input type="text" class="form-control" name="meta_key"
                                                                    id="inputEmail4" placeholder="Meta Keyword"
                                                                    required />
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="inputEmail4">Meta
                                                                    Discription</label>
                                                                <input type="text" class="form-control" name="meta_desc"
                                                                    id="inputEmail4" placeholder="Meta Discription"
                                                                    required />
                                                            </div>

                                                            <div class="col-md-6">
                                                                <label class="form-label"
                                                                    for="inputState">Status</label>
                                                                <select id="inputState" name="status"
                                                                    class="form-control" required>
                                                                    <!-- <option selected>Choose...</option> -->
                                                                    <option value="1">Active</option>
                                                                    <option value="0">Deactive</option>
                                                                </select>
                                                            </div>

                                                        </div>

                                                        <button type="submit" class="btn btn-primary"
                                                            name="add-product">
                                                            Add Product
                                                        </button>
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
        <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>

        <script>
            CKEDITOR.replace('pro_desc')
            CKEDITOR.replace('short_desc')
        </script>

        <!-- ajax function for selecting category then automatically show sub category  -->
        <script type="text/javascript">
            function get_subcategory(cate_id) {
                var cate_id = cate_id;
                $.ajax({
                    url: 'functions.php',
                    method: 'post',
                    data: { cate_id: cate_id },
                    error: function () {
                        alert("something went wrong");
                    },
                    success: function (data) {
                        $("#subcate_id").html(data);
                        // alert(data);
                    }
                })
            }
        </script>