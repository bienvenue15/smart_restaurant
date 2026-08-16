<?php
// Sidebar include for staff dashboard and admin views
// Requires: $user, $isOnShift, $canHandleCash, $canApprove, $stats (optional)
// Set $user from session if not already set
if (!isset($user) && isset($_SESSION['staff_user'])) {
$user = $_SESSION['staff_user'];
}
if (!isset($stats)) {
$stats = [];
}
$hasAdminPanel = \Permission::check('manage_menu')
|| \Permission::check('manage_tables')
|| \Permission::check('manage_orders')
|| \Permission::check('manage_staff')
|| \Permission::check('manage_settings')
|| \Permission::check('view_reports');
?>
<!-- Sidebar Navigation -->
<aside class="sidebar">
<div class="sidebar-header">
<div class="logo">
<img src="<?php echo APP_LOGO_URL; ?>" alt="Smart Restaurant logo">
<span>Smart Restaurant</span>
</div>
<?php 
// Display friendly role names with emojis
$roleMap = [
'admin' => '👑 Restaurant Boss',
'manager' => '📈 Manager',
'waiter' => '🍽️ Server',
'kitchen' => '👨‍🍳 Chef',
'cashier' => '💰 Cashier'
];
$displayRole = $roleMap[$user['role'] ?? ''] ?? ucfirst($user['role'] ?? 'Staff');
?>
<div class="role-badge role-<?php echo htmlspecialchars($user['role']); ?>" style="font-size: 15px; padding: 8px 14px;">
<?php echo htmlspecialchars($displayRole); ?>
</div>
</div>
<nav class="sidebar-nav" style="padding: 10px 0;">
<!-- Dashboard - All roles -->
<a href="<?php echo BASE_URL; ?>/?req=staff&action=dashboard" class="nav-item <?php echo ($page ?? '') === 'staff_dashboard' ? 'active' : ''; ?>" style="min-height: 50px; padding: 14px 20px; font-size: 15px;">
<i class="fas fa-home" style="font-size: 20px; width: 30px;"></i>
<span>🏠 Home</span>
</a>

<!-- Orders -->
<?php if (Permission::check('view_orders') || $user['role'] === 'kitchen'): ?>
<a href="<?php echo BASE_URL; ?>/?req=staff&action=orders_manage" class="nav-item" style="min-height: 50px; padding: 14px 20px; font-size: 15px;">
<i class="fas fa-shopping-cart" style="font-size: 20px; width: 30px;"></i>
<span><?php echo $user['role'] === 'kitchen' ? '🍛 Cook Orders' : '📝 View Orders'; ?></span>
<?php if (!empty($stats['pending_orders'])): ?>
<span class="badge" style="min-width: 24px; height: 24px; font-size: 13px;"><?php echo $stats['pending_orders']; ?></span>
<?php endif; ?>
</a>
<?php endif; ?>

<!-- Waiter Calls -->
<?php if (Permission::check('view_tables')): ?>
<a href="<?php echo BASE_URL; ?>/?req=staff&action=waiter_calls" class="nav-item" style="min-height: 50px; padding: 14px 20px; font-size: 15px;">
<i class="fas fa-bell" style="font-size: 20px; width: 30px;"></i>
<span>🔔 Customer Calls</span>
<?php if (!empty($stats['pending_calls'])): ?>
<span class="badge" style="min-width: 24px; height: 24px; font-size: 13px; animation: pulse 2s infinite;"><?php echo $stats['pending_calls']; ?></span>
<?php endif; ?>
</a>
<?php endif; ?>

<!-- Cash Register -->
<?php if ($canHandleCash ?? Permission::check('handle_cash')): ?>
    <a href="<?php echo BASE_URL; ?>/staff/cash" class="nav-item" style="min-height: 50px; padding: 14px 20px; font-size: 15px; background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%); border-left: 3px solid #10b981;">
