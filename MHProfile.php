<?php
// Allow requests from any origin (this allows localhost to make requests)
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
    <!-- Your CSS/JS includes here -->
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
            <a href="#" class="menu-item active flex items-center px-6 py-3 text-gray-600" data-section="appointments" id="MentalWellness">
                <img src="images/Vector.svg" alt="Mental Wellness Companion" class="w-5 h-5">
                <span class="menu-text ml-3">Mental Wellness Companion</span>
            </a>
            <a href="#" class="menu-item flex items-center px-6 py-3 text-gray-600" data-section="profile" id="ProfileItem">
                <img src="images/Vector.svg" alt="Profile" class="w-5 h-5">
                <span class="menu-text ml-3">Profile</span>
            </a>
            <a href="#" class="menu-item flex items-center px-6 py-3 text-gray-600" data-section="chat" id="chatItem">
                <img src="images/Vector.svg" alt="Chat" class="w-5 h-5">
                <span class="menu-text ml-3">Chat</span>
            </a>
        </nav>

        <!-- User Profile / Logout at Bottom -->
        <div class="absolute bottom-0 w-full border-t">
            <a href="#" class="menu-item flex items-center px-6 py-4 text-gray-600">
                <!-- Example placeholders for your user data -->
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
    
        // Chat Window
        echo '
        <div id="chatWindow" class="hidden fixed right-0 top-0 bottom-0 left-60 bg-gray-100 z-50">
            <div class="max-w-3xl mx-auto h-full flex flex-col p-4">
                <div class="flex items-center mb-4">
                    <button onclick="closeChat()" class="text-[#1cabe3] font-semibold mr-4">&larr; Back</button>
                    <h2 id="chatHeader" class="text-xl font-bold text-[#1cabe3]"></h2>
                </div>
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
        </div>';
    } else {
        echo '<div class="text-center text-gray-700 mt-10">No mental health professionals found.</div>';
    }
    
    $conn->close();
    ?>

    <script>
        // ------------------------------
        // Pusher Setup
        // ------------------------------
        const pusher = new Pusher('561b69476711bf54f56f', {
            cluster: 'ap1',
            encrypted: true
        });

        // The student's ID is passed from PHP:
        const userId = <?php echo json_encode($student_id ?? null); ?>;

        let currentChannel = null;
        let currentMhpId   = null;

        // Subscribe to the student's channel for receiving messages
        function initializePusher(studentId) {
            console.log('Initializing Pusher for studentId:', studentId);

            // Unsubscribe from previous channel if exists
            if (currentChannel) {
                pusher.unsubscribe(currentChannel.name);
            }

            // Subscribe to a channel named "chat_<studentId>"
            const channelName = `chat_${studentId}`;
            console.log('Subscribing to channel:', channelName);

            currentChannel = pusher.subscribe(channelName);

            // Listen for 'new-message' event
            currentChannel.bind('new-message', function(data) {
                console.log('Received Pusher message:', data);
                // data might look like { student_id: X, mhp_id: Y, message: "...", timestamp: "..." }

                // Check if it matches the current MHP chat
                if ((data.student_id == userId && data.mhp_id == currentMhpId) ||
                    (data.student_id == currentMhpId && data.mhp_id == userId)) {

                    // If the sender_type was 'MHP', message is 'received'
                    // If the sender_type was 'student', message is 'sent'
                    // But the real data from Pusher might just say "student_id" or "mhp_id"
                    // Let's assume if data.mhp_id == currentMhpId, then the MHP is sending
                    const isMhpSending = (data.mhp_id == currentMhpId && data.student_id == userId);
                    const messageType  = isMhpSending ? 'received' : 'sent';

                    const messageElement = createMessageElement(data.message, messageType);
                    const chatMessages   = document.getElementById('chatMessages');
                    chatMessages.appendChild(messageElement);
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            });
        }

        // Open the chat window
        function openChat(mhpId, mhpName) {
            currentMhpId = mhpId;
            document.getElementById('listingView').classList.add('opacity-0');
            document.getElementById('chatWindow').classList.remove('hidden');
            document.getElementById('chatHeader').textContent = `Chat with ${mhpName}`;

            // Load chat history from this file (the same file or separate messages_handler)
            loadChatHistory(mhpId);

            // Initialize Pusher with the student's ID
            initializePusher(userId);
        }

        // Close the chat window
        function closeChat() {
            document.getElementById('listingView').classList.remove('opacity-0');
            document.getElementById('chatWindow').classList.add('hidden');
            currentMhpId = null;
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

            fetch('', {  // Sending request to the same PHP file
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                console.log('Response from server:', data);
                if (data.success) {
                    // Append the sent message to chat immediately
                    const chatMessages = document.getElementById('chatMessages');
                    const messageElement = createMessageElement(message, 'sent');
                    chatMessages.appendChild(messageElement);
                    chatMessages.scrollTop = chatMessages.scrollHeight;  // Scroll to latest message
                    loadChatHistory(currentMhpId);  // Reload messages
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

            fetch('', { // Same .php file
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const chatMessages = document.getElementById('chatMessages');
                    chatMessages.innerHTML = '';

                    data.messages.forEach(msg => {
                        // If msg.sender_type is 'student', we mark it as 'sent'
                        // If msg.sender_type is 'MHP', we mark it as 'received'
                        const bubbleType = (msg.sender_type === 'student') ? 'sent' : 'received';
                        const messageElement = createMessageElement(msg.message, bubbleType);
                        chatMessages.appendChild(messageElement);
                    });
                    chatMessages.scrollTop = chatMessages.scrollHeight;
                }
            })
            .catch(error => console.error('Error loading chat history:', error));
        }


        // Section switching, etc.
        document.addEventListener('DOMContentLoaded', function() {
            // If you have code to handle sections, put it here...
            
            // Add "Enter key" event for sending message
            const messageInput = document.getElementById('messageInput');
            if (messageInput) {
                messageInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        sendMessage();
                    }
                });
            }
        });
    </script>
    <script src="sidebarnav.js"></script>
</body>
</html>