<?php 
// logout.php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
require_once __DIR__ . '/includes/session.php';

// Cancella tutti i dati di sessione
$_SESSION = array();

// Distruggi il cookie di sessione (previene session fixation al re-login)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

session_destroy();
header("Location: index.php");
exit();
?>
