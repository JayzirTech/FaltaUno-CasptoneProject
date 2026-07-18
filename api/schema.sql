-- =====================================================
-- FaltaUno — Local database schema
-- Inferred from the fields used in api/*.php
-- Run this against an empty local database, e.g.:
--   mysql -u root -p faltauno < api/schema.sql
-- =====================================================

CREATE TABLE IF NOT EXISTS usuarios (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nombre        VARCHAR(80)  NOT NULL,
  email         VARCHAR(120) NOT NULL UNIQUE,
  telefono      VARCHAR(20)  NULL,
  password_hash VARCHAR(255) NOT NULL,
  posicion      VARCHAR(20)  NOT NULL DEFAULT 'Medio',
  barrio        VARCHAR(60)  NULL,
  ciudad        VARCHAR(60)  NOT NULL DEFAULT 'Barranquilla',
  avatar_color  VARCHAR(7)   NOT NULL DEFAULT '#0E9F52',
  creado_en     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partidos (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  creador_id    INT UNSIGNED NOT NULL,
  cancha        VARCHAR(100) NOT NULL,
  direccion     VARCHAR(150) NOT NULL,
  barrio        VARCHAR(60)  NOT NULL,
  ciudad        VARCHAR(60)  NOT NULL DEFAULT 'Barranquilla',
  formato       ENUM('F5','F7','F11') NOT NULL DEFAULT 'F5',
  tipo_cancha   VARCHAR(60)  NOT NULL DEFAULT 'Sintética techada',
  fecha         DATE         NOT NULL,
  hora          TIME         NOT NULL,
  duracion_min  SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  precio        INT UNSIGNED NOT NULL DEFAULT 0,
  nivel         VARCHAR(20)  NOT NULL DEFAULT 'Todos',
  cupos_total   TINYINT UNSIGNED NOT NULL DEFAULT 10,
  estado        ENUM('activo','cancelado') NOT NULL DEFAULT 'activo',
  creado_en     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (creador_id) REFERENCES usuarios(id),
  INDEX idx_barrio (barrio),
  INDEX idx_fecha (fecha),
  INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS partido_jugadores (
  partido_id  INT UNSIGNED NOT NULL,
  usuario_id  INT UNSIGNED NOT NULL,
  unido_en    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (partido_id, usuario_id),
  FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS mensajes (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  partido_id  INT UNSIGNED NOT NULL,
  usuario_id  INT UNSIGNED NULL,
  tipo        ENUM('msg','sys') NOT NULL DEFAULT 'msg',
  texto       VARCHAR(1000) NOT NULL,
  creado_en   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (partido_id) REFERENCES partidos(id) ON DELETE CASCADE,
  FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
  INDEX idx_partido (partido_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =====================================================
-- Demo seed data
-- The password hash below is a placeholder — replace it
-- by running this once you have PHP installed:
--   php -r "echo password_hash('faltauno123', PASSWORD_DEFAULT);"
-- and swap the value in the INSERT below before running this file.
-- =====================================================

INSERT INTO usuarios (nombre, email, password_hash, posicion, barrio, avatar_color)
VALUES ('Carlos Demo', 'carlos@demo.co', '$2y$10$REPLACE.WITH.YOUR.OWN.GENERATED.HASH.................', 'Medio', 'Alto Prado', '#0E9F52');
