<?php
/**
 * Update Patient Status API
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

// Require authentication
if (!isSessionValid()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = getDBConnection();
$admin_id = getCurrentAdminId();

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$patient_id = intval($input['patient_id'] ?? 0);
$new_status = $input['status'] ?? '';

// Validate input
$valid_statuses = ['registered', 'checked_in', 'in_progress', 'completed', 'cancelled'];
if ($patient_id === 0 || !in_array($new_status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

// Get current status
$sql = "SELECT current_status FROM patient_status WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$result = $stmt->get_result();
$current = $result->fetch_assoc();

if (!$current) {
    echo json_encode(['success' => false, 'message' => 'Patient not found']);
    exit();
}

// Update status
$timestamp_field = $new_status . '_at';
$admin_field = ($new_status === 'checked_in' || $new_status === 'completed') ? ($new_status === 'checked_in' ? 'checked_in_by' : 'completed_by') : null;

$sql = "UPDATE patient_status SET current_status = ?, {$timestamp_field} = NOW()";
if ($admin_field) {
    $sql .= ", {$admin_field} = ?";
}
$sql .= " WHERE patient_id = ?";

$stmt = $conn->prepare($sql);
if ($admin_field) {
    $stmt->bind_param("sii", $new_status, $admin_id, $patient_id);
} else {
    $stmt->bind_param("si", $new_status, $patient_id);
}

if ($stmt->execute()) {
    // Log action
    logAdminAction(
        $admin_id,
        'UPDATE',
        'patient_status',
        $patient_id,
        "Changed patient status from {$current['current_status']} to {$new_status}",
        $patient_id,
        ['status' => $current['current_status']],
        ['status' => $new_status]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully',
        'new_status' => $new_status
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();
?>
