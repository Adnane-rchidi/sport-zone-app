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

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: admin_reservations.php?error=" . urlencode("Invalid reservation ID."));
    exit;
}

$reservation_id = intval($_GET["id"]);
$errors = [];
$success_message = '';

$stmt = $conn->prepare("SELECT 
                            r.id, 
                            u.name AS user_name, 
                            t.name AS terrain_name, 
                            r.date, 
                            r.heure_debut, 
                            r.heure_fin, 
                            r.statut 
                        FROM reservations r 
                        JOIN utilisateurs u ON r.user_id = u.id 
                        JOIN terrains t ON r.terrain_id = t.id 
                        WHERE r.id = ?");
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$reservation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reservation) {
    header("Location: admin_reservations.php?error=" . urlencode("Reservation not found."));
    exit;
}

$reservation_datetime_end = new DateTime($reservation['date'] . ' ' . $reservation['heure_fin']);
$current_datetime = new DateTime();
$is_past_reservation = ($reservation_datetime_end < $current_datetime);

$allowed_statuses_for_change = ['en attente', 'confirmée', 'annulée'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_status = htmlspecialchars(trim($_POST["statut"]));

    if (!in_array($new_status, $allowed_statuses_for_change)) {
        $errors[] = "Invalid status selected.";
    } elseif ($is_past_reservation) {
        $errors[] = "Cannot change status for past reservations.";
    } else {
        $stmt = $conn->prepare("UPDATE reservations SET statut = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $reservation_id);

        if ($stmt->execute()) {
            $success_message = "Reservation status updated successfully to '" . htmlspecialchars($new_status) . "'.";
            $reservation['statut'] = $new_status; 
        } else {
            $errors[] = "Error updating reservation status: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Reservation Status - Admin - SportZone</title>
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
            max-width: 700px;
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

        h3 {
            font-family: 'Montserrat', sans-serif;
            color: var(--heading-color);
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            font-size: 1.5rem;
            transition: color 0.3s ease;
        }

        label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        select {
            border: 1px solid var(--input-border);
            border-radius: 5px;
            padding: 0.5rem 1rem;
            background-color: var(--input-bg);
            color: var(--text-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--button-text);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: var(--link-hover-color);
            color: var(--button-text);
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

        ul {
            list-style: none;
            padding: 0;
            margin-bottom: 1.5rem;
        }
        ul li {
            margin-bottom: 0.5rem;
            color: var(--text-color);
            transition: color 0.3s ease;
        }
        ul li strong {
            color: var(--heading-color);
            transition: color 0.3s ease;
        }

        .current-status { 
            font-weight: bold; 
            padding: 0.2em 0.6em;
            border-radius: 5px;
            display: inline-block;
        }
        .status-enattente { background-color: var(--secondary-color); color: var(--button-text); }
        .status-confirmée { background-color: var(--success-color); color: var(--button-text); }
        .status-annulée { background-color: var(--error-color); color: var(--button-text); text-decoration: line-through; }
        .status-terminée { background-color: var(--past-row-text); color: var(--button-text); }

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
            <h2 class="mb-4">Change Reservation Status</h2>
            <p class="mb-4 d-flex justify-content-start">
                <a href="admin_reservations.php" class="btn btn-secondary back-link m-0">
                    <i class="fas fa-arrow-left"></i> Back to All Reservations
                </a>
            </p>

            <h3>Reservation Details for ID: <?= htmlspecialchars($reservation['id']) ?></h3>
            <ul>
                <li><strong>User:</strong> <?= htmlspecialchars($reservation['user_name']) ?></li>
                <li><strong>Field:</strong> <?= htmlspecialchars($reservation['terrain_name']) ?></li>
                <li><strong>Date:</strong> <?= htmlspecialchars($reservation['date']) ?></li>
                <li><strong>Time:</strong> <?= htmlspecialchars($reservation['heure_debut']) ?> - <?= htmlspecialchars($reservation['heure_fin']) ?></li>
                <li><strong>Current Status:</strong> <span class="current-status status-<?= strtolower(str_replace(' ', '', $reservation['statut'])) ?>"><?= htmlspecialchars($reservation['statut']) ?></span></li>
            </ul>

            <?php 
            if ($is_past_reservation) {
                echo "<div class='alert alert-warning error-message' role='alert'>
                            <i class='fas fa-exclamation-triangle'></i> This reservation is in the past. Status cannot be changed.
                          </div>";
            }
            if (!empty($errors)) {
                foreach ($errors as $e) {
                    echo "<div class='alert alert-danger error-message' role='alert'>
                                <i class='fas fa-times-circle'></i> " . htmlspecialchars($e) . "
                              </div>";
                }
            }
            if (!empty($success_message)) {
                echo "<div class='alert alert-success success-message' role='alert'>
                            <i class='fas fa-check-circle'></i> " . htmlspecialchars($success_message) . "
                          </div>";
            }
            ?>

            <?php if (!$is_past_reservation): ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="statut" class="form-label">New Status:</label>
                        <select name="statut" id="statut" class="form-select" required>
                            <?php foreach ($allowed_statuses_for_change as $status_option): ?>
                                <option value="<?= $status_option ?>" <?= ($reservation['statut'] === $status_option) ? 'selected' : '' ?>>
                                    <?= ucfirst(str_replace('_', ' ', $status_option)) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </form>
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