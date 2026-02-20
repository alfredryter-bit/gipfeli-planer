<?php
// index.php - Hauptdatei für den erweiterten Gipfeli-Koordinator
define('SECURE_ACCESS', true);
define('APP_ROOT', __DIR__);
define('APP_DATA_DIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'software_data');
define('APP_CONFIG_FILE', APP_DATA_DIR . DIRECTORY_SEPARATOR . 'config.php');

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
    'show_multiple_warning' => true                  // Zeige Warnung bei mehreren Einträgen
];

// Standardrolle für neue Benutzer
$defaultRole = 'user';

// Branding-Einstellungen aus der Datenbank laden, falls vorhanden
function loadBrandingFromDatabase() {
    global $config;
    
    try {
        $pdo = connectDB();
        // Prüfen ob die Tabelle existiert
        $stmt = $pdo->query("SHOW TABLES LIKE 'app_config'");
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->query("SELECT name, value FROM app_config WHERE category = 'branding'");
            while ($row = $stmt->fetch()) {
                // Bei booleschen Werten die richtige Konvertierung durchführen
                if ($row['value'] === '0' || $row['value'] === '1') {
                    $config[$row['name']] = (bool)$row['value'];
                } else {
                    $config[$row['name']] = $row['value'];
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
        if (!strpos($_SERVER['REQUEST_URI'], 'setup.php')) {
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
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
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
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Passwort korrekt, Session setzen
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            
            // Audit-Log: Login
            addAuditLog('login', "Benutzer hat sich angemeldet");
            
            return true;
        }
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

// Überprüfen, ob die E-Mail-Domain erlaubt ist
function isAllowedEmailDomain($email) {
    global $config;
    $domain = strtolower(substr(strrchr($email, "@"), 1));
    $allowedDomains = array_map('strtolower', $config['allowed_domains']);
    return in_array($domain, $allowedDomains, true);
}

// Generiere einen zufälligen Token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// E-Mail senden
function sendEmail($to, $subject, $body, $isHTML = true) {
    global $config;
    
    // Prüfen, ob die Mail-Konfiguration vollständig ist
    if (empty($config['mail_host']) || empty($config['mail_user']) || empty($config['mail_pass'])) {
        return false;
    }
    
    // App-Name für Absender korrekt verwenden
    $mailFromName = $config['app_name'] ?? 'Gipfeli-Koordinator';
    
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
            $mail->addAddress($to);
            
            // Content
            $mail->isHTML($isHTML);
            $mail->Subject = $subject;
            $mail->Body = $body;
            
            $mail->send();
            return true;
        } catch (Exception $e) {
            return false;
        }
    } else {
        // Fallback zu mail() Funktion
        $headers = 'From: ' . $mailFromName . ' <' . $config['mail_from'] . ">\r\n";
        
        if ($isHTML) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        
        return mail($to, $subject, $body, $headers);
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
        $stmt = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY name');
        return ['users' => $stmt->fetchAll()];
    } catch (PDOException $e) {
        return ['error' => 'Interner Datenbankfehler'];
    }
}

// Benutzer aktualisieren (nur für Admins)
function updateUser($data) {
    if (!isset($data['id'])) {
        return ['error' => 'Benutzer-ID fehlt'];
    }
    
    try {
        $pdo = connectDB();
        
        $updates = [];
        $params = [];
        
        // Name aktualisieren
        if (isset($data['name'])) {
            $updates[] = 'name = ?';
            $params[] = $data['name'];
        }
        
        // Rolle aktualisieren
        if (isset($data['role'])) {
            $updates[] = 'role = ?';
            $params[] = $data['role'];
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
        
        $params[] = $data['id'];
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ' WHERE id = ?';
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        // Benutzerdetails für Audit-Log abrufen
        $stmt = $pdo->prepare('SELECT name, email FROM users WHERE id = ?');
        $stmt->execute([$data['id']]);
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
        
        if ($user['role'] === 'admin') {
            // Prüfen, ob es noch andere Admins gibt
            $stmt = $pdo->query('SELECT COUNT(*) as count FROM users WHERE role = "admin"');
            $result = $stmt->fetch();
            
            if ($result['count'] <= 1) {
                return ['error' => 'Der letzte Administrator kann nicht gelöscht werden'];
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
    $email = strtolower(trim((string)$email));

    if ($name === '' || strlen($name) > 255) {
        return ['error' => 'Ungültiger Name'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
        $token = bin2hex(random_bytes(32)); // Sicherer Token
        $tokenHash = hash('sha256', $token);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Alten Token entfernen
        $stmt = $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?');
        $stmt->execute([$user['id']]);
        
        // Neuen Token speichern
        $stmt = $pdo->prepare('INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)');
        $stmt->execute([$user['id'], $tokenHash, $expires]);
        
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
            error_log("Date: $date has " . count($entry) . " entries");
            // Überprüfen, ob es ein numerisches Array oder ein assoziatives Array ist
            if (isset($entry[0])) {
                error_log("  Entry structure: Numeric array");
            } else {
                error_log("  Entry structure: Associative array - PROBLEM!");
            }
        } else {
            error_log("Date: $date has 1 single entry (not in array)");
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
        
        error_log("Total entries found: " . count($rows));
        
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
        error_log("Final entries structure: " . substr(json_encode($entries), 0, 1000) . "...");
        
        // Debug-Funktion aufrufen, um die Struktur zu überprüfen
        return debugEntries($entries);
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
        
        error_log("Saving entry: Date=$date, Name=$name, Existing entries={$result['count']}");
        
        // Generiere eine eindeutige ID für den Eintrag
        $entryId = uniqid('entry_');
        
        // Neuen Eintrag erstellen
        $stmt = $pdo->prepare('INSERT INTO gipfeli_entries (id, date, user_id, name, type, timestamp) VALUES (?, ?, ?, ?, ?, NOW())');
        $success = $stmt->execute([$entryId, $date, $userId, $name, $gipfeliType]);
        
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
            notifyUsers($date, $name, $gipfeliType, $notificationMessage);
            
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
            $stmt = $pdo->prepare('SELECT user_id, name, type FROM gipfeli_entries WHERE id = ?');
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
            
            // Alle Likes für diesen Eintrag löschen
            $stmt = $pdo->prepare('DELETE FROM gipfeli_likes WHERE entry_id = ?');
            $stmt->execute([$entryId]);
            
            // Eintrag löschen
            $stmt = $pdo->prepare('DELETE FROM gipfeli_entries WHERE id = ?');
            $stmt->execute([$entryId]);
        } else {
            // Alle eigenen Einträge für das Datum löschen (Admins dürfen alle)
            if (isAdmin()) {
                $stmt = $pdo->prepare('SELECT id FROM gipfeli_entries WHERE date = ?');
                $stmt->execute([$date]);
            } else {
                $stmt = $pdo->prepare('
                    SELECT id FROM gipfeli_entries
                    WHERE date = ?
                    AND (user_id = ? OR (user_id IS NULL AND name = ?))
                ');
                $stmt->execute([$date, (int)$_SESSION['user_id'], $_SESSION['user_name']]);
            }
            $entries = $stmt->fetchAll();

            if (empty($entries)) {
                http_response_code(404);
                return ['error' => 'Keine löschbaren Einträge gefunden'];
            }
            
            foreach ($entries as $entry) {
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
                $rateLimitIdentifier = strtolower(trim((string)($data['email'] ?? '')));
                if (!consumeRateLimit('login', 10, 900, $rateLimitIdentifier)) {
                    http_response_code(429);
                    echo json_encode(['error' => 'Zu viele Anmeldeversuche. Bitte später erneut versuchen.']);
                    break;
                }
                if (isset($data['email']) && isset($data['password'])) {
                    if (authenticateUser($data['email'], $data['password'])) {
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
                        http_response_code(401);
                        echo json_encode(['error' => 'Ungültige Anmeldedaten']);
                    }
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'E-Mail und Passwort sind erforderlich']);
                }
            }
            break;
            
       case 'register':
    if ($method === 'POST') {
        $data = getJsonInput();
        $rateLimitIdentifier = strtolower(trim((string)($data['email'] ?? '')));
        if (!consumeRateLimit('register', 5, 3600, $rateLimitIdentifier)) {
            http_response_code(429);
            echo json_encode(['error' => 'Zu viele Registrierungsversuche. Bitte später erneut versuchen.']);
            break;
        }
        if (isset($data['email']) && isset($data['password']) && isset($data['name'])) {
            $email = $data['email'];

            if (!isAllowedEmailDomain($email)) {
                http_response_code(400);
                echo json_encode(['error' => 'Diese E-Mail-Domain ist nicht erlaubt. Erlaubte Domains: ' . implode(', ', $GLOBALS['config']['allowed_domains'])]);
                break;
            }
            
            // Registrieren
            $result = registerUser($data['name'], $email, $data['password']);
            if (isset($result['success'])) {
                echo json_encode($result);
            } else {
                http_response_code(400);
                echo json_encode($result);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Name, E-Mail und Passwort sind erforderlich']);
        }
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
                $rateLimitIdentifier = strtolower(trim((string)($data['email'] ?? '')));
                if (!consumeRateLimit('reset_password', 5, 3600, $rateLimitIdentifier)) {
                    http_response_code(429);
                    echo json_encode(['error' => 'Zu viele Anfragen. Bitte später erneut versuchen.']);
                    break;
                }
                if (isset($data['email'])) {
                    $result = resetPassword($data['email']);
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
            
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpunkt nicht gefunden']);
    }
    exit;
}
// Tabellen erstellen/prüfen bei Bedarf
function setupDatabase() {
    try {
        $pdo = connectDB();
        
        // Tabellen überprüfen und bei Bedarf erstellen
        $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL,
            `email` VARCHAR(255) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
            `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Angepasste Gipfeli-Tabelle für Mehrfacheinträge
        $pdo->exec("CREATE TABLE IF NOT EXISTS `gipfeli_entries` (
            `id` VARCHAR(50) PRIMARY KEY,
            `date` DATE NOT NULL,
            `user_id` INT NULL,
            `name` VARCHAR(255) NOT NULL,
            `type` VARCHAR(255),
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

// Wenn die Anfrage an die API geht, entsprechend verarbeiten
if (isset($_GET['api'])) {
    header('Content-Type: application/json');
    handleApiRequest();
    exit;
}

// Wenn nichts von oben zutrifft, zeige die entsprechende Seite
$page = isset($_GET['page']) ? $_GET['page'] : 'main';

// Wenn der Benutzer nicht angemeldet ist und die Seite erfordert Anmeldung, umleiten
if (!isLoggedIn() && !in_array($page, ['login', 'register', 'reset-password', 'confirm-reset'])) {
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
    case 'admin-branding':
        include 'pages/admin-branding.php';
        break;
    default:
        include 'pages/main.php';
        break;
}

