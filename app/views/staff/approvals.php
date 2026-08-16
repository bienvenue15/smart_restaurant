<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $title ?? 'Pending Approvals'; ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
<style>
.approvals-container {
margin-left: 260px;
padding: 30px;
background: #f5f7fa;
min-height: 100vh;
}
.approvals-container h1 {
color: #2d3748;
font-size: 2rem;
margin-bottom: 30px;
display: flex;
align-items: center;
gap: 12px;
}
.approval-card {
background: white;
border-radius: 12px;
padding: 24px;
margin-bottom: 20px;
box-shadow: 0 4px 12px rgba(0,0,0,0.15);
transition: transform 0.2s, box-shadow 0.2s;
border-left: 4px solid #667eea;
}
.approval-card:hover {
transform: translateY(-2px);
box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}
.approval-header {
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 20px;
padding-bottom: 16px;
border-bottom: 2px solid #f0f0f0;
}
.approval-title {
font-size: 20px;
font-weight: 700;
color: #2d3748;
display: flex;
align-items: center;
gap: 10px;
}
.approval-title::before {
content: "\f0e7";
font-family: "Font Awesome 6 Free";
font-weight: 900;
font-size: 24px;
}
.approval-time {
font-size: 13px;
color: #718096;
background: #f7fafc;
padding: 6px 12px;
border-radius: 20px;
display: inline-flex;
align-items: center;
gap: 6px;
}
.approval-time::before {
content: "\f017";
font-family: "Font Awesome 6 Free";
font-weight: 900;
}
.approval-details {
margin-bottom: 20px;
background: #f8f9fa;
padding: 16px;
border-radius: 8px;
}
.detail-row {
display: flex;
margin-bottom: 12px;
padding: 8px;
background: white;
border-radius: 6px;
}
.detail-row:last-child {
margin-bottom: 0;
}
.detail-label {
font-weight: 700;
width: 150px;
color: #4a5568;
font-size: 14px;
}
.detail-value {
color: #1a202c;
font-size: 14px;
flex: 1;
}
.approval-actions {
display: flex;
gap: 12px;
}
.btn {
padding: 12px 24px;
border: none;
border-radius: 8px;
cursor: pointer;
font-weight: 600;
font-size: 15px;
transition: all 0.3s;
display: flex;
align-items: center;
gap: 8px;
flex: 1;
justify-content: center;
}
.btn-approve {
background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
color: white;
box-shadow: 0 4px 6px rgba(72, 187, 120, 0.3);
}
.btn-approve:hover {
background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
transform: translateY(-2px);
box-shadow: 0 6px 12px rgba(72, 187, 120, 0.4);
}
.btn-approve::before {
content: "✓";
font-size: 18px;
font-weight: bold;
}
.btn-reject {
background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
color: white;
box-shadow: 0 4px 6px rgba(245, 101, 101, 0.3);
}
.btn-reject:hover {
background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
transform: translateY(-2px);
box-shadow: 0 6px 12px rgba(245, 101, 101, 0.4);
}
.btn-reject::before {
content: "✗";
font-size: 18px;
font-weight: bold;
}
.risk-badge {
display: inline-block;
padding: 6px 14px;
border-radius: 20px;
font-size: 12px;
font-weight: 700;
text-transform: uppercase;
letter-spacing: 0.5px;
}
.risk-low {
background: linear-gradient(135deg, #9ae6b4 0%, #68d391 100%);
color: #22543d;
}
.risk-medium {
background: linear-gradient(135deg, #fbd38d 0%, #f6ad55 100%);
color: #744210;
}
.risk-high {
background: linear-gradient(135deg, #fc8181 0%, #f56565 100%);
color: #742a2a;
}
.risk-critical {
background: linear-gradient(135deg, #c53030 0%, #9b2c2c 100%);
color: white;
}
.no-approvals {
text-align: center;
padding: 80px 20px;
background: white;
border-radius: 12px;
box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.no-approvals h2 {
color: #2d3748;
margin: 20px 0 10px;
}
.no-approvals p {
color: #718096;
font-size: 16px;
}
/* Mobile Responsive Design */
@media (max-width: 768px) {
.approvals-container {
margin-left: 0;
padding: 16px;
}
.approvals-container h1 {
font-size: 1.5rem;
}
.approval-card {
padding: 16px;
}
.approval-header {
flex-direction: column;
align-items: flex-start;
gap: 10px;
}
.approval-title {
font-size: 18px;
}
.detail-row {
flex-direction: column;
gap: 4px;
}
.detail-label {
width: 100%;
font-size: 13px;
}
.detail-value {
font-size: 13px;
}
.approval-actions {
flex-direction: column;
gap: 10px;
}
.btn {
width: 100%;
justify-content: center;
}
}
@media (max-width: 480px) {
.approvals-container {
padding: 12px;
}
.approvals-container h1 {
font-size: 1.25rem;
}
.approval-card {
padding: 12px;
}
.approval-title {
font-size: 16px;
}
}
</style>
</head>
<?php $isFragment = isset($_GET['fragment']) && $_GET['fragment'] === 'true'; ?>
<body class="staff-dashboard<?php echo $isFragment ? ' fragment-view' : ''; ?>">
<!-- Mobile Menu Toggle -->
<?php if (!$isFragment): ?>
<button class="mobile-menu-toggle" onclick="toggleMobileMenu()" aria-label="Toggle Menu">
<i class="fas fa-bars"></i>
</button>
<div class="mobile-overlay" onclick="closeMobileMenu()"></div>
<?php endif; ?>
<?php if (!$isFragment): ?>
<?php include 'app/views/staff/_sidebar.php'; ?>
<?php endif; ?>
<div class="approvals-container">
<h1><i class="fas fa-tasks"></i> Pending Approvals</h1>
<?php if (empty($approvals)): ?>
<div class="no-approvals">
<div style="font-size: 64px; margin-bottom: 20px;">✓</div>
<h2>No Pending Approvals</h2>
<p>All actions have been reviewed</p>
</div>
<?php else: ?>
<?php foreach ($approvals as $approval): ?>
<?php error_log("APPROVAL DEBUG: " . print_r($approval, true)); ?>
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
<button class="btn btn-approve" data-approval-id="<?php echo $approval['id']; ?>" data-action="approve">
✓ Approve
</button>
<button class="btn btn-reject" data-approval-id="<?php echo $approval['id']; ?>" data-action="reject">
✗ Reject
</button>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
<script>
const BASE_URL = '<?php echo BASE_URL; ?>';
const RESTAURANT_ID = <?php echo $restaurant_id ?? 0; ?>;
// Audio notification system - wrapped in try-catch for fragment mode
<?php if (!$isFragment): ?>
<?php include __DIR__ . '/../includes/audio_notification.js'; ?>
<?php else: ?>
// Fragment mode - skip audio notifications (handled by parent)
<?php endif; ?>
// Use event delegation to handle approval buttons
document.addEventListener('DOMContentLoaded', function() {
document.body.addEventListener('click', function(e) {
const btn = e.target.closest('[data-approval-id]');
if (!btn) return;
const auditId = btn.dataset.approvalId;
const action = btn.dataset.action;
if (!confirm(`Are you sure you want to ${action} this action?`)) {
return;
}
// Disable button during request
btn.disabled = true;
const originalHtml = btn.innerHTML;
btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
const requestBody = `audit_id=${auditId}&action=${action}`;
fetch('<?php echo BASE_URL; ?>/?req=staff&action=approve_action', {
method: 'POST',
headers: {
'Content-Type': 'application/x-www-form-urlencoded',
},
body: requestBody
})
.then(response => {
return response.json();
})
.then(data => {
if (data.status === 'OK') {
// Remove the card
const card = document.getElementById(`approval-${auditId}`);
if (card) {
card.style.transition = 'opacity 0.3s';
card.style.opacity = '0';
setTimeout(() => {
card.remove();
// Check if no more approvals
const container = document.querySelector('.approvals-container');
if (!container.querySelector('.approval-card')) {
location.reload();
}
}, 300);
}
} else {
alert(data.message || 'Failed to process approval');
btn.disabled = false;
btn.innerHTML = originalHtml;
}
})
.catch(error => {
alert('An error occurred. Please try again.');
btn.disabled = false;
btn.innerHTML = originalHtml;
});
});
});
// Update current time with seconds - accurate live clock
function updateTime() {
const timeElement = document.getElementById('currentTime');
if (!timeElement) return; // Element not in DOM
const now = new Date();
const timeStr = now.toLocaleTimeString('en-US', { 
hour: '2-digit', 
minute: '2-digit', 
second: '2-digit',
hour12: true 
});
timeElement.textContent = timeStr;
}
updateTime();
setInterval(updateTime, 1000); // Update every second
// Auto-refresh approvals every 10 seconds
setInterval(() => {
location.reload();
}, 10000);
// Mobile menu toggle functions
function toggleMobileMenu() {
const sidebar = document.querySelector('.sidebar');
const overlay = document.querySelector('.mobile-overlay');
if (sidebar && overlay) {
sidebar.classList.toggle('mobile-open');
overlay.classList.toggle('active');
document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
}
}
function closeMobileMenu() {
const sidebar = document.querySelector('.sidebar');
const overlay = document.querySelector('.mobile-overlay');
if (sidebar && overlay) {
sidebar.classList.remove('mobile-open');
overlay.classList.remove('active');
document.body.style.overflow = '';
}
}
</script>
</body>
</html>