<i class="fas fa-cash-register" style="font-size: 20px; width: 30px; color: #10b981;"></i>
<span>💵 Money & Payments</span>
</a>
<?php endif; ?>

<!-- Waiter Liabilities -->
<?php if (in_array($user['role'], ['admin', 'manager', 'waiter', 'cashier'])): ?>
    <a href="<?php echo BASE_URL; ?>/staff/liabilities" class="nav-item <?php echo ($page ?? '') === 'liabilities' ? 'active' : ''; ?>" id="liabilitiesMenuItem" style="min-height: 50px; padding: 14px 20px; font-size: 15px;">
<i class="fas fa-receipt" style="font-size: 20px; width: 30px;"></i>
<span>🧾 Unpaid Bills</span>
<span class="badge" id="liabilitiesBadge" style="display: none; background: #e74c3c; min-width: 24px; height: 24px; font-size: 13px;">0</span>
</a>
<?php endif; ?>

<!-- Approvals -->
<?php if ($canApprove ?? Permission::check('approve_actions')): ?>
    <a href="<?php echo BASE_URL; ?>/staff/approvals" class="nav-item" style="min-height: 50px; padding: 14px 20px; font-size: 15px;">
<i class="fas fa-check-circle" style="font-size: 20px; width: 30px;"></i>
<span>✅ Approve Actions</span>
<?php if (!empty($stats['pending_approvals'])): ?>
<span class="badge" style="min-width: 24px; height: 24px; font-size: 13px;"><?php echo $stats['pending_approvals']; ?></span>
<?php endif; ?>
</a>
<?php endif; ?>

<!-- Reports -->
<?php if (Permission::check('view_reports')): ?>
    <a href="<?php echo BASE_URL; ?>/staff/reports" class="nav-item"
data-section="reports" onclick="return navigateTo('reports', event);" style="min-height: 50px; padding: 14px 20px; font-size: 15px;">
<i class="fas fa-chart-bar" style="font-size: 20px; width: 30px;"></i>
<span>📈 Sales Reports</span>
</a>
<?php endif; ?>

