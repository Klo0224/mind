<?php
session_start();
include("connect.php");

// Check if MHP is logged in
if (!isset($_SESSION['mhp_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$mhp_id = (int)$_SESSION['mhp_id'];

if ($student_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid student ID']);
    exit();
}

// Mark all unread messages from this student to this MHP as read
$sql = "UPDATE Messages SET is_read = 1 
        WHERE student_id = ? AND mhp_id = ? AND sender_type = 'student' AND is_read = 0";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("ii", $student_id, $mhp_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update messages']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>