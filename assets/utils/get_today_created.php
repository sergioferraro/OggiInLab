<?php
// get_today_created.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
header('Content-Type: application/json');
include "../../includes/config.php";

try {
    // Query to select the author of the modified appointments
    // The conditions are:
    // 1. The last modification occurred after the current date (`appuntamento.lastModified > CURRENT_DATE`)
    // 2. The last modification date is different from the creation date (`appuntamento.lastModified <> appuntamento.creationDate`)
    $sql = "SELECT
                admin.nomeCompleto AS autore,
                progetto.nomeProgetto AS titolo,
                appuntamento.data AS appData,
                appuntamento.oraInizio AS oraInizio,
                appuntamento.oraFine AS oraFine,
                appuntamento.descrizione,
                appuntamento.isDeleted
            FROM appuntamento
            JOIN progetto ON progetto.idProgetto = appuntamento.idCorso
            JOIN admin ON admin.id = appuntamento.autore -- Assumendo che la tabella admin esista e abbia una colonna 'id'
            WHERE
                DATE(appuntamento.creationDate) = CURRENT_DATE
                AND appuntamento.isDeleted=0";

    $query = $dbh->prepare($sql);
    $query->execute();

    // Recupera tutti i risultati come array associativo
    $results = $query->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($results)) {
        // Fetches all results as an associative array
        $authors = array_map(function($row) {
            return [
                'autore' => htmlspecialchars($row['autore']),
                'titolo' => htmlspecialchars($row['titolo']),
                'appData' => htmlspecialchars($row['appData']),
                'oraInizio' => htmlspecialchars($row['oraInizio']),
                'oraFine' => htmlspecialchars($row['oraFine']),
                'descrizione' => htmlspecialchars($row['descrizione'])
            ];
        }, $results);

        echo json_encode([
            'success' => true,
            'authors' => $authors
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nessun evento caricato oggi ']);
    }

} catch (PDOException $e) {
    // Database error handling
    echo json_encode(['error' => $e->getMessage()]);
}
?>
