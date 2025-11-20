<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Your Restaurant - Smart Restaurant System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .form-card h2, .plans-card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.8rem;
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
            border-color: #667eea;
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
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .plan-card.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .plan-card.recommended::before {
            content: "RECOMMENDED";
            position: absolute;
            top: -10px;
            right: 20px;
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: bold;
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
            color: #667eea;
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
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #5568d3;
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
            background: #f8f9ff;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 1.1rem;
            color: #667eea;
            font-weight: bold;
        }

        .loading {
            text-align: center;
            padding: 20px;
            display: none;
        }

        .loading.show {
            display: block;
        }

        @media (max-width: 768px) {
            .registration-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-utensils"></i> Smart Restaurant System</h1>
            <p>Join thousands of restaurants managing their operations efficiently</p>
        </div>

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
                        <label>Password *</label>
                        <input type="password" id="password" name="password" required>
                        <span class="error">Password must be at least 6 characters</span>
                    </div>

                    <div class="form-group">
                        <label>Address *</label>
                        <textarea id="address" name="address" rows="3" required></textarea>
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

                    <div class="loading" id="loadingIndicator">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: #667eea;"></i>
                        <p style="color: #667eea; margin-top: 10px;">Creating your account...</p>
                    </div>

                    <button type="submit" class="btn" id="submitBtn">
                        <i class="fas fa-rocket"></i> Create My Restaurant Account
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
            <h2>Welcome Aboard! 🎉</h2>
            <p id="welcomeText">Your restaurant has been successfully registered!</p>
            <div class="access-url" id="accessUrl"></div>
            <p><strong>Login Credentials:</strong></p>
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
                const response = await fetch(BASE_URL + '/?req=register&action=get_plans');
                const data = await response.json();
                
                if (data.status === 'OK') {
                    displayPlans(data.plans);
                }
            } catch (error) {
                console.error('Error loading plans:', error);
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

            // Show loading
            document.getElementById('loadingIndicator').classList.add('show');
            document.getElementById('submitBtn').disabled = true;

            const formData = new FormData(e.target);

            try {
                const response = await fetch(BASE_URL + '/?req=register&action=register_restaurant', {
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
                    document.getElementById('accessUrl').textContent = data.data.access_url;
                    document.getElementById('accessUrl').dataset.url = data.data.access_url;
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
                console.error('Error:', error);
                alert('An error occurred during registration');
            } finally {
                document.getElementById('loadingIndicator').classList.remove('show');
                document.getElementById('submitBtn').disabled = false;
            }
        });

        // Load plans on page load
        loadPlans();
    </script>
</body>
</html>
