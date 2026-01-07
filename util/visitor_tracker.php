<?php
class VisitorTracker
{
    private $conn;
    private $visitorId;
    private $sessionId;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->sessionId = session_id();
        $this->initializeVisitor();
    }

    private function initializeVisitor()
    {
        if (!isset($_COOKIE['visitor_id'])) {
            $this->visitorId = $this->generateVisitorId();
            setcookie('visitor_id', $this->visitorId, time() + (86400 * 365), '/'); // 1 year
        } else {
            $this->visitorId = $_COOKIE['visitor_id'];
        }

        $this->trackVisit();
    }

    private function generateVisitorId()
    {
        return uniqid('vis_', true) . '_' . bin2hex(random_bytes(8));
    }

    private function trackVisit()
    {
        $ip = $this->getIPAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

        // Parse user agent
        $deviceInfo = $this->parseUserAgent($userAgent);

        // Get location info (you can use an IP geolocation service)
        $location = $this->getLocationFromIP($ip);

        // Check if visitor exists
        $existing = $this->getExistingVisitor();

        if ($existing) {
            $this->updateVisitor($existing['id'], $location);
        } else {
            $this->createVisitor($ip, $userAgent, $referrer, $currentUrl, $deviceInfo, $location);
        }
    }

    private function getIPAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }

    private function parseUserAgent($userAgent)
    {
        $deviceType = 'desktop';
        $isMobile = 0;
        $isTablet = 0;
        $isDesktop = 1;
        $isBot = 0;

        // Check for mobile devices
        if (preg_match('/(android|iphone|ipod|blackberry|opera mini|windows phone)/i', $userAgent)) {
            $deviceType = 'mobile';
            $isMobile = 1;
            $isDesktop = 0;
        } elseif (preg_match('/(tablet|ipad|playbook|silk)/i', $userAgent)) {
            $deviceType = 'tablet';
            $isTablet = 1;
            $isDesktop = 0;
        }

        // Check for bots
        if (preg_match('/(bot|crawler|spider|scraper)/i', $userAgent)) {
            $isBot = 1;
        }

        // Get browser and OS
        $browser = 'Unknown';
        $os = 'Unknown';

        if (preg_match('/(chrome|firefox|safari|opera|edge|msie|trident)/i', $userAgent, $matches)) {
            $browser = strtolower($matches[0]);
        }

        if (preg_match('/(windows|macintosh|linux|android|ios|iphone)/i', $userAgent, $matches)) {
            $os = strtolower($matches[0]);
        }

        return [
            'device_type' => $deviceType,
            'is_mobile' => $isMobile,
            'is_tablet' => $isTablet,
            'is_desktop' => $isDesktop,
            'is_bot' => $isBot,
            'browser' => $browser,
            'os' => $os
        ];
    }

    private function getLocationFromIP($ip)
    {
        // For production, use a service like ipinfo.io or MaxMind
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,city";

        $response = @file_get_contents($url);
        if (!$response) {
            return [
                'country' => 'Unknown',
                'city' => 'Unknown'
            ];
        }

        $data = json_decode($response, true);

        if ($data['status'] !== 'success') {
            return [
                'country' => 'Unknown',
                'city' => 'Unknown'
            ];
        }

        return [
            'country' => $data['country'] ?? 'unknown',
            'city' => $data['city'] ?? 'unknown'
        ];
    }

    private function getExistingVisitor()
    {
        $sql = "SELECT id FROM visitor_tracking 
                WHERE visitor_id = ? OR session_id = ? 
                ORDER BY last_visit DESC LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $this->visitorId, $this->sessionId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    private function createVisitor($ip, $userAgent, $referrer, $currentUrl, $deviceInfo, $location)
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        $sql = "INSERT INTO visitor_tracking (
            visitor_id, user_id, session_id, ip_address, user_agent, 
            referrer, landing_page, device_type, browser, os, 
            country, city, is_mobile, is_tablet, is_desktop, is_bot
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "sissssssssssiiii",
            $this->visitorId,
            $userId,
            $this->sessionId,
            $ip,
            $userAgent,
            $referrer,
            $currentUrl,
            $deviceInfo['device_type'],
            $deviceInfo['browser'],
            $deviceInfo['os'],
            $location['country'],
            $location['city'],
            $deviceInfo['is_mobile'],
            $deviceInfo['is_tablet'],
            $deviceInfo['is_desktop'],
            $deviceInfo['is_bot']
        );
        $stmt->execute();
    }

    private function updateVisitor($visitorId, $location)
    {
        $sql = "UPDATE visitor_tracking 
                SET last_visit = NOW(), 
                    visit_count = visit_count + 1,
                    user_id = ?,
                    country = ?,
                    city = ?
                WHERE id = ?";

        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            "issi",
            $userId,
            $location['country'],
            $location['city'],
            $visitorId
        );
        $stmt->execute();
    }

    public function getVisitorId()
    {
        return $this->visitorId;
    }

    public function linkVisitorToUser($userId)
    {
        $sql = "UPDATE visitor_tracking SET user_id = ? WHERE visitor_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $userId, $this->visitorId);
        $stmt->execute();
    }

    public function saveCartSession($cartData)
    {
        $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $cartJson = json_encode($cartData);

        $sql = "INSERT INTO cart_sessions (visitor_id, user_id, session_id, cart_data, expires_at)
                VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
                ON DUPLICATE KEY UPDATE 
                cart_data = VALUES(cart_data),
                updated_at = NOW(),
                expires_at = VALUES(expires_at)";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("siss", $this->visitorId, $userId, $this->sessionId, $cartJson);
        return $stmt->execute();
    }

    public function getCartSession()
    {
        $sql = "SELECT cart_data FROM cart_sessions 
                WHERE (visitor_id = ? OR session_id = ?) 
                AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY updated_at DESC LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ss", $this->visitorId, $this->sessionId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            return json_decode($row['cart_data'], true);
        }

        return [];
    }
}
