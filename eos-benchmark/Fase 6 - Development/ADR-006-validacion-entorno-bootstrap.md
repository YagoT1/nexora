# ADR-006 — Validación completa del entorno de bootstrap (`docs/BOOTSTRAP.md`)

**Estado:** Cerrado — Resuelto. Entorno validado de punta a punta: Laravel creado, iniciado y con su suite de tests en verde.
**Fecha de apertura:** 2026-07-12
**Decide:** Continuación explícita solicitada por el responsable del proyecto (Yago) tras el cierre formal de `ADR-005`.

---

## Contexto

`ADR-005` cerró el incidente de instalación de Composer (SSL) con `composer diagnose` funcional, pero ese mismo diagnóstico reveló un hallazgo nuevo y distinto, fuera del alcance de ese ADR:

```
zip: extension not loaded, unzip not available, 7-Zip not available
```

Composer necesita al menos una de esas tres vías para extraer los paquetes `.zip` que descarga (incluido el propio esqueleto de `laravel/laravel` en el paso 2 de `BOOTSTRAP.md`). Sin resolver esto, `composer create-project laravel/laravel:^11.0 sgb-laravel` fallará.

El objetivo de este ADR ya no es un incidente puntual, sino la validación completa del entorno: se documentará aquí cada dependencia faltante que aparezca al ejecutar `BOOTSTRAP.md` de punta a punta, con el mismo rigor metodológico de `ADR-005` (hipótesis → evidencia → cambio justificado → verificación objetiva).

## Hallazgo 1: soporte zip ausente para Composer

### Hipótesis (sin confirmar todavía — pendientes de evidencia)

| # | Hipótesis | Cómo se confirma/descarta |
|---|---|---|
| H1 | La extensión `zip` (`php_zip.dll`) existe en el directorio de extensiones de PHP, pero la línea `extension=zip` está comentada o ausente en el `php.ini` activo. | `php -m` no lista `zip` + el DLL existe físicamente en `extension_dir`. |
| H2 | El DLL `php_zip.dll` no está presente en este build de PHP 8.5 (paquete de WinGet incompleto o build no-TS/NTS incorrecto). | El DLL no aparece en `extension_dir` al listar el directorio. |
| H3 | `extension_dir` apunta a una ruta incorrecta o inexistente, afectando la carga de cualquier extensión dinámica (no solo zip). | Poco probable — `openssl`/`curl` ya cargan correctamente según `ADR-005` — pero se verifica por bajo costo. |
| H4 | No hay alternativa de línea de comandos (`unzip.exe`, `7z.exe`) disponible en `PATH` como fallback si la extensión PHP no puede habilitarse. | `where.exe unzip` / `where.exe 7z` no devuelven resultados. |

### Evidencia recolectada

```
=== zip cargado actualmente? ===
(vacío — no cargado)

=== extension_dir configurado ===
extension_dir = "ext"

=== linea extension=zip en php.ini ===
;extension=zip           <- presente, pero comentada

=== existe php_zip.dll en extension_dir? ===
C:\...\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe\ext\php_zip.dll
-a---   1/7/2026  04:07   414208   php_zip.dll   <- SI existe

=== unzip/7z disponibles como fallback? ===
(vacío — ninguno de los dos está en PATH)
```

**Conclusión:** se confirma **H1** (extensión disponible pero deshabilitada por defecto en el `php.ini`
activo) y se descartan H2 (el DLL sí existe) y H3 (`extension_dir` resuelve correctamente — coherente
con que `openssl`/`curl` ya cargaban bien en `ADR-005`). H4 se confirma como dato adicional (no hay
fallback de línea de comandos) pero queda sin relevancia porque el fix nativo resuelve el problema.

**Nota de proceso:** el primer y segundo intento de recolectar esta evidencia fallaron por errores
propios en el script de diagnóstico (parseo incorrecto de la ruta de `php.ini`, que en Windows viene
entre comillas y contiene su propio `:` de unidad; y luego resolución de `extension_dir` relativo
contra el directorio incorrecto). Ambos se identificaron y corrigieron antes de sacar ninguna
conclusión — no se interpretó ningún resultado vacío como hallazgo real hasta confirmar que el script
en sí era correcto.

