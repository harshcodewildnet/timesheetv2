<!-- Common Modal for View/Edit -->
<div class="modal-dialog" id="modal-dialog-common">
    <div class="modal">
        <div class="modal-header">
            <h4 id="modal-title">Add Task</h4>
            <button class="close-btn" id="close-btn"><i class="fa-solid fa-circle-xmark"></i></button>
        </div>
        <hr>
        <div class="modal-body">
            <!-- <div class="table-wrapper"> -->
            <table class="entry-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Working/Leave</th>
                        <th>Time Taken</th>
                        <th><?= getClientLabel($employee['dept_id'] ?? null) ?></th>
                        <th>Nature of Task</th>
                        <th>Task Brief</th>
                        <th>Description</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="addtablebody">
                    <tr class="entry-row first">
                        <td><input type="date" min="<?= date('Y-m-d', strtotime('-10 days')) ?>"
                                max="<?= date('Y-m-d') ?>" onkeydown="return false;"></td>
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
                                <!-- <option value="02:30">02:30</option> -->
                            </select>

                        </td>
                        <td>
                            <select name="emp-client">
                                <option value="" disabled selected>Select
                                    <?= getClientLabel($employee['dept_id'] ?? null) ?>
                                </option>
                                <option value="-1" disabled hidden>-</option>
                                <?php
                                if ($employeeClients) {
                                    foreach ($employeeClients as $client) {
                                        ?>
                                        <option value="<?= $client['client_id'] ?>"><?= $client['client_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
                                <option value="-1">Other</option>
                            </select>
                        </td>
                        <!-- <td><input type="text" placeholder="Enter Task Category"></td> -->
                        <td>
                            <select name="task-category">
                                <option value="" disabled selected>Select Task Category</option>
                                <option value="-1" disabled hidden>-</option>
                                <?php
                                if ($taskCategories) {
                                    foreach ($taskCategories as $taskCategory) {
                                        ?>
                                        <option value="<?= $taskCategory['cat_id'] ?>"><?= $taskCategory['cat_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
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
                        <!-- <td><input typex="text" name="task-description" placeholder="Enter Task Description"></td> -->
                        <!-- <td>
                            <select name="status" id="status" disabled>
                                <option value="0" selected>Not Approved</option>
                                <option value="1">Approved</option>
                            </select>
                        </td> -->
                        <td><i class="fa-regular fa-trash-can"></i></td>
                    </tr>
                    <tr class="entry-row">
                        <td><input type="date" min="<?= date('Y-m-d', strtotime('-10 days')) ?>"
                                max="<?= date('Y-m-d') ?>" onkeydown="return false;"></td>
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
                                <!-- <option value="02:30">02:30</option> -->
                            </select>

                        </td>
                        <td>
                            <select name="emp-client">
                                <option value="" disabled selected>Select
                                    <?= getClientLabel($employee['dept_id'] ?? null) ?>
                                </option>
                                <option value="-1" disabled hidden>-</option>
                                <?php
                                if ($employeeClients) {
                                    foreach ($employeeClients as $client) {
                                        ?>
                                        <option value="<?= $client['client_id'] ?>"><?= $client['client_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
                                <option value="-1">Other</option>
                            </select>
                        </td>
                        <!-- <td><input type="text" placeholder="Enter Task Category"></td> -->
                        <td>
                            <select name="task-category">
                                <option value="" disabled selected>Select Task Category</option>
                                <option value="-1" disabled hidden>-</option>
                                <?php
                                if ($taskCategories) {
                                    foreach ($taskCategories as $taskCategory) {
                                        ?>
                                        <option value="<?= $taskCategory['cat_id'] ?>"><?= $taskCategory['cat_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
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
                        <!-- <td><input typex="text" name="task-description" placeholder="Enter Task Description"></td> -->
                        <!-- <td>
                            <select name="status" id="status" disabled>
                                <option value="0" selected>Not Approved</option>
                                <option value="1">Approved</option>
                            </select>
                        </td> -->
                        <td><i class="fa-regular fa-trash-can"></i></td>
                    </tr>
                    <tr class="entry-row">
                        <td><input type="date" min="<?= date('Y-m-d', strtotime('-10 days')) ?>"
                                max="<?= date('Y-m-d') ?>" onkeydown="return false;"></td>
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
                                <!-- <option value="02:30">02:30</option> -->
                            </select>

                        </td>
                        <td>
                            <select name="emp-client">
                                <option value="" disabled selected>Select
                                    <?= getClientLabel($employee['dept_id'] ?? null) ?>
                                </option>
                                <option value="-1" disabled hidden>-</option>
                                <?php
                                if ($employeeClients) {
                                    foreach ($employeeClients as $client) {
                                        ?>
                                        <option value="<?= $client['client_id'] ?>"><?= $client['client_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
                                <option value="-1">Other</option>
                            </select>
                        </td>
                        <!-- <td><input type="text" placeholder="Enter Task Category"></td> -->
                        <td>
                            <select name="task-category">
                                <option value="" disabled selected>Select Task Category</option>
                                <option value="-1" disabled hidden>-</option>
                                <?php
                                if ($taskCategories) {
                                    foreach ($taskCategories as $taskCategory) {
                                        ?>
                                        <option value="<?= $taskCategory['cat_id'] ?>"><?= $taskCategory['cat_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
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
                        <!-- <td><input typex="text" name="task-description" placeholder="Enter Task Description"></td> -->


                        <!-- <td>
                            <select name="status" id="status" disabled>
                                <option value="0" selected>Not Approved</option>
                                <option value="1">Approved</option>
                            </select>
                        </td> -->
                        <td><i class="fa-regular fa-trash-can"></i></td>
                    </tr>
                    <tr class="entry-row">
                        <td><input type="date" min="<?= date('Y-m-d', strtotime('-10 days')) ?>"
                                max="<?= date('Y-m-d') ?>" onkeydown="return false;"></td>
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
                                <!-- <option value="02:30">02:30</option> -->
                            </select>
                        </td>
                        <td>
                            <select name="emp-client">
                                <option value="" disabled selected>Select
                                    <?= getClientLabel($employee['dept_id'] ?? null) ?>
                                </option>
                                <option value="-1" disabled hidden>-</option>
                                <?php
                                if ($employeeClients) {
                                    foreach ($employeeClients as $client) {
                                        ?>
                                        <option value="<?= $client['client_id'] ?>"><?= $client['client_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
                                <option value="-1">Other</option>
                            </select>
                        </td>
                        <!-- <td><input type="text" placeholder="Enter Task Category"></td> -->
                        <td>
                            <select name="task-category">
                                <option value="" disabled selected>Select Task Category</option>
                                <option value="-1" disabled hidden>-</option>
                                <?php
                                if ($taskCategories) {
                                    foreach ($taskCategories as $taskCategory) {
                                        ?>
                                        <option value="<?= $taskCategory['cat_id'] ?>"><?= $taskCategory['cat_name'] ?></option>
                                        <?php
                                    }
                                }
                                ?>
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
                        <!-- <td><input typex="text" name="task-description" placeholder="Enter Task Description"></td> -->


                        <!-- <td>
                            <select name="status" id="status" disabled>
                                <option value="0" selected>Not Approved</option>
                                <option value="1">Approved</option>
                            </select>
                        </td> -->
                        <td><i class="fa-regular fa-trash-can"></i></td>
                    </tr>
                    <!-- <tr class="add-btn-row">
                            <td colspan="7">
                                <button class="add-btn"><i class="fa-solid fa-circle-plus"></i>Add New</button>
                            </td>
                        </tr> -->
                </tbody>
            </table>
            <div class="actions">
                <button id="submit-btn" class="add-btn">Submit</button>
                <button id="add-btn" class="add-btn"><i class="fa-solid fa-circle-plus"></i>Add New</button>
            </div>
            <!-- </div> -->
        </div>
    </div>
</div>