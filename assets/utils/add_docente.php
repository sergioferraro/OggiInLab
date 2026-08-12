<?php
// add_docente.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
header('Content-Type: application/json');
session_start();
include "../../includes/config.php";
require_once __DIR__ . '/../../includes/Logger.php';

$nome = $_POST['nome'] ?? '';
$cognome = $_POST['cognome'] ?? '';

if (empty($nome) || empty($cognome)) {
    Logger::warning('docente_add_validation_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'nome' => $nome,
        'cognome' => $cognome
    ]);
    echo json_encode(['success' => false, 'message' => 'Nome e cognome sono obbligatori']);
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
        'message' => "Errore database: " . $e->getMessage()
    ]);
}
?>
