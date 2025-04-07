<?php

// include("auth.php");
// // Use session profile image if available, otherwise fetch from database
// $profileImage = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'images/blueuser.svg';

// // If not in session but user is logged in, fetch from database
// if (!isset($_SESSION['profile_image']) && isset($_SESSION['email'])) {
//     $email = $_SESSION['email'];
//     $query = $conn->prepare("SELECT profile_image FROM users WHERE email = ?");
//     $query->bind_param("s", $email);
//     $query->execute();
//     $result = $query->get_result();
//     if ($result->num_rows > 0) {
//         $userData = $result->fetch_assoc();
//         $profileImage = $userData['profile_image'];
//         $_SESSION['profile_image'] = $profileImage;
//     }
// }
include("auth.php");
// Function to get current profile image
function getCurrentProfileImage($conn) {
    error_log("Getting profile image - Session status: " . (isset($_SESSION['profile_image']) ? "Set" : "Not set"));
    
    // First try getting from session
    if (isset($_SESSION['profile_image']) && !empty($_SESSION['profile_image'])) {
        error_log("Returning image from session: " . $_SESSION['profile_image']);
        return $_SESSION['profile_image'];
    }
    
    // If not in session, try database
    if (isset($_SESSION['email'])) {
        $email = $_SESSION['email'];
        error_log("Fetching profile image from database for email: {$email}");
        
        $stmt = $conn->prepare("SELECT profile_image FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            error_log("Database returned profile_image: " . ($row['profile_image'] ? $row['profile_image'] : "NULL"));
            
            if (!empty($row['profile_image'])) {
                // Use the image path exactly as stored in database
                $profileImage = $row['profile_image'];
                error_log("Using image from database: {$profileImage}");
                
                // Store in session for future use
                $_SESSION['profile_image'] = $profileImage;
                return $profileImage;
            }
        } else {
            error_log("No user found in database with email: {$email}");
        }
    }
    
    // If we got here, use default image
    error_log("Using default profile image");
    return 'images/blueuser.svg';
}

// Include session start and database connection
include("connect.php");

// Get the profile image dynamically
$profileImage = getCurrentProfileImage($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Availability</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css'>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background-color: #f4f7f6;
        }
        .dashboard-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .sidebar {
            transition: width 0.3s ease;
            width: 256px;
            min-width: 256px;
        }
        .sidebar.collapsed {
            width: 80px;
            min-width: 80px;
        }
        .main-content {
            transition: margin-left 0.3s ease;
            margin-left: 256px;
        }
        .main-content.expanded {
            margin-left: 80px;
        }
        .menu-item {
            transition: all 0.3s ease;
        }
        .menu-item:hover {
            background-color: #f3f4f6;
        }
        .menu-item.active {
            color: #1cabe3;
            background-color: #eff6ff;
            border-right: 4px solid #1cabe3;
        }
        .menu-text {
            transition: opacity 0.3s ease;
        }
        .sidebar.collapsed .menu-text {
            opacity: 0;
            display: none;
        }
        .section {
            display: none;
        }
        .section.active {
            display: block;
        }
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
    </style>
