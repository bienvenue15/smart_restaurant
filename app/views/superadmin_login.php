<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$loginError = $_SESSION['superadmin_error'] ?? '';
$loginSuccess = $_SESSION['superadmin_success'] ?? '';
unset($_SESSION['superadmin_error'], $_SESSION['superadmin_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login - Restaurant Management System</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .login-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 28px;
        }
        
        .login-header p {
            margin: 0;
            color: #666;
            font-size: 14px;
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
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
        }
        
        .alert-success {
            background-color: #efe;
            border: 1px solid #cfc;
            color: #3c3;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .loading {
            display: none;
            text-align: center;
            color: #667eea;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>🔐 Super Admin</h1>
            <p>Restaurant Management System</p>
        </div>
        <?php if ($loginError): ?>
            <div class="alert alert-error">✗ <?php echo htmlspecialchars($loginError); ?></div>
        <?php elseif ($loginSuccess): ?>
            <div class="alert alert-success">✓ <?php echo htmlspecialchars($loginSuccess); ?></div>
        <?php endif; ?>
        
        <div id="alert-message"></div>
        
        <form id="loginForm">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="superadmin@restaurant.com" autocomplete="email">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn-login" id="loginBtn">
                Sign In
            </button>
            
            <div class="loading" id="loading">
                Authenticating...
            </div>
        </form>
        
        <div class="back-link">
            <a href="<?php echo BASE_URL; ?>">← Back to Main Site</a>
        </div>
    </div>

    <script>
        const loginForm = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const loading = document.getElementById('loading');
        const alertMessage = document.getElementById('alert-message');
        
        loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Disable form
            loginBtn.disabled = true;
            loginBtn.textContent = 'Signing In...';
            loading.style.display = 'block';
            alertMessage.innerHTML = '';
            
            try {
                const formData = new FormData();
                formData.append('email', email);
                formData.append('password', password);
                
                const baseUrl = '<?php echo rtrim(BASE_URL, '/'); ?>';
                const loginUrl = baseUrl + '/?req=superadmin&action=login';
                
                const response = await fetch(loginUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    const errorText = await response.text();
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                // Get response text and parse JSON
                const text = await response.text();
                let result;
                try {
                    const jsonMatch = text.match(/\{[\s\S]*\}/);
                    if (jsonMatch) {
                        result = JSON.parse(jsonMatch[0]);
                    } else {
                        throw new Error('No JSON found in response');
                    }
                } catch (parseError) {
                    throw new Error('Invalid response from server');
                }
                
                if (result.status === 'OK') {
                    alertMessage.innerHTML = '<div class="alert alert-success">✓ Login successful! Redirecting...</div>';
                    
                    // Wait a moment for session cookie to be set
                    await new Promise(resolve => setTimeout(resolve, 500));
                    
                    // Redirect to dashboard
                    window.location.href = baseUrl + '/?req=superadmin&action=dashboard';
                } else {
                    alertMessage.innerHTML = `<div class="alert alert-error">✗ ${result.message || 'Login failed'}</div>`;
                    loginBtn.disabled = false;
                    loginBtn.textContent = 'Sign In';
                    loading.style.display = 'none';
                }
            } catch (error) {
                alertMessage.innerHTML = `<div class="alert alert-error">✗ ${error.message || 'Network error. Please try again.'}</div>`;
                loginBtn.disabled = false;
                loginBtn.textContent = 'Sign In';
                loading.style.display = 'none';
            }
        });
    </script>
</body>
</html>
