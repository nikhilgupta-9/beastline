<?php
class Dashboard {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    public function getDashboardStats() {
        $stats = [];
        
        // Total Revenue
        $sql = "SELECT SUM(order_total) as total FROM orders_new WHERE status = 'completed'";
        $result = $this->conn->query($sql);
        $stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Revenue growth (vs last month)
        $last_month = date('Y-m-d', strtotime('-1 month'));
        $sql = "SELECT SUM(order_total) as total FROM orders_new 
                WHERE status = 'completed' AND created_at >= '$last_month'";
        $result = $this->conn->query($sql);
        $last_month_revenue = $result->fetch_assoc()['total'] ?? 0;
        $stats['revenue_growth'] = $last_month_revenue > 0 ? 
            round(($stats['total_revenue'] - $last_month_revenue) / $last_month_revenue * 100, 1) : 0;
        
        // Total Orders
        $sql = "SELECT COUNT(*) as count FROM orders_new";
        $result = $this->conn->query($sql);
        $stats['total_orders'] = $result->fetch_assoc()['count'] ?? 0;
        
        // Order growth
        $sql = "SELECT COUNT(*) as count FROM orders_new WHERE created_at >= '$last_month'";
        $result = $this->conn->query($sql);
        $last_month_orders = $result->fetch_assoc()['count'] ?? 0;
        $stats['order_growth'] = $last_month_orders > 0 ? 
            round(($stats['total_orders'] - $last_month_orders) / $last_month_orders * 100, 1) : 0;
        
        // Total Customers
        $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer'";
        $result = $this->conn->query($sql);
        $stats['total_customers'] = $result->fetch_assoc()['count'] ?? 0;
        
        // Customer growth
        $sql = "SELECT COUNT(*) as count FROM users WHERE role = 'customer' AND created_at >= '$last_month'";
        $result = $this->conn->query($sql);
        $last_month_customers = $result->fetch_assoc()['count'] ?? 0;
        $stats['customer_growth'] = $last_month_customers > 0 ? 
            round(($stats['total_customers'] - $last_month_customers) / $last_month_customers * 100, 1) : 0;
        
        // Total Products
        $sql = "SELECT COUNT(*) as count FROM products";
        $result = $this->conn->query($sql);
        $stats['total_products'] = $result->fetch_assoc()['count'] ?? 0;
        
        // Product growth
        $sql = "SELECT COUNT(*) as count FROM products WHERE created_at >= '$last_month'";
        $result = $this->conn->query($sql);
        $last_month_products = $result->fetch_assoc()['count'] ?? 0;
        $stats['product_growth'] = $last_month_products > 0 ? 
            round(($stats['total_products'] - $last_month_products) / $last_month_products * 100, 1) : 0;
        
        return $stats;
    }
    
    public function getRecentOrders($limit = 5) {
        $orders = [];
        $sql = "SELECT o.*, CONCAT(o.first_name, ' ', o.last_name) as customer_name 
                FROM orders_new o 
                ORDER BY o.created_at DESC 
                LIMIT $limit";
        $result = $this->conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        
        return $orders;
    }
    
    public function getTopProducts($limit = 5) {
        $products = [];
        $sql = "SELECT p.pro_id, p.pro_name, p.price, p.pro_img, 
                COUNT(oi.product_id) as order_count
                FROM products p
                LEFT JOIN order_items oi ON p.pro_id = oi.product_id
                GROUP BY p.pro_id
                ORDER BY order_count DESC
                LIMIT $limit";
        $result = $this->conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        return $products;
    }
    
    public function getSalesData($days = 30) {
        $sales = [];
        $start_date = date('Y-m-d', strtotime("-$days days"));
        
        // Generate date range
        $date_range = [];
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $date_range[$date] = [
                'date' => $date,
                'sales' => 0,
                'orders' => 0
            ];
        }
        
        // Get sales data
        $sql = "SELECT DATE(created_at) as date, 
                COUNT(*) as order_count,
                SUM(order_total) as total_sales
                FROM orders_new 
                WHERE created_at >= '$start_date'
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        $result = $this->conn->query($sql);
        
        while ($row = $result->fetch_assoc()) {
            if (isset($date_range[$row['date']])) {
                $date_range[$row['date']]['sales'] = (float) $row['total_sales'];
                $date_range[$row['date']]['orders'] = (int) $row['order_count'];
            }
        }
        
        // Format for chart
        foreach ($date_range as $date => $data) {
            $sales[] = [
                'date' => $data['date'],
                'sales' => $data['sales'],
                'orders' => $data['orders']
            ];
        }
        
        return array_reverse($sales);
    }
    
    public function getVisitorStats() {
        $stats = [
            'today' => rand(100, 500),
            'this_week' => rand(800, 2000),
            'this_month' => rand(5000, 15000),
            'mobile_percent' => 65,
            'desktop_percent' => 35
        ];
        
        return $stats;
    }
}
?>