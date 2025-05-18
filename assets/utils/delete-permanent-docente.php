<?php
// delete-permanent-docente.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../includes/config.php";

if (empty($_SESSION["alogin"])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

$deleteId = $_POST['delete_id'] ?? null;
$csrfToken = $_POST['_token'] ?? '';

// Verifica CSRF
if ($csrfToken !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token non valido']);
    exit;
}

try {
    // Verifica se il docente esiste
    $sql = "SELECT idDocente FROM docente WHERE idDocente = :id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $deleteId, PDO::PARAM_INT);
    $query->execute();
    if (!$query->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Docente non trovato']);
        exit;
    }

    // Verifica se il docente è riferito in progetti
    $sqlCheck = "SELECT COUNT(*) FROM progetto WHERE idTutor = :id OR idEsperto = :id";
    $queryCheck = $dbh->prepare($sqlCheck);
    $queryCheck->bindParam(':id', $deleteId, PDO::PARAM_INT);
    $queryCheck->execute();
    $countProjects = $queryCheck->fetchColumn();

    if ($countProjects > 0) {
        echo json_encode(['success' => false, 'message' => 'Il docente è riferito in alcuni progetti. Non può essere cancellato']);
        exit;
    }

    // Elimina il docente
    $sqlDelete = "DELETE FROM docente WHERE idDocente = :id";
    $queryDelete = $dbh->prepare($sqlDelete);
    $queryDelete->bindParam(':id', $deleteId, PDO::PARAM_INT);

    if ($queryDelete->execute()) {
        echo json_encode(['success' => true, 'message' => 'Docente cancellato con successo']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore durante la cancellazione']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore del database']);
}
exit;