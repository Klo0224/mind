<?php
error_log("Debug: Reached line 421 in MHProfile.php");

// Allow requests from any origin
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle OPTIONS preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

include("auth.php"); // For session + authentication
include("config.php"); // DB connection

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $student_id = (int)$row['id'];
        $sender_type = 'student';
    } else {
        echo json_encode(["success" => false, "error" => "Student not found", "debug_email" => $email]);
        exit;
    }
} else {
    echo json_encode(["success" => false, "error" => "Email not found in session"]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_message') {
        $mhp_id  = $_POST['mhp_id'] ?? 0;
        $message = trim($_POST['message'] ?? '');

        if (!$mhp_id || $message === '') {
            echo json_encode(["success" => false, "error" => "Missing required parameters"]);
            exit;
        }

        $stmt = $conn->prepare("SELECT id FROM MHP WHERE id = ?");
        $stmt->bind_param("i", $mhp_id);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            echo json_encode(["success" => false, "error" => "MHP not found"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO Messages (student_id, mhp_id, sender_type, receiver_type, message) VALUES (?, ?, 'student', 'MHP', ?)");
        $stmt->bind_param("iis", $student_id, $mhp_id, $message);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Message sent", "data" => ["message_id" => $conn->insert_id, "timestamp" => date('Y-m-d H:i:s')]]);
        } else {
            echo json_encode(["success" => false, "error" => "Message send failed"]);
        }

    } elseif ($action === 'get_history') {
        $mhp_id = $_POST['mhp_id'] ?? 0;
        if (!$mhp_id) {
            echo json_encode(["success" => false, "error" => "Missing mhp_id"]);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT message, sender_type, receiver_type, timestamp 
            FROM Messages 
            WHERE (student_id = ? AND mhp_id = ?) 
            OR (student_id = ? AND mhp_id = ?)
            ORDER BY timestamp ASC
        ");
        $stmt->bind_param("iiii", $student_id, $mhp_id, $mhp_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $messages = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(["success" => true, "messages" => $messages]);
    } elseif ($action === 'get_all_conversations') {
        // Get all MHPs the student has chatted with
        $stmt = $conn->prepare("
            SELECT DISTINCT m.id, m.fname, m.lname, m.department, m.profile_image,
                   (SELECT message FROM Messages 
                    WHERE (student_id = ? AND mhp_id = m.id) OR (student_id = m.id AND mhp_id = ?)
                    ORDER BY timestamp DESC LIMIT 1) as last_message,
                   (SELECT timestamp FROM Messages 
                    WHERE (student_id = ? AND mhp_id = m.id) OR (student_id = m.id AND mhp_id = ?)
                    ORDER BY timestamp DESC LIMIT 1) as last_message_time
            FROM MHP m
            JOIN Messages msg ON (msg.student_id = ? AND msg.mhp_id = m.id) OR (msg.student_id = m.id AND msg.mhp_id = ?)
            WHERE m.id IN (SELECT DISTINCT mhp_id FROM Messages WHERE student_id = ?)
               OR m.id IN (SELECT DISTINCT student_id FROM Messages WHERE mhp_id = ?)
        ");
        $stmt->bind_param("iiiiiiii", $student_id, $student_id, $student_id, $student_id, $student_id, $student_id, $student_id, $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $conversations = $result->fetch_all(MYSQLI_ASSOC);

        echo json_encode(["success" => true, "conversations" => $conversations]);
    } else {
        echo json_encode(["success" => false, "error" => "Invalid action"]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Mental Wellness Companion</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css'>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <style>
        body { background-color: #f4f7f6; }
        .dashboard-card { transition: transform 0.3s, box-shadow 0.3s; }
        .dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .sidebar { transition: width 0.3s; width: 256px; min-width: 256px; }
        .sidebar.collapsed { width: 80px; min-width: 80px; }
        .main-content { transition: margin-left 0.3s; margin-left: 256px; }
        .main-content.expanded { margin-left: 80px; }
        .menu-item { transition: all 0.3s; }
        .menu-item:hover { background-color: #f3f4f6; }
        .menu-item.active { color: #1cabe3; background-color: #eff6ff; border-right: 4px solid #1cabe3; }
        .menu-text { transition: opacity 0.3s; }
        .sidebar.collapsed .menu-text { opacity: 0; display: none; }
        .section { display: none; }
        .section.active { display: block; }
        .content-section { display: none; }
        .content-section.active { display: block; }
        #mhpList {
            max-height: calc(100vh - 60px);
            overflow-y: auto;
        }
        #mhpList::-webkit-scrollbar {
            width: 6px;
        }
        #mhpList::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        #mhpList::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }
        #mhpList::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }
        .mhp-list-item.active {
            background-color: #eff6ff;
            border-right: 3px solid #1cabe3;
        }
        .conversation-item {
            transition: background-color 0.2s;
        }
        .conversation-item:hover {
            background-color: #f8fafc;
        }
        .conversation-item.active {
            background-color: #eff6ff;
        }
        .unread-badge {
            display: none;
            width: 8px;
            height: 8px;
            background-color: #ef4444;
            border-radius: 50%;
            margin-left: auto;
        }
        .unread-badge.active {
            display: block;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- SIDEBAR -->
    <div id="sidebar" class="sidebar fixed top-0 left-0 h-screen bg-white shadow-lg z-10">
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
            <!-- Add Chat Button
            <a href="#" class="menu-item flex items-center px-6 py-3 text-gray-600" id="chatButton">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                </svg>
                <span class="menu-text ml-3">My Chats</span>
                <span id="unreadCountBadge" class="unread-badge"></span>
            </a> -->
            <a href="#" class="menu-item flex items-center px-6 py-3 text-gray-600" data-section="profile" id="ProfileItem">
                <img src="images/Vector.svg" alt="Profile" class="w-5 h-5">
                <span class="menu-text ml-3">Profile</span>
            </a>
            <a href="#" class="menu-item active flex items-center px-6 py-3 text-gray-600" data-section="chat" id="ChatItem">
            <img src="images/profile.svg" alt="Mental Wellness Companion" class="w-4 h-4">
                <span class="menu-text ml-3">Chat</span>
            </a>
        </nav>

        <!-- User Profile / Logout at Bottom -->
        <div class="absolute bottom-0 w-full border-t">
            <a href="#" class="menu-item flex items-center px-6 py-4 text-gray-600">
                <img src="<?php echo htmlspecialchars($profileImage ?? 'images/default_profile.jpg'); ?>" 
                     alt="Profile Image" class="w-8 h-8 rounded-full">
                <span class="menu-text ml-3"><?php echo htmlspecialchars($fullName ?? 'Student User'); ?></span>
            </a>
            <a href="logout.php" class="menu-item flex items-center px-6 py-4 text-red-500 hover:text-red-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1
                             a3 3 0 01-3 3H6a3 3 0 01-3-3V7
                             a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="menu-text ml-3">Logout</span>
            </a>
        </div>
    </div>

    <?php
    // Display the list of MHPs from your MHP table
    $sql = "SELECT id, fname, lname, department, profile_image FROM MHP";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo '<div id="listingView">';  // Wrapper for the listing view
        echo '<h1 class="text-2xl font-bold text-center text-gray-800 mt-6 mb-8">
                <span class="text-[#1cabe3]">Mental</span> <span class="text-[#000000]">Wellness</span> Companion
              </h1>';
    
        echo '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 px-4 md:px-20 justify-items-center ml-60">';
    
        while ($row = $result->fetch_assoc()) {
            $mhpId   = htmlspecialchars($row["id"]);
            $mhpName = htmlspecialchars($row["fname"] . ' ' . $row["lname"]);
            echo '
            <div class="bg-white rounded-lg shadow-lg w-80 overflow-hidden transition-transform transform hover:scale-105">
                <div class="p-6">
                    <div class="flex items-center mb-4">
                        <img class="w-14 h-14 rounded-full object-cover mr-4" 
                             src="' . htmlspecialchars($row["profile_image"]) . '" 
                             alt="Profile Picture of ' . htmlspecialchars($row["fname"]) . '" />
                        <div>
                            <div class="text-lg font-bold text-gray-800">' . $mhpName . '</div>
                            <div class="text-sm text-gray-500">' . htmlspecialchars($row["department"]) . '</div>
                        </div>
                    </div>
                    <div class="flex justify-center mt-4">
                        <button class="bg-white text-[#1cabe3] border-2 border-[#1cabe3] px-4 py-1 rounded hover:bg-[#1cabe3] hover:text-white transition duration-300 ease-in-out"
                            onclick="openChat(\'' . $mhpId . '\', \'' . $mhpName . '\')">
                            Start Chat
                        </button>
                    </div>
                </div>
            </div>';
        }
        echo '</div>'; // End of grid
        echo '</div>'; // End of listingView
    
        // Chat Window with MHP sidebar
        echo '
<div id="chatWindow" class="hidden fixed right-0 top-0 bottom-0 left-60 bg-gray-100 z-50">
    <div class="flex h-full">
        <!-- MHP List Sidebar -->
        <div class="w-64 bg-white border-r border-gray-200 overflow-y-auto">
            <div class="p-4 border-b border-gray-200">
                <h3 class="font-semibold text-gray-700">Available Professionals</h3>
            </div>
            <div id="mhpList" class="divide-y divide-gray-100">';
                
                // Re-query the MHP list for the sidebar
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()) {
                    echo '
                    <div class="p-3 hover:bg-gray-50 cursor-pointer flex items-center mhp-list-item" 
                         onclick="openChat(\''.$row['id'].'\', \''.$row['fname'].' '.$row['lname'].'\')"
                         data-mhp-id="'.$row['id'].'">
                        <img class="w-10 h-10 rounded-full object-cover mr-3" 
                             src="'.htmlspecialchars($row['profile_image']).'" 
                             alt="'.htmlspecialchars($row['fname']).'">
                        <div>
                            <div class="font-medium text-gray-800">'.htmlspecialchars($row['fname'].' '.$row['lname']).'</div>
                            <div class="text-xs text-gray-500">'.htmlspecialchars($row['department']).'</div>
                        </div>
                    </div>';
                }
                
                echo '
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="flex-1 flex flex-col p-4">
            <!-- Chat Header with MHP Profile -->
            <div id="chatHeader" class="bg-white rounded-lg shadow-sm mb-4 p-4 flex items-center">
                <button onclick="closeChat()" class="text-[#1cabe3] font-semibold mr-4">&larr;</button>
                <div class="flex items-center">
                    <img id="chatMhpImage" class="w-10 h-10 rounded-full object-cover mr-3" src="" alt="MHP Profile">
                    <div>
                        <h2 id="chatMhpName" class="text-lg font-bold text-gray-800"></h2>
                        <p id="chatMhpDepartment" class="text-xs text-gray-500"></p>
                    </div>
                </div>
                <div class="ml-auto flex items-center">
                    <span id="chatStatus" class="text-xs text-green-500 mr-2">Online</span>
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                </div>
            </div>

            <!-- Chat Messages Area -->
            <div class="bg-white p-4 shadow-md rounded-lg flex flex-col flex-grow min-h-0">
                <div id="chatMessages" class="flex-grow overflow-y-auto mb-4 p-4 bg-gray-50 rounded min-h-0">
                    <!-- Messages will be dynamically loaded here -->
                </div>
                <div class="flex items-center">
                    <input type="text" id="messageInput" placeholder="Type a message..." 
                        class="flex-grow border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1cabe3]">
                    <button onclick="sendMessage()" 
                        class="bg-[#1cabe3] text-white px-4 py-2 rounded-r-lg hover:bg-[#158bb8] transition duration-300">
                        Send
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>';

        // Conversations Window (shows all chat threads)
        echo '
<div id="conversationsWindow" class="hidden fixed right-0 top-0 bottom-0 left-60 bg-gray-100 z-50">
    <div class="flex h-full">
        <!-- Conversations List Sidebar -->
        <div class="w-64 bg-white border-r border-gray-200 overflow-y-auto">
            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="font-semibold text-gray-700">My Conversations</h3>
                <button onclick="closeConversations()" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="conversationsList" class="divide-y divide-gray-100">
                <!-- Conversations will be loaded here -->
            </div>
        </div>
        
        <!-- Chat Area -->
        <div class="flex-1 flex flex-col p-4">
            <!-- Chat Header with MHP Profile -->
            <div id="conversationChatHeader" class="bg-white rounded-lg shadow-sm mb-4 p-4 flex items-center">
                <div class="flex items-center">
                    <img id="conversationMhpImage" class="w-10 h-10 rounded-full object-cover mr-3" src="" alt="MHP Profile">
                    <div>
                        <h2 id="conversationMhpName" class="text-lg font-bold text-gray-800"></h2>
                        <p id="conversationMhpDepartment" class="text-xs text-gray-500"></p>
                    </div>
                </div>
                <div class="ml-auto flex items-center">
                    <span id="conversationChatStatus" class="text-xs text-green-500 mr-2">Online</span>
                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                </div>
            </div>

            <!-- Chat Messages Area -->
            <div class="bg-white p-4 shadow-md rounded-lg flex flex-col flex-grow min-h-0">
                <div id="conversationChatMessages" class="flex-grow overflow-y-auto mb-4 p-4 bg-gray-50 rounded min-h-0">
                    <!-- Messages will be dynamically loaded here -->
                </div>
                <div class="flex items-center">
                    <input type="text" id="conversationMessageInput" placeholder="Type a message..." 
                        class="flex-grow border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1cabe3]">
                    <button onclick="sendConversationMessage()" 
                        class="bg-[#1cabe3] text-white px-4 py-2 rounded-r-lg hover:bg-[#158bb8] transition duration-300">
                        Send
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>';
    } else {
        echo '<div class="text-center text-gray-700 mt-10">No mental health professionals found.</div>';
    }
    
    $conn->close();
    ?>

    <script>
        // Pusher Setup
        const pusher = new Pusher('561b69476711bf54f56f', {
            cluster: 'ap1',
            encrypted: true
        });

        const userId = <?php echo json_encode($student_id ?? null); ?>;
        let currentChannel = null;
        let currentMhpId   = null;
        let unreadMessages = {};

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            // Add "Enter key" event for sending message
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendMessage();
                    }
                });
            }

            // Add "Enter key" event for conversation message input
            const conversationMessageInput = document.getElementById('conversationMessageInput');
            if (conversationMessageInput) {
                conversationMessageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendConversationMessage();
                    }
                });
            }

            // Chat button click handler
            document.getElementById('chatButton').addEventListener('click', function(e) {
                e.preventDefault();
                openConversations();
            });

            // Initialize Pusher with the student's ID
            initializePusher(userId);
        });

        // Subscribe to the student's channel for receiving messages
        function initializePusher(studentId) {
            console.log('Initializing Pusher for studentId:', studentId);

            if (currentChannel) {
                pusher.unsubscribe(currentChannel.name);
            }

            const channelName = `chat_${studentId}`;
            console.log('Subscribing to channel:', channelName);

            currentChannel = pusher.subscribe(channelName);

            currentChannel.bind('new-message', function(data) {
                console.log('Received Pusher message:', data);
                
                // Check if this message is in the currently open chat
                if ((data.student_id == userId && data.mhp_id == currentMhpId) ||
                    (data.student_id == currentMhpId && data.mhp_id == userId)) {

                    const isMhpSending = (data.mhp_id == currentMhpId && data.student_id == userId);
                    const messageType  = isMhpSending ? 'received' : 'sent';

                    const messageElement = createMessageElement(data.message, messageType);
                    
                    // Add to either the chat window or conversations window
                    if (document.getElementById('chatWindow').classList.contains('hidden')) {
                        const chatMessages = document.getElementById('conversationChatMessages');
                        chatMessages.appendChild(messageElement);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    } else {
                        const chatMessages = document.getElementById('chatMessages');
                        chatMessages.appendChild(messageElement);
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    }
                } else {
                    // This is a message in another conversation
                    if (!unreadMessages[data.mhp_id]) {
                        unreadMessages[data.mhp_id] = 0;
                    }
                    unreadMessages[data.mhp_id]++;
                    updateUnreadBadge();
                }
            });
        }

        // Open the conversations window
        function openConversations() {
            document.getElementById('listingView').classList.add('opacity-0');
            document.getElementById('conversationsWindow').classList.remove('hidden');
            
            // Load all conversations
            loadAllConversations();
        }

        // Close the conversations window
        function closeConversations() {
            document.getElementById('conversationsWindow').classList.add('hidden');
            document.getElementById('listingView').classList.remove('opacity-0');
        }

        // Load all conversations for the student
        function loadAllConversations() {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_all_conversations'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const conversationsList = document.getElementById('conversationsList');
                    conversationsList.innerHTML = '';
                    
                    if (data.conversations.length === 0) {
                        conversationsList.innerHTML = '<div class="p-4 text-center text-gray-500">No conversations yet</div>';
                        return;
                    }
                    
                    data.conversations.forEach(conversation => {
                        const conversationElement = document.createElement('div');
                        conversationElement.className = 'p-3 hover:bg-gray-50 cursor-pointer flex items-center conversation-item';
                        conversationElement.setAttribute('data-mhp-id', conversation.id);
                        conversationElement.innerHTML = `
                            <img class="w-10 h-10 rounded-full object-cover mr-3" 
                                 src="${conversation.profile_image}" 
                                 alt="${conversation.fname}">
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-800 truncate">${conversation.fname} ${conversation.lname}</div>
                                <div class="text-xs text-gray-500 truncate">${conversation.last_message || 'No messages yet'}</div>
                                <div class="text-xs text-gray-400">${formatTime(conversation.last_message_time)}</div>
                            </div>
                            <div class="unread-badge ${unreadMessages[conversation.id] > 0 ? 'active' : ''}"></div>
                        `;
                        
                        conversationElement.addEventListener('click', () => {
                            // Mark as read
                            unreadMessages[conversation.id] = 0;
                            updateUnreadBadge();
                            
                            // Open the chat
                            openConversationChat(conversation.id, `${conversation.fname} ${conversation.lname}`);
                            
                            // Highlight active conversation
                            document.querySelectorAll('.conversation-item').forEach(item => {
                                item.classList.remove('active');
                            });
                            conversationElement.classList.add('active');
                        });
                        
                        conversationsList.appendChild(conversationElement);
                    });
                } else {
                    console.error('Failed to load conversations:', data.error);
                }
            })
            .catch(error => {
                console.error('Error loading conversations:', error);
            });
        }

        // Open a specific conversation chat
        function openConversationChat(mhpId, mhpName) {
            currentMhpId = mhpId;
            
            // Fetch MHP details to populate the header
            fetchMhpDetails(mhpId).then(mhpData => {
                document.getElementById('conversationMhpName').textContent = mhpData.fname + ' ' + mhpData.lname;
                document.getElementById('conversationMhpDepartment').textContent = mhpData.department;
                document.getElementById('conversationMhpImage').src = mhpData.profile_image;
            });
            
            // Load chat history
            loadConversationChatHistory(mhpId);
        }

        // Load chat history for conversation
        function loadConversationChatHistory(mhpId) {
            const formData = new FormData();
            formData.append('action', 'get_history');
            formData.append('mhp_id', mhpId);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const chatMessages = document.getElementById('conversationChatMessages');
                    chatMessages.innerHTML = '';

                    data.messages.forEach(msg => {
                        const bubbleType = (msg.sender_type === 'student') ? 'sent' : 'received';
                        const messageElement = createMessageElement(msg.message, bubbleType);
                        chatMessages.appendChild(messageElement);
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            })
            .catch(error => console.error('Error loading chat history:', error));
        }

        // Send message from conversation chat
        function sendConversationMessage() {
            const input   = document.getElementById('conversationMessageInput');
            const message = input.value.trim();

            if (!message || !currentMhpId) return;

            input.value = '';  // Clear input field

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('mhp_id', currentMhpId);
            formData.append('message', message);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response from server:', data);
                if (data.success) {
                    const chatMessages = document.getElementById('conversationChatMessages');
                    const messageElement = createMessageElement(message, 'sent');
                    chatMessages.appendChild(messageElement);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                    loadConversationChatHistory(currentMhpId);
                } else {
                    console.error('Failed to send message:', data.error);
                    alert('Failed to send message. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
            });
        }

        // Open chat from listing view
        function openChat(mhpId, mhpName) {
            currentMhpId = mhpId;
            document.getElementById('listingView').classList.add('opacity-0');
            document.getElementById('chatWindow').classList.remove('hidden');

            // Highlight active MHP in the sidebar
            document.querySelectorAll('.mhp-list-item').forEach(item => {
                item.classList.remove('active');
                if (item.getAttribute('data-mhp-id') == mhpId) {
                    item.classList.add('active');
                }
            });

            // Fetch MHP details to populate the header
            fetchMhpDetails(mhpId).then(mhpData => {
                document.getElementById('chatMhpName').textContent = mhpData.fname + ' ' + mhpData.lname;
                document.getElementById('chatMhpDepartment').textContent = mhpData.department;
                document.getElementById('chatMhpImage').src = mhpData.profile_image;
            });
            
            // Load chat history
            loadChatHistory(mhpId);

            // Initialize Pusher with the student's ID
            initializePusher(userId);
        }

        function closeChat() {
            document.getElementById('chatWindow').classList.add('hidden');
            document.getElementById('listingView').classList.remove('opacity-0');
            currentMhpId = null;
        }

        // Fetch MHP details for the chat header
        function fetchMhpDetails(mhpId) {
            return fetch(`get_mhp_details.php?id=${mhpId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        return data.mhp;
                    } else {
                        console.error('Failed to fetch MHP details:', data.error);
                        return {
                            fname: 'MHP',
                            lname: '',
                            department: '',
                            profile_image: 'images/default_profile.jpg'
                        };
                    }
                })
                .catch(error => {
                    console.error('Error fetching MHP details:', error);
                    return {
                        fname: 'MHP',
                        lname: '',
                        department: '',
                        profile_image: 'images/default_profile.jpg'
                    };
                });
        }

        // Send a message
        function sendMessage() {
            const input   = document.getElementById('messageInput');
            const message = input.value.trim();

            if (!message || !currentMhpId) return;

            input.value = '';  // Clear input field

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('mhp_id', currentMhpId);
            formData.append('message', message);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response from server:', data);
                if (data.success) {
                    const chatMessages = document.getElementById('chatMessages');
                    const messageElement = createMessageElement(message, 'sent');
                    chatMessages.appendChild(messageElement);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                    loadChatHistory(currentMhpId);
                } else {
                    console.error('Failed to send message:', data.error);
                    alert('Failed to send message. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
            });
        }

        // Create a message bubble
        function createMessageElement(message, type) {
            const div = document.createElement('div');
            const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            div.className = `mb-4 ${type === 'sent' ? 'flex justify-end' : 'flex justify-start'}`;

            const messageContainer = document.createElement('div');
            messageContainer.className = `max-w-[70%] flex flex-col ${type === 'sent' ? 'items-end' : 'items-start'}`;

            const messageContent = document.createElement('div');
            messageContent.className = `${
                type === 'sent' ? 'bg-gray-200 text-gray-800' : 'bg-[#1cabe3] text-white'
            } px-4 py-2 rounded-lg break-words`;
            messageContent.textContent = message;

            const timeStampElem = document.createElement('div');
            timeStampElem.className = 'text-xs text-gray-500 mt-1';
            timeStampElem.textContent = timestamp;

            messageContainer.appendChild(messageContent);
            messageContainer.appendChild(timeStampElem);
            div.appendChild(messageContainer);

            return div;
        }

        // Load chat history
        function loadChatHistory(mhpId) {
            const formData = new FormData();
            formData.append('action', 'get_history');
            formData.append('mhp_id', mhpId);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const chatMessages = document.getElementById('chatMessages');
                    chatMessages.innerHTML = '';

                    data.messages.forEach(msg => {
                        const bubbleType = (msg.sender_type === 'student') ? 'sent' : 'received';
                        const messageElement = createMessageElement(msg.message, bubbleType);
                        chatMessages.appendChild(messageElement);
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            })
            .catch(error => console.error('Error loading chat history:', error));
        }

        // Format time for conversation list
        function formatTime(timestamp) {
            if (!timestamp) return '';
            const date = new Date(timestamp);
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }

        // Update the unread badge count
        function updateUnreadBadge() {
            const totalUnread = Object.values(unreadMessages).reduce((sum, count) => sum + count, 0);
            const badge = document.getElementById('unreadCountBadge');
            
            if (totalUnread > 0) {
                badge.classList.add('active');
                badge.textContent = totalUnread > 9 ? '9+' : totalUnread;
            } else {
                badge.classList.remove('active');
                badge.textContent = '';
            }
        }
    </script>
    <script src="sidebarnav.js"></script>
</body>
</html>