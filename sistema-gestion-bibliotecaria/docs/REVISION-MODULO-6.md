# Guía de revisión funcional — Módulo 6 (Excepciones y restricciones)

Este documento prepara la revisión funcional del Módulo 6 — Excepciones y restricciones (CRUD de
`ExcepcionAutorizada` restringido a Administrador, alta y listado de `RestriccionSocio` manual, y
migración de los casos históricos del relevamiento). No reemplaza `docs/BOOTSTRAP.md`: asume el
mismo entorno usado para cerrar los Módulos 1 a 5 (`18 passed (38 assertions)`, ver
`docs/REVISION-MODULO-5.md`).

**Estado de este módulo: código completo, no ejecutado.** Este sandbox no tiene un entorno PHP real
(ver ADR-002) — no hay evidencia objetiva de tests hasta que el equipo los corra y reporte el
resultado real, siguiendo el mismo procedimiento que en los Módulos 2 a 5.

---

## 1. Actualizar el entorno con lo último

```bash
git pull origin main
composer install                            # por si cambiaron dependencias (no debería)
php artisan migrate                         # no hay migraciones nuevas en este módulo
php artisan db:seed --class=ExcepcionesHistoricasSeeder
```

`ExcepcionesHistoricasSeeder` es, a diferencia de los `*DemoSeeder` de módulos anteriores, **seguro
para correr en producción** (idempotente vía `firstOrCreate`, sin guarda de entorno) — migra el
caso histórico real del relevamiento (7.2: excepción individual de penalización), no datos
ficticios. Requiere que ya exista al menos un Administrador activo (`AdminUserSeeder` en
desarrollo, o dado de alta manualmente en producción); si no hay ninguno todavía, no crea nada y
puede volver a correrse más adelante sin problema.

> **Antes de un despliegue real:** este seeder representa al socio del caso histórico ("S-0072" en
> el relevamiento en papel) con un `Socio` placeholder (DNI `HIST-S-0072`), porque el dominio no
> tiene ningún campo de "código de socio" (Riesgo R-2 del briefing). La Comisión Directiva o el
> Administrador debe identificar al socio real correspondiente y, o bien ajustar el valor de
> `$dniPlaceholder` en `database/seeders/ExcepcionesHistoricasSeeder.php` antes de correrlo contra
> datos reales, o bien cargar la excepción manualmente desde `excepciones.create` sobre el socio
> real y no ejecutar este seeder. Ver la sección 5 más abajo.

```bash
php artisan migrate:fresh --seed            # recrea todo, incluidos los 5 módulos de datos de demo
php artisan serve
```

Primer chequeo objetivo antes de cualquier revisión visual — correr la suite automática (evitar
repetir la opción `--filter`, ver la lección de `docs/REVISION-MODULO-5.md`; se listan las rutas
directamente):

```bash
php artisan test tests/Feature/Excepciones tests/Feature/Restricciones tests/Unit/ExcepcionAutorizadaEstadoVisibleTest.php
```

Cubre los 6 criterios de aceptación del módulo (más el control de acceso de RN-10/R-4) en 5
archivos nuevos: `tests/Feature/Excepciones/AccesoExcepcionesTest.php` (6 tests, control de acceso
RN-10), `tests/Feature/Excepciones/ExcepcionAutorizadaTest.php` (6 tests, criterios 2/3/5/6),
`tests/Feature/Restricciones/AccesoRestriccionesTest.php` (5 tests, control de acceso CU-3/R-4),
`tests/Feature/Restricciones/RestriccionSocioTest.php` (4 tests, alta manual y listado) y
`tests/Unit/ExcepcionAutorizadaEstadoVisibleTest.php` (5 tests, unitario puro sobre
`estadoVisible()`, Decisión D-15) — **26 tests nuevos en total.** El criterio 4 (socio con
excepción de exención vigente puede recibir préstamo con restricción activa) ya estaba cubierto
desde el Módulo 4 por `RegistroPrestamoTest` y no se duplicó (Decisión D-18 no cambió ningún
comportamiento observable de ese test, solo centralizó la consulta).

**Pendiente de ejecución real:** correr el comando anterior y reportar el resultado exacto (cantidad
de tests/asserts en verde, o el detalle de cualquier falla) antes de declarar este módulo cerrado —
mismo procedimiento que los Módulos 2 a 5.

---

