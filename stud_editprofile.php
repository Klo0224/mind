<?php
session_start();
require_once 'connect.php'; // Ensure you have a database connection file

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['profileImage'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['profileImage'];

// Validate file
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

if ($file['size'] > $maxFileSize) {
    echo json_encode(['success' => false, 'message' => 'File too large']);
    exit;
}

// Generate unique filename
$userId = $_SESSION['user_id'];
$fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFileName = "profile_{$userId}_" . uniqid() . "." . $fileExtension;
$uploadDir = 'uploads/profile_images/';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$uploadPath = $uploadDir . $newFileName;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    // Update database with new profile image path
    $stmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
    $stmt->bind_param("si", $uploadPath, $userId);
    
    if ($stmt->execute()) {
        // Update session variable
        $_SESSION['profile_image'] = $uploadPath;
        
        echo json_encode([
            'success' => true, 
            'newImagePath' => $uploadPath,
            'message' => 'Profile image updated successfully'
        ]);
    } else {
        // Remove uploaded file if database update fails
        unlink($uploadPath);
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'File upload failed']);
}

$conn->close();
?>