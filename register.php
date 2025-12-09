<?php
// CRITICAL FIX: I-enable ang Output Buffering para saluhin ang anumang output 
// bago ang header() redirect, na siyang sanhi ng White Page.
ob_start();

// Tiyakin na session_start() ay nasa connect.php lang at WALA DITO.
include 'connect.php'; 

if(isset($_POST['signIn'])){
    // ... (Sign In Logic)
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    $hashed_password = md5($password);

    $stmt = $conn->prepare("SELECT id, firstName, lastName, email, password FROM users WHERE email=? AND password=?");
    $stmt->bind_param("ss", $email, $hashed_password);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $row = $result->fetch_assoc();
        
        // Update last login time
        $user_id = $row['id']; 
        $update_login = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $update_login->bind_param("i", $user_id);
        $update_login->execute();
        $update_login->close();

        // Set Session Variables
        $_SESSION['id'] = $row['id'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['firstName'] = $row['firstName'];
        $_SESSION['lastName'] = $row['lastName'];
        $_SESSION['show_popup'] = true;
        
        // Clear buffer at redirect
        ob_end_clean(); 
        header("Location: dashboard.php");
        exit();
    }
    else{
        // Clear buffer at redirect
        ob_end_clean(); 
        header("Location: index.php?error=1");
        exit();
    }
}

if(isset($_POST['signUp'])){
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';

    if($password != $confirm_password){
        // Clear buffer at redirect
        ob_end_clean();
        header("Location: index.php?error=2&showSignUp=1");
        exit();
    }

    $hashed_password = md5($password);

    $stmt = $conn->prepare("INSERT INTO users (firstName, lastName, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $firstName, $lastName, $email, $hashed_password);

    if ($stmt->execute()) {
        // SUCCESS: Dito nagaganap ang redirect
        // Clear buffer at redirect
        ob_end_clean();
        header("Location: index.php?registered=1&email=" . urlencode($email));
        exit();
    } else {
        error_log("Sign Up Database Error: " . $stmt->error); 
        // Clear buffer at redirect
        ob_end_clean();
        header("Location: index.php?error=3&showSignUp=1");
        exit();
    }
}
?>