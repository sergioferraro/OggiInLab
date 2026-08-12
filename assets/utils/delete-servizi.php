<?php
// delete-servizi.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../includes/config.php";
require_once __DIR__ . '/../../includes/Logger.php';

header('Content-Type: application/json; charset=utf-8');

// Verify that the user is logged in and POST is used
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION["alogin"])) {
    // CSRF validation
    $postedToken = $_POST['_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        Logger::warning('servizio_delete_csrf_error', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token di sicurezza non valido.']);
        exit();
    }

    // Get the ID of the service to delete
    $idServizio = isset($_POST['idServizio']) ? intval($_POST['idServizio']) : null;

    if ($idServizio > 0) {
        try {
            $stmt = $dbh->prepare("DELETE FROM servizi WHERE idServizio = :idServizio AND idAssistente = :idAssistente");
            $stmt->bindParam(':idServizio', $idServizio, PDO::PARAM_INT);
            $stmt->bindParam(':idAssistente', $_SESSION['id'], PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                Logger::success('servizio_delete', [
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'id_servizio' => $idServizio,
                    'id_assistente' => $_SESSION['id']
                ]);
                echo json_encode(['success' => true, 'message' => 'Servizio eliminato con successo.']);
            } else {
                Logger::info('servizio_delete_not_found', [
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    'id_servizio' => $idServizio,
                    'id_assistente' => $_SESSION['id']
                ]);
                echo json_encode(['success' => false, 'message' => 'Impossibile eliminare il servizio o servizio non trovato per questo utente.']);
            }
        } catch (PDOException $e) {
            Logger::error('servizio_delete_db_error', [
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'id_servizio' => $idServizio,
                'id_assistente' => $_SESSION['id'],
                'error_message' => $e->getMessage()
            ]);
            error_log("Errore delete-servizi: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Errore del database.']);
        }
    } else {
        Logger::warning('servizio_delete_validation_error', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'id_servizio' => $idServizio
        ]);
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID del servizio non valido.']);
    }
} else {
    Logger::warning('servizio_delete_method_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    http_response_code(405); // Method not allowed
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito.']);
}
?>