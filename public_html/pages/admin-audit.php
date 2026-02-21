<?php
// pages/admin-audit.php - Audit-Log-Seite für Administratoren
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
    <title>Audit-Log - <?php echo htmlspecialchars($config['app_name']); ?></title>
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
                <li><a href="?page=admin-users">Benutzerverwaltung</a></li>
                <li><a href="?page=admin-audit" class="active">Audit-Log</a></li>
                <li><a href="?page=admin-settings"><i class="fas fa-cogs"></i> Einstellungen</a></li>
                <li><a href="?page=admin-branding"><i class="fas fa-paint-brush"></i> Branding</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    
    <div class="container">
        <h1>Audit-Log</h1>
        
        <div class="audit-controls">
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Suchen nach Benutzer, Aktion oder Beschreibung...">
                <button id="search-btn">Suchen</button>
                <button id="reset-btn" class="btn-secondary">Zurücksetzen</button>
            </div>
            <div>
                <select id="action-filter">
                    <option value="">Alle Aktionen</option>
                    <option value="login">Anmeldung</option>
                    <option value="logout">Abmeldung</option>
                    <option value="create_entry">Eintrag erstellt</option>
                    <option value="update_entry">Eintrag aktualisiert</option>
                    <option value="delete_entry">Eintrag gelöscht</option>
                    <option value="like">Like</option>
                    <option value="unlike">Unlike</option>
                    <option value="send_notification">Benachrichtigung</option>
                </select>
            </div>
        </div>
        
        <div id="loading" class="loading">
            <i class="fas fa-spinner fa-spin"></i> Lade Audit-Log...
        </div>
        
        <div id="error" class="error" style="display: none;"></div>
        
        <div class="responsive-table">
            <table id="audit-table">
                <thead>
                    <tr>
                        <th>Zeitpunkt</th>
                        <th>Benutzer</th>
                        <th>Aktion</th>
                        <th>Beschreibung</th>
                        <th>IP-Adresse</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody id="audit-tbody">
                    <!-- Audit-Log-Einträge werden hier eingefügt -->
                </tbody>
            </table>
        </div>
        
        <div id="empty-state" class="empty-state" style="display: none;">
            <i class="fas fa-search"></i> Keine Audit-Log-Einträge gefunden.
        </div>
        
        <div class="pagination" id="pagination">
            <!-- Seitenzahlen werden hier eingefügt -->
        </div>
    </div>
    
    <!-- Modal für Details -->
    <div id="details-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Audit-Log-Details</h3>
                <button class="close-modal" id="close-modal">&times;</button>
            </div>
            <div id="modal-content">
                <!-- Details werden hier eingefügt -->
            </div>
        </div>
    </div>

    <script>
        // API URL
        const API_URL = '?api=1&endpoint=';
        const CSRF_TOKEN = <?php echo json_encode(getCsrfToken()); ?>;
        
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
        const detailsModal = document.getElementById('details-modal');
        const closeModalBtn = document.getElementById('close-modal');
        const modalContent = document.getElementById('modal-content');
        
        // Alle Logs speichern
        let allLogs = [];
        
        // Event-Listener
        searchBtn.addEventListener('click', filterLogs);
        resetBtn.addEventListener('click', resetFilter);
        actionFilter.addEventListener('change', filterLogs);
        logoutBtn.addEventListener('click', logout);
        closeModalBtn.addEventListener('click', () => {
            detailsModal.style.display = 'none';
        });
        
        // Klick außerhalb des Modals schließt es
        window.addEventListener('click', (event) => {
            if (event.target === detailsModal) {
                detailsModal.style.display = 'none';
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
        
        // Audit-Log laden
        async function loadAuditLog() {
            loadingElement.style.display = 'block';
            errorElement.style.display = 'none';
            auditTbody.innerHTML = '';
            emptyState.style.display = 'none';
            
            try {
                const response = await fetch(API_URL + 'audit');
                if (!response.ok) {
                    throw new Error('Fehler beim Laden des Audit-Logs');
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
                errorElement.textContent = 'Fehler beim Laden des Audit-Logs: ' + error.message;
                errorElement.style.display = 'block';
                console.error('Fehler beim Laden des Audit-Logs:', error);
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
                timeCell.textContent = date.toLocaleString('de-CH');
                
                // Benutzer
                const userCell = document.createElement('td');
                userCell.textContent = log.name ? `${log.name} (${log.email})` : 'Unbekannt';
                
                // Aktion mit Badge
                const actionCell = document.createElement('td');
                const actionBadge = document.createElement('span');
                actionBadge.className = 'badge';
                
                switch (log.action) {
                    case 'login':
                        actionBadge.textContent = 'Anmeldung';
                        actionBadge.classList.add('badge-login');
                        break;
                    case 'logout':
                        actionBadge.textContent = 'Abmeldung';
                        actionBadge.classList.add('badge-logout');
                        break;
                    case 'create_entry':
                        actionBadge.textContent = 'Erstellt';
                        actionBadge.classList.add('badge-create');
                        break;
                    case 'update_entry':
                        actionBadge.textContent = 'Aktualisiert';
                        actionBadge.classList.add('badge-update');
                        break;
                    case 'delete_entry':
                        actionBadge.textContent = 'Gelöscht';
                        actionBadge.classList.add('badge-delete');
                        break;
                    case 'like':
                    case 'unlike':
                        actionBadge.textContent = log.action === 'like' ? 'Like' : 'Unlike';
                        actionBadge.classList.add('badge-like');
                        break;
                    case 'send_notification':
                    case 'notify':
                        actionBadge.textContent = 'Benachrichtigung';
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
                    detailsButton.textContent = 'Details anzeigen';
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

            appendDetailRow('Zeitpunkt', new Date(log.created_at).toLocaleString('de-CH'));
            appendDetailRow('Benutzer', `${log.name} (${log.email})`);
            appendDetailRow('Aktion', String(log.action || '-'));
            appendDetailRow('Beschreibung', String(log.description || '-'));
            appendDetailRow('IP-Adresse', String(log.ip_address || '-'));
            
            // Daten als JSON anzeigen
            if (log.data) {
                const dataTitle = document.createElement('h4');
                dataTitle.textContent = 'Detaildaten:';
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
