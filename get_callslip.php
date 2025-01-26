<?php
$conn = new mysqli("localhost", "root", "", "_Mindsoothe");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $unit = $_POST['unit'];
    $studentName = $_POST['studentName'];
    $courseYear = $_POST['courseYear'];
    $appointmentTime = $_POST['appointmentTime'];
    $counselorName = $_POST['counselorName'];

    $stmt = $conn->prepare("INSERT INTO call_slips (student_id, unit, student_name, course_year, appointment_time, counselor_name) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $student_id, $unit, $studentName, $courseYear, $appointmentTime, $counselorName);

    if ($stmt->execute()) {
        echo "<script>alert('Call slip saved successfully!'); window.location.href='mhpdashboard.php';</script>";
    } else {
        echo "<script>alert('Error saving call slip. Please try again.'); window.history.back();</script>";
    }

    $stmt->close();
}

$conn->close();
?>
