<?php 

session_start();
if (isset($_SESSION['user_id']) ) {
    header("Location: ../dashboard/index.php");
    exit;
}
include "../templates/nav.php"; 
include "../templates/functions.php"; 
include "../admin/conn.php";// Your DB connection
include "../templates/errorReport.php";

require '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



// Check if already logged in


if (!isset($_SESSION['from_register']) || $_SESSION['from_register'] !== true) {
  // Optional: destroy session or log unauthorized access
  http_response_code(403); // Forbidden
  exit('Access denied.');
}


$email = $_SESSION["email"];
$token = $_SESSION["token"];
$name = $_SESSION["username"];

$mail = new PHPMailer(true); // <--- This line is required
$confirmLink= "http://localhost:8080/auth/verifySuccessful.php?token=" . $token;
if (!isset($_SESSION['email_sent']) || $_SESSION['email_sent'] !== true) 
try {
   
   
    $mail->isSMTP();
    $mail->Host = 'mailhog'; // MailHog SMTP host
    $mail->Port = 1025;        // MailHog SMTP port
    $mail->SMTPAuth = false;   // No authentication for MailHog
    

    $toEmail = $email;
    $username = $name;
    $link = $confirmLink;

    // Sender and recipient
    $mail->setFrom('no-reply@example.com', 'Your App');
    $mail->addAddress($toEmail);
 
    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'Welcome to Our Platform!';
    $mail->Body    = "<h1>Welcome, $username!</h1>
    <p>Thank you for registering. We're excited to have you!</p> 
    <p>Click here to confirm your email. <a href=$confirmLink>Click Here</a> </p>";
    $mail->AltBody = "Welcome, $username! Thank you for registering.";
   
    // Send email
    $mail->send();
  
   
} catch (Exception $e) {
    echo "Email could not be sent. Error: {$mail->ErrorInfo}";
    
}
unset($_SESSION['from_register']);
?>
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
          <h3 class="mb-3 fw-medium">We have sent verification to</h3>
          <h3 class="mb-3 fw-bold text-muted-dark"><?php echo $email; ?>!</h3>
            <h4 class="fw-medium">Please confirm it.</h4>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php include "../templates/footer.php"; ?>