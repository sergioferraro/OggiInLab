<?php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
include "../../includes/config.php";
require_once __DIR__ . '/../../includes/Logger.php';
require_once __DIR__ . '/gantt_json_helper.php';
session_start(); // Ensure session is started to validate CSRF token
header('Content-Type: application/json');
// Verify CSRF token
$csrfTokenSession = $_SESSION['csrf_token'] ?? '';
$csrfTokenPost = $_POST['_token'] ?? '';

$courseId = $_POST['idCorso'];
$appointmentId = $_POST['idAppuntamento'];
$csrfToken = $_POST['_token'];

error_log("ID corso: " . $courseId);
error_log("ID appuntamento: " . $appointmentId);

if ($csrfToken !== $_SESSION['csrf_token']) {
    Logger::warning('appointment_invalidate_csrf_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit();
}

// Validate numeric IDs
if (!is_numeric($courseId) || !is_numeric($appointmentId)) {
    Logger::warning('appointment_invalidate_validation_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'id_corso' => $courseId,
        'id_appuntamento' => $appointmentId
    ]);
    echo json_encode(['success' => false, 'message' => 'ID non validi']);
    exit();
}

// Validate required POST parameters
if (!isset($_POST['idCorso']) || !isset($_POST['idAppuntamento']) || !isset($_POST['_token'])) {
    Logger::warning('appointment_invalidate_missing_params', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    echo json_encode(['success' => false, 'message' => 'Parametri obbligatori mancanti']);
    exit();
}

try {
    // Recupera i dettagli dell'appuntamento corrente
    $sqlCheck = "SELECT data, luogo, oraInizio, oraFine FROM appuntamento 
                 WHERE idAppuntamento = :idAppuntamento AND idCorso = :idCorso";
    $stmtCheck = $dbh->prepare($sqlCheck);
    $stmtCheck->execute(['idAppuntamento' => $appointmentId, 'idCorso' => $courseId]);
    $appointmentDetails = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    // --- Auth guard: non-super-amministratori non possono cancellare appuntamenti passati ---
    $isSuperAdmin = isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 1;
    if (!$isSuperAdmin && $appointmentDetails && strtotime($appointmentDetails['data']) < strtotime('today')) {
        Logger::warning('appointment_invalidate_no_privileges_past', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'id_corso' => $courseId,
            'id_appuntamento' => $appointmentId,
            'appointment_date' => $appointmentDetails['data']
        ]);
        echo json_encode(['success' => false, 'message' => 'Non puoi cancellare appuntamenti passati']);
        exit();
    }

    if ($appointmentDetails) {
        // Verifica se esiste un appuntamento già cancellato con gli stessi parametri
        $sqlExisting = "SELECT idAppuntamento FROM appuntamento 
                        WHERE data = :data 
                          AND luogo = :luogo 
                          AND oraInizio = :oraInizio 
                          AND oraFine = :oraFine 
                          AND isDeleted = 1 
                          AND idAppuntamento != :appointmentId";
        $stmtExisting = $dbh->prepare($sqlExisting);
        $stmtExisting->execute([
            'data' => $appointmentDetails['data'],
            'luogo' => $appointmentDetails['luogo'],
            'oraInizio' => $appointmentDetails['oraInizio'],
            'oraFine' => $appointmentDetails['oraFine'],
            'appointmentId' => $appointmentId
        ]);
        $existing = $stmtExisting->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Elimina definitivamente l'appuntamento esistente
            $sqlDelete = "DELETE FROM appuntamento WHERE idAppuntamento = :idAppuntamento";
            $stmtDelete = $dbh->prepare($sqlDelete);
            $stmtDelete->execute(['idAppuntamento' => $existing['idAppuntamento']]);
        }
    }

    // Esegui la soft delete dell'appuntamento
    $sql = "UPDATE appuntamento 
            SET isDeleted = 1 
            WHERE idAppuntamento = :idAppuntamento 
              AND idCorso = :idCorso";
    
    $stmt = $dbh->prepare($sql);
    $stmt->execute([
        'idAppuntamento' => $appointmentId,
        'idCorso' => $courseId
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Rigenera il JSON pubblico
        regenerateGanttJson();
        Logger::success('appointment_invalidate', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'id_corso' => $courseId,
            'id_appuntamento' => $appointmentId
        ]);
        echo json_encode(['success' => true, 'message' => 'Appuntamento invalidato con successo']);
    } else {
        Logger::info('appointment_invalidate_not_found', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'id_corso' => $courseId,
            'id_appuntamento' => $appointmentId
        ]);
        echo json_encode(['success' => false, 'message' => 'Appuntamento non trovato']);
    }
} catch (PDOException $e) {
    Logger::error('appointment_invalidate_db_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'id_corso' => $courseId,
        'id_appuntamento' => $appointmentId,
        'error_message' => $e->getMessage()
    ]);
    error_log("Errore eliminazione appuntamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore di database']);
}
