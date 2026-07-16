# Guía de revisión funcional — Módulo 5 (Renovaciones y reservas)

Este documento prepara la revisión funcional del Módulo 5 — Renovaciones y reservas (renovación de
préstamo con bloqueo por reserva, alta de reserva sobre un Libro, asignación automática de la
reserva más antigua al devolverse un ejemplar, con cálculo de ventana de retiro). No reemplaza
`docs/BOOTSTRAP.md`: asume el mismo entorno usado para cerrar los Módulos 1 a 4 (`21 passed (59
assertions)`, ver `docs/REVISION-MODULO-4.md`).

**Estado de este módulo: cerrado, con evidencia objetiva (2026-07-16).** Ejecución real: `18 passed
(38 assertions)`, sin fallos — ver `phase-summary.md`.

---

## 1. Actualizar el entorno con lo último

```bash
git pull origin main
composer install                            # por si cambiaron dependencias (no debería)
php artisan migrate                         # no hay migraciones nuevas en este módulo
php artisan db:seed --class=RenovacionesReservasDemoSeeder
```

`RenovacionesReservasDemoSeeder` es idempotente (`firstOrCreate` y verificaciones de existencia
antes de crear cada préstamo/reserva): correrlo más de una vez no duplica datos. Requiere que
`AdminUserSeeder` ya haya corrido; si partís de cero, `migrate:fresh --seed` ya respeta ese orden.

```bash
php artisan migrate:fresh --seed            # recrea todo, incluidos los 5 módulos de datos de demo
php artisan serve
```

Primer chequeo objetivo antes de cualquier revisión visual — correr la suite automática:

```bash
php artisan test --filter=Renovacion
php artisan test --filter=Reserva
php artisan test --filter=AccesoPrestamos
```

Cubre los 6 criterios de aceptación del módulo en 4 archivos: `tests/Feature/Prestamos/
RenovacionTest.php` (4 tests), `tests/Feature/Prestamos/ReservaTest.php` (4 tests),
`tests/Unit/ReservaCalcularFechaLimiteRetiroTest.php` (4 tests, unitario puro sobre el algoritmo de
la Decisión D-13), y los 2 tests nuevos agregados a `tests/Feature/Prestamos/
AccesoPrestamosTest.php` (control de acceso a la nueva ruta de alta de reserva) — 14 tests nuevos
en total (más los 4 preexistentes de `AccesoPrestamosTest`, 18 en total en estos cuatro archivos).
**Ejecutado con éxito:** `AccesoPrestamosTest` → `6 passed (7 assertions)`; `RenovacionTest` +
`ReservaTest` + `ReservaCalcularFechaLimiteRetiroTest` → `12 passed (31 assertions)`. Total: `18
passed (38 assertions)`, sin fallos.

---

## 2. Usuarios de prueba (ya existentes desde el Módulo 1)

| Rol | Email | Contraseña | Acceso esperado a Renovaciones y Reservas |
|---|---|---|---|
| Administrador | `admin@biblioteca.test` | `password` | Completo |
| Personal | `personal@biblioteca.test` | `password` | Completo |
| Voluntario | `voluntario@biblioteca.test` | `password` | **Bloqueado** (403) — mismas rutas heredan el middleware de `prestamos.*`/`catalogo.*` |

---

## 3. Datos de ejemplo cargados por `RenovacionesReservasDemoSeeder`

| Caso | Socio(s) | Libro / Ejemplar | Qué permite probar |
|---|---|---|---|
| 1 | Roberto Sosa (Estándar) | "Manual de jardinería urbana", préstamo activo, vence en 5 días, sin reservas | Ir a la ficha del socio → "Renovar" → debe tener éxito y mostrar la nueva fecha de vencimiento (hoy + 15 días, RN-19). |
| 2 | Valeria Núñez (préstamo) / Emiliano Castro (reserva pendiente) | "Introducción a la astronomía" | Ir a la ficha de Valeria Núñez → "Renovar" → debe rechazarse con el mensaje "El libro tiene una reserva pendiente de Emiliano Castro." (RN-03). |
| 3 | — | "Cocina de estación: recetas de otoño", con un ejemplar, sin reservas | Ir a la ficha del libro → "Reservar" → buscar y seleccionar cualquier socio → confirmar → debe crear la reserva en estado `pendiente`. |
| 4 | Patricia Weiss (Estándar) | "Atlas histórico de la región", reserva ya en `personal_alertado`, con fecha límite de retiro ya calculada | Ir a `/prestamos/devolucion` (panel del mostrador) → debe verse en la tabla "Reservas para retirar" junto a su fecha límite. También visible en la ficha de Patricia Weiss ("Reservas activas"). |

