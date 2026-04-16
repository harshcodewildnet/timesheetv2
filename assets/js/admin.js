
// Logic to switch tabs
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.tab-link');
    const panels = document.querySelectorAll('.tab-panel');

    // Restore last active tab from localStorage (if any)
    const lastTabId = localStorage.getItem('activeTabId');
    if (lastTabId) {
        const activeTab = document.querySelector(`.tab-link[data-tab="${lastTabId}"]`);
        const activePanel = document.getElementById(lastTabId);
        if (activeTab && activePanel) {
            // Remove existing active states
            tabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            panels.forEach(panel => panel.classList.remove('active'));

            // Set saved tab as active
            activeTab.classList.add('active');
            activeTab.setAttribute('aria-selected', 'true');
            activePanel.classList.add('active');
        }
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');

            // Save clicked tab to localStorage
            localStorage.setItem('activeTabId', target);

            // Remove active from all tabs/panels
            tabs.forEach(t => {
                t.classList.remove('active');
                t.setAttribute('aria-selected', 'false');
            });
            panels.forEach(panel => panel.classList.remove('active'));

            // Activate clicked tab/panel
            tab.classList.add('active');
            tab.setAttribute('aria-selected', 'true');
            document.getElementById(target).classList.add('active');
        });
    });
});

// modal popup open buttons logic
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".open-modal-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const modalType = btn.getAttribute("data-modal");
            const mode = btn.getAttribute("data-mode") || "add";
            const id = btn.getAttribute("data-id") || null;
            openModal(modalType, mode, id);
        });
    });
});