</head>
<body class="bg-gray-50">
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar fixed top-0 left-0 h-screen bg-white shadow-lg z-10">
        <!-- Logo Section -->
        <div class="flex items-center p-6 border-b">
            <div class="w-15 h-10 rounded-full flex items-center justify-center">
                <a href="#"><img src="images/Mindsoothe(2).svg" alt="Mindsoothe Logo"></a>
            </div>
        </div>

        <!-- Menu Items -->
        <nav class="mt-6">
            <a href="#" class="menu-item flex items-center px-6 py-3" data-section="dashboard" id="gracefulThreadItem">
                <img src="images/gracefulThread.svg" alt="Graceful Thread" class="w-5 h-5">
                <span class="menu-text ml-3">Graceful Thread</span>
            </a>
            <a href="#" class="menu-item flex items-center px-6 py-3 text-gray-600" data-section="appointments" id="MentalWellness">
                <img src="images/Vector.svg" alt="Mental Wellness Companion" class="w-5 h-5">
                <span class="menu-text ml-3">Mental Wellness Companion</span>
            </a>
            <a href="#" class="menu-item active flex items-center px-6 py-3 text-gray-600" data-section="profile" id="ProfileItem">
                <img src="images/profile.svg" alt="Mental Wellness Companion" class="w-4 h-4">
                <span class="menu-text ml-3">Profile</span>
            </a>
            <a href="#" class="menu-item flex items-center px-6 py-3 text-gray-600" data-section="chat" id="ChatItem">
            <img src="images/profile.svg" alt="Mental Wellness Companion" class="w-4 h-4">
                <span class="menu-text ml-3">Chat</span>
            </a>
            
        </nav>
        <div class="absolute bottom-0 w-full border-t">
            <!-- Logout -->
            <a href="logout.php" class="menu-item flex items-center px-6 py-4 text-red-500 hover:text-red-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="menu-text ml-3">Logout</span>
            </a>  
        </div>
    </div>
    <div class="pl-64">

    <div class="bg-white rounded-lg shadow-md p-6 mb-8" style="width: 1200px; margin-left: 13px; margin-top: 30px">
    <div class="flex items-center">
    <div class="relative">
    <img src="<?php 
    // Add cache-busting parameter
    $imgSrc = $profileImage;
    if ($imgSrc !== 'images/blueuser.svg') {
        $imgSrc .= '?v=' . time();
    }
    echo htmlspecialchars($imgSrc); 
?>" alt="Profile Picture" class="w-24 h-24 rounded-full object-cover" id="profileImage">
    <label for="fileInput" class="absolute bottom-0 right-0 bg-blue-500 rounded-full p-2 cursor-pointer hover:bg-blue-600">
        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
    </label>
    <input type="file" 
           id="fileInput" 
           class="hidden" 
           accept="image/*">
</div>
<div class="ml-6">
    <div class="flex items-center">
        <h2 class="text-2xl font-bold mr-2"><?php echo $fullName; ?></h2>
        <button onclick="openEditModal()" class=class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
    </button>
    </div>
    <p class="text-gray-600">Department: <?php echo $Department; ?></p>
    <p class="text-gray-600">Course: <?php echo $Course; ?></p>
    <p class="text-gray-600">Year: <?php echo $Year; ?></p>

    <!-- Single button to trigger the edit modal -->
    <!-- <button onclick="openEditModal()" class="mt-4 px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
        Edit Profile
    </button> -->

    

</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96">
        <h3 class="text-lg font-semibold mb-4">Edit Profile</h3>

        <!-- Form fields for editing -->
        <label for="editFirstName" class="block text-gray-700 font-semibold">First Name: </label>
        <input type="text" id="editFirstName" placeholder="First Name" class="w-full p-2 border rounded mb-2">

        <label for="editLastName" class="block text-gray-700 font-semibold">Last Name: </label>
        <input type="text" id="editLastName" placeholder="Last Name" class="w-full p-2 border rounded mb-2">

        <label for="editDepartment" class="block text-gray-700 font-semibold">Department: </label>
        <input type="text" id="editDepartment" placeholder="Department" class="w-full p-2 border rounded mb-4">

        <label for="editCourse" class="block text-gray-700 font-semibold">Course: </label>
        <input type="text" id="editCourse" placeholder="Course" class="w-full p-2 border rounded mb-2">

        <label for="editYear" class="block text-gray-700 font-semibold">Year: </label>
        <input type="number" id="editYear" placeholder="Year" class="w-full p-2 border rounded mb-2">
        

        <!-- Modal buttons -->
        <div class="flex justify-end gap-2">
            <button onclick="closeEditModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
            <button onclick="saveEdit()" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">Save</button>
        </div>
    </div>
