<?php
// pages/register.php - Registrierungsseite für den Gipfeli-Koordinator

// Branding-Einstellungen laden
$appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
$appLogo = $config['app_logo'] ?? '';
$primaryColor = $config['app_primary_color'] ?? '#e74c3c';
$secondaryColor = $config['app_secondary_color'] ?? '#6c757d';
// Erlaubte Domains deaktivieren - stattdessen leeres Array 
$allowedDomains = []; // Leeres Array - keine Domain-Einschränkung
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(t('meta.lang')); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('auth.register')); ?> - <?php echo htmlspecialchars($appName); ?></title>
    <?php if (isset($config['app_favicon']) && !empty($config['app_favicon'])): ?>
    <link rel="shortcut icon" href="<?php echo htmlspecialchars(cacheBustUrl($config['app_favicon'])); ?>" type="image/x-icon">
    <?php endif; ?>
    <style>
        :root {
            --primary-color: <?php echo $primaryColor; ?>;
            --primary-dark: <?php echo adjustBrightness($primaryColor, -20); ?>;
            --secondary-color: <?php echo $secondaryColor; ?>;
            --secondary-dark: <?php echo adjustBrightness($secondaryColor, -20); ?>;
            --layout-width: 1100px;
        }
        
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            min-height: 100vh;
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
        .app-title-top {
            color: white;
            margin: 0;
            font-size: 1.35rem;
            line-height: 1.2;
        }
        .language-switch {
            display: flex;
            align-items: center;
            gap: 8px;
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
        nav ul li a.active {
            color: var(--primary-color);
            font-weight: bold;
        }
        .page-shell {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px 0;
        }
        
        .register-container {
            width: 350px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden; /* Damit der Header innerhalb der Box bleibt */
        }
        
        /* Box-Header Styling */
        .box-header {
            background-color: var(--primary-color);
            color: white;
            padding: 15px 20px;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }
        
        .box-header img {
            max-height: 40px;
        }
        
        .box-header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        /* Formular-Container */
        .form-container {
            background-color: white;
            padding: 25px;
        }
        
        .app-name {
            color: var(--primary-color);
            margin: 0 0 5px 0;
            font-size: 24px;
            text-align: center;
        }
        
        .description {
            text-align: center;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
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
            display: none;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        
        small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
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
    <header>
        <div class="header-content">
            <div style="display: flex; align-items: center; gap: 10px;">
                <?php if (!empty($appLogo)): ?>
                <img src="<?php echo htmlspecialchars(cacheBustUrl($appLogo)); ?>" alt="Logo" height="40">
                <?php endif; ?>
                <h2 class="app-title-top"><?php echo htmlspecialchars($appName); ?></h2>
            </div>
            <div class="language-switch">
                <a href="<?php echo htmlspecialchars(buildPageUrl(['lang' => 'de'])); ?>" class="<?php echo getCurrentLanguage() === 'de' ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('lang.de')); ?></a>
                <span style="color: #fff; opacity: 0.7;">|</span>
                <a href="<?php echo htmlspecialchars(buildPageUrl(['lang' => 'en'])); ?>" class="<?php echo getCurrentLanguage() === 'en' ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('lang.en')); ?></a>
            </div>
        </div>
    </header>
    <nav>
        <ul>
            <li><a href="?page=start"><?php echo htmlspecialchars(t('nav.start')); ?></a></li>
            <li><a href="?page=login"><?php echo htmlspecialchars(t('nav.login')); ?></a></li>
            <li><a href="?page=register" class="active"><?php echo htmlspecialchars(t('nav.register')); ?></a></li>
            <li><a href="?page=reset-password"><?php echo htmlspecialchars(t('nav.reset')); ?></a></li>
        </ul>
    </nav>
    <div class="page-shell">
    <div class="register-container">
        <!-- Farbiger Header nur auf der Box -->
        <div class="box-header">
            <?php if (!empty($appLogo)): ?>
            <img src="<?php echo htmlspecialchars(cacheBustUrl($appLogo)); ?>" alt="Logo">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($appName); ?></h1>
        </div>
        
        <div class="form-container">
            <h2 class="app-name"><?php echo htmlspecialchars(t('auth.register')); ?></h2>
            <p class="description"><?php echo htmlspecialchars(t('auth.register_description')); ?></p>
            
            <div id="error-message" class="alert alert-danger"></div>
            <div id="success-message" class="alert alert-success"></div>
            
            <form id="register-form">
                <div class="form-group">
                    <label for="name"><?php echo htmlspecialchars(t('auth.name')); ?></label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label for="email"><?php echo htmlspecialchars(t('auth.email')); ?></label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password"><?php echo htmlspecialchars(t('auth.password')); ?></label>
                    <input type="password" id="password" name="password" required minlength="10" maxlength="128">
                    <small><?php echo htmlspecialchars(t('auth.password_requirements')); ?></small>
                    <ul class="password-rules" id="password-rules">
                        <li id="rule-length" class="invalid"><?php echo htmlspecialchars(t('auth.password_rule_length')); ?></li>
                        <li id="rule-classes" class="invalid"><?php echo htmlspecialchars(t('auth.password_rule_classes')); ?></li>
                    </ul>
                </div>
                
                <button type="submit"><?php echo htmlspecialchars(t('auth.register')); ?></button>
            </form>
            
            <div class="links">
                <a href="?page=start"><?php echo htmlspecialchars(t('auth.back_start')); ?></a><br>
                <a href="?page=login"><?php echo htmlspecialchars(t('auth.register_existing')); ?></a>
            </div>
        </div>
    </div>
    </div>

    <script>
        const i18n = {
            invalidEmail: <?php echo json_encode(t('auth.error.invalid_email')); ?>,
            passwordRequirements: <?php echo json_encode(t('auth.error.password_requirements')); ?>,
            registerSuccess: <?php echo json_encode(t('auth.register_success_default')); ?>,
            registerFailed: <?php echo json_encode(t('auth.error.register_failed')); ?>,
            genericError: <?php echo json_encode(t('auth.error.generic')); ?>
        };

        // Register-Formular
        const registerForm = document.getElementById('register-form');
        const errorMessage = document.getElementById('error-message');
        const successMessage = document.getElementById('success-message');
        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');
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

        passwordInput.addEventListener('input', () => {
            const state = checkPasswordPolicy(passwordInput.value);
            setRuleState(ruleLength, state.lengthOk);
            setRuleState(ruleClasses, state.classesOk);
        });
        
        registerForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const name = document.getElementById('name').value;
            const email = emailInput.value;
            const password = passwordInput.value;

            if (!emailInput.checkValidity()) {
                errorMessage.textContent = i18n.invalidEmail;
                errorMessage.style.display = 'block';
                successMessage.style.display = 'none';
                return;
            }
            const policyState = checkPasswordPolicy(password);
            if (!policyState.valid) {
                errorMessage.textContent = i18n.passwordRequirements;
                errorMessage.style.display = 'block';
                successMessage.style.display = 'none';
                return;
            }
            
            // Domain-Überprüfung entfernt
            
            try {
                const response = await fetch('?api=1&endpoint=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ name, email, password })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Erfolgreiche Registrierung
                    successMessage.textContent = data.message || i18n.registerSuccess;
                    successMessage.style.display = 'block';
                    errorMessage.style.display = 'none';
                    registerForm.reset();
                    
                    // Nach 3 Sekunden zur Login-Seite weiterleiten
                    setTimeout(() => {
                        window.location.href = '?page=login';
                    }, 3000);
                } else {
                    // Fehler anzeigen
                    errorMessage.textContent = data.error || i18n.registerFailed;
                    errorMessage.style.display = 'block';
                    successMessage.style.display = 'none';
                }
            } catch (error) {
                errorMessage.textContent = i18n.genericError;
                errorMessage.style.display = 'block';
                successMessage.style.display = 'none';
                console.error('Registrierungs-Fehler:', error);
            }
        });
    </script>
</body>
</html>

