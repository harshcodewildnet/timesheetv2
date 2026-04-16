
const empId = loggedUserId;
const restrictedTypes = ['Leave', 'Week Off', 'Public Holiday', 'Half-Day Leave'];
const minDate = getDateString(10); // 2 days ago
const maxDate = getDateString(0); // today


document.addEventListener('DOMContentLoaded', function () {
    const addButton = document.querySelector('#add-btn');
    const submitButton = document.querySelector('#submit-btn');
    const tableBody = document.querySelector('#addtablebody');


    // Listen to all task-category dropdown changes
    tableBody.addEventListener('change', function (e) {
        if (e.target && e.target.name === 'task-category') {
            const row = e.target.closest('tr');
            const taskBriefSelect = row.querySelector('select[name="task-brief"]');
            const selectedCatId = e.target.value;
            // console.log(selectedCatId);
            // console.log(typeof (selectedCatId));
            // console.log(taskBriefSelect);
            if (selectedCatId && taskBriefSelect) {
                taskBriefSelect.innerHTML = getTaskBriefOptions(selectedCatId);
                taskBriefSelect.disabled = false;
            }
        }
    });


    // Add new row
    addButton.addEventListener('click', () => {
        // const today = new Date().toISOString().split('T')[0];
        let rowEl = document.createElement('tr');
        rowEl.classList.add('entry-row');
        rowEl.innerHTML = `
                        <td><input type="date" min="${minDate}" max="${maxDate}" onkeydown="return false;"></td>
                        <td>
                            <select name="work-type" class="work-type">
                                <option value="Working">Working</option>
                                <option value="Leave">Leave</option>
                                <option value="Half-Day Leave">Half-Day Leave</option>
                                <option value="Public Holiday">Public Holiday</option>
                                <option value="Week Off">Week Off</option>
                            </select>
                        </td>
                        <td>
                            <select name="time">
                                <option value="" disabled selected>Select Time</option>
                                <option value="00:10">00:10</option>
                                <option value="00:15">00:15</option>
                                <option value="00:30">00:30</option>
                                <option value="01:00">01:00</option>
                                <option value="01:30">01:30</option>
                                <option value="02:00">02:00</option>
                                <option value="04:00">04:00</option>
                            </select>

                        </td>
                        <td>
                            <select name="emp-client">
                                ${getEmployeeClientsOptions()}
                            </select>
                        </td>
                        <td>
                            <select name="task-category">
                                <option value="" disabled selected>Select Task Category</option>
                                <option value="-1" disabled hidden>-</option>
                                ${getTaskCategoryOptions()}
                            </select>
                        </td>
                        <td>
                            <select name="task-brief">
                                <option value="" disabled selected>Select Task Brief</option>
                                <option value="-1" disabled hidden>-</option>
                            </select>
                        </td>
                        <td>
                        <textarea name="description" rows="3" placeholder="Add Description"></textarea>
                        </td>
                        <td><i class="fa-regular fa-trash-can"></i></td>
        `;

        tableBody.appendChild(rowEl);
    });


    // Listen for changes in work-type for all rows
    tableBody.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('work-type')) {
            handleWorkTypeChange(e.target);
        }
    });


    // Delete row
    tableBody.addEventListener('click', function (e) {
        if (e.target.classList.contains('fa-trash-can')) {
            const row = e.target.closest('tr');

            if (row.classList.contains('first')) {
                // alert("Can't remove the first row.");
                showToast('Can\'t remove the first row.', true);
            } else {
                row.remove();
            }
        }
    });

    // Submit entries logic
    submitButton.addEventListener('click', function () {
        const rows = document.querySelectorAll('#addtablebody .entry-row');
        const tasks = [];
        // const restrictedMap = {}; // { '2025-07-14': true }
        // const workingMap = {}; // { '2025-07-14': true }

        let hasError = false;
        //change here
        // Collect existing tasks from main table
        const existingTasks = Array.from(document.querySelectorAll('#tasktablebody tr')).map(row => {
            const date = row.cells[0]?.innerText.trim(); // e.g., '06-08-2025'
            const workType = row.cells[1]?.innerText.trim(); // e.g., 'Working'

            return {
                date: convertToISO(date), // convert to 'YYYY-MM-DD'
                workType
            };
        });

        // Helper to convert 'DD-MM-YYYY' to 'YYYY-MM-DD'
        function convertToISO(dateStr) {
            const parts = dateStr.split("-");
            return `${parts[2]}-${parts[1]}-${parts[0]}`;
        }

        rows.forEach(row => {
            const date = row.querySelector('input[type="date"]').value;
            const workType = row.querySelector('select.work-type').value;
            const taskCategorySelect = row.querySelector('select[name="task-category"]');
            const taskCategoryId = taskCategorySelect.value;
            const taskCategoryText = taskCategorySelect.options[taskCategorySelect.selectedIndex]?.text || '';
            const taskBrief = row.querySelector('select[name="task-brief"]');
            const taskBriefId = taskBrief.value;
            const clientSelect = row.querySelector('select[name="emp-client"]');
            const clientId = clientSelect.value;
            const taskBriefText = taskBrief.options[taskBrief.selectedIndex]?.text || '';
            const taskDescriptionEl = row.querySelector('textarea[name="description"]');
            const taskDescription = taskDescriptionEl.value.trim();
            const timeTaken = row.querySelector('select[name="time"]').value;
            const status = 0;

            // Check if any field is empty then show error
            const isRestrictedType = restrictedTypes.includes(workType);
            if (!date || !workType || !taskCategoryId || !taskBriefId || !timeTaken) {
                showToast(`Please complete all fields in each row.`, true);
                hasError = true;
                return;
            }

            // Project/Client is mandatory for Working entries
            if (!isRestrictedType && !clientId) {
                showToast(`Please select a Project / Client for each Working entry.`, true);
                clientSelect.focus();
                hasError = true;
                return;
            }

            if (taskBriefId == -2 && !taskDescription) {
                showToast(`Task Description is required with Task Type - 'Other'`, true);
                taskDescriptionEl.classList.add('error-border');
                taskDescriptionEl.focus();
                hasError = true;
                return;

            } else {
                taskDescriptionEl.classList.remove('error-border');
            }

            // Check against existing + current modal tasks
            const allTasksOnSameDate = existingTasks
                .filter(t => t.date === date)
                .map(t => t.workType);

            // Include already checked modal rows (from tasks array)
            tasks.forEach(t => {
                if (t.date === date) {
                    allTasksOnSameDate.push(t.workType);
                }
            });

            // Check if date already has a restricted type (Leave, Week Off, Public Holiday) — only one allowed
            if (restrictedTypes.includes(workType)) {
                const countRestricted = allTasksOnSameDate.filter(type =>
                    restrictedTypes.includes(type)
                ).length;

                if (countRestricted > 0) {
                    showToast(`Only one restricted entry(Leave/Half-Day Leave/Week Off/Public Holiday) allowed on ${date}`, true);
                    hasError = true;
                    return;
                }
            }

            // Check: if Working already exists, block restricted types (except Half-Day Leave)
            if (
                allTasksOnSameDate.includes('Working') &&
                restrictedTypes.includes(workType) &&
                workType !== 'Half-Day Leave'
            ) {
                showToast(`Cannot add '${workType}' on ${date} — Already has a 'Working' entry. Only 'Half-Day Leave' allowed.`, true);
                hasError = true;
                return;
            }

            // Check: if restricted (except Half-Day Leave) already exists, block adding Working
            if (
                workType === 'Working' &&
                allTasksOnSameDate.some(type => restrictedTypes.includes(type) && type !== 'Half-Day Leave')
            ) {
                showToast(`Cannot add 'Working' on ${date} — already has a restricted entry.`, true);
                hasError = true;
                return;
            }

            // All clear, add to task array
            tasks.push({
                date,
                workType,
                taskCategoryId,
                taskCategoryText,
                taskBriefId,
                taskBriefText,
                taskDescription,
                clientId,
                timeTaken,
                status
            });
            // }
        });


        if (hasError) return;


        if (tasks.length === 0) {
            // alert('Please fill at least one complete row.');
            showToast('Please fill at least one complete row.', true);
            return;
        }

        fetch('api/task', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', tasks: tasks })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    // alert('Failed to add tasks.');
                    showToast('Failed to add tasks.', true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error adding tasks.');
            });
    });

});


