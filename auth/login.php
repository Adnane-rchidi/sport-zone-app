<?php
session_start();
require_once '../config/config.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = htmlspecialchars(trim($_POST["email"] ?? ''));
    $password = $_POST["password"] ?? '';

    if (empty($email) || empty($password)) {
        $errors[] = "Both fields are required.";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password_hash, role FROM utilisateurs WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user["password_hash"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["role"] = $user["role"];

                if ($user["role"] === "admin") {
                    header("Location: ../admin/admin_dashboard.php");
                } else {
                    header("Location: ../dashboard/dashboard.php");
                }
                exit;
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No account found with this email.";
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
    <title>Login - SportZone</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            background-image: url('https://source.unsplash.com/random/1600x900/?sport,gym,athlete');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            transition: background-color 0.3s ease;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .login-container {
            background-color: var(--card-bg);
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 0 20px var(--shadow-color);
            max-width: 400px;
            width: 90%;
            text-align: center;
            position: relative;
            z-index: 2;
            border: 1px solid var(--border-subtle);
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        h2 {
            font-family: 'Montserrat', sans-serif;
            color: var(--heading-color);
            margin-bottom: 1.2rem;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-size: 1.8rem;
            transition: color 0.3s ease;
        }

        .form-label {
            color: var(--text-color);
            font-weight: 600;
            text-align: left;
            display: block;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .form-control {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-color);
            padding: 0.6rem 0.8rem;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: border-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out, background-color 0.3s ease, color 0.3s ease;
        }

        .form-control:focus {
            background-color: var(--input-bg);
            color: var(--text-color);
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
        }

        .btn-sport {
            background-color: var(--primary-color);
            color: var(--button-text);
            border: none;
            padding: 0.7rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            transition: background-color 0.3s ease-in-out, transform 0.2s ease-in-out, box-shadow 0.3s ease-in-out;
            margin-top: 1.2rem;
            box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3);
        }

        .btn-sport:hover {
            background-color: var(--link-hover-color);
            transform: translateY(-2px);
            color: var(--button-text);
            box-shadow: 0 6px 15px rgba(0, 123, 255, 0.4);
        }

        .error-message, .success-message {
            font-size: 0.85rem;
            margin-top: 0.4rem;
            padding: 0.4rem 0.8rem;
            border-radius: 5px;
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

        p.text-center {
            color: var(--text-color);
            margin-top: 1.2rem;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        p.text-center a {
            color: var(--link-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease-in-out;
        }

        p.text-center a:hover {
            color: var(--link-hover-color);
            text-decoration: underline;
        }

        .theme-toggle-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: var(--card-bg);
            color: var(--text-color);
            border: 1px solid var(--border-subtle);
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            z-index: 3;
            box-shadow: 0 2px 5px var(--shadow-color);
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease, box-shadow 0.3s ease;
        }
        .theme-toggle-btn:hover {
            background-color: var(--input-bg);
            box-shadow: 0 4px 8px var(--shadow-color);
        }

        .mb-3 {
            margin-bottom: 0.8rem !important;
        }
    </style>
</head>
<body>
    <button id="theme-toggle" class="theme-toggle-btn">
        <i class="fas fa-sun"></i> </button>

    <div class="login-container">
        <h2>Login to SportZone</h2>

        <?php
        if (!empty($errors)) {
            foreach ($errors as $e) {
                echo "<p class='error-message'>" . htmlspecialchars($e) . "</p>";
            }
        }
        if (isset($_GET['success']) && $_GET['success'] === 'registration_success') {
            echo "<p class='success-message'>Registration successful. Please log in.</p>";
        }
        ?>

        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="mb-3 text-start">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                <div class="invalid-feedback">
                    Please enter a valid email.
                </div>
            </div>
            <div class="mb-3 text-start">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                <div class="invalid-feedback">
                    Please enter your password.
                </div>
            </div>
            <button type="submit" class="btn btn-sport w-100">Login</button>
        </form>
        <p class="text-center mt-4">Don't have an account? <a href="register.php">Create an account</a></p>
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