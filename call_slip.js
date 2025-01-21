// script.js
document.addEventListener('DOMContentLoaded', function() {
    // Function to fetch student data
    async function fetchStudentData(studentId) {
        try {
            const response = await fetch(`get_callslip.php?studentId=${studentId}`);
            const data = await response.json();
            
            if (data.success) {
                // Fill the form with student data
                document.getElementById('studentName').value = data.data.studentName;
                document.getElementById('courseYear').value = `${data.data.course} - ${data.data.year}`;
                document.getElementById('unit').value = data.data.department;
                document.getElementById('appointmentTime').value = data.data.appointmentTime;
            } else {
                alert('Student not found');
            }
        } catch (error) {
            console.error('Error fetching student data:', error);
        }
    }

    // Function to save call slip
    async function saveCallSlip(formData) {
        try {
            const response = await fetch('get_callslip.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            const result = await response.json();
            if (result.success) {
                alert('Call slip saved successfully');
                // Optionally trigger print here
                window.print();
            } else {
                alert('Error saving call slip');
            }
        } catch (error) {
            console.error('Error saving call slip:', error);
        }
    }

    // Form submission handler
    document.getElementById('callSlipForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = {
            studentId: new URLSearchParams(window.location.search).get('studentId'),
            date: document.getElementById('date').value,
            unit: document.getElementById('unit').value,
            appointmentTime: document.getElementById('appointmentTime').value,
            allowStudent: document.getElementById('allowStudent').checked,
            rescheduleReason: document.getElementById('rescheduleReason').value
        };
        
        saveCallSlip(formData);
    });

    // Initialize form with student data from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const studentId = urlParams.get('studentId');
    if (studentId) {
        fetchStudentData(studentId);
    }
});