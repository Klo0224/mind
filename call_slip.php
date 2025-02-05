<?php
session_start();
include("connect.php");

if (isset($_SESSION['mhp_name']) && isset($_SESSION['is_logged_in']) && isset($_SESSION['mhp_id'])) {
    $mhp_id = $_SESSION['mhp_id'];

    // Prepare and execute query securely
    $stmt = $conn->prepare("SELECT fname, lname FROM mhp WHERE id = ?");
    $stmt->bind_param("i", $mhp_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $fullName = $user['fname'] . ' ' . $user['lname'];
    } else {
        $fullName = "User not found";
    }

    $stmt->close();
} else {
    // Redirect to login page if session variables are not set
    header("Location: login.php");
    exit;
}

// Fetch student details based on the student_id passed in the URL
$student_id = isset($_GET['student_id']) ? $_GET['student_id'] : '';

if ($student_id) {
    $stmt = $conn->prepare("SELECT Student_id, firstName, lastName, department, course, year FROM Users WHERE Student_id = ?");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
}

else {
    die("Invalid student ID.");
}


$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Individual Call Slip</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
    @page {
        size: letter portrait;  /* Changed from landscape to portrait */
        margin: 0;
    }
    body {
        width: 8.5in;  /* Swapped width and height */
        height: 5.5in; /* Swapped width and height */
        margin: 0;
        padding: 1rem;
    }
    .no-print {
        display: none;
    }
}
    </style>
</head>
<body class="bg-gray-100">
    <div class="w-[5.5in] mx-auto bg-white shadow-lg p-4">
        <!-- Previous header and form sections remain unchanged until the signature part -->
        <!-- Header Section -->
       <!-- Header Section -->
<div class="flex items-center justify-between border-b pb-2">
    <div class="w-20 h-20 ml-2">
        <!-- Space for USL Logo -->
        <img src="images/usl.jpg" alt="USL Logo" class="w-full h-full object-contain">
    </div>
    <div class="flex-grow text-center">
        <h1 class="text-base font-bold text-blue-900">UNIVERSITY OF SAINT LOUIS</h1>
        <h2 class="text-sm font-semibold">Guidance and Counseling Center</h2>
    </div>
    <div class="w-20"></div> <!-- Balancing space on the right -->
</div>

        <!-- Form Section -->
        <form id="callSlipForm" class="mt-3">
            <div class="mb-2">
                <input type="text" id="unit" name="unit" placeholder="(Unit)"  value="<?php echo htmlspecialchars($student['department']); ?>" class="w-full border p-1 bg-gray-50 text-sm" readonly>
            </div>

            <h2 class="text-center text-lg font-bold my-2">INDIVIDUAL CALL SLIP</h2>

            <div class="grid grid-cols-2 gap-2 mb-2">
                <div>
                    <label class="text-sm">Date:</label>
                    <input type="text" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" class="w-full border p-1 text-sm" readonly>
                </div>
                <div>
                    <label class="text-sm">Course & Yr:</label>
                    <input type="text" id="courseYear" name="courseYear" value="<?php echo htmlspecialchars($student['course'] . ' - Year ' . $student['year']); ?>" class="w-full border p-1 text-sm" readonly>
                </div>
            </div>

            <div class="mb-2 text-sm">
                <p>Dear Sir/Ma'am,</p>
                <div class="mt-1">
                    <label>Please excuse</label>
                    <input type="text" id="studentName" name="studentName" value="<?php echo htmlspecialchars($student['firstName'] . ' ' . $student['lastName']); ?>" class="w-3/4 border-b border-black mx-1" readonly>
                </div>
                <div class="mt-1">
                    <label>from your class. Kindly tell him/her to see me at</label>
                    <input type="time" id="appointmentTime" name="appointmentTime" class="border-b border-black mx-1" required>
                    <label>in my office. Thank You!</label>
                </div>
            </div>

            <div class="text-center mb-2 text-sm">
                <p>Respectfully Yours,</p>
                <div class="mt-2 inline-block">
                    <input type="text" id="counselorName" name="counselorName" value="<?php echo htmlspecialchars($fullName); ?>" class="border-b border-black w-36 text-center" required>
                    <p class="text-center">Guidance Counselor</p>
                </div>
            </div>

            <div class="border-t pt-2 text-sm">
                <div class="mb-1">
                    <input type="checkbox" id="allowStudent" name="allowStudent">
                    <label for="allowStudent">I am allowing my student to see the counselor as scheduled.</label>
                </div>
                <div class="mb-1">
                    <input type="checkbox" id="reschedule" name="reschedule">
                    <label for="reschedule">Kindly reschedule the student's appointment for the following reasons:</label>
                </div>
            </div>

            <div class="mb-2 border p-2 text-sm">
                <h3 class="font-semibold mb-1">Reason/s for Counseling:</h3>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <input type="checkbox" id="academic" name="reasons[]" value="Academic Concerns">
                        <label for="academic">Academic Concerns</label><br>
                        <input type="checkbox" id="behavioral" name="reasons[]" value="Behavioral Issues">
                        <label for="behavioral">Behavioral Issues</label><br>
                        <input type="checkbox" id="family" name="reasons[]" value="Family Concerns">
                        <label for="family">Family Concerns</label><br>
                        <input type="checkbox" id="peer" name="reasons[]" value="Peer Concerns">
                        <label for="peer">Peer Concerns</label>
                    </div>
                    <div>
                        <input type="checkbox" id="mental" name="reasons[]" value="Mental Health Concerns">
                        <label for="mental">Mental Health Concerns</label><br>
                        <input type="checkbox" id="career" name="reasons[]" value="Career Concerns">
                        <label for="career">Career Concerns</label><br>
                        <input type="checkbox" id="others" name="reasons[]" value="Others">
                        <label for="others">Others. Specify:</label>
                        <textarea id="othersSpecify" class="border p-1 w-full h-20" placeholder="Please provide additional details..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Teacher's Signature Section -->
            <div class="text-center mb-4 text-sm">
                <div class="inline-block">
                    <input type="text" id="teacherName" name="teacherName" class="border-b border-black w-36 text-center" readonly>
                    <p class="text-center">Teacher's Signature</p>
                </div>
            </div>

            <div class="text-center mt-4 no-print">
                <button id="printCallSlip" type="button" class="bg-blue-500 text-white px-4 py-2 rounded-lg shadow">Print Call Slip</button>
            </div>
        </form>
    </div>

    <script src="call_slip.js"></script>
</body>
</html>