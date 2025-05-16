<?php
// delete-servizi.php
/*
 * OggiInLab
 * Copyright (c) 2025 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "includes/config.php";

// Verify that the request is of type DELETE and the user is logged in
if ($_SERVER['REQUEST_METHOD'] === 'DELETE' && !empty($_SESSION["alogin"])) {
    // Get the ID of the service to delete
    $idServizio = isset($_GET['idServizio']) ? intval($_GET['idServizio']) : (isset($_POST['idServizio']) ? intval($_POST['idServizio']) : null);

    if ($idServizio > 0) {
        try {
            $stmt = $dbh->prepare("DELETE FROM servizi WHERE idServizio = :idServizio AND idAssistente = :idAssistente");
            $stmt->bindParam(':idServizio', $idServizio, PDO::PARAM_INT);
            $stmt->bindParam(':idAssistente', $_SESSION['id'], PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Servizio eliminato con successo.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Impossibile eliminare il servizio o servizio non trovato per questo utente.']);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore del database: ' . $e->getMessage()]);
        }
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID del servizio non valido.']);
    }
} else {
    http_response_code(405); // Method not allowed
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
}
?>