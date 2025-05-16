<?php
// get_project_appointments.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
include "../../includes/config.php";
header('Content-Type: application/json');

// Retrieve and validate the project ID parameter
$projectId = $_GET['id'] ?? '';
error_log("ID progetto ricevuto: " . $projectId);
if (!is_numeric($projectId)) {
    echo json_encode(['success' => false, 'message' => 'ID progetto non valido']);
    exit();
}

try {
    $sql = "SELECT DISTINCT
                idCorso,
					data, 
					oraInizio, 
					oraFine,
                    appuntamento.luogo AS aulaId,
					aula.nAula AS luogo,
                    idAppuntamento,
                    descrizione
					FROM appuntamento
					JOIN aula ON appuntamento.luogo = aula.idAula
                    WHERE 
                appuntamento.idCorso = ?  -- Fixed case sensitivity here
                AND appuntamento.isDeleted = 0
                AND Data >= CURRENT_DATE;"; // Added space for clarity

    $stmt = $dbh->prepare($sql);
    $stmt->execute([$projectId]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($appointments)) {
        echo json_encode(['success' => false, 'message' => 'Nessun appuntamento trovato']);
    } else {
        echo json_encode(['success' => true, 'appointments' => $appointments]);
    }
} catch (PDOException $e) {
    error_log("Errore nel recupero degli appuntamenti: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore di database']);
}
?>
