<?php
// get_deleted_appointments.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
header('Content-Type: application/json');
include "../../includes/config.php";

try {
    $sql = "SELECT DISTINCT
                progetto.nomeProgetto AS corso,
                data,
                oraInizio,
                oraFine,
                aula.nAula AS aula,
                descrizione
            FROM appuntamento
            LEFT JOIN progetto ON idCorso = idProgetto
            LEFT JOIN aula ON appuntamento.luogo = aula.idAula
            WHERE isDeleted=1 AND data >= CURRENT_DATE
            ORDER BY data;";

    $query = $dbh->prepare($sql);
    $query->execute();

    $results = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($results)) {
        // Sanitize data and structure as array of appointments
        $appointments = array_map(function($row) {
            return [
                'corso' => htmlspecialchars($row['corso']),
                'data' => htmlspecialchars($row['data']),
                'oraInizio' => htmlspecialchars($row['oraInizio']),
                'oraFine' => htmlspecialchars($row['oraFine']),
                'aula' => htmlspecialchars($row['aula']),
                'descrizione' => htmlspecialchars($row['descrizione'])
            ];
        }, $results);

        echo json_encode([
            'success' => true,
            'appointments' => $appointments
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => ' ']);
    }

} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
