<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($title) ? $title : 'Error'; ?> - Smart Restaurant</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="error-page">
        <div class="error-container">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h1 class="error-title"><?php echo isset($error_title) ? htmlspecialchars($error_title) : 'Error'; ?></h1>
            <p class="error-message"><?php echo isset($error_message) ? htmlspecialchars($error_message) : 'An error occurred.'; ?></p>
            
            <div class="error-actions">
                <a href="<?php echo BASE_URL; ?>" class="btn btn-primary">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
            </div>
            
            <div class="error-help">
                <p><i class="fas fa-info-circle"></i> Need help? Please contact our staff.</p>
            </div>
        </div>
    </div>
    
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            padding: 2rem;
        }
        
        .error-container {
            background: white;
            border-radius: 1rem;
            padding: 3rem 2rem;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2);
        }
        
        .error-icon {
            font-size: 5rem;
            color: #f59e0b;
            margin-bottom: 1.5rem;
        }
        
        .error-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 1rem;
        }
        
        .error-message {
            font-size: 1.125rem;
            color: #64748b;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .error-actions {
            margin-bottom: 2rem;
        }
        
        .error-help {
            padding-top: 1.5rem;
            border-top: 2px solid #e2e8f0;
            color: #64748b;
            font-size: 0.875rem;
        }
        
        .error-help i {
            color: #6366f1;
        }
    </style>
</body>
</html>
