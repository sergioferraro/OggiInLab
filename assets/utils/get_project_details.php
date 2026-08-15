<?php
// get_project_details.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
require_once __DIR__ . '/../../includes/session.php';
include "../../includes/config.php";

// -------------------------------------------------------------------
// Auth guard: l'endpoint richiede un admin autenticato
// -------------------------------------------------------------------
if (empty($_SESSION['alogin'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => 'Non autenticato']);
    exit;
}

header('Content-Type: application/json');

$id_progetto = isset($_GET['id']) ? intval($_GET['id']) : null;

if ($id_progetto) {
    try {
        $sql = "SELECT progetto.*, docente.cognome AS Tutor_Cognome, 
                         COALESCE(docente2.cognome, docente.cognome) AS Esperto_Cognome
                FROM progetto
                JOIN docente ON progetto.idTutor = docente.idDocente
                LEFT JOIN docente AS docente2 ON progetto.idEsperto = docente2.idDocente
                WHERE progetto.idProgetto = :id";

        $query = $dbh->prepare($sql);
        $query->bindParam(':id', $id_progetto, PDO::PARAM_INT);
        $query->execute();
        
        if ($result = $query->fetch(PDO::FETCH_ASSOC)) {
            // Convert dates to desired format
            $start_date_display = !empty($result['startDate']) ? date('d-m-Y', strtotime($result['startDate'])) : 'N/D';
            $end_date_display = !empty($result['endDate']) ? date('d-m-Y', strtotime($result['endDate'])) : 'N/D';

            echo json_encode([
                'success' => true,
                'progetto' => [
                    'nome_progetto' => htmlspecialchars($result['nomeProgetto']),
                    'Desc_Progetto' => htmlspecialchars($result['descProgetto'])
                ],
                'Tutor_Cognome' => htmlspecialchars($result['Tutor_Cognome']),
                'Esperto_Cognome' => htmlspecialchars($result['Esperto_Cognome']),
                'start_date' => $start_date_display,
                'end_date' => $end_date_display
            ]);
        } else {
            echo json_encode(['success' => false]);
        }

    } catch (PDOException $e) {
        // Dettaglio solo nei log server, messaggio generico al client
        error_log('get_project_details DB error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Errore durante il recupero dei dati.']);
    }
} else {
    echo json_encode(['error' => 'Invalid project ID']);
}
?>
