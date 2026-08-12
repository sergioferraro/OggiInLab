<?php
// reset_admin.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
session_start();
include('../../includes/config.php');
require_once __DIR__ . '/../../includes/Logger.php';
error_reporting(0);

// --- Auth guard: solo admin loggati possono resettare password ---
if (empty($_SESSION['alogin'])) {
    Logger::warning('admin_password_reset_unauthorized', [
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- CSRF validation ---
    if (empty($_POST['_token']) || $_POST['_token'] !== ($_SESSION['csrf_token'] ?? '')) {
        Logger::warning('admin_password_reset_csrf_error', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        die("Errore: Token di sicurezza non valido.");
    }
    $admin_id = intval($_POST['admin_id']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        Logger::warning('admin_password_reset_mismatch', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'admin_id' => $admin_id
        ]);
        die("Errore: Le password non corrispondono.");
    }

    // Password hash
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);

    // Update
    $sql = "UPDATE admin SET Password = :password WHERE id = :admin_id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
    $query->bindParam(':admin_id', $admin_id, PDO::PARAM_INT);

    if ($query->execute()) {
        Logger::success('admin_password_reset', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'admin_id' => $admin_id,
            'reset_by_admin' => $_SESSION['id'] ?? null
        ]);
        echo "Successo: Password aggiornata con successo.";
    } else {
        Logger::error('admin_password_reset_db_error', [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'admin_id' => $admin_id
        ]);
        echo "Errore: Non è stato possibile aggiornare la password.";
    }
} else {
    header("Location: index.php");
}
?>
