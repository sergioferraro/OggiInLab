<?php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
require_once __DIR__ . '/../../includes/session.php';
error_reporting(E_ALL);
ini_set('display_errors', defined('APP_DEBUG') && APP_DEBUG ? '1' : '0');

header('Content-Type: application/json');

include "../../includes/config.php";
require_once __DIR__ . '/../../includes/Logger.php';
require_once __DIR__ . '/gantt_json_helper.php';

// Verify auth
if (empty($_SESSION['alogin'])) {
    Logger::warning('appointment_edit_unauthorized', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit();
}

// Verify CSRF token
$csrfTokenSession = $_SESSION['csrf_token'] ?? '';
$csrfTokenPost = $_POST['_token'] ?? '';
if (!$csrfTokenSession || !$csrfTokenPost || !hash_equals($csrfTokenSession, $csrfTokenPost)) {
    Logger::warning('appointment_edit_csrf_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit();
}

// Collect and validate inputs
$idCorso       = $_POST['idCorso'] ?? '';
$idAppuntamento = $_POST['idAppuntamento'] ?? '';
$data            = $_POST['data'] ?? '';
$oraInizio      = $_POST['oraInizio'] ?? '';
$oraFine        = $_POST['oraFine'] ?? '';
$luogo          = $_POST['luogo'] ?? '';
$descrizione    = $_POST['descrizione'] ?? '';
$autore = $_SESSION['id'] ?? null;

if (!is_numeric($idCorso) || !is_numeric($idAppuntamento)) {
    Logger::warning('appointment_edit_validation_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'id_corso' => $idCorso,
        'id_appuntamento' => $idAppuntamento
    ]);
    echo json_encode(['success' => false, 'message' => 'Identificatori non validi']);
    exit();
}

try {
    $sql = "UPDATE appuntamento
            SET data = :data,
                oraInizio = :oraInizio,
                oraFine = :oraFine,
                Luogo = :luogo,
                descrizione = :descrizione,
                autore = :autore

            WHERE idCorso = :idCorso
              AND idAppuntamento = :idAppuntamento
              AND isDeleted = 0";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':data', $data);
    $stmt->bindParam(':oraInizio', $oraInizio);
    $stmt->bindParam(':oraFine', $oraFine);
    $stmt->bindParam(':luogo', $luogo);
    $stmt->bindParam(':descrizione', $descrizione);
    $stmt->bindParam(':idCorso', $idCorso, PDO::PARAM_INT);
    $stmt->bindParam(':idAppuntamento', $idAppuntamento, PDO::PARAM_INT);
    $stmt->bindParam(':autore', $autore, PDO::PARAM_INT);

    if ($stmt->execute()) {
        if ($stmt->rowCount() > 0) {
            // Rigenera il JSON pubblico
            regenerateGanttJson();
            Logger::success('appointment_edit', [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'id_corso' => $idCorso,
                'id_appuntamento' => $idAppuntamento,
                'changes' => compact('data', 'oraInizio', 'oraFine', 'luogo', 'descrizione')
            ]);
            echo json_encode(['success' => true, 'message' => 'Appuntamento aggiornato']);
        } else {
            Logger::info('appointment_edit_no_changes', [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'id_corso' => $idCorso,
                'id_appuntamento' => $idAppuntamento
            ]);
            echo json_encode(['success' => false, 'message' => 'Nessuna modifica rilevata o appuntamento non trovato']);
        }
    } else {
        Logger::error('appointment_edit_db_error', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'id_corso' => $idCorso,
            'id_appuntamento' => $idAppuntamento
        ]);
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento']);
    }
} catch (PDOException $e) {
    Logger::error('appointment_edit_db_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'id_corso' => $idCorso,
        'id_appuntamento' => $idAppuntamento,
        'error_message' => $e->getMessage()
    ]);
    error_log('Database Error (edit-appointment): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore di database']);
}
