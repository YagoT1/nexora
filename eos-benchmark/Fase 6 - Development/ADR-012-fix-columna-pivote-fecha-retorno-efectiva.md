# ADR-012 — Corrección: nombre de columna incorrecto en las relaciones pivote de movimiento interno y custodia externa

**Estado:** Resuelta — verificada con evidencia objetiva (ver actualización final: `31 passed`).
**Fecha:** 2026-07-14
**Detectado por:** Primera ejecución real de `php artisan test --filter=Catalogo` sobre el Módulo 2, ejecutada por la Comisión Directiva en su propio entorno (el mismo que validó el Módulo 1, `ADR-006`/`ADR-007`/`ADR-008`). Es exactamente el tipo de hallazgo que `ADR-002` anticipó como riesgo aceptado de entregar código sin ejecución previa en este sandbox.

---

## Contexto

Al correr `php artisan test --filter=Catalogo` por primera vez, 1 de 27 tests falló:

```
FAILED  Tests\Feature\Catalogo\EjemplarEstadoTest > un ejemplar con estado manual en reparación...
SQLSTATE[42703]: Undefined column: 7 ERROR: column ejemplares_movimiento_interno.fecha_devolucion_efectiva does not exist
Tests: 1 failed, 26 passed (68 assertions)
```

## Diagnóstico

`Ejemplar::movimientosInternos()` y `Ejemplar::custodiasExternas()` (y sus relaciones inversas en `MovimientoInterno::ejemplares()`/`CustodiaExterna::ejemplares()`) declaraban `withPivot('fecha_devolucion_efectiva')`, y los métodos `tieneMovimientoActivo()`/`estadoActual()` consultaban esas mismas relaciones con `wherePivotNull('fecha_devolucion_efectiva')`. Ese nombre de columna es correcto **solo** para la tabla pivote `ejemplares_prestamo_institucional` (migración `2024_01_01_000150`). Las otras dos tablas pivote usan un nombre distinto:

| Tabla pivote | Columna real | Migración |
|---|---|---|
| `ejemplares_prestamo_institucional` | `fecha_devolucion_efectiva` | `2024_01_01_000150` |
| `ejemplares_movimiento_interno` | `fecha_retorno_efectiva` | `2024_01_01_000170` |
| `ejemplares_custodia_externa` | `fecha_retorno_efectiva` | `2024_01_01_000190` |

PostgreSQL valida la existencia de las columnas referenciadas en una consulta al parsearla, independientemente de si hay filas que matcheen — por lo tanto **cualquier** llamada a `tieneMovimientoActivo()` o `estadoActual()` que llegara a evaluar la rama de `movimientosInternos()`/`custodiasExternas()` fallaba siempre, no solo cuando existían movimientos reales.

**Origen del defecto — no es de Módulo 2.** Se verificó con `git show 581f6fb:./app/Models/Ejemplar.php` (commit del Módulo 1, "Paso 4 — CRUD Ejemplar") que el mismo nombre de columna incorrecto ya existía en las llamadas `belongsToMany()` anónimas originales, antes de que el Paso 5 de Módulo 2 las convirtiera en relaciones nombradas (commit `a28be1c`). El refactor del Paso 5 preservó fielmente el comportamiento existente — incluido el defecto — tal como se esperaba de un refactor que no debía alterar lógica. El error es, en definitiva, del Módulo 1: `estadoActual()`/`tieneMovimientoActivo()` nunca funcionaron correctamente para movimiento interno o custodia externa, desde que se escribieron.

**Por qué no se detectó antes.** La suite de 38 tests del Módulo 1 (`ADR-006`) está orientada a autenticación, roles y auditoría — no ejercita ningún camino de código de `Ejemplar` relacionado con movimientos. Dentro de la propia suite del Módulo 2 (Paso 8), dos factores ocultaron el defecto en 26 de los 27 tests:

1. `estadoActual()` y `tieneMovimientoActivo()` usan cadenas `if`/`||` con cortocircuito: si el ejemplar tiene `estado_manual` o un préstamo domiciliario activo, el método retorna antes de llegar a la rama rota. Los tests existentes de esos dos casos (`en_reparacion`, `prestado`) nunca alcanzaban el código defectuoso salvo en una única aserción directa a `tieneMovimientoActivo()`.
2. Ningún test de `EjemplarEstadoTest` renderizaba `catalogo.libros.show` para un ejemplar "disponible" liso (sin `estado_manual`, sin préstamo) — que es el caso por defecto y más común, y el único camino que evalúa la rama rota sin cortocircuitar antes. Tampoco ningún test de `BusquedaCatalogoTest` ejercitaba el filtro `estado=disponible`, que dispara la misma rama rota dentro de `Libro::scopeConEstado()`.

En otras palabras: el defecto no era un caso límite raro, sino que rompía silenciosamente la vista de detalle de Libro y el filtro de búsqueda por estado para el caso más común (ejemplar disponible), y la suite de tests tenía una brecha de cobertura específica que coincidía exactamente con ese caso.

