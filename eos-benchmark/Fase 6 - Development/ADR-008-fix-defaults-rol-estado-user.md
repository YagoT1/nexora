# ADR-008 — Corrección: defaults de `rol`/`estado` no reflejados en memoria por Eloquent

**Estado:** Resuelta — verificada con evidencia objetiva
**Fecha:** 2026-07-12
**Detectado por:** Primera ejecución real de `php artisan test` sobre el Módulo 1 (ver `ADR-006`), tras validar el entorno completo. Es exactamente el hallazgo que `docs/BOOTSTRAP.md` §5 anticipaba: "no debe considerarse el Módulo 1 completo hasta que esta suite pase en verde" — el código nunca se había ejecutado contra PHP/Postgres reales (ver `ADR-002`).

---

## Contexto

Al correr `php artisan test` por primera vez con el entorno ya validado, 3 de 38 tests fallaron, todos con el mismo síntoma: un usuario con rol administrador y estado activo recibía `403 Forbidden` en rutas que debían permitirle acceso.

```
FAILED  Tests\Feature\RoleAuthorizationTest > un administrador si puede acceder al panel de administracion
Expected response status code [200] but received 403.

FAILED  Tests\Feature\Admin\UserManagementTest > un administrador puede crear un usuario y asignarle rol
Expected response status code [201, 301, 302, 303, 307, 308] but received 403.

FAILED  Tests\Feature\Admin\UserManagementTest > inactivar un usuario no lo elimina
Failed asserting that a row in the table [users] matches the attributes {"id": 3, "estado": "inactivo"}.
Found similar results: [{"id": 3, "estado": "activo"}]
```

## Diagnóstico

Los tres tests fallidos crean el usuario administrador así: `User::factory()->create(['rol' => User::ROL_ADMINISTRADOR])`, **sin** especificar `estado`. El único test relacionado que sí pasaba correctamente (`un_usuario_inactivo_no_puede_acceder_aunque_sea_administrador`) especifica `estado` explícitamente: `['rol' => User::ROL_ADMINISTRADOR, 'estado' => 'inactivo']`.

La migración `2024_01_02_000010_add_rol_and_estado_to_users_table.php` define `estado` con `default('activo')` **a nivel de columna de base de datos**. Ese default se aplica correctamente en la fila real insertada — pero Eloquent no sincroniza los defaults de columna de la base de datos de vuelta hacia la instancia en memoria que devuelve `create()` cuando el atributo no se pasó explícitamente en el array. Resultado: el registro en la base de datos tiene `estado = 'activo'`, pero el objeto `$admin` en memoria (el que usa `actingAs()` durante el test) tiene `estado = null`.

`EnsureUserHasRole::handle()` llama a `$usuario->estaActivo()`, que evalúa `$this->estado === 'activo'` → `null === 'activo'` → `false` → aborta con 403 antes de llegar al controller. Esto explica los tres fallos con una única causa raíz — no son tres bugs distintos.

## Decisión

Se declaran los mismos defaults también a nivel de modelo Eloquent (`protected $attributes`), no solo en la migración. Esto es una propiedad estándar de Eloquent para exactamente este propósito: los valores ahí declarados se aplican a **toda** instancia nueva del modelo (factories, `new User(...)`, seeders, código de producción) antes de cualquier asignación explícita, eliminando la divergencia entre lo que queda en memoria y lo que la base de datos aplicaría por su cuenta.

```php
// app/Models/User.php
protected $attributes = [
    'rol' => self::ROL_VOLUNTARIO,
    'estado' => 'activo',
];
```

Alternativas descartadas:

- **Parchear los tests** (agregar `'estado' => 'activo'` explícito en cada `create()`): oculta el problema en vez de corregirlo — cualquier código futuro (no solo tests) que cree un `User` sin especificar `estado` explícito seguiría teniendo el mismo bug latente.
- **Llamar `->refresh()` después de cada `create()`**: mismo problema, es un parche local en vez de una corrección en la fuente del defecto.

## Verificación

```
Antes:  Tests: 3 failed, 35 passed (92 assertions)
Después: Tests: 38 passed (94 assertions)
```

Se corrió la suite completa (no solo los 3 tests que fallaban) para confirmar que el fix no introdujo regresiones en los 35 que ya pasaban. Sin regresiones.

## Consecuencias

- `app/Models/User.php` queda como fuente única de verdad de los defaults de dominio para `rol`/`estado`, consistente con la migración.
- Ningún otro archivo requirió cambios — el defecto era puntual al modelo.
- Se recomienda aplicar el mismo patrón (`protected $attributes` reflejando cualquier `default()` de columna) en futuros modelos del proyecto que dependan de valores por defecto de base de datos y se acaten en lógica de dominio inmediatamente después de crear la instancia, para evitar la misma clase de defecto en los módulos 2-10.
