<?php
declare(strict_types=1);

/**
 * Security helpers: IP geo restriction for admin, rate limits, headers.
 */

function client_ip(): string {
    // Prefer direct connection; only trust X-Forwarded-For if behind a known proxy (configure if needed)
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        $cand = $parts[0] ?? '';
        if (filter_var($cand, FILTER_VALIDATE_IP)) $ip = $cand;
    }
    return $ip;
}

/** Rough check: is IP in Belarus (BY)? Uses ip-api.com (no key, rate-limited) with local cache. */
function ip_is_belarus(string $ip): bool {
    if ($ip === '127.0.0.1' || $ip === '::1' || str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.')) {
        return true; // localhost / private — allow for development
    }
    $cacheFile = ROOT . '/storage/ipgeo_' . md5($ip) . '.json';
    if (is_file($cacheFile) && filemtime($cacheFile) > time() - 86400 * 7) {
        $j = json_decode((string)file_get_contents($cacheFile), true);
        return ($j['countryCode'] ?? '') === 'BY';
    }
    // Fallback ranges (major Belarus prefixes) when external lookup fails
    $byPrefixes = ['178.172.', '178.124.', '178.120.', '178.121.', '178.122.', '178.123.',
        '93.85.', '93.84.', '93.125.', '37.17.', '37.212.', '37.213.', '37.214.', '37.215.',
        '46.53.', '46.56.', '46.216.', '80.94.', '82.209.', '86.57.', '87.252.', '91.149.',
        '178.125.', '178.126.', '178.127.', '188.123.', '194.158.', '213.184.'];
    $isByPrefix = false;
    foreach ($byPrefixes as $p) {
        if (str_starts_with($ip, $p)) { $isByPrefix = true; break; }
    }
    $ctx = stream_context_create(['http' => ['timeout' => 2, 'ignore_errors' => true]]);
    $raw = @file_get_contents('http://ip-api.com/json/' . urlencode($ip) . '?fields=status,countryCode', false, $ctx);
    if ($raw) {
        $j = json_decode($raw, true);
        if (($j['status'] ?? '') === 'success') {
            @file_put_contents($cacheFile, json_encode(['countryCode' => $j['countryCode'] ?? '']));
            return ($j['countryCode'] ?? '') === 'BY';
        }
    }
    return $isByPrefix;
}

function require_belarus_admin(): void {
    $enabled = setting('admin_by_only', '1') === '1';
    if (!$enabled) return;
    $ip = client_ip();
    if (!ip_is_belarus($ip)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit("Доступ в админку разрешён только с IP-адресов Беларуси.\nYour IP: $ip");
    }
}

function send_security_headers(): void {
    if (headers_sent()) return;
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    // Mild CSP — allow inline styles (theme) and same-origin scripts
    // frame-src includes 'self' so admin live-preview iframe works; YouTube/Vimeo for video blocks
    header("Content-Security-Policy: default-src 'self'; img-src 'self' data: blob: https:; media-src 'self' https:; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; frame-src 'self' https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com; connect-src 'self'; base-uri 'self'; form-action 'self'");
}

function harden_session(): void {
    // Already set in bootstrap; extra checks
    if (empty($_SESSION['_ua'])) {
        $_SESSION['_ua'] = substr(hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 16);
    } elseif ($_SESSION['_ua'] !== substr(hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 16)) {
        // UA changed mid-session — force re-login for admin
        if (!empty($_SESSION['uid'])) {
            $_SESSION = [];
        }
    }
}
