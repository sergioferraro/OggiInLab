<?php
// delete-appointment.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
include "../../includes/config.php"; // Ensure correct path to config
session_start();
header('Content-Type: application/json');

// Validate CSRF token
$csrfTokenSession = $_SESSION['csrf_token'] ?? '';
$csrfTokenPost = $_POST['_token'] ?? '';

$courseId = $_POST['idCorso'] ?? '';
$appointmentId = $_POST['idAppuntamento'] ?? '';

// Validate required POST parameters
if (!isset($_POST['idCorso']) || !isset($_POST['idAppuntamento']) || !isset($_POST['_token'])) {
    echo json_encode(['success' => false, 'message' => 'Parametri obbligatori mancanti']);
    exit();
}

// Validate numeric IDs
if (!is_numeric($courseId) || !is_numeric($appointmentId)) {
    echo json_encode(['success' => false, 'message' => 'ID non validi']);
    exit();
}

// Validate CSRF token match
if ($csrfTokenPost !== $csrfTokenSession) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit();
}
if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 0) {
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
        echo json_encode(['success' => true, 'message' => 'Appuntamento eliminato definitivamente']);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Appuntamento non trovato o non invalidato precedentemente'
        ]);
    }
} catch (PDOException $e) {
    error_log("Errore eliminazione definitiva: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore di database']);
}