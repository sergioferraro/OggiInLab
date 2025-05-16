<?php
// get_project_details.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
include "../../includes/config.php";

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
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid project ID']);
}
?>
