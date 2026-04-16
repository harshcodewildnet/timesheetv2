<!-- Profile Photo Change Modal -->
<div class="photo-modal-overlay" id="profile-photo-modal">
    <div class="modal-container">
        <div class="modal-header">
            <h3>Upload your profile picture</h3>
            <button class="close-button" onclick="closeModal()">&#10005;</button>
        </div>
        <div class="modal-body">
            <div class="image-upload-area" id="image-drop-area">
                <img id="preview-image" src="<?=$employee['profile_photo']?>" alt="Profile Preview" onerror="this.onerror=null; this.src='assets/images/profile-picture.png';" />
                <p>Drop your new profile image here (max 10MB)</p>
                <input type="file" id="image-input" accept="image/*" hidden onchange="previewImage(event)" />
            </div>
            <p id="image-name" class="file-name"></p>
            <p id="upload-error" class="upload-error"></p>
            <button class="choose-file-btn" onclick="document.getElementById('image-input').click()">Choose File</button>
        </div>
        <div class="modal-footer">
            <button class="cancel-btn" onclick="closeModal()">Cancel</button>
            <button class="save-btn" onclick="uploadImage()">Save</button>
        </div>
    </div>
</div>
