<?php
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
    <title><?php echo htmlspecialchars($appName); ?> - Start</title>
    <?php if (!empty($config['app_favicon'])): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($config['app_favicon']); ?>">
    <?php endif; ?>
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($primaryColor); ?>;
            --primary-dark: <?php echo htmlspecialchars(adjustBrightness($primaryColor, -20)); ?>;
            --secondary-color: <?php echo htmlspecialchars($secondaryColor); ?>;
            --secondary-dark: <?php echo htmlspecialchars(adjustBrightness($secondaryColor, -20)); ?>;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #1f2937;
            background: radial-gradient(circle at top, #ffffff 0%, #f4f6f8 55%, #ebeff3 100%);
            min-height: 100vh;
        }
        .wrap {
            max-width: 920px;
            margin: 0 auto;
            padding: 28px 18px 40px;
        }
        .hero {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }
        .hero-head {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: #fff;
            padding: 24px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .hero-head img {
            max-height: 46px;
            width: auto;
        }
        .hero-head h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        .hero-body {
            padding: 24px 20px 22px;
        }
        .lead {
            font-size: 1.05rem;
            line-height: 1.55;
            margin: 0 0 14px;
        }
        .features {
            margin: 0;
            padding-left: 18px;
            line-height: 1.65;
        }
        .actions {
            margin-top: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            text-decoration: none;
            border-radius: 8px;
            padding: 10px 14px;
            font-weight: bold;
            border: 1px solid transparent;
        }
        .btn-primary {
            background: var(--primary-color);
            color: #fff;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        .btn-secondary {
            background: #fff;
            color: #1f2937;
            border-color: #cbd5e1;
        }
        .btn-secondary:hover {
            background: #f8fafc;
        }
        .hint {
            margin-top: 14px;
            font-size: 0.92rem;
            color: #475569;
        }
    </style>
</head>
<body>
    <div class="wrap">
        <section class="hero">
            <div class="hero-head">
                <?php if (!empty($appLogo)): ?>
                <img src="<?php echo htmlspecialchars($appLogo); ?>" alt="Logo">
                <?php endif; ?>
                <h1><?php echo htmlspecialchars($appName); ?></h1>
            </div>
            <div class="hero-body">
                <p class="lead">
                    Der Gipfeli Planer koordiniert zuverlässig, wer wann Gipfeli mitbringt. Kein Doppel-Eintrag, klare Übersicht und direkte Benachrichtigungen.
                </p>
                <ul class="features">
                    <li>Schneller Kalender für Einträge und Änderungen</li>
                    <li>Automatische Benachrichtigungen im Team</li>
                    <li>Admin-Bereich für Benutzer, Audit und Branding</li>
                    <li>Passwort-Reset und sichere Benutzerverwaltung</li>
                </ul>
                <div class="actions">
                    <a class="btn btn-primary" href="?page=login">Anmelden</a>
                    <a class="btn btn-secondary" href="?page=register">Registrieren</a>
                    <a class="btn btn-secondary" href="?page=reset-password">Passwort vergessen</a>
                </div>
                <p class="hint">Tipp: Direktlink für später: <code>?page=start</code></p>
            </div>
        </section>
    </div>
</body>
</html>
