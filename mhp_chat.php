<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

session_start();
require_once("connect.php");

// Authentication Check
if (!isset($_SESSION['mhp_id'])) {
    header('Location: doc_registration.php');
    exit();
}
$mhp_id = (int) $_SESSION['mhp_id'];

// Helper function to send JSON response
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// API Endpoints
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch Doctor Name
    if (isset($_GET['fetchDoctorName'])) {
        $sql = "SELECT fname, lname FROM MHP WHERE id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $mhp_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $doctor = $result->fetch_assoc();
            sendJsonResponse($doctor ?: ['error' => 'Doctor not found']);
        } else {
            sendJsonResponse(['error' => 'Database error'], 500);
        }
    }
    
    // Fetch Messages
    if (isset($_GET['fetchMessages'], $_GET['student_id'])) {
        $student_id = (int) $_GET['student_id'];
        $query = "SELECT * FROM Messages WHERE student_id = ? AND mhp_id = ? ORDER BY timestamp ASC";
        
        if ($stmt = $conn->prepare($query)) {
            $stmt->bind_param("ii", $student_id, $mhp_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            sendJsonResponse($messages);
        } else {
            sendJsonResponse(['error' => 'Failed to fetch messages'], 500);
        }
    }
}
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
        .message-container {
            display: flex;
            flex-direction: column;
        }
        .message-sent {
            align-self: flex-start;
            background-color: #3b82f6;
            color: white;
            border-radius: 1rem 1rem 1rem 0;
            max-width: 70%;
            margin-bottom: 0.5rem;
        }
        .message-received {
            align-self: flex-end;
            background-color: #e5e7eb;
            color: #1f2937;
            border-radius: 1rem 1rem 0 1rem;
            max-width: 70%;
            margin-bottom: 0.5rem;
        }
        .section {
            display: none;
        }
        .section.active {
            display: block;
        }
        #chatMessages {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding: 1.5rem;
        }
    </style>
