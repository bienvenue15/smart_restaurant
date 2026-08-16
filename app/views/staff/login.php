<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Staff Login - Smart Restaurant'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="<?php echo APP_FAVICON_URL; ?>">
    <link rel="apple-touch-icon" href="<?php echo APP_LOGO_URL; ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 24px;
            padding: 45px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(255, 107, 53, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header .logo {
            width: 96px;
            height: 96px;
            margin: 0 auto 20px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 107, 53, 0.1);
            border: 2px solid rgba(255, 107, 53, 0.2);
            padding: 12px;
        }

        .login-header .logo img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .login-header h1 {
            color: #2C1810;
            font-size: 1.75rem;
            margin-bottom: 8px;
        }
        
        .login-header p {
            color: #6b7280;
            font-size: 0.9375rem;
        }
        
        .login-form {
            margin-top: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #374151;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 0.9375rem;
        }
        
        .form-input {
            position: relative;
        }
        
        .form-input i.fa-user,
        .form-input i.fa-lock {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            font-size: 1.125rem;
        }
        
        .form-input input {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-input.has-toggle input {
            padding-right: 45px;
        }
        
        .form-input input:focus {
            outline: none;
            border-color: #FF6B35;
            box-shadow: 0 0 0 3px rgba(255, 107, 53, 0.15);
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: pointer;
            font-size: 1.125rem;
            transition: color 0.2s;
            display: none;
        }
        
        .password-toggle.show {
            display: block;
        }
        
        .password-toggle:hover {
            color: #FF6B35;
        }
        
        .error-message {
            background: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .error-message p {
            color: #991b1b;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .login-submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF6B35 0%, #F7931E 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 14px rgba(255, 107, 53, 0.3);
            position: relative;
            overflow: hidden;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }
        
        .login-submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s;
        }
        
        .login-submit-btn:hover::before {
            left: 100%;
        }
        
        .login-submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(255, 107, 53, 0.4);
        }
        
        .login-submit-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }
        
        .login-submit-btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-content,
        .btn-loading {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-submit-btn i {
            font-size: 18px;
        }
        
        .login-submit-btn:focus {
            outline: none;
            box-shadow: 0 4px 14px rgba(255, 107, 53, 0.4), 0 0 0 4px rgba(255, 107, 53, 0.2);
        }
        
        .login-footer {
            margin-top: 30px;
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        
        .login-footer p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .login-footer a {
            color: #FF6B35;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .credentials-hint {
            background: #FFF9E6;
            border-left: 4px solid #FFC107;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .credentials-hint h3 {
            color: #92400e;
            font-size: 0.875rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .credentials-hint ul {
            list-style: none;
            font-size: 0.8125rem;
            color: #78350f;
        }
        
        .credentials-hint li {
            padding: 5px 0;
        }
        
        .credentials-hint code {
            background: rgba(0, 0, 0, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        
        .copyright {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            padding: 20px;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.875rem;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.1), transparent);
        }
        
        .copyright strong {
            font-weight: 700;
            color: white;
        }
        
        /* Mobile Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 16px;
            }
            
            .login-container {
                padding: 32px 28px;
                border-radius: 16px;
            }
            
            .login-header .logo {
                width: 84px;
                height: 84px;
                border-radius: 20px;
                margin-bottom: 16px;
            }
            
            .login-header h1 {
                font-size: 1.5rem;
            }
            
            .login-header p {
                font-size: 0.875rem;
            }
            
            .form-group label {
                font-size: 0.875rem;
            }
            
            .form-input input {
                padding: 13px 14px 13px 42px;
                font-size: 0.9375rem;
            }
            
            .btn-login {
                padding: 13px;
                font-size: 0.9375rem;
            }
            
            .credentials-hint {
                padding: 12px;
            }
            
            .credentials-hint h3 {
                font-size: 0.8125rem;
            }
            
            .credentials-hint ul {
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 480px) {
            body {
                padding: 12px;
            }
            
            .login-container {
                padding: 28px 22px;
                border-radius: 14px;
            }
            
            .login-header .logo {
                width: 72px;
                height: 72px;
                border-radius: 18px;
                margin-bottom: 14px;
            }
            
            .login-header h1 {
                font-size: 1.375rem;
            }
            
            .login-header p {
                font-size: 0.8125rem;
            }
            
            .form-group {
                margin-bottom: 18px;
            }
            
            .form-group label {
                font-size: 0.8125rem;
                margin-bottom: 6px;
            }
            
            .form-input i {
                left: 12px;
                font-size: 1rem;
            }
            
            .form-input input {
                padding: 12px 12px 12px 38px;
                font-size: 0.875rem;
                border-radius: 10px;
            }
            
            .btn-login {
                padding: 12px;
                font-size: 0.875rem;
                border-radius: 10px;
            }
            
            .error-message {
                padding: 10px 12px;
            }
            
            .error-message p {
                font-size: 0.8125rem;
            }
            
            .login-footer {
                margin-top: 24px;
                padding-top: 16px;
            }
            
            .login-footer p {
                font-size: 0.8125rem;
            }
            
            .credentials-hint {
                padding: 12px;
                margin-top: 16px;
            }
            
            .credentials-hint h3 {
                font-size: 0.75rem;
                margin-bottom: 8px;
            }
            
            .credentials-hint ul {
                font-size: 0.7rem;
            }
            
            .copyright {
                position: fixed;
                padding: 16px;
                font-size: 0.75rem;
            }
        }
        
        @media (max-width: 360px) {
            body {
                padding: 10px;
            }
            
            .login-container {
                padding: 24px 18px;
                border-radius: 12px;
            }
            
            .login-header .logo {
                width: 64px;
                height: 64px;
                border-radius: 16px;
                margin-bottom: 12px;
            }
            
            .login-header h1 {
                font-size: 1.25rem;
            }
            
            .form-input input {
                padding: 11px 10px 11px 36px;
                font-size: 0.8125rem;
            }
            
            .btn-login {
                padding: 11px;
                font-size: 0.8125rem;
            }
        }
        
        /* Touch-Friendly Improvements */
        @media (hover: none) and (pointer: coarse) {
            .form-input input {
                padding: 14px 15px 14px 45px;
                font-size: 16px; /* Prevents zoom on iOS */
            }
            
            .btn-login {
                min-height: 48px;
                padding: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="<?php echo APP_LOGO_URL; ?>" alt="Smart Restaurant logo">
            </div>
            <h1>👋 Welcome Back!</h1>
            <p>Sign in to manage your venue</p>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <p>
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($_SESSION['error']); ?>
                </p>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        
        <form class="login-form" method="POST" action="<?php echo BASE_URL; ?>/staff/login">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="form-input">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="form-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                </div>
            </div>
            
            <button type="submit" class="login-submit-btn" id="loginButton">
                <span class="btn-content">
                    <i class="fas fa-sign-in-alt"></i>
                    <span class="btn-text">Let's Go! 🚀</span>
                </span>
                <span class="btn-loading" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i>
                    <span>Signing in...</span>
                </span>
            </button>
        </form>
        
        
        <div class="login-footer">
            <p>
                <i class="fas fa-home"></i> 
                <a href="<?php echo BASE_URL; ?>">Back to Home</a>
            </p>
        </div>
    </div>
    
    <div class="copyright">
        &copy; 2026 SmartMenu by <strong>Inovasiyo Ltd</strong>. All rights reserved.
    </div>
    
    <script>
        // Handle form submission with loading state
        const loginForm = document.querySelector('.login-form');
        const loginButton = document.getElementById('loginButton');
        const btnContent = loginButton.querySelector('.btn-content');
        const btnLoading = loginButton.querySelector('.btn-loading');
        
        loginForm.addEventListener('submit', function(e) {
            // Show loading state
            btnContent.style.display = 'none';
            btnLoading.style.display = 'flex';
            loginButton.disabled = true;
            
            // Form will submit normally, but if there's an error and page reloads,
            // the button will be reset automatically
        });
        
        // Auto-focus username field
        document.getElementById('username').focus();
    </script>
<script>
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

// Show/hide eye icon based on password input
document.addEventListener('DOMContentLoaded', function() {
    const passwordField = document.getElementById('password');
    const passwordToggle = passwordField.nextElementSibling;
    const formInput = passwordField.parentElement;
    
    passwordField.addEventListener('input', function() {
        if (this.value.length > 0) {
            passwordToggle.classList.add('show');
            formInput.classList.add('has-toggle');
        } else {
            passwordToggle.classList.remove('show');
            formInput.classList.remove('has-toggle');
        }
    });
});
</script>
</body>
</html>
