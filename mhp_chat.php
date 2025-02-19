<?php
// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
require_once("connect.php");

// Secure error logging
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 0);
ini_set('error_log', 'error_log.log');

// Authentication Check
if (!isset($_SESSION['mhp_id'])) {
    header('Location: doc_registration.php');
    exit();
}
$mhp_id = (int) $_SESSION['mhp_id'];

// Helper function to send JSON response
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// Fetch Doctor Name
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetchDoctorName'])) {
    $sql = "SELECT fname, lname FROM MHP WHERE id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $mhp_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $doctor = $result->fetch_assoc();
        sendJsonResponse($doctor ?: ['error' => 'Doctor not found']);
    }
}

// Fetch Messages for a Specific Student
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetchMessages'], $_GET['student_id'])) {
    $student_id = (int) $_GET['student_id'];
    $query = "SELECT * FROM Messages WHERE student_id = ? AND mhp_id = ? ORDER BY timestamp ASC LIMIT 50";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("ii", $student_id, $mhp_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);
        sendJsonResponse($messages);
    }
}

// Send Message
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = isset($_POST['receiver_id']) ? (int) $_POST['receiver_id'] : 0;
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    
    if ($student_id <= 0 || empty($message)) {
        sendJsonResponse(["error" => "Invalid input"]);
    }
    
    $sender_type = 'MHP';
    $receiver_type = 'student';
    
    $query = "INSERT INTO Messages (student_id, mhp_id, sender_type, receiver_type, message) VALUES (?, ?, ?, ?, ?)";
    
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("iisss", $student_id, $mhp_id, $sender_type, $receiver_type, $message);
        if ($stmt->execute()) {
            sendJsonResponse(["success" => "Message sent successfully"]);
        } else {
            sendJsonResponse(["error" => "Failed to send message: " . $stmt->error]);
        }
    }
}

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
    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
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
            <a href="#" class="menu-item flex items-center px-6 py-3" data-section="dashboard" id="dashboardItem">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                </svg>
                <span class="ml-3">Dashboard</span>
            </a>
            <a href="#" class="menu-item active flex items-center px-6 py-3 text-gray-600" data-section="chats" id="chatItem">
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

    <!-- Main Content -->
    <div class="main-content min-h-screen p-8">
    <div id="chats-section" class="section active">
     <!-- Chats Section -->
            <div class="flex h-screen bg-gray-100">
                <!-- Left sidebar for chat user list -->
                <div class="w-1/4 bg-white border-r shadow-md flex flex-col">
                    <div class="p-4 border-b bg-gray-50">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Search students..." 
                                class="w-full p-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400 shadow-sm">
                        </div>
                    </div>
                    <ul id="userList" class="overflow-y-auto flex-grow">
                        <!-- Dynamically loaded user list will appear here -->
                        
                    </ul>
                </div>

                <!-- Right side for actual chat messages -->
                <div class="flex flex-col flex-grow bg-white shadow-md rounded-lg">
                    <div class="p-4 border-b bg-gray-50 flex items-center justify-between">
                        <h2 id="chat-header" class="text-xl font-semibold text-gray-800">Chat with Student</h2>
                    </div>

                    <div id="chatMessages" class="flex-grow overflow-y-auto p-6 space-y-4 bg-gray-50 max-h-[calc(100vh-10rem)]">
                        <!-- Messages from the selected student will appear here dynamically -->
                    </div>

                    <div class="p-4 border-t bg-gray-50 flex items-center">
                        <input type="hidden" id="student_id">
                        <input type="text" id="message_input" placeholder="Type your message..." 
                            class="flex-1 p-3 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <button onclick="sendMessage()" 
                                class="ml-3 p-3 bg-blue-500 text-white rounded-lg shadow-md hover:bg-blue-600 transition-all">
                            Send
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>
<script>
        let pusher;
        let channel;

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Pusher
            pusher = new Pusher('561b69476711bf54f56f', {
                cluster: 'ap1',
                encrypted: true
            });

            // Set up Chat User List Search
            setupUserSearch();
        });

        function setupUserSearch() {
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', function() {
                const query = searchInput.value.toLowerCase();
                fetch(`MHPSearch.php?fetchUsers=true&search=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(users => populateUserList(users))
                    .catch(error => console.error('Error fetching users:', error));
            });

            // Load all users initially
            searchInput.dispatchEvent(new Event('input'));
        }

        function populateUserList(users) {
            const userList = document.getElementById('userList');
            userList.innerHTML = '';

            users.forEach(user => {
                const li = document.createElement('li');
                li.className = 'p-4 flex items-center cursor-pointer hover:bg-gray-100 border-b';
                li.innerHTML = `
                    <div class="w-10 h-10 bg-gray-300 rounded-full mr-3"></div>
                    <div class="flex-1">
                        <p class="font-semibold">${user.firstName} ${user.lastName}</p>
                        <p class="text-sm text-gray-500">Latest message preview...</p>
                    </div>
                    <span class="text-sm text-gray-400">10:37 AM</span>
                `;
                li.addEventListener('click', () => openChatForMHP(user.id, user.firstName + ' ' + user.lastName));
                userList.appendChild(li);
            });
        }

        function openChatForMHP(studentId, studentName) {
            // 1) Set chat header
            document.getElementById('chat-header').innerText = 'Chat with ' + studentName;
            // 2) Store student_id in a hidden input
            document.getElementById('student_id').value = studentId;
            // 3) Clear chatMessages
            const chatMessagesDiv = document.getElementById('chatMessages');
            chatMessagesDiv.innerHTML = '';

            // 4) Unsubscribe from previous channel if any
            if (channel) {
                pusher.unsubscribe(channel.name);
            }

            // 5) Subscribe to new channel for that student
            channel = pusher.subscribe('chat_' + studentId);
             // Clean up first - remove any existing listeners
             channel.unbind('new-message');
            channel.bind('new-message', function(data) {
                // You can check data.sender_id, data.receiver_id, etc.
                // e.g., if data.sender_type !== 'MHP'
                if (data.sender_type !== 'MHP') {
                    appendMessageToUI(data);
                }  else {
                        console.log('Ignored message from MHP via Pusher');
                    }
            });

            // 6) Fetch existing chat from the server for this conversation
            fetch(`?fetchMessages=true&student_id=${studentId}`)
                .then(response => response.json())
                .then(messages => {
                    messages.forEach(msg => appendMessageToUI(msg));
                })
                .catch(error => console.error('Error fetching messages:', error));
        }

        function createMessageElement(message, type) {
            const div = document.createElement('div');
            const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            div.className = `mb-4 ${type === 'sent' ? 'flex justify-end' : 'flex justify-start'}`;
            
            const messageContainer = document.createElement('div');
            messageContainer.className = `max-w-[70%] flex flex-col ${type === 'sent' ? 'items-end' : 'items-start'}`;
            
            const messageContent = document.createElement('div');
            messageContent.className = `px-4 py-2 rounded-lg break-words`;
            messageContent.style.backgroundColor = type === 'sent' ? '#e6e6e6' : '#1cabe3';
            messageContent.style.color = type === 'sent' ? '#333' : '#fff';
            messageContent.textContent = message;
            
            const timeStampElem = document.createElement('div');
            timeStampElem.className = 'text-xs text-gray-500 mt-1';
            timeStampElem.textContent = timestamp;
            
            messageContainer.appendChild(messageContent);
            messageContainer.appendChild(timeStampElem);
            div.appendChild(messageContainer);
            
            return div;
        }

        function appendMessageToUI(msg) {
            const chatMessagesDiv = document.getElementById('chatMessages');

            const messageElement = createMessageElement(msg.message, msg.sender_type === 'student' ? 'received' : 'sent');
            chatMessagesDiv.appendChild(messageElement);

            // Scroll to the bottom of the chat
            chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
        }

        function sendMessage() {
            const message = document.getElementById('message_input').value.trim();
            const studentId = document.getElementById('student_id').value;

            if (!message || !studentId) {
                alert(message ? 'Please select a student first.' : 'Please enter a message.');
                return;
            }

            fetch('messages_handler_mhp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `receiver_id=${studentId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response from server:', data);
                if (data.success) {
                    // Create and append the sent message
                    const newMsg = {
                        message: message,
                        sender_type: 'MHP',
                        timestamp: new Date().toISOString()
                    };
                   // appendMessageToUI(newMsg);
                    document.getElementById('message_input').value = '';
                } else {
                    alert(data.error || 'Failed to send message');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                alert('Error sending message. Please try again.');
            });
        }
        // Add "Enter key" event for sending message
        document.getElementById('message_input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
<script src="mhp_sidebar.js"></script>
</html>
</html>