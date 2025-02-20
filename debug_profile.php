<?php
session_start();
include("connect.php");

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    
    // Fetch the current profile image path from the database
    $query = $conn->prepare("SELECT profile_image FROM users WHERE email = ?");
    $query->bind_param("s", $email);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        echo "<h2>Database Information</h2>";
        echo "Profile image path in database: " . htmlspecialchars($userData['profile_image']) . "<br>";
        
        // Check if the file exists
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . $userData['profile_image'])) {
            echo "File exists on server at full path.<br>";
        } else {
            echo "File does NOT exist at full path: " . htmlspecialchars($_SERVER['DOCUMENT_ROOT'] . '/' . $userData['profile_image']) . "<br>";
        }
        
        if (file_exists($userData['profile_image'])) {
            echo "File exists on server at relative path.<br>";
        } else {
            echo "File does NOT exist at relative path.<br>";
        }
    } else {
        echo "No user found with email: " . htmlspecialchars($email);
    }
    
    echo "<h2>Session Information</h2>";
    echo "Session profile image path: " . (isset($_SESSION['profile_image']) ? htmlspecialchars($_SESSION['profile_image']) : "Not set") . "<br>";
    
} else {
    echo "User not logged in (no email in session)";
}
?>