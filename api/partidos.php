<?php
// =====================================================
// FaltaUno — Partidos
// GET  ?                          -> lista con filtros (barrio, formato, dia, gratis, q, mios)
// GET  ?id=X                      -> detalle con jugadores
// GET  ?action=barrios            -> barrios con partidos activos
// POST ?action=crear              -> crear partido (el creador queda inscrito)
// POST ?action=unirme  {id}       -> unirse con control de cupos (transacción + lock)
// POST ?action=salir   {id}       -> salir del partido
// =====================================================
require __DIR__ . '/config.php';

$action = $_GET['action'] ?? '';

$FORMATOS = ['F5' => 10, 'F7' => 14, 'F11' => 22];
$NIVELES  = ['Todos', 'Básico', 'Intermedio', 'Avanzado'];

// Subconsulta reutilizable: partidos con conteo de inscritos
const SQL_BASE = '
    SELECT p.id, p.cancha, p.direccion, p.barrio, p.ciudad, p.formato, p.tipo_cancha,
           p.fecha, TIME_FORMAT(p.hora, "%H:%i") AS hora, p.duracion_min, p.precio,
           p.nivel, p.cupos_total, p.estado, p.creador_id,
           u.nombre AS creador_nombre, u.avatar_color AS creador_color,
           (SELECT COUNT(*) FROM partido_jugadores pj WHERE pj.partido_id = p.id) AS inscritos
    FROM partidos p
    JOIN usuarios u ON u.id = p.creador_id
';

function partido_row_publico(array $r, ?int $uid, PDO $db): array {
    $r['inscritos']  = (int) $r['inscritos'];
    $r['faltan']     = max(0, (int) $r['cupos_total'] - $r['inscritos']);
    $r['precio']     = (int) $r['precio'];
    $r['unido']      = false;
    if ($uid) {
        $st = $db->prepare('SELECT 1 FROM partido_jugadores WHERE partido_id = ? AND usuario_id = ?');
        $st->execute([$r['id'], $uid]);
        $r['unido'] = (bool) $st->fetch();
    }
    return $r;
}