</div>

                </div>
            </div>


    <div class="p-8 ml-4">
        <!-- Header -->
        <div class="mb-6 ">
            <h1 class="text-2xl font-bold text-gray-800">My Available Time</h1>
            <p class="text-gray-600">Set your vacant time slots for counseling</p> 
        </div>

      <!-- Add New Time Slot Form -->
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <form id="addTimeForm" class="flex flex-wrap gap-4">
        <div class="w-full md:w-auto">
            <select name="day" required class="w-full md:w-48 p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Select Day</option>
                <option value="Monday">Monday</option>
                <option value="Tuesday">Tuesday</option>
                <option value="Wednesday">Wednesday</option>
                <option value="Thursday">Thursday</option>
                <option value="Friday">Friday</option>
            </select>
        </div>
        

        <div class="w-full md:w-auto">
            <input type="time" name="start_time" required 
                   min="07:30" max="17:00" 
                   class="w-full md:w-40 p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div class="w-full md:w-auto">
            <input type="time" name="end_time" required 
                   min="07:30" max="17:00"
                   class="w-full md:w-40 p-2 border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>
        


        <div class="w-full md:w-auto">
            <button type="submit" class="w-full md:w-auto bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Add Time Slot
            </button>
        </div>
    </form>
</div>

        <!-- Time Slots Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Day</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Time</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <!-- Time slots will be inserted here -->
        </tbody>
    </table>
    <div id="emptyState" class="hidden p-4 text-center text-gray-500">
        No time slots available
    </div>
</div>

    <script>// Section switching functionality
    document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('addTimeForm');
    const startTimeInput = form.querySelector('[name="start_time"]');
    const endTimeInput = form.querySelector('[name="end_time"]');
    
     // Function to validate time range
function validateTimeRange(startTime, endTime) {
    // Convert 12-hour time to minutes
    const timeToMinutes = (time) => {
        const [time12, period] = time.split(' ');
        let [hours, minutes] = time12.split(':').map(Number);
        
        if (period === 'PM' && hours !== 12) hours += 12;
        if (period === 'AM' && hours === 12) hours = 0;
        
        return hours * 60 + minutes;
    };

    const startMinutes = timeToMinutes(startTime);
    const endMinutes = timeToMinutes(endTime);

    if (endMinutes <= startMinutes) {
        return {
            isValid: false,
            message: 'End time must be later than start time'
        };
    }

    return { isValid: true };
}

// Form submission validation
form.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
        day: form.querySelector('[name="day"]').value,
        start_time: startTimeInput.value,
        end_time: endTimeInput.value,
        user_id: 1
    };

    try {
        const response = await fetch('get_vacant.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(formData)
        });

        const result = await response.json();
        
        console.log('Server response:', result);

        if(result.success) {
            alert('Time slot saved successfully!');
            loadTimeSlots();
            form.reset();
        } else {
            alert('Error: ' + (result.error || 'Unknown error'));
            console.error('Server error details:', result);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to save time slot: ' + error.message);
    }
});
});

