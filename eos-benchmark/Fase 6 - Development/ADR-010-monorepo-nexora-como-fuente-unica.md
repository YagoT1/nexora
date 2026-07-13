# ADR-010 — `nexora` (monorepo raíz) como fuente única de código, documentación e historial

**Estado:** Aceptada
**Fecha:** 2026-07-13
**Decide:** Responsable del proyecto (Yago) — decisión institucional explícita, posterior al cierre de `ADR-009`.

---

## Contexto

`ADR-009` consolidó `sgb-laravel/` dentro de `sistema-gestion-bibliotecaria/` y le dio a esa carpeta
su propio repositorio git independiente (`git init`, commit raíz), preservando la estructura
originalmente decidida en `ADR-001` ("repositorio de código separado del repositorio de
documentación EOS").

El usuario decidió, inmediatamente después, que **`https://github.com/YagoT1/nexora.git` será el
repositorio oficial y definitivo del proyecto — única fuente de verdad para código, documentación,
trazabilidad e historial**. Se verificó (solo lectura, antes de decidir cómo interpretar la
instrucción) que ese remoto **ya existe y ya está configurado** como `origin` del repositorio raíz
`proximamente/` (el que contiene `eos-benchmark/`), con historial real ya sincronizado.

Esto excluye una lectura literal-ingenua de "agregar ese mismo remoto también en
`sistema-gestion-bibliotecaria/`": dos repositorios git independientes empujando al mismo remoto
producirían historiales en conflicto sobre las mismas ramas. La única interpretación técnicamente
coherente con "única fuente de verdad... historial del proyecto" es que **`nexora` (el repo raíz)
pasa a ser el monorepo del proyecto completo**, y `sistema-gestion-bibliotecaria/` se integra en él
como una subcarpeta con archivos versionados normalmente — no como un repositorio separado, ni como
submódulo.

## Decisión

Se elimina el `.git` independiente de `sistema-gestion-bibliotecaria/` (creado en `ADR-009`, un
solo commit, sin remoto configurado aún — nada que perder) y su contenido se trackea como parte
del repositorio raíz `proximamente/`, ya conectado a `nexora`.

Esto **supersede el mecanismo** (no el contenido ni el trabajo) de `ADR-001` y `ADR-009`: la
consolidación de código lograda en `ADR-009` (un solo directorio de proyecto, Laravel 12
funcional, 38 tests en verde) se mantiene íntegra — lo único que cambia es qué repositorio git la
aloja.

## Nota de ejecución

Se intentó eliminar `sistema-gestion-bibliotecaria/.git` desde el sandbox de Cowork (`rm -rf`) para
evaluar si el límite ya documentado en `ADR-002`/`ADR-003` (operaciones de escritura de git
inconsistentes sobre este mount) seguía vigente. **Confirmado nuevamente, esta vez de forma
absoluta:** los ~200 objetos, refs, logs y el índice fallaron todos con `Operation not permitted`,
sin ninguna excepción. Se descarta por completo intentar este tipo de operación desde Cowork —
la eliminación y el resto de los comandos de git de este ADR los ejecuta el usuario directamente
en su propia terminal de Windows, donde el mismo tipo de operación ya había funcionado sin
problema en `ADR-009`.

## Riesgo señalado (no bloqueante, para Architecture Review / DevOps futuro)

`ADR-001` había evaluado y **rechazado explícitamente** la alternativa de un único repositorio,
precisamente por el motivo que este ADR ahora acepta como decisión institucional: mezclar
documentación (sin CI/CD) con código de aplicación (con despliegue automático a Render.com desde
`main`, per DA-04) implica que un push puramente documental puede disparar o interferir con el
pipeline de despliegue si ese pipeline no está filtrado por path. **Recomendación:** al configurar
el trigger de despliegue en Render.com (pendiente, ver pre-checklist de infraestructura), limitarlo
explícitamente a cambios bajo `sistema-gestion-bibliotecaria/**`, no a cualquier push a `main`. Esto
preserva la separación de responsabilidades que motivó la alternativa B de `ADR-001`, ahora dentro
de un único repositorio en lugar de dos.

## Consecuencias

- `ADR-001` queda enmendado (no reescrito) con una nota que referencia este ADR.
- `sgb-laravel/`, ya sin propósito (banco de pruebas de `ADR-006`), se elimina — nunca se trackeó
  en ningún repositorio, así que su eliminación no tiene efecto sobre ningún historial de git.
- El `.gitignore` de `sistema-gestion-bibliotecaria/` (creado en `ADR-003`) sigue aplicando
  correctamente sobre esa subcarpeta aunque el tracking ahora ocurra desde la raíz — git respeta
  `.gitignore` de subdirectorios independientemente de en qué nivel se ejecute el comando.
- Cualquier referencia futura a "el repositorio de `sistema-gestion-bibliotecaria`" debe entenderse
  como "la subcarpeta `sistema-gestion-bibliotecaria/` dentro del monorepo `nexora`", no como un
  repositorio propio.