</head>
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
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="section">
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-semibold mb-6">Counselor Dashboard</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Empty Dashboard Widgets -->
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200"></div>
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200"></div>
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200"></div>
                </div>
            </div>
        </div>

        <!-- Chats Section -->
        <div id="chats-section" class="section active">
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

                    <div id="chatMessages" class="flex-grow overflow-y-auto p-6 bg-gray-50 max-h-[calc(100vh-10rem)]">
                        <!-- Messages will appear here dynamically -->
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

    <script>
        let pusher;
        let channel;
        let isSending = false;
        const mhpId = <?php echo $mhp_id; ?>;

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Pusher
            pusher = new Pusher('561b69476711bf54f56f', {
                cluster: 'ap1',
                encrypted: true
            });

            // Menu item click handlers
            document.getElementById('dashboardItem').addEventListener('click', function(e) {
                e.preventDefault();
                switchSection('dashboard');
            });

            document.getElementById('chatItem').addEventListener('click', function(e) {
                e.preventDefault();
                switchSection('chats');
            });

            // Set up chat functionality
            setupUserSearch();
            
            // Request notification permission
            if ('Notification' in window) {
                Notification.requestPermission();
            }
        });

        function switchSection(sectionName) {
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            document.getElementById(`${sectionName}-section`).classList.add('active');

            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
                item.classList.remove('text-gray-600');
            });
            
            document.getElementById(`${sectionName}Item`).classList.add('active');
            document.getElementById(`${sectionName}Item`).classList.add('text-gray-600');
        }

        function setupUserSearch() {
            const searchInput = document.getElementById('searchInput');
            searchInput.addEventListener('input', debounce(function() {
                const query = searchInput.value.trim();
                if (query.length > 0) {
                    fetch(`MHPSearch.php?fetchUsers=true&search=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(users => populateUserList(users))
                        .catch(error => {
                            console.error('Error fetching users:', error);
                            document.getElementById('userList').innerHTML = 
                                '<li class="p-4 text-red-500">Error loading users</li>';
                        });
                } else {
                    fetchUsers();
                }
            }, 300));

            fetchUsers();
        }

        function fetchUsers() {
            fetch(`MHPSearch.php?fetchUsers=true`)
                .then(response => response.json())
                .then(users => populateUserList(users))
                .catch(error => {
                    console.error('Error fetching users:', error);
                    document.getElementById('userList').innerHTML = 
                        '<li class="p-4 text-red-500">Error loading users</li>';
                });
        }

        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(context, args), wait);
            };
        }

        function populateUserList(users) {
            const userList = document.getElementById('userList');
            userList.innerHTML = '';

            if (users.length === 0) {
                userList.innerHTML = '<li class="p-4 text-gray-500">No students found</li>';
                return;
            }

            users.forEach(user => {
                const li = document.createElement('li');
                li.className = 'p-4 flex items-center cursor-pointer hover:bg-gray-100 border-b';
                li.dataset.studentId = user.id;
                
                const unreadBadge = user.unread_count > 0 ? 
                    `<span class="bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center ml-2">
                        ${user.unread_count}
                    </span>` : '';
                
                li.innerHTML = `
                    <div class="w-10 h-10 bg-gray-300 rounded-full mr-3 flex items-center justify-center">
                        ${user.firstName.charAt(0)}${user.lastName.charAt(0)}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center">
                            <p class="font-semibold">${user.firstName} ${user.lastName}</p>
                            ${unreadBadge}
                        </div>
                        <p class="text-sm text-gray-500 truncate">
                            ${user.last_message || 'No messages yet'}
                        </p>
                    </div>
                    <span class="text-sm text-gray-400">
                        ${user.last_message_time ? formatTime(user.last_message_time) : ''}
                    </span>
                `;
                li.addEventListener('click', () => openChat(user.id, `${user.firstName} ${user.lastName}`));
                userList.appendChild(li);
            });
        }

        function formatTime(timestamp) {
            return moment(timestamp).calendar(null, {
                sameDay: 'h:mm A',
                lastDay: '[Yesterday]',
                lastWeek: 'dddd',
                sameElse: 'MM/DD/YYYY'
            });
        }

        function openChat(studentId, studentName) {
            document.getElementById('chat-header').innerText = `Chat with ${studentName}`;
            document.getElementById('student_id').value = studentId;
            
            const chatMessagesDiv = document.getElementById('chatMessages');
            chatMessagesDiv.innerHTML = '<div class="text-center py-4 text-gray-500">Loading messages...</div>';

            // Unsubscribe from previous channel if any
            if (channel) {
                pusher.unsubscribe(channel.name);
            }

            // Subscribe to new channel
            channel = pusher.subscribe(`chat_${studentId}`);
            channel.bind('new-message', handleNewMessage);

            // Load message history
            fetch(`?fetchMessages=true&student_id=${studentId}`)
                .then(response => response.json())
                .then(messages => {
                    chatMessagesDiv.innerHTML = '';
                    if (messages.length === 0) {
                        chatMessagesDiv.innerHTML = '<div class="text-center py-4 text-gray-500">No messages yet. Start the conversation!</div>';
                        return;
                    }
                    messages.forEach(msg => appendMessage(msg));
                    scrollToBottom();
                    markMessagesAsRead(studentId);
                    updateUserListUnreadStatus(studentId);
                })
                .catch(error => {
                    console.error('Error fetching messages:', error);
                    chatMessagesDiv.innerHTML = '<div class="text-center py-4 text-red-500">Error loading messages</div>';
                });
        }

        function markMessagesAsRead(studentId) {
            fetch('mark_messages_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `student_id=${studentId}&mhp_id=${mhpId}`
            }).catch(error => console.error('Error marking messages as read:', error));
        }

        function updateUserListUnreadStatus(studentId) {
            const userItems = document.querySelectorAll('#userList li');
            userItems.forEach(item => {
                if (item.dataset.studentId == studentId) {
                    const badge = item.querySelector('.bg-red-500');
                    if (badge) badge.remove();
                }
            });
        }

        function handleNewMessage(data) {
            const currentStudentId = document.getElementById('student_id').value;
            
            if (currentStudentId && parseInt(currentStudentId) === parseInt(data.student_id)) {
                appendMessage(data);
                scrollToBottom();
                markMessagesAsRead(data.student_id);
            } else {
                // Show notification for new message in other chats
                if (data.sender_type === 'student' && Notification.permission === 'granted') {
                    new Notification(`New message from ${data.student_name || 'Student'}`, {
                        body: data.message.length > 50 ? data.message.substring(0, 50) + '...' : data.message
                    });
                }
                
                // Update unread count in user list
                const userItem = document.querySelector(`#userList li[data-student-id="${data.student_id}"]`);
                if (userItem) {
                    let badge = userItem.querySelector('.bg-red-500');
                    if (badge) {
                        badge.textContent = parseInt(badge.textContent) + 1;
                    } else {
                        userItem.querySelector('.font-semibold').insertAdjacentHTML('afterend', 
                            `<span class="bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center ml-2">1</span>`);
                    }
                }
            }
        }

        function appendMessage(msg) {
            const chatMessagesDiv = document.getElementById('chatMessages');
            const isSent = msg.sender_type === 'MHP';
            
            const messageDiv = document.createElement('div');
            messageDiv.className = isSent ? 'message-sent' : 'message-received';
            
            messageDiv.innerHTML = `
                <div class="px-4 py-2">
                    <div class="break-words">${msg.message}</div>
                    <div class="text-xs mt-1 ${isSent ? 'text-blue-100' : 'text-gray-500'}">
                        ${formatTime(msg.timestamp)}
                    </div>
                </div>
            `;
            
            chatMessagesDiv.appendChild(messageDiv);
        }

        function scrollToBottom() {
            const chatMessagesDiv = document.getElementById('chatMessages');
            chatMessagesDiv.scrollTop = chatMessagesDiv.scrollHeight;
        }

        function sendMessage() {
            if (isSending) return;
            
            const messageInput = document.getElementById('message_input');
            const message = messageInput.value.trim();
            const studentId = document.getElementById('student_id').value;

            if (!message || !studentId) return;

            isSending = true;
            messageInput.disabled = true;
            
            fetch('messages_handler_mhp.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `receiver_id=${studentId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                } else {
                    throw new Error(data.error || 'Failed to send message');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message);
            })
            .finally(() => {
                isSending = false;
                messageInput.disabled = false;
                messageInput.focus();
            });
        }

        // Send message on Enter key
        document.getElementById('message_input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    </script>
</body>
</html>