# ADR-011 — Incorporación de Desktop Commander MCP al stack permanente del entorno de desarrollo

**Estado:** Instalación local fijada, funcional y verificada (ver "Incidente 2" abajo). **En validación dentro de Cursor** — pasos 1 y 2 del protocolo de 9 confirmados exitosos (2026-07-13); pasos 3 a 9 pendientes de ejecución.
**Fecha:** 2026-07-13
**Decide:** Responsable del proyecto (Yago), a partir de investigación exclusiva de documentación oficial.
**Alcance:** Esta decisión es de **entorno de desarrollo** (máquina del desarrollador), no de arquitectura de la aplicación `sistema-gestion-bibliotecaria`. Se documenta en `eos-benchmark` siguiendo el mismo precedente de `ADR-004` (servidor MCP de PostgreSQL), que también fue una decisión de tooling de máquina local, no de la aplicación.

---

## Contexto

El equipo no dispone hoy de ninguna herramienta MCP capaz de ejecutar comandos directamente en la máquina Windows del usuario. Todo el trabajo de este proyecto que requirió tocar git, Docker o el entorno PHP real se resolvió entregando comandos exactos para que el usuario los corriera manualmente en su propia terminal. Se decidió incorporar **Desktop Commander MCP** como componente permanente del stack para cerrar esa brecha, verificando exclusivamente contra documentación oficial — sin asumir ninguna configuración ni comportamiento previo.

**Estado verificado de la configuración existente** (leído directamente, no asumido): `C:\Users\yagot\.cursor\mcp.json` contiene hoy dos servidores: `filesystem` (`mcp-server-filesystem`, acotado a `proximamente/`) y `postgres` (vía Docker, `crystaldba/postgres-mcp`). No existe ninguna entrada de Desktop Commander.

## Fuentes oficiales consultadas

- Repositorio oficial: `github.com/wonderwhy-er/DesktopCommanderMCP` — README.md (rama `main`), leído íntegro.
- `SECURITY.md` del mismo repositorio — leído íntegro.
- Registro oficial de npm: `registry.npmjs.org/@wonderwhy-er/desktop-commander/latest` — versión publicada verificada.
- Docker Hub API: `hub.docker.com/v2/repositories/mcp/desktop-commander/tags` — tags publicados verificados.
- Sitio oficial `desktopcommander.app` — usado solo para confirmar que existe un producto comercial separado ("Desktop Commander App") distinto del servidor MCP open source; **se descarta explícitamente**, porque el objetivo es un servidor MCP para Cursor, no una aplicación de escritorio con suscripción.

No se utilizó documentación de terceros (blogs, directorios agregadores) para ninguna decisión técnica; se citan solo como resultado de búsqueda inicial, no como fuente de la decisión.

## Hallazgo crítico de seguridad (evidencia textual, no interpretación)

El propio `SECURITY.md` del proyecto declara, textualmente:

> "Security is not currently our top priority - we haven't heard significant demand from users for stronger security controls."
>
> "The security restrictions built into the tool are primarily guardrails to help the AI model avoid actions the user didn't intend, rather than hardened security boundaries."
>
> "Directory restrictions can be bypassed via symlinks and terminal commands. Command blocking can be bypassed via substitution and absolute paths. Terminal commands can access files outside `allowedDirectories` restrictions."
>
> "For production use requiring security: Use Docker installation with selective folder mounting for complete isolation."

Esto es una declaración explícita del propio fabricante: los controles de configuración (`allowedDirectories`, `blockedCommands`) **no son un límite de seguridad real**, son solo una guía para evitar que la IA se equivoque por accidente. Cualquier comando ejecutado por este MCP corre con los permisos del usuario de Windows, sin sandboxing, salvo que se use la variante Docker.

## Alternativas evaluadas

