<?php
// Debug: Log that dashboard view is being loaded
error_log('[DASHBOARD VIEW] Dashboard view file loaded');
error_log('[DASHBOARD VIEW] Session ID: ' . (session_id() ?: 'none'));
error_log('[DASHBOARD VIEW] Session data: ' . json_encode($_SESSION ?? []));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - Restaurant Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css">
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
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #2c3e50 0%, #34495e 100%);
            color: white;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 1.5rem;
            background: rgba(0,0,0,0.2);
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-header .role {
            font-size: 0.75rem;
            color: #95a5a6;
            margin-top: 0.25rem;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 1rem 0;
        }
        
        .menu-section {
            margin-top: 1.5rem;
        }
        
        .menu-section-title {
            padding: 0.5rem 1.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #95a5a6;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.875rem 1.5rem;
            color: #ecf0f1;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
            position: relative;
        }
        
        .sidebar-menu a:hover {
            background: rgba(255,255,255,0.1);
            border-left-color: #3498db;
        }
        
        .sidebar-menu a.active {
            background: rgba(52, 152, 219, 0.2);
            border-left-color: #3498db;
            font-weight: 600;
        }
        
        .sidebar-menu i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .badge {
            background: #e74c3c;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            margin-left: auto;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
        }
        
        /* Top Navigation */
        .top-nav {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .search-bar {
            flex: 1;
            max-width: 500px;
            position: relative;
        }
        
        .search-bar input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .search-bar i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #95a5a6;
        }
        
        .top-nav-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .notification-icon {
            position: relative;
            cursor: pointer;
            font-size: 1.2rem;
            color: #7f8c8d;
            transition: color 0.3s;
        }
        
        .notification-icon:hover {
            color: #34495e;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #e74c3c;
            color: white;
            font-size: 0.65rem;
            padding: 0.15rem 0.4rem;
            border-radius: 10px;
        }
        
        .dropdown-panel {
            position: absolute;
            top: 120%;
            right: 0;
            width: 320px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 18px 35px rgba(0,0,0,0.15);
            padding: 1rem;
            display: none;
            z-index: 20;
        }
        
        .dropdown-panel.active {
            display: block;
        }
        
        .notification-panel {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            display: flex;
            gap: 0.75rem;
            padding: 0.65rem;
            border-radius: 10px;
            border: 1px solid #ecf0f1;
            margin-bottom: 0.5rem;
            cursor: pointer;
            transition: background 0.2s, border-color 0.2s;
        }
        
        .notification-item.unread {
            border-color: #d6e4ff;
            background: #f5f8ff;
        }
        
        .notification-item:last-child {
            margin-bottom: 0;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-icon-circle {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ecf0f1;
            color: #2c3e50;
            font-size: 0.95rem;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.15rem;
        }
        
        .notification-meta {
            font-size: 0.8rem;
            color: #95a5a6;
        }
        
        .dropdown-empty {
            text-align: center;
            color: #95a5a6;
            padding: 1rem 0;
            font-size: 0.9rem;
        }
        
        .user-dropdown {
            width: 220px;
        }
        
        .user-dropdown button {
            width: 100%;
            background: none;
            border: none;
            text-align: left;
            padding: 0.65rem 0;
            color: #2c3e50;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }
        
        .user-dropdown button:not(:last-child) {
            border-bottom: 1px solid #ecf0f1;
        }
        
        .user-dropdown button:hover {
            color: #3498db;
        }
        
        .support-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .support-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #ecf0f1;
        }
        
        .support-card h4 {
            margin: 0;
            font-size: 0.95rem;
            color: #7f8c8d;
        }
        
        .support-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
        }
        
        .support-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 360px);
            gap: 1.5rem;
        }
        
        .ticket-detail-panel {
            background: white;
            border-radius: 12px;
            border: 1px solid #ecf0f1;
            padding: 1.25rem;
            height: fit-content;
            max-height: calc(100vh - 220px);
            overflow-y: auto;
        }
        
        .ticket-meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .ticket-meta-item {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 10px;
        }
        
        .ticket-meta-item span {
            display: block;
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        
        .ticket-meta-item strong {
            color: #2c3e50;
        }
        
        .ticket-conversation {
            border-top: 1px solid #ecf0f1;
            padding-top: 1rem;
            margin-top: 1rem;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .message-bubble {
            padding: 0.85rem;
            border-radius: 12px;
            margin-bottom: 0.75rem;
            position: relative;
        }
        
        .message-bubble.support {
            background: #f0f6ff;
            border: 1px solid #d6e4ff;
        }
        
        .message-bubble.restaurant {
            background: #fff4ec;
            border: 1px solid #f9d8b9;
        }
        
        .message-meta {
            font-size: 0.75rem;
            color: #95a5a6;
            margin-bottom: 0.35rem;
        }
        
        .ticket-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        
        .ticket-status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            text-transform: capitalize;
        }
        
        .ticket-status-badge.open { background: #ecf5ff; color: #3498db; }
        .ticket-status-badge.in_progress { background: #eafaf1; color: #2ecc71; }
        .ticket-status-badge.waiting_customer { background: #fff8e6; color: #f39c12; }
        .ticket-status-badge.resolved,
        .ticket-status-badge.closed { background: #edeff5; color: #7f8c8d; }
        
        .priority-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.4rem;
        }
        
        .priority-dot.urgent { background: #e74c3c; }
        .priority-dot.high { background: #f39c12; }
        .priority-dot.medium { background: #3498db; }
        .priority-dot.low { background: #2ecc71; }
        
        .detail-empty-state {
            text-align: center;
            color: #95a5a6;
            padding: 2rem 1rem;
        }
        
        .system-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #ecf0f1;
            padding: 1.25rem;
        }
        
        .system-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
        }
        
        .setting-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
            margin-top: 1.25rem;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 8px;
            transition: background 0.3s;
        }
        
        .user-menu:hover {
            background: #f8f9fa;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #2c3e50;
        }
        
        .user-role {
            font-size: 0.75rem;
            color: #95a5a6;
        }
        
        /* Page Content */
        .page-content {
            padding: 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #7f8c8d;
            font-size: 0.95rem;
        }
        
        /* Dashboard Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }
        
        .stat-card.blue::before { background: #3498db; }
        .stat-card.green::before { background: #2ecc71; }
        .stat-card.orange::before { background: #f39c12; }
        .stat-card.purple::before { background: #9b59b6; }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .stat-title {
            font-size: 0.875rem;
            color: #7f8c8d;
            font-weight: 500;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-icon.blue { background: #ebf5fb; color: #3498db; }
        .stat-icon.green { background: #eafaf1; color: #2ecc71; }
        .stat-icon.orange { background: #fef5e7; color: #f39c12; }
        .stat-icon.purple { background: #f4ecf7; color: #9b59b6; }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .stat-footer {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
        }
        
        .stat-change {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
        }
        
        .stat-change.positive {
            background: #d5f4e6;
            color: #27ae60;
        }
        
        .stat-change.negative {
            background: #fadbd8;
            color: #e74c3c;
        }
        
        .stat-period {
            color: #95a5a6;
        }
        
        /* Charts Section */
        .charts-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .chart-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .chart-filter {
            display: flex;
            gap: 0.5rem;
        }
        
        .filter-btn {
            padding: 0.4rem 0.8rem;
            border: 1px solid #e9ecef;
            background: white;
            color: #7f8c8d;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .filter-btn:hover {
            background: #f8f9fa;
        }
        
        .filter-btn.active {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        canvas {
            max-height: 300px;
        }
        
        /* Recent Activity */
        .activity-list {
            list-style: none;
        }
        
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #f1f3f5;
            display: flex;
            gap: 1rem;
            align-items: start;
            transition: background 0.3s;
        }
        
        .activity-item:hover {
            background: #f8f9fa;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }
        
        .activity-icon.success { background: #d5f4e6; color: #27ae60; }
        .activity-icon.warning { background: #fff3cd; color: #f39c12; }
        .activity-icon.danger { background: #fadbd8; color: #e74c3c; }
        .activity-icon.info { background: #d6eaf8; color: #3498db; }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            font-size: 0.9rem;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .activity-text strong {
            font-weight: 600;
        }
        
        .activity-time {
            font-size: 0.8rem;
            color: #95a5a6;
        }
        
        /* Restaurants Table */
        .table-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .table-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f1f3f5;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .table-actions {
            display: flex;
            gap: 0.75rem;
        }
        
        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
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
        
        .btn-secondary {
            background: #e9ecef;
            color: #495057;
        }
        
        .btn-secondary:hover {
            background: #dee2e6;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8f9fa;
        }
        
        th {
            padding: 1rem 1.5rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #f1f3f5;
            font-size: 0.9rem;
        }
        
        tbody tr {
            transition: background 0.3s;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        .restaurant-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .restaurant-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .restaurant-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.2rem;
        }
        
        .restaurant-slug {
            font-size: 0.8rem;
            color: #95a5a6;
        }
        
        .badge-status {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .badge-active {
            background: #d5f4e6;
            color: #27ae60;
        }
        
        .badge-inactive {
            background: #fadbd8;
            color: #e74c3c;
        }
        
        .badge-plan {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
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
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border: none;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        
        .btn-view {
            background: #d6eaf8;
            color: #3498db;
        }
        
        .btn-view:hover {
            background: #3498db;
            color: white;
        }
        
        .btn-edit {
            background: #fff3cd;
            color: #f39c12;
        }
        
        .btn-edit:hover {
            background: #f39c12;
            color: white;
        }
        
        .btn-delete {
            background: #fadbd8;
            color: #e74c3c;
        }
        
        .btn-delete:hover {
            background: #e74c3c;
            color: white;
        }
        
        /* Loading and Error States */
        .loading-spinner {
            text-align: center;
            padding: 3rem;
            color: #95a5a6;
        }
        
        .loading-spinner i {
            font-size: 2rem;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
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
        
        /* Responsive */
        /* Form Styles */
        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        
        .form-group label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.75rem 1rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-group textarea {
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
            margin: 0 !important;
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
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding-top: 1.5rem;
            border-top: 2px solid #f1f3f5;
        }
        
        /* Filter Bar */
        .filter-bar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            align-items: end;
        }
        
        .filter-bar .form-group {
            flex: 1;
            margin: 0;
        }
        
        .filter-bar .btn {
            margin-bottom: 0;
        }
        
        /* Role Cards */
        .role-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .role-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        
        .role-card:hover {
            border-color: #3498db;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.1);
        }
        
        .role-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e9ecef;
        }
        
        .role-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .role-icon.admin { background: #ebf5fb; color: #3498db; }
        .role-icon.manager { background: #eafaf1; color: #2ecc71; }
        .role-icon.waiter { background: #fef5e7; color: #f39c12; }
        .role-icon.kitchen { background: #f4ecf7; color: #9b59b6; }
        .role-icon.cashier { background: #ecf0f1; color: #2c3e50; }
        
        .role-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .role-description {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin: 0;
        }
        
        .role-permissions {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .permission-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            color: #2c3e50;
            padding: 0.5rem;
            background: white;
            border-radius: 6px;
        }
        
        .permission-item i {
            width: 20px;
            text-align: center;
            color: #27ae60;
            font-weight: 600;
        }
        
        .permission-item.denied {
            opacity: 0.6;
        }
        
        .permission-item.denied i {
            color: #e74c3c;
        }
        
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: fadeIn 0.2s;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            animation: slideUp 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { transform: translateY(50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 2px solid #f1f3f5;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.3rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            color: #95a5a6;
            cursor: pointer;
            line-height: 1;
            padding: 0;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }
        
        .modal-close:hover {
            background: #f1f3f5;
            color: #2c3e50;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 2px solid #f1f3f5;
        }
        
        /* Enhanced Badge Colors */
        .badge-plan.badge-admin {
            background: #ebf5fb;
            color: #3498db;
            border: 1px solid #3498db;
        }
        
        .badge-plan.badge-manager {
            background: #eafaf1;
            color: #2ecc71;
            border: 1px solid #2ecc71;
        }
        
        .badge-plan.badge-waiter {
            background: #fef5e7;
            color: #f39c12;
            border: 1px solid #f39c12;
        }
        
        .badge-plan.badge-kitchen {
            background: #f4ecf7;
            color: #9b59b6;
            border: 1px solid #9b59b6;
        }
        
        .badge-plan.badge-cashier {
            background: #ecf0f1;
            color: #2c3e50;
            border: 1px solid #2c3e50;
        }
        
        /* Alert Box */
        .alert {
            border-radius: 8px;
            display: flex;
            align-items: center;
        }
        
        /* Export Cards */
        .export-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .export-card:hover {
            border-color: #3498db;
            box-shadow: 0 4px 12px rgba(52, 152, 219, 0.1);
            transform: translateY(-2px);
        }
        
        .export-icon {
            width: 60px;
            height: 60px;
            background: #ebf5fb;
            color: #3498db;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin: 0 auto 1rem;
        }
        
        .export-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .export-desc {
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .search-bar {
                display: none;
            }
            
            .form-row,
            .plan-options {
                grid-template-columns: 1fr;
            }
            
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .support-layout {
                grid-template-columns: 1fr;
            }
            
            .ticket-detail-panel {
                max-height: none;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-halved"></i> Super Admin</h2>
            <div class="role">System Administrator</div>
        </div>
        
        <ul class="sidebar-menu">
            <li><a href="#" class="active" data-section="dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
            
            <div class="menu-section">
                <div class="menu-section-title">Restaurant Management</div>
                <li><a href="#" data-section="restaurants"><i class="fas fa-store"></i> All Restaurants</a></li>
                <li><a href="#" data-section="add-restaurant"><i class="fas fa-plus-circle"></i> Add Restaurant</a></li>
                <li><a href="#" data-section="subscriptions"><i class="fas fa-credit-card"></i> Subscriptions</a></li>
            </div>
            
            <div class="menu-section">
                <div class="menu-section-title">User Management</div>
                <li><a href="#" data-section="users"><i class="fas fa-users"></i> All Users</a></li>
                <li><a href="#" data-section="roles"><i class="fas fa-user-shield"></i> Roles & Permissions</a></li>
            </div>
            
            <div class="menu-section">
                <div class="menu-section-title">Reports & Analytics</div>
                <li><a href="#" data-section="analytics"><i class="fas fa-chart-line"></i> Analytics</a></li>
                <li><a href="#" data-section="reports"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="#" data-section="audit-log"><i class="fas fa-history"></i> Audit Log</a></li>
            </div>
            
            <div class="menu-section">
                <div class="menu-section-title">Support</div>
                <li><a href="#" data-section="tickets"><i class="fas fa-ticket-alt"></i> Support Tickets <span class="badge">3</span></a></li>
                <li><a href="#" data-section="messages"><i class="fas fa-envelope"></i> Messages</a></li>
                <li><a href="#" data-section="notifications"><i class="fas fa-bell"></i> Notifications</a></li>
            </div>
            
            <div class="menu-section">
                <div class="menu-section-title">System</div>
                <li><a href="#" data-section="settings"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="#" data-section="backup"><i class="fas fa-database"></i> Backup</a></li>
                <li><a href="#" data-section="logs"><i class="fas fa-file-code"></i> System Logs</a></li>
            </div>
        </ul>
    </aside>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <nav class="top-nav">
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search restaurants, users, or orders...">
            </div>
            
            <div class="top-nav-right">
                <div class="notification-icon" id="notificationTrigger">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationBadge">0</span>
                    <div class="dropdown-panel notification-panel" id="notificationDropdown">
                        <div class="dropdown-empty">Loading notifications...</div>
                    </div>
                </div>
                
                <div class="user-menu" id="userMenuTrigger">
                    <div class="user-avatar">SA</div>
                    <div class="user-info">
                        <div class="user-name" id="userName">Super Admin</div>
                        <div class="user-role">Administrator</div>
                    </div>
                    <i class="fas fa-chevron-down" style="color: #95a5a6;"></i>
                    <div class="dropdown-panel user-dropdown" id="userDropdown">
                        <button type="button" onclick="loadSection('settings')"><i class="fas fa-sliders-h"></i> System Settings</button>
                        <button type="button" onclick="loadSection('notifications')"><i class="fas fa-bell"></i> Notification Center</button>
                        <button type="button" onclick="logoutSuperAdmin()"><i class="fas fa-sign-out-alt"></i> Logout</button>
                    </div>
                </div>
            </div>
        </nav>
        
        <!-- Page Content -->
        <div class="page-content" id="mainContent">
            <!-- Content will be loaded dynamically here -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        const BASE_PATH = '<?php echo rtrim(BASE_URL, '/'); ?>';
        
        // Debug authentication state - ALWAYS log this
        console.log('[DASHBOARD] ========================================');
        console.log('[DASHBOARD] Dashboard script loaded!');
        console.log('[DASHBOARD] Current URL:', window.location.href);
        console.log('[DASHBOARD] Current cookies:', document.cookie);
        console.log('[DASHBOARD] BASE_PATH:', BASE_PATH);
        console.log('[DASHBOARD] ========================================');
        
        // If we see this, the HTML loaded successfully
        if (document.body) {
            console.log('[DASHBOARD] Body element found, HTML is loaded');
        } else {
            console.error('[DASHBOARD] ERROR: Body element not found!');
        }
        
        // Check if we have session cookie
        const cookies = document.cookie.split(';').map(c => c.trim());
        const sessionCookie = cookies.find(c => c.startsWith('PHPSESSID='));
        if (sessionCookie) {
            console.log('[DASHBOARD] PHP Session cookie found:', sessionCookie.substring(0, 30) + '...');
        } else {
            console.warn('[DASHBOARD] WARNING: No PHPSESSID cookie found!');
            console.warn('[DASHBOARD] All cookies:', cookies);
        }
        
        // Test authentication immediately
        async function checkAuthStatus() {
            try {
                console.log('[DASHBOARD] Testing authentication endpoint...');
                const response = await fetch(BASE_PATH + '/?req=superadmin&action=dashboard&format=json', {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                
                console.log('[DASHBOARD] Auth check response status:', response.status);
                console.log('[DASHBOARD] Auth check response headers:', Object.fromEntries(response.headers.entries()));
                
                const result = await response.json();
                console.log('[DASHBOARD] Auth check result:', result);
                
                if (result.status === 'FAIL' && result.message.includes('Access denied')) {
                    console.error('[DASHBOARD] AUTHENTICATION FAILED - Session not recognized');
                    console.error('[DASHBOARD] Current cookies:', document.cookie);
                    console.error('[DASHBOARD] Redirecting to login...');
                    setTimeout(() => {
                        window.location.href = BASE_PATH + '/?req=superadmin';
                    }, 2000);
                    return false;
                }
                return true;
            } catch (error) {
                console.error('[DASHBOARD] Auth check error:', error);
                return false;
            }
        }
        
        // Global variables
        let revenueChart = null;
        
        // Initialize
        document.addEventListener('DOMContentLoaded', async function() {
            console.log('[DASHBOARD] DOM loaded, initializing...');
            
            // Check authentication first
            const isAuthenticated = await checkAuthStatus();
            if (!isAuthenticated) {
                console.error('[DASHBOARD] Not authenticated, stopping initialization');
                return;
            }
            
            console.log('[DASHBOARD] Authentication verified, setting up dashboard...');
            setupNavigation();
            setupTopNavInteractions();
            loadNotificationPreview();
            loadSection('dashboard');
        });
        
        // Setup navigation click handlers
        function setupNavigation() {
            document.querySelectorAll('.sidebar-menu a[data-section]').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Update active state
                    document.querySelectorAll('.sidebar-menu a').forEach(a => a.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Load section
                    const section = this.dataset.section;
                    loadSection(section);
                });
            });
        }
        
        function setupTopNavInteractions() {
            const notificationTrigger = document.getElementById('notificationTrigger');
            const userTrigger = document.getElementById('userMenuTrigger');
            
            if (notificationTrigger) {
                notificationTrigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleNotificationDropdown();
                });
            }
            
            if (userTrigger) {
                userTrigger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleUserDropdown();
                });
            }
            
            document.addEventListener('click', function() {
                closeDropdowns();
            });
        }
        
        function toggleNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            if (!dropdown) return;
            notificationDropdownOpen = !notificationDropdownOpen;
            dropdown.classList.toggle('active', notificationDropdownOpen);
            if (notificationDropdownOpen) {
                userDropdownOpen = false;
                document.getElementById('userDropdown')?.classList.remove('active');
            }
        }
        
        function toggleUserDropdown() {
            const dropdown = document.getElementById('userDropdown');
            if (!dropdown) return;
            userDropdownOpen = !userDropdownOpen;
            dropdown.classList.toggle('active', userDropdownOpen);
            if (userDropdownOpen) {
                notificationDropdownOpen = false;
                document.getElementById('notificationDropdown')?.classList.remove('active');
            }
        }
        
        function closeDropdowns() {
            if (notificationDropdownOpen) {
                notificationDropdownOpen = false;
                document.getElementById('notificationDropdown')?.classList.remove('active');
            }
            if (userDropdownOpen) {
                userDropdownOpen = false;
                document.getElementById('userDropdown')?.classList.remove('active');
            }
        }
        
        async function loadNotificationPreview() {
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=get_notifications&limit=6&format=json');
                const result = await response.json();
                
                if (result.status === 'OK') {
                    notificationFeedCache = result.data || [];
                    updateNotificationBadge(result.unread_count || 0);
                    renderNotificationDropdown();
                }
            } catch (error) {
                console.error('Failed to load notifications', error);
            }
        }
        
        function updateNotificationBadge(count) {
            const badge = document.getElementById('notificationBadge');
            if (!badge) return;
            if (!count) {
                badge.style.display = 'none';
            } else {
                badge.textContent = count > 9 ? '9+' : count;
                badge.style.display = 'inline-flex';
            }
        }
        
        function renderNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            if (!dropdown) return;
            
            if (!notificationFeedCache.length) {
                dropdown.innerHTML = '<div class="dropdown-empty">No notifications yet</div>';
                return;
            }
            
            dropdown.innerHTML = notificationFeedCache.map(item => `
                <div class="notification-item ${item.is_read == 0 ? 'unread' : ''}" onclick="handleNotificationClick(${item.id})">
                    <div class="notification-icon-circle"><i class="fas fa-${item.icon || 'bell'}"></i></div>
                    <div class="notification-content">
                        <div class="notification-title">${escapeHtml(item.title)}</div>
                        <div class="notification-text">${escapeHtml(item.message)}</div>
                        <div class="notification-meta">${formatDateTime(item.created_at)}</div>
                    </div>
                </div>
            `).join('') + `
                <button class="btn btn-secondary" style="width: 100%; margin-top: 0.5rem;" onclick="markAllNotificationsRead()">Mark all as read</button>
            `;
        }
        
        async function handleNotificationClick(id) {
            await markNotificationAsRead(id);
            loadNotificationPreview();
        }
        
        async function markAllNotificationsRead() {
            await markNotificationAsRead('all');
            loadNotificationPreview();
        }
        
        async function markNotificationAsRead(id) {
            try {
                const basePath = getBasePath();
                await fetch(basePath + '?req=superadmin&action=mark_notification_read', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: id })
                });
            } catch (error) {
                console.error('Failed to mark notification', error);
            }
        }
        
        async function logoutSuperAdmin() {
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=logout', { method: 'POST' });
                const result = await response.json();
                if (result.status === 'OK') {
                    window.location.reload();
                }
            } catch (error) {
                console.error('Logout failed', error);
            }
        }
        
        function escapeHtml(value) {
            if (value === undefined || value === null) {
                return '';
            }
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
        
        function formatDateTime(value) {
            if (!value) {
                return '-';
            }
            const date = new Date(value);
            if (isNaN(date.getTime())) {
                return escapeHtml(value);
            }
            return escapeHtml(date.toLocaleString());
        }
        
        // Load section content
        function loadSection(section) {
            const mainContent = document.getElementById('mainContent');
            
            // Show loading
            mainContent.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner"></i><p>Loading...</p></div>';
            
            // Load appropriate section
            switch(section) {
                case 'dashboard':
                    loadDashboard();
                    break;
                case 'restaurants':
                    loadRestaurants();
                    break;
                case 'add-restaurant':
                    loadAddRestaurantForm();
                    break;
                case 'subscriptions':
                    loadSubscriptions();
                    break;
                case 'users':
                    loadUsers();
                    break;
                case 'roles':
                    loadRoles();
                    break;
                case 'analytics':
                    loadAnalytics();
                    break;
                case 'reports':
                    loadReports();
                    break;
                case 'subscriptions':
                    loadSubscriptions();
                    break;
                case 'tickets':
                    loadTickets();
                    break;
                case 'messages':
                    loadMessagesSection();
                    break;
                case 'notifications':
                    loadNotificationCenter();
                    break;
                case 'settings':
                    loadSettingsSection();
                    break;
                case 'backup':
                    loadBackupSection();
                    break;
                case 'logs':
                    loadSystemLogsSection();
                    break;
                case 'audit-log':
                    loadAuditLog();
                    break;
                default:
                    loadComingSoon(section);
            }
        }
        
        // Load Dashboard Section
        function loadDashboard() {
            const html = `
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">Dashboard Overview</h1>
                    <p class="page-subtitle">Welcome back! Here's what's happening with your restaurants today.</p>
                </div>
                
                <!-- Error/Success Messages -->
                <div id="messageContainer"></div>
                
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Total Restaurants</div>
                            </div>
                            <div class="stat-icon blue">
                                <i class="fas fa-store"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalRestaurants">-</div>
                        <div class="stat-footer">
                            <span class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> 12%
                            </span>
                            <span class="stat-period">vs last month</span>
                        </div>
                    </div>
                    
                    <div class="stat-card green">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Active Restaurants</div>
                            </div>
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="activeRestaurants">-</div>
                        <div class="stat-footer">
                            <span class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> 8%
                            </span>
                            <span class="stat-period">vs last month</span>
                        </div>
                    </div>
                    
                    <div class="stat-card orange">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Total Revenue</div>
                            </div>
                            <div class="stat-icon orange">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalRevenue">-</div>
                        <div class="stat-footer">
                            <span class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> 23%
                            </span>
                            <span class="stat-period">vs last month</span>
                        </div>
                    </div>
                    
                    <div class="stat-card purple">
                        <div class="stat-header">
                            <div>
                                <div class="stat-title">Total Users</div>
                            </div>
                            <div class="stat-icon purple">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value" id="totalUsers">-</div>
                        <div class="stat-footer">
                            <span class="stat-change positive">
                                <i class="fas fa-arrow-up"></i> 15%
                            </span>
                            <span class="stat-period">vs last month</span>
                        </div>
                    </div>
                </div>
                
                <!-- Charts Section -->
                <div class="charts-grid">
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3 class="chart-title">Revenue Overview</h3>
                            <div class="chart-filter">
                                <button class="filter-btn active">7 Days</button>
                                <button class="filter-btn">30 Days</button>
                                <button class="filter-btn">90 Days</button>
                            </div>
                        </div>
                        <canvas id="revenueChart"></canvas>
                    </div>
                    
                    <div class="chart-card">
                        <div class="chart-header">
                            <h3 class="chart-title">Recent Activity</h3>
                        </div>
                        <ul class="activity-list">
                            <li class="activity-item">
                                <div class="activity-icon success">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text"><strong>Pizza Palace</strong> joined the platform</div>
                                    <div class="activity-time">2 hours ago</div>
                                </div>
                            </li>
                            <li class="activity-item">
                                <div class="activity-icon info">
                                    <i class="fas fa-credit-card"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text"><strong>Burger House</strong> upgraded to Premium</div>
                                    <div class="activity-time">5 hours ago</div>
                                </div>
                            </li>
                            <li class="activity-item">
                                <div class="activity-icon warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text"><strong>Sushi Garden</strong> subscription expires in 3 days</div>
                                    <div class="activity-time">1 day ago</div>
                                </div>
                            </li>
                            <li class="activity-item">
                                <div class="activity-icon success">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-text"><strong>Default Restaurant</strong> completed setup</div>
                                    <div class="activity-time">2 days ago</div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <!-- Restaurants Table -->
                <div class="table-card">
                    <div class="table-header">
                        <h3 class="table-title">All Restaurants</h3>
                        <div class="table-actions">
                            <button class="btn btn-secondary"><i class="fas fa-download"></i> Export</button>
                            <button class="btn btn-primary" onclick="loadSection('add-restaurant')">
                                <i class="fas fa-plus"></i> Add Restaurant
                            </button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Restaurant</th>
                                <th>Email</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Users</th>
                                <th>Tables</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="restaurantsBody">
                            <tr>
                                <td colspan="9">
                                    <div class="loading-spinner">
                                        <i class="fas fa-spinner"></i>
                                        <p>Loading restaurants...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = html;
            
            // Load dashboard data
            loadDashboardData();
            initializeCharts();
        }
        
        // Load Dashboard Data
        async function loadDashboardData() {
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=list_restaurants&format=json');
                const result = await response.json();
                
                if (result.status === 'OK') {
                    updateStats(result.data);
                    displayRestaurants(result.data);
                } else {
                    showError(result.message || 'Failed to load data');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load dashboard data');
            }
        }
        
        function getBasePath() {
            return BASE_PATH;
        }
        
        function updateStats(restaurants) {
            const totalRestaurants = restaurants.length;
            const activeRestaurants = restaurants.filter(r => r.is_active).length;
            const totalUsers = restaurants.reduce((sum, r) => sum + (parseInt(r.stats.total_users) || 0), 0);
            const totalRevenue = restaurants.reduce((sum, r) => sum + (parseFloat(r.stats.today_revenue) || 0), 0);
            
            document.getElementById('totalRestaurants').textContent = totalRestaurants;
            document.getElementById('activeRestaurants').textContent = activeRestaurants;
            document.getElementById('totalUsers').textContent = totalUsers;
            document.getElementById('totalRevenue').textContent = formatCurrency(totalRevenue);
        }
        
        function displayRestaurants(restaurants) {
            const tbody = document.getElementById('restaurantsBody');
            
            if (restaurants.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 3rem; color: #95a5a6;">No restaurants found</td></tr>';
                return;
            }
            
            tbody.innerHTML = restaurants.map(r => `
                <tr>
                    <td>
                        <div class="restaurant-info">
                            <div class="restaurant-logo">${r.name.charAt(0)}</div>
                            <div>
                                <div class="restaurant-name">${r.name}</div>
                                <div class="restaurant-slug">${r.slug}</div>
                            </div>
                        </div>
                    </td>
                    <td>${r.email}</td>
                    <td><span class="badge-plan badge-${r.subscription_plan}">${r.subscription_plan}</span></td>
                    <td><span class="badge-status badge-${r.is_active ? 'active' : 'inactive'}">${r.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>${r.stats.total_users || 0}</td>
                    <td>${r.stats.total_tables || 0}</td>
                    <td>${r.stats.total_orders || 0}</td>
                    <td>${formatCurrency(r.stats.today_revenue || 0)}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon btn-view" onclick="viewRestaurant(${r.id})" title="View"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon btn-edit" onclick="editRestaurant(${r.id})" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon btn-delete" onclick="deleteRestaurant(${r.id}, '${r.name}')" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        function initializeCharts() {
            // Revenue Chart
            const ctx = document.getElementById('revenueChart');
            if (!ctx) return;
            
            // Destroy existing chart if any
            if (revenueChart) {
                revenueChart.destroy();
            }
            
            revenueChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Revenue',
                        data: [12000, 19000, 15000, 25000, 22000, 30000, 28000],
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'RWF ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function formatCurrency(amount) {
            return new Intl.NumberFormat('en-RW', {
                style: 'currency',
                currency: 'RWF',
                minimumFractionDigits: 0
            }).format(amount);
        }
        
        function showError(message) {
            const container = document.getElementById('messageContainer');
            if (container) {
                container.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-circle"></i> ${message}</div>`;
                setTimeout(() => container.innerHTML = '', 5000);
            }
        }
        
        function showSuccess(message) {
            const container = document.getElementById('messageContainer');
            if (container) {
                container.innerHTML = `<div class="success-message"><i class="fas fa-check-circle"></i> ${message}</div>`;
                setTimeout(() => container.innerHTML = '', 5000);
            }
        }
        
        function viewRestaurant(id) {
            alert('View restaurant details - Coming soon!');
        }
        
        function editRestaurant(id) {
            // Load edit form in the main content area
            loadEditRestaurantForm(id);
        }
        
        function deleteRestaurant(id, name) {
            if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone and will remove all associated data.`)) {
                // Implement delete functionality
                alert('Delete functionality coming soon!');
            }
        }
        
        // Load Restaurants List Section
        function loadRestaurants() {
            const html = `
                <div class="page-header">
                    <h1 class="page-title">All Restaurants</h1>
                    <p class="page-subtitle">Manage all restaurant tenants in the system</p>
                </div>
                
                <div id="messageContainer"></div>
                
                <div class="table-card">
                    <div class="table-header">
                        <h3 class="table-title">Restaurant Directory</h3>
                        <div class="table-actions">
                            <button class="btn btn-secondary"><i class="fas fa-filter"></i> Filter</button>
                            <button class="btn btn-secondary"><i class="fas fa-download"></i> Export</button>
                            <button class="btn btn-primary" onclick="loadSection('add-restaurant')">
                                <i class="fas fa-plus"></i> Add Restaurant
                            </button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Restaurant</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Subscription End</th>
                                <th>Users</th>
                                <th>Tables</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="restaurantsListBody">
                            <tr>
                                <td colspan="9">
                                    <div class="loading-spinner">
                                        <i class="fas fa-spinner"></i>
                                        <p>Loading restaurants...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = html;
            loadRestaurantsList();
        }
        
        async function loadRestaurantsList() {
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=list_restaurants&format=json');
                const result = await response.json();
                
                if (result.status === 'OK') {
                    displayRestaurantsList(result.data);
                } else {
                    showError(result.message || 'Failed to load restaurants');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load restaurants');
            }
        }
        
        function displayRestaurantsList(restaurants) {
            const tbody = document.getElementById('restaurantsListBody');
            
            if (restaurants.length === 0) {
                tbody.innerHTML = '<tr><td colspan="9" style="text-align: center; padding: 3rem; color: #95a5a6;">No restaurants found</td></tr>';
                return;
            }
            
            tbody.innerHTML = restaurants.map(r => `
                <tr>
                    <td>
                        <div class="restaurant-info">
                            <div class="restaurant-logo">${r.name.charAt(0)}</div>
                            <div>
                                <div class="restaurant-name">${r.name}</div>
                                <div class="restaurant-slug">${r.slug}</div>
                            </div>
                        </div>
                    </td>
                    <td>${r.email}</td>
                    <td>${r.phone || '-'}</td>
                    <td><span class="badge-plan badge-${r.subscription_plan}">${r.subscription_plan}</span></td>
                    <td><span class="badge-status badge-${r.is_active ? 'active' : 'inactive'}">${r.is_active ? 'Active' : 'Inactive'}</span></td>
                    <td>${r.subscription_end || '-'}</td>
                    <td>${r.stats.total_users || 0}</td>
                    <td>${r.stats.total_tables || 0}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon btn-view" onclick="viewRestaurant(${r.id})" title="View"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon btn-edit" onclick="editRestaurant(${r.id})" title="Edit"><i class="fas fa-edit"></i></button>
                            <button class="btn-icon btn-delete" onclick="deleteRestaurant(${r.id}, '${r.name}')" title="Delete"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        // Load Add Restaurant Form
        function loadAddRestaurantForm() {
            const html = `
                <div class="page-header">
                    <h1 class="page-title">Add New Restaurant</h1>
                    <p class="page-subtitle">Create a new restaurant tenant in the system</p>
                </div>
                
                <div id="formMessageContainer"></div>
                
                <div class="form-card">
                    <form id="restaurantForm">
                        <!-- Basic Information -->
                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-info-circle"></i> Basic Information</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Restaurant Name <span class="required">*</span></label>
                                    <input type="text" name="name" required>
                                    <div class="help-text">Official name of the restaurant</div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Slug <span class="required">*</span></label>
                                    <input type="text" name="slug" required pattern="[a-z0-9-]+">
                                    <div class="help-text">URL-friendly identifier (lowercase, hyphens only)</div>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email <span class="required">*</span></label>
                                    <input type="email" name="email" required>
                                    <div class="help-text">Primary contact email</div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="tel" name="phone">
                                    <div class="help-text">Contact phone number</div>
                                </div>
                            </div>
                            
                            <div class="form-group full-width">
                                <label>Address</label>
                                <textarea name="address"></textarea>
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
                                        <input type="radio" name="subscription_plan" id="plan_trial" value="trial" checked required>
                                        <label for="plan_trial" class="plan-label">
                                            <div class="plan-name">Trial</div>
                                            <div class="plan-price">Free / 30 days</div>
                                        </label>
                                    </div>
                                    
                                    <div class="plan-option">
                                        <input type="radio" name="subscription_plan" id="plan_basic" value="basic">
                                        <label for="plan_basic" class="plan-label">
                                            <div class="plan-name">Basic</div>
                                            <div class="plan-price">29,000 RWF/mo</div>
                                        </label>
                                    </div>
                                    
                                    <div class="plan-option">
                                        <input type="radio" name="subscription_plan" id="plan_premium" value="premium">
                                        <label for="plan_premium" class="plan-label">
                                            <div class="plan-name">Premium</div>
                                            <div class="plan-price">79,000 RWF/mo</div>
                                        </label>
                                    </div>
                                    
                                    <div class="plan-option">
                                        <input type="radio" name="subscription_plan" id="plan_enterprise" value="enterprise">
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
                                    <input type="date" name="subscription_end">
                                    <div class="help-text">Leave empty for auto-calculation</div>
                                </div>
                                
                                <div class="form-group">
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                                        <label for="is_active">Active Status</label>
                                    </div>
                                    <div class="help-text">Uncheck to create suspended</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Resource Limits -->
                        <div class="form-section">
                            <h3 class="section-title"><i class="fas fa-sliders-h"></i> Resource Limits</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Max Tables</label>
                                    <input type="number" name="max_tables" min="0" value="20">
                                    <div class="help-text">Maximum tables (0 = unlimited)</div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Max Users</label>
                                    <input type="number" name="max_users" min="0" value="10">
                                    <div class="help-text">Maximum staff users (0 = unlimited)</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" onclick="loadSection('dashboard')">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="fas fa-save"></i> Create Restaurant
                            </button>
                        </div>
                    </form>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = html;
            setupRestaurantForm();
        }
        
        function setupRestaurantForm() {
            // Auto-generate slug from name
            document.querySelector('input[name="name"]').addEventListener('input', function(e) {
                const slugInput = document.querySelector('input[name="slug"]');
                if (!slugInput.dataset.manual) {
                    slugInput.value = e.target.value
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                }
            });
            
            document.querySelector('input[name="slug"]').addEventListener('input', function() {
                this.dataset.manual = 'true';
            });
            
            // Form submission
            document.getElementById('restaurantForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = document.getElementById('submitBtn');
                const messageContainer = document.getElementById('formMessageContainer');
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
                
                const formData = new FormData(this);
                const data = {};
                
                formData.forEach((value, key) => {
                    if (key === 'is_active') {
                        data[key] = formData.get(key) ? 1 : 0;
                    } else {
                        data[key] = value;
                    }
                });
                
                if (!data.is_active) data.is_active = 0;
                
                try {
                    const basePath = getBasePath();
                    const response = await fetch(basePath + '?req=superadmin&action=create_restaurant', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.status === 'OK') {
                        messageContainer.innerHTML = `<div class="success-message"><i class="fas fa-check-circle"></i> ${result.message}</div>`;
                        setTimeout(() => loadSection('dashboard'), 1500);
                    } else {
                        messageContainer.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-circle"></i> ${result.message || 'Failed to create restaurant'}</div>`;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-save"></i> Create Restaurant';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    messageContainer.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Network error. Please try again.</div>';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Create Restaurant';
                }
            });
        }
        
        // Load Edit Restaurant Form
        async function loadEditRestaurantForm(id) {
            document.getElementById('mainContent').innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner"></i><p>Loading restaurant...</p></div>';
            
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=get_restaurant&id=' + id);
                const result = await response.json();
                
                if (result.status === 'OK') {
                    const r = result.data;
                    
                    const html = `
                        <div class="page-header">
                            <h1 class="page-title">Edit Restaurant</h1>
                            <p class="page-subtitle">Update restaurant details and settings</p>
                        </div>
                        
                        <div id="formMessageContainer"></div>
                        
                        <div class="form-card">
                            <form id="editRestaurantForm">
                                <input type="hidden" name="id" value="${r.id}">
                                
                                <!-- Basic Information -->
                                <div class="form-section">
                                    <h3 class="section-title"><i class="fas fa-info-circle"></i> Basic Information</h3>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Restaurant Name <span class="required">*</span></label>
                                            <input type="text" name="name" required value="${r.name}">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Slug <span class="required">*</span></label>
                                            <input type="text" name="slug" required value="${r.slug}">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Email <span class="required">*</span></label>
                                            <input type="email" name="email" required value="${r.email}">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Phone</label>
                                            <input type="tel" name="phone" value="${r.phone || ''}">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group full-width">
                                        <label>Address</label>
                                        <textarea name="address">${r.address || ''}</textarea>
                                    </div>
                                </div>
                                
                                <!-- Subscription Settings -->
                                <div class="form-section">
                                    <h3 class="section-title"><i class="fas fa-credit-card"></i> Subscription Settings</h3>
                                    
                                    <div class="form-group full-width">
                                        <label>Subscription Plan <span class="required">*</span></label>
                                        <div class="plan-options">
                                            <div class="plan-option">
                                                <input type="radio" name="subscription_plan" id="edit_plan_trial" value="trial" ${r.subscription_plan === 'trial' ? 'checked' : ''}>
                                                <label for="edit_plan_trial" class="plan-label">
                                                    <div class="plan-name">Trial</div>
                                                    <div class="plan-price">Free / 30 days</div>
                                                </label>
                                            </div>
                                            
                                            <div class="plan-option">
                                                <input type="radio" name="subscription_plan" id="edit_plan_basic" value="basic" ${r.subscription_plan === 'basic' ? 'checked' : ''}>
                                                <label for="edit_plan_basic" class="plan-label">
                                                    <div class="plan-name">Basic</div>
                                                    <div class="plan-price">29,000 RWF/mo</div>
                                                </label>
                                            </div>
                                            
                                            <div class="plan-option">
                                                <input type="radio" name="subscription_plan" id="edit_plan_premium" value="premium" ${r.subscription_plan === 'premium' ? 'checked' : ''}>
                                                <label for="edit_plan_premium" class="plan-label">
                                                    <div class="plan-name">Premium</div>
                                                    <div class="plan-price">79,000 RWF/mo</div>
                                                </label>
                                            </div>
                                            
                                            <div class="plan-option">
                                                <input type="radio" name="subscription_plan" id="edit_plan_enterprise" value="enterprise" ${r.subscription_plan === 'enterprise' ? 'checked' : ''}>
                                                <label for="edit_plan_enterprise" class="plan-label">
                                                    <div class="plan-name">Enterprise</div>
                                                    <div class="plan-price">Custom</div>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Subscription End Date</label>
                                            <input type="date" name="subscription_end" value="${r.subscription_end || ''}">
                                        </div>
                                        
                                        <div class="form-group">
                                            <div class="checkbox-group">
                                                <input type="checkbox" name="is_active" id="edit_is_active" value="1" ${r.is_active ? 'checked' : ''}>
                                                <label for="edit_is_active">Active Status</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Resource Limits -->
                                <div class="form-section">
                                    <h3 class="section-title"><i class="fas fa-sliders-h"></i> Resource Limits</h3>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Max Tables</label>
                                            <input type="number" name="max_tables" min="0" value="${r.max_tables || 20}">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Max Users</label>
                                            <input type="number" name="max_users" min="0" value="${r.max_users || 10}">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Form Actions -->
                                <div class="form-actions">
                                    <button type="button" class="btn btn-secondary" onclick="loadSection('dashboard')">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                                        <i class="fas fa-save"></i> Update Restaurant
                                    </button>
                                </div>
                            </form>
                        </div>
                    `;
                    
                    document.getElementById('mainContent').innerHTML = html;
                    setupEditRestaurantForm();
                } else {
                    showError(result.message || 'Failed to load restaurant');
                    loadSection('dashboard');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load restaurant');
                loadSection('dashboard');
            }
        }
        
        function setupEditRestaurantForm() {
            document.getElementById('editRestaurantForm').addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = document.getElementById('editSubmitBtn');
                const messageContainer = document.getElementById('formMessageContainer');
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                
                const formData = new FormData(this);
                const data = {};
                
                formData.forEach((value, key) => {
                    if (key === 'is_active') {
                        data[key] = formData.get(key) ? 1 : 0;
                    } else {
                        data[key] = value;
                    }
                });
                
                if (!data.is_active) data.is_active = 0;
                
                try {
                    const basePath = getBasePath();
                    const response = await fetch(basePath + '?req=superadmin&action=update_restaurant', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    
                    const result = await response.json();
                    
                    if (result.status === 'OK') {
                        messageContainer.innerHTML = `<div class="success-message"><i class="fas fa-check-circle"></i> ${result.message}</div>`;
                        setTimeout(() => loadSection('dashboard'), 1500);
                    } else {
                        messageContainer.innerHTML = `<div class="error-message"><i class="fas fa-exclamation-circle"></i> ${result.message || 'Failed to update restaurant'}</div>`;
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Restaurant';
                    }
                } catch (error) {
                    console.error('Error:', error);
                    messageContainer.innerHTML = '<div class="error-message"><i class="fas fa-exclamation-circle"></i> Network error. Please try again.</div>';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Update Restaurant';
                }
            });
        }
        
        // Load Coming Soon sections
        function loadComingSoon(section) {
            const titles = {
                'subscriptions': 'Subscription Management',
                'users': 'User Management',
                'roles': 'Roles & Permissions',
                'analytics': 'Analytics Dashboard',
                'reports': 'Reports',
                'audit-log': 'Audit Log',
                'tickets': 'Support Tickets',
                'messages': 'Messages',
                'notifications': 'Notifications',
                'settings': 'System Settings',
                'backup': 'Backup & Restore',
                'logs': 'System Logs'
            };
            
            const html = `
                <div class="page-header">
                    <h1 class="page-title">${titles[section] || 'Feature'}</h1>
                    <p class="page-subtitle">This feature is coming soon</p>
                </div>
                
                <div style="text-align: center; padding: 5rem 2rem;">
                    <i class="fas fa-tools" style="font-size: 5rem; color: #bdc3c7; margin-bottom: 2rem;"></i>
                    <h2 style="color: #7f8c8d; margin-bottom: 1rem;">Coming Soon</h2>
                    <p style="color: #95a5a6;">This feature is currently under development and will be available soon.</p>
                    <button class="btn btn-primary" onclick="loadSection('dashboard')" style="margin-top: 2rem;">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </button>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = html;
        }
        
        // Load Users Management Section
        function loadUsers() {
            const html = `
                <div class="page-header">
                    <h1 class="page-title">User Management</h1>
                    <p class="page-subtitle">Manage all staff users across all restaurants</p>
                </div>
                
                <div id="messageContainer"></div>
                
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <div class="form-group">
                        <label>Search Users</label>
                        <input type="text" id="userSearch" placeholder="Search by name or email...">
                    </div>
                    
                    <div class="form-group">
                        <label>Filter by Restaurant</label>
                        <select id="filterRestaurant">
                            <option value="">All Restaurants</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Filter by Role</label>
                        <select id="filterRole">
                            <option value="">All Roles</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="waiter">Waiter</option>
                            <option value="kitchen">Kitchen</option>
                            <option value="cashier">Cashier</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select id="filterStatus">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    
                    <button class="btn btn-primary" onclick="applyUserFilters()">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                </div>
                
                <!-- Users Table -->
                <div class="table-card">
                    <div class="table-header">
                        <h3 class="table-title">All Users</h3>
                        <div class="table-actions">
                            <button class="btn btn-secondary" onclick="exportUsers()"><i class="fas fa-download"></i> Export</button>
                            <button class="btn btn-primary" onclick="showAddUserModal()"><i class="fas fa-plus"></i> Add New User</button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Email</th>
                                <th>Restaurant</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="usersTableBody">
                            <tr>
                                <td colspan="8">
                                    <div class="loading-spinner">
                                        <i class="fas fa-spinner"></i>
                                        <p>Loading users...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = html;
            loadUsersData();
        }
        
        // Load Users Data
        async function loadUsersData(filters = {}) {
            try {
                const basePath = getBasePath();
                
                // First, load restaurants for filter dropdown
                const restaurantsResponse = await fetch(basePath + '?req=superadmin&action=list_restaurants&format=json');
                const restaurantsResult = await restaurantsResponse.json();
                
                if (restaurantsResult.status === 'OK') {
                    const restaurantSelect = document.getElementById('filterRestaurant');
                    if (restaurantSelect) {
                        restaurantSelect.innerHTML = '<option value="">All Restaurants</option>' +
                            restaurantsResult.data.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
                    }
                }
                
                // Load all users from database
                const response = await fetch(basePath + '?req=superadmin&action=get_all_users&format=json');
                const result = await response.json();
                
                if (result.status === 'OK') {
                    displayUsers(result.data, filters);
                } else {
                    showError(result.message || 'Failed to load users');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load users');
            }
        }
        
        function displayUsers(users, filters = {}) {
            const tbody = document.getElementById('usersTableBody');
            
            // Apply filters
            let filteredUsers = users;
            
            if (filters.search) {
                const searchLower = filters.search.toLowerCase();
                filteredUsers = filteredUsers.filter(u => 
                    u.name.toLowerCase().includes(searchLower) || 
                    u.email.toLowerCase().includes(searchLower)
                );
            }
            
            if (filters.restaurant) {
                filteredUsers = filteredUsers.filter(u => u.restaurant_id == filters.restaurant);
            }
            
            if (filters.role) {
                filteredUsers = filteredUsers.filter(u => u.role === filters.role);
            }
            
            if (filters.status !== undefined && filters.status !== '') {
                filteredUsers = filteredUsers.filter(u => u.is_active == filters.status);
            }
            
            if (filteredUsers.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 3rem; color: #95a5a6;">No users found</td></tr>';
                return;
            }
            
            tbody.innerHTML = filteredUsers.map(u => `
                <tr>
                    <td>
                        <div class="restaurant-info">
                            <div class="restaurant-logo">${u.name.charAt(0).toUpperCase()}</div>
                            <div>
                                <div class="restaurant-name">${u.name}</div>
                            </div>
                        </div>
                    </td>
                    <td>${u.email}</td>
                    <td>${u.restaurant_name || '<span style="color: #95a5a6;">Super Admin</span>'}</td>
                    <td>
                        <span class="badge-plan badge-${u.role}" style="text-transform: capitalize;">${u.role}</span>
                    </td>
                    <td>
                        <span class="badge-status badge-${u.is_active == 1 ? 'active' : 'inactive'}">
                            ${u.is_active == 1 ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                    <td>${u.last_login || '-'}</td>
                    <td>${u.created_at || '-'}</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon btn-view" onclick="viewUser(${u.id})" title="View Details">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn-icon btn-edit" onclick="toggleUserStatus(${u.id}, ${u.is_active})" title="${u.is_active == 1 ? 'Deactivate' : 'Activate'}">
                                <i class="fas fa-${u.is_active == 1 ? 'ban' : 'check'}"></i>
                            </button>
                            <button class="btn-icon btn-delete" onclick="resetUserPassword(${u.id}, '${u.email}')" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
        
        function applyUserFilters() {
            const filters = {
                search: document.getElementById('userSearch')?.value || '',
                restaurant: document.getElementById('filterRestaurant')?.value || '',
                role: document.getElementById('filterRole')?.value || '',
                status: document.getElementById('filterStatus')?.value
            };
            
            loadUsersData(filters);
        }
        
        async function viewUser(userId) {
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + `?req=superadmin&action=get_user_details&user_id=${userId}&format=json`);
                const result = await response.json();
                
                if (result.status === 'OK') {
                    const user = result.data;
                    showUserDetailsModal(user);
                } else {
                    showError(result.message || 'Failed to load user details');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load user details');
            }
        }
        
        function showUserDetailsModal(user) {
            const roleIcons = {
                admin: 'user-shield',
                manager: 'user-tie',
                waiter: 'concierge-bell',
                kitchen: 'utensils',
                cashier: 'cash-register'
            };
            
            const roleColors = {
                admin: '#3498db',
                manager: '#2ecc71',
                waiter: '#f39c12',
                kitchen: '#9b59b6',
                cashier: '#2c3e50'
            };
            
            const modalHtml = `
                <div class="modal-overlay" id="userDetailsModal" onclick="closeUserDetailsModal(event)">
                    <div class="modal-content" style="max-width: 700px;" onclick="event.stopPropagation()">
                        <div class="modal-header">
                            <h3><i class="fas fa-user"></i> User Details</h3>
                            <button class="modal-close" onclick="closeUserDetailsModal()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div style="display: grid; gap: 2rem;">
                                <!-- User Profile Section -->
                                <div style="display: flex; align-items: center; gap: 1.5rem; padding: 1.5rem; background: #f8f9fa; border-radius: 12px;">
                                    <div style="width: 80px; height: 80px; background: ${roleColors[user.role]}; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: 600;">
                                        ${user.full_name.charAt(0).toUpperCase()}
                                    </div>
                                    <div style="flex: 1;">
                                        <h3 style="margin: 0 0 0.5rem 0; color: #2c3e50;">${user.full_name}</h3>
                                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                                            <span class="badge-plan badge-${user.role}" style="text-transform: capitalize;">
                                                <i class="fas fa-${roleIcons[user.role]}"></i> ${user.role}
                                            </span>
                                            <span class="badge-status badge-${user.is_active == 1 ? 'active' : 'inactive'}">
                                                ${user.is_active == 1 ? 'Active' : 'Inactive'}
                                            </span>
                                        </div>
                                        <div style="color: #7f8c8d; font-size: 0.9rem;">
                                            <i class="fas fa-building"></i> ${user.restaurant_name || 'Super Admin'}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Contact Information -->
                                <div>
                                    <h4 style="margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.1rem;">
                                        <i class="fas fa-address-card"></i> Contact Information
                                    </h4>
                                    <div style="display: grid; gap: 1rem;">
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <div style="width: 40px; height: 40px; background: #ebf5fb; color: #3498db; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                            <div>
                                                <div style="font-size: 0.85rem; color: #7f8c8d;">Email</div>
                                                <div style="font-weight: 500;">${user.email || 'Not provided'}</div>
                                            </div>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <div style="width: 40px; height: 40px; background: #eafaf1; color: #2ecc71; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-phone"></i>
                                            </div>
                                            <div>
                                                <div style="font-size: 0.85rem; color: #7f8c8d;">Phone</div>
                                                <div style="font-weight: 500;">${user.phone || 'Not provided'}</div>
                                            </div>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 1rem;">
                                            <div style="width: 40px; height: 40px; background: #fef5e7; color: #f39c12; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <div style="font-size: 0.85rem; color: #7f8c8d;">Username</div>
                                                <div style="font-weight: 500;">${user.username}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Account Information -->
                                <div>
                                    <h4 style="margin: 0 0 1rem 0; color: #2c3e50; font-size: 1.1rem;">
                                        <i class="fas fa-info-circle"></i> Account Information
                                    </h4>
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
                                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                            <div style="font-size: 0.85rem; color: #7f8c8d; margin-bottom: 0.25rem;">User ID</div>
                                            <div style="font-weight: 600; color: #2c3e50;">#${user.id}</div>
                                        </div>
                                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                            <div style="font-size: 0.85rem; color: #7f8c8d; margin-bottom: 0.25rem;">Restaurant ID</div>
                                            <div style="font-weight: 600; color: #2c3e50;">#${user.restaurant_id || 'N/A'}</div>
                                        </div>
                                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                            <div style="font-size: 0.85rem; color: #7f8c8d; margin-bottom: 0.25rem;">Last Login</div>
                                            <div style="font-weight: 600; color: #2c3e50;">${user.last_login_formatted || 'Never'}</div>
                                        </div>
                                        <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                            <div style="font-size: 0.85rem; color: #7f8c8d; margin-bottom: 0.25rem;">Created</div>
                                            <div style="font-weight: 600; color: #2c3e50;">${user.created_at_formatted}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" onclick="closeUserDetailsModal()">Close</button>
                            <button class="btn btn-primary" onclick="closeUserDetailsModal(); showEditUserModal(${user.id})">
                                <i class="fas fa-edit"></i> Edit User
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }
        
        function closeUserDetailsModal(event) {
            if (event && event.target.className !== 'modal-overlay') return;
            const modal = document.getElementById('userDetailsModal');
            if (modal) modal.remove();
        }
        
        async function toggleUserStatus(userId, currentStatus) {
            const action = currentStatus == 1 ? 'deactivate' : 'activate';
            
            if (!confirm(`Are you sure you want to ${action} this user?`)) {
                return;
            }
            
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=toggle_user_status', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, is_active: currentStatus == 1 ? 0 : 1 })
                });
                
                const result = await response.json();
                
                if (result.status === 'OK') {
                    showSuccess(`User ${action}d successfully`);
                    applyUserFilters();
                } else {
                    showError(result.message || `Failed to ${action} user`);
                }
            } catch (error) {
                console.error('Error:', error);
                showError(`Failed to ${action} user`);
            }
        }
        
        async function showAddUserModal() {
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=list_restaurants&format=json');
                const result = await response.json();
                
                let restaurantOptions = '';
                if (result.status === 'OK') {
                    restaurantOptions = result.data.map(r => `<option value="${r.id}">${r.name}</option>`).join('');
                }
                
                const modalHtml = `
                    <div class="modal-overlay" id="addUserModal" onclick="closeAddUserModal(event)">
                        <div class="modal-content" style="max-width: 600px;" onclick="event.stopPropagation()">
                            <div class="modal-header">
                                <h3><i class="fas fa-user-plus"></i> Add New User</h3>
                                <button class="modal-close" onclick="closeAddUserModal()">&times;</button>
                            </div>
                            <form id="addUserForm" onsubmit="submitAddUser(event)">
                                <div class="modal-body">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Full Name *</label>
                                            <input type="text" name="full_name" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Email *</label>
                                            <input type="email" name="email" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Username *</label>
                                            <input type="text" name="username" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Phone</label>
                                            <input type="tel" name="phone">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Restaurant *</label>
                                            <select name="restaurant_id" required>
                                                <option value="">Select Restaurant</option>
                                                ${restaurantOptions}
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Role *</label>
                                            <select name="role" required>
                                                <option value="">Select Role</option>
                                                <option value="admin">Admin</option>
                                                <option value="manager">Manager</option>
                                                <option value="waiter">Waiter</option>
                                                <option value="kitchen">Kitchen Staff</option>
                                                <option value="cashier">Cashier</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="alert" style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 1rem; margin-top: 1rem;">
                                        <i class="fas fa-info-circle" style="color: #856404;"></i>
                                        <span style="color: #856404; margin-left: 0.5rem;">A temporary password will be generated and shown after creation.</span>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Create User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHtml);
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load restaurant list');
            }
        }
        
        function closeAddUserModal(event) {
            if (event && event.target.className !== 'modal-overlay') return;
            const modal = document.getElementById('addUserModal');
            if (modal) modal.remove();
        }
        
        async function submitAddUser(event) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=create_user', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.status === 'OK') {
                    closeAddUserModal();
                    alert(`User created successfully!\n\nTemporary Password: ${result.temp_password}\n\nPlease share this with the user and ask them to change it after login.`);
                    showSuccess('User created successfully');
                    applyUserFilters();
                } else {
                    showError(result.message || 'Failed to create user');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to create user');
            }
        }
        
        async function showEditUserModal(userId) {
            try {
                const basePath = getBasePath();
                
                // Get user details
                const userResponse = await fetch(basePath + `?req=superadmin&action=get_user_details&user_id=${userId}&format=json`);
                const userResult = await userResponse.json();
                
                if (userResult.status !== 'OK') {
                    showError('Failed to load user details');
                    return;
                }
                
                const user = userResult.data;
                
                // Get restaurants list
                const restResponse = await fetch(basePath + '?req=superadmin&action=list_restaurants&format=json');
                const restResult = await restResponse.json();
                
                let restaurantOptions = '';
                if (restResult.status === 'OK') {
                    restaurantOptions = restResult.data.map(r => 
                        `<option value="${r.id}" ${r.id == user.restaurant_id ? 'selected' : ''}>${r.name}</option>`
                    ).join('');
                }
                
                const modalHtml = `
                    <div class="modal-overlay" id="editUserModal" onclick="closeEditUserModal(event)">
                        <div class="modal-content" style="max-width: 600px;" onclick="event.stopPropagation()">
                            <div class="modal-header">
                                <h3><i class="fas fa-user-edit"></i> Edit User</h3>
                                <button class="modal-close" onclick="closeEditUserModal()">&times;</button>
                            </div>
                            <form id="editUserForm" onsubmit="submitEditUser(event, ${userId})">
                                <div class="modal-body">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Full Name *</label>
                                            <input type="text" name="full_name" value="${user.full_name}" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Email *</label>
                                            <input type="email" name="email" value="${user.email}" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Username *</label>
                                            <input type="text" name="username" value="${user.username}" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Phone</label>
                                            <input type="tel" name="phone" value="${user.phone || ''}">
                                        </div>
                                    </div>
                                    
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Restaurant *</label>
                                            <select name="restaurant_id" required>
                                                ${restaurantOptions}
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Role *</label>
                                            <select name="role" required>
                                                <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                                                <option value="manager" ${user.role === 'manager' ? 'selected' : ''}>Manager</option>
                                                <option value="waiter" ${user.role === 'waiter' ? 'selected' : ''}>Waiter</option>
                                                <option value="kitchen" ${user.role === 'kitchen' ? 'selected' : ''}>Kitchen Staff</option>
                                                <option value="cashier" ${user.role === 'cashier' ? 'selected' : ''}>Cashier</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update User
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHtml);
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load edit form');
            }
        }
        
        function closeEditUserModal(event) {
            if (event && event.target.className !== 'modal-overlay') return;
            const modal = document.getElementById('editUserModal');
            if (modal) modal.remove();
        }
        
        async function submitEditUser(event, userId) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.user_id = userId;
            
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=update_user', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.status === 'OK') {
                    closeEditUserModal();
                    showSuccess('User updated successfully');
                    applyUserFilters();
                } else {
                    showError(result.message || 'Failed to update user');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to update user');
            }
        }
        
        function exportUsers() {
            const basePath = getBasePath();
            window.location.href = basePath + '?req=superadmin&action=export_users';
        }
        
        async function resetUserPassword(userId, email) {
            if (!confirm(`Reset password for ${email}?\n\nA new temporary password will be generated.`)) {
                return;
            }
            
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=reset_user_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId })
                });
                
                const result = await response.json();
                
                if (result.status === 'OK') {
                    alert(`Password reset successfully!\n\nNew temporary password: ${result.new_password}\n\nPlease share this with the user and ask them to change it after login.`);
                    showSuccess('Password reset successfully');
                } else {
                    showError(result.message || 'Failed to reset password');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to reset password');
            }
        }
        
        // Load Roles & Permissions Section
        function loadRoles() {
            const html = `
                <div class="page-header">
                    <h1 class="page-title">Roles & Permissions</h1>
                    <p class="page-subtitle">Manage user roles and their access levels across the system</p>
                </div>
                
                <div id="messageContainer"></div>
                
                <!-- Role Statistics -->
                <div class="stats-grid" style="grid-template-columns: repeat(5, 1fr);">
                    <div class="stat-card blue">
                        <div class="stat-header">
                            <div><div class="stat-title">Admins</div></div>
                            <div class="stat-icon blue"><i class="fas fa-user-shield"></i></div>
                        </div>
                        <div class="stat-value" id="adminCount">0</div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-header">
                            <div><div class="stat-title">Managers</div></div>
                            <div class="stat-icon green"><i class="fas fa-user-tie"></i></div>
                        </div>
                        <div class="stat-value" id="managerCount">0</div>
                    </div>
                    <div class="stat-card orange">
                        <div class="stat-header">
                            <div><div class="stat-title">Waiters</div></div>
                            <div class="stat-icon orange"><i class="fas fa-concierge-bell"></i></div>
                        </div>
                        <div class="stat-value" id="waiterCount">0</div>
                    </div>
                    <div class="stat-card purple">
                        <div class="stat-header">
                            <div><div class="stat-title">Kitchen</div></div>
                            <div class="stat-icon purple"><i class="fas fa-utensils"></i></div>
                        </div>
                        <div class="stat-value" id="kitchenCount">0</div>
                    </div>
                    <div class="stat-card" style="border-left: 4px solid #2c3e50;">
                        <div class="stat-header">
                            <div><div class="stat-title">Cashiers</div></div>
                            <div class="stat-icon" style="background: #ecf0f1; color: #2c3e50;"><i class="fas fa-cash-register"></i></div>
                        </div>
                        <div class="stat-value" id="cashierCount">0</div>
                    </div>
                </div>
                
                <!-- Role Definitions -->
                <div class="form-card">
                    <h3 class="section-title"><i class="fas fa-info-circle"></i> Role Definitions & Permissions</h3>
                    
                    <div class="role-cards-grid">
                        <!-- Admin Role -->
                        <div class="role-card">
                            <div class="role-header">
                                <div class="role-icon admin"><i class="fas fa-user-shield"></i></div>
                                <div>
                                    <h4 class="role-name">Admin (Restaurant Owner)</h4>
                                    <p class="role-description">Full control of their restaurant</p>
                                </div>
                            </div>
                            <div class="role-permissions">
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Manage menu items
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Add/remove staff users
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> View all reports
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Manage tables & orders
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Handle cash sessions
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Configure settings
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> Access other restaurants
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> Change subscription
                                </div>
                            </div>
                        </div>
                        
                        <!-- Manager Role -->
                        <div class="role-card">
                            <div class="role-header">
                                <div class="role-icon manager"><i class="fas fa-user-tie"></i></div>
                                <div>
                                    <h4 class="role-name">Manager</h4>
                                    <p class="role-description">Daily operations management</p>
                                </div>
                            </div>
                            <div class="role-permissions">
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Manage orders
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Approve requests
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> View reports
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Edit menu items
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Manage cash sessions
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> Add/remove staff
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> Delete restaurant
                                </div>
                            </div>
                        </div>
                        
                        <!-- Waiter Role -->
                        <div class="role-card">
                            <div class="role-header">
                                <div class="role-icon waiter"><i class="fas fa-concierge-bell"></i></div>
                                <div>
                                    <h4 class="role-name">Waiter</h4>
                                    <p class="role-description">Order taking & service</p>
                                </div>
                            </div>
                            <div class="role-permissions">
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Take orders
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> View menu
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Assign to tables
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Request approvals
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Process payments
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> Edit menu
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> View reports
                                </div>
                            </div>
                        </div>
                        
                        <!-- Kitchen Role -->
                        <div class="role-card">
                            <div class="role-header">
                                <div class="role-icon kitchen"><i class="fas fa-utensils"></i></div>
                                <div>
                                    <h4 class="role-name">Kitchen Staff</h4>
                                    <p class="role-description">Food preparation</p>
                                </div>
                            </div>
                            <div class="role-permissions">
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> View orders
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Update status
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Mark ready
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> Take new orders
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> Edit menu
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> Process payments
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cashier Role -->
                        <div class="role-card">
                            <div class="role-header">
                                <div class="role-icon cashier"><i class="fas fa-cash-register"></i></div>
                                <div>
                                    <h4 class="role-name">Cashier</h4>
                                    <p class="role-description">Payment processing</p>
                                </div>
                            </div>
                            <div class="role-permissions">
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Process payments
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Manage cash sessions
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Handle refunds
                                </div>
                                <div class="permission-item">
                                    <i class="fas fa-check"></i> Print receipts
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> Edit menu
                                </div>
                                <div class="permission-item denied">
                                    <i class="fas fa-times"></i> Manage users
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Role Distribution Table -->
                <div class="table-card">
                    <div class="table-header">
                        <h3 class="table-title">Users by Role</h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Role</th>
                                <th>Total Users</th>
                                <th>Active</th>
                                <th>Inactive</th>
                                <th>Percentage</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="roleDistributionBody">
                            <tr>
                                <td colspan="6">
                                    <div class="loading-spinner">
                                        <i class="fas fa-spinner"></i>
                                        <p>Loading role distribution...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = html;
            loadRoleData();
        }
        
        async function loadRoleData() {
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=get_all_users&format=json');
                const result = await response.json();
                
                if (result.status === 'OK') {
                    const users = result.data;
                    
                    // Count users by role
                    const roleCounts = {
                        admin: 0,
                        manager: 0,
                        waiter: 0,
                        kitchen: 0,
                        cashier: 0
                    };
                    
                    const roleActive = {
                        admin: 0,
                        manager: 0,
                        waiter: 0,
                        kitchen: 0,
                        cashier: 0
                    };
                    
                    users.forEach(u => {
                        if (roleCounts.hasOwnProperty(u.role)) {
                            roleCounts[u.role]++;
                            if (u.is_active == 1) roleActive[u.role]++;
                        }
                    });
                    
                    // Update stat cards
                    document.getElementById('adminCount').textContent = roleCounts.admin;
                    document.getElementById('managerCount').textContent = roleCounts.manager;
                    document.getElementById('waiterCount').textContent = roleCounts.waiter;
                    document.getElementById('kitchenCount').textContent = roleCounts.kitchen;
                    document.getElementById('cashierCount').textContent = roleCounts.cashier;
                    
                    // Display role distribution
                    displayRoleDistribution(roleCounts, roleActive, users.length);
                } else {
                    showError(result.message || 'Failed to load role data');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load role data');
            }
        }
        
        function displayRoleDistribution(counts, active, total) {
            const tbody = document.getElementById('roleDistributionBody');
            
            const roles = [
                { name: 'Admin', key: 'admin', icon: 'user-shield', color: 'blue' },
                { name: 'Manager', key: 'manager', icon: 'user-tie', color: 'green' },
                { name: 'Waiter', key: 'waiter', icon: 'concierge-bell', color: 'orange' },
                { name: 'Kitchen', key: 'kitchen', icon: 'utensils', color: 'purple' },
                { name: 'Cashier', key: 'cashier', icon: 'cash-register', color: '#2c3e50' }
            ];
            
            tbody.innerHTML = roles.map(role => {
                const count = counts[role.key];
                const activeCount = active[role.key];
                const inactiveCount = count - activeCount;
                const percentage = total > 0 ? ((count / total) * 100).toFixed(1) : 0;
                
                return `
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <div class="stat-icon ${role.color}" style="width: 40px; height: 40px; font-size: 1rem;">
                                    <i class="fas fa-${role.icon}"></i>
                                </div>
                                <span style="font-weight: 600;">${role.name}</span>
                            </div>
                        </td>
                        <td><strong>${count}</strong></td>
                        <td><span class="badge-status badge-active">${activeCount}</span></td>
                        <td><span class="badge-status badge-inactive">${inactiveCount}</span></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <div style="flex: 1; background: #e9ecef; height: 8px; border-radius: 4px; overflow: hidden;">
                                    <div style="width: ${percentage}%; height: 100%; background: ${role.color === 'blue' ? '#3498db' : role.color === 'green' ? '#2ecc71' : role.color === 'orange' ? '#f39c12' : role.color === 'purple' ? '#9b59b6' : '#2c3e50'};"></div>
                                </div>
                                <span style="font-size: 0.85rem; color: #7f8c8d;">${percentage}%</span>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-secondary" style="padding: 0.5rem 1rem; font-size: 0.85rem;" onclick="filterUsersByRole('${role.key}')">
                                <i class="fas fa-filter"></i> View Users
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }
        
        function filterUsersByRole(role) {
            loadSection('users');
            setTimeout(() => {
                const roleSelect = document.getElementById('filterRole');
                if (roleSelect) {
                    roleSelect.value = role;
                    applyUserFilters();
                }
            }, 500);
        }
        
        // Load Analytics Section
        function loadAnalytics() {
            const html = `
                <div class="page-header">
                    <h1 class="page-title">Analytics & Insights</h1>
                    <p class="page-subtitle">Real-time business intelligence and performance metrics</p>
                </div>
                
                <div id="messageContainer"></div>
                
                <!-- Key Metrics -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-header">
                            <div><div class="stat-title">Total Revenue</div></div>
                            <div class="stat-icon blue"><i class="fas fa-dollar-sign"></i></div>
                        </div>
                        <div class="stat-value" id="totalRevenue">$0</div>
                        <div class="stat-subtitle">From completed orders</div>
                    </div>
                    <div class="stat-card green">
                        <div class="stat-header">
                            <div><div class="stat-title">Total Orders</div></div>
                            <div class="stat-icon green"><i class="fas fa-shopping-cart"></i></div>
                        </div>
                        <div class="stat-value" id="totalOrders">0</div>
                        <div class="stat-subtitle">All time orders</div>
                    </div>
                    <div class="stat-card orange">
                        <div class="stat-header">
                            <div><div class="stat-title">Active Restaurants</div></div>
                            <div class="stat-icon orange"><i class="fas fa-store"></i></div>
                        </div>
                        <div class="stat-value" id="activeRestaurants">0</div>
                        <div class="stat-subtitle">Currently active</div>
                    </div>
                    <div class="stat-card purple">
                        <div class="stat-header">
                            <div><div class="stat-title">Total Users</div></div>
                            <div class="stat-icon purple"><i class="fas fa-users"></i></div>
                        </div>
                        <div class="stat-value" id="totalSystemUsers">0</div>
                        <div class="stat-subtitle">Across all restaurants</div>
                    </div>
                </div>
                
                <!-- Chart Controls -->
                <div class="form-card" style="margin-bottom: 1.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 class="section-title"><i class="fas fa-chart-line"></i> Revenue Trends</h3>
                        <div style="display: flex; gap: 0.5rem;">
                            <button class="btn btn-secondary" onclick="changeRevenuePeriod('day')" id="periodDay">24 Hours</button>
                            <button class="btn btn-secondary" onclick="changeRevenuePeriod('week')" id="periodWeek">7 Days</button>
                            <button class="btn btn-primary" onclick="changeRevenuePeriod('month')" id="periodMonth">30 Days</button>
                            <button class="btn btn-secondary" onclick="changeRevenuePeriod('year')" id="periodYear">12 Months</button>
                        </div>
                    </div>
                    <canvas id="revenueChart" style="margin-top: 1.5rem; max-height: 350px;"></canvas>
                </div>
                
                <!-- Distribution Charts -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
                    <div class="form-card">
                        <h3 class="section-title"><i class="fas fa-chart-pie"></i> Subscription Plans</h3>
                        <canvas id="planChart" style="max-height: 300px;"></canvas>
                    </div>
                    <div class="form-card">
                        <h3 class="section-title"><i class="fas fa-chart-bar"></i> User Growth</h3>
                        <canvas id="userGrowthChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>
                
                <!-- Top Restaurants -->
                <div class="form-card">
                    <h3 class="section-title"><i class="fas fa-trophy"></i> Top Performing Restaurants</h3>
                    <canvas id="topRestaurantsChart" style="margin-top: 1.5rem; max-height: 350px;"></canvas>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = html;
            loadAnalyticsData();
        }
        
        let revenueChartInstance = null;
        let currentPeriod = 'month';
        const auditLogState = {
            page: 1,
            limit: 25,
            search: '',
            action: '',
            startDate: '',
            endDate: ''
        };
        let auditLogCache = [];
        let auditLogTotalPages = 1;
        const supportTicketState = {
            page: 1,
            limit: 15,
            status: '',
            priority: '',
            search: ''
        };
        let supportTicketCache = [];
        let supportOverviewCache = null;
        let ticketDetailCache = {};
        let supportMessagesCache = [];
        let notificationFeedCache = [];
        let notificationDropdownOpen = false;
        let userDropdownOpen = false;
        
        async function loadAnalyticsData() {
            try {
                const basePath = getBasePath();
                
                // Load overview analytics
                const analyticsResponse = await fetch(basePath + '?req=superadmin&action=get_analytics_data&format=json');
                const analyticsResult = await analyticsResponse.json();
                
                if (analyticsResult.status === 'OK') {
                    const data = analyticsResult.data;
                    
                    // Update stat cards
                    document.getElementById('totalRevenue').textContent = '$' + data.overview.total_revenue;
                    document.getElementById('totalOrders').textContent = data.overview.total_orders;
                    document.getElementById('activeRestaurants').textContent = data.overview.total_restaurants;
                    document.getElementById('totalSystemUsers').textContent = data.overview.total_users;
                    
                    // Create plan distribution chart
                    createPlanChart(data.plan_distribution);
                    
                    // Create user growth chart
                    createUserGrowthChart(data.user_growth);
                }
                
                // Load revenue data
                loadRevenueChart(currentPeriod);
                
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load analytics data');
            }
        }
        
        async function loadRevenueChart(period) {
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + `?req=superadmin&action=get_revenue_data&period=${period}&format=json`);
                const result = await response.json();
                
                if (result.status === 'OK') {
                    createRevenueChart(result.data.revenue_timeline);
                    createTopRestaurantsChart(result.data.revenue_by_restaurant);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        function changeRevenuePeriod(period) {
            currentPeriod = period;
            
            // Update button states
            ['periodDay', 'periodWeek', 'periodMonth', 'periodYear'].forEach(id => {
                document.getElementById(id).className = 'btn btn-secondary';
            });
            document.getElementById('period' + period.charAt(0).toUpperCase() + period.slice(1)).className = 'btn btn-primary';
            
            loadRevenueChart(period);
        }
        
        function createRevenueChart(data) {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            if (revenueChartInstance) {
                revenueChartInstance.destroy();
            }
            
            revenueChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(d => d.period),
                    datasets: [{
                        label: 'Revenue ($)',
                        data: data.map(d => parseFloat(d.revenue)),
                        borderColor: '#3498db',
                        backgroundColor: 'rgba(52, 152, 219, 0.1)',
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: $' + context.parsed.y.toFixed(2);
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        function createPlanChart(data) {
            const ctx = document.getElementById('planChart').getContext('2d');
            
            const colors = {
                basic: '#95a5a6',
                standard: '#3498db',
                premium: '#f39c12',
                enterprise: '#9b59b6'
            };
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.map(d => d.subscription_plan.charAt(0).toUpperCase() + d.subscription_plan.slice(1)),
                    datasets: [{
                        data: data.map(d => d.count),
                        backgroundColor: data.map(d => colors[d.subscription_plan] || '#2c3e50')
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });
        }
        
        function createUserGrowthChart(data) {
            const ctx = document.getElementById('userGrowthChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.month),
                    datasets: [{
                        label: 'New Users',
                        data: data.map(d => d.count),
                        backgroundColor: '#2ecc71'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        }
        
        function createTopRestaurantsChart(data) {
            const ctx = document.getElementById('topRestaurantsChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.map(d => d.restaurant_name),
                    datasets: [{
                        label: 'Revenue ($)',
                        data: data.map(d => parseFloat(d.revenue)),
                        backgroundColor: '#3498db'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: $' + context.parsed.x.toFixed(2) + ' (' + data[context.dataIndex].order_count + ' orders)';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Load Reports Section
        function loadReports() {
            const html = `
                <div class="page-header">
                    <h1 class="page-title">Reports & Exports</h1>
                    <p class="page-subtitle">Generate detailed reports and export data</p>
                </div>
                
                <div id="messageContainer"></div>
                
                <!-- Quick Export Actions -->
                <div class="form-card" style="margin-bottom: 1.5rem;">
                    <h3 class="section-title"><i class="fas fa-download"></i> Quick Exports</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-top: 1.5rem;">
                        <button class="export-card" onclick="exportReport('overview')">
                            <div class="export-icon"><i class="fas fa-chart-line"></i></div>
                            <div class="export-title">Overview Report</div>
                            <div class="export-desc">System metrics summary</div>
                        </button>
                        <button class="export-card" onclick="exportReport('restaurant_performance')">
                            <div class="export-icon"><i class="fas fa-store"></i></div>
                            <div class="export-title">Restaurant Performance</div>
                            <div class="export-desc">Detailed performance data</div>
                        </button>
                        <button class="export-card" onclick="window.location.href = getBasePath() + '?req=superadmin&action=export_users'">
                            <div class="export-icon"><i class="fas fa-users"></i></div>
                            <div class="export-title">All Users</div>
                            <div class="export-desc">Complete user list</div>
                        </button>
                        <button class="export-card" onclick="alert('Custom report builder coming soon!')">
                            <div class="export-icon"><i class="fas fa-cog"></i></div>
                            <div class="export-title">Custom Report</div>
                            <div class="export-desc">Build your own report</div>
                        </button>
                    </div>
                </div>
                
                <!-- Restaurant Performance Table -->
                <div class="table-card">
                    <div class="table-header">
                        <h3 class="table-title">Restaurant Performance</h3>
                        <div class="table-actions">
                            <button class="btn btn-primary" onclick="exportReport('restaurant_performance')">
                                <i class="fas fa-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Restaurant</th>
                                <th>Plan</th>
                                <th>Users</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>Avg Order</th>
                                <th>Tables</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="performanceTableBody">
                            <tr>
                                <td colspan="8">
                                    <div class="loading-spinner">
                                        <i class="fas fa-spinner"></i>
                                        <p>Loading performance data...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = html;
            loadPerformanceData();
        }
        
        async function loadPerformanceData() {
            try {
                const basePath = getBasePath();
                const response = await fetch(basePath + '?req=superadmin&action=get_restaurant_performance&format=json');
                const result = await response.json();
                
                if (result.status === 'OK') {
                    displayPerformanceTable(result.data);
                } else {
                    showError(result.message || 'Failed to load performance data');
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load performance data');
            }
        }
        
        function displayPerformanceTable(data) {
            const tbody = document.getElementById('performanceTableBody');
            
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" style="text-align: center; padding: 3rem; color: #95a5a6;">No data available</td></tr>';
                return;
            }
            
            tbody.innerHTML = data.map(r => `
                <tr>
                    <td>
                        <div class="restaurant-info">
                            <div class="restaurant-logo">${r.name.charAt(0).toUpperCase()}</div>
                            <div>
                                <div class="restaurant-name">${r.name}</div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge-plan badge-${r.subscription_plan}">${r.subscription_plan}</span></td>
                    <td>${r.user_count}</td>
                    <td>${r.order_count}</td>
                    <td><strong>$${r.total_revenue}</strong></td>
                    <td>$${r.avg_order_value}</td>
                    <td>${r.table_count}</td>
                    <td>
                        <span class="badge-status badge-${r.is_active == 1 ? 'active' : 'inactive'}">
                            ${r.is_active == 1 ? 'Active' : 'Inactive'}
                        </span>
                    </td>
                </tr>
            `).join('');
        }
        
        function exportReport(type) {
            const basePath = getBasePath();
            window.location.href = basePath + `?req=superadmin&action=export_report&type=${type}`;
        }
        
        // Load Audit Log Section
        function loadAuditLog() {
            auditLogState.page = 1;
            const html = `
                <div class="page-header">
                    <h1 class="page-title">Audit Log</h1>
                    <p class="page-subtitle">Track sensitive activity taken by staff across all restaurants</p>
                </div>
                
                <div id="messageContainer"></div>
                
                <div class="filter-bar">
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" id="auditSearchInput" placeholder="Search by user, action or description" value="${auditLogState.search || ''}">
                    </div>
                    <div class="form-group">
                        <label>Action</label>
                        <select id="auditActionFilter">
                            <option value="">All Actions</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" id="auditStartDate" value="${auditLogState.startDate || ''}">
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" id="auditEndDate" value="${auditLogState.endDate || ''}">
                    </div>
                    <div class="form-group" style="align-self: flex-end; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <button class="btn btn-primary" onclick="applyAuditFilters()"><i class="fas fa-filter"></i> Apply</button>
                        <button class="btn btn-secondary" onclick="resetAuditFilters()"><i class="fas fa-undo"></i> Reset</button>
                        <button class="btn btn-primary" onclick="exportAuditLogData()"><i class="fas fa-download"></i> Export</button>
                    </div>
                </div>
                
                <div class="table-card">
                    <div class="table-header">
                        <h3 class="table-title">Recent Activity</h3>
                        <div class="table-actions">
                            <span id="auditTotalCount" class="table-subtitle"></span>
                        </div>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>IP / Device</th>
                                <th>Role</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="auditLogTableBody">
                            <tr>
                                <td colspan="6">
                                    <div class="loading-spinner">
                                        <i class="fas fa-spinner"></i>
                                        <p>Loading activity...</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="pagination" id="auditPagination"></div>
                </div>
            `;
            
            document.getElementById('mainContent').innerHTML = html;
            loadAuditLogData();
        }
        
        async function loadAuditLogData() {
            try {
                const basePath = getBasePath();
                const params = new URLSearchParams({
                    limit: auditLogState.limit,
                    page: auditLogState.page,
                    format: 'json'
                });
                
                if (auditLogState.search) params.append('search', auditLogState.search);
                if (auditLogState.action) params.append('action', auditLogState.action);
                if (auditLogState.startDate) params.append('start_date', auditLogState.startDate);
                if (auditLogState.endDate) params.append('end_date', auditLogState.endDate);
                
                const queryString = params.toString();
                const response = await fetch(basePath + '?req=superadmin&action=get_audit_log' + (queryString ? '&' + queryString : ''));
                const result = await response.json();
                
                if (result.status === 'OK') {
                    auditLogCache = result.data || [];
                    auditLogTotalPages = result.total_pages || 1;
                    
                    const countLabel = document.getElementById('auditTotalCount');
                    if (countLabel) {
                        const total = result.total || 0;
                        countLabel.textContent = `${total} entr${total === 1 ? 'y' : 'ies'}`;
                    }
                    
                    populateAuditActionFilter(result.actions || []);
                    renderAuditLogRows(auditLogCache);
                    renderAuditPagination(result.page || 1, auditLogTotalPages);
                } else {
                    showError(result.message || 'Failed to load audit log');
                    renderAuditLogRows([]);
                    renderAuditPagination(1, 1);
                }
            } catch (error) {
                console.error('Error:', error);
                showError('Failed to load audit log');
            }
        }
        
        function populateAuditActionFilter(actions) {
            const select = document.getElementById('auditActionFilter');
            if (!select) return;
            
            const currentValue = auditLogState.action || '';
            let options = '<option value=\"\">All Actions</option>';
            
            actions.forEach(action => {
                if (!action) return;
                const safeValue = escapeHtml(action);
                options += `<option value="${safeValue}">${safeValue}</option>`;
            });
            
            select.innerHTML = options;
            if (currentValue && !actions.includes(currentValue)) {
                const safeValue = escapeHtml(currentValue);
                select.insertAdjacentHTML('beforeend', `<option value="${safeValue}">${safeValue}</option>`);
            }
            select.value = currentValue;
        }
        
        function renderAuditLogRows(logs) {
            const tbody = document.getElementById('auditLogTableBody');
            if (!tbody) return;
            
            if (!logs || logs.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; padding: 3rem; color: #95a5a6;">No audit entries found</td></tr>';
                return;
            }
            
            const rows = logs.map(entry => {
                const initials = entry.full_name ? escapeHtml(entry.full_name.charAt(0).toUpperCase()) : '?';
                const name = entry.full_name ? escapeHtml(entry.full_name) : 'Unknown User';
                const email = entry.email ? escapeHtml(entry.email) : 'No email';
                const actionLabel = entry.action ? escapeHtml(entry.action) : '-';
                const actionClassRaw = entry.action ? entry.action.toString().toLowerCase().replace(/[^a-z0-9-]/g, '-') : 'default';
                const actionClass = actionClassRaw || 'default';
                const description = entry.description ? escapeHtml(entry.description) : '<span style="color: #95a5a6;">No description</span>';
                const ipAddress = entry.ip_address ? escapeHtml(entry.ip_address) : '-';
                const userAgentFull = entry.user_agent ? escapeHtml(entry.user_agent) : 'N/A';
                const userAgentShort = entry.user_agent 
                    ? escapeHtml(entry.user_agent.length > 60 ? entry.user_agent.substring(0, 60) + '…' : entry.user_agent)
                    : 'N/A';
                const role = entry.role ? escapeHtml(entry.role) : 'N/A';
                const dateDisplay = formatDateTime(entry.created_at);
                
                return `
                    <tr>
                        <td>
                            <div class="restaurant-info">
                                <div class="restaurant-logo">${initials}</div>
                                <div>
                                    <div class="restaurant-name">${name}</div>
                                    <div class="table-subtitle">${email}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge-plan badge-${actionClass}">${actionLabel}</span></td>
                        <td>${description}</td>
                        <td>
                            <div>
                                <strong>${ipAddress}</strong>
                                <div class="table-subtitle" title="${userAgentFull}">${userAgentShort}</div>
                            </div>
                        </td>
                        <td>${role}</td>
                        <td>${dateDisplay}</td>
                    </tr>
                `;
            }).join('');
            
            tbody.innerHTML = rows;
        }
        
        function renderAuditPagination(page, totalPages) {
            const container = document.getElementById('auditPagination');
            if (!container) return;
            
            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }
            
            const prevDisabled = page <= 1 ? 'disabled' : '';
            const nextDisabled = page >= totalPages ? 'disabled' : '';
            
            container.innerHTML = `
                <button class="btn btn-secondary" ${prevDisabled} onclick="changeAuditPage(${page - 1})">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <span class="pagination-info">Page ${page} of ${totalPages}</span>
                <button class="btn btn-secondary" ${nextDisabled} onclick="changeAuditPage(${page + 1})">
                    <i class="fas fa-chevron-right"></i>
                </button>
            `;
        }
        
        function applyAuditFilters() {
            auditLogState.search = document.getElementById('auditSearchInput')?.value.trim() || '';
            auditLogState.action = document.getElementById('auditActionFilter')?.value || '';
            auditLogState.startDate = document.getElementById('auditStartDate')?.value || '';
            auditLogState.endDate = document.getElementById('auditEndDate')?.value || '';
            auditLogState.page = 1;
            loadAuditLogData();
        }
        
        function resetAuditFilters() {
            auditLogState.search = '';
            auditLogState.action = '';
            auditLogState.startDate = '';
            auditLogState.endDate = '';
            auditLogState.page = 1;
            
            if (document.getElementById('auditSearchInput')) document.getElementById('auditSearchInput').value = '';
            if (document.getElementById('auditActionFilter')) document.getElementById('auditActionFilter').value = '';
            if (document.getElementById('auditStartDate')) document.getElementById('auditStartDate').value = '';
            if (document.getElementById('auditEndDate')) document.getElementById('auditEndDate').value = '';
            
            loadAuditLogData();
        }
        
        function changeAuditPage(newPage) {
            if (newPage < 1 || newPage > auditLogTotalPages || newPage === auditLogState.page) {
                return;
            }
            auditLogState.page = newPage;
            loadAuditLogData();
        }
        
        function exportAuditLogData() {
            const basePath = getBasePath();
            const params = new URLSearchParams();
            if (auditLogState.search) params.append('search', auditLogState.search);
            if (auditLogState.action) params.append('action', auditLogState.action);
            if (auditLogState.startDate) params.append('start_date', auditLogState.startDate);
            if (auditLogState.endDate) params.append('end_date', auditLogState.endDate);
            
            const queryString = params.toString();
            window.location.href = basePath + '?req=superadmin&action=export_audit_log' + (queryString ? '&' + queryString : '');
        }
        // Support Center & System Utilities
        function loadTickets() { supportTicketState.page = 1; const html = `
                <div class="page-header">
                    <h1 class="page-title">Support Tickets</h1>
                    <p class="page-subtitle">Track and resolve support requests across tenants</p>
                </div>
                <div id="messageContainer"></div>
                <div class="support-stats-grid" id="supportStatsGrid">
                    ${['Open Tickets','Waiting on Customer','Resolved Tickets','Urgent Tickets'].map(() => `
                        <div class="support-card">
                            <h4></h4>
                            <div class="value">-</div>
                        </div>`).join('')}
                </div>
                <div class="filter-bar">
                    <div class="form-group">
                        <label>Status</label>
                        <select id="ticketStatusFilter">
                            <option value="">All</option>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="waiting_customer">Waiting Customer</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Priority</label>
                        <select id="ticketPriorityFilter">
                            <option value="">All</option>
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Search</label>
                        <input type="text" id="ticketSearchInput" placeholder="Subject, contact, or description">
                    </div>
                    <div class="form-group" style="align-self:flex-end;display:flex;gap:0.5rem;">
                        <button class="btn btn-primary" onclick="applyTicketFilters()"><i class="fas fa-filter"></i> Apply</button>
                        <button class="btn btn-secondary" onclick="resetTicketFilters()"><i class="fas fa-undo"></i> Reset</button>
                    </div>
                </div>
                <div class="support-layout">
                    <div class="table-card">
                        <div class="table-header">
                            <h3 class="table-title">Tickets</h3>
                            <span class="table-subtitle" id="ticketCountLabel"></span>
                        </div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Ticket</th>
                                    <th>Restaurant</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody id="supportTicketsBody">
                                <tr><td colspan="5">
                                    <div class="loading-spinner">
                                        <i class="fas fa-spinner"></i>
                                        <p>Loading tickets...</p>
                                    </div>
                                </td></tr>
                            </tbody>
                        </table>
                        <div class="pagination" id="supportPagination"></div>
                    </div>
                    <div class="ticket-detail-panel" id="ticketDetailPanel">
                        <div class="detail-empty-state">
                            <i class="fas fa-headset" style="font-size:2.5rem;margin-bottom:0.75rem;"></i>
                            <p>Select a ticket to view full details</p>
                        </div>
                    </div>
                </div>
            `; document.getElementById('mainContent').innerHTML = html; loadSupportData(); }
        async function loadSupportData() { try { const basePath = getBasePath(); const params = new URLSearchParams({ page: supportTicketState.page, limit: supportTicketState.limit, format: 'json' }); if (supportTicketState.status) params.append('status', supportTicketState.status); if (supportTicketState.priority) params.append('priority', supportTicketState.priority); if (supportTicketState.search) params.append('search', supportTicketState.search); const [overviewRes, ticketsRes] = await Promise.all([ fetch(basePath + '?req=superadmin&action=get_support_overview&format=json'), fetch(basePath + '?req=superadmin&action=get_support_tickets&' + params.toString()) ]); const overview = await overviewRes.json(); const tickets = await ticketsRes.json(); if (overview.status === 'OK') { supportOverviewCache = overview.data; renderSupportStats(); } if (tickets.status === 'OK') { supportTicketCache = tickets.data || []; supportTicketState.totalPages = tickets.total_pages || 1; renderSupportTickets(tickets.total || 0); } else { showError(tickets.message || 'Failed to load tickets'); } } catch (error) { console.error('Support data failed', error); showError('Support center unavailable'); } }
        function renderSupportStats() { const cards = document.querySelectorAll('#supportStatsGrid .support-card'); if (!cards.length || !supportOverviewCache) return; const values = [supportOverviewCache.overview.open_tickets, supportOverviewCache.overview.waiting_customer, supportOverviewCache.overview.resolved_tickets, supportOverviewCache.overview.urgent_tickets]; cards.forEach((card, idx) => { card.querySelector('h4').textContent = ['Open Tickets','Waiting on Customer','Resolved Tickets','Urgent Tickets'][idx]; card.querySelector('.value').textContent = values[idx] ?? '-'; }); }
        function renderSupportTickets(total) { const tbody = document.getElementById('supportTicketsBody'); const pagination = document.getElementById('supportPagination'); const label = document.getElementById('ticketCountLabel'); if (label) label.textContent = `${total} ticket${total === 1 ? '' : 's'}`; if (!supportTicketCache.length) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:2rem;color:#95a5a6;">No tickets found</td></tr>'; } else { tbody.innerHTML = supportTicketCache.map(ticket => `
            <tr onclick="viewTicket(${ticket.id})" style="cursor:pointer;">
                <td>
                    <div style="font-weight:600;color:#2c3e50;">${escapeHtml(ticket.subject)}</div>
                    <div class="table-subtitle">${ticket.contact_name ? escapeHtml(ticket.contact_name) : 'Unknown contact'}</div>
                </td>
                <td>${ticket.restaurant_name ? escapeHtml(ticket.restaurant_name) : '<span style="color:#95a5a6;">General</span>'}</td>
                <td><span class="priority-dot ${ticket.priority}"></span> ${ticket.priority}</td>
                <td><span class="ticket-status-badge ${ticket.status}">${ticket.status.replace('_',' ')}</span></td>
                <td>${formatDateTime(ticket.updated_at)}</td>
            </tr>`).join(''); } if (supportTicketState.totalPages > 1) { pagination.innerHTML = `
                <button class="btn btn-secondary" ${supportTicketState.page === 1 ? 'disabled' : ''} onclick="changeSupportPage(${supportTicketState.page - 1})"><i class="fas fa-chevron-left"></i></button>
                <span class="pagination-info">Page ${supportTicketState.page} of ${supportTicketState.totalPages}</span>
                <button class="btn btn-secondary" ${supportTicketState.page >= supportTicketState.totalPages ? 'disabled' : ''} onclick="changeSupportPage(${supportTicketState.page + 1})"><i class="fas fa-chevron-right"></i></button>`; } else { pagination.innerHTML = ''; } }
        async function viewTicket(id) { try { const basePath = getBasePath(); const response = await fetch(basePath + `?req=superadmin&action=get_ticket_details&ticket_id=${id}&format=json`); const result = await response.json(); if (result.status === 'OK') { renderTicketDetail(result.data); } else { showError(result.message || 'Ticket not found'); } } catch (error) { console.error('Ticket detail failed', error); } }
        function renderTicketDetail(detail) { const panel = document.getElementById('ticketDetailPanel'); if (!panel || !detail.ticket) return; const ticket = detail.ticket; panel.innerHTML = `
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;">
                <div>
                    <div class="ticket-status-badge ${ticket.status}">${ticket.status.replace('_',' ')}</div>
                    <h3 style="margin:0.5rem 0 0;">${escapeHtml(ticket.subject)}</h3>
                    <div class="table-subtitle">${ticket.restaurant_name ? escapeHtml(ticket.restaurant_name) : 'General request'}</div>
                </div>
                <div class="ticket-status-badge" style="background:none;padding:0;"><span class="priority-dot ${ticket.priority}"></span>${ticket.priority}</div>
            </div>
            <p style="margin:1rem 0;color:#2c3e50;">${ticket.description ? escapeHtml(ticket.description) : 'No description supplied.'}</p>
            <div class="ticket-meta">
                <div class="ticket-meta-item"><span>Contact</span><strong>${ticket.contact_name ? escapeHtml(ticket.contact_name) : 'Unknown'}</strong><div class="table-subtitle">${ticket.contact_email || ''}</div></div>
                <div class="ticket-meta-item"><span>Assigned</span><strong>${ticket.assigned_name || 'Unassigned'}</strong></div>
                <div class="ticket-meta-item"><span>Channel</span><strong>${ticket.channel}</strong></div>
                <div class="ticket-meta-item"><span>Updated</span><strong>${formatDateTime(ticket.updated_at)}</strong></div>
            </div>
            <div class="ticket-actions">
                <select id="ticketStatusSelect" class="form-control" style="flex:1;">
                    ${['open','in_progress','waiting_customer','resolved','closed'].map(status => `<option value="${status}" ${status === ticket.status ? 'selected' : ''}>${status.replace('_',' ')}</option>`).join('')}
                </select>
                <button class="btn btn-primary" onclick="updateTicketStatus(${ticket.id})"><i class="fas fa-save"></i> Update</button>
            </div>
            <div class="ticket-conversation">
                ${detail.replies && detail.replies.length ? detail.replies.map(reply => `
                    <div class="message-bubble ${reply.sender_type}">
                        <div class="message-meta">${reply.sender_type === 'support' ? (reply.staff_name || 'Support Team') : 'Customer'} &middot; ${formatDateTime(reply.created_at)}</div>
                        <div>${escapeHtml(reply.message)}</div>
                        ${reply.attachment_url ? `<a href="${reply.attachment_url}" target="_blank">Attachment</a>` : ''}
                    </div>`).join('') : '<div class="detail-empty-state" style="padding:1rem 0;">No replies yet</div>'}
            </div>
            <form class="form-card" style="margin-top:1rem;" onsubmit="submitTicketReply(event, ${ticket.id})">
                <h3 class="section-title"><i class="fas fa-reply"></i> Post Reply</h3>
                <div class="form-group"><textarea id="ticketReplyInput" rows="3" placeholder="Write a reply..." required></textarea></div>
                <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane"></i> Send Reply</button>
            </form>`; }
        async function updateTicketStatus(ticketId) { const status = document.getElementById('ticketStatusSelect')?.value; if (!status) return; try { const basePath = getBasePath(); const response = await fetch(basePath + '?req=superadmin&action=update_ticket_status', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ticket_id: ticketId, status }) }); const result = await response.json(); if (result.status === 'OK') { showSuccess('Ticket updated'); loadSupportData(); viewTicket(ticketId); } else { showError(result.message || 'Failed to update ticket'); } } catch (error) { console.error('Ticket update failed', error); } }
        async function submitTicketReply(event, ticketId) { event.preventDefault(); const input = document.getElementById('ticketReplyInput'); if (!input || !input.value.trim()) return; try { const basePath = getBasePath(); const response = await fetch(basePath + '?req=superadmin&action=reply_ticket', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ ticket_id: ticketId, message: input.value.trim() }) }); const result = await response.json(); if (result.status === 'OK') { input.value = ''; viewTicket(ticketId); } else { showError(result.message || 'Failed to send reply'); } } catch (error) { console.error('Reply failed', error); } }
        function applyTicketFilters() { supportTicketState.status = document.getElementById('ticketStatusFilter')?.value || ''; supportTicketState.priority = document.getElementById('ticketPriorityFilter')?.value || ''; supportTicketState.search = document.getElementById('ticketSearchInput')?.value.trim() || ''; supportTicketState.page = 1; loadSupportData(); }
        function resetTicketFilters() { supportTicketState.status = ''; supportTicketState.priority = ''; supportTicketState.search = ''; supportTicketState.page = 1; document.getElementById('ticketStatusFilter').value = ''; document.getElementById('ticketPriorityFilter').value = ''; document.getElementById('ticketSearchInput').value = ''; loadSupportData(); }
        function changeSupportPage(page) { if (page < 1 || page > (supportTicketState.totalPages || 1)) return; supportTicketState.page = page; loadSupportData(); }
        async function loadMessagesSection() { const html = `
                <div class="page-header">
                    <h1 class="page-title">Messages & Inquiries</h1>
                    <p class="page-subtitle">General contact requests from restaurants and partners</p>
                </div>
                <div id="messageContainer"></div>
                <div class="table-card">
                    <div class="table-header"><h3 class="table-title">Recent Messages</h3></div>
                    <table>
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Restaurant</th>
                                <th>Channel</th>
                                <th>Status</th>
                                <th>Received</th>
                            </tr>
                        </thead>
                        <tbody id="messagesTableBody">
                            <tr><td colspan="5">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner"></i>
                                    <p>Loading messages...</p>
                                </div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>`; document.getElementById('mainContent').innerHTML = html; try { const basePath = getBasePath(); const response = await fetch(basePath + '?req=superadmin&action=get_support_messages&format=json'); const result = await response.json(); if (result.status === 'OK') { supportMessagesCache = result.data || []; renderSupportMessages(); } else { showError(result.message || 'Failed to load messages'); } } catch (error) { console.error('Messages load failed', error); } }
        function renderSupportMessages() { const tbody = document.getElementById('messagesTableBody'); if (!tbody) return; if (!supportMessagesCache.length) { tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:2rem;color:#95a5a6;">No messages available</td></tr>'; return; } tbody.innerHTML = supportMessagesCache.map(msg => `
            <tr>
                <td>
                    <div style="font-weight:600;">${escapeHtml(msg.subject)}</div>
                    <div class="table-subtitle">${escapeHtml(msg.contact_name || '')} ${msg.contact_email ? '&middot; ' + escapeHtml(msg.contact_email) : ''}</div>
                </td>
                <td>${msg.restaurant_name ? escapeHtml(msg.restaurant_name) : '<span style="color:#95a5a6;">N/A</span>'}</td>
                <td>${msg.channel}</td>
                <td><span class="badge-status badge-${msg.status === 'new' ? 'active' : msg.status === 'archived' ? 'inactive' : 'pending'}">${msg.status}</span></td>
                <td>${formatDateTime(msg.created_at)}</td>
            </tr>`).join(''); }
        async function loadNotificationCenter() { const html = `
                <div class="page-header">
                    <h1 class="page-title">Notification Center</h1>
                    <p class="page-subtitle">Billing alerts, support escalations, and platform updates</p>
                </div>
                <div id="messageContainer"></div>
                <div class="system-card" id="notificationCenterContainer">
                    <div class="loading-spinner">
                        <i class="fas fa-spinner"></i>
                        <p>Loading notifications...</p>
                    </div>
                </div>`; document.getElementById('mainContent').innerHTML = html; try { const basePath = getBasePath(); const response = await fetch(basePath + '?req=superadmin&action=get_notifications&limit=40&format=json'); const result = await response.json(); if (result.status === 'OK') { notificationFeedCache = result.data || []; updateNotificationBadge(result.unread_count || 0); renderNotificationCenter(); } else { showError(result.message || 'Failed to load notifications'); } } catch (error) { console.error('Notification center failed', error); } }
        function renderNotificationCenter() { const container = document.getElementById('notificationCenterContainer'); if (!container) return; if (!notificationFeedCache.length) { container.innerHTML = '<div class="dropdown-empty" style="padding:2rem 0;">No notifications yet</div>'; return; } container.innerHTML = notificationFeedCache.map(item => `
            <div class="notification-item ${item.is_read == 0 ? 'unread' : ''}" style="cursor:default;">
                <div class="notification-icon-circle"><i class="fas fa-${item.icon || 'bell'}"></i></div>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(item.title)}</div>
                    <div class="notification-text">${escapeHtml(item.message)}</div>
                    <div class="notification-meta">${formatDateTime(item.created_at)}</div>
                </div>
                ${item.is_read == 0 ? `<button class="btn btn-secondary" style="padding:0.25rem 0.5rem;font-size:0.75rem;" onclick="markNotificationAsRead(${item.id}); event.stopPropagation(); loadNotificationCenter();">Mark read</button>` : ''}
            </div>`).join(''); }
        async function loadSettingsSection() { const html = `
                <div class="page-header">
                    <h1 class="page-title">System Settings</h1>
                    <p class="page-subtitle">Configure global system preferences and enforcements</p>
                </div>
                <div id="messageContainer"></div>
                <div class="system-card" style="margin-bottom: 2rem;">
                    <h3 style="margin-bottom: 1rem; color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 0.5rem;">Support & Contact</h3>
                    <form id="systemSettingsForm">
                        <div class="setting-grid">
                            <div class="form-group"><label>Support Email *</label><input type="email" name="support_email" required></div>
                            <div class="form-group"><label>Support Phone</label><input type="text" name="support_phone"></div>
                            <div class="form-group"><label>Default Timezone</label><input type="text" name="default_timezone" placeholder="Africa/Kigali"></div>
                            <div class="form-group"><label>Maintenance Mode</label><select name="maintenance_mode"><option value="off">Off</option><option value="on">On</option></select></div>
                        </div>
                        
                        <h3 style="margin: 2rem 0 1rem 0; color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 0.5rem;">Security & Sessions</h3>
                        <div class="setting-grid">
                            <div class="form-group"><label>Session Timeout (seconds)</label><input type="number" name="session_timeout" min="300" max="86400" value="7200"></div>
                            <div class="form-group"><label>Max Login Attempts</label><input type="number" name="max_login_attempts" min="3" max="10" value="5"></div>
                            <div class="form-group"><label>Password Min Length</label><input type="number" name="password_min_length" min="6" max="20" value="8"></div>
                            <div class="form-group"><label>Staff Clock-In Required</label><select name="staff_clock_in_required"><option value="on">Required</option><option value="off">Optional</option></select></div>
                        </div>
                        
                        <h3 style="margin: 2rem 0 1rem 0; color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 0.5rem;">Business Hours</h3>
                        <div class="setting-grid" id="business-hours-container"></div>
                        <button type="button" class="btn btn-secondary" onclick="resetBusinessHours()" style="margin-top: 0.5rem;"><i class="fas fa-undo"></i> Reset to Default</button>
                        
                        <h3 style="margin: 2rem 0 1rem 0; color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 0.5rem;">Order Management</h3>
                        <div class="setting-grid">
                            <div class="form-group"><label>Max Pending Orders</label><input type="number" name="max_pending_orders" min="10" max="500" value="50"></div>
                            <div class="form-group"><label>Auto-Assign Orders</label><select name="auto_assign_orders"><option value="off">Manual</option><option value="on">Automatic</option></select></div>
                            <div class="form-group"><label>Order Timeout (seconds)</label><input type="number" name="order_timeout" min="60" max="3600" value="300"></div>
                            <div class="form-group"><label>Minimum Order Amount (RWF)</label><input type="number" name="minimum_order_amount" min="0" step="100" value="0"></div>
                        </div>
                        
                        <h3 style="margin: 2rem 0 1rem 0; color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 0.5rem;">Table Management</h3>
                        <div class="setting-grid">
                            <div class="form-group"><label>Max Tables Per Restaurant</label><input type="number" name="max_tables_per_restaurant" min="10" max="500" value="100"></div>
                            <div class="form-group"><label>Auto-Release Tables</label><select name="table_auto_release"><option value="on">Enabled</option><option value="off">Disabled</option></select></div>
                            <div class="form-group"><label>Table Release Timeout (seconds)</label><input type="number" name="table_release_timeout" min="300" max="7200" value="3600"></div>
                            <div class="form-group"><label>Staff Max Shift Hours</label><input type="number" name="staff_shift_max_hours" min="4" max="16" value="12"></div>
                        </div>
                        
                        <h3 style="margin: 2rem 0 1rem 0; color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 0.5rem;">Notifications</h3>
                        <div class="setting-grid">
                            <div class="form-group"><label>Notifications Enabled</label><select name="notifications_enabled"><option value="on">Enabled</option><option value="off">Disabled</option></select></div>
                            <div class="form-group"><label>Email Notifications</label><select name="email_notifications"><option value="on">Enabled</option><option value="off">Disabled</option></select></div>
                        </div>
                        
                        <h3 style="margin: 2rem 0 1rem 0; color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 0.5rem;">Backup & Maintenance</h3>
                        <div class="setting-grid">
                            <div class="form-group"><label>Backup Schedule</label><input type="text" name="backup_schedule" placeholder="02:00 Africa/Kigali"></div>
                            <div class="form-group"><label>Backup Retention (days)</label><input type="number" name="backup_retention_days" min="7" max="365" value="30"></div>
                        </div>
                        
                        <h3 style="margin: 2rem 0 1rem 0; color: #2c3e50; border-bottom: 2px solid #e0e0e0; padding-bottom: 0.5rem;">QR Code Settings</h3>
                        <div class="setting-grid">
                            <div class="form-group"><label>QR Code Format</label><select name="qrcode_format"><option value="png">PNG</option><option value="svg">SVG</option><option value="pdf">PDF</option></select></div>
                            <div class="form-group"><label>QR Code Size (pixels)</label><input type="number" name="qrcode_size" min="100" max="1000" value="300"></div>
                        </div>
                        
                        <div class="form-actions" style="margin-top:2rem; padding-top:1rem; border-top: 2px solid #e0e0e0;">
                            <button class="btn btn-primary" type="submit"><i class="fas fa-save"></i> Save All Settings</button>
                            <button class="btn btn-secondary" type="button" onclick="resetToDefaults()"><i class="fas fa-undo"></i> Reset to Defaults</button>
                        </div>
                    </form>
                </div>`; document.getElementById('mainContent').innerHTML = html; initBusinessHours(); document.getElementById('systemSettingsForm').addEventListener('submit', saveSystemSettings); try { const basePath = getBasePath(); const response = await fetch(basePath + '?req=superadmin&action=get_system_settings&format=json'); const result = await response.json(); if (result.status === 'OK') { populateSettingsForm(result.data || {}); } } catch (error) { console.error('Settings load failed', error); } }
        
        function initBusinessHours() {
            const container = document.getElementById('business-hours-container');
            const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            const dayNames = { mon: 'Monday', tue: 'Tuesday', wed: 'Wednesday', thu: 'Thursday', fri: 'Friday', sat: 'Saturday', sun: 'Sunday' };
            container.innerHTML = days.map(day => `
                <div class="form-group" style="grid-column: span 2;">
                    <label>${dayNames[day]}</label>
                    <input type="text" name="business_hours[${day}]" placeholder="09:00-22:00" pattern="\\d{2}:\\d{2}-\\d{2}:\\d{2}">
                    <small style="color: #666; display: block; margin-top: 0.25rem;">Format: HH:MM-HH:MM (leave empty for closed)</small>
                </div>
            `).join('');
        }
        
        function resetBusinessHours() {
            const form = document.getElementById('systemSettingsForm');
            const defaults = {
                mon: '09:00-22:00', tue: '09:00-22:00', wed: '09:00-22:00', thu: '09:00-22:00',
                fri: '09:00-22:00', sat: '09:00-23:00', sun: '10:00-21:00'
            };
            Object.keys(defaults).forEach(day => {
                const input = form.querySelector(`input[name="business_hours[${day}]"]`);
                if (input) input.value = defaults[day];
            });
        }
        
        function resetToDefaults() {
            if (!confirm('Reset all settings to defaults? This cannot be undone.')) return;
            resetBusinessHours();
            const form = document.getElementById('systemSettingsForm');
            form.support_email.value = 'info@inovasiyo.rw';
            form.support_phone.value = '+250 788 000 999';
            form.default_timezone.value = 'Africa/Kigali';
            form.maintenance_mode.value = 'off';
            form.session_timeout.value = '7200';
            form.max_login_attempts.value = '5';
            form.password_min_length.value = '8';
            form.staff_clock_in_required.value = 'on';
            form.max_pending_orders.value = '50';
            form.auto_assign_orders.value = 'off';
            form.order_timeout.value = '300';
            form.minimum_order_amount.value = '0';
            form.max_tables_per_restaurant.value = '100';
            form.table_auto_release.value = 'on';
            form.table_release_timeout.value = '3600';
            form.staff_shift_max_hours.value = '12';
            form.notifications_enabled.value = 'on';
            form.email_notifications.value = 'on';
            form.backup_schedule.value = '02:00 Africa/Kigali';
            form.backup_retention_days.value = '30';
            form.qrcode_format.value = 'png';
            form.qrcode_size.value = '300';
        }
        function populateSettingsForm(settings) { 
            const form = document.getElementById('systemSettingsForm'); 
            if (!form) return; 
            
            // Support & Contact
            form.support_email.value = settings.support_email?.value || 'info@inovasiyo.rw';
            form.support_phone.value = settings.support_phone?.value || '+250 788 000 999';
            form.default_timezone.value = settings.default_timezone?.value || 'Africa/Kigali';
            form.maintenance_mode.value = settings.maintenance_mode?.value || 'off';
            
            // Security & Sessions
            form.session_timeout.value = settings.session_timeout?.value || '7200';
            form.max_login_attempts.value = settings.max_login_attempts?.value || '5';
            form.password_min_length.value = settings.password_min_length?.value || '8';
            form.staff_clock_in_required.value = settings.staff_clock_in_required?.value || 'on';
            
            // Business Hours
            const businessHours = settings.business_hours?.value || '{}';
            let hours = {};
            try {
                hours = typeof businessHours === 'string' ? JSON.parse(businessHours) : businessHours;
            } catch (e) {
                hours = {};
            }
            const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            days.forEach(day => {
                const input = form.querySelector(`input[name="business_hours[${day}]"]`);
                if (input) input.value = hours[day] || '';
            });
            
            // Order Management
            form.max_pending_orders.value = settings.max_pending_orders?.value || '50';
            form.auto_assign_orders.value = settings.auto_assign_orders?.value || 'off';
            form.order_timeout.value = settings.order_timeout?.value || '300';
            form.minimum_order_amount.value = settings.minimum_order_amount?.value || '0';
            
            // Table Management
            form.max_tables_per_restaurant.value = settings.max_tables_per_restaurant?.value || '100';
            form.table_auto_release.value = settings.table_auto_release?.value || 'on';
            form.table_release_timeout.value = settings.table_release_timeout?.value || '3600';
            form.staff_shift_max_hours.value = settings.staff_shift_max_hours?.value || '12';
            
            // Notifications
            form.notifications_enabled.value = settings.notifications_enabled?.value || 'on';
            form.email_notifications.value = settings.email_notifications?.value || 'on';
            
            // Backup
            form.backup_schedule.value = settings.backup_schedule?.value || '02:00 Africa/Kigali';
            form.backup_retention_days.value = settings.backup_retention_days?.value || '30';
            
            // QR Code
            form.qrcode_format.value = settings.qrcode_format?.value || 'png';
            form.qrcode_size.value = settings.qrcode_size?.value || '300';
        }
        async function saveSystemSettings(event) { 
            event.preventDefault(); 
            const form = event.target; 
            const submitBtn = form.querySelector('button[type="submit"]'); 
            
            // Collect all settings including business hours
            const businessHours = {};
            const days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];
            days.forEach(day => {
                const input = form.querySelector(`input[name="business_hours[${day}]"]`);
                if (input && input.value.trim()) {
                    businessHours[day] = input.value.trim();
                }
            });
            
            const payload = { 
                settings: {
                    // Support & Contact
                    support_email: form.support_email.value.trim(),
                    support_phone: form.support_phone.value.trim(),
                    default_timezone: form.default_timezone.value.trim(),
                    maintenance_mode: form.maintenance_mode.value,
                    
                    // Security & Sessions
                    session_timeout: form.session_timeout.value,
                    max_login_attempts: form.max_login_attempts.value,
                    password_min_length: form.password_min_length.value,
                    staff_clock_in_required: form.staff_clock_in_required.value,
                    
                    // Business Hours (as JSON)
                    business_hours: JSON.stringify(businessHours),
                    
                    // Order Management
                    max_pending_orders: form.max_pending_orders.value,
                    auto_assign_orders: form.auto_assign_orders.value,
                    order_timeout: form.order_timeout.value,
                    minimum_order_amount: form.minimum_order_amount.value,
                    
                    // Table Management
                    max_tables_per_restaurant: form.max_tables_per_restaurant.value,
                    table_auto_release: form.table_auto_release.value,
                    table_release_timeout: form.table_release_timeout.value,
                    staff_shift_max_hours: form.staff_shift_max_hours.value,
                    
                    // Notifications
                    notifications_enabled: form.notifications_enabled.value,
                    email_notifications: form.email_notifications.value,
                    
                    // Backup
                    backup_schedule: form.backup_schedule.value.trim(),
                    backup_retention_days: form.backup_retention_days.value,
                    
                    // QR Code
                    qrcode_format: form.qrcode_format.value,
                    qrcode_size: form.qrcode_size.value
                }
            }; 
            
            if (submitBtn) { 
                submitBtn.disabled = true; 
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...'; 
            } 
            
            try { 
                const basePath = getBasePath(); 
                const response = await fetch(basePath + '?req=superadmin&action=update_system_settings', { 
                    method: 'POST', 
                    headers: { 
                        'Content-Type': 'application/json', 
                        'Accept': 'application/json' 
                    }, 
                    credentials: 'include', 
                    cache: 'no-store', 
                    body: JSON.stringify(payload) 
                }); 
                const result = await response.json(); 
                if (result.status === 'OK') { 
                    showSuccess('All settings saved successfully'); 
                } else { 
                    showError(result.message || 'Unable to save settings'); 
                } 
            } catch (error) { 
                console.error('Settings save failed', error); 
                showError('Unable to save settings. Please try again.'); 
            } finally { 
                if (submitBtn) { 
                    submitBtn.disabled = false; 
                    submitBtn.innerHTML = '<i class="fas fa-save"></i> Save All Settings'; 
                } 
            } 
        }
        function loadBackupSection() { const html = `
                <div class="page-header">
                    <h1 class="page-title">Backups & Maintenance</h1>
                    <p class="page-subtitle">Trigger on-demand snapshots before major updates</p>
                </div>
                <div id="messageContainer"></div>
                <div class="system-card">
                    <p>Nightly backups run automatically at the configured schedule. Use this action to queue an immediate backup.</p>
                    <button class="btn btn-primary" onclick="triggerBackupRequest()"><i class="fas fa-database"></i> Trigger Backup</button>
                </div>`; document.getElementById('mainContent').innerHTML = html; }
        async function triggerBackupRequest() { try { const basePath = getBasePath(); const response = await fetch(basePath + '?req=superadmin&action=trigger_backup', { method: 'POST' }); const result = await response.json(); if (result.status === 'OK') { showSuccess('Backup queued'); loadNotificationPreview(); } else { showError(result.message || 'Backup failed'); } } catch (error) { console.error('Backup trigger failed', error); } }
        async function loadSystemLogsSection() { const html = `
                <div class="page-header">
                    <h1 class="page-title">System Activity Logs</h1>
                    <p class="page-subtitle">Quick audit summary across all tenants</p>
                </div>
                <div id="messageContainer"></div>
                <div class="table-card">
                    <div class="table-header"><h3 class="table-title">Latest Activity</h3></div>
                    <table>
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="systemLogsBody">
                            <tr><td colspan="4">
                                <div class="loading-spinner">
                                    <i class="fas fa-spinner"></i>
                                    <p>Loading logs...</p>
                                </div>
                            </td></tr>
                        </tbody>
                    </table>
                </div>`; document.getElementById('mainContent').innerHTML = html; try { const basePath = getBasePath(); const response = await fetch(basePath + '?req=superadmin&action=get_system_logs&format=json'); const result = await response.json(); if (result.status === 'OK') { renderSystemLogs(result.data || []); } else { showError(result.message || 'Failed to load logs'); } } catch (error) { console.error('System logs failed', error); } }
        function renderSystemLogs(logs) { const tbody = document.getElementById('systemLogsBody'); if (!tbody) return; if (!logs.length) { tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;padding:2rem;color:#95a5a6;">No logs recorded</td></tr>'; return; } tbody.innerHTML = logs.map(log => `
            <tr>
                <td>${log.full_name || 'Unknown'}<div class="table-subtitle">${log.email || ''}</div></td>
                <td>${escapeHtml(log.action || '')}</td>
                <td>${escapeHtml(log.description || '')}</td>
                <td>${formatDateTime(log.created_at)}</td>
            </tr>`).join(''); }
        
        // Additional stub functions for future implementation
        function loadSubscriptions() { loadComingSoon('subscriptions'); }
        function loadSettings() { loadComingSoon('settings'); }
    </script>
</body>
</html>
