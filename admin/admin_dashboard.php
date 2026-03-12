<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$admin_name = htmlspecialchars($_SESSION['name'] ?? 'Admin');
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        :root {
            --body-bg: #f0f2f5;
            --text-color: #343a40;
            --heading-color: #1a1a1a;
            --card-bg: #ffffff;
            --input-bg: #e9ecef;
            --input-border: #ced4da;
            --primary-color: #007bff;
            --secondary-color: #fd7e14;
            --button-text: #ffffff;
            --link-color: #007bff;
            --link-hover-color: #0056b3;
            --error-color: #dc3545;
            --success-color: #28a745;
            --border-subtle: #dee2e6;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        html[data-bs-theme="dark"] {
            --body-bg: #1a1a2e;
            --text-color: #e0e0e0;
            --heading-color: #ffffff;
            --card-bg: #27293d;
            --input-bg: #3a3f5a;
            --input-border: #525777;
            --primary-color: #66b3ff;
            --secondary-color: #ff9d4d;
            --button-text: #ffffff;
            --link-color: #66b3ff;
            --link-hover-color: #9ac9f9;
            --error-color: #dc3545;
            --success-color: #28a745;
            --border-subtle: #3a3a4e;
            --shadow-color: rgba(0, 0, 0, 0.5);
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background: var(--body-bg);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .navbar {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-subtle);
            box-shadow: 0 2px 10px var(--shadow-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .navbar-brand {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            color: var(--primary-color) !important;
            font-size: 1.8rem;
            letter-spacing: 1px;
            transition: color 0.3s ease;
        }
        .navbar-text {
            color: var(--text-color);
        }

        .main-content {
            flex-grow: 1;
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .dashboard-container {
            background-color: var(--card-bg);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 0 15px var(--shadow-color);
            max-width: 900px;
            margin: 2rem auto;
            border: 1px solid var(--border-subtle);
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        h1, h3 {
            font-family: 'Montserrat', sans-serif;
            color: var(--heading-color);
            margin-bottom: 1.5rem;
            font-weight: 800;
            text-align: center;
            transition: color 0.3s ease;
        }

        .welcome-message {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.2rem;
            color: var(--text-color);
        }

        .admin-options-grid {
            list-style: none;
            padding: 0;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .admin-options-grid li {
            margin-bottom: 0;
        }

        .admin-option-card {
            background-color: var(--input-bg);
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            border: 1px solid var(--border-subtle);
            box-shadow: 0 2px 8px var(--shadow-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .admin-option-card a {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s ease, transform 0.2s ease;
            display: block;
            padding: 1rem 0;
        }

        .admin-option-card a:hover {
            color: var(--link-hover-color);
            transform: translateY(-2px);
            text-decoration: none;
        }

        .admin-option-card i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
            transition: color 0.3s ease;
        }

        .theme-toggle-btn {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            margin-left: 1rem;
            transition: color 0.3s ease;
        }
        .theme-toggle-btn:hover {
            color: var(--primary-color);
        }

        .logout-link {
            color: var(--error-color);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .logout-link:hover {
            color: #b02a37;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            Welcome, <strong><?= $admin_name ?></strong>!
                        </span>
                    </li>
                    <li class="nav-item">
                        <button id="theme-toggle" class="theme-toggle-btn">
                            <i class="fas fa-sun"></i> </button>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link logout-link ms-3" href="../auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <div class="dashboard-container">
            <h1>Welcome Admin, <?= $admin_name ?>!</h1>
            <p class="welcome-message">This is the administration dashboard. Use the options below to manage the platform.</p>

            <h3>Admin Options:</h3>
            <ul class="admin-options-grid"> <li>
                    <div class="admin-option-card">
                        <a href="admin_terrains.php">
                            <i class="fas fa-futbol"></i><br>
                            Manage Fields
                        </a>
                    </div>
                </li>
                <li>
                    <div class="admin-option-card">
                        <a href="admin_sports.php">
                            <i class="fas fa-running"></i><br>
                            Manage Sports
                        </a>
                    </div>
                </li>
                <li>
                    <div class="admin-option-card">
                        <a href="admin_users.php">
                            <i class="fas fa-users-cog"></i><br>
                            Manage Users
                        </a>
                    </div>
                </li>
                <li>
                    <div class="admin-option-card">
                        <a href="admin_reservations.php">
                            <i class="fas fa-calendar-alt"></i><br>
                            View All Bookings
                        </a>
                    </div>
                </li>
                <li>
                    <div class="admin-option-card">
                        <a href="admin_closed_hours.php">
                            <i class="fas fa-clock"></i><br>
                            Manage Closed Hours
                        </a>
                    </div>
                </li>
            </ul>
        </div>
    </div>

    <script src="../bootstrap-5.0.2-dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const themeToggle = document.getElementById('theme-toggle');
        const htmlElement = document.documentElement;

        function setTheme(theme) {
            htmlElement.setAttribute('data-bs-theme', theme);
            localStorage.setItem('theme', theme);
            if (theme === 'dark') {
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme') || 'light';
            setTheme(savedTheme);
        });

        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            setTheme(newTheme);
        });
    </script>
</body>
</html>