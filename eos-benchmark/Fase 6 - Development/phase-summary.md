# Phase Summary

## Phase

06 — Development

---

## Estado

Módulo 1 (de 10) **cerrado**: entorno validado, proyecto Laravel 12 creado, migrado, sembrado, iniciado y con su suite de tests completa pasando (38/38). Repositorio de código consolidado en un único monorepo — `nexora` (https://github.com/YagoT1/nexora.git) es la fuente única de verdad para código, documentación, trazabilidad e historial del proyecto (`ADR-010`), con el commit de consolidación ya publicado (`515c161`). El entorno temporal de validación (`sgb-laravel/`) fue verificado sin pérdida de contenido y eliminado (`ADR-009`, adenda de cierre). Único pendiente no bloqueante: pre-checklist de infraestructura (ver "Próximo trabajo", punto 4). En curso: preparación del Módulo 2 — Catálogo, mediante briefing técnico previo a cualquier implementación (instrucción explícita del responsable del proyecto).

---

## Objetivo

Construir el sistema conforme al Plan de Implementación v2, módulo por módulo, respetando el orden de dependencias definido en DA-08.

---

## Avance

### Módulo 1 — Infraestructura y autenticación: código escrito

Repositorio: `sistema-gestion-bibliotecaria/`, subcarpeta trackeada del monorepo `nexora` (ver `ADR-001-repositorio-de-codigo.md`, enmendado por `ADR-010-monorepo-nexora-como-fuente-unica.md`).

Entregado:

- 30 migraciones: 29 cubriendo las 25 entidades del Modelo de Dominio v2 completo (no solo el alcance funcional de Fase 1 del software, conforme exige el Plan de Implementación v2 para el Módulo 1), más 1 migración de alteración sobre la tabla `users` generada por Breeze (agrega `rol` y `estado`). Corregido en esta revisión — ver `ADR-003`.
- Modelos Eloquent para todas las entidades, con relaciones y trazabilidad a la regla/decisión de origen documentada en cada archivo.
- Middleware de autorización por rol (`EnsureUserHasRole`), infraestructura de auditoría append-only (RN-14) vía trait `Auditable`.
- Panel de administración de usuarios (crear, editar, inactivar, reactivar, asignar rol) con vistas Blade + Alpine.js.
- Seeders: Tipos de Socio, Parámetros de Configuración, usuarios de prueba por rol.
- 4 archivos de test (Feature) cubriendo explícitamente los criterios de aceptación del Módulo 1: autenticación por rol, timeout de sesión, autorización por rol (incluyendo usuario inactivo), y auditoría de cambios.

**No ejecutado ni validado en el entorno donde se escribió** (ver `ADR-002-limitaciones-de-entorno-y-estrategia-de-desarrollo.md`): el sandbox de esta sesión no dispone de PHP, Composer, PostgreSQL ni acceso de red a Packagist. El primer checkpoint de calidad real es ejecutar `docs/BOOTSTRAP.md` en un entorno con PHP 8.3.

### Revisión de código y correcciones de seguridad (ver `ADR-003`)

Se revisó el código entregado del Módulo 1 y se corrigieron dos hallazgos directamente sobre el código fuente: (1) el trait `Auditable` excluye ahora los campos `$hidden` del modelo (password, remember_token) del payload que se escribe en `registros_auditoria`, que antes quedaba expuesto sin filtrar; (2) `UserController` impide que un administrador cambie su propio rol/estado o se autoinactive, evitando que el sistema quede sin ningún administrador activo. Verificado con `php -l` (PHP 8.5 vía `@php-wasm/cli`, instalado por no haber PHP nativo en el sandbox) y por revisión estática contra los tests existentes — no reemplaza la ejecución real de la suite. `ADR-003` deja registrados tres hallazgos menores sin corregir por estar fuera de alcance: `.git` inconsistente en `sistema-gestion-bibliotecaria/`, conteo de migraciones desactualizado en este mismo documento y en el README (29 documentadas, 30 reales), y falta de `.gitignore` explícito.

### Servidor MCP para acceso a PostgreSQL (ver `ADR-004`)

Se evaluaron alternativas para exponer la base de datos vía MCP a los clientes del equipo (Claude Desktop, Cursor). El servidor "oficial" (`@modelcontextprotocol/server-postgres`) está archivado desde mayo 2025 por una vulnerabilidad de inyección SQL sin parchear — se descartó por esa razón concreta, no por preferencia. Se eligió `crystaldba/postgres-mcp` (Postgres MCP Pro), que corrige explícitamente esa clase de vulnerabilidad y soporta tanto desarrollo local como, más adelante, Render staging con el mismo servidor en modo restringido. Se creó `docker-compose.yml` (Postgres 16 local), se configuró Cursor, y quedó documentado el paso manual pendiente para Claude Desktop (Cowork no tiene acceso a esa carpeta de configuración) en `docs/POSTGRES-MCP-SETUP.md`. La conexión en vivo no pudo verificarse desde esta sesión — requiere Docker Desktop corriendo en la máquina real; queda un checklist de verificación manual en esa misma guía.

### Incidente resuelto: fallo SSL instalando Composer sobre PHP 8.5 (ver `ADR-005`)

Al actualizar el entorno local a PHP 8.5.8 (Windows), la instalación de Composer falló con
`OpenSSL: certificate verify failed`. Causa raíz con dos factores: (1) `openssl.cafile`/`curl.cainfo`
sin configurar en el `php.ini` activo (comportamiento por defecto de PHP en Windows) — corregido
pero insuficiente por sí solo; (2) causa determinante: **Avast Antivirus** interceptando el tráfico
HTTPS con una CA raíz propia, no incluida en ningún bundle público. Resuelto agregando la CA raíz
real de Avast al bundle de PHP. Cerrado con evidencia objetiva (`composer diagnose` confirma HTTPS
a Packagist y GitHub OK). Se agregó una sección de troubleshooting reutilizable a
`docs/BOOTSTRAP.md` §9 para que otros desarrolladores no repitan la investigación. Pendiente
separado detectado (no bloqueante para este incidente): falta soporte de `zip`/`unzip`/`7-Zip` para
Composer, necesario antes del paso 2 de `docs/BOOTSTRAP.md`.

### Validación completa del entorno de bootstrap (ver `ADR-006`, `ADR-007`, `ADR-008`)

Se retomó `docs/BOOTSTRAP.md` desde el punto donde `ADR-005` lo dejó interrumpido (soporte `zip`
faltante para Composer) y se ejecutó de punta a punta, con el mismo rigor de diagnóstico basado en
evidencia. Hallazgos y decisiones:

- **Extensiones PHP deshabilitadas por defecto** (`zip`, `pdo_pgsql`, `fileinfo`) en el `php.ini` de
  WinGet — todas con su DLL presente, solo comentadas. Habilitadas y verificadas una por una.
- **Laravel 11 fuera de soporte de seguridad activo** (`ADR-007`): Composer rechazó instalar
  `laravel/framework ^11.0` por advisories sin parche en toda la rama 11.x (dos hallazgos de junio
  2026, uno de severidad alta, corregidos recién en Laravel 12.60/12.61). Se decidió actualizar el
  objetivo de arquitectura a **Laravel 12**, enmendando `DA-03` sin reescribir la decisión original.
- **Conflicto de puerto 5432** con un PostgreSQL 18 nativo ya instalado en la máquina — remapeado
  el contenedor Docker del proyecto a 5433, sin tocar el servicio nativo.
- **Defecto real de código** (`ADR-008`, no del entorno): `app/Models/User.php` no declaraba a nivel
  de modelo los mismos defaults de `rol`/`estado` que la migración define a nivel de columna —
  Eloquent no sincroniza esos defaults hacia la instancia en memoria que devuelve `create()`,
  causando que un administrador activo recibiera 403 en 3 tests. Corregido con
  `protected $attributes` en el modelo; verificado sin regresiones.

Verificación final, con evidencia objetiva: `composer create-project` sin errores, `php artisan
migrate --seed` sin errores (33 migraciones, 3 seeders), `php artisan test` → **38 passed, 94
assertions, 0 failures**, y `php artisan serve` respondiendo `200 OK` con sesión de base de datos
funcional (confirmado también visualmente por el usuario en el navegador). Es la primera vez que
este código se ejecuta contra PHP y PostgreSQL reales desde que se escribió (ver `ADR-002`).

### Hallazgo técnico documentado durante el desarrollo

**Nota de arquitectura (no bloqueante, para Architecture Review futura):** DA-09 especifica un índice único parcial *por tabla* de movimiento para garantizar RN-04 (invariante de circulación) a nivel de motor de base de datos. Como la invariante exige unicidad *entre las cuatro tablas de movimiento* (un ejemplar no puede estar simultáneamente en un préstamo domiciliario Y en una custodia externa, por ejemplo), un índice único por tabla no cubre el caso cruzado — eso solo puede resolverlo la verificación de aplicación ("Nivel 2" de DA-09). Se implementó `Ejemplar::tieneMovimientoActivo()` para cubrir ese caso. Se deja constancia de que la garantía a nivel de motor (Nivel 1) es, con el diseño de tablas actualmente aprobado, necesariamente parcial — una garantía completa a nivel de base de datos requeriría unificar las cuatro tablas de movimiento en una sola con discriminador de tipo, lo cual sería un cambio de arquitectura y no corresponde decidirlo unilateralmente en el Módulo 1.

---

## Próximo trabajo

1. ~~Housekeeping de git y consolidación de repositorio~~ — **hecho** (`ADR-009`, `ADR-010`): un
   único repositorio (`sistema-gestion-bibliotecaria/` como subcarpeta del monorepo `nexora`),
   publicado en `https://github.com/YagoT1/nexora.git` (commit `515c161`).
2. ~~Verificación de integridad y cierre de `sgb-laravel/`~~ — **hecho** (adenda de cierre en
   `ADR-009`): 0 archivos perdidos, entorno temporal eliminado.
3. ~~Ejecutar `sistema-gestion-bibliotecaria/docs/BOOTSTRAP.md` en un entorno con PHP 8.3 real~~ —
   **hecho** (ver `ADR-006`/`ADR-007`/`ADR-008`): 38/38 tests en verde.
4. Completar el pre-checklist de infraestructura (Render.com, HTTPS, cron, variables de entorno) —
   pendiente, no bloqueante para el inicio del Módulo 2. Incluye la recomendación de `ADR-010`:
   filtrar el trigger de despliegue de Render.com por path (`sistema-gestion-bibliotecaria/**`), no
   por cualquier push a `main` del monorepo.
5. ~~Módulo 2 — Catálogo: briefing técnico~~ — **hecho** (`BRIEFING-MODULO-2-CATALOGO.md`): concluye
   que hay información suficiente para iniciar la implementación (pasos 1 a 8), con una única
   decisión pendiente y no bloqueante (R-1, historial de condición física).

### Módulo 2 — Catálogo: implementación en curso (2026-07-14)

Tras revisión objetiva del estado del proyecto (ver nota más abajo), se determinó que no existía
ningún bloqueo real para iniciar la implementación y que seguir invirtiendo tiempo en tooling de
entorno (`ADR-004`, `ADR-011`) ya no aportaba valor frente a avanzar el producto. Se inició el plan
de implementación recomendado por el briefing:

- **Paso 1 (Autor, Editorial) — código escrito:** `AutorController`, `EditorialController`
  (namespace `App\Http\Controllers\Catalogo`), rutas bajo `catalogo.*` con middleware
  `role:administrador,personal` (Modelo de Dominio v2, 6.1: Voluntario no gestiona catálogo),
  vistas Blade + Alpine siguiendo la convención de `admin.users.*`, y enlac