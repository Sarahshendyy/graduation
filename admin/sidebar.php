<?php
// include "connection.php";
include "../mail.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #071739;
            --secondary-color: #4B6382;
            --info-color: #A4B5C4;
            --light-color: #CDD5DB;
            --accent-warm: #A68868;
            --accent-light: #E3C39D;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'DM Sans', sans-serif;
        }

        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: 250px;
            background-color: var(--primary-color);
            padding: 20px;
            color: white;
            transition: all 0.3s ease;
            z-index: 1000;
            overflow: hidden;
        }

        .sidebar.collapsed {
            width: 70px;
        }

        .sidebar-header {
            padding: 20px 0;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            position: relative;
            margin-bottom: 20px;
        }

        .logo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding-top: 10px;
            transition: all 0.3s ease;
        }

        .sidebar-header img {
            width: 115px;
            height: 51px;
            transition: all 0.3s ease;
        }

        .sidebar-header h3 {
            margin: 0;
            font-size: 1.2rem;
            color: white;
            text-align: center;
            width: 100%;
            transition: all 0.3s ease;
        }

        .toggle-sidebar {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            color: white;
            border: none;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 0;
            z-index: 1;
        }

        .toggle-sidebar:hover {
            color: var(--accent-light);
        }

        /* Collapsed state styles */
        .sidebar.collapsed .sidebar-header {
            padding: 15px 0;
        }

        .sidebar.collapsed .logo-container {
            padding-top: 0;
        }

        .sidebar.collapsed .sidebar-header img {
            width: 40px;
            height: 40px;
            margin: 0 auto;
        }

        .sidebar.collapsed .toggle-sidebar {
            position: static;
            margin: 5px auto 0;
            display: block;
        }

        .sidebar.collapsed .sidebar-header h3,
        .sidebar.collapsed .nav-link span {
            display: none;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center;
            padding: 12px;
        }

        .sidebar.collapsed .nav-link i {
            margin-right: 0;
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        .main-content.expanded {
            margin-left: 70px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .sidebar.collapsed {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .main-content.expanded {
                margin-left: 0;
            }

            .toggle-sidebar {
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1002;
                background-color: var(--primary-color);
                border-radius: 50%;
                width: 40px;
                height: 40px;
            }

            .toggle-sidebar.collapsed {
                left: 20px;
            }
        }

        .nav-menu {
            list-style: none;
            padding: 0;
            margin-top: 30px;
        }

        .nav-item {
            margin-bottom: 10px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background-color: var(--secondary-color);
            color: white;
        }

        .nav-link.active {
            background-color: var(--accent-warm);
            color: white;
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .dashboard-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }

        .chart-container {
            width: 48%;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
        }

        .table-container {
            width: 100%;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .summary-card {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .status-upcoming {
            background-color: #66b3ff;
            color: white;
        }

        .status-ongoing {
            background-color: #ffcc00;
            color: black;
        }

        .status-completed {
            background-color: #4caf50;
            color: white;
        }

        .status-canceled {
            background-color: #ff4d4d;
            color: white;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <img style="width: 115px;
    height: 51px;" src="../img/white_logo.png" alt="Logo">
                <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3): ?>
                    <h3>Workspace Owner</h3>
                <?php else: ?>
                    <h3 style="color: #fff;">WorkSphere Admin</h3>
                <?php endif; ?>
            </div>
            <button class="toggle-sidebar" id="toggleSidebar">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <ul class="nav-menu">
            <?php if (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3): ?>
                <!-- Workspace Owner Sidebar -->
                <li class="nav-item">
                    <a href="../workspace/workspaces_dashboard.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'workspaces_dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../workspace/booking_overview.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'booking_overview.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span>Booking Overview</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../workspace/rooms_table.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'rooms_table.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span>Rooms Management</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../workspace/workspace-calendar.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'workspace-calendar.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar"></i>
                        <span>Workspace Calendar</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../workspace/chat.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'chat.php' ? 'active' : ''; ?>">
                        <i class="fas fa-comments"></i>
                        <span>Chat</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="../profile.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user profile-icon"></i>
                        <span>My Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <form action="../workspace/connection.php" method="POST" style="margin:0;">
                        <button type="submit" name="logout" class="nav-link"
                            style="width:100%;background:none;border:none;padding:0;text-align:left;display:flex;align-items:center;">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </li>
            <?php elseif (isset($_SESSION['role_id']) && $_SESSION['role_id'] == 4): ?>
                <!-- Admin Sidebar -->
                <li class="nav-item">
                    <a href="admin_dashboard.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="bookings_list.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings_list.php' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i>
                        <span>Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="workspaces_list.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'workspaces_list.php' ? 'active' : ''; ?>">
                        <i class="fas fa-building"></i>
                        <span>Workspaces</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="users_list.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users_list.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admins_list.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admins_list.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user-shield"></i>
                        <span>Admins</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="homee.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'homee.php' ? 'active' : ''; ?>">
                        <i class="fas fa-comments"></i>
                        <span>Chat</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="workspace_approval.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'workspace_approval.php' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i>
                        <span>Workspace Approval</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="profile.php"
                        class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                        <i class="fas fa-user profile-icon"></i>
                        <span>Profile</span>
                    </a>
                </li>
                <li class="nav-item">
                    <form action="connection.php" method="POST" style="margin:0;">
                        <button type="submit" name="logout" class="nav-link"
                            style="width:100%;background:none;border:none;padding:0;text-align:left;display:flex;align-items:center;">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </li>
            <?php endif; ?>
        </ul>
    </div>
    <script>
        // Sidebar Toggle Functionality
        document.addEventListener('DOMContentLoaded', function () {
            const sidebar = document.querySelector('.sidebar');
            const mainContent = document.querySelector('.main-content');
            const toggleBtn = document.getElementById('toggleSidebar');

            // Check for saved state
            const isSidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (isSidebarCollapsed) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                toggleBtn.classList.add('collapsed');
            }

            // Check if we're on mobile
            const isMobile = window.innerWidth <= 768;
            if (isMobile) {
                sidebar.classList.remove('collapsed');
                mainContent.classList.remove('expanded');
            }

            toggleBtn.addEventListener('click', function () {
                if (isMobile) {
                    sidebar.classList.toggle('active');
                } else {
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    toggleBtn.classList.toggle('collapsed');

                    // Save state only for desktop
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                }
            });

            // Handle window resize
            window.addEventListener('resize', function () {
                const isMobile = window.innerWidth <= 768;
                if (isMobile) {
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                } else {
                    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    if (isCollapsed) {
                        sidebar.classList.add('collapsed');
                        mainContent.classList.add('expanded');
                        toggleBtn.classList.add('collapsed');
                    }
                }
            });
        });
    </script>
    <script src="js/sidebar.js"></script>
</body>

</html>