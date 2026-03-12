<?php
session_start();
require_once '../config/config.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = htmlspecialchars(trim($_POST["name"] ?? ''));
    $email = htmlspecialchars(trim($_POST["email"] ?? ''));
    $password = $_POST["password"] ?? '';
    $confirm_password = $_POST["confirm"] ?? '';

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    } else {
        $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "This email is already registered.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = "client";

            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO utilisateurs (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

            if ($stmt->execute()) {
                $_SESSION['user_id'] = $conn->insert_id;
                $_SESSION['name'] = $name;
                $_SESSION['role'] = $role;

                header("Location: login.php?success=registration_success");
                exit;
            } else {
                $errors[] = "Error during registration. Please try again: " . $stmt->error;
            }
        }
        if (isset($stmt) && $stmt instanceof mysqli_stmt) {
           $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SportZone</title>
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
            background-image: url('https://source.unsplash.com/random/1600x900/?fitness,running,weights');
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

        .register-container {
            background-color: var(--card-bg);
            padding: 1.5rem 2rem;
            border-radius: 15px;
            box-shadow: 0 0 20px var(--shadow-color);
            max-width: 450px;
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
            margin-bottom: 1rem;
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
            margin-top: 1rem;
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
            font-size: 0.85rem;
            margin-top: 0.4rem;
            padding: 0.4rem 0.8rem;
            background-color: rgba(220, 53, 69, 0.2);
            border-radius: 5px;
            border: 1px solid var(--error-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        .success-message {
            color: var(--success-color);
            font-size: 0.85rem;
            margin-top: 0.4rem;
            padding: 0.4rem 0.8rem;
            background-color: rgba(40, 167, 69, 0.2);
            border-radius: 5px;
            border: 1px solid var(--success-color);
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        p.text-center {
            color: var(--text-color);
            margin-top: 1rem;
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

    <div class="register-container">
        <h2>Create Your SportZone Account</h2>

        <?php
        if (!empty($errors)) {
            foreach ($errors as $e) {
                echo "<p class='error-message'>" . htmlspecialchars($e) . "</p>";
            }
        }
        ?>

        <form method="POST" action="" class="needs-validation" novalidate>
            <div class="mb-3 text-start">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter your full name" value="<?= isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '' ?>" required>
                <div class="invalid-feedback">
                    Please enter your full name.
                </div>
            </div>
            <div class="mb-3 text-start">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>" required>
                <div class="invalid-feedback">
                    Please enter a valid email address.
                </div>
            </div>
            <div class="mb-3 text-start">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required minlength="6">
                <div class="invalid-feedback">
                    Password must be at least 6 characters long.
                </div>
            </div>
            <div class="mb-3 text-start">
                <label for="confirm" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm" name="confirm" placeholder="Confirm your password" required>
                <div class="invalid-feedback">
                    Please confirm your password.
                </div>
            </div>
            <button type="submit" class="btn btn-sport w-100">Register</button>
        </form>
        <p class="text-center mt-4">Already have an account? <a href="login.php">Login here</a></p>
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
                        const password = form.querySelector('#password');
                        const confirmPassword = form.querySelector('#confirm');

                        if (password && confirmPassword) {
                            if (password.value !== confirmPassword.value) {
                                confirmPassword.setCustomValidity("Passwords do not match.");
                            } else {
                                confirmPassword.setCustomValidity("");
                            }
                        }

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