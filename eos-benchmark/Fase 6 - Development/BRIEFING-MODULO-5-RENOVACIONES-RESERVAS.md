# Briefing técnico — Módulo 5: Renovaciones y reservas

**Fecha:** 2026-07-15
**Elaborado por:** Equipo de desarrollo, a partir exclusivamente de documentación EOS oficial aprobada.
**Estado:** Para revisión — ver "Recomendación" al final. No se ha implementado ninguna funcionalidad de este módulo todavía.

---

## Fuentes utilizadas (documentación EOS oficial, v2 aprobada)

Mismo criterio de proporcionalidad que los briefings de Módulos 2, 3 y 4: se reconstruye únicamente el contexto necesario para este módulo.

- `Fase 2 - Domain Modeling/entregables/modelo-de-dominio-v2.md` — reglas de negocio RN-03, RN-05, RN-19, RN-20, RN-21; entidad 3.2 (Renovación), 3.3 (Reserva); parámetros de configuración (Ventana de retiro de reserva, Días de atención al público); registro de cambios C-05/C-06/CL-01/CL-04 (contexto de por qué estas reglas quedaron redactadas así).
- `Fase 3 - Architecture/entregables/plan-implementacion-fase1-v2.md` — sección "Módulo 5 — Renovaciones y reservas" (alcance, reglas cubiertas, criterios de aceptación literales), DA-08.
- `Fase 3 - Architecture/entregables/propuesta-arquitectura-v2.md` — DA-08 (secuencia de construcción), DA-02, DA-03, DA-06.
- Código ya escrito en `sistema-gestion-bibliotecaria/` (Módulos 1-4), verificado por lectura directa: modelos `Reserva`, `Renovacion`, `PrestamoDomiciliario`, `Libro`, `Ejemplar`, `ParametroConfiguracion`; migración de `reservas` (incluye el comentario con los 5 estados válidos); `ParametroConfiguracionSeeder` (valores ya sembrados de `ventana_retiro_reserva_horas_atencion` y `dias_atencion_al_publico`); `EjemplarController::update()` (lógica de RN-21 ya escrita en Módulo 2).

No se releyó el Relevamiento (Fase 1) ni las Fases 4/5 completas: no aportan reglas adicionales sobre renovaciones o reservas más allá de lo ya incorporado al Modelo de Dominio y al Plan de Implementación v2.

---

## 1. Objetivo funcional del módulo

Permitir dos operaciones sobre el ciclo de circulación que hoy no existen: renovar un préstamo activo (extendiendo su plazo, salvo que haya demanda en espera sobre el mismo título) y gestionar la cola de reservas de un Libro (alta de reserva, asignación automática del siguiente socio en la cola cuando un ejemplar queda libre, y visibilidad de esa asignación en el mostrador).

## 2. Alcance

**Incluido:**
- Renovación de un préstamo domiciliario activo: bloqueada si el Libro tiene reservas en estado `pendiente` o `personal_alertado` (RN-03); si no las tiene, actualiza `fecha_vencimiento` y crea un registro de `Renovacion` con la fecha anterior (RN-19). Sin límite de renovaciones consecutivas.
- Alta de reserva sobre un Libro (no sobre un Ejemplar puntual): verificación de que el socio no tiene ya una reserva activa sobre el mismo título.
- Asignación automática de la reserva más antigua cuando un ejemplar del título queda disponible sin movimiento activo: transición a `personal_alertado`, registro de `fecha_alerta_al_personal`, cálculo de `fecha_limite_retiro` (RN-05).
- Punto de disparo de esa asignación automática: la devolución de un préstamo (`PrestamoController::devolver()`, Módulo 4) ya marca la reserva más antigua como `personal_alertado` — este módulo generaliza y centraliza esa lógica de asignación (ver riesgo R-1) para que también dispare desde cualquier otro evento que libere un ejemplar reservado (alta de un ejemplar nuevo del mismo título, por ejemplo).
- Visualización de reservas en estado `personal_alertado` en el panel de mostrador (socio, título, fecha límite de retiro).
- Refactor menor de `EjemplarController::update()` (RN-21, Módulo 2): reemplazar el literal `'pendiente'` por la constante `Reserva::ESTADO_PENDIENTE` que este módulo introduce, sin cambiar su comportamiento.

