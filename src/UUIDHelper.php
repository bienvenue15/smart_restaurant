<?php
/**
 * UUID Helper Functions
 * Provides utility functions for working with UUIDs in the system
 */

class UUIDHelper {
    
    /**
     * Generate a new UUID v4
     * @return string
     */
    public static function generate() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Validate UUID format
     * @param string $uuid
     * @return bool
     */
    public static function isValid($uuid) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
    
    /**
     * Column mapping: old integer column -> new UUID column
     * @return array
     */
    public static function getColumnMapping() {
        return [
            // Restaurant references
            'restaurant_id' => 'restaurant_uuid',
            
            // Staff references
            'staff_id' => 'staff_uuid',
            'waiter_id' => 'waiter_uuid',
            'confirmed_by' => 'confirmed_by_uuid',
            'served_by' => 'served_by_uuid',
            'paid_to' => 'paid_to_uuid',
            'created_by_staff' => 'created_by_staff_uuid',
            'received_by' => 'received_by_uuid',
            'acknowledged_by' => 'acknowledged_by_uuid',
            'completed_by' => 'completed_by_uuid',
            'cleared_by' => 'cleared_by_uuid',
            'approved_by' => 'approved_by_uuid',
            
            // Entity references
            'order_id' => 'order_uuid',
            'table_id' => 'table_uuid',
            'menu_item_id' => 'menu_item_uuid',
            'category_id' => 'category_uuid',
            'session_id' => 'session_uuid',
            'cash_session_id' => 'session_uuid',
        ];
    }
    
    /**
     * Get UUID column name for a given old column name
     * @param string $oldColumn
     * @return string
     */
    public static function getUUIDColumn($oldColumn) {
        $mapping = self::getColumnMapping();
        return $mapping[$oldColumn] ?? $oldColumn;
    }
    
    /**
     * Convert WHERE clause from ID to UUID
     * @param string $sql
     * @return string
     */
    public static function convertSQLToUUID($sql) {
        $mapping = self::getColumnMapping();
        
        foreach ($mapping as $oldCol => $newCol) {
            // Replace in WHERE clauses
            $sql = preg_replace(
                '/\bWHERE\s+' . $oldCol . '\s*=/i',
                'WHERE ' . $newCol . ' =',
                $sql
            );
            
            // Replace in AND clauses
            $sql = preg_replace(
                '/\bAND\s+' . $oldCol . '\s*=/i',
                'AND ' . $newCol . ' =',
                $sql
            );
            
            // Replace in column lists
            $sql = preg_replace(
                '/,\s*' . $oldCol . '\s*,/i',
                ', ' . $newCol . ',',
                $sql
            );
            
            // Replace in SELECT
            $sql = preg_replace(
                '/SELECT\s+' . $oldCol . '\s*,/i',
                'SELECT ' . $newCol . ',',
                $sql
            );
        }
        
        return $sql;
    }
    
    /**
     * Get table primary key column name
     * @param string $tableName
     * @return string
     */
    public static function getPrimaryKey($tableName) {
        $uuidTables = [
            'restaurants', 'staff_users', 'menu_categories', 'menu_items',
            'restaurant_tables', 'orders', 'order_items', 'payments',
            'waiter_calls', 'waiter_liabilities', 'cash_sessions', 'cash_transactions'
        ];
        
        return in_array($tableName, $uuidTables) ? 'uuid' : 'id';
    }
    
    /**
     * Prepare SQL with UUID placeholders
     * @param string $sql
     * @param array $params
     * @param PDO $pdo
     * @return PDOStatement
     */
    public static function prepare($sql, $params, $pdo) {
        $sql = self::convertSQLToUUID($sql);
        $stmt = $pdo->prepare($sql);
        
        // Validate UUIDs in params
        foreach ($params as $key => $value) {
            if (is_string($value) && strlen($value) === 36 && strpos($value, '-') !== false) {
                if (!self::isValid($value)) {
                    throw new Exception("Invalid UUID format: $value");
                }
            }
        }
        
        return $stmt;
    }
}

/**
 * Legacy ID to UUID mapping cache
 * For transitional queries that might still reference old IDs
 */
class LegacyIDMapper {
    private static $cache = [];
    
    /**
     * Get UUID for legacy integer ID
     * @param string $table
     * @param int $oldId
     * @param PDO $pdo
     * @return string|null
     */
    public static function getUUID($table, $oldId, $pdo) {
        $cacheKey = "$table:$oldId";
        
        if (isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }
        
        // This only works if you kept a backup mapping table
        // For now, return null as we've removed all ID columns
        return null;
    }
}
