<?php
// pages/stats.php - Statistikseite für den Gipfeli-Koordinator
// Diese Seite zeigt Statistiken über Gipfeli-Einträge
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(t('meta.lang')); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('stats.title')); ?> - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <?php if (!empty($config['app_favicon'])): ?>
    <link rel="icon" href="<?php echo htmlspecialchars(cacheBustUrl($config['app_favicon'])); ?>">
    <?php endif; ?>
    <style>
        :root {
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
            max-width: 650px; /* Fixe Breite für den Container */
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
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
        h1, h2, h3 {
            color: #4a4a4a;
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
            color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
        }
        nav ul li a.active {
            color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            font-weight: bold;
        }
        .app-title {
            color: white;
            margin: 0;
            font-size: 1.35rem;
            line-height: 1.2;
        }
        .stats-container {
            display: grid;
            grid-template-columns: 1fr 1fr; /* Genau 2 Spalten */
            gap: 20px;
            margin-top: 20px;
        }
        .stats-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            min-height: 250px; /* Minimale Höhe für gleichmäßiges Aussehen */
        }
        /* Wochentagsstatistik nimmt die gesamte Breite ein */
        .stats-card.full-width {
            grid-column: span 2;
            min-height: auto;
        }
        .stats-card h3 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            font-size: 18px;
        }
        .stats-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        .stats-item {
            display: flex;
            flex-direction: column;
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .stats-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        .stats-name {
            font-weight: bold;
            flex: 1;
        }
        .stats-value {
            color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            font-weight: bold;
            margin-left: 10px;
            text-align: right;
            min-width: 40px;
        }
        .stats-secondary {
            color: #6c757d;
            font-size: 0.9em;
            text-align: right;
            min-width: 70px;
        }
        .loading {
            text-align: center;
            margin: 50px 0;
            font-style: italic;
            color: #6c757d;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .btn-logout {
            background-color: transparent;
            border: 1px solid white;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-logout:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
        .stats-bar-container {
            height: 8px;
            background-color: #f1f1f1;
            border-radius: 4px;
            margin-top: 5px;
            width: 100%;
        }
        .stats-bar {
            height: 100%;
            background-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            border-radius: 4px;
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
        
        /* Form-Styling für Passwort-Modal */
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
        button {
            background-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            color: white;
            border: none;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: <?php echo htmlspecialchars(adjustBrightness($config['app_primary_color'], -20)); ?>;
        }
        .btn-secondary {
            background-color: <?php echo htmlspecialchars($config['app_secondary_color']); ?>;
        }
        .btn-secondary:hover {
            background-color: <?php echo htmlspecialchars(adjustBrightness($config['app_secondary_color'], -20)); ?>;
        }
        small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        
        /* Responsive Design für mobile Geräte */
        @media (max-width: 768px) {
            .stats-container {
                grid-template-columns: 1fr; /* Eine Spalte auf kleinen Bildschirmen */
            }
            .stats-card.full-width {
                grid-column: 1;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div style="display: flex; align-items: center; gap: 10px;">
                <?php if (!empty($config['app_logo'])): ?>
                <img src="<?php echo htmlspecialchars(cacheBustUrl($config['app_logo'])); ?>" alt="Logo" height="40">
                <?php endif; ?>
                <h2 class="app-title"><?php echo htmlspecialchars($config['app_name']); ?></h2>
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
            <li><a href="?page=main"><?php echo htmlspecialchars(t('nav.calendar')); ?></a></li>
            <li><a href="?page=stats" class="active"><?php echo htmlspecialchars(t('nav.stats')); ?></a></li>
            <?php if (isAdmin()): ?>
                <li><a href="?page=admin-users"><?php echo htmlspecialchars(t('nav.users')); ?></a></li>
                <li><a href="?page=admin-audit"><?php echo htmlspecialchars(t('nav.audit')); ?></a></li>
                <li><a href="?page=admin-settings"><i class="fas fa-cogs"></i> <?php echo htmlspecialchars(t('nav.settings')); ?></a></li>
                <li><a href="?page=admin-branding"><i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars(t('nav.branding')); ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="container">
        <h1><?php echo htmlspecialchars(t('stats.title')); ?></h1>
        
        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i> <?php echo htmlspecialchars(t('stats.loading')); ?>
        </div>
        
        <div id="error" class="error" style="display: none;"></div>
        
        <div class="stats-container" id="stats-container">
            <!-- Statistiken werden per JavaScript hier eingefügt -->
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
        // API URL
        const API_URL = '?api=1&endpoint=';
        const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';
        const i18n = {
            passwordRequirements: <?php echo json_encode(t('password.error.requirements')); ?>,
            passwordMismatch: <?php echo json_encode(t('password.error.mismatch')); ?>,
            passwordChangeFailed: <?php echo json_encode(t('password.error.change_failed')); ?>,
            passwordChanged: <?php echo json_encode(t('password.changed_success')); ?>,
            logoutFailed: <?php echo json_encode(t('auth.error.login_failed')); ?>,
            statsLoadError: <?php echo json_encode(t('stats.error.load')); ?>,
            statsNoData: <?php echo json_encode(t('stats.error.none')); ?>,
            statsUsersTitle: <?php echo json_encode(t('stats.users.title')); ?>,
            statsTypesTitle: <?php echo json_encode(t('stats.types.title')); ?>,
            statsDaysTitle: <?php echo json_encode(t('stats.days.title')); ?>,
            likes: <?php echo json_encode(t('stats.likes')); ?>,
            noData: <?php echo json_encode(t('common.no_data')); ?>,
            classic: <?php echo json_encode(t('stats.classic')); ?>
        };
        
        // DOM-Elemente
        const loadingElement = document.getElementById('loading');
        const errorElement = document.getElementById('error');
        const statsContainer = document.getElementById('stats-container');
        const logoutBtn = document.getElementById('logout-btn');
        const passwordModal = document.getElementById('password-modal');
        const changePasswordBtn = document.getElementById('change-password-btn');
        const closePasswordModalBtn = document.getElementById('close-password-modal');
        const cancelPasswordBtn = document.getElementById('cancel-password-btn');
        const passwordForm = document.getElementById('password-form');
        const passwordStatus = document.getElementById('password-status');
        const newPasswordInput = document.getElementById('new-password');
        const ruleLength = document.getElementById('rule-length');
        const ruleClasses = document.getElementById('rule-classes');

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
        
        // Event-Listener initialisieren
        document.addEventListener('DOMContentLoaded', function() {
            // Event-Listener für Logout
            logoutBtn.addEventListener('click', logout);
            
            // Event-Listener für Passwort ändern
            changePasswordBtn.addEventListener('click', function(e) {
                e.preventDefault();
                passwordForm.reset();
                passwordStatus.style.display = 'none';
                setRuleState(ruleLength, false);
                setRuleState(ruleClasses, false);
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
            
            // Initialisierung
            loadStats();
        });

        newPasswordInput.addEventListener('input', function() {
            const policy = checkPasswordPolicy(newPasswordInput.value);
            setRuleState(ruleLength, policy.lengthOk);
            setRuleState(ruleClasses, policy.classesOk);
        });
        
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
                console.error('Fehler beim Ändern des Passworts:', error);
            } finally {
                loadingElement.style.display = 'none';
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
                console.error(i18n.logoutFailed, error);
            }
        }
        
        // Statistiken laden
        async function loadStats() {
            loadingElement.style.display = 'block';
            errorElement.style.display = 'none';
            statsContainer.innerHTML = '';
            
            try {
                const response = await fetch(API_URL + 'stats');
                if (!response.ok) {
                    throw new Error(i18n.statsLoadError);
                }
                
                const data = await response.json();
                renderStats(data);
            } catch (error) {
                errorElement.textContent = i18n.statsLoadError + ': ' + error.message;
                errorElement.style.display = 'block';
                console.error(i18n.statsLoadError, error);
            } finally {
                loadingElement.style.display = 'none';
            }
        }
        
        // Statistiken rendern
        function renderStats(data) {
            if (!data.users || !data.types || !data.days) {
                errorElement.textContent = i18n.statsNoData;
                errorElement.style.display = 'block';
                return;
            }
            
            // Benutzer-Statistiken
            const userStatsCard = document.createElement('div');
            userStatsCard.className = 'stats-card';
            
            const userStatsTitle = document.createElement('h3');
            userStatsTitle.textContent = i18n.statsUsersTitle;
            userStatsCard.appendChild(userStatsTitle);
            
            const userStatsList = document.createElement('ul');
            userStatsList.className = 'stats-list';
            
            // Maximale Anzahl finden für die Prozentbalken
            const maxCount = Math.max(...data.users.map(user => user.count));
            
            data.users.forEach(user => {
                const item = document.createElement('li');
                item.className = 'stats-item';
                
                // Oberste Zeile für Namen und Werte
                const topRow = document.createElement('div');
                topRow.className = 'stats-row';
                
                const nameDiv = document.createElement('div');
                nameDiv.className = 'stats-name';
                nameDiv.textContent = user.name;
                
                const valueDiv = document.createElement('div');
                valueDiv.className = 'stats-value';
                valueDiv.textContent = `${user.count}×`;
                
                const likesDiv = document.createElement('div');
                likesDiv.className = 'stats-secondary';
                likesDiv.textContent = `${user.likes || 0} ${i18n.likes}`;
                
                topRow.appendChild(nameDiv);
                topRow.appendChild(valueDiv);
                topRow.appendChild(likesDiv);
                
                item.appendChild(topRow);
                
                // Balken für visuelle Darstellung
                const barContainer = document.createElement('div');
                barContainer.className = 'stats-bar-container';
                
                const bar = document.createElement('div');
                bar.className = 'stats-bar';
                bar.style.width = `${(user.count / maxCount) * 100}%`;
                
                barContainer.appendChild(bar);
                item.appendChild(barContainer);
                
                userStatsList.appendChild(item);
            });
            
            if (data.users.length === 0) {
                const emptyItem = document.createElement('li');
                emptyItem.className = 'stats-item';
                emptyItem.textContent = i18n.noData;
                userStatsList.appendChild(emptyItem);
            }
            
            userStatsCard.appendChild(userStatsList);
            statsContainer.appendChild(userStatsCard);
            
            // Gipfeli-Typen-Statistiken
            const typeStatsCard = document.createElement('div');
            typeStatsCard.className = 'stats-card';
            
            const typeStatsTitle = document.createElement('h3');
            typeStatsTitle.textContent = i18n.statsTypesTitle;
            typeStatsCard.appendChild(typeStatsTitle);
            
            const typeStatsList = document.createElement('ul');
            typeStatsList.className = 'stats-list';
            
            // Maximale Anzahl finden für die Prozentbalken
            const maxTypeCount = Math.max(...data.types.map(type => type.count));
            
            data.types.forEach(type => {
                const item = document.createElement('li');
                item.className = 'stats-item';
                
                // Oberste Zeile für Namen und Werte
                const topRow = document.createElement('div');
                topRow.className = 'stats-row';
                
                const nameDiv = document.createElement('div');
                nameDiv.className = 'stats-name';
                nameDiv.textContent = type.type || i18n.classic;
                
                const valueDiv = document.createElement('div');
                valueDiv.className = 'stats-value';
                valueDiv.textContent = `${type.count}×`;
                
                topRow.appendChild(nameDiv);
                topRow.appendChild(valueDiv);
                
                item.appendChild(topRow);
                
                // Balken für visuelle Darstellung
                const barContainer = document.createElement('div');
                barContainer.className = 'stats-bar-container';
                
                const bar = document.createElement('div');
                bar.className = 'stats-bar';
                bar.style.width = `${(type.count / maxTypeCount) * 100}%`;
                
                barContainer.appendChild(bar);
                item.appendChild(barContainer);
                
                typeStatsList.appendChild(item);
            });
            
            if (data.types.length === 0) {
                const emptyItem = document.createElement('li');
                emptyItem.className = 'stats-item';
                emptyItem.textContent = i18n.noData;
                typeStatsList.appendChild(emptyItem);
            }
            
            typeStatsCard.appendChild(typeStatsList);
            statsContainer.appendChild(typeStatsCard);
            
            // Wochentag-Statistiken - nimmt die volle Breite ein
            const dayStatsCard = document.createElement('div');
            dayStatsCard.className = 'stats-card full-width';
            
            const dayStatsTitle = document.createElement('h3');
            dayStatsTitle.textContent = i18n.statsDaysTitle;
            dayStatsCard.appendChild(dayStatsTitle);
            
            const dayStatsList = document.createElement('ul');
            dayStatsList.className = 'stats-list';
            
            // Maximale Anzahl finden für die Prozentbalken
            const maxDayCount = Math.max(...data.days.map(day => day.count));
            
            data.days.forEach(day => {
                const item = document.createElement('li');
                item.className = 'stats-item';
                
                // Oberste Zeile für Namen und Werte
                const topRow = document.createElement('div');
                topRow.className = 'stats-row';
                
                const nameDiv = document.createElement('div');
                nameDiv.className = 'stats-name';
                nameDiv.textContent = day.name;
                
                const valueDiv = document.createElement('div');
                valueDiv.className = 'stats-value';
                valueDiv.textContent = `${day.count}×`;
                
                topRow.appendChild(nameDiv);
                topRow.appendChild(valueDiv);
                
                item.appendChild(topRow);
                
                // Balken für visuelle Darstellung
                const barContainer = document.createElement('div');
                barContainer.className = 'stats-bar-container';
                
                const bar = document.createElement('div');
                bar.className = 'stats-bar';
                bar.style.width = `${(day.count / maxDayCount) * 100}%`;
                
                barContainer.appendChild(bar);
                item.appendChild(barContainer);
                
                dayStatsList.appendChild(item);
            });
            
            if (data.days.length === 0) {
                const emptyItem = document.createElement('li');
                emptyItem.className = 'stats-item';
                emptyItem.textContent = i18n.noData;
                dayStatsList.appendChild(emptyItem);
            }
            
            dayStatsCard.appendChild(dayStatsList);
            statsContainer.appendChild(dayStatsCard);
        }
    </script>
</body>
</html>

