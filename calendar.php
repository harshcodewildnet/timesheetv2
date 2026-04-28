<?php
header("X-Robots-Tag: noindex, nofollow", true);

// require_once 'config/session_init.php';
require_once 'config/session_check.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';

requireRole(['admin']);

$emp_id = $_SESSION['emp_id'];
$empObj = new Employee($conn);
$employee = $empObj->getEmployeeById($emp_id);

// Fetch holidays from DB
// $stmt = $conn->query("SELECT id, type, date, description FROM calendar");
// $holidays = $stmt->fetch_all(MYSQLI_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <script>
        if (localStorage.getItem('sidebar-collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-collapsed');
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Calendar</title>
    <link rel="stylesheet" href="assets/css/index2.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <style>
        body {
            background-color: var(--main-color);
        }

        .nav-item .nav-link:hover {
            background-color: #fff;
        }

        .tab-content {
            background-color: var(--main-color-yellow-light);
        }

        .fc .fc-toolbar-title {
            /* color: #ff8813; */
        }

        .fc a {
            color: #ff8813;
            text-decoration: none;
            /* any color */
        }

        .fc a.fc-event {
            background-color: var(--main-color-yellow);
            border: 1px solid var(--main-color-yellow);
        }

        .fc .fc-daygrid-day-frame {
            padding: 4px;
        }

        .fc .fc-col-header-cell-cushion {
            color: #ff8813;
        }

        .fc .fc-daygrid-day-number {
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
        }
    </style>
</head>

<body class="">
    <!-- Header Dblist-->
    <header>
        <a href="dashboard-<?= $_SESSION['emp_role'] ?>">
            <div class="logo">
                <img src="assets/images/wnet-image.png" alt="WildNet logo">
            </div>
        </a>
        <div class="search-area">
        </div>
    </header>
    <?php
    include_once "includes/sidebar-" . $employee['role'] . ".php";
    ?>
    <main>
        <div class="top-row">
            <!-- <h3 class="">Home</h3> -->
            <div class="left">
                <h3>Calendar</h3>
            </div>
            <div class="right">
            </div>
        </div>
        <div class="content-area">

            <!-- ///////////////////////// -->

            <!-- Nav Tabs -->
            <ul class="nav nav-tabs" id="calendarTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-dark active" id="view-tab" data-bs-toggle="tab" data-bs-target="#view"
                        type="button" role="tab">Calendar View</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-dark" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload"
                        type="button" role="tab">Upload Holidays</button>
                </li>
            </ul>

            <div class="tab-content mt-3 p-3 d-flex flex-grow-1 flex-column" id="calendarTabsContent">
                <!-- Calendar View -->
                <div class="tab-pane fade flex-grow-1 show active" id="view" role="tabpanel">
                    <?php
                    $stmt = $conn->query("SELECT id, type, date, description FROM calendar ORDER BY date ASC");
                    $holidays = $stmt->fetch_all(MYSQLI_ASSOC);
                    ?>
                    <div id="calendar"></div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            var calendarEl = document.getElementById('calendar');
                            var calendar = new FullCalendar.Calendar(calendarEl, {
                                initialView: 'dayGridMonth',
                                height: '100%',
                                events: <?php echo json_encode(array_map(function ($h) {
                                    $color = '#ff8813'; // Default orange
                                    if ($h['type'] === 'Public Holiday') $color = '#d9534f'; // Red
                                    if ($h['type'] === 'Floater Leave') $color = '#5bc0de'; // Blue
                                    if ($h['type'] === 'Week Off') $color = '#f0ad4e'; // Yellow/Orange
                                    
                                    return [
                                        'title' => $h['description'] . ' (' . $h['type'] . ')',
                                        'start' => $h['date'],
                                        'backgroundColor' => $color,
                                        'borderColor' => $color,
                                        'allDay' => true
                                    ];
                                }, $holidays)); ?>
                            });
                            calendar.render();
                        });
                    </script>
                </div>

                <!-- Upload Holidays -->
                <div class="tab-pane fade" id="upload" role="tabpanel">
                    <!-- <h4>Add a Holiday</h4>
                    <form id="caleder-form" action="api/add-holiday" method="post" class="mb-4">
                        <div class="mb-2">
                            <input type="text" name="title" class="form-control" placeholder="Holiday Name" required>
                        </div>
                        <div class="mb-2">
                            <input type="date" name="date" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <textarea name="description" class="form-control" placeholder="Description"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form> -->
                    <div class="top-row p-0 mb-4">
                        <h4>Bulk Upload via Excel Sheet</h4>
                        <a href="includes/email_templates/calendar_template.xlsx" download="calendar-template.xlsx"
                            class="btn btn-success">
                            <i class="fa fa-download"></i> Download Template
                        </a>

                    </div>
                    <form id="calender-form-bulk" action="api/upload-calendar" method="post"
                        enctype="multipart/form-data">
                        <div class="mb-4">
                            <input type="file" name="xls-file" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-secondary">Upload Excel Sheet</button>
                    </form>
                </div>
            </div>

            <!-- ////////////////////////// -->

        </div>

    </main>
    <!-- <footer></footer> -->

    <!-- Message Toast -->
    <div id="toast">Saved Successfully!</div>

    <!-- Custom Alert -->
    <div id="custom-alert" class="alert-overlay">
        <div class="alert-box" id="alert-box">
            <p id="alert-message">Alert Message</p>
            <div class="alert-actions">
                <button id="alert-confirm-btn">Yes</button>
                <button id="alert-cancel-btn">No</button>
            </div>
        </div>
    </div>

    <?php
    // Edit Profile Photo Modal 
    include_once 'photo-modal.php';
    ?>

    <!-- <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events:
                 <?php
                 //  echo json_encode(array_map(function ($h) {
                 //     return [
                 //         'title' => $h['title'],
                 //         'start' => $h['date'],
                 //         'description' => $h['description']
                 //     ];
                 // }, $holidays)); 
                 ?>
            });
            calendar.render();
        });
    </script> -->

    <!-- Show Toast Script -->
    <script>
        function showToast(message, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = message;

            if (isError) {
                toast.style.backgroundColor = '#ef828c';
            } else {
                toast.style.backgroundColor = '#5eda7b';
            }

            toast.className = 'show';

            setTimeout(() => { toast.classList.remove('show') }, 3000);
        }

    </script>

    <!-- Session message Script -->

    <?php
    if (!empty($_SESSION['message'])) {
        echo "
                <script>
                    showToast('" . $_SESSION['message'] . "', " . !$_SESSION['success'] . ");
                </script>  
            ";
        unset($_SESSION['message']);
        unset($_SESSION['success']);
    }
    ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <script src="assets/js/graph.js"></script> -->

    <script>

        // Date formatting function
        function formatDate(dateString) {
            const parts = dateString.split('-'); // ["YYYY", "MM", "DD"]
            return `${parts[2]}-${parts[1]}-${parts[0]}`; // "DD-MM-YYYY"
        }

    </script>
    <script src="assets/js/alert.js"></script>
    <script src="assets/js/main.js"></script>


    <style>
        /* Disable text selection */
        body {
            user-select: none;
        }
    </style>

    <script>
        // Disable right-click
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });

        // Disable specific key combinations
        document.addEventListener('keydown', function (e) {
            // F12
            if (e.key === "F12") {
                e.preventDefault();
            }
            // Ctrl+Shift+I / Ctrl+Shift+J
            if (e.ctrlKey && e.shiftKey && (e.key.toUpperCase() === 'I' || e.key.toUpperCase() === 'J')) {
                e.preventDefault();
            }
            // Ctrl+U
            if (e.ctrlKey && e.key.toUpperCase() === 'U') {
                e.preventDefault();
            }
        });

        // Disable copy
        document.addEventListener('copy', function (e) {
            e.preventDefault();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const bulkForm = document.getElementById('calender-form-bulk');
            const fileInput = bulkForm.querySelector('input[name="xls-file"]');

            bulkForm.addEventListener('submit', function (e) {
                e.preventDefault();

                const file = fileInput.files[0];

                // Validate file presence
                if (!file) {
                    alert('Please select an Excel or CSV file before uploading.');
                    return;
                }

                // Validate file type
                const allowedExtensions = ['xls', 'xlsx', 'csv'];
                const ext = file.name.split('.').pop().toLowerCase();
                if (!allowedExtensions.includes(ext)) {
                    alert('Invalid file type. Please upload only XLS, XLSX, or CSV file.');
                    return;
                }

                // Validate file size (optional: <5MB)
                const maxSize = 5 * 1024 * 1024; // 5MB
                if (file.size > maxSize) {
                    alert('File size exceeds 5MB limit. Please upload a smaller file.');
                    return;
                }

                // Show confirmation
                if (!confirm('Are you sure you want to upload this file?')) {
                    return;
                }

                // Disable form & show loading message
                const submitBtn = bulkForm.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.innerHTML = 'Uploading... <span class="spinner-border spinner-border-sm"></span>';

                // Create FormData and send request
                const formData = new FormData(bulkForm);

                fetch(bulkForm.action, {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // showToast(`CSV Upload Successfull ! ${data.message}`, false);
                            // If PHP redirects (success case)
                            // window.location.href = response.url;
                            window.location.reload();
                        } else {
                            showToast(`Upload Error ! ${data.message}`, true);
                        }
                    })
                    // .then(response => {
                    //     if (response.redirected) {
                    //         // If PHP redirects (success case)
                    //         window.location.href = response.url;
                    //     } else {
                    //         return response.text().then(text => {
                    //             alert('Upload completed: ' + text);
                    //         });
                    //     }
                    // })
                    .catch(error => {
                        alert('Error uploading file: ' + error.message);
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = 'Upload Excel Sheet';
                    });
            });
        });
    </script>


</body>

</html>