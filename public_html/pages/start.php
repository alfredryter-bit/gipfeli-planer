<?php
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
    <title><?php echo htmlspecialchars($appName); ?> - <?php echo htmlspecialchars(t('start.title')); ?></title>
    <?php if (!empty($config['app_favicon'])): ?>
    <link rel="icon" href="<?php echo htmlspecialchars(cacheBustUrl($config['app_favicon'])); ?>">
    <?php endif; ?>
    <style>
        :root {
            --primary-color: <?php echo htmlspecialchars($primaryColor); ?>;
            --primary-dark: <?php echo htmlspecialchars(adjustBrightness($primaryColor, -20)); ?>;
            --secondary-color: <?php echo htmlspecialchars($secondaryColor); ?>;
            --secondary-dark: <?php echo htmlspecialchars(adjustBrightness($secondaryColor, -20)); ?>;
            --layout-width: 1100px;
        }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #1f2937;
            background: radial-gradient(circle at top, #ffffff 0%, #f4f6f8 55%, #ebeff3 100%);
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
        .app-title {
            color: white;
            margin: 0;
            font-size: 1.35rem;
            line-height: 1.2;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
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
    <header>
        <div class="header-content">
            <div style="display: flex; align-items: center; gap: 10px;">
                <?php if (!empty($appLogo)): ?>
                <img src="<?php echo htmlspecialchars(cacheBustUrl($appLogo)); ?>" alt="Logo" height="40">
                <?php endif; ?>
                <h2 class="app-title"><?php echo htmlspecialchars($appName); ?></h2>
            </div>
            <div class="user-info">
                <div class="language-switch">
                    <a href="<?php echo htmlspecialchars(buildPageUrl(['lang' => 'de'])); ?>" class="<?php echo getCurrentLanguage() === 'de' ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('lang.de')); ?></a>
                    <span style="color: #fff; opacity: 0.7;">|</span>
                    <a href="<?php echo htmlspecialchars(buildPageUrl(['lang' => 'en'])); ?>" class="<?php echo getCurrentLanguage() === 'en' ? 'active' : ''; ?>"><?php echo htmlspecialchars(t('lang.en')); ?></a>
                </div>
            </div>
        </div>
    </header>
    <nav>
        <ul>
            <li><a href="?page=start" class="active"><?php echo htmlspecialchars(t('nav.start')); ?></a></li>
            <li><a href="?page=login"><?php echo htmlspecialchars(t('nav.login')); ?></a></li>
            <li><a href="?page=register"><?php echo htmlspecialchars(t('nav.register')); ?></a></li>
            <li><a href="?page=reset-password"><?php echo htmlspecialchars(t('nav.reset')); ?></a></li>
        </ul>
    </nav>
    <div class="wrap">
        <section class="hero">
            <div class="hero-head">
                <?php if (!empty($appLogo)): ?>
                <img src="<?php echo htmlspecialchars(cacheBustUrl($appLogo)); ?>" alt="Logo">
                <?php endif; ?>
                <h1><?php echo htmlspecialchars($appName); ?></h1>
            </div>
            <div class="hero-body">
                <p class="lead"><?php echo htmlspecialchars(t('start.lead')); ?></p>
                <ul class="features">
                    <li><?php echo htmlspecialchars(t('start.feature.calendar')); ?></li>
                    <li><?php echo htmlspecialchars(t('start.feature.notify')); ?></li>
                    <li><?php echo htmlspecialchars(t('start.feature.admin')); ?></li>
                    <li><?php echo htmlspecialchars(t('start.feature.security')); ?></li>
                </ul>
                <div class="actions">
                    <a class="btn btn-primary" href="?page=login"><?php echo htmlspecialchars(t('nav.login')); ?></a>
                    <a class="btn btn-secondary" href="?page=register"><?php echo htmlspecialchars(t('nav.register')); ?></a>
                    <a class="btn btn-secondary" href="?page=reset-password"><?php echo htmlspecialchars(t('nav.reset')); ?></a>
                </div>
                <p class="hint"><?php echo htmlspecialchars(t('start.hint')); ?> <code>?page=start</code></p>
            </div>
        </section>
    </div>
</body>
</html>

