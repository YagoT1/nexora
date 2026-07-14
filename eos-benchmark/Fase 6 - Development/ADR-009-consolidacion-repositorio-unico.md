# ADR-009 — Consolidación de `sgb-laravel/` en `sistema-gestion-bibliotecaria/` como repositorio único

**Estado:** Cerrada — Resuelta. Repositorio único consolidado y verificado.
**Fecha:** 2026-07-13
**Decide:** Responsable del proyecto (Yago), sobre alternativas presentadas tras el cierre de `ADR-006`.

---

## Contexto

Con `ADR-006` cerrado, `sgb-laravel/` (creado dentro de `proximamente/`, hermano de
`sistema-gestion-bibliotecaria/`) es el proyecto Laravel 12 real: creado, migrado, sembrado,
iniciado y con su suite de 38 tests en verde. `sistema-gestion-bibliotecaria/` sigue siendo, como
lo fue desde el inicio (`ADR-001`), solo el origen de los archivos fuente del Módulo 1 — nunca tuvo
un `vendor/`, un `artisan`, ni el resto del esqueleto de Laravel, por diseño (`docs/BOOTSTRAP.md`
siempre trató a un proyecto separado como el destino real).

Se presentaron tres alternativas: (a) dejar `sgb-laravel/` como el repositorio definitivo, (b)
volcar su contenido de vuelta a `sistema-gestion-bibliotecaria/` y mantener ese como único
repositorio, (c) posponer la decisión. Se eligió **(b)**, preservando la estructura de carpetas
originalmente planeada en `ADR-001`.

## Restricción conocida: `.git` de `sistema-gestion-bibliotecaria/` sigue roto

Re-verificado en esta sesión, sin cambios desde `ADR-002`/`ADR-003`:

```
sistema-gestion-bibliotecaria/.git/objects/   → no existe
sistema-gestion-bibliotecaria/.git/config     → incompleto (falta bare, worktree)
proximamente/.git/index.lock                  → sigue presente (efecto secundario de ADR-003)
proximamente/.git/HEAD.lock + 5 variantes .stale.*  → siguen presentes
```

No es reparable in place. Además, `ADR-002` y `ADR-003` ya establecieron, con evidencia repetida,
que las operaciones de escritura de git ejecutadas **desde esta sesión de Cowork** sobre este mount
no se completan de forma confiable (dejan locks huérfanos, o directamente fallan). Por esa razón,
**todo el procedimiento de git de este ADR lo ejecuta el usuario directamente en su propia
terminal de Windows** — no en Cowork. Lo que sí se hizo desde esta sesión es la parte segura:
preparar el plan exacto y verificar (solo lectura) el estado de los archivos involucrados.

## Plan de ejecución

Ninguno de los pasos siguientes se ejecutó desde Cowork. Se documentan aquí para trazabilidad y se
entregan al usuario paso a paso en el chat, con el mismo criterio de "acción exacta → resultado
esperado → verificar antes de continuar" usado en `ADR-005`.

1. Limpiar los locks huérfanos en `proximamente/.git` (solo si no hay ningún proceso git corriendo).
2. Eliminar `sistema-gestion-bibliotecaria/.git` por completo (no reparable in place; sin `objects/`
   no hay ningún commit real que perder).
3. Copiar el contenido de `sgb-laravel/` hacia `sistema-gestion-bibliotecaria/` con `robocopy`,
   excluyendo explícitamente `vendor/`, `node_modules/`, `.git/`, cachés de `storage/framework/` y
   `.env` — sin usar `/MIR` ni `/PURGE`, para no borrar nada propio de
   `sistema-gestion-bibliotecaria/` que no exista en `sgb-laravel/` (`docs/`, `docker-compose.yml`,
   `README.md`, `.gitignore`).
4. Copiar explícitamente el `.env` ya verificado de `sgb-laravel/` (contiene la configuración de
   Postgres puerto 5433 ya probada, no secretos de producción).
5. Regenerar `vendor/` y `node_modules/` en el destino con `composer install` y `npm install` — no
   copiarlos, para no arrastrar binarios ni rutas absolutas del directorio anterior.
6. Volver a correr `php artisan test` **desde la nueva ubicación**, antes de tocar git — no asumir
   que la copia quedó bien, probarlo.
