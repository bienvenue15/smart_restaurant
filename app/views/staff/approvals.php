<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Pending Approvals'; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
    <style>
        .approvals-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .approval-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .approval-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .approval-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }
        
        .approval-time {
            font-size: 14px;
            color: #666;
        }
        
        .approval-details {
            margin-bottom: 15px;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 10px;
        }
        
        .detail-label {
            font-weight: 600;
            width: 150px;
            color: #555;
        }
        
        .detail-value {
            color: #333;
        }
        
        .approval-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c82333;
        }
        
        .risk-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .risk-low {
            background: #d4edda;
            color: #155724;
        }
        
        .risk-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .risk-high {
            background: #f8d7da;
            color: #721c24;
        }
        
        .risk-critical {
            background: #721c24;
            color: white;
        }
        
        .no-approvals {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .no-approvals i {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="approvals-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <a href="<?php echo BASE_URL; ?>/?req=staff&action=dashboard" class="back-link">← Back to Dashboard</a>
            <div style="color: #666; font-weight: 600;">
                <i class="fas fa-clock"></i> <span id="currentTime"></span>
            </div>
        </div>
        
        <h1>Pending Approvals</h1>
        
        <?php if (empty($approvals)): ?>
            <div class="no-approvals">
                <div style="font-size: 64px; margin-bottom: 20px;">✓</div>
                <h2>No Pending Approvals</h2>
                <p>All actions have been reviewed</p>
            </div>
        <?php else: ?>
            <?php foreach ($approvals as $approval): ?>
                <div class="approval-card" id="approval-<?php echo $approval['id']; ?>">
                    <div class="approval-header">
                        <div>
                            <div class="approval-title"><?php echo ucwords(str_replace('_', ' ', $approval['action_type'])); ?></div>
                            <div class="approval-time"><?php echo date('M d, Y H:i', strtotime($approval['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="approval-details">
                        <div class="detail-row">
                            <div class="detail-label">Requested By:</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($approval['requested_by_name']); ?>
                                <span style="color: #999;">(<?php echo ucfirst($approval['requester_role']); ?>)</span>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Table/Record:</div>
                            <div class="detail-value">
                                <?php echo htmlspecialchars($approval['table_name']); ?> 
                                #<?php echo $approval['record_id']; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($approval['old_value'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Old Value:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($approval['old_value']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($approval['new_value'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">New Value:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($approval['new_value']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($approval['reason'])): ?>
                            <div class="detail-row">
                                <div class="detail-label">Reason:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($approval['reason']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <div class="detail-label">IP Address:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($approval['ip_address']); ?></div>
                        </div>
                    </div>
                    
                    <div class="approval-actions">
                        <button class="btn btn-approve" onclick="handleApproval(<?php echo $approval['id']; ?>, 'approve')">
                            ✓ Approve
                        </button>
                        <button class="btn btn-reject" onclick="handleApproval(<?php echo $approval['id']; ?>, 'reject')">
                            ✗ Reject
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
        function handleApproval(auditId, action) {
            if (!confirm(`Are you sure you want to ${action} this action?`)) {
                return;
            }
            
            fetch('<?php echo BASE_URL; ?>/?req=staff&action=approve_action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `audit_id=${auditId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'OK') {
                    // Remove the card
                    const card = document.getElementById(`approval-${auditId}`);
                    card.style.opacity = '0';
                    setTimeout(() => {
                        card.remove();
                        
                        // Check if no more approvals
                        const container = document.querySelector('.approvals-container');
                        if (!container.querySelector('.approval-card')) {
                            location.reload();
                        }
                    }, 300);
                } else {
                    alert(data.message || 'Failed to process approval');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
            });
        }
        
        // Update current time with seconds - accurate live clock
        function updateTime() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit', 
                second: '2-digit',
                hour12: true 
            });
            document.getElementById('currentTime').textContent = timeStr;
        }
        updateTime();
        setInterval(updateTime, 1000); // Update every second
    </script>
</body>
</html>
