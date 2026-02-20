<?php
// pages/reset-password.php - Passwort zurücksetzen für den Gipfeli-Koordinator

// Branding-Einstellungen laden
$appName = $config['app_name'] ?? 'Gipfeli-Koordinator';
$appLogo = $config['app_logo'] ?? '';
$primaryColor = $config['app_primary_color'] ?? '#e74c3c';
$secondaryColor = $config['app_secondary_color'] ?? '#6c757d';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passwort zurücksetzen - <?php echo htmlspecialchars($appName); ?></title>
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
    <div class="reset-container">
        <!-- Farbiger Header nur auf der Box -->
        <div class="box-header">
            <?php if (!empty($appLogo)): ?>
            <img src="<?php echo htmlspecialchars($appLogo); ?>" alt="Logo">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars($appName); ?></h1>
        </div>
        
        <div class="form-container">
            <h2 class="app-name">Passwort zurücksetzen</h2>
            <p class="description">Gib deine E-Mail-Adresse ein, um einen Link zum Zurücksetzen zu erhalten</p>
            
            <div id="error-message" class="alert alert-danger"></div>
            <div id="success-message" class="alert alert-success"></div>
            
            <form id="reset-form">
                <div class="form-group">
                    <label for="email">E-Mail-Adresse</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <button type="submit" id="submit-btn">Link zum Zurücksetzen senden</button>
            </form>
            
            <div class="links">
                <a href="?page=login">Zurück zur Anmeldung</a>
            </div>
        </div>
    </div>

    <script>
        // Reset-Formular
        const resetForm = document.getElementById('reset-form');
        const errorMessage = document.getElementById('error-message');
        const successMessage = document.getElementById('success-message');
        const submitBtn = document.getElementById('submit-btn');
        
        resetForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            // Button deaktivieren, um mehrfaches Absenden zu verhindern
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wird gesendet...';
            
            const email = document.getElementById('email').value;
            
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
                    successMessage.textContent = data.message || 'Falls die E-Mail registriert ist, wurde ein Link zum Zurücksetzen des Passworts gesendet.';
                    successMessage.style.display = 'block';
                    errorMessage.style.display = 'none';
                    resetForm.reset();
                } else {
                    // Fehler anzeigen
                    errorMessage.textContent = data.error || 'Fehler beim Zurücksetzen des Passworts';
                    errorMessage.style.display = 'block';
                    successMessage.style.display = 'none';
                }
            } catch (error) {
                errorMessage.textContent = 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.';
                errorMessage.style.display = 'block';
                successMessage.style.display = 'none';
                console.error('Passwort-Reset-Fehler:', error);
            } finally {
                // Button wieder aktivieren
                submitBtn.disabled = false;
                submitBtn.textContent = 'Link zum Zurücksetzen senden';
            }
        });
    </script>
</body>
</html>