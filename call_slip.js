document.addEventListener('DOMContentLoaded', function() {
    // Format date function
    function formatDate(date) {
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const day = date.getDate();
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        return `${month} ${day}, ${year}`;
    }

    // Format time function
    function formatTime(timeString) {
        const [hours, minutes] = timeString.split(':');
        const period = hours >= 12 ? 'PM' : 'AM';
        const formattedHours = hours % 12 || 12;
        return `${formattedHours}:${minutes} ${period}`;
    }

    // Validation function for call slip
    function validateCallSlip() {
        // Check appointment time
        const appointmentTime = document.getElementById('appointmentTime').value;
        if (!appointmentTime) {
            alert('Please fill in the appointment time.');
            return false;
        }

        // Check allow student
        // const allowStudentCheckbox = document.getElementById('allowStudent');
        // if (!allowStudentCheckbox.checked) {
        //     alert('Student must be allowed for counseling.');
        //     return false;
        // }

        // Check reasons for counseling
        // const selectedReasons = document.querySelectorAll('input[name="reasons[]"]:checked');
        // if (selectedReasons.length === 0) {
        //     alert('Please select at least one reason for counseling.');
        //     return false;
        // }

        // // Check "Others" specification if selected
        // const othersCheckbox = document.getElementById('others');
        // const othersSpecify = document.getElementById('othersSpecify');
        // if (othersCheckbox.checked && !othersSpecify.value.trim()) {
        //     alert('Please specify details for "Others" reason.');
        //     othersSpecify.focus();
        //     return false;
        // }

        return true;
    }

    // Save call slip function
    async function saveCallSlip(formData) {
        try {
            const response = await fetch('save_callslip.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const result = await response.json();
            
            if (result.success) {
                alert('Call slip saved successfully');
                return true;
            } else {
                alert(result.message || 'Error saving call slip');
                return false;
            }
        } catch (error) {
            console.error('Error saving call slip:', error);
            alert('Error saving call slip. Please try again.');
            return false;
        }
    }

    // Print call slip function
    function printCallSlip() {
        if (!validateCallSlip()) {
            return false;
        }
        window.print();
        return true;
    }

    // Set current date when page loads
    const currentDate = new Date();
    document.getElementById('date').value = formatDate(currentDate);

    // Handle print button
    const printButton = document.getElementById('printCallSlip');
    printButton.addEventListener('click', printCallSlip);

    // Handle form submission
    const callSlipForm = document.getElementById('callSlipForm');
    callSlipForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Collect selected reasons
        const selectedReasons = Array.from(document.querySelectorAll('input[name="reasons[]"]:checked'))
            .map(checkbox => {
                if (checkbox.value === 'Others') {
                    return `Others: ${document.getElementById('othersSpecify').value}`;
                }
                return checkbox.value;
            });

        // Prepare form data
        const formData = {
            userId: new URLSearchParams(window.location.search).get('userId'),
            date: document.getElementById('date').value,
            unit: document.getElementById('unit').value,
            studentName: document.getElementById('studentName').value,
            courseYear: document.getElementById('courseYear').value,
            appointmentTime: document.getElementById('appointmentTime').value,
            // allowStudent: document.getElementById('allowStudent').checked,
            reschedule: document.getElementById('reschedule').checked,
            rescheduleReason: document.getElementById('rescheduleReason').value,
            reasons: selectedReasons,
            othersSpecify: document.getElementById('othersSpecify').value,
            createdAt: new Date().toISOString()
        };
        
        // Validate and save
        if (validateCallSlip()) {
            const saved = await saveCallSlip(formData);
            if (saved) {
                printCallSlip();
            }
        }
    });

    // Handle "Others" checkbox
    const othersCheckbox = document.getElementById('others');
    const othersSpecify = document.getElementById('othersSpecify');
    othersCheckbox.addEventListener('change', function() {
        othersSpecify.disabled = !this.checked;
        if (this.checked) {
            othersSpecify.focus();
        } else {
            othersSpecify.value = '';
        }
    });

    // Handle reschedule checkbox
    const rescheduleCheckbox = document.getElementById('reschedule');
    const rescheduleReason = document.getElementById('rescheduleReason');
    
    rescheduleCheckbox.addEventListener('change', function() {
        rescheduleReason.disabled = !this.checked;
        if (!this.checked) {
            rescheduleReason.value = '';
        }
    });

    // Handle allow student checkbox
    // const allowStudentCheckbox = document.getElementById('allowStudent');
    // allowStudentCheckbox.addEventListener('change', function() {
    //     if (this.checked) {
    //         rescheduleCheckbox.checked = false;
    //         rescheduleReason.disabled = true;
    //         rescheduleReason.value = '';
    //     }
    // });

    // Optional: Fetch student data if user ID is provided
    const urlParams = new URLSearchParams(window.location.search);
    const userId = urlParams.get('userId');
    if (userId) {
        async function fetchStudentData(userId) {
            try {
                const response = await fetch(`get_callslip.php?userId=${userId}`);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                const data = await response.json();
                
                if (data.success) {
                    // Fill the form with student data
                    document.getElementById('studentName').value = data.data.studentName;
                    document.getElementById('courseYear').value = `${data.data.course} - ${data.data.year}`;
                    document.getElementById('unit').value = data.data.department;
                    if (data.data.appointmentTime) {
                        document.getElementById('appointmentTime').value = formatTime(data.data.appointmentTime);
                    }
                } else {
                    alert('Student not found');
                }
            } catch (error) {
                console.error('Error fetching student data:', error);
                alert('Error loading student information. Please try again.');
            }
        }
        fetchStudentData(userId);
    }
});