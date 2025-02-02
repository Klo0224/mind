<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$database = '_Mindsoothe';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['query'])) {
    $query = $conn->real_escape_string($_GET['query']);

    // Search by Student_id, firstName, or lastName
    $sql = "
    SELECT 
        u.Student_id,
        u.firstName,
        u.lastName,
        u.profile_image,
        u.Department,
        u.Course, 
        u.Year,
        ts.day_of_week,
        ts.start_time,
        ts.end_time,
        pr.severity AS PhResult
    FROM Users u
    LEFT JOIN time_slots ts ON u.id = ts.user_id
    LEFT JOIN phq9_responses pr ON u.id = pr.user_id
    WHERE 
        u.Student_id LIKE '%$query%' OR
        u.firstName LIKE '%$query%' OR
        u.lastName LIKE '%$query%'
    ";

    $result = $conn->query($sql);

    $students = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $userId = $row['Student_id'];
            if (!isset($students[$userId])) {
                $students[$userId] = [
                    'Student_id' => $row['Student_id'],
                    'firstName' => $row['firstName'],
                    'lastName' => $row['lastName'],
                    'profile_image' => $row['profile_image'],
                    'Department' => $row['Department'],  // Department
                    'Course' => $row['Course'],
                    'Year' => $row['Year'],
                    'PhResult' => $row['PhResult'] ?: 'Not Available', // new1
                    'time_slots' => []

                ];
            }
            $students[$userId]['PhResult'] = $row['PhResult'] ? $row['PhResult'] : 'Not Available';

            if ($row['day_of_week']) {
                $students[$userId]['time_slots'][] = [
                    'day_of_week' => $row['day_of_week'],
                    'start_time' => $row['start_time'],
                    'end_time' => $row['end_time']
                ];
            }
            
        }
    }

    echo json_encode(array_values($students));
}

$conn->close();
?>
