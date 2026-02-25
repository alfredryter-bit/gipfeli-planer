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
        .tiny-hint {
            margin-top: 14px;
            text-align: center;
            font-size: 10px;
            color: #94a3b8;
            letter-spacing: 0.02em;
            user-select: none;
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
    </style>
</head>
<body>
    <header>
        <div class="header-content">
            <div style="display: flex; align-items: center; gap: 10px;">
                <?php if (!empty($appLogo)): ?>
                <img src="<?php echo htmlspecialchars(cacheBustUrl($appLogo)); ?>" alt="Logo" height="40">
                <?php endif; ?>
                <h2 class="app-title" id="app-title-trigger"><?php echo htmlspecialchars($appName); ?></h2>
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
            </div>
        </section>
        <div class="tiny-hint"><?php echo htmlspecialchars(t('start.konami_hint')); ?></div>
    </div>
    <script>
        const konamiCode = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];
        let konamiIndex = 0;
        const easterMessages = [
            <?php echo json_encode(t('main.easter.1')); ?>,
            <?php echo json_encode(t('main.easter.2')); ?>,
            <?php echo json_encode(t('main.easter.3')); ?>,
            <?php echo json_encode(t('main.easter.4')); ?>,
            <?php echo json_encode(t('main.easter.5')); ?>
        ];

        document.addEventListener('keydown', (e) => {
            if (e.key === konamiCode[konamiIndex]) {
                konamiIndex++;
                if (konamiIndex === konamiCode.length) {
                    activateEasterEgg();
                    konamiIndex = 0;
                }
            } else {
                konamiIndex = 0;
            }
        });

        function setupMobileEasterEgg() {
            const trigger = document.getElementById('app-title-trigger');
            if (!trigger) return;

            let pressTimer = null;
            let longPressActive = false;

            trigger.addEventListener('touchstart', () => {
                longPressActive = false;
                pressTimer = setTimeout(() => {
                    longPressActive = true;
                    trigger.style.color = '#FFD700';
                }, 1500);
            }, { passive: true });

            trigger.addEventListener('touchmove', () => {
                clearTimeout(pressTimer);
            }, { passive: true });

            trigger.addEventListener('touchend', () => {
                clearTimeout(pressTimer);
                if (longPressActive) {
                    activateEasterEgg();
                    setTimeout(() => {
                        trigger.style.color = '';
                    }, 600);
                }
            }, { passive: true });
        }

        function activateEasterEgg() {
            const isMobile = /iPhone|iPad|iPod|Android/i.test(navigator.userAgent);
            const gipfeliCount = isMobile ? 140 : 280;
            const gipfeliEmojis = ['🥐', '🥐', '🥐', '🥐', '🥖', '🍞'];

            for (let i = 0; i < gipfeliCount; i++) {
                const gipfeli = document.createElement('div');
                gipfeli.textContent = gipfeliEmojis[Math.floor(Math.random() * gipfeliEmojis.length)];
                gipfeli.style.position = 'fixed';
                gipfeli.style.left = Math.random() * 100 + 'vw';
                gipfeli.style.top = (Math.random() * -280) + 'px';
                gipfeli.style.fontSize = (Math.random() * (isMobile ? 18 : 26) + 14) + 'px';
                gipfeli.style.transform = 'rotate(' + Math.random() * 360 + 'deg)';
                gipfeli.style.zIndex = '9999';
                gipfeli.style.opacity = Math.random() * 0.4 + 0.6;

                const duration = Math.random() * 8 + 8;
                gipfeli.style.transition = 'top ' + duration + 's linear, transform ' + duration + 's ease-in-out';

                document.body.appendChild(gipfeli);

                setTimeout(() => {
                    gipfeli.style.top = '110vh';
                    gipfeli.style.transform = 'rotate(' + (Math.random() * 720 - 360) + 'deg)';
                }, Math.random() * 1800);

                setTimeout(() => {
                    if (document.body.contains(gipfeli)) {
                        document.body.removeChild(gipfeli);
                    }
                }, duration * 1000 + 2200);
            }

            const message = document.createElement('div');
            message.textContent = easterMessages[Math.floor(Math.random() * easterMessages.length)];
            message.style.position = 'fixed';
            message.style.top = '50%';
            message.style.left = '50%';
            message.style.transform = 'translate(-50%, -50%)';
            message.style.background = 'rgba(255, 255, 255, 0.92)';
            message.style.padding = isMobile ? '14px 18px' : '18px 24px';
            message.style.borderRadius = '12px';
            message.style.boxShadow = '0 0 20px rgba(0,0,0,0.35)';
            message.style.zIndex = '10000';
            message.style.fontWeight = 'bold';
            message.style.fontSize = isMobile ? '18px' : '24px';
            message.style.textAlign = 'center';
            message.style.color = 'var(--primary-color)';
            document.body.appendChild(message);

            setTimeout(() => {
                message.style.transition = 'transform 0.7s ease-in, opacity 0.7s ease-in';
                message.style.transform = 'translate(-50%, -50%) scale(1.7)';
                message.style.opacity = '0';
                setTimeout(() => {
                    if (document.body.contains(message)) {
                        document.body.removeChild(message);
                    }
                }, 700);
            }, 3200);
        }

        document.addEventListener('DOMContentLoaded', setupMobileEasterEgg);
    </script>
</body>
</html>