| Criterio | A. Instalación directa (`npx`) | B. Instalación vía Docker (aislada) |
|---|---|---|
| Mecanismo | `npx -y @wonderwhy-er/desktop-commander@<version>` como proceso Node en el host | `docker run -i --rm -v <carpetas>:<mounts> mcp/desktop-commander` |
| Aislamiento | Ninguno — acceso directo al sistema de archivos y comandos con los permisos del usuario de Windows | Completo — el proceso corre en un contenedor; solo ve las carpetas montadas explícitamente |
| Reproducibilidad de versión | **Total** — npm publica versiones inmutables (`0.2.41` verificado en el registro); se puede fijar una versión exacta | **No disponible** — verificado en Docker Hub: el repositorio `mcp/desktop-commander` publica **un único tag, `latest`**, sin versiones fijas. Cualquier `docker pull` futuro puede traer una imagen distinta sin aviso |
| Camino oficial documentado para Cursor específicamente | Sí — la sección "Cursor" del README usa exactamente este método como config recomendada | No — el instalador automático de Docker (`install-docker.ps1`) configura explícitamente **Claude Desktop**, no Cursor. El uso con Cursor requeriría escribir el JSON del contenedor a mano, sin guía oficial específica para ese cliente |
| Complejidad de mantenimiento | Baja — un paquete npm, versión fija, actualización = cambiar un número en `mcp.json` | Media — depende de que Docker Desktop esté corriendo (ya lo está, por el MCP de Postgres de este mismo proyecto), y de mantener manualmente qué carpetas se montan |
| Riesgo residual | Alto en teoría (ejecución de comandos sin sandboxing), mitigado por el mismo protocolo de "explicar antes de ejecutar, verificar después" ya usado en todo este proyecto | Bajo — aislamiento real, pero **sacrifica la reproducibilidad de versión exacta**, un requisito explícito del usuario |
| Precedente en este proyecto | — | El MCP de Postgres (`ADR-004`) ya usa Docker por una razón de seguridad concreta y puntual (vulnerabilidad de inyección SQL sin parchear en la alternativa no-Docker) |

## Decisión

**Se adopta la Alternativa A: instalación directa vía `npx`, con la versión fijada explícitamente (no `@latest`).**

**Justificación:** el usuario pidió expresamente una incorporación "reproducible, mantenible, verificable" como prioridad, además de segura. La variante Docker, pese a ofrecer aislamiento real, **no puede cumplir el requisito de reproducibilidad de versión** con la imagen oficial actual (un solo tag mutable). Fijar la versión del paquete npm sí lo permite de forma total y verificable (`0.2.41`, confirmado contra el registro oficial el 2026-07-13). El riesgo de seguridad que introduce la falta de sandboxing se documenta explícitamente (no se minimiza) y se mitiga operativamente: Desktop Commander se usará bajo la misma disciplina ya vigente en todo este proyecto — ninguna acción se ejecuta sin explicar antes qué se va a hacer, por qué, y verificar el resultado después. Se deja constancia de que, si en el futuro se requiere aislamiento real (por ejemplo, si se decide dar a la IA autonomía sin supervisión directa), la migración a Docker queda disponible y documentada como alternativa, aceptando en ese momento el trade-off de perder el pineo de versión.

**Versión fijada:** `0.2.41` (la más reciente publicada en npm al momento de esta decisión, verificada directamente contra `registry.npmjs.org`, no asumida).

## Configuración propuesta (aún no aplicada)

Agregar la siguiente entrada a `C:\Users\yagot\.cursor\mcp.json`, dentro del objeto `mcpServers` ya existente, sin tocar las entradas `filesystem` ni `postgres`:

```json
"desktop-commander": {
  "command": "npx",
  "args": ["-y", "@wonderwhy-er/desktop-commander@0.2.41"]
}
```

**Qué se modifica:** un único archivo, `C:\Users\yagot\.cursor\mcp.json` (configuración global de Cursor, ya existente y ya editada anteriormente en este proyecto para el MCP de Postgres). Se agrega una única clave nueva (`desktop-commander`) dentro de `mcpServers`; no se elimina ni se altera ninguna clave existente.

**Por qué es necesaria:** es el único archivo que Cursor lee para descubrir servidores MCP globales — confirmado tanto por la sección "Cursor" del README oficial de Desktop Commander como por el hecho de que los dos servidores ya activos (`filesystem`, `postgres`) están declarados exactamente ahí.

**Evidencia que respalda la decisión:** README oficial (sección "Install in Other Clients" → "Cursor"), `SECURITY.md` oficial, registro npm (versión `0.2.41` confirmada), Docker Hub API (un solo tag `latest`, sin versiones fijas) — todo citado arriba, sin asumir nada no verificado directamente.

