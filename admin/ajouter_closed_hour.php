<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: ../auth/login.php");
    exit;
}

$errors = [];

$terrains = $conn->query("SELECT id, name FROM terrains WHERE is_active = TRUE ORDER BY name");
if (!$terrains) {
    die("Error fetching terrains: " . $conn->error);
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
    } elseif ($debut_fermeture_post >= $fin_fermeture_post) {
        $errors[] = "Start closure time must be before end closure time.";
    } else {
        try {
            $closed_start_datetime = new DateTime($date . ' ' . $debut_fermeture_post);
            $closed_end_datetime = new DateTime($date . ' ' . $fin_fermeture_post);
            $current_datetime = new DateTime();

            if ($closed_end_datetime < $current_datetime) {
                $errors[] = "Cannot add closed hours in the past.";
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
                    $stmt_overlap_closed = $conn->prepare("SELECT id FROM date_heures_fermees WHERE terrain_id = ? AND date = ? AND
                                          ((debut_fermeture < ?) AND (fin_fermeture > ?))");
                    $stmt_overlap_closed->bind_param("isss", $terrain_id, $date, $fin_fermeture_post, $debut_fermeture_post);
                    $stmt_overlap_closed->execute();
                    $overlapping_closed = $stmt_overlap_closed->get_result();

                    if ($overlapping_closed->num_rows > 0) {
                        $errors[] = "This closed hour overlaps with an already existing closed hour entry for this field.";
                    }
                    $stmt_overlap_closed->close();
                }

                if (empty($errors)) {
                    $stmt = $conn->prepare("INSERT INTO date_heures_fermees (terrain_id, date, debut_fermeture, fin_fermeture) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $terrain_id, $date, $debut_fermeture_post, $fin_fermeture_post);

                    if ($stmt->execute()) {
                        header("Location: admin_closed_hours.php?msg=" . urlencode("Closed hour added successfully."));
                        exit;
                    } else {
                        $errors[] = "Error adding closed hour: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        } catch (Exception $e) {
            $errors[] = "Error parsing date/time: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Closed Hour - Admin - SportZone</title>
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
            --button-text: #ffffff;
            --link-color: #007bff;
            --link-hover-color: #0056b3;
            --error-color: #dc3545;
            --success-message-color: #28a745;
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
            --secondary-color: #9ac9f9;
            --success-color: #4CAF50;
            --danger-color: #ff6b6b;
            --button-text: #ffffff;
            --link-color: #66b3ff;
            --link-hover-color: #9ac9f9;
            --error-color: #ff6b6b;
            --success-message-color: #4CAF50;
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .content-container {
            background-color: var(--card-bg);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 0 15px var(--shadow-color);
            max-width: 600px;
            width: 100%;
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

        .form-label {
            color: var(--text-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            background-color: var(--input-bg);
            color: var(--text-color);
            border-color: var(--input-border);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }
        .form-control:focus, .form-select:focus {
            background-color: var(--card-bg);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--button-text);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: var(--link-hover-color);
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--button-text);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: 600;
        }
        .btn-secondary:hover {
            background-color: #5c636a;
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
            margin-top: 1.5rem;
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
            <h2 class="mb-4">Add New Closed Hour Entry</h2>
            <p class="text-center">
                <a href="admin_closed_hours.php" class="btn btn-secondary back-link">
                    <i class="fas fa-arrow-left"></i> Back to Closed Hours List
                </a>
            </p>

            <?php
            if (!empty($errors)) {
                foreach ($errors as $e) {
                    echo "<div class='alert alert-danger message-error' role='alert'>$e</div>";
                }
            }
            ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="terrain_id" class="form-label">Field:</label>
                    <select name="terrain_id" id="terrain_id" class="form-select" required>
                        <option value="">-- Select a field --</option>
                        <?php while ($t = $terrains->fetch_assoc()): ?>
                            <option value="<?= $t["id"] ?>" <?= (isset($_POST['terrain_id']) && $_POST['terrain_id'] == $t['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t["name"]) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="date" class="form-label">Date:</label>
                    <input type="date" name="date" id="date" class="form-control" value="<?= isset($_POST['date']) ? htmlspecialchars($_POST['date']) : date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                </div>

                <div class="mb-3">
                    <label for="debut_fermeture" class="form-label">Start Closure Time:</label>
                    <input type="time" name="debut_fermeture" id="debut_fermeture" class="form-control" value="<?= isset($_POST['debut_fermeture']) ? htmlspecialchars($_POST['debut_fermeture']) : '' ?>" required>
                </div>

                <div class="mb-3">
                    <label for="fin_fermeture" class="form-label">End Closure Time:</label>
                    <input type="time" name="fin_fermeture" id="fin_fermeture" class="form-control" value="<?= isset($_POST['fin_fermeture']) ? htmlspecialchars($_POST['fin_fermeture']) : '' ?>" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i> Add Closed Hour
                    </button>
                </div>
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