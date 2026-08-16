<?php
/**
 * SESSION MIGRATION TEST PAGE
 * This page tests the automatic migration from staff_id to staff_uuid
 */
require_once __DIR__ . '/init_session.php';

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Migration Test</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #1a1a1a; margin-bottom: 10px; font-size: 28px; }
        h2 { color: #333; margin-bottom: 16px; font-size: 20px; border-bottom: 2px solid #3b82f6; padding-bottom: 8px; }
        .status { display: inline-block; padding: 6px 14px; border-radius: 20px; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        .status.success { background: #d1fae5; color: #065f46; }
        .status.error { background: #fee2e2; color: #991b1b; }
        .status.warning { background: #fef3c7; color: #92400e; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin: 16px 0; }
        .info-item { background: #f8fafc; padding: 12px 16px; border-radius: 8px; border-left: 4px solid #3b82f6; }
        .info-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 4px; }
        .info-value { font-size: 16px; color: #0f172a; font-weight: 700; font-family: monospace; }
        .code-block { background: #1e293b; color: #cbd5e1; padding: 16px; border-radius: 8px; overflow-x: auto; font-family: 'Courier New', monospace; font-size: 13px; margin: 12px 0; }
        .btn { display: inline-block; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; margin: 8px 8px 8px 0; cursor: pointer; border: none; font-size: 14px; transition: all 0.2s; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        .btn-secondary { background: #6b7280; color: white; }
        .btn-secondary:hover { background: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f8fafc; color: #475569; font-weight: 700; font-size: 12px; text-transform: uppercase; }
        td { color: #334155; font-size: 14px; }
        .test-result { padding: 12px 16px; border-radius: 8px; margin: 8px 0; border-left: 4px solid; }
        .test-result.pass { background: #d1fae5; border-color: #10b981; color: #065f46; }
        .test-result.fail { background: #fee2e2; border-color: #ef4444; color: #991b1b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔧 Session Migration Test</h1>
            <p style="color: #64748b; margin-top: 8px;">Testing automatic migration from staff_id to staff_uuid</p>
        </div>

        <!-- Current Session Status -->
        <div class="card">
            <h2>📊 Current Session Status</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Session ID</div>
                    <div class="info-value"><?php echo session_id(); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Logged In</div>
                    <div class="info-value">
                        <?php echo isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in'] ? '✅ YES' : '❌ NO'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Has staff_uuid</div>
                    <div class="info-value">
                        <?php echo isset($_SESSION['staff_uuid']) ? '✅ YES' : '❌ NO'; ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Has staff_id (old)</div>
                    <div class="info-value">
                        <?php echo isset($_SESSION['staff_id']) ? '⚠️ YES (OLD)' : '✅ NO'; ?>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['staff_uuid'])): ?>
                <div class="test-result pass">
                    <strong>✅ PASS:</strong> staff_uuid is set to: <code><?php echo $_SESSION['staff_uuid']; ?></code>
                </div>
            <?php else: ?>
                <div class="test-result fail">
                    <strong>❌ FAIL:</strong> staff_uuid is NOT set
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['staff_id']) && !isset($_SESSION['staff_uuid'])): ?>
                <div class="test-result fail">
                    <strong>⚠️ WARNING:</strong> Old session detected! You have staff_id but no staff_uuid. Session migration needed.
                </div>
            <?php endif; ?>
        </div>

        <!-- All Session Variables -->
        <div class="card">
            <h2>🔍 All Session Variables</h2>
            <table>
                <thead>
                    <tr>
                        <th style="width: 30%;">Key</th>
                        <th>Value</th>
                        <th style="width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($_SESSION)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #94a3b8;">No session variables found</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($_SESSION as $key => $value): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($key); ?></strong></td>
                                <td>
                                    <?php 
                                    if (is_array($value)) {
                                        echo '<code>' . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . '</code>';
                                    } else {
                                        echo htmlspecialchars($value);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($key === 'staff_id') {
                                        echo '<span class="status warning">OLD</span>';
                                    } elseif ($key === 'staff_uuid') {
                                        echo '<span class="status success">NEW</span>';
                                    } else {
                                        echo '<span class="status">OK</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Manual Migration Tool -->
        <div class="card">
            <h2>🔄 Manual Session Migration</h2>
            <p style="color: #64748b; margin-bottom: 16px;">If you have an old session with staff_id, you can manually migrate it here.</p>
            
            <?php
            if (isset($_GET['migrate']) && $_GET['migrate'] === 'now') {
                if (isset($_SESSION['staff_id']) && isset($_SESSION['staff_user'])) {
                    $_SESSION['staff_uuid'] = $_SESSION['staff_user']['uuid'] ?? $_SESSION['staff_id'];
                    echo '<div class="test-result pass"><strong>✅ Migration Completed!</strong> staff_uuid set to: ' . $_SESSION['staff_uuid'] . '</div>';
                } else {
                    echo '<div class="test-result fail"><strong>❌ Cannot Migrate:</strong> Missing staff_id or staff_user in session</div>';
                }
            }
            ?>
            
            <a href="?migrate=now" class="btn btn-primary">🔄 Migrate Session Now</a>
            <a href="?" class="btn btn-secondary">🔃 Refresh Page</a>
        </div>

        <!-- API Test -->
        <div class="card">
            <h2>🧪 API Endpoint Tests</h2>
            <p style="color: #64748b; margin-bottom: 16px;">Click buttons to test API endpoints with your current session.</p>
            
            <div id="api-results"></div>
            
            <button onclick="testNotifications()" class="btn btn-success">Test Get Notifications</button>
            <button onclick="testDelayedOrders()" class="btn btn-success">Test Check Delayed Orders</button>
            <button onclick="clearResults()" class="btn btn-secondary">Clear Results</button>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h2>⚡ Quick Actions</h2>
            <a href="/restaurant/?req=staff" class="btn btn-primary">Go to Staff Portal</a>
            <a href="/restaurant/check_session.php" class="btn btn-secondary">Check Session (JSON)</a>
            <a href="?clear=yes" class="btn btn-danger">Destroy Session</a>
        </div>

        <?php
        if (isset($_GET['clear']) && $_GET['clear'] === 'yes') {
            session_destroy();
            echo '<div class="card"><div class="test-result pass"><strong>✅ Session Destroyed!</strong> <a href="?" class="btn btn-primary" style="margin-left: 16px;">Reload Page</a></div></div>';
        }
        ?>
    </div>

    <script>
        function testNotifications() {
            const resultsDiv = document.getElementById('api-results');
            resultsDiv.innerHTML = '<p style="color: #64748b;">⏳ Testing staff_get_notifications...</p>';
            
            fetch('/restaurant/?req=api&action=staff_get_notifications&include_read=0')
                .then(response => {
                    const status = response.status;
                    return response.json().then(data => ({status, data}));
                })
                .then(({status, data}) => {
                    let resultClass = status === 200 ? 'pass' : 'fail';
                    let resultIcon = status === 200 ? '✅' : '❌';
                    resultsDiv.innerHTML = `
                        <div class="test-result ${resultClass}">
                            <strong>${resultIcon} staff_get_notifications - HTTP ${status}</strong>
                            <div class="code-block">${JSON.stringify(data, null, 2)}</div>
                        </div>
                    `;
                })
                .catch(error => {
                    resultsDiv.innerHTML = `<div class="test-result fail"><strong>❌ Error:</strong> ${error.message}</div>`;
                });
        }

        function testDelayedOrders() {
            const resultsDiv = document.getElementById('api-results');
            resultsDiv.innerHTML = '<p style="color: #64748b;">⏳ Testing staff_check_delayed_orders...</p>';
            
            fetch('/restaurant/?req=api&action=staff_check_delayed_orders')
                .then(response => {
                    const status = response.status;
                    return response.json().then(data => ({status, data}));
                })
                .then(({status, data}) => {
                    let resultClass = status === 200 ? 'pass' : 'fail';
                    let resultIcon = status === 200 ? '✅' : '❌';
                    resultsDiv.innerHTML = `
                        <div class="test-result ${resultClass}">
                            <strong>${resultIcon} staff_check_delayed_orders - HTTP ${status}</strong>
                            <div class="code-block">${JSON.stringify(data, null, 2)}</div>
                        </div>
                    `;
                })
                .catch(error => {
                    resultsDiv.innerHTML = `<div class="test-result fail"><strong>❌ Error:</strong> ${error.message}</div>`;
                });
        }

        function clearResults() {
            document.getElementById('api-results').innerHTML = '';
        }
    </script>
</body>
</html>