/////////////////////////

// Handle Inline Edit and Delete
document.querySelector('#tasktablebody').addEventListener('click', function (e) {
    const row = e.target.closest('tr');

    // Edit Task
    if (e.target.classList.contains('edit-icon')) {
        handleInlineEdit(row);
    }

    // Delete task
    if (e.target.classList.contains('delete-icon')) {
        const taskId = e.target.dataset.id;
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
                            // row.remove();
                            // loadGraphs([loggedUserId]);
                            // showToast("Task deleted successfully.");
                            window.location.reload();
                        } else {
                            showToast("Failed to delete task.", true);
                        }
                        hideAlert();
                    });
            },
            () => {
                // console.log('Task Deletion Cancelled')
            },
            "Confirm",
            "Cancel"
        );
    }
});


function handleInlineEdit(row) {

    // Cancel any other editing row first
    const existingEditRow = document.querySelector('#tasktablebody tr.active');
    if (existingEditRow && existingEditRow !== row) {
        cancelEdit(existingEditRow);
    }

    row.classList.add('active');
    // Store original HTML
    row.dataset.originalHtml = row.innerHTML;
    // change here
    const cells = row.querySelectorAll('td');
    const taskId = row.dataset.taskId;
    const date = cells[0].innerText;
    const workType = cells[1].innerText;
    const time = cells[2].innerText;
    const client = cells[3].innerText;
    const category = cells[4].innerText;
    const brief = cells[5].innerText;
    const description = cells[6].innerText;
    const status = cells[7].innerText;
    const comment = cells[8].innerText;

    // Replace row content with editable form
    // <td><input type="date" value="${reverseFormatDate(date)}"></td> (replaced)
    // change here
    row.innerHTML = `
     <td><input type="date" min="${minDate}" max="${maxDate}" value="${reverseFormatDate(date)}" onkeydown="return false;"></td>
        <td>
            <select name="work-type" class="work-type">
                <option ${workType === "Working" ? "selected" : ""}>Working</option>
                <option ${workType === "Leave" ? "selected" : ""}>Leave</option>
                <option ${workType === "Half-Day Leave" ? "selected" : ""}>Half-Day Leave</option>
                <option ${workType === "Public Holiday" ? "selected" : ""}>Public Holiday</option>
                <option ${workType === "Week Off" ? "selected" : ""}>Week Off</option>
            </select>
        </td>
        <td>
            <select name="time">
                <option value="00:10" ${time === '00:10' ? 'selected' : ''}>00:10</option>
                <option value="00:15" ${time === '00:15' ? 'selected' : ''}>00:15</option>
                <option value="00:30" ${time === '00:30' ? 'selected' : ''}>00:30</option>
                <option value="01:00" ${time === '01:00' ? 'selected' : ''}>01:00</option>
                <option value="01:30" ${time === '01:30' ? 'selected' : ''}>01:30</option>
                <option value="02:00" ${time === '02:00' ? 'selected' : ''}>02:00</option>
                <option value="04:00" ${time === '04:00' ? 'selected' : ''}>04:00</option>
            </select>
        </td>
        <td>
            <select name="emp-client">
            ${getEmployeeClientsOptions()}
            </select>
        </td>
        <td>
            <select name="task-category">
                <option value="" disabled selected>Select Task Category</option>    
                <option value="-1" disabled hidden>-</option>    
                ${getTaskCategoryOptions()}
            </select>
        </td>
        <td>
            <select name="task-brief">
                <option value="" disabled selected>Select Task Brief</option>
                <option value="-1" disabled hidden>-</option>
            </select>
        </td>
        <td>
            <textarea name="description" rows="3" placeholder="Add Description">${description || ''}</textarea>
        </td>
        <td>${status || '-'}</td>
        <td>${comment || ''}</td>   
        <td>
            <i class="fa-solid fa-check save-icon" data-id="${taskId}"></i>
            <i class="fa-solid fa-xmark cancel-icon"></i>
        </td>
    `;

    // Attach work-type change listener for edit row
    const workTypeSelect = row.querySelector('select[name="work-type"]');
    if (workTypeSelect) {
        workTypeSelect.addEventListener('change', () => {
            handleWorkTypeChange(workTypeSelect);
        });

        // Initial trigger for edit row
        handleWorkTypeChange(workTypeSelect);
    }


    const categorySelect = row.querySelector('select[name="task-category"]');
    const briefSelect = row.querySelector('select[name="task-brief"]');
    const clientSelect = row.querySelector('select[name="emp-client"]');

    // -- PREFILL values --
    const selectedCatId = getCategoryIdByName(category); // 'category' from original row
    if (selectedCatId) {
        categorySelect.value = selectedCatId;
        // Populate brief options
        briefSelect.innerHTML = getTaskBriefOptions(selectedCatId);
        const selectedBriefId = getBriefIdByName(selectedCatId, brief);
        if (selectedBriefId) {
            briefSelect.value = selectedBriefId;
        }
    }

    // Set up change listener AFTER prefill
    categorySelect.addEventListener('change', () => {
        const selectedCatId = categorySelect.value;
        briefSelect.innerHTML = getTaskBriefOptions(selectedCatId);
        briefSelect.disabled = false;
    });

    // PREFILL CLIENT SELECT
    const selectedClientId = getClientIdByName(client);
    if (selectedClientId) {
        clientSelect.value = selectedClientId;
    }


    // Initially trigger brief options if category already filled
    // categorySelect.value = getCategoryIdByName(category);
    // categorySelect.dispatchEvent(new Event('change'));
    // setTimeout(() => {
    //     briefSelect.value = getBriefIdByName(categorySelect.value, brief);
    // }, 100);

    // Handle save and cancel
    row.querySelector('.save-icon').addEventListener('click', () => {
        const updatedTask = {
            task_id: taskId,
            date: row.querySelector('input[type="date"]').value,
            worktype: row.querySelector('select.work-type').value,
            cat_id: categorySelect.value,
            brief_id: briefSelect.value,
            description: row.querySelector('textarea[name="description"]').value,
            clientId: row.querySelector('select[name="emp-client"]').value,
            // change here
            duration: row.querySelector('select[name="time"]').value,
        };

        // Create a new object without description (optional) for required-field check
        const { description, ...fieldsToCheck } = updatedTask;

        // Check if all required fields are filled
        const allFieldsFilled = Object.values(fieldsToCheck).every(val => val !== '' && val !== null);

        if (!allFieldsFilled) {
            showToast("Please fill all fields before saving.", true);
            return;
        }

        // Project/Client is mandatory for Working entries
        const isEditRestricted = restrictedTypes.includes(updatedTask.worktype);
        if (!isEditRestricted && !updatedTask.clientId) {
            showToast("Please select a Project / Client.", true);
            return;
        }

        // Validate restricted entries
        if (restrictedTypes.includes(updatedTask.worktype) || updatedTask.worktype === 'Working') {
            const allRows = document.querySelectorAll('#tasktablebody tr');
            const targetDate = formatDate(updatedTask.date);
            let hasConflict = false;

            allRows.forEach(otherRow => {
                const otherTaskId = otherRow.dataset.taskId;
                if (otherRow !== row && otherTaskId && otherTaskId !== taskId) {
                    const dateCell = otherRow.cells[0]?.innerText?.trim();
                    const workTypeCell = otherRow.cells[1]?.innerText?.trim();

                    if (dateCell === targetDate) {
                        //  Conflict if same restricted type exists
                        if (restrictedTypes.includes(updatedTask.worktype) && restrictedTypes.includes(workTypeCell)) {
                            hasConflict = true;
                        }

                        //  Conflict if Working + restricted (except Half-Day Leave)
                        if (updatedTask.worktype === 'Working' && restrictedTypes.includes(workTypeCell) && workTypeCell !== 'Half-Day Leave') {
                            hasConflict = true;
                        }

                        if (workTypeCell === 'Working' && restrictedTypes.includes(updatedTask.worktype) && updatedTask.worktype !== 'Half-Day Leave') {
                            hasConflict = true;
                        }
                    }
                }
            });

            if (hasConflict) {
                showToast(`Conflicting entry for ${targetDate}. Cannot mix '${updatedTask.worktype}' with existing task type(s) on that date.`, true);
                return;
            }
        }



        // console.log(updatedTask);
        // return;
        fetch('api/task', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'edit', task: updatedTask })
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // showToast("Task updated successfully.");
                    // setTimeout(() => window.location.reload(), 3000);
                    window.location.reload();
                    // updateTaskTable(data.updatedTasks); // Reload table

                } else {
                    showToast("Failed to update task.", true);
                }
            });
    });

    // Cancel handler
    row.querySelector('.cancel-icon').addEventListener('click', () => {
        cancelEdit(row);
    });
}


