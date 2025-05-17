<?php
// invalida-appointment.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
include "../../includes/config.php";
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

// Validate CSRF token
if ($csrfToken !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit();
}

// Validate numeric IDs
if (!is_numeric($courseId) || !is_numeric($appointmentId)) {
    echo json_encode(['success' => false, 'message' => 'ID non validi']);
    exit();
}

// Validate required POST parameters
if (!isset($_POST['idCorso']) || !isset($_POST['idAppuntamento']) || !isset($_POST['_token'])) {
    echo json_encode(['success' => false, 'message' => 'Parametri obbligatori mancanti']);
    exit();
}

try {
    // Retrieve the details of the current appointment
    $sqlCheck = "SELECT data, luogo, oraInizio, oraFine FROM appuntamento 
                 WHERE idAppuntamento = :idAppuntamento AND idCorso = :idCorso";
    $stmtCheck = $dbh->prepare($sqlCheck);
    $stmtCheck->execute(['idAppuntamento' => $appointmentId, 'idCorso' => $courseId]);
    $appointmentDetails = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($appointmentDetails) {
        // Check if a previously deleted appointment exists with the same parameters
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
            // Permanently delete the existing appointment
            $sqlDelete = "DELETE FROM appuntamento WHERE idAppuntamento = :idAppuntamento";
            $stmtDelete = $dbh->prepare($sqlDelete);
            $stmtDelete->execute(['idAppuntamento' => $existing['idAppuntamento']]);
        }
    }

    // Perform a soft delete of the appointment
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
        echo json_encode(['success' => true, 'message' => 'Appuntamento invalidato con successo']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Appuntamento non trovato']);
    }
} catch (PDOException $e) {
    error_log("Errore eliminazione appuntamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore di database']);
}
