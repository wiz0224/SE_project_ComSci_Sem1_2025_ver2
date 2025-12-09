<?php
include 'connect.php';

if(isset($_POST['signIn'])){
    $email=$_POST['email'];
    $password=$_POST['password'];
    $password=md5($password);

    $sql="SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result=$conn->query($sql);
    if($result->num_rows>0){
        $row=$result->fetch_assoc();
        $_SESSION['email'] = $row['email'];
        $_SESSION['firstName'] = $row['firstName'];
        $_SESSION['lastName'] = $row['lastName'];
        $_SESSION['show_popup'] = true;
        header("Location: dashboard.php");
        exit();
    }
    else{
        header("Location: index.php?error=1");
        exit();
    }
}

if(isset($_POST['signup'])){
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if($password != $confirm_password){
        header("Location: index.php?error=2");
        exit();
    }

    $password = md5($password);

    $sql = "INSERT INTO users (firstName, lastName, email, password) VALUES ('$firstName', '$lastName', '$email', '$password')";

    if ($conn->query($sql) === TRUE) {
        header("Location: index.php?registered=1");
        exit();
    } else {
        header("Location: index.php?error=3");
        exit();
    }
}
?>