<?php
/**
 * cleanup_logs.php – Eliminazione automatica dei log più vecchi di 6 mesi
 *
 * Da invocare tramite cron, non da browser.
 * OggiInLab – Copyright (c) 2026 Sergio Ferraro
 */

declare(strict_types=1);

// ------------------------------------------------------------------
// Sicurezza: esecuzione solo da CLI
// ------------------------------------------------------------------
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Accesso negato. Questo script può essere eseguito solo da riga di comando.');
}

require_once __DIR__ . '/../../includes/config.php';

// ------------------------------------------------------------------
// Configurazione
// ------------------------------------------------------------------
$RETENTION_DAYS = 180; // 6 mesi
$LOG_DIR        = '/var/log/php'; // adattare al proprio sistema

// ------------------------------------------------------------------
// Pulizia log PHP (file)
// ------------------------------------------------------------------
if (is_dir($LOG_DIR)) {
    $files = glob($LOG_DIR . '/*.log');
    $now   = time();
    foreach ($files as $file) {
        if (filemtime($file) < ($now - ($RETENTION_DAYS * 86400))) {
            unlink($file);
            echo "[OK] Eliminato: $file\n";
        }
    }
}

// ------------------------------------------------------------------
// Pulizia log di database (se presente una tabella apposita)
// ------------------------------------------------------------------
try {
    // Se in futuro aggiungi una tabella 'syslog' o 'access_log',
    // sblocca la query qui:
    // $dbh->prepare("DELETE FROM access_log WHERE created_at < DATE_SUB(NOW(), INTERVAL 6 MONTH)")->execute();
    // echo "[OK] Log di database puliti.\n";
} catch (PDOException $e) {
    echo "[ERRORE] Pulizia DB: " . $e->getMessage() . "\n";
}

echo "[Fatto] Cleanup completato a " . date('Y-m-d H:i:s') . "\n";
