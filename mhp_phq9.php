<?php
// Fetch PHQ9 severity result
$Student_id = $_GET['Student_id'];
$query = "SELECT severity FROM phq9_responses WHERE user_id = ? ORDER BY response_date DESC LIMIT 1";
$stmt = $conn->prepare($query);  
$stmt->bind_param("i", $Student_id);
$stmt->execute();
$result = $stmt->get_result();

$phq9_result = 'No data available';
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $phq9_result = $row['severity'];
}

$conn->close();
?>