## 2. Usuarios de prueba (ya existentes desde el Módulo 1)

| Rol | Email | Contraseña | Acceso esperado a Excepciones | Acceso esperado a Restricciones |
|---|---|---|---|---|
| Administrador | `admin@biblioteca.test` | `password` | Completo (RN-10) | Completo |
| Personal | `personal@biblioteca.test` | `password` | **Bloqueado** (403) | Completo (CU-3) |
| Voluntario | `voluntario@biblioteca.test` | `password` | **Bloqueado** (403) | **Bloqueado** (403) |

---

## 3. Datos de ejemplo

`ExcepcionesHistoricasSeeder` deja cargada, tras correrlo, una `ExcepcionAutorizada` de tipo
"Exención de restricción por atraso" sobre un socio placeholder ("Socio histórico (S-0072 — ver
docs/REVISION-MODULO-6.md)"), con motivo "Colaboración histórica con la institución" y vigencia
indefinida (`fecha_fin` null) — visible en `/excepciones` filtrando por tipo o buscando ese socio.

No se agregó ningún `*DemoSeeder` adicional para este módulo: a diferencia de Catálogo/Socios/
Préstamos/Reservas, el CRUD de Excepciones y el alta de Restricciones manuales son operaciones de
alta simple sobre datos ya existentes (cualquier Socio o Ejemplar cargado por los seeders de
módulos anteriores sirve como entidad afectada) — la revisión visual de los criterios 2, 3 y 6 se
hace creando y revocando una excepción en vivo desde la UI, no requiere datos precargados
adicionales. Los socios con restricción automática activa ya sembrados por `SociosDemoSeeder`
(Módulo 3) y `PrestamosDemoSeeder` (Módulo 4) sirven para probar en vivo el criterio 4 (ya cubierto
por test) y el flujo de alta de restricción manual (criterio CU-3) sobre cualquier otro socio.

---

## 4. Checklist de criterios de aceptación (Plan de Implementación v2, Módulo 6)

| # | Criterio (texto del plan) | ¿Revisable ahora? | Cómo revisarlo |
|---|---|---|---|
| 1 | Un usuario con rol Personal no puede acceder a la pantalla de creación de excepciones. La ruta devuelve error de autorización. | **Sí** | Iniciar sesión como `personal@biblioteca.test` y navegar a `/excepciones/nueva` (o a `/excepciones`) → debe devolver 403. Cubierto por `AccesoExcepcionesTest`. |
| 2 | Un Administrador puede crear una excepción de exención para el socio S-0072 con motivo "Colaboración histórica con la institución" y vigencia indefinida. | **Sí** | Ya cargado por `ExcepcionesHistoricasSeeder` (sección 3); alternativamente, crear una nueva desde `/excepciones/nueva` sobre cualquier socio, dejando la fecha de fin vacía. Cubierto por `ExcepcionAutorizadaTest`. |
| 3 | La excepción queda registrada con el nombre del Administrador que la creó y la fecha. | **Sí** | En el listado `/excepciones`, columna "Autorizado por" — debe mostrar el nombre del usuario que la creó y la fecha de autorización. Cubierto por `ExcepcionAutorizadaTest`. |
| 4 | Un socio con excepción de exención vigente puede recibir un préstamo aunque tenga una restricción automática activa. | **Sí** (ya cubierto desde el Módulo 4) | Ver `RegistroPrestamoTest::test_un_socio_con_restriccion_activa_y_excepcion_de_exencion_vigente_si_puede_recibir_el_prestamo` — sin cambios de comportamiento en este módulo (D-18). |
| 5 | Una excepción con fecha de fin pasada aparece con estado "Vencida" y no aplica en las validaciones de préstamo. | **Sí** | Crear una excepción con fecha de fin en el pasado (vía `/excepciones/nueva`, o directamente en base de datos para la prueba) → debe verse "Vencida" en el listado, y un socio con restricción activa y solo esa excepción vencida debe seguir bloqueado al intentar un préstamo. Cubierto por `ExcepcionAutorizadaTest` (ambas mitades del criterio) y `ExcepcionAutorizadaEstadoVisibleTest` (el cómputo de `estadoVisible()` en aislamiento). |
| 6 | El Administrador puede revocar una excepción antes de su fecha de fin. La revocación queda registrada con fecha y usuario. | **Sí** | Desde `/excepciones`, botón "Revocar" sobre cualquier excepción vigente → debe pasar a estado "Revocada" y no permitir revocarla una segunda vez. Cubierto por `ExcepcionAutorizadaTest`. |

