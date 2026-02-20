<?php
// pages/admin-branding.php - Seite für Branding-Einstellungen
// Diese Seite ist nur für Administrator-Benutzer erreichbar

// Sicherstellen, dass nur Administratoren Zugriff haben
if (!isAdmin()) {
    header('Location: ?page=main');
    exit;
}

function saveBrandingUpload($fieldName, $prefix, array $allowedMimeTypes, $maxFileSize = 2097152) {
    if (empty($_FILES[$fieldName]['name'])) {
        return [null, null];
    }

    if (!isset($_FILES[$fieldName]['error']) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Upload fehlgeschlagen.'];
    }

    if ((int)$_FILES[$fieldName]['size'] > $maxFileSize) {
        return [null, 'Datei ist zu groß (max. 2 MB).'];
    }

    $tmpFile = $_FILES[$fieldName]['tmp_name'];
    if (!is_uploaded_file($tmpFile)) {
        return [null, 'Ungültige Upload-Datei.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $tmpFile) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!isset($allowedMimeTypes[$mimeType])) {
        return [null, 'Dateityp nicht erlaubt.'];
    }

    $uploadDir = 'assets';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return [null, 'Upload-Verzeichnis konnte nicht erstellt werden.'];
    }

    $fileName = sprintf(
        'assets/%s_%s.%s',
        $prefix,
        str_replace('.', '', uniqid('', true)),
        $allowedMimeTypes[$mimeType]
    );

    if (!move_uploaded_file($tmpFile, $fileName)) {
        return [null, 'Datei konnte nicht gespeichert werden.'];
    }

    return [$fileName, null];
}

// Branding-Einstellungen laden
$appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
$appLogo = $config['app_logo'] ?? '';
$primaryColor = $config['app_primary_color'] ?? '#e74c3c';
$secondaryColor = $config['app_secondary_color'] ?? '#6c757d';
$appFavicon = $config['app_favicon'] ?? '';
$allowMultipleEntries = $config['allow_multiple_entries'] ?? false;
$showMultipleWarning = $config['show_multiple_warning'] ?? true;

// Wenn das Formular abgesendet wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_branding'])) {
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        http_response_code(403);
        $errorMessage = "Ungültige Anfrage. Bitte Seite neu laden.";
    } else {
    try {
        $pdo = connectDB();
        
        // Vorhandene Konfiguration laden
        $existingConfig = [];
        $stmt = $pdo->query("SELECT name, value FROM app_config WHERE category = 'branding'");
        while ($row = $stmt->fetch()) {
            $existingConfig[$row['name']] = $row['value'];
        }
        
        // Neue Werte vorbereiten
        $newConfig = [
            'app_name' => trim($_POST['app_name']),
            'app_primary_color' => trim($_POST['app_primary_color']),
            'app_secondary_color' => trim($_POST['app_secondary_color']),
            'allow_multiple_entries' => isset($_POST['allow_multiple_entries']) ? 1 : 0,
            'show_multiple_warning' => isset($_POST['show_multiple_warning']) ? 1 : 0
        ];

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $newConfig['app_primary_color'])
            || !preg_match('/^#[0-9A-Fa-f]{6}$/', $newConfig['app_secondary_color'])) {
            throw new RuntimeException('Ungültiger Farbwert.');
        }
        
        // Logo-Datei hochladen, falls vorhanden
        [$logoFileName, $logoError] = saveBrandingUpload('app_logo', 'logo', [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp'
        ]);
        if ($logoError) {
            throw new RuntimeException($logoError);
        }
        if ($logoFileName) {
            $newConfig['app_logo'] = $logoFileName;
        }
        
        // Favicon hochladen, falls vorhanden
        [$faviconFileName, $faviconError] = saveBrandingUpload('app_favicon', 'favicon', [
            'image/vnd.microsoft.icon' => 'ico',
            'image/x-icon' => 'ico',
            'image/png' => 'png'
        ]);
        if ($faviconError) {
            throw new RuntimeException($faviconError);
        }
        if ($faviconFileName) {
            $newConfig['app_favicon'] = $faviconFileName;
        }
        
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
        
        // Werte speichern oder aktualisieren
        foreach ($newConfig as $name => $value) {
            if (isset($existingConfig[$name])) {
                // Wenn der Wert bereits existiert, aktualisieren
                $stmt = $pdo->prepare("UPDATE app_config SET value = ? WHERE category = 'branding' AND name = ?");
                $stmt->execute([$value, $name]);
            } else {
                // Wenn der Wert noch nicht existiert, einfügen
                $stmt = $pdo->prepare("INSERT INTO app_config (category, name, value) VALUES ('branding', ?, ?)");
                $stmt->execute([$name, $value]);
            }
        }
        
        // Neue Konfigurationswerte in die lokale Konfiguration übernehmen
        foreach ($newConfig as $name => $value) {
            $GLOBALS['config'][$name] = $value;
        }
        
        // Audit-Log
        addAuditLog('update_branding', "Branding-Einstellungen aktualisiert");
        
        $successMessage = "Branding-Einstellungen wurden erfolgreich gespeichert.";
        
        // Seite neu laden, um die Änderungen zu sehen
        header("Location: ?page=admin-branding&success=1");
        exit;
    } catch (PDOException $e) {
        $errorMessage = "Fehler beim Speichern der Einstellungen.";
    } catch (RuntimeException $e) {
        $errorMessage = $e->getMessage();
    }
    }
}

