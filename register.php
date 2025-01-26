<?php 
include 'connect.php';

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) && preg_match('/@usl\.edu\.ph$/', $email);
}

if(isset($_POST['signUp'])){
    $firstName = $_POST['firstName'];
    $lastName = $_POST['lastName'];
    $email = $_POST['email'];
    $idnum = $_POST['idnum'];  
    $course = $_POST['course']; 
    $year = $_POST['year'];  
    $dept = $_POST['dept'];  
    $password = $_POST['password'];
    $hashedPassword = md5($password);

    // Validate email syntax and domain
    if (!isValidEmail($email)) {
        echo "<script type='text/javascript'>alert('Invalid email format! Please use your corporate email address.');</script>";
        exit();
    }

    // Check if the email already exists
    $checkEmail = "SELECT * FROM users WHERE email='$email'";
    $result = $conn->query($checkEmail);

    if($result->num_rows > 0){
        echo "<script type='text/javascript'>
            alert('Email Address Already Exists!');
            window.location.href = 'Login.html';
            </script>";
    } else {
        $insertQuery = "INSERT INTO users (Student_id, firstName, lastName, email, password, Course, Year, Department)
                        VALUES ('$idnum', '$firstName', '$lastName', '$email', '$hashedPassword', '$course', '$year', '$dept')";
        
        if($conn->query($insertQuery) === TRUE){
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
    $email = $_POST['email'];
    $password = $_POST['password'];
    $hashedPassword = md5($password);

    // Validate email syntax and domain
    if (!isValidEmail($email)) {
        echo "<script type='text/javascript'>alert('Invalid email format! Please use your corporate email address.');</script>";
        exit();
    }

    $sql = "SELECT * FROM users WHERE email='$email' AND password='$hashedPassword'";
    $result = $conn->query($sql);

    if($result->num_rows > 0){
        session_start();
        $row = $result->fetch_assoc();
        $_SESSION['email'] = $row['email'];
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
?>
