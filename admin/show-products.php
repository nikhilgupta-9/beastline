<?php include('auth_check.php'); ?>
<?php
include "db-conn.php";
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Sales</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
</head>

<body class="crm_body_bg">
<?php include "header.php"; ?>

<section class="main_content dashboard_part large_header_bg">
    <div class="container-fluid g-0">
        <div class="row">
            <div class="col-lg-12 p-0 ">
                <div class="header_iner d-flex justify-content-between align-items-center">
                    <div class="serach_field-area d-flex align-items-center">
                        <div class="search_inner">
                            <form method="GET">
                                <div class="search_field">
                                    <input type="text" name="search" placeholder="Search product..." value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
                                </div>
                                <button type="submit"><img src="assets/img/icon/icon_search.svg" alt></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="main_content_iner">
        <div class="container-fluid p-0 sm_padding_15px">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <table class="table table-striped">
                        <thead class="bg-dark text-white">
                            <tr>
                                <th>#</th>
                                <th>Pro ID</th>
                                <th>Product Name</th>
                                <th>Category ID</th>
                                <th>Subcategory</th>
                                <th>MRP</th>
                                <th>Selling Price</th>
                                <th>Whole Sale Price</th>
                                <th class="text-center">Edit</th>
                                <th class="text-center">Delete</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                        $sno = 1;
                        $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
                    
                        $sql = "SELECT * FROM `products`";
                        if (!empty($search)) {
                            $sql .= " WHERE pro_name LIKE '%".$search."%'";
                        }
                        
                        $result = mysqli_query($conn, $sql);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $pro_img = $row['pro_img'];
                            $images = explode(",", $pro_img);
                            $firstimg = isset($images[0]) ? $images[0] : 'default.jpg'; // Provide a default image if none exists
                        ?>
                            <tr>
                                <td><?= $sno++ ?></td>
                                <td><?= htmlspecialchars($row['pro_id']) ?></td>
                                <td><?= htmlspecialchars($row['pro_name']) ?></td>
                                <td><?= htmlspecialchars($row['pro_cate']) ?></td>
                                <td><img src="assets/img/uploads/<?= htmlspecialchars($firstimg) ?>" alt="" style="height:60px; width:60px;"></td>
                                <td><?= htmlspecialchars($row['mrp']) ?></td>
                                <td><?= htmlspecialchars($row['selling_price']) ?></td>
                                <td><?= htmlspecialchars($row['whole_sale_selling_price']) ?></td>
                                <td class="text-center">
                                    <a href="edit_products.php?edit_product_details=<?= htmlspecialchars($row['pro_id']) ?>">
                                        <i class="fa-regular fa-pen-to-square text-primary fs-3"></i>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="product_delete.php?delete=<?= htmlspecialchars($row['pro_id']) ?>" onclick="return confirm('Are you sure you want to delete this product?')">
                                        <i class="fa-solid fa-trash text-danger fs-3"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

<?php include "footer.php"; ?>
