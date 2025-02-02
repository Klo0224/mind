<?php
require_once 'connect.php';

function addCounselor($fname, $lname, $email, $department) {
    global $conn;
    
    // Use prepared statements to prevent SQL injection
    $checkStmt = $conn->prepare("SELECT email FROM MHP WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        return [
            'status' => 'error',
            'message' => 'Email already exists',
            'alert_type' => 'error'
        ];
    }
    
    // If email doesn't exist, proceed with insertion
    $defaultPassword = md5('123'); // Note: Consider using password_hash() instead of md5
    
    $insertStmt = $conn->prepare("INSERT INTO MHP (fname, lname, email, department, password) VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param("sssss", $fname, $lname, $email, $department, $defaultPassword);
    
    if ($insertStmt->execute()) {
        return [
            'status' => 'success',
            'message' => 'Counselor added successfully',
            'alert_type' => 'success'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to add counselor',
            'alert_type' => 'error'
        ];
    }
}

function getAllCounselors() {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM MHP ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $counselors = [];
    while ($row = $result->fetch_assoc()) {
        $counselors[] = $row;
    }
    
    return $counselors;
}

function getCounselorById($id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT * FROM MHP WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

function updateCounselor($id, $fname, $lname, $email, $department) {
    global $conn;
    
    // First check if email exists for other counselors
    $checkStmt = $conn->prepare("SELECT id FROM MHP WHERE email = ? AND id != ?");
    $checkStmt->bind_param("si", $email, $id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        return [
            'status' => 'error',
            'message' => 'Email already exists for another counselor',
            'alert_type' => 'error'
        ];
    }
    
    $updateStmt = $conn->prepare("UPDATE MHP SET fname = ?, lname = ?, email = ?, department = ? WHERE id = ?");
    $updateStmt->bind_param("ssssi", $fname, $lname, $email, $department, $id);
    
    if ($updateStmt->execute()) {
        return [
            'status' => 'success',
            'message' => 'Counselor updated successfully',
            'alert_type' => 'success'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to update counselor',
            'alert_type' => 'error'
        ];
    }
}

function deleteCounselor($id) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM MHP WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        return [
            'status' => 'success',
            'message' => 'Counselor deleted successfully',
            'alert_type' => 'success'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to delete counselor',
            'alert_type' => 'error'
        ];
    }
}
?>