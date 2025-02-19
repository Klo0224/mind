<?php
// Prevent browser caching (so back button won't work)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
include("mhp_connection.php");


// Ensure session has the MHP ID
if (!isset($_SESSION['mhp_id'])) {
    die("MHP session ID is not set. Please log in."); // Or redirect to the login page
}

$conn = new mysqli("localhost", "root", "", "_Mindsoothe");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get the MHP ID from session
$mhp_id = $_SESSION['mhp_id'];

// Fetch the profile image path for the logged-in MHP
$counselorProfileImage = 'images/blueuser.svg'; // Default image
$stmt = $conn->prepare("SELECT profile_image FROM mhp WHERE id = ?");
$stmt->bind_param("i", $mhp_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $counselorProfileImage = !empty($row['profile_image']) ? $row['profile_image'] : $counselorProfileImage;
}

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Counselor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <style>
        .sidebar {
            transition: width 0.3s ease;
            width: 256px;
            min-width: 256px;
        }
        .main-content {
            transition: margin-left 0.3s ease;
            margin-left: 256px;
        }
        .menu-item:hover {
            background-color: #f3f4f6;
        }
        .menu-item.active {
            color: #1cabe3;
            background-color: #eff6ff;
            border-right: 4px solid #1cabe3;
        }
        .content-section {
            display: none;
        }
        .content-section.active {
            display: block;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal.active {
            display: flex;
        }
        .profile-upload-container {
            position: relative;
            display: inline-block;
        }
        
        .upload-icon {
            transition: all 0.3s ease;
        }
        
        .upload-icon:hover {
            transform: scale(1.1);
        }
    </style>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <div class="sidebar fixed top-0 left-0 h-screen bg-white shadow-lg z-10">
        <!-- Logo Section -->
        <div class="flex items-center p-6 border-b">
            <div class="w-15 h-10 rounded-full flex items-center justify-center">
                <img src="images/Mindsoothe(2).svg" alt="Mindsoothe Logo">
            </div>
        </div>

        <!-- Menu Items -->
        <nav class="mt-6">
            <a href="mhp_dashboard.php" class="menu-item active flex items-center px-6 py-3" data-section="dashboard" id="dashboardItem">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="ml-3">Dashboard</span>
            </a>
            <a href="mhp_chat.php" class="menu-item flex items-center px-6 py-3 text-gray-600" data-section="chats" id="chatItem">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18a1 1 0 011 1v12a1 1 0 01-1 1H6l-3 3V5a1 1 0 011-1z"/>
                </svg>
                <span class="ml-3">Chats</span>
            </a>
        </nav>

        <!-- Logout Button -->
        <div class="absolute bottom-0 w-full p-6 border-t">
            <a href="logout.php" class="flex items-center text-red-500 hover:text-red-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="ml-3">Logout</span>
            </a>
        </div>
    </div>
    
    <div class="main-content min-h-screen p-8">
    <div id="dashboard-section" class="section active">
    <!-- Dashboard content -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-8" style="width: 1200px; margin-left: 13px; margin-top: 30px">
    <div class="flex items-center">
        <div class="relative">
        <img id="counselor-image" 
             src="<?php echo htmlspecialchars($counselorProfileImage); ?>" 
             alt="Profile Picture" 
             class="w-24 h-24 rounded-full object-cover">
        <label for="profile-upload" 
               class="absolute bottom-0 right-0 bg-blue-500 rounded-full p-2 cursor-pointer hover:bg-blue-600 upload-icon">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
            </svg>
        </label>
        <input type="file" 
               id="profile-upload" 
               class="hidden" 
               accept="image/*">
    </div>
        <div class="ml-6">
    <h2 class="text-2xl font-bold"><?php echo $fullName; ?></h2>
    <p class="text-gray-600">Department: <?php echo $department; ?></p>
</div>
</div>
</div>
<!-- Search Section -->
<div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="flex items-center mb-4">
                    <input 
                        type="text" 
                        id="searchInput" 
                        placeholder="Search student..." 
                        class="flex-1 p-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                        onkeydown="handleKeyPress(event)"
                    >
                    <button onclick="searchStudent()" class="ml-4 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Search</button>
                </div>
                
                <!-- Recent Searches Section -->
                <div id="recent-searches" class="flex flex-wrap gap-2 mt-2">
                    <!-- Recent search tags will be dynamically added here -->
                </div>
            </div>

            <!-- Student Results - Initially Hidden -->
            <div id="student-results" class="hidden bg-white rounded-lg shadow-md p-6">
                <!-- Student results will be dynamically inserted here -->
            </div>

             <!-- Profile Update Confirmation Modal -->
        <div id="update-modal" class="modal">
            <div class="m-auto bg-white rounded-lg p-6 max-w-sm">
                <h3 class="text-lg font-bold mb-4">Update Profile Picture</h3>
                <p class="mb-6">Are you sure you want to update your profile picture?</p>
                <div class="flex justify-end space-x-4">
                    <button onclick="cancelProfileUpdate()" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>
                    <button onclick="confirmProfileUpdate()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">Confirm</button>
                </div>
            </div>
        </div>
</div>
</body>
<script>
            // Section switching functionality
            const menuItems = document.querySelectorAll('.menu-item');
            const sections = document.querySelectorAll('.section');
            
            menuItems.forEach(item => {
              item.addEventListener('click', function(e) {
                if (this.getAttribute('data-section')) {
                  e.preventDefault();
                  
                  menuItems.forEach(mi => mi.classList.remove('active'));
                  sections.forEach(section => section.classList.remove('active'));
                  
                  this.classList.add('active');
                  
                  const sectionId = this.getAttribute('data-section');
                  document.getElementById(`${sectionId}-section`).classList.add('active');
                }
              });
            });
    </script>
<script src="mhp_sidebar.js"></script>

<script>
        document.getElementById('profile-upload').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    console.log('Session check...');
    
    // Add debug request to check session
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            console.log('Session status:', data);
        });
    
    const formData = new FormData();
    formData.append('profile_image', file);
    
    fetch('mhp_upload_profile.php', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        document.getElementById('counselor-image').src = 'http://localhost/testers/' + data.filepath + '?t=' + new Date().getTime();
    } else {
        console.error('Upload error:', data.error);
        alert(data.error || 'Failed to upload image.');
    }
})
.catch(error => {
    console.error('Error:', error);
    alert('An error occurred while uploading the image.');
});

});
    </script>