if (isset($_GET['success'])) {
    $successMessage = "Branding-Einstellungen wurden erfolgreich gespeichert.";
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Branding-Einstellungen - <?php echo htmlspecialchars($appName); ?></title>
    <?php if (!empty($appFavicon)): ?>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($appFavicon); ?>" type="image/x-icon">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        
        .container {
            max-width: 650px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: <?php echo $primaryColor; ?>;
            color: white;
            padding: 10px 0;
        }
        
        header h2 {
            color: white;
            margin: 0;
            font-size: 1.5rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 650px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        h1, h2, h3 {
            color: #4a4a4a;
        }
        
        .user-info {
            display: flex;
            align-items: center;
        }
        
        .user-info span {
            margin-right: 15px;
        }
        
        nav {
            background-color: #f8f8f8;
            border-bottom: 1px solid #e1e1e1;
        }
        
        nav ul {
            display: flex;
            list-style-type: none;
            padding: 0;
            margin: 0;
            max-width: 650px;
            margin: 0 auto;
        }
        
        nav ul li {
            padding: 10px 15px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: #333;
            font-size: 16px;
        }
        
        nav ul li a:hover {
            color: <?php echo $primaryColor; ?>;
        }
        
        nav ul li a.active {
            color: <?php echo $primaryColor; ?>;
            font-weight: bold;
        }
        
        /* Dropdown-Menü für Benutzer-Aktionen */
        .user-dropdown {
            position: relative;
            display: inline-block;
        }
        .user-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 160px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 4px;
        }
        .user-dropdown-content a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            font-size: 13px;
        }
        .user-dropdown-content a:hover {
            background-color: #f1f1f1;
            border-radius: 4px;
        }
        .user-dropdown:hover .user-dropdown-content {
            display: block;
        }
        .user-dropdown-toggle {
            background: transparent;
            border: none;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
        }
        .user-dropdown-toggle .fa-user-circle {
            margin-right: 5px;
        }
        
        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 10;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 15px;
            border-radius: 8px;
            width: 350px;
            max-width: 90%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .modal-title {
            font-size: 18px;
            font-weight: bold;
            margin: 0;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #999;
        }
        .close-modal:hover {
            color: #333;
        }
        
        /* Status-Meldungen */
        .status {
            margin-top: 8px;
            padding: 6px;
            border-radius: 4px;
            display: none;
            font-size: 13px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        /* Form Styling */
        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        input, textarea, button, select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-family: inherit;
            font-size: inherit;
        }
        button {
            background-color: <?php echo $primaryColor; ?>;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: <?php echo adjustBrightness($primaryColor, -20); ?>;
        }
        .btn-secondary {
            background-color: <?php echo $secondaryColor; ?>;
        }
        .btn-secondary:hover {
            background-color: <?php echo adjustBrightness($secondaryColor, -20); ?>;
        }
        small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        
        /* Branding-spezifische Styles */
        .color-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .color-input-group input[type="color"] {
            height: 40px;
            width: 60px;
        }
        
        .color-input-group input[type="text"] {
            flex: 1;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        
        .preview-section {
            margin-top: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #f9f9f9;
        }
        
        .preview-header {
            background-color: var(--primary-color);
            color: white;
            padding: 10px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .preview-name {
            font-weight: bold;
            color: white;
        }
        
        .preview-buttons {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        
        .preview-button {
            padding: 8px 15px;
            border-radius: 4px;
            border: none;
            font-weight: bold;
            cursor: pointer;
            color: white;
        }
        
        .primary-button {
            background-color: var(--primary-color);
        }
        
        .primary-button:hover {
            background-color: var(--primary-color-dark);
        }
        
        .secondary-button {
            background-color: var(--secondary-color);
        }
        
        .secondary-button:hover {
            background-color: var(--secondary-color-dark);
        }
        
        .preview-images {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .preview-image {
            border: 1px solid #ddd;
            padding: 5px;
            background-color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }
        
        .preview-image img {
            max-height: 50px;
            max-width: 200px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin: 0;
            padding: 0;
            width: auto;
        }
        
        .submit-button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .submit-button:hover {
            background-color: var(--primary-color-dark);
        }
        
        .status-message {
            margin: 15px 0;
            padding: 10px;
            border-radius: 4px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .btn-logout {
            background-color: transparent;
            border: 1px solid white;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div style="display: flex; align-items: center; gap: 10px;">
                <?php if (!empty($appLogo)): ?>
                <img src="<?php echo htmlspecialchars($appLogo); ?>" alt="Logo" height="40">
                <?php endif; ?>
                <h2><?php echo htmlspecialchars($appName); ?></h2>
            </div>
            <div class="user-info">
                <div class="user-dropdown">
                    <button class="user-dropdown-toggle">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="user-dropdown-content">
                        <a href="#" id="change-password-btn"><i class="fas fa-key"></i> Passwort ändern</a>
                        <a href="#" id="logout-btn"><i class="fas fa-sign-out-alt"></i> Abmelden</a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <nav>
        <ul>
            <li><a href="?page=main">Kalender</a></li>
            <li><a href="?page=stats">Statistiken</a></li>
            <?php if (isAdmin()): ?>
                <li><a href="?page=admin-users">Benutzerverwaltung</a></li>
                <li><a href="?page=admin-audit">Audit-Log</a></li>
                <li><a href="?page=admin-branding" class="active"><i class="fas fa-paint-brush"></i> Branding</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="container">
        <h1>Branding-Einstellungen</h1>
        
        <?php if (isset($successMessage)): ?>
        <div class="status-message success-message">
            <?php echo htmlspecialchars($successMessage); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
        <div class="status-message error-message">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
        <?php endif; ?>
        
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken()); ?>">
            <div class="form-group">
                <label for="app_name">Name der Anwendung:</label>
                <input type="text" id="app_name" name="app_name" value="<?php echo htmlspecialchars($appName); ?>" required>
                <div class="help-text">Wird in der Kopfzeile und als Browser-Titel angezeigt.</div>
            </div>
            
            <div class="form-group">
                <label for="app_primary_color">Primärfarbe:</label>
                <div class="color-input-group">
                    <input type="color" id="app_primary_color_picker" value="<?php echo htmlspecialchars($primaryColor); ?>">
                    <input type="text" id="app_primary_color" name="app_primary_color" value="<?php echo htmlspecialchars($primaryColor); ?>">
                </div>
                <div class="help-text">Hauptfarbe für Kopfzeile, Buttons und Akzente.</div>
            </div>
            
            <div class="form-group">
                <label for="app_secondary_color">Sekundärfarbe:</label>
                <div class="color-input-group">
                    <input type="color" id="app_secondary_color_picker" value="<?php echo htmlspecialchars($secondaryColor); ?>">
                    <input type="text" id="app_secondary_color" name="app_secondary_color" value="<?php echo htmlspecialchars($secondaryColor); ?>">
                </div>
                <div class="help-text">Farbe für sekundäre Elemente wie "Abbrechen"-Buttons.</div>
            </div>
            
            <div class="form-group">
                <label for="app_logo">Logo:</label>
                <input type="file" id="app_logo" name="app_logo" accept="image/png,image/jpeg,image/webp">
                <div class="help-text">Empfohlene Höhe: 30px, transparenter Hintergrund.</div>
                
                <?php if (!empty($appLogo)): ?>
                <div class="preview-image">
                    <span>Aktuelles Logo:</span>
                    <img src="<?php echo htmlspecialchars($appLogo); ?>" alt="Logo">
                </div>
                <?php endif; ?>
                <div id="logo-preview"></div>
            </div>
            
            <div class="form-group">
                <label for="app_favicon">Favicon:</label>
                <input type="file" id="app_favicon" name="app_favicon" accept="image/x-icon,image/vnd.microsoft.icon,image/png">
                <div class="help-text">Das Icon im Browser-Tab. Format: .ico oder .png</div>
                
                <?php if (!empty($appFavicon)): ?>
                <div class="preview-image">
                    <span>Aktuelles Favicon:</span>
                    <img src="<?php echo htmlspecialchars($appFavicon); ?>" alt="Favicon">
                </div>
                <?php endif; ?>
                <div id="favicon-preview"></div>
            </div>
            
            <div class="form-group">
                <label>Funktionseinstellungen:</label>
                <div class="checkbox-group">
                    <input type="checkbox" id="allow_multiple_entries" name="allow_multiple_entries" <?php echo $allowMultipleEntries ? 'checked' : ''; ?>>
                    <label for="allow_multiple_entries">Mehrere Einträge pro Tag erlauben</label>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="show_multiple_warning" name="show_multiple_warning" <?php echo $showMultipleWarning ? 'checked' : ''; ?>>
                    <label for="show_multiple_warning">Warnung anzeigen, wenn bereits Einträge für diesen Tag existieren</label>
                </div>
            </div>
            
            <div class="preview-section">
                <h3>Vorschau:</h3>
                
                <div class="preview-header">
                    <div class="preview-name" id="preview-name"><?php echo htmlspecialchars($appName); ?></div>
                </div>
                
                <div class="preview-buttons">
                    <button type="button" class="preview-button primary-button" id="preview-primary-button">Primär-Button</button>
                    <button type="button" class="preview-button secondary-button" id="preview-secondary-button">Sekundär-Button</button>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="save_branding" class="submit-button">Einstellungen speichern</button>
            </div>
        </form>
    </div>
    
    <!-- Modal für Passwort ändern -->
    <div id="password-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Passwort ändern</h3>
                <button class="close-modal" id="close-password-modal">&times;</button>
            </div>
            <div id="password-status" class="status" style="display: none;"></div>
            <form id="password-form">
                <div>
                    <label for="current-password">Aktuelles Passwort:</label>
                    <input type="password" id="current-password" required>
                </div>
                <div>
                    <label for="new-password">Neues Passwort:</label>
                    <input type="password" id="new-password" required minlength="8">
                    <small>Mindestens 8 Zeichen</small>
                </div>
                <div>
                    <label for="confirm-password">Passwort bestätigen:</label>
                    <input type="password" id="confirm-password" required>
                </div>
                <div>
                    <button type="submit">Passwort ändern</button>
                    <button type="button" class="btn-secondary" id="cancel-password-btn">Abbrechen</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // API URL
        const API_URL = '?api=1&endpoint=';
        const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';
        
        // Verknüpfung der Farbauswähler
        const primaryColorPicker = document.getElementById('app_primary_color_picker');
        const primaryColorInput = document.getElementById('app_primary_color');
        const secondaryColorPicker = document.getElementById('app_secondary_color_picker');
        const secondaryColorInput = document.getElementById('app_secondary_color');
        const appNameInput = document.getElementById('app_name');
        const logoutBtn = document.getElementById('logout-btn');
        const changePasswordBtn = document.getElementById('change-password-btn');
        const passwordModal = document.getElementById('password-modal');
        const closePasswordModalBtn = document.getElementById('close-password-modal');
        const cancelPasswordBtn = document.getElementById('cancel-password-btn');
        const passwordForm = document.getElementById('password-form');
        const passwordStatus = document.getElementById('password-status');
        
        // CSS-Variablen für Vorschau
        document.documentElement.style.setProperty('--primary-color', primaryColorInput.value);
        document.documentElement.style.setProperty('--primary-color-dark', adjustBrightness(primaryColorInput.value, -20));
        document.documentElement.style.setProperty('--secondary-color', secondaryColorInput.value);
        document.documentElement.style.setProperty('--secondary-color-dark', adjustBrightness(secondaryColorInput.value, -20));
        
        // Event-Listener initialisieren
        document.addEventListener('DOMContentLoaded', function() {
            // Event-Listener für Logout
            logoutBtn.addEventListener('click', logout);
            
            // Event-Listener für Passwort ändern
            changePasswordBtn.addEventListener('click', function(e) {
                e.preventDefault();
                passwordModal.style.display = 'block';
            });
            
            // Event-Listener zum Schließen des Passwort-Modals
            closePasswordModalBtn.addEventListener('click', function() {
                passwordModal.style.display = 'none';
            });
            
            cancelPasswordBtn.addEventListener('click', function() {
                passwordModal.style.display = 'none';
            });
            
            // Klick außerhalb des Modals schließt es
            window.addEventListener('click', function(event) {
                if (event.target === passwordModal) {
                    passwordModal.style.display = 'none';
                }
            });
            
            // Event-Listener für Passwort-Formular
            passwordForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const currentPassword = document.getElementById('current-password').value;
                const newPassword = document.getElementById('new-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                // Validierung
                if (newPassword.length < 8) {
                    showPasswordStatus('Das neue Passwort muss mindestens 8 Zeichen lang sein.', 'error');
                    return;
                }
                
                if (newPassword !== confirmPassword) {
                    showPasswordStatus('Die Passwörter stimmen nicht überein.', 'error');
                    return;
                }
                
                changePassword(currentPassword, newPassword);
            });
        });
        
        // Event-Listener für Farbauswähler
        primaryColorPicker.addEventListener('input', function() {
            primaryColorInput.value = this.value;
            updatePreview();
        });
        
        primaryColorInput.addEventListener('input', function() {
            primaryColorPicker.value = this.value;
            updatePreview();
        });
        
        secondaryColorPicker.addEventListener('input', function() {
            secondaryColorInput.value = this.value;
            updatePreview();
        });
        
        secondaryColorInput.addEventListener('input', function() {
            secondaryColorPicker.value = this.value;
            updatePreview();
        });
        
        appNameInput.addEventListener('input', updatePreview);
        
        // Passwort-Status anzeigen
        function showPasswordStatus(message, type) {
            passwordStatus.textContent = message;
            passwordStatus.className = 'status ' + type;
            passwordStatus.style.display = 'block';
            
            if (type === 'success') {
                setTimeout(() => {
                    passwordStatus.style.display = 'none';
                    passwordModal.style.display = 'none';
                }, 2000);
            }
        }
        
        // Passwort ändern
        async function changePassword(currentPassword, newPassword) {
            try {
                const response = await fetch(API_URL + 'change-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify({ 
                        current_password: currentPassword,
                        new_password: newPassword
                    })
                });
                
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.error || 'Fehler beim Ändern des Passworts');
                }
                
                showPasswordStatus('Passwort erfolgreich geändert!', 'success');
                passwordForm.reset();
            } catch (error) {
                showPasswordStatus(error.message, 'error');
                console.error('Fehler beim Ändern des Passworts:', error);
            }
        }
        
        // Logout-Funktion
        async function logout() {
            try {
                await fetch(API_URL + 'logout', { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': CSRF_TOKEN
                    }
                });
                window.location.href = '?page=login';
            } catch (error) {
                console.error('Logout fehlgeschlagen:', error);
            }
        }
