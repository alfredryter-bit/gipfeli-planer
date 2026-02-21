<?php
// pages/admin-users.php - Benutzerverwaltungsseite für Administratoren
// Diese Seite ist nur für Administratoren zugänglich
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
    <title>Benutzerverwaltung - <?php echo htmlspecialchars($config['app_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <?php if (!empty($config['app_favicon'])): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($config['app_favicon']); ?>">
    <?php endif; ?>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        header {
            background-color: <?php echo htmlspecialchars($config['app_primary_color']); ?>;
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
            max-width: 1000px;
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
            max-width: 1000px;
            margin: 0 auto;
        }
        nav ul li {
            padding: 10px 15px;
        }
        nav ul li a {
            text-decoration: none;
            color: #333;
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
    </style>
</head>
<body>
       <header>
        <div class="header-content">
    <div style="display: flex; align-items: center; gap: 10px;">
        <?php if (!empty($config['app_logo'])): ?>
        <img src="<?php echo htmlspecialchars($config['app_logo']); ?>" alt="Logo" height="40">
        <?php endif; ?>
        <h2 style="color: white; margin: 0;"><?php echo htmlspecialchars($config['app_name']); ?></h2>
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
            <?php if (isAdmin()): ?>
                <li><a href="?page=admin-users" class="active">Benutzerverwaltung</a></li>
                <li><a href="?page=admin-audit">Audit-Log</a></li>
                <li><a href="?page=admin-settings"><i class="fas fa-cogs"></i> Einstellungen</a></li>
                <li><a href="?page=admin-branding"><i class="fas fa-paint-brush"></i> Branding</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="container">
        <div class="users-header">
            <h1>Benutzerverwaltung</h1>
            <button id="add-user-btn">Benutzer hinzufügen</button>
        </div>
        
        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i> Lade Benutzer...
        </div>
        
        <div id="error-message" class="error" style="display: none;"></div>
        <div id="success-message" class="success" style="display: none;"></div>
        
        <div class="responsive-table">
            <table id="users-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Rolle</th>
                        <th>Status</th>
                        <th>Letzte Aktivität</th>
                        <th>Registriert am</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody id="users-tbody">
                    <!-- Benutzer werden hier eingefügt -->
                </tbody>
            </table>
        </div>
        
        <div id="empty-state" class="empty-state" style="display: none;">
            <i class="fas fa-users"></i> Keine Benutzer gefunden.
        </div>
    </div>
    
    <!-- Modal für Benutzer hinzufügen/bearbeiten -->
    <div id="user-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title">Benutzer hinzufügen</h3>
                <button class="close-modal" id="close-modal">&times;</button>
            </div>
            <form id="user-form">
                <input type="hidden" id="user-id">
                
                <div class="form-group">
                    <label for="user-name">Name:</label>
                    <input type="text" id="user-name" required>
                </div>
                
                <div class="form-group">
                    <label for="user-email">E-Mail:</label>
                    <input type="email" id="user-email" required>
                </div>
                
                <div class="form-group">
                    <label for="user-role">Rolle:</label>
                    <select id="user-role" required>
                        <option value="user">Benutzer</option>
                        <option value="admin">Administrator</option>
                        <?php if (isSuperAdmin()): ?>
                        <option value="super_admin">Super-Admin</option>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="user-is-active">Status:</label>
                    <select id="user-is-active" required>
                        <option value="1">Aktiv</option>
                        <option value="0">Deaktiviert</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="user-password">Passwort:</label>
                    <input type="password" id="user-password" placeholder="Optional - leer = Passwort per Link setzen" minlength="10" maxlength="128">
                    <small>Mindestens 10 Zeichen und mindestens 3 Zeichentypen.</small>
                    <ul class="password-rules" id="password-rules">
                        <li id="rule-length" class="invalid">Mindestens 10 Zeichen</li>
                        <li id="rule-classes" class="invalid">Mindestens 3 Zeichentypen (Gross-/Kleinbuchstaben, Zahlen, Sonderzeichen)</li>
                    </ul>
                </div>

                <div class="form-group checkbox-group" id="notify-user-group">
                    <input type="checkbox" id="notify-user" checked>
                    <label for="notify-user">Benutzer informieren (E-Mail)</label>
                </div>
                
                <div>
                    <button type="submit" id="save-user-btn">Speichern</button>
                    <button type="button" class="btn-secondary" id="cancel-btn">Abbrechen</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal für Benutzer löschen -->
    <div id="delete-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Benutzer löschen</h3>
                <button class="close-modal" id="close-delete-modal">&times;</button>
            </div>
            <p>Möchtest du den Benutzer <strong id="delete-user-name"></strong> wirklich löschen?</p>
            <p><strong>Diese Aktion kann nicht rückgängig gemacht werden!</strong></p>
            <div>
                <button id="confirm-delete-btn" class="btn-danger">Ja, löschen</button>
                <button id="cancel-delete-btn" class="btn-secondary">Abbrechen</button>
            </div>
        </div>
    </div>

    <script>
        // API URL
        const API_URL = '?api=1&endpoint=';
        const CSRF_TOKEN = '<?php echo getCsrfToken(); ?>';
        const IS_SUPER_ADMIN = <?php echo isSuperAdmin() ? 'true' : 'false'; ?>;
        
        // DOM-Elemente
        const loadingElement = document.getElementById('loading');
        const errorMessage = document.getElementById('error-message');
        const successMessage = document.getElementById('success-message');
        const usersTbody = document.getElementById('users-tbody');
        const emptyState = document.getElementById('empty-state');
        const logoutBtn = document.getElementById('logout-btn');
        
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
        const deleteUserName = document.getElementById('delete-user-name');

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
        closeModalBtn.addEventListener('click', () => userModal.style.display = 'none');
        cancelBtn.addEventListener('click', () => userModal.style.display = 'none');
        closeDeleteModalBtn.addEventListener('click', () => deleteModal.style.display = 'none');
        cancelDeleteBtn.addEventListener('click', () => deleteModal.style.display = 'none');
        addUserBtn.addEventListener('click', () => showAddUserModal());
        userForm.addEventListener('submit', saveUser);
        confirmDeleteBtn.addEventListener('click', deleteUser);
        
        // Klick außerhalb der Modals schließt sie
        window.addEventListener('click', (event) => {
            if (event.target === userModal) {
                userModal.style.display = 'none';
            }
            if (event.target === deleteModal) {
                deleteModal.style.display = 'none';
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
                    throw new Error('Fehler beim Laden der Benutzer');
                }
                
                const data = await response.json();
                
                if (!data.users || data.users.length === 0) {
                    emptyState.style.display = 'block';
                    return;
                }
                
                renderUsers(data.users);
            } catch (error) {
                errorMessage.textContent = 'Fehler beim Laden der Benutzer: ' + error.message;
                errorMessage.style.display = 'block';
                console.error('Fehler beim Laden der Benutzer:', error);
            } finally {
                loadingElement.style.display = 'none';
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
            return date.toLocaleString('de-CH');
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
                    roleBadge.textContent = 'Super-Admin';
                } else if (user.role === 'admin') {
                    roleBadge.className = 'badge badge-admin';
                    roleBadge.textContent = 'Administrator';
                } else {
                    roleBadge.className = 'badge badge-user';
                    roleBadge.textContent = 'Benutzer';
                }
                roleCell.appendChild(roleBadge);

                // Status
                const statusCell = document.createElement('td');
                const statusBadge = document.createElement('span');
                const isActive = parseInt(user.is_active, 10) === 1;
                statusBadge.className = 'badge ' + (isActive ? 'badge-active' : 'badge-inactive');
                statusBadge.textContent = isActive ? 'Aktiv' : 'Deaktiviert';
                statusCell.appendChild(statusBadge);

                // Letzte Aktivität
                const lastActiveCell = document.createElement('td');
                lastActiveCell.textContent = formatDateTime(user.last_active_at);
                
                // Registrierungsdatum
                const dateCell = document.createElement('td');
                const date = new Date(user.created_at);
                dateCell.textContent = date.toLocaleDateString('de-CH');
                
                // Aktionen
                const actionsCell = document.createElement('td');

                if (!IS_SUPER_ADMIN && user.role === 'super_admin') {
                    actionsCell.textContent = 'Nur Super-Admin';
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
                editBtn.innerHTML = '<i class="fas fa-edit"></i> Bearbeiten';
                editBtn.addEventListener('click', () => showEditUserModal(user));
                actionsCell.appendChild(editBtn);
                
                // Löschen-Button (nicht für den eigenen Benutzer)
                if (user.id != <?php echo $_SESSION['user_id']; ?>) {
                    const deleteBtn = document.createElement('button');
                    deleteBtn.innerHTML = '<i class="fas fa-trash"></i> Löschen';
                    deleteBtn.className = 'btn-danger';
                    deleteBtn.style.marginLeft = '5px';
                    deleteBtn.addEventListener('click', () => showDeleteUserModal(user));
                    actionsCell.appendChild(deleteBtn);
                }
                
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
            modalTitle.textContent = 'Benutzer hinzufügen';
            userId.value = '';
            userName.value = '';
            userEmail.value = '';
            userRole.value = 'user';
            userIsActive.value = '1';
            userPassword.value = '';
            userPassword.required = false;
            userPassword.placeholder = 'Optional - leer = Passwort per Link setzen';
            userEmail.readOnly = false;
            notifyUserCheckbox.checked = true;
            notifyUserGroup.style.display = 'flex';
            setRuleState(ruleLength, false);
            setRuleState(ruleClasses, false);
            
            userModal.style.display = 'block';
        }
        
        // Modal zum Bearbeiten eines Benutzers anzeigen
        function showEditUserModal(user) {
            if (!IS_SUPER_ADMIN && user.role === 'super_admin') {
                showError('Super-Admin kann nur vom Super-Admin bearbeitet werden');
                return;
            }
            modalTitle.textContent = 'Benutzer bearbeiten';
            userId.value = user.id;
            userName.value = user.name;
            userEmail.value = user.email;
            userRole.value = user.role;
            userIsActive.value = (parseInt(user.is_active, 10) === 1) ? '1' : '0';
            userPassword.value = '';
            userPassword.required = false;
            userPassword.placeholder = 'Leer lassen, um nicht zu ändern';
            userEmail.readOnly = true;
            notifyUserGroup.style.display = 'none';
            setRuleState(ruleLength, false);
            setRuleState(ruleClasses, false);
            
            userModal.style.display = 'block';
        }
        
        // Modal zum Löschen eines Benutzers anzeigen
        function showDeleteUserModal(user) {
            deleteUserName.textContent = `${user.name} (${user.email})`;
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
                showError('Name und E-Mail sind erforderlich');
                return;
            }
            if (!userEmail.checkValidity()) {
                showError('Bitte gib eine gültige E-Mail-Adresse ein');
                return;
            }
            if (!id && password) {
                const policy = checkPasswordPolicy(password);
                if (!policy.valid) {
                    showError('Passwort erfüllt die Anforderungen noch nicht');
                    return;
                }
            }
            if (!id && !password && !notifyUser) {
                showError('Ohne Passwort muss "Benutzer informieren" aktiviert sein');
                return;
            }
            if (id && password) {
                const policy = checkPasswordPolicy(password);
                if (!policy.valid) {
                    showError('Passwort erfüllt die Anforderungen noch nicht');
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
                        throw new Error(data.error || 'Fehler beim Aktualisieren des Benutzers');
                    }
                    
                    showSuccess('Benutzer erfolgreich aktualisiert');
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
                        throw new Error(data.error || 'Fehler beim Erstellen des Benutzers');
                    }
                    
                    showSuccess(data.message || 'Benutzer erfolgreich erstellt');
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
                    throw new Error(data.error || 'Fehler beim Löschen des Benutzers');
                }
                
                deleteModal.style.display = 'none';
                showSuccess('Benutzer erfolgreich gelöscht');
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
