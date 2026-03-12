<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$name = htmlspecialchars($_SESSION['name'] ?? '');
$role = htmlspecialchars($_SESSION['role'] ?? '');
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SportZone</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
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

        .main-content {
            flex-grow: 1;
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        h1 {
            font-family: 'Montserrat', sans-serif;
            color: var(--heading-color);
            font-weight: 800;
            margin-bottom: 1rem;
            text-align: center;
            transition: color 0.3s ease;
        }

        .lead {
            color: var(--text-color);
            text-align: center;
            margin-bottom: 2rem;
            transition: color 0.3s ease;
        }

        .dashboard-section {
            background-color: var(--card-bg);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 0 15px var(--shadow-color);
            margin-bottom: 2rem;
            border: 1px solid var(--border-subtle);
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .dashboard-section h3 {
            font-family: 'Montserrat', sans-serif;
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            font-weight: 700;
            text-align: center;
            transition: color 0.3s ease;
        }

        .action-button {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.2rem 1.5rem;
            background-color: var(--primary-color);
            color: var(--button-text);
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            text-align: center;
            transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out, box-shadow 0.3s ease-in-out, color 0.3s ease;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }

        .action-button:hover {
            background-color: var(--link-hover-color);
            transform: translateY(-3px);
            color: var(--button-text);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
        }

        .action-button i {
            margin-right: 10px;
            font-size: 1.4rem;
            transition: color 0.3s ease;
        }

        .btn-logout {
            background-color: var(--error-color);
            color: var(--button-text);
            box-shadow: 0 4px 10px rgba(220, 53, 69, 0.3);
            font-size: 1.1rem;
            font-weight: 700;
            padding: 0.8rem 2.5rem;
            border-radius: 8px;
            transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out, box-shadow 0.3s ease-in-out, color 0.3s ease;
            width: 100%;
            margin-top: 2.5rem;
            display: block;
            text-decoration: none;
        }

        .btn-logout:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            color: var(--button-text);
            box-shadow: 0 6px 15px rgba(220, 53, 69, 0.4);
        }

        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            padding-top: 1rem;
        }

        .unrecognized-role-message {
            text-align: center;
            padding: 2rem;
            background-color: rgba(220, 53, 69, 0.1);
            border: 1px solid var(--error-color);
            border-radius: 10px;
            color: var(--error-color);
            margin-top: 2rem;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">SportZone Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <button id="theme-toggle" class="theme-toggle-btn">
                            <i class="fas fa-sun"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <h1 class="display-4">Welcome, <?= $name ?>!</h1>
        <p class="lead">You are logged in as: <strong class="text-uppercase" style="color: var(--secondary-color);"><?= $role ?></strong>.</p>

        <?php if ($role === 'client'): ?>
            <div class="dashboard-section">
                <h3>Client Options</h3>
                <div class="options-grid">
                    <a href="../reservations/add_reservation.php" class="action-button">
                        <i class="fas fa-calendar-plus"></i> Book a new field
                    </a>
                    <a href="../reservations/list_reservations.php" class="action-button">
                        <i class="fas fa-list-alt"></i> View & manage my bookings
                    </a>
                </div>
                <a href="../auth/logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        <?php elseif ($role === 'admin'): ?>
            <div class="dashboard-section">
                <h3>Admin Options</h3>
                <div class="options-grid">
                    <a href="../admin/admin_terrains.php" class="action-button">
                        <i class="fas fa-futbol"></i> Manage Fields
                    </a>
                    <a href="../admin/admin_sports.php" class="action-button">
                        <i class="fas fa-running"></i> Manage Sports
                    </a>
                    <a href="../admin/admin_users.php" class="action-button">
                        <i class="fas fa-users"></i> Manage Users
                    </a>
                    <a href="../admin/admin_reservations.php" class="action-button">
                        <i class="fas fa-book"></i> View All Bookings
                    </a>
                    <a href="../admin/admin_closed_hours.php" class="action-button">
                        <i class="fas fa-clock"></i> Manage Closed Hours
                    </a>
                </div>
                <a href="../auth/logout.php" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        <?php else: ?>
            <div class="unrecognized-role-message">
                <p>Unrecognized role. Please <a href="../auth/logout.php" style="color: var(--error-color); text-decoration: underline;">logout</a> and try again.</p>
            </div>
        <?php endif; ?>
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