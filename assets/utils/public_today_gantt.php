<?php
/**
 * public_today_gantt.php – OggiInLab Public Gantt Data
 *
 * Legge il file JSON statico generato da gantt_json_helper.php
 * e restituisce solo gli eventi della data odierna.
 * Nessun accesso al DB: il JSON è generato solo quando un admin
 * crea/modifica/elimina un appuntamento.
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);

// Header di sicurezza (endpoint pubblico, nessuna sessione)
require_once __DIR__ . '/../../includes/security_headers.php';

// -------------------------------------------------------------------
// Rate limiting: max 60 richieste / minuto per IP
// -------------------------------------------------------------------
$rateFile = __DIR__ . '/../../uploads/.gantt_rate_' . $_SERVER['REMOTE_ADDR'];
$now = time();

if (file_exists($rateFile)) {
    $data = json_decode(file_get_contents($rateFile), true) ?: [];
    // Rimuovi vecchie entry (> 60 sec)
    $data = array_values(array_filter($data, fn($t) => $now - $t < 60));
    if (count($data) >= 60) {
        header('HTTP/1.1 429 Too Many Requests');
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Troppo richieste. Riprova tra un minuto.']);
        exit;
    }
    $data[] = $now;
} else {
    $data = [$now];
}
file_put_contents($rateFile, json_encode($data), LOCK_EX);

// Cleanup: rimuovi file rate limit vecchi > 120 sec
if ($now - filemtime($rateFile) > 120) {
    @unlink($rateFile);
}

// -------------------------------------------------------------------
// Leggi il file JSON statico
// -------------------------------------------------------------------
header('Content-Type: application/json');

$jsonFile = __DIR__ . '/../../uploads/.gantt_data.json';

if (!file_exists($jsonFile)) {
    echo json_encode([
        'today'  => date('Y-m-d'),
        'events' => [],
        'error'  => 'Dati non ancora disponibili. Il tabellone verrà aggiornato al prossimo aggiornamento degli appuntamenti.',
    ]);
    exit;
}

$allData = json_decode(file_get_contents($jsonFile), true);

if (!$allData || !isset($allData['events'])) {
    echo json_encode([
        'today'  => date('Y-m-d'),
        'events' => [],
        'error'  => 'Dati non validi.',
    ]);
    exit;
}

// -------------------------------------------------------------------
// Filtra solo gli eventi di oggi
// -------------------------------------------------------------------
$today = date('Y-m-d');

$todayEvents = array_values(array_filter($allData['events'], function ($ev) use ($today) {
    return isset($ev['date']) && $ev['date'] === $today;
}));

echo json_encode([
    'today'     => $today,
    'events'    => $todayEvents,
    'generated' => $allData['generated'] ?? 'unknown',
]);