**Explícitamente fuera de este módulo** (per DA-08):
- Expiración automática de la ventana de retiro (marcar una reserva `personal_alertado` como `vencida_por_no_retiro` cuando se cumple la `fecha_limite_retiro`, y procesar la siguiente reserva de la cola). El propio Plan de Implementación v2 lo asigna explícitamente al Módulo 7 ("Tareas programadas"): *"La expiración de la ventana de retiro la gestiona la tarea programada (Módulo 7), no el flujo de usuario."* Este módulo solo calcula y muestra `fecha_limite_retiro`; no la vence. Ver riesgo R-2.
- Retiro efectivo de una reserva por parte del socio (transición a `retirada`) como parte del flujo de préstamo: no está en los criterios de aceptación de este módulo ni en su descripción funcional. El estado `retirada` ya existe en la migración (comentario de los 5 estados válidos) pero su transición queda fuera de alcance explícito — ver riesgo R-3.
- Cancelación manual de una reserva por parte del personal (estado `cancelada`): mismo caso que el punto anterior — el estado existe en el esquema, pero ningún criterio de aceptación de este módulo exige una pantalla para producirlo. RN-21 (que sí es de este módulo, heredado de Módulo 2) solo exige **alertar**, no cancelar automáticamente.
- Gestión de `ExcepcionAutorizada` y `RestriccionSocio` (Módulo 6).
- Tareas programadas de cualquier tipo (Módulo 7).

## 3. Reglas de negocio aplicables

| Código | Regla | Aplicación en este módulo |
|---|---|---|
| RN-03 | Una renovación solo es posible si el Libro no tiene Reservas en estado Pendiente o Personal alertado. | Verificación previa a la renovación: `Libro::reservas()->whereIn('estado', [Reserva::ESTADO_PENDIENTE, Reserva::ESTADO_PERSONAL_ALERTADO])->exists()`. Si existe alguna, se rechaza con el mensaje literal exigido por el criterio de aceptación, indicando el socio de la reserva más antigua. |
| RN-05 | Cuando un ejemplar reservado queda disponible, alerta interna al personal; ventana de 48 horas **de atención al público** desde la alerta; al vencerse, el ejemplar pasa al siguiente en la cola o vuelve a Disponible. | Este módulo implementa la asignación y el cálculo de `fecha_limite_retiro` (ver Decisión D-13 más abajo, sección 7). El vencimiento de la ventana en sí es Módulo 7 (ver Alcance y riesgo R-2). |
| RN-19 | Al renovar, `Prestamo.fecha_vencimiento` se actualiza; `Renovacion` preserva la fecha anterior; el préstamo permanece `activo` sin importar el número de renovaciones. | `nueva_fecha_vencimiento = fecha_vencimiento_anterior recalculada con ParametroConfiguracion::PLAZO_PRESTAMO_DIAS` desde la fecha de renovación (mismo parámetro que Módulo 4 ya consume, sin introducir uno nuevo). Se crea un registro de `Renovacion` por cada renovación (histórico completo, no solo la última). |
| RN-20 | El sistema no envía mensajes automáticos a socios; toda alerta es interna, visible para el personal. | Ya respetado por diseño: no existe (ni se agrega) ningún mecanismo de email/SMS. La alerta de reserva asignada es exclusivamente una fila visible en el panel de mostrador — mismo patrón que la alerta de reserva de Módulo 4 (mensaje flash de sesión). |
| RN-21 | Si un cambio de modalidad deja reservas pendientes sin ejemplar que las satisfaga, alertar al personal para gestión manual. | Ya implementado en `EjemplarController::update()` desde Módulo 2 (Paso 7). Este módulo solo reemplaza el literal `'pendiente'` por la constante que introduce (`Reserva::ESTADO_PENDIENTE`), sin tocar la lógica de negocio en sí — refactor de consistencia, no una reimplementación. |

## 4. Entidades del dominio involucradas

Todas ya tienen migración y modelo Eloquent desde Módulo 1 (verificado por lectura directa):

