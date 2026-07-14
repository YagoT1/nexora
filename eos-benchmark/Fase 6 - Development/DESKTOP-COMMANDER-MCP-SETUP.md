# Setup de Desktop Commander MCP (entorno de desarrollo)

Procedimiento reproducible para incorporar Desktop Commander MCP a Cursor (u otro cliente MCP) en
cualquier máquina. Ver `ADR-011-incorporacion-desktop-commander-mcp.md` en esta misma carpeta para
el análisis completo de alternativas, la evidencia de seguridad y la justificación de cada decisión.

Esta es una decisión de **entorno de desarrollo**, no de la aplicación `sistema-gestion-bibliotecaria`
— por eso este documento vive en `eos-benchmark`, no en `sistema-gestion-bibliotecaria/docs/` (que
está reservado a configuración propia de esa aplicación, como el MCP de Postgres).

**Importante:** los pasos de este documento se ejecutan en tu máquina, no en una sesión de Cowork —
Cowork no tiene acceso a Cursor ni puede reiniciarlo. Lo que se hizo automáticamente desde Cowork ya
está aplicado (ver "Qué se hizo automáticamente" al final); lo que sigue es lo que falta correr y
verificar vos mismo.

## Qué es y qué no es

Desktop Commander MCP (`@wonderwhy-er/desktop-commander`, MIT, repositorio oficial
`github.com/wonderwhy-er/DesktopCommanderMCP`) es un servidor MCP que da a un cliente compatible
(Cursor, Claude Desktop, etc.) la capacidad de ejecutar comandos de terminal, gestionar procesos y
operar sobre el sistema de archivos de la máquina donde corre.

**No confundir con "Desktop Commander App"** (`desktopcommander.app`), un producto comercial de
escritorio con suscripción mensual, propiedad del mismo autor pero **distinto** del servidor MCP
open source. Esta incorporación usa exclusivamente el servidor MCP, gratuito.

## Prerrequisitos

