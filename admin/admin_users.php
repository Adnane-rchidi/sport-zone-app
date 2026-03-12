<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

$sql = "SELECT id, name, email, role FROM utilisateurs ORDER BY role DESC, name ASC";
$result = $conn->query($sql);

if (!$result) {
    die("Database query error: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin - SportZone</title>
    <link href="../bootstrap-5.0.2-dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --body-bg: #f0f2f5;
            --text-color: #343a40;
            --heading-color: #1a1a1a;
            --card-bg: #ffffff;
            --input-bg: #e9ecef;
            --input-border: #ced4da;
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --button-text: #ffffff;
            --link-color: #007bff;
            --link-hover-color: #0056b3;
            --error-color: #dc3545;
            --success-message-color: #28a745;
            --border-subtle: #dee2e6;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --table-header-bg: #e9ecef;
            --table-row-hover-bg: #f5f5f5;
            --badge-admin-bg: #dc3545;
            --badge-user-bg: #007bff;
        }

        html[data-bs-theme="dark"] {
            --body-bg: #1a1a2e;
            --text-color: #e0e0e0;
            --heading-color: #ffffff;
            --card-bg: #27293d;
            --input-bg: #3a3f5a;
            --input-border: #525777;
            --primary-color: #66b3ff;
            --secondary-color: #9ac9f9;
            --success-color: #4CAF50;
            --danger-color: #ff6b6b;
            --warning-color: #FFD700;
            --info-color: #4dd0e1;
            --button-text: #ffffff;
            --link-color: #66b3ff;
            --link-hover-color: #9ac9f9;
            --error-color: #ff6b6b;
            --success-message-color: #4CAF50;
            --border-subtle: #3a3a4e;
            --shadow-color: rgba(0, 0, 0, 0.5);
            --table-header-bg: #3a3f5a;
            --table-row-hover-bg: #3a3f5a;
            --badge-admin-bg: #dc3545;
            --badge-user-bg: #66b3ff;
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

        .content-container {
            background-color: var(--card-bg);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 0 15px var(--shadow-color);
            max-width: 900px;
            margin: 2rem auto;
            border: 1px solid var(--border-subtle);
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        h2 {
            font-family: 'Montserrat', sans-serif;
            color: var(--heading-color);
            margin-bottom: 1.5rem;
            font-weight: 800;
            text-align: center;
            transition: color 0.3s ease;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
            font-size: 0.9rem;
            border: none;
            text-decoration: none;
        }
        .btn-action:hover {
            text-decoration: none;
        }
        .btn-change-role {
            background-color: var(--warning-color);
            color: #212529;
        }
        html[data-bs-theme="dark"] .btn-change-role {
            color: var(--button-text);
        }
        .btn-change-role:hover {
            background-color: #e0a800;
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: var(--button-text);
        }
        .btn-delete:hover {
            background-color: #c82333;
        }
        .btn-disabled {
            background-color: var(--secondary-color);
            color: var(--button-text);
            cursor: not-allowed;
            opacity: 0.7;
        }
        
        .table {
            background-color: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-subtle);
            border-radius: 10px;
            overflow: hidden;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
        .table th {
            background-color: var(--table-header-bg);
            color: var(--heading-color);
            font-weight: 700;
            border-bottom: 1px solid var(--border-subtle);
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        .table td, .table th {
            padding: 1rem;
            vertical-align: middle;
            border-top: 1px solid var(--border-subtle);
        }
        .table tbody tr:hover {
            background-color: var(--table-row-hover-bg);
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: var(--body-bg);
        }
        html[data-bs-theme="dark"] .table-striped tbody tr:nth-of-type(odd) {
            background-color: var(--table-header-bg);
        }

        .badge {
            font-size: 0.85em;
            padding: 0.5em 0.75em;
            border-radius: 0.375rem;
            font-weight: 600;
        }
        .badge.bg-danger {
            background-color: var(--badge-admin-bg) !important;
        }
        .badge.bg-primary {
            background-color: var(--badge-user-bg) !important;
        }

        .message-success {
            color: var(--success-message-color);
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid var(--success-message-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }
        .message-error {
            color: var(--error-color);
            background-color: rgba(220, 53, 69, 0.2);
            border: 1px solid var(--error-color);
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: var(--link-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: var(--link-hover-color);
            text-decoration: underline;
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
            color: var(--error-color) !important;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.3s ease;
        }
        .logout-link:hover {
            color: #b02a37 !important;
            text-decoration: underline;
        }
        .navbar-nav .dropdown-menu .dropdown-item {
            color: var(--text-color);
        }
        .navbar-nav .dropdown-menu .dropdown-item:hover {
            background-color: var(--table-row-hover-bg);
            color: var(--primary-color);
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
                        <button id="theme-toggle" class="theme-toggle-btn">
                            <i class="fas fa-sun"></i>
                        </button>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['name'] ?? '') ?> (<?= htmlspecialchars(ucfirst($_SESSION['role'] ?? '')) ?>)
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="../auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container main-content">
        <div class="content-container">
            <h2 class="mb-4">Manage Users</h2>

            <p class="text-center">
                <a href="admin_dashboard.php" class="btn btn-secondary back-link">
                    <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
                </a>
            </p>

            <?php
            if (isset($_GET['msg'])) {
                echo "<div class='alert alert-success message-success' role='alert'>" . htmlspecialchars($_GET['msg']) . "</div>";
            }
            if (isset($_GET['error'])) {
                echo "<div class='alert alert-danger message-error' role='alert'>" . htmlspecialchars($_GET['error']) . "</div>";
            }
            ?>

            <?php if ($result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row["id"]) ?></td>
                                    <td><?= htmlspecialchars($row["name"]) ?></td>
                                    <td><?= htmlspecialchars($row["email"]) ?></td>
                                    <td>
                                        <span class="badge <?= ($row['role'] === 'admin') ? 'bg-danger' : 'bg-primary' ?>">
                                            <?= htmlspecialchars(ucfirst($row["role"])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($row['id'] != $_SESSION['user_id']): ?>
                                            <a href="modifier_user_role.php?id=<?= $row['id'] ?>" class="btn btn-action btn-change-role me-2">
                                                <i class="fas fa-user-edit"></i> Change Role
                                            </a>
                                            <a href="supprimer_user.php?id=<?= $row['id'] ?>" class="btn btn-action btn-delete" onclick="return confirm('Are you sure you want to delete this user? This will also cancel all their future bookings.');">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-action btn-disabled" disabled>
                                                (Your Account)
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    No users found.
                </div>
            <?php endif; ?>
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