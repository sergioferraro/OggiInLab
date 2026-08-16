<?php
/*
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 *
 * TEMPLATE per includes/config.php (quest'ultimo escluso dal repo, vedi .gitignore).
 *
 * LE CREDENZIALI DEL DATABASE NON SONO PIU' HARDCODED IN QUESTO FILE.
 * Vengono lette dalle variabili d'ambiente (vedi REPORT_CREDENZIALI.md, Opzione A):
 *
 *     DB_HOST   (default: localhost)
 *     DB_USER   (obbligatoria in produzione)
 *     DB_PASS   (obbligatoria in produzione)
 *     DB_NAME   (default: nzschool)
 *
 * In produzione i valori sono esportati automaticamente dall'ambiente
 * operativo all'avvio (Apache2 / PHP-FPM + systemd, REPORT_CREDENZIALI.md §4.2).
 *
 * Per lo sviluppo locale puoi impostarle nel shell prima di avviare il server:
 *
 *     export DB_HOST=127.0.0.1 DB_USER=oggiinlab_app DB_PASS='...' DB_NAME=nzschool
 *     php -S localhost:8000
 */

// Token CSRF di sessione (comportamento invariato).
$_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));

// Debug: true solo in sviluppo locale, MAI in produzione.
define('APP_DEBUG', false);

/**
 * Legge una variabile d'ambiente di configurazione.
 * Restituisce $default se la variabile non è definita o è vuota.
 */
function env_cfg(string $name, ?string $default = null): ?string
{
    $v = getenv($name);
    return ($v === false || $v === '') ? $default : $v;
}

$dbHost = env_cfg('DB_HOST', 'localhost');
$dbUser = env_cfg('DB_USER');
$dbPass = env_cfg('DB_PASS');
$dbName = env_cfg('DB_NAME', 'nzschool');

// In produzione le credenziali DEVONO provenire dall'ambiente.
// Se mancano: dettaglio nei log, messaggio generico al client.
$missing = [];
if ($dbUser === null) { $missing[] = 'DB_USER'; }
if ($dbPass === null) { $missing[] = 'DB_PASS'; }
if ($missing !== [])
{
    error_log('OggiInLab: variabili d\'ambiente DB mancanti: ' . implode(', ', $missing)
        . ' (setup: REPORT_CREDENZIALI.md §4.2)');
    exit('Il servizio non è attualmente disponibile. Riprova più tardi.');
}

// Nomi delle costanti invariati: i file includenti non vanno toccati.
define('DB_HOST', $dbHost);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);
define('DB_NAME', $dbName);

try
{
    $dbh = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
        ]
    );
}
catch (PDOException $e)
{
    // Dettaglio solo nei log di server; al client solo un messaggio generico.
    error_log('OggiInLab errore connessione DB: ' . $e->getMessage());
    exit('Il servizio non è attualmente disponibile. Riprova più tardi.');
}
