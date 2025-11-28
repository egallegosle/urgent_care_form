<?php
/**
 * Document Management Actions
 * Handles verify, reject, delete actions
 */

session_start();
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/document_functions.php';

// Check authentication
requireAuth();

// Set JSON response header
header('Content-Type: application/json');

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get action and document ID
$action = $_POST['action'] ?? '';
$document_id = isset($_POST['document_id']) ? (int)$_POST['document_id'] : 0;

if (!$document_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid document ID']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;

// Get database connection
$conn = getDBConnection();

$result = ['success' => false];

switch ($action) {
    case 'verify':
        $result = verifyDocument($conn, $document_id, $user_id);
        break;

    case 'reject':
        $reason = $_POST['reason'] ?? 'No reason provided';
        $result = rejectDocument($conn, $document_id, $user_id, $reason);
        break;

    case 'delete':
        $result = deleteDocument($conn, $document_id, $user_id, 'admin');
        break;

    default:
        $result = ['success' => false, 'error' => 'Invalid action'];
}

closeDBConnection($conn);

echo json_encode($result);
