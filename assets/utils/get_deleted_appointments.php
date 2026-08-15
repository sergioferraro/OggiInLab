<?php
// get_deleted_appointments.php
require_once __DIR__ . '/../../includes/session.php';
header('Content-Type: application/json');
include "../../includes/config.php";

// -------------------------------------------------------------------
// Auth guard: l'endpoint richiede un admin autenticato
// -------------------------------------------------------------------
if (empty($_SESSION['alogin'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit;
}

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
    // Dettaglio solo nei log server, messaggio generico al client
    error_log('get_deleted_appointments DB error: ' . $e->getMessage());
    echo json_encode(['error' => 'Errore durante il recupero dei dati.']);
}
?>