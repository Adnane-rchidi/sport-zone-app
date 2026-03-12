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
    header("Location: admin_closed_hours.php?error=" . urlencode("Invalid closed hour ID provided."));
    exit;
}

$id = intval($_GET["id"]);
$errors = [];

$stmt = $conn->prepare("SELECT id, terrain_id, DATE_FORMAT(date, '%Y-%m-%d') AS date_only, TIME_FORMAT(debut_fermeture, '%H:%i:%s') AS debut_fermeture, TIME_FORMAT(fin_fermeture, '%H:%i:%s') AS fin_fermeture FROM date_heures_fermees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$closed_hour_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$closed_hour_data) {
    header("Location: admin_closed_hours.php?error=" . urlencode("Closed hour entry not found."));
    exit;
}

$closed_hour_date = $closed_hour_data['date_only'];
$closed_hour_fin_fermeture = $closed_hour_data['fin_fermeture'];
$closed_hour_debut_fermeture = $closed_hour_data['debut_fermeture'];

try {
    $current_closed_end_datetime_obj = new DateTime($closed_hour_date . ' ' . $closed_hour_fin_fermeture);
    $now = new DateTime();
    if ($current_closed_end_datetime_obj < $now) {
        header("Location: admin_closed_hours.php?error=" . urlencode("Cannot modify a closed hour entry that has already passed."));
        exit;
    }
} catch (Exception $e) {
    $errors[] = "Error parsing date/time (initial check): " . $e->getMessage();
}

$terrains = $conn->query("SELECT id, name FROM terrains WHERE is_active = TRUE ORDER BY name");
if (!$terrains) {
    die("Error fetching active terrains: " . $conn->error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $terrain_id = intval($_POST['terrain_id']);
    $date = trim($_POST['date']);
    $debut_fermeture_post = trim($_POST['debut_fermeture']);
    $fin_fermeture_post = trim($_POST['fin_fermeture']);

    if (strlen($debut_fermeture_post) === 5) {
        $debut_fermeture_post .= ':00';
    }
    if (strlen($fin_fermeture_post) === 5) {
        $fin_fermeture_post .= ':00';
    }

    if (empty($terrain_id) || empty($date) || empty($debut_fermeture_post) || empty($fin_fermeture_post)) {
        $errors[] = "All fields are required.";
    } elseif (strtotime($debut_fermeture_post) >= strtotime($fin_fermeture_post)) {
        $errors[] = "Start closure time must be before end closure time.";
    } else {
        try {
            $closed_start_datetime = new DateTime($date . ' ' . $debut_fermeture_post);
            $closed_end_datetime = new DateTime($date . ' ' . $fin_fermeture_post);
            $current_datetime = new DateTime();

            if ($closed_end_datetime < $current_datetime) {
                $errors[] = "Cannot set closed hours for periods that are in the past.";
            } else {
                $stmt_overlap_reservations = $conn->prepare("SELECT id, date, heure_debut, heure_fin FROM reservations WHERE terrain_id = ? AND date = ? AND
                                ((heure_debut < ?) AND (heure_fin > ?) AND (statut = 'en attente' OR statut = 'confirmée'))");
                $stmt_overlap_reservations->bind_param("isss", $terrain_id, $date, $fin_fermeture_post, $debut_fermeture_post);
                $stmt_overlap_reservations->execute();
                $overlapping_reservations = $stmt_overlap_reservations->get_result();

                if ($overlapping_reservations->num_rows > 0) {
                    $errors[] = "This closed hour overlaps with existing active reservations. Please cancel/reschedule those bookings first.";
                }
                $stmt_overlap_reservations->close();

                if (empty($errors)) {
                    $stmt_overlap_closed = $conn->prepare("SELECT id FROM date_heures_fermees WHERE terrain_id = ? AND date = ? AND id != ? AND
                                ((debut_fermeture < ?) AND (fin_fermeture > ?))");
                    $stmt_overlap_closed->bind_param("iisss", $terrain_id, $date, $id, $fin_fermeture_post, $debut_fermeture_post);
                    $stmt_overlap_closed->execute();
                    $overlapping_closed = $stmt_overlap_closed->get_result();

                    if ($overlapping_closed->num_rows > 0) {
                        $errors[] = "This closed hour overlaps with another existing closed hour entry for this field.";
                    }
                    $stmt_overlap_closed->close();
                }

                if (empty($errors)) {
                    $stmt = $conn->prepare("UPDATE date_heures_fermees SET terrain_id = ?, date = ?, debut_fermeture = ?, fin_fermeture = ? WHERE id = ?");
                    $stmt->bind_param("isssi", $terrain_id, $date, $debut_fermeture_post, $fin_fermeture_post, $id);

                    if ($stmt->execute()) {
                        header("Location: admin_closed_hours.php?msg=" . urlencode("Closed hour updated successfully."));
                        exit;
                    } else {
                        $errors[] = "Error updating closed hour: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error processing date/time: " . $e->getMessage();
        }
    }
}

$display_terrain_id = isset($_POST['terrain_id']) ? $_POST['terrain_id'] : $closed_hour_data['terrain_id'];
$display_date = isset($_POST['date']) ? $_POST['date'] : $closed_hour_date;
$display_debut_fermeture = isset($_POST['debut_fermeture']) ? $_POST['debut_fermeture'] : substr($closed_hour_data['debut_fermeture'], 0, 5);
$display_fin_fermeture = isset($_POST['fin_fermeture']) ? $_POST['fin_fermeture'] : substr($closed_hour_data['fin_fermeture'], 0, 5);

?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light"> <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Closed Hour - Admin - SportZone</title>
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
            max-width: 500px;
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

        label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            display: block;
        }

        input[type="date"], 
        input[type="time"], 
        select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--input-border);
            border-radius: 5px;
            background-color: var(--input-bg);
            color: var(--text-color);
            box-sizing: border-box;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
        input[type="date"]:focus, 
        input[type="time"]:focus, 
        select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
            outline: none;
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
            margin-bottom: 1rem;
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
            <h2 class="mb-4">Modify Closed Hour Entry</h2>
            <p class="mb-4 d-flex justify-content-start">
                <a href="admin_closed_hours.php" class="btn btn-secondary back-link m-0">
                    <i class="fas fa-arrow-left"></i> Back to Closed Hours List
                </a>
            </p>

            <?php
            if (!empty($errors)) {
                foreach ($errors as $e) {
                    echo "<div class='alert alert-danger error-message' role='alert'>" . htmlspecialchars($e) . "</div>";
                }
            }
            ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="terrain_id" class="form-label">Field:</label>
                    <select name="terrain_id" id="terrain_id" class="form-select" required>
                        <option value="">-- Select a field --</option>
                        <?php $terrains->data_seek(0); ?>
                        <?php while ($t = $terrains->fetch_assoc()): ?>
                            <option value="<?= $t["id"] ?>" <?= ($display_terrain_id == $t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t["name"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="date" class="form-label">Date:</label>
                    <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($display_date) ?>" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="debut_fermeture" class="form-label">Start Closure Time:</label>
                    <input type="time" name="debut_fermeture" id="debut_fermeture" class="form-control" value="<?= htmlspecialchars($display_debut_fermeture) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="fin_fermeture" class="form-label">End Closure Time:</label>
                    <input type="time" name="fin_fermeture" id="fin_fermeture" class="form-control" value="<?= htmlspecialchars($display_fin_fermeture) ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
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