<?php
/**
 * Validation Helper Class
 * Provides server-side validation for all form inputs to match frontend validation rules
 * SECURITY: Never trust client-side validation alone - always validate on server
 */

class ValidationHelper {
    
    /**
     * Validate required field
     */
    public static function required($value, $fieldName = 'Field') {
        if (empty($value) && $value !== '0' && $value !== 0) {
            return "$fieldName is required";
        }
        return null;
    }
    
    /**
     * Validate email format
     */
    public static function email($value, $fieldName = 'Email') {
        if (empty($value)) {
            return null; // Use required() separately for empty check
        }
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return "$fieldName must be a valid email address";
        }
        return null;
    }
    
    /**
     * Validate string length
     */
    public static function length($value, $min = null, $max = null, $fieldName = 'Field') {
        $len = strlen($value);
        
        if ($min !== null && $len < $min) {
            return "$fieldName must be at least $min characters";
        }
        
        if ($max !== null && $len > $max) {
            return "$fieldName must not exceed $max characters";
        }
        
        return null;
    }
    
    /**
     * Validate numeric range
     */
    public static function range($value, $min = null, $max = null, $fieldName = 'Value') {
        if (!is_numeric($value)) {
            return "$fieldName must be a number";
        }
        
        $num = floatval($value);
        
        if ($min !== null && $num < $min) {
            return "$fieldName must be at least $min";
        }
        
        if ($max !== null && $num > $max) {
            return "$fieldName must not exceed $max";
        }
        
        return null;
    }
    
    /**
     * Validate integer value
     */
    public static function integer($value, $fieldName = 'Value') {
        if (!is_numeric($value) || intval($value) != $value) {
            return "$fieldName must be a whole number";
        }
        return null;
    }
    
    /**
     * Validate pattern match (regex)
     */
    public static function pattern($value, $pattern, $message, $fieldName = 'Field') {
        if (empty($value)) {
            return null; // Use required() separately
        }
        if (!preg_match($pattern, $value)) {
            return $message ?? "$fieldName has invalid format";
        }
        return null;
    }
    
    /**
     * Validate phone number (Rwandan format: 10 digits starting with 07 or +2507)
     */
    public static function phone($value, $fieldName = 'Phone number') {
        if (empty($value)) {
            return null;
        }
        
        // Remove spaces and dashes
        $clean = preg_replace('/[\s\-]/', '', $value);
        
        // Accept: 0712345678, +250712345678, 250712345678
        if (!preg_match('/^(\+?250|0)?7[0-9]{8}$/', $clean)) {
            return "$fieldName must be a valid Rwandan phone number (e.g., 0712345678)";
        }
        
        return null;
    }
    
    /**
     * Validate TIN number (9-10 digits)
     */
    public static function tin($value, $fieldName = 'TIN') {
        if (empty($value)) {
            return null;
        }
        if (!preg_match('/^[0-9]{9,10}$/', $value)) {
            return "$fieldName must be 9-10 digits";
        }
        return null;
    }
    
    /**
     * Validate slug format (lowercase letters, numbers, hyphens only)
     */
    public static function slug($value, $fieldName = 'Slug') {
        if (empty($value)) {
            return null;
        }
        if (!preg_match('/^[a-z0-9\-]+$/', $value)) {
            return "$fieldName can only contain lowercase letters, numbers, and hyphens";
        }
        return null;
    }
    
    /**
     * Validate UUID format
     */
    public static function uuid($value, $fieldName = 'UUID') {
        if (empty($value)) {
            return null;
        }
        if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
            return "$fieldName has invalid format";
        }
        return null;
    }
    
    /**
     * Validate file upload
     */
    public static function file($file, $maxSize, $allowedTypes = [], $fieldName = 'File') {
        if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return null; // No file uploaded (use required() if mandatory)
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return "$fieldName upload failed (error code: {$file['error']})";
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $maxMB = round($maxSize / 1024 / 1024, 1);
            $actualMB = round($file['size'] / 1024 / 1024, 1);
            return "$fieldName is too large ({$actualMB}MB). Maximum size is {$maxMB}MB";
        }
        
        // Check MIME type
        if (!empty($allowedTypes)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mimeType, $allowedTypes)) {
                $allowed = implode(', ', $allowedTypes);
                return "$fieldName type not allowed. Allowed types: $allowed";
            }
        }
        
        return null;
    }
    
    /**
     * Validate date format (YYYY-MM-DD)
     */
    public static function date($value, $fieldName = 'Date') {
        if (empty($value)) {
            return null;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return "$fieldName must be in format YYYY-MM-DD";
        }
        
        // Validate actual date
        $parts = explode('-', $value);
        if (!checkdate($parts[1], $parts[2], $parts[0])) {
            return "$fieldName is not a valid date";
        }
        
        return null;
    }
    
    /**
     * Validate URL format
     */
    public static function url($value, $fieldName = 'URL') {
        if (empty($value)) {
            return null;
        }
        if (!filter_var($value, FILTER_VALIDATE_URL)) {
            return "$fieldName must be a valid URL";
        }
        return null;
    }
    
    /**
     * Validate color hex code
     */
    public static function hexColor($value, $fieldName = 'Color') {
        if (empty($value)) {
            return null;
        }
        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
            return "$fieldName must be a valid hex color code (e.g., #FF5733)";
        }
        return null;
    }
    
    /**
     * Validate password strength
     */
    public static function password($value, $minLength = 8, $fieldName = 'Password') {
        if (empty($value)) {
            return null;
        }
        
        if (strlen($value) < $minLength) {
            return "$fieldName must be at least $minLength characters";
        }
        
        // Optional: Require complexity (uncomment if needed)
        // if (!preg_match('/[A-Z]/', $value)) {
        //     return "$fieldName must contain at least one uppercase letter";
        // }
        // if (!preg_match('/[a-z]/', $value)) {
        //     return "$fieldName must contain at least one lowercase letter";
        // }
        // if (!preg_match('/[0-9]/', $value)) {
        //     return "$fieldName must contain at least one number";
        // }
        
        return null;
    }
    
    /**
     * Validate multiple fields at once
     * 
     * @param array $rules Format: ['field_name' => ['rule' => 'params', ...], ...]
     * @param array $data The data to validate
     * @return array Array of errors (field_name => error_message)
     * 
     * Example:
     * $rules = [
     *     'email' => ['required' => true, 'email' => true],
     *     'name' => ['required' => true, 'length' => [3, 100]],
     *     'price' => ['required' => true, 'range' => [0, 999999]]
     * ];
     */
    public static function validateMultiple($rules, $data) {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? '';
            $fieldName = ucfirst(str_replace('_', ' ', $field));
            
            foreach ($fieldRules as $rule => $params) {
                $error = null;
                
                switch ($rule) {
                    case 'required':
                        if ($params) {
                            $error = self::required($value, $fieldName);
                        }
                        break;
                        
                    case 'email':
                        if ($params) {
                            $error = self::email($value, $fieldName);
                        }
                        break;
                        
                    case 'length':
                        $min = $params[0] ?? null;
                        $max = $params[1] ?? null;
                        $error = self::length($value, $min, $max, $fieldName);
                        break;
                        
                    case 'range':
                        $min = $params[0] ?? null;
                        $max = $params[1] ?? null;
                        $error = self::range($value, $min, $max, $fieldName);
                        break;
                        
                    case 'integer':
                        if ($params) {
                            $error = self::integer($value, $fieldName);
                        }
                        break;
                        
                    case 'pattern':
                        $pattern = $params[0] ?? '';
                        $message = $params[1] ?? null;
                        $error = self::pattern($value, $pattern, $message, $fieldName);
                        break;
                        
                    case 'phone':
                        if ($params) {
                            $error = self::phone($value, $fieldName);
                        }
                        break;
                        
                    case 'tin':
                        if ($params) {
                            $error = self::tin($value, $fieldName);
                        }
                        break;
                        
                    case 'slug':
                        if ($params) {
                            $error = self::slug($value, $fieldName);
                        }
                        break;
                        
                    case 'uuid':
                        if ($params) {
                            $error = self::uuid($value, $fieldName);
                        }
                        break;
                        
                    case 'date':
                        if ($params) {
                            $error = self::date($value, $fieldName);
                        }
                        break;
                        
                    case 'url':
                        if ($params) {
                            $error = self::url($value, $fieldName);
                        }
                        break;
                        
                    case 'hexColor':
                        if ($params) {
                            $error = self::hexColor($value, $fieldName);
                        }
                        break;
                        
                    case 'password':
                        $minLength = is_array($params) ? ($params[0] ?? 8) : 8;
                        $error = self::password($value, $minLength, $fieldName);
                        break;
                }
                
                if ($error) {
                    $errors[$field] = $error;
                    break; // Stop at first error for this field
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize string input (strip tags, trim)
     */
    public static function sanitize($value) {
        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }
        return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize for SQL LIKE query (escape wildcards)
     */
    public static function sanitizeLike($value) {
        return str_replace(['%', '_'], ['\%', '\_'], $value);
    }
}
