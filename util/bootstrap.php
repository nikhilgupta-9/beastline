<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/connect.php';
require_once __DIR__ . '/../util/visitor_tracker.php';

if (class_exists('VisitorTracker')) {
    $visitorTracker = new VisitorTracker($conn);
    // $visitorTracker->track();    

    // Track visitor only once per session
    if (!isset($_SESSION['visitor_tracked'])) {
        new VisitorTracker($conn);
        $_SESSION['visitor_tracked'] = true;
    }else{
        echo "<script>console.log('visiter id not set in session or cookies')</script>";
    }
}
