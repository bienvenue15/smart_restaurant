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
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
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
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.25);
            padding: 12px;
        }

        .login-header .logo img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        .login-header h1 {
            color: #1f2937;
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
        
        .form-input i {
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
        
        .form-input input:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
        
        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(99, 102, 241, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
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
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .credentials-hint {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
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
            text-align: center;
            margin-top: 30px;
            color: white;
            font-size: 0.875rem;
        }
        
        .copyright strong {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="<?php echo APP_LOGO_URL; ?>" alt="Smart Restaurant logo">
            </div>
            <h1>Staff Portal</h1>
            <p>Sign in to access the dashboard</p>
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
        
        <form class="login-form" method="POST" action="<?php echo BASE_URL; ?>/?req=staff&action=authenticate">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="form-input">
                    <i class="fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="form-input">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                Sign In
            </button>
        </form>
        
        <div class="credentials-hint">
            <h3><i class="fas fa-info-circle"></i> Demo Credentials</h3>
            <ul>
                <li><strong>Admin:</strong> <code>admin</code> / <code>admin123</code></li>
                <li><strong>Manager:</strong> <code>manager</code> / <code>admin123</code></li>
                <li><strong>Waiter:</strong> <code>waiter1</code> / <code>admin123</code></li>
                <li><strong>Kitchen:</strong> <code>kitchen</code> / <code>admin123</code></li>
            </ul>
        </div>
        
        <div class="login-footer">
            <p>
                <i class="fas fa-arrow-left"></i> 
                <a href="<?php echo BASE_URL; ?>">Back to Customer Menu</a>
            </p>
        </div>
    </div>
    
    <div class="copyright">
        &copy; 2025 Smart Restaurant by <strong>Inovasiyo Ltd</strong>. All rights reserved.
    </div>
</body>
</html>
