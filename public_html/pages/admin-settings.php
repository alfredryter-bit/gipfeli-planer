<?php
if (!isAdmin()) {
    header('Location: ?page=main');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einstellungen - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <?php if (!empty($config['app_favicon'])): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($config['app_favicon']); ?>">
    <?php endif; ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            color: #1f2937;
        }
        header {
            background-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            color: #fff;
            padding: 10px 0;
        }
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1100px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-logout {
            background: transparent;
            border: 1px solid #fff;
            color: #fff;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }
        nav {
            background: #f8f8f8;
            border-bottom: 1px solid #e5e7eb;
        }
        nav ul {
            display: flex;
            list-style: none;
            margin: 0 auto;
            padding: 0;
            max-width: 1100px;
        }
        nav li {
            padding: 10px 14px;
        }
        nav a {
            color: #374151;
            text-decoration: none;
        }
        nav a.active {
            color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            font-weight: bold;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
            display: grid;
            gap: 16px;
        }
        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px;
        }
        .card h2 {
            margin: 0 0 10px;
            font-size: 20px;
        }
        .meta {
            color: #6b7280;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .form-group {
            margin-bottom: 10px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 4px;
        }
        input[type="text"],
        input[type="number"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            box-sizing: border-box;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            padding: 8px 10px;
            font: inherit;
        }
        .row-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 8px;
        }
        button {
            border: none;
            border-radius: 6px;
            padding: 8px 12px;
            cursor: pointer;
            color: #fff;
            background: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            font-weight: bold;
        }
        .btn-secondary {
            background: <?php echo htmlspecialchars($config['app_secondary_color']); ?>;
        }
        .status {
            display: none;
            margin-top: 10px;
            border-radius: 6px;
            padding: 8px 10px;
            font-size: 14px;
        }
        .status.success {
            display: block;
            background: #d1fae5;
            color: #065f46;
        }
        .status.error {
            display: block;
            background: #fee2e2;
            color: #991b1b;
        }
        .switch-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .switch-row input {
            width: auto;
        }
        .roles-table {
            width: 100%;
            border-collapse: collapse;
        }
        .roles-table th,
        .roles-table td {
            border-top: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        .log-tools {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }
        .log-tools input {
            width: 90px;
        }
        .log-wrapper {
            max-height: 420px;
            overflow: auto;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
        }
        .log-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .log-table th,
        .log-table td {
            border-top: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        .log-table th {
            position: sticky;
            top: 0;
            background: #f8fafc;
            z-index: 1;
        }
        .log-context {
            white-space: pre-wrap;
            word-break: break-word;
            margin: 0;
            font-family: Consolas, "Courier New", monospace;
            font-size: 12px;
        }
        @media (max-width: 900px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div style="display:flex;align-items:center;gap:10px;">
                <?php if (!empty($config['app_logo'])): ?>
                <img src="<?php echo htmlspecialchars($config['app_logo']); ?>" alt="Logo" height="40">
                <?php endif; ?>
                <h2 style="margin:0;color:#fff;"><?php echo htmlspecialchars($config['app_name']); ?></h2>
            </div>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <button id="logout-btn" class="btn-logout">Abmelden</button>
            </div>
        </div>
    </header>

    <nav>
        <ul>
            <li><a href="?page=main">Kalender</a></li>
            <li><a href="?page=stats">Statistiken</a></li>
            <li><a href="?page=admin-users">Benutzerverwaltung</a></li>
            <li><a href="?page=admin-audit">Audit-Log</a></li>
            <li><a href="?page=admin-settings" class="active"><i class="fas fa-cogs"></i> Einstellungen</a></li>
            <li><a href="?page=admin-branding"><i class="fas fa-paint-brush"></i> Branding</a></li>
        </ul>
    </nav>

    <div class="container">
        <section class="card">
            <h2>Rollen</h2>
            <table class="roles-table">
                <thead>
                    <tr>
                        <th>Rolle</th>
                        <th>Rechte</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Admin</td>
                        <td>Benutzer verwalten, Audit-Log sehen, Branding ändern, SMTP testen, SMTP-Log sehen.</td>
                    </tr>
                    <tr>
                        <td>Super-Admin</td>
                        <td>Alle Admin-Rechte plus SMTP konfigurieren und Systemeinstellungen wie Debug-Mode ändern.</td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>System</h2>
            <p class="meta">Debug-Mode steuert zusätzliche `error_log`-Ausgaben.</p>
            <div class="switch-row">
                <input type="checkbox" id="debug-mode">
                <label for="debug-mode" style="margin:0;">Debug-Mode aktivieren</label>
            </div>
            <?php if (isSuperAdmin()): ?>
            <div class="row-actions">
                <button type="button" id="save-system-btn">System speichern</button>
            </div>
            <?php else: ?>
            <p class="meta">Nur Super-Admin kann diese Einstellung ändern.</p>
            <?php endif; ?>
            <div id="system-status" class="status"></div>
        </section>

        <section class="card">
            <h2>SMTP</h2>
            <div class="grid-2">
                <?php if (isSuperAdmin()): ?>
                <div>
                    <h3>Konfiguration</h3>
                    <div class="form-group">
                        <label for="smtp-mail-host">SMTP-Host</label>
                        <input type="text" id="smtp-mail-host" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="smtp-mail-port">SMTP-Port</label>
                        <input type="number" id="smtp-mail-port" min="1" max="65535">
                    </div>
                    <div class="form-group">
                        <label for="smtp-mail-user">SMTP-Benutzer</label>
                        <input type="text" id="smtp-mail-user" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="smtp-mail-pass">SMTP-Passwort</label>
                        <input type="password" id="smtp-mail-pass" placeholder="Leer lassen, um Passwort zu behalten">
                    </div>
                    <div class="form-group">
                        <label for="smtp-mail-from">Absender-E-Mail</label>
                        <input type="email" id="smtp-mail-from">
                    </div>
                    <div class="form-group">
                        <label for="smtp-mail-name">Absender-Name</label>
                        <input type="text" id="smtp-mail-name">
                    </div>
                    <div class="row-actions">
                        <button type="button" id="smtp-save-btn">SMTP speichern</button>
                    </div>
                </div>
                <?php endif; ?>
                <div>
                    <h3>SMTP-Test</h3>
                    <div class="form-group">
                        <label for="smtp-test-to">Test-E-Mail an</label>
                        <input type="email" id="smtp-test-to" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
                    </div>
                    <div class="row-actions">
                        <button type="button" class="btn-secondary" id="smtp-test-btn">SMTP testen</button>
                    </div>
                    <p class="meta">Test ist für Admin und Super-Admin verfügbar.</p>
                </div>
            </div>
            <div id="smtp-status" class="status"></div>
        </section>

        <section class="card">
            <h2>SMTP-Log</h2>
            <div class="log-tools">
                <label for="smtp-log-limit" style="margin:0;">Einträge</label>
                <input type="number" id="smtp-log-limit" value="200" min="1" max="500">
                <button type="button" id="refresh-smtp-log-btn" class="btn-secondary">Neu laden</button>
                <span id="smtp-log-file" class="meta"></span>
            </div>
            <div class="log-wrapper">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th>Zeit</th>
                            <th>Status</th>
                            <th>IP / User</th>
                            <th>Context</th>
                        </tr>
                    </thead>
                    <tbody id="smtp-log-body">
                        <tr><td colspan="4">Lade SMTP-Log...</td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script>
        const API_URL = '?api=1&endpoint=';
        const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';
        const IS_SUPER_ADMIN = <?php echo isSuperAdmin() ? 'true' : 'false'; ?>;

        const logoutBtn = document.getElementById('logout-btn');
        const debugModeCheckbox = document.getElementById('debug-mode');
        const saveSystemBtn = document.getElementById('save-system-btn');
        const systemStatus = document.getElementById('system-status');

        const smtpMailHost = document.getElementById('smtp-mail-host');
        const smtpMailPort = document.getElementById('smtp-mail-port');
        const smtpMailUser = document.getElementById('smtp-mail-user');
        const smtpMailPass = document.getElementById('smtp-mail-pass');
        const smtpMailFrom = document.getElementById('smtp-mail-from');
        const smtpMailName = document.getElementById('smtp-mail-name');
        const smtpTestTo = document.getElementById('smtp-test-to');
        const smtpSaveBtn = document.getElementById('smtp-save-btn');
        const smtpTestBtn = document.getElementById('smtp-test-btn');
        const smtpStatus = document.getElementById('smtp-status');

        const smtpLogLimit = document.getElementById('smtp-log-limit');
        const refreshSmtpLogBtn = document.getElementById('refresh-smtp-log-btn');
        const smtpLogBody = document.getElementById('smtp-log-body');
        const smtpLogFile = document.getElementById('smtp-log-file');

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showStatus(element, message, type) {
            if (!element) return;
            element.className = 'status ' + type;
            element.textContent = message;
        }

        function formatLogTime(value) {
            if (!value) return '-';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return String(value);
            return date.toLocaleString('de-CH');
        }

        async function logout() {
            try {
                await fetch(API_URL + 'logout', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': CSRF_TOKEN }
                });
                window.location.href = '?page=start';
            } catch (error) {
                console.error('Logout fehlgeschlagen:', error);
            }
        }

        async function loadSystemSettings() {
            try {
                const response = await fetch(API_URL + 'system-settings');
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'Systemeinstellungen konnten nicht geladen werden');
                }
                debugModeCheckbox.checked = !!(data.settings && data.settings.debug_mode);
            } catch (error) {
                showStatus(systemStatus, error.message, 'error');
            }
        }

        async function saveSystemSettings() {
            if (!IS_SUPER_ADMIN) return;
            try {
                const response = await fetch(API_URL + 'system-settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify({ debug_mode: debugModeCheckbox.checked })
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'Systemeinstellungen konnten nicht gespeichert werden');
                }
                showStatus(systemStatus, data.message || 'Systemeinstellungen gespeichert', 'success');
            } catch (error) {
                showStatus(systemStatus, error.message, 'error');
            }
        }

        async function loadSmtpConfig() {
            if (!IS_SUPER_ADMIN) return;
            try {
                const response = await fetch(API_URL + 'smtp-config');
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'SMTP-Konfiguration konnte nicht geladen werden');
                }
                const settings = data.settings || {};
                smtpMailHost.value = settings.mail_host || '';
                smtpMailPort.value = settings.mail_port || 587;
                smtpMailUser.value = settings.mail_user || '';
                smtpMailFrom.value = settings.mail_from || '';
                smtpMailName.value = settings.mail_name || '';
                smtpMailPass.value = '';
                if (settings.mail_pass_configured) {
                    smtpMailPass.placeholder = 'Passwort gesetzt - leer lassen zum Beibehalten';
                }
            } catch (error) {
                showStatus(smtpStatus, error.message, 'error');
            }
        }

        async function saveSmtpConfig() {
            if (!IS_SUPER_ADMIN) return;
            if (!smtpMailFrom.checkValidity()) {
                showStatus(smtpStatus, 'Bitte gültige Absender-E-Mail eingeben.', 'error');
                return;
            }
            const payload = {
                mail_host: smtpMailHost.value.trim(),
                mail_port: parseInt(smtpMailPort.value, 10) || 587,
                mail_user: smtpMailUser.value.trim(),
                mail_pass: smtpMailPass.value,
                mail_from: smtpMailFrom.value.trim(),
                mail_name: smtpMailName.value.trim()
            };
            try {
                const response = await fetch(API_URL + 'smtp-config', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'SMTP-Konfiguration konnte nicht gespeichert werden');
                }
                smtpMailPass.value = '';
                showStatus(smtpStatus, data.message || 'SMTP-Konfiguration gespeichert', 'success');
            } catch (error) {
                showStatus(smtpStatus, error.message, 'error');
            }
        }

        async function runSmtpTest() {
            const target = smtpTestTo.value.trim();
            if (!target || !smtpTestTo.checkValidity()) {
                showStatus(smtpStatus, 'Bitte gültige Test-E-Mail-Adresse eingeben.', 'error');
                return;
            }
            try {
                const response = await fetch(API_URL + 'smtp-test', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify({ to: target })
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'SMTP-Test fehlgeschlagen');
                }
                showStatus(smtpStatus, data.message || 'SMTP-Test erfolgreich', 'success');
                await loadSmtpLog();
            } catch (error) {
                showStatus(smtpStatus, error.message, 'error');
            }
        }

        async function loadSmtpLog() {
            const limit = Math.max(1, Math.min(500, parseInt(smtpLogLimit.value, 10) || 200));
            try {
                const response = await fetch(API_URL + 'smtp-log&limit=' + limit);
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'SMTP-Log konnte nicht geladen werden');
                }
                const entries = Array.isArray(data.entries) ? data.entries : [];
                smtpLogFile.textContent = data.log_file ? ('Log: ' + data.log_file) : '';
                if (entries.length === 0) {
                    smtpLogBody.innerHTML = '<tr><td colspan="4">Keine Einträge vorhanden.</td></tr>';
                    return;
                }
                smtpLogBody.innerHTML = entries.map((entry) => {
                    const contextText = JSON.stringify(entry.context ?? {}, null, 2);
                    return '<tr>'
                        + '<td>' + escapeHtml(formatLogTime(entry.timestamp)) + '</td>'
                        + '<td>' + escapeHtml(entry.status ?? '-') + '</td>'
                        + '<td>' + escapeHtml((entry.ip ?? '-') + ' / ' + (entry.user_id ?? '-')) + '</td>'
                        + '<td><pre class="log-context">' + escapeHtml(contextText) + '</pre></td>'
                        + '</tr>';
                }).join('');
            } catch (error) {
                smtpLogBody.innerHTML = '<tr><td colspan="4">' + escapeHtml(error.message) + '</td></tr>';
            }
        }

        logoutBtn.addEventListener('click', logout);
        smtpTestBtn.addEventListener('click', runSmtpTest);
        refreshSmtpLogBtn.addEventListener('click', loadSmtpLog);
        if (saveSystemBtn) {
            saveSystemBtn.addEventListener('click', saveSystemSettings);
        }
        if (smtpSaveBtn) {
            smtpSaveBtn.addEventListener('click', saveSmtpConfig);
        }
        if (!IS_SUPER_ADMIN) {
            debugModeCheckbox.disabled = true;
        }

        loadSystemSettings();
        loadSmtpConfig();
        loadSmtpLog();
    </script>
</body>
</html>
