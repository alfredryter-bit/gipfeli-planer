<?php
// setup.php - Erweitertes Installationsskript für den Gipfeli-Koordinator

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

// Keine Fehlerausgabe an den Browser
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

// Direkten Zugriff auf Setup nach Installation verhindern
$isLocalForceSetup = isset($_GET['force_setup']) && in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
$legacyConfigFile = APP_ROOT . DIRECTORY_SEPARATOR . 'config.php';
if ((is_file(APP_CONFIG_FILE) || is_file($legacyConfigFile)) && !$isLocalForceSetup) {
    header('Location: index.php');
    exit('Setup ist gesperrt, da bereits eine Konfiguration existiert.');
}

session_start();
header('Content-Type: text/html; charset=utf-8');

function getSetupCsrfToken() {
    if (empty($_SESSION['setup_csrf_token'])) {
        $_SESSION['setup_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['setup_csrf_token'];
}

function validateSetupCsrfToken($token) {
    return is_string($token)
        && isset($_SESSION['setup_csrf_token'])
        && hash_equals($_SESSION['setup_csrf_token'], $token);
}

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

function isValidDatabaseName($dbName) {
    return is_string($dbName) && preg_match('/^[A-Za-z0-9_]{1,64}$/', $dbName);
}

// Standardkonfiguration
$config = [
    'db_host' => 'localhost',
    'db_user' => 'root',
    'db_pass' => '',
    'db_name' => 'gipfeli_db',
    'allowed_domains' => ['microtom.net', 'gmail.com'],
    'mail_host' => '',
    'mail_port' => 587,
    'mail_user' => '',
    'mail_pass' => '',
    'mail_from' => '',
    'mail_name' => 'Gipfeli-Koordinator',
    
    // Branding-Einstellungen
    'app_name' => 'Gipfeli-Koordinator',
    'app_logo' => '',
    'app_primary_color' => '#e74c3c',
    'app_secondary_color' => '#6c757d',
    'app_favicon' => '',
    'app_base_url' => '',
    
    // Feature-Einstellungen
    'allow_multiple_entries' => true,
    'show_multiple_warning' => true
];

// Installationsschritte
$steps = [
    1 => 'Datenbank-Konfiguration',
    2 => 'E-Mail-Konfiguration',
    3 => 'Branding-Einstellungen',
    4 => 'Feature-Konfiguration',
    5 => 'Admin-Benutzer erstellen',
    6 => 'Zusammenfassung'
];

// Tabellen, die überprüft werden sollen
$tablesToCheck = ['users', 'gipfeli_entries', 'gipfeli_likes', 'password_resets', 'audit_log'];

// Prüfe, ob Tabellen bereits existieren
$hasExistingTables = false;

// Aktueller Schritt
$currentStep = isset($_SESSION['setup_step']) ? $_SESSION['setup_step'] : 1;

// Variable für Fehlermeldungen initialisieren
$errors = [];

// Wenn ein Schritt abgeschlossen wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedCsrfToken = $_POST['csrf_token'] ?? null;
    if (!validateSetupCsrfToken($postedCsrfToken)) {
        http_response_code(403);
        $errors[] = "Ungültiger CSRF-Token. Bitte lade die Seite neu.";
    } elseif (isset($_POST['step'])) {
        switch ($_POST['step']) {
            case 1: // Datenbank-Konfiguration abgeschlossen
                $config['db_host'] = $_POST['db_host'] ?? 'localhost';
                $config['db_user'] = $_POST['db_user'] ?? '';
                $config['db_pass'] = $_POST['db_pass'] ?? '';
                $config['db_name'] = $_POST['db_name'] ?? 'gipfeli_db';
                
                $errors = [];

                if (!isValidDatabaseName($config['db_name'])) {
                    $errors[] = "Der Datenbankname darf nur Buchstaben, Zahlen und Unterstriche enthalten.";
                    break;
                }
                
                // Verbindung zur Datenbank testen
                try {
                    $dsn = "mysql:host={$config['db_host']}";
                    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass']);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // In Session speichern
                    $_SESSION['db_config'] = [
                        'db_host' => $config['db_host'],
                        'db_user' => $config['db_user'],
                        'db_pass' => $config['db_pass'],
                        'db_name' => $config['db_name']
                    ];
                    
                    // Prüfen, ob Tabellen in der angegebenen Datenbank existieren
                    $hasExistingTables = false;
                    $stmt = $pdo->prepare("SHOW DATABASES LIKE ?");
                    $stmt->execute([$config['db_name']]);
                    if ($stmt->rowCount() > 0) {
                        // Zur Datenbank wechseln
                        $pdo->exec("USE `{$config['db_name']}`");
                        
                        // Prüfe jede Tabelle
                        foreach ($tablesToCheck as $table) {
                            $query = $pdo->query("SHOW TABLES LIKE '$table'");
                            if ($query->rowCount() > 0) {
                                $hasExistingTables = true;
                                break;
                            }
                        }
                    }
                    
                    if ($hasExistingTables) {
                        // Wenn Tabellen existieren, zum speziellen Schritt 1.5 (Datenerhaltungsoption)
                        $_SESSION['setup_step'] = 1.5;
                        $currentStep = 1.5;
                    } else {
                        // Wenn keine Tabellen existieren, direkt zum nächsten Schritt
                        $_SESSION['setup_step'] = 2;
                        $currentStep = 2;
                    }
                } catch (PDOException $e) {
                    $errors[] = "Datenbankfehler bei der Verbindungsprüfung.";
                }
                break;
                
            case 1.5: // Entscheidung über Datenerhaltung
                $_SESSION['keep_existing_data'] = ($_POST['setup_option'] === 'keep');
                
                // Weiter zu Schritt 2
                $_SESSION['setup_step'] = 2;
                $currentStep = 2;
                break;
                
            case 2: // E-Mail-Konfiguration abgeschlossen
                $config['mail_host'] = $_POST['mail_host'] ?? '';
                $config['mail_port'] = intval($_POST['mail_port'] ?? 587);
                $config['mail_user'] = $_POST['mail_user'] ?? '';
                $config['mail_pass'] = $_POST['mail_pass'] ?? '';
                $config['mail_from'] = $_POST['mail_from'] ?? '';
                $config['mail_name'] = $_POST['mail_name'] ?? 'Gipfeli-Koordinator';
                $config['allowed_domains'] = explode(',', $_POST['allowed_domains'] ?? 'microtom.net,gmail.com');
                
                // Entferne Leerzeichen
                foreach ($config['allowed_domains'] as &$domain) {
                    $domain = trim($domain);
                }
                
                // In Session speichern
                $_SESSION['mail_config'] = [
                    'mail_host' => $config['mail_host'],
                    'mail_port' => $config['mail_port'],
                    'mail_user' => $config['mail_user'],
                    'mail_pass' => $config['mail_pass'],
                    'mail_from' => $config['mail_from'],
                    'mail_name' => $config['mail_name'],
                    'allowed_domains' => $config['allowed_domains']
                ];
                
                // Zum nächsten Schritt
                $_SESSION['setup_step'] = 3;
                $currentStep = 3;
                break;
                
            case 3: // Branding-Einstellungen abgeschlossen
                $config['app_name'] = $_POST['app_name'] ?? 'Gipfeli-Koordinator';
                $config['app_logo'] = $_POST['app_logo'] ?? '';
                $config['app_primary_color'] = $_POST['app_primary_color'] ?? '#e74c3c';
                $config['app_secondary_color'] = $_POST['app_secondary_color'] ?? '#6c757d';
                $config['app_favicon'] = $_POST['app_favicon'] ?? '';
                
                // In Session speichern
                $_SESSION['branding_config'] = [
                    'app_name' => $config['app_name'],
                    'app_logo' => $config['app_logo'],
                    'app_primary_color' => $config['app_primary_color'],
                    'app_secondary_color' => $config['app_secondary_color'],
                    'app_favicon' => $config['app_favicon']
                ];
                
                // Zum nächsten Schritt
                $_SESSION['setup_step'] = 4;
                $currentStep = 4;
                break;
                
            case 4: // Feature-Konfiguration abgeschlossen
                $config['allow_multiple_entries'] = isset($_POST['allow_multiple_entries']);
                $config['show_multiple_warning'] = isset($_POST['show_multiple_warning']);
                
                // In Session speichern
                $_SESSION['feature_config'] = [
                    'allow_multiple_entries' => $config['allow_multiple_entries'],
                    'show_multiple_warning' => $config['show_multiple_warning']
                ];
                
                // Zum nächsten Schritt
                $_SESSION['setup_step'] = 5;
                $currentStep = 5;
                break;
                
            case 5: // Admin-Benutzer erstellen
                $adminName = $_POST['admin_name'] ?? '';
                $adminEmail = $_POST['admin_email'] ?? '';
                $adminPassword = $_POST['admin_password'] ?? '';
                
                $errors = [];
                
                if (empty($adminName)) {
                    $errors[] = "Bitte gib einen Namen für den Admin-Benutzer an.";
                }
                
                if (empty($adminEmail) || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Bitte gib eine gültige E-Mail-Adresse für den Admin-Benutzer an.";
                }
                
                if (empty($adminPassword) || strlen($adminPassword) < 8) {
                    $errors[] = "Das Passwort muss mindestens 8 Zeichen lang sein.";
                }
                
                if (empty($errors)) {
                    // In Session speichern
                    $_SESSION['admin_user'] = [
                        'name' => $adminName,
                        'email' => $adminEmail,
                        'password' => $adminPassword
                    ];
                    
                    // Zum nächsten Schritt
                    $_SESSION['setup_step'] = 6;
                    $currentStep = 6;
                }
                break;
                
            case 6: // Installation abschließen
                $errors = [];
                
                // Konfiguration aus Session laden
                $dbConfig = $_SESSION['db_config'] ?? null;
                $mailConfig = $_SESSION['mail_config'] ?? null;
                $brandingConfig = $_SESSION['branding_config'] ?? null;
                $featureConfig = $_SESSION['feature_config'] ?? null;
                $adminUser = $_SESSION['admin_user'] ?? null;
                $keepExistingData = $_SESSION['keep_existing_data'] ?? true;
                
                if ($dbConfig && $mailConfig && $brandingConfig && $featureConfig && $adminUser) {
                    if (!isValidDatabaseName($dbConfig['db_name'])) {
                        $errors[] = "Ungültiger Datenbankname.";
                        break;
                    }
                    try {
                        // Datenbankverbindung herstellen
                        $dsn = "mysql:host={$dbConfig['db_host']}";
                        $pdo = new PDO($dsn, $dbConfig['db_user'], $dbConfig['db_pass']);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        
                        // Prüfen, ob die Datenbank existiert
                        $stmt = $pdo->prepare("SHOW DATABASES LIKE ?");
                        $stmt->execute([$dbConfig['db_name']]);
                        if ($stmt->rowCount() === 0) {
                            // Datenbank erstellen
                            $pdo->exec("CREATE DATABASE `{$dbConfig['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        }
                        
                        // Zur Datenbank wechseln
                        $pdo->exec("USE `{$dbConfig['db_name']}`");
                        
                        // Tabellen löschen, wenn Neuinstallation gewählt wurde
                        if (!$keepExistingData) {
                            // Tabellen in umgekehrter Reihenfolge löschen (wegen Fremdschlüsselbeziehungen)
                            foreach (array_reverse($tablesToCheck) as $table) {
                                $pdo->exec("DROP TABLE IF EXISTS `$table`");
                            }
                        }
                        
                        // Tabellen erstellen
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

                        $userIdColumn = $pdo->query("SHOW COLUMNS FROM gipfeli_entries LIKE 'user_id'");
                        if ($userIdColumn->rowCount() === 0) {
                            $pdo->exec("ALTER TABLE gipfeli_entries ADD COLUMN user_id INT NULL AFTER date");
                            $pdo->exec("ALTER TABLE gipfeli_entries ADD INDEX idx_user_id (user_id)");
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
                        
                        // Admin-Benutzer erstellen, wenn Neuinstallation oder der Admin-Benutzer nicht existiert
                        if (!$keepExistingData || !userExists($pdo, $adminUser['email'])) {
                            $hashedPassword = password_hash($adminUser['password'], PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')");
                            $stmt->execute([$adminUser['name'], $adminUser['email'], $hashedPassword]);
                        }
                        
                        // Assets-Verzeichnis erstellen, falls es noch nicht existiert
                        if (!file_exists('assets')) {
                            mkdir('assets', 0755, true);
                        }
                        
                        // Konfigurationsdatei speichern
                        $configContent = "<?php\n";
                        $configContent .= "// Automatisch generierte Konfigurationsdatei für den Gipfeli-Koordinator\n";
                        $configContent .= "// Direkten Zugriff verhindern\n";
                        $configContent .= "defined('SECURE_ACCESS') or die('Direkter Zugriff verweigert');\n\n";
                        $configContent .= "\$config = [\n";
                        
                        // Datenbankeinstellungen
                        $configContent .= "    // Datenbankeinstellungen\n";
                        $configContent .= "    'db_host' => '" . addslashes($dbConfig['db_host']) . "',\n";
                        $configContent .= "    'db_user' => '" . addslashes($dbConfig['db_user']) . "',\n";
                        $configContent .= "    'db_pass' => '" . addslashes($dbConfig['db_pass']) . "',\n";
                        $configContent .= "    'db_name' => '" . addslashes($dbConfig['db_name']) . "',\n\n";
                        
                        // E-Mail-Einstellungen
                        $configContent .= "    // E-Mail-Einstellungen\n";
                        $configContent .= "    'allowed_domains' => ['" . implode("', '", array_map('addslashes', $mailConfig['allowed_domains'])) . "'],\n";
                        $configContent .= "    'mail_host' => '" . addslashes($mailConfig['mail_host']) . "',\n";
                        $configContent .= "    'mail_port' => " . intval($mailConfig['mail_port']) . ",\n";
                        $configContent .= "    'mail_user' => '" . addslashes($mailConfig['mail_user']) . "',\n";
                        $configContent .= "    'mail_pass' => '" . addslashes($mailConfig['mail_pass']) . "',\n";
                        $configContent .= "    'mail_from' => '" . addslashes($mailConfig['mail_from']) . "',\n";
                        $configContent .= "    'mail_name' => '" . addslashes($mailConfig['mail_name']) . "',\n\n";
                        
                        // Branding-Einstellungen
                        $configContent .= "    // Branding-Einstellungen\n";
                        $configContent .= "    'app_name' => '" . addslashes($brandingConfig['app_name']) . "',\n";
                        $configContent .= "    'app_logo' => '" . addslashes($brandingConfig['app_logo']) . "',\n";
                        $configContent .= "    'app_primary_color' => '" . addslashes($brandingConfig['app_primary_color']) . "',\n";
                        $configContent .= "    'app_secondary_color' => '" . addslashes($brandingConfig['app_secondary_color']) . "',\n";
                        $configContent .= "    'app_favicon' => '" . addslashes($brandingConfig['app_favicon']) . "',\n";
                        $appBaseUrl = ($isHttpsRequest ? 'https' : 'http') . '://' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
                        $configContent .= "    'app_base_url' => '" . addslashes($appBaseUrl) . "',\n\n";
                        
                        // Feature-Einstellungen
                        $configContent .= "    // Feature-Einstellungen\n";
                        $configContent .= "    'allow_multiple_entries' => " . ($featureConfig['allow_multiple_entries'] ? 'true' : 'false') . ",\n";
                        $configContent .= "    'show_multiple_warning' => " . ($featureConfig['show_multiple_warning'] ? 'true' : 'false') . "\n";
                        
                        $configContent .= "];\n";
                        
                        if (!is_dir(APP_DATA_DIR) && !mkdir(APP_DATA_DIR, 0750, true) && !is_dir(APP_DATA_DIR)) {
                            $errors[] = "Fehler beim Erstellen des software_data-Verzeichnisses.";
                            break;
                        }

                        if (file_put_contents(APP_CONFIG_FILE, $configContent, LOCK_EX)) {
                            // Installation erfolgreich
                            $_SESSION['installation_complete'] = true;
                            
                            // Setze restriktive Berechtigungen für die Konfigurationsdatei
                            @chmod(APP_CONFIG_FILE, 0640);
                        } else {
                            $errors[] = "Fehler beim Speichern der Konfigurationsdatei in software_data.";
                        }
                    } catch (PDOException $e) {
                        $errors[] = "Datenbankfehler während der Installation.";
                    }
                } else {
                    $errors[] = "Konfigurationsdaten fehlen. Bitte starte die Installation neu.";
                }
                break;
        }
    }
}

// Hilfsfunktion um zu prüfen, ob ein Benutzer bereits existiert
function userExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return (int)$stmt->fetchColumn() > 0;
}

// Installation abschließen und Session löschen
if (isset($_SESSION['installation_complete']) && $_SESSION['installation_complete']) {
    session_destroy();
}

// Generiert Farbauswahl-Optionen
function generateColorOptions() {
    $colors = [
        '#e74c3c' => 'Rot',
        '#3498db' => 'Blau',
        '#2ecc71' => 'Grün',
        '#f39c12' => 'Orange',
        '#9b59b6' => 'Lila',
        '#1abc9c' => 'Türkis',
        '#34495e' => 'Dunkelblau',
        '#e67e22' => 'Amber',
        '#16a085' => 'Smaragd',
        '#c0392b' => 'Dunkelrot'
    ];
    
    $options = '';
    foreach ($colors as $value => $name) {
        $options .= "<option value=\"$value\" style=\"background-color:$value; color:white;\">$name</option>";
    }
    return $options;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gipfeli-Koordinator Installation</title>
    <style>
        :root {
            --primary-color: <?php echo $config['app_primary_color']; ?>;
            --primary-dark: <?php echo adjustBrightness($config['app_primary_color'], -20); ?>;
            --secondary-color: <?php echo $config['app_secondary_color']; ?>;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            max-width: 700px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: var(--primary-color);
            text-align: center;
        }
        .step {
            border-left: 4px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 20px;
        }
        .step-title {
            font-size: 20px;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .checkbox-group, .radio-group {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .checkbox-group input[type="checkbox"],
        .radio-group input[type="radio"] {
            margin-right: 10px;
        }
        small {
            display: block;
            margin-top: 5px;
            color: #666;
        }
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: var(--primary-dark);
        }
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }
        .next-steps {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 20px;
        }
        code {
            background-color: #f8f9fa;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
        .progress-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
        }
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
            padding-top: 30px;
            font-size: 12px;
        }
        .progress-step::before {
            content: '';
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: #ddd;
            z-index: 2;
        }
        .progress-step.active::before {
            background-color: var(--primary-color);
        }
        .progress-step.completed::before {
            background-color: #28a745;
        }
        .progress-step::after {
            content: '';
            position: absolute;
            top: 19px;
            left: 0;
            width: 100%;
            height: 2px;
            background-color: #ddd;
            z-index: 1;
        }
        .progress-step:first-child::after {
            left: 50%;
            width: 50%;
        }
        .progress-step:last-child::after {
            width: 50%;
        }
        .progress-step.completed::after {
            background-color: #28a745;
        }
        .progress-step.active::after {
            background-color: var(--primary-color);
        }
        .color-preview {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-left: 10px;
            border: 1px solid #ddd;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gipfeli-Koordinator Installation</h1>
        
        <?php if (isset($_SESSION['installation_complete']) && $_SESSION['installation_complete']): ?>
            <div class="alert alert-success">
                <strong>Installation erfolgreich abgeschlossen!</strong> Alle notwendigen Tabellen und Konfigurationen wurden erstellt.
            </div>
            <div class="next-steps">
                <h3>Nächste Schritte:</h3>
                <p>1. <a href="index.php">Öffne den Gipfeli-Koordinator</a> und melde dich mit deinem Admin-Benutzer an.</p>
                <p>2. <strong>Wichtig:</strong> Lösche die <code>setup.php</code> Datei aus Sicherheitsgründen oder benenne sie um.</p>
                <p>3. Stelle sicher, dass die Dateirechte der <code>../software_data/config.php</code> auf 640 oder 644 gesetzt sind:
                  <code>chmod 640 ../software_data/config.php</code></p>
            </div>
        <?php else: ?>
            <!-- Fortschrittsanzeige -->
            <div class="progress-container">
                <?php foreach ($steps as $stepNum => $stepName): 
                    // Zwischenschritt 1.5 in der Fortschrittsanzeige speziell behandeln
                    $isCompleted = $stepNum < $currentStep || ($stepNum == 1 && $currentStep > 1);
                    $isActive = $stepNum === $currentStep || ($stepNum == 1 && $currentStep == 1.5);
                ?>
                    <div class="progress-step <?php echo $isCompleted ? 'completed' : ($isActive ? 'active' : ''); ?>">
                        <?php echo $stepName; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <!-- Installationsschritte -->
            <?php if ($currentStep === 1): ?>
                <!-- Schritt 1: Datenbank-Konfiguration -->
                <div class="step">
                    <h2 class="step-title">Schritt 1: Datenbank-Konfiguration</h2>
                    <p>Gib die Zugangsdaten für deine MySQL-Datenbank ein:</p>
                    
                    <form method="post" action="">
                        <input type="hidden" name="step" value="1">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getSetupCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="form-group">
                            <label for="db_host">Datenbank-Host:</label>
                            <input type="text" id="db_host" name="db_host" value="<?php echo htmlspecialchars($config['db_host']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_user">Datenbank-Benutzer:</label>
                            <input type="text" id="db_user" name="db_user" value="<?php echo htmlspecialchars($config['db_user']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="db_pass">Datenbank-Passwort:</label>
                            <input type="password" id="db_pass" name="db_pass" value="<?php echo htmlspecialchars($config['db_pass']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="db_name">Datenbankname:</label>
                            <input type="text" id="db_name" name="db_name" value="<?php echo htmlspecialchars($config['db_name']); ?>" required>
                        </div>
                        
                        <button type="submit">Weiter zu Schritt 2</button>
                    </form>
                </div>
            <?php elseif ($currentStep === 1.5): ?>
                <!-- Zwischenschritt: Datenerhaltungsoption -->
                <div class="step">
                    <h2 class="step-title">Bestehende Datenbank gefunden</h2>
                    <p>In der Datenbank wurden bereits Tabellen erkannt. Wie möchtest du fortfahren?</p>
                    
                    <div class="alert alert-warning">
                        <form method="post" action="">
                            <input type="hidden" name="step" value="1.5">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getSetupCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                            
                            <div class="form-group">
                                <div class="radio-group">
                                    <input type="radio" id="setup_keep" name="setup_option" value="keep" checked>
                                    <label for="setup_keep">Bestehende Tabellen und Daten behalten</label>
                                    <small>Die Installation aktualisiert nur die Konfiguration und lässt vorhandene Daten intakt.</small>
                                </div>
                                <div class="radio-group" style="margin-top: 10px;">
                                    <input type="radio" id="setup_clean" name="setup_option" value="clean">
                                    <label for="setup_clean">Komplette Neuinstallation (ACHTUNG: Alle Daten werden gelöscht!)</label>
                                    <small>Alle vorhandenen Tabellen werden gelöscht und neu erstellt.</small>
                                </div>
                            </div>
                            
                            <button type="submit">Weiter zu Schritt 2</button>
                        </form>
                    </div>
                </div>
            <?php elseif ($currentStep === 2): ?>
                <!-- Schritt 2: E-Mail-Konfiguration -->
                <div class="step">
                    <h2 class="step-title">Schritt 2: E-Mail- und Domain-Konfiguration</h2>
                    <p>Konfiguriere die E-Mail-Einstellungen für Benachrichtigungen und erlaubte Domains:</p>
                    
                    <form method="post" action="">
                        <input type="hidden" name="step" value="2">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getSetupCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="form-group">
                            <label for="mail_host">SMTP-Server:</label>
                            <input type="text" id="mail_host" name="mail_host" value="<?php echo htmlspecialchars($config['mail_host']); ?>">
                            <small>z.B. smtp.gmail.com oder mail.example.com</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="mail_port">SMTP-Port:</label>
                            <input type="number" id="mail_port" name="mail_port" value="<?php echo $config['mail_port']; ?>">
                            <small>Typischerweise 587 für TLS, 465 für SSL</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="mail_user">SMTP-Benutzername:</label>
                            <input type="text" id="mail_user" name="mail_user" value="<?php echo htmlspecialchars($config['mail_user']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="mail_pass">SMTP-Passwort:</label>
                            <input type="password" id="mail_pass" name="mail_pass" value="<?php echo htmlspecialchars($config['mail_pass']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="mail_from">Absender-E-Mail:</label>
                            <input type="email" id="mail_from" name="mail_from" value="<?php echo htmlspecialchars($config['mail_from']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="mail_name">Absender-Name:</label>
                            <input type="text" id="mail_name" name="mail_name" value="<?php echo htmlspecialchars($config['mail_name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="allowed_domains">Erlaubte E-Mail-Domains (kommagetrennt):</label>
                            <input type="text" id="allowed_domains" name="allowed_domains" value="<?php echo htmlspecialchars(implode(',', $config['allowed_domains'])); ?>" required>
                            <small>z.B. microtom.net,gmail.com</small>
                        </div>
                        
                        <button type="submit">Weiter zu Schritt 3</button>
                    </form>
                </div>
            <?php elseif ($currentStep === 3): ?>
                <!-- Schritt 3: Branding-Einstellungen -->
                <div class="step">
                    <h2 class="step-title">Schritt 3: Branding-Einstellungen</h2>
                    <p>Passe das Aussehen des Gipfeli-Koordinators an dein Unternehmen an:</p>
                    
                    <form method="post" action="">
                        <input type="hidden" name="step" value="3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getSetupCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="form-group">
                            <label for="app_name">Name der Anwendung:</label>
                            <input type="text" id="app_name" name="app_name" value="<?php echo htmlspecialchars($config['app_name']); ?>" required>
                            <small>Der Titel, der im Header und im Browser-Tab angezeigt wird</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="app_logo">Logo-Pfad:</label>
                            <input type="text" id="app_logo" name="app_logo" value="<?php echo htmlspecialchars($config['app_logo']); ?>">
                            <small>z.B. assets/logo.png - Das Logo wird im Header angezeigt</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="app_primary_color">Primäre Farbe:</label>
                            <select id="app_primary_color" name="app_primary_color">
                                <?php echo generateColorOptions(); ?>
                            </select>
                            <div id="primary-preview" class="color-preview" style="background-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>"></div>
                            <small>Wird für Header, Buttons und Akzente verwendet</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="app_secondary_color">Sekundäre Farbe:</label>
                            <select id="app_secondary_color" name="app_secondary_color">
                                <?php echo generateColorOptions(); ?>
                            </select>
                            <div id="secondary-preview" class="color-preview" style="background-color: <?php echo htmlspecialchars($config['app_secondary_color']); ?>"></div>
                            <small>Wird für sekundäre Buttons und weniger wichtige Elemente verwendet</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="app_favicon">Favicon-Pfad:</label>
                            <input type="text" id="app_favicon" name="app_favicon" value="<?php echo htmlspecialchars($config['app_favicon']); ?>">
                            <small>z.B. assets/favicon.ico - Das Icon im Browser-Tab</small>
                        </div>
                        
                        <button type="submit">Weiter zu Schritt 4</button>
                    </form>
                    
                    <script>
                        // Farb-Vorschau aktualisieren
                        document.getElementById('app_primary_color').addEventListener('change', function() {
                            document.getElementById('primary-preview').style.backgroundColor = this.value;
                        });
                        
                        document.getElementById('app_secondary_color').addEventListener('change', function() {
                            document.getElementById('secondary-preview').style.backgroundColor = this.value;
                        });
                    </script>
                </div>
            <?php elseif ($currentStep === 4): ?>
                <!-- Schritt 4: Feature-Konfiguration -->
                <div class="step">
                    <h2 class="step-title">Schritt 4: Feature-Konfiguration</h2>
                    <p>Konfiguriere, welche Funktionen aktiviert sein sollen:</p>
                    
                    <form method="post" action="">
                        <input type="hidden" name="step" value="4">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getSetupCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="allow_multiple_entries" name="allow_multiple_entries" <?php echo $config['allow_multiple_entries'] ? 'checked' : ''; ?>>
                                <label for="allow_multiple_entries">Mehrere Einträge pro Tag erlauben</label>
                            </div>
                            <small>Wenn aktiviert, können mehrere Personen Gipfeli für denselben Tag ankündigen</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="show_multiple_warning" name="show_multiple_warning" <?php echo $config['show_multiple_warning'] ? 'checked' : ''; ?>>
                                <label for="show_multiple_warning">Warnung anzeigen bei mehreren Einträgen</label>
                            </div>
                            <small>Zeigt eine Warnung an, wenn bereits ein Eintrag für den Tag existiert</small>
                        </div>
                        
                        <button type="submit">Weiter zu Schritt 5</button>
                    </form>
                </div>
            <?php elseif ($currentStep === 5): ?>
                <!-- Schritt 5: Admin-Benutzer erstellen -->
                <div class="step">
                    <h2 class="step-title">Schritt 5: Admin-Benutzer erstellen</h2>
                    <p>Erstelle den Hauptadministrator für den Gipfeli-Koordinator:</p>
                    
                    <form method="post" action="">
                        <input type="hidden" name="step" value="5">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getSetupCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                        
                        <div class="form-group">
                            <label for="admin_name">Name:</label>
                            <input type="text" id="admin_name" name="admin_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_email">E-Mail-Adresse:</label>
                            <input type="email" id="admin_email" name="admin_email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="admin_password">Passwort:</label>
                            <input type="password" id="admin_password" name="admin_password" required minlength="8">
                            <small>Mindestens 8 Zeichen</small>
                        </div>
                        
                        <button type="submit">Weiter zu Schritt 6</button>
                    </form>
                </div>
            <?php elseif ($currentStep === 6): ?>
                <!-- Schritt 6: Zusammenfassung und Installation -->
                <div class="step">
                    <h2 class="step-title">Schritt 6: Installation abschließen</h2>
                    <p>Überprüfe die Konfiguration und schließe die Installation ab:</p>
                    
                    <h3>Datenbank</h3>
                    <div class="form-group">
                        <label>Datenbank-Host:</label>
                        <div><?php echo htmlspecialchars($_SESSION['db_config']['db_host']); ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Benutzer:</label>
                        <div><?php echo htmlspecialchars($_SESSION['db_config']['db_user']); ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Datenbank-Name:</label>
                        <div><?php echo htmlspecialchars($_SESSION['db_config']['db_name']); ?></div>
                    </div>
                    
                    <?php if ($hasExistingTables): ?>
                    <div class="form-group">
                        <label>Bestehende Daten:</label>
                        <div><?php echo isset($_SESSION['keep_existing_data']) && $_SESSION['keep_existing_data'] ? 'Werden beibehalten' : 'Werden gelöscht (Neuinstallation)'; ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <h3>E-Mail-Konfiguration</h3>
                    <div class="form-group">
                        <label>E-Mail-Server:</label>
                        <div><?php echo htmlspecialchars($_SESSION['mail_config']['mail_host']) ?: '<em>Nicht konfiguriert</em>'; ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Erlaubte E-Mail-Domains:</label>
                        <div><?php echo htmlspecialchars(implode(', ', $_SESSION['mail_config']['allowed_domains'])); ?></div>
                    </div>
                    
                    <h3>Branding</h3>
                    <div class="form-group">
                        <label>Anwendungsname:</label>
                        <div><?php echo htmlspecialchars($_SESSION['branding_config']['app_name']); ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Primäre Farbe:</label>
                        <div style="display: flex; align-items: center;">
                            <?php echo htmlspecialchars($_SESSION['branding_config']['app_primary_color']); ?>
                            <div class="color-preview" style="background-color: <?php echo htmlspecialchars($_SESSION['branding_config']['app_primary_color']); ?>"></div>
                        </div>
                    </div>
                    
                    <h3>Features</h3>
                    <div class="form-group">
                        <label>Mehrere Einträge pro Tag:</label>
                        <div><?php echo $_SESSION['feature_config']['allow_multiple_entries'] ? 'Aktiviert' : 'Deaktiviert'; ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Warnung bei mehreren Einträgen:</label>
                        <div><?php echo $_SESSION['feature_config']['show_multiple_warning'] ? 'Aktiviert' : 'Deaktiviert'; ?></div>
                    </div>
                    
                    <h3>Admin-Benutzer</h3>
                    <div class="form-group">
                        <label>Name:</label>
                        <div><?php echo htmlspecialchars($_SESSION['admin_user']['name']); ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label>E-Mail:</label>
                        <div><?php echo htmlspecialchars($_SESSION['admin_user']['email']); ?></div>
                    </div>
                    
                    <form method="post" action="">
                        <input type="hidden" name="step" value="6">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getSetupCsrfToken(), ENT_QUOTES, 'UTF-8'); ?>">
                        <button type="submit">Installation abschließen</button>
                    </form>
                </div>
            <?php endif; ?>
            
            <div class="step">
                <h2>Hinweise:</h2>
                <ul>
                    <li>Falls die Datenbank noch nicht existiert, wird sie automatisch erstellt.</li>
                    <li>Die E-Mail-Konfiguration ist optional, wird aber für Passwort-Zurücksetzen und Benachrichtigungen benötigt.</li>
                    <li>Für das Logo und Favicon solltest du einen Ordner namens "assets" erstellen und die Dateien dort ablegen.</li>
                    <li>Nach der Installation kannst du die <code>setup.php</code> löschen oder umbenennen.</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
