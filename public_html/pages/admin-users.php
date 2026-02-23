<?php
// pages/admin-users.php - Benutzerverwaltungsseite für Administratoren
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
    <title><?php echo htmlspecialchars(t('users.title')); ?> - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
        .users-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        button {
            background-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
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
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #bd2130;
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
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .badge-admin {
            background-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
            color: white;
        }
        .badge-super-admin {
            background-color: #111827;
            color: white;
        }
        .badge-user {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        .badge-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        .badge-inactive {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .responsive-table {
            overflow-x: auto;
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
        }
        .close-modal:hover {
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
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
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
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
        .actions-group {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .action-icon-btn {
            min-width: 34px;
            height: 34px;
            padding: 0 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            font-size: 13px;
        }
        .action-icon-btn.btn-warning {
            background-color: #f59e0b;
        }
        .action-icon-btn.btn-warning:hover {
            background-color: #d97706;
        }
        .action-icon-btn.btn-success {
            background-color: #10b981;
        }
        .action-icon-btn.btn-success:hover {
            background-color: #059669;
        }
        .app-title {
            color: white;
            margin: 0;
            font-size: 1.35rem;
            line-height: 1.2;
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
                <li><a href="?page=admin-users" class="active"><?php echo htmlspecialchars(t('nav.users')); ?></a></li>
                <li><a href="?page=admin-audit"><?php echo htmlspecialchars(t('nav.audit')); ?></a></li>
                <li><a href="?page=admin-settings"><i class="fas fa-cogs"></i> <?php echo htmlspecialchars(t('nav.settings')); ?></a></li>
                <li><a href="?page=admin-branding"><i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars(t('nav.branding')); ?></a></li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="container">
        <div class="users-header">
            <h1><?php echo htmlspecialchars(t('users.title')); ?></h1>
            <button id="add-user-btn"><?php echo htmlspecialchars(t('users.add')); ?></button>
        </div>
        
        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i> <?php echo htmlspecialchars(t('users.loading')); ?>
        </div>
        
        <div id="error-message" class="error" style="display: none;"></div>
        <div id="success-message" class="success" style="display: none;"></div>
        
        <div class="responsive-table">
            <table id="users-table">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars(t('users.table.name')); ?></th>
                        <th><?php echo htmlspecialchars(t('users.table.email')); ?></th>
                        <th><?php echo htmlspecialchars(t('users.table.role')); ?></th>
                        <th><?php echo htmlspecialchars(t('users.table.status')); ?></th>
                        <th><?php echo htmlspecialchars(t('users.table.last_active')); ?></th>
                        <th><?php echo htmlspecialchars(t('users.table.created')); ?></th>
                        <th><?php echo htmlspecialchars(t('users.table.actions')); ?></th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                    <!-- Benutzer werden hier eingefügt -->
                </tbody>
            </table>
        </div>
        
        <div id="empty-state" class="empty-state" style="display: none;">
            <i class="fas fa-users"></i> <?php echo htmlspecialchars(t('users.empty')); ?>
        </div>
    </div>
    
    <!-- Modal für Benutzer hinzufügen/bearbeiten -->
    <div id="user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title"><?php echo htmlspecialchars(t('users.add')); ?></h3>
                <button class="close-modal" id="close-modal">&times;</button>
            </div>
            <form id="user-form">
                <input type="hidden" id="user-id">
                
                <div class="form-group">
                    <label for="user-name"><?php echo htmlspecialchars(t('users.table.name')); ?>:</label>
                    <input type="text" id="user-name" required>
                </div>
                
                <div class="form-group">
                    <label for="user-email"><?php echo htmlspecialchars(t('users.table.email')); ?>:</label>
                    <input type="email" id="user-email" required>
                </div>
                
                <div class="form-group">
                    <label for="user-role"><?php echo htmlspecialchars(t('users.table.role')); ?>:</label>
                    <select id="user-role" required>
                        <option value="user"><?php echo htmlspecialchars(t('users.role.user')); ?></option>
                        <option value="admin"><?php echo htmlspecialchars(t('users.role.admin')); ?></option>
                        <?php if (isSuperAdmin()): ?>
                        <option value="super_admin"><?php echo htmlspecialchars(t('users.role.super_admin')); ?></option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="user-is-active"><?php echo htmlspecialchars(t('users.table.status')); ?>:</label>
                    <select id="user-is-active" required>
                        <option value="1"><?php echo htmlspecialchars(t('users.status.active')); ?></option>
                        <option value="0"><?php echo htmlspecialchars(t('users.status.inactive')); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="user-password"><?php echo htmlspecialchars(t('auth.password')); ?>:</label>
                    <input type="password" id="user-password" placeholder="<?php echo htmlspecialchars(t('users.password_optional_create')); ?>" minlength="10" maxlength="128">
                    <small><?php echo htmlspecialchars(t('auth.password_requirements')); ?></small>
                    <ul class="password-rules" id="password-rules">
                        <li id="rule-length" class="invalid"><?php echo htmlspecialchars(t('auth.password_rule_length')); ?></li>
                        <li id="rule-classes" class="invalid"><?php echo htmlspecialchars(t('auth.password_rule_classes')); ?></li>
                    </ul>
                </div>

                <div class="form-group checkbox-group" id="notify-user-group">
                    <input type="checkbox" id="notify-user" checked>
                    <label for="notify-user"><?php echo htmlspecialchars(t('users.notify_user')); ?></label>
                </div>
                
                <div>
                    <button type="submit" id="save-user-btn"><?php echo htmlspecialchars(t('common.save')); ?></button>
                    <button type="button" class="btn-secondary" id="cancel-btn"><?php echo htmlspecialchars(t('common.cancel')); ?></button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal für Benutzer löschen -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><?php echo htmlspecialchars(t('users.delete.title')); ?></h3>
                <button class="close-modal" id="close-delete-modal">&times;</button>
            </div>
            <p id="delete-user-question"></p>
            <p><strong><?php echo htmlspecialchars(t('users.delete.warning')); ?></strong></p>
            <div>
                <button id="confirm-delete-btn" class="btn-danger"><?php echo htmlspecialchars(t('users.delete.confirm')); ?></button>
                <button id="cancel-delete-btn" class="btn-secondary"><?php echo htmlspecialchars(t('common.cancel')); ?></button>
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
                    <ul class="password-rules" id="change-password-rules">
                        <li id="change-rule-length" class="invalid"><?php echo htmlspecialchars(t('auth.password_rule_length')); ?></li>
                        <li id="change-rule-classes" class="invalid"><?php echo htmlspecialchars(t('auth.password_rule_classes')); ?></li>
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
        const IS_SUPER_ADMIN = <?php echo isSuperAdmin() ? 'true' : 'false'; ?>;
        const i18n = {
            locale: <?php echo json_encode(t('locale.date')); ?>,
            passwordRequirements: <?php echo json_encode(t('password.error.requirements')); ?>,
            passwordMismatch: <?php echo json_encode(t('password.error.mismatch')); ?>,
            passwordChangeFailed: <?php echo json_encode(t('password.error.change_failed')); ?>,
            passwordChanged: <?php echo json_encode(t('password.changed_success')); ?>,
            usersLoadError: <?php echo json_encode(t('users.error.load')); ?>,
            roleSuperAdmin: <?php echo json_encode(t('users.role.super_admin')); ?>,
            roleAdmin: <?php echo json_encode(t('users.role.admin')); ?>,
            roleUser: <?php echo json_encode(t('users.role.user')); ?>,
            onlySuperAdmin: <?php echo json_encode(t('users.only_super_admin')); ?>,
            actionEdit: <?php echo json_encode(t('users.action.edit')); ?>,
            actionDelete: <?php echo json_encode(t('users.action.delete')); ?>,
            actionDisable: <?php echo json_encode(t('users.action.disable')); ?>,
            actionEnable: <?php echo json_encode(t('users.action.enable')); ?>,
            statusActive: <?php echo json_encode(t('users.status.active')); ?>,
            statusInactive: <?php echo json_encode(t('users.status.inactive')); ?>,
            addUser: <?php echo json_encode(t('users.add')); ?>,
            editUser: <?php echo json_encode(t('users.edit.title')); ?>,
            passwordOptionalCreate: <?php echo json_encode(t('users.password_optional_create')); ?>,
            passwordOptionalEdit: <?php echo json_encode(t('users.password_optional_edit')); ?>,
            errorSuperAdminOnly: <?php echo json_encode(t('users.error.super_admin_only')); ?>,
            errorRequired: <?php echo json_encode(t('users.save_error.required')); ?>,
            errorInvalidEmail: <?php echo json_encode(t('users.save_error.invalid_email')); ?>,
            errorNotifyRequired: <?php echo json_encode(t('users.save_error.notify_required')); ?>,
            errorPasswordPolicy: <?php echo json_encode(t('auth.error.password_requirements')); ?>,
            successUpdated: <?php echo json_encode(t('users.success.updated')); ?>,
            successCreated: <?php echo json_encode(t('users.success.created')); ?>,
            successDeleted: <?php echo json_encode(t('users.success.deleted')); ?>,
            successActivated: <?php echo json_encode(t('users.success.activated')); ?>,
            successDeactivated: <?php echo json_encode(t('users.success.deactivated')); ?>,
            statusChangeFailed: <?php echo json_encode(t('users.error.status')); ?>,
            deleteQuestionPattern: <?php echo json_encode(t('users.delete.question')); ?>,
            unknownError: <?php echo json_encode(t('common.unknown_error')); ?>
        };
        
        // DOM-Elemente
        const loadingElement = document.getElementById('loading');
        const errorMessage = document.getElementById('error-message');
        const successMessage = document.getElementById('success-message');
        const usersTbody = document.getElementById('users-tbody');
        const emptyState = document.getElementById('empty-state');
        const logoutBtn = document.getElementById('logout-btn');
        const changePasswordBtn = document.getElementById('change-password-btn');
        const passwordModal = document.getElementById('password-modal');
        const closePasswordModalBtn = document.getElementById('close-password-modal');
        const cancelPasswordBtn = document.getElementById('cancel-password-btn');
        const passwordForm = document.getElementById('password-form');
        const passwordStatus = document.getElementById('password-status');
        const newPasswordInput = document.getElementById('new-password');
        const changeRuleLength = document.getElementById('change-rule-length');
        const changeRuleClasses = document.getElementById('change-rule-classes');
        
        // User-Modal-Elemente
        const userModal = document.getElementById('user-modal');
        const closeModalBtn = document.getElementById('close-modal');
        const cancelBtn = document.getElementById('cancel-btn');
        const userForm = document.getElementById('user-form');
        const modalTitle = document.getElementById('modal-title');
        const userId = document.getElementById('user-id');
        const userName = document.getElementById('user-name');
        const userEmail = document.getElementById('user-email');
        const userRole = document.getElementById('user-role');
        const userIsActive = document.getElementById('user-is-active');
        const userPassword = document.getElementById('user-password');
        const notifyUserGroup = document.getElementById('notify-user-group');
        const notifyUserCheckbox = document.getElementById('notify-user');
        const ruleLength = document.getElementById('rule-length');
        const ruleClasses = document.getElementById('rule-classes');
        const addUserBtn = document.getElementById('add-user-btn');
        
        // Delete-Modal-Elemente
        const deleteModal = document.getElementById('delete-modal');
        const closeDeleteModalBtn = document.getElementById('close-delete-modal');
        const cancelDeleteBtn = document.getElementById('cancel-delete-btn');
        const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
        const deleteUserQuestion = document.getElementById('delete-user-question');

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

        userPassword.addEventListener('input', () => {
            const value = userPassword.value;
            if (!value) {
                setRuleState(ruleLength, false);
                setRuleState(ruleClasses, false);
                return;
            }
            const policy = checkPasswordPolicy(value);
            setRuleState(ruleLength, policy.lengthOk);
            setRuleState(ruleClasses, policy.classesOk);
        });
        
        // Event-Listener
        logoutBtn.addEventListener('click', logout);
        changePasswordBtn.addEventListener('click', (e) => {
            e.preventDefault();
            passwordForm.reset();
            passwordStatus.style.display = 'none';
            setRuleState(changeRuleLength, false);
            setRuleState(changeRuleClasses, false);
            passwordModal.style.display = 'block';
        });
        closePasswordModalBtn.addEventListener('click', () => passwordModal.style.display = 'none');
        cancelPasswordBtn.addEventListener('click', () => passwordModal.style.display = 'none');
        closeModalBtn.addEventListener('click', () => userModal.style.display = 'none');
        cancelBtn.addEventListener('click', () => userModal.style.display = 'none');
        closeDeleteModalBtn.addEventListener('click', () => deleteModal.style.display = 'none');
        cancelDeleteBtn.addEventListener('click', () => deleteModal.style.display = 'none');
        addUserBtn.addEventListener('click', () => showAddUserModal());
        userForm.addEventListener('submit', saveUser);
        confirmDeleteBtn.addEventListener('click', deleteUser);
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
            setRuleState(changeRuleLength, policy.lengthOk);
            setRuleState(changeRuleClasses, policy.classesOk);
        });
        
        // Klick außerhalb der Modals schließt sie
        window.addEventListener('click', (event) => {
            if (event.target === userModal) {
                userModal.style.display = 'none';
            }
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
            }
            if (event.target === passwordModal) {
                passwordModal.style.display = 'none';
            }
        });
        
        // Initialisierung
        loadUsers();
        
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
        
        // Benutzer laden
        async function loadUsers() {
            loadingElement.style.display = 'block';
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';
            usersTbody.innerHTML = '';
            emptyState.style.display = 'none';
            
            try {
                const response = await fetch(API_URL + 'users');
                if (!response.ok) {
                    throw new Error(i18n.usersLoadError);
                }
                
                const data = await response.json();
                
                if (!data.users || data.users.length === 0) {
                    emptyState.style.display = 'block';
                    return;
                }
                
                renderUsers(data.users);
            } catch (error) {
                errorMessage.textContent = i18n.usersLoadError + ': ' + error.message;
                errorMessage.style.display = 'block';
                console.error(i18n.usersLoadError, error);
            } finally {
                loadingElement.style.display = 'none';
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

        function formatDateTime(value) {
            if (!value) {
                return '-';
            }
            const date = new Date(value.replace(' ', 'T'));
            if (Number.isNaN(date.getTime())) {
                return '-';
            }
            return date.toLocaleString(i18n.locale);
        }
        
        // Benutzer rendern
        function renderUsers(users) {
            usersTbody.innerHTML = '';
            
            users.forEach(user => {
                const row = document.createElement('tr');
                
                // Name
                const nameCell = document.createElement('td');
                nameCell.textContent = user.name;
                
                // E-Mail
                const emailCell = document.createElement('td');
                emailCell.textContent = user.email;
                
                // Rolle mit Badge
                const roleCell = document.createElement('td');
                const roleBadge = document.createElement('span');
                if (user.role === 'super_admin') {
                    roleBadge.className = 'badge badge-super-admin';
                    roleBadge.textContent = i18n.roleSuperAdmin;
                } else if (user.role === 'admin') {
                    roleBadge.className = 'badge badge-admin';
                    roleBadge.textContent = i18n.roleAdmin;
                } else {
                    roleBadge.className = 'badge badge-user';
                    roleBadge.textContent = i18n.roleUser;
                }
                roleCell.appendChild(roleBadge);

                // Status
                const statusCell = document.createElement('td');
                const statusBadge = document.createElement('span');
                const isActive = parseInt(user.is_active, 10) === 1;
                statusBadge.className = 'badge ' + (isActive ? 'badge-active' : 'badge-inactive');
                statusBadge.textContent = isActive ? i18n.statusActive : i18n.statusInactive;
                statusCell.appendChild(statusBadge);

                // Letzte Aktivität
                const lastActiveCell = document.createElement('td');
                lastActiveCell.textContent = formatDateTime(user.last_active_at);
                
                // Registrierungsdatum
                const dateCell = document.createElement('td');
                const date = new Date(user.created_at);
                dateCell.textContent = date.toLocaleDateString(i18n.locale);
                
                // Aktionen
                const actionsCell = document.createElement('td');
                const actionsGroup = document.createElement('div');
                actionsGroup.className = 'actions-group';

                if (!IS_SUPER_ADMIN && user.role === 'super_admin') {
                    actionsCell.textContent = i18n.onlySuperAdmin;
                    row.appendChild(nameCell);
                    row.appendChild(emailCell);
                    row.appendChild(roleCell);
                    row.appendChild(statusCell);
                    row.appendChild(lastActiveCell);
                    row.appendChild(dateCell);
                    row.appendChild(actionsCell);
                    usersTbody.appendChild(row);
                    return;
                }
                
                // Bearbeiten-Button
                const editBtn = document.createElement('button');
                editBtn.className = 'action-icon-btn';
                editBtn.title = i18n.actionEdit;
                editBtn.setAttribute('aria-label', i18n.actionEdit);
                editBtn.innerHTML = '<i class="fas fa-user-edit"></i>';
                editBtn.addEventListener('click', () => showEditUserModal(user));
                actionsGroup.appendChild(editBtn);

                // Aktivieren/Deaktivieren (nicht für eigenen Benutzer)
                if (user.id != <?php echo $_SESSION['user_id']; ?>) {
                    const toggleBtn = document.createElement('button');
                    const activeNow = parseInt(user.is_active, 10) === 1;
                    toggleBtn.className = 'action-icon-btn ' + (activeNow ? 'btn-warning' : 'btn-success');
                    toggleBtn.title = activeNow ? i18n.actionDisable : i18n.actionEnable;
                    toggleBtn.setAttribute('aria-label', toggleBtn.title);
                    toggleBtn.innerHTML = activeNow
                        ? '<i class="fas fa-user-slash"></i>'
                        : '<i class="fas fa-user-check"></i>';
                    toggleBtn.addEventListener('click', () => toggleUserStatus(user));
                    actionsGroup.appendChild(toggleBtn);
                }
                
                // Löschen-Button (nicht für den eigenen Benutzer)
                if (user.id != <?php echo $_SESSION['user_id']; ?>) {
                    const deleteBtn = document.createElement('button');
                    deleteBtn.className = 'action-icon-btn btn-danger';
                    deleteBtn.title = i18n.actionDelete;
                    deleteBtn.setAttribute('aria-label', i18n.actionDelete);
                    deleteBtn.innerHTML = '<i class="fas fa-trash-alt"></i>';
                    deleteBtn.addEventListener('click', () => showDeleteUserModal(user));
                    actionsGroup.appendChild(deleteBtn);
                }
                actionsCell.appendChild(actionsGroup);
                
                // Zellen zur Zeile hinzufügen
                row.appendChild(nameCell);
                row.appendChild(emailCell);
                row.appendChild(roleCell);
                row.appendChild(statusCell);
                row.appendChild(lastActiveCell);
                row.appendChild(dateCell);
                row.appendChild(actionsCell);
                
                usersTbody.appendChild(row);
            });
        }
        
        // Modal zum Hinzufügen eines Benutzers anzeigen
        function showAddUserModal() {
            modalTitle.textContent = i18n.addUser;
            userId.value = '';
            userName.value = '';
            userEmail.value = '';
            userRole.value = 'user';
            userIsActive.value = '1';
            userPassword.value = '';
            userPassword.required = false;
            userPassword.placeholder = i18n.passwordOptionalCreate;
            userEmail.readOnly = false;
            notifyUserCheckbox.checked = true;
            notifyUserGroup.style.display = 'flex';
            setRuleState(ruleLength, false);
            setRuleState(ruleClasses, false);
            
            userModal.style.display = 'block';
        }

        async function toggleUserStatus(user) {
            const activeNow = parseInt(user.is_active, 10) === 1;
            const targetState = activeNow ? 0 : 1;
            loadingElement.style.display = 'block';
            errorMessage.style.display = 'none';

            try {
                const response = await fetch(API_URL + 'users', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify({
                        id: user.id,
                        is_active: targetState
                    })
                });
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || i18n.statusChangeFailed);
                }
                showSuccess(targetState === 1 ? i18n.successActivated : i18n.successDeactivated);
                await loadUsers();
            } catch (error) {
                showError(error.message);
            } finally {
                loadingElement.style.display = 'none';
            }
        }
        
        // Modal zum Bearbeiten eines Benutzers anzeigen
        function showEditUserModal(user) {
            if (!IS_SUPER_ADMIN && user.role === 'super_admin') {
                showError(i18n.errorSuperAdminOnly);
                return;
            }
            modalTitle.textContent = i18n.editUser;
            userId.value = user.id;
            userName.value = user.name;
            userEmail.value = user.email;
            userRole.value = user.role;
            userIsActive.value = (parseInt(user.is_active, 10) === 1) ? '1' : '0';
            userPassword.value = '';
            userPassword.required = false;
            userPassword.placeholder = i18n.passwordOptionalEdit;
            userEmail.readOnly = true;
            notifyUserGroup.style.display = 'none';
            setRuleState(ruleLength, false);
            setRuleState(ruleClasses, false);
            
            userModal.style.display = 'block';
        }
        
        // Modal zum Löschen eines Benutzers anzeigen
        function showDeleteUserModal(user) {
            deleteUserQuestion.textContent = i18n.deleteQuestionPattern.replace('%s', `${user.name} (${user.email})`);
            confirmDeleteBtn.dataset.userId = user.id;
            
            deleteModal.style.display = 'block';
        }
        
        // Benutzer speichern (hinzufügen oder aktualisieren)
        async function saveUser(e) {
            e.preventDefault();
            
            const id = userId.value;
            const name = userName.value.trim();
            const email = userEmail.value.trim();
            const role = userRole.value;
            const isActive = parseInt(userIsActive.value, 10) === 1 ? 1 : 0;
            const password = userPassword.value;
            const notifyUser = notifyUserCheckbox.checked;
            
            // Validierung
            if (!name || !email) {
                showError(i18n.errorRequired);
                return;
            }
            if (!userEmail.checkValidity()) {
                showError(i18n.errorInvalidEmail);
                return;
            }
            if (!id && password) {
                const policy = checkPasswordPolicy(password);
                if (!policy.valid) {
                    showError(i18n.errorPasswordPolicy);
                    return;
                }
            }
            if (!id && !password && !notifyUser) {
                showError(i18n.errorNotifyRequired);
                return;
            }
            if (id && password) {
                const policy = checkPasswordPolicy(password);
                if (!policy.valid) {
                    showError(i18n.errorPasswordPolicy);
                    return;
                }
            }
            
            loadingElement.style.display = 'block';
            errorMessage.style.display = 'none';
            
            try {
                const userData = { name, role, is_active: isActive };
                
                if (id) {
                    // Benutzer aktualisieren
                    userData.id = id;
                    userData.email = email;
                    if (password) {
                        userData.password = password;
                    }
                    
                    const response = await fetch(API_URL + 'users', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': CSRF_TOKEN
                        },
                        body: JSON.stringify(userData)
                    });
                    
                    if (!response.ok) {
                        const data = await response.json();
                        throw new Error(data.error || i18n.usersLoadError);
                    }
                    
                    showSuccess(i18n.successUpdated);
                } else {
                    // Neuen Benutzer erstellen
                    userData.email = email;
                    userData.password = password;
                    userData.notify_user = notifyUser;
                    
                    const response = await fetch(API_URL + 'admin-create-user', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-Token': CSRF_TOKEN
                        },
                        body: JSON.stringify(userData)
                    });
                    const data = await response.json();
                    
                    if (!response.ok) {
                        throw new Error(data.error || i18n.unknownError);
                    }
                    
                    showSuccess(data.message || i18n.successCreated);
                }
                
                userModal.style.display = 'none';
                await loadUsers();
            } catch (error) {
                showError(error.message);
            } finally {
                loadingElement.style.display = 'none';
            }
        }
        
        // Benutzer löschen
        async function deleteUser() {
            const userId = confirmDeleteBtn.dataset.userId;
            
            loadingElement.style.display = 'block';
            errorMessage.style.display = 'none';
            
            try {
                const response = await fetch(API_URL + 'users', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': CSRF_TOKEN
                    },
                    body: JSON.stringify({ id: userId })
                });
                
                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.error || i18n.unknownError);
                }
                
                deleteModal.style.display = 'none';
                showSuccess(i18n.successDeleted);
                await loadUsers();
            } catch (error) {
                showError(error.message);
            } finally {
                loadingElement.style.display = 'none';
            }
        }
        
        // Fehlermeldung anzeigen
        function showError(message) {
            errorMessage.textContent = message;
            errorMessage.style.display = 'block';
            successMessage.style.display = 'none';
        }
        
        // Erfolgsmeldung anzeigen
        function showSuccess(message) {
            successMessage.textContent = message;
            successMessage.style.display = 'block';
            errorMessage.style.display = 'none';
            
            // Nach 3 Sekunden ausblenden
            setTimeout(() => {
                successMessage.style.display = 'none';
            }, 3000);
        }
    </script>
</body>
</html>