7. `git init`, `.gitignore` ya existente se aplica automáticamente, commit inicial, y `git remote
   add origin` con la URL real del repositorio (pendiente — no se asume ninguna, `BOOTSTRAP.md` ya
   la dejaba como placeholder).
8. Una vez confirmado, `sgb-laravel/` (ya no necesario, su propósito era servir de banco de
   pruebas para `ADR-006`) queda a criterio del usuario si se elimina o se conserva temporalmente.

## Verificación

Ejecutado por el usuario en su propia terminal, paso a paso:

1. Locks huérfanos en `proximamente/.git` eliminados; `git status` del repo raíz (`nexora`) responde con normalidad.
2. `sistema-gestion-bibliotecaria/.git` eliminado limpio (sin `objects/`, no había ningún commit real que perder).
3. `robocopy` desde `sgb-laravel/` con exclusión de `vendor/`, `node_modules/`, `.git/`, `.env` — **0 errores**, 209 archivos copiados. Preservó correctamente los archivos ya curados del proyecto (`README.md`, `.gitignore`, `.env.example`, marcados "Más antiguo" por robocopy porque la versión en destino ya era la vigente).
4. `.env` verificado copiado explícitamente desde `sgb-laravel/`.
5. `composer install` (112 paquetes, incluye `laravel/breeze` que antes no figuraba en el `composer.lock` de este repositorio) y `npm install` (161 paquetes) — ambos sin errores, `0 vulnerabilities`.
6. `php artisan test` desde la nueva ubicación consolidada: **38 passed, 94 assertions** — idéntico al resultado en `sgb-laravel/`, confirmando que la consolidación no alteró el comportamiento del proyecto.
7. Limpieza adicional: se eliminó `bootstrap/app.middleware.snippet.php`, referencia histórica del Módulo 1 original ya redundante (documentaba manualmente la fusión de middleware que ya ocurrió y quedó verificada en el `bootstrap/app.php` real).
8. `git init` + `git add -A` + `git commit`: **178 archivos, un commit raíz, 0 archivos de `vendor/`, `node_modules/`, `.env` o cachés reales incluidos** (confirmado inspeccionando `git status` antes de commitear, no asumido). `git remote add origin` queda pendiente — el usuario no proporcionó todavía la URL real del repositorio de GitHub para este proyecto (no se asume la de `nexora`, que pertenece a un repositorio distinto por diseño de `ADR-001`).

## Consecuencias

- `sistema-gestion-bibliotecaria/` vuelve a ser el único repositorio del proyecto, conforme a la
  estructura original de `ADR-001`.
- `docs/BOOTSTRAP.md` deja de tener vigencia como procedimiento a futuro una vez completado este
  ADR — pasa a ser documentación histórica de cómo se llegó a este estado, salvo su sección 9 y 10
  de troubleshooting, que siguen siendo reutilizables para cualquier instalación nueva del entorno.
- El housekeeping de `proximamente/.git` (paso 1) queda igual de pendiente para el usuario que lo
  que ya estaba documentado en `phase-summary.md` — este ADR no lo resuelve, solo lo reconfirma
  como prerrequisito del paso 2.

## Verificación final de integridad y cierre formal de `sgb-laravel/` (2026-07-13)

Tras completar `ADR-010` (commit `515c161` en `nexora`, ya pusheado a
`https://github.com/YagoT1/nexora.git`), y antes de eliminar `sgb-laravel/` según lo dejaba abierto
el paso 8 de este ADR, se ejecutó una verificación de integridad de solo lectura, archivo por
archivo, entre `sgb-laravel/` y `sistema-gestion-bibliotecaria/` — no se asumió que la consolidación
fue completa solo porque los pasos anteriores lo indicaban.

**Método:** comparación de listados de archivos (excluyendo `vendor/`, `node_modules/`, `.git/`,
cachés de `storage/framework/` y `.env`, todos ya cubiertos por decisión explícita en los pasos 3-5)
y checksum SHA-256 de cada archivo presente en ambos directorios.

**Resultado:**

- **0 archivos existen solo en `sgb-laravel/`** — ningún contenido relevante quedó sin consolidar.
- 3 archivos existen solo en `sistema-gestion-bibliotecaria/`: `docker-compose.yml`,
  `docs/BOOTSTRAP.md`, `docs/POSTGRES-MCP-SETUP.md` — exactamente los archivos que, por diseño
  (`ADR-001`, `ADR-004`), nunca fueron parte de `sgb-laravel/` (un esqueleto Laravel puro creado
  solo para validar el bootstrap en `ADR-006`). Consistente con lo esperado, no es un hallazgo.
