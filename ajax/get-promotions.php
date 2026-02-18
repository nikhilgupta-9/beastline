<?php
header('Content-Type: application/json');
// Return available promotions/coupons
echo json_encode(['success' => true, 'promotions' => []]);
?>