**Resultado esperado tras la modificación:** el archivo `mcp.json` queda con tres servidores (`filesystem`, `postgres`, `desktop-commander`), sintácticamente válido (JSON parseable), sin alterar el comportamiento de los dos servidores existentes. Cursor, al reiniciarse, debe mostrar `desktop-commander` en su panel de servidores MCP activos — esto **debe confirmarse dentro de Cursor por el usuario**, ya que esta sesión no tiene forma de observar la interfaz de Cursor ni de reiniciar la aplicación.

## Limitación de esta sesión para la validación posterior

Esta sesión de Cowork no tiene ningún acceso a Cursor ni a la máquina Windows del usuario más allá de los archivos de configuración de texto. La verificación de que Desktop Commander efectivamente ejecuta comandos, gestiona procesos largos y funciona correctamente en Windows **debe ejecutarse dentro de Cursor, por el usuario**, siguiendo un protocolo incremental que se entrega junto con la propuesta de modificación. Esta ADR no se cierra hasta que esa verificación se complete y se documenten los resultados.

## Ejecución (2026-07-13)

Confirmado por el usuario, sin requerir aprobación adicional por ítem ("Sí. No pidas confirmación, ejecuta"). Se modificó `C:\Users\yagot\.cursor\mcp.json` agregando la clave `desktop-commander` dentro de `mcpServers`, sin alterar `filesystem` ni `postgres`.

**Verificación objetiva (no visual, parseo real):** se cargó el archivo con `json.load` de Python. Resultado:
- JSON válido.
- Tres servidores registrados: `filesystem`, `desktop-commander`, `postgres`.
- `filesystem` idéntico al estado previo.
- `postgres` idéntico al estado previo (incluida su clave `env`, no expuesta en el log de verificación).
- `desktop-commander` con exactamente el contenido propuesto: `command: npx`, `args: ["-y", "@wonderwhy-er/desktop-commander@0.2.41"]`.

Resultado coincide con lo esperado. No se detectó ninguna evidencia contraria a la hipótesis (archivo corrupto, clave duplicada, sintaxis inválida) — no corresponde detener el proceso ni reabrir el diagnóstico.

## Protocolo de validación incremental (a ejecutar por el usuario, dentro de Cursor)

Esta sesión no tiene forma de abrir Cursor, reiniciarlo, ni observar su interfaz. Cada paso siguiente debe ejecutarse en Cursor y su resultado debe reportarse antes de pasar al siguiente. Si un paso no da el resultado esperado, corresponde detenerse y reabrir el diagnóstico, no continuar con los pasos siguientes.

