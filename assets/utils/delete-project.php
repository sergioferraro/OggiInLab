<?php
// delete-project.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/config.php'; // Use absolute path
require_once __DIR__ . '/../../includes/Logger.php'; // Include the Logger class

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

    // Check if there are any non-deleted appointments associated with the project
    $checkSql = "SELECT COUNT(*) AS cnt FROM appuntamento WHERE idCorso = :id AND isDeleted = 0";
    $checkStmt = $dbh->prepare($checkSql);
    $checkStmt->execute([':id' => $projectId]);
    $row = $checkStmt->fetch(PDO::FETCH_ASSOC);
    if ((int)$row['cnt'] > 0) {
        echo json_encode(['success' => false, 'message' => 'Impossibile eliminare: il progetto ha ancora appuntamenti associati.']);
        exit;
    }

    // Prepare and execute delete query
    $sql = "DELETE FROM progetto WHERE idProgetto = :id";
    $stmt = $dbh->prepare($sql);
    $stmt->execute([':id' => $projectId]);

    if ($stmt->rowCount() > 0) {
        // Log the successful deletion
        Logger::success('project_delete', [
            'project_id' => $projectId,
            'project_name' => $_POST['project_name'] ?? null,
            'rows_affected' => $stmt->rowCount()
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Progetto eliminato']);
    } else {
        // Log the failed deletion (not found)
        Logger::warning('project_delete_not_found', [
            'project_id' => $projectId,
            'reason' => 'Project not found in database'
        ]);
        
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Progetto non trovato']);
    }
} catch(PDOException $e) {
    // Log the database error
    Logger::error('project_delete_db_error', [
        'project_id' => $projectId ?? null,
        'error_message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
    
    error_log("Delete error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Errore interno']);
} catch(Exception $e) {
    // Log the validation/other error
    Logger::warning('project_delete_validation_error', [
        'project_id' => $projectId ?? null,
        'error_message' => $e->getMessage()
    ]);
    
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>