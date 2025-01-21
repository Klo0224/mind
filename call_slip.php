<!-- index.html -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Individual Call Slip</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-4">
    <div class="max-w-2xl mx-auto bg-white shadow-lg rounded-lg p-6">
        <!-- Header Section -->
        <div class="flex items-center justify-between border-b pb-4">
            <div class="flex items-center">
                <img src="path_to_logo.png" alt="University Logo" class="h-12">
                <div class="ml-4">
                    <h1 class="text-xl font-bold text-blue-900">UNIVERSITY OF SAINT LOUIS</h1>
                    <h2 class="text-lg font-semibold">Guidance and Counseling Center</h2>
                </div>
            </div>
            <div class="text-sm">
                <p>Document No.: FM-GCC-309</p>
                <p>Revision No.: 01</p>
                <p>Effectivity Date: May 23, 2022</p>
                <p>Page No.: 1 of 1</p>
            </div>
        </div>

        <!-- Form Section -->
        <form id="callSlipForm" class="mt-6">
            <div class="mb-4">
                <input type="text" id="unit" name="unit" placeholder="(Unit)" class="w-full border p-2">
            </div>

            <h2 class="text-center text-xl font-bold my-4">INDIVIDUAL CALL SLIP</h2>

            <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                    <label>Date:</label>
                    <input type="date" id="date" name="date" class="w-full border p-2">
                </div>
                <div>
                    <label>Course & Yr:</label>
                    <input type="text" id="courseYear" name="courseYear" class="w-full border p-2" readonly>
                </div>
            </div>

            <div class="mb-4">
                <p>Dear Sir/Ma'am,</p>
                <div class="mt-2">
                    <label>Please excuse</label>
                    <input type="text" id="studentName" name="studentName" class="w-3/4 border-b border-black mx-2" readonly>
                </div>
                <div class="mt-2">
                    <label>from your class. Kindly tell him/her to see me at</label>
                    <input type="time" id="appointmentTime" name="appointmentTime" class="border-b border-black mx-2" readonly>
                    <label>in my office. Thank You!</label>
                </div>
            </div>

            <div class="text-right mb-4">
                <p>Respectfully Yours,</p>
                <div class="mt-4">
                    <input type="text" id="counselorName" name="counselorName" class="border-b border-black w-48" readonly>
                    <p>Guidance Counselor</p>
                </div>
            </div>

            <div class="border-t pt-4">
                <div class="mb-2">
                    <input type="checkbox" id="allowStudent" name="allowStudent">
                    <label for="allowStudent">I am allowing my student to see the counselor as scheduled.</label>
                </div>
                <div class="mb-2">
                    <input type="checkbox" id="reschedule" name="reschedule">
                    <label for="reschedule">Kindly reschedule the student's appointment for the following reasons:</label>
                    <textarea id="rescheduleReason" name="rescheduleReason" class="w-full border mt-2 p-2" rows="2"></textarea>
                </div>
            </div>

            <div class="mt-4 text-center">
                <div class="border-t border-black w-48 mx-auto pt-2">
                    <p>Teacher's Signature</p>
                </div>
            </div>
        </form>
    </div>

    <script src="call_slip.js"></script>
</body>
</html>