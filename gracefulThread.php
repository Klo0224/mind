<?php
        include("auth.php");

        // Text moderation function
        function moderateText($text) {
        // List of words/phrases to be flagged
        $badWords = [
            'puta', 'bwisit', 'gago', 'tanga', 'bobo', 'salot', 'pekeng', 'bakla', 'gaga', 'putangina',
            'ang kapal mo', 'mamatay ka na', 'ugok', 'wag ka makialam', 'katangahan', 'loko', 'sira ulo', 
            'haliparot', 'putangina mo', 'tangina mo', 'chismis', 'mukhang pera', 'patay gutom', 
            'mang-uuto', 'kasama sa buhay', 'hipokrito', 'unano', 'aswang', 'mangkukulam', 'mayabang', 
            'malandi', 'hudas', 'maasim ang mukha', 'sugapa', 'maka-appeal', 'bulok', 'tanga ka', 
            'fuck', 'shit', 'asshole', 'bitch', 'damn', 'cunt', 'motherfucker', 'bastard', 'dick', 
            'pussy', 'nigga', 'whore', 'slut', 'cocksucker', 'retard', 'crackhead', 'twat', 'fag', 
            'kike', 'chink', 'gook', 'spic', 'raghead', 'sandnigger', 'dirty Jew', 'wog', 'kaffir', 
            'nazi', 'towelhead', 'beaner', 'polack', 'dago', 'yid', 'wop', 'cholo', 'gypo', 'prick', 
            'cunt', 'whore', 'pikey', 'inbred', 'hillbilly', 'redneck', 'mamatay'
        ];
        
        
        // Replace flagged words with '[redacted]' and set the detection flag
        foreach ($badWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i'; // Case insensitive word boundary matching
            if (preg_match($pattern, $text)) {
                $detected = true; // Set flag if bad word is found
                $text = preg_replace($pattern, '[redacted]', $text);
            }
        }

        return $text;
    }

    // Handle post submission
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['postText'])) {
        $content = mysqli_real_escape_string($conn, $_POST['postText']);
        
        // Moderate the post content
        $moderatedContent = moderateText($content);

        if (!empty($moderatedContent)) {
            // Insert the moderated post into the Graceful_Thread table
            $insertPost = "INSERT INTO GracefulThread (user_id, content) VALUES ('$userId', '$moderatedContent')";
            
            if (mysqli_query($conn, $insertPost)) {
                // Redirect to the same page to prevent form resubmission on page refresh
                header("Location: " . $_SERVER['PHP_SELF']);
                exit(); // Ensure no further code is executed
            } else {
                // Display error alert with the MySQL error
                echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
            }
        } else {
            // Display alert for empty content
            echo "<script>alert('Post content cannot be empty!');</script>";
        }
    }
        // Handle like/unlike actions
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['like_button'])) {
            $postId = $_POST['post_id'];
        
            // Check if the user has already liked the post
            $checkLikeQuery = mysqli_query($conn, "SELECT * FROM post_likes WHERE user_id='$userId' AND post_id='$postId'");
        
            if (mysqli_num_rows($checkLikeQuery) > 0) {
                // User has already liked the post, so unlike it (delete the record)
                $deleteLikeQuery = "DELETE FROM post_likes WHERE user_id='$userId' AND post_id='$postId'";
                mysqli_query($conn, $deleteLikeQuery);
        } else {
                // User hasn't liked the post, so like it (insert a new record)
                $insertLikeQuery = "INSERT INTO post_likes (user_id, post_id) VALUES ('$userId', '$postId')";
                mysqli_query($conn, $insertLikeQuery);
            }

            // Redirect to prevent form resubmission on page refresh
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Graceful Thread</title>
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
<body class="bg-gray-100">
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
            <!-- <a href="#" class="menu-item active flex items-center px-6 py-3" data-section="dashboard" id="gracefulThreadItem">
                <img src="images/gracefulThread.svg" alt="Graceful Thread" class="w-5 h-5">
                <span class="menu-text ml-3">Graceful Thread</span>
            </a> -->
            <a href="#" class="menu-item active flex items-center px-6 py-3 text-gray-600" data-section="dashboard" id="gracefulThreadItem">
                <img src="images/gracefulThread.svg" alt="Graceful Thread" class="w-5 h-5">
                <span class="menu-text ml-3">Graceful Thread</span>
            </a>
            <a href="#" class="menu-item flex items-center px-6 py-3 text-gray-600" data-section="appointments" id="MentalWellness">
                <img src="images/Vector.svg" alt="Mental Wellness Companion" class="w-5 h-5">
                <span class="menu-text ml-3">Mental Wellness Companion</span>
            </a>
            <a href="#" class="menu-item flex items-center px-6 py-3 text-gray-600" data-section="profile" id="ProfileItem">
                <img src="images/Vector.svg" alt="Mental Wellness Companion" class="w-5 h-5">
                <span class="menu-text ml-3">Profile</span>
            </a>
            
        </nav>

        <!-- User Profile and Logout Section -->
        <div class="absolute bottom-0 w-full border-t">
            <!-- User Profile -->
            <a href="#" class="menu-item flex items-center px-6 py-4 text-gray-600">
                <span class="menu-text ml-8"><?php echo htmlspecialchars($fullName); ?></span>
            </a>

            <!-- Logout -->
            <a href="landingpage.html" class="menu-item flex items-center px-6 py-4 text-red-500 hover:text-red-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="menu-text ml-3">Logout</span>
            </a>  
        </div>
    </div>

    <!-- Main Content Area -->
<div class="main-content flex flex-col p-6 bg-gray-100">
    <!-- Post Input -->
    <form method="POST" action="" class="flex items-center mb-6">
        <input
            type="text"
            id="postText"
            name="postText"
            placeholder="What are you grateful for?"
            class="flex-grow p-4 border rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        />
        <button
            type="submit"
            id="postButton"
            class="ml-4 px-6 py-3 bg-blue-500 text-white font-semibold rounded-lg shadow hover:bg-blue-600 transition"
        >
            Post
        </button>
    </form>

    <!-- Posts Timeline -->
    <div class="posts space-y-4" id="timeline">
        <?php
            // Fetch posts from Graceful_Thread
            $fetchPosts = mysqli_query($conn, "SELECT GT.id, GT.content, GT.created_at, UA.firstName, UA.lastName, UA.profile_image 
                                               FROM GracefulThread GT 
                                               INNER JOIN Users UA ON GT.user_id = UA.id 
                                               ORDER BY GT.created_at DESC");

            if (mysqli_num_rows($fetchPosts) > 0) {
                while ($post = mysqli_fetch_assoc($fetchPosts)) {
                    $postId = $post['id']; // Get the post ID for likes
                    $postUser = $post['firstName'] . ' ' . $post['lastName'];
                    $postImage = $post['profile_image'] ? $post['profile_image'] : 'images/blueuser.svg';
                    $postContent = htmlspecialchars($post['content']);
                    $postTime = date('F j, Y, g:i a', strtotime($post['created_at']));

                    // Check if the current user liked this post
                    $checkLikeQuery = mysqli_query($conn, "SELECT * FROM post_likes WHERE user_id='$userId' AND post_id='$postId'");
                    $isLiked = mysqli_num_rows($checkLikeQuery) > 0;

                    // Determine the correct Font Awesome icon class for the like button
                    $iconClass = $isLiked ? 'fas fa-heart text-red-500' : 'far fa-heart text-gray-400';

                    // Count the number of likes for the post
                    $likeCountQuery = mysqli_query($conn, "SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id='$postId'");
                    $likeCountResult = mysqli_fetch_assoc($likeCountQuery);
                    $likeCount = $likeCountResult['like_count'];
        ?>

        <div class="post bg-white rounded-lg shadow p-4">
            <!-- Post Header -->
            <div class="post-header flex items-center mb-4">
                <img
                    src="<?php echo htmlspecialchars($postImage); ?>"
                    alt="User Avatar"
                    class="w-12 h-12 rounded-full border"
                />
                <div class="ml-4">
                    <span class="block font-semibold text-gray-800"><?php echo htmlspecialchars($postUser); ?></span>
                    <span class="block text-sm text-gray-500"><?php echo htmlspecialchars($postTime); ?></span>
                </div>
            </div>

            <!-- Post Content -->
            <div class="post-content mb-4">
                <p class="text-gray-700"><?php echo $postContent; ?></p>
            </div>

            <!-- Post Actions -->
            <div class="post-actions flex items-center">
                <form method="POST" action="" class="flex items-center">
                    <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                    <!-- Like Button -->
                    <button type="submit" name="like_button" class="focus:outline-none">
                        <i class="<?php echo $iconClass; ?> text-lg"></i>
                    </button>
                </form>
                <p class="ml-2 text-sm text-gray-600"><?php echo $likeCount; ?> people like this post</p>
            </div>
        </div>

        <?php
                }
            } else {
                echo "<p class='text-gray-500 text-center'>No posts yet. Be the first to post!</p>";
            }
        ?>
    </div>
</div>


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
    <script src="sidebarnav.js"></script>
</body>
</html>