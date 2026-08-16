<?php
/**
 * Server-Sent Events (SSE) Controller
 * Provides real-time updates to clients without polling
 */

// Prevent timeout for long-running SSE connection
set_time_limit(0);
ini_set('max_execution_time', 0);

// Start session if not started
$sessionsDir = dirname(__DIR__, 2) . '/sessions';
if (is_dir($sessionsDir) && is_writable($sessionsDir)) {
    session_save_path($sessionsDir);
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../src/config.php';
require_once __DIR__ . '/../core/EventBroadcaster.php';

class EventsController {
    private $db;
    private $restaurantId;
    private $userId;
    private $userRole;
    private $lastEventId;
    
    public function __construct() {
        // Database connection
        try {
            $this->db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            error_log("SSE Database Connection Error: " . $e->getMessage());
            $this->sendErrorAndExit("Database connection failed");
        }
        
        // Get user context
        if (isset($_SESSION['staff_user'])) {
            // Staff user
            $this->restaurantId = $_SESSION['staff_user']['restaurant_uuid'] ?? null;
            $this->userId = $_SESSION['staff_user']['uuid'] ?? null;
            $this->userRole = $_SESSION['staff_user']['role'] ?? 'guest';
        } elseif (isset($_SESSION['restaurant_uuid'])) {
            // Customer user
            $this->restaurantId = $_SESSION['restaurant_uuid'];
            $this->userId = session_id(); // Use session ID for customers
            $this->userRole = 'customer';
        } else {
            // No valid session - send test mode
            $this->restaurantId = null;
            $this->userId = 'test-' . session_id();
            $this->userRole = 'test';
        }
        
        // Ensure event_stream table exists
        $this->ensureEventStreamTable();
        
        $this->lastEventId = isset($_GET['lastEventId']) ? intval($_GET['lastEventId']) : 0;
    }
    
    /**
     * Send error and exit
     */
    private function sendErrorAndExit($message) {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        echo "event: error\n";
        echo "data: " . json_encode(['error' => $message]) . "\n\n";
        flush();
        exit();
    }
    
    /**
     * Ensure event_stream table exists
     */
    private function ensureEventStreamTable() {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS event_stream (
                id INT AUTO_INCREMENT PRIMARY KEY,
                restaurant_id INT NULL,
                event_type VARCHAR(50) NOT NULL,
                event_data TEXT NOT NULL,
                target_role VARCHAR(20) DEFAULT 'all',
                target_user_id VARCHAR(255) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_restaurant (restaurant_id),
                INDEX idx_created (created_at),
                INDEX idx_role (target_role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (PDOException $e) {
            error_log("SSE Table Creation Error: " . $e->getMessage());
        }
    }
    
    /**
     * Start SSE stream
     */
    public function stream() {
        // Set headers for SSE
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable nginx buffering
        
        // Disable output buffering
        if (ob_get_level()) ob_end_clean();
        
        // Send initial connection event
        $this->sendEvent('connected', [
            'message' => 'Real-time connection established',
            'role' => $this->userRole,
            'restaurant_id' => $this->restaurantId,
            'timestamp' => time()
        ]);
        
        // Keep connection alive and check for events
        $startTime = time();
        $maxDuration = 300; // 5 minutes max connection time
        
        while (true) {
            // Check if connection is still alive
            if (connection_aborted()) {
                break;
            }
            
            // Check max duration
            if ((time() - $startTime) > $maxDuration) {
                $this->sendEvent('timeout', ['message' => 'Connection timeout, please reconnect']);
                break;
            }
            
            // Check for new events based on user role
            $events = $this->checkForEvents();
            
            foreach ($events as $event) {
                $this->sendEvent($event['type'], $event['data'], $event['id']);
                $this->lastEventId = $event['id'];
            }
            
            // Send heartbeat every 15 seconds to keep connection alive
            if (time() % 15 === 0) {
                $this->sendEvent('heartbeat', ['timestamp' => time()]);
            }
            
            // Flush output
            flush();
            
            // Sleep for 1 second before checking again
            sleep(1);
        }
    }
    
    /**
     * Check for new events
     */
    private function checkForEvents() {
        $events = [];
        
        // For test mode, return empty (no events)
        if ($this->userRole === 'test') {
            return $events;
        }
        
        if (!$this->restaurantId) {
            return $events;
        }
        
        try {
            // Check for new events in event_stream table
            $query = "SELECT * FROM event_stream 
                      WHERE (restaurant_id = ? OR restaurant_id IS NULL)
                      AND id > ? 
                      AND (target_role = ? OR target_role = 'all' OR target_role = 'test' OR target_user_id = ?)
                      ORDER BY id ASC 
                      LIMIT 10";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([
                $this->restaurantId,
                $this->lastEventId,
                $this->userRole,
                $this->userId
            ]);
            
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Transform events to proper format
            $formattedEvents = [];
            foreach ($events as $event) {
                $formattedEvents[] = [
                    'id' => $event['id'],
                    'type' => $event['event_type'],
                    'data' => json_decode($event['event_data'], true)
                ];
            }
            
            return $formattedEvents;
            
        } catch (PDOException $e) {
            error_log("SSE Check Events Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Send SSE event to client
     */
    private function sendEvent($type, $data, $id = null) {
        if ($id !== null) {
            echo "id: $id\n";
        }
        echo "event: $type\n";
        echo "data: " . json_encode($data) . "\n\n";
        flush();
    }
}

// Initialize and start streaming
$controller = new EventsController();
$controller->stream();
