# Guía de revisión funcional — Módulo 4 (Préstamos y devoluciones), Pasos 1 a 6

Este documento prepara la revisión funcional del Módulo 4 — Préstamos y devoluciones (registro de
préstamo, devolución, restricción automática por atraso, alerta de reserva pendiente). No reemplaza
`docs/BOOTSTRAP.md`: asume el mismo entorno usado para cerrar los Módulos 1, 2 y 3 (`11 passed (25
assertions)`, ver `docs/REVISION-MODULO-3.md`).

**Estado de este módulo: código completo, no ejecutado.** Igual que los Módulos 1, 2 y 3 en su
primera entrega, este documento se redacta antes de la primera corrida real contra PHP/PostgreSQL
(esta sesión de Cowork no puede ejecutar PHP — ver `ADR-002`). No debe considerarse cerrado hasta
obtener esa evidencia.

---

## 1. Actualizar el entorno con lo último

```bash
git pull origin main
composer install                      # por si cambiaron dependencias (no debería, en este avance)
php artisan migrate                   # no hay migraciones nuevas en este módulo
php artisan db:seed --class=PrestamosDemoSeeder
```

`PrestamosDemoSeeder` es idempotente (`firstOrCreate` y verificaciones de existencia antes de crear
cada préstamo/reserva/excepción): correrlo más de una vez no duplica datos. Requiere que
`AdminUserSeeder` ya haya corrido (usa el Administrador como registrador); si partís de cero,
`migrate:fresh --seed` ya respeta ese orden.

```bash
php artisan migrate:fresh --seed      # recrea todo, incluidos usuarios y datos de demo de los 4 módulos
php artisan serve
```

Primer chequeo objetivo antes de cualquier revisión visual — correr la suite automática (Paso 5):

```bash
php artisan test --filter=Prestamos
```

Cubre los 8 criterios de aceptación del módulo en 3 archivos bajo `tests/Feature/Prestamos/`
(`AccesoPrestamosTest`, `RegistroPrestamoTest`, `DevolucionTest`) — 19 tests en total. **Todavía sin
ejecutar contra un entorno real** — el resultado (verde o no) es la evidencia que falta para cerrar
este módulo.

---

## 2. Usuarios de prueba (ya existentes desde el Módulo 1)

| Rol | Email | Contraseña | Acceso esperado a Préstamos |
|---|---|---|---|
| Administrador | `admin@biblioteca.test` | `password` | Completo |
| Personal | `personal@biblioteca.test` | `password` | Completo |
| Voluntario | `voluntario@biblioteca.test` | `password` | **Bloqueado** (403) — mismo criterio que Catálogo y Socios |

---

## 3. Datos de ejemplo cargados por `PrestamosDemoSeeder`

A diferencia de `SociosDemoSeeder` (que dejó atrasos ya *devueltos*, para mostrar el historial),
este seeder deja préstamos **activos y vencidos, sin devolver todavía**, para poder ejercitar en
vivo el flujo de devolución y ver sus efectos ocurrir en el momento de la revisión:

| Caso | Socio | Libro / Ejemplar | Qué permite probar |
|---|---|---|---|
| 1 | Carlos Gómez (Estándar) | "Compendio de historia local", préstamo vencido hace 5 días, todavía activo | Al devolverlo desde `/prestamos/devolucion`: genera `RestriccionSocio` de 5 días (RN-18) **y** dispara la alerta de reserva pendiente (ver caso 2). |
| 2 | Lucía Fernández (Estándar) | Reserva `pendiente` sobre "Compendio de historia local" | Al devolver el préstamo del caso 1, esta reserva debe pasar a `personal_alertado` y mostrar el mensaje de alerta. |
| 3 | Marta Ibarra (Honorario) | "Guía de aves autóctonas", préstamo vencido hace 4 días, todavía activo | Al devolverlo: **no** debe generarse ninguna `RestriccionSocio` (RN-07), aunque el atraso se registre en el historial. |
| 4 | Diego Paredes (Estándar) | — | Tiene una `RestriccionSocio` vigente **y** una `ExcepcionAutorizada` vigente de tipo "Exención" — permite registrarle un préstamo nuevo pese a la restricción (RN-06). |
| 5 | — | "Archivo fotográfico institucional (restringido)", modalidad Restringido a autorización | Tiene una `ExcepcionAutorizada` vigente de tipo "Autorización de salida de material restringido" para ese ejemplar puntual — permite prestarlo pese a la modalidad (RN-09). |

Los casos 4 y 5 existen porque todavía no hay ninguna interfaz para crear una `ExcepcionAutorizada`
(su gestión es Módulo 6 — ver riesgo R-2 de `BRIEFING-MODULO-4-PRESTAMOS.md`): se siembran
directamente para poder revisar esos dos caminos igual, sin esperar a que exista esa pantalla.

---

## 4. Checklist de criterios de aceptación (Plan de Implementación v2, Módulo 4)

