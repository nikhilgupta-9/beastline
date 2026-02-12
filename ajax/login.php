<?php
session_start();

header('Content-Type: application/json');

require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../util/function.php';
require_once __DIR__ . '/../util/visitor_tracker.php';

$response = [
    'success' => false,
    'message' => 'Invalid request'
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode($response);
    exit;
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$remember = isset($_POST['remember']) ? 1 : 0;

if (empty($email) || empty($password)) {
    $response['message'] = 'Please enter both email and password.';
    echo json_encode($response);
    exit;
}

/* Fetch user */
$sql = "SELECT * FROM users WHERE email = ? AND status = 1 LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $response['message'] = 'Invalid email or password.';
    echo json_encode($response);
    exit;
}

$user = $result->fetch_assoc();

/* Verify password */
if (!password_verify($password, $user['password'])) {
    $response['message'] = 'Invalid email or password.';
    echo json_encode($response);
    exit;
}

/* Login success */
$_SESSION['user_id']    = $user['id'];
$_SESSION['user_name']  = $user['first_name'] . ' ' . $user['last_name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_type']  = $user['user_type'];

/* Visitor tracking */
if (!empty($_COOKIE['visitor_id'])) {
    $tracker = new VisitorTracker($conn);
    $tracker->linkVisitorToUser($user['id']);
}

/* Remember me */
if ($remember === 1) {
    $token  = bin2hex(random_bytes(32));
    $expiry = time() + (86400 * 30);

    setcookie('remember_token', $token, $expiry, '/', '', false, true);

    $update_sql = "UPDATE users SET remember_token = ?, token_expiry = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("si", $token, $user['id']);
    $update_stmt->execute();
}

/* Record activity */
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

$activity_sql = "INSERT INTO user_activities (user_id, activity_type, ip_address, user_agent)
                 VALUES (?, 'login', ?, ?)";
$activity_stmt = $conn->prepare($activity_sql);
$activity_stmt->bind_param("iss", $user['id'], $ip, $user_agent);
$activity_stmt->execute();

/* Merge guest cart */
if (isset($_SESSION['guest_cart']) && file_exists(__DIR__ . '/../util/cart_manager.php')) {
    require_once __DIR__ . '/../util/cart_manager.php';
    $cartManager = new CartManager($conn);
    $cartManager->migrateGuestCartToUser($user['id']);
}

/* Redirect logic */
$redirect_url = $_SESSION['redirect_url'] ?? '';

if (empty($redirect_url)) {
    if ($user['user_type'] === 'admin') {
        $redirect_url = '../admin/';
    } else {
        $redirect_url = ''.$site.'checkout';
    }
}

unset($_SESSION['redirect_url']);

$response = [
    'success' => true,
    'message' => 'Login successful.',
    'redirect' => $redirect_url
];

echo json_encode($response);
exit;
