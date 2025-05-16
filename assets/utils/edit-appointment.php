<?php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

include "../../includes/config.php";

// Verify auth
if (empty($_SESSION['alogin'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit();
}

// Verify CSRF token
$csrfTokenSession = $_SESSION['csrf_token'] ?? '';
$csrfTokenPost = $_POST['_token'] ?? '';
if (!$csrfTokenSession || !$csrfTokenPost || !hash_equals($csrfTokenSession, $csrfTokenPost)) {
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
            echo json_encode(['success' => true, 'message' => 'Appuntamento aggiornato']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Nessuna modifica rilevata o appuntamento non trovato']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore durante l\'aggiornamento']);
    }
} catch (PDOException $e) {
    error_log('Database Error (edit-appointment): ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore di database']);
}
