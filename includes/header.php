<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'GMPC Stock Requisition' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css">
    <style>
        :root {
            --sidebar-width: 260px;
        }
        body {
            background-color: #f8fafc;
        }
        .sidebar {
            width: var(--sidebar-width);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            background: linear-gradient(180deg, #1e3a5f 0%, #2c5282 100%);
            color: white;
            z-index: 1000;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 4px 12px;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.15);
            color: white;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .stat-card {
            border-radius: 12px;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .table-card {
            border-radius: 12px;
            overflow: hidden;
        }
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-completed { background: #dbeafe; color: #1e40af; }
        .status-in-stock { background: #d1fae5; color: #065f46; }
        .status-low-stock { background: #fef3c7; color: #92400e; }
        .status-out-of-stock { background: #fee2e2; color: #991b1b; }
        .status-critical { background: #fee2e2; color: #dc2626; }
        .login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
        }
        .login-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .login-header {
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .mobile-menu-toggle {
                display: block !important;
            }
            .sidebar-overlay {
                display: block;
                opacity: 0;
                pointer-events: none;
            }
            .sidebar-overlay.show {
                opacity: 1;
                pointer-events: auto;
            }
        }
    </style>
    <script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('show');
        document.querySelector('.sidebar-overlay').classList.toggle('show');
    }
    </script>
</head>
<body>
<button class="btn btn-primary mobile-menu-toggle" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>
