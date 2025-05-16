<?php
// add_docente.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
header('Content-Type: application/json');
session_start();
include "../../includes/config.php";

$nome = $_POST['nome'] ?? '';
$cognome = $_POST['cognome'] ?? '';

if (empty($nome) || empty($cognome)) {
    echo json_encode(['success' => false, 'message' => 'Nome e cognome sono obbligatori']);
    exit();
}

try {
    $stmt = $dbh->prepare("INSERT INTO docente (nome, cognome) VALUES (:nome, :cognome)");
    $stmt->execute([
        ':nome' => $nome,
        ':cognome' => $cognome
    ]);

    $newId = $dbh->lastInsertId();

    echo json_encode([
        'success' => true,
        'docente' => [
            'idDocente' => $newId,
            'nome' => $nome,
            'cognome' => $cognome
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => "Errore database: " . $e->getMessage()
    ]);
}
?>
