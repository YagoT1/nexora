# Bootstrap del proyecto

Pasos exactos para convertir este repositorio de archivos fuente en un proyecto Laravel 12 real, ejecutable y testeado. Ejecutar en una máquina con PHP 8.3 o superior, Composer, y acceso a PostgreSQL 16 (local o remoto).

**Nota (2026-07-12, ver `ADR-007` en `eos-benchmark/Fase 6 - Development`):** la versión objetivo pasó de Laravel 11 a Laravel 12 — la rama 11.x dejó de recibir parches de seguridad activos y dos vulnerabilidades de junio de 2026 (una de severidad alta) no tienen fix disponible en ningún release 11.x.

## 1. Requisitos previos

- PHP 8.3 o superior (Laravel 12 exige como mínimo PHP 8.2) con extensiones: `pdo_pgsql`, `mbstring`, `xml`, `ctype`, `bcmath`, `curl`, `fileinfo`.
- Composer 2.x. **En Windows, si la instalación falla con un error de SSL (`certificate verify failed`), ver la sección 9 antes de reintentar a ciegas** — es un problema conocido y con causa raíz identificada (ver `ADR-005`). **Si en cambio falla con un error de "security advisories" al resolver `laravel/framework`, ver la sección 10** (ver `ADR-006`/`ADR-007`).
- PostgreSQL 16 accesible (local vía Docker, o instancia de staging en Render.com). Para desarrollo local ya hay un `docker-compose.yml` en la raíz de este repositorio: `docker compose up -d` levanta Postgres 16 con las credenciales de `.env.example` (ver `ADR-004` en `eos-benchmark/Fase 6 - Development`).
- Node.js 20+ (solo para compilar assets de Alpine.js/CSS vía Vite; no hay build de SPA).

## 2. Crear el proyecto Laravel base

Ejecutar en un directorio de trabajo temporal, **fuera** de este repositorio:

```bash
composer create-project laravel/laravel:^12.0 sgb-laravel
cd sgb-laravel
composer require laravel/breeze --dev
php artisan breeze:install blade
```

Elegir la opción **Blade** (sin Vue/React/Inertia) cuando el instalador de Breeze pregunte, conforme a DA-03 de la Propuesta de Arquitectura v2 (Blade + Alpine.js para la Fase 1).

## 3. Integrar los archivos de este repositorio

Copiar (sobrescribiendo donde corresponda) desde `sistema-gestion-bibliotecaria/` hacia la raíz de `sgb-laravel/`:

```bash
cp -r database/migrations/*.php   sgb-laravel/database/migrations/
cp -r database/seeders/*.php      sgb-laravel/database/seeders/
cp -r app/Models/*.php            sgb-laravel/app/Models/
cp -r app/Http/Middleware/*.php   sgb-laravel/app/Http/Middleware/
cp -r app/Http/Controllers/Admin  sgb-laravel/app/Http/Controllers/
cp -r app/Support                 sgb-laravel/app/
cp -r resources/views/admin       sgb-laravel/resources/views/
cp -r resources/views/layouts/app.blade.php  sgb-laravel/resources/views/layouts/
cp -r tests/Feature/*.php         sgb-laravel/tests/Feature/
```

Fusionar manualmente (no sobrescribir, porque Breeze ya generó contenido):

- `routes/web.php` → agregar las rutas de `routes/web.php` de este repositorio (sección `// --- Módulo 1 ---`) al archivo generado por Breeze.
- `bootstrap/app.php` → registrar el middleware `role` (ver comentario `// --- Módulo 1 ---` en `app/Http/Middleware/EnsureUserHasRole.php` de este repositorio).
- `database/seeders/DatabaseSeeder.php` → agregar las llamadas a los seeders nuevos (`TipoSocioSeeder`, `ParametroConfiguracionSeeder`, `AdminUserSeeder`).
- `.env` → copiar los valores de `.env.example` de este repositorio (conexión PostgreSQL, `SESSION_LIFETIME=120`).

## 4. Migrar la base de datos

```bash
php artisan migrate --seed
```

Esto debe ejecutar sin errores las ~29 migraciones (todas las entidades del dominio, no solo las de circulación) y sembrar: los dos Tipos de Socio (Estándar/Honorario), los parámetros de configuración iniciales, y un usuario Administrador de prueba.

## 5. Correr los tests

```bash
php artisan test
```

Los tests de `tests/Feature/` cubren los criterios de aceptación explícitos del Módulo 1 (autenticación, timeout de sesión, autorización por rol, auditoría de cambios de configuración). **Este es el primer checkpoint de calidad real del código entregado — no debe considerarse el Módulo 1 completo hasta que esta suite pase en verde.**

## 6. Verificar manualmente los criterios de aceptación restantes

Los siguientes criterios del Plan de Implementación v2 no son verificables por test automatizado y requieren revisión manual, tal como exige el propio plan ("Nota sobre el ciclo de desarrollo"):

- El layout base es responsive y navegable.
- El panel de administración permite crear, editar e inactivar usuarios y asignar rol.

## 7. Inicializar git (no se hizo en el entorno donde se escribió este código)

```bash
git init
git add -A
git commit -m "feat: Modulo 1 - infraestructura y autenticacion"
git remote add origin <URL del repositorio GitHub de la institución>
git push -u origin main
```

## 8. Pendiente de infraestructura (fuera del alcance de este bootstrap)

Ver pre-checklist completo en `eos-benchmark/Fase 3 - Architecture/entregables/plan-implementacion-fase1-v2.md`: entornos de Render.com (staging/producción), HTTPS, cron job (`php artisan schedule:run` cada hora), variables de entorno sin valores sensibles en el repositorio.

## 9. Troubleshooting: error SSL (`certificate verify failed`) instalando Composer en Windows

