
// Store previous status when the dropdown gets focus
document.querySelector('#tasktablebody').addEventListener('focusin', function (e) {
    if (e.target && e.target.name === 'status-dropdown') {
        e.target.setAttribute('data-previous', e.target.value);
    }
});


document.querySelector('#tasktablebody').addEventListener('change', function (e) {
    if (e.target && e.target.name === 'status-dropdown') {
        const row = e.target.closest('.task-bullet-row, .task-row-card, tr');
        const taskId = row.dataset.taskId;
        const newStatus = parseInt(e.target.value);
        const previousStatus = e.target.getAttribute('data-previous');
        const commentInput = row.querySelector('input[name="comment"]').value;

        if (newStatus === -1) {
            // Show popup to enter comment
            showCommentPopup((popupComment) => {
                if (popupComment.trim() === '') {
                    // showToast('Comment is Empty.', true);
                    e.target.value = previousStatus; // Reset dropdown to previous status
                    return;
                }

                updateTaskStatus(taskId, newStatus, popupComment);
                row.querySelector('input[name="comment"]').value = popupComment;
                // Hide Alert Manually
                hideAlert();

            }, () => {
                // On Cancel, reset dropdown to previous value
                e.target.value = previousStatus;
                showToast('Comment is required to disapprove task', true);
            });
        } else {
            updateTaskStatus(taskId, newStatus, commentInput);
        }
    }
});



// document.querySelector('#tasktablebody').addEventListener('blur', function (e) {
//     if (e.target.name === 'comment') {
//         const row = e.target.closest('tr');
//         const taskId = row.getAttribute('data-task-id');
//         const status = row.querySelector('.status-dropdown').value;
//         const comment = e.target.value;

//         updateTaskStatus(taskId, status, comment);
//     }
// }, true); // Use capture phase to catch blur events

// document.querySelector('#tasktablebody').addEventListener('change', function (e) {
//     if (e.target && e.target.name === 'comment') {
//         const row = e.target.closest('tr');
//         const taskId = row.dataset.taskId;
//         const status = row.querySelector('select[name="status-dropdown"]').value;
//         const comment = e.target.value;
//         if (comment.length > 300) {
//             showToast('Warning : Comment larger than 300 characters !', true);
//             e.target.value = '';
//             return;
//         }
//         updateTaskStatus(taskId, status, comment);
//     }
// });


// Make all comment inputs readonly (can also set this in HTML)
document.querySelectorAll('input[name="comment"]').forEach(input => {
    input.readOnly = true;
    console.log('readOnly');

});

// Delegate event for comment input popup
document.querySelector('#tasktablebody').addEventListener('click', function (e) {
    if (e.target && e.target.name === 'comment') {
        openCommentPopupForRow(e.target);
    }
});


function openCommentPopupForRow(inputEl) {
    const row = inputEl.closest('.task-bullet-row, .task-row-card, tr');
    const taskId = row.dataset.taskId;
    const status = row.querySelector('select[name="status-dropdown"]').value;
    const oldComment = inputEl.value;

    showCommentPopup(popupComment => {
        if (popupComment.length > 300) {
            showToast('Warning : Comment larger than 300 characters !', true);
            return;
        }
        inputEl.value = popupComment;
        updateTaskStatus(taskId, status, popupComment);
        hideAlert();

    }, /*onCancel=*/null, oldComment);
}


// Modify showCommentPopup to accept an initial value:
function showCommentPopup(onConfirm, onCancel, initialValue = '') {
    showAlert('Please enter a comment:', function () {
        const comment = document.getElementById('popup-comment-input').value.trim();
        const errorEl = document.getElementById('popup-comment-error');
        if (comment === '') {
            errorEl.textContent = 'Comment is empty.';
            return; // Don’t close
        } else if (comment.length > 300) {
            errorEl.textContent = 'Warning : Comment larger than 300 characters !';
            return;
        }
        errorEl.textContent = '';
        onConfirm(comment);
    }, function () {
        if (onCancel) onCancel();
    }, 'Submit', 'Cancel');
    document.getElementById('alert-message').innerHTML = `
    <label>Please enter comment</label><br>
    <textarea id="popup-comment-input" placeholder="Enter comment" style="margin-top:10px;padding: 8px 12px;width:90%;max-width: 100%;" value="${initialValue.replace(/"/g, '&quot;')}" maxlength="300"/>${initialValue.replace(/"/g, '&quot;')}</textarea>
    <p id="popup-comment-error" style="color: red; font-size: 13px; margin-top: 5px;"></p>
  `;
}


