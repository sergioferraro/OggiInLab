<?php
// elimina_calendario.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
header('Content-Type: application/json');
include "../../includes/config.php"; // Assuming this file starts session and connects to DB


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token CSRF non valido");
    }

    // Get the event ID to delete
    $id = $_POST['idCalendario'];

    try {
        // Prepare and execute the DELETE query with school year check
        $stmt = $dbh->prepare("DELETE FROM calendario WHERE idCalendario = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Redirect back to the admin panel
        header("Location: ../../calend_ann.php"); // Adjust the filename as needed
        exit;
    } catch (PDOException $e) {
        die("Errore durante l'eliminazione dell'evento: " . $e->getMessage());
    }
} else {
    die("Metodo non consentito");
}
?>