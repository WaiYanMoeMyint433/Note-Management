<?php 
session_start(); // Start session
include "../templates/errorReport.php";
include "../templates/nav.php";
include "../templates/functions.php";
include "../admin/conn.php";
if (isset($_SESSION["user_id"])) {
  header("Location: ../dashboard/index.php");
  exit;
}
$send = false;
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF validation failed.');
}
    $email = $_POST["email"];
    $user_id = getIdbyEmail($conn,$email);
    $status = checkEmail($conn,$email); 
    if(!$status){
      showAlert("Email is not registered" , $type = 'error');
    }else{
      $token = bin2hex(random_bytes(32)); 
      $confirmLink = $domain . "passwordResetSuccess.php?token=" . $token;
      $content = (object) [
        'Subject' => "Password Reset Request",
        'Body' => "<h1>Click Link to Change new password!</h1> 
                   <p>Here is your link, <a href='" . htmlspecialchars($confirmLink, ENT_QUOTES) . "'>Click Here</a> </p>", 
        'AltBody' => "Thank you"
      ]; 
      $expires_at = date("Y-m-d H:i:s", strtotime("+1 hour")); // Token valid for 1 hour

      //delete old duplicates
      // Clear any old request for this email
      $deleteStmt = $conn->prepare("DELETE FROM password_reset WHERE user_id = ?");
      $deleteStmt->bind_param("i", $user_id);
      $deleteStmt->execute();
      $deleteStmt->close();

      // Now insert new one
      $stmt = $conn->prepare("INSERT INTO password_reset (user_id, token, expire_at) VALUES (?, ?, ?)");
      $stmt->bind_param("iss", $user_id, $token, $expires_at);

      if ($stmt->execute()) {
          $send = true;
      } else {
          // Duplicate entry error code: 1062
          if ($conn->errno === 1062) {
              showAlert("We have already sent a password reset link to this email.", "warning");
          } else {
              showAlert("Something went wrong. Please try again later.", "error");
          }
      }

      $stmt->close();
      $conn->close();
      
      $status = sendMailforPasswordReset($email,$confirmLink,$content);
      if($status){
        showAlert("successfully sent password reset link", $type = 'success');
      }else{
        showAlert("password reset link failed to send", $type = 'error');
      }
    }

} 
?>
<div class="container mt-5">
<?php
if($send === false){
?>

    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow rounded-4">
          <div class="card-body p-4">
            <h3 class="mb-4 text-center">Please Enter Your Email to Receive the link.</h3>
            <form action="passwordResetExternal.php" method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
              <div class="mb-3">
                <label for="email" class="form-label">email</label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>
              <div class="d-grid mt-4">
                <button type="submit" class="btn btn-primary">Get Reset Link</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  
<?php }else { ?>
<style>
    .card {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: none;
    }
    .card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
    }
    .text-muted-dark {
      color: #0d6efd;
    }
  </style>
  <div class="container mt-5">
    <div class="row justify-content-center">
      <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg rounded-4">
          <div class="card-body p-4 p-md-5 text-center">
          <h3 class="mb-3 fw-medium">Password Reset Link has been sent to</h3>
          <h3 class="mb-3 fw-bold text-muted-dark"><?php echo $email; ?>!</h3>
            <h4 class="fw-medium">Please check it.</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php } ?>
</div>
<?php include "../templates/footer.php";?>