| # | Criterio (texto del plan) | ¿Revisable ahora? | Cómo revisarlo |
|---|---|---|---|
| 1 | "El préstamo de un ejemplar con préstamo activo es rechazado por la base de datos, no solo por el código de aplicación. Si dos solicitudes simultáneas intentan prestar el mismo ejemplar, exactamente una tiene éxito." | **Sí** (el enforcement de aplicación; el índice único de la base de datos también, ver `RegistroPrestamoTest::test_la_base_de_datos_rechaza_un_segundo_prestamo_activo_para_el_mismo_ejemplar`) | Desde la UI: intentar prestar el ejemplar del caso 1 (ya tiene un préstamo activo) — el sistema debe rechazarlo con un mensaje claro, sin error técnico. La concurrencia real (dos requests simultáneas) no es reproducible manualmente desde el navegador; queda cubierta por el test que inserta el segundo préstamo directamente y confirma que la base de datos lo rechaza. |
| 2 | "Un socio con restricción activa no puede recibir un préstamo, y el sistema muestra el motivo y la fecha de fin de la restricción." | **Sí** | Ir a Socios → ver "Diego Paredes" (caso 4) → si se revoca manualmente su excepción (o se prueba con otro socio con restricción sin excepción), "Registrar préstamo" debe mostrar la restricción vigente y, al confirmar, el error debe incluir la fecha de fin. |
| 3 | "Un socio con restricción activa pero con Excepción Autorizada vigente de tipo 'Exención' puede recibir el préstamo. El registro del préstamo indica que se usó una excepción." | **Sí** | Socios → "Diego Paredes" (caso 4, ya tiene restricción + excepción vigente) → "Registrar préstamo" → completar cualquier ejemplar disponible → debe permitir el alta pese a la restricción. |
| 4 | "Un socio estándar con 3 préstamos activos recibe una alerta al intentar un cuarto. El personal puede continuar ingresando un motivo de excepción. El préstamo queda registrado con ese motivo." | **Parcial con los datos del seeder** — ningún socio sembrado tiene ya 3 préstamos activos; se puede armar el caso a mano (crear un socio Estándar y prestarle 3 ejemplares) o confiar en `RegistroPrestamoTest::test_un_socio_estandar_con_3_prestamos_activos_recibe_alerta_al_intentar_un_cuarto`. |
| 5 | "Un socio Honorario con 5 préstamos activos recibe la misma alerta de límite (su límite es 5). El sistema usa el límite del Tipo de Socio, no un valor hardcodeado." | **Parcial**, mismo motivo que el criterio 4 — cubierto por `RegistroPrestamoTest::test_un_socio_honorario_con_5_prestamos_activos_recibe_alerta_usando_el_limite_de_su_tipo`. |
| 6 | "La devolución de un préstamo vencido con 3 días de atraso genera una restricción de 3 días de duración para el socio, salvo que sea Honorario." | **Sí** | Ir a `/prestamos/devolucion`, buscar "Compendio de historia local" (caso 1, vencido hace 5 días) → confirmar devolución con fecha de hoy → debe generarse una `RestriccionSocio` de 5 días para Carlos Gómez. Para el caso Honorario: buscar "Guía de aves autóctonas" (caso 3) → confirmar devolución → no debe generarse ninguna restricción. |
| 7 | "La devolución de un libro con reserva pendiente activa la alerta de 'avisar al socio' en el panel del mostrador dentro del ciclo de la misma request." | **Sí** | Al devolver "Compendio de historia local" (caso 1), la pantalla de resultado debe mostrar el mensaje de alerta mencionando a Lucía Fernández (caso 2). |
| 8 | "La devolución puede registrarse sin identificar quién trae el libro." | **Sí** | El formulario de confirmación de devolución no tiene ningún campo de socio — visible en cualquiera de los casos anteriores. |

**Resumen:** 6 de los 8 criterios son totalmente revisables con los datos del seeder tal cual vienen
(1, 2, 3, 6, 7, 8); los criterios 4 y 5 requieren armar manualmente 3 o 5 préstamos activos para un
socio (no incluido en el seeder para no sobrecargarlo con datos redundantes), o confiar en la
cobertura de tests que sí los ejercita directamente.

---

## 5. Riesgos y decisiones documentadas durante el desarrollo (ver `BRIEFING-MODULO-4-PRESTAMOS.md`)

- **R-1:** ningún proceso marca automáticamente un préstamo vencido como "Atrasado" — esa tarea
  programada es Módulo 7. El cálculo de atraso en la devolución se hace por fecha, no por ese
  campo.
- **R-2:** no existe interfaz para crear `ExcepcionAutorizada` (Módulo 6) — los casos 4 y 5 del
  seeder la crean directamente para poder revisar esos caminos igual.
- **R-3:** los parámetros globales `limite_prestamos_estandar`/`limite_prestamos_honorario` de
  `ParametroConfiguracion` **no** se usan — la fuente de verdad del límite es siempre
  `TipoSocio::limite_prestamos_simultaneos`, tal como exige el criterio de aceptación 5.

---

## 6. Qué reportar

Si algo de lo anterior no se comporta como se describe, o `php artisan test --filter=Prestamos` no
da `19 passed` en la primera corrida real, es información valiosa: indicá el paso exacto, el
usuario/rol usado, y qué esperabas vs. qué obtuviste. Los defectos encontrados se documentan en
`phase-summary.md` (o en un ADR si ameritan una decisión de diseño) antes de declarar el módulo
cerrado.
