<?php
include_once "../config/connect.php";

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_term = mysqli_real_escape_string($conn, $_GET['q']);
    
    $sql = "SELECT p.pro_id, p.pro_name, p.pro_img, p.selling_price, p.slug_url,
                   c.categories as category_name
            FROM products p
            LEFT JOIN categories c ON p.pro_cate = c.id
            WHERE p.pro_name LIKE '%$search_term%' 
               OR p.short_desc LIKE '%$search_term%'
               OR p.description LIKE '%$search_term%'
               OR c.categories LIKE '%$search_term%'
            AND p.status = 1
            ORDER BY 
                CASE 
                    WHEN p.pro_name LIKE '$search_term%' THEN 1
                    WHEN p.pro_name LIKE '%$search_term%' THEN 2
                    ELSE 3
                END
            LIMIT 8";
    
    $result = mysqli_query($conn, $sql);
    
    if (mysqli_num_rows($result) > 0) {
        echo '<div class="search-suggestions-list">';
        while ($row = mysqli_fetch_assoc($result)) {
            echo '<a href="' . $site . 'product-details/' . $row['slug_url'] . '" class="search-suggestion-item">';
            echo '<img src="' . $site . 'admin/assets/img/uploads/' . $row['pro_img'] . '" alt="' . htmlspecialchars($row['pro_name']) . '">';
            echo '<div class="search-suggestion-info">';
            echo '<h6>' . htmlspecialchars($row['pro_name']) . '</h6>';
            echo '<span class="category">' . htmlspecialchars($row['category_name']) . '</span>';
            echo '<span class="price">₹' . number_format($row['selling_price'], 2) . '</span>';
            echo '</div>';
            echo '</a>';
        }
        echo '<div class="search-view-all">';
        echo '<a href="' . $site . 'search/?q=' . urlencode($_GET['q']) . '" class="btn btn-sm btn-outline-dark w-100">View All Results</a>';
        echo '</div>';
        echo '</div>';
    } else {
        echo '<div class="search-no-results">';
        echo '<p>No products found for "' . htmlspecialchars($_GET['q']) . '"</p>';
        echo '</div>';
    }
}
?>