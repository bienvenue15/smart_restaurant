<?php
/**
 * Announcement Manager
 * Handles system-wide announcements for restaurants
 */

class AnnouncementManager {
    
    private $db;
    
    public function __construct($db = null) {
        if ($db) {
            $this->db = $db;
        } else {
            $this->db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PWD
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }
    
    /**
     * Get active announcements for a specific context
     * 
     * @param string $audience 'all', 'staff', 'customers', 'admins'
     * @param int|null $restaurantId Specific restaurant or null for all
     * @param int|null $userId User ID to check dismissals
     * @return array Active announcements
     */
    public function getActiveAnnouncements($audience = 'all', $restaurantId = null, $userId = null) {
        $query = "SELECT a.* 
                  FROM announcements a
                  WHERE a.is_active = 1
                  AND (a.target_audience = :audience OR a.target_audience = 'all')
                  AND (a.restaurant_id IS NULL OR a.restaurant_id = :restaurant_id)
                  AND (a.start_date IS NULL OR a.start_date <= NOW())
                  AND (a.end_date IS NULL OR a.end_date >= NOW())";
        
        // Exclude dismissed announcements if user ID provided
        if ($userId) {
            $query .= " AND a.id NOT IN (
                SELECT announcement_id 
                FROM announcement_dismissals 
                WHERE user_id = :user_id
            )";
        }
        
        $query .= " ORDER BY 
                    FIELD(a.priority, 'urgent', 'high', 'normal', 'low'),
                    a.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':audience', $audience, PDO::PARAM_STR);
        $stmt->bindValue(':restaurant_id', $restaurantId, PDO::PARAM_INT);
        
        if ($userId) {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create new announcement
     */
    public function createAnnouncement($data) {
        $query = "INSERT INTO announcements (
                    title, message, type, target_audience, priority, 
                    restaurant_id, is_active, is_dismissible, 
                    start_date, end_date, created_by
                  ) VALUES (
                    :title, :message, :type, :target_audience, :priority,
                    :restaurant_id, :is_active, :is_dismissible,
                    :start_date, :end_date, :created_by
                  )";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'] ?? 'info',
            'target_audience' => $data['target_audience'] ?? 'all',
            'priority' => $data['priority'] ?? 'normal',
            'restaurant_id' => $data['restaurant_id'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'is_dismissible' => $data['is_dismissible'] ?? 1,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'created_by' => $data['created_by'] ?? null
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update announcement
     */
    public function updateAnnouncement($id, $data) {
        $query = "UPDATE announcements SET
                    title = :title,
                    message = :message,
                    type = :type,
                    target_audience = :target_audience,
                    priority = :priority,
                    restaurant_id = :restaurant_id,
                    is_active = :is_active,
                    is_dismissible = :is_dismissible,
                    start_date = :start_date,
                    end_date = :end_date
                  WHERE id = :id";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'],
            'target_audience' => $data['target_audience'],
            'priority' => $data['priority'],
            'restaurant_id' => $data['restaurant_id'] ?? null,
            'is_active' => $data['is_active'],
            'is_dismissible' => $data['is_dismissible'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null
        ]);
    }
    
    /**
     * Delete announcement
     */
    public function deleteAnnouncement($id) {
        $stmt = $this->db->prepare("DELETE FROM announcements WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Get all announcements (for admin management)
     */
    public function getAllAnnouncements($limit = 100, $offset = 0) {
        $query = "SELECT a.*, 
                         r.name as restaurant_name,
                         COUNT(DISTINCT ad.user_id) as dismissal_count
                  FROM announcements a
                  LEFT JOIN restaurants r ON a.restaurant_id = r.id
                  LEFT JOIN announcement_dismissals ad ON a.id = ad.announcement_id
                  GROUP BY a.id
                  ORDER BY a.created_at DESC
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Dismiss announcement for a user
     */
    public function dismissAnnouncement($announcementId, $userId) {
        $query = "INSERT IGNORE INTO announcement_dismissals (announcement_id, user_id) 
                  VALUES (:announcement_id, :user_id)";
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            'announcement_id' => $announcementId,
            'user_id' => $userId
        ]);
    }
    
    /**
     * Toggle announcement status
     */
    public function toggleStatus($id) {
        $query = "UPDATE announcements SET is_active = NOT is_active WHERE id = ?";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([$id]);
    }
    
    /**
     * Get announcement statistics
     */
    public function getStatistics() {
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(is_active = 1) as active,
                    SUM(is_active = 0) as inactive,
                    SUM(target_audience = 'staff') as for_staff,
                    SUM(target_audience = 'customers') as for_customers,
                    SUM(CASE WHEN restaurant_id IS NULL THEN 1 ELSE 0 END) as broadcast
                  FROM announcements";
        
        $stmt = $this->db->query($query);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
