<?php
// =====================================================
// FaltaUno — Configuración global del API
// EDITA las credenciales de la base de datos y el SECRET
// antes de subir a producción.
// =====================================================
declare(strict_types=1);

define('DB_HOST', 'localhost');
define('DB_NAME', 'u863013313_faltauno');      // <- tu base de datos en Hostinger
define('DB_USER', 'u863013313_faltauno');      // <- tu usuario MySQL
define('DB_PASS', 'Faltauno123.');     // <- tu clave MySQL

define('APP_SECRET', 'cambia-esto-por-una-cadena-larga-aleatoria-de-40+caracteres');
define('TOKEN_COOKIE', 'faltauno_token');
define('TOKEN_TTL', 60 * 60 * 24 * 30);     // 30 días

date_default_timezone_set('America/Bogota');

// ---------- Conexión PDO (singleton) ----------
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

// ---------- Respuestas JSON ----------
function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $msg, int $code = 400): void {
    json_out(['ok' => false, 'error' => $msg], $code);
}

// Cuerpo de la petición: acepta JSON o form-data
function body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $_POST;
}

// ---------- Tokens firmados (HMAC-SHA256) ----------
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

// ---------- Usuario actual ----------
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
    if (!$user) json_error('Debes iniciar sesión', 401);
    return $user;
}

// ---------- Utilidades ----------
function clean(string $s, int $max = 200): string {
    return mb_substr(trim($s), 0, $max);
}
