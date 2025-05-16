<?php
// delete-project.php
session_start();
header('Content-Type: application/json');
include "../../includes/config.php"; // Assuming this file starts session and connects to DB

if (!isset($_POST['delete_id']) || !isset($_POST['_token'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}
if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 0) {
    echo json_encode(['success' => false, 'message' => 'non si dispone di privilegi sufficienti']);
    exit();
}
$deleteId = intval($_POST['delete_id']);
$csrfToken = $_POST['_token'];

// Check CSRF token (if stored in session)
// Assuming session has 'csrf_token'
if ($_SESSION['csrf_token'] !== $csrfToken) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

try {
    $toggleSql = "UPDATE docente SET isDeleted = NOT isDeleted WHERE idDocente = :id";
    $query = $dbh->prepare($toggleSql);
    $result = $query->execute([':id' => $deleteId]);

    if ($result) {
        echo json_encode([
        'success' => true,
        'message' => "Soft delete toggled successfully."
        ]);
    } else {
    echo json_encode([
        'success' => false, 
        'message' => 'Docente non trovato o soft-delete toggle fallito'
    ]);
    }
} catch (PDOException $e) {
    error_log("Delete docente error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore di sistema']);
}



?>