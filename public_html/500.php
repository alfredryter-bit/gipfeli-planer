<?php
declare(strict_types=1);

$dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'software_data';
$securityLogger = $dataDir . DIRECTORY_SEPARATOR . 'security_logger.php';
$securityLogFile = $dataDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app_security.log';

if (is_file($securityLogger)) {
    require_once $securityLogger;
}

if (function_exists('ls_security_log')) {
    ls_security_log($securityLogFile, 'http_500', 'internal server error', 500);
}

if (function_exists('ls_render_error_page')) {
    ls_render_error_page(500, '500 Serverfehler', 'Es ist ein interner Fehler aufgetreten. Bitte versuche es später erneut.');
    exit;
}

http_response_code(500);
header('Content-Type: text/plain; charset=UTF-8');
echo '500 Internal Server Error';
