<?php
/*
 * OggiInLab – Inizializzazione sessione sicura (punto di ingresso unico)
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under la MIT License
 *
 * Sostituisce session_start() in tutte le pagine:
 *  - HttpOnly: il cookie di sessione non è leggibile via JavaScript
 *    (mitiga il furto di PHPSESSID in caso di XSS residuo);
 *  - Secure: impostato automaticamente se la richiesta arriva via HTTPS
 *    (anche dietro proxy che inietta X-Forwarded-Proto);
 *  - SameSite=Lax: protezione CSRF aggiuntiva (i token CSRF restano la difesa primaria);
 *  - use_strict_mode: rigetto di session ID non ancora create dal server;
 *  - invio degli header di sicurezza centralizzati (security_headers.php).
 */

require_once __DIR__ . '/security_headers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    session_start();
}
