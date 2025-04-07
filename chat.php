<?php
session_start();
require_once("auth.php");
require_once("config.php");

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

// Get current user info
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT id, firstName, lastName, profile_image FROM Users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$current_user = $result->fetch_assoc();

// Get all MHPs for the sidebar
$mhp_stmt = $conn->prepare("SELECT id, fname, lname, department, profile_image FROM MHP");
$mhp_stmt->execute();
$mhp_result = $mhp_stmt->get_result();
$mhps = $mhp_result->fetch_all(MYSQLI_ASSOC);

// Get recent conversations with unread counts
$conv_stmt = $conn->prepare("
    SELECT 
        m.id as mhp_id,
        m.fname as mhp_fname,
        m.lname as mhp_lname,
        m.department as mhp_department,
        m.profile_image as mhp_profile,
        MAX(msg.timestamp) as last_message_time,
        (SELECT COUNT(*) FROM Messages WHERE student_id = ? AND mhp_id = m.id AND is_read = 0) as unread_count
    FROM MHP m
    LEFT JOIN Messages msg ON msg.mhp_id = m.id AND msg.student_id = ?
    GROUP BY m.id
    ORDER BY last_message_time DESC
");
$conv_stmt->bind_param("ii", $current_user['id'], $current_user['id']);
$conv_stmt->execute();
$conv_result = $conv_stmt->get_result();
$conversations = $conv_result->fetch_all(MYSQLI_ASSOC);

// Get initial chat if requested
$initial_chat_id = isset($_GET['chat_with']) ? (int)$_GET['chat_with'] : null;
$initial_chat = null;

if ($initial_chat_id) {
    foreach ($mhps as $mhp) {
        if ($mhp['id'] == $initial_chat_id) {
            $initial_chat = $mhp;
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat | Mental Wellness Companion</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.14.0/css/all.min.css'>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://js.pusher.com/8.0/pusher.min.js"></script>
    <style>
        body { 
            background-color: #f4f7f6;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar {
            width: 350px;
            min-width: 350px;
            background: white;
            border-right: 1px solid #e2e8f0;
        }
        .chat-container {
            display: flex;
            height: calc(100vh - 80px);
        }
        .chat-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: #f8fafc;
        }
        .conversation-item {
            transition: all 0.2s;
            cursor: pointer;
            padding: 12px 16px;
            border-bottom: 1px solid #f1f5f9;
        }
        .conversation-item:hover {
            background-color: #f8fafc;
        }
        .conversation-item.active {
            background-color: #e0f2fe;
        }
        .message-sent {
            align-self: flex-end;
            background-color: #3b82f6;
            color: white;
            border-radius: 18px 18px 0 18px;
            max-width: 70%;
            margin-bottom: 8px;
            padding: 10px 16px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        .message-received {
            align-self: flex-start;
            background-color: white;
            color: #1f2937;
            border-radius: 18px 18px 18px 0;
            max-width: 70%;
            margin-bottom: 8px;
            padding: 10px 16px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }
        .unread-badge {
            background-color: #ef4444;
            color: white;
            border-radius: 9999px;
            font-size: 12px;
            padding: 2px 8px;
            font-weight: 600;
        }
        .chat-header {
            background-color: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px;
            display: flex;
            align-items: center;
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background-color: #f8fafc;
        }
        .chat-input {
            background-color: white;
            border-top: 1px solid #e5e7eb;
            padding: 16px;
        }
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: #64748b;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
        .mhp-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Main Sidebar Navigation -->
    <div id="sidebar" class="sidebar fixed top-0 left-0 h-screen bg-white shadow-lg z-10">
        <div class="flex items-center p-6 border-b">
            <div class="w-15 h-10 rounded-full flex items-center justify-center">
                <a href="#"><img src="images/Mindsoothe(2).svg" alt="Mindsoothe Logo"></a>
            </div>
        </div>
        
        <!-- Menu Items -->
        <nav class="mt-6">
            <a href="dashboard.php" class="menu-item flex items-center px-6 py-3">
                <img src="images/gracefulThread.svg" alt="Graceful Thread" class="w-5 h-5">
                <span class="ml-3">Graceful Thread</span>
            </a>
            <a href="companion.php" class="menu-item flex items-center px-6 py-3 text-gray-600">
                <img src="images/Vector.svg" alt="Mental Wellness Companion" class="w-5 h-5">
                <span class="ml-3">Mental Wellness Companion</span>
            </a>
            <a href="profile.php" class="menu-item flex items-center px-6 py-3 text-gray-600">
                <img src="images/Vector.svg" alt="Profile" class="w-5 h-5">
                <span class="ml-3">Profile</span>
            </a>
            <a href="chat.php" class="menu-item active flex items-center px-6 py-3 text-gray-600">
                <img src="images/Vector.svg" alt="Chat" class="w-5 h-5">
                <span class="ml-3">Chat</span>
            </a>
        </nav>

        <!-- User Profile / Logout -->
        <div class="absolute bottom-0 w-full border-t">
            <a href="profile.php" class="menu-item flex items-center px-6 py-4 text-gray-600">
                <img src="<?= htmlspecialchars($current_user['profile_image'] ?? 'images/default_profile.jpg') ?>" 
                     alt="Profile Image" class="w-8 h-8 rounded-full">
                <span class="ml-3"><?= htmlspecialchars($current_user['firstName'] . ' ' . $current_user['lastName']) ?></span>
            </a>
            <a href="logout.php" class="menu-item flex items-center px-6 py-4 text-red-500 hover:text-red-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="ml-3">Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content min-h-screen p-8 ml-[350px]">
        <div class="bg-white rounded-lg shadow-sm h-full">
            <div class="chat-container">
                <!-- Left Sidebar - Conversations -->
                <div class="sidebar">
                    <div class="p-4 border-b">
                        <h2 class="text-xl font-semibold">Messages</h2>
                    </div>
                    
                    <div class="overflow-y-auto h-[calc(100vh-180px)]">
                        <!-- Recent Conversations -->
                        <?php foreach ($conversations as $conv): ?>
                            <div class="conversation-item flex items-center <?= ($initial_chat && $initial_chat['id'] == $conv['mhp_id']) ? 'active' : '' ?>" 
                                 data-mhp-id="<?= $conv['mhp_id'] ?>"
                                 onclick="openChat(<?= $conv['mhp_id'] ?>, '<?= htmlspecialchars($conv['mhp_fname'] . ' ' . $conv['mhp_lname']) ?>')">
                                <img src="<?= htmlspecialchars($conv['mhp_profile']) ?>" 
                                     alt="<?= htmlspecialchars($conv['mhp_fname']) ?>" 
                                     class="user-avatar mr-3">
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium truncate"><?= htmlspecialchars($conv['mhp_fname'] . ' ' . $conv['mhp_lname']) ?></div>
                                    <div class="text-sm text-gray-500 truncate"><?= htmlspecialchars($conv['mhp_department']) ?></div>
                                </div>
                                <?php if ($conv['unread_count'] > 0): ?>
                                    <span class="unread-badge"><?= $conv['unread_count'] ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Available MHPs -->
                    <div class="p-4 border-t">
                        <h2 class="text-lg font-semibold mb-3">Available Professionals</h2>
                        <div class="space-y-2">
                            <?php foreach ($mhps as $mhp): ?>
                                <div class="flex items-center p-2 hover:bg-gray-50 rounded cursor-pointer"
                                     onclick="openChat(<?= $mhp['id'] ?>, '<?= htmlspecialchars($mhp['fname'] . ' ' . $mhp['lname']) ?>')">
                                    <img src="<?= htmlspecialchars($mhp['profile_image']) ?>" 
                                         alt="<?= htmlspecialchars($mhp['fname']) ?>" 
                                         class="w-8 h-8 rounded-full mr-3">
                                    <div class="min-w-0">
                                        <div class="font-medium truncate"><?= htmlspecialchars($mhp['fname'] . ' ' . $mhp['lname']) ?></div>
                                        <div class="text-xs text-gray-500 truncate"><?= htmlspecialchars($mhp['department']) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Chat Area -->
                <div class="chat-main">
                    <?php if ($initial_chat): ?>
                        <!-- Active Chat -->
                        <div id="activeChat" class="h-full flex flex-col">
                            <div class="chat-header">
                                <img src="<?= htmlspecialchars($initial_chat['profile_image']) ?>" 
                                     alt="<?= htmlspecialchars($initial_chat['fname']) ?>" 
                                     class="mhp-avatar mr-3">
                                <div>
                                    <h2 id="chatHeader" class="text-lg font-bold"><?= htmlspecialchars($initial_chat['fname'] . ' ' . $initial_chat['lname']) ?></h2>
                                    <div class="text-sm text-gray-500"><?= htmlspecialchars($initial_chat['department']) ?></div>
                                </div>
                            </div>
                            
                            <div id="chatMessages" class="chat-messages">
                                <!-- Messages will be loaded here -->
                            </div>
                            
                            <div class="chat-input">
                                <div class="flex items-center">
                                    <input type="text" id="messageInput" placeholder="Type a message..." 
                                        class="flex-grow border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1cabe3]">
                                    <button onclick="sendMessage()" 
                                        class="ml-2 bg-[#1cabe3] text-white p-2 rounded-full hover:bg-[#158bb8] transition duration-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Empty State -->
                        <div id="emptyChatState" class="empty-state">
                            <svg class="w-16 h-16 mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <h3 class="text-xl font-medium mb-2">Select a conversation</h3>
                            <p class="max-w-md px-4">Choose a mental health professional to start chatting or view your existing conversations.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pusher Setup
        const pusher = new Pusher('561b69476711bf54f56f', {
            cluster: 'ap1',
            encrypted: true
        });

        // Current user and chat state
        const currentUserId = <?= $current_user['id'] ?>;
        let currentChannel = null;
        let currentMhpId = <?= $initial_chat ? $initial_chat['id'] : 'null' ?>;
        let currentMhpName = '<?= $initial_chat ? htmlspecialchars($initial_chat['fname'] . ' ' . $initial_chat['lname']) : '' ?>';

        // Initialize Pusher for the current user
        function initializePusher() {
            // Unsubscribe from previous channel if exists
            if (currentChannel) {
                pusher.unsubscribe(currentChannel.name);
            }

            // Subscribe to user's channel
            const channelName = `chat_${currentUserId}`;
            currentChannel = pusher.subscribe(channelName);

            // Listen for new messages
            currentChannel.bind('new-message', function(data) {
                console.log('New message received:', data);
                
                // If this message is for the currently open chat
                if (currentMhpId && data.mhp_id == currentMhpId) {
                    appendMessage(data.message, 'received');
                    
                    // Mark as read
                    markMessagesAsRead(currentMhpId);
                } else {
                    // Update unread count in sidebar
                    updateUnreadCount(data.mhp_id);
                    
                    // Show notification
                    if (Notification.permission === 'granted') {
                        new Notification(`New message from ${data.mhp_name}`, {
                            body: data.message.length > 50 ? data.message.substring(0, 50) + '...' : data.message
                        });
                    }
                }
            });
        }

        // Open chat with a specific MHP
        function openChat(mhpId, mhpName) {
            currentMhpId = mhpId;
            currentMhpName = mhpName;
            
            // Update URL without reloading
            history.pushState(null, null, `chat.php?chat_with=${mhpId}`);
            
            // Update UI
            document.getElementById('emptyChatState')?.classList.add('hidden');
            
            // Create active chat if it doesn't exist
            if (!document.getElementById('activeChat')) {
                const chatMain = document.querySelector('.chat-main');
                chatMain.innerHTML = `
                    <div id="activeChat" class="h-full flex flex-col">
                        <div class="chat-header">
                            <img src="${document.querySelector(`.conversation-item[data-mhp-id="${mhpId}"] img`).src}" 
                                 alt="${mhpName}" 
                                 class="mhp-avatar mr-3">
                            <div>
                                <h2 id="chatHeader" class="text-lg font-bold">${mhpName}</h2>
                                <div class="text-sm text-gray-500">${document.querySelector(`.conversation-item[data-mhp-id="${mhpId}"] .text-sm`).textContent}</div>
                            </div>
                        </div>
                        
                        <div id="chatMessages" class="chat-messages"></div>
                        
                        <div class="chat-input">
                            <div class="flex items-center">
                                <input type="text" id="messageInput" placeholder="Type a message..." 
                                    class="flex-grow border border-gray-300 rounded-full px-4 py-2 focus:outline-none focus:ring-2 focus:ring-[#1cabe3]">
                                <button onclick="sendMessage()" 
                                    class="ml-2 bg-[#1cabe3] text-white p-2 rounded-full hover:bg-[#158bb8] transition duration-300">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                
                // Add event listener to new input
                document.getElementById('messageInput').addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendMessage();
                    }
                });
            } else {
                // Update existing chat header
                document.getElementById('chatHeader').textContent = mhpName;
            }
            
            // Highlight active conversation in sidebar
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
                if (item.dataset.mhpId == mhpId) {
                    item.classList.add('active');
                }
            });
            
            // Load chat history
            loadChatHistory(mhpId);
            
            // Mark messages as read
            markMessagesAsRead(mhpId);
            
            // Initialize Pusher if not already done
            if (!currentChannel) {
                initializePusher();
            }
        }

        // Load chat history
        function loadChatHistory(mhpId) {
            fetch('get_messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mhp_id=${mhpId}`
            })
            .then(response => response.json())
            .then(data => {
                const chatMessages = document.getElementById('chatMessages');
                chatMessages.innerHTML = '';
                
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        const messageType = msg.sender_type === 'student' ? 'sent' : 'received';
                        appendMessage(msg.message, messageType);
                    });
                    
                    // Scroll to bottom
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                } else {
                    chatMessages.innerHTML = `
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                </svg>
                                <p>No messages yet</p>
                                <p class="text-sm">Send your first message to start the conversation</p>
                            </div>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading chat history:', error);
            });
        }

        // Append a message to the chat
        function appendMessage(message, type) {
            const chatMessages = document.getElementById('chatMessages');
            
            // If empty state is shown, remove it
            if (chatMessages.children.length === 1 && chatMessages.children[0].classList.contains('flex')) {
                chatMessages.innerHTML = '';
            }
            
            const timestamp = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `mb-3 ${type === 'sent' ? 'flex justify-end' : 'flex justify-start'}`;

            const messageContainer = document.createElement('div');
            messageContainer.className = `max-w-[75%] flex flex-col ${type === 'sent' ? 'items-end' : 'items-start'}`;

            const messageContent = document.createElement('div');
            messageContent.className = type === 'sent' ? 'message-sent' : 'message-received';
            messageContent.textContent = message;

            const timeStampElem = document.createElement('div');
            timeStampElem.className = 'text-xs text-gray-500 mt-1';
            timeStampElem.textContent = timestamp;

            messageContainer.appendChild(messageContent);
            messageContainer.appendChild(timeStampElem);
            messageDiv.appendChild(messageContainer);
            
            chatMessages.appendChild(messageDiv);
            
            // Scroll to bottom
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        // Send a message
        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();

            if (!message || !currentMhpId) return;

            messageInput.value = ''; // Clear input

            fetch('send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mhp_id=${currentMhpId}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Append the sent message immediately
                    appendMessage(message, 'sent');
                } else {
                    console.error('Failed to send message:', data.error);
                    alert('Failed to send message. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
            });
        }

        // Mark messages as read
        function markMessagesAsRead(mhpId) {
            fetch('mark_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mhp_id=${mhpId}`
            })
            .then(() => {
                // Update UI
                const badge = document.querySelector(`.conversation-item[data-mhp-id="${mhpId}"] .unread-badge`);
                if (badge) {
                    badge.remove();
                }
            })
            .catch(error => console.error('Error marking messages as read:', error));
        }

        // Update unread count in sidebar
        function updateUnreadCount(mhpId) {
            const convItem = document.querySelector(`.conversation-item[data-mhp-id="${mhpId}"]`);
            if (convItem) {
                let badge = convItem.querySelector('.unread-badge');
                if (badge) {
                    badge.textContent = parseInt(badge.textContent) + 1;
                } else {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'unread-badge';
                    newBadge.textContent = '1';
                    convItem.appendChild(newBadge);
                }
            }
        }

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Request notification permission
            if ('Notification' in window && Notification.permission !== 'granted') {
                Notification.requestPermission();
            }
            
            // Handle Enter key for sending messages
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendMessage();
                    }
                });
            }
            
            // Load initial chat if specified
            if (currentMhpId) {
                initializePusher();
                loadChatHistory(currentMhpId);
            }
        });
    </script>
</body>
</html>