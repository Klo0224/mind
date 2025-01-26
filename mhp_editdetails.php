<?php
include("connect.php");

// Assuming you want to fetch a specific counselor (you might pass this via GET/POST)
$counselorId = isset($_GET['id']) ? intval($_GET['id']) : 1; // Default to first counselor if no ID provided

// Prepare SQL statement
$sql = "SELECT fname, lname, department FROM mhp WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $counselorId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Fetch the counselor details
    $counselor = $result->fetch_assoc();
    
    // Combine first and last name
    $fullName = htmlspecialchars($counselor['fname'] . ' ' . $counselor['lname']);
    $department = htmlspecialchars($counselor['department']);
} else {
    $fullName = "Counselor Not Found";
    $department = "N/A";
}

// Close statement and connection
$stmt->close();
$conn->close();


// // Fetch PHQ9 severity result
// $Student_id = $_GET['Student_id'];
// $query = "SELECT severity FROM phq9_responses WHERE user_id = ? ORDER BY response_date DESC LIMIT 1";
// $stmt = $conn->prepare($query);
// $stmt->bind_param("i", $Student_id);
// $stmt->execute();
// $result = $stmt->get_result();

// $phq9_result = 'No data available';
// if ($result->num_rows > 0) {
//     $row = $result->fetch_assoc();
//     $phq9_result = $row['severity'];
// }

// $conn->close();
?>