| Entidad | Migración | Modelo | Qué falta para este módulo |
|---|---|---|---|
| Reserva | ✔ (5 estados documentados en comentario de migración: `pendiente`, `personal_alertado`, `retirada`, `vencida_por_no_retiro`, `cancelada`) | ✔ (`libro()`, `socio()`, `ejemplarAsignado()`) | Sin constantes de estado (mismo patrón que `PrestamoDomiciliario::ESTADO_*` de Módulo 4) — se agregan por consistencia, sin cambio de esquema. |
| Renovacion | ✔ | ✔ (`prestamo()`) | Nada — se usa tal cual. |
| PrestamoDomiciliario | ✔ | ✔ (`renovaciones()` ya declarada) | Nada — se reutiliza. |
| Libro | ✔ | ✔ (`reservas()` ya declarada desde Módulo 2) | Nada — se reutiliza. |
| Ejemplar | ✔ | ✔ | Nada — se reutiliza `tieneMovimientoActivo()` para decidir si un ejemplar del título está realmente libre antes de asignarlo a una reserva. |
| ParametroConfiguracion | ✔ | ✔ | Nada — se leen por primera vez `VENTANA_RETIRO_RESERVA_HORAS` (sembrado `'48'`) y `DIAS_ATENCION_AL_PUBLICO` (sembrado `'lunes,martes,miercoles,jueves,viernes'`), ambos sin ningún consumidor hasta ahora (mismo patrón que Módulo 4 con `PLAZO_PRESTAMO_DIAS`/`TOPE_MAXIMO_RESTRICCION_DIAS`). |

**Conclusión de esta verificación:** igual que en los tres módulos anteriores, el modelo de datos completo ya existe. Lo que falta es exclusivamente la capa de controlador, rutas, FormRequests, vistas, un método de asignación de cola y tests — confirmado por búsqueda directa: cero controladores o rutas de renovaciones/reservas existen hoy.

## 5. Casos de uso comprendidos

- **CU-1:** Renovar un préstamo activo (RN-03, RN-19), desde la vista de préstamo o la ficha del socio.
- **CU-2:** Crear una reserva sobre un Libro (verificando que el socio no tenga ya una reserva activa sobre el mismo título).
- **CU-3:** Asignación automática de la reserva más antigua cuando un ejemplar del título queda libre (RN-05), disparada desde la devolución (Módulo 4) y generalizada como método reutilizable.
- **CU-4:** Visualización de reservas `personal_alertado` en el panel de mostrador, con fecha límite de retiro.

## 6. Dependencias con otros módulos

- **Módulo 4 (Préstamos y devoluciones):** precondición dura y explícita del Plan de Implementación v2 ("Precondición: Módulo 4 completo"). Cerrado con evidencia real (`21 passed`). Además, `PrestamoController::devolver()` ya contiene la primera mitad de la lógica de asignación de reserva (marca la más antigua como `personal_alertado` sin calcular `fecha_limite_retiro` ni reutilizar un método centralizado) — este módulo debe **extraer y completar** esa lógica en un método de `Libro` o `Reserva` reutilizable, y hacer que `PrestamoController::devolver()` lo invoque en lugar de su transición inline actual. Es un refactor necesario, no una reescritura: el comportamiento observable desde los tests de Módulo 4 (`DevolucionTest::test_la_devolucion_de_un_libro_con_reserva_pendiente_marca_la_reserva_como_personal_alertado`) debe seguir pasando sin modificación.
- **Módulo 6 (Excepciones y restricciones):** sin dependencia — este módulo no toca `ExcepcionAutorizada` ni `RestriccionSocio`.
- **Módulo 7 (Tareas programadas):** dependencia de escritura futura, no de este módulo hacia Módulo 7 sino al revés — Módulo 7 va a necesitar recorrer reservas `personal_alertado` vencidas y reutilizar el mismo método de asignación de cola que este módulo construye, para procesar "la siguiente reserva" tras expirar una ventana (texto literal del Plan: *"con la misma lógica de asignación del Módulo 5"*). Se documenta como restricción de diseño: el método de asignación debe quedar en un lugar reutilizable (modelo, no controlador) para que Módulo 7 pueda invocarlo sin duplicar lógica.

## 7. Decisiones de arquitectura que condicionan la implementación

