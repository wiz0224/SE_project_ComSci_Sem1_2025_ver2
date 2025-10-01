<?php
include 'connect.php';

$message = '';
$step = 1; // 1 = enter email, 2 = enter new password
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email']) && !isset($_POST['new_password'])) {
        // Step 1: User submitted email
        $email = trim($_POST['email']);
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $step = 2; // Show new password form
        } else {
            $message = "No account found with that email address.";
        }
    } elseif (isset($_POST['email']) && isset($_POST['new_password'])) {
        // Step 2: User submitted new password
        $email = trim($_POST['email']);
        $new_pass = trim($_POST['new_password']);
        $hashed_pass = md5($new_pass);

        $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $update->bind_param("ss", $hashed_pass, $email);
        if ($update->execute()) {
            $message = "Your password has been reset successfully. <a href='index.php'>Login now</a>.";
            $step = 3; // Done
        } else {
            $message = "Failed to reset password. Please try again.";
            $step = 2;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .forgot-container {
            max-width: 400px;
            margin: 60px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow:0 10px 25px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .forgot-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .message {
            margin-bottom: 1rem;
            color: #A0153E;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <h2>Forgot Password</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <!-- Step 1: Enter email -->
        <form method="post" action="forgot_password.php">
            <div class="input-group">
                <i class="fa fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>
                <label for="email">Email</label>
            </div>
            <input type="submit" class="btn" value="Next">
        </form>
        <?php elseif ($step === 2): ?>
        <!-- Step 2: Enter new password -->
        <form method="post" action="forgot_password.php">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <div class="input-group">
                <i class="fa fa-lock"></i>
                <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
                <label for="new_password">New Password</label>
            </div>
            <input type="submit" class="btn" value="Reset Password">
        </form>
        <?php endif; ?>

        <div style="text-align:center; margin-top:1rem;">
            <a href="index.php">Back to Login</a>
        </div>
    </div>
</body>
</html>