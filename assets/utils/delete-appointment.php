<?php
// delete-appointment.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
require_once __DIR__ . '/../../includes/session.php';
include "../../includes/config.php";
require_once __DIR__ . '/../../includes/Logger.php';
require_once __DIR__ . '/gantt_json_helper.php'; // Ensure correct path to config
header('Content-Type: application/json');

// Validate CSRF token
$csrfTokenSession = $_SESSION['csrf_token'] ?? '';
$csrfTokenPost = $_POST['_token'] ?? '';

$courseId = $_POST['idCorso'] ?? '';
$appointmentId = $_POST['idAppuntamento'] ?? '';

// Validate required POST parameters
if (!isset($_POST['idCorso']) || !isset($_POST['idAppuntamento']) || !isset($_POST['_token'])) {
    Logger::warning('appointment_hard_delete_missing_params', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    echo json_encode(['success' => false, 'message' => 'Parametri obbligatori mancanti']);
    exit();
}

// Validate numeric IDs
if (!is_numeric($courseId) || !is_numeric($appointmentId)) {
    Logger::warning('appointment_hard_delete_validation_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'id_corso' => $courseId,
        'id_appuntamento' => $appointmentId
    ]);
    echo json_encode(['success' => false, 'message' => 'ID non validi']);
    exit();
}

// Validate CSRF token match
if (!is_string($csrfTokenPost) || $csrfTokenPost === '' || !hash_equals($csrfTokenSession, $csrfTokenPost)) {
    Logger::warning('appointment_hard_delete_csrf_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit();
}
// --- Auth guard ---
if (empty($_SESSION['alogin'])) {
    Logger::warning('appointment_hard_delete_unauthorized', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit();
}
if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 0) {
    Logger::warning('appointment_hard_delete_no_privileges', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    echo json_encode(['success' => false, 'message' => 'non si dispone di privilegi sufficienti']);
    exit();
}
try {
    // Perform hard delete ONLY if already soft-deleted (isDeleted=1)
    $sql = "DELETE FROM appuntamento 
            WHERE idAppuntamento = :idAppuntamento 
            AND idCorso = :idCorso 
            AND isDeleted = 1";
    
    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        'idAppuntamento' => $appointmentId,
        'idCorso' => $courseId
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Rigenera il JSON pubblico
        regenerateGanttJson();
        Logger::success('appointment_hard_delete', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'id_corso' => $courseId,
            'id_appuntamento' => $appointmentId
        ]);
        echo json_encode(['success' => true, 'message' => 'Appuntamento eliminato definitivamente']);
    } else {
        Logger::info('appointment_hard_delete_not_found', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'id_corso' => $courseId,
            'id_appuntamento' => $appointmentId
        ]);
        echo json_encode([
            'success' => false, 
            'message' => 'Appuntamento non trovato o non invalidato precedentemente'
        ]);
    }
} catch (PDOException $e) {
    Logger::error('appointment_hard_delete_db_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'id_corso' => $courseId,
        'id_appuntamento' => $appointmentId,
        'error_message' => $e->getMessage()
    ]);
    error_log("Errore eliminazione definitiva: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore di database']);
}