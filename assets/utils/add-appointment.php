<?php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
header('Content-Type: application/json');

include "../../includes/config.php";

// Ensure the request method is POST and required parameters exist
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}


// Retrieve and sanitize input values
$idCorso   = isset($_POST['idCorso']) ? intval($_POST['idCorso']) : 0;
$data       = isset($_POST['data']) ? $_POST['data'] : '';
$oraInizio = isset($_POST['oraInizio']) ? $_POST['oraInizio'] : '';
$oraFine   = isset($_POST['oraFine']) ? $_POST['oraFine'] : '';
$luogo      = isset($_POST['luogo']) ? trim($_POST['luogo']) : null;
$descrizione = isset($_POST['descrizione']) ? trim($_POST['descrizione']) : null; // Added description field
$autore = $_SESSION['id'] ?? null;

if (!$idCorso || empty($data) || empty($oraInizio) || empty($oraFine)) {
    echo json_encode(['success' => false, 'message' => 'Parametri mancanti o non validi.']);
    exit;
}

{{ 
    // Check if the date is a holiday
    try {
        $holidayCheckSql = "SELECT COUNT(*) FROM calendario WHERE giorno = :data";
        $holidayStmt = $dbh->prepare($holidayCheckSql);
        $holidayStmt->bindParam(':data', $data);
        $holidayStmt->execute();
        $holidayCount = $holidayStmt->fetchColumn();
    
        if ($holidayCount > 0) {
            echo json_encode(['success' => false, 'message' => 'Appuntamento non registrato, giorno festivo']);
            exit;
        }
    } catch (PDOException $e) {
        error_log("Errore verifica festivo: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore durante la verifica del giorno festivo.']);
        exit;
    }
    }}
    //check if the date is sunday
    try {
        $date = new DateTimeImmutable($data);
        if ($date->format('N') == 7) {
            echo json_encode(['success' => false, 'message' => 'Appuntamento non registrato, è domenica']);
            exit;
        }
    } catch (Exception $e) {
        error_log("Errore verifica domenica: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Errore durante la verifica del giorno.']);
        exit;
    }
    // Check for overlapping appointments
try {
    $overlapSql = "SELECT COUNT(*) FROM appuntamento 
                   WHERE data = :data 
                     AND luogo = :luogo 
                     AND oraInizio < :oraFine 
                     AND oraFine > :oraInizio
                     AND isDeleted=0";
    $overlapStmt = $dbh->prepare($overlapSql);
    $overlapStmt->bindParam(':data', $data);
    $overlapStmt->bindParam(':luogo', $luogo);
    $overlapStmt->bindParam(':oraInizio', $oraInizio);
    $overlapStmt->bindParam(':oraFine', $oraFine);
    $overlapStmt->execute();
    $overlapCount = $overlapStmt->fetchColumn();

    if ($overlapCount > 0) {
        echo json_encode(['success' => false, 'message' => 'Appuntamento non registrato: sovrapposizione con un appuntamento esistente nel medesimo luogo e orario.']);
        exit;
    }
} catch (PDOException $e) {
    error_log("Errore verifica sovrapposizione: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore durante la verifica della sovrapposizione.']);
    exit;
}
try {
    // Create a prepared statement including the new 'Descrizione' column
    $sql = "INSERT INTO appuntamento (idCorso, data, oraInizio, oraFine, luogo, descrizione, autore) 
            VALUES (:idCorso, :data, :oraInizio, :oraFine, :luogo, :descrizione, :autore)";
    $stmt = $dbh->prepare($sql);
    $stmt->bindParam(':idCorso', $idCorso, PDO::PARAM_INT);
    $stmt->bindParam(':data', $data);
    $stmt->bindParam(':oraInizio', $oraInizio);
    $stmt->bindParam(':oraFine', $oraFine);
    $stmt->bindParam(':luogo', $luogo);
    $stmt->bindParam(':descrizione', $descrizione);
    $stmt->bindParam(':autore', $autore, PDO::PARAM_INT);

    $stmt->execute();

    echo json_encode(['success' => true, 'message' => 'Appuntamento aggiunto con successo.']);
} catch (PDOException $e) {
    error_log("Errore inserimento appuntamento: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore durante l\'inserimento dell\'appuntamento.']);
}
    