<script>
                // Function to handle Enter key press
                function handleKeyPress(event) {
                    console.log("Key pressed:", event.key); // Debugging line
                    if (event.key === "Enter") {
                        console.log("Enter key detected, triggering searchStudent");
                        searchStudent(); // Call the search function
                    }
                }

                async function searchStudent() {
                    const searchInput = document.getElementById('searchInput').value.trim();
                    const resultsContainer = document.getElementById('student-results');

                    if (searchInput === '') {
                        resultsContainer.classList.add('hidden');
                        resultsContainer.innerHTML = '';
                        return;
                    }

                    try {
                        // Fetch results from the server
                        const response = await fetch(`mhp_search.php?query=${encodeURIComponent(searchInput)}`);
                        const students = await response.json();

                        // Check if students were found
                        if (students.length === 0) {
                            resultsContainer.classList.remove('hidden');
                            resultsContainer.innerHTML = `<p class="text-gray-600">No students found.</p>`;
                            return;
                        }

                        // Generate HTML for each student
                        let resultsHTML = '';
                        students.forEach((student, index) => {
                            const profileImage = student.profile_image || 'images/blueuser.svg';
                            const timeSlots = student.time_slots.map(slot => `
                                <li>
                                    ${slot.day_of_week}: ${slot.start_time} - ${slot.end_time}
                                </li>
                            `).join('');

                            resultsHTML += `
                                <div class="border rounded-lg p-4 hover:shadow-lg transition-shadow mb-4">
                                    <div class="flex items-center justify-between cursor-pointer" onclick="toggleStudentDetails(${index})">
                                        <div class="flex items-center">
                                            <img src="${profileImage}" alt="Student" class="w-16 h-16 rounded-full object-cover">
                                            <div class="ml-4">
                                                <h3 class="font-semibold">${student.firstName} ${student.lastName}</h3>
                                            </div>
                                        </div>
                                        <svg id="toggle-icon-${index}" class="w-6 h-6 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>

                                    <div id="student-details-${index}" class="hidden mt-4">
                                        <div class="text-sm text-gray-600">
                                            <p>Student ID: ${student.Student_id}</p>
                                            <p>Course: ${student.Course}</p>
                                            <p>Year: ${student.Year}</p>
                                            <p>Department: ${student.Department}</p>
                                            <p>PHQ9 Result: ${student.PhResult}</p>
                                        </div>

                                        <!-- Right Section: Vacant Time -->
                                        <div class="mt-4">
                                            <h4 class="font-semibold mb-2">Vacant Time:</h4>
                                            <ul class="list-disc list-inside text-sm text-gray-600">
                                                ${timeSlots || '<li>No vacant time available</li>'}
                                            </ul>
                                        </div>

                                        <!-- Bottom Buttons -->
                                        <div class="flex justify-between mt-4">
                                            <button onclick="openChat('${student.Student_id}')" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Message</button>
                                           <button onclick="printCallSlip('${student.Student_id}')" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">
                                            Print Call Slip
                                            </button>

                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        resultsContainer.innerHTML = resultsHTML;
                        resultsContainer.classList.remove('hidden');
                    } catch (error) {
                        console.error('Error searching students:', error);
                        resultsContainer.classList.remove('hidden');
                        resultsContainer.innerHTML = `<p class="text-red-600">Error fetching students. Please try again.</p>`;
                    }
                }

                function toggleStudentDetails(index) {
                    const detailsContainer = document.getElementById(`student-details-${index}`);
                    const toggleIcon = document.getElementById(`toggle-icon-${index}`);
                    
                    detailsContainer.classList.toggle('hidden');
                    toggleIcon.classList.toggle('rotate-180');
                }
            </script>

<script>
                // Function to save recent searches
                function saveRecentSearch(query) {
                    if (!query) return;

                    // Get existing recent searches from localStorage
                    let recentSearches = JSON.parse(localStorage.getItem('recentStudentSearches') || '[]');
                    
                    // Remove duplicates and keep only last 5 unique searches
                    recentSearches = recentSearches.filter(search => search.toLowerCase() !== query.toLowerCase());
                    recentSearches.unshift(query);
                    recentSearches = recentSearches.slice(0, 5);

                    // Save back to localStorage
                    localStorage.setItem('recentStudentSearches', JSON.stringify(recentSearches));

                    // Update the recent searches display
                    displayRecentSearches();
                }

                // Function to display recent searches
                function displayRecentSearches() {
                    const recentSearchesContainer = document.getElementById('recent-searches');
                    const recentSearches = JSON.parse(localStorage.getItem('recentStudentSearches') || '[]');

                    // Clear existing recent searches
                    recentSearchesContainer.innerHTML = '';

                    // Add recent search tags
                    recentSearches.forEach((search, index) => {
                        const searchWrapper = document.createElement('div');
                        searchWrapper.className = 'flex items-center bg-blue-100 text-blue-800 text-sm font-medium mr-2 px-2.5 py-0.5 rounded-full hover:bg-blue-200 transition-colors';
                        
                        const searchTag = document.createElement('span');
                        searchTag.textContent = search;
                        searchTag.className = 'cursor-pointer mr-2';
                        searchTag.onclick = () => {
                            document.getElementById('searchInput').value = search;
                            searchStudent();
                        };

                        const deleteButton = document.createElement('button');
                        deleteButton.innerHTML = '&times;';
                        deleteButton.className = 'text-red-500 hover:text-red-700 font-bold';
                        deleteButton.onclick = (e) => {
                            e.stopPropagation(); // Prevent triggering search
                            deleteRecentSearch(index);
                        };

                        searchWrapper.appendChild(searchTag);
                        searchWrapper.appendChild(deleteButton);
                        recentSearchesContainer.appendChild(searchWrapper);
                    });

                    // Add "Clear All" button if there are recent searches
                    if (recentSearches.length > 0) {
                        const clearAllButton = document.createElement('button');
                        clearAllButton.textContent = 'Clear All';
                        clearAllButton.className = 'ml-4 text-sm text-red-600 hover:text-red-800 underline';
                        clearAllButton.onclick = clearAllRecentSearches;
                        recentSearchesContainer.appendChild(clearAllButton);
                    }
                }

                // Function to delete a specific recent search
                function deleteRecentSearch(index) {
                    let recentSearches = JSON.parse(localStorage.getItem('recentStudentSearches') || '[]');
                    recentSearches.splice(index, 1);
                    localStorage.setItem('recentStudentSearches', JSON.stringify(recentSearches));
                    displayRecentSearches();
                }

                // Function to clear all recent searches
                function clearAllRecentSearches() {
                    localStorage.removeItem('recentStudentSearches');
                    displayRecentSearches();
                }

                // Modify searchStudent to save recent searches
                async function searchStudent() {
                    const searchInput = document.getElementById('searchInput').value.trim();
                    const resultsContainer = document.getElementById('student-results');

                    if (searchInput === '') {
                        resultsContainer.classList.add('hidden');
                        resultsContainer.innerHTML = '';
                        return;
                    }

                    // Save the search query to recent searches
                    saveRecentSearch(searchInput);

                    try {
                        // Fetch results from the server
                        const response = await fetch(`mhp_search.php?query=${encodeURIComponent(searchInput)}`);
                        const students = await response.json();

                        // Check if students were found
                        if (students.length === 0) {
                            resultsContainer.classList.remove('hidden');
                            resultsContainer.innerHTML = `<p class="text-gray-600">No students found.</p>`;
                            return;
                        }

                        // Generate HTML for each student
                        let resultsHTML = '';
                        students.forEach((student, index) => {
                            const profileImage = student.profile_image || 'images/blueuser.svg';
                            const timeSlots = student.time_slots.map(slot => `
                                <li>
                                    ${slot.day_of_week}: ${slot.start_time} - ${slot.end_time}
                                </li>
                            `).join('');

                            resultsHTML += `
                                <div class="border rounded-lg p-4 hover:shadow-lg transition-shadow mb-4">
                                    <div class="flex items-center justify-between cursor-pointer" onclick="toggleStudentDetails(${index})">
                                        <div class="flex items-center">
                                            <img src="${profileImage}" alt="Student" class="w-16 h-16 rounded-full object-cover">
                                            <div class="ml-4">
                                                <h3 class="font-semibold">${student.firstName} ${student.lastName}</h3>
                                            </div>
                                        </div>
                                        <svg id="toggle-icon-${index}" class="w-6 h-6 transform transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>

                                    <div id="student-details-${index}" class="hidden mt-4">
                                        <div class="text-sm text-gray-600">
                                            <p>Student ID: ${student.Student_id}</p>
                                            <p>Course: ${student.Course}</p>
                                            <p>Year: ${student.Year}</p>
                                            <p>Department: ${student.Department}</p>
                                            <p>PHQ9 Result: ${student.PhResult}</p>
                                        </div>

                                        <!-- Right Section: Vacant Time -->
                                        <div class="mt-4">
                                            <h4 class="font-semibold mb-2">Vacant Time:</h4>
                                            <ul class="list-disc list-inside text-sm text-gray-600">
                                                ${timeSlots || '<li>No vacant time available</li>'}
                                            </ul>
                                        </div>

                                        <!-- Bottom Buttons -->
                                        <div class="flex justify-between mt-4">
                                            <button onclick="openChat('${student.Student_id}')" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">Message</button>
                                            <button onclick="printCallSlip('${student.Student_id}')" class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600">Print Call Slip</button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });

                        resultsContainer.innerHTML = resultsHTML;
                        resultsContainer.classList.remove('hidden');
                    } catch (error) {
                        console.error('Error searching students:', error);
                        resultsContainer.classList.remove('hidden');
                        resultsContainer.innerHTML = `<p class="text-red-600">Error fetching students. Please try again.</p>`;
                    }
                }

                // Initialize recent searches on page load
                document.addEventListener('DOMContentLoaded', displayRecentSearches);
            </script>
             <script>
            // Global variables
            let selectedImage = null;

            // Section switching functionality
            const menuItems = document.querySelectorAll('.menu-item');
            const sections = document.querySelectorAll('.content-section');

            menuItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    menuItems.forEach(mi => mi.classList.remove('active'));
                    sections.forEach(section => section.classList.remove('active'));
                    
                    this.classList.add('active');
                    
                    const sectionId = this.getAttribute('data-section');
                    document.getElementById(`${sectionId}-section`).classList.add('active');
                });
            });

            // Profile image upload functionality
            const profileUpload = document.getElementById('profile-upload');
            const counselorImage = document.getElementById('counselor-image');
            const updateModal = document.getElementById('update-modal');

            profileUpload.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    selectedImage = file;
                    updateModal.classList.add('active');
                }
            });

            function cancelProfileUpdate() {
                selectedImage = null;
                profileUpload.value = '';
                updateModal.classList.remove('active');
            }

            function confirmProfileUpdate() {
                if (selectedImage) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        counselorImage.src = e.target.result;
                        // Here you would typically upload the image to your server
                    }
                    reader.readAsDataURL(selectedImage);
                }
                updateModal.classList.remove('active');
            }

            // Student search functionality
            

            // Function to open chat with specific student
            function openChat(studentName) {
                // Switch to chats section
                menuItems.forEach(mi => mi.classList.remove('active'));
                sections.forEach(section => section.classList.remove('active'));
                
                document.querySelector('[data-section="chats"]').classList.add('active');
                document.getElementById('chats-section').classList.add('active');

                // You would typically load the chat history here
                document.getElementById('chat-container').innerHTML = `
                    <div class="text-center text-gray-600">
                        Chat session with ${studentName}
                    </div>
                `;
            }

            // Function to print call slip
            function printCallSlip(studentName) {
                // Implement call slip printing functionality
                console.log(`Printing call slip for ${studentName}`);
            }
        </script>
        <script>
    function printCallSlip(studentId) {
        window.location.href = `call_slip.php?student_id=${encodeURIComponent(studentId)}`;
    }
</script>  
<script>
    function openChat(studentId) {
        window.location.href = `mhp_chat.php?student_id=${encodeURIComponent(studentId)}`;
    }
</script>   
</html>