// Live-Vorschau aktualisieren
        function updatePreview() {
            const primaryColor = primaryColorInput.value;
            const secondaryColor = secondaryColorInput.value;
            const appName = appNameInput.value;
            
            document.documentElement.style.setProperty('--primary-color', primaryColor);
            document.documentElement.style.setProperty('--primary-color-dark', adjustBrightness(primaryColor, -20));
            document.documentElement.style.setProperty('--secondary-color', secondaryColor);
            document.documentElement.style.setProperty('--secondary-color-dark', adjustBrightness(secondaryColor, -20));
            
            document.getElementById('preview-name').textContent = appName;
        }
        
        // Hilfsfunktion zur Anpassung der Helligkeit einer Farbe
        function adjustBrightness(hex, steps) {
            // Entferne das # am Anfang, falls vorhanden
            hex = hex.replace(/^#/, '');
            
            // Konvertiere in RGB
            let r = parseInt(hex.substring(0, 2), 16);
            let g = parseInt(hex.substring(2, 4), 16);
            let b = parseInt(hex.substring(4, 6), 16);
            
            // Helligkeit anpassen
            r = Math.max(0, Math.min(255, r + steps));
            g = Math.max(0, Math.min(255, g + steps));
            b = Math.max(0, Math.min(255, b + steps));
            
            // Zurück zu hex konvertieren
            return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
        }
        
        // Bild-Vorschau
        document.getElementById('app_logo').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('logo-preview');
                    preview.innerHTML = `
                        <div class="preview-image">
                            <span>Neues Logo:</span>
                            <img src="${e.target.result}" alt="Logo-Vorschau">
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
        
        document.getElementById('app_favicon').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('favicon-preview');
                    preview.innerHTML = `
                        <div class="preview-image">
                            <span>Neues Favicon:</span>
                            <img src="${e.target.result}" alt="Favicon-Vorschau">
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>
