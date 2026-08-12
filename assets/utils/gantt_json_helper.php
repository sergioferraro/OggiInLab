<?php
/**
 * gantt_json_helper.php – Genera il file JSON statico per il tabellone pubblico
 *
 * Usa la connessione DB admin (config.php). Non richiede utente pubblico dedicato.
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */

/**
 * Rigenera il file JSON con tutti gli appuntamenti attivi.
 * Da chiamare dopo ogni CRUD sugli appuntamenti.
 *
 * Richiede che $dbh sia già disponibile (da config.php).
 */
function regenerateGanttJson(): void
{
    global $dbh;

    if (!$dbh) {
        error_log('Gantt JSON: connessione DB non disponibile');
        return;
    }

    $jsonFile = __DIR__ . '/../../uploads/.gantt_data.json';

    try {
        $stmt = $dbh->prepare(
            'SELECT DISTINCT
                    a.idAppuntamento,
                    a.data,
                    a.oraInizio,
                    a.oraFine,
                    a.descrizione,
                    au.nAula       AS luogo,
                    p.nomeProgetto AS nomeproj
             FROM appuntamento a
             LEFT JOIN aula      au ON a.luogo = au.idAula
             LEFT JOIN progetto  p  ON a.idCorso = p.idProgetto
             WHERE a.isDeleted = 0
             ORDER BY a.data, a.oraInizio'
        );
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Gantt JSON DB error: ' . $e->getMessage());
        return;
    }

    $events = array_map(static function (array $r): array {
        return [
            'id'          => $r['idAppuntamento'],
            'title'       => $r['nomeproj'] ?? 'Appuntamento',
            'description' => trim($r['descrizione'] ?? ''),
            'date'        => $r['data'],
            'startTime'   => substr($r['oraInizio'], 0, 5),
            'endTime'     => substr($r['oraFine'],   0, 5),
            'place'       => $r['luogo'] ?? 'N/D',
        ];
    }, $rows);

    $payload = json_encode([
        'generated' => date('c'),
        'events'    => $events,
    ], JSON_UNESCAPED_UNICODE);

    $written = file_put_contents($jsonFile, $payload, LOCK_EX);
    if ($written === false) {
        error_log('Gantt JSON: impossibile scrivere il file ' . $jsonFile);
    }
}
