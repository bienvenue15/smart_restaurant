<?php
/**
 * Auto loader with multi-tenancy support
 */
require_once 'src/controller.php';
require_once 'src/view.php';
require_once 'src/model.php';
require_once 'app/core/SystemSettings.php';

// Only load restaurant classes if not in registration or superadmin
$skipRestaurantInit = false;
if (isset($_GET['req'])) {
    $req = $_GET['req'];
    if ($req === 'register' || $req === 'superadmin') {
        $skipRestaurantInit = true;
    }
}

if (!$skipRestaurantInit) {
    require_once 'src/restaurant.php';
    require_once 'src/tenant_middleware.php';
}

function maintenanceBypassAllowed($reqParam)
{
    if (php_sapi_name() === 'cli') {
        return true;
    }
    $allowed = ['superadmin'];
    return in_array($reqParam, $allowed, true);
}

$currentReq = $_GET['req'] ?? '';
if (SystemSettings::isMaintenanceMode() && !maintenanceBypassAllowed($currentReq)) {
    if ($currentReq === 'api') {
        header('Content-Type: application/json', true, 503);
        echo json_encode([
            'status' => 'FAIL',
            'message' => 'System temporarily unavailable due to maintenance. Please try again later.'
        ]);
    } else {
        include 'app/views/maintenance.php';
    }
    exit;
}

 final class Autoload{
    private $url =null;
    private $controller;
    public function __construct(){
        // Initialize restaurant context only if not skipped
        global $skipRestaurantInit;
        if (!$skipRestaurantInit && class_exists('Restaurant')) {
            Restaurant::initialize();
        }
        
       // echo "Now the auto load is about to start working<br />";
        if(isset($_GET['req'])){
            $this->url = explode('/',$_GET['req']);
        }

        if(!empty($this->url) && $this->url != null){
            $filePath = 'app/controllers/'.$this->url[0].".php";
            
            if(file_exists($filePath)){
                require_once $filePath;
                
                // Try to find the controller class
                $className = ucfirst($this->url[0]);
                $controllerClass = $className . 'Controller';
                
                error_log('[AUTOLOAD] Looking for controller class: ' . $controllerClass . ' or ' . $className);
                
                // Check if the Controller suffix class exists, otherwise use original name
                try {
                    if (class_exists($controllerClass)) {
                        error_log('[AUTOLOAD] Found class: ' . $controllerClass);
                        error_log('[AUTOLOAD] Instantiating controller...');
                        $this->controller = new $controllerClass;
                        error_log('[AUTOLOAD] Controller instantiated successfully');
                    } else if (class_exists($className)) {
                        error_log('[AUTOLOAD] Found class: ' . $className);
                        error_log('[AUTOLOAD] Instantiating controller...');
                        $this->controller = new $className;
                        error_log('[AUTOLOAD] Controller instantiated successfully');
                    } else {
                        error_log('[AUTOLOAD] ERROR: Controller class not found: ' . $controllerClass . ' or ' . $className);
                        echo "Error 404 Controller class not found: " . $controllerClass . " or " . $className;
                        return;
                    }
                    
                    error_log('[AUTOLOAD] Calling controller->index()');
                    $this->controller->index();
                    error_log('[AUTOLOAD] Controller->index() completed');
                } catch (Throwable $e) {
                    error_log('[AUTOLOAD] FATAL ERROR: ' . $e->getMessage());
                    error_log('[AUTOLOAD] ERROR stack: ' . $e->getTraceAsString());
                    // Show error on page
                    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
                    echo "<h1>Fatal Error</h1>";
                    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
                    echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " (Line " . $e->getLine() . ")</p>";
                    echo "<pre style='background:#f5f5f5;padding:10px;overflow:auto;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
                    echo "</body></html>";
                    exit;
                }
            }
            else{
                echo "Error 404 Controller ".$this->url[0]." Not found";
            }
        }
        else{
            $filePath = 'app/controllers/'.DEFAULT_CONTROLLER.'.php';
            //echo $filePath;
            if(file_exists($filePath)){
                require_once $filePath;
                $className = ucfirst(DEFAULT_CONTROLLER);
                $controllerClass = $className . 'Controller';
                
                // Check if the Controller suffix class exists, otherwise use original name
                if (class_exists($controllerClass)) {
                    $this->controller = new $controllerClass;
                } else if (class_exists($className)) {
                    $this->controller = new $className;
                } else {
                    echo "Error 404 Controller class not found";
                    return;
                }
                
                $this->controller->index();
            }
            else{
                echo "Error 404 Controller ".$this->url[0]." Not found";
            }
        }
        //print_r($this->url);
    }
 }