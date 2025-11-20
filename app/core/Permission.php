<?php
/**
 * Permission Helper Class
 * Provides middleware-style permission checking for controllers
 * 
 * Copyright (c) 2024 Inovasiyo Ltd. All rights reserved.
 */

class Permission {
    
    /**
     * Check if current staff has permission
     * Returns JSON error and exits if not authorized
     */
    public static function require($permissionCode, $returnJson = true) {
        if (!isset($_SESSION['staff_id'])) {
            if ($returnJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'FAIL',
                    'message' => 'Authentication required'
                ]);
                exit;
            } else {
                header('Location: /restaurant/staff?action=login&error=auth_required');
                exit;
            }
        }
        
        $staffModel = new Staff();
        $hasPermission = $staffModel->hasPermission($_SESSION['staff_id'], $permissionCode);
        
        if (!$hasPermission) {
            if ($returnJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'FAIL',
                    'message' => 'You do not have permission to perform this action',
                    'required_permission' => $permissionCode
                ]);
                exit;
            } else {
                header('Location: /restaurant/staff?action=dashboard&error=permission_denied');
                exit;
            }
        }
        
        return true;
    }
    
    /**
     * Check if current staff has any of the given permissions
     */
    public static function requireAny($permissionCodes, $returnJson = true) {
        if (!isset($_SESSION['staff_id'])) {
            if ($returnJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'FAIL',
                    'message' => 'Authentication required'
                ]);
                exit;
            } else {
                header('Location: /restaurant/staff?action=login&error=auth_required');
                exit;
            }
        }
        
        $staffModel = new Staff();
        
        foreach ($permissionCodes as $code) {
            if ($staffModel->hasPermission($_SESSION['staff_id'], $code)) {
                return true;
            }
        }
        
        // No permission found
        if ($returnJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'FAIL',
                'message' => 'You do not have permission to perform this action',
                'required_permissions' => $permissionCodes
            ]);
            exit;
        } else {
            header('Location: /restaurant/staff?action=dashboard&error=permission_denied');
            exit;
        }
    }
    
    /**
     * Check permission without exiting (returns boolean)
     */
    public static function check($permissionCode) {
        if (!isset($_SESSION['staff_id'])) {
            return false;
        }
        
        $staffModel = new Staff();
        return $staffModel->hasPermission($_SESSION['staff_id'], $permissionCode);
    }
    
    /**
     * Require staff to be on active shift
     */
    public static function requireShift($returnJson = true) {
        if (!isset($_SESSION['staff_id'])) {
            if ($returnJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'FAIL',
                    'message' => 'Authentication required'
                ]);
                exit;
            } else {
                header('Location: /restaurant/staff?action=login&error=auth_required');
                exit;
            }
        }
        
        $staffModel = new Staff();
        $isOnShift = $staffModel->isOnShift($_SESSION['staff_id']);
        
        if (!$isOnShift) {
            if ($returnJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'status' => 'FAIL',
                    'message' => 'You must clock in before performing this action'
                ]);
                exit;
            } else {
                header('Location: /restaurant/staff?action=dashboard&error=not_on_shift');
                exit;
            }
        }
        
        return true;
    }
    
    /**
     * Get current staff info
     */
    public static function getStaffInfo() {
        if (!isset($_SESSION['staff_id'])) {
            return null;
        }
        
        return [
            'id' => $_SESSION['staff_id'],
            'username' => $_SESSION['staff_username'] ?? '',
            'full_name' => $_SESSION['staff_full_name'] ?? '',
            'role' => $_SESSION['staff_role'] ?? ''
        ];
    }
    
    /**
     * Log audit trail
     */
    public static function logAudit($actionType, $tableName, $recordId, $oldValue = null, $newValue = null, $reason = '') {
        if (!isset($_SESSION['staff_id'])) {
            return false;
        }
        
        $staffModel = new Staff();
        return $staffModel->logAudit(
            $_SESSION['staff_id'],
            $actionType,
            $tableName,
            $recordId,
            $oldValue,
            $newValue,
            $reason
        );
    }
}
?>
