<?php
declare(strict_types=1);

if (!defined('APP_DATA_DIR')) {
    return;
}

require_once APP_DATA_DIR . DIRECTORY_SEPARATOR . 'security_logger.php';

if (!isset($GLOBALS['LS_ACCESS_LOG']) || !is_string($GLOBALS['LS_ACCESS_LOG']) || $GLOBALS['LS_ACCESS_LOG'] === '') {
    return;
}

if (!defined('LS_ACCESS_LOG_SHUTDOWN_REGISTERED')) {
    define('LS_ACCESS_LOG_SHUTDOWN_REGISTERED', true);
    register_shutdown_function(function (): void {
        if (!function_exists('ls_access_log')) {
            return;
        }
        $accessLog = $GLOBALS['LS_ACCESS_LOG'] ?? '';
        if (!is_string($accessLog) || $accessLog === '') {
            return;
        }
        ls_access_log($accessLog);
    });
}
