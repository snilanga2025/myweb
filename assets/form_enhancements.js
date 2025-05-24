document.addEventListener('DOMContentLoaded', function() {
    // Function to handle image preview
    function setupImagePreview(fileInputId, previewElementId) {
        const fileInput = document.getElementById(fileInputId);
        const previewElement = document.getElementById(previewElementId);

        if (fileInput && previewElement) {
            fileInput.addEventListener('change', function(event) {
                const file = event.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        // Remove any existing image/placeholder text
                        while (previewElement.firstChild) {
                            previewElement.removeChild(previewElement.firstChild);
                        }
                        // Create and append new image
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.maxWidth = '150px';
                        img.style.maxHeight = '150px';
                        img.style.borderRadius = '4px';
                        img.style.border = '1px solid #ddd';
                        img.style.marginTop = '10px';
                        previewElement.appendChild(img);
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    }

    // Apply to edit_student.php - assuming file input id 'profile_image_input' and preview div id 'image_preview_container'
    // These IDs will need to be added to the HTML of edit_student.php
    setupImagePreview('profile_image_input_edit', 'image_preview_container_edit');

    // Apply to register.php - assuming file input id 'profile_image_input_register' and preview div id 'image_preview_container_register'
    // These IDs will need to be added to the HTML of register.php
    setupImagePreview('profile_image_input_register', 'image_preview_container_register');
});