// open modal popups
function openModal(modalType, mode, id = null) {
    const modalId = `modal-${modalType}`;
    const modal = document.getElementById(modalId);
    const form = modal.querySelector("form.profile-container");
    const title = modal.querySelector(".modal-title");
    const submitBtn = modal.querySelector(".save-profile-btn");
    const inputs = modal.querySelectorAll("input, select, textarea");
    const passwordFields = modal.querySelectorAll('.password-field');
    // console.log(passwordFields);

    // Reset form
    form.reset();
    // Clear errors
    form.querySelectorAll("small.error").forEach(err => {
        err.textContent = "";
        err.style.display = "none";   // or visibility = "hidden"
    });

    // Also clear dependent selects when opening in add mode
    if (mode === "add") {
        const subdeptSelect = form.querySelector('select[name="subdept_id"]');
        // const rmSelect = form.querySelector('select[name="rm_id"]');
        if (subdeptSelect) {
            subdeptSelect.innerHTML = `<option value="">Select Sub Department</option>`;
        }
        // if (rmSelect) {
        //     rmSelect.innerHTML = `<option value="">Select Reporting Manager</option>`;
        // }
        inputs.forEach(input => {
            // console.log('Input enabled : ', input);
            input.disabled = false
        });
    } else {
        inputs.forEach(input => {
            if (input.classList.contains('editable')) {
                // console.log('Input enabled : ', input);
                input.disabled = false;
            }
        });
    }
    submitBtn.style.display = 'inline-block';

    form.dataset.mode = mode;
    form.dataset.modalType = modalType;
    if (id) form.dataset.id = id; else delete form.dataset.id;


    // inside openModal
    if (mode === "add") {
        title.textContent = `Add ${capitalize(modalType)}`;
        submitBtn.textContent = "Add";
        passwordFields.forEach(el => {
            el.style.display = 'none';
            // remove required from all password inputs
            el.querySelectorAll("input").forEach(inp => inp.removeAttribute("required"));
        });
    } else if (mode === "edit") {
        title.textContent = `Edit ${capitalize(modalType)}`;
        submitBtn.textContent = "Update";
        passwordFields.forEach(el => {
            el.style.display = 'block';
            // removing required as not mandatory on every edit
            el.querySelectorAll("input").forEach(inp => {
                inp.textContent = '';
                inp.removeAttribute("required");
            });
        });
    } else if (mode === "view") {
        title.textContent = `View ${capitalize(modalType)}`;
        submitBtn.style.display = "none";

        const passwordFields = modal.querySelectorAll(".password-field");

        passwordFields.forEach((field, index) => {
            // Show only first password field
            console.log(field);

            if (index === 0) {
                field.style.display = "block";
                // console.log('first');

            }
            // Hide confirm-password field
            else {
                field.style.display = "none";
                // console.log('second');

            }

            // Disable inputs and remove required attribute
            field.querySelectorAll("input").forEach(inp => {
                inp.disabled = true;
                inp.removeAttribute("required");
            });
        });
    }



    // Fetch data if not "add"(view/edit mode)
    if (mode !== "add" && id) {
        let apiUrl = "";
        let bodyData = "";

        if (modalType === "department") {
            apiUrl = "api/department";
            bodyData = `action=get&id=${id}`;
        } else if (modalType === "sub-department") {
            apiUrl = "api/subdepartment";
            bodyData = `action=get&id=${id}`;
        }
        else if (modalType === "client") {
            apiUrl = "api/client";
            bodyData = `action=get&id=${id}`;
        }
        else {
            apiUrl = "api/employee";
            bodyData = `action=get&id=${id}`;
        }

        fetch(apiUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: bodyData
        })
            .then(res => res.json())
            .then(data => {
                if (data.status === "success") {
                    const item = data.emp || data.dept || data.subdept || data.client;
                    // console.log(item);
                    for (const key in item) {
                        const field = modal.querySelector(`[name="${key}"]`);
                        // console.log(key + ":" + item[key]);
                        // if (field) field.value = item[key];
                        if (field) {
                            field.value = item[key];

                            // Special: trigger change on dept_id to populate dependent selects
                            if (key === 'dept_id') {
                                field.dispatchEvent(new Event('change'));
                                // Also call explicitly to ensure labels update even if event bubbles are weird
                                updateDynamicLabels(form, item[key]);

                                // Delay setting subdept/rm values after options ar e populated
                                const subdeptId = item['subdept_id'];
                                const rmId = item['rm_id'];

                                setTimeout(() => {
                                    if (subdeptId) {
                                        const subSelect = modal.querySelector('select[name="subdept_id"]');
                                        if (subSelect) subSelect.value = subdeptId;
                                    }

                                    if (rmId) {
                                        const rmSelect = modal.querySelector('select[name="rm_id"]');
                                        if (rmSelect) rmSelect.value = rmId;
                                    }
                                }, 800); // 300ms delay (adjust if needed)
                            }
                        }

                    }

                    setupClientBadgeHandler(modal, modalType, data);

                    if (mode === "view") {
                        inputs.forEach(input => {
                            input.disabled = true
                            // console.log('input disabled: ', input);
                        });
                    } else {
                        const passwordField = modal.querySelector('input[name="password"]');
                        if (passwordField) passwordField.value = '';
                    }
                } else {
                    alert(data.message || "Data not found");
                }
            })
            .catch(err => {
                console.error("Fetch error:", err);
                alert("Error loading data");
            });
    }

    setupClientBadgeHandler(modal, modalType);
    setupRoleChangeHandler(modal);

    // Initial label update for Add mode (default labels)
    updateDynamicLabels(form, modal.querySelector('select[name="dept_id"]')?.value || 0);

    // Show modal
    modal.classList.add("show");
}

function setupClientBadgeHandler(modal, modalType, data = {}) {
    // console.log('setupClientBadgeHandler run');

    const select = modal.querySelector(`.client-select-${modalType}`);
    const badgesContainer = modal.querySelector(`.client-badges-${modalType}`);
    const hiddenInput = modal.querySelector(`.clients-hidden-${modalType}`);

    if (!select || !badgesContainer || !hiddenInput) return;

    const mode = modal.querySelector('form.profile-container')?.dataset.mode || 'add';
    const isViewOnly = mode === 'view';

    // Clear old badges & hidden input
    badgesContainer.innerHTML = '';
    hiddenInput.value = '';

    // Track selected client IDs
    let selectedValues = new Set();

    // Pre-fill if editing (from backend data)
    if ((mode === 'edit' || mode === 'view') && data.assignedClients && Array.isArray(data.assignedClients)) {
        data.assignedClients.forEach(client => {
            selectedValues.add(client.client_id);
        });
    }
    // else if (hiddenInput.value) {
    //     // fallback if value is already present (e.g. modal reopened)
    //     hiddenInput.value.split(',').forEach(v => selectedValues.add(v));
    // }

    // Function to refresh badges UI
    function renderBadges() {
        // console.log('renderBadges run');

        badgesContainer.innerHTML = '';

        selectedValues.forEach(value => {
            const option = select.querySelector(`option[value="${value}"]`);
            const label = option ? option.textContent : value;

            const badge = document.createElement('span');
            badge.className = 'client-badge';
            badge.textContent = label;

            if (!isViewOnly) {
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn-remove';
                removeBtn.innerHTML = '<i class="fa-solid fa-circle-xmark"></i>';
                // removeBtn.style.fontSize = '0.7rem';
                removeBtn.addEventListener('click', () => {
                    selectedValues.delete(value);
                    updateHiddenInput();
                    renderBadges();
                });

                badge.appendChild(removeBtn);
            }

            badgesContainer.appendChild(badge);
        });
    }

    // Update hidden input value
    function updateHiddenInput() {
        // console.log('updateHiddenInput run');
        hiddenInput.value = Array.from(selectedValues).join(',');
    }


    if (!isViewOnly) { // Add/Edit Mode
        // Handle select change (adding new clients)
        select.addEventListener('change', () => {
            const value = select.value;
            if (value && !selectedValues.has(value)) {
                selectedValues.add(value);
                updateHiddenInput();
                renderBadges();
            }
            select.value = ''; // reset dropdown
        });
    } else { // View Mode
        select.disabled = true;
    }

    // Initialize hidden input & badges
    updateHiddenInput();
    renderBadges();
}

