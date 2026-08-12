<?php
/*
 * OggiInLab - Logger Utility
 * Copyright (c) 2026 Sergio Ferraro
 * Licensed under the MIT License
 *
 * Centralized logging for admin actions.
 * Logs are stored in JSON format for easy parsing and reading.
 */

class Logger {
    private const LOG_DIR = __DIR__ . '/../logs/';
    private const LOG_FILE = 'admin_actions.log';
    
    // Log levels (priority order)
    const LEVEL_DEBUG   = 100;
    const LEVEL_INFO    = 200;
    const LEVEL_SUCCESS = 250;
    const LEVEL_WARNING = 300;
    const LEVEL_ERROR   = 400;
    const LEVEL_CRITICAL = 500;
    
    // Human-readable level names
    private static $levelNames = [
        self::LEVEL_DEBUG   => 'DEBUG',
        self::LEVEL_INFO    => 'INFO',
        self::LEVEL_SUCCESS => 'SUCCESS',
        self::LEVEL_WARNING => 'WARNING',
        self::LEVEL_ERROR   => 'ERROR',
        self::LEVEL_CRITICAL => 'CRITICAL'
    ];
    
    /**
     * Get the current log file path
     */
    private static function getLogFilePath(): string {
        return self::LOG_DIR . self::LOG_FILE;
    }
    
    /**
     * Initialize the log directory if it doesn't exist
     */
    public static function init(): void {
        if (!is_dir(self::LOG_DIR)) {
            mkdir(self::LOG_DIR, 0755, true);
        }
        
        // Create empty log file if it doesn't exist
        $logFile = self::getLogFilePath();
        if (!file_exists($logFile)) {
            file_put_contents($logFile, json_encode([]));
        }
    }
    
    /**
     * Get the admin information from session
     */
    private static function getAdminInfo(): array {
        return [
            'id' => $_SESSION['id'] ?? null,
            'name' => $_SESSION['nomeCompleto'] ?? $_SESSION['alogin'] ?? null,
            'username' => $_SESSION['alogin'] ?? null,
            'is_super_admin' => $_SESSION['is_super_admin'] ?? false
        ];
    }
    
    /**
     * Get client information (IP, user agent)
     */
    private static function getClientInfo(): array {
        return [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ];
    }
    
    /**
     * Get current timestamp in ISO 8601 format
     */
    private static function getTimestamp(): string {
        return date('c'); // ISO 8601 format with timezone
    }
    
    /**
     * Log a message with the specified level
     *
     * @param int $level The log level (self::LEVEL_*)
     * @param string $actionType Type of action being logged (e.g., 'project_delete', 'appointment_add')
     * @param array $context Additional context data
     * @return bool True if logging was successful, false otherwise
     */
    public static function log(int $level, string $actionType, array $context = []): bool {
        // Build the log entry
        $entry = [
            'timestamp' => self::getTimestamp(),
            'level' => self::$levelNames[$level] ?? 'UNKNOWN',
            'level_code' => $level,
            'admin' => self::getAdminInfo(),
            'action_type' => $actionType,
            'client_info' => self::getClientInfo(),
        ] + $context; // Merge context into the entry
        
        // Convert to JSON
        $jsonEntry = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // Write to log file (append mode)
        $logFile = self::getLogFilePath();
        try {
            // Use LOCK_EX to prevent concurrent write issues
            return file_put_contents($logFile, $jsonEntry . PHP_EOL, FILE_APPEND | LOCK_EX) !== false;
        } catch (Exception $e) {
            error_log("Logger failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log a debug message
     */
    public static function debug(string $actionType, array $context = []): bool {
        return self::log(self::LEVEL_DEBUG, $actionType, $context);
    }
    
    /**
     * Log an info message (normal operations)
     */
    public static function info(string $actionType, array $context = []): bool {
        return self::log(self::LEVEL_INFO, $actionType, $context);
    }
    
    /**
     * Log a success message
     */
    public static function success(string $actionType, array $context = []): bool {
        return self::log(self::LEVEL_SUCCESS, $actionType, $context);
    }
    
    /**
     * Log a warning message (suspicious but allowed)
     */
    public static function warning(string $actionType, array $context = []): bool {
        return self::log(self::LEVEL_WARNING, $actionType, $context);
    }
    
    /**
     * Log an error message
     */
    public static function error(string $actionType, array $context = []): bool {
        return self::log(self::LEVEL_ERROR, $actionType, $context);
    }
    
    /**
     * Log a critical security-related message
     */
    public static function critical(string $actionType, array $context = []): bool {
        return self::log(self::LEVEL_CRITICAL, $actionType, $context);
    }
    
    /**
     * Read log entries (for viewing in admin panel)
     *
     * @param int|null $limit Number of entries to retrieve
     * @param int|null $minLevel Minimum level filter
     * @return array Array of log entries
     */
    public static function read(int $limit = 100, ?int $minLevel = null): array {
        $logFile = self::getLogFilePath();
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        // Read all lines and then reverse for most recent first
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lines = array_reverse($lines); // Most recent first
        
        $entries = [];
        foreach ($lines as $line) {
            if (count($entries) >= $limit) break;
            
            $entry = json_decode(trim($line), true);

            // Skip malformed lines (null/false) and empty arrays from init()
            if (!is_array($entry) || empty($entry)) {
                continue;
            }

            // Apply level filter
            if ($minLevel !== null && isset($entry['level_code']) && $entry['level_code'] < $minLevel) {
                continue;
            }

            $entries[] = $entry;
        }
        
        return $entries;
    }
    
    /**
     * Clear all log entries
     */
    public static function clear(): bool {
        $logFile = self::getLogFilePath();
        if (file_exists($logFile)) {
            return file_put_contents($logFile, '') !== false;
        }
        return true;
    }
    
    /**
     * Get statistics about logs
     */
    public static function getStats(): array {
        $logFile = self::getLogFilePath();
        
        if (!file_exists($logFile)) {
            return [
                'total_entries' => 0,
                'file_size' => 0,
                'levels' => []
            ];
        }
        
        $stats = [
            'total_entries' => 0,
            'file_size' => filesize($logFile),
            'levels' => []
        ];
        
        $handle = fopen($logFile, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $entry = json_decode(trim($line), true);
                if ($entry) {
                    $stats['total_entries']++;
                    $level = $entry['level'];
                    $stats['levels'][$level] = ($stats['levels'][$level] ?? 0) + 1;
                }
            }
            fclose($handle);
        }
        
        return $stats;
    }
}

// Initialize on first load
Logger::init();
?>