**Resumen:** los 6 criterios son revisables con el seeder de este módulo (criterio 2) combinado con
altas/revocaciones en vivo desde la UI (criterios 1, 3, 5, 6) y la suite ya existente del Módulo 4
(criterio 4, sin duplicar).

Adicionalmente, el plan describe el alta y listado de Restricciones manuales (CU-3, sin un criterio
de aceptación numerado propio, pero sí control de acceso — RN-10 no aplica acá, es Riesgo R-4):
desde la ficha de cualquier socio, enlace "Restricciones" → formulario de alta (motivo + fecha de
fin) → el nuevo registro aparece en el historial con tipo "Manual" y el usuario que la generó.
Cubierto por `RestriccionSocioTest` y `AccesoRestriccionesTest`.

---

## 5. Riesgos y decisiones documentadas durante el desarrollo (ver `BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md`)

- **Decisión D-14:** constantes `ExcepcionAutorizada::ESTADO_VIGENTE/ESTADO_VENCIDA/ESTADO_REVOCADA`
  y la relación `revocadoPor()`, faltante pese a que la columna ya existía desde el Módulo 1.
- **Decisión D-15:** el estado "Vencida" que debe **verse** en el listado se calcula en el momento
  de la lectura (`estadoVisible()`), sin ninguna tarea programada que reescriba la columna `estado`
  — la columna real solo transiciona explícitamente a `'vigente'` (al crear) o `'revocada'` (al
  revocar), nunca a `'vencida'`. Mismo criterio arquitectónico que D-09 (`Ejemplar::estadoActual()`).
- **Decisión D-16:** constantes `RestriccionSocio::TIPO_AUTOMATICA/TIPO_MANUAL`, reemplazando el
  literal `'automatica'` que ya usaba `PrestamoController::devolver()` desde el Módulo 4.
- **Decisión D-17:** trait `Auditable` agregado a `RestriccionSocio` — hasta este módulo todas sus
  filas eran generadas por el sistema; la restricción manual introduce la primera vía de creación
  humana.
- **Decisión D-18:** `ExcepcionAutorizada::vigentePara($entidad, $tipo)` centraliza la consulta "¿
  esta entidad tiene una excepción vigente de este tipo?", antes duplicada entre
  `PrestamoController` (método privado, eliminado) y `Ejemplar::puedeSalirDeLaBiblioteca()` (consulta
  inline). Sin cambio de comportamiento observable.
- **R-1 (mitigado por D-15):** sin tarea programada, la columna `estado` nunca transiciona sola a
  `'vencida'` — documentado como decisión, no como limitación oculta.
- **R-2 (abierto, no bloqueante — ver la nota de la sección 1):** el dominio no tiene ningún campo
  de "código de socio". `ExcepcionesHistoricasSeeder` representa el caso histórico con un `Socio`
  placeholder identificable por el DNI `HIST-S-0072` y por su motivo. En un despliegue real con
  datos de producción, la Comisión Directiva/Administrador debe identificar al socio real
  correspondiente antes de correr (o volver a correr) este seeder contra esos datos.
- **R-3 (decisión de implementación):** `ExcepcionesHistoricasSeeder`, a diferencia de los
  `*DemoSeeder`, no tiene guarda de entorno de producción — el caso histórico es una decisión real
  ya tomada por la Comisión Directiva, no un dato ficticio de demostración.
- **R-4 (no es una inconsistencia):** RN-10 limita el CRUD de `ExcepcionAutorizada` a Administrador,
  pero las Restricciones manuales admiten también Personal — dos middlewares de rol distintos
  (`role:administrador` para `excepciones.*`, `role:administrador,personal` para
  `restricciones.*`) dentro del mismo módulo, mismo patrón ya usado en el Módulo 1.

---

## 6. Qué reportar

Correr el comando de la sección 1 y reportar el resultado exacto (cantidad de tests/asserts en
verde, o el detalle de cualquier falla). Si algo de la revisión visual de la sección 4 no se
comporta como se describe, es información valiosa: indicá el paso exacto, el usuario/rol usado, y
qué esperabas vs. qué obtuviste. Los defectos encontrados se documentan en `phase-summary.md` (o en
un ADR si ameritan una decisión de diseño).
