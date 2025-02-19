window.onload = function() {
    // Get the menu items by their IDs
    const dashboardItem = document.getElementById('dashboardItem');
    const chatItem = document.getElementById('chatItem');

    // Get the current page URL
    const currentPage = window.location.pathname;
    console.log('Current Page:', currentPage); // Debug log to check current page path

    // Check if the user is on the "mhp_chat.php" or "mhp_dashboard.php" page and apply the 'clicked' class
    if (currentPage.includes('mhp_chat.php') && chatItem) {
        chatItem.classList.add('clicked');
        console.log('Chat marked as clicked'); // Debug log
    } else if (currentPage.includes('mhp_dashboard.php') && dashboardItem) {
        dashboardItem.classList.add('clicked');
        console.log('Dashboard marked as clicked'); // Debug log
    }

    // Add event listeners for page switching only if the elements exist
    if (dashboardItem) {
        dashboardItem.addEventListener('click', function() {
            window.location.href = 'mhp_dashboard.php';
        });
    }

    if (chatItem) {
        chatItem.addEventListener('click', function() {
            window.location.href = 'mhp_chat.php';
        });
    }
};