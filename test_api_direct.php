<?php
/**
 * DIRECT API TEST PAGE
 * This page directly calls the API methods without going through the routing
 */
require_once __DIR__ . '/init_session.php';

// Include necessary files
require_once __DIR__ . '/src/config.php';
require_once __DIR__ . '/src/autoload.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Direct API Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #1a1a1a; margin-bottom: 10px; font-size: 28px; }
        h2 { color: #333; margin-bottom: 16px; font-size: 20px; border-bottom: 2px solid #3b82f6; padding-bottom: 8px; }
        .code-block { background: #1e293b; color: #cbd5e1; padding: 16px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 13px; margin: 12px 0; white-space: pre-wrap; }
        .status { display: inline-block; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 12px; text-transform: uppercase; margin: 4px 0; }
        .status.success { background: #d1fae5; color: #065f46; }
        .status.error { background: #fee2e2; color: #991b1b; }
        .status.warning { background: #fef3c7; color: #92400e; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 8px 8px 8px 0; cursor: pointer; border: none; font-size: 14px; }
        .btn-primary { background: #3b82f6; color: white; }
        .test-section { background: #f8fafc; padding: 16px; border-radius: 8px; margin: 12px 0; border-left: 4px solid #3b82f6; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔬 Direct API Test</h1>
            <p style="color: #64748b; margin-top: 8px;">Testing API calls directly within PHP (bypassing routing)</p>
        </div>

        <!-- Session Check -->
        <div class="card">
            <h2>📊 Session Status</h2>
            <?php
            $sessionOk = isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'];
            $hasUuid = isset($_SESSION['staff_uuid']);
            $hasOldId = isset($_SESSION['staff_id']);
            $hasUser = isset($_SESSION['staff_user']);
            ?>
            
            <div class="test-section">
                <p><strong>Logged In:</strong> 
                    <?php if ($sessionOk): ?>
                        <span class="status success">✅ YES</span>
                    <?php else: ?>
                        <span class="status error">❌ NO</span>
                    <?php endif; ?>
                </p>
                
                <p><strong>staff_uuid (NEW):</strong> 
                    <?php if ($hasUuid): ?>
                        <span class="status success">✅ SET: <?php echo $_SESSION['staff_uuid']; ?></span>
                    <?php else: ?>
                        <span class="status error">❌ NOT SET</span>
                    <?php endif; ?>
                </p>
                
                <p><strong>staff_id (OLD):</strong> 
                    <?php if ($hasOldId): ?>
                        <span class="status warning">⚠️ SET: <?php echo $_SESSION['staff_id']; ?> (OLD FORMAT)</span>
                    <?php else: ?>
                        <span class="status success">✅ NOT SET (GOOD)</span>
                    <?php endif; ?>
                </p>
                
                <p><strong>staff_user:</strong> 
                    <?php if ($hasUser): ?>
                        <span class="status success">✅ SET</span>
                    <?php else: ?>
                        <span class="status error">❌ NOT SET</span>
                    <?php endif; ?>
                </p>
            </div>

            <?php if ($hasOldId && !$hasUuid && $hasUser): ?>
                <div class="test-section" style="border-left-color: #f59e0b; background: #fffbeb;">
                    <p style="color: #92400e;"><strong>⚠️ MIGRATION NEEDED:</strong> You have old session format.</p>
                    <p style="margin-top: 8px;"><a href="test_session_migration.php?migrate=now" class="btn btn-primary">Auto-Migrate Session</a></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Test Session Migration -->
        <div class="card">
            <h2>🔄 Test Session Migration Logic</h2>
            <?php
            echo "<div class='code-block'>";
            echo "// Testing migration logic:\n\n";
            
            if (!isset($_SESSION['staff_uuid']) && isset($_SESSION['staff_id']) && isset($_SESSION['staff_user'])) {
                echo "BEFORE MIGRATION:\n";
                echo "  staff_uuid: NOT SET\n";
                echo "  staff_id: " . $_SESSION['staff_id'] . "\n";
                echo "  staff_user['uuid']: " . ($_SESSION['staff_user']['uuid'] ?? 'NOT FOUND') . "\n\n";
                
                // Perform migration
                $_SESSION['staff_uuid'] = $_SESSION['staff_user']['uuid'] ?? $_SESSION['staff_id'];
                
                echo "AFTER MIGRATION:\n";
                echo "  staff_uuid: " . $_SESSION['staff_uuid'] . "\n";
                echo "  ✅ Migration completed successfully!\n";
            } elseif (isset($_SESSION['staff_uuid'])) {
                echo "✅ staff_uuid already set to: " . $_SESSION['staff_uuid'] . "\n";
                echo "No migration needed.\n";
            } else {
                echo "❌ Cannot test migration - missing required session data\n";
                echo "Please login first at: /restaurant/?req=staff\n";
            }
            echo "</div>";
            ?>
        </div>

        <!-- Test Database Query -->
        <div class="card">
            <h2>🗄️ Test Database Query with UUID</h2>
            <?php
            if (isset($_SESSION['staff_uuid'])) {
                try {
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT uuid, username, full_name, role FROM staff WHERE uuid = ?");
                    $stmt->execute([$_SESSION['staff_uuid']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    echo "<div class='test-section'>";
                    if ($user) {
                        echo "<p><span class='status success'>✅ SUCCESS</span> User found in database with UUID</p>";
                        echo "<div class='code-block'>" . json_encode($user, JSON_PRETTY_PRINT) . "</div>";
                    } else {
                        echo "<p><span class='status error'>❌ ERROR</span> No user found with UUID: " . $_SESSION['staff_uuid'] . "</p>";
                    }
                    echo "</div>";
                } catch (Exception $e) {
                    echo "<div class='test-section'>";
                    echo "<p><span class='status error'>❌ DATABASE ERROR</span></p>";
                    echo "<div class='code-block'>" . htmlspecialchars($e->getMessage()) . "</div>";
                    echo "</div>";
                }
            } else {
                echo "<div class='test-section'>";
                echo "<p><span class='status warning'>⚠️ SKIPPED</span> staff_uuid not set in session</p>";
                echo "</div>";
            }
            ?>
        </div>

        <!-- Test API Methods -->
        <div class="card">
            <h2>🧪 Test API Methods</h2>
            
            <div class="test-section">
                <h3 style="margin-bottom: 12px;">Test 1: staffGetNotifications (via AJAX)</h3>
                <button onclick="testNotifications()" class="btn btn-primary">Run Test</button>
                <div id="test1-result"></div>
            </div>

            <div class="test-section">
                <h3 style="margin-bottom: 12px;">Test 2: staffCheckDelayedOrders (via AJAX)</h3>
                <button onclick="testDelayedOrders()" class="btn btn-primary">Run Test</button>
                <div id="test2-result"></div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
            <h2>⚡ Quick Links</h2>
            <a href="/restaurant/?req=staff" class="btn btn-primary">Go to Staff Portal</a>
            <a href="test_session_migration.php" class="btn btn-primary">Session Migration Test</a>
            <a href="check_session.php" class="btn btn-primary">Session JSON</a>
            <a href="?" class="btn btn-primary">Reload This Page</a>
        </div>
    </div>

    <script>
        function testNotifications() {
            const resultDiv = document.getElementById('test1-result');
            resultDiv.innerHTML = '<p style="color: #64748b; margin-top: 12px;">⏳ Testing...</p>';
            
            fetch('/restaurant/?req=api&action=staff_get_notifications&include_read=0')
                .then(response => {
                    const status = response.status;
                    const statusText = response.statusText;
                    return response.text().then(text => {
                        try {
                            return { status, statusText, data: JSON.parse(text) };
                        } catch (e) {
                            return { status, statusText, data: text, parseError: true };
                        }
                    });
                })
                .then(result => {
                    let html = '<div style="margin-top: 12px;">';
                    html += '<p><strong>HTTP Status:</strong> ' + result.status + ' ' + result.statusText + '</p>';
                    if (result.status === 200) {
                        html += '<p style="color: #059669; font-weight: 700;">✅ SUCCESS</p>';
                    } else {
                        html += '<p style="color: #dc2626; font-weight: 700;">❌ FAILED (Expected 200, got ' + result.status + ')</p>';
                    }
                    html += '<div class="code-block">' + JSON.stringify(result.data, null, 2) + '</div>';
                    html += '</div>';
                    resultDiv.innerHTML = html;
                })
                .catch(error => {
                    resultDiv.innerHTML = '<p style="color: #dc2626; margin-top: 12px;"><strong>❌ Error:</strong> ' + error.message + '</p>';
                });
        }

        function testDelayedOrders() {
            const resultDiv = document.getElementById('test2-result');
            resultDiv.innerHTML = '<p style="color: #64748b; margin-top: 12px;">⏳ Testing...</p>';
            
            fetch('/restaurant/?req=api&action=staff_check_delayed_orders')
                .then(response => {
                    const status = response.status;
                    const statusText = response.statusText;
                    return response.text().then(text => {
                        try {
                            return { status, statusText, data: JSON.parse(text) };
                        } catch (e) {
                            return { status, statusText, data: text, parseError: true };
                        }
                    });
                })
                .then(result => {
                    let html = '<div style="margin-top: 12px;">';
                    html += '<p><strong>HTTP Status:</strong> ' + result.status + ' ' + result.statusText + '</p>';
                    if (result.status === 200) {
                        html += '<p style="color: #059669; font-weight: 700;">✅ SUCCESS</p>';
                    } else {
                        html += '<p style="color: #dc2626; font-weight: 700;">❌ FAILED (Expected 200, got ' + result.status + ')</p>';
                    }
                    html += '<div class="code-block">' + JSON.stringify(result.data, null, 2) + '</div>';
                    html += '</div>';
                    resultDiv.innerHTML = html;
                })
                .catch(error => {
                    resultDiv.innerHTML = '<p style="color: #dc2626; margin-top: 12px;"><strong>❌ Error:</strong> ' + error.message + '</p>';
                });
        }
    </script>
</body>
</html>
