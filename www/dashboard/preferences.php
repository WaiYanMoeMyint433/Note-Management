<?php
require '../vendor/autoload.php';
include "../templates/functions.php";
include "../templates/errorReport.php";
include "../admin/conn.php";
include "./auth_check.php"; // Check if user is logged in

$user_id = $_SESSION['user_id'] ?? null;
// Fetch user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
if (!$user) {
  showAlert("User not found.", "error");
  header("Location: ../index.php");
  exit;
}

$avatar_path =  isset($_SESSION["avatar_path"]) ? $_SESSION["avatar_path"] : "../public/images/avatar.jpg";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Preferences</title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="../css/app.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --card-bg-light: #f1f0f6;
      /* Soft purple-gray for light mode */
      --card-bg-dark: #3b2f5b;
      /* Darker purple-gray for dark mode */
      --text-color-light: #1f2937;
      /* Dark text for light mode */
      --text-color-dark: #e2e8f0;
      /* Light text for dark mode */
      --border-color-light: #d1d5db;
      /* Light border */
      --border-color-dark: #5a4a8a;
      /* Dark border aligned with theme */
    }

    .dark-theme {
      background-color: #1a202c;
      color: var(--text-color-dark);
    }

    .preference-card {
      background-color: var(--card-bg-light);
      border: 1px solid var(--border-color-light);
    }

    .dark-theme .preference-card {
      background-color: var(--card-bg-dark);
      border-color: var(--border-color-dark);
    }

    .preference-title,
    .preference-label {
      color: var(--text-color-light);
    }

    .dark-theme .preference-title,
    .dark-theme .preference-label {
      color: var(--text-color-dark);
    }

    .dark-theme .bg-purple-600:hover {
      background-color: #6B48FF;
    }

    @media (max-width: 640px) {
      .preference-section {
        flex-direction: column;
        gap: 1rem;
      }
    }
  </style>
</head>

<body class="bg-gray-50 text-gray-900 flex flex-col min-h-screen" id="body">
  <!-- Back Button -->


  <!-- Main Content -->
  <div class="main flex-1 p-6">
    <div class="p-6">
      <button onclick="window.location.href='index.php'" class="p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
        <i class="fas fa-arrow-left mr-2"></i> Back to Notes
      </button>
    </div>
    <div class="content max-w-7xl mx-auto">
      <h1 class="text-3xl font-bold text-purple-600 mb-8">User Profile</h1>
      <div class="preference-card p-6 rounded-lg shadow-md mt-4">
        <div class="flex flex-col md:flex-row">
          <div class="w-full md:w-1/3 flex items-center justify-center">
            <img src="<?php echo $avatar_path ?>" class="w-32 h-32 rounded-full" alt="User Avatar">
          </div>
          <div class="w-full md:w-2/3">
            <div class="p-6 bg-purple-600 text-white rounded-r-lg">
              <div class="flex justify-between items-center">
                <h5 class="text-xl font-bold"><?php echo escape($user['name']); ?></h5>
                <a href="./editProfile.php" class="text-white" aria-label="Edit Profile"><i class="fas fa-edit"></i></a>
              </div>
              <p class="text-base mt-2"><strong>Web Developer</strong></p>
              <p class="text-base mt-2">A kiddo who uses Bootstrap and Laravel in web development. Currently playing around with design via Figma.</p>
              <p class="text-base mt-2"><strong>Email:</strong> <?php echo escape($user['email']); ?></p>
              <div class="mt-4">
                <a href="./passwordReset.php" class="bg-white text-purple-600 px-4 py-2 rounded-lg hover:bg-gray-100 transition">Reset Password?</a>
              </div>
              <div class="flex space-x-2 mt-4">
                <a href="#" class="text-white"><i class="fab fa-twitter"></i></a>
                <a href="#" class="text-white"><i class="fab fa-instagram"></i></a>
                <a href="#" class="text-white"><i class="fab fa-linkedin"></i></a>
                <a href="#" class="text-white"><i class="fab fa-github"></i></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="content max-w-7xl mx-auto mt-8">
      <h1 class="text-3xl font-bold text-purple-600 mb-8">User Preferences</h1>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Font Size Preference -->
        <div class="preference-card p-6 rounded-lg shadow-md">
          <h2 class="preference-title text-lg font-semibold mb-4">
            <i class="fas fa-text-height mr-2 text-purple-600"></i> Font Size
          </h2>
          <div class="space-y-2">
            <label class="preference-label block">Select Font Size:</label>
            <select id="fontSize" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-600">
              <option value="text-sm">Small</option>
              <option value="text-base" selected>Medium</option>
              <option value="text-lg">Large</option>
            </select>
          </div>
        </div>
        <!-- Note Color Preference -->
        <div class="preference-card p-6 rounded-lg shadow-md">
          <h2 class="preference-title text-lg font-semibold mb-4">
            <i class="fas fa-palette mr-2 text-purple-600"></i> Note Color
          </h2>
          <div class="space-y-2">
            <label class="preference-label block">Pick Note Color:</label>
            <input type="color" id="noteColor" value="#ffffff" class="w-full h-12 p-1 border border-gray-300 rounded-lg cursor-pointer">
          </div>
        </div>
        <!-- Theme Preference -->
        <div class="preference-card p-6 rounded-lg shadow-md">
          <h2 class="preference-title text-lg font-semibold mb-4">
            <i class="fas fa-moon mr-2 text-purple-600"></i> Theme
          </h2>
          <div class="space-y-2">
            <label class="preference-label block">Toggle Theme:</label>
            <button id="toggleTheme" class="w-full p-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
              <i class="fas fa-moon mr-2"></i> Switch to Dark Mode
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- External JavaScript -->
  <script src="../js/preferences.js"></script>
</body>

</html>