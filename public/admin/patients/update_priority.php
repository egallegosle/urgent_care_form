<?php
/**
 * Update Patient Priority API
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
$new_priority = $input['priority'] ?? '';

$valid_priorities = ['normal', 'urgent', 'emergency'];
if ($patient_id === 0 || !in_array($new_priority, $valid_priorities)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$sql = "UPDATE patient_status SET priority = ? WHERE patient_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $new_priority, $patient_id);

if ($stmt->execute()) {
    logAdminAction($admin_id, 'UPDATE', 'patient_status', $patient_id, "Changed priority to {$new_priority}", $patient_id);
    echo json_encode(['success' => true, 'message' => 'Priority updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}

$conn->close();
?>
