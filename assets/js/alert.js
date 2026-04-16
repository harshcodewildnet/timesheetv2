
document.addEventListener('DOMContentLoaded', () => {
    const alertOverlay = document.getElementById('custom-alert');
    const alertBox = document.getElementById('alert-box');
    const alertMessage = document.getElementById('alert-message');
    const confirmBtn = document.getElementById('alert-confirm-btn');
    const cancelBtn = document.getElementById('alert-cancel-btn');

    let confirmCallback = null;
    let cancelCallback = null;

    function showAlert(message, onConfirm, onCancel = null, confirmText = 'Confirm', cancelText = 'Cancel') {

        alertMessage.textContent = message;
        confirmBtn.textContent = confirmText;
        cancelBtn.textContent = cancelText;

        confirmCallback = onConfirm;
        cancelCallback = onCancel;

        alertOverlay.style.display = 'flex';
        // console.log('show alert called');

    }

    confirmBtn.onclick = () => {
        // alertOverlay.style.display = 'none';
        if (confirmCallback) confirmCallback();
    };

    cancelBtn.onclick = () => {
        alertOverlay.style.display = 'none';
        if (cancelCallback) cancelCallback();
    };

    // document.addEventListener('click', (e) => {
    //     if (alertOverlay.style.display === 'flex' && !alertBox.contains(e.target)) {
    //         alertOverlay.style.display = 'none';
    //     }
    // });

    // Make showAlert globally available
    window.showAlert = showAlert;
    window.hideAlert = () => {
        alertOverlay.style.display = 'none';
    };

});