document.querySelectorAll(".cancel-btn").forEach(cancelBtn => {
    cancelBtn.addEventListener("click", () => {
        const modal = cancelBtn.closest(".modal-dialog");
        modal.classList.remove("show");
    });
});

// Helper function
function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Toggle Modal Fields editable/disabled
// document.addEventListener("DOMContentLoaded", () => {
//     document.querySelectorAll(".edit-profile-btn").forEach(editBtn => {
//         editBtn.addEventListener("click", () => {
//             const modal = editBtn.closest(".modal");
//             const inputs = modal.querySelectorAll(".editable");
//             const saveBtn = modal.querySelector(".save-profile-btn");
//             // const cancelBtn = modal.querySelector(".cancel-btn");

//             // Toggle edit mode
//             const isDisabled = inputs[0].disabled;
//             inputs.forEach(input => input.disabled = !isDisabled);

//             if (isDisabled) {
//                 saveBtn.style.display = "inline-block";
//             } else {
//                 saveBtn.style.display = "none";
//             }
//         });
//     });

//     // Handle cancel button
//     document.querySelectorAll(".cancel-btn").forEach(cancelBtn => {
//         cancelBtn.addEventListener("click", () => {
//             const modal = cancelBtn.closest(".modal-dialog");
//             modal.classList.remove("show");
//         });
//     });
// });


// Cascade RM dropdown with Dept Dropdown
// document.addEventListener("change", function (e) {
//     if (e.target && e.target.id.includes("dept")) {
//         const deptSelect = e.target;
//         const form = deptSelect.closest(".profile-container");
//         const rmSelect = form.querySelector("select[name='rm_id']");

//         const deptId = deptSelect.value;
//         if (!rmSelect) return;

//         // Reset RM dropdown
//         rmSelect.innerHTML = `<option value="">Select Manager</option>`;

//         if (!deptId) return;

//         fetch('api/employee', {
//             method: 'POST',
//             headers: {
//                 'Content-Type': 'application/x-www-form-urlencoded',
//             },
//             body: `action=get_rms_by_dept&dept_id=${deptId}`
//         })
//             .then(res => res.json())
//             .then(data => {
//                 if (data.status === 'success') {
//                     data.rms.forEach(rm => {
//                         const option = document.createElement('option');
//                         option.value = rm.emp_id;
//                         option.textContent = rm.name;
//                         rmSelect.appendChild(option);
//                     });
//                 }
//             })
//             .catch(err => {
//                 console.error("Error fetching RM list:", err);
//             });
//     }
// });