function cancelEdit(row) {
    const originalHtml = row.dataset.originalHtml;
    if (originalHtml) {
        row.innerHTML = originalHtml;
        row.classList.remove('active');
        delete row.dataset.originalHtml;
    }
}



// function getTaskCategoryOptions() {
//     let options = '<option value="" disabled selected>Select Task Category</option>';
//     taskCategories.forEach(category => {
//         options += `<option value="${category.cat_id}">${category.cat_name}</option>`;
//     });
//     // console.log(options);
//     return options;
// }

// for setting min,max dates in calender
function getDateString(daysAgo = 0) {
    const d = new Date();
    d.setDate(d.getDate() - daysAgo);
    return d.toISOString().split('T')[0];
}


// Date formatting function
function formatDate(dateString) {
    const parts = dateString.split('-'); // ["YYYY", "MM", "DD"]
    return `${parts[2]}-${parts[1]}-${parts[0]}`; // "DD-MM-YYYY"
}

// Duration formatting function
function formatTime(timeString) {
    return timeString.slice(0, 5); // Converts '02:30:00' to '02:30'
}


function reverseFormatDate(dateStr) {
    // From: "15-07-2025" → To: "2025-07-15"
    const parts = dateStr.split("-");
    if (parts.length !== 3) return ""; // fallback for invalid format

    const [dd, mm, yyyy] = parts;
    return `${yyyy}-${mm}-${dd}`;
}

