<?php
session_start(); // Start session
include "../templates/functions.php";
include "../templates/errorReport.php";
include "../admin/conn.php";
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// Check if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['insession'])) {
    header("Location: ../dashboard/index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF validation failed.');
    }

    $token = bin2hex(random_bytes(16));
    $username = htmlspecialchars($_POST["username"]);
    $email = htmlspecialchars($_POST["email"]);
    $password = $_POST["password"];
    $password1 = $_POST["password1"];

    $_SESSION['email'] = $email;
    $_SESSION['username'] = $username;
    $_SESSION['token'] = $token;

    if($password !== $password1) {
        $_SESSION['registration_errors'] = ['Passwords do not match.'];
        header("Location: register.php");
        exit();
    }
    // --- Validation ---
    validateUserInput($username, $email, $password, $conn);
    // --- Secure Password Hashing ---
    $last_id = createUser($conn, $username, $email, $password, $token);

    // Execute the statement
    if ($last_id > 0) {
        
        $mail = new PHPMailer(true); // <--- This line is required
        $confirmLink = "http://localhost:8080/auth/verifySuccessful.php?token=" . $token;
        try {


            $mail->isSMTP();
            $mail->Host = 'mailhog'; // MailHog SMTP host
            $mail->Port = 1025;        // MailHog SMTP port
            $mail->SMTPAuth = false;   // No authentication for MailHog


            $toEmail = $email;
            $username = $_POST['name'] ?? '';
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
        $_SESSION['user_id'] = $last_id;
        $_SESSION['from_register'] = true;
        $_SESSION['email_sent'] = true; // Mark that we've sent the email
        header("Location: verify.php");
        exit();
    } else {
        error_log("Error executing statement: " . $stmt->error);
        $_SESSION['registration_errors'] = ['An error occurred during registration. Please try again later.'];
        header("Location: register.php");
        exit();
    }
    mysqli_close($conn); // Close the connection when done


}

include "../templates/nav.php";
?>

<div class="container mt-5" style="max-width: 600px; margin: auto; margin-bottom: 150px;">
    <h2>Register Form</h2>
    <?php if (isset($_SESSION['registration_errors'])): ?>
        <?php
        $colors = [
            'error' => '#f44336',
            'warning' => '#ff9800',
            'success' => '#4CAF50',
            'info' => '#2196F3',
        ];
        $type = 'error';
        $color = $colors[$type] ?? '#f44336';
        foreach ($_SESSION['registration_errors'] as $index => $error):
            $alertId = 'alertBox_' . $index; // Unique ID for each alert
            $topPosition = 5 + ($index * 4); // Stack alerts vertically
        ?>
            <div id="<?php echo $alertId; ?>" style="
                position: fixed;
                top: <?php echo $topPosition; ?>em;
                right: 20px;
                min-width: 20em;
                max-width: 50rem;
                padding: 15px 20px;
                background-color: <?php echo $color; ?>;
                color: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                font-family: Arial, sans-serif;
                z-index: 9999;
                opacity: 1;
                transition: opacity 0.5s ease-out;
            ">
                <?php echo htmlspecialchars($error); ?>
            </div>

            <script>
                setTimeout(function() {
                    var alertBox = document.getElementById("<?php echo $alertId; ?>");
                    if (alertBox) {
                        alertBox.style.opacity = "0";
                        setTimeout(function() {
                            alertBox.remove();
                        }, 500);
                    }
                }, 5000); // Hide after 5 seconds
            </script>
        <?php endforeach; ?>
        <?php unset($_SESSION['registration_errors']); ?>
    <?php endif; ?>
    <form action="./register.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

        <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" aria-describedby="username" value="<?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : ''; ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input type="email" class="form-control" id="email" name="email" aria-describedby="emailHelp" value="<?php echo isset($_SESSION['email']) ? htmlspecialchars($_SESSION['email']) : ''; ?>" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>
        <div class="mb-3">
            <label for="password1" class="form-label">Enter Password Again</label>
            <input type="password" class="form-control" id="password1" name="password1" required>
            <div id="passwordHelp" class="form-text">Minimum 8 characters, including a number and special character.</div>
        </div>
        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</div>

<?php include "../templates/footer.php"; ?>