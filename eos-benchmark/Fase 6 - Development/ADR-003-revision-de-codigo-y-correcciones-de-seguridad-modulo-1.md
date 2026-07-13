# ADR-003 — Revisión de código y correcciones de seguridad sobre el código entregado del Módulo 1

**Estado:** Aceptada
**Fecha:** 2026-07-08
**Decide:** Revisión técnica solicitada explícitamente por el responsable del proyecto (Yago), ejecutada en esta sesión de Cowork. Alcance y regla de trabajo fijados por el propio solicitante: medir riesgo antes de actuar, permiso para instalar herramientas de verificación, prohibición explícita de destruir nada, y obligación de documentar todo.

---

## Contexto

Sobre el código fuente entregado en la Fase 06 (Módulo 1, ver `ADR-001` y `ADR-002`) se realizó una revisión de código enfocada en seguridad, manejo de casos borde de negocio y consistencia, dado que ese código nunca fue ejecutado en el entorno donde se escribió (`ADR-002`). Se identificaron dos hallazgos con corrección aplicada en esta misma sesión, y tres hallazgos menores que quedan documentados sin corregir por estar fuera del alcance autorizado.

## Hallazgos corregidos

### 1. Fuga de datos sensibles en el registro de auditoría

`app/Support/Auditing/Auditable.php` registraba en `registros_auditoria` el resultado completo de `$model->getAttributes()` (evento `created`) y `$model->getChanges()` (evento `updated`), sin excluir ningún campo. Para el modelo `User`, esto incluía el hash de la contraseña (`password`) y, potencialmente, `remember_token`, quedando escritos en texto plano dentro de una tabla de auditoría cada vez que se crea o modifica un usuario. Aunque el valor esté hasheado, persistirlo en una tabla adicional amplía innecesariamente la superficie de exposición si esa tabla se filtra, se expone en un reporte, o se accede sin los mismos controles que a la tabla `users`.

**Corrección aplicada:** `registrarAuditoria()` ahora excluye, vía `Illuminate\Support\Arr::except()`, los campos declarados en `$model->getHidden()` antes de persistir `valor_anterior` y `valor_nuevo`. Es un cambio genérico (no específico de `User`): cualquier modelo que use el trait `Auditable` y declare `$hidden` queda protegido de la misma forma; los modelos sin campos sensibles declarados (`ParametroConfiguracion`, `Socio`, etc.) no cambian de comportamiento porque `getHidden()` devuelve un arreglo vacío.

### 2. Riesgo de que un administrador se autobloquee

`app/Http/Controllers/Admin/UserController.php` permitía que un administrador, actuando sobre su propio registro de usuario vía `update()` o `inactivar()`, se quitara a sí mismo el rol de administrador o se desactivara la cuenta, sin ninguna restricción. Si ese administrador fuera el único activo, la acción dejaría al sistema completo sin ningún usuario con permisos de administración, sin mecanismo de recuperación previsto en el Módulo 1 (no hay comando de emergencia ni acceso de superusuario fuera de la tabla `users`).

**Corrección aplicada:** `update()` rechaza (con `422` y mensaje de validación) el intento de un usuario de cambiar su propio `rol` o `estado` a través de este panel; `inactivar()` rechaza el intento de autodesactivación con un mensaje flash. `reactivar()` no requirió cambios: un usuario inactivo ya es bloqueado por el middleware `EnsureUserHasRole` antes de poder llegar a cualquier ruta de este panel, por lo que el caso de autorreactivación es inalcanzable en la práctica.

Esta es una salvaguarda técnica añadida por esta revisión, no una regla de negocio derivada del Modelo de Dominio v2 o del Plan de Implementación v2 — se documenta así explícitamente en el código (comentario en ambos archivos) para no confundirla con una RN/DA existente.

## Verificación realizada

Este sandbox no tiene PHP nativo instalado (`ADR-002`, hallazgo 1). Con autorización explícita para instalar herramientas de verificación, se instaló `@php-wasm/cli` vía npm (PHP 8.5.5 real, compilado a WebAssembly — el mismo mecanismo que `ADR-002` ya había validado como viable en su actualización del 2026-07-08) y se corrió `php -l` sobre los dos archivos modificados. Resultado: sin errores de sintaxis en ninguno de los dos.

Esto **no reemplaza** la ejecución de la suite de tests (`php artisan test`) contra un entorno real con Composer y Laravel instalados — eso sigue siendo, como ya establecía `ADR-002`, el primer checkpoint de calidad real pendiente del Módulo 1. Como verificación adicional de bajo costo, se revisaron manualmente los tests existentes contra los dos cambios:

- `AuditLogTest.php` — ninguna de sus 3 aserciones depende de que `valor_anterior`/`valor_nuevo` contengan campos declarados en `$hidden`; el cambio 1 no debería alterar su resultado.
- `RoleAuthorizationTest.php` — sus 5 casos no ejercitan auto-edición ni autoinactivación; el cambio 2 no los afecta.
- `UserManagementTest.php` — `test_inactivar_un_usuario_no_lo_elimina` inactiva un usuario distinto del administrador actuante; el cambio 2 no lo afecta.

Esta es una verificación estática (lectura de código), no una ejecución real. Debe confirmarse formalmente corriendo la suite completa en el bootstrap.

## Hallazgos documentados

### a. `sistema-gestion-bibliotecaria/.git` — intento de reparación realizado, sin éxito, con efecto secundario

Se autorizó explícitamente intentar reparar este repositorio en la misma sesión donde se detectó, bajo la regla de no destruir nada. Diagnóstico y resultado:

