<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $restaurant ? 'Edit' : 'Add'; ?> Restaurant - Super Admin</title>
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
            padding: 2rem;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .page-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .back-btn {
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 8px;
            background: white;
            color: #7f8c8d;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .back-btn:hover {
            background: #3498db;
            color: white;
        }
        
        .page-title {
            font-size: 1.75rem;
            color: #2c3e50;
        }
        
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 2rem;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #f1f3f5;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        label .required {
            color: #e74c3c;
        }
        
        input, select, textarea {
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .help-text {
            font-size: 0.8rem;
            color: #7f8c8d;
            margin-top: 0.25rem;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
        }
        
        .plan-options {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        
        .plan-option {
            position: relative;
        }
        
        .plan-option input[type="radio"] {
            position: absolute;
            opacity: 0;
        }
        
        .plan-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .plan-option input[type="radio"]:checked + .plan-label {
            border-color: #3498db;
            background: #ebf5fb;
        }
        
        .plan-label:hover {
            border-color: #3498db;
        }
        
        .plan-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .plan-price {
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .error-message {
            background: #fadbd8;
            color: #e74c3c;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .success-message {
            background: #d5f4e6;
            color: #27ae60;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1.5rem;
            border-top: 2px solid #f1f3f5;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }
        
        .btn-secondary:hover {
            background: #dee2e6;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .plan-options {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <button class="back-btn" onclick="window.location='?req=superadmin&action=dashboard'">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h1 class="page-title"><?php echo $restaurant ? 'Edit' : 'Add New'; ?> Restaurant</h1>
        </div>
        
        <div class="form-card">
            <div id="messageContainer"></div>
            
            <form id="restaurantForm">
                <input type="hidden" name="id" value="<?php echo $restaurant['id'] ?? ''; ?>">
                
                <!-- Basic Information -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-info-circle"></i> Basic Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Restaurant Name <span class="required">*</span></label>
                            <input type="text" name="name" required value="<?php echo htmlspecialchars($restaurant['name'] ?? ''); ?>">
                            <div class="help-text">Official name of the restaurant</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Slug <span class="required">*</span></label>
                            <input type="text" name="slug" required value="<?php echo htmlspecialchars($restaurant['slug'] ?? ''); ?>" pattern="[a-z0-9-]+">
                            <div class="help-text">URL-friendly identifier (lowercase, hyphens only)</div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" required value="<?php echo htmlspecialchars($restaurant['email'] ?? ''); ?>">
                            <div class="help-text">Primary contact email</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($restaurant['phone'] ?? ''); ?>">
                            <div class="help-text">Contact phone number</div>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Address</label>
                        <textarea name="address"><?php echo htmlspecialchars($restaurant['address'] ?? ''); ?></textarea>
                        <div class="help-text">Physical location of the restaurant</div>
                    </div>
                </div>
                
                <!-- Subscription Settings -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-credit-card"></i> Subscription Settings</h3>
                    
                    <div class="form-group full-width">
                        <label>Subscription Plan <span class="required">*</span></label>
                        <div class="plan-options">
                            <div class="plan-option">
                                <input type="radio" name="subscription_plan" id="plan_trial" value="trial" required <?php echo (($restaurant['subscription_plan'] ?? 'trial') === 'trial') ? 'checked' : ''; ?>>
                                <label for="plan_trial" class="plan-label">
                                    <div class="plan-name">Trial</div>
                                    <div class="plan-price">Free / 30 days</div>
                                </label>
                            </div>
                            
                            <div class="plan-option">
                                <input type="radio" name="subscription_plan" id="plan_basic" value="basic" <?php echo (($restaurant['subscription_plan'] ?? '') === 'basic') ? 'checked' : ''; ?>>
                                <label for="plan_basic" class="plan-label">
                                    <div class="plan-name">Basic</div>
                                    <div class="plan-price">29,000 RWF/mo</div>
                                </label>
                            </div>
                            
                            <div class="plan-option">
                                <input type="radio" name="subscription_plan" id="plan_premium" value="premium" <?php echo (($restaurant['subscription_plan'] ?? '') === 'premium') ? 'checked' : ''; ?>>
                                <label for="plan_premium" class="plan-label">
                                    <div class="plan-name">Premium</div>
                                    <div class="plan-price">79,000 RWF/mo</div>
                                </label>
                            </div>
                            
                            <div class="plan-option">
                                <input type="radio" name="subscription_plan" id="plan_enterprise" value="enterprise" <?php echo (($restaurant['subscription_plan'] ?? '') === 'enterprise') ? 'checked' : ''; ?>>
                                <label for="plan_enterprise" class="plan-label">
                                    <div class="plan-name">Enterprise</div>
                                    <div class="plan-price">Custom</div>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Subscription End Date</label>
                            <input type="date" name="subscription_end" value="<?php echo $restaurant['subscription_end'] ?? ''; ?>">
                            <div class="help-text">Leave empty for unlimited</div>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_active" id="is_active" value="1" <?php echo (($restaurant['is_active'] ?? 1) == 1) ? 'checked' : ''; ?>>
                                <label for="is_active">Active Status</label>
                            </div>
                            <div class="help-text">Uncheck to suspend the restaurant</div>
                        </div>
                    </div>
                </div>
                
                <!-- Resource Limits -->
                <div class="form-section">
                    <h3 class="section-title"><i class="fas fa-sliders-h"></i> Resource Limits</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Max Tables</label>
                            <input type="number" name="max_tables" min="0" value="<?php echo $restaurant['max_tables'] ?? 20; ?>">
                            <div class="help-text">Maximum number of tables (0 = unlimited)</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Max Users</label>
                            <input type="number" name="max_users" min="0" value="<?php echo $restaurant['max_users'] ?? 10; ?>">
                            <div class="help-text">Maximum number of staff users (0 = unlimited)</div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location='?req=superadmin&action=dashboard'">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-save"></i> <?php echo $restaurant ? 'Update' : 'Create'; ?> Restaurant
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('restaurantForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const messageContainer = document.getElementById('messageContainer');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            const formData = new FormData(this);
            const data = {};
            
            formData.forEach((value, key) => {
                if (key === 'is_active') {
                    data[key] = formData.get(key) ? 1 : 0;
                } else {
                    data[key] = value;
                }
            });
            
            // Ensure is_active is set
            if (!data.is_active) {
                data.is_active = 0;
            }
            
            try {
                const basePath = getBasePath();
                const action = data.id ? 'update_restaurant' : 'create_restaurant';
                
                const response = await fetch(basePath + '?req=superadmin&action=' + action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.status === 'OK') {
                    messageContainer.innerHTML = `<div class="success-message"><i class="fas fa-check-circle"></i> ${result.message}</div>`;
                    setTimeout(() => {
                        window.location.href = basePath + '?req=superadmin&action=dashboard';
                    }, 1500);
                } else {
                    messageContainer.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-circle"></i> ${result.message || 'Failed to save restaurant'}</div>`;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> <?php echo $restaurant ? 'Update' : 'Create'; ?> Restaurant';
                }
            } catch (error) {
                console.error('Error:', error);
                messageContainer.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Network error. Please try again.</div>';
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-save"></i> <?php echo $restaurant ? 'Update' : 'Create'; ?> Restaurant';
            }
        });
        
        // Auto-generate slug from name
        document.querySelector('input[name="name"]').addEventListener('input', function(e) {
            const slugInput = document.querySelector('input[name="slug"]');
            if (!slugInput.value || slugInput.dataset.auto !== 'false') {
                slugInput.value = e.target.value
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '-')
                    .replace(/^-+|-+$/g, '');
                slugInput.dataset.auto = 'true';
            }
        });
        
        document.querySelector('input[name="slug"]').addEventListener('input', function() {
            this.dataset.auto = 'false';
        });
        
        function getBasePath() {
            const pathParts = window.location.pathname.split('/').filter(p => p);
            return pathParts.length > 0 ? '/' + pathParts[0] + '/' : '/';
        }
    </script>
</body>
</html>
