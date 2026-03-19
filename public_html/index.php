<?php
// index.php - Hauptdatei für den erweiterten Gipfeli-Koordinator
define('SECURE_ACCESS', true);
define('APP_ROOT', __DIR__);
define('APP_DATA_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'software_data');
define('APP_CONFIG_FILE', APP_DATA_DIR . DIRECTORY_SEPARATOR . 'config.php');
$LS_DATA_DIR = APP_DATA_DIR . DIRECTORY_SEPARATOR . 'logs';
$LS_ACCESS_LOG = $LS_DATA_DIR . DIRECTORY_SEPARATOR . 'app_access.log';
$LS_SECURITY_LOG = $LS_DATA_DIR . DIRECTORY_SEPARATOR . 'app_security.log';
$LS_AUTH_STATE_FILE = $LS_DATA_DIR . DIRECTORY_SEPARATOR . 'auth_monitor_state.json';
$GLOBALS['LS_DATA_DIR'] = $LS_DATA_DIR;
$GLOBALS['LS_ACCESS_LOG'] = $LS_ACCESS_LOG;
$GLOBALS['LS_SECURITY_LOG'] = $LS_SECURITY_LOG;
$GLOBALS['LS_AUTH_STATE_FILE'] = $LS_AUTH_STATE_FILE;

if (is_file(APP_DATA_DIR . DIRECTORY_SEPARATOR . 'bootstrap_hook.php')) {
    require_once APP_DATA_DIR . DIRECTORY_SEPARATOR . 'bootstrap_hook.php';
}

$isHttpsRequest = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);

ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttpsRequest,
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();
header('Content-Type: text/html; charset=utf-8');

// In Produktion keine Fehlerausgabe an Clients
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Hilfsfunktion für die Anpassung der Farben
function adjustBrightness($hex, $steps) {
    // Aus Hex-Farbe RGB-Werte extrahieren
    $hex = ltrim($hex, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Helligkeit anpassen
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));

    // Zurück nach Hex konvertieren
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

// Konfiguration für die Datenbank und E-Mail
$config = [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'gipfeli_db',
    'allowed_domains' => ['microtom.net', 'gmail.com'], // Erlaubte E-Mail-Domains
    'mail_host' => '',   // SMTP-Server
    'mail_port' => 587,  // SMTP-Port (typischerweise 587 für TLS)
    'mail_user' => '',   // SMTP-Benutzername
    'mail_pass' => '',   // SMTP-Passwort
    'mail_from' => '',   // Absender-E-Mail
    'mail_name' => 'Gipfeli-Koordinator',  // Absender-Name
    
    // Branding-Einstellungen
    'app_name' => 'MicroTom Gipfeli-Koordinator',    // Name der Anwendung
    'app_logo' => 'assets/logo.png',                 // Pfad zum Logo
    'app_primary_color' => '#e74c3c',                // Primärfarbe (Header, Buttons)
    'app_secondary_color' => '#6c757d',              // Sekundärfarbe (für Sekundär-Buttons)
    'app_favicon' => 'assets/favicon.ico',           // Favicon
    'app_base_url' => '',
    
    // Feature-Einstellungen
    'allow_multiple_entries' => true,                // Erlaube mehrere Einträge pro Tag
    'show_multiple_warning' => true,                 // Zeige Warnung bei mehreren Einträgen
    'debug_mode' => false                            // Schreibt zusätzliche Debug-Logs ins error.log
];

// Standardrolle für neue Benutzer
$defaultRole = 'user';

// Branding- und SMTP-Einstellungen aus der Datenbank laden, falls vorhanden
function loadBrandingFromDatabase() {
    global $config;
    
    try {
        $pdo = connectDB();
        if (!$pdo instanceof PDO) {
            return;
        }
        // Prüfen ob die Tabelle existiert
        $stmt = $pdo->query("SHOW TABLES LIKE 'app_config'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT category, name, value FROM app_config WHERE category IN ('branding', 'smtp', 'system')");
            while ($row = $stmt->fetch()) {
                $name = $row['name'];
                $value = $row['value'];
                if ($name === 'allow_multiple_entries' || $name === 'show_multiple_warning' || $name === 'debug_mode') {
                    $config[$name] = ((string)$value === '1');
                } elseif ($name === 'mail_port') {
                    $config[$name] = (int)$value;
                } else {
                    $config[$name] = $value;
                }
            }
        }
    } catch (PDOException $e) {
        // Ignoriere Fehler beim Laden - wir verwenden die Standardwerte
        error_log("Fehler beim Laden der Branding-Einstellungen: " . $e->getMessage());
    }
}

// Lade Konfiguration außerhalb der Webroot
if (is_file(APP_CONFIG_FILE)) {
    include APP_CONFIG_FILE;
}

function isDebugModeEnabled() {
    global $config;
    $value = $config['debug_mode'] ?? false;
    if (is_bool($value)) {
        return $value;
    }
    if (is_int($value)) {
        return $value === 1;
    }
    $normalized = strtolower(trim((string)$value));
    return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
}

function debugLog($message) {
    if (!isDebugModeEnabled()) {
        return;
    }
    error_log((string)$message);
}

function getAppCacheBusterVersion() {
    static $version = null;
    if ($version !== null) {
        return $version;
    }

    global $config;
    $configured = trim((string)($config['asset_version'] ?? ''));
    if ($configured !== '') {
        $version = preg_replace('/[^a-zA-Z0-9._-]/', '', $configured);
        return $version !== '' ? $version : '1';
    }

    $version = (string)@filemtime(__FILE__);
    if ($version === '' || $version === '0') {
        $version = date('YmdHis');
    }
    return $version;
}

function cacheBustUrl($path) {
    $path = trim((string)$path);
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $path) || stripos($path, 'data:') === 0) {
        return $path;
    }

    $separator = (strpos($path, '?') !== false) ? '&' : '?';
    return $path . $separator . 'v=' . rawurlencode(getAppCacheBusterVersion());
}

function getSupportedLanguages() {
    $dir = APP_ROOT . DIRECTORY_SEPARATOR . 'i18n';
    if (!is_dir($dir)) {
        return ['de', 'en'];
    }

    $languages = [];
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
        $code = strtolower((string)pathinfo($file, PATHINFO_FILENAME));
        if (preg_match('/^[a-z]{2}(?:_[a-z]{2})?$/', $code)) {
            $languages[] = $code;
        }
    }
    $languages = array_values(array_unique($languages));
    sort($languages);

    if (empty($languages)) {
        return ['de', 'en'];
    }
    return $languages;
}

function getCurrentLanguage() {
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    $supported = getSupportedLanguages();
    $requested = strtolower(trim((string)($_GET['lang'] ?? '')));
    if (in_array($requested, $supported, true)) {
        $_SESSION['lang'] = $requested;
    }

    $sessionLang = strtolower(trim((string)($_SESSION['lang'] ?? 'de')));
    $lang = in_array($sessionLang, $supported, true) ? $sessionLang : 'de';
    $_SESSION['lang'] = $lang;
    return $lang;
}

function t($key, $fallback = null) {
    $lang = getCurrentLanguage();
    $translations = loadTranslationCatalog($lang);
    if (isset($translations[$key])) {
        return $translations[$key];
    }

    if ($lang !== 'de') {
        $fallbackCatalog = loadTranslationCatalog('de');
        if (isset($fallbackCatalog[$key])) {
            return $fallbackCatalog[$key];
        }
    }

    if ($fallback !== null) {
        return $fallback;
    }
    return $key;
}

function loadTranslationCatalog($lang) {
    static $catalogCache = [];
    $lang = strtolower(trim((string)$lang));
    if ($lang === '') {
        $lang = 'de';
    }

    if (isset($catalogCache[$lang])) {
        return $catalogCache[$lang];
    }

    $path = APP_ROOT . DIRECTORY_SEPARATOR . 'i18n' . DIRECTORY_SEPARATOR . $lang . '.php';
    if (!is_file($path)) {
        $catalogCache[$lang] = [];
        return $catalogCache[$lang];
    }

    $data = include $path;
    $catalogCache[$lang] = is_array($data) ? $data : [];
    return $catalogCache[$lang];
}

function buildPageUrl(array $overrides = []) {
    $query = $_GET;
    unset($query['api'], $query['endpoint']);
    foreach ($overrides as $key => $value) {
        if ($value === null || $value === '') {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }
    $qs = http_build_query($query);
    return $qs === '' ? '?' : ('?' . $qs);
}
// Datenbankverbindung herstellen
function connectDB() {
    global $config;
    try {
        $dsn = "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ];
        $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
        return $pdo;
    } catch (PDOException $e) {
        // Leitet zur Installation weiter, wenn die Datenbank nicht erreichbar ist
        if (strpos((string)($_SERVER['REQUEST_URI'] ?? ''), 'setup.php') === false
            && is_file(APP_ROOT . DIRECTORY_SEPARATOR . 'setup.php')) {
            header('Location: setup.php');
            exit;
        }
        return null;
    }
}

// Branding-Einstellungen laden, wenn möglich
if (function_exists('connectDB')) {
    loadBrandingFromDatabase();
}

// Prüfen, ob der Benutzer angemeldet ist
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Prüfen, ob der angemeldete Benutzer ein Admin ist
function isAdmin() {
    return isLoggedIn()
        && isset($_SESSION['user_role'])
        && in_array($_SESSION['user_role'], ['admin', 'super_admin'], true);
}

function isSuperAdmin() {
    return isLoggedIn()
        && isset($_SESSION['user_role'])
        && $_SESSION['user_role'] === 'super_admin';
}

function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function getAppBaseUrl() {
    global $config;

    if (!empty($config['app_base_url']) && filter_var($config['app_base_url'], FILTER_VALIDATE_URL)) {
        return rtrim($config['app_base_url'], '/');
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443);
    $scheme = $isHttps ? 'https' : 'http';
    $host = $_SERVER['SERVER_NAME'] ?? 'localhost';

    return $scheme . '://' . $host;
}

function validatePasswordPolicy($password) {
    if (!is_string($password)) {
        return 'Ungültiges Passwortformat';
    }

    $length = strlen($password);
    if ($length < 10) {
        return 'Passwort muss mindestens 10 Zeichen lang sein';
    }
    if ($length > 128) {
        return 'Passwort darf maximal 128 Zeichen lang sein';
    }

    $classes = 0;
    $classes += preg_match('/[a-z]/', $password) ? 1 : 0;
    $classes += preg_match('/[A-Z]/', $password) ? 1 : 0;
    $classes += preg_match('/[0-9]/', $password) ? 1 : 0;
    $classes += preg_match('/[^a-zA-Z0-9]/', $password) ? 1 : 0;

    if ($classes < 3) {
        return 'Passwort muss mindestens 3 Zeichentypen enthalten (Gross-/Kleinbuchstaben, Zahlen, Sonderzeichen)';
    }

    return null;
}

function normalizeEmailAddress($email) {
    $normalized = strtolower(trim((string)$email));
    if ($normalized === '' || strlen($normalized) > 254) {
        return null;
    }
    return filter_var($normalized, FILTER_VALIDATE_EMAIL) ? $normalized : null;
}

function getAllowedRoleValues() {
    return ['user', 'admin', 'super_admin'];
}

function getRequestHeadersSafe() {
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        return is_array($headers) ? $headers : [];
    }

    $headers = [];
    foreach ($_SERVER as $name => $value) {
        if (strpos($name, 'HTTP_') === 0) {
            $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
            $headers[$key] = $value;
        }
    }
    return $headers;
}

