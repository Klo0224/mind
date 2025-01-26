
      const signUpButton = document.getElementById('signUp');
      const signInButton = document.getElementById('signIn');
      const container = document.getElementById('container');

      signUpButton.addEventListener('click', () => {
        container.classList.add("right-panel-active");
      });

      signInButton.addEventListener('click', () => {
        container.classList.remove("right-panel-active");
      });

      function saveLicenseUpload() {
    const frontFile = document.getElementById('license_front');
    const backFile = document.getElementById('license_back');
    const modalFrontFile = document.getElementById('modal_license_front');
    const modalBackFile = document.getElementById('modal_license_back');

    // Transfer files from modal inputs to form inputs
    if (modalFrontFile.files.length) {
        frontFile.files = modalFrontFile.files;
    }
    if (modalBackFile.files.length) {
        backFile.files = modalBackFile.files;
    }

    // Update trigger input text
    updateTriggerText();

    // Close the modal
    closeLicenseModal();
}

      function openLicenseModal() {
    document.getElementById('licenseModal').style.display = 'block';
}

function closeLicenseModal() {
    document.getElementById('licenseModal').style.display = 'none';
}

function handleLicenseUpload(input, side) {
    if (input.files && input.files[0]) {
        // Update the display name in the modal
        const fileName = input.files[0].name;
        document.getElementById(side + '_file_name').textContent = fileName;
        
        // Update the hidden form input
        const formInput = document.getElementById('license_' + side);
        formInput.files = input.files;
        
        // Update the trigger input text
        updateTriggerText();
    }
}