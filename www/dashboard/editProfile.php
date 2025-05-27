<?php


include "../templates/functions.php";
include "../admin/conn.php";
include "./auth_check.php"; // Check if user is logged in

$user_id = $_SESSION['user_id'];
$user = showRecords($conn, $user_id);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF validation failed.');
  }
  $username = $_POST['username'] ?? '';
  $email = $_POST['email'] ?? '';
  // Validate inputs
  if (empty($username) || empty($email)) {
    die("Both username and email are required.");
  }

  $data = [$username, $email];
  $success = updateUser($conn, $user_id, $data);


  $img_succes = false;
  //avatar upload
  if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $targetDir = "../uploads/images/";
    $fileName = basename($_FILES["avatar"]["name"]);
    $targetFilePath = $targetDir . uniqid() . "_" . $fileName;
    $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));

    // Allow only image files
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (in_array($fileType, $allowedTypes)) {
      // Move uploaded file to uploads folder
      if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $targetFilePath)) {
        // Save $targetFilePath to database if needed
        $_SESSION["avatar_path"] = $targetFilePath;
        $img_succes = true;
      } else {
        echo "Error uploading file.";
      }
    } else {
      echo "Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.";
    }
  } else {
    $_SESSION["avatar_path"] = "../public/images/avatar.jpg";
    $img_succes = true;
  }

  if ($success &&  $img_succes) {
    $_SESSION['message'] = "Profile updated successfully.";
    header("Location: ./index.php");
    exit();
  } else {
    echo "Error updating profile: " . $conn->error;
  }
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit Profile</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
  </style>
</head>

<body class="min-h-screen flex flex-col items-center justify-start p-6 transition-all" id="body">
  <div class="p-6">
    <button onclick="window.location.href='index.php'" class="p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
      <i class="fas fa-arrow-left mr-2"></i> Back to Notes
    </button>
  </div>
  <button class="theme-toggle btn btn-outline-secondary" onclick="toggleTheme()">
    <i class="fas fa-moon"></i> Toggle Theme
  </button>

  <div class="container max-w-2xl w-full mt-6 profile-card p-5">
    <h2 class="text-center mb-4">Edit Profile</h2>
    <form action="editProfile.php" method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

      <div class="mb-3">
        <label for="avatar" class="form-label">Choose Avatar</label>
        <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*">
        <img id="preview" src="#" alt="Image Preview" style="display:none;">
      </div>

      <div class="mb-3">
        <label for="username" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="username" name="username"
          value="<?php echo escape($user['name']) ?>" required>
        <div class="invalid-feedback">Please enter your name.</div>
      </div>

      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" class="form-control" id="email" name="email"
          value="<?php echo escape($user['email']) ?>" required>
        <div class="invalid-feedback">Please enter a valid email.</div>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary w-100">Update Profile</button>
        <a href="./index.php" class="btn btn-secondary w-100">Cancel</a>
      </div>
    </form>
  </div>

  <script>
    (() => {
      'use strict';
      const forms = document.querySelectorAll('.needs-validation');
      Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
          if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });
    })();

    document.getElementById("avatar").addEventListener("change", function(event) {
      const file = event.target.files[0];
      const preview = document.getElementById("preview");

      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          preview.src = e.target.result;
          preview.style.display = "block";
        };
        reader.readAsDataURL(file);
      } else {
        preview.style.display = "none";
      }
    });

    function toggleTheme() {
      const body = document.getElementById('body');
      body.classList.toggle('dark-theme');
      localStorage.setItem('theme', body.classList.contains('dark-theme') ? 'dark' : 'light');
    }

    document.addEventListener('DOMContentLoaded', () => {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme === 'dark') {
        document.getElementById('body').classList.add('dark-theme');
      }
    });
  </script>
</body>

</html>