<?php
/**
 * Document Upload Processor
 * Handles AJAX file uploads from patient registration form
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/document_functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Check if patient_id exists in session
if (!isset($_SESSION['patient_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No active patient session. Please complete patient registration first.'
    ]);
    exit;
}

$patient_id = $_SESSION['patient_id'];

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method'
    ]);
    exit;
}

// Check if document type is provided
if (!isset($_POST['document_type']) || empty($_POST['document_type'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Document type is required'
    ]);
    exit;
}

$document_type = $_POST['document_type'];
$description = $_POST['description'] ?? null;

// Validate document type
$valid_types = [
    'insurance_card_front',
    'insurance_card_back',
    'photo_id_front',
    'photo_id_back',
    'medical_records',
    'prescription',
    'referral',
    'other'
];

if (!in_array($document_type, $valid_types)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid document type'
    ]);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['document']) || $_FILES['document']['error'] === UPLOAD_ERR_NO_FILE) {
    echo json_encode([
        'success' => false,
        'error' => 'No file uploaded'
    ]);
    exit;
}

// Get database connection
$conn = getDBConnection();

// Upload the document
$result = uploadDocument(
    $conn,
    $_FILES['document'],
    $patient_id,
    $document_type,
    'patient',
    null,
    $description
);

// Close connection
closeDBConnection($conn);

// Return result
echo json_encode($result);
