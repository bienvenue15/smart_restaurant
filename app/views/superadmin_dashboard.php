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
        
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --sidebar-width: 280px;
            --navbar-height: 70px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e9f2 100%);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1a2332 0%, #2c3e50 100%);
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            overflow-y: auto;
            overflow-x: hidden;
            transition: var(--transition);
            z-index: 1001;
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed {
            transform: translateX(-100%);
        }
        
        .sidebar-header {
            padding: 1.5rem;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(10px);
        }
        
        .sidebar-header h2 {
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .sidebar-header .close-sidebar {
            display: none;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            opacity: 0.8;
            transition: opacity 0.3s;
        }
        
        .sidebar-header .close-sidebar:hover {
            opacity: 1;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 1.5rem 0;
        }
        
        .sidebar-menu li {
            margin: 0.25rem 0;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 1.5rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: var(--transition);
            border-left: 4px solid transparent;
            font-weight: 500;
            position: relative;
            overflow: hidden;
        }
        
        .sidebar-menu a::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            width: 0;
            height: 100%;
            background: rgba(102, 126, 234, 0.2);
            transition: width 0.3s;
            z-index: -1;
        }
        
        .sidebar-menu a:hover::before {
            width: 100%;
        }
        
        .sidebar-menu a:hover {
            color: white;
            border-left-color: #667eea;
            padding-left: 2rem;
        }
        
        .sidebar-menu a.active {
            background: rgba(102, 126, 234, 0.3);
            border-left-color: #667eea;
            color: white;
        }
        
        .sidebar-menu i {
            width: 24px;
            font-size: 1.1rem;
            text-align: center;
        }
        
        /* Overlay for mobile */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s;
        }
        
        .overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
        }
        
        .main-content.expanded {
            margin-left: 0;
        }
        
        /* Navbar */
        .navbar {
            background: var(--primary-gradient);
            color: white;
            padding: 0 2rem;
            height: var(--navbar-height);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 999;
            backdrop-filter: blur(10px);
        }
        
        .navbar-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .hamburger {
            display: none;
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            transition: transform 0.3s;
        }
        
        .hamburger:hover {
            transform: scale(1.1);
        }
        
        .navbar h1 {
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-email {
            font-size: 0.9rem;
            opacity: 0.95;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid rgba(255,255,255,0.3);
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        /* Container */
        .container {
            flex: 1;
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
            width: 100%;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }
        
        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--primary-gradient);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.25);
        }
        
        .stat-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 0.95rem;
            margin: 0;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: var(--primary-gradient);
            color: white;
        }
        
        .stat-card .value {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0.5rem 0;
        }
        
        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .section-header h2 {
            color: #2c3e50;
            font-size: 1.75rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.875rem 1.75rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* Table Styles */
        .restaurants-table {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
            border: 1px solid rgba(102, 126, 234, 0.1);
        }
        
        .table-wrapper {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1200px;
        }
        
        thead {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        
        th {
            padding: 1.25rem 1rem;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #e9ecef;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        td {
            padding: 1.25rem 1rem;
            border-bottom: 1px solid #f0f0f0;
            color: #495057;
        }
        
        tbody tr {
            transition: background-color 0.2s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        tbody tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            display: inline-block;
            padding: 0.35rem 0.85rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: var(--transition);
            font-weight: 500;
            min-width: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
            padding: 4rem;
            color: #666;
            font-size: 1.1rem;
        }
        
        .error {
            background: linear-gradient(135deg, #f8d7da 0%, #f5c2c7 100%);
            color: #721c24;
            padding: 1.25rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc3545;
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.1);
        }
        
        /* ========== RESPONSIVE DESIGN - MOBILE FIRST ========== */
        
        /* Large Tablets and below (1200px) */
        @media (max-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
            }
            
            .container {
                padding: 1.75rem;
            }
        }
        
        /* Tablets (992px and below) - Hamburger menu activates */
        @media (max-width: 992px) {
            :root {
                --sidebar-width: 0;
            }
            
            /* Sidebar becomes off-canvas */
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
                position: fixed;
                z-index: 10000;
            }
            
            .sidebar.open {
                transform: translateX(0);
                box-shadow: 6px 0 20px rgba(0,0,0,0.2);
            }
            
            .sidebar-header .close-sidebar {
                display: block;
            }
            
            /* Main content takes full width */
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            /* Show hamburger menu */
            .hamburger {
                display: block;
            }
            
            .navbar {
                padding: 0.875rem 1.5rem;
            }
            
            .navbar h1 {
                font-size: 1.25rem;
            }
            
            .container {
                padding: 1.5rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .table-wrapper {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            table {
                min-width: 900px;
            }
        }
        
        /* Mobile Landscape / Small Tablets (768px) */
        @media (max-width: 768px) {
            .navbar {
                padding: 0.75rem 1rem;
                gap: 0.5rem;
            }
            
            .navbar h1 {
                font-size: 1.125rem;
            }
            
            /* Hide user email on mobile */
            .user-email {
                display: none !important;
            }
            
            .btn-logout {
                padding: 0.5rem 0.875rem;
                font-size: 0.875rem;
            }
            
            .btn-logout span {
                display: none;
            }
            
            .btn-logout i {
                margin: 0;
            }
            
            /* Single column stats */
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.5rem;
            }
            
            .stat-card .value {
                font-size: 2rem;
            }
            
            .stat-icon {
                width: 45px;
                height: 45px;
                font-size: 1.25rem;
            }
            
            .container {
                padding: 1rem;
            }
            
            /* Stack section header */
            .section-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            
            .section-header h2 {
                font-size: 1.4rem;
            }
            
            .btn-primary {
                width: 100%;
                justify-content: center;
                padding: 0.875rem 1.5rem;
            }
            
            /* Mobile-optimized table */
            .restaurants-table {
                margin: 0 -1rem;
                border-radius: 0;
            }
            
            table {
                min-width: 800px;
                font-size: 0.875rem;
            }
            
            th, td {
                padding: 0.875rem 0.625rem;
                font-size: 0.8125rem;
            }
            
            th {
                font-size: 0.75rem;
            }
            
            /* Mobile-friendly action buttons */
            .actions {
                display: flex;
                flex-wrap: wrap;
                gap: 0.375rem;
            }
            
            .btn-sm {
                min-width: 36px;
                padding: 0.5rem;
                font-size: 0.875rem;
            }
            
            /* Trend indicators */
            .trend-indicator {
                font-size: 0.8125rem;
                flex-wrap: wrap;
            }
        }
        
        /* Mobile Portrait (480px and below) */
        @media (max-width: 480px) {
            .navbar h1 {
                font-size: 1rem;
            }
            
            .navbar h1 i {
                display: none;
            }
            
            .hamburger {
                padding: 0.4rem;
                font-size: 1.25rem;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .stat-card .value {
                font-size: 1.75rem;
            }
            
            .stat-card h3 {
                font-size: 0.8125rem;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1.125rem;
            }
            
            .section-header h2 {
                font-size: 1.125rem;
            }
            
            .btn-primary {
                padding: 0.75rem 1.25rem;
                font-size: 0.875rem;
            }
            
            /* Smaller table on very small screens */
            table {
                min-width: 700px;
                font-size: 0.8125rem;
            }
            
            th, td {
                padding: 0.625rem 0.5rem;
                font-size: 0.75rem;
            }
            
            .actions {
                flex-direction: column;
                gap: 0.25rem;
                min-width: 100px;
            }
            
            .btn-sm {
                width: 100%;
                justify-content: center;
            }
            
            .badge {
                font-size: 0.7rem;
                padding: 0.25rem 0.625rem;
            }
            
            /* Smaller trend indicators */
            .trend-indicator {
                margin-top: 0.5rem;
                padding-top: 0.5rem;
                font-size: 0.75rem;
            }
            
            .trend-label {
                font-size: 0.7rem;
            }
        }
        
        /* Extra Small Phones (360px) */
        @media (max-width: 360px) {
            .container {
                padding: 0.75rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-card .value {
                font-size: 1.5rem;
            }
            
            .navbar {
                padding: 0.625rem 0.75rem;
            }
            
            .navbar h1 {
                font-size: 0.9rem;
            }
            
            .section-header h2 {
                font-size: 1rem;
            }
            
            th, td {
                padding: 0.5rem 0.375rem;
            }
        }
        
        /* Print Styles */
        @media print {
            .sidebar,
            .navbar,
            .hamburger,
            .overlay,
            .actions,
            .btn-primary,
            .mobile-menu-toggle {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .stat-card, .restaurants-table {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }
        }
        
        /* Scrollbar Styling */
        .sidebar::-webkit-scrollbar,
        .table-wrapper::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 3px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }
        
        .table-wrapper::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* Trend Indicator Styles */
        .trend-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid rgba(0,0,0,0.05);
            font-size: 0.875rem;
        }
        
        .trend-up {
            color: #10b981;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .trend-down {
            color: #ef4444;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .trend-label {
            color: #6b7280;
            font-size: 0.8rem;
            font-weight: 400;
        }
        
        @media (max-width: 768px) {
            .trend-indicator {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Overlay for mobile menu -->
    <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
    
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2>
                <i class="fas fa-crown"></i>
                <span>Super Admin</span>
            </h2>
            <button class="close-sidebar" onclick="toggleSidebar()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <ul class="sidebar-menu">
            <li>
                <a href="?req=superadmin&action=dashboard" class="active">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="?req=superadmin&action=restaurants">
                    <i class="fas fa-store"></i>
                    <span>Restaurants</span>
                </a>
            </li>
            <li>
                <a href="?req=superadmin&action=plans">
                    <i class="fas fa-layer-group"></i>
                    <span>Subscription Plans</span>
                </a>
            </li>
            <li>
                <a href="?req=superadmin&action=users">
                    <i class="fas fa-users"></i>
                    <span>All Users</span>
                </a>
            </li>
            <li>
                <a href="?req=superadmin&action=analytics">
                    <i class="fas fa-chart-pie"></i>
                    <span>Analytics</span>
                </a>
            </li>
            <li>
                <a href="?req=superadmin&action=settings">
                    <i class="fas fa-cog"></i>
                    <span>System Settings</span>
                </a>
            </li>
        </ul>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <nav class="navbar">
            <div class="navbar-left">
                <button class="hamburger" id="hamburger" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </h1>
            </div>
            <div class="user-info">
                <span class="user-email" id="userEmail">
                    <i class="fas fa-user-circle"></i>
                    <span></span>
                </span>
                <button class="btn-logout" onclick="logout()">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </nav>
        
        <div class="container">
            <!-- Stats Grid -->
            <div class="stats-grid" id="statsGrid">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <h3>Total Restaurants</h3>
                        <div class="stat-icon">
                            <i class="fas fa-store"></i>
                        </div>
                    </div>
                    <div class="value" id="totalRestaurants">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <h3>Active Restaurants</h3>
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                    <div class="value" id="activeRestaurants">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <h3>Total Users</h3>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    <div class="value" id="totalUsers">-</div>
                </div>
                <div class="stat-card">
                    <div class="stat-card-header">
                        <h3>Today's Revenue</h3>
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                    </div>
                    <div class="value" id="todayRevenue">-</div>
                </div>
            </div>
            
            <!-- Section Header -->
            <div class="section-header">
                <h2>
                    <i class="fas fa-list"></i>
                    All Restaurants
                </h2>
                <button class="btn-primary" onclick="alert('Create restaurant feature coming soon!')">
                    <i class="fas fa-plus"></i>
                    <span>Add Restaurant</span>
                </button>
            </div>
            
            <div id="errorMessage"></div>
            
            <!-- Restaurants Table -->
            <div class="restaurants-table">
                <div class="table-wrapper">
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
                                <td colspan="11" class="loading">
                                    <i class="fas fa-spinner fa-spin"></i> Loading restaurants...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Get user info from session
        const userEmail = '<?php echo $_SESSION['email'] ?? 'Admin'; ?>';
        const userEmailSpan = document.querySelector('#userEmail span');
        if (userEmailSpan) {
            userEmailSpan.textContent = userEmail;
        }
        
        // Toggle sidebar function
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const mainContent = document.getElementById('mainContent');
            
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
            
            // For desktop, toggle expanded class
            if (window.innerWidth > 992) {
                mainContent.classList.toggle('expanded');
            }
        }
        
        // Close sidebar on window resize if open on mobile
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('overlay');
                sidebar.classList.remove('open');
                overlay.classList.remove('active');
            }
        });
        
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
                    updateStats(result.data, result.global_stats);
                } else {
                    showError(result.message || 'Failed to load restaurants');
                }
            } catch (error) {
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
                            <button class="btn-sm btn-info" onclick="viewRestaurant(${r.id})" title="View Details"><i class="fas fa-eye"></i></button>
                            <button class="btn-sm btn-warning" onclick="editRestaurant(${r.id})" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn-sm btn-danger" onclick="deleteRestaurant(${r.id}, '${r.name}')" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        function updateStats(restaurants, globalStats) {
            // Update stat cards with dynamic values and percentages
            updateStatCard('totalRestaurants', globalStats.total_restaurants);
            updateStatCard('activeRestaurants', globalStats.active_restaurants);
            updateStatCard('totalUsers', globalStats.total_users);
            updateStatCard('todayRevenue', globalStats.today_revenue, true);
        }
        
        function updateStatCard(elementId, statData, isCurrency = false) {
            const valueElement = document.getElementById(elementId);
            const card = valueElement.closest('.stat-card');
            
            // Update main value
            if (isCurrency) {
                valueElement.textContent = formatCurrency(statData.value);
            } else {
                valueElement.textContent = statData.value;
            }
            
            // Remove existing trend indicator if any
            const existingTrend = card.querySelector('.trend-indicator');
            if (existingTrend) {
                existingTrend.remove();
            }
            
            // Add trend indicator
            const trendDiv = document.createElement('div');
            trendDiv.className = 'trend-indicator';
            
            const isPositive = statData.change >= 0;
            const trendClass = isPositive ? 'trend-up' : 'trend-down';
            const icon = isPositive ? '↑' : '↓';
            const sign = isPositive ? '+' : '';
            
            trendDiv.innerHTML = `
                <span class="${trendClass}">
                    <i class="fas fa-arrow-${isPositive ? 'up' : 'down'}"></i>
                    ${sign}${Math.abs(statData.change)}%
                </span>
                <span class="trend-label">vs last month</span>
            `;
            
            card.appendChild(trendDiv);
        }
        
        function formatCurrency(amount) {
            const formatted = new Intl.NumberFormat('en-RW', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(amount);
            return 'RWF ' + formatted;
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
            }
            
            window.location.href = basePath + '?req=superadmin';
        }
    </script>
</body>
</html>
