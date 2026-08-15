<?php
// add_docente.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/session.php';
include "../../includes/config.php";
require_once __DIR__ . '/../../includes/Logger.php';

// -------------------------------------------------------------------
// Auth guard: solo admin autenticati possono aggiungere docenti
// -------------------------------------------------------------------
if (empty($_SESSION['alogin'])) {
    Logger::warning('docente_add_unauthorized', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

// -------------------------------------------------------------------
// CSRF check
// -------------------------------------------------------------------
if (empty($_POST['_token']) || !is_string($_POST['_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['_token'])) {
    Logger::warning('docente_add_csrf_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit;
}

$nome = trim((string)($_POST['nome'] ?? ''));
$cognome = trim((string)($_POST['cognome'] ?? ''));

// Validazione: solo lettere, spazi, apostrofi e punteggiatura comune; max 30 caratteri (colonna varchar(32))
$nomeValido = preg_match("/^[\p{L} '\-\.]+$/u", $nome) && mb_strlen($nome) <= 30;
$cognomeValido = preg_match("/^[\p{L} '\-\.]+$/u", $cognome) && mb_strlen($cognome) <= 30;

if (!$nomeValido || !$cognomeValido) {
    Logger::warning('docente_add_validation_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'nome' => $nome,
        'cognome' => $cognome
    ]);
    echo json_encode(['success' => false, 'message' => 'Nome e cognome non validi (solo lettere, max 30 caratteri)']);
    exit();
}

try {
    // Controlla se la coppia nome+cognome esiste già
    $checkStmt = $dbh->prepare("SELECT idDocente, nome, cognome FROM docente WHERE nome = :nome AND cognome = :cognome");
    $checkStmt->execute([':nome' => $nome, ':cognome' => $cognome]);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        Logger::warning('docente_add_duplicate', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'nome' => $nome,
            'cognome' => $cognome,
            'existing_id' => $existing['idDocente']
        ]);
        echo json_encode([
            'success' => true,
            'duplicate' => true,
            'message' => 'Docente già presente in elenco',
            'docente' => [
                'idDocente' => $existing['idDocente'],
                'nome' => $existing['nome'],
                'cognome' => $existing['cognome']
            ]
        ]);
        exit();
    }

    $stmt = $dbh->prepare("INSERT INTO docente (nome, cognome) VALUES (:nome, :cognome)");
    $stmt->execute([
        ':nome' => $nome,
        ':cognome' => $cognome
    ]);

    $newId = $dbh->lastInsertId();

    Logger::success('docente_add', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'id_docente' => $newId,
        'nome' => $nome,
        'cognome' => $cognome
    ]);

    echo json_encode([
        'success' => true,
        'duplicate' => false,
        'docente' => [
            'idDocente' => $newId,
            'nome' => $nome,
            'cognome' => $cognome
        ]
    ]);
} catch (PDOException $e) {
    Logger::error('docente_add_db_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'nome' => $nome,
        'cognome' => $cognome,
        'error_message' => $e->getMessage()
    ]);
    echo json_encode([
        'success' => false,
        'message' => "Errore durante l'operazione. Riprova."
    ]);
}
?>
