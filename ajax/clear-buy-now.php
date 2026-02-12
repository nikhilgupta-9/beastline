<?php
session_start();

// Clear buy now session
if (isset($_SESSION['buy_now'])) {
    // Store in previous for reference if needed
    $_SESSION['buy_now_previous'] = $_SESSION['buy_now'];
    unset($_SESSION['buy_now']);
}

echo json_encode(['success' => true]);
?>