- **DA-02/DA-03:** sin cambios — mismo stack ya validado cuatro veces en verde.
- **DA-08:** orden #5, después de Préstamos y devoluciones (#4, cerrado), antes de Excepciones y restricciones (#6).
- **Decisión D-13 (nueva, de este briefing) — cálculo de `fecha_limite_retiro` cuando "horas de atención al público" no especifica horario, solo días.** El Modelo de Dominio v2 define el parámetro "Días de atención al público" únicamente como una lista de días de la semana (`lunes,martes,miercoles,jueves,viernes`), sin un horario de apertura/cierre asociado (no existe ningún parámetro de "hora de apertura" u "hora de cierre" en todo el catálogo de `ParametroConfiguracion`). RN-05 exige que la ventana de 48 horas cuente solo "horas de atención al público", no horas de reloj corridas. Sin un horario dentro del día, la única interpretación consistente con los datos disponibles es tratar cada día de atención como un bloque continuo de 24 horas que sí cuenta para la ventana, y cada día que no es de atención como un bloque que no cuenta en absoluto (se salta por completo, preservando la hora del día al reanudar). Se descarta agregar un parámetro de horario de apertura/cierre no solicitado por ningún documento del proyecto — sería inventar un requisito (violación directa de la disciplina del proyecto) y sobreingeniería frente a lo que los criterios de aceptación piden verificar (que la fecha límite se muestre correctamente, no que refleje un horario comercial específico). Algoritmo resultante, determinístico y testeable: partiendo de `fecha_alerta_al_personal`, avanzar día por día; si el día es de atención, consume horas de la ventana restante (hasta el fin del día o hasta agotar la ventana, lo que ocurra primero); si no es de atención, se salta al mismo horario del día siguiente sin consumir horas de la ventana. Se implementa como `Reserva::calcularFechaLimiteRetiro()` (o método estático equivalente), aislado y unitariamente testeable sin depender de la base de datos.
- **DA-06:** no documenta explícitamente que este módulo debe refactorizar código de Módulo 2 (RN-21) y Módulo 4 (asignación de reserva) — mismo tipo de omisión ya señalada y aceptada en los briefings anteriores; se resuelve dentro de este mismo documento (sección 2 y 6).

## 8. Riesgos técnicos identificados

**R-1 — La lógica de asignación de reserva ya existe, parcial y duplicada en potencia, dentro de `PrestamoController::devolver()` (Módulo 4).**
Módulo 4 implementó la mitad de RN-05 (marcar la reserva más antigua como `personal_alertado`) porque era un criterio de aceptación explícito de ese módulo, sin calcular `fecha_limite_retiro` (que no era su responsabilidad) ni extraerlo a un método reutilizable (no hacía falta todavía). Este módulo debe centralizar esa lógica en el modelo (`Libro` o `Reserva`) y hacer que `PrestamoController::devolver()` la invoque, en vez de mantener dos implementaciones del mismo criterio. Riesgo controlado: existe un test de regresión ya escrito (Módulo 4) que verifica el comportamiento observable actual; el refactor debe mantenerlo en verde sin modificarlo.

**R-2 — Este módulo calcula `fecha_limite_retiro` pero no la hace cumplir (no vence la reserva automáticamente).**
Es una limitación de alcance conocida y exigida por el propio Plan de Implementación v2, no un defecto: *"La expiración de la ventana de retiro la gestiona la tarea programada (Módulo 7), no el flujo de usuario."* Hasta que exista Módulo 7, una reserva `personal_alertado` cuya `fecha_limite_retiro` ya pasó seguirá apareciendo como tal en el panel de mostrador (con la fecha vencida visible, lo cual es información útil para el personal aunque el sistema no actúe todavía). Documentado para que Módulo 7 sepa exactamente qué transición le corresponde completar.

**R-3 — Los estados `retirada` y `cancelada` de `Reserva` no tienen ninguna transición implementada por este módulo.**
Ambos existen en el esquema desde Módulo 1 (comentario de la migración), pero ningún criterio de aceptación de Módulo 5 exige una pantalla para producirlos: el criterio de aceptación de RN-21 exige solo **alertar** al personal para que "cancele y gestione esas reservas manualmente" — no especifica una interfaz de cancelación concreta, y el Plan de Implementación v2 no la incluye en los criterios de aceptación de este módulo. Se decide no construir una pantalla de cancelación/retiro manual no solicitada (evitar inventar alcance). Si en la revisión funcional se determina que es una omisión real del Plan (no de este briefing), corresponde una decisión de producto, no una implementación unilateral — se deja como pregunta abierta para la Comisión Directiva, no como bloqueante: los 6 criterios de aceptación literales de este módulo no dependen de esas dos transiciones.

## 9. Criterios de aceptación

(Transcritos literalmente del Plan de Implementación v2, sección Módulo 5 — Renovaciones y reservas, sin modificación.)

