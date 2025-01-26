<?php
session_start();
include 'auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Log the received data
$raw_data = file_get_contents('php://input');
error_log("Received data: " . $raw_data);

// Get the POST data
$data = json_decode($raw_data, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received or invalid JSON']);
    exit();
}

// Get user_id from email
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $_SESSION['email']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$user_id = $user['id'];

// Log the user ID
error_log("User ID from session: " . $user_id);

// Update the user_id in the data array
$data['user_id'] = $user_id;

// Map the answers to the corresponding response texts
$answerTexts = [
    0 => "Not at all",
    1 => "Several days",
    2 => "More than half the days",
    3 => "Nearly every day"
];

// Determine severity based on total score
$severity = "";
if ($data['total_score'] >= 0 && $data['total_score'] <= 4) {
    $severity = "None-minimal";
} elseif ($data['total_score'] >= 5 && $data['total_score'] <= 9) {
    $severity = "Mild";
} elseif ($data['total_score'] >= 10 && $data['total_score'] <= 14) {
    $severity = "Moderate";
} elseif ($data['total_score'] >= 15 && $data['total_score'] <= 19) {
    $severity = "Moderately severe";
} else {
    $severity = "Severe";
}

// Convert numeric answers to text responses
$textAnswers = array_map(function($answer) use ($answerTexts) {
    return isset($answerTexts[$answer]) ? $answerTexts[$answer] : "Invalid Response";
}, $data['answers']);

try {
    // Prepare the SQL statement
    $stmt = $conn->prepare("INSERT INTO phq9_responses (
        user_id, 
        question_1,
        question_2,
        question_3,
        question_4,
        question_5,
        question_6,
        question_7,
        question_8,
        question_9,
        response_score,
        severity,
        response_date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // Bind the parameters - note the additional 's' for severity
    $stmt->bind_param("isssssssssss",
        $user_id,
        $textAnswers[0],
        $textAnswers[1],
        $textAnswers[2],
        $textAnswers[3],
        $textAnswers[4],
        $textAnswers[5],
        $textAnswers[6],
        $textAnswers[7],
        $textAnswers[8],
        $data['total_score'],
        $severity
    );

    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log("Error in save_phq9.php: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();
?>