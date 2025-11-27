<?php
/**
 * Update Patient Admin Notes API
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/auth.php';

if (!isSessionValid()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$conn = getDBConnection();
$admin_id = getCurrentAdminId();

$input = json_decode(file_get_contents('php://input'), true);
$patient_id = intval($input['patient_id'] ?? 0);
$notes = $input['notes'] ?? '';

if ($patient_id === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$sql = "UPDATE patient_status SET admin_notes = ? WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $notes, $patient_id);

if ($stmt->execute()) {
    logAdminAction($admin_id, 'UPDATE', 'patient_status', $patient_id, "Updated admin notes", $patient_id);
    echo json_encode(['success' => true, 'message' => 'Notes saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();
?>
