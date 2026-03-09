<?php
header('Content-Type: application/json');
// Apply coupon/promotion
echo json_encode(['success' => true, 'message' => 'Promotion applied']);
?>