function getJsonInput() {
    static $parsed = false;
    static $jsonData = [];

    if (!$parsed) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw, true);
        $jsonData = is_array($decoded) ? $decoded : [];
        $parsed = true;
    }

    return $jsonData;
}

function logSecurityEventSafe($type, $detail = '', $status = null, array $extra = []) {
    $securityLog = $GLOBALS['LS_SECURITY_LOG'] ?? '';
    if (!is_string($securityLog) || $securityLog === '' || !function_exists('ls_security_log')) {
        return;
    }
    ls_security_log($securityLog, (string)$type, (string)$detail, is_int($status) ? $status : null, $extra);
}

function monitorAuthPostRequest($endpoint, array $payload) {
    $endpoint = strtolower(trim((string)$endpoint));
    if ($endpoint !== 'login' && $endpoint !== 'register') {
        return;
    }

    $securityLog = $GLOBALS['LS_SECURITY_LOG'] ?? '';
    $stateFile = $GLOBALS['LS_AUTH_STATE_FILE'] ?? '';
    if (!is_string($securityLog) || $securityLog === '' || !is_string($stateFile) || $stateFile === '' || !function_exists('ls_monitor_auth_post')) {
        return;
    }

    $identifier = '';
    if (isset($payload['email']) && is_string($payload['email'])) {
        $identifier = trim($payload['email']);
    } elseif (isset($payload['username']) && is_string($payload['username'])) {
        $identifier = trim($payload['username']);
    } elseif (isset($payload['name']) && is_string($payload['name'])) {
        $identifier = trim($payload['name']);
    }

    ls_monitor_auth_post($securityLog, $stateFile, $endpoint, $identifier);
}

function consumeRateLimit($action, $maxAttempts, $windowSeconds, $identifier = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = hash('sha256', $ip . '|' . strtolower((string)$identifier));

    $dir = APP_DATA_DIR . DIRECTORY_SEPARATOR . 'rate_limits';
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        return true;
    }

    $file = $dir . DIRECTORY_SEPARATOR . preg_replace('/[^a-z0-9_-]/i', '_', $action) . '.json';
    $now = time();
    $cutoff = $now - (int)$windowSeconds;
    $data = [];

    if (is_file($file)) {
        $raw = @file_get_contents($file);
        $decoded = json_decode((string)$raw, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }

    foreach ($data as $storedKey => $timestamps) {
        if (!is_array($timestamps)) {
            unset($data[$storedKey]);
            continue;
        }
        $data[$storedKey] = array_values(array_filter($timestamps, function ($ts) use ($cutoff) {
            return is_int($ts) && $ts >= $cutoff;
        }));
        if (empty($data[$storedKey])) {
            unset($data[$storedKey]);
        }
    }

    if (!isset($data[$key])) {
        $data[$key] = [];
    }

    if (count($data[$key]) >= (int)$maxAttempts) {
        return false;
    }

    $data[$key][] = $now;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

// Benutzer-Authentifizierung
function authenticateUser($email, $password) {
    $email = normalizeEmailAddress($email);
    if ($email === null || !is_string($password) || $password === '') {
        return ['success' => false, 'reason' => 'invalid'];
    }

    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'reason' => 'invalid'];
        }
        if (isset($user['is_active']) && (int)$user['is_active'] !== 1) {
            return ['success' => false, 'reason' => 'inactive'];
        }

        if (password_verify($password, $user['password'])) {
            // Passwort korrekt, Session setzen
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];

            $touchStmt = $pdo->prepare('UPDATE users SET last_active_at = NOW() WHERE id = ?');
            $touchStmt->execute([(int)$user['id']]);
            $_SESSION['last_activity_touch'] = time();
            
            // Audit-Log: Login
            addAuditLog('login', "Benutzer hat sich angemeldet");
            
            return ['success' => true];
        }
        return ['success' => false, 'reason' => 'invalid'];
    } catch (PDOException $e) {
        return ['success' => false, 'reason' => 'invalid'];
    }
}

function touchCurrentUserActivity() {
    if (!isLoggedIn()) {
        return;
    }
    $now = time();
    if (isset($_SESSION['last_activity_touch']) && ($now - (int)$_SESSION['last_activity_touch']) < 300) {
        return;
    }
    try {
        $pdo = connectDB();
        if (!$pdo instanceof PDO) {
            return;
        }
        $stmt = $pdo->prepare('UPDATE users SET last_active_at = NOW() WHERE id = ?');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $_SESSION['last_activity_touch'] = $now;
    } catch (PDOException $e) {
        // no-op
    }
}

function destroyCurrentSession() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function enforceCurrentUserIsActive($isApiRequest = false) {
    if (!isLoggedIn()) {
        return true;
    }

    try {
        $pdo = connectDB();
        if (!$pdo instanceof PDO) {
            return true;
        }

        $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $user = $stmt->fetch();

        if (!$user || (int)($user['is_active'] ?? 0) !== 1) {
            destroyCurrentSession();
            if ($isApiRequest) {
                header('Content-Type: application/json');
                http_response_code(401);
                echo json_encode(['error' => 'Sitzung beendet, weil der Benutzer deaktiviert wurde']);
                exit;
            }
            header('Location: ?page=login');
            exit;
        }

        // Sessiondaten mit Datenbank synchron halten (z.B. bei Rollenänderung)
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        return true;
    } catch (PDOException $e) {
        return true;
    }
}

// Überprüfen, ob die E-Mail-Domain erlaubt ist
function isAllowedEmailDomain($email) {
    global $config;
    $normalizedEmail = normalizeEmailAddress($email);
    if ($normalizedEmail === null) {
        return false;
    }
    $domain = strtolower(substr(strrchr($normalizedEmail, "@"), 1));
    $allowedDomains = array_values(array_filter(array_map('strtolower', (array)($config['allowed_domains'] ?? []))));
    if (empty($allowedDomains)) {
        return true;
    }
    return in_array($domain, $allowedDomains, true);
}

// Generiere einen zufälligen Token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

function logSmtpEvent($status, array $context = []) {
    $dir = APP_DATA_DIR . DIRECTORY_SEPARATOR . 'logs';
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        return;
    }

    $entry = [
        'timestamp' => date('c'),
        'status' => $status,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_id' => $_SESSION['user_id'] ?? null,
        'context' => $context
    ];

    @file_put_contents(
        $dir . DIRECTORY_SEPARATOR . 'smtp.log',
        json_encode($entry, JSON_UNESCAPED_UNICODE) . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}

function getSmtpLogEntries($limit = 200) {
    $file = APP_DATA_DIR . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'smtp.log';
    if (!is_file($file)) {
        return [];
    }

    $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (!is_array($lines)) {
        return [];
    }

    $lines = array_reverse($lines);
    if ($limit > 0) {
        $lines = array_slice($lines, 0, (int)$limit);
    }

    $entries = [];
    foreach ($lines as $line) {
        $decoded = json_decode((string)$line, true);
        if (is_array($decoded)) {
            $entries[] = $decoded;
        } else {
            $entries[] = ['timestamp' => null, 'status' => 'raw', 'context' => ['line' => (string)$line]];
        }
    }
    return $entries;
}