// Handle dept subdept and rm selects
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('select[name="dept_id"]').forEach(deptSelect => {
        deptSelect.addEventListener('change', async function () {
            // console.log("change event on dept select triggered");

            const form = this.closest('form');
            const deptId = this.value;

            updateDynamicLabels(form, deptId);

            const subdeptSelect = form.querySelector('select[name="subdept_id"]');
            // const rmSelect = form.querySelector('select[name="rm_id"]');

            // Fetch and populate subdepartments if subdept select exists
            if (subdeptSelect) {
                subdeptSelect.innerHTML = `<option value="">Loading...</option>`;
                try {
                    const res = await fetch('api/subdepartment', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=getByDepartment&id=${deptId}`
                    });
                    const data = await res.json();
                    if (data.status === "success") {
                        subdeptSelect.innerHTML = `<option value="">Select Sub Department</option>`;
                        data.subdepartments.forEach(sub => {
                            const opt = document.createElement('option');
                            opt.value = sub.subdept_id;
                            opt.textContent = sub.subdept_name;
                            subdeptSelect.appendChild(opt);
                        });
                    } else {
                        subdeptSelect.innerHTML = `<option value="">No Sub Departments</option>`;
                    }

                } catch (e) {
                    subdeptSelect.innerHTML = `<option value="">Failed to load</option>`;
                }
            }

            // if (rmSelect) {
            //     // const subdeptId = subdeptSelect?.value;
            //     // if (subdeptId) {
            //     //     fetchRMsBySubdept(subdeptId, rmSelect);
            //     // } else {
            //     //     fetchRMsByDept(deptId, rmSelect);
            //     // }
            //     fetchRMsByDept(deptId, rmSelect);

            // }

        });
    });

    // For Executive: fetch RM by subdept (or fallback to dept if subdept is not selected)
    // document.querySelectorAll('select[name="subdept_id"]').forEach(subdeptSelect => {
    //     subdeptSelect.addEventListener('change', function () {
    //         console.log("change event on dept select triggered");
    //         const form = this.closest('form');
    //         const rmSelect = form.querySelector('select[name="rm_id"]');
    //         const deptId = form.querySelector('select[name="dept_id"]').value;
    //         const subdeptId = this.value;

    //         if (rmSelect) {
    //             if (subdeptId) {
    //                 fetchRMsBySubdept(subdeptId, rmSelect);
    //             } else {
    //                 fetchRMsByDept(deptId, rmSelect);
    //             }
    //         }
    //     });
    // });
});

// Utility: Fetch RMs by Department
async function fetchRMsByDept(deptId, selectEl) {
    console.log("fetchRMsByDept called");
    selectEl.innerHTML = `<option value="">Loading...</option>`;
    try {
        const res = await fetch('api/employee', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=get-rms&dept_id=${deptId}`
        });
        const data = await res.json();
        if (data.status === "success") {
            selectEl.innerHTML = `<option value="">Select Reporting Manager</option>`;
            data.rms.forEach(rm => {
                const opt = document.createElement('option');
                opt.value = rm.emp_id;
                opt.textContent = rm.name;
                selectEl.appendChild(opt);
            });
        } else {
            selectEl.innerHTML = `<option value="">No Managers</option>`;
        }

    } catch (e) {
        selectEl.innerHTML = `<option value="">Failed to load</option>`;
    }
}

// Utility: Fetch RMs by Subdepartment
// async function fetchRMsBySubdept(subdeptId, selectEl) {
//     console.log("fetchRMsBySubdept called");

//     selectEl.innerHTML = `<option value="">Loading...</option>`;
//     try {
//         const res = await fetch('api/employee', {
//             method: 'POST',
//             headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
//             body: `action=get-rms&subdept_id=${subdeptId}`
//         });
//         const data = await res.json();
//         if (data.status === "success") {
//             selectEl.innerHTML = `<option value="">Select Reporting Manager</option>`;
//             data.rms.forEach(rm => {
//                 const opt = document.createElement('option');
//                 opt.value = rm.emp_id;
//                 opt.textContent = rm.name;
//                 selectEl.appendChild(opt);
//             });
//         } else {
//             selectEl.innerHTML = `<option value="">No Managers</option>`;

//         }

//     } catch (e) {
//         selectEl.innerHTML = `<option value="">Failed to load</option>`;
//     }
// }

// Close modal logic
document.querySelectorAll('.close-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        btn.closest('.modal-dialog').classList.remove('show');
    });
});