// Function to load time slots
// Function to load time slots
async function loadTimeSlots() {
    try {
        const response = await fetch('get_vacant.php');
        
        // Add detailed logging
        console.log('Response status:', response.status);
        console.log('Content-Type:', response.headers.get('Content-Type'));
        
        const text = await response.text();
        console.log('Raw response:', text);
        
        let timeSlots;
        try {
            timeSlots = JSON.parse(text);
        } catch (parseError) {
            console.error('JSON Parsing Error:', parseError);
            throw new Error('Invalid JSON response: ' + text);
        }

        console.log('Parsed time slots:', timeSlots);

        const tbody = document.querySelector('table tbody');
        tbody.innerHTML = ''; 

        if (!Array.isArray(timeSlots) || timeSlots.length === 0) {
            document.getElementById('emptyState').classList.remove('hidden');
            return;
        }

        document.getElementById('emptyState').classList.add('hidden');

        timeSlots.forEach(slot => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${slot.day_of_week}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${slot.start_time}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${slot.end_time}</td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="deleteTimeSlot(${slot.id})" class="text-red-600 hover:text-red-900">Delete</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    } catch (error) {
        console.error('Error loading time slots:', error);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'text-red-600 p-4';
        errorDiv.textContent = 'Failed to load time slots: ' + error.message;
        document.querySelector('table').before(errorDiv);
    }
}

// Function to delete time slot
async function deleteTimeSlot(id) {
    if (!confirm('Are you sure you want to delete this time slot?')) {
        return;
    }

    try {
        const response = await fetch(`get_vacant.php?id=${id}`, {
            method: 'DELETE'
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            loadTimeSlots(); // Reload the table
        } else {
            alert(result.error || 'Failed to delete time slot');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to delete time slot. Please try again later.');
    }
}

// Load time slots when page loads
document.addEventListener('DOMContentLoaded', loadTimeSlots);
</script>
<script src="sidebarnav.js"></script>

<script>
                // Profile Image Handler
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('fileInput');
    const profileImage = document.getElementById('profileImage');
    
    // Initial cache-busting for the profile image
    if (profileImage && profileImage.src.indexOf('blueuser.svg') === -1) {
        profileImage.src = profileImage.src + '?t=' + new Date().getTime();
    }
    
    if (fileInput && profileImage) {
        fileInput.addEventListener('change', async function() {
            const selectedFile = this.files[0];
            if (!selectedFile) return;
            
            const confirmation = confirm('Are you sure you want to change your profile picture?');
            if (!confirmation) {
                this.value = '';
                return;
            }
            
            const formData = new FormData();
            formData.append('profileImage', selectedFile);
            
            // Show loading state
            profileImage.style.opacity = '0.5';
            
            try {
                const response = await fetch('stud_details.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Update image with cache-busting
                    const newImageUrl = `${data.newImagePath}?t=${new Date().getTime()}`;
                    profileImage.src = newImageUrl;
                    
                    // Update session
                    await fetch('update_session.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            profile_image: data.newImagePath
                        })
                    });
                    
                    alert('Profile image updated successfully!');
                } else {
                    throw new Error(data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error updating profile image: ' + error.message);
            } finally {
                profileImage.style.opacity = '1';
                fileInput.value = ''; // Reset file input
            }
        });
    } else {
        console.error('Required elements not found. Check your HTML IDs.');
    }
});
            </script>

<!-- new -->
<?php
// Assuming user is authenticated and their data is fetched based on the session
$userId = $_SESSION['user_id']; // Set this properly after login

$query = "SELECT firstName, lastName, Department FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $fullName = $user['firstName'] . ' ' . $user['lastName'];
    $department = $user['Department'];
} else {
    // Handle case where user data is not found
    $fullName = '';
    $department = '';
}
$stmt->close();
?>

<!-- new -->

<script>
function openEditModal() {
    // Pre-fill the input fields with the current values from PHP variables
    document.getElementById('editFirstName').value = '<?php echo $fullName ? explode(" ", $fullName)[0] : ""; ?>';
    document.getElementById('editLastName').value = '<?php echo $fullName ? explode(" ", $fullName)[1] : ""; ?>';
    document.getElementById('editCourse').value = '<?php echo $Course; ?>';
    document.getElementById('editYear').value = '<?php echo $Year; ?>';
    document.getElementById('editDepartment').value = '<?php echo $Department; ?>';

    // Show the modal
    const modal = document.getElementById('editModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

async function saveEdit() {
    const formData = new FormData();

    const firstName = document.getElementById('editFirstName').value.trim();
    const lastName = document.getElementById('editLastName').value.trim();
    const course = document.getElementById('editCourse').value.trim();
    const year = document.getElementById('editYear').value.trim();
    const department = document.getElementById('editDepartment').value.trim();

    if (!firstName || !lastName || !course || !year || !department) {
        alert('Please fill in all fields.');
        return;
    }

    formData.append('firstName', firstName);
    formData.append('lastName', lastName);
    formData.append('course', course);
    formData.append('year', year);
    formData.append('department', department);
    formData.append('action', 'updateProfile');

    try {
        const response = await fetch('update_profile.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.text();

        if (response.ok && result.trim() === 'success') {
            alert('Profile updated successfully.');
            location.reload(); // Reload the page to reflect changes
        } else {
            alert('Failed to update: ' + result);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while updating.');
    }

    closeEditModal();
}

</script>
    </div>
    </div>
</body>
</html>