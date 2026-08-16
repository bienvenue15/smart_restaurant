<?php
/**
 * Database Import Script for Production
 * SECURITY: Delete this file immediately after use!
 */

// Prevent browser caching
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

require_once 'src/config.php';

// Security: Only allow on production and only once
$isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
if ($isLocal) {
    die("❌ This script should only run on PRODUCTION server!");
}

set_time_limit(0);
ini_set('memory_limit', '512M');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Import - SmartMenu</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #0f0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: #000;
            border: 2px solid #0f0;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,255,0,0.3);
        }
        h1 {
            color: #0f0;
            text-align: center;
            text-shadow: 0 0 10px #0f0;
        }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        .info { color: #0ff; }
        pre {
            background: #111;
            padding: 15px;
            border-left: 3px solid #0f0;
            overflow-x: auto;
        }
        .btn {
            background: #0f0;
            color: #000;
            padding: 15px 30px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin: 10px 5px;
        }
        .btn:hover {
            background: #0c0;
        }
        .btn-danger {
            background: #f00;
            color: #fff;
        }
        .btn-danger:hover {
            background: #c00;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>DATABASE IMPORT FOR PRODUCTION</h1>
    
    <?php
    $sqlFile = __DIR__ . '/db_restaurant (2).sql';
    
    if (!file_exists($sqlFile)) {
        echo '<p class="error">❌ ERROR: SQL file not found!</p>';
        echo '<p class="info">Expected: db_restaurant (2).sql</p>';
        exit;
    }
    
    echo '<p class="info">📁 SQL File: ' . basename($sqlFile) . '</p>';
    echo '<p class="info">📦 File Size: ' . number_format(filesize($sqlFile) / 1024, 2) . ' KB</p>';
    echo '<p class="warning">⚠️ Database: ' . DB_NAME . '</p>';
    echo '<p class="warning">👤 User: ' . DB_USER . '</p><br>';
    
    if (isset($_POST['confirm_import'])) {
        echo '<h2 class="success">🚀 Starting Import...</h2>';
        echo '<pre>';
        
        try {
            // Connect to database
            $db = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PWD,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
            
            echo "✅ Connected to database: " . DB_NAME . "\n\n";
            
            // Read SQL file
            $sql = file_get_contents($sqlFile);
            
            // Remove comments and split by semicolon
            $sql = preg_replace('/--.*\n/', '', $sql);
            $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
            
            // Split into individual statements
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) { return !empty($stmt); }
            );
            
            echo "📋 Found " . count($statements) . " SQL statements\n\n";
            
            $success = 0;
            $failed = 0;
            
            foreach ($statements as $index => $statement) {
                if (empty(trim($statement))) continue;
                
                try {
                    $db->exec($statement);
                    $success++;
                    
                    // Show progress every 50 statements
                    if ($success % 50 === 0) {
                        echo "✅ Processed {$success} statements...\n";
                    }
                } catch (PDOException $e) {
                    // Ignore "table already exists" errors
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        $failed++;
                        echo "⚠️ Statement " . ($index + 1) . " failed: " . $e->getMessage() . "\n";
                    } else {
                        $success++;
                    }
                }
            }
            
            echo "\n" . str_repeat("=", 60) . "\n";
            echo "✅ Import Complete!\n";
            echo "✅ Successful: {$success}\n";
            if ($failed > 0) {
                echo "⚠️ Failed: {$failed}\n";
            }
            echo str_repeat("=", 60) . "\n\n";
            
            // Verify tables
            $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            echo "📊 Tables in database (" . count($tables) . "):\n";
            foreach ($tables as $table) {
                echo "  • {$table}\n";
            }
            
            echo "\n✅✅✅ DATABASE IMPORT SUCCESSFUL ✅✅✅\n";
            echo "\n🔒 IMPORTANT: Delete this file NOW for security!\n";
            echo "Command: rm import_database.php\n";
            
        } catch (PDOException $e) {
            echo "❌ ERROR: " . $e->getMessage() . "\n";
        }
        
        echo '</pre>';
        
        echo '<form method="post" style="margin-top: 30px;">';
        echo '<button type="submit" name="delete_script" class="btn btn-danger">🗑️ DELETE THIS SCRIPT NOW</button>';
        echo '</form>';
        
    } elseif (isset($_POST['delete_script'])) {
        if (unlink(__FILE__)) {
            echo '<p class="success">✅ Script deleted successfully!</p>';
            echo '<p class="info">You can now close this page.</p>';
        } else {
            echo '<p class="error">❌ Failed to delete script. Please delete manually:</p>';
            echo '<pre>rm ' . basename(__FILE__) . '</pre>';
        }
        
    } else {
        ?>
        <div class="warning">
            <h2>⚠️ WARNING</h2>
            <p>This will import the database schema and data into:</p>
            <p><strong>Database: <?php echo DB_NAME; ?></strong></p>
            <p>Existing tables may be dropped and recreated.</p>
            <p><strong>Make sure you have a backup!</strong></p>
        </div>
        
        <form method="post" style="text-align: center; margin-top: 30px;">
            <button type="submit" name="confirm_import" class="btn">
                🚀 START IMPORT
            </button>
        </form>
        <?php
    }
    ?>
    
</div>
</body>
</html>