function handleWorkTypeChange(workTypeSelect) {
    // console.log("handleWorkTypeChange called");
    const row = workTypeSelect.closest('tr');
    const taskCategory = row.querySelector('select[name="task-category"]');
    const taskBrief = row.querySelector('select[name="task-brief"]');
    const timeSelect = row.querySelector('select[name="time"]');

    const workType = workTypeSelect.value;

    if (workType === 'Leave') {
        disableTaskFields(taskCategory, taskBrief, timeSelect, '08:00');
    } else if (workType === 'Half-Day Leave') {
        disableTaskFields(taskCategory, taskBrief, timeSelect, '04:30');
    } else if (workType === 'Public Holiday' || workType === 'Week Off') {
        disableTaskFields(taskCategory, taskBrief, timeSelect, '00:00');
    } else { // Working
        // Remove non-standard time options added dynamically
        cleanUpCustomTimeOptions(timeSelect);

        taskCategory.disabled = false;
        taskBrief.disabled = false;
        timeSelect.disabled = false;

        taskCategory.value = '';
        taskBrief.value = '';
        // timeSelect.value = '';
    }
}

// Disable task-related dropdowns and set fixed time
function disableTaskFields(taskCategory, taskBrief, timeSelect, fixedTime) {
    // console.log("disableTaskFields called");
    taskCategory.value = -1;
    taskBrief.value = -1;
    taskCategory.disabled = true;
    taskBrief.disabled = true;
    setTimeOption(timeSelect, fixedTime);
    timeSelect.disabled = true;
}