## Decisión

Se corrige el nombre de columna en los cuatro puntos afectados de `app/Models/Ejemplar.php` (dos `withPivot()`, dos pares de `wherePivotNull()` en `tieneMovimientoActivo()`/`estadoActual()`), en las relaciones inversas de `app/Models/MovimientoInterno.php` y `app/Models/CustodiaExterna.php`, y en las cuatro ramas equivalentes de `Libro::scopeConEstado()` que reproducen deliberadamente esta misma lógica en SQL (advertencia de duplicación ya documentada en el propio código desde el Paso 5). La relación `prestamosInstitucionales()` y su inversa en `PrestamoInstitucional::ejemplares()` no se tocan: ya usaban el nombre correcto.

Se descarta unificar el nombre de columna entre las tres tablas pivote (por ejemplo, migrando todas a `fecha_retorno_efectiva`): sería un cambio de esquema no solicitado, fuera del alcance de esta corrección, con impacto en datos ya migrados en el entorno del usuario, y sin ninguna necesidad funcional que lo justifique — el código puede (y debe) simplemente reflejar el esquema real tal como está.

**Cierre de la brecha de cobertura.** Además de la corrección de código, se agregaron tests que específicamente cubren los caminos que la suite original no alcanzaba:

- `EjemplarEstadoTest::test_un_ejemplar_disponible_sin_movimientos_muestra_estado_disponible_en_la_vista_de_detalle` — el caso que hubiera fallado en cualquier ejemplar "disponible" real.
- `EjemplarEstadoTest::test_un_ejemplar_con_movimiento_interno_activo_tiene_movimiento_activo_y_muestra_ese_estado` — crea un `MovimientoInterno` real y vincula el ejemplar vía la relación pivote, cerrando la cobertura de RN-04 (Nivel 2, DA-09) para este tipo de movimiento.
- `EjemplarEstadoTest::test_un_ejemplar_en_custodia_externa_activa_tiene_movimiento_activo_y_muestra_ese_estado` — mismo caso para custodia externa.
- `BusquedaCatalogoTest::test_el_filtro_de_estado_disponible_excluye_libros_con_ejemplares_en_movimiento_interno` — ejercita `Libro::scopeConEstado()` con datos reales de movimiento interno.

## Verificación

**Antes de esta corrección** (evidencia real, entorno del usuario, 2026-07-14): `Tests: 1 failed, 26 passed (68 assertions)`.

**Después de esta corrección:** código corregido y cuatro tests nuevos agregados en esta sesión, pero **todavía no ejecutados contra un entorno real** — esta sesión de Cowork no puede correr PHP/Composer/PostgreSQL (`ADR-002`). Conforme a la instrucción vigente de la Comisión Directiva ("no consideren el módulo cerrado hasta obtener la evidencia objetiva correspondiente"), este ADR queda en estado "código corregido, no verificado" hasta que se corra `php artisan test --filter=Catalogo` (se esperan 31 tests en verde: los 27 originales, con el que fallaba ahora en verde, más los 4 nuevos) en el entorno real y su resultado se documente como actualización de este mismo ADR.

## Consecuencias

- El Módulo 1 tenía, desde su escritura, un defecto latente y no detectado en `Ejemplar::tieneMovimientoActivo()`/`estadoActual()` para 2 de los 4 tipos de movimiento — su suite de 38 tests (orientada a auth/roles/auditoría) nunca lo hubiera detectado porque no ejercita ese código. Se deja constancia de que "38/38 en verde" (`ADR-006`) certificaba lo que efectivamente cubría esa suite, no la ausencia total de defectos en el código del Módulo 1 — distinción ya implícita en el alcance de cada suite, pero que vale explicitar dado este hallazgo.
- Se refuerza, con un caso concreto, el criterio ya aplicado en `ADR-008`: una suite en verde certifica lo que efectivamente cubre, y una brecha de cobertura puede ocultar un defecto de producción real durante mucho tiempo. Se recomienda, al iniciar cada módulo nuevo (3 en adelante), revisar explícitamente si los métodos de dominio reutilizados de módulos anteriores (como `Ejemplar::estadoActual()`/`tieneMovimientoActivo()`, que Módulo 4/5 van a usar intensivamente) ya tienen cobertura real más allá de los casos que el módulo que los originó necesitaba probar.
- Sin cambios de esquema, sin cambios de comportamiento observable desde la UI salvo la corrección misma (los casos que antes fallaban con error 500 ahora deben funcionar).

---

## Actualización (2026-07-14) — Segunda ejecución real: un segundo defecto, distinto, en el mismo método

Tras el push del fix anterior, la Comisión Directiva volvió a correr `php artisan test --filter=Catalogo`: `1 failed, 30 passed (85 assertions)`. Los 27 tests originales (con el que fallaba antes ahora en verde) y 3 de los 4 tests nuevos pasaron. Falló exactamente el test nuevo que ejercita `Libro::scopeConEstado()` con datos reales de movimiento interno (`BusquedaCatalogoTest::test_el_filtro_de_estado_disponible_excluye_libros_con_ejemplares_en_movimiento_interno`), con un error distinto:

```
SQLSTATE[42703]: Undefined column: 7 ERROR: column "pivot_null" does not exist
LINE 1: ..."ejemplares_movimiento_interno"."ejemplar_id" and "pivot_null" = fecha_retorno_efectiva...
```

### Diagnóstico

Este no es el mismo defecto que el de la sección anterior (el nombre de columna ya estaba corregido) — es un defecto distinto, más profundo, en la misma línea de código: `wherePivotNull()` **no es un método válido dentro de un closure de `whereHas()`**. Ese closure recibe un `Illuminate\Database\Eloquent\Builder` acotado al modelo relacionado (`MovimientoInterno`/`CustodiaExterna`), no la instancia de la relación `BelongsToMany` — y `wherePivotNull()` solo existe en `BelongsToMany`, no en el Builder genérico. Al no existir el método, Eloquent lo resuelve por su parser dinámico de "where\<Columna\>" (el mismo mecanismo que permite escribir `whereNombre('valor')` en vez de `where('nombre', 'valor')`), que interpreta `PivotNull` como el nombre de columna `pivot_null` y el argumento `'fecha_retorno_efectiva'` como el valor a comparar — de ahí el SQL literal `"pivot_null" = fecha_retorno_efectiva` que no tiene sentido y falla porque esa columna no existe.

`wherePivotNull()` sí funciona correctamente cuando se llama directamente sobre la relación (`$this->movimientosInternos()->wherePivotNull(...)`, como hacen `Ejemplar::tieneMovimientoActivo()`/`estadoActual()`) — por eso los 3 tests nuevos de `EjemplarEstadoTest` (que ejercitan esos métodos directamente) pasaron correctamente en esta misma ejecución. El problema es específico a la combinación `whereHas(...) + wherePivotNull()` dentro del closure, que `Libro::scopeConEstado()` usa desde que se escribió en el Paso 5 — es decir, este es un tercer defecto preexistente desde el Paso 5, no introducido por la corrección anterior, simplemente nunca antes ejercitado por ningún test hasta el nuevo test de búsqueda por estado agregado en esta misma corrección.

### Decisión

Se corrige referenciando la columna de la tabla pivote de forma calificada (`'ejemplares_movimiento_interno.fecha_retorno_efectiva'` / `'ejemplares_custodia_externa.fecha_retorno_efectiva'`) con `whereNull()` en vez de `wherePivotNull()`. Es válido porque `whereHas()` sobre una relación `BelongsToMany` sí hace el `JOIN` con la tabla pivote para poder acotar la subconsulta — se confirma en el propio SQL del error, que ya incluye `inner join "ejemplares_movimiento_interno"` — solo que no expone un método de conveniencia para filtrar por sus columnas dentro del closure; hay que nombrarlas explícitamente. No se introduce ninguna dependencia ni abstracción nueva.

### Verificación

**Antes de esta segunda corrección:** `1 failed, 30 passed (85 assertions)` (evidencia real, entorno del usuario, 2026-07-14, segunda ejecución).

### Consecuencias

- Confirma, con un segundo caso en el mismo ADR, el patrón ya señalado: `Libro::scopeConEstado()` reproduce en SQL una lógica que en `Ejemplar` está en PHP, y esa traducción tiene su propia superficie de error independiente de que la lógica de negocio original sea correcta — no alcanza con que el método fuente (`estadoActual()`) esté bien para asumir que su traducción a SQL también lo está. Cualquier cambio futuro a `estadoActual()`/`tieneMovimientoActivo()` que introduzca patrones nuevos de pivote debe re-verificar explícitamente que su traducción en `scopeConEstado()` use APIs válidas en ese contexto (columnas calificadas, no métodos de conveniencia de relación), no solo que el nombre de columna coincida.

---

## Verificación final (2026-07-14) — Tercera ejecución real: en verde

Tras pushear la segunda corrección, la Comisión Directiva corrió `php artisan test --filter=Catalogo` por tercera vez:

```
Tests: 31 passed (87 assertions)
Duration: 8.55s
```

Los 27 tests originales del Paso 8, más los 4 tests de regresión agregados por esta corrección (2 en `EjemplarEstadoTest` que cierran la cobertura de RN-04 para movimiento interno/custodia externa, 1 en `EjemplarEstadoTest` para el caso "disponible" sin movimientos, 1 en `BusquedaCatalogoTest` para el filtro `estado=disponible`), todos en verde. Ambos defectos descritos en este ADR quedan corregidos y verificados con evidencia objetiva real — no solo código revisado estáticamente.

Con esto, el Módulo 2 — Catálogo cumple el mismo estándar de cierre que el Módulo 1 (`ADR-006`): código completo, ejecutado contra PHP/PostgreSQL reales, con su suite de tests en verde. Ver `phase-summary.md` para la declaración formal de cierre del módulo.
