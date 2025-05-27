<?php
include "../templates/errorReport.php";
include "../admin/conn.php";
include "../templates/functions.php";
include "./auth_check.php"; // Check if user is logged in

$user_id = $_SESSION['user_id'];
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF validation failed.');
  }
  $password = $_POST["password"];
  if (empty($password)) {
    $error_message = "Please fill your old password.";
  } else {
    // Prepare statement to avoid SQL injection
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
      $user = $result->fetch_assoc();
      if (password_verify($password, $user["password"])) {
        $_SESSION['password_confirmed'] = true;
        header("Location: changePassword.php"); // Redirect to a protected page
        exit;
      } else {
        $error_message = "Incorrect password";
      }
    }
    $stmt->close();
  }
}


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
            <h3 class="mb-4 text-center">Please Enter Your password to Continue.</h3>
            <form action="passwordReset.php" method="POST" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <?php if (!empty($error_message)): ?>
                  <div class="text-danger mt-1" style="font-size: 0.875em;">
                    <?php echo htmlspecialchars($error_message); ?>
                  </div>
                <?php endif; ?>
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
    document.addEventListener('DOMContentLoaded', () => {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark') {
        document.getElementById('body').classList.add('dark-theme');
      }
    });
  </script>