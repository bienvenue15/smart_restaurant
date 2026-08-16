<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Plans Management - Super Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 28px; margin-bottom: 5px; }
        .header p { opacity: 0.9; }
        .back-link { display: inline-block; margin-bottom: 15px; color: white; text-decoration: none; opacity: 0.9; }
        .back-link:hover { opacity: 1; }
        
        .container { max-width: 1400px; margin: 40px auto; padding: 0 20px; }
        
        .actions-bar {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.3s;
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
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success { background: #10b981; color: white; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-secondary { background: #6b7280; color: white; }
        
        .plans-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
        }
        
        .plan-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        .plan-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            border-color: #667eea;
        }
        
        .plan-header {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
        }
        .plan-name {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .plan-description {
            color: #6b7280;
            font-size: 14px;
        }
        
        .plan-price {
            display: flex;
            align-items: baseline;
            gap: 8px;
            margin: 16px 0;
        }
        .price-amount {
            font-size: 32px;
            font-weight: 700;
            color: #667eea;
        }
        .price-period {
            color: #6b7280;
            font-size: 14px;
        }
        
        .plan-limits {
            margin: 20px 0;
        }
        .limit-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .limit-label {
            color: #6b7280;
            font-size: 14px;
        }
        .limit-value {
            color: #1f2937;
            font-weight: 600;
        }
        .unlimited {
            color: #10b981;
        }
        
        .plan-features {
            margin: 20px 0;
        }
        .feature-tag {
            display: inline-block;
            padding: 4px 12px;
            background: #e0e7ff;
            color: #4338ca;
            border-radius: 12px;
            font-size: 12px;
            margin: 4px;
        }
        
        .plan-actions {
            display: flex;
            gap: 8px;
            margin-top: 20px;
        }
        .plan-actions .btn {
            flex: 1;
            justify-content: center;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            align-items: center;
            justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            margin-bottom: 24px;
        }
        .modal-header h2 {
            font-size: 24px;
            color: #1f2937;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Mobile Responsive Design */
        @media (max-width: 1200px) {
            .plans-grid {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 20px;
            }
        }
        
        @media (max-width: 992px) {
            .header {
                padding: 18px 30px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .container {
                padding: 0 16px;
                margin: 30px auto;
            }
            
            .plans-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 18px;
            }
            
            .actions-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
            }
            
            .btn-primary {
                width: 100%;
                justify-content: center;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                padding: 16px 20px;
            }
            
            .header h1 {
                font-size: 20px;
            }
            
            .header p {
                font-size: 13px;
            }
            
            .container {
                padding: 0 12px;
                margin: 24px auto;
            }
            
            .actions-bar {
                padding: 16px;
            }
            
            .plans-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .plan-card {
                padding: 20px;
            }
            
            .plan-name {
                font-size: 20px;
            }
            
            .price-amount {
                font-size: 28px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .modal-content {
                padding: 24px;
                width: 95%;
            }
            
            .modal-header h2 {
                font-size: 20px;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 12px 16px;
            }
            
            .header h1 {
                font-size: 18px;
            }
            
            .header h1 i {
                display: none;
            }
            
            .container {
                padding: 0 10px;
                margin: 20px auto;
            }
            
            .actions-bar {
                padding: 14px;
            }
            
            .actions-bar h3 {
                font-size: 16px;
            }
            
            .actions-bar p {
                font-size: 12px;
            }
            
            .btn {
                padding: 10px 18px;
                font-size: 14px;
            }
            
            .plan-card {
                padding: 16px;
            }
            
            .plan-name {
                font-size: 18px;
            }
            
            .plan-description {
                font-size: 13px;
            }
            
            .price-amount {
                font-size: 24px;
            }
            
            .price-period {
                font-size: 13px;
            }
            
            .limit-label,
            .limit-value {
                font-size: 13px;
            }
            
            .feature-tag {
                font-size: 11px;
                padding: 3px 10px;
            }
            
            .plan-actions {
                flex-direction: column;
            }
            
            .plan-actions .btn {
                padding: 8px 16px;
                font-size: 13px;
            }
            
            .modal-content {
                padding: 20px;
                width: 96%;
            }
            
            .modal-header h2 {
                font-size: 18px;
            }
            
            .form-group label {
                font-size: 14px;
            }
            
            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 10px;
                font-size: 14px;
            }
        }
        
        @media (max-width: 360px) {
            .header {
                padding: 10px 12px;
            }
            
            .header h1 {
                font-size: 16px;
            }
            
            .plan-card {
                padding: 14px;
            }
            
            .plan-name {
                font-size: 16px;
            }
            
            .price-amount {
                font-size: 20px;
            }
            
            .btn {
                padding: 8px 14px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <a href="<?php echo BASE_URL; ?>/?req=superadmin&action=dashboard" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <h1><i class="fas fa-boxes"></i> Subscription Plans Management</h1>
        <p>Manage subscription tiers and pricing - changes take effect immediately</p>
    </div>
    
    <div class="container">
        <div id="alertContainer"></div>
        
        <div class="actions-bar">
            <div>
                <h3 style="color:#1f2937;margin-bottom:5px;">Active Subscription Plans</h3>
                <p style="color:#6b7280;font-size:14px;">All changes apply system-wide instantly</p>
            </div>
            <button class="btn btn-primary" onclick="showCreateModal()">
                <i class="fas fa-plus"></i> Create New Plan
            </button>
        </div>
        
        <div class="plans-grid" id="plansGrid">
            <?php if (!empty($plans)): ?>
                <?php foreach ($plans as $plan): ?>
                    <div class="plan-card">
                        <div class="plan-header">
                            <div style="display:flex;justify-content:space-between;align-items:start;">
                                <div>
                                    <div class="plan-name"><?php echo htmlspecialchars($plan['display_name']); ?></div>
                                    <span class="status-badge status-<?php echo $plan['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $plan['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                                <span style="background:#e0e7ff;color:#4338ca;padding:6px 12px;border-radius:6px;font-weight:600;font-size:12px;">
                                    <?php echo strtoupper($plan['plan_name']); ?>
                                </span>
                            </div>
                            <p class="plan-description"><?php echo htmlspecialchars($plan['description']); ?></p>
                        </div>
                        
                        <div class="plan-price">
                            <span class="price-amount">RWF <?php echo number_format($plan['price_monthly']); ?></span>
                            <span class="price-period">/ month</span>
                        </div>
                        <div style="color:#6b7280;font-size:13px;margin-bottom:16px;">
                            or RWF <?php echo number_format($plan['price_yearly']); ?> / year
                        </div>
                        
                        <div class="plan-limits">
                            <div class="limit-item">
                                <span class="limit-label"><i class="fas fa-chair"></i> Tables</span>
                                <span class="limit-value <?php echo $plan['max_tables'] >= 999999 ? 'unlimited' : ''; ?>">
                                    <?php echo $plan['max_tables'] >= 999999 ? 'Unlimited' : number_format($plan['max_tables']); ?>
                                </span>
                            </div>
                            <div class="limit-item">
                                <span class="limit-label"><i class="fas fa-users"></i> Staff Users</span>
                                <span class="limit-value <?php echo $plan['max_users'] >= 999999 ? 'unlimited' : ''; ?>">
                                    <?php echo $plan['max_users'] >= 999999 ? 'Unlimited' : number_format($plan['max_users']); ?>
                                </span>
                            </div>
                            <div class="limit-item">
                                <span class="limit-label"><i class="fas fa-utensils"></i> Menu Items</span>
                                <span class="limit-value <?php echo $plan['max_menu_items'] >= 999999 ? 'unlimited' : ''; ?>">
                                    <?php echo $plan['max_menu_items'] >= 999999 ? 'Unlimited' : number_format($plan['max_menu_items']); ?>
                                </span>
                            </div>
                            <div class="limit-item">
                                <span class="limit-label"><i class="fas fa-shopping-cart"></i> Orders/Month</span>
                                <span class="limit-value <?php echo $plan['max_orders_per_month'] >= 999999 ? 'unlimited' : ''; ?>">
                                    <?php echo $plan['max_orders_per_month'] >= 999999 ? 'Unlimited' : number_format($plan['max_orders_per_month']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="plan-features">
                            <strong style="display:block;margin-bottom:8px;color:#1f2937;">Features:</strong>
                            <?php 
                            $features = is_array($plan['features']) ? $plan['features'] : (json_decode($plan['features'], true) ?? []);
                            foreach ($features as $feature): 
                            ?>
                                <span class="feature-tag"><?php echo htmlspecialchars($feature); ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="plan-actions">
                            <button class="btn btn-warning" onclick='editPlan(<?php echo json_encode($plan); ?>)'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <button class="btn btn-<?php echo $plan['is_active'] ? 'danger' : 'success'; ?>" 
                                    onclick="togglePlanStatus(<?php echo $plan['id']; ?>, <?php echo $plan['is_active'] ? '0' : '1'; ?>)">
                                <i class="fas fa-<?php echo $plan['is_active'] ? 'times' : 'check'; ?>"></i> 
                                <?php echo $plan['is_active'] ? 'Deactivate' : 'Activate'; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No plans found.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Create/Edit Plan Modal -->
    <div id="planModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Create New Plan</h2>
            </div>
            <form id="planForm" onsubmit="savePlan(event)">
                <input type="hidden" id="planId" name="plan_id">
                
                <div class="form-group">
                    <label>Plan Code (lowercase, no spaces) *</label>
                    <input type="text" id="planName" name="plan_name" required pattern="[a-z_]+">
                </div>
                
                <div class="form-group">
                    <label>Display Name *</label>
                    <input type="text" id="displayName" name="display_name" required>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Monthly Price (RWF) *</label>
                        <input type="number" id="priceMonthly" name="price_monthly" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Yearly Price (RWF) *</label>
                        <input type="number" id="priceYearly" name="price_yearly" step="0.01" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Duration (Days) *</label>
                    <input type="number" id="durationDays" name="duration_days" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Max Tables (999999 = Unlimited) *</label>
                        <input type="number" id="maxTables" name="max_tables" required>
                    </div>
                    <div class="form-group">
                        <label>Max Users (999999 = Unlimited) *</label>
                        <input type="number" id="maxUsers" name="max_users" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Max Menu Items (999999 = Unlimited) *</label>
                        <input type="number" id="maxMenuItems" name="max_menu_items" required>
                    </div>
                    <div class="form-group">
                        <label>Max Orders/Month (999999 = Unlimited) *</label>
                        <input type="number" id="maxOrders" name="max_orders_per_month" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Features (comma-separated)</label>
                    <textarea id="features" name="features" rows="3" placeholder="basic_pos,qr_ordering,reports"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Badge Color (Hex) *</label>
                    <input type="color" id="badgeColor" name="badge_color" value="#6c757d" required>
                    <small style="color:#6b7280;margin-top:4px;display:block;">Color for plan badges in the system</small>
                </div>
                
                <div style="display:flex;gap:12px;margin-top:24px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">
                        <i class="fas fa-save"></i> Save Plan
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        
        function showAlert(message, type = 'success') {
            const alertContainer = document.getElementById('alertContainer');
            alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => alertContainer.innerHTML = '', 5000);
        }
        
        function showCreateModal() {
            document.getElementById('modalTitle').textContent = 'Create New Plan';
            document.getElementById('planForm').reset();
            document.getElementById('planId').value = '';
            document.getElementById('planName').removeAttribute('readonly');
            document.getElementById('planModal').classList.add('active');
        }
        
        function editPlan(plan) {
            document.getElementById('modalTitle').textContent = 'Edit Plan';
            document.getElementById('planId').value = plan.id;
            document.getElementById('planName').value = plan.plan_name;
            document.getElementById('planName').setAttribute('readonly', 'readonly');
            document.getElementById('displayName').value = plan.display_name;
            document.getElementById('description').value = plan.description || '';
            document.getElementById('priceMonthly').value = plan.price_monthly;
            document.getElementById('priceYearly').value = plan.price_yearly;
            document.getElementById('durationDays').value = plan.duration_days;
            document.getElementById('maxTables').value = plan.max_tables;
            document.getElementById('maxUsers').value = plan.max_users;
            document.getElementById('maxMenuItems').value = plan.max_menu_items;
            document.getElementById('maxOrders').value = plan.max_orders_per_month;
            
            // Handle features - can be array or JSON string
            const features = Array.isArray(plan.features) ? plan.features : (typeof plan.features === 'string' ? JSON.parse(plan.features || '[]') : []);
            document.getElementById('features').value = features.join(', ');
            
            // Set badge color
            document.getElementById('badgeColor').value = plan.badge_color || '#6c757d';
            
            document.getElementById('planModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('planModal').classList.remove('active');
        }
        
        function savePlan(e) {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            formData.append('action', 'save_plan');
            
            fetch(BASE_URL + '/?req=superadmin&action=save_plan', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert(data.message);
                    closeModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(() => showAlert('Error saving plan', 'error'));
        }
        
        function togglePlanStatus(planId, newStatus) {
            if (!confirm(`Are you sure you want to ${newStatus ? 'activate' : 'deactivate'} this plan?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'toggle_plan_status');
            formData.append('plan_id', planId);
            formData.append('is_active', newStatus);
            
            fetch(BASE_URL + '/?req=superadmin&action=toggle_plan_status', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'OK') {
                    showAlert(data.message);
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(() => showAlert('Error updating plan status', 'error'));
        }
    </script>
</body>
</html>
