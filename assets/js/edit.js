document.querySelector('#tasktablebody').addEventListener('click', function (e) {
    const row = e.target.closest('tr');

    if (e.target.classList.contains('edit-icon')) {
        handleInlineEdit(row);
    }

    if (e.target.classList.contains('delete-icon')) {
        const taskId = e.target.dataset.id;
        // if (confirm("Are you sure you want to delete this task?")) {
        //     fetch('api/task', {
        //         method: 'POST',
        //         headers: { 'Content-Type': 'application/json' },
        //         body: JSON.stringify({ action: 'delete', task_id: taskId })
        //     })
        //         .then(res => res.json())
        //         .then(data => {
        //             if (data.success) {
        //                 row.remove();
        //                 showToast("Task deleted successfully.");
        //             } else {
        //                 showToast("Failed to delete task.", true);
        //             }
        //         });
        // }
        showAlert('Are you sure you want to delete this task?',
            () => {
                fetch('api/task', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'delete', task_id: taskId })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            row.remove();
                            showToast("Task deleted successfully.");
                        } else {
                            showToast("Failed to delete task.", true);
                        }
                        // hideAlert();
                    });
            },
            () => {
                console.log('Task Deletion Cancelled')
            },
            "Confirm",
            "Cancel"
        );
    }
});


function handleInlineEdit(row) {
    const cells = row.querySelectorAll('td');
    const originalData = [...cells].map(cell => cell.innerHTML);
    const taskId = row.dataset.taskId;

    // Convert cells to editable inputs/selects
    const date = cells[0].innerText;
    const workType = cells[1].innerText;
    const time = cells[2].innerText;
    const client = cells[3].innerText;
    const category = cells[4].innerText;
    const brief = cells[5].innerText;
    const description = cells[6].innerText;
    const comment = cells[8].innerText;

    // Replace row content with editable form
    row.innerHTML = `
        <td><input type="date" value="${reverseFormatDate(date)}"></td>
        <td>
            <select>
                <option ${workType === "Working" ? "selected" : ""}>Working</option>
                <option ${workType === "Leave" ? "selected" : ""}>Leave</option>
                <option ${workType === "Public Holiday" ? "selected" : ""}>Public Holiday</option>
                <option ${workType === "Week Off" ? "selected" : ""}>Week Off</option>
            </select>
        </td>
        <td>
            <select>
                <option value="0:30" ${time === '0:30' ? 'selected' : ''}>0:30</option>
                <option value="1:00" ${time === '1:00' ? 'selected' : ''}>1:00</option>
                <option value="1:30" ${time === '1:30' ? 'selected' : ''}>1:30</option>
                <option value="2:00" ${time === '2:00' ? 'selected' : ''}>2:00</option>
                <option value="2:30" ${time === '2:30' ? 'selected' : ''}>2:30</option>
            </select>
        </td>
        <td><select name="client">${getClientOptions()}</select></td>
        <td><select name="cat">${getTaskCategoryOptions()}</select></td>
        <td><select name="brief"></select></td>
        <td><textarea name="description">${description || ''}</textarea></td>
        <td>Not Approved</td>
        <td><input type="text" value="${comment || ''}"></td>
        <td>
            <i class="fa-solid fa-check save-icon" data-id="${taskId}"></i>
            <i class="fa-solid fa-xmark cancel-icon"></i>
        </td>
    `;

    // Update task-brief options based on selected category
    const categorySelect = row.querySelector('select[name="cat"]');
    const briefSelect = row.querySelector('select[name="brief"]');
    categorySelect.addEventListener('change', () => {
        const selectedCatId = categorySelect.value;
        briefSelect.innerHTML = getTaskBriefOptions(selectedCatId);
        briefSelect.disabled = false;
    });

    // Initially trigger brief options if category already filled
    categorySelect.value = getCategoryIdByName(category);
    categorySelect.dispatchEvent(new Event('change'));
    setTimeout(() => {
        briefSelect.value = getBriefIdByName(categorySelect.value, brief);
    }, 100);

    // Handle save and cancel
    row.querySelector('.save-icon').addEventListener('click', () => {
        const updatedTask = {
            task_id: taskId,
            date: row.querySelector('input[type="date"]').value,
            worktype: row.querySelectorAll('select')[0].value,
            cat_id: categorySelect.value,
            brief_id: briefSelect.value,
            duration: row.querySelectorAll('select')[2].value,
            comment: row.querySelector('input[type="text"]').value
        };

        fetch('api/task', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'edit', task: updatedTask })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    showToast("Task updated successfully.");
                    updateTaskTable(data.updatedTasks); // Reload table
                } else {
                    showToast("Failed to update task.", true);
                }
            });
    });

    row.querySelector('.cancel-icon').addEventListener('click', () => {
        row.innerHTML = '';
        originalData.forEach((data, i) => {
            const td = document.createElement('td');
            td.innerHTML = data;
            row.appendChild(td);
        });
    });
}



function getTaskCategoryOptions() {
    let options = '<option value="" disabled selected>Select Task Category</option>';
    taskCategories.forEach(category => {
        options += `<option value="${category.cat_id}">${category.cat_name}</option>`;
    });
    // console.log(options);
    return options;
}

function getTaskBriefOptions(categoryId) {
    let options = '<option value="" disabled selected>Select Task Brief</option>';

    const key = String(categoryId).trim();

    if (briefsByCategory[key]) {
        briefsByCategory[key].forEach(brief => {
            options += `<option value="${brief.brief_id}">${brief.brief_name}</option>`;
        });
        options += `<option value="other">Other</option>`;
    } else {
        console.warn(`No briefs found for categoryId: ${categoryId}`);
    }

    return options;
}

function reverseFormatDate(dateStr) {
    // From: "15-07-2025" → To: "2025-07-15"
    const parts = dateStr.split("-");
    if (parts.length !== 3) return ""; // fallback for invalid format

    const [dd, mm, yyyy] = parts;
    return `${yyyy}-${mm}-${dd}`;
}


function getCategoryIdByName(name) {
    const cat = taskCategories.find(c => c.cat_name === name);
    return cat ? cat.cat_id : '';
}

function getBriefIdByName(catId, briefName) {
    const briefs = briefsByCategory[String(catId)] || [];
    const brief = briefs.find(b => b.brief_name === briefName);
    return brief ? brief.brief_id : '';
}
