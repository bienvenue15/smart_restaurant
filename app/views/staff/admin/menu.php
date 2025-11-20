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
    </style>
</head>
<?php $isFragment = isset($_GET['fragment']) && $_GET['fragment'] === 'true'; ?>
<body class="staff-dashboard<?php echo $isFragment ? ' fragment-view' : ''; ?>">
    <?php 
    // Load sidebar dependencies
    require_once __DIR__ . '/../../../src/model.php';
    require_once __DIR__ . '/../../../app/core/Permission.php';
    require_once __DIR__ . '/../../../app/models/Staff.php';
    
    $staffModel = new Staff();
    $isOnShift = $staffModel->isOnShift($user['id']);
    $canHandleCash = Permission::check('handle_cash');
    $canApprove = Permission::check('approve_actions');
    
    // Get stats for sidebar badges
    $statsResult = $staffModel->getDashboardStats();
    $stats = $statsResult['status'] === 'OK' ? $statsResult['data'] : [];
    
    // Include sidebar when not in fragment mode
    if (!$isFragment) {
        include __DIR__ . '/../_sidebar.php';
    }
    ?>
    
    <div class="admin-container">
        <div class="page-header">
            <h1>
                <i class="fas fa-utensils"></i>
                Menu Management
            </h1>
        </div>
        
        <div id="alert-container"></div>
        
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
                    <button class="btn btn-primary" onclick="openCategoryModal()">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
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
                    <button class="btn btn-primary" onclick="openItemModal()">
                        <i class="fas fa-plus"></i> Add Menu Item
                    </button>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="items-table-body">
                            <tr class="loading"><td colspan="6">Loading menu items...</td></tr>
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
                        <label>Image</label>
                        <input type="file" class="form-control" id="item-image" name="image" accept="image/*" onchange="previewImage(event)">
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
    
    <script>
        const BASE_PATH = '<?php echo rtrim(BASE_URL, '/'); ?>';
        const RESTAURANT_ID = <?php echo $restaurant_id; ?>;
        
        let categories = <?php echo json_encode($categories); ?>;
        let items = <?php echo json_encode($items); ?>;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            renderCategories();
            renderItems();
            loadCategoriesForSelect();
        });
        
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
                        <button class="btn btn-warning" onclick="editCategory(${cat.id})" style="padding: 5px 10px; margin-right: 5px;">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger" onclick="deleteCategory(${cat.id})" style="padding: 5px 10px;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        function renderItems() {
            const tbody = document.getElementById('items-table-body');
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="no-data">No menu items found. Add your first item!</td></tr>';
                return;
            }
            
            tbody.innerHTML = items.map(item => {
                const imageUrl = item.image_url ? 
                    (item.image_url.startsWith('http') ? item.image_url : BASE_PATH + '/' + item.image_url) : 
                    BASE_PATH + '/assets/images/no-image.png';
                const categoryName = item.category_name || 'Uncategorized';
                const isAvailable = item.is_available == 1;
                
                return `
                    <tr>
                        <td><img src="${imageUrl}" class="item-image" alt="${escapeHtml(item.name)}"></td>
                        <td><strong>${escapeHtml(item.name)}</strong></td>
                        <td>${escapeHtml(categoryName)}</td>
                        <td>${formatCurrency(item.price)}</td>
                        <td>
                            <span class="badge ${isAvailable ? 'badge-success' : 'badge-danger'}">
                                ${isAvailable ? 'Available' : 'Unavailable'}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-warning" onclick="editItem(${item.id})" style="padding: 5px 10px; margin-right: 5px;">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn ${isAvailable ? 'btn-warning' : 'btn-success'}" onclick="toggleAvailability(${item.id}, ${isAvailable ? 0 : 1})" style="padding: 5px 10px; margin-right: 5px;">
                                <i class="fas fa-${isAvailable ? 'eye-slash' : 'eye'}"></i>
                            </button>
                            <button class="btn btn-danger" onclick="deleteItem(${item.id})" style="padding: 5px 10px;">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        function loadCategoriesForSelect() {
            const select = document.getElementById('item-category');
            select.innerHTML = '<option value="">Select Category</option>' +
                categories.map(cat => 
                    `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`
                ).join('');
        }
        
        function openCategoryModal(id = null) {
            const modal = document.getElementById('category-modal');
            const form = document.getElementById('category-form');
            const title = document.getElementById('category-modal-title');
            
            if (id) {
                const cat = categories.find(c => c.id == id);
                if (cat) {
                    document.getElementById('category-id').value = cat.id;
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
                const item = items.find(i => i.id == id);
                if (item) {
                    document.getElementById('item-id').value = item.id;
                    document.getElementById('item-category').value = item.category_id;
                    document.getElementById('item-name').value = item.name;
                    document.getElementById('item-description').value = item.description || '';
                    document.getElementById('item-price').value = item.price;
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
        
        function previewImage(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('image-preview').src = e.target.result;
                    document.getElementById('image-preview').style.display = 'block';
                    document.getElementById('current-image').style.display = 'none';
                };
                reader.readAsDataURL(file);
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
                    location.reload(); // Reload to refresh data
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
            const action = document.getElementById('item-id').value ? 'update_menu_item' : 'create_menu_item';
            
            fetch(`${BASE_PATH}/?req=staff&action=api&api_action=${action}`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('Menu item saved successfully!', 'success');
                    closeItemModal();
                    location.reload(); // Reload to refresh data
                } else {
                    showAlert(data.message || 'Error saving menu item', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
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
                    location.reload();
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
                    location.reload();
                } else {
                    showAlert(data.message || 'Error deleting menu item', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
        }
        
        function toggleAvailability(id, newStatus) {
            const item = items.find(i => i.id == id);
            if (!item) return;
            
            const formData = new FormData();
            formData.append('id', id);
            formData.append('category_id', item.category_id);
            formData.append('name', item.name);
            formData.append('description', item.description || '');
            formData.append('price', item.price);
            formData.append('is_available', newStatus);
            formData.append('display_order', item.display_order || 0);
            
            fetch(`${BASE_PATH}/?req=staff&action=api&api_action=update_menu_item`, {
                method: 'POST',
                body: formData,
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert('Item availability updated!', 'success');
                    location.reload();
                } else {
                    showAlert(data.message || 'Error updating availability', 'error');
                }
            })
            .catch(err => {
                showAlert('Error: ' + err.message, 'error');
            });
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
    </script>
</body>
</html>