- Node.js ≥ 18 instalado (requisito declarado en el `package.json` oficial del proyecto). Verificar:
  ```powershell
  node --version
  npx --version
  ```
  Si `npx` no está disponible, instalar Node.js 18+ desde [nodejs.org](https://nodejs.org) primero.

## 1. Instalación local fijada — **PENDIENTE, requiere que vos la ejecutes**

> **Cambio de método (2026-07-13, ver "Incidente 2" en `ADR-011`):** el método original vía `npx`
> falló de forma determinista con `ERR_MODULE_NOT_FOUND` — `desktop-commander@0.2.41` declara su
> dependencia de `@modelcontextprotocol/sdk` con un rango flexible (`^1.9.0`, no fijado), y ese rango
> resuelve hoy a una versión del SDK cuya reestructuración interna (parte de su migración a v2, en
> beta activa) rompió la ruta de import que `desktop-commander` espera. `npx` no persiste ni versiona
> dependencias transitivas entre instalaciones, así que reintentar no lo arregla. Se reemplaza por una
> **instalación local fijada**, con `package-lock.json` propio, que sí permite fijar también esa
> dependencia transitiva.

Ejecutar en PowerShell (una sola vez):

```powershell
mkdir "$env:USERPROFILE\.cursor\mcp-servers\desktop-commander" -Force
cd "$env:USERPROFILE\.cursor\mcp-servers\desktop-commander"
npm init -y
npm install @wonderwhy-er/desktop-commander@0.2.41
npm install @modelcontextprotocol/sdk@1.9.0 --save-exact
```

**Por qué `@modelcontextprotocol/sdk@1.9.0` específicamente:** es el límite inferior exacto que el
propio `desktop-commander@0.2.41` declara como compatible (`^1.9.0` significa "1.9.0 o cualquier 1.x
posterior"), así que el autor garantiza esa versión como válida por su propio manifiesto de npm
(verificado directamente contra `registry.npmjs.org/@wonderwhy-er/desktop-commander/0.2.41`, no
asumido). Además, se confirmó contra `registry.npmjs.org/@modelcontextprotocol/sdk/1.9.0` que esa
versión expone un mapa de `exports` con comodín abierto (`"./*"`), compatible con el import profundo
que `desktop-commander` necesita — a diferencia de la versión que se resolvió por defecto, cuyo
archivo de `stdio` ya se había movido a una carpeta interna `shared/` distinta de la que
`desktop-commander` importa.

Después del segundo `npm install`, verificar que el archivo objetivo existe antes de configurar Cursor:

```powershell
Test-Path "$env:USERPROFILE\.cursor\mcp-servers\desktop-commander\node_modules\@wonderwhy-er\desktop-commander\dist\index.js"
```

*Resultado esperado:* `True`. Si da `False`, el `npm install` no completó correctamente — no continuar
al siguiente paso hasta resolverlo (pegar el error completo para diagnosticar, no reintentar a ciegas).

Como prueba adicional, antes de tocar Cursor, correr el servidor de forma aislada y confirmar que
arranca sin el error `ERR_MODULE_NOT_FOUND`:

```powershell
node "$env:USERPROFILE\.cursor\mcp-servers\desktop-commander\node_modules\@wonderwhy-er\desktop-commander\dist\index.js"
```

*Resultado esperado:* el proceso queda esperando entrada por stdio (no imprime ningún error y no
termina solo) — cortarlo con Ctrl+C una vez confirmado. Si vuelve a aparecer `ERR_MODULE_NOT_FOUND`
u otro error, **detenerse y reportarlo** antes de tocar `mcp.json` — no continuar bajo el supuesto de
que "capaz igual funciona en Cursor".

## 2. Configuración en Cursor — ya aplicada, condicionada a que el paso 1 esté OK

`C:\Users\yagot\.cursor\mcp.json` ya apunta a la instalación local (no a `npx`):

```json
"desktop-commander": {
  "command": "node",
  "args": ["C:\\Users\\yagot\\.cursor\\mcp-servers\\desktop-commander\\node_modules\\@wonderwhy-er\\desktop-commander\\dist\\index.js"]
}
```

**Esta entrada no funciona todavía** — depende de que el paso 1 se haya ejecutado y verificado en tu
máquina primero. Si reiniciás Cursor antes de completar el paso 1, `desktop-commander` va a fallar en
el panel de MCP con un error de "no such file" (la carpeta todavía no existe), no con el
`ERR_MODULE_NOT_FOUND` anterior — es un fallo distinto y esperado hasta que completes el paso 1.

**Para reproducir esto en otra máquina desde cero:** repetir el paso 1 completo (crear la carpeta,
`npm init`, instalar `desktop-commander@0.2.41`, fijar `@modelcontextprotocol/sdk@1.9.0`), y agregar
el bloque de arriba dentro del objeto `"mcpServers"` de `~/.cursor/mcp.json`, ajustando la ruta al
usuario correspondiente. Si el archivo ya tiene otras entradas, **no reemplazar el archivo completo**
— agregar solo esta clave.

### Cómo actualizar la versión en el futuro (decisión explícita, no automática)

1. Verificar la versión estable más reciente de `desktop-commander`:
   `https://registry.npmjs.org/@wonderwhy-er/desktop-commander/latest`.
2. Revisar el *changelog* del release en `github.com/wonderwhy-er/DesktopCommanderMCP/releases`.
3. Dentro de la carpeta de instalación local, correr
   `npm install @wonderwhy-er/desktop-commander@<nueva_version>`.
4. Verificar contra el registro de npm qué rango de `@modelcontextprotocol/sdk` declara esa nueva
   versión, y si ese rango sigue siendo compatible con la versión ya fijada (`1.9.0`) o si hace falta
   fijar una versión distinta del SDK — **no asumir que sigue funcionando sin verificarlo**.
5. Repetir la prueba aislada (`node dist/index.js`, confirmar que no tira `ERR_MODULE_NOT_FOUND`)
   antes de reiniciar Cursor.
6. Repetir el protocolo de validación incremental de `ADR-011` antes de dar por buena la actualización.
7. Documentar el cambio (nueva ADR o adenda a `ADR-011`, con la versión anterior, la nueva, y el motivo).

Nunca cambiar a `@latest` ni a un rango flexible sin fijar "para simplificar" — es exactamente lo que
causó el Incidente 2.

## 3. Reiniciar Cursor

Cerrar **todas** las ventanas de Cursor (no solo recargar la ventana) y volver a abrirlo, para que
relea `mcp.json`.

## 4. Validación

Ver el protocolo completo de 9 pasos en `ADR-011` ("Protocolo de validación incremental"). Resumen:

1. Confirmar que `desktop-commander` aparece registrado en el panel de MCP de Cursor.
2. Ejecutar un comando simple y confirmar que se lee la salida estándar.
3. Ejecutar un comando inválido y confirmar que el error se captura (no falla en silencio).
4. Iniciar un proceso largo y leer su salida antes de que termine.
5. Iniciar un proceso persistente y terminarlo explícitamente.
6. Ejecutar un comando específico de PowerShell (no solo `cmd.exe`).
7. Revisar `allowedDirectories` (`get_config({})`) y acotarlo explícitamente si viene vacío.
8. Confirmar que el log de auditoría (`%USERPROFILE%\.claude-server-commander\claude_tool_call.log`) se genera.

No dar la incorporación por terminada hasta completar todos los pasos con resultado satisfactorio.

## 5. Nota de seguridad (leer antes de usar)

Cita textual del `SECURITY.md` oficial del proyecto:

> "The security restrictions built into the tool are primarily guardrails to help the AI model avoid
> actions the user didn't intend, rather than hardened security boundaries." — "Terminal commands can
> access files outside `allowedDirectories` restrictions."

En criollo: los límites de configuración (`allowedDirectories`, `blockedCommands`) **no son una
barrera de seguridad real** — son solo para evitar errores accidentales de la IA. Cualquier comando
ejecutado corre con los permisos reales del usuario de Windows. Mitigación adoptada: usar Desktop
Commander bajo la misma disciplina de este proyecto — explicar antes de ejecutar, verificar después,
nunca delegar acciones irreversibles sin revisión humana explícita. Si en el futuro se necesita
aislamiento real, la alternativa documentada y descartada por ahora (Docker, ver `ADR-011`) sigue
disponible, aceptando en ese momento perder el pineo de versión exacta.

## Qué se hizo automáticamente desde Cowork

- `C:\Users\yagot\.cursor\mcp.json` — entrada `desktop-commander` actualizada para apuntar a la
  instalación local (`node <ruta>\dist\index.js`) en lugar de `npx` (JSON validado con parseo real,
  no solo inspección visual).
- `ADR-011-incorporacion-desktop-commander-mcp.md` (con el diagnóstico de Incidente 2) y este
  documento.

No se creó la carpeta de instalación local, no se corrió ningún `npm install`, no se reinició Cursor
y no se corrió ningún paso del protocolo de validación — todo eso ocurre en tu máquina real, fuera del
alcance de esta sesión. La entrada de `mcp.json` **no funcionará hasta que completes el paso 1**.
