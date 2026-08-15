<?php
/**
 * elimina_calendario.php – OggiInLab: Elimina voce calendario
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);
require_once __DIR__ . '/../../includes/session.php';

require_once __DIR__ . '/../../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Metodo non consentito.');
}

// CSRF check
$postedToken = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
    die('Token CSRF non valido.');
}

$id = $_POST['idCalendario'] ?? null;
if ($id === null || !is_numeric($id)) {
    die('ID non valido.');
}

try {
    $stmt = $dbh->prepare('DELETE FROM calendario WHERE idCalendario = :id');
    $stmt->execute([':id' => (int) $id]);
} catch (PDOException $e) {
    error_log('Elimina calendario: ' . $e->getMessage());
    die('Errore durante l\'eliminazione.');
}

header('Location: ../../calend_ann.php');
exit;
