<?php
/**
 * login_rate_limit.php – OggiInLab
 *
 * Rate limiting e lockout sul login (SECURITY_REPORT.md — M-3, CWE-307).
 *
 * Stesso meccanismo di assets/utils/public_today_gantt.php: stato su file
 * JSON (timestamp), finestra mobile, scrittura con LOCK_EX. I file di stato
 * vivono in logs/ (cartella di stato dell'app, già gitignorata; accesso web
 * negato via logs/.htaccess).
 *
 * Due livelli di protezione:
 *
 *  1) Throttling per IP
 *     Massimo IP_MAX_ATTEMPTS tentativi di login (riesciuti o falliti) per
 *     ogni IP in una finestra mobile di IP_WINDOW_SECONDS.
 *
 *  2) Lockout progressivo per username
 *     Dopo USER_MAX_FAILURES fallimenti consecutivi il nome utente è
 *     bloccato per LOCK_BASE_SECONDS; l'intervallo raddoppia a ogni
 *     ulteriore soglia di USER_MAX_FAILURES fallimenti, fino al massimo
 *     LOCK_MAX_SECONDS. Il conteggio si azzera al primo login riuscito o
 *     dopo LOCK_RESET_SECONDS senza nuovi fallimenti.
 *
 * Uso tipico (index.php):
 *   $ipState = LoginRateLimit::ipAttempt($_SERVER['REMOTE_ADDR'] ?? 'unknown');
 *   if ($ipState['blocked']) { ... }
 *   $userState = LoginRateLimit::checkUser($username);
 *   if ($userState['locked']) { ... }
 *   ... password_verify ...
 *   LoginRateLimit::recordSuccess($username);   // oppure recordFailure($username)
 *
 * OggiInLab
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 */
declare(strict_types=1);

final class LoginRateLimit {

    // ---- Policy (regolabili qui) ----
    public const IP_MAX_ATTEMPTS    = 20;     // tentativi di login per IP ...
    public const IP_WINDOW_SECONDS  = 900;    // ... in questa finestra (15 minuti)
    public const USER_MAX_FAILURES  = 5;      // fallimenti prima del primo lockout
    public const LOCK_BASE_SECONDS  = 300;    // primo lockout: 5 minuti
    public const LOCK_MAX_SECONDS   = 7200;   // lockout massimo: 2 ore
    public const LOCK_RESET_SECONDS = 86400;  // azzera il contatore dopo 24h senza nuovi fallimenti

    private const CLEANUP_MAX_AGE   = 172800; // cleanup: rimuove gli stati vecchi di 48h+

    // -------------------------------------------------------------------
    // Throttling per IP
    // -------------------------------------------------------------------

    /**
     * Registra un tentativo di login per IP e applica il throttling.
     *
     * @param string $ip indirizzo IP del client (REMOTE_ADDR)
     * @return array{blocked: bool, wait: int, count: int}
     *   blocked: true se la finestra per questo IP è già piena
     *   wait:    minuti da attendere prima del prossimo tentativo (0 se non bloccato)
     *   count:   tentativi attuali nella finestra
     */
    public static function ipAttempt(string $ip): array {
        $file = self::ipFile($ip);
        $now  = time();

        $data = self::readJson($file);
        // Mantieni solo i timestamp dentro la finestra mobile
        $data = is_array($data)
            ? array_values(array_filter($data, static fn($t): bool => is_int($t) && $now - $t < self::IP_WINDOW_SECONDS))
            : [];

        if (count($data) >= self::IP_MAX_ATTEMPTS) {
            self::writeJson($file, $data); // riscrivi la lista "pulita"
            $oldest  = $data[0] ?? $now;
            $waitSec = max(1, ($oldest + self::IP_WINDOW_SECONDS) - $now);
            return [
                'blocked' => true,
                'wait'    => (int)ceil($waitSec / 60),
                'count'   => count($data),
            ];
        }

        $data[] = $now;
        self::writeJson($file, $data);
        return ['blocked' => false, 'wait' => 0, 'count' => count($data)];
    }

    // -------------------------------------------------------------------
    // Lockout progressivo per username
    // -------------------------------------------------------------------

    /**
     * Controlla se il username è in lockout.
     *
     * @param string $username nome utente (già normalizzato dal chiamante)
     * @return array{locked: bool, wait: int, failures: int}
     *   locked:   true se il nome utente è bloccato
     *   wait:     minuti rimanenti di blocco (0 se non bloccato)
     *   failures: fallimenti accumulati
     */
    public static function checkUser(string $username): array {
        $file = self::userFile($username);
        $now  = time();
        $d    = self::readJson($file);

        if (!is_array($d)) {
            return ['locked' => false, 'wait' => 0, 'failures' => 0];
        }

        $failures    = (int)($d['failures'] ?? 0);
        $lockedUntil = (int)($d['locked_until'] ?? 0);
        $lastFailure = (int)($d['last_failure'] ?? 0);

        // Contatore vecchio (24h+ senza nuovi fallimenti): lo azzera
        if ($failures > 0 && $now - $lastFailure > self::LOCK_RESET_SECONDS) {
            $failures    = 0;
            $lockedUntil = 0;
            self::writeJson($file, ['failures' => 0, 'locked_until' => 0, 'last_failure' => $lastFailure]);
        }

        if ($lockedUntil > $now) {
            return [
                'locked'   => true,
                'wait'     => (int)ceil(($lockedUntil - $now) / 60),
                'failures' => $failures,
            ];
        }

        return ['locked' => false, 'wait' => 0, 'failures' => $failures];
    }

