<?php
// get_student_data.php
header('Content-Type: application/json');
require_once 'connect.php';

if (isset($_GET['studentId'])) {
    $studentId = $_GET['studentId'];
    
    // Get student information
    $stmt = $pdo->prepare("
        SELECT u.*, t.start_time, t.end_time 
        FROM users u 
        LEFT JOIN time_slots t ON u.id = t.user_id 
        WHERE u.Student_id = ?
    ");
    
    $stmt->execute([$studentId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        $response = [
            'success' => true,
            'data' => [
                'studentName' => $data['firstName'] . ' ' . $data['lastName'],
                'course' => $data['Course'],
                'year' => $data['Year'],
                'department' => $data['Department'],
                'appointmentTime' => $data['start_time']
            ]
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Student not found'
        ];
    }
    
    echo json_encode($response);
}

// save_call_slip.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $pdo->prepare("
        INSERT INTO call_slips (
            student_id, 
            appointment_date, 
            appointment_time, 
            unit,
            allow_student,
            reschedule_reason
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    try {
        $stmt->execute([
            $data['studentId'],
            $data['date'],
            $data['appointmentTime'],
            $data['unit'],
            $data['allowStudent'] ? 1 : 0,
            $data['rescheduleReason']
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Call slip saved successfully']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error saving call slip']);
    }
}
?>