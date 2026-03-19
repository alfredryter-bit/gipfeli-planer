<?php
declare(strict_types=1);

if (!function_exists('ls_client_ip')) {
    function ls_client_ip(): string
    {
        $candidates = [];

        $cf = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
        if (is_string($cf) && $cf !== '') {
            $candidates[] = trim($cf);
        }

        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if (is_string($xff) && $xff !== '') {
            $parts = array_map('trim', explode(',', $xff));
            if (!empty($parts)) {
                $candidates[] = $parts[0];
            }
        }

        $ra = $_SERVER['REMOTE_ADDR'] ?? '';
        if (is_string($ra) && $ra !== '') {
            $candidates[] = trim($ra);
        }

        foreach ($candidates as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return '0.0.0.0';
    }
}

if (!function_exists('ls_ensure_dir')) {
    function ls_ensure_dir(string $dir): bool
    {
        if (is_dir($dir)) {
            return true;
        }
        return @mkdir($dir, 0770, true) || is_dir($dir);
    }
}

if (!function_exists('ls_rotate_daily')) {
    function ls_rotate_daily(string $file): void
    {
        if (!is_file($file)) {
            return;
        }
        $today = gmdate('Y-m-d');
        $mtime = @filemtime($file);
        if ($mtime === false) {
            return;
        }
        $fileDay = gmdate('Y-m-d', $mtime);
        if ($fileDay === $today) {
            return;
        }

        $rotated = $file . '.' . $fileDay;
        if (!is_file($rotated)) {
            @rename($file, $rotated);
        }
    }
}

if (!function_exists('ls_write_jsonl')) {
    function ls_write_jsonl(string $file, array $row): void
    {
        $dir = dirname($file);
        if (!ls_ensure_dir($dir)) {
            return;
        }
        ls_rotate_daily($file);

        $json = json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return;
        }
        @file_put_contents($file, $json . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

if (!function_exists('ls_base_event')) {
    function ls_base_event(string $type): array
    {
        return [
            'ts' => gmdate('c'),
            'event_type' => $type,
            'ip' => ls_client_ip(),
            'ip_source' => isset($_SERVER['HTTP_CF_CONNECTING_IP']) ? 'cf-connecting-ip'
                : (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? 'x-forwarded-for' : 'remote_addr'),
            'method' => (string)($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            'uri' => (string)($_SERVER['REQUEST_URI'] ?? '/'),
            'host' => (string)($_SERVER['HTTP_HOST'] ?? ''),
            'referer' => (string)($_SERVER['HTTP_REFERER'] ?? ''),
            'ua' => (string)($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ];
    }
}

if (!function_exists('ls_access_log')) {
    function ls_access_log(string $logFile): void
    {
        $row = ls_base_event('http_access');
        $row['status'] = http_response_code() ?: 200;
        ls_write_jsonl($logFile, $row);
    }
}

if (!function_exists('ls_security_log')) {
    function ls_security_log(string $logFile, string $type, string $detail = '', ?int $status = null, array $extra = []): void
    {
        $row = ls_base_event($type);
        if ($detail !== '') {
            $row['detail'] = $detail;
        }
        if ($status !== null) {
            $row['status'] = $status;
        }
        foreach ($extra as $k => $v) {
            $row[$k] = $v;
        }
        ls_write_jsonl($logFile, $row);
    }
}

if (!function_exists('ls_render_error_page')) {
    function ls_render_error_page(int $status, string $title, string $message): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="de"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
        echo '<style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;background:#0f172a;color:#e2e8f0;display:grid;place-items:center;min-height:100vh;margin:0}';
        echo '.box{max-width:720px;padding:28px;border-radius:14px;background:#111827;border:1px solid #334155}h1{margin:0 0 10px;font-size:28px}p{margin:0;line-height:1.5;color:#cbd5e1}</style>';
        echo '</head><body><div class="box"><h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
    }
}

if (!function_exists('ls_auth_state_load')) {
    function ls_auth_state_load(string $stateFile): array
    {
        $dir = dirname($stateFile);
        if (!ls_ensure_dir($dir)) {
            return [];
        }
        if (!is_file($stateFile)) {
            return [];
        }
        $raw = @file_get_contents($stateFile);
        $decoded = json_decode((string)$raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('ls_auth_state_save')) {
    function ls_auth_state_save(string $stateFile, array $state): void
    {
        $dir = dirname($stateFile);
        if (!ls_ensure_dir($dir)) {
            return;
        }
        $json = json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return;
        }
        @file_put_contents($stateFile, $json, LOCK_EX);
    }
}

if (!function_exists('ls_auth_prune_timestamps')) {
    function ls_auth_prune_timestamps(array $timestamps, int $cutoff): array
    {
        $result = [];
        foreach ($timestamps as $ts) {
            if (is_int($ts) && $ts >= $cutoff) {
                $result[] = $ts;
            }
        }
        return $result;
    }
}

if (!function_exists('ls_auth_should_alert')) {
    function ls_auth_should_alert(array &$state, string $key, int $now, int $cooldownSeconds): bool
    {
        if (!isset($state['alerts']) || !is_array($state['alerts'])) {
            $state['alerts'] = [];
        }
        $last = isset($state['alerts'][$key]) && is_int($state['alerts'][$key]) ? $state['alerts'][$key] : 0;
        if ($last > 0 && ($now - $last) < $cooldownSeconds) {
            return false;
        }
        $state['alerts'][$key] = $now;
        return true;
    }
}

if (!function_exists('ls_normalize_auth_identifier')) {
    function ls_normalize_auth_identifier(?string $identifier): string
    {
        $value = strtolower(trim((string)$identifier));
        if ($value === '') {
            return '';
        }
        if (strlen($value) > 190) {
            $value = substr($value, 0, 190);
        }
        return preg_replace('/\s+/', ' ', $value) ?? $value;
    }
}

if (!function_exists('ls_monitor_auth_post')) {
    function ls_monitor_auth_post(
        string $securityLogFile,
        string $stateFile,
        string $endpoint,
        string $identifier = ''
    ): void {
        $endpoint = strtolower(trim($endpoint));
        if ($endpoint !== 'login' && $endpoint !== 'register') {
            return;
        }

        $now = time();
        $windowSeconds = 600;
        $cutoff = $now - $windowSeconds;
        $ip = ls_client_ip();
        $normalizedIdentifier = ls_normalize_auth_identifier($identifier);

        $state = ls_auth_state_load($stateFile);
        if (!isset($state['posts']) || !is_array($state['posts'])) {
            $state['posts'] = [];
        }
        if (!isset($state['identifiers']) || !is_array($state['identifiers'])) {
            $state['identifiers'] = [];
        }

        $postKey = $ip . '|' . $endpoint;
        $postList = isset($state['posts'][$postKey]) && is_array($state['posts'][$postKey]) ? $state['posts'][$postKey] : [];
        $postList = ls_auth_prune_timestamps($postList, $cutoff);
        $postList[] = $now;
        $state['posts'][$postKey] = $postList;

        if (!isset($state['identifiers'][$postKey]) || !is_array($state['identifiers'][$postKey])) {
            $state['identifiers'][$postKey] = [];
        }
        $identifierMap = $state['identifiers'][$postKey];
        foreach ($identifierMap as $name => $timestamps) {
            if (!is_array($timestamps)) {
                unset($identifierMap[$name]);
                continue;
            }
            $pruned = ls_auth_prune_timestamps($timestamps, $cutoff);
            if (empty($pruned)) {
                unset($identifierMap[$name]);
            } else {
                $identifierMap[$name] = $pruned;
            }
        }
        if ($normalizedIdentifier !== '') {
            if (!isset($identifierMap[$normalizedIdentifier]) || !is_array($identifierMap[$normalizedIdentifier])) {
                $identifierMap[$normalizedIdentifier] = [];
            }
            $identifierMap[$normalizedIdentifier][] = $now;
        }
        $state['identifiers'][$postKey] = $identifierMap;

        ls_auth_state_save($stateFile, $state);

        $authPostExtra = [
            'endpoint' => $endpoint,
            'post_count_window' => count($postList),
        ];
        if ($normalizedIdentifier !== '') {
            $authPostExtra['username'] = $normalizedIdentifier;
        }
        ls_security_log($securityLogFile, 'auth_post', 'auth endpoint POST', null, $authPostExtra);

        if (count($postList) >= 12) {
            $alertKey = 'burst|' . $postKey;
            if (ls_auth_should_alert($state, $alertKey, $now, 300)) {
                ls_security_log(
                    $securityLogFile,
                    'auth_post_burst',
                    'high volume auth posts from same ip',
                    429,
                    [
                        'endpoint' => $endpoint,
                        'post_count_window' => count($postList),
                        'window_seconds' => $windowSeconds,
                    ]
                );
            }
        }

        $distinctUsers = count($identifierMap);
        if ($distinctUsers >= 8 && count($postList) >= 10) {
            $alertKey = 'stuffing|' . $postKey;
            if (ls_auth_should_alert($state, $alertKey, $now, 600)) {
                ls_security_log(
                    $securityLogFile,
                    'credential_stuffing_pattern',
                    'many usernames from same ip in auth window',
                    429,
                    [
                        'endpoint' => $endpoint,
                        'distinct_usernames_window' => $distinctUsers,
                        'post_count_window' => count($postList),
                        'window_seconds' => $windowSeconds,
                    ]
                );
            }
        }

        ls_auth_state_save($stateFile, $state);
    }
}
