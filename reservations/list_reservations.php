<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT
                            r.id,
                            t.name AS terrain_name,
                            s.name AS sport_name,
                            r.date,
                            r.heure_debut,
                            r.heure_fin,
                            r.statut
                        FROM reservations r
                        JOIN terrains t ON r.terrain_id = t.id
                        JOIN sports s ON t.sports_id = s.id
                        WHERE r.user_id = ?
                        ORDER BY r.date DESC, r.heure_debut DESC");
$stmt->bind_param("i", $user_id);
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
    <title>My Reservations - SportZone</title>
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
            font-size: 1.6rem;
            letter-spacing: 1px;
            transition: color 0.3s ease;
        }

        .main-content {
            flex-grow: 1;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .content-container {
            background-color: var(--card-bg);
            padding: 1rem 1.5rem;
            border-radius: 15px;
            box-shadow: 0 0 15px var(--shadow-color);
            max-width: 800px;
            width: 95%;
            margin: 1rem auto;
            border: 1px solid var(--border-subtle);
            transition: background-color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }

        h2 {
            font-family: 'Montserrat', sans-serif;
            color: var(--heading-color);
            margin-bottom: 1rem;
            font-weight: 800;
            text-align: center;
            font-size: 1.6rem;
            transition: color 0.3s ease;
        }

        .table {
            color: var(--text-color);
            border: 1px solid var(--border-subtle);
            transition: color 0.3s ease, border-color 0.3s ease;
            font-size: 0.85rem;
        }

        .table thead th {
            background-color: var(--table-header-bg);
            color: var(--heading-color);
            border-bottom: 2px solid var(--border-subtle);
            border-top: none;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
            padding: 0.6rem;
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
            padding: 0.6rem;
            vertical-align: middle;
        }

        .status-enattente {
            color: var(--secondary-color);
            font-weight: bold;
        }

        .status-confirmée {
            color: var(--success-color);
            font-weight: bold;
        }

        .status-annulée {
            color: var(--error-color);
            font-weight: bold;
            text-decoration: line-through;
        }

        .past-reservation {
            background-color: var(--past-row-bg) !important;
            color: var(--past-row-text);
            opacity: 0.7;
        }

        .action-link {
            color: var(--link-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            font-size: 0.8rem;
        }

        .action-link:hover {
            color: var(--link-hover-color);
            text-decoration: underline;
        }

        .no-action-text {
            color: var(--text-color);
            opacity: 0.7;
            font-size: 0.8rem;
        }

        .success-message, .error-message {
            font-size: 0.8rem;
            margin-bottom: 0.8rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            border: 1px solid;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        .error-message {
            color: var(--error-color);
            background-color: rgba(220, 53, 69, 0.2);
            border-color: var(--error-color);
        }

        .success-message {
            color: var(--success-color);
            background-color: rgba(40, 167, 69, 0.2);
            border-color: var(--success-color);
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: var(--link-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
            font-size: 0.9rem;
        }

        .back-link:hover {
            color: var(--link-hover-color);
            text-decoration: underline;
        }

        .theme-toggle-btn {
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.3rem;
            cursor: pointer;
            padding: 0;
            margin-left: 0.8rem;
            transition: color 0.3s ease;
        }
        .theme-toggle-btn:hover {
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .content-container {
                padding: 0.8rem;
                margin: 0.8rem auto;
                max-width: 98%;
            }
            .table th, .table td {
                padding: 0.4rem;
                font-size: 0.75rem;
            }
            .navbar-brand {
                font-size: 1.2rem;
            }
            h2 {
                font-size: 1.4rem;
                margin-bottom: 0.6rem;
            }
            .table-responsive {
                overflow-x: auto;
            }
            .success-message, .error-message {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
            }
            .back-link {
                font-size: 0.85rem;
                margin-top: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">SportZone Bookings</a>
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
        <div class="content-container">
            <h2>My Reservations</h2>

            <?php
            if (isset($_GET['msg'])) {
                echo "<p class='success-message'>" . htmlspecialchars($_GET['msg']) . "</p>";
            }
            if (isset($_GET['error'])) {
                echo "<p class='error-message'>" . htmlspecialchars($_GET['error']) . "</p>";
            }
            ?>

            <?php if (empty($reservations)): ?>
                <p class="text-center text-muted">You have no reservations yet. <a href="add_reservation.php" class="action-link">Book one now!</a></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Field</th>
                                <th>Sport</th>
                                <th>Date</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $res): ?>
                            <?php
                                $clean_heure_fin = $res['heure_fin'];
                                if (strpos($res['heure_fin'], ' ') !== false) {
                                    $time_parts = explode(' ', $res['heure_fin']);
                                    $clean_heure_fin = end($time_parts);
                                }
                                $reservation_datetime_end = new DateTime($res['date'] . ' ' . $clean_heure_fin);
                                $current_datetime = new DateTime();
                                $is_past_reservation = ($reservation_datetime_end < $current_datetime);
                            ?>
                            <tr class="<?= $is_past_reservation ? 'past-reservation' : '' ?>">
                                <td><?= htmlspecialchars($res['terrain_name']) ?></td>
                                <td><?= htmlspecialchars($res['sport_name']) ?></td>
                                <td><?= htmlspecialchars($res['date']) ?></td>
                                <td><?= htmlspecialchars($res['heure_debut']) ?></td>
                                <td><?= htmlspecialchars($res['heure_fin']) ?></td>
                                <td class="status-<?= strtolower(str_replace(' ', '', $res['statut'])) ?>"><?= htmlspecialchars($res['statut']) ?></td>
                                <td>
                                    <?php
                                    if (!$is_past_reservation && $res['statut'] !== 'annulée'): ?>
                                        <a href="cancel_reservation.php?id=<?= $res['id'] ?>" class="action-link" onclick="return confirm('Are you sure you want to cancel this booking?');">Cancel</a>
                                    <?php else: ?>
                                        <span class="no-action-text">No Action</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <a href="../dashboard/dashboard.php" class="back-link">Back to Dashboard</a>
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