1. **Reinicio completo de Cursor** (cerrar todas las ventanas, no solo recargar). *Resultado esperado:* Cursor vuelve a leer `mcp.json` al iniciar. ✅ **Confirmado (2026-07-13)** — reinicio completado por el usuario tras finalizar la instalación local.
2. **Registro:** abrir el panel de MCP de Cursor (Settings → MCP) y confirmar que `desktop-commander` aparece listado como servidor activo, con su set de herramientas visible (`execute_command`/`start_process`, `read_file`, `write_file`, `list_processes`, etc.). *Resultado esperado:* aparece sin errores de conexión. ✅ **Confirmado (2026-07-13)** — el usuario reporta el servidor activo en Cursor con **26 tools, 2 resources enabled**, sin errores de conexión visibles en el panel. Esto corrobora, desde dentro del cliente MCP real (no solo desde la prueba aislada por terminal), que la instalación local resolvió efectivamente el `ERR_MODULE_NOT_FOUND` de Incidente 2.
3. **Ejecución de comando simple (stdout):** pedirle que ejecute un comando inocuo, por ejemplo `whoami` o `echo Hola desde Desktop Commander`. *Resultado esperado:* la salida estándar se muestra correctamente en el chat. ✅ **Confirmado (2026-07-13)** — el Agente de Cursor ejecutó `whoami` vía Desktop Commander y devolvió `yago-s-torres\yagot` (formato `DOMINIO\usuario` real de Windows). Esto distingue claramente esta ejecución de una prueba equivalente corrida en un sandbox Linux (que devolvería solo `yagot`, sin dominio) — confirma que el comando corrió sobre el sistema Windows real del usuario, no sobre un entorno aislado.
4. **Captura de error (stderr):** pedirle que ejecute un comando inexistente, por ejemplo `comando_que_no_existe_xyz`. *Resultado esperado:* el error se captura y se reporta explícitamente, no falla en silencio. ✅ **Confirmado (2026-07-13)** — devolvió `CommandNotFoundException` / `ObjectNotFound: (comando_que_no_existe_xyz:String)` de PowerShell, reportado explícitamente en el chat, sin colgarse ni fallar en silencio.
5. **Proceso de larga duración — lectura en curso:** iniciar un proceso que tarde, por ejemplo `ping -n 15 127.0.0.1` (PowerShell/cmd), y leer su salida antes de que termine. *Resultado esperado:* se puede leer la salida parcial mientras el proceso sigue corriendo, no solo al finalizar.
6. **Proceso de larga duración — terminación:** iniciar un proceso persistente (por ejemplo `ping -t 127.0.0.1`) y terminarlo explícitamente. *Resultado esperado:* el proceso se corta efectivamente, verificable con `list_processes`/`kill_process` o equivalente.
7. **Interacción específica con Windows:** ejecutar un comando específico de PowerShell, por ejemplo `Get-ChildItem` o `$PSVersionTable`, no solo comandos de `cmd.exe`. *Resultado esperado:* se ejecuta correctamente, confirmando qué shell usa por defecto (`get_config` debe reportar `defaultShell`).
8. **Alcance de archivos:** ejecutar `get_config({})` y reportar el valor por defecto de `allowedDirectories`. Si viene vacío (acceso a todo el filesystem para operaciones de archivo, según advertencia del propio fabricante), acotarlo explícitamente a las carpetas de trabajo reales (`C:\Users\yagot\Downloads\proximamente`, `C:\Users\yagot\.cursor`) vía `set_config_value`, y volver a confirmar con `get_config({})`.
9. **Auditoría:** confirmar que existe el log `%USERPROFILE%\.claude-server-commander\claude_tool_call.log` tras los pasos anteriores, como evidencia de que el registro de auditoría del propio servidor está activo.

## Incidente 1 — Falla de instalación de `npx` en el paso 1 del protocolo (en investigación)

**Fecha:** 2026-07-13. **Estado:** abierto, sin causa raíz confirmada.

Al reiniciar Cursor, el log de MCP muestra: conexión stdio iniciada correctamente, ~1 minuto de
descarga/extracción del paquete, y luego cientos de `npm warn tar TAR_ENTRY_ERROR ENOENT` (archivos
esperados por el extractor `tar` que no aparecen), seguido de `npm error code ENOTEMPTY` al intentar
borrar `node_modules\async` durante la limpieza, y `npm warn cleanup ... EPERM: operation not
permitted, rmdir '...\node_modules\@supabase'`. Termina en `Connection failed: MCP error -32000:
Connection closed` — el servidor nunca llegó a iniciar.

**Descartado:** la entrada en `mcp.json` no es la causa — su sintaxis y contenido ya fueron
verificados objetivamente (ver sección "Ejecución" arriba) y coinciden exactamente con el formato
oficial documentado para "Other Clients"/Cursor.

**Hipótesis en evaluación (no confirmadas):**

1. Caché de `npx` corrupta o de una extracción parcial previa, sin relación con software externo.
2. Interferencia de un proceso externo (antivirus u otro) reteniendo archivos mientras `tar` intenta
   escribirlos o borrarlos — el patrón `ENOENT` masivo durante extracción seguido de `EPERM` en la
   limpieza es la misma firma que la de `ADR-005` (Avast interceptando operaciones de Composer/PHP
   en esta misma máquina), pero **no se confirma que sea la misma causa** sin evidencia adicional.
3. El paquete es inusualmente pesado para un simple servidor de terminal/filesystem (incluye
   Puppeteer, Sharp, ExcelJS, un editor Tiptap completo, cliente de Supabase), lo que amplía la
   ventana de tiempo en la que cualquier interferencia externa puede manifestarse.

