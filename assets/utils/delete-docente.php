<?php
/**
 * delete-docente.php – OggiInLab: Toggle stato docente (soft delete)
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Logger.php';

// -------------------------------------------------------------------
// Method check
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

// -------------------------------------------------------------------
// CSRF check
// -------------------------------------------------------------------
$postedToken = $_POST['_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
    Logger::warning('docente_status_csrf_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit;
}

// -------------------------------------------------------------------
// Validate ID
// -------------------------------------------------------------------
$deleteId = $_POST['delete_id'] ?? null;
if ($deleteId === null || !is_numeric($deleteId)) {
    Logger::warning('docente_status_validation_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'id' => $deleteId
    ]);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID non valido']);
    exit;
}

// -------------------------------------------------------------------
// Toggle isDeleted
// -------------------------------------------------------------------
try {
    $stmt = $dbh->prepare(
        'UPDATE docente SET isDeleted = NOT isDeleted WHERE idDocente = :id'
    );
    $stmt->execute([':id' => (int) $deleteId]);

    if ($stmt->rowCount() > 0) {
        Logger::success('docente_status_toggle', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'id_docente' => (int) $deleteId,
            'new_status' => $stmt->fetchColumn() // will get the isDeleted value after toggle
        ]);
        echo json_encode([
            'success' => true,
            'message' => 'Stato docente aggiornato con successo.',
        ]);
    } else {
        Logger::info('docente_status_not_found', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'id_docente' => (int) $deleteId
        ]);
        echo json_encode([
            'success' => false,
            'message' => 'Docente non trovato.',
        ]);
    }
} catch (PDOException $e) {
    Logger::error('docente_status_db_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'id_docente' => (int) $deleteId,
        'error_message' => $e->getMessage()
    ]);
    error_log('Toggle docente: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Errore di sistema.',
    ]);
}
