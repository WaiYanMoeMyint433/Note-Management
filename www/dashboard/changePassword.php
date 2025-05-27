<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "../admin/conn.php";
include "../templates/functions.php";
include "./auth_check.php"; // Check if user is logged in


if (!isset($_SESSION['password_confirmed']) || $_SESSION['password_confirmed'] !== true) {
    // Redirect back to password confirmation
    header("Location: passwordReset.php");
    exit();
}

// Initialize error message
$error_message = "";
$success_message = "";

$id = $_SESSION['user_id'];
// PHP validation on form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF validation failed.');
    }
    $password = $_POST["password"] ?? "";
    $password1 = $_POST["password1"] ?? "";

    // Server-side validation
    if (empty($password) || empty($password1)) {
        $error_message = "Both password fields are required.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/', $password)) {
        $error_message = "Password must include at least one number and one special character.";
    } elseif ($password !== $password1) {
        $error_message = "Passwords do not match.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Prepare the SQL statement to update the password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stmt === false) {
            $error_message = "Failed to prepare the statement: " . $conn->error;
        } else {
            // Bind the hashed password and user_id
            $stmt->bind_param("si", $hashed_password, $id);

            // Execute the statement
            if ($stmt->execute()) {
                $success_message = "Password updated successfully!";
                // Optionally, unset the session variable to prevent reuse
                unset($_SESSION['password_confirmed']);
                header("Location: ./index.php");
                exit;
            } else {
                $error_message = "Error updating password: " . $stmt->error;
            }

            // Close the statement
            $stmt->close();
        }
    }
}
$conn->close(); // Close the database connection
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Note Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../public/nav.css">
</head>
<style>
    :root {
        --bg-light: #f9fafb;
        --bg-dark: #1e1e2f;
        --card-light: #ffffff;
        --card-dark: #2d2d45;
        --text-light: #1f2937;
        --text-dark: #e5e7eb;
    }

    body.dark-theme {
        background-color: var(--bg-dark);
        color: var(--text-dark);
    }

    .profile-card {
        background-color: var(--card-light);
        color: var(--text-light);
        border-radius: 1rem;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.08);
        transition: 0.3s;
    }

    body.dark-theme .profile-card {
        background-color: var(--card-dark);
        color: var(--text-dark);
    }

    .form-label {
        font-weight: 500;
    }

    .theme-toggle {
        position: fixed;
        top: 1rem;
        right: 1rem;
        cursor: pointer;
        z-index: 10;
    }

    #preview {
        border-radius: 8px;
        margin-top: 10px;
        max-height: 150px;
    }

    body.dark-theme .bg-purple-600 {
        background-color: #333 !important;
        color: #fff !important;
        border: none;
    }

    body.dark-theme .bg-purple-600:hover {
        background-color: #444 !important;
    }
</style>

<body id="body">

    <!-- Navigation -->


    <div class="container mt-5">
        <div class="row justify-content-center mb-4">
            <button onclick="window.location.href='index.php'" class="p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
                <i class="fas fa-arrow-left mr-2"></i> Back to Notes
            </button>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow rounded-4">
                    <div class="card-body p-4">
                        <h3 class="mb-4 text-center">Change Password</h3>
                        <?php
                        if (!empty($error_message)) {
                            showAlert($error_message, 'error');
                        }
                        if (!empty($success_message)) {
                            showAlert($success_message, 'success');
                        }
                        ?>
                        <form action="changePassword.php" method="POST" novalidate onsubmit="return validateForm()">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="mb-3">
                                <p>Please Enter Your New Password</p>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div id="passwordError" class="text-danger mt-1" style="font-size: 0.875em;"></div>
                            </div>
                            <div class="mb-3">
                                <p>Please Enter Your New Password Again</p>
                                <input type="password" class="form-control" id="password1" name="password1" required>
                                <div id="password1Error" class="text-danger mt-1" style="font-size: 0.875em;"></div>
                            </div>
                            <div class="d-grid mt-4">
                                <button type="submit" class="btn btn-primary">Enter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function validateForm() {
            // Get the password fields
            const password = document.getElementById("password").value;
            const password1 = document.getElementById("password1").value;

            // Get the error message elements
            const passwordError = document.getElementById("passwordError");
            const password1Error = document.getElementById("password1Error");

            // Reset error messages
            passwordError.textContent = "";
            password1Error.textContent = "";

            let isValid = true;

            // Check if passwords are empty
            if (!password) {
                passwordError.textContent = "Password is required.";
                isValid = false;
            }
            if (!password1) {
                password1Error.textContent = "Please confirm your password.";
                isValid = false;
            }

            // Check password length
            if (password.length < 8) {
                passwordError.textContent = "Password must be at least 8 characters long.";
                isValid = false;
            }

            // Check for number and special character
            if (!/^(?=.*[0-9])(?=.*[!@#$%^&*(),.?":{}|<>]).{8,}$/.test(password)) {
                passwordError.textContent = "Password must include at least one number and one special character.";
                isValid = false;
            }

            // Check if passwords match
            if (password !== password1) {
                password1Error.textContent = "Passwords do not match.";
                isValid = false;
            }

            return isValid;
        }
        <?php if (!empty($success_message)): ?>
            setTimeout(function() {
                window.location.href = 'dashboard.php';
            }, 5000); // Redirect after 5 seconds to match showAlert
        <?php endif; ?>
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark') {
                document.getElementById('body').classList.add('dark-theme');
            }
        });
    </script>
</body>