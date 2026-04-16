// Expected variables: $emp_name, $rm_name, $hod_name, $budget, $clientId, $clientName, $dept_id
$label = getClientLabel($dept_id);
ob_start();
?>
<div style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">
    <h2 style="color:#0066cc;"><?= $label ?> Budget Change Notification</h2>
    <p>Dear <?= htmlspecialchars($rm_name ?: 'Reporting Manager') ?> / <?= htmlspecialchars($hod_name ?: 'HOD') ?>,</p>

    <p>This is to inform you that <strong><?= htmlspecialchars($emp_name) ?></strong> has updated the budget for
        <?= strtolower($label) ?>:
    </p>

    <table style="border-collapse:collapse; margin-top:10px;">
        <tr>
            <td style="padding:4px 8px; font-weight:bold;"><?= $label ?> ID:</td>
            <td style="padding:4px 8px;"><?= htmlspecialchars($clientId) ?></td>
        </tr>
        <tr>
            <td style="padding:4px 8px; font-weight:bold;"><?= $label ?> Name:</td>
            <td style="padding:4px 8px;"><?= htmlspecialchars($clientName) ?></td>
        </tr>
        <tr>
            <td style="padding:4px 8px; font-weight:bold;">New Budget:</td>
            <td style="padding:4px 8px; color:#008000; font-size:16px; font-weight:bold;">
                &#8377;<?= htmlspecialchars($budget) ?></td>
        </tr>
    </table>

    <p style="margin-top:15px;">Please review and take necessary action if required.</p>

    <p style="font-size:12px; color:#777; margin-top:20px;">This is an automated notification - please do not reply.</p>
    <b>
        <p>
            Regards,</br>
            Timesheet System</br>
            Wildnet Technologies Limited
        </p>
    </b>
</div>
<?php
$html = ob_get_clean();
