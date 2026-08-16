<?php
/**
 * UUID Migration Helper - SQL Query Converter
 * 
 * This script provides helper functions to automatically convert SQL queries
 * from integer ID columns to UUID columns. Use this during the transition period.
 * 
 * Usage in controllers/models:
 *   require_once 'src/UUIDQueryConverter.php';
 *   $sql = UUIDQueryConverter::convert($originalSql);
 */

class UUIDQueryConverter {
    
    /**
     * Column mapping from old integer columns to new UUID columns
     */
    private static $columnMap = [
        // Primary key mappings
        'id' => 'uuid',
        
        // Foreign key mappings
        'restaurant_id' => 'restaurant_uuid',
        'staff_id' => 'staff_uuid',
        'order_id' => 'order_uuid',
        'table_id' => 'table_uuid',
        'menu_item_id' => 'menu_item_uuid',
        'category_id' => 'category_uuid',
        'waiter_id' => 'waiter_uuid',
        'session_id' => 'session_uuid',
        'cash_session_id' => 'cash_session_uuid',
        'payment_id' => 'payment_uuid',
        'call_id' => 'call_uuid',
        
        // Special assignment columns
        'assigned_to' => 'assigned_to_uuid',
        'assigned_by' => 'assigned_by_uuid',
        'approved_by' => 'approved_by_uuid',
        'created_by' => 'created_by_uuid',
        'updated_by' => 'updated_by_uuid',
        'created_by_staff' => 'created_by_staff_uuid',
        'acknowledged_by' => 'acknowledged_by_uuid',
        'completed_by' => 'completed_by_uuid',
        'received_by' => 'received_by_uuid',
    ];
    
    /**
     * Convert SQL query from integer IDs to UUIDs
     * 
     * @param string $sql The original SQL query
     * @param array $customMappings Additional column mappings (optional)
     * @return string The converted SQL query
     */
    public static function convert($sql, $customMappings = []) {
        $map = array_merge(self::$columnMap, $customMappings);
        $converted = $sql;
        
        foreach ($map as $old => $new) {
            // Convert in WHERE clauses
            $converted = preg_replace(
                "/\bWHERE\s+$old\s*=/i",
                "WHERE $new =",
                $converted
            );
            $converted = preg_replace(
                "/\bAND\s+$old\s*=/i",
                "AND $new =",
                $converted
            );
            $converted = preg_replace(
                "/\bWHERE\s+(\w+)\.$old\s*=/i",
                "WHERE $1.$new =",
                $converted
            );
            $converted = preg_replace(
                "/\bAND\s+(\w+)\.$old\s*=/i",
                "AND $1.$new =",
                $converted
            );
            
            // Convert in SELECT clauses
            $converted = preg_replace(
                "/\bSELECT\s+$old,/i",
                "SELECT $new,",
                $converted
            );
            $converted = preg_replace(
                "/\bSELECT\s+(\w+)\.$old,/i",
                "SELECT $1.$new,",
                $converted
            );
            $converted = preg_replace(
                "/,\s*$old,/i",
                ", $new,",
                $converted
            );
            $converted = preg_replace(
                "/,\s*(\w+)\.$old,/i",
                ", $1.$new,",
                $converted
            );
            
            // Convert in JOIN clauses
            $converted = preg_replace(
                "/\bON\s+(\w+)\.$old\s*=\s*(\w+)\.(\w+)/i",
                "ON $1.$new = $2.$3",
                $converted
            );
            
            // Convert in INSERT/UPDATE statements
            $converted = preg_replace(
                "/\bSET\s+$old\s*=/i",
                "SET $new =",
                $converted
            );
            $converted = preg_replace(
                "/\(\s*$old,/i",
                "($new,",
                $converted
            );
            $converted = preg_replace(
                "/,\s*$old\s*\)/i",
                ", $new)",
                $converted
            );
        }
        
        return $converted;
    }
    
    /**
     * Convert array keys from integer IDs to UUIDs
     * 
     * @param array $data The data array
     * @param array $customMappings Additional column mappings (optional)
     * @return array The converted array
     */
    public static function convertArrayKeys($data, $customMappings = []) {
        if (!is_array($data)) {
            return $data;
        }
        
        $map = array_merge(self::$columnMap, $customMappings);
        $converted = [];
        
        foreach ($data as $key => $value) {
            $newKey = isset($map[$key]) ? $map[$key] : $key;
            $converted[$newKey] = is_array($value) ? self::convertArrayKeys($value, $customMappings) : $value;
        }
        
        return $converted;
    }
    
    /**
     * Get the UUID column name for a given old column name
     * 
     * @param string $oldColumn The old column name
     * @return string The new UUID column name
     */
    public static function getUUIDColumn($oldColumn) {
        return self::$columnMap[$oldColumn] ?? $oldColumn;
    }
    
    /**
     * Check if a column has been migrated to UUID
     * 
     * @param string $columnName The column name to check
     * @return bool True if migrated, false otherwise
     */
    public static function isMigrated($columnName) {
        return isset(self::$columnMap[$columnName]);
    }
    
    /**
     * Get all column mappings
     * 
     * @return array The column mapping array
     */
    public static function getAllMappings() {
        return self::$columnMap;
    }
}