- El `.git` de `sistema-gestion-bibliotecaria/` no tiene directorio `objects/` (ausente, no vacío) y su `config` resultó ilegible (`fatal: unknown error occurred while reading the configuration files` al intentar `git init` sobre él). Sin `objects/` un repositorio git no puede almacenar ningún commit; es, en la práctica, un repositorio inexistente aunque la carpeta `.git` esté presente.
- Por eso, comandos de lectura (`git log`, `git status`) ejecutados desde dentro de `sistema-gestion-bibliotecaria/` no operan sobre este repositorio: git lo descarta por inválido y sube un nivel, operando en su lugar sobre el repositorio de la carpeta raíz `proximamente/` (remoto `github.com/YagoT1/nexora`). Esto explica una inconsistencia detectada: `HEAD` de `sistema-gestion-bibliotecaria/.git` apunta a `refs/heads/master`, pero `git status` ejecutado "desde ahí" mostraba `On branch main` — la rama del repositorio raíz, no la de este `.git`.
- **Se intentó reparar con `git init` dentro de `sistema-gestion-bibliotecaria/`** (operación aditiva/idempotente por diseño de git, no destructiva: no borra refs ni config existentes, solo completa lo faltante). El intento **falló** con `fatal: unknown error occurred while reading the configuration files` y no llegó a crear `objects/`.
- **Efecto secundario detectado:** ese intento fallido dejó un `index.lock` (0 bytes) en el repositorio raíz `proximamente/.git`, que git no pudo eliminar por sí mismo al finalizar (`warning: unable to unlink '.../proximamente/.git/index.lock': Operation not permitted`). Confirmado con lectura posterior: el archivo persiste. El repositorio raíz sigue funcionando para operaciones de lectura (`git log` verificado funcional después del incidente), pero cualquier operación de escritura (`git add`, `git commit`) fallaría hasta que ese lock se elimine.
- Esto **reproduce en vivo, con más precisión, el hallazgo de `ADR-002` §5**: no es solo que la inicialización de git en esta carpeta haya fallado en el pasado — es que el propio mount de la carpeta de workspace conectada impide a git completar su ciclo normal de creación/borrado de locks (`EPERM` al hacer `unlink`), tanto para el repositorio roto como, ahora confirmado, para el repositorio raíz sano. **Conclusión: ninguna operación de escritura de git (`init`, `add`, `commit`) es segura de intentar en esta carpeta conectada desde este sandbox** — no es un problema del repositorio de `sistema-gestion-bibliotecaria/` en particular, es una limitación de la combinación sandbox+mount frente a cualquier repositorio git.
- Por la regla explícita de no destruir nada, **no se intentó eliminar** el `index.lock` nuevo del repositorio raíz ni el `.git` roto de `sistema-gestion-bibliotecaria/` — habiendo ya reproducido que las operaciones de borrado/creación de archivos internos de git no se completan de forma confiable en este entorno, forzar una eliminación manual desde acá tiene el mismo riesgo, no menos.

**Queda pendiente, a resolver en la máquina real del desarrollador (no en este sandbox):**
1. Verificar y, si sigue presente, eliminar `proximamente/.git/index.lock` (archivo de 0 bytes, seguro de borrar una vez confirmado que ningún proceso de git está en curso) antes de cualquier commit en el repositorio raíz.
2. Eliminar por completo `sistema-gestion-bibliotecaria/.git` (no es reparable in place — le falta `objects/` y su `config` es ilegible) y ejecutar `git init` limpio, conforme al paso 7 de `docs/BOOTSTRAP.md` y a la decisión de `ADR-001` de mantenerlo como repositorio independiente.

### b. Inconsistencia de conteo de migraciones — corregido

`phase-summary.md` afirmaba "29 migraciones" (no el `README.md`, que no menciona un número — corrijo aquí una imprecisión de mi propio resumen anterior en esta conversación). El conteo real en `database/migrations/` es 30: 29 de creación de entidades del dominio más `2024_01_02_000010_add_rol_and_estado_to_users_table.php`, una migración de alteración sobre la tabla `users` generada por Breeze. Corregido directamente en `phase-summary.md` — edición de contenido, no destructiva.

### c. Ausencia de `.gitignore` — corregido

Se creó `sistema-gestion-bibliotecaria/.gitignore` con las exclusiones estándar de Laravel 11 (`vendor/`, `node_modules/`, `.env` real, cachés de `storage/`/`bootstrap/cache`) más entradas de editor/SO. Archivo nuevo, no reemplaza ni borra nada existente.

## Decisión

Se aplican las correcciones 1 y 2 (código) y b y c (documentación/config) directamente. El hallazgo (a) se intentó resolver activamente, no se logró, y se detiene ahí: cualquier intento adicional de reparación de git desde este sandbox implica el mismo riesgo ya demostrado de dejar el repositorio raíz en peor estado del que tenía antes de esta sesión. Se prioriza la regla explícita de no destruir nada por sobre completar la reparación a toda costa.

## Consecuencias

- El Módulo 1 continúa en estado "código listo para verificación", con dos correcciones de seguridad/robustez en el código, más las correcciones de documentación y `.gitignore` de esta sesión.
- El primer checkpoint de calidad real (`composer install && php artisan migrate && php artisan test`, `ADR-002`) sigue pendiente.
- **Nuevo pendiente crítico, previo a cualquier commit:** verificar `proximamente/.git/index.lock` en la máquina real antes de usar git ahí, y reconstruir `sistema-gestion-bibliotecaria/.git` desde cero (no reparable). Ninguna operación de escritura de git debe intentarse de nuevo desde una sesión de Cowork sobre esta carpeta conectada hasta que se entienda mejor la causa del `EPERM` en `unlink`.