// Add or select time if not present
function setTimeOption(selectElement, timeValue) {
    let option = Array.from(selectElement.options).find(opt => opt.value === timeValue);
    if (!option) {
        option = document.createElement('option');
        option.value = timeValue;
        option.text = timeValue;
        option.dataset.custom = "true";  // Mark custom options
        selectElement.appendChild(option);
    }
    selectElement.value = timeValue;
}

// Remove previously added custom time options
function cleanUpCustomTimeOptions(selectElement) {
    const optionsToRemove = Array.from(selectElement.options).filter(
        opt => opt.dataset.custom === "true"
    );
    optionsToRemove.forEach(opt => opt.remove());
}


function getTaskCategoryOptions() {
    let options = '';
    // let options = '<option value="" disabled selected>Select Task Category</option>';
    // options += '<option value="-1" disabled hidden>-</option>';
    taskCategories.forEach(category => {
        options += `<option value="${category.cat_id}">${category.cat_name}</option>`;
    });
    // console.log("getTaskCategoriesOptions Called");
    return options;
}

function getEmployeeClientsOptions() {
    let options = '';
    const isProject = (typeof deptId !== 'undefined' && parseInt(deptId) === 5);
    const label = isProject ? 'Project' : 'Client';

    options += `
        <option value="" disabled selected>Select ${label}</option>
        <option value="-1" disabled hidden>-</option>
    `;
    if (employeeClients.length > 0) {
        employeeClients.forEach(client => {
            options += `<option value="${client.client_id}">${client.client_name}</option>`;
        });
    }
    options += `<option value="-1">Other</option>`;

    return options;
}