- La renovación de un préstamo con reservas pendientes es rechazada con el mensaje "El libro tiene una reserva pendiente de [nombre del socio]."
- La renovación de un préstamo sin reservas pendientes actualiza la fecha de vencimiento y crea el registro de Renovación con la fecha anterior.
- Cuando el ejemplar de un Libro reservado es devuelto, la reserva más antigua del Libro pasa a "Personal alertado" y aparece en el panel del mostrador.
- El panel muestra correctamente la fecha límite de retiro del ejemplar apartado.
- Un socio no puede tener dos reservas activas para el mismo Libro.
- Al cambiar la modalidad del único ejemplar disponible de un Libro con reservas pendientes a Solo sala, el sistema muestra una alerta al personal.

## 10. Plan de implementación recomendado

Orden interno sugerido:

1. **Ajustes de base a modelos existentes** — constantes de estado en `Reserva` (`ESTADO_PENDIENTE`, `ESTADO_PERSONAL_ALERTADO`, `ESTADO_RETIRADA`, `ESTADO_VENCIDA_POR_NO_RETIRO`, `ESTADO_CANCELADA`, sin cambio de esquema); `Reserva::calcularFechaLimiteRetiro()` (algoritmo de la Decisión D-13, con tests unitarios propios que no dependan de la base de datos); refactor del literal `'pendiente'` en `EjemplarController::update()` (RN-21) a la nueva constante.
2. **Centralización de la asignación de cola** — método reutilizable (p. ej. `Libro::asignarSiguienteReserva(Ejemplar $ejemplar)`) que encapsule: buscar la reserva `pendiente` más antigua del Libro, verificar que el ejemplar no tenga movimiento activo, transicionarla a `personal_alertado` con `fecha_alerta_al_personal = now()` y `fecha_limite_retiro` calculada. Refactorizar `PrestamoController::devolver()` (Módulo 4) para invocar este método en lugar de su lógica inline, verificando que el test de regresión existente siga en verde.
3. **Renovación** — controlador con `create()`/`store()` (o acción directa desde la vista de préstamo/socio) aplicando RN-03 (bloqueo con mensaje que incluye el nombre del socio de la reserva pendiente más antigua) y RN-19 (actualización de vencimiento + registro de `Renovacion`).
4. **Alta de reserva** — formulario de creación de reserva sobre un Libro, verificando que el socio no tenga ya una reserva activa (`pendiente` o `personal_alertado`) sobre el mismo título.
5. **Puntos de entrada en la UI ya existente** — botón "Renovar" en la vista de préstamo activo y en la ficha del socio (Módulo 3/4); botón "Reservar" en la vista de detalle de Libro (Módulo 2) cuando no hay ejemplares disponibles; sección de reservas `personal_alertado` en el panel de mostrador de Socio (o una vista de mostrador general, si corresponde — a confirmar en el propio paso, sin necesidad de una decisión previa de producto).
6. **Tests** — cobertura de los 6 criterios de aceptación explícitos, más pruebas unitarias del algoritmo de `calcularFechaLimiteRetiro()` (casos: alerta en día de atención con margen suficiente en el mismo día, alerta que cruza un fin de semana, alerta el último día de atención de la semana).
7. **Datos de demostración y guía de revisión funcional**, mismo patrón que Módulos 2, 3 y 4.

En todos los pasos, reutilizar sin modificar los métodos de dominio ya escritos (`Libro::reservas()`, `Ejemplar::tieneMovimientoActivo()`, `ParametroConfiguracion::obtener()`) y el patrón de acceso por rol ya establecido (`role:administrador,personal`).

---

## Recomendación

**El equipo considera que existe información suficiente para iniciar el desarrollo completo del Módulo 5 — Renovaciones y reservas, sin ninguna decisión de producto o dominio pendiente que lo bloquee.**

De los tres riesgos identificados, R-1 y R-2 son de naturaleza técnica y de alcance (resueltos o documentados como no bloqueantes dentro de este mismo briefing, igual que los riesgos de Módulos 3 y 4). R-3 es el único con una componente de posible ambigüedad de producto (¿hace falta una pantalla de cancelación/retiro manual de reserva?), pero no bloquea el inicio: los 6 criterios de aceptación literales del Plan de Implementación v2 no dependen de esa funcionalidad, y se deja registrada la pregunta para que la Comisión Directiva la resuelva si lo considera necesario, sin detener el desarrollo del resto del módulo. Se recomienda proceder con los 7 pasos del plan de implementación en el orden indicado.
