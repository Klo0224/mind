<?php
session_start();
include("connect.php");

// Check if the user is logged in
if (isset($_SESSION['mhp_name']) && isset($_SESSION['is_logged_in']) && isset($_SESSION['mhp_id'])) {
    $mhp_id = $_SESSION['mhp_id'];

    // Prepare and execute query securely
    $stmt = $conn->prepare("SELECT fname, lname, department FROM mhp WHERE id = ?");
    $stmt->bind_param("i", $mhp_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $fullName = $user['fname'] . ' ' . $user['lname'];
        $department = $user['department'];
    } else {
        $fullName = "User not found";
        $department = "Department not found";
    }

    $stmt->close();
} else {
    // Redirect to login page if session variables are not set
    header("Location: login.php");
    exit;
}
?>
