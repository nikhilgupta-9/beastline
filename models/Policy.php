<?php
class Policy {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get all policies
    public function getAllPolicies($status = null) {
        $sql = "SELECT * FROM policies";
        
        if ($status !== null) {
            $sql .= " WHERE status = ?";
        }
        
        $sql .= " ORDER BY display_order, created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        
        if ($status !== null) {
            $stmt->bind_param("i", $status);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $policies = [];
        while($row = $result->fetch_assoc()) {
            $policies[] = $row;
        }
        
        return $policies;
    }
    
    // Get policy by ID
    public function getPolicyById($id) {
        $sql = "SELECT * FROM policies WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    // Get policy by slug
    public function getPolicyBySlug($slug) {
        $sql = "SELECT * FROM policies WHERE slug = ? AND status = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $slug);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }
    
    // Create new policy
    public function createPolicy($data) {
        // Generate unique policy ID
        $policy_id = 'POL' . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);
        
        $sql = "INSERT INTO policies (policy_id, title, slug, content, meta_title, meta_description, meta_keywords, status, display_order) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sssssssii", 
            $policy_id,
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['meta_title'],
            $data['meta_description'],
            $data['meta_keywords'],
            $data['status'],
            $data['display_order']
        );
        
        return $stmt->execute();
    }
    
    // Update policy
    public function updatePolicy($id, $data) {
        $sql = "UPDATE policies SET 
                title = ?,
                slug = ?,
                content = ?,
                meta_title = ?,
                meta_description = ?,
                meta_keywords = ?,
                status = ?,
                display_order = ?
                WHERE id = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssssssiii", 
            $data['title'],
            $data['slug'],
            $data['content'],
            $data['meta_title'],
            $data['meta_description'],
            $data['meta_keywords'],
            $data['status'],
            $data['display_order'],
            $id
        );
        
        return $stmt->execute();
    }
    
    // Delete policy
    public function deletePolicy($id) {
        $sql = "DELETE FROM policies WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Toggle status
    public function toggleStatus($id) {
        $sql = "UPDATE policies SET status = 1 - status WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Generate slug from title
    public function generateSlug($title) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Check if slug exists
        $counter = 1;
        $original_slug = $slug;
        
        while ($this->slugExists($slug)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    // Check if slug exists
    private function slugExists($slug, $exclude_id = null) {
        $sql = "SELECT id FROM policies WHERE slug = ?";
        
        if ($exclude_id) {
            $sql .= " AND id != ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $slug, $exclude_id);
        } else {
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $slug);
        }
        
        $stmt->execute();
        $stmt->store_result();
        
        return $stmt->num_rows > 0;
    }
    
    // Get policy stats
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(status = 1) as active,
                SUM(status = 0) as inactive
                FROM policies";
        
        $result = $this->conn->query($sql);
        return $result->fetch_assoc();
    }
}
?>