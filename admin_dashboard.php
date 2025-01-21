<!-- index.php -->
<?php
require_once 'mhp_acc_mhp.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $result = addCounselor($_POST['fname'], $_POST['lname'], $_POST['email'], $_POST['department']);

    if ($result === "error_email_exists") {
        echo "<script>alert('The email already exists. Please use a different email.');</script>";
    } elseif ($result === "success") {
        echo "<script>alert('Counselor added successfully.'); window.location.href='admin_dashboard.php';</script>";
    } else {
        echo "<script>alert('Error: Could not add counselor. Please try again.');</script>";
    }
}
$MHP = getAllCounselors();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                addCounselor($_POST['fname'],$_POST['lname'], $_POST['email'], $_POST['department']);
                break;
            case 'update':
                updateCounselor($_POST['id'], $_POST['fname'],$_POST['lname'], $_POST['email'], $_POST['department']);
                break;
            case 'delete':
                deleteCounselor($_POST['id']);
                break;
        }
        header('Location: admin_dashboard.php');
        exit;
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guidance Counselor Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold">Guidance Counselor Management</h1>
    <form method="POST" action="admin.php">
        <button type="submit" 
                class="inline-block mt-6 bg-white text-[#1cabe3] font-bold border-2 border-[#1cabe3] py-3 px-6 rounded-lg hover:bg-[#1cabe3] hover:text-white transition duration-300">
            Logout
        </button>
    </form>
</div>
        
        <!-- Add New Counselor Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Add New Counselor</h2>
            <form id="addForm" method="POST" class="space-y-4">

                <input type="hidden" name="action" value="add">
                <div>
                    <label class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" name="fname" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" name="lname" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Department</label>
                    <input type="text" name="department" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <button type="submit" class="inline-block mt-6 bg-white text-[#1cabe3] font-bold border-2 border-[#1cabe3] py-3 px-6 rounded-lg hover:bg-[#1cabe3] hover:text-white transition duration-300">Add Counselor</button>
            </form>
        </div>

        <div class="mb-4 relative w-1/3">
    <input type="text" 
           id="counselorSearch" 
           placeholder="Search counselors by name, email, or department..." 
           class="w-full px-4 py-2 pl-10 rounded-md border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 18a8 8 0 100-16 8 8 0 000 16zm6.32-1.9l4.24 4.24"></path>
    </svg>
</div>



        
        <!-- Counselors List -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Counselors List</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">First Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="counselorsTableBody">
    <?php foreach ($MHP as $MHP): ?>
    <tr class="counselor-row" 
        data-fname="<?php echo htmlspecialchars(strtolower($MHP['fname'])); ?>"
        data-lname="<?php echo htmlspecialchars(strtolower($MHP['lname'])); ?>"
        data-email="<?php echo htmlspecialchars(strtolower($MHP['email'])); ?>"
        data-department="<?php echo htmlspecialchars(strtolower($MHP['department'])); ?>">
        <td class="px-6 py-4"><?php echo htmlspecialchars($MHP['fname']); ?></td>
        <td class="px-6 py-4"><?php echo htmlspecialchars($MHP['lname']); ?></td>
        <td class="px-6 py-4"><?php echo htmlspecialchars($MHP['email']); ?></td>
        <td class="px-6 py-4"><?php echo htmlspecialchars($MHP['department']); ?></td>
        <td class="px-6 py-4">
            <button onclick="editCounselor(<?php echo $MHP['id']; ?>)" 
                    class="text-blue-600 hover:text-blue-900 mr-2">Edit</button>
            <button onclick="deleteCounselor(<?php echo $MHP['id']; ?>)" 
                    class="text-red-600 hover:text-red-900">Delete</button>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-semibold mb-4">Edit Counselor</h3>
            <form id="editForm" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">First Name</label>
                    <input type="text" name="fname" id="edit_fname" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Last Name</label>
                    <input type="text" name="lname" id="edit_lname" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="edit_email" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Department</label>
                    <input type="text" name="department" id="edit_department" required 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeEditModal()" 
                            class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600">Cancel</button>
                    <button type="submit" 
                            class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function editCounselor(id) {
        $.get('get_mhp.php', {id: id}, function(data) {
            const MHP = JSON.parse(data);
            $('#edit_id').val(MHP.id);
            $('#edit_fname').val(MHP.fname);
            $('#edit_lname').val(MHP.lname);
            $('#edit_email').val(MHP.email);
            $('#edit_department').val(MHP.department);
            $('#editModal').removeClass('hidden');
        });
    }

    function closeEditModal() {
        $('#editModal').addClass('hidden');
    }

    function deleteCounselor(id) {
        if (confirm('Are you sure you want to delete this counselor?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Add this to your existing JavaScript
$(document).ready(function() {
    // Search functionality
    $('#counselorSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        
        if (searchTerm === '') {
            // If search is empty, show all rows
            $('.counselor-row').show();
            return;
        }
        
        // Filter through each row
        $('.counselor-row').each(function() {
            const $row = $(this);
            const fname = $row.data('fname');
            const lname = $row.data('lname');
            const email = $row.data('email');
            const department = $row.data('department');
            
            // Check if any field contains the search term
            const matches = fname.includes(searchTerm) ||
                          lname.includes(searchTerm) ||
                          email.includes(searchTerm) ||
                          department.includes(searchTerm);
            
            // Show/hide row based on match
            $row.toggle(matches);
        });
        
        // Show a message if no results found
        const visibleRows = $('.counselor-row:visible').length;
        const noResultsRow = $('#noResultsRow');
        
        if (visibleRows === 0) {
            if (noResultsRow.length === 0) {
                const colspan = $('.counselor-row:first td').length;
                $('#counselorsTableBody').append(`
                    <tr id="noResultsRow">
                        <td colspan="${colspan}" class="px-6 py-4 text-center text-gray-500">
                            No counselors found matching "${searchTerm}"
                        </td>
                    </tr>
                `);
            } else {
                noResultsRow.show();
            }
        } else {
            noResultsRow.hide();
        }
    });
});
    </script>
</body>
</html>