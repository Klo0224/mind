<?php
session_start();
include("connect.php");

// Ensure user is logged in
if (!isset($_SESSION['email'])) {
    header("Location: mhp_doc_registration.php");
    exit();
}

$email = $_SESSION['email'];
$query = mysqli_query($conn, "SELECT fname, lname, department FROM mhp WHERE email='$email'");

if (mysqli_num_rows($query) > 0) {
    $mhp = mysqli_fetch_assoc($query);
    $fullname = $mhp['fname'] . ' ' . $mhp['lname'];
    $department = $mhp['department'];
    
    echo "Name: " . $fullname . "<br>";
    echo "Department: " . $department;
} else {
    echo "User not found.";
}
?>