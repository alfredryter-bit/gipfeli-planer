# Gipfeli Planer

Web-Anwendung zur Planung von Gipfeli-Einträgen mit Benutzer-Login, Admin-Bereich, Audit-Log und Branding.

## Projektstruktur

```text
Gipfeli Planer/
├─ public_html/         # Webroot
│  ├─ index.php         # App + API-Endpunkte
│  ├─ setup.php         # Installations-Setup
│  ├─ pages/            # UI-Seiten
│  └─ assets/           # Öffentliche statische Assets
└─ software_data/       # Nicht öffentlich (Konfiguration + Laufzeitdaten)
   └─ config.php        # Wird durch setup.php erzeugt
```

## Sicherheitsmodell (wichtig)

- `config.php` liegt **nicht** in der Webroot, sondern unter `software_data/config.php`.
- Session-Cookies sind gehärtet (`HttpOnly`, `SameSite`, `Secure` wenn HTTPS).
- Logout ist nur per `POST` erlaubt.
- Kritische API-Aktionen prüfen serverseitig Ownership.
- Admin-Branding nutzt CSRF-Token und validierte Datei-Uploads.
- `.htaccess` blockiert sensible Dateien und direkten Zugriff auf `pages/*.php`.

## Voraussetzungen

- PHP 8.1+ (empfohlen)
- MySQL/MariaDB
- Apache mit `mod_rewrite` (für API-Rewrite)

## Installation

1. `public_html` als Webroot konfigurieren.
2. Sicherstellen, dass `../software_data` für PHP beschreibbar ist.
3. `https://<deine-domain>/setup.php` aufrufen und Setup abschließen.
4. Nach Setup prüfen, dass `software_data/config.php` existiert und Rechte restriktiv sind (z. B. `640`).

## Betriebshinweise

- Wenn bereits eine Konfiguration existiert, ist `setup.php` gesperrt.
- `force_setup` ist nur für lokalen Zugriff (`127.0.0.1` / `::1`) gedacht.
- Keine Backups (`.zip`, `.sql`, etc.) in `public_html` ablegen.

## Entwicklung

- App-Logik und API: `public_html/index.php`
- Setup/Provisioning: `public_html/setup.php`
- UI-Seiten: `public_html/pages/`
- Branding-Assets: `public_html/assets/`
