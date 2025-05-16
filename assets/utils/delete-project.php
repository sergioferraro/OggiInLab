<?php
// delete-project.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
header('Content-Type: application/json');
include __DIR__ . "/../../includes/config.php"; // Use absolute path

// Check if admin is logged in
if (empty($_SESSION["alogin"])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}
if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 0) {
    echo json_encode(['success' => false, 'message' => 'non si dispone di privilegi sufficienti']);
    exit();
}
// Validate CSRF token
$csrfToken = $_POST['_token'] ?? '';
if (empty($csrfToken) || $csrfToken !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit;
}
unset($_SESSION['csrf_token']); // Invalidate token
try {
    // Sanitize input
    $projectId = (int) filter_input(INPUT_POST, 'delete_id', FILTER_VALIDATE_INT);
    if ($projectId <= 0) { // Handle zero as invalid
        throw new Exception("ID progetto non valido");
    }

    // Prepare and execute delete query
    $sql = "DELETE FROM progetto WHERE idProgetto = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':id' => $projectId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Progetto eliminato']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Progetto non trovato']);
    }
} catch(PDOException $e) {
    error_log("Delete error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore interno']);
} catch(Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>