<?php
// =====================================================
// FaltaUno — API global configuration (TEMPLATE)
// Copy this file to config.php and fill in your own local
// values. config.php is gitignored — never commit real
// credentials or the app secret.
// =====================================================
declare(strict_types=1);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'faltauno');        // local database name
define('DB_USER', 'root');            // local MySQL user
define('DB_PASS', '');                // local MySQL password

define('APP_SECRET', 'replace-this-with-a-long-random-string-40+chars');
define('TOKEN_COOKIE', 'faltauno_token');
define('TOKEN_TTL', 60 * 60 * 24 * 30);     // 30 days

date_default_timezone_set('America/Bogota');

// ---------- PDO connection (singleton) ----------
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

// ---------- JSON responses ----------
function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $msg, int $code = 400): void {
    json_out(['ok' => false, 'error' => $msg], $code);
}

// Request body: accepts JSON or form-data
function body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

// ---------- Signed tokens (HMAC-SHA256) ----------
function b64u(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function b64u_dec(string $s): string|false { return base64_decode(strtr($s, '-_', '+/')); }

function token_create(int $uid): string {
    $payload = b64u(json_encode(['uid' => $uid, 'exp' => time() + TOKEN_TTL]));
    $sig = b64u(hash_hmac('sha256', $payload, APP_SECRET, true));
    return $payload . '.' . $sig;
}

function token_uid(?string $token): ?int {
    if (!$token || strpos($token, '.') === false) return null;
    [$payload, $sig] = explode('.', $token, 2);
    $expected = b64u(hash_hmac('sha256', $payload, APP_SECRET, true));
    if (!hash_equals($expected, $sig)) return null;
    $data = json_decode((string) b64u_dec($payload), true);
    if (!is_array($data) || ($data['exp'] ?? 0) < time()) return null;
    return (int) $data['uid'];
}

function set_auth_cookie(int $uid): void {
    setcookie(TOKEN_COOKIE, token_create($uid), [
        'expires'  => time() + TOKEN_TTL,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
}

function clear_auth_cookie(): void {
    setcookie(TOKEN_COOKIE, '', ['expires' => time() - 3600, 'path' => '/']);
}

// ---------- Current user ----------
function user_by_id(int $uid): ?array {
    $st = db()->prepare('SELECT id, nombre, email, telefono, posicion, barrio, ciudad, avatar_color FROM usuarios WHERE id = ?');
    $st->execute([$uid]);
    $user = $st->fetch();
    return $user ?: null;
}

function current_user(): ?array {
    $uid = token_uid($_COOKIE[TOKEN_COOKIE] ?? null);
    if (!$uid) return null;
    $st = db()->prepare('SELECT id, nombre, email, telefono, posicion, barrio, ciudad, avatar_color FROM usuarios WHERE id = ?');
    $st->execute([$uid]);
    $user = $st->fetch();
    return $user ?: null;
}

function require_user(): array {
    $user = current_user();
    if (!$user) json_error('You must be logged in', 401);
    return $user;
}

// ---------- Utilities ----------
function clean(string $s, int $max = 200): string {
    return mb_substr(trim($s), 0, $max);
}
