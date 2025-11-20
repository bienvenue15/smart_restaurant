<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Restaurant Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 260px;
            background: #2c3e50;
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            background: #34495e;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
        }
        
        .sidebar-menu li {
            margin: 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #3498db;
        }
        
        .sidebar-menu a.active {
            background: rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
        }
        
        .sidebar-menu i {
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: margin-left 0.3s;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 1.5rem;
        }
        
        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-header h2 {
            color: #333;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .restaurants-table {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e9ecef;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-trial {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-basic {
            background: #cce5ff;
            color: #004085;
        }
        
        .badge-premium {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-enterprise {
            background: #e2d9f3;
            color: #4a148c;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.85rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        
        .btn-sm:hover {
            opacity: 0.8;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .loading {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>🔐 Super Admin Dashboard</h1>
        <div class="user-info">
            <span id="userEmail"></span>
            <button class="btn-logout" onclick="logout()">Logout</button>
        </div>
    </nav>
    
    <div class="container">
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <h3>Total Restaurants</h3>
                <div class="value" id="totalRestaurants">-</div>
            </div>
            <div class="stat-card">
                <h3>Active Restaurants</h3>
                <div class="value" id="activeRestaurants">-</div>
            </div>
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value" id="totalUsers">-</div>
            </div>
            <div class="stat-card">
                <h3>Today's Revenue</h3>
                <div class="value" id="todayRevenue">-</div>
            </div>
        </div>
        
        <div class="section-header">
            <h2>All Restaurants</h2>
            <button class="btn-primary" onclick="alert('Create restaurant feature coming soon!')">+ Add Restaurant</button>
        </div>
        
        <div id="errorMessage"></div>
        
        <div class="restaurants-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Users</th>
                        <th>Tables</th>
                        <th>Menu Items</th>
                        <th>Orders</th>
                        <th>Revenue Today</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="restaurantsBody">
                    <tr>
                        <td colspan="11" class="loading">Loading restaurants...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Get user info from session
        const userEmail = '<?php echo $_SESSION['email'] ?? 'Admin'; ?>';
        document.getElementById('userEmail').textContent = userEmail;
        
        // Load restaurants on page load
        loadRestaurants();
        
        async function loadRestaurants() {
            try {
                const pathParts = window.location.pathname.split('/').filter(p => p);
                const basePath = pathParts.length > 0 ? '/' + pathParts[0] + '/' : '/';
                const response = await fetch(basePath + '?req=superadmin&action=list_restaurants&format=json', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const result = await response.json();
                
                if (result.status === 'OK') {
                    displayRestaurants(result.data);
                    updateStats(result.data);
                } else {
                    showError(result.message || 'Failed to load restaurants');
                }
            } catch (error) {
                console.error('Error loading restaurants:', error);
                showError('Failed to load restaurants. Please try again.');
            }
        }
        
        function displayRestaurants(restaurants) {
            const tbody = document.getElementById('restaurantsBody');
            
            if (restaurants.length === 0) {
                tbody.innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 2rem; color: #666;">No restaurants found</td></tr>';
                return;
            }
            
            tbody.innerHTML = restaurants.map(r => `
                <tr>
                    <td>${r.id}</td>
                    <td><strong>${r.name}</strong><br><small style="color: #666;">${r.slug}</small></td>
                    <td>${r.email}</td>
                    <td><span class="badge badge-${r.subscription_plan}">${r.subscription_plan.toUpperCase()}</span></td>
                    <td><span class="badge badge-${r.is_active ? 'active' : 'inactive'}">${r.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>${r.stats.total_users || 0}</td>
                    <td>${r.stats.total_tables || 0}</td>
                    <td>${r.stats.total_menu_items || 0}</td>
                    <td>${r.stats.total_orders || 0}</td>
                    <td>${formatCurrency(r.stats.today_revenue || 0)}</td>
                    <td>
                        <div class="actions">
                            <button class="btn-sm btn-info" onclick="viewRestaurant(${r.id})" title="View Details">👁️</button>
                            <button class="btn-sm btn-warning" onclick="editRestaurant(${r.id})" title="Edit">✏️</button>
                            <button class="btn-sm btn-danger" onclick="deleteRestaurant(${r.id}, '${r.name}')" title="Delete">🗑️</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        function updateStats(restaurants) {
            const totalRestaurants = restaurants.length;
            const activeRestaurants = restaurants.filter(r => r.is_active).length;
            const totalUsers = restaurants.reduce((sum, r) => sum + (parseInt(r.stats.total_users) || 0), 0);
            const todayRevenue = restaurants.reduce((sum, r) => sum + (parseFloat(r.stats.today_revenue) || 0), 0);
            
            document.getElementById('totalRestaurants').textContent = totalRestaurants;
            document.getElementById('activeRestaurants').textContent = activeRestaurants;
            document.getElementById('totalUsers').textContent = totalUsers;
            document.getElementById('todayRevenue').textContent = formatCurrency(todayRevenue);
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-RW', {
                style: 'currency',
                currency: 'RWF',
                minimumFractionDigits: 0
            }).format(amount);
        }
        
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.innerHTML = `<div class="error">${message}</div>`;
            document.getElementById('restaurantsBody').innerHTML = '<tr><td colspan="11" style="text-align: center; padding: 2rem; color: #dc3545;">Failed to load data</td></tr>';
        }
        
        function viewRestaurant(id) {
            alert(`View restaurant ${id} - Feature coming soon!`);
        }
        
        function editRestaurant(id) {
            alert(`Edit restaurant ${id} - Feature coming soon!`);
        }
        
        function deleteRestaurant(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone!`)) {
                alert(`Delete restaurant ${id} - Feature coming soon!`);
            }
        }
        
        async function logout() {
            const pathParts = window.location.pathname.split('/').filter(p => p);
            const basePath = pathParts.length > 0 ? '/' + pathParts[0] + '/' : '/';
            
            try {
                await fetch(basePath + '?req=superadmin&action=logout', { method: 'POST' });
            } catch (e) {
                console.error('Logout error:', e);
            }
            
            window.location.href = basePath + '?req=superadmin';
        }
    </script>
</body>
</html>
