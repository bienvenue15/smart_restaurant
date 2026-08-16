<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($title) ? $title : 'Menu Management - Admin Dashboard'; ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/staff.css">
<link rel="icon" type="image/x-icon" href="<?php echo APP_FAVICON_URL; ?>">
<style>
.admin-container {
margin-left: 260px;
padding: 30px;
background: #f5f7fa;
min-height: 100vh;
}
.page-header {
background: white;
padding: 25px;
border-radius: 10px;
margin-bottom: 25px;
box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.page-header h1 {
margin: 0;
color: #2c3e50;
display: flex;
align-items: center;
gap: 15px;
}
.tabs {
display: flex;
gap: 10px;
margin-bottom: 25px;
border-bottom: 2px solid #e0e0e0;
}
.tab {
padding: 15px 25px;
cursor: pointer;
border: none;
background: none;
font-size: 16px;
font-weight: 500;
color: #666;
border-bottom: 3px solid transparent;
transition: all 0.3s;
}
.tab.active {
color: #3498db;
border-bottom-color: #3498db;
}
.tab-content {
display: none;
}
.tab-content.active {
display: block;
}
.card {
background: white;
border-radius: 10px;
padding: 25px;
margin-bottom: 25px;
box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.card-header {
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 20px;
padding-bottom: 15px;
border-bottom: 2px solid #f0f0f0;
}
.card-header h2 {
margin: 0;
color: #2c3e50;
}
.btn {
padding: 10px 20px;
border: none;
border-radius: 5px;
cursor: pointer;
font-size: 14px;
font-weight: 500;
transition: all 0.3s;
}
.btn-primary {
background: #3498db;
color: white;
}
.btn-primary:hover {
background: #2980b9;
}
.btn-success {
background: #27ae60;
color: white;
}
.btn-danger {
background: #e74c3c;
color: white;
}
.btn-warning {
background: #f39c12;
color: white;
}
.table-responsive {
overflow-x: auto;
}
table {
width: 100%;
border-collapse: collapse;
}
th, td {
padding: 12px;
text-align: left;
border-bottom: 1px solid #e0e0e0;
}
th {
background: #f8f9fa;
font-weight: 600;
color: #2c3e50;
}
.item-image {
width: 60px;
height: 60px;
object-fit: cover;
border-radius: 5px;
}
.badge {
padding: 5px 10px;
border-radius: 20px;
font-size: 12px;
font-weight: 500;
}
.badge-success {
background: #d4edda;
color: #155724;
}
.badge-danger {
background: #f8d7da;
color: #721c24;
}
.form-group {
margin-bottom: 20px;
}
.form-group label {
display: block;
margin-bottom: 8px;
font-weight: 500;
color: #2c3e50;
}
.form-control {
width: 100%;
padding: 10px;
border: 1px solid #ddd;
border-radius: 5px;
font-size: 14px;
}
.form-control:focus {
outline: none;
border-color: #3498db;
}
.modal {
display: none;
position: fixed;
top: 0;
left: 0;
width: 100%;
height: 100%;
background: rgba(0,0,0,0.5);
z-index: 1000;
align-items: center;
justify-content: center;
}
.modal.active {
display: flex;
}
.modal-content {
background: white;
border-radius: 10px;
padding: 30px;
max-width: 600px;
width: 90%;
max-height: 90vh;
overflow-y: auto;
}
.modal-header {
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 20px;
padding-bottom: 15px;
border-bottom: 2px solid #f0f0f0;
}
.modal-header h3 {
margin: 0;
}
.close {
background: none;
border: none;
font-size: 24px;
cursor: pointer;
color: #999;
}
.image-preview {
width: 150px;
height: 150px;
object-fit: cover;
border-radius: 5px;
margin-top: 10px;
border: 2px dashed #ddd;
}
.alert {
padding: 15px;
border-radius: 5px;
margin-bottom: 20px;
}
.alert-success {
background: #d4edda;
color: #155724;
border: 1px solid #c3e6cb;
}
.alert-error {
background: #f8d7da;
color: #721c24;
border: 1px solid #f5c6cb;
}
.loading {
text-align: center;
padding: 20px;
color: #666;
}
.no-data {
text-align: center;
padding: 40px;
color: #999;
}
/* Toast animations */
@keyframes slideIn {
from {
transform: translateX(400px);
opacity: 0;
}
to {
transform: translateX(0);
opacity: 1;
}
}
@keyframes slideOut {
from {
transform: translateX(0);
opacity: 1;
}
to {
transform: translateX(400px);
opacity: 0;
}
}
/* Mobile Responsive Design */
@media (max-width: 1024px) {
.admin-container {
margin-left: 70px;
padding: 24px;
}
}
@media (max-width: 768px) {
.admin-container {
margin-left: 0;
padding: 16px;
}
.page-header {
padding: 20px 18px;
margin-bottom: 20px;
}
.page-header h1 {
font-size: 1.375rem;
gap: 12px;
}
.tabs {
overflow-x: auto;
flex-wrap: nowrap;
-webkit-overflow-scrolling: touch;
scrollbar-width: none;
margin-bottom: 20px;
}
.tabs::-webkit-scrollbar {
display: none;
}
.tab {
white-space: nowrap;
padding: 12px 20px;
font-size: 14px;
}
.card {
padding: 18px;
margin-bottom: 20px;
}
.card-header {
flex-direction: column;
align-items: flex-start;
gap: 12px;
margin-bottom: 16px;
}
.card-header h2 {
font-size: 1.125rem;
}
.card-header .btn {
width: 100%;
justify-content: center;
}
.table-responsive {
margin: 0 -18px;
padding: 0 18px;
}
table {
font-size: 0.875rem;
}
th, td {
padding: 10px 8px;
font-size: 0.8125rem;
}
.item-image {
width: 50px;
height: 50px;
}
.btn {
padding: 9px 16px;
font-size: 0.8125rem;
}
.modal-content {
width: 95%;
padding: 24px;
}
.modal-header h3 {
font-size: 1.125rem;
}
.form-group {
margin-bottom: 16px;
}
.image-preview {
width: 120px;
height: 120px;
}
}
@media (max-width: 480px) {
.admin-container {
padding: 12px;
}
.page-header {
padding: 16px 14px;
margin-bottom: 16px;
}
.page-header h1 {
font-size: 1.125rem;
gap: 10px;
}
.tab {
padding: 10px 16px;
font-size: 13px;
}
.card {
padding: 14px;
margin-bottom: 16px;
}
.card-header h2 {
font-size: 1rem;
}
table {
min-width: 500px;
}
th, td {
padding: 8px 6px;
font-size: 0.75rem;
}
.item-image {
width: 45px;
height: 45px;
}
.badge {
font-size: 0.7rem;
padding: 4px 8px;
}
.btn {
padding: 8px 12px;
font-size: 0.75rem;
}
.modal-content {
width: 96%;
padding: 18px;
margin: 5px;
}
.modal-header {
margin-bottom: 16px;
padding-bottom: 12px;
}
.modal-header h3 {
font-size: 1rem;
}
.form-group label {
font-size: 0.875rem;
}
.form-control {
padding: 9px;
font-size: 0.875rem;
}
.image-preview {
width: 100px;
height: 100px;
}
.alert {
padding: 12px;
font-size: 0.875rem;
}
}
@media (max-width: 360px) {
.admin-container {
padding: 10px;
}
.page-header h1 {
font-size: 1rem;
}
.card {
padding: 12px;
}
.tab {
padding: 8px 14px;
font-size: 12px;
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
<?php 
// Load sidebar dependencies
require_once __DIR__ . '/../../../../app/core/Permission.php';
require_once __DIR__ . '/../../../../app/models/Staff.php';
$staffModel = new Staff();
$isOnShift = $staffModel->isOnShift($user['uuid']);
$canHandleCash = Permission::check('handle_cash');
$canApprove = Permission::check('approve_actions');
// Get stats for sidebar badges
$restaurantIdForStats = $_SESSION['staff_user']['restaurant_uuid'] ?? null;
$statsResult = $staffModel->getDashboardStats($restaurantIdForStats);
$stats = $statsResult['status'] === 'OK' ? $statsResult['data'] : [];
// Include sidebar when not in fragment mode
if (!$isFragment) {
include __DIR__ . '/../_sidebar.php';
}
?>
<!-- Announcement Banner -->
<?php if (!$isFragment) include __DIR__ . '/../includes/announcement_banner.php'; ?>
<div class="admin-container">
<div class="page-header">
<div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
<h1 style="margin: 0;">
<i class="fas fa-utensils"></i>
<?php echo isset($is_view_only) && $is_view_only ? 'Menu View' : 'Menu Management'; ?>
</h1>
<div style="display: flex; align-items: center; gap: 15px;">
<button class="btn-notification-header" onclick="parent.toggleNotificationPanel()" style="background: none; border: none; color: #666; font-size: 20px; cursor: pointer; position: relative; padding: 8px 12px;">
<i class="fas fa-bell"></i>
<span id="notification-badge-iframe" class="notification-badge" style="display: none; position: absolute; top: 2px; right: 2px; background: #dc3545; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; font-weight: bold; display: flex; align-items: center; justify-content: center;"></span>
</button>
<div style="text-align: right;">
<div style="color: #666; font-size: 14px; margin-bottom: 5px;">
<i class="fas fa-clock"></i> <span id="currentTime"></span>
</div>
<div style="color: #999; font-size: 12px;">
<span id="currentDate"></span>
</div>
</div>
</div>
</div>
<?php if (isset($is_view_only) && $is_view_only): ?>
<div style="color: #666; font-size: 14px; margin-top: 15px;">
<i class="fas fa-info-circle"></i> 
<?php if ($user['role'] === 'kitchen'): ?>
Kitchen view - You can update item availability (except beverages)
<?php else: ?>
View-only mode - Check item availability and prices
<?php endif; ?>
</div>
<?php endif; ?>
</div>
<div id="alert-container"></div>
<?php if ($user['role'] === 'kitchen'): ?>
<div class="card" style="margin-bottom: 20px; background: #fff3cd; border-left: 4px solid #f39c12;">
<div style="padding: 15px;">
<div style="display: flex; align-items: start; gap: 10px;">
<i class="fas fa-utensils" style="color: #f39c12; font-size: 20px; margin-top: 2px;"></i>
<div style="flex: 1;">
<strong style="color: #2c3e50; font-size: 15px;">Kitchen Staff Menu Control</strong>
<div style="margin: 10px 0 0 0; color: #666; font-size: 13px; line-height: 1.6;">
<p style="margin: 0 0 8px 0;"><strong>What you can do:</strong></p>
<ul style="margin: 0; padding-left: 20px;">
<li>Mark food items as <strong>Available</strong> or <strong>Unavailable</strong></li>
<li>Managers and owners will be automatically notified of changes</li>
<li>You can provide a reason for the change (e.g., "Out of stock", "Oven broken")</li>
</ul>
<p style="margin: 12px 0 0 0; padding: 8px; background: #f8f9fa; border-radius: 4px;">
<i class="fas fa-lock" style="color: #f39c12;"></i> 
<strong>Note:</strong> Beverage items are locked - only managers can update their availability.
</p>
</div>
</div>
</div>
</div>
</div>
<?php else: ?>
<div class="card" style="margin-bottom: 20px; background: #e8f4f8; border-left: 4px solid #3498db;">
<div style="padding: 15px;">
<div style="display: flex; align-items: center; gap: 10px;">
<i class="fas fa-info-circle" style="color: #3498db; font-size: 20px;"></i>
<div>
<strong style="color: #2c3e50;">QR Code Information</strong>
<p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">
When you update menu items (name, price, availability, description), your existing QR codes remain valid. 
They will automatically display the updated information when scanned. You only need to regenerate QR codes 
when adding new tables or changing restaurant settings. All menu changes are logged in the Activity Log.
</p>
</div>
</div>
</div>
</div>
<?php endif; ?>
<div class="tabs">
<button class="tab active" onclick="switchTab('categories')">
<i class="fas fa-folder"></i> Categories
</button>
<button class="tab" onclick="switchTab('items')">
<i class="fas fa-hamburger"></i> Menu Items
</button>
</div>
<!-- Categories Tab -->
<div id="categories-tab" class="tab-content active">
<div class="card">
<div class="card-header">
<h2>Menu Categories</h2>
<div style="display: flex; gap: 10px;">
<button class="btn btn-success" onclick="exportMenu()" title="Export menu to Excel">
<i class="fas fa-file-export"></i> Export Menu
</button>
<button class="btn btn-primary" onclick="openImportModal()" title="Import menu from Excel/CSV">
<i class="fas fa-file-import"></i> Import Menu
</button>
<?php if (!isset($is_view_only) || !$is_view_only): ?>
<button class="btn btn-primary" onclick="openCategoryModal()">
<i class="fas fa-plus"></i> Add Category
</button>
<?php endif; ?>
</div>
</div>
<div class="table-responsive">
<table>
<thead>
<tr>
<th>ID</th>
<th>Name</th>
<th>Description</th>
<th>Display Order</th>
<th>Actions</th>
</tr>
</thead>
<tbody id="categories-table-body">
<tr class="loading"><td colspan="5">Loading categories...</td></tr>
</tbody>
</table>
</div>
</div>
</div>
<!-- Menu Items Tab -->
<div id="items-tab" class="tab-content">
<div class="card">
<div class="card-header">
<h2>Menu Items</h2>
<?php if (!isset($is_view_only) || !$is_view_only): ?>
<button class="btn btn-primary" onclick="openItemModal()">
<i class="fas fa-plus"></i> Add Menu Item
</button>
<?php endif; ?>
</div>
<div class="table-responsive">
<table>
<thead>
<tr>
<th>Image</th>
<th>Name</th>
<th>Category</th>
<th>Price</th>
<th>Prep Time</th>
<th>Status</th>
<th>Actions</th>
</tr>
</thead>
<tbody id="items-table-body">
<tr class="loading"><td colspan="7">Loading menu items...</td></tr>
</tbody>
</table>
</div>
</div>
</div>
<!-- Category Modal -->
<div id="category-modal" class="modal">
<div class="modal-content">
<div class="modal-header">
<h3 id="category-modal-title">Add Category</h3>
<button class="close" onclick="closeCategoryModal()">&times;</button>
</div>
<form id="category-form" onsubmit="saveCategory(event)">
<input type="hidden" id="category-id" name="id">
<div class="form-group">
<label>Category Name *</label>
<input type="text" class="form-control" id="category-name" name="name" required>
</div>
<div class="form-group">
<label>Description</label>
<textarea class="form-control" id="category-description" name="description" rows="3"></textarea>
</div>
<div class="form-group">
<label>Display Order</label>
<input type="number" class="form-control" id="category-order" name="display_order" value="0">
</div>
<div style="display: flex; gap: 10px; justify-content: flex-end;">
<button type="button" class="btn" onclick="closeCategoryModal()">Cancel</button>
<button type="submit" class="btn btn-primary">Save</button>
</div>
</form>
</div>
</div>
<!-- Item Modal -->
<div id="item-modal" class="modal">
<div class="modal-content">
<div class="modal-header">
<h3 id="item-modal-title">Add Menu Item</h3>
<button class="close" onclick="closeItemModal()">&times;</button>
</div>
<form id="item-form" onsubmit="saveItem(event)" enctype="multipart/form-data">
<input type="hidden" id="item-id" name="id">
<div class="form-group">
<label>Category *</label>
<select class="form-control" id="item-category" name="category_id" required>
<option value="">Select Category</option>
</select>
</div>
<div class="form-group">
<label>Item Name *</label>
<input type="text" class="form-control" id="item-name" name="name" required>
</div>
<div class="form-group">
<label>Description</label>
<textarea class="form-control" id="item-description" name="description" rows="3"></textarea>
</div>
<div class="form-group">
<label>Price (RWF) *</label>
<input type="number" class="form-control" id="item-price" name="price" step="0.01" min="0" required>
</div>
<div class="form-group">
<label>Preparation Time (minutes) *</label>
<input type="number" class="form-control" id="item-prep-time" name="preparation_time" min="1" max="180" value="15" required>
<small style="color: #666;">Estimated time to prepare this item (1-180 minutes)</small>
</div>
<div class="form-group">
<label>Image</label>
<input type="file" class="form-control" id="item-image" name="image" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewImage(event)">
<small style="color: #666; display: block; margin-top: 5px;">
<i class="fas fa-camera"></i> <strong>Recommended:</strong> 600x600px, max 1MB upload<br>
<i class="fas fa-compress-alt"></i> Images auto-compressed to ~100KB (600x600px, optimized quality)<br>
<i class="fas fa-info-circle"></i> Supports: JPEG, PNG, WebP
</small>
<img id="image-preview" class="image-preview" style="display: none;" alt="Preview">
<img id="current-image" class="image-preview" style="display: none;" alt="Current">
</div>
<div class="form-group">
<label>
<input type="checkbox" id="item-available" name="is_available" checked>
Available for ordering
</label>
</div>
<div class="form-group">
<label>Display Order</label>
<input type="number" class="form-control" id="item-order" name="display_order" value="0">
</div>
<div style="display: flex; gap: 10px; justify-content: flex-end;">
<button type="button" class="btn" onclick="closeItemModal()">Cancel</button>
<button type="submit" class="btn btn-primary">Save</button>
</div>
</form>
</div>
</div>
</div>

<!-- Import Modal -->
<div id="importModal" class="modal">
<div class="modal-content" style="max-width: 800px;">
<div class="modal-header">
<h3><i class="fas fa-file-import"></i> Import Menu Data</h3>
<button class="close-modal" onclick="closeImportModal()">&times;</button>
</div>
<div class="modal-body">
<div style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
<h4 style="margin: 0 0 10px 0; color: #1976d2;"><i class="fas fa-info-circle"></i> Import Instructions</h4>
<ul style="margin: 0; padding-left: 20px; color: #555;">
<li>Download the template file to see the correct format</li>
<li>Fill in your menu data (categories and items)</li>
<li>Save as CSV file and upload below</li>
<li>Categories will be created automatically</li>
<li>Existing items will be updated, new items will be added</li>
</ul>
<button class="btn btn-primary" onclick="downloadTemplate()" style="margin-top: 10px;">
<i class="fas fa-download"></i> Download Template
</button>
</div>

<div class="form-group">
<label><strong>Select CSV File:</strong></label>
<input type="file" id="importFile" accept=".csv,.txt" class="form-control" onchange="previewImportFile()" style="padding: 10px;">
<small style="color: #666; display: block; margin-top: 5px;">Supported formats: CSV (.csv)</small>
</div>

<div id="importPreview" style="display: none; margin-top: 20px;">
<h4><i class="fas fa-eye"></i> Preview (First 10 Items)</h4>
<div class="table-responsive">
<table>
<thead>
<tr>
<th>Category</th>
<th>Item Name</th>
<th>Price</th>
<th>Available</th>
</tr>
</thead>
<tbody id="previewTableBody"></tbody>
</table>
</div>
</div>

<div id="importResults"></div>
</div>
<div class="modal-footer">
<button type="button" class="btn" onclick="closeImportModal()">Cancel</button>
<button type="button" class="btn btn-success" onclick="processImport()">
<i class="fas fa-upload"></i> Import Now
</button>
</div>
</div>
</div>

<script>
const BASE_URL = '<?php echo rtrim(BASE_URL, '/'); ?>';
const BASE_PATH = '<?php echo rtrim(BASE_URL, '/'); ?>';
// Real-time clock update
function updateTime() {
const now = new Date();
const timeStr = now.toLocaleTimeString('en-US', { 
hour: '2-digit', 
minute: '2-digit', 
second: '2-digit',
hour12: true 
});
const dateStr = now.toLocaleDateString('en-US', {
weekday: 'long',
year: 'numeric',
month: 'long',
day: 'numeric'
});
const timeEl = document.getElementById('currentTime');
const dateEl = document.getElementById('currentDate');
if (timeEl) timeEl.textContent = timeStr;
if (dateEl) dateEl.textContent = dateStr;
}
updateTime();
setInterval(updateTime, 1000);
const USER_ROLE = '<?php echo $user['role']; ?>';
const RESTAURANT_ID = '<?php echo $restaurant_id; ?>';
const isViewOnly = <?php echo isset($is_view_only) && $is_view_only ? 'true' : 'false'; ?>;
// Audio notification system
<?php include __DIR__ . '/../../includes/audio_notification.js'; ?>
let categories = <?php echo json_encode($categories); ?>;
let items = <?php echo json_encode($items); ?>;
// Initialize
document.addEventListener('DOMContentLoaded', function() {
renderCategories();
renderItems();
loadCategoriesForSelect();
});

// Function to reload menu data from server (called after successful operations)
async function reloadMenuData() {
try {
const response = await fetch(`${BASE_PATH}/?req=staff&action=api&api_action=get_menu_data&restaurant_id=${RESTAURANT_ID}`, {
credentials: 'include'
});
const text = await response.text();
try {
const data = JSON.parse(text);
if (data.status === 'OK') {
categories = data.categories || [];
items = data.items || [];
renderCategories();
renderItems();
loadCategoriesForSelect();
}
} catch(e) {
console.error('Invalid JSON in reloadMenuData:', text.substring(0, 200));
}
} catch (err) {
console.error('Error reloading menu data:', err);
}
}

function switchTab(tab) {
document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
event.target.classList.add('active');
document.getElementById(tab + '-tab').classList.add('active');
}
function renderCategories() {
const tbody = document.getElementById('categories-table-body');
if (categories.length === 0) {
tbody.innerHTML = '<tr><td colspan="5" class="no-data">No categories found. Add your first category!</td></tr>';
return;
}
tbody.innerHTML = categories.map(cat => `
<tr>
<td>${cat.id}</td>
<td><strong>${escapeHtml(cat.name)}</strong></td>
<td>${escapeHtml(cat.description || '-')}</td>
<td>${cat.display_order || 0}</td>
<td>
${!isViewOnly ? `
<button class="btn btn-warning" onclick="editCategory(${cat.id})" style="padding: 5px 10px; margin-right: 5px;">
<i class="fas fa-edit"></i>
</button>
${USER_ROLE === 'admin' ? `
<button class="btn btn-danger" onclick="deleteCategory(${cat.id})" style="padding: 5px 10px;">
<i class="fas fa-trash"></i>
</button>
` : ''}
` : '<span class="badge badge-info">View Only</span>'}
</td>
</tr>
`).join('');
}
function renderItems() {
const tbody = document.getElementById('items-table-body');
if (!items || items.length === 0) {
tbody.innerHTML = '<tr><td colspan="7" class="no-data">No menu items found. Add your first item!</td></tr>';
return;
}
tbody.innerHTML = items.map(item => {
const imageUrl = item.image_url ? 
(item.image_url.startsWith('http') ? item.image_url : BASE_PATH + '/' + item.image_url) : 
BASE_PATH + '/assets/images/no-image.png';
const categoryName = item.category_name || 'Uncategorized';
const isAvailable = item.is_available == 1;
const isBeverage = categoryName.toLowerCase() === 'beverages';
const prepTime = item.preparation_time || 15;
// For kitchen staff, check if they can modify this item
const kitchenCanModify = USER_ROLE === 'kitchen' && !isBeverage;
return `
<tr>
<td><img src="${imageUrl}" class="item-image" alt="${escapeHtml(item.name)}"></td>
<td><strong>${escapeHtml(item.name)}</strong></td>
<td>
${escapeHtml(categoryName)}
${USER_ROLE === 'kitchen' && isBeverage ? '<br><small style="color: #f39c12;"><i class="fas fa-lock"></i> Manager Only</small>' : ''}
</td>
<td>${formatCurrency(item.price)}</td>
<td><i class="fas fa-clock" style="color: #666;"></i> ${prepTime} min</td>
<td>
<span class="badge ${isAvailable ? 'badge-success' : 'badge-danger'}">
${isAvailable ? 'Available' : 'Unavailable'}
</span>
</td>
<td>
${!isViewOnly ? `
<button class="btn btn-warning" onclick="editItem(${item.id})" style="padding: 5px 10px; margin-right: 5px;">
<i class="fas fa-edit"></i>
</button>
<button class="btn ${isAvailable ? 'btn-warning' : 'btn-success'}" onclick="toggleAvailability(${item.id}, ${isAvailable ? 0 : 1}, '${escapeHtml(categoryName)}')" style="padding: 5px 10px; margin-right: 5px;" title="${isAvailable ? 'Mark as Unavailable' : 'Mark as Available'}">
<i class="fas fa-${isAvailable ? 'eye-slash' : 'eye'}"></i>
</button>
${USER_ROLE === 'admin' ? `
<button class="btn btn-danger" onclick="deleteItem(${item.id})" style="padding: 5px 10px;">
<i class="fas fa-trash"></i>
</button>
` : ''}
` : (USER_ROLE === 'kitchen' ? `
${kitchenCanModify ? `
<button class="btn ${isAvailable ? 'btn-warning' : 'btn-success'}" onclick="toggleAvailability(${item.id}, ${isAvailable ? 0 : 1}, '${escapeHtml(categoryName)}')" style="padding: 5px 12px; font-size: 13px;" title="${isAvailable ? 'Mark as Unavailable' : 'Mark as Available'}">
<i class="fas fa-${isAvailable ? 'eye-slash' : 'eye'}"></i> ${isAvailable ? 'Mark Unavailable' : 'Mark Available'}
</button>
` : `
<span class="badge badge-warning" style="padding: 6px 12px; font-size: 12px;">
<i class="fas fa-lock"></i> Manager Only
</span>
`}
` : '<span class="badge badge-info">View Only</span>')}
</td>
</tr>
`;
}).join('');
}
function loadCategoriesForSelect() {
const select = document.getElementById('item-category');
select.innerHTML = '<option value="">Select Category</option>' +
categories.map(cat => 
`<option value="${cat.uuid}">${escapeHtml(cat.name)}</option>`
).join('');
}
function openCategoryModal(id = null) {
const modal = document.getElementById('category-modal');
const form = document.getElementById('category-form');
const title = document.getElementById('category-modal-title');
if (id) {
const cat = categories.find(c => c.uuid == id);
if (cat) {
document.getElementById('category-id').value = cat.uuid;
document.getElementById('category-name').value = cat.name;
document.getElementById('category-description').value = cat.description || '';
document.getElementById('category-order').value = cat.display_order || 0;
title.textContent = 'Edit Category';
}
} else {
form.reset();
document.getElementById('category-id').value = '';
title.textContent = 'Add Category';
}
modal.classList.add('active');
}
function closeCategoryModal() {
document.getElementById('category-modal').classList.remove('active');
document.getElementById('category-form').reset();
}
function openItemModal(id = null) {
const modal = document.getElementById('item-modal');
const form = document.getElementById('item-form');
const title = document.getElementById('item-modal-title');
loadCategoriesForSelect();
if (id) {
const item = items.find(i => i.uuid == id);
if (item) {
document.getElementById('item-id').value = item.uuid;
document.getElementById('item-category').value = item.category_uuid;
document.getElementById('item-name').value = item.name;
document.getElementById('item-description').value = item.description || '';
document.getElementById('item-price').value = item.price;
document.getElementById('item-prep-time').value = item.preparation_time || 15;
document.getElementById('item-available').checked = item.is_available == 1;
document.getElementById('item-order').value = item.display_order || 0;
if (item.image_url) {
const imageUrl = item.image_url.startsWith('http') ? item.image_url : BASE_PATH + '/' + item.image_url;
document.getElementById('current-image').src = imageUrl;
document.getElementById('current-image').style.display = 'block';
document.getElementById('image-preview').style.display = 'none';
}
title.textContent = 'Edit Menu Item';
}
} else {
form.reset();
document.getElementById('item-id').value = '';
document.getElementById('current-image').style.display = 'none';
document.getElementById('image-preview').style.display = 'none';
title.textContent = 'Add Menu Item';
}
modal.classList.add('active');
}
function closeItemModal() {
document.getElementById('item-modal').classList.remove('active');
document.getElementById('item-form').reset();
document.getElementById('current-image').style.display = 'none';
document.getElementById('image-preview').style.display = 'none';
}
/**
 * Compress image using Canvas API
 */
function compressImage(file, maxWidth, maxHeight, quality) {
    maxWidth = maxWidth || 600;
    maxHeight = maxHeight || 600;
    quality = quality || 0.8;
    
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = function(e) {
            const img = new Image();
            img.src = e.target.result;
            img.onload = function() {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                
                // Calculate new dimensions while maintaining aspect ratio
                if (width > height) {
                    if (width > maxWidth) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }
                
                canvas.width = width;
                canvas.height = height;
                
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                // Convert canvas to blob
                canvas.toBlob(
                    function(blob) {
                        if (blob) {
                            // Create a new File object from the blob
                            const compressedFile = new File([blob], file.name, {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            resolve(compressedFile);
                        } else {
                            reject(new Error('Canvas to Blob conversion failed'));
                        }
                    },
                    'image/jpeg',
                    quality
                );
            };
            img.onerror = function() {
                reject(new Error('Image loading failed'));
            };
        };
        reader.onerror = function() {
            reject(new Error('File reading failed'));
        };
    });
}

async function previewImage(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showAlert('Invalid file type! Please upload JPEG, PNG, or WebP images only.', 'error');
        event.target.value = '';
        return;
    }
    
    try {
        showAlert('Processing image...', 'info');
        
        // Read the original image to check dimensions
        const reader = new FileReader();
        reader.readAsDataURL(file);
        
        reader.onload = async function(e) {
            const img = new Image();
            img.src = e.target.result;
            
            img.onload = async function() {
                const originalSize = (file.size / 1024).toFixed(2);
                const needsCompression = file.size > 100 * 1024 || img.width > 600 || img.height > 600;
                
                let processedFile = file;
                
                if (needsCompression) {
                    showAlert('Compressing image (' + originalSize + 'KB → optimizing)...', 'info');
                    try {
                        processedFile = await compressImage(file, 600, 600, 0.85);
                        const newSize = (processedFile.size / 1024).toFixed(2);
                        const reduction = ((1 - processedFile.size / file.size) * 100).toFixed(0);
                        showAlert('Image compressed: ' + originalSize + 'KB → ' + newSize + 'KB (' + reduction + '% smaller)', 'success');
                    } catch (err) {
                        showAlert('Compression failed, using original: ' + err.message, 'warning');
                        console.error('Compression error:', err);
                    }
                } else {
                    showAlert('Image optimized (' + originalSize + 'KB)', 'success');
                }
                
                // Update the file input with compressed file
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(processedFile);
                event.target.files = dataTransfer.files;
                
                // Display preview
                const previewReader = new FileReader();
                previewReader.onload = function(pe) {
                    document.getElementById('image-preview').src = pe.target.result;
                    document.getElementById('image-preview').style.display = 'block';
                    document.getElementById('current-image').style.display = 'none';
                };
                previewReader.readAsDataURL(processedFile);
            };
            
            img.onerror = function() {
                showAlert('Error loading image preview. Please try another file.', 'error');
                event.target.value = '';
            };
        };
        
        reader.onerror = function() {
            showAlert('Error reading image file. Please try again.', 'error');
            event.target.value = '';
        };
        
    } catch (err) {
        showAlert('Error processing image: ' + err.message, 'error');
        event.target.value = '';
    }
}
function editCategory(id) {
openCategoryModal(id);
}
function editItem(id) {
openItemModal(id);
}
function saveCategory(event) {
event.preventDefault();
const formData = new FormData(event.target);
const action = document.getElementById('category-id').value ? 'update_category' : 'create_category';
fetch(`${BASE_PATH}/?req=staff&action=api&api_action=${action}`, {
method: 'POST',
body: formData,
credentials: 'include'
})
.then(r => r.json())
.then(data => {
if (data.status === 'OK') {
showAlert('Category saved successfully!', 'success');
closeCategoryModal();
reloadMenuData();
if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped'); // Reload to refresh data
} else {
showAlert(data.message || 'Error saving category', 'error');
}
})
.catch(err => {
showAlert('Error: ' + err.message, 'error');
});
}
function saveItem(event) {
event.preventDefault();
const formData = new FormData(event.target);
formData.append('restaurant_id', RESTAURANT_ID);
// Handle checkbox - ensure it sends a value
const isAvailable = document.getElementById('item-available').checked ? 1 : 0;
formData.set('is_available', isAvailable);

const action = document.getElementById('item-id').value ? 'update_menu_item' : 'create_menu_item';
showAlert('Saving menu item...', 'info');
fetch(`${BASE_PATH}/?req=staff&action=api&api_action=${action}`, {
method: 'POST',
body: formData,
credentials: 'include'
})
.then(function(r) {
if (!r.ok) {
throw new Error('Server error: ' + r.status);
}
return r.text();
})
.then(function(text) {
try {
var data = JSON.parse(text);
if (data.status === 'OK') {
showAlert('Menu item saved successfully!', 'success');
closeItemModal();
reloadMenuData();
if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped');
} else {
showAlert(data.message || 'Error saving menu item', 'error');
}
} catch(e) {
console.error('Invalid JSON response:', text.substring(0, 200));
showAlert('Server error: Invalid response format. Check console for details.', 'error');
}
})
.catch(function(err) {
showAlert('Error saving menu item: ' + err.message, 'error');
console.error('Save item error:', err);
});
}
function deleteCategory(id) {
if (!confirm('Are you sure you want to delete this category? This action cannot be undone.')) {
return;
}
const formData = new FormData();
formData.append('id', id);
fetch(`${BASE_PATH}/?req=staff&action=api&api_action=delete_category`, {
method: 'POST',
body: formData,
credentials: 'include'
})
.then(r => r.json())
.then(data => {
if (data.status === 'OK') {
showAlert('Category deleted successfully!', 'success');
reloadMenuData();
if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped');
} else {
showAlert(data.message || 'Error deleting category', 'error');
}
})
.catch(err => {
showAlert('Error: ' + err.message, 'error');
});
}
function deleteItem(id) {
if (!confirm('Are you sure you want to delete this menu item? This action cannot be undone.')) {
return;
}
const formData = new FormData();
formData.append('id', id);
fetch(`${BASE_PATH}/?req=staff&action=api&api_action=delete_menu_item`, {
method: 'POST',
body: formData,
credentials: 'include'
})
.then(r => r.json())
.then(data => {
if (data.status === 'OK') {
showAlert('Menu item deleted successfully!', 'success');
reloadMenuData();
if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped');
} else {
showAlert(data.message || 'Error deleting menu item', 'error');
}
})
.catch(err => {
showAlert('Error: ' + err.message, 'error');
});
}
function toggleAvailability(id, newStatus, categoryName) {
const item = items.find(i => i.uuid == id);
if (!item) return;
// For kitchen staff, check if it's a beverage
if (USER_ROLE === 'kitchen') {
const isBeverage = categoryName && categoryName.toLowerCase() === 'beverages';
if (isBeverage) {
showAlert('Kitchen staff cannot update beverage availability. Please contact a manager or admin.', 'error');
return;
}
const statusText = newStatus == 1 ? 'available' : 'unavailable';
const actionText = newStatus == 1 ? 'marking as available' : 'marking as unavailable';
// Custom styled prompt for reason
const dialogHTML = `
<div style="padding: 20px; text-align: left;">
<h3 style="margin: 0 0 15px 0; color: #2c3e50;">
<i class="fas fa-${newStatus == 1 ? 'check-circle' : 'times-circle'}" style="color: ${newStatus == 1 ? '#28a745' : '#dc3545'};"></i>
${newStatus == 1 ? 'Mark as Available' : 'Mark as Unavailable'}
</h3>
<p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">
<strong>Item:</strong> ${item.name}
</p>
<p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
<strong>Action:</strong> ${actionText.charAt(0).toUpperCase() + actionText.slice(1)}
</p>
<p style="margin: 0 0 10px 0; color: #666; font-size: 13px;">
<i class="fas fa-info-circle"></i> This will notify manager and owner
</p>
</div>
`;
const reason = prompt(`Why is "${item.name}" now ${statusText}?\\n\\nExamples:\\n• Out of ingredients\\n• Equipment issue\\n• Back in stock\\n• Quality issue\\n\\nReason (optional):`);
// If user cancels, don't proceed
if (reason === null) return;
// Show loading state
showAlert('Updating availability...', 'info');
fetch(`${BASE_PATH}/?req=api&action=kitchen_update_availability`, {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({
item_id: id,
is_available: newStatus == 1,
reason: reason || ''
}),
credentials: 'include'
})
.then(r => r.json())
.then(data => {
if (data.status === 'OK') {
showAlert('Item availability updated! Manager/Owner have been notified.', 'success');
reloadMenuData();
setTimeout(() => { if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped'); }, 1500);
} else {
showAlert((data.message || 'Error updating availability'), 'error');
}
})
.catch(err => {
showAlert('Network error: ' + err.message, 'error');
});
} else {
// For managers/admins, also use kitchen endpoint (it supports all roles)
showAlert('Updating availability...', 'info');
fetch(`${BASE_PATH}/?req=api&action=kitchen_update_availability`, {
method: 'POST',
headers: {'Content-Type': 'application/json'},
body: JSON.stringify({
item_id: id,
is_available: newStatus == 1,
reason: ''
}),
credentials: 'include'
})
.then(r => r.json())
.then(data => {
if (data.status === 'OK') {
showAlert('Item availability updated!', 'success');
reloadMenuData();
setTimeout(() => { if(window.parent.refreshDashboardStats) window.parent.refreshDashboardStats(); else console.log('Dashboard refresh skipped'); }, 1500);
} else {
showAlert((data.message || 'Error updating availability'), 'error');
}
})
.catch(err => {
showAlert('Error: ' + err.message, 'error');
});
}
}
function showAlert(message, type) {
const container = document.getElementById('alert-container');
const alert = document.createElement('div');
alert.className = `alert alert-${type}`;
alert.textContent = message;
container.appendChild(alert);
setTimeout(() => {
alert.remove();
}, 5000);
}
function escapeHtml(text) {
const div = document.createElement('div');
div.textContent = text;
return div.innerHTML;
}
function formatCurrency(amount) {
return new Intl.NumberFormat('en-RW', {
style: 'currency',
currency: 'RWF',
minimumFractionDigits: 0
}).format(amount);
}

// ==================== EXPORT/IMPORT FUNCTIONALITY ====================

// Export menu to Excel/CSV
async function exportMenu() {
try {
showLoading('Preparing export...');
const response = await fetch(`${BASE_URL}/?req=staff&action=api&api_action=export_menu&restaurant_id=${RESTAURANT_ID}`);
const data = await response.json();
hideLoading();

if (data.status === 'OK') {
// Create CSV content
const csvContent = createCSVContent(data.data);
            
// Download CSV file
const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
const link = document.createElement('a');
const url = URL.createObjectURL(blob);
const timestamp = new Date().toISOString().split('T')[0];
link.setAttribute('href', url);
link.setAttribute('download', `menu_export_${timestamp}.csv`);
link.style.visibility = 'hidden';
document.body.appendChild(link);
link.click();
document.body.removeChild(link);
            
showSuccess('Menu exported successfully!');
} else {
showError(data.message || 'Failed to export menu');
}
} catch (error) {
hideLoading();
showError('Error exporting menu: ' + error.message);
}
}

// Create CSV content from menu data
function createCSVContent(data) {
const headers = ['Category Name', 'Category Description', 'Item Name', 'Item Description', 'Price', 'Image URL', 'Available'];
let csv = headers.join(',') + '\n';
    
data.categories.forEach(function(category) {
const items = data.items.filter(function(item) { return item.category_uuid == category.uuid; });
        
if (items.length === 0) {
// Category with no items
csv += `"${escapeCSV(category.name)}","${escapeCSV(category.description || '')}","","","","",""\n`;
} else {
items.forEach(function(item) {
const row = [
escapeCSV(category.name),
escapeCSV(category.description || ''),
escapeCSV(item.name),
escapeCSV(item.description || ''),
item.price || '0',
item.image || '',
item.is_available == 1 ? 'Yes' : 'No'
];
csv += row.join(',') + '\n';
});
}
});
    
return csv;
}

// Escape CSV special characters
function escapeCSV(str) {
if (str == null) return '';
str = String(str);
if (str.indexOf(',') !== -1 || str.indexOf('"') !== -1 || str.indexOf('\n') !== -1) {
return '"' + str.replace(/"/g, '""') + '"';
}
return str;
}

// Open import modal
function openImportModal() {
document.getElementById('importModal').style.display = 'flex';
document.getElementById('importFile').value = '';
document.getElementById('importPreview').style.display = 'none';
document.getElementById('importResults').innerHTML = '';
}

// Close import modal
function closeImportModal() {
document.getElementById('importModal').style.display = 'none';
}

// Download template file
function downloadTemplate() {
const headers = ['Category Name', 'Category Description', 'Item Name', 'Item Description', 'Price', 'Image URL', 'Available'];
const exampleRow = ['Beverages', 'Hot and cold drinks', 'Coffee', 'Freshly brewed coffee', '2000', '', 'Yes'];
const csv = headers.join(',') + '\n' + exampleRow.join(',') + '\n';
    
const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
const link = document.createElement('a');
const url = URL.createObjectURL(blob);
link.setAttribute('href', url);
link.setAttribute('download', 'menu_template.csv');
link.style.visibility = 'hidden';
document.body.appendChild(link);
link.click();
document.body.removeChild(link);
}

// Preview import file
function previewImportFile() {
const fileInput = document.getElementById('importFile');
const file = fileInput.files[0];
    
if (!file) {
showError('Please select a file');
return;
}
    
const reader = new FileReader();
reader.onload = function(e) {
try {
const content = e.target.result;
const rows = parseCSV(content);
            
if (rows.length < 2) {
showError('File is empty or invalid');
return;
}
            
displayPreview(rows);
} catch (error) {
showError('Error reading file: ' + error.message);
}
};
reader.readAsText(file);
}

// Parse CSV content
function parseCSV(content) {
const lines = content.split('\n');
const rows = [];
    
for (let i = 0; i < lines.length; i++) {
const line = lines[i].trim();
if (line) {
rows.push(parseCSVLine(line));
}
}
    
return rows;
}

// Parse a single CSV line
function parseCSVLine(line) {
const cols = [];
let col = '';
let inQuotes = false;
    
for (let i = 0; i < line.length; i++) {
const char = line[i];
        
if (char === '"') {
if (inQuotes && line[i + 1] === '"') {
col += '"';
i++;
} else {
inQuotes = !inQuotes;
}
} else if (char === ',' && !inQuotes) {
cols.push(col.trim());
col = '';
} else {
col += char;
}
}
cols.push(col.trim());
    
return cols;
}

// Display preview of import data
function displayPreview(rows) {
const preview = document.getElementById('importPreview');
const tbody = document.getElementById('previewTableBody');
    
let html = '';
const dataRows = rows.slice(1);
    
for (let i = 0; i < Math.min(10, dataRows.length); i++) {
const row = dataRows[i];
html += `<tr>
<td>${escapeHTML(row[0] || '')}</td>
<td>${escapeHTML(row[2] || '')}</td>
<td>${escapeHTML(row[4] || '0')}</td>
<td>${escapeHTML(row[6] || 'Yes')}</td>
</tr>`;
}
    
tbody.innerHTML = html;
preview.style.display = 'block';
    
if (dataRows.length > 10) {
document.getElementById('importResults').innerHTML = 
`<p style="color: #666; font-style: italic;">Showing first 10 of ${dataRows.length} items...</p>`;
}
}

// Process import
async function processImport() {
const fileInput = document.getElementById('importFile');
const file = fileInput.files[0];
    
if (!file) {
showError('Please select a file');
return;
}
    
const reader = new FileReader();
reader.onload = async function(e) {
try {
showLoading('Importing menu data...');
            
const content = e.target.result;
const rows = parseCSV(content);
            
if (rows.length < 2) {
hideLoading();
showError('File is empty or invalid');
return;
}
            
// Send to server
const formData = new FormData();
formData.append('csv_data', content);
formData.append('restaurant_id', RESTAURANT_ID);
            
const response = await fetch(`${BASE_URL}/?req=staff&action=api&api_action=import_menu`, {
method: 'POST',
body: formData
});
            
const data = await response.json();
hideLoading();
            
if (data.status === 'OK') {
showSuccess(`Successfully imported: ${data.data.categories_created} categories, ${data.data.items_created} items`);
closeImportModal();
reloadMenuData();
} else {
showError(data.message || 'Import failed');
if (data.errors && data.errors.length > 0) {
document.getElementById('importResults').innerHTML = 
'<div style="color: #e74c3c; margin-top: 10px;"><strong>Errors:</strong><ul>' +
data.errors.map(function(err) { return '<li>' + err + '</li>'; }).join('') +
'</ul></div>';
}
}
} catch (error) {
hideLoading();
showError('Error importing: ' + error.message);
}
};
reader.readAsText(file);
}

// Escape HTML
function escapeHTML(str) {
if (str == null) return '';
const div = document.createElement('div');
div.textContent = str;
return div.innerHTML;
}

// Show loading overlay
function showLoading(message) {
let overlay = document.getElementById('loadingOverlay');
if (!overlay) {
overlay = document.createElement('div');
overlay.id = 'loadingOverlay';
overlay.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center; z-index: 99999;';
overlay.innerHTML = `<div style="background: white; padding: 30px; border-radius: 10px; text-align: center;">
<i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #3498db; margin-bottom: 15px;"></i>
<p style="margin: 0; font-size: 16px; color: #2c3e50;" id="loadingMessage">${message || 'Processing...'}</p>
</div>`;
document.body.appendChild(overlay);
} else {
overlay.style.display = 'flex';
document.getElementById('loadingMessage').textContent = message || 'Processing...';
}
}

// Hide loading overlay
function hideLoading() {
const overlay = document.getElementById('loadingOverlay');
if (overlay) {
overlay.style.display = 'none';
}
}

// Show success message
function showSuccess(message) {
showToast(message, 'success');
}

// Show error message
function showError(message) {
showToast(message, 'error');
}

// Show toast notification
function showToast(message, type) {
const toast = document.createElement('div');
toast.style.cssText = `
position: fixed;
top: 20px;
right: 20px;
background: ${type === 'success' ? '#27ae60' : '#e74c3c'};
color: white;
padding: 15px 25px;
border-radius: 8px;
box-shadow: 0 4px 12px rgba(0,0,0,0.3);
z-index: 100000;
font-size: 14px;
max-width: 400px;
animation: slideIn 0.3s ease;
`;
toast.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i> ${message}`;
document.body.appendChild(toast);
    
setTimeout(function() {
toast.style.animation = 'slideOut 0.3s ease';
setTimeout(function() {
document.body.removeChild(toast);
}, 300);
}, 3000);
}

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