// Submission Logic with Validations
document.querySelectorAll('.modal-dialog form').forEach(form => {
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        let isValid = true;
        // Validate text inputs
        form.querySelectorAll('input[type="text"]:not([hidden]), input[type="email"]').forEach(input => {
            const errorEl = input.nextElementSibling;
            const value = input.value.trim();

            // console.log('input: ', input);
            // console.log('erEl: ', errorEl);


            if (input.hasAttribute('required') && value === '') {
                errorEl.textContent = 'This field is required';
                errorEl.style.display = 'block';
                isValid = false;
            } else if (input.type === 'email' && input.name === 'client_email') {
                const emailPattern = /^[^@]+@[^@]+\.[^@]+$/;

                if (!emailPattern.test(value)) {
                    errorEl.textContent = 'Please enter a valid email';
                    errorEl.style.display = 'block';
                    isValid = false;
                } else {
                    errorEl.style.display = 'none';
                }
            } else if (input.type === 'email') {
                // If field is optional (no required attr) and empty, skip validation
                if (value === '' && !input.hasAttribute('required')) {
                    errorEl.style.display = 'none';
                } else {
                    const emailPattern = /^[^@]+@[^@]+\.[^@]+$/;
                    const allowedDomains = ['wildnetedge.com', 'yopmail.com', 'wildnet.global', 'wildnettechnologies.com'];
                    const emailDomain = value.split('@')[1]?.toLowerCase();

                    if (!emailPattern.test(value)) {
                        errorEl.textContent = 'Please enter a valid email';
                        errorEl.style.display = 'block';
                        isValid = false;
                    } else if (!(input.id === 'client-email') && !allowedDomains.includes(emailDomain)) {
                        errorEl.textContent = `Only company emails (${allowedDomains.join(', ')}) are allowed`;
                        errorEl.style.display = 'block';
                        isValid = false;
                    } else {
                        errorEl.style.display = 'none';
                    }
                }
            }
            else if (input.name === 'emp_id') {
                const trimmed = value.trim();

                // Remove leading zeros 
                const normalized = trimmed.replace(/^0+/, '') || '0';

                // Convert to number
                const empId = Number(normalized);

                if (!/^\d+$/.test(trimmed) || empId < 1 || empId > 9999) {
                    errorEl.textContent = 'Please enter a valid Emp Id (1-9999)';
                    errorEl.style.display = 'block';
                    isValid = false;
                }
            }
            else if (input.name === 'client_id') {
                const trimmed = value.trim();
                const normalized = trimmed.replace(/^0+/, '') || '0';


                if (!/^[A-Za-z0-9\_\-]+$/.test(normalized)) {
                    errorEl.textContent = 'Please enter a valid name (letters, numbers, hyphens & underscores only (no spaces))';
                    errorEl.style.display = 'block';
                    isValid = false;
                    // return;
                }
            }
            else if (input.id.endsWith('client-name')) {
                // Client name → allow alphanumeric + space + - + &
                if (!/^[A-Za-z0-9\s\-\&]+$/.test(value)) {
                    errorEl.textContent = 'Please enter a valid name (letters, numbers, spaces, hyphens, & only)';
                    errorEl.style.display = 'block';
                    isValid = false;
                    // return;
                }
            }
            else if (input.id.endsWith('name')) {
                // Other names → allow only alphabets + space + - + &
                if (!/^[A-Za-z\s\-\&]+$/.test(value)) {
                    errorEl.textContent = 'Please enter a valid name (alphabets, spaces, hyphens, & only)';
                    errorEl.style.display = 'block';
                    isValid = false;
                    // return;
                }
            }
            else {
                errorEl.style.display = 'none';
            }


            // if (!isValid)
            //     return;
        });


        // password + confirm-password validation
        const passwordInput = form.querySelector('input[name="password"]');
        const confirmInput = form.querySelector('input[name="confirm-password"]');

        if (passwordInput && passwordInput.value.trim() !== '') {
            const passErr = passwordInput.closest('.password-wrapper').nextElementSibling;
            if (passwordInput.value.length < 6) {
                passErr.textContent = 'Password must be at least 6 characters';
                passErr.style.display = 'block';
                isValid = false;
            } else {
                passErr.style.display = 'none';
            }

            if (confirmInput) {
                // const confirmErr = confirmInput.nextElementSibling;
                const confirmErr = confirmInput.closest('.password-wrapper').nextElementSibling;
                if (confirmInput.value !== passwordInput.value) {
                    confirmErr.textContent = 'Passwords do not match';
                    confirmErr.style.display = 'block';
                    isValid = false;
                } else {
                    confirmErr.style.display = 'none';
                }
            }
        }

        // Validate selects
        form.querySelectorAll('select').forEach(select => {
            const errorEl = select.nextElementSibling;
            // console.log('Validating select:', select.name, 'Value:', select.value);

            if (select.hasAttribute('required') && (!select.value || select.value.trim() === '')) {
                errorEl.textContent = 'Please select an option';
                errorEl.style.display = 'block';
                isValid = false;
                console.log('Select invalid →', select.name);
            } else {
                errorEl.style.display = 'none';
            }
            // console.log('Validating select:', select.name, 'Value:', select.value);

        });


        if (!isValid) {
            isValid = true;
            // console.log('not valid here');

            return;
        }

        // Submit if valid
        // if (isValid) {
        // console.log('Form is valid, submit via AJAX or further handling here...');
        // return;
        // yourSubmitHandler(form); // or call save logic

        const mode = form.dataset.mode;
        const modalType = form.dataset.modalType;
        const id = form.dataset.id || '';
        const submitBtn = form.querySelector("button[type='submit']");
        const formData = new FormData(form);
        formData.append("action", mode);
        if (id) formData.append("id", id);

        let apiUrl;
        switch (modalType) {
            case 'department':
                apiUrl = "api/department";
                break;
            case 'sub-department':
                apiUrl = "api/subdepartment";
                break;
            case 'client':
                apiUrl = "api/client";
                break;
            default:
                apiUrl = "api/employee";
        }


        // Disable button to prevent repeat submission
        submitBtn.disabled = true;
        showSpinner(); //Show spinner

        fetch(apiUrl, {
            method: "POST",
            body: formData
        })
            .then(res => {
                if (!res.ok) {
                    throw new Error(`Server responded with ${res.status}`);
                }
                return res.json().catch(() => {
                    throw new Error("Invalid json response received.");
                });
                // res.json()
            })
            .then(data => {
                // alert(data.message);
                if (data.status === "success") {
                    // form.closest(".modal-dialog").classList.remove("show");
                    // showToast(data.message);
                    // Optional: Reload table or data view
                    // setTimeout(() => window.location.reload(), 2000);
                    window.location.reload();

                } else if (data.status === "warning") {
                    showToast(data.message, true);
                    // Still reload if required
                    // window.location.reload();
                }

                else {
                    // form.closest(".modal-dialog").classList.remove("show");
                    showToast(data.message || "Unknown server error", true);
                }
            })
            .catch(err => {
                console.error("Fetch error:", err);
                // alert("Submission failed.");
                showToast("Submission failed. " + err, true);
            })
            .finally(() => {
                submitBtn.disabled = false;
                hideSpinner(); //HIDE spinner
            });

        // }
    });


    // Hide error on user input
    form.querySelectorAll('input, select').forEach(el => {
        el.addEventListener('input', () => {
            const errorEl = el.nextElementSibling;
            if (errorEl && errorEl.classList.contains('error')) {
                errorEl.style.display = 'none';
            }
        });
    });
});

