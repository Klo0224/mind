window.onload = function() {
    // Get the menu items by their IDs
    const gracefulThreadItem = document.getElementById('gracefulThreadItem');
    const mentalWellnessItem = document.getElementById('MentalWellness');
    const ProfileItem = document.getElementById('ProfileItem');
    const chatItem = document.getElementById('chatItem');

    // Get the current page URL
    const currentPage = window.location.pathname;
    console.log('Current Page:', currentPage); // Debug log to check current page path

    // Check if the user is on the "MentalWellness", "Graceful-thread", or "Profile" page and apply the 'clicked' class
    if (currentPage.includes('mhp.php') && mentalWellnessItem) {
        mentalWellnessItem.classList.add('clicked');
        console.log('MentalWellness marked as clicked'); // Debug log
    } else if (currentPage.includes('gracefulThread.php') && gracefulThreadItem) {
        gracefulThreadItem.classList.add('clicked');
        console.log('Graceful-thread marked as clicked'); // Debug log
    } else if (currentPage.includes('vacant.php') && ProfileItem) {
        ProfileItem.classList.add('clicked');
        console.log('Profile marked as clicked'); // Debug log
    } else if (currentPage.includes('MHProfile.php') && chatItem) {
        chatItem.classList.add('clicked');
        console.log('Profile marked as clicked'); // Debug log
    } 
    
    else {
        console.log('Page not matched or user not logged in'); // Debug log
    }

    // Add event listeners for page switching only if the elements exist
    if (gracefulThreadItem) {
        gracefulThreadItem.addEventListener('click', function() {
            // Navigate to the "Graceful-thread" page when clicked
            window.location.href = 'gracefulThread.php';
        });
    }

    if (mentalWellnessItem) {
        mentalWellnessItem.addEventListener('click', function() {
            // Navigate to the "MentalWellness" page when clicked
            window.location.href = 'mhp.php';
        });
    }

    if (ProfileItem) {
        ProfileItem.addEventListener('click', function() {
            // Navigate to the "Profile" page when clicked
            window.location.href = 'vacant.php';
        });
    }

    if (chatItem) {
        chatItem.addEventListener('click', function() {
            // Navigate to the "Profile" page when clicked
            window.location.href = 'MHProfile.php';
        });
    }
};
