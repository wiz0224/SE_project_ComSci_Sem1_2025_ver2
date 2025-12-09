<?php
session_start();
// Gumamit ng 'conn.php' na ginagamit mo sa ibang files
include 'conn.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signIn'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    // Check if the user exists
    // Idinagdag natin ang 'id' at 'position' sa SELECT statement
    $sql = "SELECT id, firstname, lastname, email, password FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        
        // Gamit ang MD5 hashing, tulad ng nakita sa ibang scripts
        if ($row['password'] === md5($password)) { 
            
            // ===================================================
            // CRITICAL: I-UPDATE ANG LAST LOGIN TIME DITO
            // ===================================================
            $user_id = $row['id'];
            
            $update_login = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_login->bind_param("i", $user_id);
            $update_login->execute();
            $update_login->close();
            // ===================================================

            // Set session variables
            $_SESSION['id'] = $row['id'];
            $_SESSION['firstName'] = $row['firstname'];
            $_SESSION['lastName'] = $row['lastname'];
            $_SESSION['email'] = $row['email'];
            // I-assume natin na ADMIN ang lahat ng users sa 'users' table 
            // kung wala kayong 'position' column
            $_SESSION['position'] = 'Admin'; 

            // Success! Redirect to dashboard
            header("Location: dashboard.php");
            exit();

        } else {
            // Password incorrect
            header("Location: index.php?error=1");
            exit();
        }
    } else {
        // Email not found
        header("Location: index.php?error=1");
        exit();
    }
} else {
    // Direct access or form not submitted correctly
    header("Location: index.php");
    exit();
}
?>