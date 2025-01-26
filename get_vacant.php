<?php
include("connect.php");
include("auth.php");

// Ensure JSON response
header('Content-Type: application/json');

try {
    // Verify user is logged in
    if (!isset($_SESSION['email'])) {
        throw new Exception('User not authenticated');
    }

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input || !validateTimeSlotInput($input)) {
                throw new Exception('Invalid input data');
            }
            
            $sql = "INSERT INTO time_slots (user_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('isss', 
                $userId, 
                $input['day'], 
                $input['start_time'], 
                $input['end_time']
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to add time slot: ' . $stmt->error);
            }
            
            echo json_encode(['success' => true, 'message' => 'Time slot added successfully']);
            $stmt->close();
            break;
        
        case 'GET':
            $sql = "SELECT * FROM time_slots WHERE user_id = ? ORDER BY day_of_week, start_time";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $timeSlots = [];
            while ($row = $result->fetch_assoc()) {
                $timeSlots[] = $row;
            }
            
            echo json_encode($timeSlots);
            $stmt->close();
            break;
        
        case 'DELETE':
            if (!isset($_GET['id'])) {
                throw new Exception('No time slot ID provided');
            }
            
            $id = intval($_GET['id']);
            $sql = "DELETE FROM time_slots WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $id, $userId);
            
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete time slot: ' . $stmt->error);
            }
            
            echo json_encode(['success' => true, 'message' => 'Time slot deleted successfully']);
            $stmt->close();
            break;
        
        default:
            throw new Exception('Unsupported HTTP method');
    }

    $conn->close();

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => $e->getMessage()
    ]);
}

// Validation function
function validateTimeSlotInput($input) {
    $requiredFields = ['day', 'start_time', 'end_time'];
    foreach ($requiredFields as $field) {
        if (!isset($input[$field]) || empty($input[$field])) {
            return false;
        }
    }
    
    $validDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    if (!in_array($input['day'], $validDays)) {
        return false;
    }
    
    $start = strtotime($input['start_time']);
    $end = strtotime($input['end_time']);
    $minTime = strtotime('07:30');
    $maxTime = strtotime('17:00');
    
    return $start !== false && 
           $end !== false && 
           $start < $end && 
           $start >= $minTime && 
           $end <= $maxTime;
}
?>