### Fix aplicado

Se descomentó la línea en el `php.ini` activo:

```diff
- ;extension=zip
+ extension=zip
```

### Verificación objetiva

```
php -m | findstr /I "zip"   →   zip   (antes: vacío)

composer diagnose           →   zip: extension present, unzip not available, 7-Zip not available
                                 (antes: zip: extension not loaded, unzip not available, 7-Zip not available)
                                 Checking http/https connectivity to packagist: OK
                                 Checking github.com rate limit: OK
                                 Checking disk free space: OK
```

**Hallazgo 1: cerrado.** Composer ya tiene un método funcional de extracción de paquetes.

---

## Bitácora de ejecución

| Paso | Acción | Evidencia esperada | Resultado | Estado |
|---|---|---|---|---|
| 1 | Diagnóstico de soporte zip | DLL presente / línea comentada en php.ini | Confirmado: H1 | ✅ Completo |
| 2 | Descomentar `extension=zip` en php.ini activo | `php -m` lista `zip`; `composer diagnose` ya no reporta el warning | Coincide en ambos puntos | ✅ Completo |
| 3 | `composer create-project laravel/laravel:^11.0 sgb-laravel` | Esqueleto Laravel 11 creado sin errores | La extracción del zip funcionó (confirma Hallazgo 1), pero Composer rechazó `laravel/framework ^11.0` completo por 7 security advisories — 2 sin fix en toda la rama 11.x. Ver `ADR-007`: se decide pasar a Laravel 12. | ⚠️ Bloqueado — escalado a decisión de arquitectura |
| 4 | Enmendar DA-03 y `BOOTSTRAP.md` con la decisión de `ADR-007` | Ambos documentos referencian Laravel 12 | Completo. Nota de proceso: la edición de `propuesta-arquitectura-v2.md` falló 2 veces con `EPERM` en rename vía la herramienta de archivos; se aisló la causa (rename por bash funciona, unlink no — mismo patrón que `ADR-002`/`ADR-003` con `.git`) y se aplicó el cambio directamente vía el mount de bash, verificado luego por ambos canales. | ✅ Completo |
| 5 | `composer create-project laravel/laravel:^12.0 sgb-laravel` (recreando desde cero) | Esqueleto Laravel 12 creado sin errores | Extracción del zip OK, ya no aparece el bloqueo de security advisories (confirma `ADR-007`). Nuevo bloqueo distinto: `league/flysystem-local` requiere `ext-fileinfo`, ausente en este entorno. | ⚠️ Bloqueado — Hallazgo 2 |

## Hallazgo 2: extensión `fileinfo` ausente

### Hipótesis (sin confirmar — pendientes de evidencia)

| # | Hipótesis | Cómo se confirma/descarta |
|---|---|---|
| H1 | `php_fileinfo.dll` existe en `ext/` pero la línea `extension=fileinfo` está comentada en el `php.ini` activo (mismo patrón que Hallazgo 1). | `php -m` no lista `fileinfo` + el DLL existe físicamente en la carpeta `ext` resuelta contra el directorio de instalación de PHP. |
| H2 | El DLL no está presente en este build de PHP 8.5. | El DLL no aparece en la carpeta `ext`. |

### Evidencia pendiente

Solicitada a continuación en el chat.

