<?php
/**
 * Patient Data Export - CSV and PDF
 */
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

requireAuth();

$conn = getDBConnection();
$admin_id = getCurrentAdminId();

$format = $_GET['format'] ?? 'csv';
$patient_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Export single patient to PDF
if ($format === 'pdf' && $patient_id > 0) {
    // Get patient data
    $sql = "SELECT * FROM vw_admin_patients WHERE patient_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();

    if (!$patient) {
        die('Patient not found');
    }

    // Log export
    logAdminAction($admin_id, 'EXPORT', 'patients', $patient_id, 'Exported patient details to PDF', $patient_id);

    // For simplicity, we'll create an HTML version that can be printed to PDF
    // In production, you might use a library like TCPDF or mPDF
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Patient Record - <?php echo htmlspecialchars($patient['full_name']); ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 12px;
                line-height: 1.6;
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            h1 { color: #0066cc; font-size: 24px; margin-bottom: 10px; }
            h2 { color: #004d99; font-size: 18px; margin-top: 20px; margin-bottom: 10px; border-bottom: 2px solid #0066cc; padding-bottom: 5px; }
            h3 { font-size: 14px; margin-top: 15px; margin-bottom: 5px; }
            .info-grid { display: grid; grid-template-columns: 200px 1fr; gap: 10px; margin-bottom: 20px; }
            .label { font-weight: bold; }
            .value { }
            .header { text-align: center; margin-bottom: 30px; }
            .footer { margin-top: 40px; border-top: 1px solid #ccc; padding-top: 10px; font-size: 10px; color: #666; }
            @media print {
                button { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>PATIENT MEDICAL RECORD</h1>
            <p><strong>Clinic Name:</strong> PrimeHealth Urgent Care</p>
            <p><strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?></p>
            <p><strong>Printed By:</strong> <?php echo htmlspecialchars(getCurrentAdminName()); ?></p>
        </div>

        <button onclick="window.print()" style="margin-bottom: 20px; padding: 10px 20px; background: #0066cc; color: white; border: none; cursor: pointer;">
            Print / Save as PDF
        </button>

        <h2>Patient Demographics</h2>
        <div class="info-grid">
            <div class="label">Patient ID:</div>
            <div class="value"><?php echo $patient['patient_id']; ?></div>

            <div class="label">Full Name:</div>
            <div class="value"><?php echo htmlspecialchars($patient['full_name']); ?></div>

            <div class="label">Date of Birth:</div>
            <div class="value"><?php echo date('F j, Y', strtotime($patient['date_of_birth'])); ?> (<?php echo $patient['age']; ?> years old)</div>

            <div class="label">Gender:</div>
            <div class="value"><?php echo htmlspecialchars($patient['gender']); ?></div>

            <div class="label">Registration Date:</div>
            <div class="value"><?php echo date('F j, Y g:i A', strtotime($patient['registration_date'])); ?></div>
        </div>

        <h2>Contact Information</h2>
        <div class="info-grid">
            <div class="label">Email:</div>
            <div class="value"><?php echo htmlspecialchars($patient['email']); ?></div>

            <div class="label">Cell Phone:</div>
            <div class="value"><?php echo htmlspecialchars($patient['cell_phone']); ?></div>

            <div class="label">Address:</div>
            <div class="value">
                <?php echo htmlspecialchars($patient['address']); ?><br>
                <?php echo htmlspecialchars($patient['city']); ?>, <?php echo htmlspecialchars($patient['state']); ?> <?php echo htmlspecialchars($patient['zip_code']); ?>
            </div>
        </div>

        <h2>Emergency Contact</h2>
        <div class="info-grid">
            <div class="label">Name:</div>
            <div class="value"><?php echo htmlspecialchars($patient['emergency_contact_name']); ?></div>

            <div class="label">Phone:</div>
            <div class="value"><?php echo htmlspecialchars($patient['emergency_contact_phone']); ?></div>

            <div class="label">Relationship:</div>
            <div class="value"><?php echo htmlspecialchars($patient['emergency_relationship']); ?></div>
        </div>

        <h2>Visit Information</h2>
        <div class="info-grid">
            <div class="label">Reason for Visit:</div>
            <div class="value"><?php echo nl2br(htmlspecialchars($patient['reason_for_visit'])); ?></div>
        </div>

        <?php if ($patient['insurance_provider']): ?>
        <h2>Insurance Information</h2>
        <div class="info-grid">
            <div class="label">Provider:</div>
            <div class="value"><?php echo htmlspecialchars($patient['insurance_provider']); ?></div>

            <div class="label">Policy Number:</div>
            <div class="value"><?php echo htmlspecialchars($patient['policy_number']); ?></div>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p><strong>CONFIDENTIAL PATIENT INFORMATION</strong></p>
            <p>This document contains protected health information (PHI) and must be handled in accordance with HIPAA regulations.</p>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Export patient list to CSV
if ($format === 'csv') {
    // Get filters from session or query params
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';

    // Build query
    $where_conditions = [];
    $params = [];
    $types = '';

    if ($date_from) {
        $where_conditions[] = "DATE(registration_date) >= ?";
        $params[] = $date_from;
        $types .= 's';
    }

    if ($date_to) {
        $where_conditions[] = "DATE(registration_date) <= ?";
        $params[] = $date_to;
        $types .= 's';
    }

    $where_clause = count($where_conditions) > 0 ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $sql = "SELECT
                patient_id,
                full_name,
                date_of_birth,
                age,
                gender,
                email,
                cell_phone,
                address,
                city,
                state,
                zip_code,
                reason_for_visit,
                insurance_provider,
                registration_date,
                current_status,
                drchrono_sync_status
            FROM vw_admin_patients
            {$where_clause}
            ORDER BY registration_date DESC";

    if (count($params) > 0) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }

    // Log export
    $record_count = $result->num_rows;
    logAdminAction($admin_id, 'EXPORT', 'patients', null, "Exported {$record_count} patients to CSV");

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=patients_export_' . date('Y-m-d_His') . '.csv');

    // Create output stream
    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Add headers
    fputcsv($output, [
        'Patient ID',
        'Full Name',
        'Date of Birth',
        'Age',
        'Gender',
        'Email',
        'Cell Phone',
        'Address',
        'City',
        'State',
        'ZIP',
        'Reason for Visit',
        'Insurance Provider',
        'Registration Date',
        'Current Status',
        'DrChrono Sync Status'
    ]);

    // Add data rows
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['patient_id'],
            $row['full_name'],
            $row['date_of_birth'],
            $row['age'],
            $row['gender'],
            $row['email'],
            $row['cell_phone'],
            $row['address'],
            $row['city'],
            $row['state'],
            $row['zip_code'],
            $row['reason_for_visit'],
            $row['insurance_provider'] ?? 'Self-Pay',
            $row['registration_date'],
            $row['current_status'] ?? 'registered',
            $row['drchrono_sync_status']
        ]);
    }

    fclose($output);
    exit();
}

$conn->close();
?>
