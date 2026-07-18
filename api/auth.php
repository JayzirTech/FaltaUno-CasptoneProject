<?php
// =====================================================
// FaltaUno — Autenticación
// POST ?action=register  {nombre,email,password,telefono?,posicion?,barrio?}
// POST ?action=login     {email,password}
// POST ?action=logout
// GET  ?action=me
// =====================================================
require __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';

$AVATAR_COLORS = ['#0E9F52','#E4572E','#4062BB','#B15DFF','#F2A104','#17BEBB','#D7263D','#0A8754','#C05299'];
$POSICIONES = ['Arquero','Defensa','Medio','Delantero'];

try {

    // -------------------- REGISTRO --------------------
    if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $b = body();
        $nombre   = clean((string)($b['nombre'] ?? ''), 80);
        $email    = strtolower(clean((string)($b['email'] ?? ''), 120));
        $password = (string)($b['password'] ?? '');
        $telefono = clean((string)($b['telefono'] ?? ''), 20);
        $posicion = in_array($b['posicion'] ?? '', $POSICIONES, true) ? $b['posicion'] : 'Medio';
        $barrio   = clean((string)($b['barrio'] ?? ''), 60);

        if (mb_strlen($nombre) < 3)                        json_error('Escribe tu nombre completo');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))    json_error('El correo no es válido');
        if (strlen($password) < 6)                         json_error('La clave debe tener mínimo 6 caracteres');

        $st = db()->prepare('SELECT id FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) json_error('Ese correo ya está registrado. Inicia sesión.', 409);

        $color = $AVATAR_COLORS[array_rand($AVATAR_COLORS)];
        $st = db()->prepare(
            'INSERT INTO usuarios (nombre, email, telefono, password_hash, posicion, barrio, avatar_color)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$nombre, $email, $telefono ?: null, password_hash($password, PASSWORD_DEFAULT), $posicion, $barrio ?: null, $color]);

        $uid = (int) db()->lastInsertId();
        set_auth_cookie($uid);
        json_out(['ok' => true, 'user' => user_by_id($uid)], 201);
    }

    // -------------------- LOGIN --------------------
    if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $b = body();
        $email    = strtolower(clean((string)($b['email'] ?? ''), 120));
        $password = (string)($b['password'] ?? '');

        $st = db()->prepare('SELECT id, password_hash FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        $row = $st->fetch();

        if (!$row || !password_verify($password, $row['password_hash'])) {
            json_error('Correo o clave incorrectos', 401);
        }

        // Rehash transparente si cambia el algoritmo por defecto de PHP
        if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
            $up = db()->prepare('UPDATE usuarios SET password_hash = ? WHERE id = ?');
            $up->execute([password_hash($password, PASSWORD_DEFAULT), $row['id']]);
        }

        set_auth_cookie((int) $row['id']);
        json_out(['ok' => true, 'user' => user_by_id((int) $row['id'])]);
    }

    // -------------------- LOGOUT --------------------
    if ($action === 'logout') {
        clear_auth_cookie();
        json_out(['ok' => true]);
    }

    // -------------------- SESIÓN ACTUAL --------------------
    if ($action === 'me') {
        $user = current_user();
        if (!$user) json_out(['ok' => false, 'user' => null]);

        // Estadísticas del perfil
        $st = db()->prepare(
            'SELECT COUNT(*) AS total,
                    SUM(p.fecha < CURDATE()) AS jugados
             FROM partido_jugadores pj
             JOIN partidos p ON p.id = pj.partido_id
             WHERE pj.usuario_id = ? AND p.estado = "activo"'
        );
        $st->execute([$user['id']]);
        $stats = $st->fetch();

        $st = db()->prepare('SELECT COUNT(*) AS creados FROM partidos WHERE creador_id = ?');
        $st->execute([$user['id']]);
        $creados = $st->fetch();

        json_out(['ok' => true, 'user' => $user, 'stats' => [
            'inscritos' => (int) ($stats['total'] ?? 0),
            'jugados'   => (int) ($stats['jugados'] ?? 0),
            'creados'   => (int) ($creados['creados'] ?? 0),
        ]]);
    }

    json_error('Acción no válida', 404);

} catch (PDOException $e) {
    json_error('Error de base de datos. Intenta de nuevo.', 500);
}
