<?php
session_start();
include "../templates/errorReport.php";
include "../templates/functions.php";
include "../admin/conn.php";


if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard/index.php");
    exit;
}

$loginError = $_SESSION["error"] ?? "";
unset($_SESSION["error"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF validation failed.');
    }

    $email = htmlspecialchars($_POST["email"]);
    $password = $_POST["password"];

    if (!empty($email) && !empty($password)) {
        $stmt = $conn->prepare("SELECT id, email, password, activation, token FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user["activation"] === 0) {
                $_SESSION['error'] = "Please verify your email first to login.";
                $stmt->close();
                header("Location: login.php");
                exit;
            }

            if (password_verify($password, $user["password"])) {
                $_SESSION["user_id"] = $user["id"];
                $_SESSION["user_email"] = $user["email"];
                header("Location: ../dashboard/index.php");
                exit;
            } else {
                $loginError = "Incorrect password.";
            }
        } else {
            $loginError = "No account found with that email.";
        }
        $stmt->close();
    } else {
        $loginError = "Please fill in all fields.";
    }
}
if (!empty($loginError)) {
    $_SESSION["error"] = $loginError;
}

include "../templates/nav.php";
?>

<div class="container mt-5 mb-5">
    <div class="<?php echo !empty(trim($loginError)) && $loginError !==  "" ? 'show' : 'hidden'; ?>">
        <?php showAlert($loginError, "error") ?>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card shadow rounded-4">
                <div class="card-body p-4">
                    <h3 class="mb-4 text-center">Login</h3>
                    <form action="login.php" method="POST" novalidate>
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <input type="hidden" name="action" value="login">
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary">Log In</button>
                        </div>
                    </form>
                    <div class="text-center mt-3">
                        <small>Don't have an account? <a href="register.php">Register here</a></small>
                    </div>
                    <div class="text-center mt-3">
                        <small>Forgot your password? <a href="passwordResetExternal.php">Reset Here</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include "../templates/footer.php"; ?>