| Paso | Acción | Evidencia esperada | Resultado | Estado |
|---|---|---|---|---|
| 6 | Diagnóstico de soporte fileinfo | Ver hipótesis arriba | Confirmado H1 (DLL presente, línea comentada). Se aprovechó para relevar el estado completo de las extensiones requeridas por `BOOTSTRAP.md §1` de una sola vez: `bcmath`/`ctype`/`curl`/`mbstring`/`xml` ya activas (las tres primeras built-in, sin línea `extension=`); solo `pdo_pgsql` y `fileinfo` estaban comentadas. | ✅ Completo |
| 7 | Descomentar `extension=pdo_pgsql` y `extension=fileinfo` | Ambas cargan en `php -m` | DLL de `pdo_pgsql` confirmado presente antes del cambio; `php -m` confirma ambas cargadas después | ✅ Completo |
| 8 | Recrear `sgb-laravel` con Laravel 12 (segundo intento, entorno ya corregido) | Esqueleto creado sin errores, con `vendor/` y `composer.lock` | Éxito completo: 111 paquetes instalados, `Application key set successfully`, `No security vulnerability advisories found` (valida `ADR-007`). Único mensaje no bloqueante: `could not find driver` para SQLite — es la base de conveniencia por defecto del instalador de Laravel, irrelevante porque el proyecto usa PostgreSQL (`ADR-004`) y el `.env` se reemplaza en el paso 3 de `BOOTSTRAP.md`. | ✅ Completo |
| 9 | `composer require laravel/breeze --dev` + `php artisan breeze:install blade` | Breeze instalado, stack Blade sin Vue/React/Inertia | Éxito: build de Vite limpio, `0 vulnerabilities`, `Breeze scaffolding installed successfully`. | ✅ Completo |
| 10 | Paso 3: copiar migraciones/seeders/modelos/middleware/controllers/Support/views/tests + fusionar routes/web.php, bootstrap/app.php, DatabaseSeeder.php, .env | Sin colisiones de nombre; fusiones aplicadas sin sobrescribir contenido de Breeze | Ejecutado directamente vía acceso a archivos (no requería decisión del usuario, es integración mecánica de código ya revisado en `ADR-003`). 30 migraciones copiadas sin colisión con las 3 de Breeze (`0001_01_01_...` ordena antes que `2024_01_01_...`). `DatabaseSeeder.php`: se reemplazó el usuario genérico de Breeze por los 3 seeders del dominio (`AdminUserSeeder` ya crea usuarios de prueba por rol). `.env`: se preservó `APP_KEY` ya generada y las claves nuevas de Laravel 12 (`CACHE_STORE`, `QUEUE_CONNECTION`, etc.), sobrescribiendo solo `APP_NAME`, `DB_*` (con la contraseña real de `docker-compose.yml`) y `SESSION_*`. Único hallazgo menor: `tests/Feature/AuthenticationTest.php` (fuente) coexiste con `tests/Feature/Auth/AuthenticationTest.php` (Breeze) — namespaces distintos, sin colisión de clase. | ✅ Completo |
| 11 | Levantar Postgres y correr `php artisan migrate --seed` (paso 4) | Migraciones y seeders corren sin error | `docker compose up -d` falló: Docker Desktop no está corriendo (daemon inalcanzable). La migración sí conectó a `127.0.0.1:5432` pero con `FATAL: la autentificación password falló para el usuario «sgb»` — no `connection refused`. Ver Hallazgo 3. | ⚠️ Bloqueado |

## Hallazgo 3: conflicto de puerto 5432 con PostgreSQL nativo

### Evidencia

```
Get-NetTCPConnection -LocalPort 5432 -State Listen  →  PID 6312, proceso "postgres"
Get-Service -Name "*postgres*"                       →  postgresql-x64-18, Status: Running
docker info                                          →  solo "Client", sin sección de servidor
                                                         (Docker Desktop no está corriendo)
```

**Conclusión:** hay un PostgreSQL 18 instalado como servicio nativo de Windows, ocupando el
puerto 5432 de forma completamente independiente del `docker-compose.yml` de este proyecto (que
ni siquiera llegó a levantar, porque Docker Desktop está apagado). El rechazo de autenticación
provino de esa instancia nativa, no del contenedor.

### Decisión

Se consulta al responsable del proyecto en lugar de asumir una resolución, porque hay una
alternativa técnicamente válida (usar el Postgres 18 nativo) que se aparta de la arquitectura
aprobada (`DA-03`/`DA-04`: Postgres 16) sin haber sido decidida explícitamente. Se opta por
**remapear el contenedor Docker al puerto 5433**, preservando la versión aprobada (16) y la
reproducibilidad ya documentada en `ADR-004`, sin tocar el servicio nativo (que podría
pertenecer a otro proyecto de la máquina del usuario).

### Cambios aplicados

- `docker-compose.yml`: `"127.0.0.1:5432:5432"` → `"127.0.0.1:5433:5432"` (puerto interno del
  contenedor sin cambios).