**Paso de diagnóstico propuesto (el más simple primero, antes de sospechar de software externo):**
borrar únicamente la entrada de caché de `npx` correspondiente a este paquete
(`C:\Users\yagot\AppData\Local\npm-cache\_npx\842d8baeac482b44`) y reintentar. Si el borrado en sí
falla con el mismo patrón `EPERM`/`ENOTEMPTY`, es evidencia a favor de la hipótesis 2 (algo retiene
esos archivos activamente ahora, no solo durante la extracción). Si el borrado funciona y el
reintento conecta correctamente, se documenta como caché corrupta puntual, sin relación con
software externo.

Pendiente: resultado del usuario para continuar el diagnóstico o cerrar el incidente.

## Incidente 2 — Evidencia contradictoria: la causa raíz NO es (solo) caché corrupta; reformulación del diagnóstico

**Fecha:** 2026-07-13. **Estado:** causa raíz confirmada con evidencia directa. Diagnóstico de Incidente 1 reformulado.

### Evidencia recolectada

El usuario ejecutó el paso de diagnóstico propuesto en Incidente 1: borró la carpeta de caché
(`Test-Path` confirmó `False` tras el borrado, es decir, el borrado sí se completó, sin error
`EPERM`/`ENOTEMPTY` en el borrado en sí — esto **descarta parcialmente** la hipótesis 2 de Incidente 1
en su forma más simple: nada retiene la carpeta de caché de forma persistente) y reinició Cursor. El
log de la reconexión muestra:

1. La misma cascada de `npm warn tar TAR_ENTRY_ERROR ENOENT` que en Incidente 1 (cientos de entradas),
   seguida de un `npm error code ENOTEMPTY` al limpiar `node_modules\puppeteer-core\lib\puppeteer\cdp`.
   Esto por sí solo seguiría siendo compatible con la hipótesis de interferencia externa (antivirus)
   de Incidente 1 — **no se descarta esa hipótesis secundaria**, queda abierta.
2. Pero, a diferencia de Incidente 1, esta vez la instalación avanzó lo suficiente para intentar
   **arrancar el proceso Node real**, y falló con un error distinto y determinista, reproducido
   **dos veces consecutivas** (10:45:31 y 10:45:42), con el mismo mensaje exacto ambas veces:

   ```
   Error [ERR_MODULE_NOT_FOUND]: Cannot find module
   '...\node_modules\@modelcontextprotocol\sdk\server\stdio.js' imported from
   '...\node_modules\@wonderwhy-er\desktop-commander\dist\custom-stdio.js'
   ```

Un error `ERR_MODULE_NOT_FOUND` de Node no es un error de permisos ni de sistema de archivos: es el
resolvedor de módulos ESM de Node diciendo, en tiempo de ejecución, que ese archivo específico no
existe en el árbol de `node_modules` que sí se terminó de instalar. Que se reproduzca **igual, dos
veces seguidas, después de un borrado de caché confirmado**, es evidencia directa de que **no es un
problema de caché corrupta ni de interferencia intermitente** — es determinista.

### Verificación independiente (no asumida): manifiesto real de npm

Se consultó directamente `registry.npmjs.org/@wonderwhy-er/desktop-commander/0.2.41` (no una fuente de
terceros). El manifiesto confirma:

```
"@modelcontextprotocol/sdk": "^1.9.0"
```

Es decir: `desktop-commander@0.2.41` **no fija** la versión de su propia dependencia del SDK de MCP —
usa un rango flexible (`^1.9.0`, cualquier `1.x >= 1.9.0`). El manifiesto también confirma
`"_hasShrinkwrap": false`: el paquete publicado **no incluye un lockfile**, así que cada instalación
vía `npx` (que no reutiliza un lockfile del proyecto, porque no hay proyecto) resuelve de nuevo, en
el momento de la instalación, cuál es la versión `1.x` más reciente que satisface ese rango.

Esto es relevante ahora mismo porque, por búsqueda directa, el SDK oficial de TypeScript de MCP está
en medio de una reestructuración activa hacia una v2 (beta en curso, estable anunciada para el
2026-07-28 según el blog oficial del proyecto) que ya introdujo, dentro de la propia serie `1.x`, una
carpeta interna `shared/` — visible directamente en los archivos que si se extrajeron correctamente
en este intento: `node_modules\@modelcontextprotocol\sdk\dist\cjs\shared\stdio.js`,
`dist\esm\shared\stdio.d.ts.map`, etc. El código compilado de `desktop-commander@0.2.41`
(`dist/custom-stdio.js`), en cambio, sigue haciendo un import fijo a la ruta antigua
`@modelcontextprotocol/sdk/server/stdio.js`, que en la versión del SDK que efectivamente se resolvió
ya no existe en esa ubicación.

