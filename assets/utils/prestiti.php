<?php
/**
 * prestiti.php – API endpoint per gestione prestiti attrezzature
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/session.php';
header('Content-Type: application/json');

include __DIR__ . '/../../includes/config.php';

// -------------------------------------------------------------------
// CSRF validation
// -------------------------------------------------------------------
if (empty($_POST['_token']) || !is_string($_POST['_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['_token'])) {
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit;
}

// -------------------------------------------------------------------
// Auth guard
// -------------------------------------------------------------------
if (empty($_SESSION['alogin'])) {
    echo json_encode(['success' => false, 'message' => 'Non autenticato']);
    exit;
}

// -------------------------------------------------------------------
// Route by action
// -------------------------------------------------------------------
$action = $_POST['action'] ?? '';

switch ($action) {

    // ── List open (non riconsegnati) prestiti ──
    case 'list_open':
        try {
            $stmt = $dbh->prepare(
                'SELECT id, beneficiario, classe, data_prestito, data_consegna_prevista, descrizione_bene
                 FROM prestito
                 WHERE data_consegna_effettiva IS NULL
                 ORDER BY data_prestito DESC'
            );
            $stmt->execute();
            $prestiti = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'prestiti' => $prestiti]);
        } catch (PDOException $e) {
            error_log('Errore recupero prestiti: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Errore durante il recupero dei prestiti.']);
        }
        break;

    // ── Segna riconsegna ──
    case 'return':
        $prestitoId = isset($_POST['prestito_id']) ? intval($_POST['prestito_id']) : 0;
        if ($prestitoId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID prestito non valido.']);
            break;
        }
        try {
            $oggi = date('Y-m-d');
            $stmt = $dbh->prepare(
                'UPDATE prestito SET data_consegna_effettiva = :data_effettiva WHERE id = :id'
            );
            $stmt->execute([
                ':data_effettiva' => $oggi,
                ':id'             => $prestitoId,
            ]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Riconsegna registrata con successo.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Prestito non trovato o già riconsegnato.']);
            }
        } catch (PDOException $e) {
            error_log('Errore aggiornamento riconsegna: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Errore durante il salvataggio della riconsegna.']);
        }
        break;

    // ── Azione non riconosciuta ──
    default:
        echo json_encode(['success' => false, 'message' => 'Azione non riconosciuta.']);
        break;
}
