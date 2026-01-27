<?php
require_once __DIR__ . '/config/db-conn.php';
require_once __DIR__ . '/auth/admin-auth.php';

$session_id = $_GET['session_id'] ?? '';

if (empty($session_id)) {
    echo json_encode(['error' => 'Session ID required']);
    exit;
}

// Get visitor details
$sql = "SELECT * FROM visitor_activities WHERE session_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $session_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [
    'activities' => []
];

$first_row = null;
while ($row = $result->fetch_assoc()) {
    if ($first_row === null) {
        $first_row = $row;
        $data['session_id'] = $row['session_id'];
        $data['ip_address'] = $row['ip_address'];
        $data['country'] = $row['country'];
        $data['city'] = $row['city'];
        $data['region'] = $row['region'];
        
        // Parse user agent for device and browser info
        $user_agent = $row['user_agent'];
        $data['device'] = (strpos(strtolower($user_agent), 'mobile') !== false) ? 'Mobile' : 'Desktop';
        
        if (strpos($user_agent, 'Firefox') !== false) {
            $data['browser'] = 'Firefox';
        } elseif (strpos($user_agent, 'Chrome') !== false) {
            $data['browser'] = 'Chrome';
        } elseif (strpos($user_agent, 'Safari') !== false) {
            $data['browser'] = 'Safari';
        } else {
            $data['browser'] = 'Unknown';
        }
    }
    
    $data['activities'][] = [
        'time' => date('H:i:s', strtotime($row['created_at'])),
        'page' => basename($row['page_url']),
        'action' => $row['action'] ?? 'Visit'
    ];
}

echo json_encode($data);