// show/hide password toggle
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".eye-icon").forEach(icon => {
        icon.addEventListener("click", togglePasswordVisibility);
    });
});

// password toggle function
function togglePasswordVisibility(e) {
    const icon = e.currentTarget;
    const passwordInput = icon.closest(".password-wrapper").querySelector("input[type='password'], input[type='text']");
    const eyeIcon = icon.querySelector("i");

    if (!passwordInput) return;

    const isHidden = passwordInput.type === "password";
    passwordInput.type = isHidden ? "text" : "password";

    eyeIcon.classList.toggle("fa-eye-slash", !isHidden);
    eyeIcon.classList.toggle("fa-eye", isHidden);
}


// Form Submission Logic
// document.querySelectorAll(".profile-container").forEach(form => {
//     form.addEventListener("submit", function (e) {
//         e.preventDefault();

//         const mode = form.dataset.mode;
//         const modalType = form.dataset.modalType;
//         const id = form.dataset.id || '';
//         const submitBtn = form.querySelector("button[type='submit']");
//         const formData = new FormData(form);
//         formData.append("action", mode);
//         if (id) formData.append("id", id);

//         let apiUrl;
//         switch (modalType) {
//             case 'department':
//                 apiUrl = "api/department";
//                 break;
//             case 'sub-department':
//                 apiUrl = "api/sub-department";
//                 break;
//             default:
//                 apiUrl = "api/employee";
//         }


//         // Disable button to prevent repeat submission
//         submitBtn.disabled = true;

//         fetch(apiUrl, {
//             method: "POST",
//             body: formData
//         })
//             .then(res => res.json())
//             .then(data => {
//                 // alert(data.message);
//                 if (data.status === "success") {
//                     form.closest(".modal-dialog").classList.remove("show");
//                     showToast(data.message);
//                     // Optional: Reload table or data view
//                     setTimeout(() => window.location.reload(), 3500);
//                 } else {
//                     // form.closest(".modal-dialog").classList.remove("show");
//                     showToast(data.message, true);
//                 }
//             })
//             .catch(err => {
//                 console.error(err);
//                 alert("Submission failed.");
//             })
//             .finally(() => {
//                 submitBtn.disabled = false;
//             });
//     });
// });

