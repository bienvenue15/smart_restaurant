<?php
/**
 * Class which establishes a connection to the database with multi-tenancy support
 */
require_once 'src/config.php';

class Model {
    public $db;
    protected $restaurantId;
    protected $tenantTables = [
        'staff_users', 'restaurant_tables', 'menu_categories', 'menu_items', 
        'orders', 'order_items', 'waiter_calls', 'payments', 
        'cash_sessions', 'audit_trail'
    ];

    public function __construct(){
        $this->db = new PDO(DB_TYPE.":host=".DB_HOST.";dbname=".DB_NAME,DB_USER,DB_PWD,[
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false
        ]);
        
        // Get current restaurant context
        if (class_exists('Restaurant')) {
            $this->restaurantId = Restaurant::getCurrentId();
        }
    }

    public function save($table, $data){
       try{
        // Auto-add restaurant_id for tenant tables
        if (in_array($table, $this->tenantTables) && $this->restaurantId !== null) {
            if (!isset($data['restaurant_id'])) {
                $data['restaurant_id'] = $this->restaurantId;
            }
        }
        
        $qb = "INSERT INTO ".$table."(";
        $num = count($data);
        $i = 0;
        $qm = "";
        $values = [];
       // return $qb;
        foreach($data as $column => $value){
            $qb .= $i>0 ? ",".$column : $column;
            $qm .= $i>0 ? ",?" : "?";
            array_push($values, $value);
            ++$i;
        }
        
        $qb .= ")values(".$qm.")";
        //return $qb;
        $stm = $this->db->prepare($qb);
        $stm->execute($values);
        
        return ["status" => "OK", "message" => "Data inserted succesfully", "id" => $this->db->lastInsertId()];
    }
    catch(PDOException $e){
        return ["status" => "FAIL", "message" => "Something went wrong", "error" => $e->getMessage()];
    }
    }
    
    /**
     * Execute query with automatic restaurant filtering
     */
    public function query($sql, $params = []) {
        // Add restaurant filter for tenant tables
        if ($this->restaurantId !== null && $this->needsTenantFilter($sql)) {
            $sql = $this->addTenantFilter($sql);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Check if query needs tenant filtering
     */
    private function needsTenantFilter($sql) {
        $sql = strtoupper($sql);
        if (strpos($sql, 'SELECT') !== 0) {
            return false;
        }
        
        foreach ($this->tenantTables as $table) {
            if (strpos($sql, strtoupper($table)) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add restaurant_id filter to query
     */
    private function addTenantFilter($sql) {
        if (stripos($sql, 'WHERE') !== false) {
            $sql = preg_replace('/WHERE/i', "WHERE restaurant_id = {$this->restaurantId} AND", $sql, 1);
        } else {
            $keywords = ['ORDER BY', 'GROUP BY', 'LIMIT', 'HAVING'];
            $inserted = false;
            
            foreach ($keywords as $keyword) {
                if (stripos($sql, $keyword) !== false) {
                    $sql = preg_replace("/$keyword/i", "WHERE restaurant_id = {$this->restaurantId} $keyword", $sql, 1);
                    $inserted = true;
                    break;
                }
            }
            
            if (!$inserted) {
                $sql .= " WHERE restaurant_id = {$this->restaurantId}";
            }
        }
        
        return $sql;
    }
    
    /**
     * Get current restaurant ID
     */
    public function getRestaurantId() {
        return $this->restaurantId;
    }
}
?>