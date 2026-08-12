<?php
// delete-all-deleted.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
include "../../includes/config.php";
require_once __DIR__ . '/gantt_json_helper.php';
session_start();
header('Content-Type: application/json');

// --- CSRF validation ---
$csrfTokenPost = $_POST['_token'] ?? '';
$csrfTokenSession = $_SESSION['csrf_token'] ?? '';

if ($csrfTokenPost !== $csrfTokenSession) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit;
}

// --- Auth guard ---
if (empty($_SESSION['alogin'])) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}
if (isset($_SESSION['is_super_admin']) && $_SESSION['is_super_admin'] == 0) {
    echo json_encode(['success' => false, 'message' => 'Non si dispone di privilegi sufficienti']);
    exit;
}

try {
    // Permanently delete all soft-deleted appointments (isDeleted = 1)
    $sql = "DELETE FROM appuntamento WHERE isDeleted = 1";
    $stmt = $dbh->prepare($sql);
    $stmt->execute();

    $deletedCount = $stmt->rowCount();

    // Rigenera il JSON pubblico
    regenerateGanttJson();

    echo json_encode([
        'success' => true,
        'message' => "Eliminati definitivamente {$deletedCount} appuntamenti invalidati.",
        'deletedCount' => $deletedCount
    ]);
} catch (PDOException $e) {
    error_log("Errore eliminazione batch: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Errore di database']);
}
?>
