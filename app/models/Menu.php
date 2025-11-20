<?php
require_once 'src/model.php';

class Menu extends Model {
    
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Get all menu categories with their items
     * @param int|null $restaurantId - Filter by restaurant ID for multi-tenancy
     */
    public function getAllCategoriesWithItems($restaurantId = null) {
        try {
            $query = "SELECT 
                        c.id as category_id, 
                        c.name as category_name, 
                        c.description as category_description,
                        c.display_order,
                        m.id as item_id,
                        m.name as item_name,
                        m.description as item_description,
                        m.price,
                        m.image_url,
                        m.is_available,
                        m.is_special,
                        m.preparation_time,
                        m.dietary_info
                      FROM menu_categories c
                      LEFT JOIN menu_items m ON c.id = m.category_id";
            
            $conditions = ["c.is_active = 1"];
            $params = [];
            
            // Filter by restaurant_id for multi-tenancy
            if ($restaurantId) {
                $conditions[] = "c.restaurant_id = ?";
                $conditions[] = "(m.restaurant_id = ? OR m.id IS NULL)";
                $params[] = $restaurantId;
                $params[] = $restaurantId;
            }
            
            $query .= " WHERE " . implode(" AND ", $conditions);
            $query .= " ORDER BY c.display_order, m.name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute($params);
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Organize data by category
            $categories = [];
            foreach ($results as $row) {
                $catId = $row['category_id'];
                
                if (!isset($categories[$catId])) {
                    $categories[$catId] = [
                        'id' => $row['category_id'],
                        'name' => $row['category_name'],
                        'description' => $row['category_description'],
                        'display_order' => $row['display_order'],
                        'items' => []
                    ];
                }
                
                if ($row['item_id']) {
                    $categories[$catId]['items'][] = [
                        'id' => $row['item_id'],
                        'name' => $row['item_name'],
                        'description' => $row['item_description'],
                        'price' => $row['price'],
                        'image_url' => $row['image_url'],
                        'is_available' => $row['is_available'],
                        'is_special' => $row['is_special'],
                        'preparation_time' => $row['preparation_time'],
                        'dietary_info' => $row['dietary_info']
                    ];
                }
            }
            
            return ['status' => 'OK', 'data' => array_values($categories)];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch menu', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get menu item by ID
     */
    public function getItemById($itemId) {
        try {
            $query = "SELECT * FROM menu_items WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$itemId]);
            
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($item) {
                return ['status' => 'OK', 'data' => $item];
            } else {
                return ['status' => 'FAIL', 'message' => 'Item not found'];
            }
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch item', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Update menu item availability
     */
    public function updateItemAvailability($itemId, $isAvailable) {
        try {
            $query = "UPDATE menu_items SET is_available = ? WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$isAvailable, $itemId]);
            
            return ['status' => 'OK', 'message' => 'Item availability updated'];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to update availability', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get special/featured items
     */
    public function getSpecialItems() {
        try {
            $query = "SELECT m.*, c.name as category_name 
                      FROM menu_items m
                      INNER JOIN menu_categories c ON m.category_id = c.id
                      WHERE m.is_special = 1 AND m.is_available = 1
                      ORDER BY m.name";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $items];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Failed to fetch special items', 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Search menu items
     */
    public function searchItems($keyword) {
        try {
            $query = "SELECT m.*, c.name as category_name 
                      FROM menu_items m
                      INNER JOIN menu_categories c ON m.category_id = c.id
                      WHERE (m.name LIKE ? OR m.description LIKE ? OR m.dietary_info LIKE ?)
                      AND m.is_available = 1
                      ORDER BY m.name";
            
            $searchTerm = "%{$keyword}%";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['status' => 'OK', 'data' => $items];
            
        } catch (PDOException $e) {
            return ['status' => 'FAIL', 'message' => 'Search failed', 'error' => $e->getMessage()];
        }
    }
}
?>