// delete employee logic
// document.addEventListener('click', (e) => {
//     if (e.target.classList.contains('delete-btn-emp')) {
//         const empId = e.target.dataset.id;
//         const row = e.target.closest('tr');

//         showAlert(
//             'Are you sure you want to delete this employee?',
//             () => {
//                 fetch('api/employee', {
//                     method: 'POST',
//                     headers: { 'Content-Type': 'application/json' },
//                     body: JSON.stringify({ action: 'delete', id: empId })
//                 })
//                     .then(res => res.json())
//                     .then(data => {
//                         if (data.status === 'success') {
//                             row.remove();
//                             showToast(data.message || "Deleted successfully.");
//                         } else {
//                             showToast(data.message || "Deletion failed.", true);
//                         }
//                         hideAlert();
//                     })
//                     .catch(err => {
//                         console.error(err);
//                         showToast("Something went wrong.", true);
//                         hideAlert();
//                     });
//             },
//             () => {
//                 console.log('Deletion Cancelled');
//             },
//             "Confirm",
//             "Cancel"
//         );
//     }
// });

// delete dept/subdept logic
// document.addEventListener('click', (e) => {
//     if (e.target.classList.contains('delete-btn-dep')) {
//         const id = e.target.dataset.id;
//         const type = e.target.dataset.modal; // "department" or "subdepartment"
//         console.log(e.target.dataset);

//         const row = e.target.closest('tr');

//         const apiUrl = type === 'sub-department'
//             ? 'api/subdepartment'
//             : 'api/department';

//         const entityName = type === 'sub-department' ? 'subdepartment' : 'department';

//         showAlert(
//             `Are you sure you want to delete this ${entityName}?`,
//             () => {
//                 fetch(apiUrl, {
//                     method: 'POST',
//                     headers: { 'Content-Type': 'application/json' },
//                     body: JSON.stringify({ action: 'delete', id })
//                 })
//                     .then(res => res.json())
//                     .then(data => {
//                         if (data.status === 'success') {
//                             row.remove();
//                             showToast(`${entityName.charAt(0).toUpperCase() + entityName.slice(1)} deleted successfully.`);
//                         } else {
//                             showToast(data.message || `Failed to delete ${entityName}.`, true);
//                         }
//                         hideAlert();
//                     })
//                     .catch(err => {
//                         console.error(err);
//                         showToast("Something went wrong.", true);
//                         hideAlert();
//                     });
//             },
//             () => {
//                 console.log(`${entityName} deletion cancelled`);
//             },
//             "Confirm", "Cancel"
//         );
//     }
// });


// Unified delete logic for all entities
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('delete-btn')) {
        const id = e.target.dataset.id;
        const entity = e.target.dataset.entity || 'employee'; // e.g. employee, client, department, sub-department
        const row = e.target.closest('tr');

        // Determine correct API endpoint
        let apiUrl = '';
        switch (entity) {
            case 'client':
                apiUrl = 'api/client';
                break;
            case 'department':
                apiUrl = 'api/department';
                break;
            case 'sub-department':
            case 'subdepartment':
                apiUrl = 'api/subdepartment';
                break;
            default:
                apiUrl = 'api/employee';
        }

        const entityLabel = entity.replace('-', ' '); // for better message formatting

        showAlert(
            `Are you sure you want to delete this ${entityLabel} with id: ${id}?`,
            () => {
                fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        id: id
                    })
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            if (row) row.remove();
                            showToast(`${entityLabel.charAt(0).toUpperCase() + entityLabel.slice(1)} with id: ${id} deleted successfully.`);
                        } else {
                            showToast(data.message || `Failed to delete ${entityLabel}.`, true);
                        }
                        hideAlert();
                    })
                    .catch(err => {
                        console.error(err);
                        showToast(`Something went wrong while deleting ${entityLabel}.`, true);
                        hideAlert();
                    });
            },
            () => {
                console.log(`${entityLabel} deletion cancelled`);
            },
            "Confirm",
            "Cancel"
        );
    }
});




