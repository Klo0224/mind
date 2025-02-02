<?php 
session_start();
include 'connect.php';

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@usl\.edu\.ph$/', $email);
}

// Registration Logic
if(isset($_POST['signUp'])){
    $firstName = trim(htmlspecialchars($_POST['firstName']));
    $lastName = trim(htmlspecialchars($_POST['lastName']));
    $email = trim(strtolower($_POST['email']));
    $idnum = trim(htmlspecialchars($_POST['idnum']));
    $course = trim(htmlspecialchars($_POST['course']));
    $year = trim(htmlspecialchars($_POST['year']));
    $dept = trim(htmlspecialchars($_POST['dept']));
    $password = $_POST['password'];
    $hashedPassword = md5($password);

    // Validate email syntax and domain
    if (!isValidEmail($email)) {
        echo "<script type='text/javascript'>alert('Invalid email format! Please use your corporate email address.');</script>";
        exit();
    }

    // Check if the email already exists using prepared statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        echo "<script type='text/javascript'>
            alert('Email Address Already Exists!');
            window.location.href = 'Login.html';
            </script>";
    } else {
        // Use prepared statement for insertion
        $insertStmt = $conn->prepare("INSERT INTO users (Student_id, firstName, lastName, email, password, Course, Year, Department, profile_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'images/blueuser.svg')");
        $insertStmt->bind_param("ssssssss", $idnum, $firstName, $lastName, $email, $hashedPassword, $course, $year, $dept);
        
        if($insertStmt->execute()){
            echo "<script type='text/javascript'>
                alert('Registration successful!');
                window.location.href = 'Login.html';
                </script>";
            exit();
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// Login Logic
if(isset($_POST['signIn'])){
    $email = trim(strtolower($_POST['email']));
    $password = $_POST['password'];
    $hashedPassword = md5($password);

    // Validate email syntax and domain
    if (!isValidEmail($email)) {
        echo "<script type='text/javascript'>alert('Invalid email format! Please use your corporate email address.');</script>";
        exit();
    }

    // Use prepared statement for login
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $hashedPassword);
    $stmt->execute();
    $result = $stmt->get_result();

    if($result->num_rows > 0){
        $user = $result->fetch_assoc();
        
        // Set all necessary session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['firstName'] = $user['firstName'];
        $_SESSION['lastName'] = $user['lastName'];
        $_SESSION['Department'] = $user['Department'];
        $_SESSION['profile_image'] = $user['profile_image'] ?: 'images/blueuser.svg';
        $_SESSION['isLoggedIn'] = true;

        // Log successful login
        error_log("Successful login for user: " . $user['email']);
        
        echo "<script type='text/javascript'>
            window.location.href = 'gracefulThread.php';
            </script>";
        exit();
    } else {
        echo "<script type='text/javascript'>
            alert('Incorrect Email or Password');
            window.location.href = 'Login.html';
            </script>";
    }
}

$conn->close();
?>