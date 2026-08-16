<!DOCTYPE html>
<html>
<head>
    <title>QR Code Generator - Restaurant System</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #17a2b8; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th { background: #4CAF50; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f5f5f5; }
        .btn { background: #4CAF50; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; }
        .btn:hover { background: #45a049; }
        .btn-secondary { background: #007bff; }
        .btn-secondary:hover { background: #0056b3; }
        .qr-link { color: #007bff; text-decoration: none; font-size: 12px; word-break: break-all; }
        .qr-link:hover { text-decoration: underline; }
        pre { background: #f4f4f4; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Restaurant QR Code Generator</h1>
        
        <?php
        require_once 'src/config.php';
        require_once 'src/model.php';
        require_once 'app/core/QRCodeGenerator.php';
        
        $action = $_GET['action'] ?? 'list';
        
        try {
            $db = new PDO(
                DB_TYPE.":host=".DB_HOST.";dbname=".DB_NAME,
                DB_USER,
                DB_PWD,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            if ($action === 'generate') {
                echo '<div class="info">⏳ Generating QR codes for all tables...</div>';
                
                $qrGenerator = new QRCodeGenerator();
                $result = $qrGenerator->generateAllTables($db);
                
                if ($result['status'] === 'OK') {
                    echo '<div class="success">';
                    echo '<h3>✅ QR Codes Generated Successfully!</h3>';
                    foreach ($result['results'] as $tableResult) {
                        if ($tableResult['status'] === 'OK') {
                            $slug = $tableResult['restaurant_slug'] ?: 'global';
                            echo "<p><strong>{$slug}</strong> / Table {$tableResult['table']}</p>";
                            echo "<p style='font-size: 12px; color: #666;'>File: {$tableResult['filename']}</p>";
                            echo "<p class='qr-link'>URL: <a href='{$tableResult['url']}' target='_blank'>{$tableResult['url']}</a></p>";
                            echo "<hr style='margin: 10px 0;'>";
                        }
                    }
                    echo '<p style="margin-top: 20px;">QR code images saved to: <code>images/qrcodes/&lt;restaurant&gt;/</code></p>';
                    echo '</div>';
                } else {
                    echo '<div class="error">❌ Error: ' . htmlspecialchars($result['message']) . '</div>';
                }
                
                echo '<p><a href="?action=list" class="btn btn-secondary">View All Tables</a></p>';
            }
            
            // Always show current tables
            echo '<h2>📋 Current Tables in Database</h2>';
            
            $stmt = $db->query("
                SELECT rt.id, rt.restaurant_id, rt.table_number, rt.qr_code, rt.seats, rt.status, r.name as restaurant_name, r.slug
                FROM restaurant_tables rt
                LEFT JOIN restaurants r ON rt.restaurant_id = r.id
                ORDER BY r.name, rt.table_number
            ");
            $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($tables) === 0) {
                echo '<div class="error">⚠️ No tables found in database! Please create tables first.</div>';
            } else {
                echo '<table>';
                echo '<tr>
                        <th>ID</th>
                        <th>Restaurant</th>
                        <th>Table #</th>
                        <th>QR Code</th>
                        <th>Seats</th>
                        <th>Status</th>
                        <th>Test Link</th>
                      </tr>';
                
                foreach ($tables as $table) {
                    $testUrl = BASE_URL . '/?req=menu&qr=' . urlencode($table['qr_code']);
                    echo '<tr>';
                    echo '<td>' . $table['id'] . '</td>';
                    echo '<td>' . htmlspecialchars($table['restaurant_name'] ?? 'N/A') . '<br><small style="color: #666;">' . htmlspecialchars($table['slug'] ?? '') . '</small></td>';
                    echo '<td><strong>' . htmlspecialchars($table['table_number']) . '</strong></td>';
                    echo '<td><code style="font-size: 11px;">' . htmlspecialchars($table['qr_code']) . '</code></td>';
                    echo '<td>' . $table['seats'] . '</td>';
                    echo '<td>' . $table['status'] . '</td>';
                    echo '<td><a href="' . $testUrl . '" target="_blank" class="btn" style="padding: 5px 10px; font-size: 12px;">Test Menu</a></td>';
                    echo '</tr>';
                }
                
                echo '</table>';
                
                echo '<p style="margin-top: 30px;">';
                if ($action !== 'generate') {
                    echo '<a href="?action=generate" class="btn">🔄 Generate QR Codes for All Tables</a> ';
                }
                echo '<a href="check_qr.php?qr=' . urlencode($tables[0]['qr_code'] ?? '') . '" class="btn btn-secondary">🔍 Test QR Validation</a>';
                echo '</p>';
            }
            
        } catch (PDOException $e) {
            echo '<div class="error">❌ Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        ?>
        
        <h2>📖 Instructions</h2>
        <div class="info">
            <ol>
                <li><strong>Generate QR Codes:</strong> Click the "Generate QR Codes" button above to create QR code images for all tables</li>
                <li><strong>Test Menu Access:</strong> Click "Test Menu" links in the table to verify QR codes work</li>
                <li><strong>Print QR Codes:</strong> QR code images are saved in <code>images/qrcodes/&lt;restaurant-slug&gt;/</code></li>
                <li><strong>Place on Tables:</strong> Print and place QR codes on physical restaurant tables</li>
            </ol>
        </div>
        
        <h2>🔗 Your QR Code URL Format</h2>
        <pre>http://localhost/restaurant/?req=menu&qr=QR-REST1-TBL05-e3b0c44298fc</pre>
        <p>Where:</p>
        <ul>
            <li><code>QR-REST1-TBL05-e3b0c44298fc</code> = The unique QR code value from database</li>
            <li>This must match <strong>exactly</strong> what's in the <code>qr_code</code> column</li>
        </ul>
    </div>
</body>
</html>
