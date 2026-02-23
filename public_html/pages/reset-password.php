<?php
// pages/reset-password.php - Passwort zurücksetzen für den Gipfeli-Koordinator

// Branding-Einstellungen laden
$appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
$appLogo = $config['app_logo'] ?? '';
$primaryColor = $config['app_primary_color'] ?? '#e74c3c';
$secondaryColor = $config['app_secondary_color'] ?? '#6c757d';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars(t('meta.lang')); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(t('auth.reset')); ?> - <?php echo htmlspecialchars($appName); ?></title>
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
            padding: 30px 0;
        }
        
        .reset-container {
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
            <li><a href="?page=register"><?php echo htmlspecialchars(t('nav.register')); ?></a></li>
            <li><a href="?page=reset-password" class="active"><?php echo htmlspecialchars(t('nav.reset')); ?></a></li>
        </ul>
    </nav>
    <div class="page-shell">
    <div class="reset-container">
        <!-- Farbiger Header nur auf der Box -->
        <div class="box-header">
            <?php if (!empty($appLogo)): ?>
            <img src="<?php echo htmlspecialchars(cacheBustUrl($appLogo)); ?>" alt="Logo">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($appName); ?></h1>
        </div>
        
        <div class="form-container">
            <h2 class="app-name"><?php echo htmlspecialchars(t('auth.reset')); ?></h2>
            <p class="description"><?php echo htmlspecialchars(t('auth.reset_description')); ?></p>
            
            <div id="error-message" class="alert alert-danger"></div>
            <div id="success-message" class="alert alert-success"></div>
            
            <form id="reset-form">
                <div class="form-group">
                    <label for="email"><?php echo htmlspecialchars(t('auth.email')); ?></label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <button type="submit" id="submit-btn"><?php echo htmlspecialchars(t('auth.reset_send_link')); ?></button>
            </form>
            
            <div class="links">
                <a href="?page=start"><?php echo htmlspecialchars(t('auth.back_start')); ?></a><br><br>
                <a href="?page=login"><?php echo htmlspecialchars(t('auth.back_login')); ?></a>
            </div>
        </div>
    </div>
    </div>

    <script>
        const i18n = {
            sending: <?php echo json_encode(t('auth.reset_sending')); ?>,
            sendLink: <?php echo json_encode(t('auth.reset_send_link')); ?>,
            invalidEmail: <?php echo json_encode(t('auth.error.invalid_email')); ?>,
            resetSuccess: <?php echo json_encode(t('auth.reset_success_default')); ?>,
            resetError: <?php echo json_encode(t('auth.error.reset_failed')); ?>,
            genericError: <?php echo json_encode(t('auth.error.generic')); ?>
        };

        // Reset-Formular
        const resetForm = document.getElementById('reset-form');
        const errorMessage = document.getElementById('error-message');
        const successMessage = document.getElementById('success-message');
        const submitBtn = document.getElementById('submit-btn');
        const emailInput = document.getElementById('email');
        
        resetForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Button deaktivieren, um mehrfaches Absenden zu verhindern
            submitBtn.disabled = true;
            submitBtn.textContent = i18n.sending;
            
            const email = emailInput.value;
            if (!emailInput.checkValidity()) {
                errorMessage.textContent = i18n.invalidEmail;
                errorMessage.style.display = 'block';
                successMessage.style.display = 'none';
                submitBtn.disabled = false;
                submitBtn.textContent = i18n.sendLink;
                return;
            }
            
            try {
                const response = await fetch('?api=1&endpoint=reset-password', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ email })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Erfolgreiche Anfrage
                    successMessage.textContent = data.message || i18n.resetSuccess;
                    successMessage.style.display = 'block';
                    errorMessage.style.display = 'none';
                    resetForm.reset();
                } else {
                    // Fehler anzeigen
                    errorMessage.textContent = data.error || i18n.resetError;
                    errorMessage.style.display = 'block';
                    successMessage.style.display = 'none';
                }
            } catch (error) {
                errorMessage.textContent = i18n.genericError;
                errorMessage.style.display = 'block';
                successMessage.style.display = 'none';
                console.error('Passwort-Reset-Fehler:', error);
            } finally {
                // Button wieder aktivieren
                submitBtn.disabled = false;
                submitBtn.textContent = i18n.sendLink;
            }
        });
    </script>
</body>
</html>