try {
    $user = current_user();
    $uid  = $user['id'] ?? null;

    // -------------------- BARRIOS DISPONIBLES --------------------
    if ($action === 'barrios') {
        $rows = db()->query(
            'SELECT barrio, COUNT(*) AS n FROM partidos
             WHERE estado = "activo" AND fecha >= CURDATE()
             GROUP BY barrio ORDER BY barrio'
        )->fetchAll();
        json_out(['ok' => true, 'barrios' => $rows]);
    }

    // -------------------- DETALLE --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
        $id = (int) $_GET['id'];
        $st = db()->prepare(SQL_BASE . ' WHERE p.id = ?');
        $st->execute([$id]);
        $p = $st->fetch();
        if (!$p) json_error('El partido no existe', 404);

        $p = partido_row_publico($p, $uid, db());

        $st = db()->prepare(
            'SELECT u.id, u.nombre, u.posicion, u.avatar_color,
                    (u.id = ?) AS es_admin
             FROM partido_jugadores pj
             JOIN usuarios u ON u.id = pj.usuario_id
             WHERE pj.partido_id = ?
             ORDER BY es_admin DESC, pj.unido_en ASC'
        );
        $st->execute([$p['creador_id'], $id]);
        $p['jugadores'] = array_map(function ($j) {
            $j['es_admin'] = (bool) $j['es_admin'];
            return $j;
        }, $st->fetchAll());

        json_out(['ok' => true, 'partido' => $p]);
    }

    // -------------------- LISTA CON FILTROS --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === '') {
        $where  = ['p.estado = "activo"'];
        $params = [];

        if (!empty($_GET['mios'])) {
            if (!$uid) json_error('Debes iniciar sesión', 401);
            $where[] = 'p.id IN (SELECT partido_id FROM partido_jugadores WHERE usuario_id = ?)';
            $params[] = $uid;
        } else {
            $where[] = 'p.fecha >= CURDATE()';
        }
        if (!empty($_GET['barrio'])) {
            $where[] = 'p.barrio = ?';
            $params[] = clean((string) $_GET['barrio'], 60);
        }
        if (!empty($_GET['formato']) && isset($FORMATOS[$_GET['formato']])) {
            $where[] = 'p.formato = ?';
            $params[] = $_GET['formato'];
        }
        if (!empty($_GET['dia']) && $_GET['dia'] === 'hoy') {
            $where[] = 'p.fecha = CURDATE()';
        }
        if (isset($_GET['gratis']) && $_GET['gratis'] === '1') {
            $where[] = 'p.precio = 0';
        }
        if (!empty($_GET['q'])) {
            $where[] = '(p.cancha LIKE ? OR p.barrio LIKE ?)';
            $q = '%' . clean((string) $_GET['q'], 60) . '%';
            $params[] = $q;
            $params[] = $q;
        }

        $sql = SQL_BASE . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY p.fecha ASC, p.hora ASC LIMIT 60';
        $st = db()->prepare($sql);
        $st->execute($params);

        $lista = array_map(fn($r) => partido_row_publico($r, $uid, db()), $st->fetchAll());

        // Filtro "falta 1" se resuelve después del conteo
        if (!empty($_GET['falta1'])) {
            $lista = array_values(array_filter($lista, fn($p) => $p['faltan'] === 1));
        }

        json_out(['ok' => true, 'partidos' => $lista]);
    }

    // ==================== ACCIONES (requieren sesión) ====================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Método no permitido', 405);
    $user = require_user();
    $b = body();

    // -------------------- CREAR --------------------
    if ($action === 'crear') {
        $cancha    = clean((string)($b['cancha'] ?? ''), 100);
        $direccion = clean((string)($b['direccion'] ?? ''), 150);
        $barrio    = clean((string)($b['barrio'] ?? ''), 60);
        $formato   = isset($FORMATOS[$b['formato'] ?? '']) ? $b['formato'] : 'F5';
        $tipo      = clean((string)($b['tipo_cancha'] ?? 'Sintética techada'), 60);
        $fecha     = (string)($b['fecha'] ?? '');
        $hora      = (string)($b['hora'] ?? '');
        $duracion  = max(30, min(180, (int)($b['duracion_min'] ?? 60)));
        $precio    = max(0, min(200000, (int)($b['precio'] ?? 0)));
        $nivel     = in_array($b['nivel'] ?? '', $NIVELES, true) ? $b['nivel'] : 'Todos';
        $cupos     = (int)($b['cupos_total'] ?? $FORMATOS[$formato]);
        $cupos     = max(4, min(30, $cupos));

        if (mb_strlen($cancha) < 3)    json_error('Escribe el nombre de la cancha');
        if (mb_strlen($direccion) < 4) json_error('Escribe la dirección de la cancha');
        if (mb_strlen($barrio) < 3)    json_error('Escribe el barrio');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || $fecha < date('Y-m-d')) json_error('La fecha no es válida');
        if (!preg_match('/^\d{2}:\d{2}$/', $hora)) json_error('La hora no es válida');

        $db = db();
        $db->beginTransaction();

        $st = $db->prepare(
            'INSERT INTO partidos (creador_id, cancha, direccion, barrio, formato, tipo_cancha, fecha, hora, duracion_min, precio, nivel, cupos_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $st->execute([$user['id'], $cancha, $direccion, $barrio, $formato, $tipo, $fecha, $hora, $duracion, $precio, $nivel, $cupos]);
        $pid = (int) $db->lastInsertId();

        $db->prepare('INSERT INTO partido_jugadores (partido_id, usuario_id) VALUES (?, ?)')
           ->execute([$pid, $user['id']]);

        $db->prepare('INSERT INTO mensajes (partido_id, usuario_id, tipo, texto) VALUES (?, NULL, "sys", ?)')
           ->execute([$pid, $user['nombre'] . ' creó el partido']);

        $db->commit();
        json_out(['ok' => true, 'id' => $pid], 201);
    }

    // -------------------- UNIRME --------------------
    if ($action === 'unirme') {
        $pid = (int)($b['id'] ?? 0);
        $db = db();
        $db->beginTransaction();

        // Bloquea la fila del partido para evitar sobre-cupo en concurrencia
        $st = $db->prepare('SELECT cupos_total, estado, fecha FROM partidos WHERE id = ? FOR UPDATE');
        $st->execute([$pid]);
        $p = $st->fetch();

        if (!$p || $p['estado'] !== 'activo') { $db->rollBack(); json_error('El partido no está disponible', 404); }
        if ($p['fecha'] < date('Y-m-d'))      { $db->rollBack(); json_error('Este partido ya pasó'); }

        $st = $db->prepare('SELECT COUNT(*) AS n FROM partido_jugadores WHERE partido_id = ?');
        $st->execute([$pid]);
        if ((int) $st->fetch()['n'] >= (int) $p['cupos_total']) {
            $db->rollBack();
            json_error('El partido ya está completo', 409);
        }

        $st = $db->prepare('SELECT 1 FROM partido_jugadores WHERE partido_id = ? AND usuario_id = ?');
        $st->execute([$pid, $user['id']]);
        if ($st->fetch()) { $db->rollBack(); json_error('Ya estás en este partido', 409); }

        $db->prepare('INSERT INTO partido_jugadores (partido_id, usuario_id) VALUES (?, ?)')
           ->execute([$pid, $user['id']]);
        $db->prepare('INSERT INTO mensajes (partido_id, usuario_id, tipo, texto) VALUES (?, NULL, "sys", ?)')
           ->execute([$pid, $user['nombre'] . ' se unió al partido']);

        $db->commit();
        json_out(['ok' => true]);
    }

    // -------------------- SALIR --------------------
    if ($action === 'salir') {
        $pid = (int)($b['id'] ?? 0);

        $st = db()->prepare('SELECT creador_id FROM partidos WHERE id = ?');
        $st->execute([$pid]);
        $p = $st->fetch();
        if (!$p) json_error('El partido no existe', 404);
        if ((int) $p['creador_id'] === (int) $user['id']) {
            json_error('El admin no puede salir de su propio partido. Puedes cancelarlo desde el detalle.');
        }

        $st = db()->prepare('DELETE FROM partido_jugadores WHERE partido_id = ? AND usuario_id = ?');
        $st->execute([$pid, $user['id']]);

        if ($st->rowCount() > 0) {
            db()->prepare('INSERT INTO mensajes (partido_id, usuario_id, tipo, texto) VALUES (?, NULL, "sys", ?)')
                ->execute([$pid, $user['nombre'] . ' salió del partido']);
        }
        json_out(['ok' => true]);
    }

    // -------------------- CANCELAR (solo admin) --------------------
    if ($action === 'cancelar') {
        $pid = (int)($b['id'] ?? 0);
        $st = db()->prepare('UPDATE partidos SET estado = "cancelado" WHERE id = ? AND creador_id = ?');
        $st->execute([$pid, $user['id']]);
        if ($st->rowCount() === 0) json_error('Solo el admin puede cancelar el partido', 403);

        db()->prepare('INSERT INTO mensajes (partido_id, usuario_id, tipo, texto) VALUES (?, NULL, "sys", "El admin canceló el partido")')
            ->execute([$pid]);
        json_out(['ok' => true]);
    }

    json_error('Acción no válida', 404);

} catch (PDOException $e) {
    if (db()->inTransaction()) db()->rollBack();
    json_error('Error de base de datos. Intenta de nuevo.', 500);
}
