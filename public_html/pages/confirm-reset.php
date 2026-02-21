<?php
// pages/confirm-reset.php - Passwort-Reset bestätigen für den Gipfeli-Koordinator

// Branding-Einstellungen laden
$appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
$appLogo = $config['app_logo'] ?? '';
$primaryColor = $config['app_primary_color'] ?? '#e74c3c';
$secondaryColor = $config['app_secondary_color'] ?? '#6c757d';

// Token aus der URL abrufen
$token = isset($_GET['token']) ? htmlspecialchars($_GET['token']) : '';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neues Passwort festlegen - <?php echo htmlspecialchars($appName); ?></title>
    <?php if (isset($config['app_favicon']) && !empty($config['app_favicon'])): ?>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars($config['app_favicon']); ?>" type="image/x-icon">
    <?php endif; ?>
    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
            --primary-dark: <?php echo adjustBrightness($primaryColor, -20); ?>;
            --secondary-color: <?php echo $secondaryColor; ?>;
            --secondary-dark: <?php echo adjustBrightness($secondaryColor, -20); ?>;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .reset-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            padding: 30px;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-height: 60px;
            max-width: 200px;
        }
        .app-name {
            color: var(--primary-color);
            margin: 10px 0 0 0;
            font-size: 24px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ddd;
            box-sizing: border-box;
        }
        button {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }
        button:hover {
            background-color: var(--primary-dark);
        }
        .links {
            margin-top: 20px;
            text-align: center;
        }
        .links a {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 14px;
        }
        .links a:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .description {
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        .password-strength {
            height: 5px;
            margin-top: 5px;
            border-radius: 2px;
            background-color: #ddd;
        }
        .password-strength-meter {
            height: 100%;
            border-radius: 2px;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        .password-rules {
            margin: 8px 0 0;
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
    <div class="reset-container">
        <div class="logo-container">
            <?php if (!empty($appLogo)): ?>
            <img src="<?php echo htmlspecialchars($appLogo); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <h1 class="app-name"><?php echo htmlspecialchars($appName); ?></h1>
            <p class="description">Neues Passwort festlegen</p>
        </div>
        
        <div id="message-container">
            <!-- Nachrichten werden hier dynamisch eingefügt -->
        </div>
        
        <?php if (empty($token)): ?>
            <div class="alert alert-danger">
                Kein gültiger Token gefunden. Bitte fordere einen neuen Link zum Zurücksetzen des Passworts an.
            </div>
            <div class="links">
                <a href="?page=reset-password">Passwort zurücksetzen</a>
                <br><br>
                <a href="?page=login">Zurück zur Anmeldung</a>
            </div>
        <?php else: ?>
            <form id="confirm-form">
                <input type="hidden" id="token" name="token" value="<?php echo $token; ?>">
                
                <div class="form-group">
                    <label for="password">Neues Passwort</label>
                    <input type="password" id="password" name="password" required minlength="10" maxlength="128">
                    <div class="password-strength">
                        <div class="password-strength-meter" id="password-strength-meter"></div>
                    </div>
                    <small>Mindestens 10 Zeichen und mindestens 3 Zeichentypen.</small>
                    <ul class="password-rules" id="password-rules">
                        <li id="rule-length" class="invalid">Mindestens 10 Zeichen</li>
                        <li id="rule-classes" class="invalid">Mindestens 3 Zeichentypen (Gross-/Kleinbuchstaben, Zahlen, Sonderzeichen)</li>
                    </ul>
                </div>
                
                <div class="form-group">
                    <label for="confirm-password">Passwort bestätigen</label>
                    <input type="password" id="confirm-password" name="confirm-password" required minlength="10" maxlength="128">
                </div>
                
                <button type="submit" id="submit-btn">Passwort aktualisieren</button>
            </form>
            
            <div class="links">
                <a href="?page=login">Zurück zur Anmeldung</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($token)): ?>
    <script>
        // DOM-Elemente
        const confirmForm = document.getElementById('confirm-form');
        const messageContainer = document.getElementById('message-container');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm-password');
        const submitBtn = document.getElementById('submit-btn');
        const passwordStrengthMeter = document.getElementById('password-strength-meter');
        const ruleLength = document.getElementById('rule-length');
        const ruleClasses = document.getElementById('rule-classes');
        
        // Hilfsfunktion zum Anzeigen von Nachrichten
        function showMessage(message, type) {
            messageContainer.innerHTML = `
                <div class="alert alert-${type}">
                    ${message}
                </div>
            `;
        }
        
        // Hilfsfunktion zur Passwort-Stärke-Bewertung
        function checkPasswordStrength(password) {
            let strength = 0;
            
            // Länge
            if (password.length >= 10) strength += 25;
            if (password.length >= 12) strength += 15;
            
            // Komplexität
            if (/[a-z]/.test(password)) strength += 10;
            if (/[A-Z]/.test(password)) strength += 10;
            if (/[0-9]/.test(password)) strength += 10;
            if (/[^a-zA-Z0-9]/.test(password)) strength += 10;
            
            // Verschiedene Zeichentypen
            const types = [/[a-z]/, /[A-Z]/, /[0-9]/, /[^a-zA-Z0-9]/];
            const typesCount = types.filter(type => type.test(password)).length;
            strength += typesCount * 5;
            
            // Maximum 100%
            return Math.min(strength, 100);
        }

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
        
        // Event-Listener für Passwort-Stärke
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            
            passwordStrengthMeter.style.width = `${strength}%`;
            
            // Farbe basierend auf Stärke
            if (strength < 40) {
                passwordStrengthMeter.style.backgroundColor = '#dc3545'; // rot
            } else if (strength < 70) {
                passwordStrengthMeter.style.backgroundColor = '#ffc107'; // gelb
            } else {
                passwordStrengthMeter.style.backgroundColor = '#28a745'; // grün
            }

            const policy = checkPasswordPolicy(password);
            setRuleState(ruleLength, policy.lengthOk);
            setRuleState(ruleClasses, policy.classesOk);
        });
        
        // Formular-Submit-Handler
        confirmForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const token = document.getElementById('token').value;
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            // Formularvalidierung
            const policy = checkPasswordPolicy(password);
            if (!policy.valid) {
                showMessage('Das Passwort erfüllt die Anforderungen noch nicht.', 'danger');
                return;
            }
            
            if (password !== confirmPassword) {
                showMessage('Die Passwörter stimmen nicht überein.', 'danger');
                return;
            }
            
            // Button deaktivieren, um mehrfaches Absenden zu verhindern
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wird aktualisiert...';
            
            try {
                const response = await fetch('?api=1&endpoint=confirm-reset', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ token, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Erfolgreiche Passwort-Zurücksetzung
                    showMessage(data.message || 'Dein Passwort wurde erfolgreich aktualisiert. Du wirst zur Anmeldeseite weitergeleitet.', 'success');
                    
                    // Formular ausblenden
                    confirmForm.style.display = 'none';
                    
                    // Nach 3 Sekunden zur Login-Seite weiterleiten
                    setTimeout(() => {
                        window.location.href = '?page=login';
                    }, 3000);
                } else {
                    // Fehler anzeigen
                    showMessage(data.error || 'Fehler beim Zurücksetzen des Passworts. Bitte versuche es erneut.', 'danger');
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Passwort aktualisieren';
                }
            } catch (error) {
                showMessage('Ein unerwarteter Fehler ist aufgetreten. Bitte versuche es später erneut.', 'danger');
                console.error('Fehler beim Passwort-Reset:', error);
                submitBtn.disabled = false;
                submitBtn.textContent = 'Passwort aktualisieren';
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
