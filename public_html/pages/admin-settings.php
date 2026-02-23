<?php
if (!isAdmin()) {
    header('Location: ?page=main');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(t('meta.lang')); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('settings.title')); ?> - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
            background: #f5f5f5;
            color: #1f2937;
            font-size: 14px;
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
            max-width: var(--layout-width);
            margin: 0 auto;
            padding: 0 20px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
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
            background: #f8f8f8;
            border-bottom: 1px solid #e5e7eb;
        }
        nav ul {
            display: flex;
            list-style: none;
            margin: 0 auto;
            padding: 0;
            max-width: var(--layout-width);
            justify-content: center;
            flex-wrap: wrap;
            gap: 4px;
        }
        nav li {
            padding: 10px 14px;
        }
        nav a {
            color: #374151;
            text-decoration: none;
            font-size: 15px;
        }
        nav a.active {
            color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            font-weight: bold;
        }
        .container {
            max-width: var(--layout-width);
            margin: 0 auto;
            padding: 20px;
            display: grid;
            gap: 16px;
        }
        .app-title {
            color: #fff;
            margin: 0;
            font-size: 1.35rem;
            line-height: 1.2;
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
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
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
            padding: 0;
        }
        .close-modal:hover {
            color: #333;
        }
        .password-form .form-group {
            margin: 0 0 12px;
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
            <li><a href="?page=stats"><?php echo htmlspecialchars(t('nav.stats')); ?></a></li>
            <li><a href="?page=admin-users"><?php echo htmlspecialchars(t('nav.users')); ?></a></li>
            <li><a href="?page=admin-audit"><?php echo htmlspecialchars(t('nav.audit')); ?></a></li>
            <li><a href="?page=admin-settings" class="active"><i class="fas fa-cogs"></i> <?php echo htmlspecialchars(t('nav.settings')); ?></a></li>
            <li><a href="?page=admin-branding"><i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars(t('nav.branding')); ?></a></li>
        </ul>
    </nav>

    <div class="container">
        <section class="card">
            <h2><?php echo htmlspecialchars(t('settings.roles.title')); ?></h2>
            <table class="roles-table">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars(t('settings.roles.col.role')); ?></th>
                        <th><?php echo htmlspecialchars(t('settings.roles.col.rights')); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php echo htmlspecialchars(t('users.role.admin')); ?></td>
                        <td><?php echo htmlspecialchars(t('settings.roles.admin.rights')); ?></td>
                    </tr>
                    <tr>
                        <td><?php echo htmlspecialchars(t('users.role.super_admin')); ?></td>
                        <td><?php echo htmlspecialchars(t('settings.roles.super_admin.rights')); ?></td>
                    </tr>
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2><?php echo htmlspecialchars(t('settings.system.title')); ?></h2>
            <p class="meta"><?php echo htmlspecialchars(t('settings.system.meta')); ?></p>
            <div class="switch-row">
                <input type="checkbox" id="debug-mode">
                <label for="debug-mode" style="margin:0;"><?php echo htmlspecialchars(t('settings.system.debug_enable')); ?></label>
            </div>
            <?php if (isSuperAdmin()): ?>
            <div class="row-actions">
                <button type="button" id="save-system-btn"><?php echo htmlspecialchars(t('settings.system.save')); ?></button>
            </div>
            <?php else: ?>
            <p class="meta"><?php echo htmlspecialchars(t('settings.system.super_admin_only')); ?></p>
            <?php endif; ?>
            <div id="system-status" class="status"></div>
        </section>

        <section class="card">
            <h2><?php echo htmlspecialchars(t('settings.smtp.title')); ?></h2>
            <div class="grid-2">
                <?php if (isSuperAdmin()): ?>
                <div>
                    <h3><?php echo htmlspecialchars(t('settings.smtp.config_title')); ?></h3>
                    <div class="form-group">
                        <label for="smtp-mail-host"><?php echo htmlspecialchars(t('settings.smtp.host')); ?></label>
                        <input type="text" id="smtp-mail-host" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="smtp-mail-port"><?php echo htmlspecialchars(t('settings.smtp.port')); ?></label>
                        <input type="number" id="smtp-mail-port" min="1" max="65535">
                    </div>
                    <div class="form-group">
                        <label for="smtp-mail-user"><?php echo htmlspecialchars(t('settings.smtp.user')); ?></label>
                        <input type="text" id="smtp-mail-user" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="smtp-mail-pass"><?php echo htmlspecialchars(t('settings.smtp.pass')); ?></label>
                        <input type="password" id="smtp-mail-pass" placeholder="<?php echo htmlspecialchars(t('settings.smtp.pass_keep')); ?>">
                    </div>
                    <div class="form-group">
                        <label for="smtp-mail-from"><?php echo htmlspecialchars(t('settings.smtp.from')); ?></label>
                        <input type="email" id="smtp-mail-from">
                    </div>
                    <div class="form-group">
                        <label for="smtp-mail-name"><?php echo htmlspecialchars(t('settings.smtp.from_name')); ?></label>
                        <input type="text" id="smtp-mail-name">
                    </div>
                    <div class="row-actions">
                        <button type="button" id="smtp-save-btn"><?php echo htmlspecialchars(t('settings.smtp.save')); ?></button>
                    </div>
                </div>
                <?php endif; ?>
                <div>
                    <h3><?php echo htmlspecialchars(t('settings.smtp.test_title')); ?></h3>
                    <div class="form-group">
                        <label for="smtp-test-to"><?php echo htmlspecialchars(t('settings.smtp.test_to')); ?></label>
                        <input type="email" id="smtp-test-to" value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>">
                    </div>
                    <div class="row-actions">
                        <button type="button" class="btn-secondary" id="smtp-test-btn"><?php echo htmlspecialchars(t('settings.smtp.test')); ?></button>
                    </div>
                    <p class="meta"><?php echo htmlspecialchars(t('settings.smtp.test_meta')); ?></p>
                </div>
            </div>
            <div id="smtp-status" class="status"></div>
        </section>

        <section class="card">
            <h2><?php echo htmlspecialchars(t('settings.smtp.log_title')); ?></h2>
            <div class="log-tools">
                <label for="smtp-log-limit" style="margin:0;"><?php echo htmlspecialchars(t('settings.smtp.log_entries')); ?></label>
                <input type="number" id="smtp-log-limit" value="200" min="1" max="500">
                <button type="button" id="refresh-smtp-log-btn" class="btn-secondary"><?php echo htmlspecialchars(t('settings.smtp.log_reload')); ?></button>
                <span id="smtp-log-file" class="meta"></span>
            </div>
            <div class="log-wrapper">
                <table class="log-table">
                    <thead>
                        <tr>
                            <th><?php echo htmlspecialchars(t('settings.smtp.log_time')); ?></th>
                            <th><?php echo htmlspecialchars(t('settings.smtp.log_status')); ?></th>
                            <th><?php echo htmlspecialchars(t('settings.smtp.log_ip_user')); ?></th>
                            <th><?php echo htmlspecialchars(t('settings.smtp.log_context')); ?></th>
                        </tr>
                    </thead>
                    <tbody id="smtp-log-body">
                        <tr><td colspan="4"><?php echo htmlspecialchars(t('settings.smtp.log_loading')); ?></td></tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

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
                    <ul class="password-rules">
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
        const API_URL = '?api=1&endpoint=';
        const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';
        const IS_SUPER_ADMIN = <?php echo isSuperAdmin() ? 'true' : 'false'; ?>;
        const i18n = {
            locale: <?php echo json_encode(t('locale.date')); ?>,
            passwordRequirements: <?php echo json_encode(t('password.error.requirements')); ?>,
            passwordMismatch: <?php echo json_encode(t('password.error.mismatch')); ?>,
            passwordChangeFailed: <?php echo json_encode(t('password.error.change_failed')); ?>,
            passwordChanged: <?php echo json_encode(t('password.changed_success')); ?>,
            systemLoadError: <?php echo json_encode(t('settings.error.system_load')); ?>,
            systemSaveError: <?php echo json_encode(t('settings.error.system_save')); ?>,
            systemSaved: <?php echo json_encode(t('settings.success.system_saved')); ?>,
            smtpLoadError: <?php echo json_encode(t('settings.error.smtp_load')); ?>,
            smtpSaveError: <?php echo json_encode(t('settings.error.smtp_save')); ?>,
            smtpSaved: <?php echo json_encode(t('settings.success.smtp_saved')); ?>,
            smtpFromInvalid: <?php echo json_encode(t('settings.error.smtp_from_invalid')); ?>,
            smtpTestInvalid: <?php echo json_encode(t('settings.error.smtp_test_invalid')); ?>,
            smtpTestFailed: <?php echo json_encode(t('settings.error.smtp_test_failed')); ?>,
            smtpTestOk: <?php echo json_encode(t('settings.success.smtp_test_ok')); ?>,
            smtpLogLoadError: <?php echo json_encode(t('settings.error.smtp_log_load')); ?>,
            smtpPassSetKeep: <?php echo json_encode(t('settings.smtp.pass_set_keep')); ?>,
            smtpLogNone: <?php echo json_encode(t('settings.smtp.log_none')); ?>,
            logPrefix: 'Log: '
        };

        const logoutBtn = document.getElementById('logout-btn');
        const changePasswordBtn = document.getElementById('change-password-btn');
        const passwordModal = document.getElementById('password-modal');
        const closePasswordModalBtn = document.getElementById('close-password-modal');
        const cancelPasswordBtn = document.getElementById('cancel-password-btn');
        const passwordForm = document.getElementById('password-form');
        const passwordStatus = document.getElementById('password-status');
        const newPasswordInput = document.getElementById('new-password');
        const ruleLength = document.getElementById('rule-length');
        const ruleClasses = document.getElementById('rule-classes');
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
            return date.toLocaleString(i18n.locale);
        }

        async function logout() {
            try {
                await fetch(API_URL + 'logout', {
                    method: 'POST',
                    headers: { 'X-CSRF-Token': CSRF_TOKEN }
                });
                window.location.href = '?page=start';
            } catch (error) {
                console.error('Logout failed:', error);
            }
        }

        function showPasswordStatus(message, type) {
            passwordStatus.className = 'status ' + type;
            passwordStatus.textContent = message;
            passwordStatus.style.display = 'block';
            if (type === 'success') {
                setTimeout(() => {
                    passwordStatus.style.display = 'none';
                    passwordModal.style.display = 'none';
                }, 2000);
            }
        }

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
                    throw new Error(data.error || i18n.passwordChangeFailed);
                }
                showPasswordStatus(i18n.passwordChanged, 'success');
                passwordForm.reset();
            } catch (error) {
                showPasswordStatus(error.message, 'error');
            }
        }

        async function loadSystemSettings() {
            try {
                const response = await fetch(API_URL + 'system-settings');
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || i18n.systemLoadError);
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
                    throw new Error(data.error || i18n.systemSaveError);
                }
                showStatus(systemStatus, data.message || i18n.systemSaved, 'success');
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
                    throw new Error(data.error || i18n.smtpLoadError);
                }
                const settings = data.settings || {};
                smtpMailHost.value = settings.mail_host || '';
                smtpMailPort.value = settings.mail_port || 587;
                smtpMailUser.value = settings.mail_user || '';
                smtpMailFrom.value = settings.mail_from || '';
                smtpMailName.value = settings.mail_name || '';
                smtpMailPass.value = '';
                if (settings.mail_pass_configured) {
                    smtpMailPass.placeholder = i18n.smtpPassSetKeep;
                }
            } catch (error) {
                showStatus(smtpStatus, error.message, 'error');
            }
        }

        async function saveSmtpConfig() {
            if (!IS_SUPER_ADMIN) return;
            if (!smtpMailFrom.checkValidity()) {
                showStatus(smtpStatus, i18n.smtpFromInvalid, 'error');
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
                    throw new Error(data.error || i18n.smtpSaveError);
                }
                smtpMailPass.value = '';
                showStatus(smtpStatus, data.message || i18n.smtpSaved, 'success');
            } catch (error) {
                showStatus(smtpStatus, error.message, 'error');
            }
        }

        async function runSmtpTest() {
            const target = smtpTestTo.value.trim();
            if (!target || !smtpTestTo.checkValidity()) {
                showStatus(smtpStatus, i18n.smtpTestInvalid, 'error');
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
                    throw new Error(data.error || i18n.smtpTestFailed);
                }
                showStatus(smtpStatus, data.message || i18n.smtpTestOk, 'success');
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
                    throw new Error(data.error || i18n.smtpLogLoadError);
                }
                const entries = Array.isArray(data.entries) ? data.entries : [];
                smtpLogFile.textContent = data.log_file ? (i18n.logPrefix + data.log_file) : '';
                if (entries.length === 0) {
                    smtpLogBody.innerHTML = '<tr><td colspan="4">' + escapeHtml(i18n.smtpLogNone) + '</td></tr>';
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
        changePasswordBtn.addEventListener('click', (e) => {
            e.preventDefault();
            passwordForm.reset();
            passwordStatus.style.display = 'none';
            setRuleState(ruleLength, false);
            setRuleState(ruleClasses, false);
            passwordModal.style.display = 'block';
        });
        closePasswordModalBtn.addEventListener('click', () => passwordModal.style.display = 'none');
        cancelPasswordBtn.addEventListener('click', () => passwordModal.style.display = 'none');
        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const currentPassword = document.getElementById('current-password').value;
            const newPassword = document.getElementById('new-password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            const policy = checkPasswordPolicy(newPassword);
            if (!policy.valid) {
                showPasswordStatus(i18n.passwordRequirements, 'error');
                return;
            }
            if (newPassword !== confirmPassword) {
                showPasswordStatus(i18n.passwordMismatch, 'error');
                return;
            }
            await changePassword(currentPassword, newPassword);
        });
        newPasswordInput.addEventListener('input', () => {
            const policy = checkPasswordPolicy(newPasswordInput.value);
            setRuleState(ruleLength, policy.lengthOk);
            setRuleState(ruleClasses, policy.classesOk);
        });
        window.addEventListener('click', (event) => {
            if (event.target === passwordModal) {
                passwordModal.style.display = 'none';
            }
        });
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

