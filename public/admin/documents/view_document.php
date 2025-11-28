<?php
/**
 * View/Serve Document
 * Securely serves documents to authorized admin users
 */

session_start();
require_once '../../../config/database.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/document_functions.php';

// Check authentication
requireAuth();

// Get document ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('Invalid document ID');
}

$document_id = (int)$_GET['id'];

// Get database connection
$conn = getDBConnection();

// Get user info for logging
$user_id = $_SESSION['user_id'] ?? null;

// Check if download or view
$action = isset($_GET['download']) ? 'download' : 'view';

if ($action === 'download') {
    downloadDocument($conn, $document_id, $user_id, 'admin');
} else {
    serveDocument($conn, $document_id, $user_id, 'admin');
}

closeDBConnection($conn);