    /**
     * Registra un login fallito per username e (se dovuto) attiva/estende il lockout.
     *
     * Progressione: 5 fallimenti → 5 min; 10 → 10 min; 15 → 20 min;
     * 20 → 40 min; 25 → 80 min; oltre → LOCK_MAX_SECONDS (2h).
     *
     * @param string $username nome utente (già normalizzato dal chiamante)
     * @return array{locked: bool, wait: int, failures: int}
     *   locked:   true se dopo questo fallimento il nome utente è bloccato
     *   wait:     durata del blocco in minuti
     *   failures: fallimenti accumulati
     */
    public static function recordFailure(string $username): array {
        $file = self::userFile($username);
        $now  = time();
        $d    = self::readJson($file);

        $failures    = (int)($d['failures'] ?? 0);
        $lastFailure = (int)($d['last_failure'] ?? 0);

        // Contatore vecchio (24h+ senza nuovi fallimenti): lo azzera
        if ($failures > 0 && $now - $lastFailure > self::LOCK_RESET_SECONDS) {
            $failures = 0;
        }

        $failures++;

        // Lockout solo se si supera la soglia di fallimenti
        if ($failures >= self::USER_MAX_FAILURES) {
            $excess = $failures - self::USER_MAX_FAILURES;
            $steps  = min(intdiv($excess, self::USER_MAX_FAILURES), 8); // 2^8 x base, poi scatta il max
            $lock   = min(self::LOCK_BASE_SECONDS * (1 << $steps), self::LOCK_MAX_SECONDS);
            $lockedUntil = $now + $lock;
            $locked  = true;
        } else {
            $lock   = 0;
            $lockedUntil = (int)($d['locked_until'] ?? 0);
            $locked  = false;
        }

        self::writeJson($file, [
            'failures'     => $failures,
            'locked_until' => $lockedUntil,
            'last_failure' => $now,
        ]);

        return [
            'locked'   => $locked,
            'wait'     => $locked ? (int)ceil($lock / 60) : 0,
            'failures' => $failures,
        ];
    }

    /**
     * Login riuscito: azzera lo stato di lockout del username.
     */
    public static function recordSuccess(string $username): void {
        $file = self::userFile($username);
        if (is_file($file)) {
            @unlink($file);
        }
    }

    // -------------------------------------------------------------------
    // Manutenzione
    // -------------------------------------------------------------------

    /**
     * Rimuove i file di stato scaduti (chiamare occasionalmente, es. al login).
     *
     * @return int numero di file rimossi
     */
    public static function cleanup(int $maxAgeSeconds = self::CLEANUP_MAX_AGE): int {
        $now     = time();
        $removed = 0;
        foreach (glob(self::stateDir() . '/.login_*') ?: [] as $f) {
            if (is_file($f) && $now - (int)filemtime($f) > $maxAgeSeconds && @unlink($f)) {
                $removed++;
            }
        }
        return $removed;
    }

    // -------------------------------------------------------------------
    // Interni
    // -------------------------------------------------------------------

    private static function stateDir(): string {
        $dir = dirname(__DIR__) . '/logs';
        if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
            // Fallback: uploads/ esiste già nell'app
            $dir = dirname(__DIR__) . '/uploads';
        }
        return $dir;
    }

    private static function ipFile(string $ip): string {
        $key = preg_replace('/[^A-Za-z0-9._\-]/', '_', substr($ip, 0, 64)) ?: 'unknown';
        return self::stateDir() . '/.login_ip_' . $key;
    }

    private static function userFile(string $username): string {
        $u  = trim($username);
        $lc = function_exists('mb_strtolower') ? mb_strtolower($u, 'UTF-8') : strtolower($u);
        $key = preg_replace('/[^A-Za-z0-9._\-\@]/', '_', (string)$lc);
        $key = substr($key, 0, 64) ?: 'unknown';
        return self::stateDir() . '/.login_user_' . $key;
    }

    private static function readJson(string $file): ?array {
        if (!is_file($file)) {
            return null;
        }
        $raw = @file_get_contents($file);
        if ($raw === false) {
            return null;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : null;
    }

    private static function writeJson(string $file, array $data): void {
        @file_put_contents($file, json_encode($data), LOCK_EX);
    }
}
