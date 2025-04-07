<?php
session_start();

// Database connection
include("connect.php");

// Check if MHP is logged in
if (!isset($_SESSION['mhp_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

function getAllUsers($conn, $mhp_id, $search = '') {
    // Get users who have messaged with this MHP or are available for messaging
    $sql = "SELECT DISTINCT u.id, u.firstName, u.lastName, u.Student_id,
            (SELECT m.message FROM Messages m 
             WHERE (m.student_id = u.id AND m.mhp_id = ?)
             ORDER BY m.timestamp DESC LIMIT 1) as last_message,
            (SELECT m.timestamp FROM Messages m 
             WHERE (m.student_id = u.id AND m.mhp_id = ?)
             ORDER BY m.timestamp DESC LIMIT 1) as last_message_time,
            (SELECT COUNT(*) FROM Messages m 
             WHERE m.student_id = u.id AND m.mhp_id = ? AND m.is_read = 0 AND m.sender_type = 'student') as unread_count
            FROM Users u
            LEFT JOIN Messages m ON m.student_id = u.id
            WHERE 1=1";
    
    $params = [$mhp_id, $mhp_id, $mhp_id];
    $types = 'iii';
    
    if ($search) {
        $sql .= " AND (u.firstName LIKE ? OR u.lastName LIKE ? OR u.Student_id LIKE ?)";
        $searchParam = '%' . $search . '%';
        array_push($params, $searchParam, $searchParam, $searchParam);
        $types .= 'sss';
    }
    
    $sql .= " GROUP BY u.id ORDER BY last_message_time DESC";
    
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    } else {
        throw new Exception("Database query failed: " . $conn->error);
    }
}

if (isset($_GET['fetchUsers'])) {
    try {
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $users = getAllUsers($conn, $_SESSION['mhp_id'], $search);
        header('Content-Type: application/json');
        echo json_encode($users);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit();
}
?>