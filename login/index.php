<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration & Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php
$showSignIn = true;
if (isset($_GET['registered'])) {
    $showSignIn = true;
} elseif (isset($_GET['showSignUp'])) {
    $showSignIn = false;
}
?>
     <div class="container" id="signUp" style="display: <?php echo $showSignIn ? 'none' : 'block'; ?>;">
        <h1 class="form-title">Register</h1>
        <form method="post" action="register.php">
            <div class="input-group">
                <i class="fa fa-user"></i>
                <input type="text" name="firstName" id="fName" placeholder="First Name" required>
                <label for="fName">First Name</label>
            </div>
            <div class="input-group">
                <i class="fa fa-user"></i>
                <input type="text" name="lastName" id="lName" placeholder="Last Name" required>
                <label for="lName">Last Name</label>
            </div>
            <div class="input-group">
                <i class="fa fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <label for="email">Email</label>
            </div>
            <div class="input-group">
                <i class="fa fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <div class="input-group">
                <i class="fa fa-lock"></i>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                <label for="confirm_password">Confirm Password</label>
            </div>
            <input type="submit" class="btn" value="Sign Up" name="signup">
        </form>
         <div class="admin-info">
            <p><strong>Note:</strong> This system is for authorized government education administrators only.</p>
         </div>
         <div class="links">
            <p>Already Have Account?</p>
            <button id="signInButton">Sign In</button>
         </div>
    </div>

    <div class="container" id="signIn" style="display: <?php echo $showSignIn ? 'block' : 'none'; ?>;">  
        <h1 class="form-title">Admin Sign In</h1>
        <?php if (isset($_GET['error']) && $_GET['error'] == 1): ?>
        <div id="error-message" style="color: red; margin-bottom: 10px;justify-content: center; text-align: center;">
            Invalid email or password!
        </div>
        <?php endif; ?>
        <form method="post" action="register.php">
           
            <div class="input-group">
                <i class="fa fa-envelope"></i>
                <input type="email" name="email" id="email" placeholder="Email" required>
                <label for="email">Email</label>
            </div>
            <div class="input-group">
                <i class="fa fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <label for="password">Password</label>
            </div>
            <p class="recover">
                <a href="forgot_password.php">Forgot Password?</a>
            </p>
            <input type="submit" class="btn" value="Sign In" name="signIn">
        </form>
         <div class="admin-info">
            <p><strong>For Admin Use Only:</strong> Please login with your government-issued credentials.</p>
         </div>
         <div class="links">
            <p>Don't have an account yet?</p>
            <button id="signUpButton">Sign Up</button>
         </div>
    </div>
   <script src="script.js"></script>
   <script>
if (window.location.search.includes('error=1')) {
    window.history.replaceState({}, document.title, window.location.pathname);
}
</script>
   
</body>
</html>