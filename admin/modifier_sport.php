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

$errors = [];
$sport_name = '';

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: admin_sports.php?error=" . urlencode("Invalid sport ID provided for modification."));
    exit;
}

$id = intval($_GET["id"]);

$stmt = $conn->prepare("SELECT name FROM sports WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$sport_data = $result->fetch_assoc();
$stmt->close();

if (!$sport_data) {
    header("Location: admin_sports.php?error=" . urlencode("Sport not found."));
    exit;
}

$sport_name = $sport_data['name'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $new_sport_name = htmlspecialchars(trim($_POST["name"]));

    if (empty($new_sport_name)) {
        $errors[] = "Sport name cannot be empty.";
    } elseif ($new_sport_name === $sport_name) {
        header("Location: admin_sports.php?msg=" . urlencode("No changes made to sport."));
        exit;
    } else {
        $stmt_check = $conn->prepare("SELECT id FROM sports WHERE LOWER(name) = LOWER(?) AND id != ?");
        $stmt_check->bind_param("si", $new_sport_name, $id);
        $stmt_check->execute();
        $existing_sport = $stmt_check->get_result()->fetch_assoc();
        $stmt_check->close();

        if ($existing_sport) {
            $errors[] = "A sport with this name already exists.";
        } else {
            $stmt_update = $conn->prepare("UPDATE sports SET name = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_sport_name, $id);

            if ($stmt_update->execute()) {
                header("Location: admin_sports.php?msg=" . urlencode("Sport updated successfully."));
                exit;
            } else {
                $errors[] = "Error updating sport: " . $stmt_update->error;
            }
            $stmt_update->close();
        }
    }
    $sport_name = $new_sport_name;
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sport - Admin - SportZone</title>
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
            max-width: 400px;
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

        input[type="text"] {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--input-border);
            border-radius: 5px;
            background-color: var(--input-bg);
            color: var(--text-color);
            box-sizing: border-box;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }
        input[type="text"]:focus {
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
            width: 100%;
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
            <h2 class="mb-4">Edit Sport</h2>
            <p class="mb-4 d-flex justify-content-start">
                <a href="admin_sports.php" class="btn btn-secondary back-link m-0">
                    <i class="fas fa-arrow-left"></i> Back to Sports List
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
                    <label for="name" class="form-label">Sport Name:</label>
                    <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($sport_name) ?>" required>
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