Incidente completo, con diagnóstico paso a paso, en `eos-benchmark/Fase 6 - Development/ADR-005-incidente-ssl-composer-php85.md`. Resumen para no repetir la investigación:

**Síntoma:** `Composer-Setup.exe` (o `php -r "file_get_contents('https://...')"`) falla con `OpenSSL: certificate verify failed` / `SSL routines::certificate verify failed`.

**Causa raíz (dos factores, ambos suelen estar presentes en instalaciones nuevas de PHP en Windows):**

1. Los builds oficiales de PHP para Windows no traen configurado `openssl.cafile`/`curl.cainfo` en `php.ini` — sin eso, PHP no tiene ningún certificado raíz público contra el cual validar nada.
2. Si además hay un antivirus o proxy que hace inspección de tráfico HTTPS (Avast, Kaspersky, ESET, Bitdefender, proxies corporativos con TLS inspection, etc.), ese software re-firma los certificados con una CA propia generada localmente — que nunca va a estar en ningún bundle público, sin importar cuán completo esté.

**Diagnóstico rápido, en orden:**

```powershell
php -i | findstr /I "cafile cainfo"
```
Si aparece vacío → falta el factor 1. Configurar:

```powershell
Invoke-WebRequest -Uri "https://curl.se/ca/cacert.pem" -OutFile "C:\php\cacert.pem"
# Agregar al final del php.ini activo (confirmar la ruta exacta con `php --ini`):
#   openssl.cafile="C:\php\cacert.pem"
#   curl.cainfo="C:\php\cacert.pem"
```

Si después de eso `php -r "var_dump(file_get_contents('https://getcomposer.org/versions'));"` **sigue** devolviendo `bool(false)` con el mismo error, es el factor 2 (interceptación TLS). Identificar la CA real inspeccionando la conexión directamente:

```powershell
$tcpClient = New-Object System.Net.Sockets.TcpClient("getcomposer.org", 443)
$callback = [System.Net.Security.RemoteCertificateValidationCallback]{ $true }
$sslStream = New-Object System.Net.Security.SslStream($tcpClient.GetStream(), $false, $callback)
$sslStream.AuthenticateAsClient("getcomposer.org")
(New-Object System.Security.Cryptography.X509Certificates.X509Certificate2($sslStream.RemoteCertificate)) | Format-List Subject, Issuer
$tcpClient.Close()
```

El campo `Issuer` va a identificar el software responsable. Buscar en el almacén de certificados de Windows la CA **raíz autofirmada** correspondiente (`Subject` igual a `Issuer` — cuidado, no confundir con el certificado hoja que devuelve el comando de arriba, que tiene `Subject` del sitio visitado, no de la CA):

```powershell
Get-ChildItem -Path Cert:\ -Recurse -ErrorAction SilentlyContinue | Where-Object { $_.Subject -like "*<nombre del software>*" } | Select-Object Subject, Issuer, Thumbprint, PSParentPath | Format-List
```

Exportarla y agregarla al mismo `cacert.pem`:

```powershell
$avastRoot = Get-ChildItem -Path Cert:\LocalMachine\Root | Where-Object { $_.Thumbprint -eq "<thumbprint de la CA raíz>" }
Export-Certificate -Cert $avastRoot -FilePath "C:\php\extra-root.crt" -Type CERT
certutil -encode C:\php\extra-root.crt C:\php\extra-root.pem
Get-Content C:\php\extra-root.pem | Add-Content -Path C:\php\cacert.pem
```

Volver a probar con el mismo `php -r "var_dump(file_get_contents(...))"` antes de reinstalar Composer — debe devolver JSON real.

**Nota:** después de reinstalar Composer, si `composer -V` no se reconoce como comando aunque la instalación haya terminado bien, cerrar la aplicación de terminal por completo (no solo la pestaña) y abrirla de nuevo — el PATH actualizado no siempre se propaga a sesiones ya abiertas.

**Nota adicional:** si `composer diagnose` reporta `zip: extension not loaded, unzip not available, 7-Zip not available`, resolver eso *antes* del paso 2 de este documento — Composer necesita alguno de los tres para extraer los paquetes que descarga.

## 10. Troubleshooting: Composer rechaza `laravel/framework` por "security advisories"

Incidente completo en `eos-benchmark/Fase 6 - Development/ADR-006-validacion-entorno-bootstrap.md` y `ADR-007-actualizacion-laravel-11-a-12.md`. Resumen para no repetir la investigación:

**Síntoma:** `composer create-project laravel/laravel:^11.0 ...` (o cualquier instalación que fije `^11.0`) falla con `Your requirements could not be resolved to an installable set of packages... affected by security advisories`.

**Causa raíz:** Composer 2.9+ bloquea por defecto la instalación de versiones con advisories de seguridad conocidos. Al 2026-07, toda la rama Laravel 11.x está afectada por al menos dos vulnerabilidades (una de severidad alta) sin ninguna versión corregida dentro de 11.x — solo parcheadas desde Laravel 12.60/12.61. Esto refleja el fin de la ventana de soporte de seguridad de Laravel 11 (2 años desde su release en marzo de 2024), no un error de configuración.

**No desactivar el chequeo de seguridad para forzar Laravel 11.** La resolución correcta, ya aplicada en este proyecto (`ADR-007`), es usar Laravel 12: `composer create-project laravel/laravel:^12.0 sgb-laravel`, tal como indica el paso 2 de este documento.

Si en el futuro Composer vuelve a bloquear una instalación por advisories, verificar primero — antes de desactivar cualquier política — si existe una versión más nueva dentro del mismo rango mayor que ya tenga el fix, consultando `https://packagist.org/api/security-advisories/?packages[]=<paquete>`.
