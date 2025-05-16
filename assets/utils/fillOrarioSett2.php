<?php
// fillOrarioSett2.php
// Import appointments from the "orario_settimana" table to the "appuntamento" table
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
header('Content-Type: application/json');
include "../../includes/config.php";
// Retrieve the project "orario"
$stmt = $dbh->prepare("SELECT * FROM progetto WHERE nomeProgetto = :nome");
$stmt->execute(['nome' => 'orario']);
$project = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$project) {
    http_response_code(500);
    echo json_encode(['error' => 'Progetto "orario" non trovato.']);
    exit;
}
$idProgetto = $project['idProgetto'];

$autore = $_SESSION['id'] ?? null;
if (!$autore) {
    http_response_code(401);
    echo json_encode(['error' => 'Utente non autenticato.']);
    exit;
}

// Map days to English for the strtotime function
$daysMap = [
    'Lunedì'    => 'Monday',
    'Martedì'   => 'Tuesday',
    'Mercoledì' => 'Wednesday',
    'Giovedì'   => 'Thursday',
    'Venerdì'   => 'Friday',
    'Sabato'    => 'Saturday'
];

// Select all entries from orario_settimana for this project
$oraStmt = $dbh->prepare(
    "SELECT idAula, giorno, ora_inizio AS inizio, ora_fine AS fine, classe
     FROM orario_settimana
     WHERE idProgetto = :idProj"
);
$oraStmt->execute(['idProj' => $idProgetto]);
$rows = $oraStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo json_encode(['message' => 'Nessuna voce in orario_settimana per il progetto.']);
    exit;
}

$inserted = 0;

// Calculate the date of the current week's Monday
$today = new DateTime();
$monday = clone $today;
$monday->modify('next week monday');

// Iterate from Monday to Sunday of this week
for ($i = 0; $i < 7; $i++) {
    $currentDay = clone $monday;
    $currentDay->modify("+$i days");
    
    // Get the day name in Italian (e.g., 'Lunedì')
    $giornoIt = $currentDay->format('l'); // 'Monday', 'Tuesday'... 

    // Mappa il giorno in inglese per strtotime
    $dayEn = array_search($giornoIt, $daysMap); // cerca la chiave corrispondente

    if ($dayEn) {
        // Search in the "orario settimana" table for this day (use $dayEn)
        $oraStmt = $dbh->prepare(
            "SELECT idAula, ora_inizio AS inizio, ora_fine AS fine, classe
             FROM orario_settimana
             WHERE idProgetto = :idProj AND giorno = :giorno"
        );
        $oraStmt->execute(['idProj' => $idProgetto, 'giorno' => $dayEn]);
        $rowsForDay = $oraStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rowsForDay as $r) {
            $data = $currentDay->format('Y-m-d');
            
            // Check for holidays
            $holStmt = $dbh->prepare("SELECT COUNT(*) FROM calendario WHERE giorno = :data");
            $holStmt->execute(['data' => $data]);
            if ($holStmt->fetchColumn() > 0) {
                continue;
            }

            // Check for overlap
            $ovStmt = $dbh->prepare(
                "SELECT 1 FROM appuntamento
                 WHERE data = :data
                   AND luogo = :luogo
                   AND isDeleted = 0
                   AND (oraInizio < :fine)
                   AND (oraFine > :inizio)
                 LIMIT 1"
            );
            $ovStmt->execute([
                'data'   => $data,
                'luogo'  => $r['idAula'],
                'inizio' => $r['inizio'],
                'fine'   => $r['fine']
            ]);
            if ($ovStmt->fetch()) {
                continue;
            }

            // Insert the appointment
            $ins = $dbh->prepare(
                "INSERT INTO appuntamento
                 (idCorso, data, oraInizio, oraFine, luogo, isDeleted, descrizione, autore)
                 VALUES
                 (:idCorso, :data, :inizio, :fine, :luogo, 0, :descr, :autore)"
            );
            $ins->execute([
                'idCorso' => $idProgetto,
                'data'    => $data,
                'inizio'  => $r['inizio'],
                'fine'    => $r['fine'],
                'luogo'   => $r['idAula'],
                'descr'   => $r['classe'],
                'autore'  => $autore
            ]);
            $inserted++;
        }
    }
}

$response = [
    'inserted' => $inserted,
    'status'   => 'success',
    'message'  => 'Operazione completata con successo!' 
];

echo json_encode($response);
exit;
?>
