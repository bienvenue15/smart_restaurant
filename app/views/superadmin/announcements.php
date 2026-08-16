<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Announcement Management - SuperAdmin</title>
<link rel="stylesheet" href="/restaurant/assets/css/style.css">
<style>
* {
margin: 0;
padding: 0;
box-sizing: border-box;
}
body {
font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
min-height: 100vh;
padding: 20px;
}
.container {
max-width: 1400px;
margin: 0 auto;
}
.header {
background: white;
padding: 20px 30px;
border-radius: 15px;
box-shadow: 0 5px 20px rgba(0,0,0,0.1);
margin-bottom: 30px;
display: flex;
justify-content: space-between;
align-items: center;
flex-wrap: wrap;
gap: 15px;
}
.header h1 {
color: #333;
font-size: 28px;
font-weight: 600;
}
.header-actions {
display: flex;
gap: 10px;
flex-wrap: wrap;
}
.btn {
padding: 10px 20px;
border: none;
border-radius: 8px;
cursor: pointer;
font-size: 14px;
font-weight: 500;
transition: all 0.3s;
text-decoration: none;
display: inline-flex;
align-items: center;
gap: 8px;
}
.btn-primary {
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
color: white;
}
.btn-primary:hover {
transform: translateY(-2px);
box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}
.btn-secondary {
background: #f3f4f6;
color: #555;
}
.btn-secondary:hover {
background: #e5e7eb;
}
.btn-danger {
background: #ef4444;
color: white;
}
.btn-success {
background: #10b981;
color: white;
}
.btn-warning {
background: #f59e0b;
color: white;
}
.stats-grid {
display: grid;
grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
gap: 20px;
margin-bottom: 30px;
}
.stat-card {
background: white;
padding: 25px;
border-radius: 15px;
box-shadow: 0 5px 20px rgba(0,0,0,0.1);
display: flex;
justify-content: space-between;
align-items: center;
}
.stat-info h3 {
color: #888;
font-size: 14px;
font-weight: 500;
margin-bottom: 8px;
}
.stat-info p {
color: #333;
font-size: 32px;
font-weight: 700;
}
.stat-icon {
width: 60px;
height: 60px;
border-radius: 12px;
display: flex;
align-items: center;
justify-content: center;
font-size: 28px;
}
.stat-total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
.stat-active { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
.stat-inactive { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); }
.stat-broadcast { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.main-content {
background: white;
border-radius: 15px;
box-shadow: 0 5px 20px rgba(0,0,0,0.1);
padding: 30px;
}
.filters {
display: flex;
gap: 15px;
margin-bottom: 25px;
flex-wrap: wrap;
}
.filter-group {
display: flex;
flex-direction: column;
gap: 5px;
}
.filter-group label {
color: #666;
font-size: 13px;
font-weight: 500;
}
.filter-group select,
.filter-group input {
padding: 8px 12px;
border: 1px solid #e5e7eb;
border-radius: 6px;
font-size: 14px;
min-width: 150px;
}
.announcements-list {
display: flex;
flex-direction: column;
gap: 15px;
}
.announcement-card {
border: 1px solid #e5e7eb;
border-radius: 12px;
padding: 20px;
transition: all 0.3s;
position: relative;
}
.announcement-card:hover {
box-shadow: 0 5px 15px rgba(0,0,0,0.08);
}
.announcement-header {
display: flex;
justify-content: space-between;
align-items: start;
margin-bottom: 15px;
flex-wrap: wrap;
gap: 10px;
}
.announcement-title {
flex: 1;
min-width: 200px;
}
.announcement-title h3 {
color: #333;
font-size: 18px;
font-weight: 600;
margin-bottom: 5px;
}
.announcement-badges {
display: flex;
gap: 8px;
flex-wrap: wrap;
}
.badge {
padding: 4px 12px;
border-radius: 20px;
font-size: 12px;
font-weight: 500;
}
.badge-info { background: #dbeafe; color: #1e40af; }
.badge-warning { background: #fef3c7; color: #92400e; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-danger { background: #fee2e2; color: #991b1b; }
.badge-promotion { background: #f3e8ff; color: #6b21a8; }
.badge-staff { background: #e0e7ff; color: #3730a3; }
.badge-customers { background: #fce7f3; color: #831843; }
.badge-admins { background: #fed7aa; color: #9a3412; }
.badge-all { background: #e5e7eb; color: #374151; }
.badge-urgent { background: #fecaca; color: #991b1b; font-weight: 600; }
.badge-high { background: #fed7aa; color: #9a3412; }
.badge-normal { background: #dbeafe; color: #1e40af; }
.badge-low { background: #e5e7eb; color: #6b7280; }
.announcement-actions {
display: flex;
gap: 8px;
}
.btn-sm {
padding: 6px 12px;
font-size: 13px;
}
.announcement-message {
color: #555;
line-height: 1.6;
margin-bottom: 15px;
}
.announcement-meta {
display: flex;
gap: 20px;
flex-wrap: wrap;
color: #888;
font-size: 13px;
}
.meta-item {
display: flex;
align-items: center;
gap: 5px;
}
.status-inactive {
opacity: 0.6;
border-left: 4px solid #ef4444;
}
.status-active {
border-left: 4px solid #10b981;
}
/* Modal */
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
padding: 20px;
}
.modal.active {
display: flex;
}
.modal-content {
background: white;
border-radius: 15px;
padding: 30px;
max-width: 600px;
width: 100%;
max-height: 90vh;
overflow-y: auto;
}
.modal-header {
display: flex;
justify-content: space-between;
align-items: center;
margin-bottom: 25px;
}
.modal-header h2 {
font-size: 24px;
color: #333;
}
.close-modal {
background: none;
border: none;
font-size: 28px;
color: #888;
cursor: pointer;
padding: 0;
width: 30px;
height: 30px;
}
.form-group {
margin-bottom: 20px;
}
.form-group label {
display: block;
margin-bottom: 8px;
color: #333;
font-weight: 500;
font-size: 14px;
}
.form-group input,
.form-group select,
.form-group textarea {
width: 100%;
padding: 10px 15px;
border: 1px solid #e5e7eb;
border-radius: 8px;
font-size: 14px;
font-family: inherit;
}
.form-group textarea {
min-height: 100px;
resize: vertical;
}
.form-group small {
display: block;
margin-top: 5px;
color: #888;
font-size: 12px;
}
.form-row {
display: grid;
grid-template-columns: 1fr 1fr;
gap: 15px;
}
.checkbox-group {
display: flex;
align-items: center;
gap: 10px;
}
.checkbox-group input[type="checkbox"] {
width: auto;
}
.modal-actions {
display: flex;
gap: 10px;
justify-content: flex-end;
margin-top: 25px;
}
.empty-state {
text-align: center;
padding: 60px 20px;
color: #888;
}
.empty-state svg {
width: 80px;
height: 80px;
margin-bottom: 20px;
opacity: 0.3;
}
/* Responsive */
@media (max-width: 768px) {
.header {
flex-direction: column;
align-items: stretch;
}
.header h1 {
font-size: 22px;
}
.header-actions {
justify-content: stretch;
}
.header-actions .btn {
flex: 1;
justify-content: center;
}
.stats-grid {
grid-template-columns: 1fr;
}
.form-row {
grid-template-columns: 1fr;
}
.announcement-header {
flex-direction: column;
}
.announcement-actions {
width: 100%;
justify-content: stretch;
}
.announcement-actions .btn-sm {
flex: 1;
}
}
</style>
</head>
<body>
<div class="container">
<div class="header">
<h1><i class="fas fa-bullhorn"></i> Announcement Management</h1>
<div class="header-actions">
<button class="btn btn-primary" onclick="openCreateModal()">
<i class="fas fa-plus"></i> Create Announcement
</button>
<a href="?req=superadmin&action=dashboard" class="btn btn-secondary">
<i class="fas fa-home"></i> Dashboard
</a>
</div>
</div>
<div class="stats-grid" id="statsGrid">
<!-- Stats will be loaded here -->
</div>
<div class="main-content">
<div class="filters">
<div class="filter-group">
<label>Type</label>
<select id="filterType" onchange="loadAnnouncements()">
<option value="">All Types</option>
<option value="info">Info</option>
<option value="warning">Warning</option>
<option value="success">Success</option>
<option value="danger">Danger</option>
<option value="promotion">Promotion</option>
</select>
</div>
<div class="filter-group">
<label>Audience</label>
<select id="filterAudience" onchange="loadAnnouncements()">
<option value="">All Audiences</option>
<option value="all">All Users</option>
<option value="staff">Staff</option>
<option value="customers">Customers</option>
<option value="admins">Admins</option>
</select>
</div>
<div class="filter-group">
<label>Status</label>
<select id="filterStatus" onchange="loadAnnouncements()">
<option value="">All Status</option>
<option value="1">Active</option>
<option value="0">Inactive</option>
</select>
</div>
<div class="filter-group">
<label>Search</label>
<input type="text" id="filterSearch" placeholder="Search title..." onkeyup="loadAnnouncements()">
</div>
</div>
<div class="announcements-list" id="announcementsList">
<!-- Announcements will be loaded here -->
</div>
</div>
</div>
<!-- Create/Edit Modal -->
<div class="modal" id="announcementModal">
<div class="modal-content">
<div class="modal-header">
<h2 id="modalTitle">Create Announcement</h2>
<button class="close-modal" onclick="closeModal()">&times;</button>
</div>
<form id="announcementForm">
<input type="hidden" id="announcementId">
<div class="form-group">
<label>Title *</label>
<input type="text" id="title" required maxlength="255">
</div>
<div class="form-group">
<label>Message *</label>
<textarea id="message" required></textarea>
</div>
<div class="form-row">
<div class="form-group">
<label>Type *</label>
<select id="type" required>
<option value="info">Info</option>
<option value="warning">Warning</option>
<option value="success">Success</option>
<option value="danger">Danger</option>
<option value="promotion">Promotion</option>
</select>
</div>
<div class="form-group">
<label>Priority *</label>
<select id="priority" required>
<option value="normal">Normal</option>
<option value="low">Low</option>
<option value="high">High</option>
<option value="urgent">Urgent</option>
</select>
</div>
</div>
<div class="form-row">
<div class="form-group">
<label>Target Audience *</label>
<select id="targetAudience" required>
<option value="all">All Users</option>
<option value="staff">Staff Only</option>
<option value="customers">Customers Only</option>
<option value="admins">Admins Only</option>
</select>
</div>
<div class="form-group">
<label>Restaurant</label>
<select id="restaurantId">
<option value="">All Restaurants (Broadcast)</option>
<!-- Restaurants will be loaded -->
</select>
<small>Leave empty to broadcast to all restaurants</small>
</div>
</div>
<div class="form-row">
<div class="form-group">
<label>Start Date</label>
<input type="datetime-local" id="startDate">
<small>Optional: When to start showing</small>
</div>
<div class="form-group">
<label>End Date</label>
<input type="datetime-local" id="endDate">
<small>Optional: When to stop showing</small>
</div>
</div>
<div class="form-group">
<div class="checkbox-group">
<input type="checkbox" id="isDismissible" checked>
<label for="isDismissible">Allow users to dismiss this announcement</label>
</div>
</div>
<div class="form-group">
<div class="checkbox-group">
<input type="checkbox" id="isActive" checked>
<label for="isActive">Active</label>
</div>
</div>
<div class="modal-actions">
<button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
<button type="submit" class="btn btn-primary">Save Announcement</button>
</div>
</form>
</div>
</div>
<script>
let announcements = [];
let restaurants = [];
// Load data on page load
document.addEventListener('DOMContentLoaded', () => {
loadStats();
loadRestaurants();
loadAnnouncements();
});
// Load statistics
async function loadStats() {
try {
const response = await fetch('?req=superadmin&action=get_announcement_stats');
const result = await response.json();
if (result.status === 'OK') {
const stats = result.data;
document.getElementById('statsGrid').innerHTML = `
<div class="stat-card">
<div class="stat-info">
<h3>Total Announcements</h3>
<p>${stats.total}</p>
</div>
<div class="stat-icon stat-total"><i class="fas fa-bullhorn"></i></div>
</div>
<div class="stat-card">
<div class="stat-info">
<h3>Active</h3>
<p>${stats.active}</p>
</div>
<div class="stat-icon stat-active"><i class="fas fa-check-circle"></i></div>
</div>
<div class="stat-card">
<div class="stat-info">
<h3>Inactive</h3>
<p>${stats.inactive}</p>
</div>
<div class="stat-icon stat-inactive"><i class="fas fa-pause-circle"></i></div>
</div>
<div class="stat-card">
<div class="stat-info">
<h3>Broadcast</h3>
<p>${stats.broadcast}</p>
</div>
<div class="stat-icon stat-broadcast"><i class="fas fa-satellite-dish"></i></div>
</div>
`;
}
} catch (error) {
}
}
// Load restaurants for dropdown
async function loadRestaurants() {
try {
const response = await fetch('?req=superadmin&action=get_restaurants');
if (!response.ok) {
return;
}
const contentType = response.headers.get('content-type');
if (!contentType || !contentType.includes('application/json')) {
const text = await response.text();
return;
}
const result = await response.json();
if (result.status === 'OK') {
restaurants = result.data;
const select = document.getElementById('restaurantId');
restaurants.forEach(r => {
const option = document.createElement('option');
option.value = r.id;
option.textContent = r.name;
select.appendChild(option);
});
} else {
}
} catch (error) {
}
}
// Load announcements
async function loadAnnouncements() {
try {
const response = await fetch('?req=superadmin&action=get_announcements');
const result = await response.json();
if (result.status === 'OK') {
announcements = result.data;
renderAnnouncements();
}
} catch (error) {
}
}
// Render announcements with filters
function renderAnnouncements() {
const filterType = document.getElementById('filterType').value;
const filterAudience = document.getElementById('filterAudience').value;
const filterStatus = document.getElementById('filterStatus').value;
const filterSearch = document.getElementById('filterSearch').value.toLowerCase();
let filtered = announcements.filter(a => {
if (filterType && a.type !== filterType) return false;
if (filterAudience && a.target_audience !== filterAudience) return false;
if (filterStatus !== '' && a.is_active != filterStatus) return false;
if (filterSearch && !a.title.toLowerCase().includes(filterSearch)) return false;
return true;
});
const container = document.getElementById('announcementsList');
if (filtered.length === 0) {
container.innerHTML = `
<div class="empty-state">
<svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
</svg>
<h3>No announcements found</h3>
<p>Create your first announcement to get started</p>
</div>
`;
return;
}
container.innerHTML = filtered.map(a => `
<div class="announcement-card status-${a.is_active ? 'active' : 'inactive'}">
<div class="announcement-header">
<div class="announcement-title">
<h3>${escapeHtml(a.title)}</h3>
<div class="announcement-badges">
<span class="badge badge-${a.type}">${a.type.toUpperCase()}</span>
<span class="badge badge-${a.target_audience}">${a.target_audience.toUpperCase()}</span>
<span class="badge badge-${a.priority}">${a.priority.toUpperCase()}</span>
${a.restaurant_name ? `<span class="badge" style="background: #fef3c7; color: #92400e;"><i class="fas fa-store"></i> ${escapeHtml(a.restaurant_name)}</span>` : '<span class="badge" style="background: #dbeafe; color: #1e40af;"><i class="fas fa-satellite-dish"></i> BROADCAST</span>'}
</div>
</div>
<div class="announcement-actions">
<button class="btn btn-sm btn-secondary" onclick="editAnnouncement(${a.id})"><i class="fas fa-edit"></i> Edit</button>
<button class="btn btn-sm ${a.is_active ? 'btn-warning' : 'btn-success'}" onclick="toggleStatus(${a.id})">
${a.is_active ? '<i class="fas fa-pause"></i> Deactivate' : '<i class="fas fa-play"></i> Activate'}
</button>
<button class="btn btn-sm btn-danger" onclick="deleteAnnouncement(${a.id})"><i class="fas fa-trash"></i> Delete</button>
</div>
</div>
<div class="announcement-message">
${escapeHtml(a.message)}
</div>
<div class="announcement-meta">
${a.start_date ? `<span class="meta-item"><i class="fas fa-calendar-alt"></i> Starts: ${formatDate(a.start_date)}</span>` : ''}
${a.end_date ? `<span class="meta-item"><i class="fas fa-flag-checkered"></i> Ends: ${formatDate(a.end_date)}</span>` : ''}
<span class="meta-item">${a.is_dismissible ? '<i class="fas fa-check-square"></i> Dismissible' : '<i class="fas fa-lock"></i> Non-dismissible'}</span>
<span class="meta-item"><i class="fas fa-eye"></i> ${a.dismissal_count || 0} dismissals</span>
<span class="meta-item"><i class="fas fa-clock"></i> ${formatDate(a.created_at)}</span>
</div>
</div>
`).join('');
}
// Open create modal
function openCreateModal() {
document.getElementById('modalTitle').textContent = 'Create Announcement';
document.getElementById('announcementForm').reset();
document.getElementById('announcementId').value = '';
document.getElementById('isDismissible').checked = true;
document.getElementById('isActive').checked = true;
document.getElementById('announcementModal').classList.add('active');
}
// Edit announcement
function editAnnouncement(id) {
const announcement = announcements.find(a => a.id == id);
if (!announcement) return;
document.getElementById('modalTitle').textContent = 'Edit Announcement';
document.getElementById('announcementId').value = announcement.id;
document.getElementById('title').value = announcement.title;
document.getElementById('message').value = announcement.message;
document.getElementById('type').value = announcement.type;
document.getElementById('priority').value = announcement.priority;
document.getElementById('targetAudience').value = announcement.target_audience;
document.getElementById('restaurantId').value = announcement.restaurant_id || '';
document.getElementById('startDate').value = announcement.start_date ? announcement.start_date.replace(' ', 'T').substring(0, 16) : '';
document.getElementById('endDate').value = announcement.end_date ? announcement.end_date.replace(' ', 'T').substring(0, 16) : '';
document.getElementById('isDismissible').checked = announcement.is_dismissible == 1;
document.getElementById('isActive').checked = announcement.is_active == 1;
document.getElementById('announcementModal').classList.add('active');
}
// Close modal
function closeModal() {
document.getElementById('announcementModal').classList.remove('active');
}
// Handle form submission
document.getElementById('announcementForm').addEventListener('submit', async (e) => {
e.preventDefault();
const id = document.getElementById('announcementId').value;
const data = {
id: id || undefined,
title: document.getElementById('title').value,
message: document.getElementById('message').value,
type: document.getElementById('type').value,
priority: document.getElementById('priority').value,
target_audience: document.getElementById('targetAudience').value,
restaurant_id: document.getElementById('restaurantId').value || null,
start_date: document.getElementById('startDate').value || null,
end_date: document.getElementById('endDate').value || null,
is_dismissible: document.getElementById('isDismissible').checked ? 1 : 0,
is_active: document.getElementById('isActive').checked ? 1 : 0,
created_by: 0 // SuperAdmin
};
try {
const action = id ? 'update_announcement' : 'create_announcement';
const response = await fetch(`?req=superadmin&action=${action}`, {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify(data)
});
// Check if response is OK
if (!response.ok) {
const text = await response.text();
alert(`Server error (${response.status}): ${text.substring(0, 200)}`);
return;
}
const contentType = response.headers.get('content-type');
if (!contentType || !contentType.includes('application/json')) {
const text = await response.text();
alert('Server returned invalid response. Check console for details.');
return;
}
const result = await response.json();
if (result.status === 'OK') {
alert(result.message);
closeModal();
loadStats();
loadAnnouncements();
} else {
alert(result.message || 'Operation failed');
}
} catch (error) {
alert('Failed to save announcement: ' + error.message);
}
});
// Toggle announcement status
async function toggleStatus(id) {
if (!confirm('Toggle announcement status?')) return;
try {
const response = await fetch('?req=superadmin&action=toggle_announcement', {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify({ id })
});
const result = await response.json();
if (result.status === 'OK') {
loadStats();
loadAnnouncements();
} else {
alert(result.message || 'Operation failed');
}
} catch (error) {
alert('Failed to toggle status');
}
}
// Delete announcement
async function deleteAnnouncement(id) {
if (!confirm('Are you sure you want to delete this announcement? This action cannot be undone.')) return;
try {
const response = await fetch('?req=superadmin&action=delete_announcement', {
method: 'POST',
headers: { 'Content-Type': 'application/json' },
body: JSON.stringify({ id })
});
const result = await response.json();
if (result.status === 'OK') {
loadStats();
loadAnnouncements();
} else {
alert(result.message || 'Operation failed');
}
} catch (error) {
alert('Failed to delete announcement');
}
}
// Utility functions
function escapeHtml(text) {
const map = {
'&': '&amp;',
'<': '&lt;',
'>': '&gt;',
'"': '&quot;',
"'": '&#039;'
};
return text.replace(/[&<>"']/g, m => map[m]);
}
function formatDate(dateStr) {
if (!dateStr) return '';
const date = new Date(dateStr);
return date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
</script>
</body>
</html>
