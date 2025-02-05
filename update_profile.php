<?php
session_start();
require 'connect.php'; // Include the database connection

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['firstName']);
    $lastName = trim($_POST['lastName']);
    $course = trim($_POST['course']);
    $year = trim($_POST['year']);
    $department = trim($_POST['department']);

    if (!empty($firstName) && !empty($lastName) && !empty($course) && !empty($year) && !empty($department)) {
        $stmt = $conn->prepare("UPDATE users SET firstName = ?, lastName = ?, Course = ?, Year = ?, Department = ? WHERE id = ?");
        $stmt->bind_param('sssisi', $firstName, $lastName, $course, $year, $department, $userId);

        if ($stmt->execute()) {
            echo 'success';
        } else {
            echo 'Error updating profile: ' . $stmt->error;
        }

        $stmt->close();
    } else {
        echo 'All fields are required.';
    }
} else {
    echo 'Invalid request method.';
}

$conn->close();
?>
