# Setup del servidor MCP de PostgreSQL (Postgres MCP Pro)

Procedimiento reproducible para conectar un cliente MCP (Claude Desktop, Cursor, Claude Code) a la
base de datos PostgreSQL de este proyecto. Ver `ADR-004` en
`eos-benchmark/Fase 6 - Development` para el análisis de alternativas y la justificación de por
qué se eligió `crystaldba/postgres-mcp` (Postgres MCP Pro) en lugar del servidor de referencia de
Anthropic.

**Importante:** los pasos de este documento se ejecutan en tu máquina (Windows), no en una sesión
de Cowork — Cowork no tiene acceso a Docker Desktop ni a los clientes MCP instalados localmente.
Lo que se automatizó desde Cowork ya está aplicado (ver "Qué se hizo automáticamente" al final);
lo que sigue es lo que falta correr/verificar vos mismo.

## Prerrequisitos

- Docker Desktop instalado y corriendo en Windows. Verificar con:
  ```powershell
  docker --version
  docker ps
  ```
  Si el segundo comando falla, Docker Desktop no está corriendo — iniciarlo antes de continuar.

## 1. Levantar PostgreSQL 16 local

Desde la raíz de `sistema-gestion-bibliotecaria/` (donde está `docker-compose.yml`):

```powershell
docker compose up -d
```

Verificar que quedó saludable (puede tardar unos segundos):

```powershell
docker compose ps
```

La columna `STATUS` debe decir `healthy`. Si dice `starting` esperar unos segundos y repetir.

**Si el puerto 5432 ya está en uso** (otro Postgres corriendo): editar `docker-compose.yml`,
cambiar `"127.0.0.1:5432:5432"` por, por ejemplo, `"127.0.0.1:5433:5432"`, y usar el puerto 5433
en el `DATABASE_URI` del paso 3.

**Ya ocurrió en esta máquina** (ver `ADR-006`, Hallazgo 3): hay un PostgreSQL 18 nativo
(servicio de Windows `postgresql-x64-18`) escuchando en 5432. `docker-compose.yml` y
`DATABASE_URI` de abajo ya están actualizados a 5433 — no hace falta repetir este paso, solo
tenerlo presente si el contenedor se recrea desde cero en otra máquina sin ese conflicto.

## 2. Descargar la imagen del servidor MCP

```powershell
docker pull crystaldba/postgres-mcp
```

## 3. Configurar los clientes MCP

### Cursor — ya aplicado

`C:\Users\yagot\.cursor\mcp.json` ya tiene la entrada `postgres` agregada (hecho desde esta sesión
de Cowork, sin tocar la entrada `filesystem` existente). Solo hace falta **reiniciar Cursor** para
que la tome.

### Claude Desktop — pendiente, manual

Cowork no tiene acceso al `%APPDATA%\Claude` de tu máquina (es una carpeta reservada), así que este
paso hay que hacerlo a mano:

1. Abrir `%APPDATA%\Claude\claude_desktop_config.json` con un editor de texto (Notepad alcanza). Si
   el archivo no existe, crearlo.
2. Si el archivo está vacío o no existe, pegar exactamente esto:
   ```json
   {
     "mcpServers": {
       "postgres": {
         "command": "docker",
         "args": [
           "run",
           "-i",
           "--rm",
           "-e",
           "DATABASE_URI",
           "crystaldba/postgres-mcp",
           "--access-mode=unrestricted"
         ],
         "env": {
           "DATABASE_URI": "postgresql://sgb:sgb_dev_local_only@host.docker.internal:5433/sgb"
         }
       }
     }
   }
   ```
3. Si el archivo **ya tiene contenido** (otros `mcpServers`), **no reemplazar todo el archivo** —
   agregar solo la clave `"postgres": { ... }` (el bloque de arriba, desde `"command"` hasta el
   cierre de esa entrada) dentro del objeto `"mcpServers"` existente, separada por coma de las
   entradas que ya haya.
4. Guardar y **reiniciar Claude Desktop** por completo (cerrar desde la bandeja del sistema, no
   solo la ventana).

## 4. Verificar que funciona

No puedo ejecutar esto por vos — requiere Docker Desktop y los clientes corriendo en tu máquina.
Checklist:

1. En Cursor o Claude Desktop, después de reiniciar, buscar el indicador de servidores MCP
   conectados (ícono de herramientas/martillo en Cursor; ícono de conector en Claude Desktop) y
   confirmar que `postgres` aparece como conectado, sin errores.
2. Pedirle al asistente, en una conversación normal: **"Listá los schemas de mi base de datos
   Postgres"**. Respuesta esperada: al menos el schema `public` (vacío de tablas hasta que se corra
   `php artisan migrate --seed` del bootstrap de Laravel).
3. Si falla con un error de conexión: confirmar que `docker compose ps` (paso 1) sigue mostrando
   `healthy`, y que el `DATABASE_URI` usa `host.docker.internal` (no `localhost`) — es necesario
   porque el MCP corre en su propio contenedor Docker, distinto del de Postgres.
4. Si el cliente no muestra el servidor en absoluto: validar que el JSON quedó bien formado (un
   error de sintaxis hace que el cliente ignore el archivo completo, no solo esa entrada). Se puede
   pegar el contenido en cualquier validador de JSON.

## 5. Nota de seguridad — modo de acceso

Esta configuración usa `--access-mode=unrestricted` (lectura y escritura sin restricciones) porque
apunta a una base de datos de desarrollo local, sin datos reales, recreable en cualquier momento
con `docker compose down -v && docker compose up -d` + `php artisan migrate --seed`.

**Si en algún momento se reconfigura este mismo servidor MCP para apuntar a la instancia de
staging/producción de Render.com** (cambiando el `DATABASE_URI`), hay que cambiar también a
`--access-mode=restricted` en los `args` — ese modo limita el servidor a operaciones de solo
lectura con límite de tiempo de ejecución, apropiado para una base con datos reales. No usar
`unrestricted` contra ninguna base que no sea completamente descartable.

## Qué se hizo automáticamente desde Cowork

- `sistema-gestion-bibliotecaria/docker-compose.yml` — creado.
- `docs/BOOTSTRAP.md` — actualizado con referencia a este compose file.
- `C:\Users\yagot\.cursor\mcp.json` — entrada `postgres` agregada (JSON validado).
- Este documento y `ADR-004`.

No se tocó `%APPDATA%\Claude\claude_desktop_config.json` (sin acceso) ni se ejecutó ningún comando
Docker (corren en tu máquina, no en el sandbox de esta sesión).
