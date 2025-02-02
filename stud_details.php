<?php
session_start();
include("connect.php");
header('Content-Type: application/json');

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

try {
    // Use prepared statement to prevent SQL injection
    $email = $_SESSION['email'];
    $userQuery = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $userQuery->bind_param("s", $email);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    
    if ($userResult->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $userData = $userResult->fetch_assoc();
    $userId = $userData['id'];
    
    // Check if file was uploaded
    if (!isset($_FILES['profileImage']) || $_FILES['profileImage']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded or upload error occurred');
    }
    
    $file = $_FILES['profileImage'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    // Validate file
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExt, $allowedExtensions)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.');
    }
    
    // Limit file size (5MB)
    if ($fileSize > 5000000) {
        throw new Exception('File is too large. Maximum size is 5MB.');
    }
    
    // Generate unique filename using timestamp and random string
    $timestamp = time();
    $randomString = bin2hex(random_bytes(8));
    $newFileName = "profile_{$userId}_{$timestamp}_{$randomString}.{$fileExt}";
    $uploadDir = 'uploads/profile_images/';
    $uploadPath = $uploadDir . $newFileName;
    
    // Create directory if it doesn't exist
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Get current profile image before uploading new one
    $currentImageQuery = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
    $currentImageQuery->bind_param("i", $userId);
    $currentImageQuery->execute();
    $currentImageResult = $currentImageQuery->get_result();
    $currentImageData = $currentImageResult->fetch_assoc();
    $oldImage = $currentImageData['profile_image'];
    
    // Move uploaded file
    if (!move_uploaded_file($fileTmpName, $uploadPath)) {
        throw new Exception('Failed to move uploaded file. Check directory permissions.');
    }

    // Add this right after move_uploaded_file:
$fullServerPath = $_SERVER['DOCUMENT_ROOT'] . '/' . $uploadPath;
error_log("Attempting to save file to: " . $fullServerPath);
error_log("File exists after upload: " . (file_exists($fullServerPath) ? 'Yes' : 'No'));
    
    // Update database with new image path
    $updateQuery = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
    $updateQuery->bind_param("si", $uploadPath, $userId);

    // Add this debugging query after your update:
$checkQuery = "SELECT profile_image FROM users WHERE id = '$userId'";
$checkResult = mysqli_query($conn, $checkQuery);
$row = mysqli_fetch_assoc($checkResult);
error_log("Stored path in database: " . $row['profile_image']);
    
    if (!$updateQuery->execute()) {
        // If database update fails, delete the uploaded file
        unlink($uploadPath);
        throw new Exception('Database update failed: ' . $conn->error);
    }
    
    // Delete old profile image only after successful update
    if ($oldImage && $oldImage !== 'images/blueuser.svg' && file_exists($oldImage)) {
        unlink($oldImage);
    }
    
    echo json_encode([
        'success' => true,
        'newImagePath' => $uploadPath,
        'message' => 'Profile image updated successfully'
    ]);
    
} catch (Exception $e) {
    error_log("Profile Update Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'details' => 'Error logged to server'
    ]);
}

$conn->close();
?>