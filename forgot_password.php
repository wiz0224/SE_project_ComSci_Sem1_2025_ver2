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
            $step = 3; // Finished step
        } else {
            $message = "Error resetting password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">

    <style>
        /* CSS Variables for Light/Dark Mode (Consistent) */
        :root {
            --background-primary: #ffffff;
            --background-secondary: #f8f9fa;
            --text-color-primary: #212529;
            --text-color-secondary: #6c757d;
            --primary-color: #007bff;
            --form-bg: #ffffff;
            --form-border: #dee2e6;
            --input-bg: #ffffff;
            --input-border: #ced4da;
            --placeholder-color: #6c757d;
            --btn-secondary-bg: #6c757d;
            --btn-secondary-color: #ffffff;
            /* FONT CONSISTENCY */
            font-family: 'Roboto', sans-serif;
        }

        .dark-mode {
            --background-primary: #121212;
            --background-secondary: #1e1e1e;
            --text-color-primary: #e0e0e0;
            --text-color-secondary: #b0b0b0;
            --primary-color: #64b5f6;
            --form-bg: #2d2d2d;
            --form-border: #3a3a3a;
            --input-bg: #1e1e1e;
            --input-border: #444444;
            --placeholder-color: #b0b0b0;
            --btn-secondary-bg: #495057;
            --btn-secondary-color: #ffffff;
        }

        body {
            font-family: 'Roboto', sans-serif; 
            background-color: var(--background-secondary);
            color: var(--text-color-primary);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            transition: background-color 0.3s, color 0.3s;
        }

        .forgot-container {
            background: var(--form-bg);
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            border: 1px solid var(--form-border);
            transition: background 0.3s, box-shadow 0.3s, border-color 0.3s;
        }
        .dark-mode .forgot-container {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        }
        
        /* Style for simple Back link/button */
        .back-link {
            background-color: var(--btn-secondary-bg);
            color: var(--btn-secondary-color);
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
            transition: background-color 0.2s;
            margin-bottom: 20px; /* Added margin for spacing */
        }
        .back-link:hover {
            background-color: #5a6268; 
            color: #fff;
            text-decoration: none;
        }
        .dark-mode .back-link:hover {
            background-color: #555c63; 
        }
        .back-link i {
            margin-right: 5px;
            font-size: 1.2em;
        }

        .forgot-container h2 {
            margin-bottom: 25px;
            color: var(--primary-color);
            text-align: center;
        }

        .input-group {
            position: relative;
            margin-bottom: 25px;
        }
        
        .input-group input {
            width: 100%;
            padding: 10px 10px 10px 40px; 
            border: 1px solid var(--input-border);
            border-radius: 5px;
            background-color: var(--input-bg);
            color: var(--text-color-primary);
            transition: border-color 0.3s, background-color 0.3s;
        }

        .input-group input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .input-group label {
            position: absolute;
            top: 0; 
            left: 40px; 
            font-size: 10px; 
            color: var(--placeholder-color);
            pointer-events: none;
            transition: 0.3s ease all;
            background-color: var(--form-bg); 
            padding: 0 5px;
        }

        .input-group input:focus ~ label,
        .input-group input:not(:placeholder-shown) ~ label {
            color: var(--primary-color);
        }
        
        .input-group input::placeholder {
            color: var(--placeholder-color); 
        }
        
        .input-group i {
            position: absolute;
            top: 50%;
            left: 10px;
            transform: translateY(-50%);
            color: var(--placeholder-color);
        }
        
        .btn {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #0056b3;
        }
        .dark-mode .btn:hover {
            background-color: #4da6ff;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            background-color: #e2f0e4;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .dark-mode .message {
            background-color: #1d3323;
            color: #8ce197;
            border: 1px solid #497a51;
        }

        .message a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .message a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <a href="index.php" class="back-link">
             <i class='bx bx-arrow-back'></i> Back
        </a>
        
        <h2>Forgot Password</h2>
        <?php if ($message): ?>
            <div class="message"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
        <form method="post" action="forgot_password.php">
            <div class="input-group">
                <i class="fa fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <label for="email">Email</label>
            </div>
            <input type="submit" class="btn" value="Next">
        </form>
        <?php elseif ($step === 2): ?>
        <form method="post" action="forgot_password.php">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            <div class="input-group">
                <i class="fa fa-lock"></i>
                <input type="password" name="new_password" id="new_password" placeholder="New Password" required>
                <label for="new_password">New Password</label>
            </div>
            <input type="submit" class="btn" value="Reset Password">
        </form>
        <?php endif; ?>
    </div>

    <script>
        // DARK MODE ACTIVATION LOGIC (CONSISTENT)
        if (localStorage.getItem('darkMode') === 'true') {
            document.body.classList.add('dark-mode');
        }
    </script>
</body>
</html>