- De 178 archivos presentes en ambos, 3 mostraron checksum distinto: `.gitignore`,
  `.phpunit.result.cache`, `package-lock.json`. Se investigó cada uno antes de descartarlo:
  - `.phpunit.result.cache`: artefacto local de la última ejecución de tests en cada carpeta:
    listado explícitamente en `.gitignore`, nunca trackeado en git. Sin efecto sobre la
    integridad del historial.
  - `package-lock.json`: difiere únicamente en el campo `name` (`sgb-laravel` vs
    `sistema-gestion-bibliotecaria`), consecuencia esperada de regenerar `node_modules/` con
    `npm install` en el destino en lugar de copiarlo (paso 5 de este ADR), ya verificado sin
    vulnerabilidades.
  - `.gitignore`: **hallazgo que requirió investigación adicional antes de concluir.** El acceso
    vía bash a este archivo específico devolvía contenido con ~1100 bytes de padding nulo (`\0`)
    después de las 23 líneas válidas — aparentando una diferencia real. Se verificó por tres vías
    independientes antes de sacar cualquier conclusión: (1) `git show HEAD:sistema-gestion-bibliotecaria/.gitignore`
    devuelve exactamente 286 bytes, limpio, byte a byte idéntico al de `sgb-laravel/`; (2) la
    herramienta `Read` (bridge distinto al bash de esta sesión) devuelve el mismo contenido limpio
    de 23 líneas, sin bytes nulos; (3) el `git status` ya confirmado previamente por el usuario en
    su propia terminal, sobre el árbol de trabajo real, no reportó ningún archivo modificado — si
    el archivo real en disco tuviera bytes nulos distintos del blob commiteado, git lo habría
    marcado como modificado. **Conclusión:** no hay corrupción real en disco, en git, ni en lo ya
    pusheado a `nexora`; la anomalía es exclusiva de cómo el sandbox de bash de esta sesión de
    Cowork lee ese archivo puntual. Se documenta como una instancia adicional, acotada a lectura
    (no escritura), del límite ya establecido en `ADR-002`/`ADR-003`/`ADR-010` sobre la fiabilidad
    de este mount — con la salvedad de que aquí no hay ninguna pérdida de datos, solo una lectura
    inconsistente que fue detectada y descartada con evidencia cruzada antes de reportarse como
    hallazgo real.
- Verificación estructural final: `vendor/`, `node_modules/`, `.env` (68 líneas, no vacío) y `.git`
  presentes y pobladas en `sistema-gestion-bibliotecaria/`, tal como exige el paso 5-7 de este ADR.

**Cierre:** con 0 pérdida de contenido confirmada, `sgb-laravel/` queda formalmente cerrado como
entorno temporal de validación (su propósito único, definido en `ADR-006`, ya se cumplió) y
corresponde eliminarlo. Se verificó, y se reconfirma aquí, que nunca tuvo `.git` propio — su
eliminación no tiene ningún efecto sobre ningún historial de control de versiones.

**Nota de ejecución — ampliación del límite conocido del sandbox de Cowork:** se intentó `rm -rf
sgb-laravel/` desde esta sesión, asumiendo que al no ser un repositorio git el riesgo documentado en
`ADR-002`/`ADR-003`/`ADR-010` no aplicaría. **Esa suposición fue incorrecta y se corrige aquí con
evidencia:** el intento falló al 100% — las 14.921 rutas de archivo (2.080 directorios) recibieron
`Operation not permitted`, incluyendo archivos ordinarios sin ninguna relación con git (`.editorconfig`,
`.env`, controladores PHP). `sgb-laravel/` permanece completamente intacta; no se perdió ningún dato,
pero tampoco se logró eliminar. **Conclusión:** el límite de fiabilidad de este mount para
operaciones destructivas no está acotado a `.git` — se extiende a la eliminación masiva de archivos
en general dentro de esta carpeta conectada. Se actualiza el criterio operativo: ninguna eliminación
masiva (no solo git-interna) debe intentarse de nuevo desde Cowork sobre este mount; queda pendiente
que el usuario ejecute `Remove-Item -Recurse -Force sgb-laravel` desde su propia terminal de
Windows, donde operaciones equivalentes ya se completaron sin problema en `ADR-009` original.
