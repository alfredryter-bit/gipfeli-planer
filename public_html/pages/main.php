<?php
// pages/main.php - Hauptseite mit Kalender und Gipfeli-Verwaltung
// Diese Seite ist nur für angemeldete Benutzer erreichbar

// Branding-Einstellungen laden
$appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
$appLogo = $config['app_logo'] ?? '';
$primaryColor = $config['app_primary_color'] ?? '#e74c3c';
$secondaryColor = $config['app_secondary_color'] ?? '#6c757d';
$allowMultipleEntries = $config['allow_multiple_entries'] ?? false;
$showMultipleWarning = $config['show_multiple_warning'] ?? true;
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(t('meta.lang')); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($appName); ?></title>
    <?php if (isset($config['app_favicon']) && !empty($config['app_favicon'])): ?>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars(cacheBustUrl($config['app_favicon'])); ?>" type="image/x-icon">
    <?php endif; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
            --primary-color-dark: <?php echo adjustBrightness($primaryColor, -20); ?>;
            --secondary-color: <?php echo $secondaryColor; ?>;
            --secondary-color-dark: <?php echo adjustBrightness($secondaryColor, -20); ?>;
            --layout-width: 1100px;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            font-size: 14px;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            padding: 15px;
        }
        header {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 0;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: var(--layout-width);
            margin: 0 auto;
            padding: 0 20px;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo {
            height: 40px;
            width: auto;
        }
        h1 {
            color: #4a4a4a;
            text-align: center;
            margin-top: 15px;
            font-size: 1.5em;
        }
        .user-info {
            display: flex;
            align-items: center;
        }
        .language-switch {
            display: flex;
            align-items: center;
            gap: 4px;
            margin-right: 10px;
        }
        .language-switch a {
            color: #fff;
            text-decoration: none;
            font-weight: bold;
            opacity: 0.85;
            font-size: 13px;
        }
        .language-switch a.active {
            opacity: 1;
            text-decoration: underline;
        }
.user-dropdown {
            position: relative;
            display: inline-block;
        }
        .user-dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #f9f9f9;
            min-width: 180px;
            box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
            z-index: 12;
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
            font-size: 16px;
            padding: 0;
        }
        .user-dropdown-toggle .fa-user-circle {
            margin-right: 5px;
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
            max-width: var(--layout-width);
            margin: 0 auto;
            justify-content: center;
            flex-wrap: wrap;
            gap: 4px;
        }
        nav ul li {
            padding: 10px 15px;
        }
        nav ul li a {
            text-decoration: none;
            color: #333;
            font-size: 15px;
        }
        nav ul li a:hover {
            color: var(--primary-color);
        }
        nav ul li a.active {
            color: var(--primary-color);
            font-weight: bold;
        }
        .app-title {
            color: white;
            margin: 0;
            font-size: 1.35rem;
            line-height: 1.2;
        }
        .calendar {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 15px;
        }
        .month-header {
            background-color: var(--primary-color);
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 16px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .month-nav {
            background: none;
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 0 8px;
            transition: transform 0.2s ease, background-color 0.2s ease;
        }
        .month-nav:hover {
            transform: scale(1.1);
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
        }
        .month-nav:active {
            transform: scale(0.95);
        }
        .weekdays {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            text-align: center;
            font-weight: bold;
            background-color: #f1f1f1;
            padding: 8px 0;
            font-size: 12px;
            position: sticky;
            top: 0;
            z-index: 3;
        }
        .days {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1px;
            background-color: #e9e9e9;
            transition: opacity 0.3s ease;
        }
        .day {
            min-height: 85px; /* Erhöht die Höhe der Zellen, um mehr Platz für Einträge zu schaffen */
            padding: 4px;
            background-color: white;
            position: relative;
            overflow: visible; /* Erlaubt den Einträgen, aus der Zelle herauszuragen */
        }
        .day-number {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 12px;
        }
        .weekend {
            background-color: #f9f9f9;
        }
        .past-day {
            position: relative;
            background-color: #fceeee;
        }
        .past-day::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                45deg,
                rgba(255, 0, 0, 0.1),
                rgba(255, 0, 0, 0.1) 10px,
                rgba(255, 0, 0, 0.05) 10px,
                rgba(255, 0, 0, 0.05) 20px
            );
            z-index: 1;
        }
        .day:not(.empty):not(.past-day):hover {
            background-color: #f5f5f5;
            cursor: pointer;
        }
        .empty {
            background-color: #eaeaea;
        }
        .has-gipfeli {
            position: relative;
        }
        .has-gipfeli::after {
            content: '🥐';
            position: absolute;
            top: 3px;
            right: 3px;
            font-size: 12px;
            z-index: 2;
        }
        .has-multiple::after {
            content: '🥐+';
            font-size: 12px;
            position: absolute;
            top: 3px;
            right: 3px;
            z-index: 3;
        }
        .gipfeli-entries {
            display: flex;
            flex-direction: column;
            gap: 3px;
            max-height: 75px;
            overflow-y: auto;
        }
        .gipfeli-entry {
            font-size: 11px;
            padding: 2px 3px;
            background-color: #f8e0de;
            border-radius: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 2;
        }
        .gipfeli-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .gipfeli-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2px;
        }
        .like-btn {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            font-size: 11px;
            padding: 0;
            display: flex;
            align-items: center;
        }
        .like-btn.liked {
            color: var(--primary-color);
        }
        .like-btn:hover {
            color: var(--primary-color);
        }
        .like-count {
            margin-left: 2px;
            font-size: 9px;
        }
        .delete-btn {
            background: none;
            border: none;
            color: var(--primary-color);
            cursor: pointer;
            font-size: 10px;
            padding: 1px;
        }
        .delete-btn:hover {
            color: var(--primary-color-dark);
        }
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
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #999;
        }
        .close-modal:hover {
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .password-form .form-group {
            margin: 0;
        }
        .password-actions {
            display: flex;
            gap: 8px;
        }
        .password-actions button {
            flex: 1;
        }
        .password-rules {
            margin: 6px 0 0;
            padding-left: 18px;
            font-size: 12px;
            color: #666;
        }
        .password-rules li.valid {
            color: #155724;
        }
        .password-rules li.invalid {
            color: #721c24;
        }
        input, textarea, button, select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-family: inherit;
            font-size: inherit;
        }
        textarea {
            resize: vertical;
            min-height: 70px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .checkbox-group input[type="checkbox"] {
            margin: 0;
            padding: 0;
            width: auto;
        }
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: var(--primary-color-dark);
        }
        .btn-secondary {
            background-color: var(--secondary-color);
        }
        .btn-secondary:hover {
            background-color: var(--secondary-color-dark);
        }
        .instructions {
            background-color: #f8f8f8;
            border-left: 4px solid var(--primary-color);
            padding: 12px;
            margin-top: 15px;
            margin-bottom: 0;
            border-radius: 0 8px 8px 0;
            font-size: 13px;
        }
        .loading {
            display: none;
            text-align: center;
            margin: 15px 0;
        }
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
        .warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .btn-logout {
            background-color: transparent;
            border: 1px solid white;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .like-tooltip {
            position: relative;
        }
        .like-tooltip:hover .tooltip-content {
            display: block;
        }
        .tooltip-content {
            display: none;
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            background-color: #333;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 9px;
            white-space: nowrap;
            z-index: 10;
        }
        .tooltip-content::after {
            content: '';
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            border-width: 4px;
            border-style: solid;
            border-color: #333 transparent transparent transparent;
        }
        .entries-list {
            max-height: 300px;
            overflow-y: auto;
            margin-top: 10px;
        }
        .entry-item {
            border-bottom: 1px solid #eee;
            padding: 8px 0;
        }
        .entry-item:last-child {
            border-bottom: none;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
            justify-content: flex-end;
        }
        /* Tooltip für mehr Einträge */
        .more-indicator {
            cursor: pointer;
            background-color: #6c757d;
            color: white;
            font-size: 10px;
            text-align: center;
            border-radius: 3px;
            padding: 2px;
        }
        .loading-data {
            position: relative;
            pointer-events: none;
        }
        
        .loading-data::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.7);
            z-index: 3;
        }
        
        .keyboard-hint {
            font-size: 10px;
            opacity: 0.7;
            margin-top: 5px;
            text-align: center;
        }
        
        /* Wochentage in den Zellen für mobile Geräte */
        .mobile-weekday {
            display: none;
        }
        
        /* Responsive Anpassungen */
        @media (max-width: 480px) {
            .day {
                min-height: 65px;
                padding: 2px;
            }
            .gipfeli-entries {
                max-height: 60px;
            }
            .day-number {
                font-size: 10px;
                margin-bottom: 2px;
            }
            .gipfeli-entry {
                font-size: 10px;
                padding: 1px 2px;
            }
            .mobile-weekday {
                display: inline;
                font-size: 10px;
                color: #666;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
    <div class="brand">
        <?php if (!empty($appLogo)): ?>
        <img src="<?php echo htmlspecialchars(cacheBustUrl($appLogo)); ?>" alt="Logo" class="logo">
        <?php endif; ?>
        <h2 class="app-title"><?php echo htmlspecialchars($appName); ?></h2>
    </div>
    <div class="user-info">
                <div class="language-switch">
                    <a href="<?php echo htmlspecialchars(buildPageUrl(['lang' => 'de'])); ?>" class="<?php echo getCurrentLanguage() === 'de' ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('lang.de')); ?></a>
                    <span style="color: #fff; opacity: 0.7;">|</span>
                    <a href="<?php echo htmlspecialchars(buildPageUrl(['lang' => 'en'])); ?>" class="<?php echo getCurrentLanguage() === 'en' ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('lang.en')); ?></a>
                </div>
                <div class="user-dropdown">
                    <button class="user-dropdown-toggle">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        <i class="fas fa-caret-down"></i>
                    </button>
                    <div class="user-dropdown-content">
                        <a href="#" id="change-password-btn"><i class="fas fa-key"></i> <?php echo htmlspecialchars(t('action.change_password')); ?></a>
                        <a href="#" id="logout-btn"><i class="fas fa-sign-out-alt"></i> <?php echo htmlspecialchars(t('action.logout')); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </header>
    
    <nav>
        <ul>
            <li><a href="?page=main" class="active"><?php echo htmlspecialchars(t('nav.calendar')); ?></a></li>
            <li><a href="?page=stats"><?php echo htmlspecialchars(t('nav.stats')); ?></a></li>
            <?php if (isAdmin()): ?>
                <li><a href="?page=admin-users"><?php echo htmlspecialchars(t('nav.users')); ?></a></li>
                <li><a href="?page=admin-audit"><?php echo htmlspecialchars(t('nav.audit')); ?></a></li>
                <li><a href="?page=admin-settings"><i class="fas fa-cogs"></i> <?php echo htmlspecialchars(t('nav.settings')); ?></a></li>
                <li><a href="?page=admin-branding"><i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars(t('nav.branding')); ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="container">
        <div id="loading" class="loading"><?php echo htmlspecialchars(t('main.loading')); ?></div>
        
        <div class="status" id="status-message"></div>
        
        <div class="calendar">
            <div class="month-header">
                <button class="month-nav" id="prev-month">&lt;</button>
                <span id="current-month"></span>
                <button class="month-nav" id="next-month">&gt;</button>
            </div>
            <div class="weekdays">
                <div><?php echo htmlspecialchars(t('main.weekday.mo')); ?></div>
                <div><?php echo htmlspecialchars(t('main.weekday.di')); ?></div>
                <div><?php echo htmlspecialchars(t('main.weekday.mi')); ?></div>
                <div><?php echo htmlspecialchars(t('main.weekday.do')); ?></div>
                <div><?php echo htmlspecialchars(t('main.weekday.fr')); ?></div>
            </div>
            <div class="days" id="calendar-days">
                <!-- Kalendertage werden per JavaScript eingefügt -->
            </div>
        </div>
        
        <div class="instructions">
            <p><strong><?php echo htmlspecialchars(t('main.instructions.title')); ?>:</strong> <?php echo htmlspecialchars($allowMultipleEntries ? t('main.instructions.allow_multiple') : t('main.instructions.single')); ?></p>
        </div>
    </div>
    
    <!-- Modal für Gipfeli eintragen -->
    <div id="gipfeli-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo htmlspecialchars(t('main.modal.add_title')); ?></h3>
                <button class="close-modal" id="close-modal">&times;</button>
            </div>
            <div id="warning-message" class="status warning" style="display: none;"></div>
            <form id="gipfeli-form">
                <input type="hidden" id="selected-date">
                <div>
                    <label for="name"><?php echo htmlspecialchars(t('main.label.your_name')); ?>:</label>
                    <input type="text" id="name" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" readonly>
                </div>
                <div>
                    <label for="gipfeli-type"><?php echo htmlspecialchars(t('main.label.type')); ?>:</label>
                    <input type="text" id="gipfeli-type" placeholder="<?php echo htmlspecialchars(t('main.placeholder.type')); ?>">
                </div>
                <div class="checkbox-group">
                    <input type="checkbox" id="notify-others" checked>
                    <label for="notify-others"><?php echo htmlspecialchars(t('main.label.notify_others')); ?></label>
                </div>
                <div id="notification-message-container" style="display: block;">
                    <label for="notification-message"><?php echo htmlspecialchars(t('main.label.message_optional')); ?>:</label>
                    <textarea id="notification-message" placeholder="<?php echo htmlspecialchars(t('main.placeholder.message')); ?>"></textarea>
                </div>
                <div>
                    <button type="submit"><?php echo htmlspecialchars(t('main.action.submit_entry')); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal für alle Einträge eines Tages anzeigen -->
    <div id="entries-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="entries-modal-title"><?php echo htmlspecialchars(t('main.modal.entries_title')); ?></h3>
                <button class="close-modal" id="close-entries-modal">&times;</button>
            </div>
            <div id="entries-list" class="entries-list">
                <!-- Einträge werden hier eingefügt -->
            </div>
            <div class="action-buttons">
                <button id="add-to-day-btn"><?php echo htmlspecialchars(t('main.action.add_another')); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Modal für Passwort ändern -->
    <div id="password-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo htmlspecialchars(t('password.change')); ?></h3>
                <button class="close-modal" id="close-password-modal">&times;</button>
            </div>
            <div id="password-status" class="status" style="display: none;"></div>
            <form id="password-form" class="password-form">
                <div class="form-group">
                    <label for="current-password"><?php echo htmlspecialchars(t('password.current')); ?>:</label>
                    <input type="password" id="current-password" required>
                </div>
                <div class="form-group">
                    <label for="new-password"><?php echo htmlspecialchars(t('password.new')); ?>:</label>
                    <input type="password" id="new-password" required minlength="10" maxlength="128">
                    <small><?php echo htmlspecialchars(t('auth.password_requirements')); ?></small>
                    <ul class="password-rules" id="password-rules">
                        <li id="rule-length" class="invalid"><?php echo htmlspecialchars(t('auth.password_rule_length')); ?></li>
                        <li id="rule-classes" class="invalid"><?php echo htmlspecialchars(t('auth.password_rule_classes')); ?></li>
                    </ul>
                </div>
                <div class="form-group">
                    <label for="confirm-password"><?php echo htmlspecialchars(t('password.confirm')); ?>:</label>
                    <input type="password" id="confirm-password" required>
                </div>
                <div class="password-actions">
                    <button type="submit"><?php echo htmlspecialchars(t('password.change')); ?></button>
                    <button type="button" class="btn-secondary" id="cancel-password-btn"><?php echo htmlspecialchars(t('common.cancel')); ?></button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Konfiguration für mehrere Einträge
        const allowMultipleEntries = <?php echo $allowMultipleEntries ? 'true' : 'false'; ?>;
        const showMultipleWarning = <?php echo $showMultipleWarning ? 'true' : 'false'; ?>;
        
        // API URL
        const API_URL = '?api=1&endpoint=';
        const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';
        const i18n = {
            locale: <?php echo json_encode(t('locale.date')); ?>,
            keyboardHint: <?php echo json_encode(t('main.keyboard_hint')); ?>,
            monthNames: [
                <?php echo json_encode(t('main.month.jan')); ?>,
                <?php echo json_encode(t('main.month.feb')); ?>,
                <?php echo json_encode(t('main.month.mar')); ?>,
                <?php echo json_encode(t('main.month.apr')); ?>,
                <?php echo json_encode(t('main.month.may')); ?>,
                <?php echo json_encode(t('main.month.jun')); ?>,
                <?php echo json_encode(t('main.month.jul')); ?>,
                <?php echo json_encode(t('main.month.aug')); ?>,
                <?php echo json_encode(t('main.month.sep')); ?>,
                <?php echo json_encode(t('main.month.oct')); ?>,
                <?php echo json_encode(t('main.month.nov')); ?>,
                <?php echo json_encode(t('main.month.dec')); ?>
            ],
            weekdayNames: [
                <?php echo json_encode(t('main.weekday.mo')); ?>,
                <?php echo json_encode(t('main.weekday.di')); ?>,
                <?php echo json_encode(t('main.weekday.mi')); ?>,
                <?php echo json_encode(t('main.weekday.do')); ?>,
                <?php echo json_encode(t('main.weekday.fr')); ?>
            ],
            loadError: <?php echo json_encode(t('main.status.load_error')); ?>,
            saveSuccess: <?php echo json_encode(t('main.status.save_ok')); ?>,
            saveError: <?php echo json_encode(t('main.status.save_error')); ?>,
            deleteSuccess: <?php echo json_encode(t('main.status.delete_ok')); ?>,
            deleteError: <?php echo json_encode(t('main.status.delete_error')); ?>,
            deleteConfirm: <?php echo json_encode(t('main.confirm.delete')); ?>,
            likeError: <?php echo json_encode(t('main.status.like_error')); ?>,
            invalidResponse: <?php echo json_encode(t('main.status.invalid_response')); ?>,
            passwordRequirements: <?php echo json_encode(t('password.error.requirements')); ?>,
            passwordMismatch: <?php echo json_encode(t('password.error.mismatch')); ?>,
            passwordChangeFailed: <?php echo json_encode(t('main.password.change_failed')); ?>,
            passwordChanged: <?php echo json_encode(t('password.changed_success')); ?>,
            deleteEntry: <?php echo json_encode(t('main.action.delete_entry')); ?>,
            classicEntry: <?php echo json_encode(t('main.entry.classic')); ?>,
            entriesForDate: <?php echo json_encode(t('main.entries_for_date')); ?>,
            moreEntries: <?php echo json_encode(t('main.more_entries')); ?>,
            warningTemplate: <?php echo json_encode(t('main.warning.entries')); ?>,
            warningSingle: <?php echo json_encode(t('main.warning.entry_single')); ?>,
            warningPlural: <?php echo json_encode(t('main.warning.entry_plural')); ?>,
            addForDate: <?php echo json_encode(t('main.modal.add_for_date')); ?>,
            editForDate: <?php echo json_encode(t('main.modal.edit_for_date')); ?>,
            easterMessages: [
                <?php echo json_encode(t('main.easter.1')); ?>,
                <?php echo json_encode(t('main.easter.2')); ?>,
                <?php echo json_encode(t('main.easter.3')); ?>,
                <?php echo json_encode(t('main.easter.4')); ?>,
                <?php echo json_encode(t('main.easter.5')); ?>
            ]
        };
        
        // Aktuelle Daten
        let currentDate = new Date();
        let currentMonth = currentDate.getMonth();
        let currentYear = currentDate.getFullYear();
        
        // Gipfeli-Daten aus der Datenbank
        let gipfeliData = {};
        
        // DOM-Elemente
        const calendarDays = document.getElementById('calendar-days');
        const currentMonthElement = document.getElementById('current-month');
        const modal = document.getElementById('gipfeli-modal');
        const entriesModal = document.getElementById('entries-modal');
        const passwordModal = document.getElementById('password-modal');
        const entriesModalTitle = document.getElementById('entries-modal-title');
        const entriesList = document.getElementById('entries-list');
        const gipfeliForm = document.getElementById('gipfeli-form');
        const passwordForm = document.getElementById('password-form');
        const selectedDateInput = document.getElementById('selected-date');
        const loadingElement = document.getElementById('loading');
        const statusMessage = document.getElementById('status-message');
        const passwordStatus = document.getElementById('password-status');
        const warningMessage = document.getElementById('warning-message');
        const closeModalBtn = document.getElementById('close-modal');
        const closeEntriesModalBtn = document.getElementById('close-entries-modal');
        const closePasswordModalBtn = document.getElementById('close-password-modal');
        const cancelPasswordBtn = document.getElementById('cancel-password-btn');
        const logoutBtn = document.getElementById('logout-btn');
        const changePasswordBtn = document.getElementById('change-password-btn');
        const notifyCheckbox = document.getElementById('notify-others');
        const notificationMessageContainer = document.getElementById('notification-message-container');
        const addToDayBtn = document.getElementById('add-to-day-btn');
        const newPasswordInput = document.getElementById('new-password');
        const ruleLength = document.getElementById('rule-length');
        const ruleClasses = document.getElementById('rule-classes');
        
        // Monatsnamen
        const monthNames = i18n.monthNames;
        
        // Event-Listener für Benachrichtigungsoptionen
        notifyCheckbox.addEventListener('change', function() {
            notificationMessageContainer.style.display = this.checked ? 'block' : 'none';
        });
        notificationMessageContainer.style.display = notifyCheckbox.checked ? 'block' : 'none';

        function checkPasswordPolicy(password) {
            const lengthOk = password.length >= 10 && password.length <= 128;
            const classes = [
                /[a-z]/.test(password),
                /[A-Z]/.test(password),
                /[0-9]/.test(password),
                /[^a-zA-Z0-9]/.test(password)
            ].filter(Boolean).length;
            const classesOk = classes >= 3;
            return { lengthOk, classesOk, valid: lengthOk && classesOk };
        }

        function setRuleState(element, isValid) {
            element.classList.toggle('valid', isValid);
            element.classList.toggle('invalid', !isValid);
        }

        newPasswordInput.addEventListener('input', () => {
            const policy = checkPasswordPolicy(newPasswordInput.value);
            setRuleState(ruleLength, policy.lengthOk);
            setRuleState(ruleClasses, policy.classesOk);
        });
        
        // Event-Listener für Logout
        logoutBtn.addEventListener('click', logout);
        
        // Event-Listener für "Weiteren Eintrag hinzufügen"
        addToDayBtn.addEventListener('click', function() {
            const date = addToDayBtn.dataset.date;
            if (date) {
                entriesModal.style.display = 'none';
                openAddGipfeliModal(date);
            }
        });
        
        // Event-Listener für Passwort ändern
        changePasswordBtn.addEventListener('click', function(e) {
            e.preventDefault();
            openPasswordModal();
        });
        
        // Initialisierung
        loadEntries();
        setupAutoRefresh();
        
        // Tastaturhinweis hinzufügen
        const keyboardHint = document.createElement('div');
        keyboardHint.className = 'keyboard-hint';
        keyboardHint.textContent = i18n.keyboardHint;
        document.querySelector('.calendar').after(keyboardHint);
        
        // Hilfsfunktion zur Statusanzeige
        function showStatus(message, type) {
            statusMessage.textContent = message;
            statusMessage.className = 'status ' + type;
            statusMessage.style.display = 'block';
            
            setTimeout(() => {
                statusMessage.style.display = 'none';
            }, 3000);
        }
        
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
        
        // Logout-Funktion
        async function logout() {
            try {
                await fetch(API_URL + 'logout', { 
                    method: 'POST',
                    headers: {
                        'X-CSRF-Token': CSRF_TOKEN
                    }
                });
                window.location.href = '?page=start';
            } catch (error) {
                console.error('Logout failed:', error);
            }
        }
        
        // Verbesserte loadEntries-Funktion mit Ladeindikator und Fehlerbehandlung
        async function loadEntries() {
            loadingElement.style.display = 'block';
            calendarDays.classList.add('loading-data');
            
            try {
                const response = await fetch(API_URL + 'entries');
                if (!response.ok) {
                    throw new Error(i18n.loadError + ': ' + response.status);
                }
                
                // Rohdaten speichern
                const rawData = await response.json();
                console.log("Raw data from server:", rawData);
                
                // Rohdaten prüfen und konvertieren
                if (rawData.error) {
                    throw new Error(rawData.error);
                }
                
                // Daten übernehmen
                gipfeliData = rawData;
                
             // Daten überprüfen
                for (const date in gipfeliData) {
                    console.log(`Data for ${date}:`, {
                        isArray: Array.isArray(gipfeliData[date]),
                        entryCount: Array.isArray(gipfeliData[date]) ? gipfeliData[date].length : 1,
                        data: gipfeliData[date]
                    });
                }
                
                // Kalender neu rendern
                renderCalendar();
            } catch (error) {
                showStatus(i18n.loadError + ': ' + error.message, 'error');
                console.error(i18n.loadError, error);
            } finally {
                loadingElement.style.display = 'none';
                calendarDays.classList.remove('loading-data');
            }
        }
        
        // Funktion zum Wechseln des Monats mit Animation
        async function changeMonth(direction) {
            // Animation beim Monatswechsel
            calendarDays.style.opacity = '0.3';
            calendarDays.style.transition = 'opacity 0.3s ease';
            
            // Monat ändern
            if (direction === 'prev') {
                currentMonth--;
                if (currentMonth < 0) {
                    currentMonth = 11;
                    currentYear--;
                }
            } else {
                currentMonth++;
                if (currentMonth > 11) {
                    currentMonth = 0;
                    currentYear++;
                }
            }
            
            // Aktualisiere den Monatstitel sofort
            currentMonthElement.textContent = `${monthNames[currentMonth]} ${currentYear}`;
            
            // Lade die Daten für den neuen Monat
            await loadEntries();
            
            // Animationsende
            setTimeout(() => {
                calendarDays.style.opacity = '1';
            }, 50);
        }
        
        // Gipfeli-Eintrag speichern
        async function saveEntry(date, name, gipfeliType, notify = false, message = '') {
            loadingElement.style.display = 'block';
            
            try {
                // Ausgabe zum Debuggen
                console.log("Saving entry:", { date, name, gipfeliType, notify, message });
                console.log("Current entries for this date:", gipfeliData[date]);
                
                const response = await fetch(API_URL + 'entries', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify({ 
                        date, 
                        name, 
                        gipfeliType,
                        notify,
                        message
                    })
                });
                
                if (!response.ok) {
                    throw new Error(i18n.saveError + ': ' + response.status);
                }
                
                const result = await response.json();
                console.log("Server response:", result);
                
                if (!result.success || !result.entry) {
                    throw new Error(i18n.invalidResponse);
                }
                
                // Die gipfeliData korrekt aktualisieren
                if (gipfeliData[date]) {
                    // Wenn bereits Einträge für dieses Datum existieren
                    if (Array.isArray(gipfeliData[date])) {
                        // Falls es schon ein Array ist, neuen Eintrag hinzufügen
                        gipfeliData[date].push(result.entry);
                        console.log("Added to existing array of entries:", gipfeliData[date]);
                    } else {
                        // Falls es ein einzelner Eintrag ist, zu einem Array konvertieren
                        gipfeliData[date] = [gipfeliData[date], result.entry];
                        console.log("Converted single entry to array:", gipfeliData[date]);
                    }
                } else {
                    // Erster Eintrag für dieses Datum
                    gipfeliData[date] = result.entry;
                    console.log("Added first entry for this date:", gipfeliData[date]);
                }
                
                // Neurendern forcieren
                renderCalendar();
                showStatus(i18n.saveSuccess, 'success');
                
                // Statt die Einträge direkt nach dem Speichern neu zu laden, laden wir alle Einträge neu
                await loadEntries();
                
            } catch (error) {
                showStatus(i18n.saveError + ': ' + error.message, 'error');
                console.error(i18n.saveError, error);
            } finally {
                loadingElement.style.display = 'none';
            }
        }
        
        // Gipfeli-Eintrag löschen
        async function deleteEntry(date, entryId) {
            if (!confirm(i18n.deleteConfirm)) {
                return;
            }
            
            loadingElement.style.display = 'block';
            
            try {
                const response = await fetch(API_URL + 'delete', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify({ date, entryId })
                });
                
                if (!response.ok) {
                    throw new Error(i18n.deleteError);
                }
                
                // Aktualisiere die lokalen Daten
                if (Array.isArray(gipfeliData[date])) {
                    // Bei mehreren Einträgen: Entferne den mit der ID
                    const index = gipfeliData[date].findIndex(entry => entry.id === entryId);
                    if (index !== -1) {
                        gipfeliData[date].splice(index, 1);
                        
                        // Wenn nur noch ein Eintrag übrig ist, konvertiere zurück zu einem Objekt
                        if (gipfeliData[date].length === 1) {
                            gipfeliData[date] = gipfeliData[date][0];
                       } else if (gipfeliData[date].length === 0) {
                            // Wenn keine Einträge mehr übrig sind, entferne den Tag
                            delete gipfeliData[date];
                        }
                    }
                } else {
                    // Bei einem einzelnen Eintrag: Entferne den ganzen Tag
                    delete gipfeliData[date];
                }
                
                renderCalendar();
                showStatus(i18n.deleteSuccess, 'success');
                
                // Schließe das Einträge-Modal und öffne es neu, falls es geöffnet war
                if (entriesModal.style.display === 'block') {
                    entriesModal.style.display = 'none';
                    
                    // Wenn es noch Einträge gibt, zeige sie an
                    if (gipfeliData[date]) {
                        showEntriesForDay(date);
                    }
                }
            } catch (error) {
                showStatus(i18n.deleteError + ': ' + error.message, 'error');
                console.error(i18n.deleteError, error);
            } finally {
                loadingElement.style.display = 'none';
            }
        }
        
        // Like hinzufügen oder entfernen
        async function toggleLike(date, entryId, event) {
            if (event) event.stopPropagation();
            
            loadingElement.style.display = 'block';
            
            try {
                const response = await fetch(API_URL + 'like', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify({ date, entryId })
                });
                
                if (!response.ok) {
                    throw new Error(i18n.likeError);
                }
                
                await loadEntries(); // Neu laden, um aktualisierte Like-Infos zu erhalten
                
                // Wenn das Einträge-Modal geöffnet ist, aktualisiere es
                if (entriesModal.style.display === 'block') {
                    showEntriesForDay(date);
                }
            } catch (error) {
                showStatus(error.message, 'error');
                console.error(i18n.likeError, error);
            } finally {
                loadingElement.style.display = 'none';
            }
        }
        
        // Passwort ändern
        async function changePassword(currentPassword, newPassword) {
            loadingElement.style.display = 'block';
            
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
                    throw new Error(data.error || i18n.passwordChangeFailed);
                }
                
                showPasswordStatus(i18n.passwordChanged, 'success');
                passwordForm.reset();
            } catch (error) {
                showPasswordStatus(error.message, 'error');
                console.error(i18n.passwordChangeFailed, error);
            } finally {
                loadingElement.style.display = 'none';
            }
        }
        
        // Event-Listener für Monatswechsel
        document.getElementById('prev-month').addEventListener('click', () => changeMonth('prev'));
        document.getElementById('next-month').addEventListener('click', () => changeMonth('next'));
        
        // Event-Listener für Modal-Schließen
        closeModalBtn.addEventListener('click', () => {
            modal.style.display = 'none';
        });
        
        closeEntriesModalBtn.addEventListener('click', () => {
            entriesModal.style.display = 'none';
        });
        
        closePasswordModalBtn.addEventListener('click', () => {
            passwordModal.style.display = 'none';
        });
        
        cancelPasswordBtn.addEventListener('click', () => {
            passwordModal.style.display = 'none';
        });
        
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
            if (event.target === entriesModal) {
                entriesModal.style.display = 'none';
            }
            if (event.target === passwordModal) {
                passwordModal.style.display = 'none';
            }
        });
        
        // Event-Listener für Passwort-Formular
        passwordForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            // Validierung
            const policy = checkPasswordPolicy(newPassword);
            if (!policy.valid) {
                showPasswordStatus(i18n.passwordRequirements, 'error');
                return;
            }
            
            if (newPassword !== confirmPassword) {
                showPasswordStatus(i18n.passwordMismatch, 'error');
                return;
            }
            
            changePassword(currentPassword, newPassword);
        });
        
        // Event-Listener für Formular
        gipfeliForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const date = selectedDateInput.value;
            const name = document.getElementById('name').value.trim();
            const gipfeliType = document.getElementById('gipfeli-type').value.trim();
            const notify = document.getElementById('notify-others').checked;
            const message = document.getElementById('notification-message').value.trim();
            
            if (name) {
                saveEntry(date, name, gipfeliType, notify, message);
                modal.style.display = 'none';
            }
        });
        
        // Prüfen, ob ein Tag mehrere Einträge hat
        function hasMultipleEntries(dateString) {
            return Array.isArray(gipfeliData[dateString]) && gipfeliData[dateString].length > 1;
        }
        
        // Zählt die Anzahl der Einträge für einen Tag
        function countEntries(dateString) {
            if (!gipfeliData[dateString]) return 0;
            return Array.isArray(gipfeliData[dateString]) ? gipfeliData[dateString].length : 1;
        }
        
        // Funktion zum Loggen des Status des Tages (Debugging)
        function logDayStatus(dateString) {
            console.log(`Status for ${dateString}:`, {
                hasData: !!gipfeliData[dateString],
                isArray: Array.isArray(gipfeliData[dateString]),
                entries: gipfeliData[dateString],
                entryCount: countEntries(dateString)
            });
        }
        
        // Funktion zum Überprüfen und automatischen Aktualisieren des Kalenders nach einer bestimmten Zeit
        function setupAutoRefresh() {
            const REFRESH_INTERVAL = 5 * 60 * 1000; // 5 Minuten in Millisekunden
            
            setInterval(async () => {
                console.log("Auto-Refresh: Lade Daten neu...");
                await loadEntries();
            }, REFRESH_INTERVAL);
            
            // Lade Daten neu, wenn der Tab wieder aktiv wird
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    console.log("Tab wieder aktiv: Lade Daten neu...");
                    loadEntries();
                }
            });
        }
        
        // Tastaturnavigation für Monatswechsel hinzufügen
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                changeMonth('prev');
            } else if (e.key === 'ArrowRight') {
                changeMonth('next');
            }
        });

        // Kalender rendern
        function renderCalendar() {
            // Log der aktuellen Einträge für Debugging
            console.log("Current gipfeliData in renderCalendar:", gipfeliData);	

            // Monat und Jahr anzeigen
            currentMonthElement.textContent = `${monthNames[currentMonth]} ${currentYear}`;
            
            // Kalender leeren
            calendarDays.innerHTML = '';
            
            // Ersten Tag des Monats ermitteln
            const firstDayOfMonth = new Date(currentYear, currentMonth, 1);
            
            // Wochentag des ersten Tags im Monat (0 = Sonntag, 1 = Montag, ..., 6 = Samstag)
            let firstWeekday = firstDayOfMonth.getDay();
            
            // Anpassen für europäische Woche (Montag = 0, ..., Sonntag = 6)
            firstWeekday = firstWeekday === 0 ? 6 : firstWeekday - 1;
            
            // Letzten Tag des Monats ermitteln
            const lastDay = new Date(currentYear, currentMonth + 1, 0).getDate();
            
            // Aktuelles Datum für Vergleich
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Wochentage-Namen
            const weekdayNames = i18n.weekdayNames;
            
            // Leere Zellen für Tage vor dem ersten Tag des Monats
            for (let i = 0; i < firstWeekday; i++) {
                // Nur Mo-Fr anzeigen (0-4), also keine leeren Zellen für Sa/So
                if (i < 5) {
                    const emptyCell = document.createElement('div');
                    emptyCell.className = 'day empty';
                    calendarDays.appendChild(emptyCell);
                }
            }
            
            // Tage des Monats einfügen
            for (let day = 1; day <= lastDay; day++) {
                const date = new Date(currentYear, currentMonth, day);
                const dayOfWeek = date.getDay();
                
                // Europäischen Wochentag berechnen (0 = Montag, ..., 6 = Sonntag)
                const euroWeekday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
                
                // Nur Wochentage Mo-Fr anzeigen (0-4)
                if (euroWeekday >= 5) {
                    continue; // Samstag und Sonntag überspringen
                }
                
                const dayCell = document.createElement('div');
                dayCell.className = 'day';
                
                const dateString = formatDate(date);
                const isPastDay = date < today;
                
                // Vergangene Tage kennzeichnen
                if (isPastDay) {
                    dayCell.classList.add('past-day');
                } else {
                    // Nur Arbeitstage in der Zukunft klickbar machen
                    dayCell.addEventListener('click', () => handleDayClick(day, dateString));
                }
                
                // Tagesnummer anzeigen
                const dayNumber = document.createElement('div');
                dayNumber.className = 'day-number';
                dayNumber.innerHTML = `<span class="mobile-weekday">${weekdayNames[euroWeekday]}: </span>${day}`;
                dayCell.appendChild(dayNumber);
                
                // Prüfen, ob für diesen Tag Gipfeli eingetragen sind
                if (gipfeliData[dateString]) {
                    dayCell.classList.add('has-gipfeli');
                    
                    // Bei mehreren Einträgen besonders kennzeichnen
                    if (hasMultipleEntries(dateString)) {
                        dayCell.classList.add('has-multiple');
                    }
                    
                    // Log zum Debuggen
                    console.log(`Rendering entries for ${dateString}:`, gipfeliData[dateString]);
                    
                    // Container für alle Einträge
                    const entriesContainer = document.createElement('div');
                    entriesContainer.className = 'gipfeli-entries';
                    
                    // Alle Einträge verarbeiten
                    const entries = Array.isArray(gipfeliData[dateString]) 
                        ? gipfeliData[dateString] 
                        : [gipfeliData[dateString]];
                    
                    // Maximal zwei Einträge direkt anzeigen
                    const maxVisible = 2;
                    const visibleEntries = entries.slice(0, maxVisible);
                    const hiddenCount = Math.max(0, entries.length - maxVisible);
                    
                    // Sichtbare Einträge anzeigen
                    visibleEntries.forEach(entry => {
                        const entryElement = createEntryElement(entry, dateString);
                        entriesContainer.appendChild(entryElement);
                    });
                    
                    // Wenn es mehr Einträge gibt, einen Indikator anzeigen
                    if (hiddenCount > 0) {
                        const moreIndicator = document.createElement('div');
                        moreIndicator.className = 'gipfeli-entry';
                        moreIndicator.innerHTML = `<div class="gipfeli-info">
                            <span>${i18n.moreEntries.replace('%d', hiddenCount)}</span>
                        </div>`;
                        entriesContainer.appendChild(moreIndicator);
                    }
                    
                    dayCell.appendChild(entriesContainer);
                }
                
                calendarDays.appendChild(dayCell);
            }
        }
        
        // Eintragselement erstellen
        function createEntryElement(entry, dateString) {
            const gipfeliEntry = document.createElement('div');
            gipfeliEntry.className = 'gipfeli-entry';
            
            // Info-Zeile mit Name und Typ
            const gipfeliInfo = document.createElement('div');
            gipfeliInfo.className = 'gipfeli-info';
            
            const entryText = document.createElement('span');
            entryText.textContent = entry.name;
            if (entry.type) {
                entryText.textContent += `: ${entry.type}`;
            }
            gipfeliInfo.appendChild(entryText);
            
            // Löschen-Button nur anzeigen, wenn es vom aktuellen Benutzer ist
            if (entry.name === "<?php echo htmlspecialchars($_SESSION['user_name']); ?>") {
                const deleteBtn = document.createElement('button');
                deleteBtn.className = 'delete-btn';
                deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                deleteBtn.title = i18n.deleteEntry;
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    deleteEntry(dateString, entry.id);
                });
                gipfeliInfo.appendChild(deleteBtn);
            }
            
            gipfeliEntry.appendChild(gipfeliInfo);
            
            // Aktionen-Zeile mit Like-Button und Anzahl
            const gipfeliActions = document.createElement('div');
            gipfeliActions.className = 'gipfeli-actions';
            
            // Like-Button mit Tooltip für die Namen
            const likeContainer = document.createElement('div');
            likeContainer.className = 'like-tooltip';
            
            const likeBtn = document.createElement('button');
            likeBtn.className = 'like-btn';
            if (entry.user_liked) {
                likeBtn.classList.add('liked');
            }
            likeBtn.innerHTML = `<i class="fas fa-heart"></i> <span class="like-count">${entry.like_count || 0}</span>`;
            likeBtn.addEventListener('click', (e) => toggleLike(dateString, entry.id, e));
            likeContainer.appendChild(likeBtn);
            
            // Tooltip mit Namen der Liker
            if (entry.likes && entry.likes.length > 0) {
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip-content';
                tooltip.textContent = entry.likes.map(like => like.user_name).join(', ');
                likeContainer.appendChild(tooltip);
            }
            
            gipfeliActions.appendChild(likeContainer);
            gipfeliEntry.appendChild(gipfeliActions);
            
            return gipfeliEntry;
        }
        
        // Datum formatieren (YYYY-MM-DD)
        function formatDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Klick auf einen Tag im Kalender verarbeiten
        function handleDayClick(day, dateString) {
            // Wenn bereits Einträge für diesen Tag existieren
            if (gipfeliData[dateString]) {
                // Bei mehreren Einträgen oder wenn mehrere Einträge erlaubt sind, zeige alle an
                if (hasMultipleEntries(dateString) || allowMultipleEntries) {
                    showEntriesForDay(dateString);
                } else {
                    // Bei einem einzelnen Eintrag und keine mehreren erlaubt, direktes Bearbeiten
                    const entry = gipfeliData[dateString];
                    // Nur eigene Einträge bearbeiten
                    if (entry.name === "<?php echo htmlspecialchars($_SESSION['user_name']); ?>") {
                        openEditGipfeliModal(day, dateString, entry);
                    } else {
                        // Wenn es nicht der eigene Eintrag ist, nur anzeigen
                        showEntriesForDay(dateString);
                    }
                }
            } else {
                // Wenn noch kein Eintrag existiert, neuen erstellen
                openAddGipfeliModal(dateString);
            }
        }
        
        // Modal zum Anzeigen aller Einträge eines Tages
        function showEntriesForDay(dateString) {
            entriesList.innerHTML = '';
            const entries = Array.isArray(gipfeliData[dateString]) ? gipfeliData[dateString] : [gipfeliData[dateString]];
            
            const date = new Date(dateString);
            const formattedDate = date.toLocaleDateString(i18n.locale, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            entriesModalTitle.textContent = i18n.entriesForDate.replace('%s', formattedDate);
            
            // Speichere das Datum für den "Weiteren Eintrag hinzufügen"-Button
            addToDayBtn.dataset.date = dateString;
            
            // Zeige oder verstecke den Button je nachdem, ob mehrere Einträge erlaubt sind
            addToDayBtn.style.display = allowMultipleEntries ? 'block' : 'none';
            
            entries.forEach(entry => {
                const entryItem = document.createElement('div');
                entryItem.className = 'entry-item';
                
                const entryHeader = document.createElement('div');
                entryHeader.style.display = 'flex';
                entryHeader.style.justifyContent = 'space-between';
                
                const entryName = document.createElement('div');
                entryName.style.fontWeight = 'bold';
                entryName.textContent = entry.name;
                entryHeader.appendChild(entryName);
                
                const actionButtons = document.createElement('div');
                
                // Nur eigene Einträge können gelöscht werden
                if (entry.name === "<?php echo htmlspecialchars($_SESSION['user_name']); ?>") {
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'delete-btn';
                    deleteBtn.innerHTML = '<i class="fas fa-times"></i>';
                    deleteBtn.title = i18n.deleteEntry;
                    deleteBtn.style.marginLeft = '5px';
                    deleteBtn.addEventListener('click', () => {
                        deleteEntry(dateString, entry.id);
                    });
                    actionButtons.appendChild(deleteBtn);
                }
                
                entryHeader.appendChild(actionButtons);
                entryItem.appendChild(entryHeader);
                
                const entryType = document.createElement('div');
                entryType.textContent = entry.type || i18n.classicEntry;
                entryItem.appendChild(entryType);
                
                const entryLikes = document.createElement('div');
                entryLikes.style.marginTop = '5px';
                entryLikes.style.display = 'flex';
                entryLikes.style.alignItems = 'center';
                
                const likeBtn = document.createElement('button');
                likeBtn.className = 'like-btn';
                if (entry.user_liked) {
                    likeBtn.classList.add('liked');
                }
                likeBtn.innerHTML = `<i class="fas fa-heart"></i> <span class="like-count">${entry.like_count || 0}</span>`;
                likeBtn.addEventListener('click', (e) => toggleLike(dateString, entry.id, e));
                entryLikes.appendChild(likeBtn);
                
                if (entry.likes && entry.likes.length > 0) {
                    const likesText = document.createElement('span');
                    likesText.style.marginLeft = '5px';
                    likesText.style.fontSize = '12px';
                    likesText.style.color = '#666';
                    likesText.textContent = entry.likes.map(like => like.user_name).join(', ');
                    entryLikes.appendChild(likesText);
                }
                
                entryItem.appendChild(entryLikes);
                entriesList.appendChild(entryItem);
            });
            
            entriesModal.style.display = 'block';
        }
        
        // Modal zum Ändern des Passworts öffnen
        function openPasswordModal() {
            // Formular zurücksetzen
            passwordForm.reset();
            passwordStatus.style.display = 'none';
            setRuleState(ruleLength, false);
            setRuleState(ruleClasses, false);
            
            // Modal anzeigen
            passwordModal.style.display = 'block';
        }
        
        // Modal zum Eintragen eines neuen Gipfeli öffnen
        function openAddGipfeliModal(dateString) {
            const selectedDate = new Date(dateString);
            
            // Nur für Arbeitstage und nicht für vergangene Tage
            const dayOfWeek = selectedDate.getDay();
            const isPastDay = selectedDate < new Date().setHours(0, 0, 0, 0);
            
            // Prüfen, ob der Tag ein Arbeitstag ist (Mo-Fr) und nicht in der Vergangenheit liegt
            if (dayOfWeek >= 1 && dayOfWeek <= 5 && !isPastDay) {
                selectedDateInput.value = dateString;
                
                // Warnung anzeigen, wenn bereits Einträge für diesen Tag existieren
                warningMessage.style.display = 'none';
                if (gipfeliData[dateString] && showMultipleWarning) {
                    const entryCount = countEntries(dateString);
                    const noun = entryCount === 1 ? i18n.warningSingle : i18n.warningPlural;
                    warningMessage.textContent = i18n.warningTemplate.replace('%d', entryCount).replace('%s', noun);
                    warningMessage.style.display = 'block';
                }
                
                // Formular zurücksetzen
                document.getElementById('gipfeli-type').value = '';
                document.getElementById('notify-others').checked = true;
                document.getElementById('notification-message').value = '';
                document.getElementById('notification-message-container').style.display = 'block';
                
                // Aktualisieren des Modaltitels mit Monatsinformation
                updateModalTitle(dateString);
                
                modal.style.display = 'block';
            }
        }
        
       // Aktuellen Monat als Titel in der Datums-Auswahl
        function updateModalTitle(date) {
            const selectedDate = new Date(date);
            const formattedDate = selectedDate.toLocaleDateString(i18n.locale, { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            
            // Setze den Titel im Modal
            const modalTitle = document.querySelector('.modal-title');
            modalTitle.textContent = i18n.addForDate.replace('%s', formattedDate);
            
            // Füge Monatsinformation in einem kleinen Untertitel hinzu
            const modalSubtitle = document.createElement('small');
            modalSubtitle.style.display = 'block';
            modalSubtitle.style.marginTop = '5px';
            modalSubtitle.style.fontWeight = 'normal';
            modalSubtitle.textContent = `${monthNames[selectedDate.getMonth()]} ${selectedDate.getFullYear()}`;
            
            // Entferne ggf. vorhandenen Untertitel
            const existingSubtitle = modalTitle.querySelector('small');
            if (existingSubtitle) {
                existingSubtitle.remove();
            }
            
            modalTitle.appendChild(modalSubtitle);
        }
        
        // Modal zum Bearbeiten eines bestehenden Gipfeli-Eintrags öffnen
        function openEditGipfeliModal(day, dateString, entry) {
            const selectedDate = new Date(dateString);
            
            // Nur für Arbeitstage und nicht für vergangene Tage
            const dayOfWeek = selectedDate.getDay();
            const isPastDay = selectedDate < new Date().setHours(0, 0, 0, 0);
            
            // Prüfen, ob der Tag ein Arbeitstag ist (Mo-Fr) und nicht in der Vergangenheit liegt
            if (dayOfWeek >= 1 && dayOfWeek <= 5 && !isPastDay) {
                selectedDateInput.value = dateString;
                
               // Bestehende Daten in das Formular übernehmen
                document.getElementById('gipfeli-type').value = entry.type || '';
                document.getElementById('notify-others').checked = true;
                document.getElementById('notification-message').value = '';
                document.getElementById('notification-message-container').style.display = 'block';
                
                // Warnung ausblenden
                warningMessage.style.display = 'none';
                
                // Modaltitel anpassen mit aktualisierter Funktion
                updateModalTitle(dateString);
                
                // Bearbeitungsmodus im Titel anzeigen
                const modalTitle = document.querySelector('.modal-title');
                const selectedDateText = new Date(dateString).toLocaleDateString(i18n.locale, {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                modalTitle.textContent = i18n.editForDate.replace('%s', selectedDateText);
                
                modal.style.display = 'block';
            }
        }
// Easter Egg - Kombinierte Desktop und Mobile Lösung
let konamiCode = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];
let konamiIndex = 0;

// Konami Code Handler für Desktop
document.addEventListener('keydown', function(e) {
    // Überprüfen, ob die Taste dem nächsten Zeichen im Konami-Code entspricht
    if (e.key === konamiCode[konamiIndex]) {
        konamiIndex++;
        
        // Wenn der vollständige Code eingegeben wurde
        if (konamiIndex === konamiCode.length) {
            activateEasterEgg();
            konamiIndex = 0; // Zurücksetzen für nächste Eingabe
        }
    } else {
        konamiIndex = 0; // Zurücksetzen bei falscher Eingabe
    }
});

// Easter Egg für Mobilgeräte - Langes Drücken auf Monat + Schütteln
function setupMobileEasterEgg() {
    // 1. Langes Drücken auf den Monatsnamen
    const monthHeader = document.getElementById('current-month');
    
    if (monthHeader) {
        let pressTimer;
        let longPressActive = false;
        
        // Start des langen Drückens
        monthHeader.addEventListener('touchstart', function(e) {
            longPressActive = false;
            pressTimer = setTimeout(function() {
                longPressActive = true;
                // Visuelles Feedback
                monthHeader.style.transition = 'color 0.3s';
                monthHeader.style.color = '#FFD700'; // Gold-Farbe als Feedback
            }, 1500); // 1,5 Sekunden drücken
        });
        
        // Abbruch, wenn der Benutzer den Finger bewegt
        monthHeader.addEventListener('touchmove', function(e) {
            clearTimeout(pressTimer);
        });
        
        // Ende des Drückens
        monthHeader.addEventListener('touchend', function(e) {
            clearTimeout(pressTimer);
            
            if (longPressActive) {
                activateEasterEgg();
                // Farbe zurücksetzen
                setTimeout(() => {
                    monthHeader.style.color = '';
                }, 500);
            }
        });
    }
}

// Setup beim Laden der Seite
document.addEventListener('DOMContentLoaded', setupMobileEasterEgg);

// Der Hauptteil des Easter Eggs - Gipfeli-Regen
function activateEasterEgg() {
    // Massiver Gipfeli-Regen erzeugen - angepasst für Performance auf mobilen Geräten
    const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
    const gipfeliCount = isMobile ? 200 : 500; // Weniger Gipfeli auf mobilen Geräten für bessere Performance
    const gipfeliEmojis = ['🥐', '🥐', '🥐', '🥐', '🥐', '🥐', '🥐', '🥖', '🍞'];
    
    for (let i = 0; i < gipfeliCount; i++) {
        let gipfeli = document.createElement('div');
        const randomEmoji = gipfeliEmojis[Math.floor(Math.random() * gipfeliEmojis.length)];
        gipfeli.innerHTML = randomEmoji;
        gipfeli.style.position = 'fixed';
        gipfeli.style.left = Math.random() * 100 + 'vw';
        gipfeli.style.top = (Math.random() * -300) + 'px';
        gipfeli.style.fontSize = (Math.random() * (isMobile ? 20 : 30) + 15) + 'px'; // Kleinere Größen auf Mobilgeräten
        gipfeli.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
        gipfeli.style.zIndex = '9999';
        gipfeli.style.opacity = Math.random() * 0.4 + 0.6;
        
        // Langsamere Fallgeschwindigkeiten
        const duration = Math.random() * 8 + 8; // 8-16 Sekunden
        gipfeli.style.transition = 'top ' + duration + 's linear, transform ' + duration + 's ease-in-out';
        
        document.body.appendChild(gipfeli);
        
        // Verzögerter Start für gestaffelten Effekt
        setTimeout(() => {
            gipfeli.style.top = '110vh';
            gipfeli.style.transform = 'rotate(' + (Math.random() * 720 - 360) + 'deg)';
        }, Math.random() * 2000);
        
        // Verzögerte Entfernung der Elemente
        setTimeout(() => {
            if (document.body.contains(gipfeli)) {
                document.body.removeChild(gipfeli);
            }
        }, duration * 1000 + 2000);
    }
    
    // Lustige Nachricht
    const messages = i18n.easterMessages;
    const randomMessage = messages[Math.floor(Math.random() * messages.length)];
    
    const message = document.createElement('div');
    message.textContent = randomMessage;
    message.style.position = 'fixed';
    message.style.top = '50%';
    message.style.left = '50%';
    message.style.transform = 'translate(-50%, -50%)';
    message.style.background = 'rgba(255, 255, 255, 0.9)';
    message.style.padding = (isMobile ? '15px 20px' : '20px 30px');
    message.style.borderRadius = '12px';
    message.style.boxShadow = '0 0 20px rgba(0,0,0,0.4)';
    message.style.zIndex = '10000';
    message.style.fontWeight = 'bold';
    message.style.fontSize = (isMobile ? '20px' : '28px');
    message.style.textAlign = 'center';
    message.style.color = 'var(--primary-color)';
    
    document.body.appendChild(message);
    
    // Nachricht mit Animation entfernen
    setTimeout(() => {
        message.style.transition = 'transform 0.7s ease-in, opacity 0.7s ease-in';
        message.style.transform = 'translate(-50%, -50%) scale(1.8)';
        message.style.opacity = '0';
        
        setTimeout(() => {
            if (document.body.contains(message)) {
                document.body.removeChild(message);
            }
        }, 700);
    }, 4000);
    
    // Vibration auf mobilen Geräten, falls unterstützt
    if (isMobile && 'vibrate' in navigator) {
        // Kurzes Vibrationsmuster
        navigator.vibrate([100, 50, 100, 50, 200]);
    }
}

    </script>
</body>
</html>

