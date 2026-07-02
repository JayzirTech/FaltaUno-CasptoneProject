# ⚽ FaltaUno - Proyecto Integrador RIWI

¡Bienvenido a **FaltaUno**! La plataforma web diseñada para eliminar la fricción a la hora de organizar partidos de fútbol. Si te falta un jugador para completar el partido de esta noche o estás buscando un equipo al cual unirte en tu zona, FaltaUno te conecta en tiempo real.

Este proyecto ha sido desarrollado bajo metodologías ágiles (Scrum) como parte del Proyecto Integrador en **RIWI**.

---

## 🚀 Características del MVP (Mínimo Producto Viable)

FaltaUno resuelve el problema del "teléfono roto" y las cancelaciones de última hora mediante 4 módulos principales (Épicas):

1. **Autenticación y Perfiles Seguros:** Registro e inicio de sesión con contraseñas encriptadas en el backend y perfiles donde los jugadores definen su posición en la cancha y contacto.
2. **Muro Principal de Partidos:** Espacio donde los organizadores publican vacantes (fecha, hora, lugar, precio de la cancha, cupos faltantes) y los usuarios buscan partidos activos usando filtros dinámicos.
3. **Sistema de Postulaciones (Matchmaking):** Los jugadores se postulan a los partidos disponibles y el organizador tiene el control total para aceptarlos o rechazarlos en tiempo real, gestionando las vacantes concurrentes.
4. **Panel de Control del Organizador:** Vista centralizada para que cada usuario gestione los partidos que ha creado y administre las postulaciones activas.

---

## 🛠️ Stack Tecnológico

Para garantizar una experiencia fluida emulando una aplicación móvil nativa, el proyecto se construyó utilizando la siguiente arquitectura:

* **Frontend:** JavaScript Vanilla (ES6+), HTML5, CSS3 estructurado bajo la arquitectura de una **SPA (Single Page Application)** con enrutamiento dinámico nativo (sin recargas de página).
* **Backend:** Node.js (Express) encargado de la lógica de negocio, protección de rutas y hashing de seguridad.
* **Base de Datos:** PostgreSQL. Diseñada estrictamente en **Tercera Forma Normal (3FN)** para garantizar la consistencia, evitar redundancia y asegurar un control óptimo de cupos concurrentes.

## 👥 Nuestro Equipo y Roles (Metodología Scrum)
Somos un equipo de 6 integrantes estructurados bajo el marco de trabajo ágil para simular una empresa de software real:

- **Scrum Master / Líder Técnico:** Coordinación del tablero Jira, remoción de impedimentos y control del alcance del Sprint.

- **Analista de Producto / QA:** Redacción de Historias de Usuario, maquetación de bocetos, validaciones de formularios y pruebas intensivas de calidad libres de bugs.

- **Equipo Frontend (2 Desarrolladores):** Maquetación de interfaces dinámicas en la SPA y consumo de las APIs del servidor.

- **Equipo Backend (2 Desarrolladores):**    Arquitectura y normalización de la base de datos, encriptación mediante hashing (Bcrypt) y desarrollo de endpoints de negocio.

## 🏁 Flujo de Trabajo Git (Reglas de Oro)
Para mantener el repositorio limpio y evitar conflictos de código en el servidor remoto, el equipo sigue este flujo estricto:

- **Rama Principal (main):** Solo código en producción 100% estable.

- **Rama de Integración (develop):** Donde se fusionan las características terminadas.

- **Ramas de Características (feature/):** Cada desarrollador trabaja en su máquina con nomenclatura clara vinculada a Jira (ej: feature/login-auth o feature/muro-maquetacion).

![note] ⚠️ **Regla de Oro:** Antes de hacer Push de una característica, el desarrollador debe traer lo nuevo de develop a su rama local (git pull origin develop), resolver conflictos en su máquina local, realizar el Commit final y luego abrir el Pull Request (PR) hacia develop para revisión del equipo.

## 📋 Requisitos e Instalación
Prerrequisitos
Node.js
Gestor de bases de datos relacionales (MySQL o PostgreSQL).

- **Instalación Local**
Clona el repositorio:
  ```bash
  git clone https://github.com/JayzirTech/FaltaUno-CustomProject.git
  ```
- **Configura las variables de entorno:**
 Ve a la carpeta server/, crea un archivo .env guiándote de .env.example y añade las credenciales de tu base de datos local.

- **Importa la base de datos:**
 Ejecuta el script ubicado en server/database/ en tu gestor de base de datos.

¡Inicia el proyecto y a jugar!