document.addEventListener('DOMContentLoaded', () => {
    const statusToggles = document.querySelectorAll('.toggle-status');

    statusToggles.forEach(toggle => {
        toggle.addEventListener('change', async function () {
            const entityType = this.dataset.entity || 'employee'; // 'employee' or 'client'
            const id = this.dataset.id;
            const newStatus = this.checked ? 1 : 0;

            // Determine API endpoint based on entity type
            const apiUrl = entityType === 'client' ? 'api/client' : 'api/employee';

            try {
                const response = await fetch(apiUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'toggle_status',
                        [`${entityType}_id`]: id, // dynamically send employee_id or client_id
                        status: newStatus
                    })
                });

                const result = await response.json();

                if (result.status === 'success') {
                    console.log(`${entityType} '${id}' status updated to ${newStatus == 1 ? 'Active' : 'Inactive'}`);
                    showToast(`${entityType.charAt(0).toUpperCase() + entityType.slice(1)} '${id}' status updated to ${newStatus == 1 ? 'Active' : 'Inactive'}`);
                } else {
                    showToast(`Status Update Failed : ${result.message || 'Unknown error'}`, true);
                    console.error(`Failed to update status for ${entityType} '${id}': ${result.message || 'Unknown error'}`);
                    this.checked = !this.checked; // Rollback UI
                }

            } catch (error) {
                console.error('Error:', error);
                showToast('Error updating status.', true);
                this.checked = !this.checked; // Rollback UI
            }
        });
    });
});



document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.search-input').forEach(input => {
        input.addEventListener('input', function () {
            const tabPanel = this.closest('.tab-panel');
            const tableBody = tabPanel.querySelector('tbody');
            const rows = Array.from(tableBody.querySelectorAll('tr:not(.no-entries-row)'));
            const noDataRow = tableBody.querySelector('.no-entries-row');
            const query = this.value.trim().toLowerCase();

            let visibleRowsCount = 0;
            rows.forEach(row => {
                const rowText = Array.from(row.cells).map(cell => cell.textContent.toLowerCase()).join(' ');
                if (rowText.includes(query)) {
                    row.style.display = '';
                    visibleRowsCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noDataRow) {
                noDataRow.style.display = visibleRowsCount === 0 ? '' : 'none';
            }
        });
    });

});

function setupRoleChangeHandler(modal) {
    const roleSelect = modal.querySelector('.role-select');
    if (!roleSelect) return;

    const dynamicFields = modal.querySelector('.dynamic-fields');
    const rmSelect = modal.querySelector('select[name="rm_id"]');

    function toggleFields() {
        const role = roleSelect.value;
        if (role === 'hod') {
            if (dynamicFields) dynamicFields.style.display = 'none';
            if (rmSelect) rmSelect.removeAttribute('required');
        } else {
            if (dynamicFields) dynamicFields.style.display = 'flex';
            if (rmSelect) rmSelect.setAttribute('required', 'true');
        }
    }

    roleSelect.addEventListener('change', toggleFields);

    // Initial check
    toggleFields();
}

/**
 * Dynamically updates labels and placeholders in a form based on Department ID.
 * Department 5 -> "Project", Others -> "Client"
 */
function updateDynamicLabels(form, deptId) {
    if (!form) return;
    const isProject = (parseInt(deptId) === 5);
    const targetLabel = isProject ? 'Project' : 'Client';
    const sourceLabel = isProject ? 'Client' : 'Project';

    // Update labels like "Select Clients" -> "Select Projects"
    form.querySelectorAll('label').forEach(label => {
        if (label.textContent.includes(sourceLabel)) {
            label.textContent = label.textContent.replace(new RegExp(sourceLabel, 'g'), targetLabel);
        }
    });

    // Update select options (placeholders)
    form.querySelectorAll('select[name="client-select"], select[name="emp-client"], select[name="client_id"]').forEach(select => {
        if (select.options.length > 0 && select.options[0].value === "") {
            if (select.options[0].textContent.includes(sourceLabel)) {
                select.options[0].textContent = select.options[0].textContent.replace(new RegExp(sourceLabel, 'g'), targetLabel);
            }
        }
    });

    // Update modal title if needed
    const modalTitle = form.closest('.modal-dialog')?.querySelector('.modal-title');
    if (modalTitle && modalTitle.textContent.includes(sourceLabel)) {
        modalTitle.textContent = modalTitle.textContent.replace(new RegExp(sourceLabel, 'g'), targetLabel);
    }
}



