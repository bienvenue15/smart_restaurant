<?php
/**
 * UUID-Only Access Policy Enforcer
 * Prevents accidental use of INT IDs in external-facing code
 * 
 * STRICT RULE: After migration, INT 'id' can ONLY be used internally.
 * All external access MUST use UUIDs.
 */

class UUIDPolicy {
    
    /**
     * Validate that query uses UUID, not INT id
     * Call this before executing any user-facing query
     * 
     * @param string $query SQL query to validate
     * @param array $params Query parameters
     * @throws Exception if query uses 'id' in WHERE clause
     */
    public static function enforceUUIDOnly($query, $params = []) {
        // Check if query uses 'id' in WHERE clause (forbidden!)
        if (preg_match('/WHERE.*\b(restaurant_id|order_id|payment_id|staff_id)\b/i', $query)) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = $backtrace[1]['function'] ?? 'unknown';
            
            throw new Exception(
                "SECURITY POLICY VIOLATION: Query uses INT 'id' instead of UUID.\n" .
                "Query: {$query}\n" .
                "Caller: {$caller}\n\n" .
                "Use UUID columns instead:\n" .
                "  ✗ restaurant_id → ✓ restaurant_uuid\n" .
                "  ✗ order_id      → ✓ order_uuid\n" .
                "  ✗ payment_id    → ✓ payment_uuid\n" .
                "  ✗ staff_id      → ✓ staff_uuid"
            );
        }
        
        // Check if parameters contain numeric IDs (suspicious!)
        foreach ($params as $param) {
            if (is_numeric($param) && $param < 1000000) {
                error_log("WARNING: Query parameter '{$param}' looks like an INT id. Should use UUID instead!");
            }
        }
        
        return true;
    }
    
    /**
     * Sanitize API response - remove INT 'id' fields
     * Only UUIDs should be exposed externally
     * 
     * @param array $data Data to sanitize
     * @return array Sanitized data (IDs removed)
     */
    public static function sanitizeResponse($data) {
        if (!is_array($data)) {
            return $data;
        }
        
        // Fields to remove from external responses
        $forbiddenFields = [
            'id',
            'restaurant_id',
            'order_id',
            'payment_id',
            'staff_id',
            'category_id',
            'item_id'
        ];
        
        foreach ($forbiddenFields as $field) {
            if (isset($data[$field])) {
                unset($data[$field]);
            }
        }
        
        // Recursively sanitize nested arrays
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::sanitizeResponse($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Log usage of INT ids (for auditing migration progress)
     * 
     * @param string $context Where the ID was used
     * @param mixed $idValue The ID value
     */
    public static function logIdUsage($context, $idValue) {
        error_log(sprintf(
            "[UUID POLICY] INT id used in %s: %s (should migrate to UUID)",
            $context,
            $idValue
        ));
    }
    
    /**
     * Check if value is UUID format
     * @param string $value Value to check
     * @return bool True if UUID format
     */
    public static function isUUID($value) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value) === 1;
    }
    
    /**
     * Assert parameter is UUID, not INT id
     * Throws exception if not UUID
     * 
     * @param mixed $value Value to check
     * @param string $paramName Parameter name for error message
     * @throws Exception if not UUID
     */
    public static function assertIsUUID($value, $paramName = 'parameter') {
        if (!self::isUUID($value)) {
            throw new Exception(
                "SECURITY POLICY VIOLATION: {$paramName} must be UUID format.\n" .
                "Received: {$value}\n" .
                "Expected format: xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
            );
        }
    }
}