Para ver la asignación automática **en el momento** (en vez de ya precargada, caso 4), se puede
reutilizar el caso 2 del `PrestamosDemoSeeder` (Módulo 4: préstamo de Carlos Gómez sobre "Compendio
de historia local", con la reserva pendiente de Lucía Fernández) y devolverlo desde
`/prestamos/devolucion` — la reserva de Lucía debe pasar a `personal_alertado` con su fecha límite
visible de inmediato, sin recargar la página.

---

## 4. Checklist de criterios de aceptación (Plan de Implementación v2, Módulo 5)

| # | Criterio (texto del plan) | ¿Revisable ahora? | Cómo revisarlo |
|---|---|---|---|
| 1 | La renovación de un préstamo con reservas pendientes es rechazada con el mensaje "El libro tiene una reserva pendiente de [nombre del socio]." | **Sí** | Caso 2 de la tabla anterior. |
| 2 | La renovación de un préstamo sin reservas pendientes actualiza la fecha de vencimiento y crea el registro de Renovación con la fecha anterior. | **Sí** | Caso 1 de la tabla anterior; el registro de `Renovacion` no tiene pantalla propia todavía (no exigido por el criterio), se verifica por `RenovacionTest`. |
| 3 | Sin límite de renovaciones consecutivas — la regla es la ausencia de reservas pendientes. | **Sí** | Repetir "Renovar" sobre el caso 1 varias veces seguidas; todas deben tener éxito mientras no aparezca una reserva. |
| 4 | Un socio puede reservar un Libro; un socio no puede tener dos reservas activas para el mismo Libro. | **Sí** | Caso 3 de la tabla (alta); repetir la reserva con el mismo socio sobre el mismo libro debe rechazarse. |
| 5 | Al devolverse el ejemplar de un Libro con reserva pendiente, la reserva más antigua pasa a "Personal alertado" y se calcula su fecha límite de retiro según la ventana de atención al público configurada. | **Sí** | Reutilizar el caso 2 del `PrestamosDemoSeeder` (Módulo 4) y devolverlo — ver nota al pie de la tabla de datos. |
| 6 | El panel del mostrador (pantalla de devolución) muestra las reservas asignadas con su fecha límite de retiro. | **Sí** | Caso 4 de la tabla; también visible tras ejercitar el criterio 5. |

**Resumen:** los 6 criterios son revisables con los datos del seeder de este módulo combinados con
el seeder del Módulo 4 (para el criterio 5, que necesita un préstamo activo con reserva pendiente
sobre su libro — situación ya cubierta por ese seeder, no duplicada aquí).

---

## 5. Riesgos y decisiones documentadas durante el desarrollo (ver `BRIEFING-MODULO-5-RENOVACIONES-RESERVAS.md`)

- **Decisión D-13:** no existe en el dominio ningún parámetro de horario de apertura/cierre —
  `ParametroConfiguracion::DIAS_ATENCION_AL_PUBLICO` solo define qué días de la semana son de
  atención. Se decidió tratar cada día de atención como un bloque continuo de 24 horas y saltar
  completos los días que no lo son, sin inventar un parámetro de horario no solicitado. El
  algoritmo (`Reserva::calcularFechaLimiteRetiro()`) está cubierto por 4 tests unitarios puros que
  verifican los casos de borde (margen en el mismo día, cruce de fin de semana, último día hábil de
  la semana, alerta en día no hábil).
- **R-1:** la lógica de asignación de la siguiente reserva, antes parcialmente inline en
  `PrestamoController::devolver()` (Módulo 4), se centralizó en `Libro::asignarSiguienteReserva()`
  para que el futuro Módulo 7 (tareas programadas) pueda reutilizarla sin duplicar el cálculo de la
  ventana de retiro.
- **R-2:** ninguna tarea programada vence automáticamente una reserva en `personal_alertado` cuyo
  plazo de retiro ya pasó — eso es explícitamente Módulo 7 (Tareas programadas). Este módulo solo
  calcula y muestra la fecha límite.
- **R-3:** las transiciones a los estados `retirada` y `cancelada` de `Reserva` no tienen todavía
  ninguna pantalla dedicada (no exigido por los criterios de aceptación de este módulo, que solo
  hablan de alta y asignación automática) — pregunta abierta no bloqueante, documentada en el
  briefing.

---

## 6. Qué reportar

Si algo de lo anterior no se comporta como se describe durante la revisión visual, es información
valiosa: indicá el paso exacto, el usuario/rol usado, y qué esperabas vs. qué obtuviste. Los
defectos encontrados se documentan en `phase-summary.md` (o en un ADR si ameritan una decisión de
diseño).
