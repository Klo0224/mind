<?php
session_start();

// Database connection
include("connect.php");

// Check if doctor is logged in
if (!isset($_SESSION['mhp_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}


function getAllUsers($conn, $search = '') {
    $sql = "SELECT id, firstName, lastName, Student_id FROM Users";
    if ($search) {
        $sql .= " WHERE firstName LIKE ? OR lastName LIKE ?";
    }
    $stmt = $conn->prepare($sql);
    if ($search) {
        $searchParam = '%' . $search . '%';
        $stmt->bind_param('ss', $searchParam, $searchParam);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

if (isset($_GET['fetchUsers'])) {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $users = getAllUsers($conn, $search);
    echo json_encode($users);
    exit();
}
?>