### Diagnóstico reformulado

**Causa raíz confirmada:** incompatibilidad entre dos paquetes versionados de forma independiente —
`desktop-commander@0.2.41` fue publicado con un rango flexible (`^1.9.0`) hacia
`@modelcontextprotocol/sdk`, y ese rango hoy resuelve a una versión del SDK cuya reestructuración
interna (parte de la migración hacia v2) rompió la ruta de import fija que el código de
`desktop-commander` espera. **No es** un problema de nuestra máquina, de `mcp.json`, ni (únicamente)
de interferencia de antivirus. La hipótesis de Incidente 1 sobre caché corrupta queda **descartada
como causa suficiente** (el borrado de caché no lo arregló); la hipótesis de interferencia externa
durante la extracción (`TAR_ENTRY_ERROR`/`ENOTEMPTY`) **sigue abierta como problema secundario**, pero
ya no es la causa bloqueante — aunque esa extracción fuera perfecta, el error de resolución de
módulos ocurriría igual, porque depende de qué versión del SDK exista en el registro de npm en el
momento de instalar, no de cómo se extrajo el paquete.

### Alternativas de remediación evaluadas

| Criterio | A. Reintentar `npx` sin cambios | B. Instalación local fijada (con lockfile propio) | C. Docker, pineado por digest en vez de `latest` |
|---|---|---|---|
| Resuelve la causa raíz | No — el rango `^1.9.0` sin fijar sigue resolviendo a la misma versión del SDK mientras esa sea la más reciente publicada | Sí — un `npm install` local genera `package-lock.json`, y se puede forzar explícitamente una versión de `@modelcontextprotocol/sdk` anterior a la reestructuración, quedando fijada para siempre en ese lockfile | Solo parcialmente — pinear por digest sí da reproducibilidad de imagen, pero no soluciona el problema de fondo si la imagen del mismo maintainer también depende del SDK sin fijar |
| Compatible con el propósito de Desktop Commander (ejecutar comandos en el host Windows real) | Sí | Sí | **No** — un contenedor Docker solo ve su propio filesystem y su propio shell; `execute_command` correría dentro del contenedor, no en el PowerShell/cmd real del usuario. Esto es distinto del MCP de Postgres (que solo necesita alcanzar una base de datos por red, no "ver" el host) |
| Reproducibilidad futura | No | Sí — lockfile versionado, `npm ci` reproduce el mismo árbol exacto en cualquier máquina | N/A (descartado por el punto anterior) |
| Complejidad adicional | Ninguna, pero no resuelve nada | Media — un `npm install` manual una vez, en una carpeta fija, más un `override` explícito de la versión del SDK | Alta, y además inútil para este caso concreto |
| Consistencia con la prioridad ya declarada (reproducibilidad ante todo) | No cumple | Cumple, y de forma más robusta que el `npx` pineado original (que solo fijaba el paquete top-level, no sus dependencias transitivas) | No aplica |

### Decisión

**Se abandona `npx` como mecanismo de instalación de Desktop Commander y se adopta la Alternativa B:
instalación local fijada, en una carpeta dedicada, con `package-lock.json` propio y la dependencia
`@modelcontextprotocol/sdk` forzada explícitamente a una versión anterior a la reestructuración que
causó esta falla.**

**Justificación:** el objetivo original de ADR-011 — reproducibilidad total, no solo del paquete
top-level sino de todo el árbol de dependencias — nunca se cumplió del todo con `npx`, porque `npx`
no persiste ni versiona las dependencias transitivas entre instalaciones. Esta instalación local
cierra exactamente esa brecha, sin sacrificar la capacidad de Desktop Commander de operar
directamente sobre el sistema real del usuario (razón por la que Docker se descarta de nuevo, ahora
con un argumento más fuerte que la falta de versionado de imagen: es arquitectónicamente incompatible
con el propósito de la herramienta).

Ver `DESKTOP-COMMANDER-MCP-SETUP.md` (actualizado) para los comandos exactos que el usuario debe
ejecutar en su propia terminal — esta sesión de Cowork no tiene acceso a `npm`/`node` de Windows para
ejecutarlos por sí misma.

