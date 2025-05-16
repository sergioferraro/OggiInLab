<?php
// get_modified_appointments_authors.php
session_start();
header('Content-Type: application/json');
// Assicurati che il percorso al tuo file di configurazione sia corretto
include "../../includes/config.php";

try {
    // Query per selezionare l'autore degli appuntamenti modificati
    // Le condizioni sono:
    // 1. L'ultima modifica è avvenuta dopo la data corrente (`appuntamento.lastModified > CURRENT_DATE`)
    // 2. La data di ultima modifica è diversa dalla data di creazione (`appuntamento.lastModified <> appuntamento.creationDate`)
    // Viene inclusa la JOIN con la tabella 'admin' come richiesto dall'utente,
    // anche se per selezionare solo l'autore non sarebbe strettamente necessaria se l'autore esiste sempre in 'appuntamento'.
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

    // Verifica se sono stati trovati risultati
    if (!empty($results)) {
        // Struttura i risultati in un array di autori
        // htmlspecialchars non è strettamente necessario per un ID numerico,
        // ma lo manteniamo per coerenza e sicurezza generale.
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

        // Restituisce i risultati in formato JSON con successo = true
        echo json_encode([
            'success' => true,
            'authors' => $authors // Cambiato 'appointments' in 'authors' per riflettere il contenuto
        ]);
    } else {
        // Nessun risultato trovato, restituisce successo = false e un messaggio vuoto
        echo json_encode(['success' => false, 'message' => 'Nessun evento caricato oggi ']);
    }

} catch (PDOException $e) {
    // Gestione degli errori di database
    // Restituisce un messaggio di errore in formato JSON
    echo json_encode(['error' => $e->getMessage()]);
}
?>
