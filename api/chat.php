<?php
// =====================================================
// FaltaUno — Chat grupal por partido
// GET  ?partido_id=X&desde=ID   -> mensajes con id > desde (polling incremental)
// GET  ?action=resumen          -> último mensaje de cada chat del usuario
// POST {partido_id, texto}      -> enviar mensaje (solo miembros)
// =====================================================
require __DIR__ . '/config.php';

$user = require_user();
$action = $_GET['action'] ?? '';

function es_miembro(int $pid, int $uid): bool {
    $st = db()->prepare('SELECT 1 FROM partido_jugadores WHERE partido_id = ? AND usuario_id = ?');
    $st->execute([$pid, $uid]);
    return (bool) $st->fetch();
}

try {

    // -------------------- RESUMEN DE CHATS --------------------
    if ($action === 'resumen') {
        $st = db()->prepare(
            'SELECT p.id, p.cancha, p.barrio, p.fecha, TIME_FORMAT(p.hora, "%H:%i") AS hora,
                    (SELECT COUNT(*) FROM partido_jugadores x WHERE x.partido_id = p.id) AS miembros,
                    m.texto  AS ultimo_texto,
                    m.tipo   AS ultimo_tipo,
                    u2.nombre AS ultimo_autor,
                    m.creado_en AS ultimo_en
             FROM partido_jugadores pj
             JOIN partidos p ON p.id = pj.partido_id AND p.estado = "activo"
             LEFT JOIN mensajes m ON m.id = (
                 SELECT MAX(id) FROM mensajes WHERE partido_id = p.id
             )
             LEFT JOIN usuarios u2 ON u2.id = m.usuario_id
             WHERE pj.usuario_id = ?
             ORDER BY COALESCE(m.creado_en, p.creado_en) DESC'
        );
        $st->execute([$user['id']]);
        json_out(['ok' => true, 'chats' => $st->fetchAll()]);
    }

    // -------------------- LEER MENSAJES --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $pid   = (int)($_GET['partido_id'] ?? 0);
        $desde = (int)($_GET['desde'] ?? 0);

        if (!es_miembro($pid, (int) $user['id'])) json_error('Únete al partido para ver el chat', 403);

        $st = db()->prepare(
            'SELECT m.id, m.tipo, m.texto,
                    TIME_FORMAT(m.creado_en, "%h:%i %p") AS hora,
                    DATE(m.creado_en) AS dia,
                    m.usuario_id,
                    u.nombre, u.avatar_color
             FROM mensajes m
             LEFT JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.partido_id = ? AND m.id > ?
             ORDER BY m.id ASC
             LIMIT 200'
        );
        $st->execute([$pid, $desde]);

        $msgs = array_map(function ($m) use ($user) {
            $m['mio'] = ((int) $m['usuario_id'] === (int) $user['id']);
            return $m;
        }, $st->fetchAll());

        json_out(['ok' => true, 'mensajes' => $msgs]);
    }

    // -------------------- ENVIAR --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $b = body();
        $pid   = (int)($b['partido_id'] ?? 0);
        $texto = clean((string)($b['texto'] ?? ''), 1000);

        if ($texto === '') json_error('El mensaje está vacío');
        if (!es_miembro($pid, (int) $user['id'])) json_error('Únete al partido para escribir en el chat', 403);

        $st = db()->prepare('INSERT INTO mensajes (partido_id, usuario_id, tipo, texto) VALUES (?, ?, "msg", ?)');
        $st->execute([$pid, $user['id'], $texto]);

        json_out(['ok' => true, 'id' => (int) db()->lastInsertId()], 201);
    }

    json_error('Método no permitido', 405);

} catch (PDOException $e) {
    json_error('Error de base de datos. Intenta de nuevo.', 500);
}
