<?php
// pages/admin-audit.php - Audit-Log-Seite für Administratoren
// Diese Seite ist nur für Administratoren zugänglich
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
    <title><?php echo htmlspecialchars(t('audit.title')); ?> - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
            max-width: var(--layout-width);
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
        .audit-controls {
            margin: 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .search-box {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        input[type="text"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 250px;
        }
        button {
            background-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
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
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        th {
            background-color: #f8f8f8;
            padding: 12px;
            text-align: left;
            font-weight: bold;
        }
        td {
            padding: 12px;
            border-top: 1px solid #f1f1f1;
        }
        tr:hover {
            background-color: #f9f9f9;
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
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .empty-state {
            text-align: center;
            margin: 50px 0;
            color: #6c757d;
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
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            gap: 5px;
        }
        .pagination button {
            padding: 8px 12px;
            background-color: #f8f8f8;
            color: #333;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .pagination button:hover {
            background-color: #e9e9e9;
        }
        .pagination button.active {
            background-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            color: white;
            border-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
        }
        .pagination button:disabled {
            background-color: #f8f8f8;
            color: #ccc;
            cursor: not-allowed;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-login {
            background-color: #d4edda;
            color: #155724;
        }
        .badge-logout {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        .badge-create {
            background-color: #cce5ff;
            color: #004085;
        }
        .badge-update {
            background-color: #fff3cd;
            color: #856404;
        }
        .badge-delete {
            background-color: #f8d7da;
            color: #721c24;
        }
        .badge-like {
            background-color: #f8d7da;
            color: #721c24;
        }
        .badge-notify {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .responsive-table {
            overflow-x: auto;
        }
        .detail-button {
            background: none;
            border: none;
            color: #007bff;
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
        }
        .detail-button:hover {
            color: #0056b3;
            background: none;
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
            width: 500px;
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
        }
        .close-modal:hover {
            color: #333;
        }
        pre {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
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
            <li><a href="?page=stats"><?php echo htmlspecialchars(t('nav.stats')); ?></a></li>
            <?php if (isAdmin()): ?>
                <li><a href="?page=admin-users"><?php echo htmlspecialchars(t('nav.users')); ?></a></li>
                <li><a href="?page=admin-audit" class="active"><?php echo htmlspecialchars(t('nav.audit')); ?></a></li>
                <li><a href="?page=admin-settings"><i class="fas fa-cogs"></i> <?php echo htmlspecialchars(t('nav.settings')); ?></a></li>
                <li><a href="?page=admin-branding"><i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars(t('nav.branding')); ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="container">
        <h1><?php echo htmlspecialchars(t('audit.title')); ?></h1>
        
        <div class="audit-controls">
            <div class="search-box">
                <input type="text" id="search-input" placeholder="<?php echo htmlspecialchars(t('audit.search_placeholder')); ?>">
                <button id="search-btn"><?php echo htmlspecialchars(t('audit.search')); ?></button>
                <button id="reset-btn" class="btn-secondary"><?php echo htmlspecialchars(t('audit.reset')); ?></button>
            </div>
            <div>
                <select id="action-filter">
                    <option value=""><?php echo htmlspecialchars(t('audit.filter.all')); ?></option>
                    <option value="login"><?php echo htmlspecialchars(t('audit.filter.login')); ?></option>
                    <option value="logout"><?php echo htmlspecialchars(t('audit.filter.logout')); ?></option>
                    <option value="create_entry"><?php echo htmlspecialchars(t('audit.filter.create_entry')); ?></option>
                    <option value="update_entry"><?php echo htmlspecialchars(t('audit.filter.update_entry')); ?></option>
                    <option value="delete_entry"><?php echo htmlspecialchars(t('audit.filter.delete_entry')); ?></option>
                    <option value="like"><?php echo htmlspecialchars(t('audit.filter.like')); ?></option>
                    <option value="unlike"><?php echo htmlspecialchars(t('audit.filter.unlike')); ?></option>
                    <option value="send_notification"><?php echo htmlspecialchars(t('audit.filter.notify')); ?></option>
                </select>
            </div>
        </div>
        
        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i> <?php echo htmlspecialchars(t('audit.loading')); ?>
        </div>
        
        <div id="error" class="error" style="display: none;"></div>
        
        <div class="responsive-table">
            <table id="audit-table">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars(t('audit.col.time')); ?></th>
                        <th><?php echo htmlspecialchars(t('audit.col.user')); ?></th>
                        <th><?php echo htmlspecialchars(t('audit.col.action')); ?></th>
                        <th><?php echo htmlspecialchars(t('audit.col.description')); ?></th>
                        <th><?php echo htmlspecialchars(t('audit.col.ip')); ?></th>
                        <th><?php echo htmlspecialchars(t('audit.col.details')); ?></th>
                    </tr>
                </thead>
                <tbody id="audit-tbody">
                    <!-- Audit-Log-Einträge werden hier eingefügt -->
                </tbody>
            </table>
        </div>
        
        <div id="empty-state" class="empty-state" style="display: none;">
            <i class="fas fa-search"></i> <?php echo htmlspecialchars(t('audit.empty')); ?>
        </div>
        
        <div class="pagination" id="pagination">
            <!-- Seitenzahlen werden hier eingefügt -->
        </div>
    </div>
    
    <!-- Modal für Details -->
    <div id="details-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo htmlspecialchars(t('audit.details_title')); ?></h3>
                <button class="close-modal" id="close-modal">&times;</button>
            </div>
            <div id="modal-content">
                <!-- Details werden hier eingefügt -->
            </div>
        </div>
    </div>

    <div id="password-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo htmlspecialchars(t('password.change')); ?></h3>
                <button class="close-modal" id="close-password-modal">&times;</button>
            </div>
            <div id="password-status" class="error" style="display: none;"></div>
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
        // API URL
        const API_URL = '?api=1&endpoint=';
        const CSRF_TOKEN = <?php echo json_encode(getCsrfToken()); ?>;
        const i18n = {
            locale: <?php echo json_encode(t('locale.date')); ?>,
            passwordRequirements: <?php echo json_encode(t('password.error.requirements')); ?>,
            passwordMismatch: <?php echo json_encode(t('password.error.mismatch')); ?>,
            passwordChangeFailed: <?php echo json_encode(t('password.error.change_failed')); ?>,
            passwordChanged: <?php echo json_encode(t('password.changed_success')); ?>,
            loadError: <?php echo json_encode(t('audit.error.load')); ?>,
            unknown: <?php echo json_encode(t('common.unknown')); ?>,
            actionLogin: <?php echo json_encode(t('audit.filter.login')); ?>,
            actionLogout: <?php echo json_encode(t('audit.filter.logout')); ?>,
            actionCreated: <?php echo json_encode(t('audit.action.created')); ?>,
            actionUpdated: <?php echo json_encode(t('audit.action.updated')); ?>,
            actionDeleted: <?php echo json_encode(t('audit.action.deleted')); ?>,
            actionLike: <?php echo json_encode(t('audit.filter.like')); ?>,
            actionUnlike: <?php echo json_encode(t('audit.filter.unlike')); ?>,
            actionNotify: <?php echo json_encode(t('audit.filter.notify')); ?>,
            showDetails: <?php echo json_encode(t('audit.show_details')); ?>,
            labelTime: <?php echo json_encode(t('audit.col.time')); ?>,
            labelUser: <?php echo json_encode(t('audit.col.user')); ?>,
            labelAction: <?php echo json_encode(t('audit.col.action')); ?>,
            labelDescription: <?php echo json_encode(t('audit.col.description')); ?>,
            labelIp: <?php echo json_encode(t('audit.col.ip')); ?>,
            labelDetailsData: <?php echo json_encode(t('audit.label.details_data')); ?>
        };
        
        // Paginierungsvariablen
        let currentPage = 0;
        const logsPerPage = 20;
        let totalLogs = 0;
        let filteredLogs = [];
        
        // DOM-Elemente
        const loadingElement = document.getElementById('loading');
        const errorElement = document.getElementById('error');
        const auditTbody = document.getElementById('audit-tbody');
        const emptyState = document.getElementById('empty-state');
        const paginationElement = document.getElementById('pagination');
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');
        const resetBtn = document.getElementById('reset-btn');
        const actionFilter = document.getElementById('action-filter');
        const logoutBtn = document.getElementById('logout-btn');
        const changePasswordBtn = document.getElementById('change-password-btn');
        const detailsModal = document.getElementById('details-modal');
        const closeModalBtn = document.getElementById('close-modal');
        const modalContent = document.getElementById('modal-content');
        const passwordModal = document.getElementById('password-modal');
        const closePasswordModalBtn = document.getElementById('close-password-modal');
        const cancelPasswordBtn = document.getElementById('cancel-password-btn');
        const passwordForm = document.getElementById('password-form');
        const passwordStatus = document.getElementById('password-status');
        const newPasswordInput = document.getElementById('new-password');
        const ruleLength = document.getElementById('rule-length');
        const ruleClasses = document.getElementById('rule-classes');
        
        // Alle Logs speichern
        let allLogs = [];

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
        
        // Event-Listener
        searchBtn.addEventListener('click', filterLogs);
        resetBtn.addEventListener('click', resetFilter);
        actionFilter.addEventListener('change', filterLogs);
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
        closeModalBtn.addEventListener('click', () => {
            detailsModal.style.display = 'none';
        });
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
        
        // Klick außerhalb des Modals schließt es
        window.addEventListener('click', (event) => {
            if (event.target === detailsModal) {
                detailsModal.style.display = 'none';
            }
            if (event.target === passwordModal) {
                passwordModal.style.display = 'none';
            }
        });
        
        // Initialisierung
        loadAuditLog();
        
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
                console.error('Logout fehlgeschlagen:', error);
            }
        }

        function showPasswordStatus(message, type) {
            passwordStatus.textContent = message;
            passwordStatus.className = type === 'success' ? 'success' : 'error';
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
        
        // Audit-Log laden
        async function loadAuditLog() {
            loadingElement.style.display = 'block';
            errorElement.style.display = 'none';
            auditTbody.innerHTML = '';
            emptyState.style.display = 'none';
            
            try {
                const response = await fetch(API_URL + 'audit');
                if (!response.ok) {
                    throw new Error(i18n.loadError);
                }
                
                const data = await response.json();
                
                if (!data.logs || data.logs.length === 0) {
                    emptyState.style.display = 'block';
                    return;
                }
                
                allLogs = data.logs;
                filteredLogs = [...allLogs];
                totalLogs = filteredLogs.length;
                
                renderLogs();
            } catch (error) {
                errorElement.textContent = i18n.loadError + ': ' + error.message;
                errorElement.style.display = 'block';
                console.error(i18n.loadError, error);
            } finally {
                loadingElement.style.display = 'none';
            }
        }
        
        // Filter zurücksetzen
        function resetFilter() {
            searchInput.value = '';
            actionFilter.value = '';
            filteredLogs = [...allLogs];
            totalLogs = filteredLogs.length;
            currentPage = 0;
            renderLogs();
        }
        
        // Logs filtern
        function filterLogs() {
            const searchTerm = searchInput.value.toLowerCase();
            const actionTerm = actionFilter.value.toLowerCase();
            
            filteredLogs = allLogs.filter(log => {
                const matchesSearch = searchTerm === '' || 
                    log.name?.toLowerCase().includes(searchTerm) || 
                    log.email?.toLowerCase().includes(searchTerm) || 
                    log.action?.toLowerCase().includes(searchTerm) || 
                    log.description?.toLowerCase().includes(searchTerm) ||
                    log.ip_address?.toLowerCase().includes(searchTerm);
                
                const matchesAction = actionTerm === '' || log.action?.toLowerCase() === actionTerm;
                
                return matchesSearch && matchesAction;
            });
            
            totalLogs = filteredLogs.length;
            currentPage = 0;
            renderLogs();
        }
        
        // Logs rendern
        function renderLogs() {
            auditTbody.innerHTML = '';
            
            if (filteredLogs.length === 0) {
                emptyState.style.display = 'block';
                paginationElement.innerHTML = '';
                return;
            }
            
            emptyState.style.display = 'none';
            
            // Aktuelle Seite berechnen
            const startIndex = currentPage * logsPerPage;
            const endIndex = Math.min(startIndex + logsPerPage, filteredLogs.length);
            
            // Logs für die aktuelle Seite rendern
            for (let i = startIndex; i < endIndex; i++) {
                const log = filteredLogs[i];
                const row = document.createElement('tr');
                
                // Zeitstempel
                const timeCell = document.createElement('td');
                const date = new Date(log.created_at);
                timeCell.textContent = date.toLocaleString(i18n.locale);
                
                // Benutzer
                const userCell = document.createElement('td');
                userCell.textContent = log.name ? `${log.name} (${log.email})` : i18n.unknown;
                
                // Aktion mit Badge
                const actionCell = document.createElement('td');
                const actionBadge = document.createElement('span');
                actionBadge.className = 'badge';
                
                switch (log.action) {
                    case 'login':
                        actionBadge.textContent = i18n.actionLogin;
                        actionBadge.classList.add('badge-login');
                        break;
                    case 'logout':
                        actionBadge.textContent = i18n.actionLogout;
                        actionBadge.classList.add('badge-logout');
                        break;
                    case 'create_entry':
                        actionBadge.textContent = i18n.actionCreated;
                        actionBadge.classList.add('badge-create');
                        break;
                    case 'update_entry':
                        actionBadge.textContent = i18n.actionUpdated;
                        actionBadge.classList.add('badge-update');
                        break;
                    case 'delete_entry':
                        actionBadge.textContent = i18n.actionDeleted;
                        actionBadge.classList.add('badge-delete');
                        break;
                    case 'like':
                    case 'unlike':
                        actionBadge.textContent = log.action === 'like' ? i18n.actionLike : i18n.actionUnlike;
                        actionBadge.classList.add('badge-like');
                        break;
                    case 'send_notification':
                    case 'notify':
                        actionBadge.textContent = i18n.actionNotify;
                        actionBadge.classList.add('badge-notify');
                        break;
                    default:
                        actionBadge.textContent = log.action;
                }
                
                actionCell.appendChild(actionBadge);
                
                // Beschreibung
                const descCell = document.createElement('td');
                descCell.textContent = log.description || '-';
                
                // IP-Adresse
                const ipCell = document.createElement('td');
                ipCell.textContent = log.ip_address || '-';
                
                // Details-Button
                const detailsCell = document.createElement('td');
                
                if (log.data) {
                    const detailsButton = document.createElement('button');
                    detailsButton.className = 'detail-button';
                    detailsButton.textContent = i18n.showDetails;
                    detailsButton.addEventListener('click', () => showDetails(log));
                    detailsCell.appendChild(detailsButton);
                } else {
                    detailsCell.textContent = '-';
                }
                
                // Zellen zur Zeile hinzufügen
                row.appendChild(timeCell);
                row.appendChild(userCell);
                row.appendChild(actionCell);
                row.appendChild(descCell);
                row.appendChild(ipCell);
                row.appendChild(detailsCell);
                
                auditTbody.appendChild(row);
            }
            
            // Paginierung rendern
            renderPagination();
        }
        
        // Paginierung rendern
        function renderPagination() {
            paginationElement.innerHTML = '';
            
            if (filteredLogs.length === 0) {
                return;
            }
            
            const pageCount = Math.ceil(totalLogs / logsPerPage);
            
            // Zurück-Button
            const prevButton = document.createElement('button');
            prevButton.textContent = '«';
            prevButton.disabled = currentPage === 0;
            prevButton.addEventListener('click', () => {
                if (currentPage > 0) {
                    currentPage--;
                    renderLogs();
                }
            });
            paginationElement.appendChild(prevButton);
            
            // Seitenzahlen
            const startPage = Math.max(0, currentPage - 2);
            const endPage = Math.min(pageCount - 1, startPage + 4);
            
            for (let i = startPage; i <= endPage; i++) {
                const pageButton = document.createElement('button');
                pageButton.textContent = i + 1;
                pageButton.className = i === currentPage ? 'active' : '';
                pageButton.addEventListener('click', () => {
                    currentPage = i;
                    renderLogs();
                });
                paginationElement.appendChild(pageButton);
            }
            
            // Weiter-Button
            const nextButton = document.createElement('button');
            nextButton.textContent = '»';
            nextButton.disabled = currentPage >= pageCount - 1;
            nextButton.addEventListener('click', () => {
                if (currentPage < pageCount - 1) {
                    currentPage++;
                    renderLogs();
                }
            });
            paginationElement.appendChild(nextButton);
        }
        
        // Details anzeigen
        function showDetails(log) {
            modalContent.innerHTML = '';

            const appendDetailRow = (label, value) => {
                const row = document.createElement('div');
                const strong = document.createElement('strong');
                strong.textContent = `${label}: `;
                row.appendChild(strong);
                row.appendChild(document.createTextNode(value));
                modalContent.appendChild(row);
            };

            appendDetailRow(i18n.labelTime, new Date(log.created_at).toLocaleString(i18n.locale));
            appendDetailRow(i18n.labelUser, `${log.name} (${log.email})`);
            appendDetailRow(i18n.labelAction, String(log.action || '-'));
            appendDetailRow(i18n.labelDescription, String(log.description || '-'));
            appendDetailRow(i18n.labelIp, String(log.ip_address || '-'));
            
            // Daten als JSON anzeigen
            if (log.data) {
                const dataTitle = document.createElement('h4');
                dataTitle.textContent = i18n.labelDetailsData + ':';
                modalContent.appendChild(dataTitle);
                
                try {
                    // Falls die Daten bereits als Objekt vorliegen
                    const dataJson = typeof log.data === 'string' ? JSON.parse(log.data) : log.data;
                    
                    const dataPre = document.createElement('pre');
                    dataPre.textContent = JSON.stringify(dataJson, null, 2);
                    modalContent.appendChild(dataPre);
                } catch (e) {
                    // Falls die Daten kein gültiges JSON sind
                    const dataText = document.createElement('div');
                    dataText.textContent = log.data;
                    modalContent.appendChild(dataText);
                }
            }
            
            // Modal anzeigen
            detailsModal.style.display = 'block';
        }
    </script>
</body>
</html>

