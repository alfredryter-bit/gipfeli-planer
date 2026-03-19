<?php
declare(strict_types=1);

$dataDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'software_data';
$securityLogger = $dataDir . DIRECTORY_SEPARATOR . 'security_logger.php';
$securityLogFile = $dataDir . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'app_security.log';

if (is_file($securityLogger)) {
    require_once $securityLogger;
}

if (function_exists('ls_security_log')) {
    ls_security_log($securityLogFile, 'http_403', 'forbidden', 403);
}

if (function_exists('ls_render_error_page')) {
    ls_render_error_page(403, '403 Forbidden', 'Du hast keine Berechtigung, diese Ressource aufzurufen.');
    exit;
}

http_response_code(403);
header('Content-Type: text/plain; charset=UTF-8');
echo '403 Forbidden';
