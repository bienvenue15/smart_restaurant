<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Restaurant - SmartMenu</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .registration-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .form-card, .plans-card {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 40px rgba(255, 107, 53, 0.15);
            border: 1px solid rgba(255, 193, 7, 0.1);
        }

        .form-card h2, .plans-card h2 {
            color: #2C1810;
            margin-bottom: 25px;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-card h2 i, .plans-card h2 i {
            color: #FF6B35;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: 600;
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.1);
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-wrapper input {
            padding-right: 45px;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            cursor: pointer;
            font-size: 1.1rem;
            transition: color 0.2s;
            display: none;
        }
        
        .password-toggle.show {
            display: block;
        }
        
        .password-toggle:hover {
            color: #FF6B35;
        }
        
        .terms-checkbox {
            margin: 20px 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .terms-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin-top: 2px;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .terms-checkbox label {
            color: #555;
            font-size: 0.95rem;
            cursor: pointer;
            line-height: 1.5;
        }
        
        .terms-checkbox a {
            color: #FF6B35;
            text-decoration: none;
            font-weight: 600;
        }
        
        .terms-checkbox a:hover {
            text-decoration: underline;
        }

        .form-group .error {
            color: #e74c3c;
            font-size: 0.9rem;
            margin-top: 5px;
            display: none;
        }

        .form-group.has-error input {
            border-color: #e74c3c;
        }

        .form-group.has-error .error {
            display: block;
        }

        .plan-cards {
            display: grid;
            gap: 15px;
        }

        .plan-card {
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .plan-card:hover {
            border-color: #FF6B35;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.15);
        }

        .plan-card.selected {
            border-color: #FF6B35;
            background: #FFF9E6;
        }

        .plan-card.recommended::before {
            content: "RECOMMENDED";
            position: absolute;
            top: -10px;
            right: 20px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(255, 107, 53, 0.3);
        }

        .plan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .plan-name {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }

        .plan-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #FF6B35;
        }

        .plan-duration {
            font-size: 0.9rem;
            color: #777;
        }

        .plan-features {
            list-style: none;
            margin-top: 15px;
        }

        .plan-features li {
            padding: 5px 0;
            color: #555;
            font-size: 0.9rem;
        }

        .plan-features li i {
            color: #27ae60;
            margin-right: 8px;
        }

        .btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 53, 0.3);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 53, 0.4);
        }

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .success-message {
            background: white;
            border-radius: 15px;
            padding: 40px;
            text-align: center;
            display: none;
        }

        .success-message.show {
            display: block;
        }

        .success-icon {
            font-size: 4rem;
            color: #27ae60;
            margin-bottom: 20px;
        }

        .success-message h2 {
            color: #333;
            margin-bottom: 15px;
        }

        .success-message p {
            color: #666;
            margin-bottom: 10px;
        }

        .success-message .access-url {
            background: #FFF9E6;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 1.1rem;
            color: #FF6B35;
            font-weight: bold;
            border: 2px solid #FFC107;
        }

        .loading {
            text-align: center;
            padding: 20px;
            display: none;
        }

        .loading.show {
            display: block;
        }
        
        .back-home-btn {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .back-home-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .registration-container {
                grid-template-columns: 1fr;
            }
            
            .back-home-btn {
                position: static;
                margin-bottom: 20px;
            }
        }
    </style>