function ensureAppConfigTable(PDO $pdo) {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `app_config` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category` VARCHAR(50) NOT NULL,
        `name` VARCHAR(50) NOT NULL,
        `value` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY (`category`, `name`)
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}

function getAppConfigCategory(PDO $pdo, $category) {
    ensureAppConfigTable($pdo);
    $stmt = $pdo->prepare("SELECT name, value FROM app_config WHERE category = ?");
    $stmt->execute([(string)$category]);
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['name']] = $row['value'];
    }
    return $settings;
}

function saveAppConfigCategory(PDO $pdo, $category, array $settings) {
    ensureAppConfigTable($pdo);
    $existing = getAppConfigCategory($pdo, $category);
    foreach ($settings as $name => $value) {
        if (isset($existing[$name])) {
            $stmt = $pdo->prepare("UPDATE app_config SET value = ? WHERE category = ? AND name = ?");
            $stmt->execute([(string)$value, (string)$category, (string)$name]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO app_config (category, name, value) VALUES (?, ?, ?)");
            $stmt->execute([(string)$category, (string)$name, (string)$value]);
        }
    }
}

// E-Mail senden
function sendEmail($to, $subject, $body, $isHTML = true) {
    global $config;

    $normalizedTo = normalizeEmailAddress($to);
    if ($normalizedTo === null) {
        logSmtpEvent('invalid_recipient', ['to' => (string)$to, 'subject' => (string)$subject]);
        return false;
    }
    
    // Prüfen, ob die Mail-Konfiguration vollständig ist
    if (empty($config['mail_host']) || empty($config['mail_user']) || empty($config['mail_pass']) || empty($config['mail_from'])) {
        logSmtpEvent('missing_config', [
            'to' => $normalizedTo,
            'subject' => (string)$subject
        ]);
        return false;
    }
    
    // App-Name für Absender korrekt verwenden
    $mailFromName = $config['mail_name'] ?? ($config['app_name'] ?? 'Gipfeli-Koordinator');
    
    // PHPMailer verwenden, falls verfügbar
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        require 'vendor/autoload.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $config['mail_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['mail_user'];
            $mail->Password = $config['mail_pass'];
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['mail_port'];
            $mail->CharSet = 'UTF-8';
            
            // Recipients
            $mail->setFrom($config['mail_from'], $mailFromName);
            $mail->addAddress($normalizedTo);
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            logSmtpEvent('sent', [
                'transport' => 'smtp_phpmailer',
                'to' => $normalizedTo,
                'subject' => (string)$subject
            ]);
            return true;
        } catch (Exception $e) {
            logSmtpEvent('error', [
                'transport' => 'smtp_phpmailer',
                'to' => $normalizedTo,
                'subject' => (string)$subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    } else {
        // Fallback zu mail() Funktion
        $headers = 'From: ' . $mailFromName . ' <' . $config['mail_from'] . ">\r\n";
        
        if ($isHTML) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        $sent = mail($normalizedTo, $subject, $body, $headers);
        logSmtpEvent($sent ? 'sent' : 'error', [
            'transport' => 'mail_fallback',
            'to' => $normalizedTo,
            'subject' => (string)$subject
        ]);
        return $sent;
    }
}
// Benachrichtigung an alle Benutzer senden
function notifyUsers($date, $name, $type, $message = '') {
    try {
        global $config;
        $appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
        
        $pdo = connectDB();
        $stmt = $pdo->prepare('SELECT email, name FROM users WHERE id != ?');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $users = $stmt->fetchAll();
        
        $formattedDate = date('d.m.Y', strtotime($date));
        $subject = "Gipfeli-Ankündigung für $formattedDate";
        
        foreach ($users as $user) {
            $body = "Hallo {$user['name']},<br><br>";
            $body .= "{$_SESSION['user_name']} bringt am $formattedDate Gipfeli mit!<br>";
            $body .= "Sorte: $type<br><br>";
            
            if (!empty($message)) {
                $body .= "Nachricht: $message<br><br>";
            }
            
            $body .= "Liebe Grüsse,<br>Dein $appName";
            
            sendEmail($user['email'], $subject, $body);
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Audit-Log-Eintrag hinzufügen
function addAuditLog($action, $description = '', $data = null) {
    if (!isLoggedIn()) return false;
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('INSERT INTO audit_log (user_id, action, description, data, ip_address) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([
            $_SESSION['user_id'], 
            $action, 
            $description, 
            $data ? json_encode($data) : null,
            $_SERVER['REMOTE_ADDR']
        ]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Audit-Log abrufen
function getAuditLog($limit = 100, $offset = 0) {
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('
            SELECT a.*, u.name, u.email 
            FROM audit_log a 
            LEFT JOIN users u ON a.user_id = u.id 
            ORDER BY a.created_at DESC 
            LIMIT ? OFFSET ?
        ');
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

// "Like" für einen Gipfeli-Eintrag hinzufügen oder entfernen
function toggleLike($date, $entryId = null) {
    if (!isLoggedIn()) return false;
    
    try {
        $pdo = connectDB();
        
        // Wenn keine Entry-ID angegeben ist, prüfen ob es nur einen Eintrag für dieses Datum gibt
        if (!$entryId) {
            $stmt = $pdo->prepare('SELECT id FROM gipfeli_entries WHERE date = ?');
            $stmt->execute([$date]);
            $entries = $stmt->fetchAll();
            
            if (count($entries) === 1) {
                $entryId = $entries[0]['id'];
            } else {
                return ['error' => 'Für dieses Datum gibt es mehrere Einträge. Bitte geben Sie eine Entry-ID an.'];
            }
        }
        
        // Prüfen, ob der Eintrag existiert
        $stmt = $pdo->prepare('SELECT id FROM gipfeli_entries WHERE id = ?');
        $stmt->execute([$entryId]);
        $entry = $stmt->fetch();
        
        if (!$entry) {
            return ['error' => 'Eintrag nicht gefunden'];
        }
        
        // Prüfen, ob der Benutzer den Eintrag bereits geliked hat
        $stmt = $pdo->prepare('SELECT id FROM gipfeli_likes WHERE user_id = ? AND entry_id = ?');
        $stmt->execute([$_SESSION['user_id'], $entryId]);
        $like = $stmt->fetch();
        
        if ($like) {
            // Like entfernen
            $stmt = $pdo->prepare('DELETE FROM gipfeli_likes WHERE id = ?');
            $stmt->execute([$like['id']]);
            
            // Audit-Log
            addAuditLog('unlike', "Like für Gipfeli-Eintrag $entryId entfernt");
            
            return ['success' => true, 'liked' => false];
        } else {
            // Like hinzufügen
            $stmt = $pdo->prepare('INSERT INTO gipfeli_likes (user_id, entry_id, entry_date) VALUES (?, ?, ?)');
            $stmt->execute([$_SESSION['user_id'], $entryId, $date]);
            
            // Audit-Log
            addAuditLog('like', "Gipfeli-Eintrag $entryId geliked");
            
            return ['success' => true, 'liked' => true];
        }
    } catch (PDOException $e) {
        return ['error' => 'Interner Datenbankfehler'];
    }
}
// Statistik über Gipfeli-Einträge abrufen
function getGipfeliStats() {
    try {
        $pdo = connectDB();
        
        // Anzahl der Gipfeli-Einträge pro Benutzer
        $stmt = $pdo->query('
            SELECT g.name, COUNT(*) as count, 
                   (SELECT COUNT(*) FROM gipfeli_likes WHERE entry_id IN (
                       SELECT id FROM gipfeli_entries WHERE name = g.name
                   )) as likes
            FROM gipfeli_entries g
            GROUP BY g.name
            ORDER BY count DESC
        ');
        $userStats = $stmt->fetchAll();
        
        // Beliebteste Gipfeli-Sorte
        $stmt = $pdo->query('
            SELECT type, COUNT(*) as count
            FROM gipfeli_entries
            WHERE type != ""
            GROUP BY type
            ORDER BY count DESC
        ');
        $typeStats = $stmt->fetchAll();
        
        // Beliebteste Wochentage
        $stmt = $pdo->query('
            SELECT DAYOFWEEK(date) as day, COUNT(*) as count
            FROM gipfeli_entries
            GROUP BY day
            ORDER BY count DESC
        ');
        $dayStats = $stmt->fetchAll();
        
        // Tage in Woche konvertieren
        $dayNames = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
        foreach ($dayStats as &$day) {
            $day['name'] = $dayNames[$day['day'] - 1];
        }
        
        return [
            'users' => $userStats,
            'types' => $typeStats,
            'days' => $dayStats
        ];
    } catch (PDOException $e) {
        return ['error' => 'Interner Datenbankfehler'];
    }
}

// Alle Benutzer abrufen (nur für Admins)
function getAllUsers() {
    try {
        $pdo = connectDB();
        $stmt = $pdo->query('SELECT id, name, email, role, is_active, last_active_at, created_at FROM users ORDER BY name');
        return ['users' => $stmt->fetchAll()];
    } catch (PDOException $e) {
        return ['error' => 'Interner Datenbankfehler'];
    }
}

function notifyUsersEntryDeleted($date, array $deletedEntries, $actorName) {
    try {
        global $config;
        $appName = $config['app_name'] ?? 'Gipfeli-Koordinator';

        $pdo = connectDB();
        $stmt = $pdo->prepare('SELECT email, name FROM users WHERE id != ?');
        $stmt->execute([(int)$_SESSION['user_id']]);
        $users = $stmt->fetchAll();

        if (empty($users)) {
            return true;
        }

        $formattedDate = date('d.m.Y', strtotime($date));
        $subject = "Änderung Gipfeli-Ankündigung für $formattedDate";

        $typeParts = [];
        foreach ($deletedEntries as $entry) {
            $typeValue = trim((string)($entry['type'] ?? ''));
            if ($typeValue !== '') {
                $typeParts[] = $typeValue;
            }
        }
        $typeParts = array_values(array_unique($typeParts));
        $typeLine = empty($typeParts) ? '' : ('Betroffene Sorten: ' . implode(', ', $typeParts) . '<br>');

        foreach ($users as $user) {
            $body = "Hallo {$user['name']},<br><br>";
            $body .= htmlspecialchars((string)$actorName, ENT_QUOTES, 'UTF-8') . " hat einen Gipfeli-Eintrag für den $formattedDate gelöscht.<br>";
            $body .= $typeLine;
            $body .= "<br>Liebe Grüsse,<br>Dein $appName";
            sendEmail($user['email'], $subject, $body);
        }

        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Benutzer aktualisieren (nur für Admins)
function updateUser($data) {
    if (!isset($data['id'])) {
        return ['error' => 'Benutzer-ID fehlt'];
    }
    
    try {
        $pdo = connectDB();
        $userId = (int)$data['id'];
        if ($userId <= 0) {
            return ['error' => 'Ungültige Benutzer-ID'];
        }

        $stmt = $pdo->prepare('SELECT id, name, email, role, is_active FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $targetUser = $stmt->fetch();
        if (!$targetUser) {
            return ['error' => 'Benutzer nicht gefunden'];
        }

        if ($targetUser['role'] === 'super_admin' && !isSuperAdmin()) {
            return ['error' => 'Super-Admin kann nur vom Super-Admin bearbeitet werden'];
        }
        
        $updates = [];
        $params = [];
        
        // Name aktualisieren
        if (isset($data['name'])) {
            $name = trim((string)$data['name']);
            if ($name === '' || strlen($name) > 255) {
                return ['error' => 'Ungültiger Name'];
            }
            $updates[] = 'name = ?';
            $params[] = $name;
        }

        // E-Mail aktualisieren
        if (isset($data['email'])) {
            $email = normalizeEmailAddress($data['email']);
            if ($email === null) {
                return ['error' => 'Ungültige E-Mail-Adresse'];
            }
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                return ['error' => 'Diese E-Mail ist bereits vergeben'];
            }
            $updates[] = 'email = ?';
            $params[] = $email;
        }
        
        // Rolle aktualisieren
        if (isset($data['role'])) {
            $role = (string)$data['role'];
            if (!in_array($role, getAllowedRoleValues(), true)) {
                return ['error' => 'Ungültige Rolle'];
            }
            if ($role === 'super_admin' && !isSuperAdmin()) {
                return ['error' => 'Nur Super-Admin darf Super-Admin vergeben'];
            }
            if ($targetUser['role'] === 'super_admin' && $role !== 'super_admin') {
                $countStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'");
                $superAdminCount = (int)$countStmt->fetchColumn();
                if ($superAdminCount <= 1) {
                    return ['error' => 'Der letzte Super-Admin kann nicht herabgestuft werden'];
                }
            }
            $updates[] = 'role = ?';
            $params[] = $role;
        }

        // Aktiv-Status aktualisieren
        if (array_key_exists('is_active', $data)) {
            $isActive = ((int)$data['is_active'] === 1) ? 1 : 0;
            if ($userId === (int)$_SESSION['user_id'] && $isActive === 0) {
                return ['error' => 'Du kannst deinen eigenen Benutzer nicht deaktivieren'];
            }
            if ($targetUser['role'] === 'super_admin' && $isActive === 0) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin' AND is_active = 1");
                $activeSuperAdmins = (int)$stmt->fetchColumn();
                if ($activeSuperAdmins <= 1) {
                    return ['error' => 'Der letzte aktive Super-Admin kann nicht deaktiviert werden'];
                }
            }
            if (in_array($targetUser['role'], ['admin', 'super_admin'], true) && $isActive === 0) {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('admin','super_admin') AND is_active = 1");
                $activePrivileged = (int)$stmt->fetchColumn();
                if ($activePrivileged <= 1) {
                    return ['error' => 'Der letzte aktive Administrator kann nicht deaktiviert werden'];
                }
            }
            $updates[] = 'is_active = ?';
            $params[] = $isActive;
        }
        
        // Passwort aktualisieren
        if (isset($data['password']) && !empty($data['password'])) {
            $passwordPolicyError = validatePasswordPolicy($data['password']);
            if ($passwordPolicyError !== null) {
                return ['error' => $passwordPolicyError];
            }
            $updates[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        if (empty($updates)) {
            return ['error' => 'Keine Änderungen angegeben'];
        }
        
        $params[] = $userId;
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Benutzerdetails für Audit-Log abrufen
        $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        // Audit-Log
        addAuditLog('update_user', "Benutzer aktualisiert: {$user['name']} ({$user['email']})", [
            'updated_fields' => array_keys($data)
        ]);
        
        return ['success' => true, 'message' => 'Benutzer erfolgreich aktualisiert'];
    } catch (PDOException $e) {
        return ['error' => 'Interner Datenbankfehler'];
    }
}

// Benutzer löschen (nur für Admins)
function deleteUser($userId) {
    // Verhindern, dass der eigene Account gelöscht wird
    if ($userId == $_SESSION['user_id']) {
        return ['error' => 'Du kannst deinen eigenen Account nicht löschen'];
    }
    
    try {
        $pdo = connectDB();
        
        // Benutzerdetails für Audit-Log abrufen
        $stmt = $pdo->prepare('SELECT name, email, role FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['error' => 'Benutzer nicht gefunden'];
        }
        
        if ($user['role'] === 'super_admin' && !isSuperAdmin()) {
            return ['error' => 'Super-Admin kann nur vom Super-Admin gelöscht werden'];
        }

        if (in_array($user['role'], ['admin', 'super_admin'], true)) {
            // Prüfen, ob es noch andere privilegierte Benutzer gibt
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role IN ('admin', 'super_admin')");
            $result = $stmt->fetch();
            if ((int)$result['count'] <= 1) {
                return ['error' => 'Der letzte Administrator kann nicht gelöscht werden'];
            }
        }

        if ($user['role'] === 'super_admin') {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'super_admin'");
            $result = $stmt->fetch();
            if ((int)$result['count'] <= 1) {
                return ['error' => 'Der letzte Super-Admin kann nicht gelöscht werden'];
            }
        }
        
        // Benutzer löschen
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        
        // Audit-Log
        addAuditLog('delete_user', "Benutzer gelöscht: {$user['name']} ({$user['email']})", [
            'user_role' => $user['role']
        ]);
        
        return ['success' => true, 'message' => 'Benutzer erfolgreich gelöscht'];
    } catch (PDOException $e) {
        return ['error' => 'Interner Datenbankfehler'];
    }
}
// Benutzer registrieren
function registerUser($name, $email, $password) {
    $name = trim((string)$name);
    $email = normalizeEmailAddress($email);

    if ($name === '' || strlen($name) > 255) {
        return ['error' => 'Ungültiger Name'];
    }

    if ($email === null) {
        return ['error' => 'Ungültige E-Mail-Adresse'];
    }

    $passwordPolicyError = validatePasswordPolicy($password);
    if ($passwordPolicyError !== null) {
        return ['error' => $passwordPolicyError];
    }

    try {
        $pdo = connectDB();
        
        // Prüfen, ob die E-Mail bereits existiert
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['error' => 'Diese E-Mail ist bereits registriert'];
        }
        
        // Benutzer erstellen
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([$name, $email, $hashedPassword, $GLOBALS['defaultRole']]);
        
        // Audit-Log (ohne Benutzer-ID, da noch nicht angemeldet)
        $userId = $pdo->lastInsertId();
        $stmt = $pdo->prepare('INSERT INTO audit_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, 'register', "Benutzer hat sich registriert", $_SERVER['REMOTE_ADDR']]);
        
        // Diese Zeile hinzufügen, um die Willkommens-E-Mail zu senden
        sendWelcomeEmail($name, $email);
        
        return ['success' => true, 'message' => 'Registrierung erfolgreich'];
    } catch (PDOException $e) {
        return ['error' => 'Interner Datenbankfehler'];
    }
}

// Willkommens-E-Mail nach Registrierung senden
function sendWelcomeEmail($name, $email) {
    global $config;
    $appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
    
    $subject = "Willkommen bei $appName";
    
    $body = "Hallo $name,<br><br>";
    $body .= "herzlichen Dank für deine Registrierung bei $appName!<br><br>";
    $body .= "Dein Konto wurde erfolgreich erstellt. Du kannst dich jetzt mit deiner E-Mail-Adresse und deinem Passwort anmelden.<br><br>";
    $body .= "Hier sind deine Anmeldedaten:<br>";
    $body .= "E-Mail: $email<br><br>";
    $body .= "Liebe Grüsse,<br>Dein $appName-Team";
    
    return sendEmail($email, $subject, $body);
}

function createPasswordResetTokenForUser(PDO $pdo, $userId, $validHours = 1) {
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', strtotime('+' . max(1, (int)$validHours) . ' hour'));

    $stmt = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
    $stmt->execute([(int)$userId]);

    $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([(int)$userId, $tokenHash, $expires]);

    return $token;
}

function sendUserInviteEmail($name, $email, $token) {
    global $config;
    $appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
    $resetLink = getAppBaseUrl() . '/?page=confirm-reset&token=' . rawurlencode($token);

    $subject = "Einladung zu $appName";
    $safeName = htmlspecialchars((string)$name, ENT_QUOTES, 'UTF-8');
    $body = "Hallo $safeName,<br><br>";
    $body .= "für dich wurde ein Benutzerkonto bei $appName erstellt.<br>";
    $body .= "Bitte klicke auf den folgenden Link und setze dein Passwort:<br><br>";
    $body .= "<a href=\"$resetLink\">$resetLink</a><br><br>";
    $body .= "Der Link ist 48 Stunden gültig.<br><br>";
    $body .= "Liebe Grüsse,<br>Dein $appName";

    return sendEmail($email, $subject, $body);
}

function createUserByAdmin($name, $email, $role, $password = '', $notifyUser = true, $isActive = 1) {
    $name = trim((string)$name);
    $email = normalizeEmailAddress($email);
    $role = trim((string)$role);
    $password = (string)$password;
    $notifyUser = (bool)$notifyUser;
    $isActive = ((int)$isActive === 1) ? 1 : 0;

    if (!isAdmin()) {
        return ['error' => 'Zugriff verweigert'];
    }
    if ($name === '' || strlen($name) > 255) {
        return ['error' => 'Ungültiger Name'];
    }
    if ($email === null) {
        return ['error' => 'Ungültige E-Mail-Adresse'];
    }
    if (!isAllowedEmailDomain($email)) {
        return ['error' => 'Diese E-Mail-Domain ist nicht erlaubt.'];
    }
    if (!in_array($role, getAllowedRoleValues(), true)) {
        return ['error' => 'Ungültige Rolle'];
    }
    if ($role === 'super_admin' && !isSuperAdmin()) {
        return ['error' => 'Nur Super-Admin darf Super-Admin erstellen'];
    }
    if ($password === '' && !$notifyUser) {
        return ['error' => 'Ohne Passwort muss "Benutzer informieren" aktiviert sein'];
    }
    if ($password !== '') {
        $passwordPolicyError = validatePasswordPolicy($password);
        if ($passwordPolicyError !== null) {
            return ['error' => $passwordPolicyError];
        }
    }

    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['error' => 'Diese E-Mail ist bereits registriert'];
        }

        $effectivePassword = $password !== '' ? $password : bin2hex(random_bytes(32));
        $hashedPassword = password_hash($effectivePassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, is_active, last_active_at) VALUES (?, ?, ?, ?, ?, NULL)');
        $stmt->execute([$name, $email, $hashedPassword, $role, $isActive]);
        $newUserId = (int)$pdo->lastInsertId();

        $mailSent = null;
        if ($notifyUser) {
            if ($password === '') {
                $inviteToken = createPasswordResetTokenForUser($pdo, $newUserId, 48);
                $mailSent = sendUserInviteEmail($name, $email, $inviteToken);
            } else {
                $mailSent = sendWelcomeEmail($name, $email);
            }
        }

        addAuditLog('create_user', "Benutzer erstellt: {$name} ({$email})", [
            'role' => $role,
            'is_active' => $isActive,
            'notify_user' => $notifyUser,
            'password_set_by_admin' => ($password !== ''),
            'mail_sent' => $mailSent
        ]);

        $message = $password === ''
            ? 'Benutzer erstellt. E-Mail zum Passwort setzen wurde vorbereitet.'
            : 'Benutzer erfolgreich erstellt.';
        if ($notifyUser && $mailSent === false) {
            $message .= ' Versand der E-Mail ist fehlgeschlagen (siehe SMTP-Log).';
        }

        return ['success' => true, 'message' => $message, 'mail_sent' => $mailSent];
    } catch (PDOException $e) {
        return ['error' => 'Interner Datenbankfehler'];
    }
}

// Passwort ändern für eingeloggte Benutzer
function changeUserPassword($currentPassword, $newPassword) {
    if (!isLoggedIn()) {
        return ['error' => 'Nicht angemeldet'];
    }
    
    try {
        $pdo = connectDB();
        
        // Aktuellen Benutzer abfragen
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['error' => 'Benutzer nicht gefunden'];
        }
        
        // Aktuelles Passwort überprüfen
        if (!password_verify($currentPassword, $user['password'])) {
            return ['error' => 'Das aktuelle Passwort ist nicht korrekt'];
        }

        $passwordPolicyError = validatePasswordPolicy($newPassword);
        if ($passwordPolicyError !== null) {
            return ['error' => $passwordPolicyError];
        }
        
        // Neues Passwort setzen
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
        
        // Audit-Log
        addAuditLog('password_change', "Benutzer hat sein Passwort geändert");
        
        return ['success' => true, 'message' => 'Passwort erfolgreich geändert'];
    } catch (PDOException $e) {
        error_log('Fehler beim Ändern des Passworts: ' . $e->getMessage());
        return ['error' => 'Interner Datenbankfehler'];
    }
}

// Passwort zurücksetzen (Token erstellen und E-Mail senden)
function resetPassword($email) {
    try {
        global $config;
        $appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
        $email = normalizeEmailAddress($email);
        if ($email === null) {
            return ['error' => 'Ungültige E-Mail-Adresse'];
        }
        
        $pdo = connectDB();
        
        // Prüfen, ob die E-Mail existiert
        $stmt = $pdo->prepare('SELECT id, name FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Aus Sicherheitsgründen geben wir dieselbe Erfolgsmeldung zurück
            return ['success' => true, 'message' => 'Falls die E-Mail registriert ist, wurde ein Link zum Zurücksetzen des Passworts gesendet'];
        }
        
        // Token generieren (nur Hash in DB speichern)
        $token = createPasswordResetTokenForUser($pdo, (int)$user['id'], 1);
        
        // E-Mail mit Reset-Link senden
        $resetLink = getAppBaseUrl() . '/?page=confirm-reset&token=' . rawurlencode($token);
        $subject = "Passwort zurücksetzen - " . $appName;
        $userName = htmlspecialchars($user['name']);
        
        $body = "Hallo $userName,<br><br>";
        $body .= "Du hast eine Anfrage zum Zurücksetzen deines Passworts für $appName gestellt.<br><br>";
        $body .= "Klicke auf den folgenden Link, um dein Passwort zurückzusetzen:<br><br>";
        $body .= "<a href=\"$resetLink\">$resetLink</a><br><br>";
        $body .= "Dieser Link ist für eine Stunde gültig.<br><br>";
        $body .= "Falls du keine Anfrage zum Zurücksetzen des Passworts gestellt hast, kannst du diese E-Mail ignorieren.<br><br>";
        $body .= "Liebe Grüsse,<br>Dein $appName";
        
        $mailSent = sendEmail($email, $subject, $body);
        
        // Audit-Log
        $stmt = $pdo->prepare('INSERT INTO audit_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)');
        $stmt->execute([$user['id'], 'password_reset_request', "Passwort-Reset angefordert", $_SERVER['REMOTE_ADDR']]);
        
        return [
            'success' => true, 
            'message' => 'Falls die E-Mail registriert ist, wurde ein Link zum Zurücksetzen des Passworts gesendet'
        ];
    } catch (PDOException $e) {
        error_log('Passwort-Reset-Fehler: ' . $e->getMessage());
        return ['error' => 'Interner Datenbankfehler'];
    } catch (Exception $e) {
        error_log('Passwort-Reset-Fehler: ' . $e->getMessage());
        return ['error' => 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.'];
    }
}

// Passwort zurücksetzen bestätigen
function confirmResetPassword($token, $password) {
    try {
        global $config;
        $appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
        
        $pdo = connectDB();

        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/i', $token)) {
            return ['error' => 'Ungültiger oder abgelaufener Token. Bitte fordere einen neuen Link an.'];
        }

        $passwordPolicyError = validatePasswordPolicy($password);
        if ($passwordPolicyError !== null) {
            return ['error' => $passwordPolicyError];
        }

        $tokenHash = hash('sha256', $token);
        
        // Token überprüfen (kompatibel für alte un-gehashte Tokens)
        $stmt = $pdo->prepare('
            SELECT pr.user_id, pr.expires_at, u.name, u.email
            FROM password_resets pr
            JOIN users u ON pr.user_id = u.id
            WHERE pr.token IN (?, ?)
        ');
        $stmt->execute([$tokenHash, $token]);
        $reset = $stmt->fetch();
        
        if (!$reset) {
            return ['error' => 'Ungültiger oder abgelaufener Token. Bitte fordere einen neuen Link an.'];
        }
        
        // Prüfen, ob der Token abgelaufen ist
        if (strtotime($reset['expires_at']) < time()) {
            return ['error' => 'Der Link ist abgelaufen. Bitte fordere einen neuen Link an.'];
        }
        
        // Passwort aktualisieren
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
        $stmt->execute([$hashedPassword, $reset['user_id']]);
        
        // Token entfernen
        $stmt = $pdo->prepare('DELETE FROM password_resets WHERE token IN (?, ?)');
        $stmt->execute([$tokenHash, $token]);
        
        // Bestätigungs-E-Mail senden
        $subject = "Passwort-Änderung bestätigt - " . $appName;
        $body = "Hallo {$reset['name']},<br><br>";
        $body .= "Dein Passwort für $appName wurde erfolgreich geändert.<br><br>";
        $body .= "Falls du diese Änderung nicht selbst vorgenommen hast, kontaktiere bitte umgehend deinen Administrator.<br><br>";
        $body .= "Liebe Grüsse,<br>Dein $appName";
        
        sendEmail($reset['email'], $subject, $body);
        
        // Audit-Log
        $stmt = $pdo->prepare('INSERT INTO audit_log (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)');
        $stmt->execute([$reset['user_id'], 'password_reset_complete', "Passwort wurde zurückgesetzt", $_SERVER['REMOTE_ADDR']]);
        
        return ['success' => true, 'message' => 'Dein Passwort wurde erfolgreich zurückgesetzt. Du kannst dich jetzt mit deinem neuen Passwort anmelden.'];
    } catch (PDOException $e) {
        error_log('Passwort-Reset-Bestätigungs-Fehler: ' . $e->getMessage());
        return ['error' => 'Interner Datenbankfehler'];
    } catch (Exception $e) {
        error_log('Passwort-Reset-Bestätigungs-Fehler: ' . $e->getMessage());
        return ['error' => 'Ein Fehler ist aufgetreten. Bitte versuche es später erneut.'];
    }
}

// Hilfsfunktion zum Überprüfen der Datenstruktur von Einträgen
function debugEntries($entries) {
    foreach ($entries as $date => $entry) {
        if (is_array($entry)) {
            debugLog("Date: $date has " . count($entry) . " entries");
            // Überprüfen, ob es ein numerisches Array oder ein assoziatives Array ist
            if (isset($entry[0])) {
                debugLog("  Entry structure: Numeric array");
            } else {
                debugLog("  Entry structure: Associative array - PROBLEM!");
            }
        } else {
            debugLog("Date: $date has 1 single entry (not in array)");
        }
    }
    return $entries;
}

// Modifizierte getAllEntries-Funktion für Mehrfacheinträge
function getAllEntries() {
    try {
        $pdo = connectDB();
        
        // Alle Einträge abrufen, nach Datum und Zeitstempel sortiert
        $stmt = $pdo->query('SELECT id, date, name, type, timestamp FROM gipfeli_entries ORDER BY date ASC, timestamp ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        debugLog("Total entries found: " . count($rows));
        
        // Format in ein Objekt umwandeln, wie es das Frontend erwartet
        $entries = [];
        
        foreach ($rows as $row) {
            $dateStr = $row['date'];
            $entryId = $row['id'];
            
            // Likes für diesen Eintrag abrufen
            $stmt = $pdo->prepare('
                SELECT l.user_id, u.name as user_name 
                FROM gipfeli_likes l
                JOIN users u ON l.user_id = u.id
                WHERE l.entry_id = ?
            ');
            $stmt->execute([$entryId]);
            $likes = $stmt->fetchAll();
            
            // Prüfen, ob der aktuelle Benutzer den Eintrag geliked hat
            $userLiked = false;
            if (isLoggedIn()) {
                foreach ($likes as $like) {
                    if ($like['user_id'] == $_SESSION['user_id']) {
                        $userLiked = true;
                        break;
                    }
                }
            }
            
            $entry = [
                'id' => $entryId,
                'name' => $row['name'],
                'type' => $row['type'] ?? '',
                'timestamp' => $row['timestamp'],
                'likes' => $likes,
                'like_count' => count($likes),
                'user_liked' => $userLiked
            ];
            
            // Wenn bereits ein Eintrag für dieses Datum existiert, in ein Array umwandeln
            if (isset($entries[$dateStr])) {
                if (!is_array($entries[$dateStr]) || !isset($entries[$dateStr][0])) {
                    // Wenn es nur einen Eintrag gibt und er noch nicht in einem Array ist,
                    // konvertieren wir ihn in ein Array
                    $entries[$dateStr] = [$entries[$dateStr]];
                }
                // Neuen Eintrag hinzufügen
                $entries[$dateStr][] = $entry;
            } else {
                // Ersten Eintrag für dieses Datum hinzufügen
                $entries[$dateStr] = $entry;
            }
        }
        
        // Zum Debuggen die finale Struktur protokollieren
        debugLog("Final entries structure: " . substr(json_encode($entries), 0, 1000) . "...");
        
        // Debug-Funktion aufrufen, um die Struktur zu überprüfen
        return isDebugModeEnabled() ? debugEntries($entries) : $entries;
    } catch (PDOException $e) {
        error_log("Error in getAllEntries: " . $e->getMessage());
        return ['error' => 'Interner Datenbankfehler'];
    }
}

// Eintrag speichern (für Mehrfacheinträge)
function saveEntry($data) {
    if (!isset($data['date'])) {
        http_response_code(400);
        return ['error' => 'Datum ist erforderlich'];
    }
    
    $date = $data['date'];
    $name = $_SESSION['user_name'];
    $userId = (int)$_SESSION['user_id'];
    $gipfeliType = $data['gipfeliType'] ?? '';
    $sendNotification = isset($data['notify']) ? (bool)$data['notify'] : false;
    $notificationMessage = $data['message'] ?? '';

    $dateObject = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObject || $dateObject->format('Y-m-d') !== $date) {
        http_response_code(400);
        return ['error' => 'Ungültiges Datum'];
    }
    
    try {
        $pdo = connectDB();
        
        // Überprüfen, ob der Benutzer bereits einen Eintrag für diesen Tag hat
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM gipfeli_entries WHERE date = ? AND user_id = ?');
        $stmt->execute([$date, $userId]);
        $result = $stmt->fetch();
        
        debugLog("Saving entry: Date=$date, Name=$name, Existing entries={$result['count']}");
        
        // Generiere eine eindeutige ID für den Eintrag
        $entryId = uniqid('entry_');
        
        // Neuen Eintrag erstellen
        $stmt = $pdo->prepare('INSERT INTO gipfeli_entries (id, date, user_id, name, type, notified_all, timestamp) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $success = $stmt->execute([$entryId, $date, $userId, $name, $gipfeliType, 0]);
        
        if (!$success) {
            error_log("Database error when inserting: " . implode(", ", $stmt->errorInfo()));
            throw new PDOException("Insert failed: " . implode(", ", $stmt->errorInfo()));
        }
        
        // Audit-Log
        addAuditLog('create_entry', "Neuer Gipfeli-Eintrag für $date", [
            'type' => $gipfeliType,
            'entry_id' => $entryId
        ]);
        
        // Benachrichtigung senden, falls gewünscht
        if ($sendNotification) {
            $notificationSent = notifyUsers($date, $name, $gipfeliType, $notificationMessage);
            if ($notificationSent) {
                $markStmt = $pdo->prepare('UPDATE gipfeli_entries SET notified_all = 1 WHERE id = ?');
                $markStmt->execute([$entryId]);
            }
            
            // Audit-Log für Benachrichtigung
            addAuditLog('send_notification', "Benachrichtigung für Gipfeli am $date gesendet", [
                'message' => $notificationMessage
            ]);
        }
        
        // Erstelle eine neue Eintragsobjekt mit Like-Informationen für die Antwort
        $entry = [
            'id' => $entryId,
            'name' => $name,
            'type' => $gipfeliType,
            'timestamp' => date('Y-m-d H:i:s'),
            'likes' => [],
            'like_count' => 0,
            'user_liked' => false
        ];
        
        return [
            'success' => true,
            'entry' => $entry
        ];
    } catch (PDOException $e) {
        error_log('Eintrag speichern: ' . $e->getMessage());
        http_response_code(500);
        return ['error' => 'Interner Datenbankfehler'];
    }
}

// Eintrag löschen für Mehrfacheinträge
function deleteEntry($date, $entryId = null) {
    try {
        $pdo = connectDB();
        
        if ($entryId) {
            // Einen bestimmten Eintrag löschen
            $stmt = $pdo->prepare('SELECT id, date, user_id, name, type, notified_all FROM gipfeli_entries WHERE id = ?');
            $stmt->execute([$entryId]);
            $entry = $stmt->fetch();
            
            if (!$entry) {
                http_response_code(404);
                return ['error' => 'Eintrag nicht gefunden'];
            }

            $isOwner = ((int)$entry['user_id'] === (int)$_SESSION['user_id'])
                || ($entry['user_id'] === null && $entry['name'] === $_SESSION['user_name']);
            if (!isAdmin() && !$isOwner) {
                http_response_code(403);
                return ['error' => 'Nicht berechtigt'];
            }
            
            $deletedEntries = [$entry];

            // Alle Likes für diesen Eintrag löschen
            $stmt = $pdo->prepare('DELETE FROM gipfeli_likes WHERE entry_id = ?');
            $stmt->execute([$entryId]);
            
            // Eintrag löschen
            $stmt = $pdo->prepare('DELETE FROM gipfeli_entries WHERE id = ?');
            $stmt->execute([$entryId]);
        } else {
            // Alle eigenen Einträge für das Datum löschen (Admins dürfen alle)
            if (isAdmin()) {
                $stmt = $pdo->prepare('SELECT id, date, name, type, notified_all FROM gipfeli_entries WHERE date = ?');
                $stmt->execute([$date]);
            } else {
                $stmt = $pdo->prepare('
                    SELECT id, date, name, type, notified_all FROM gipfeli_entries
                    WHERE date = ?
                    AND (user_id = ? OR (user_id IS NULL AND name = ?))
                ');
                $stmt->execute([$date, (int)$_SESSION['user_id'], $_SESSION['user_name']]);
            }
            $deletedEntries = $stmt->fetchAll();

            if (empty($deletedEntries)) {
                http_response_code(404);
                return ['error' => 'Keine löschbaren Einträge gefunden'];
            }
            
            foreach ($deletedEntries as $entry) {
                // Alle Likes für diesen Eintrag löschen
                $stmt = $pdo->prepare('DELETE FROM gipfeli_likes WHERE entry_id = ?');
                $stmt->execute([$entry['id']]);
            }
            
            if (isAdmin()) {
                $stmt = $pdo->prepare('DELETE FROM gipfeli_entries WHERE date = ?');
                $stmt->execute([$date]);
            } else {
                $stmt = $pdo->prepare('
                    DELETE FROM gipfeli_entries
                    WHERE date = ?
                    AND (user_id = ? OR (user_id IS NULL AND name = ?))
                ');
                $stmt->execute([$date, (int)$_SESSION['user_id'], $_SESSION['user_name']]);
            }
        }
        
        $hadNotification = false;
        foreach ($deletedEntries as $deletedEntry) {
            if ((int)($deletedEntry['notified_all'] ?? 0) === 1) {
                $hadNotification = true;
                break;
            }
        }

        if ($hadNotification) {
            notifyUsersEntryDeleted($date, $deletedEntries, (string)($_SESSION['user_name'] ?? 'Ein Benutzer'));
            addAuditLog('send_notification', "Lösch-Benachrichtigung für Gipfeli am $date gesendet", [
                'entry_id' => $entryId
            ]);
        }

        // Audit-Log
        addAuditLog('delete_entry', "Gipfeli-Eintrag gelöscht für $date" . ($entryId ? " (ID: $entryId)" : ""));
        
        return ['success' => true];
    } catch (PDOException $e) {
        http_response_code(500);
        return ['error' => 'Interner Datenbankfehler'];
    }
}
// Funktion zum Behandeln von API-Anfragen
function handleApiRequest() {
    $method = $_SERVER['REQUEST_METHOD'];
    $endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';

    if ($method === 'POST' && in_array($endpoint, ['login', 'register'], true)) {
        $authPostPayload = getJsonInput();
        if (!is_array($authPostPayload)) {
            $authPostPayload = [];
        }
        monitorAuthPostRequest($endpoint, $authPostPayload);
    }

    // CSRF-Schutz für schreibende Zugriffe (POST, DELETE)
    // Ausnahme: Login und Register (da hier oft noch kein Token vorhanden oder gewollt ist)
    if (in_array($method, ['POST', 'DELETE']) && !in_array($endpoint, ['login', 'register', 'reset-password', 'confirm-reset'])) {
        $headers = getRequestHeadersSafe();
        $normalizedHeaders = [];
        foreach ($headers as $headerName => $headerValue) {
            $normalizedHeaders[strtolower((string)$headerName)] = $headerValue;
        }
        $token = $normalizedHeaders['x-csrf-token'] ?? $_POST['csrf_token'] ?? null;

        // Versuchen, den Token aus dem JSON-Body zu lesen, falls vorhanden
        if (!$token && $method === 'POST') {
            $jsonData = getJsonInput();
            if (isset($jsonData['csrf_token'])) {
                $token = $jsonData['csrf_token'];
            }
        }

        if (!$token || !validateCsrfToken($token)) {
            http_response_code(403);
            logSecurityEventSafe('http_403', 'invalid csrf token', 403, ['endpoint' => (string)$endpoint]);
            die(json_encode(['error' => 'Ungültiger CSRF-Token']));
        }
    }
    
    // API-Endpunkte
    switch ($endpoint) {
        case 'entries':
            if (!isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Nicht angemeldet']);
                break;
            }
            
            if ($method === 'GET') {
                // Alle Einträge abrufen
                echo json_encode(getAllEntries());
            } elseif ($method === 'POST') {
                // Neuen Eintrag hinzufügen oder aktualisieren
                $data = getJsonInput();
                echo json_encode(saveEntry($data));
            }
            break;
            
        case 'delete':
            if (!isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Nicht angemeldet']);
                break;
            }
            
            if ($method === 'POST') {
                // Eintrag löschen
                $data = getJsonInput();
                if (isset($data['date'])) {
                    $entryId = isset($data['entryId']) ? $data['entryId'] : null;
                    echo json_encode(deleteEntry($data['date'], $entryId));
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Datum fehlt']);
                }
            }
            break;
            
        case 'like':
            if (!isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Nicht angemeldet']);
                break;
            }
            
            if ($method === 'POST') {
                // Like hinzufügen/entfernen
                $data = getJsonInput();
                if (isset($data['date'])) {
                    $entryId = isset($data['entryId']) ? $data['entryId'] : null;
                    echo json_encode(toggleLike($data['date'], $entryId));
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Datum fehlt']);
                }
            }
            break;
            
        case 'notify':
            if (!isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Nicht angemeldet']);
                break;
            }
            
            if ($method === 'POST') {
                // Benachrichtigung senden
                $data = getJsonInput();
                if (isset($data['date']) && isset($data['name']) && isset($data['type'])) {
                    $message = isset($data['message']) ? $data['message'] : '';
                    if (notifyUsers($data['date'], $data['name'], $data['type'], $message)) {
                        // Audit-Log
                        addAuditLog('notify', "Benachrichtigung für Gipfeli am {$data['date']} gesendet", [
                            'message' => $message
                        ]);
                        
                        echo json_encode(['success' => true]);
                    } else {
                        http_response_code(500);
                        echo json_encode(['error' => 'Fehler beim Senden der Benachrichtigungen']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Unvollständige Daten']);
                }
            }
            break;
            
        case 'stats':
            if (!isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Nicht angemeldet']);
                break;
            }
            
            if ($method === 'GET') {
                // Statistiken abrufen
                echo json_encode(getGipfeliStats());
            }
            break;
            
        case 'login':
            if ($method === 'POST') {
                $data = getJsonInput();
                $normalizedEmail = normalizeEmailAddress($data['email'] ?? '');
                $securityUsername = $normalizedEmail ?? strtolower(trim((string)($data['email'] ?? '')));
                $rateLimitIdentifier = $normalizedEmail ?? strtolower(trim((string)($data['email'] ?? '')));
                if (!consumeRateLimit('login', 10, 900, $rateLimitIdentifier)) {
                    http_response_code(429);
                    logSecurityEventSafe('auth_fail', 'login rate limit exceeded', 429, ['username' => $securityUsername]);
                    echo json_encode(['error' => 'Zu viele Anmeldeversuche. Bitte später erneut versuchen.']);
                    break;
                }
                if (isset($data['email']) && isset($data['password'])) {
                    if ($normalizedEmail === null || !is_string($data['password']) || $data['password'] === '') {
                        http_response_code(401);
                        logSecurityEventSafe('auth_fail', 'login failed', 401, ['username' => $securityUsername]);
                        echo json_encode(['error' => 'Ungültige Anmeldedaten']);
                        break;
                    }
                    $authResult = authenticateUser($normalizedEmail, $data['password']);
                    if (($authResult['success'] ?? false) === true) {
                        echo json_encode([
                            'success' => true,
                            'user' => [
                                'id' => $_SESSION['user_id'],
                                'email' => $_SESSION['user_email'],
                                'name' => $_SESSION['user_name'],
                                'role' => $_SESSION['user_role']
                            ]
                        ]);
                    } else {
                        if (($authResult['reason'] ?? '') === 'inactive') {
                            http_response_code(403);
                            logSecurityEventSafe('auth_fail', 'login failed inactive user', 403, ['username' => $securityUsername]);
                            echo json_encode(['error' => 'Dein Benutzerkonto ist deaktiviert. Bitte Admin kontaktieren.']);
                            break;
                        }
                        http_response_code(401);
                        logSecurityEventSafe('auth_fail', 'login failed', 401, ['username' => $securityUsername]);
                        echo json_encode(['error' => 'Ungültige Anmeldedaten']);
                    }
                } else {
                    http_response_code(400);
                    logSecurityEventSafe('auth_fail', 'login failed missing fields', 400, ['username' => $securityUsername]);
                    echo json_encode(['error' => 'E-Mail und Passwort sind erforderlich']);
                }
            }
            break;
            
       case 'register':
    if ($method === 'POST') {
        $data = getJsonInput();
        $normalizedEmail = normalizeEmailAddress($data['email'] ?? '');
        $securityUsername = $normalizedEmail ?? strtolower(trim((string)($data['email'] ?? '')));
        $rateLimitIdentifier = $normalizedEmail ?? strtolower(trim((string)($data['email'] ?? '')));
        if (!consumeRateLimit('register', 5, 3600, $rateLimitIdentifier)) {
            http_response_code(429);
            logSecurityEventSafe('register_fail', 'register rate limit exceeded', 429, ['username' => $securityUsername]);
            echo json_encode(['error' => 'Zu viele Registrierungsversuche. Bitte später erneut versuchen.']);
            break;
        }
        if (isset($data['email']) && isset($data['password']) && isset($data['name'])) {
            if ($normalizedEmail === null) {
                http_response_code(400);
                logSecurityEventSafe('register_fail', 'register failed invalid email', 400, ['username' => $securityUsername]);
                echo json_encode(['error' => 'Ungültige E-Mail-Adresse']);
                break;
            }
            $email = $normalizedEmail;

            if (!isAllowedEmailDomain($email)) {
                http_response_code(400);
                logSecurityEventSafe('register_fail', 'register failed blocked email domain', 400, ['username' => $securityUsername]);
                echo json_encode(['error' => 'Diese E-Mail-Domain ist nicht erlaubt. Erlaubte Domains: ' . implode(', ', $GLOBALS['config']['allowed_domains'])]);
                break;
            }
            
            // Registrieren
            $result = registerUser($data['name'], $email, $data['password']);
            if (isset($result['success']) && $result['success'] === true) {
                echo json_encode($result);
            } else {
                $registerStatus = 400;
                if (isset($result['error']) && is_string($result['error']) && strpos(strtolower($result['error']), 'bereits registriert') !== false) {
                    $registerStatus = 409;
                }
                http_response_code($registerStatus);
                logSecurityEventSafe('register_fail', 'register failed', $registerStatus, ['username' => $securityUsername]);
                echo json_encode($result);
            }
        } else {
            http_response_code(400);
            logSecurityEventSafe('register_fail', 'register failed missing fields', 400, ['username' => $securityUsername]);
            echo json_encode(['error' => 'Name, E-Mail und Passwort sind erforderlich']);
        }
    }
    break;

        case 'admin-create-user':
            if (!isLoggedIn() || !isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Zugriff verweigert']);
                break;
            }
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Methode nicht erlaubt']);
                break;
            }

            $data = getJsonInput();
            $name = $data['name'] ?? '';
            $email = $data['email'] ?? '';
            $role = $data['role'] ?? 'user';
            $password = (string)($data['password'] ?? '');
            $notifyUser = !isset($data['notify_user']) || (bool)$data['notify_user'];
            $isActive = array_key_exists('is_active', $data) && (int)$data['is_active'] === 0 ? 0 : 1;

            $result = createUserByAdmin($name, $email, $role, $password, $notifyUser, $isActive);
            if (isset($result['success']) && $result['success'] === true) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
            break;
            
        case 'logout':
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Methode nicht erlaubt']);
                break;
            }

            // Audit-Log: Logout
            if (isLoggedIn()) {
                addAuditLog('logout', "Benutzer hat sich abgemeldet");
            }
            
            // Session zerstören
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            echo json_encode(['success' => true]);
            break;
            
        case 'change-password':
            if (!isLoggedIn()) {
                http_response_code(401);
                echo json_encode(['error' => 'Nicht angemeldet']);
                break;
            }
            
            if ($method === 'POST') {
                $data = getJsonInput();
                if (isset($data['current_password']) && isset($data['new_password'])) {
                    $result = changeUserPassword($data['current_password'], $data['new_password']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Aktuelles und neues Passwort sind erforderlich']);
                }
            }
            break;
            
        case 'reset-password':
            if ($method === 'POST') {
                $data = getJsonInput();
                $normalizedEmail = normalizeEmailAddress($data['email'] ?? '');
                $rateLimitIdentifier = $normalizedEmail ?? strtolower(trim((string)($data['email'] ?? '')));
                if (!consumeRateLimit('reset_password', 5, 3600, $rateLimitIdentifier)) {
                    http_response_code(429);
                    echo json_encode(['error' => 'Zu viele Anfragen. Bitte später erneut versuchen.']);
                    break;
                }
                if (isset($data['email'])) {
                    if ($normalizedEmail === null) {
                        http_response_code(400);
                        echo json_encode(['error' => 'Ungültige E-Mail-Adresse']);
                        break;
                    }
                    $result = resetPassword($normalizedEmail);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'E-Mail ist erforderlich']);
                }
            }
            break;
            
        case 'confirm-reset':
            if ($method === 'POST') {
                $data = getJsonInput();
                $rateLimitIdentifier = strtolower(trim((string)($data['token'] ?? '')));
                if (!consumeRateLimit('confirm_reset', 10, 3600, $rateLimitIdentifier)) {
                    http_response_code(429);
                    echo json_encode(['error' => 'Zu viele Versuche. Bitte später erneut versuchen.']);
                    break;
                }
                if (isset($data['token']) && isset($data['password'])) {
                    $result = confirmResetPassword($data['token'], $data['password']);
                    echo json_encode($result);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Token und Passwort sind erforderlich']);
                }
            }
            break;
        case 'users':
            if (!isLoggedIn() || !isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Zugriff verweigert']);
                break;
            }
            
            if ($method === 'GET') {
                // Alle Benutzer abrufen
                echo json_encode(getAllUsers());
            } elseif ($method === 'POST') {
                // Benutzer bearbeiten
                $data = getJsonInput();
                if (isset($data['id'])) {
                    echo json_encode(updateUser($data));
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Benutzer-ID fehlt']);
                }
            } elseif ($method === 'DELETE') {
                // Benutzer löschen
                $data = getJsonInput();
                if (isset($data['id'])) {
                    echo json_encode(deleteUser($data['id']));
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Benutzer-ID fehlt']);
                }
            }
            break;
            
        case 'audit':
            if (!isLoggedIn() || !isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Zugriff verweigert']);
                break;
            }
            
            if ($method === 'GET') {
                // Audit-Log abrufen
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
                $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
                echo json_encode(['logs' => getAuditLog($limit, $offset)]);
            }
            break;

        case 'branding':
            if (!isLoggedIn() || !isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Zugriff verweigert']);
                break;
            }
            
            if ($method === 'GET') {
                // Branding-Einstellungen abrufen
                try {
                    $pdo = connectDB();
                    $stmt = $pdo->query("SELECT name, value FROM app_config WHERE category = 'branding'");
                    $brandingSettings = [];
                    while ($row = $stmt->fetch()) {
                        $brandingSettings[$row['name']] = $row['value'];
                    }
                    echo json_encode(['settings' => $brandingSettings]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Interner Datenbankfehler']);
                }
            } elseif ($method === 'POST') {
                // Branding-Einstellungen speichern
                $data = getJsonInput();
                try {
                    $pdo = connectDB();
                    
                    // Tabelle erstellen, falls sie nicht existiert
                    $pdo->exec("CREATE TABLE IF NOT EXISTS `app_config` (
                        `id` INT AUTO_INCREMENT PRIMARY KEY,
                        `category` VARCHAR(50) NOT NULL,
                        `name` VARCHAR(50) NOT NULL,
                        `value` TEXT,
                        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        UNIQUE KEY (`category`, `name`)
                    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    
                    // Vorhandene Einstellungen abrufen
                    $stmt = $pdo->query("SELECT name, value FROM app_config WHERE category = 'branding'");
                    $existingSettings = [];
                    while ($row = $stmt->fetch()) {
                        $existingSettings[$row['name']] = $row['value'];
                    }
                    
                    // Einstellungen speichern/aktualisieren
                    foreach ($data as $name => $value) {
                        if (isset($existingSettings[$name])) {
                            $stmt = $pdo->prepare("UPDATE app_config SET value = ? WHERE category = 'branding' AND name = ?");
                            $stmt->execute([$value, $name]);
                        } else {
                            $stmt = $pdo->prepare("INSERT INTO app_config (category, name, value) VALUES ('branding', ?, ?)");
                            $stmt->execute([$name, $value]);
                        }
                    }
                    
                    // Audit-Log
                    addAuditLog('update_branding', "Branding-Einstellungen aktualisiert");
                    
                    echo json_encode(['success' => true]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Interner Datenbankfehler']);
                }
            }
            break;

        case 'smtp-config':
            if (!isLoggedIn() || !isSuperAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Nur Super-Admin darf SMTP konfigurieren']);
                break;
            }

            if ($method === 'GET') {
                echo json_encode([
                    'settings' => [
                        'mail_host' => (string)($config['mail_host'] ?? ''),
                        'mail_port' => (int)($config['mail_port'] ?? 587),
                        'mail_user' => (string)($config['mail_user'] ?? ''),
                        'mail_from' => (string)($config['mail_from'] ?? ''),
                        'mail_name' => (string)($config['mail_name'] ?? ''),
                        'mail_pass' => '',
                        'mail_pass_configured' => !empty($config['mail_pass'])
                    ]
                ]);
            } elseif ($method === 'POST') {
                $data = getJsonInput();
                $mailHost = trim((string)($data['mail_host'] ?? ''));
                $mailPort = (int)($data['mail_port'] ?? 587);
                $mailUser = trim((string)($data['mail_user'] ?? ''));
                $mailFrom = normalizeEmailAddress($data['mail_from'] ?? '');
                $mailName = trim((string)($data['mail_name'] ?? ''));
                $mailPassInput = trim((string)($data['mail_pass'] ?? ''));

                if ($mailHost === '' || strlen($mailHost) > 255) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Ungültiger SMTP-Host']);
                    break;
                }
                if ($mailPort < 1 || $mailPort > 65535) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Ungültiger SMTP-Port']);
                    break;
                }
                if ($mailFrom === null) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Ungültige Absender-E-Mail']);
                    break;
                }
                if ($mailName === '' || strlen($mailName) > 255) {
                    http_response_code(400);
                    echo json_encode(['error' => 'Ungültiger Absendername']);
                    break;
                }

                try {
                    $pdo = connectDB();
                    $existing = getAppConfigCategory($pdo, 'smtp');
                    $effectivePass = $mailPassInput !== '' ? $mailPassInput : (string)($existing['mail_pass'] ?? ($config['mail_pass'] ?? ''));

                    $settings = [
                        'mail_host' => $mailHost,
                        'mail_port' => (string)$mailPort,
                        'mail_user' => $mailUser,
                        'mail_pass' => $effectivePass,
                        'mail_from' => $mailFrom,
                        'mail_name' => $mailName
                    ];
                    saveAppConfigCategory($pdo, 'smtp', $settings);

                    $GLOBALS['config']['mail_host'] = $mailHost;
                    $GLOBALS['config']['mail_port'] = $mailPort;
                    $GLOBALS['config']['mail_user'] = $mailUser;
                    $GLOBALS['config']['mail_pass'] = $effectivePass;
                    $GLOBALS['config']['mail_from'] = $mailFrom;
                    $GLOBALS['config']['mail_name'] = $mailName;

                    addAuditLog('update_smtp_config', 'SMTP-Konfiguration aktualisiert');
                    echo json_encode(['success' => true, 'message' => 'SMTP-Konfiguration gespeichert']);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Interner Datenbankfehler']);
                }
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Methode nicht erlaubt']);
            }
            break;

        case 'system-settings':
            if (!isLoggedIn() || !isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Zugriff verweigert']);
                break;
            }

            if ($method === 'GET') {
                echo json_encode([
                    'settings' => [
                        'debug_mode' => isDebugModeEnabled()
                    ]
                ]);
            } elseif ($method === 'POST') {
                if (!isSuperAdmin()) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Nur Super-Admin darf Systemeinstellungen ändern']);
                    break;
                }

                $data = getJsonInput();
                $debugMode = !empty($data['debug_mode']) ? 1 : 0;
                try {
                    $pdo = connectDB();
                    saveAppConfigCategory($pdo, 'system', [
                        'debug_mode' => (string)$debugMode
                    ]);
                    $GLOBALS['config']['debug_mode'] = ($debugMode === 1);
                    addAuditLog('update_system_settings', 'Systemeinstellungen aktualisiert', ['debug_mode' => $debugMode]);
                    echo json_encode(['success' => true, 'message' => 'Systemeinstellungen gespeichert']);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(['error' => 'Interner Datenbankfehler']);
                }
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Methode nicht erlaubt']);
            }
            break;

        case 'smtp-log':
            if (!isLoggedIn() || !isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Zugriff verweigert']);
                break;
            }
            if ($method !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Methode nicht erlaubt']);
                break;
            }

            $limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 200;
            echo json_encode([
                'entries' => getSmtpLogEntries($limit),
                'log_file' => APP_DATA_DIR . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'smtp.log'
            ]);
            break;

        case 'smtp-test':
            if (!isLoggedIn() || !isAdmin()) {
                http_response_code(403);
                echo json_encode(['error' => 'Zugriff verweigert']);
                break;
            }
            if ($method !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Methode nicht erlaubt']);
                break;
            }

            $data = getJsonInput();
            $recipient = normalizeEmailAddress($data['to'] ?? ($_SESSION['user_email'] ?? ''));
            if ($recipient === null) {
                http_response_code(400);
                echo json_encode(['error' => 'Ungültige Empfänger-E-Mail']);
                break;
            }

            $appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
            $subject = 'SMTP-Test - ' . $appName;
            $body = 'Dies ist eine SMTP-Testmail von ' . htmlspecialchars($appName, ENT_QUOTES, 'UTF-8')
                . '.<br>Zeitpunkt: ' . date('d.m.Y H:i:s');

            $sent = sendEmail($recipient, $subject, $body);
            if ($sent) {
                addAuditLog('smtp_test', "SMTP-Test erfolgreich an $recipient");
                echo json_encode([
                    'success' => true,
                    'message' => 'SMTP-Testmail wurde gesendet',
                    'log_file' => APP_DATA_DIR . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'smtp.log'
                ]);
            } else {
                http_response_code(500);
                addAuditLog('smtp_test_failed', "SMTP-Test fehlgeschlagen für $recipient");
                echo json_encode([
                    'error' => 'SMTP-Test fehlgeschlagen (siehe SMTP-Log)',
                    'log_file' => APP_DATA_DIR . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'smtp.log'
                ]);
            }
            break;
            
        default:
            http_response_code(404);
            logSecurityEventSafe('http_404', 'api endpoint not found', 404, ['endpoint' => (string)$endpoint]);
            echo json_encode(['error' => 'Endpunkt nicht gefunden']);
    }
    exit;
}
// Tabellen erstellen/prüfen bei Bedarf
function setupDatabase() {
    try {
        $pdo = connectDB();
        if (!$pdo instanceof PDO) {
            return false;
        }
        
        // Tabellen überprüfen und bei Bedarf erstellen
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('user', 'admin', 'super_admin') NOT NULL DEFAULT 'user',
            `is_active` TINYINT(1) NOT NULL DEFAULT 1,
            `last_active_at` DATETIME NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Rollen-Spalte um super_admin erweitern (Migration)
        $roleColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
        $roleInfo = $roleColumn ? $roleColumn->fetch() : null;
        if ($roleInfo && strpos((string)$roleInfo['Type'], 'super_admin') === false) {
            $pdo->exec("ALTER TABLE users MODIFY role ENUM('user', 'admin', 'super_admin') NOT NULL DEFAULT 'user'");
        }

        // Benutzerstatus-/Aktivitätsfelder für ältere Installationen ergänzen
        $isActiveColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
        if ($isActiveColumn && $isActiveColumn->rowCount() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role");
        }

        $lastActiveColumn = $pdo->query("SHOW COLUMNS FROM users LIKE 'last_active_at'");
        if ($lastActiveColumn && $lastActiveColumn->rowCount() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN last_active_at DATETIME NULL AFTER is_active");
        }
        $pdo->exec("UPDATE users SET is_active = 1 WHERE is_active IS NULL");

        // Sicherstellen, dass mindestens ein Super-Admin existiert
        $superAdminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'super_admin'")->fetchColumn();
        if ($superAdminCount === 0) {
            $pdo->exec("UPDATE users SET role = 'super_admin' WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
        }
        
        // Angepasste Gipfeli-Tabelle für Mehrfacheinträge
        $pdo->exec("CREATE TABLE IF NOT EXISTS `gipfeli_entries` (
            `id` VARCHAR(50) PRIMARY KEY,
            `date` DATE NOT NULL,
            `user_id` INT NULL,
            `name` VARCHAR(255) NOT NULL,
            `type` VARCHAR(255),
            `notified_all` TINYINT(1) NOT NULL DEFAULT 0,
            `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (`date`),
            INDEX (`user_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        // Migration für alte Installationen ohne user_id
        $stmt = $pdo->query("SHOW COLUMNS FROM gipfeli_entries LIKE 'user_id'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE gipfeli_entries ADD COLUMN user_id INT NULL AFTER date");
            $pdo->exec("ALTER TABLE gipfeli_entries ADD INDEX idx_user_id (user_id)");
            $pdo->exec("
                UPDATE gipfeli_entries g
                JOIN users u ON u.name = g.name
                SET g.user_id = u.id
                WHERE g.user_id IS NULL
            ");
        }

        $notifiedColumn = $pdo->query("SHOW COLUMNS FROM gipfeli_entries LIKE 'notified_all'");
        if ($notifiedColumn && $notifiedColumn->rowCount() === 0) {
            $pdo->exec("ALTER TABLE gipfeli_entries ADD COLUMN notified_all TINYINT(1) NOT NULL DEFAULT 0 AFTER type");
        }
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `password_resets` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `token` VARCHAR(64) NOT NULL UNIQUE,
            `expires_at` DATETIME NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS `audit_log` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT,
            `action` VARCHAR(50) NOT NULL,
            `description` TEXT,
            `data` JSON,
            `ip_address` VARCHAR(45),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Angepasste Likes-Tabelle für Mehrfacheinträge
        $pdo->exec("CREATE TABLE IF NOT EXISTS `gipfeli_likes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `entry_id` VARCHAR(50) NOT NULL,
            `entry_date` DATE NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY (`user_id`, `entry_id`),
            FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`entry_id`) REFERENCES `gipfeli_entries`(`id`) ON DELETE CASCADE
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Tabelle für Branding-Einstellungen erstellen
        $pdo->exec("CREATE TABLE IF NOT EXISTS `app_config` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `category` VARCHAR(50) NOT NULL,
            `name` VARCHAR(50) NOT NULL,
            `value` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (`category`, `name`)
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Datenbank beim ersten Aufruf einrichten
setupDatabase();
touchCurrentUserActivity();
enforceCurrentUserIsActive(isset($_GET['api']));

// Wenn die Anfrage an die API geht, entsprechend verarbeiten
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    handleApiRequest();
    exit;
}

// Wenn nichts von oben zutrifft, zeige die entsprechende Seite
$page = isset($_GET['page']) ? $_GET['page'] : (isLoggedIn() ? 'main' : 'start');

// Wenn der Benutzer nicht angemeldet ist und die Seite erfordert Anmeldung, umleiten
if (!isLoggedIn() && !in_array($page, ['start', 'login', 'register', 'reset-password', 'confirm-reset'])) {
    header('Location: ?page=login');
    exit;
}

// Admin-Seiten nur für Admins zugänglich
if (strpos($page, 'admin-') === 0 && !isAdmin()) {
    header('Location: ?page=main');
    exit;
}

// Seite anzeigen basierend auf dem 'page' Parameter
switch ($page) {
    case 'start':
        include 'pages/start.php';
        break;
    case 'login':
        include 'pages/login.php';
        break;
    case 'register':
        include 'pages/register.php';
        break;
    case 'reset-password':
        include 'pages/reset-password.php';
        break;
    case 'confirm-reset':
        include 'pages/confirm-reset.php';
        break;
    case 'stats':
        include 'pages/stats.php';
        break;
    case 'admin-users':
        include 'pages/admin-users.php';
        break;
    case 'admin-audit':
        include 'pages/admin-audit.php';
        break;
    case 'admin-settings':
        include 'pages/admin-settings.php';
        break;
    case 'admin-branding':
        include 'pages/admin-branding.php';
        break;
    default:
        include 'pages/main.php';
        break;
}

