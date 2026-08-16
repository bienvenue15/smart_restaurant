<?php
/**
 * SESSION FIX UTILITY
 * This page will automatically fix your session by converting staff_id to staff_uuid
 */
require_once __DIR__ . '/init_session.php';

$fixed = false;
$message = '';
$error = '';

// Auto-fix if requested
if (!isset($_SESSION['staff_uuid']) && isset($_SESSION['staff_id']) && isset($_SESSION['staff_user'])) {
    $_SESSION['staff_uuid'] = $_SESSION['staff_user']['uuid'] ?? $_SESSION['staff_id'];
    $fixed = true;
    $message = 'Session automatically migrated from staff_id to staff_uuid!';
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Fix Utility</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { max-width: 600px; width: 100%; }
        .card { 
            background: white; 
            border-radius: 16px; 
            padding: 32px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #1a1a1a; 
            margin-bottom: 16px; 
            font-size: 32px;
            text-align: center;
        }
        .icon { font-size: 64px; text-align: center; margin-bottom: 16px; }
        .message { 
            background: #d1fae5; 
            border: 2px solid #10b981;
            color: #065f46; 
            padding: 16px; 
            border-radius: 8px; 
            margin: 20px 0;
            text-align: center;
            font-weight: 600;
        }
        .error { 
            background: #fee2e2; 
            border: 2px solid #ef4444;
            color: #991b1b; 
            padding: 16px; 
            border-radius: 8px; 
            margin: 20px 0;
            text-align: center;
            font-weight: 600;
        }
        .info { 
            background: #f0f9ff; 
            border: 2px solid #3b82f6;
            color: #1e40af; 
            padding: 16px; 
            border-radius: 8px; 
            margin: 20px 0;
        }
        .btn { 
            display: block;
            width: 100%;
            padding: 14px;
            border-radius: 8px; 
            text-decoration: none; 
            font-weight: 700;
            margin: 12px 0;
            cursor: pointer; 
            border: none; 
            font-size: 16px;
            text-align: center;
            transition: all 0.2s;
        }
        .btn-primary { 
            background: #3b82f6; 
            color: white; 
        }
        .btn-primary:hover { 
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        .btn-success { 
            background: #10b981; 
            color: white; 
        }
        .btn-success:hover { 
            background: #059669;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        .status-grid { 
            display: grid; 
            gap: 12px; 
            margin: 20px 0;
            background: #f8fafc;
            padding: 16px;
            border-radius: 8px;
        }
        .status-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        .status-item:last-child { border-bottom: none; }
        .status-label { 
            font-weight: 600; 
            color: #475569;
            font-size: 14px;
        }
        .status-value { 
            font-family: monospace; 
            font-weight: 700;
            font-size: 14px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-error { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <?php if ($fixed): ?>
                <div class="icon">✅</div>
                <h1>Session Fixed!</h1>
                <div class="message">
                    <?php echo $message; ?>
                </div>
            <?php elseif (isset($_SESSION['staff_uuid'])): ?>
                <div class="icon">✅</div>
                <h1>Session OK</h1>
                <div class="info">
                    Your session is already using the new UUID format. No fix needed!
                </div>
            <?php else: ?>
                <div class="icon">⚠️</div>
                <h1>Session Check</h1>
                <div class="error">
                    Cannot fix session - you need to login first!
                </div>
            <?php endif; ?>

            <div class="status-grid">
                <div class="status-item">
                    <span class="status-label">Session ID</span>
                    <span class="status-value"><?php echo substr(session_id(), 0, 16) . '...'; ?></span>
                </div>
                <div class="status-item">
                    <span class="status-label">Logged In</span>
                    <span class="status-value">
                        <?php if (isset($_SESSION['staff_logged_in']) && $_SESSION['staff_logged_in']): ?>
                            <span class="badge badge-success">✅ Yes</span>
                        <?php else: ?>
                            <span class="badge badge-error">❌ No</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">staff_uuid (NEW)</span>
                    <span class="status-value">
                        <?php if (isset($_SESSION['staff_uuid'])): ?>
                            <span class="badge badge-success">✅ Set</span>
                        <?php else: ?>
                            <span class="badge badge-error">❌ Missing</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="status-item">
                    <span class="status-label">staff_id (OLD)</span>
                    <span class="status-value">
                        <?php if (isset($_SESSION['staff_id'])): ?>
                            <span class="badge badge-warning">⚠️ Present</span>
                        <?php else: ?>
                            <span class="badge badge-success">✅ None</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (isset($_SESSION['staff_uuid'])): ?>
                <div class="status-item">
                    <span class="status-label">UUID Value</span>
                    <span class="status-value" style="color: #059669;">
                        <?php echo $_SESSION['staff_uuid']; ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <a href="/restaurant/?req=staff" class="btn btn-primary">
                📊 Go to Staff Dashboard
            </a>
            
            <a href="test_session_migration.php" class="btn btn-success">
                🔧 Full Diagnostic Page
            </a>

            <?php if (!isset($_SESSION['staff_logged_in']) || !$_SESSION['staff_logged_in']): ?>
                <div class="info" style="margin-top: 20px;">
                    <strong>💡 Tip:</strong> You need to login first at the staff portal to create a session.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($fixed): ?>
    <script>
        // Auto-redirect to dashboard after 3 seconds
        setTimeout(() => {
            window.location.href = '/restaurant/?req=staff';
        }, 3000);
    </script>
    <?php endif; ?>
</body>
</html>
