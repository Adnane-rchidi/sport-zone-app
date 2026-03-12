<?php
session_start();
require_once '../config/config.php';

if (!isset($conn) || $conn->connect_error) {
    die("Database connection failed: " . (isset($conn) ? $conn->connect_error : "Connection object not created. Check config/config.php"));
}

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

if (isset($_GET['delete_id']) && $_SESSION["role"] === "admin") {
    $delete_id = filter_var($_GET['delete_id'], FILTER_SANITIZE_NUMBER_INT);
    
    $check_stmt = $conn->prepare("SELECT id FROM reservations WHERE id = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("i", $delete_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $delete_stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
            if ($delete_stmt) {
                $delete_stmt->bind_param("i", $delete_id);
                if ($delete_stmt->execute()) {
                    header("Location: admin_reservations.php?msg=Reservation successfully deleted.");
                    exit;
                } else {
                    header("Location: admin_reservations.php?error=Error deleting reservation.");
                    exit;
                }
                $delete_stmt->close();
            } else {
                error_log("Failed to prepare delete query: " . $conn->error);
                header("Location: admin_reservations.php?error=Database error on delete prepare.");
                exit;
            }
        } else {
            header("Location: admin_reservations.php?error=Reservation not found.");
            exit;
        }
    } else {
        error_log("Failed to prepare check query for deletion: " . $conn->error);
        header("Location: admin_reservations.php?error=Database error on check prepare for delete.");
        exit;
    }
}


$terrains = [];
$sports = [];

$stmt_terrains = $conn->prepare("SELECT id, name FROM terrains ORDER BY name ASC");
if ($stmt_terrains) {
    $stmt_terrains->execute();
    $result_terrains = $stmt_terrains->get_result();
    while ($row = $result_terrains->fetch_assoc()) {
        $terrains[$row['id']] = $row['name'];
    }
    $stmt_terrains->close();
} else {
    error_log("Failed to prepare terrains query: " . $conn->error);
}

$stmt_sports = $conn->prepare("SELECT id, name FROM sports ORDER BY name ASC");
if ($stmt_sports) {
    $stmt_sports->execute();
    $result_sports = $stmt_sports->get_result();
    while ($row = $result_sports->fetch_assoc()) {
        $sports[$row['id']] = $row['name'];
    }
    $stmt_sports->close();
} else {
    error_log("Failed to prepare sports query: " . $conn->error);
}

$possible_statuses = ['en attente', 'confirmée', 'annulée', 'terminée'];

$filter_user_name = isset($_GET['user_name']) ? trim($_GET['user_name']) : '';
$filter_terrain_id = isset($_GET['terrain_id']) && array_key_exists($_GET['terrain_id'], $terrains) ? $_GET['terrain_id'] : '';
$filter_sport_id = isset($_GET['sport_id']) && array_key_exists($_GET['sport_id'], $sports) ? $_GET['sport_id'] : '';
$filter_date = isset($_GET['date']) ? trim($_GET['date']) : '';
$filter_status = isset($_GET['status']) && in_array($_GET['status'], $possible_statuses) ? $_GET['status'] : '';

$sql = "SELECT
            r.id,
            u.name AS user_name,
            u.email AS user_email,
            t.name AS terrain_name,
            s.name AS sport_name,
            r.date AS date_reservation,
            r.heure_debut,
            r.heure_fin,
            r.statut
        FROM reservations r
        JOIN utilisateurs u ON r.user_id = u.id
        JOIN terrains t ON r.terrain_id = t.id
        JOIN sports s ON t.sports_id = s.id
        WHERE 1=1";

$params = [];
$param_types = "";

if (!empty($filter_user_name)) {
    $sql .= " AND u.name LIKE ?";
    $params[] = "%" . $filter_user_name . "%";
    $param_types .= "s";
}
if (!empty($filter_terrain_id)) {
    $sql .= " AND t.id = ?";
    $params[] = $filter_terrain_id;
    $param_types .= "i";
}
if (!empty($filter_sport_id)) {
    $sql .= " AND s.id = ?";
    $params[] = $filter_sport_id;
    $param_types .= "i";
}
if (!empty($filter_date)) {
    $sql .= " AND r.date = ?";
    $params[] = $filter_date;
    $param_types .= "s";
}
if (!empty($filter_status)) {
    if ($filter_status === 'terminée') {
        $sql .= " AND CONCAT(r.date, ' ', r.heure_fin) < NOW() AND (r.statut = 'en attente' OR r.statut = 'confirmée')";
    } else {
        $sql .= " AND r.statut = ?";
        $params[] = $filter_status;
        $param_types .= "s";
    }
}

$sql .= " ORDER BY r.date DESC, r.heure_debut DESC";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}