</head>
<body>
    <a href="<?php echo BASE_URL; ?>" class="back-home-btn">
        <i class="fas fa-arrow-left"></i> Back to Home
    </a>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-utensils"></i> SmartMenu</h1>
            <p>🎉 Join us and make your café, bar or restaurant super easy to manage!</p>
        <div id="registrationForm" class="registration-container">
            <div class="form-card">
                <h2><i class="fas fa-building"></i> Restaurant Information</h2>
                <form id="registerForm">
                    <div class="form-group">
                        <label>Restaurant Name *</label>
                        <input type="text" id="restaurant_name" name="restaurant_name" required>
                        <span class="error">Restaurant name is required</span>
                    </div>

                    <div class="form-group">
                        <label>Owner/Manager Name *</label>
                        <input type="text" id="owner_name" name="owner_name" required>
                        <span class="error">Owner name is required</span>
                    </div>

                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" id="email" name="email" required>
                        <span class="error">Valid email is required</span>
                    </div>

                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required>
                        <span class="error">Phone number is required</span>
                    </div>

                    <div class="form-group">
                        <label>TIN Number *</label>
                        <input type="text" id="tin" name="tin" pattern="[0-9]{9,10}" maxlength="10" placeholder="Tax Identification Number" required>
                        <span class="error">TIN must be 9-10 digits</span>
                    </div>

                    <div class="form-group">
                        <label>Password *</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                        </div>
                        <span class="error">Password must be at least 6 characters</span>
                    </div>

                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <div class="password-wrapper">
                            <input type="password" id="confirm_password" name="confirm_password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                        </div>
                        <span class="error">Passwords do not match</span>
                    </div>

                    <div class="form-group">
                        <label>Address * (Sector, Cell, Village, Street, Number)</label>
                        <textarea id="address" name="address" rows="3" placeholder="e.g., Kimihurura Sector, Kimihurura Cell, Ubumwe Village, KG 123 St, House #45" required></textarea>
                        <span class="error">Address is required</span>
                    </div>

                    <div class="form-group">
                        <label>City *</label>
                        <input type="text" id="city" name="city" value="Kigali" required>
                        <span class="error">City is required</span>
                    </div>

                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" id="country" name="country" value="Rwanda" readonly>
                    </div>

                    <input type="hidden" id="selected_plan" name="plan" value="trial">

                    <div class="terms-checkbox">
                        <input type="checkbox" id="terms" name="terms" required>
                        <label for="terms">
                            I agree to the <a href="#" onclick="showTermsOfService(event); return false;">Terms of Service</a> and <a href="#" onclick="showPrivacyPolicy(event); return false;">Privacy Policy</a>
                        </label>
                    </div>

                    <div class="loading" id="loadingIndicator">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                        <p style="color: #FF6B35; margin-top: 10px;">✨ Creating your account...</p>
                    </div>

                    <button type="submit" class="btn" id="submitBtn">
                        <i class="fas fa-rocket"></i> Start My Free Trial
                    </button>
                </form>
            </div>

            <div class="plans-card">
                <h2><i class="fas fa-tags"></i> Choose Your Plan</h2>
                <div class="plan-cards" id="planCards">
                    <!-- Plans will be loaded here -->
                </div>
            </div>
        </div>

        <div class="success-message" id="successMessage">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Welcome Aboard! <i class="fas fa-star"></i></h2>
            <p id="welcomeText">Your restaurant has been successfully registered!</p>
            <div🎊 Welcome Aboard! </h2>
            <p id="welcomeText">Your account is ready! Let's get started.
            <p>Email: <span id="userEmail"></span></p>
            <p>Password: (the one you just set)</p>
            <p style="margin-top: 20px; color: #e74c3c;">
                <i class="fas fa-info-circle"></i> Please save this information
            </p>
            <button class="btn" onclick="window.location.href=document.getElementById('accessUrl').dataset.url" style="margin-top: 20px;">
                <i class="fas fa-sign-in-alt"></i> Go to Login
            </button>
        </div>
    </div>

    <script>
        const BASE_URL = '<?php echo BASE_URL; ?>';
        let selectedPlan = 'trial';

        // Load subscription plans
        async function loadPlans() {
            try {
                const response = await fetch(BASE_URL + '/api/get_plans');
                const data = await response.json();
                
                if (data.status === 'OK') {
                    displayPlans(data.plans);
                }
            } catch (error) {
            }
        }

        function displayPlans(plans) {
            const container = document.getElementById('planCards');
            container.innerHTML = '';

            plans.forEach(plan => {
                const card = document.createElement('div');
                card.className = 'plan-card' + (plan.recommended ? ' recommended' : '') + 
                                (plan.id === selectedPlan ? ' selected' : '');
                card.onclick = () => selectPlan(plan.id);

                const features = Object.entries(plan.features)
                    .filter(([key, value]) => value)
                    .map(([key, value]) => {
                        const label = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        const displayValue = typeof value === 'boolean' ? '' : `: ${value}`;
                        return `<li><i class="fas fa-check"></i> ${label}${displayValue}</li>`;
                    }).join('');

                const price = typeof plan.price === 'number' 
                    ? `${plan.price.toLocaleString()} ${plan.currency}` 
                    : plan.price;

                card.innerHTML = `
                    <div class="plan-header">
                        <div class="plan-name">${plan.name}</div>
                        <div>
                            <div class="plan-price">${price}</div>
                            <div class="plan-duration">${plan.duration}</div>
                        </div>
                    </div>
                    <p style="color: #666; margin: 10px 0;">${plan.description}</p>
                    <ul class="plan-features">${features}</ul>
                `;

                container.appendChild(card);
            });
        }

        function selectPlan(planId) {
            selectedPlan = planId;
            document.getElementById('selected_plan').value = planId;
            
            // Update UI
            document.querySelectorAll('.plan-card').forEach(card => {
                card.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }

        // Handle form submission
        document.getElementById('registerForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            // Clear previous errors
            document.querySelectorAll('.form-group').forEach(group => {
                group.classList.remove('has-error');
            });
            
            // Validate passwords match
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                const confirmGroup = document.getElementById('confirm_password').closest('.form-group');
                confirmGroup.classList.add('has-error');
                alert('Passwords do not match!');
                return;
            }
            
            if (password.length < 6) {
                const passGroup = document.getElementById('password').closest('.form-group');
                passGroup.classList.add('has-error');
                alert('Password must be at least 6 characters!');
                return;
            }

            // Show loading
            document.getElementById('loadingIndicator').classList.add('show');
            document.getElementById('submitBtn').disabled = true;

            const formData = new FormData(e.target);

            try {
                const response = await fetch(BASE_URL + '/?req=api&action=register_restaurant', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'OK') {
                    // Show success message
                    document.getElementById('registrationForm').style.display = 'none';
                    document.getElementById('successMessage').classList.add('show');
                    document.getElementById('welcomeText').textContent = 
                        `${data.data.restaurant_name || 'Your restaurant'} has been successfully registered!`;
                    document.getElementById('accessUrl').textContent = 'https://smartresto.inovasiyo.rw/staff';
                    document.getElementById('accessUrl').dataset.url = 'https://smartresto.inovasiyo.rw/staff';
                    document.getElementById('userEmail').textContent = data.data.email;
                } else {
                    // Show errors
                    if (data.errors) {
                        Object.keys(data.errors).forEach(field => {
                            const input = document.getElementById(field);
                            if (input) {
                                const group = input.closest('.form-group');
                                group.classList.add('has-error');
                                group.querySelector('.error').textContent = data.errors[field];
                            }
                        });
                    } else {
                        alert(data.message || 'Registration failed');
                    }
                }
            } catch (error) {
                alert('An error occurred during registration');
            } finally {
                document.getElementById('loadingIndicator').classList.remove('show');
                document.getElementById('submitBtn').disabled = false;
            }
        });

        // Load plans on page load
        loadPlans();
        
        // Real-time password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const confirmGroup = this.closest('.form-group');
            
            if (confirmPassword && password !== confirmPassword) {
                confirmGroup.classList.add('has-error');
            } else {
                confirmGroup.classList.remove('has-error');
            }
        });
        
        // Real-time password match validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            const confirmGroup = this.closest('.form-group');
            
            if (confirmPassword && password !== confirmPassword) {
                confirmGroup.classList.add('has-error');
            } else {
                confirmGroup.classList.remove('has-error');
            }
            
            // Show/hide eye icon
            const toggleIcon = this.parentElement.querySelector('.password-toggle');
            if (this.value.length > 0) {
                toggleIcon.classList.add('show');
            } else {
                toggleIcon.classList.remove('show');
            }
        });
        
        // Show/hide eye icon for password field
        document.getElementById('password').addEventListener('input', function() {
            const toggleIcon = this.parentElement.querySelector('.password-toggle');
            if (this.value.length > 0) {
                toggleIcon.classList.add('show');
            } else {
                toggleIcon.classList.remove('show');
            }
        });
        
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = event.target;
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Generic modal function
        function showInfoModal(title, content, icon = 'info-circle') {
            const modal = document.createElement('div');
            modal.id = 'infoModal';
            modal.style.cssText = 'display: flex; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center; animation: fadeIn 0.3s ease;';
            modal.innerHTML = `
                <div style="background: white; border-radius: 20px; padding: 0; max-width: 800px; width: 90%; max-height: 85vh; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.3); position: relative; display: flex; flex-direction: column;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; color: white; border-radius: 20px 20px 0 0;">
                        <button onclick="document.getElementById('infoModal').remove(); document.body.style.overflow='auto';" style="position: absolute; top: 15px; right: 15px; background: rgba(255,255,255,0.2); border: none; font-size: 1.5rem; color: white; cursor: pointer; padding: 8px 12px; border-radius: 8px; transition: all 0.3s;">
                            <i class="fas fa-times"></i>
                        </button>
                        <h2 style="margin: 0; font-size: 2rem; display: flex; align-items: center; gap: 15px;">
                            <i class="fas fa-${icon}" style="font-size: 1.5rem;"></i>
                            ${title}
                        </h2>
                    </div>
                    <div style="padding: 40px; overflow-y: auto; flex: 1;">
                        ${content}
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
            document.body.style.overflow = 'hidden';
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    modal.remove();
                    document.body.style.overflow = 'auto';
                }
            });
        }

        function showPrivacyPolicy(event) {
            event.preventDefault();
            const content = `
                <div style="line-height: 1.8; color: #374151;">
                    <p style="color: #6b7280; margin-bottom: 30px; font-size: 0.95rem;">Last updated: January 15, 2026</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">1. Information We Collect</h3>
                    <p>We collect information necessary to provide restaurant management services, including:</p>
                    <ul style="margin: 15px 0; padding-left: 25px;">
                        <li>Restaurant business information (name, address, contact details)</li>
                        <li>Staff account information (names, emails, roles)</li>
                        <li>Customer order data and preferences</li>
                        <li>Payment and transaction information</li>
                        <li>Usage analytics and system logs</li>
                    </ul>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">2. How We Use Your Information</h3>
                    <p>Your data is used to:</p>
                    <ul style="margin: 15px 0; padding-left: 25px;">
                        <li>Provide and maintain restaurant management services</li>
                        <li>Process orders and payments</li>
                        <li>Improve our platform and develop new features</li>
                        <li>Send important service notifications</li>
                        <li>Provide customer support</li>
                        <li>Comply with legal obligations</li>
                    </ul>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">3. Data Security</h3>
                    <p>We implement industry-standard security measures including encryption, secure servers, regular backups, and access controls. Your payment data is processed through secure payment gateways and never stored on our servers.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">4. Data Sharing</h3>
                    <p>We do not sell your personal data. We may share information only:</p>
                    <ul style="margin: 15px 0; padding-left: 25px;">
                        <li>With your explicit consent</li>
                        <li>To comply with legal requirements</li>
                        <li>With trusted service providers under strict agreements</li>
                        <li>To protect rights, property, or safety</li>
                    </ul>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">5. Your Rights</h3>
                    <p>Under GDPR and Rwanda Data Protection Law, you have rights to access, correct, delete, or export your data. Contact us at <strong>info@inovasiyo.rw</strong> to exercise these rights.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">6. Cookies</h3>
                    <p>We use essential cookies for authentication and system functionality. See our Cookie Policy for details.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">7. Contact Us</h3>
                    <p>For privacy concerns or questions:<br>Email: <strong>info@inovasiyo.rw</strong><br>Phone: <strong>+250 781 612 134</strong></p>
                </div>
            `;
            showInfoModal('Privacy Policy', content, 'shield-alt');
        }

        function showTermsOfService(event) {
            event.preventDefault();
            const content = `
                <div style="line-height: 1.8; color: #374151;">
                    <p style="color: #6b7280; margin-bottom: 30px; font-size: 0.95rem;">Last updated: January 15, 2026</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">1. Acceptance of Terms</h3>
                    <p>By accessing and using SmartMenu, you agree to be bound by these Terms of Service. If you do not agree, please do not use our services.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">2. Service Description</h3>
                    <p>SmartMenu provides a cloud-based restaurant management platform including order management, table management, staff coordination, menu management, and analytics tools.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">3. Account Registration</h3>
                    <p>You must provide accurate and complete information during registration. You are responsible for maintaining the confidentiality of your account credentials and for all activities under your account.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">4. Subscription and Payment</h3>
                    <ul style="margin: 15px 0; padding-left: 25px;">
                        <li>Subscriptions are billed monthly or annually based on your chosen plan</li>
                        <li>Payment must be made in advance via agreed methods</li>
                        <li>Subscription fees are non-refundable except where required by law</li>
                        <li>We reserve the right to modify pricing with 30 days notice</li>
                    </ul>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">5. Acceptable Use</h3>
                    <p>You agree not to:</p>
                    <ul style="margin: 15px 0; padding-left: 25px;">
                        <li>Use the service for unlawful purposes</li>
                        <li>Attempt to gain unauthorized access to the system</li>
                        <li>Interfere with or disrupt the service</li>
                        <li>Share your account with unauthorized users</li>
                        <li>Reverse engineer or copy any part of the platform</li>
                    </ul>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">6. Service Availability</h3>
                    <p>We strive for 99.9% uptime but do not guarantee uninterrupted service. Scheduled maintenance will be announced in advance when possible.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">7. Data Backup</h3>
                    <p>We perform regular backups of your data. However, you are responsible for maintaining your own backup copies of critical information.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">8. Termination</h3>
                    <p>Either party may terminate the service with 30 days written notice. We may suspend or terminate accounts that violate these terms immediately.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">9. Limitation of Liability</h3>
                    <p>Inovasiyo Ltd shall not be liable for indirect, incidental, or consequential damages arising from use of the service.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">10. Governing Law</h3>
                    <p>These terms are governed by the laws of Rwanda. Disputes shall be resolved in Rwandan courts.</p>
                    <h3 style="color: #1f2937; font-size: 1.3rem; margin-top: 25px; margin-bottom: 15px;">11. Contact</h3>
                    <p>For questions about these terms:<br>Email: <strong>info@inovasiyo.rw</strong><br>Phone: <strong>+250 781 612 134</strong></p>
                </div>
            `;
            showInfoModal('Terms of Service', content, 'file-contract');
        }
    </script>
</body>
</html>
