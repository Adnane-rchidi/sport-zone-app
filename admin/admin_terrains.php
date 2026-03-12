<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

$sql = "SELECT terrains.id, terrains.name, terrains.addresse, sports.name AS sport, terrains.is_active
        FROM terrains
        JOIN sports ON terrains.sports_id = sports.id
        ORDER BY terrains.name";
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
    <title>Manage Fields - Admin - SportZone</title>
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
            --table-header-bg: #e9ecef;
            --table-row-hover-bg: #f5f5f5;
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
            --table-header-bg: #3a3f5a;
            --table-row-hover-bg: #3a3f5a;
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
            max-width: 1200px;
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

        .btn-add-new {
            background-color: var(--success-color);
            color: var(--button-text);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: 600;
        }
        .btn-add-new:hover {
            background-color: #218838;
            color: var(--button-text);
        }

        .btn-edit {
            background-color: var(--primary-color);
            color: var(--button-text);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 0.9rem;
        }
        .btn-edit:hover {
            background-color: var(--link-hover-color);
            color: var(--button-text);
        }

        .btn-delete {
            background-color: var(--error-color);
            color: var(--button-text);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 0.9rem;
        }
        .btn-delete:hover {
            background-color: #c82333;
            color: var(--button-text);
        }

        .btn-toggle-status {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-size: 0.9rem;
            color: var(--button-text);
            border: none;
        }
        .btn-activate {
            background-color: var(--success-color);
        }
        .btn-activate:hover {
            background-color: #218838;
        }
        .btn-deactivate {
            background-color: var(--error-color);
        }
        .btn-deactivate:hover {
            background-color: #c82333;
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

        .active-status {
            color: var(--success-color);
            font-weight: bold;
        }
        .inactive-status {
            color: var(--error-color);
            font-weight: bold;
        }

        .message-success {
            color: var(--success-color);
            background-color: rgba(40, 167, 69, 0.2);
            border: 1px solid var(--success-color);
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
                        <button id="theme-toggle" class="theme-toggle-btn">
                            <i class="fas fa-sun"></i>
                        </button>
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
        <div class="content-container">
            <h2 class="mb-4">List of Fields</h2>

            <?php
            if (isset($_GET['msg'])) {
                echo "<div class='alert alert-success message-success' role='alert'>" . htmlspecialchars($_GET['msg']) . "</div>";
            }
            if (isset($_GET['error'])) {
                echo "<div class='alert alert-danger message-error' role='alert'>" . htmlspecialchars($_GET['error']) . "</div>";
            }
            ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="../admin/admin_dashboard.php" class="btn btn-secondary back-link">
                    <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
                </a>
                <a href="ajouter_terrain.php" class="btn btn-add-new">
                    <i class="fas fa-plus-circle"></i> Add New Field
                </a>
            </div>

            <?php if ($result->num_rows === 0): ?>
                <div class="alert alert-info text-center" role="alert">
                    No fields found.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Address</th>
                                <th>Sport</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row["id"]) ?></td>
                                    <td><?= htmlspecialchars($row["name"]) ?></td>
                                    <td><?= htmlspecialchars($row["addresse"]) ?></td>
                                    <td><?= htmlspecialchars($row["sport"]) ?></td>
                                    <td>
                                        <?php if ($row["is_active"]): ?>
                                            <span class="active-status">Active</span>
                                        <?php else: ?>
                                            <span class="inactive-status">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="modifier_terrain.php?id=<?= $row['id'] ?>" class="btn btn-edit me-2">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="supprimer_terrain.php?id=<?= $row['id'] ?>" class="btn btn-delete me-2" onclick="return confirm('Are you sure you want to delete this field? This will prevent new bookings but existing ones might still reference it.');">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                        <?php if ($row["is_active"]): ?>
                                            <a href="toggle_terrain_status.php?id=<?= $row['id'] ?>&status=0" class="btn btn-toggle-status btn-deactivate">
                                                <i class="fas fa-toggle-off"></i> Deactivate
                                            </a>
                                        <?php else: ?>
                                            <a href="toggle_terrain_status.php?id=<?= $row['id'] ?>&status=1" class="btn btn-toggle-status btn-activate">
                                                <i class="fas fa-toggle-on"></i> Activate
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
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