`C:\Users\yagot\.cursor\mcp.json` fue actualizado para apuntar a la instalación local
(`node <carpeta>\node_modules\@wonderwhy-er\desktop-commander\dist\index.js`) en lugar de `npx`. Esta
entrada **no funcionará hasta que el usuario complete la instalación local** descrita en el documento
de setup — se documenta así, sin ambigüedad, para no dar una falsa señal de que ya quedó operativo.

### Incidente adicional durante la instalación local — falla de Puppeteer por interferencia TLS (resuelto)

Al ejecutar `npm install @wonderwhy-er/desktop-commander@0.2.41` por primera vez en la carpeta local,
la instalación falló de forma distinta a Incidente 1: `npm error ... unable to verify the first
certificate` al intentar el postinstall de Puppeteer (descarga del binario de Chrome), junto con la
misma firma de `EPERM`/limpieza fallida vista antes. Este error de certificado es la misma familia de
problema ya documentada en `ADR-005` (Composer/PHP interceptado por SSL en esta misma máquina) —
ahora afectando a `npm`/Node en vez de a Composer. La instalación quedó incompleta: `dist/index.js`
de `desktop-commander` no llegó a existir (`Test-Path` confirmó `False`), pese a que npm reportó
"added 82 packages" (subdependencias sí se instalaron; el paquete principal no).

**Remediación aplicada:** se reinstaló desde cero (`node_modules` y `package-lock.json` borrados
primero) con la variable de entorno `PUPPETEER_SKIP_DOWNLOAD=true` fijada solo para esa sesión de
PowerShell, evitando por completo el paso de descarga que fallaba, sin tocar certificados ni
configuración de seguridad del sistema. Resultado: instalación completa (523 paquetes), sin
`TAR_ENTRY_ERROR` ni `EPERM`, `Test-Path` confirmó `True`.

**Nota para el futuro:** si alguna función de Desktop Commander que dependa del navegador embebido de
Puppeteer llega a necesitarse, habrá que revisar en ese momento si conviene resolver la interferencia
TLS de fondo (mismo mecanismo que en `ADR-005`) o mantener `PUPPETEER_SKIP_DOWNLOAD=true` de forma
permanente para este servidor. Por ahora no es necesario: las funciones de terminal/filesystem que
motivan esta incorporación no dependen de Puppeteer.

### Verificación funcional aislada (fuera de Cursor) — resultado

- `npm list @modelcontextprotocol/sdk` confirmó: `@modelcontextprotocol/sdk@1.9.0` en ambos lugares
  del árbol (dependencia directa fijada y dependencia transitiva de `desktop-commander`, deduplicadas
  — sin versiones conflictivas).
- `node node_modules\@wonderwhy-er\desktop-commander\dist\index.js`, corrido standalone, quedó a la
  espera de entrada por stdio sin imprimir ningún error — el comportamiento esperado de un servidor
  MCP por stdio sin un cliente real del otro lado, y la ausencia de `ERR_MODULE_NOT_FOUND` confirma
  que Incidente 2 quedó resuelto.

**Incidente 2: cerrado.** La instalación local fijada es funcional. Continúa pendiente únicamente el
protocolo de validación de 9 pasos dentro de Cursor (sección "Protocolo de validación incremental"
arriba), que es la última condición para cerrar esta ADR por completo.

## Conclusión pendiente

Esta ADR no se da por cerrada hasta completar los 9 pasos del protocolo con resultados reportados,
usando la instalación local (Alternativa B), ya verificada como funcional de forma aislada. Si todos
los pasos son satisfactorios, Desktop Commander queda **aprobado como componente permanente del stack
MCP**, con la salvedad de seguridad ya documentada (sin sandboxing real, mitigado operativamente), la
salvedad de que Puppeteer opera sin su navegador embebido (`PUPPETEER_SKIP_DOWNLOAD=true`, ver arriba)
y la salvedad de mantenimiento de que cualquier actualización futura de versión debe repetir el mismo
proceso de fijado explícito, nunca `@latest` ni un rango flexible sin revisar. Si algún paso falla o
produce un resultado inesperado, esta ADR se reabre para diagnóstico antes de aprobar la
incorporación.