document.addEventListener('input', (e) => {
    if (e.target.id === 'popup-comment-input') {
        document.getElementById('popup-comment-error').textContent = '';
    }
});



function updateTaskStatus(taskId, status, comment) {
    fetch('api/update-task-status', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ task_id: taskId, status: status, comment: comment })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show green border
                const row = document.querySelector(`.task-bullet-row[data-task-id="${taskId}"], .task-row-card[data-task-id="${taskId}"], tr[data-task-id="${taskId}"]`);
                if (row) row.classList.add('row-saved');

                // Remove border after 2 seconds
                setTimeout(() => {
                    row.classList.remove('row-saved');
                }, 3000);

                // Show toast
                showToast('Status Updated!');

                // Silent refresh table structure to maintain scroll position
                if (typeof applyFilters === 'function') {
                    applyFilters(true);
                } else if (typeof loadTasks === 'function') {
                    loadTasks(currentTab, currentPage, limit, getFilterValues());
                }
            } else {
                showToast('Save Failed!', true);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('Error Saving!', true);
        });
}


// function showCommentPopup(onConfirm, onCancel) {
//     showAlert('Please enter comment for disapproval:', function () {
//         const comment = document.getElementById('popup-comment-input').value.trim();
//         const errorEl = document.getElementById('popup-comment-error');

//         if (comment === '') {
//             errorEl.textContent = 'Comment is empty.';
//             return; // Don't close the popup
//         }
//         else if (comment.length > 300) {
//             errorEl.textContent = 'Warning : Comment larger than 300 characters !';
//             return;
//         }

//         // Clear any previous errors
//         errorEl.textContent = '';
//         onConfirm(comment); // Pass valid comment

//     }, function () {
//         if (onCancel) onCancel();
//     }, 'Submit', 'Cancel');

//     document.getElementById('alert-message').innerHTML = `
//         <label>Please enter comment for disapproval:</label><br>
//         <input type="text" id="popup-comment-input" placeholder="Enter comment" style="margin-top:10px;width:90%;" />
//         <p id="popup-comment-error" style="color: red; font-size: 13px; margin-top: 5px;"></p>
//     `;
// }


document.getElementById('bulk-approve-btn').addEventListener('click', () => {
    const selectedTasks = document.querySelectorAll('.select-task:checked');
    const selectedGroups = document.querySelectorAll('.group-checkbox:checked');

    if (selectedTasks.length === 0 && selectedGroups.length === 0) {
        showToast('Please select at least one task or employee card to approve.', true);
        return;
    }

    const updates = [];

    // Case 1: Individually selected tasks (Admin/HOD views)
    selectedTasks.forEach(cb => {
        const row = cb.closest('.task-bullet-row, .task-row-card, tr');
        const taskId = row.dataset.taskId || row.getAttribute('data-task-id');
        const comment = row.querySelector('input[name="comment"]').value.trim();

        updates.push({
            task_id: taskId,
            status: 1,
            comment: comment
        });
    });

    // Case 2: Tasks from selected employee cards (RM view)
    selectedGroups.forEach(groupCb => {
        const box = groupCb.closest('.employee-task-box');
        if (box) {
            box.querySelectorAll('.task-bullet-row').forEach(row => {
                const taskId = row.dataset.taskId;
                // Avoid duplicates if both are present (though we are removing inner ones)
                if (!updates.find(u => u.task_id === taskId)) {
                    const commentInput = row.querySelector('input[name="comment"]');
                    updates.push({
                        task_id: taskId,
                        status: 1,
                        comment: commentInput ? commentInput.value.trim() : ''
                    });
                }
            });
        }
    });

    if (updates.length === 0) {
        showToast('No tasks found to approve.', true);
        return;
    }
    // console.log(updates);
    // Send to backend
    fetch('api/bulk-approve', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tasks: updates })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Tasks approved successfully!');
                if (typeof applyFilters === 'function') {
                    applyFilters(true);
                } else if (typeof loadTasks === 'function') {
                    loadTasks(currentTab, currentPage, limit, getFilterValues());
                }
            } else {
                showToast('Error approving tasks.');
            }
        })
        .catch(error => console.error('Error:', error));
});