- `sgb-laravel/.env`: `DB_PORT=5432` → `DB_PORT=5433`.
- `C:\Users\yagot\.cursor\mcp.json`: `DATABASE_URI` actualizado al puerto 5433.
- `docs/POSTGRES-MCP-SETUP.md`: nota agregada documentando que este conflicto ya ocurrió y fue
  resuelto en esta máquina.

| Paso | Acción | Evidencia esperada | Resultado | Estado |
|---|---|---|---|---|
| 12 | Iniciar Docker Desktop y reintentar `docker compose up -d` + `migrate --seed` con el puerto remapeado | Contenedor healthy en 5433; migraciones y seeders sin error | Primer intento: Docker Desktop nunca había sido iniciado en esta sesión de Windows (confirmado — mostró la pantalla de onboarding "What is a Container?" al abrirlo por primera vez). Segundo intento, con Docker ya arriba: contenedor creado y arrancado; las 33 migraciones (30 del dominio + 3 de Breeze) y los 3 seeders (`TipoSocioSeeder`, `ParametroConfiguracionSeeder`, `AdminUserSeeder`) corrieron sin ningún error. | ✅ Completo |
| 13 | `php artisan test` (paso 5 de `BOOTSTRAP.md` — primer checkpoint de calidad real) | Suite en verde | Primera corrida: 3 fallas reales de código (no del entorno) — usuario administrador activo recibía 403. Causa raíz y fix documentados en `ADR-008` (defaults de `rol`/`estado` no reflejados en memoria por Eloquent tras `create()`). Segunda corrida, con el fix aplicado: **38 passed, 94 assertions, 0 failures.** Sin regresiones sobre los 35 que ya pasaban. | ✅ Completo |
| 14 | Confirmar que el proyecto "se inicia" (criterio explícito del usuario, no solo migra/testea) | `php artisan serve` arranca sin error y responde | `curl -I http://127.0.0.1:8000/login` → `HTTP/1.1 200 OK`, cookies de sesión reales (`sistema_de_gestion_bibliotecaria_session`, driver `database` funcionando de punta a punta). Verificación visual manual del usuario en el navegador: pantalla de login renderiza correctamente. | ✅ Completo |

## Cierre formal

Los tres criterios de cierre fijados explícitamente por el responsable del proyecto quedan cumplidos con evidencia objetiva:

1. **El proyecto se crea:** `composer create-project laravel/laravel:^12.0` completo, sin errores, `vendor/` y `composer.lock` presentes (paso 8 de la bitácora).
2. **El proyecto se inicia:** `php artisan serve` responde `200 OK` en `/login`, con sesión de base de datos funcional y verificación visual manual (paso 14).
3. **Ejecuta sus pruebas correctamente:** `php artisan test` → **38 passed, 94 assertions, 0 failures** (paso 13, tras corregir el defecto real de `ADR-008`).

Hallazgos y decisiones que este ADR generó y quedaron documentados por separado:

- **Hallazgo 1** (este documento): soporte `zip` deshabilitado por defecto en el `php.ini` de WinGet.
- **Hallazgo 2** (este documento): extensiones `pdo_pgsql`/`fileinfo` deshabilitadas por defecto, mismo patrón.
- **Hallazgo 3** (este documento): conflicto de puerto 5432 con un PostgreSQL 18 nativo ya instalado — resuelto remapeando Docker a 5433, sin tocar el servicio nativo.
- **`ADR-007`**: Laravel 11 quedó fuera de soporte de seguridad activo — se actualizó el objetivo de arquitectura a Laravel 12 (enmienda a `DA-03`).
- **`ADR-008`**: defecto real de código (no del entorno) en `app/Models/User.php` — defaults de `rol`/`estado` no reflejados en memoria por Eloquent tras `create()`, causando 3 fallas de test. Corregido y verificado sin regresiones.

Pendiente, fuera del alcance de este ADR (ver `phase-summary.md`, "Próximo trabajo"): reparación de `.git` en ambos repositorios (`ADR-002`/`ADR-003`, requiere acción manual del usuario en su máquina real, no desde Cowork) y el pre-checklist de infraestructura (Render.com, HTTPS, cron).

**Hallazgo 2: cerrado.**