function getTaskBriefOptions(categoryId) {
    let options = '<option value="" disabled selected>Select Task Brief</option>';
    options += '<option value="-1" disabled hidden>-</option>';

    // let options = '';
    const key = String(categoryId).trim();

    if (briefsByCategory[key]) {
        // console.log("briefsByCategory[key]: ");
        // console.log(briefsByCategory[key]);
        briefsByCategory[key].forEach(brief => {
            options += `<option value="${brief.brief_id}">${brief.brief_name}</option>`;
        });
        options += `<option value="-2">Other</option>`;
    } else {
        console.warn(`No briefs found for categoryId: ${categoryId}`);
        options += `<option value="-2">Other</option>`;
    }
    // console.log("getTaskBriefOptions Called :", options);
    return options;
}


function normalizeString(str) {
    return str.trim().toLowerCase().replace(/\s+/g, ' ');
}

function getCategoryIdByName(name) {
    const cat = taskCategories.find(
        c => normalizeString(c.cat_name) === normalizeString(name)
    );
    return cat ? cat.cat_id : '';
}

function getBriefIdByName(catId, briefName) {
    const briefs = briefsByCategory[String(catId)] || [];
    const brief = briefs.find(
        b => normalizeString(b.brief_name) === normalizeString(briefName)
    );
    return brief ? brief.brief_id : '';
}

function getClientIdByName(name) {
    if (normalizeString(name) === 'other') {
        return '-1';
    }
    const client = employeeClients.find(
        cl => normalizeString(cl.client_name) === normalizeString(name)
    );
    return client ? client.client_id : '';
}