<!-- Support Tickets -->
<a href="<?php echo BASE_URL; ?>/?req=staff&action=support" class="nav-item <?php echo ($page ?? '') === 'support' ? 'active' : ''; ?>" style="min-height: 50px; padding: 14px 20px; font-size: 15px;">
<i class="fas fa-life-ring" style="font-size: 20px; width: 30px;"></i>
<span>🆘 Get Help</span>
</a>
<?php if ($hasAdminPanel): ?>
<div class="nav-divider" style="margin: 15px 0; border-top: 1px solid rgba(255,255,255,0.2);"></div>
<?php 
$isOwner = $user['role'] === 'admin';
$panelTitle = $isOwner ? '👑 Owner Controls' : '📋 Management';
?>
<div class="nav-section-header" style="padding: 10px 20px; color: rgba(255,255,255,0.7); font-size: 11px; text-transform: uppercase; font-weight: 600;">
<?php echo $panelTitle; ?>
</div>
<?php if (\Permission::check('manage_menu') || \Permission::check('edit_menu')): ?>
<a href="<?php echo BASE_URL; ?>/?req=staff&action=menu_manage" class="nav-item <?php echo ($page ?? '') === 'menu_manage' ? 'active' : ''; ?>">
<i class="fas fa-utensils"></i>
<span>🍴 Menu Items</span>
</a>
<?php endif; ?>
<?php if (\Permission::check('manage_tables')): ?>
<a href="<?php echo BASE_URL; ?>/?req=staff&action=tables_manage" class="nav-item <?php echo ($page ?? '') === 'tables_manage' ? 'active' : ''; ?>">
<i class="fas fa-chair"></i>
<span>🪑 Tables & QR Codes</span>
</a>
<?php endif; ?>
<?php if (\Permission::check('manage_staff') && $user['role'] === 'admin'): ?>
<a href="<?php echo BASE_URL; ?>/?req=staff&action=staff_manage" class="nav-item <?php echo ($page ?? '') === 'staff_manage' ? 'active' : ''; ?>" 
data-section="staff_manage" onclick="return navigateTo('staff_manage', event);">
<i class="fas fa-users"></i>
<span>👥 Employees</span>
<?php if ($user['role'] === 'admin'): ?>
<span class="owner-only" style="font-size: 9px; background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 3px; margin-left: 5px;">OWNER</span>
<?php endif; ?>
</a>
<?php endif; ?>
<?php if (in_array($user['role'], ['admin', 'manager'])): ?>
<a href="<?php echo BASE_URL; ?>/?req=staff&action=order_tracking" class="nav-item <?php echo ($page ?? '') === 'order_tracking' ? 'active' : ''; ?>" style="background: linear-gradient(135deg, #667eea22 0%, #764ba222 100%); border-left: 3px solid #667eea;">
<i class="fas fa-chart-line"></i>
<span>📊 Live Order Tracking</span>
</a>
<?php endif; ?>
<?php if (in_array($user['role'], ['admin', 'manager'])): ?>
<a href="<?php echo BASE_URL; ?>/?req=staff&action=activity_log" class="nav-item <?php echo ($page ?? '') === 'activity_log' ? 'active' : ''; ?>">
<i class="fas fa-history"></i>
<span>📜 Activity History</span>
</a>
<?php endif; ?>
<?php if ($user['role'] === 'admin'): ?>
<a href="<?php echo BASE_URL; ?>/?req=staff&action=subscription" class="nav-item <?php echo ($page ?? '') === 'subscription' ? 'active' : ''; ?>">
<i class="fas fa-crown"></i>
<span>👑 Subscription Plan</span>
<span class="owner-only" style="font-size: 9px; background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 3px; margin-left: 5px;">OWNER</span>
</a>
<?php endif; ?>
<?php if (\Permission::check('manage_settings') && $user['role'] === 'admin'): ?>
<a href="<?php echo BASE_URL; ?>/?req=staff&action=settings" class="nav-item <?php echo ($page ?? '') === 'restaurant_settings' ? 'active' : ''; ?>">
<i class="fas fa-cog"></i>
<span>⚙️ Settings</span>
<span class="owner-only" style="font-size: 9px; background: rgba(255,255,255,0.2); padding: 2px 6px; border-radius: 3px; margin-left: 5px;">OWNER</span>
</a>
<?php endif; ?>
<?php endif; ?>
</nav>
<div class="sidebar-footer">
<!-- Shift Status -->
<?php if (isset($isOnShift)): ?>
<div class="shift-status" style="padding: 15px; margin-bottom: 10px; background: <?php echo $isOnShift ? '#d4edda' : '#f8d7da'; ?>; border-radius: 8px;">
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
<span style="font-weight: 600; color: <?php echo $isOnShift ? '#155724' : '#721c24'; ?>;">
<i class="fas fa-<?php echo $isOnShift ? 'check-circle' : 'clock'; ?>"></i>
<?php echo $isOnShift ? 'On Shift' : 'Off Shift'; ?>
</span>
</div>
<button onclick="if(typeof window.toggleShift==='function'){window.toggleShift(event);}else{toggleShift(event);}" class="btn-shift" style="width: 100%; padding: 8px; background: <?php echo $isOnShift ? '#dc3545' : '#28a745'; ?>; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: 600;">
<i class="fas fa-<?php echo $isOnShift ? 'clock' : 'play-circle'; ?>"></i>
<?php echo $isOnShift ? 'Clock Out' : 'Clock In'; ?>
</button>
</div>
<?php endif; ?>
<div class="user-info">
<div class="user-avatar">
<i class="fas fa-user-circle"></i>
</div>
<div class="user-details">
<div class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
<div class="user-role"><?php echo htmlspecialchars($user['username'] ?? $displayRole); ?></div>
</div>
</div>
<a href="<?php echo BASE_URL; ?>/?req=staff&action=logout" class="btn-logout">
<i class="fas fa-sign-out-alt"></i>
</a>
</div>
</aside>
<!-- Global Audio Notification System for Waiter Calls -->
<script>
(function() {
// Global variables for audio system
let globalAudioContext = null;
let globalRingingInterval = null;
let globalIsRinging = false;
let lastKnownCallCount = 0;
// Function to play loud alarm sound
function playLoudAlarm() {
if (!globalAudioContext || globalAudioContext.state === 'closed') {
const AudioContext = window.AudioContext || window.webkitAudioContext;
globalAudioContext = new AudioContext();
}
if (globalAudioContext.state === 'suspended') {
globalAudioContext.resume().then(() => playAlarmNow());
return;
}
playAlarmNow();
}
function playAlarmNow() {
try {
if (!globalAudioContext || globalAudioContext.state !== 'running') return;
const now = globalAudioContext.currentTime;
// Create 4 layers for MAXIMUM loudness
for (let layer = 0; layer < 4; layer++) {
const pattern = [
{ freq: 850 + (layer * 80), duration: 0.12 },
{ freq: 1100 + (layer * 80), duration: 0.12 },
{ freq: 850 + (layer * 80), duration: 0.12 },
{ freq: 1300 + (layer * 80), duration: 0.12 },
{ freq: 1100 + (layer * 80), duration: 0.12 },
{ freq: 1500 + (layer * 80), duration: 0.18 }
];
let time = now;
pattern.forEach((note) => {
const oscillator = globalAudioContext.createOscillator();
const gainNode = globalAudioContext.createGain();
oscillator.connect(gainNode);
gainNode.connect(globalAudioContext.destination);
oscillator.frequency.value = note.freq;
oscillator.type = 'square';
// Maximum volume - 0.25 per layer = 1.0 total
gainNode.gain.setValueAtTime(0, time);
gainNode.gain.linearRampToValueAtTime(0.25, time + 0.01);
gainNode.gain.exponentialRampToValueAtTime(0.001, time + note.duration);
oscillator.start(time);
oscillator.stop(time + note.duration);
time += note.duration;
});
}
// Mobile vibration
try {
if ('vibrate' in navigator) {
navigator.vibrate([400, 100, 400, 100, 400]);
}
} catch (vibrateError) {
// Vibrate requires user interaction - browser security
}
} catch (e) {
}
}
function startGlobalRinging() {
if (globalIsRinging) return;
globalIsRinging = true;
// Initialize audio context
if (!globalAudioContext) {
const AudioContext = window.AudioContext || window.webkitAudioContext;
globalAudioContext = new AudioContext();
}
// Resume if suspended
if (globalAudioContext.state === 'suspended') {
globalAudioContext.resume();
}
// Play immediately
playLoudAlarm();
// Continue ringing every 1.5 seconds
globalRingingInterval = setInterval(() => {
playLoudAlarm();
}, 1500);
// Store in localStorage for cross-page persistence
localStorage.setItem('waiter_call_ringing', 'true');
}
function stopGlobalRinging() {
if (!globalIsRinging) return;
globalIsRinging = false;
if (globalRingingInterval) {
clearInterval(globalRingingInterval);
globalRingingInterval = null;
}
localStorage.removeItem('waiter_call_ringing');
}
// Check for pending calls
function checkPendingCalls() {
const restaurantId = <?php echo isset($user['restaurant_id']) ? $user['restaurant_id'] : 'null'; ?>;
if (!restaurantId) return;
fetch(`<?php echo BASE_URL; ?>/?req=api&action=staff_get_waiter_calls_stats&restaurant_id=${restaurantId}`, {
credentials: 'include'
})
.then(r => {
if (!r.ok) {
throw new Error('HTTP ' + r.status);
}
return r.text();
})
.then(text => {
let data;
try {
data = JSON.parse(text);
} catch (e) {
return;
}
return data;
})
.then(data => {
if (!data) return;
if (data.status === 'OK' && data.data) {
const pendingCount = parseInt(data.data.pending) || 0;
// Update badge counts across all pages
document.querySelectorAll('.nav-item .badge').forEach(badge => {
if (badge.closest('a[href*="waiter_calls"]')) {
if (pendingCount > 0) {
badge.textContent = pendingCount;
badge.style.display = 'inline-block';
} else {
badge.style.display = 'none';
}
}
});
// Start or stop ringing based on call count
if (pendingCount > 0 && !globalIsRinging) {
startGlobalRinging();
lastKnownCallCount = pendingCount;
} else if (pendingCount === 0 && globalIsRinging) {
stopGlobalRinging();
lastKnownCallCount = 0;
} else if (pendingCount > 0 && globalIsRinging) {
lastKnownCallCount = pendingCount;
}
}
})
.catch(() => {});
}
// Initialize audio on user interaction
function unlockAudio() {
if (!globalAudioContext) {
const AudioContext = window.AudioContext || window.webkitAudioContext;
globalAudioContext = new AudioContext();
}
if (globalAudioContext.state === 'suspended') {
globalAudioContext.resume();
}
}
// Only check calls for roles that should hear alerts
<?php if (isset($isOnShift) && $isOnShift && in_array($user['role'], ['admin', 'manager', 'waiter'])): ?>
// Check immediately on page load
setTimeout(checkPendingCalls, 500);
// Check every 5 seconds
setInterval(checkPendingCalls, 5000);
// Resume ringing if it was active (page reload/navigation)
if (localStorage.getItem('waiter_call_ringing') === 'true') {
setTimeout(() => {
checkPendingCalls();
}, 1000);
}
<?php endif; ?>
// Unlock audio on any user interaction
['click', 'touchstart', 'keydown'].forEach(event => {
document.addEventListener(event, unlockAudio, { once: true });
});
// Expose stop function globally for call handling
window.stopWaiterCallAlarm = stopGlobalRinging;
})();
</script>
<!-- Notification System for Order Ready Alerts -->
<script>
// Define global notification functions early to avoid ReferenceError
window.toggleNotificationPanel = function() {};
window.markSingleNotificationRead = function() {};
window.markAllNotificationsRead = function() {};
(function() {
<?php if (in_array($user['role'], ['waiter', 'admin', 'manager', 'kitchen'])): ?>
const BASE_PATH = '<?php echo rtrim(BASE_URL, '/'); ?>';
const USER_ROLE = '<?php echo $user['role']; ?>';
// Poll for notifications every 10 seconds
function checkNotifications() {
fetch(`${BASE_PATH}/?req=api&action=staff_get_notifications&include_read=0`, {
credentials: 'include'
})
.then(r => {
if (!r.ok) {
throw new Error('HTTP ' + r.status);
}
return r.text();
})
.then(text => {
let data;
try {
data = JSON.parse(text);
} catch (e) {
return;
}
if (data && data.status === 'OK') {
// Update badge
updateNotificationBadge();
// Log notification check
if (data.count > 0) {
}
// Show popup only for new notifications not yet shown
if (data.count > 0) {
data.data.forEach(notification => {
if (!shownNotifications.has(notification.id)) {
shownNotifications.add(notification.id);
// Don't save to localStorage - keep in memory only for real-time alerts
// Show popup for new notifications
showNotificationAlert(notification);
// Play 5-second sound for new order notifications (kitchen, manager, admin)
if (notification.type === 'new_order' && ['kitchen', 'manager', 'admin'].includes(USER_ROLE)) {
try {
const notifData = JSON.parse(notification.data || '{}');
if (notifData.sound) {
playKitchenOrderSound(notifData.sound_duration || 5000);
}
} catch (e) {
// Fallback to default sound
playKitchenOrderSound(5000);
}
}
// Play 5-second sound for waiter call notifications (manager, admin)
if (notification.type === 'waiter_call' && ['manager', 'admin'].includes(USER_ROLE)) {
try {
const notifData = JSON.parse(notification.data || '{}');
if (notifData.sound) {
playKitchenOrderSound(notifData.sound_duration || 5000);
}
} catch (e) {
// Fallback to default sound
playKitchenOrderSound(5000);
}
}
// Play 5-second sound for waiter assignment notifications
if (notification.type === 'waiter_assignment' && USER_ROLE === 'waiter') {
try {
const notifData = JSON.parse(notification.data || '{}');
if (notifData.sound) {
playKitchenOrderSound(notifData.sound_duration || 5000);
}
} catch (e) {
// Fallback to default sound
playKitchenOrderSound(5000);
}
}
// Play 5-second sound for order ready notifications (waiter, manager, admin)
if (notification.type === 'order_ready' && ['waiter', 'manager', 'admin'].includes(USER_ROLE)) {
try {
const notifData = JSON.parse(notification.data || '{}');
if (notifData.sound) {
playKitchenOrderSound(notifData.sound_duration || 5000);
}
} catch (e) {
// Fallback to default sound
playKitchenOrderSound(5000);
}
}
}
});
}
}
})
.catch((error) => {
});
}
function showNotificationAlert(notification) {
// Only play sound for waiter calls (customer rang) - NOT for general notifications
// Sound is handled separately in checkPendingCalls() for waiter calls
// Show visual alert
const alert = document.createElement('div');
alert.className = 'notification-alert';
alert.style.cssText = `
position: fixed;
top: 80px;
right: 20px;
z-index: 9999;
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
color: white;
padding: 20px;
border-radius: 10px;
box-shadow: 0 10px 25px rgba(0,0,0,0.3);
max-width: 350px;
animation: slideInRight 0.3s ease-out;
`;
alert.innerHTML = `
<div style="display: flex; align-items: start; gap: 15px;">
<div style="font-size: 24px;"><i class="fas fa-bell" style="color: white;"></i></div>
<div style="flex: 1;">
<div style="font-weight: bold; font-size: 16px; margin-bottom: 5px;">
${notification.title}
</div>
<div style="font-size: 14px; opacity: 0.9;">
${notification.message}
</div>
</div>
<button onclick="this.parentElement.parentElement.remove()" 
style="background: rgba(255,255,255,0.2); border: none; color: white; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 16px;">
×
</button>
</div>
`;
document.body.appendChild(alert);
// Auto remove after 8 seconds
setTimeout(() => {
if (alert.parentElement) {
alert.style.animation = 'slideOutRight 0.3s ease-in';
setTimeout(() => alert.remove(), 300);
}
}, 8000);
}
function playKitchenOrderSound(duration = 5000) {
// Play a repeating beep sound for the specified duration (default 5 seconds)
try {
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
// Resume audio context if suspended (browser autoplay policy)
if (audioCtx.state === 'suspended') {
audioCtx.resume().catch(err => {
return;
});
}
const now = audioCtx.currentTime;
const endTime = now + (duration / 1000);
let currentTime = now;
while (currentTime < endTime) {
const oscillator = audioCtx.createOscillator();
const gainNode = audioCtx.createGain();
oscillator.connect(gainNode);
gainNode.connect(audioCtx.destination);
oscillator.frequency.value = 1000; // Higher pitch for kitchen alerts
oscillator.type = 'square'; // More attention-grabbing sound
gainNode.gain.value = 0.4;
oscillator.start(currentTime);
gainNode.gain.exponentialRampToValueAtTime(0.01, currentTime + 0.3);
oscillator.stop(currentTime + 0.3);
currentTime += 0.5; // Beep every 0.5 seconds
}
} catch (err) {
}
}
function markNotificationRead(notificationId) {
fetch(`${BASE_PATH}/?req=api&action=staff_mark_notification_read`, {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({notification_id: notificationId}),
credentials: 'include'
}).catch(() => {});
}
// Add CSS animation
if (!document.getElementById('notification-styles')) {
const style = document.createElement('style');
style.id = 'notification-styles';
style.textContent = `
@keyframes slideInRight {
from { transform: translateX(400px); opacity: 0; }
to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOutRight {
from { transform: translateX(0); opacity: 1; }
to { transform: translateX(400px); opacity: 0; }
}
`;
document.head.appendChild(style);
}
// Notification Panel Management
let allNotifications = [];
// Track shown notifications in memory only (reset on page reload)
// This ensures real-time notifications work properly
let shownNotifications = new Set();
// DO NOT load from localStorage - this prevents real-time notifications
// The database tracks is_read status instead
function toggleNotificationPanel() {
let panel = document.getElementById('notification-panel');
if (!panel) {
panel = createNotificationPanel();
document.body.appendChild(panel);
}
panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
if (panel.style.display === 'block') {
loadNotificationPanel();
}
}
function createNotificationPanel() {
const panel = document.createElement('div');
panel.id = 'notification-panel';
panel.style.cssText = `
position: fixed;
top: 50%;
left: 50%;
transform: translate(-50%, -50%);
z-index: 10000;
background: white;
border-radius: 12px;
box-shadow: 0 10px 40px rgba(0,0,0,0.3);
width: 90%;
max-width: 500px;
max-height: 600px;
display: none;
`;
panel.innerHTML = `
<div style="padding: 20px; border-bottom: 1px solid #e0e0e0; display: flex; align-items: center; justify-content: space-between;">
<h3 style="margin: 0; color: #2c3e50;">
<i class="fas fa-bell"></i> Notifications
</h3>
<button onclick="toggleNotificationPanel()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
</div>
<div id="notification-list" style="max-height: 450px; overflow-y: auto; padding: 15px;">
<div style="text-align: center; color: #999; padding: 40px 20px;">
<i class="fas fa-spinner fa-spin" style="font-size: 32px;"></i>
<p>Loading notifications...</p>
</div>
</div>
<div style="padding: 15px; border-top: 1px solid #e0e0e0; text-align: center;">
<button onclick="markAllNotificationsRead()" style="padding: 10px 20px; background: #4a90e2; color: white; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">
<i class="fas fa-check-double"></i> Mark All as Read
</button>
</div>
`;
return panel;
}
function loadNotificationPanel() {
fetch(`${BASE_PATH}/?req=api&action=staff_get_notifications&include_read=0`, {
credentials: 'include'
})
.then(r => r.json())
.then(data => {
const list = document.getElementById('notification-list');
if (!list) return;
if (data.status === 'OK' && data.count > 0) {
list.innerHTML = data.data.map(n => `
<div style="padding: 15px; border-bottom: 1px solid #f0f0f0; background: ${n.is_read ? '#f9f9f9' : 'white'}; border-left: 3px solid ${getNotificationColor(n.notification_type)};">
<div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 5px;">
<strong style="color: #2c3e50;">${escapeHtml(n.title)}</strong>
<button onclick="markSingleNotificationRead(${n.id})" style="background: #4a90e2; color: white; border: none; padding: 4px 10px; border-radius: 4px; cursor: pointer; font-size: 11px;">
<i class="fas fa-check"></i>
</button>
</div>
<div style="color: #666; font-size: 14px; margin-bottom: 5px;">${escapeHtml(n.message)}</div>
<div class="notif-time" data-timestamp="${n.created_at}" style="color: #999; font-size: 12px;">${formatTime(n.created_at)}</div>
</div>
`).join('');
// Update timestamps every 10 seconds
updateTimestamps();
} else {
list.innerHTML = '<div style="text-align: center; color: #999; padding: 40px 20px;"><i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 15px;"></i><p>No notifications</p></div>';
}
})
.catch(() => {
const list = document.getElementById('notification-list');
if (list) list.innerHTML = '<div style="text-align: center; color: #dc3545; padding: 40px 20px;">Error loading notifications</div>';
});
}
function getNotificationColor(type) {
const colors = {
'assignment': '#4a90e2',
'waiter_call': '#f39c12',
'order_ready': '#27ae60',
'order_update': '#8e44ad'
};
return colors[type] || '#95a5a6';
}
function escapeHtml(text) {
const div = document.createElement('div');
div.textContent = text;
return div.innerHTML;
}
function formatTime(timestamp) {
// Handle MySQL datetime format (YYYY-MM-DD HH:MM:SS)
// Replace space with 'T' to make it ISO 8601 compliant for better parsing
const isoTimestamp = timestamp.replace(' ', 'T');
const date = new Date(isoTimestamp);
const now = new Date();
const diff = Math.floor((now - date) / 1000);
const minutes = Math.floor(diff / 60);
if (diff < 60) return 'Just now';
if (minutes === 1) return '1 min ago';
if (minutes < 60) return minutes + ' mins ago';
if (minutes < 1440) return Math.floor(minutes / 60) + 'h ' + (minutes % 60) + 'm ago'; // Show hours and minutes
if (diff < 172800) return 'Yesterday';
return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
}
// Update all timestamps in real-time
function updateTimestamps() {
document.querySelectorAll('.notif-time').forEach(elem => {
const timestamp = elem.getAttribute('data-timestamp');
if (timestamp) {
elem.textContent = formatTime(timestamp);
}
});
}
// Auto-update timestamps every 10 seconds
setInterval(updateTimestamps, 10000);
function markSingleNotificationRead(id) {
markNotificationRead(id);
// Remove from shown notifications set when marked as read
// Remove from in-memory set (will be re-added if notification comes again)
shownNotifications.delete(id);
setTimeout(() => loadNotificationPanel(), 300);
updateNotificationBadge();
}
function markAllNotificationsRead() {
fetch(`${BASE_PATH}/?req=api&action=staff_mark_all_notifications_read`, {
method: 'POST',
credentials: 'include'
})
.then(r => r.json())
.then(() => {
// Clear shown notifications from localStorage
// Clear in-memory tracking
shownNotifications.clear();
loadNotificationPanel();
updateNotificationBadge();
})
.catch(() => {});
}
function updateNotificationBadge() {
fetch(`${BASE_PATH}/?req=api&action=staff_get_notifications&include_read=0`, {
credentials: 'include'
})
.then(r => r.json())
.then(data => {
const badgeHeader = document.getElementById('notification-badge-header');
if (data.status === 'OK' && data.count > 0) {
const countText = data.count > 9 ? '9+' : data.count;
if (badgeHeader) {
badgeHeader.textContent = countText;
badgeHeader.style.display = 'flex';
}
} else {
if (badgeHeader) badgeHeader.style.display = 'none';
}
})
.catch(() => {});
}
window.toggleNotificationPanel = toggleNotificationPanel;
window.markSingleNotificationRead = markSingleNotificationRead;
window.markAllNotificationsRead = markAllNotificationsRead;
// Check immediately and then every 10 seconds
setTimeout(checkNotifications, 2000);
setInterval(checkNotifications, 10000);
// Delayed order escalation check (every 30 seconds)
function checkDelayedOrders() {
fetch('<?php echo BASE_URL; ?>/?req=api&action=staff_check_delayed_orders', {
method: 'GET',
headers: {
'Content-Type': 'application/json'
}
})
.then(response => response.json())
.then(data => {
if (data.status === 'OK' && data.notifications_sent > 0) {
// Trigger notification check to pick up new delay notifications
checkNotifications();
}
})
.catch(error => {
});
}
// Check for delayed orders every 30 seconds (only for managers/admins/kitchen)
const userRole = '<?php echo isset($_SESSION['staff_user']['role']) ? $_SESSION['staff_user']['role'] : ''; ?>';
if (['admin', 'manager', 'kitchen'].includes(userRole)) {
setTimeout(checkDelayedOrders, 5000); // First check after 5 seconds
setInterval(checkDelayedOrders, 30000); // Then every 30 seconds
}
<?php endif; ?>
})();
</script>
