<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'client') {
    header('Location: ../auth/login.php');
    exit;
}

$errors = [];
$success = "";
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $terrain_id = intval($_POST['terrain_id']);
    $reservation_date = trim($_POST['date_reservation']);
    $start_time = trim($_POST['heure_debut']);
    $end_time = trim($_POST['heure_fin']);

    if (empty($terrain_id) || empty($reservation_date) || empty($start_time) || empty($end_time)) {
        $errors[] = "All fields are required.";
    } elseif ($start_time >= $end_time) {
        $errors[] = "Start time must be before end time.";
    } else {
        $datetime_start_str = $reservation_date . ' ' . $start_time;
        $datetime_end_str = $reservation_date . ' ' . $end_time;
        
        $datetime_start = new DateTime($datetime_start_str);
        $datetime_end = new DateTime($datetime_end_str);
        $current_datetime = new DateTime();
        $min_reservation_time_threshold = (clone $current_datetime)->modify('+1 hour');

        if ($datetime_start <= $current_datetime) {
            $errors[] = "Bookings cannot be made in the past or at the current time. The booking must be in the future.";
        } elseif ($datetime_start < $min_reservation_time_threshold) {
            $errors[] = "Bookings must be made at least 1 hour in advance of the start time.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM terrains WHERE id = ? AND is_active = TRUE");
            $stmt->bind_param("i", $terrain_id);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $errors[] = "The selected field is not currently available for booking.";
            }
            $stmt->close();

            if (empty($errors)) {
                $stmt = $conn->prepare("SELECT id FROM reservations WHERE terrain_id = ? AND date = ? AND 
                                        ((heure_debut < ?) AND (heure_fin > ?) AND statut IN ('en attente', 'confirmée'))");
                $stmt->bind_param("isss", $terrain_id, $reservation_date, $end_time, $start_time);
                $stmt->execute();
                $result_overlap = $stmt->get_result();

                if ($result_overlap->num_rows > 0) {
                    $errors[] = "The field is already booked for this time slot.";
                }
                $stmt->close();
            }

            if (empty($errors)) {
                 $stmt = $conn->prepare("SELECT id FROM date_heures_fermees WHERE terrain_id = ? AND date = ? AND 
                                         ((debut_fermeture < ?) AND (fin_fermeture > ?))");
                $stmt->bind_param("isss", $terrain_id, $reservation_date, $end_time, $start_time);
                $stmt->execute();
                $result_closed = $stmt->get_result();

                if ($result_closed->num_rows > 0) {
                    $errors[] = "The field is closed during this time period.";
                }
                $stmt->close();
            }

            if (empty($errors)) {
                $status = 'en attente';
                $stmt = $conn->prepare("INSERT INTO reservations (user_id, terrain_id, date, heure_debut, heure_fin, statut) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissss", $user_id, $terrain_id, $reservation_date, $start_time, $end_time, $status);

                if ($stmt->execute()) {
                    $success = "Booking successful. Status: " . $status;
                    header("Location: list_reservations.php?msg=" . urlencode($success));
                    exit;
                } else {
                    $errors[] = "Error during booking. Please try again.";
                }
                $stmt->close();
            }
        }
    }
}

$terrains = [];
$result = $conn->query("SELECT t.id, t.name, t.addresse, s.name AS sport_name FROM terrains t JOIN sports s ON t.sports_id = s.id WHERE t.is_active = TRUE ORDER BY t.name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $terrains[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book a Field - SportZone</title>
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

        .form-container {
            background-color: var(--card-bg);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 0 15px var(--shadow-color);
            max-width: 600px;
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

        .form-label {
            color: var(--text-color);
            font-weight: 600;
            display: block;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }

        .form-control, .form-select {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-color);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out, background-color 0.3s ease, color 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--input-bg);
            color: var(--text-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }

        .btn-sport {
            background-color: var(--primary-color);
            color: var(--button-text);
            border: none;
            padding: 0.8rem 2.5rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out, box-shadow 0.3s ease-in-out;
            margin-top: 1.5rem;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }

        .btn-sport:hover {
            background-color: var(--link-hover-color);
            transform: translateY(-2px);
            color: var(--button-text);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
        }

        .error-message {
            color: var(--error-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: rgba(220, 53, 69, 0.2);
            border-radius: 5px;
            border: 1px solid var(--error-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        .success-message {
            color: var(--success-color);
            font-size: 0.9rem;
            margin-top: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: rgba(40, 167, 69, 0.2);
            border-radius: 5px;
            border: 1px solid var(--success-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
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
    <nav class="navbar navbar-expand-lg">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">SportZone Booking</a>
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
        <div class="form-container">
            <h2>Book a Field</h2>

            <?php
            if (isset($_GET['msg'])) {
                echo "<p class='success-message'>" . htmlspecialchars($_GET['msg']) . "</p>";
            }
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    echo "<p class='error-message'>$error</p>";
                }
            }
            ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="terrain_id" class="form-label">Field:</label>
                    <select name="terrain_id" id="terrain_id" class="form-select" required>
                        <option value="">-- Select a field --</option>
                        <?php foreach ($terrains as $terrain): ?>
                            <option value="<?= $terrain['id'] ?>" <?= (isset($_POST['terrain_id']) && $_POST['terrain_id'] == $terrain['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($terrain['name']) ?> (<?= htmlspecialchars($terrain['sport_name']) ?> - <?= htmlspecialchars($terrain['addresse']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">
                        Please select a field.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="date_reservation" class="form-label">Reservation Date:</label>
                    <input type="date" name="date_reservation" id="date_reservation" class="form-control" value="<?= isset($_POST['date_reservation']) ? htmlspecialchars($_POST['date_reservation']) : date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>" required>
                    <div class="invalid-feedback">
                        Please select a valid date.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="heure_debut" class="form-label">Start Time:</label>
                    <input type="time" name="heure_debut" id="heure_debut" class="form-control" value="<?= isset($_POST['heure_debut']) ? htmlspecialchars($_POST['heure_debut']) : '' ?>" required>
                    <div class="invalid-feedback">
                        Please select a start time.
                    </div>
                </div>

                <div class="mb-3">
                    <label for="heure_fin" class="form-label">End Time:</label>
                    <input type="time" name="heure_fin" id="heure_fin" class="form-control" value="<?= isset($_POST['heure_fin']) ? htmlspecialchars($_POST['heure_fin']) : '' ?>" required>
                    <div class="invalid-feedback">
                        Please select an end time.
                    </div>
                </div>

                <button type="submit" class="btn btn-sport w-100">Book Now</button>
            </form>
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

            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            document.getElementById('date_reservation').min = `${year}-${month}-${day}`;
        });

        themeToggle.addEventListener('click', () => {
            const currentTheme = htmlElement.getAttribute('data-bs-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            setTheme(newTheme);
        });

        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>