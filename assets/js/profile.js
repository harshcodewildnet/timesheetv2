document.addEventListener('DOMContentLoaded', function () {
    const profileImg = document.querySelector('.profile-img');
    const profileModal = document.getElementById('profile-photo-modal');
    profileImg.addEventListener('click', function () {
        profileModal.classList.toggle('show');
    });
});


function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        document.getElementById('preview-image').src = URL.createObjectURL(file);
        document.getElementById('image-name').textContent = file.name;
        document.getElementById('upload-error').textContent = '';
    }
}

function uploadImage() {
    const fileInput = document.getElementById('image-input');
    const file = fileInput.files[0];

    if (!file) {
        document.getElementById('upload-error').textContent = 'Please select an image to upload.';
        return;
    }

    if (file.size > 10 * 1024 * 1024) { // 10MB limit
        document.getElementById('upload-error').textContent = 'Image size exceeds 10MB.';
        return;
    }


    const formData = new FormData();
    formData.append('profileImage', file);

    // Optional: show loading indicator
    // document.getElementById('uploadStatus').innerText = 'Uploading...';


    // Send Ajax Request
    fetch('api/update-photo', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Image uploaded successfully!');
                closeModal();

                // Optional: Update the profile photo on the page
                document.getElementById('profile-image').src = data.imagePath;
            } else {
                alert('Upload failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error uploading image:', error);
            alert('An error occurred while uploading the image.');
        })
        .finally(() => {
            // document.getElementById('uploadStatus').innerText = '';
        });

}

function closeModal() {
    document.getElementById('profile-photo-modal').classList.remove('show');
}