if ($params) {
    $bind_params = [];
    $bind_params[] = $param_types;

    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_params);
}

$stmt->execute();
$result = $stmt->get_result();
$reservations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage All Reservations - Admin - SportZone</title>
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
            --past-row-bg: #f8f8f8;
            --past-row-text: #888;
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
            --past-row-bg: #2d3042;
            --past-row-text: #a0a0a0;
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

        .filter-form {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            background-color: var(--input-bg);
            box-shadow: 0 2px 5px var(--shadow-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .filter-form h3 {
            font-family: 'Montserrat', sans-serif;
            color: var(--heading-color);
            margin-bottom: 1rem;
            font-weight: 700;
            text-align: left;
            font-size: 1.4rem;
        }
        .filter-form label {
            font-weight: 600;
            margin-right: 0.5rem;
            color: var(--text-color);
        }
        .filter-form input[type="text"],
        .filter-form input[type="date"],
        .filter-form select {
            border: 1px solid var(--input-border);
            border-radius: 5px;
            padding: 0.375rem 0.75rem;
            background-color: var(--card-bg);
            color: var(--text-color);
            margin-right: 10px;
            margin-bottom: 10px;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
            width: auto;
            max-width: 100%;
        }
        .filter-form button {
            background-color: var(--primary-color);
            color: var(--button-text);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 10px;
        }
        .filter-form button:hover {
            background-color: var(--link-hover-color);
        }
        .filter-form .btn-reset-filter {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-bottom: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .filter-form .btn-reset-filter:hover {
            background-color: #5a6268;
            color: white;
        }
        .filter-form .row {
            align-items: flex-end;
        }
        .filter-form .col-md-auto {
            margin-bottom: 10px;
        }


        .table {
            color: var(--text-color);
            border: 1px solid var(--border-subtle);
            transition: color 0.3s ease, border-color 0.3s ease;
        }

        .table thead th {
            background-color: var(--table-header-bg);
            color: var(--heading-color);
            border-bottom: 2px solid var(--border-subtle);
            border-top: none;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        .table tbody tr {
            background-color: var(--card-bg);
            transition: background-color 0.3s ease;
        }

        .table tbody tr:hover {
            background-color: var(--table-row-hover-bg);
        }

        .table td, .table th {
            border: 1px solid var(--border-subtle);
            padding: 0.3rem;
            vertical-align: middle;
            font-size: 0.75rem;
        }

        .status-enattente { color: var(--secondary-color); font-weight: bold; }
        .status-confirmée { color: var(--success-color); font-weight: bold; }
        .status-annulée { color: var(--error-color); font-weight: bold; text-decoration: line-through; }
        .status-terminée { color: var(--past-row-text); font-weight: bold; }
        
        .past-reservation {
            background-color: var(--past-row-bg) !important;
            color: var(--past-row-text) !important;
            opacity: 0.7;
        }

        .success-message {
            color: var(--success-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            padding: 0.8rem 1.2rem;
            background-color: rgba(40, 167, 69, 0.2);
            border-radius: 8px;
            border: 1px solid var(--success-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
            text-align: center;
        }

        .error-message {
            color: var(--error-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            padding: 0.8rem 1.2rem;
            background-color: rgba(220, 53, 69, 0.2);
            border-radius: 8px;
            border: 1px solid var(--error-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
            text-align: center;
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

        .btn-action {
            background-color: var(--primary-color);
            color: var(--button-text);
            border: none;
            padding: 0.2rem 0.4rem;
            border-radius: 5px;
            font-size: 0.7rem;
            transition: background-color 0.3s ease;
            text-decoration: none;
            margin-right: 5px;
            white-space: nowrap;
        }
        .btn-action:hover {
            background-color: var(--link-hover-color);
            color: var(--button-text);
        }

        .btn-danger-action {
            background-color: var(--error-color);
            color: var(--button-text);
            border: none;
            padding: 0.2rem 0.4rem;
            border-radius: 5px;
            font-size: 0.7rem;
            transition: background-color 0.3s ease;
            text-decoration: none;
            white-space: nowrap;
        }
        .btn-danger-action:hover {
            background-color: #c82333;
            color: var(--button-text);
        }

        .no-action-text {
            color: var(--past-row-text);
            opacity: 0.8;
            font-style: italic;
            font-size: 0.8rem;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 2rem;
            color: var(--link-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: var(--link-hover-color);
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .table-responsive {
                overflow-x: auto;
            }
            .filter-form .row {
                flex-direction: column;
            }
            .filter-form .col-md-auto {
                width: 100%;
                margin-bottom: 10px;
            }
            .filter-form .d-flex.align-items-end {
                flex-direction: column;
                align-items: stretch !important;
            }
            .filter-form button,
            .filter-form .btn-reset-filter {
                width: 100%;
                margin-right: 0 !important;
                margin-bottom: 10px;
            }
            .table td, .table th {
                padding: 0.2rem;
                font-size: 0.65rem;
            }
            .btn-action, .btn-danger-action {
                padding: 0.15rem 0.3rem;
                font-size: 0.6rem;
            }
            .no-action-text {
                font-size: 0.65rem;
            }
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
            <h2 class="mb-4">Manage All Reservations</h2>
            <p class="mb-4 d-flex justify-content-start">
                <a href="admin_dashboard.php" class="btn btn-secondary back-link m-0">
                    <i class="fas fa-arrow-left"></i> Back to Admin Dashboard
                </a>
            </p>

            <?php
            if (isset($_GET['msg'])) {
                echo "<div class='alert alert-success success-message' role='alert'>" . htmlspecialchars($_GET['msg']) . "</div>";
            }
            if (isset($_GET['error'])) {
                echo "<div class='alert alert-danger error-message' role='alert'>" . htmlspecialchars($_GET['error']) . "</div>";
            }
            ?>

            <div class="filter-form">
                <h3>Filter Reservations</h3>
                <form method="GET" action="">
                    <div class="row g-2">
                        <div class="col-md-auto">
                            <label for="user_name" class="form-label mb-0">User Name:</label>
                            <input type="text" class="form-control" id="user_name" name="user_name" value="<?= htmlspecialchars($filter_user_name) ?>">
                        </div>
                        <div class="col-md-auto">
                            <label for="terrain_id" class="form-label mb-0">Field Name:</label>
                            <select class="form-select" name="terrain_id" id="terrain_id">
                                <option value="">All Fields</option>
                                <?php foreach ($terrains as $id => $name): ?>
                                    <option value="<?= $id ?>" <?= ($filter_terrain_id == $id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <label for="sport_id" class="form-label mb-0">Sport:</label>
                            <select class="form-select" name="sport_id" id="sport_id">
                                <option value="">All Sports</option>
                                <?php foreach ($sports as $id => $name): ?>
                                    <option value="<?= $id ?>" <?= ($filter_sport_id == $id) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <label for="date" class="form-label mb-0">Date:</label>
                            <input type="date" class="form-control" id="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
                        </div>
                        <div class="col-md-auto">
                            <label for="status" class="form-label mb-0">Status:</label>
                            <select class="form-select" name="status" id="status">
                                <option value="">All</option>
                                <?php foreach ($possible_statuses as $status_option): ?>
                                    <option value="<?= $status_option ?>" <?= ($filter_status === $status_option) ? 'selected' : '' ?>>
                                        <?= ucfirst(str_replace('_', ' ', $status_option)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-auto d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                            <a href="admin_reservations.php" class="btn btn-reset-filter">Reset Filters</a>
                        </div>
                    </div>
                </form>
            </div>

            <?php if (empty($reservations)): ?>
                <p class="text-center text-muted">No reservations found matching your criteria.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-sm">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Email</th>
                                <th>Field</th>
                                <th>Sport</th>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $res): ?>
                            <?php
                                $reservation_datetime_end = new DateTime($res['date_reservation'] . ' ' . $res['heure_fin']);
                                $current_datetime = new DateTime();
                                $is_past_reservation = ($reservation_datetime_end < $current_datetime);

                                $display_status = htmlspecialchars($res['statut']);
                                $status_class = "status-" . strtolower(str_replace(' ', '', $res['statut']));

                                if ($is_past_reservation && ($res['statut'] == 'en attente' || $res['statut'] == 'confirmée')) {
                                    $display_status = "Terminated";
                                    $status_class = "status-terminée";
                                }
                            ?>
                            <tr class="<?= $is_past_reservation ? 'past-reservation' : '' ?>">
                                <td><?= htmlspecialchars($res['id']) ?></td>
                                <td><?= htmlspecialchars($res['user_name']) ?></td>
                                <td><?= htmlspecialchars($res['user_email']) ?></td>
                                <td><?= htmlspecialchars($res['terrain_name']) ?></td>
                                <td><?= htmlspecialchars($res['sport_name']) ?></td>
                                <td><?= htmlspecialchars($res['date_reservation']) ?></td>
                                <td><?= htmlspecialchars($res['heure_debut']) ?></td>
                                <td><?= htmlspecialchars($res['heure_fin']) ?></td>
                                <td class="<?= $status_class ?>"><?= $display_status ?></td>
                                <td>
                                    <a href="change_reservation_status.php?id=<?= $res['id'] ?>" class="btn btn-action">Change Status</a>

                                    <a href="admin_reservations.php?delete_id=<?= $res['id'] ?>" class="btn btn-danger-action" onclick="return confirm('Are you sure you want to PERMANENTLY DELETE this reservation?');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
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