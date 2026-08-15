<?php
/**
 * toggle-admin-status.php – OggiInLab: Toggle stato amministratore (isActive)
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/session.php';
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/Logger.php';

// -------------------------------------------------------------------
// Auth guard
// -------------------------------------------------------------------
if (empty($_SESSION['alogin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Non autorizzato']);
    exit;
}

// Solo Super Admin può cambiare lo stato
if (empty($_SESSION['is_super_admin']) || $_SESSION['is_super_admin'] != 1) {
    Logger::warning('admin_status_no_privileges', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Permesso negato']);
    exit;
}

// -------------------------------------------------------------------
// Method check
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Logger::warning('admin_status_method_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo non consentito']);
    exit;
}

// -------------------------------------------------------------------
// CSRF check
// -------------------------------------------------------------------
$postedToken = $_POST['_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
    Logger::warning('admin_status_csrf_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token CSRF non valido']);
    exit;
}

// -------------------------------------------------------------------
// Validate ID
// -------------------------------------------------------------------
$adminId = $_POST['admin_id'] ?? null;
if ($adminId === null || !is_numeric($adminId)) {
    Logger::warning('admin_status_validation_error', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'id' => $adminId
    ]);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID non valido']);
    exit;
}

$adminId = (int) $adminId;

// -------------------------------------------------------------------
// Toggle isActive
// -------------------------------------------------------------------
try {
    // Recupera lo stato corrente e se è un super admin
    $stmt = $dbh->prepare(
        'SELECT isActive, is_super_admin FROM admin WHERE id = :id'
    );
    $stmt->execute([':id' => $adminId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        echo json_encode(['success' => false, 'message' => 'Amministratore non trovato.']);
        exit;
    }

    $currentIsActive = (int) $admin['isActive'];
    $isSuperAdmin = (int) $admin['is_super_admin'];

    // Non puoi disattivare te stesso
    if ($adminId == $_SESSION['id']) {
        echo json_encode(['success' => false, 'message' => 'Non puoi disattivare il tuo account.']);
        exit;
    }

    // Se stai disattivando, verifica che rimanga almeno un admin attivo
    if ($currentIsActive == 1) {
        $activeCount = $dbh->query('SELECT COUNT(*) FROM admin WHERE isActive = 1')->fetchColumn();
        if ($activeCount <= 1) {
            echo json_encode(['success' => false, 'message' => 'Deve rimanere almeno un amministratore attivo.']);
            exit;
        }
    }

    // Esegui il toggle
    $newStatus = $currentIsActive == 1 ? 0 : 1;
    $updateStmt = $dbh->prepare(
        'UPDATE admin SET isActive = :status WHERE id = :id'
    );
    $updateStmt->execute([
        ':status' => $newStatus,
        ':id'     => $adminId,
    ]);

    echo json_encode([
        'success'  => true,
        'message'  => 'Stato amministratore aggiornato.',
        'isActive' => $newStatus,
    ]);

} catch (PDOException $e) {
    error_log('Toggle admin status: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Errore di sistema.',
    ]);
}
