# Briefing técnico — Módulo 4: Préstamos y devoluciones

**Fecha:** 2026-07-15
**Elaborado por:** Equipo de desarrollo, a partir exclusivamente de documentación EOS oficial aprobada.
**Estado:** Para revisión — ver "Recomendación" al final. No se ha implementado ninguna funcionalidad de este módulo todavía.

---

## Fuentes utilizadas (documentación EOS oficial, v2 aprobada)

Mismo criterio de proporcionalidad que los briefings de Módulo 2 y 3: se reconstruye únicamente el contexto necesario para este módulo.

- `Fase 2 - Domain Modeling/entregables/modelo-de-dominio-v2.md` — reglas de negocio RN-01, RN-02, RN-04, RN-06, RN-08, RN-09, RN-12, RN-13, RN-18; entidades 3.1 (Préstamo domiciliario), 3.2 (Renovación, solo para no invadir su alcance), 4.1 (Excepción autorizada), 4.2 (Restricción de socio), 4.3 (Historial de atrasos); decisiones D-09 (estado derivado) e invariante de circulación (RN-04).
- `Fase 3 - Architecture/entregables/plan-implementacion-fase1-v2.md` — sección "Módulo 4 — Préstamos y devoluciones" (alcance, reglas cubiertas, criterios de aceptación literales), DA-08.
- `Fase 3 - Architecture/entregables/propuesta-arquitectura-v2.md` — DA-09 (estrategia de enforcement de RN-04 a dos niveles: índice único parcial + verificación de aplicación), DA-02, DA-03, DA-06, DA-08.
- Código ya escrito en `sistema-gestion-bibliotecaria/` (Módulos 1-3), verificado por lectura directa: modelos `PrestamoDomiciliario`, `HistorialAtraso`, `RestriccionSocio`, `ExcepcionAutorizada`, `Reserva`, `Ejemplar`, `TipoSocio`, `Socio`; migraciones de esas tablas (incluidos los índices únicos parciales de DA-09 Nivel 1); `ParametroConfiguracion` y su seeder; `SocioController::show()` (vista de mostrador, Módulo 3).

No se releyó el Relevamiento (Fase 1) ni las Fases 4/5 completas: no aportan reglas adicionales sobre préstamos más allá de lo ya incorporado al Modelo de Dominio y al Plan de Implementación v2.

---

## 1. Objetivo funcional del módulo

Registrar el ciclo completo de préstamo domiciliario y devolución desde el mostrador: dar de alta un préstamo verificando las condiciones del socio y del ejemplar, y registrar su devolución con los efectos colaterales que correspondan (atraso, restricción automática, alerta de reserva pendiente) — sin depender de un sistema externo ni de intervención técnica para ninguno de esos efectos.

## 2. Alcance

**Incluido:**
- Registro de préstamo domiciliario: selección de socio y ejemplar, verificación de restricción activa (RN-06), verificación de modalidad del ejemplar (RN-08/RN-09), verificación de límite con alerta y motivo de excepción (RN-01), fecha de préstamo editable (RN-13) y fecha de vencimiento calculada (RN-02).
- Registro de devolución: identificación del ejemplar (no requiere identificar al socio, RN-12), condición física opcional, cálculo de atraso y generación de `HistorialAtraso` y, si corresponde, `RestriccionSocio` automática (RN-18, con la exención de RN-07 para Honorario), marcado de reserva pendiente como "Personal alertado" si existe.
- Enforcement de la invariante de circulación (RN-04) a los dos niveles que exige DA-09: índice único parcial ya creado en Módulo 1, y verificación explícita en la aplicación antes de intentar la operación (reutilizando `Ejemplar::tieneMovimientoActivo()`, ya escrito).

**Explícitamente fuera de este módulo** (per DA-06/DA-08):
- Renovaciones y cola de reservas (Módulo 5) — este módulo solo **marca** una reserva pendiente como "Personal alertado" al devolver el libro correspondiente (criterio de aceptación explícito de este módulo), pero no gestiona la cola, el retiro ni el vencimiento de la reserva.
- Gestión (alta/revocación) de `ExcepcionAutorizada` (Módulo 6 — "Excepciones y restricciones") — este módulo solo **lee** excepciones vigentes ya existentes, igual que ya lo hace `Ejemplar::puedeSalirDeLaBiblioteca()` desde Módulo 2.
- Tarea programada que marque automáticamente un préstamo como "Atrasado" al vencer (Módulo 7 — "Tareas programadas"). Ver riesgo R-1.
- Un panel persistente de alertas para el personal (Módulo 8 — "Panel de alertas"). El efecto de la alerta de reserva (cambio de estado + mensaje de confirmación en el momento de la devolución) sí es de este módulo; su visualización centralizada y persistente, no.
- Préstamos institucionales (fuera del alcance de Fase 1 del software completo, DA-07 — la tabla ya existe por exigencia de Módulo 1 de migrar el dominio completo, pero no se opera sobre ella).

## 3. Reglas de negocio aplicables

| Código | Regla | Aplicación en este módulo |
|---|---|---|
| RN-01 | Un socio no puede tener préstamos domiciliarios activos superiores al límite de su Tipo de Socio. El sistema alerta, no bloquea; si el personal continúa, debe registrar un motivo. | *Enforcement* real, por primera vez en el proyecto. Se compara la cantidad de préstamos abiertos del socio (`estado` en `activo`/`atrasado`) contra `TipoSocio::limite_prestamos_simultaneos` (no un valor hardcodeado — el sistema ya lo hace configurable desde Módulo 3, D-04). Si se alcanza o supera el límite, el alta solo se completa si se informa `motivo_excepcion_limite`; el registro queda marcado `es_excepcion_de_limite = true`. |
| RN-02 | El plazo de todo préstamo domiciliario es de 15 días desde la fecha de préstamo. | `fecha_vencimiento = fecha_prestamo + ParametroConfiguracion::obtener(PLAZO_PRESTAMO_DIAS, 15)` días. El parámetro ya existe (`plazo_prestamo_dias = '15'`, sembrado en Módulo 1) pero, según el propio comentario de cabecera de `PrestamoDomiciliario.php`, **nunca fue leído por ningún código hasta ahora** — este módulo es el primero en consumirlo. |
| RN-04 | Un ejemplar solo puede participar en un movimiento activo a la vez, verificación cruzada entre los cuatro tipos de movimiento, sin excepciones. | *Enforcement* a los dos niveles de DA-09: Nivel 1 (índice único parcial sobre `ejemplar_id` en `prestamos_domiciliarios`, ya creado en la migración de Módulo 1) y Nivel 2 (`Ejemplar::tieneMovimientoActivo()`, ya escrito desde el Paso 5 de Módulo 2 con exactamente esta finalidad declarada en su propio docblock). Este módulo agrega el manejo de la excepción de violación de índice único como salvaguarda final ante una carrera de concurrencia real (el caso que Nivel 2 por sí solo no puede descartar). |
| RN-06 | Un socio con restricción activa no puede recibir préstamos, salvo Excepción Autorizada vigente de tipo "Exención". | Se reutiliza `RestriccionSocio::estaActiva()` (Módulo 1) para detectar la restricción, y se busca una `ExcepcionAutorizada` vigente (`estaVigente()`, Módulo 1) de tipo `TIPO_EXENCION_RESTRICCION` para el socio. Si hay restricción y no hay excepción vigente, el alta se rechaza mostrando el motivo y la fecha de fin — no es un límite salteable con un motivo de texto libre (a diferencia de RN-01): solo una `ExcepcionAutorizada` real la habilita. |
| RN-08 / RN-09 | Modalidad "Solo sala" nunca sale; "Restringido a autorización" requiere Excepción Autorizada vigente para ese ejemplar puntual. | Ya completamente resuelto por `Ejemplar::puedeSalirDeLaBiblioteca()` (Módulo 2, Paso 7) — este módulo solo debe invocarlo antes de crear el préstamo, sin reimplementar la lógica. |
| RN-12 | La devolución no requiere que la realice el mismo socio que retiró el ejemplar. | La pantalla de devolución no pide identificar ningún socio — solo el ejemplar. |
| RN-13 | La fecha de préstamo es editable en el momento del registro (desfase entre entrega física y registro administrativo). | El formulario de alta incluye `fecha_prestamo` como campo editable, con `hoy` como valor por defecto — no se asume igual a `fecha_registro` (que sí es siempre `now()`, marca cuándo se tecleó el registro). |
| RN-18 | La restricción automática se genera en el momento de la devolución tardía, con 1 día de restricción por día de atraso, tope máximo configurable. | `dias_atraso = fecha_devolucion_efectiva - fecha_vencimiento` (si es positivo). Restricción de `min(dias_atraso, ParametroConfiguracion::TOPE_MAXIMO_RESTRICCION_DIAS)` días, salvo RN-07. El tope ya existe como parámetro sembrado (`tope_maximo_restriccion_dias = '30'`) y, al igual que el plazo de préstamo, nunca fue leído hasta ahora. |
| RN-07 (heredada de Módulo 3) | Honorario no recibe restricciones automáticas. | Se verifica `TipoSocio::sujeto_a_restriccion_automatica` antes de crear la `RestriccionSocio`; si es `false`, se registra igual el `HistorialAtraso` con `restriccion_generada = false` — mismo criterio ya aplicado (en sentido de lectura) en la vista de mostrador de Módulo 3. |
| D-09 | El estado de Ejemplar es parcialmente derivado. | Este módulo no agrega ningún campo de estado nuevo a `Ejemplar`: el préstamo activo ya hace que `estadoActual()` devuelva "Prestado" sin cambios adicionales (mecanismo ya construido en Módulo 1/2). |

## 4. Entidades del dominio involucradas

Todas ya tienen migración y modelo Eloquent desde Módulo 1 (verificado por lectura directa):

| Entidad | Migración | Modelo | Qué falta para este módulo |
|---|---|---|---|
| PrestamoDomiciliario | ✔ (incluye índice único parcial DA-09 Nivel 1) | ✔ | Sin constantes de estado (`'activo'`/`'atrasado'`/`'devuelto'` como strings mágicos en otros archivos) — se agregan por consistencia con el resto del proyecto (`Ejemplar::ESTADOS_*`, `ExcepcionAutorizada::TIPO_*`). No es un cambio de esquema. |
| HistorialAtraso | ✔ | ✔ | Nada — se usa tal cual. |
| RestriccionSocio | ✔ | ✔ | Nada — se usa tal cual (`estaActiva()` ya existe). |
| ExcepcionAutorizada | ✔ | ✔ | Nada — se **lee** (`estaVigente()`), no se escribe (su CRUD es Módulo 6). |
| Reserva | ✔ | ✔ | Nada — se transiciona su `estado` a `'personal_alertado'` desde este módulo (única escritura que este módulo hace sobre `Reserva`; el resto de su ciclo de vida es Módulo 5). |
| ParametroConfiguracion | ✔ | ✔ (`obtener()`, cacheado 1h) | Nada — se **lee** por primera vez desde código de aplicación (`PLAZO_PRESTAMO_DIAS`, `TOPE_MAXIMO_RESTRICCION_DIAS`). |
| Ejemplar | ✔ | ✔ | Nada — se reutilizan `tieneMovimientoActivo()`, `puedeSalirDeLaBiblioteca()`, `estadoActual()`. |
| Socio | ✔ | ✔ | Se agrega un método de conveniencia `cantidadPrestamosActivos()` (evita duplicar la consulta `whereIn(['activo','atrasado'])` que ya aparece en `SocioController::show()`) — no es cambio de esquema, es DRY. |

**Conclusión de esta verificación:** igual que en los dos módulos anteriores, el modelo de datos completo ya existe, incluyendo el mecanismo de concurrencia (DA-09 Nivel 1) y toda la lógica de dominio reutilizable de módulos previos (`estaActiva()`, `estaVigente()`, `puedeSalirDeLaBiblioteca()`, `tieneMovimientoActivo()`). Lo que falta es exclusivamente la capa de controlador, rutas, FormRequests, vistas y tests que orquesten esas piezas ya construidas — confirmado por búsqueda directa: cero controladores o rutas de préstamos existen hoy.

## 5. Casos de uso comprendidos

- **CU-1:** Registrar un préstamo domiciliario (verificando RN-01, RN-04, RN-06, RN-08/RN-09; con fecha editable RN-13 y vencimiento calculado RN-02).
- **CU-2:** Registrar la devolución de un préstamo domiciliario (RN-12), con condición física opcional.
- **CU-3:** Generación automática de restricción por devolución tardía (RN-18), salvo Honorario (RN-07).
- **CU-4:** Alerta de reserva pendiente al devolver un libro reservado (transición de estado de `Reserva`, sin gestionar su ciclo completo).

## 6. Dependencias con otros módulos

- **Módulo 2 (Catálogo) y Módulo 3 (Socios):** precondición dura y explícita del propio Plan de Implementación v2 ("Precondición: Módulos 2 y 3 completos"). Ambos están cerrados con evidencia real (`31 passed`, `11 passed`).
- **Módulo 5 (Renovaciones y reservas):** este módulo escribe un único campo del ciclo de vida de `Reserva` (la transición a `personal_alertado`), dejando el resto (cola, vencimiento de la ventana de retiro, cancelación) para Módulo 5 — dependencia de escritura parcial, acotada y ya prevista por el propio criterio de aceptación de este módulo, no una invasión de su alcance.
- **Módulo 6 (Excepciones y restricciones):** dependencia de **lectura**, mismo patrón que R-2 de los briefings de Módulo 2 y 3 — DA-06 no la documenta explícitamente, pero es sobre un esquema y método de dominio (`ExcepcionAutorizada::estaVigente()`) que ya existen desde Módulo 1. Ver riesgo R-2 más abajo por una implicancia adicional específica de este módulo (no solo de lectura de esquema, sino de **ausencia total de datos** cargables sin intervención manual).
- **Módulo 7 (Tareas programadas):** este módulo no depende de que Módulo 7 exista para funcionar correctamente (ver riesgo R-1), pero Módulo 7 sí va a depender de las convenciones que este módulo establece (constantes de estado de `PrestamoDomiciliario`).

## 7. Decisiones de arquitectura que condicionan la implementación

- **DA-02 (monolito modular):** sin cambios — controlador y rutas dentro de la misma aplicación Laravel.
- **DA-03 (enmendada por ADR-007):** Laravel 12, Blade + Alpine.js, PostgreSQL 16 — stack ya validado tres veces en verde (Módulos 1, 2 y 3).
- **DA-06:** no documenta la dependencia de lectura hacia Excepciones/Restricciones (ver R-2), consistente con la misma omisión ya señalada y aceptada en los dos briefings anteriores.
- **DA-08:** orden #4, después de Catálogo (#2) y Socios (#3), antes de Renovaciones y reservas (#5).
- **DA-09 (estrategia de enforcement de RN-04 a dos niveles):** es la decisión de arquitectura más directamente relevante para este módulo — ver la aplicación concreta en la sección 3 (fila RN-04) y el riesgo R-3.

## 8. Riesgos técnicos identificados

**R-1 — El estado `'atrasado'` de un préstamo no se marca automáticamente al vencer, porque la tarea programada que haría esa transición es Módulo 7 (todavía no construido).**
Esto no bloquea el módulo: el cálculo de atraso en la devolución (RN-18) se hace comparando `fecha_devolucion_efectiva` contra `fecha_vencimiento` directamente en el momento de la devolución, sin depender de que el campo `estado` ya diga `'atrasado'` — el mismo patrón de "estado derivado de fechas, no de un flag mutable" que D-09 ya aplica a `Ejemplar`. En todas las consultas que necesitan "préstamos abiertos" (para RN-01 y para RN-04 Nivel 2, indirectamente vía `tieneMovimientoActivo()`), se trata `'activo'` y `'atrasado'` como equivalentes (ambos "no cerrados"), igual que ya lo hace `SocioController::show()` desde Módulo 3. Un préstamo vencido pero no devuelto seguirá mostrando `estado = 'activo'` hasta que exista Módulo 7 o hasta que se devuelva — es una limitación conocida y aceptada del alcance actual, no un defecto de este módulo.

**R-2 — No existe ninguna interfaz para crear una `ExcepcionAutorizada` de tipo "Exención de restricción por atraso" (RN-06) ni "Autorización de salida de material restringido" (RN-09); su gestión es Módulo 6.**
A diferencia de R-2 de los briefings anteriores (que eran solo omisiones documentales de DA-06 sobre dependencias de lectura), acá la implicancia práctica es distinta: el camino de "socio con restricción pero con excepción vigente" y "ejemplar restringido con autorización vigente" **no es ejercitable desde la interfaz de usuario todavía**, porque no hay forma de crear esos registros salvo directamente en base de datos o mediante seeders/tests. No es bloqueante para este módulo (que solo necesita **leer** excepciones, no crearlas — ya se resolvió igual en `Ejemplar::puedeSalirDeLaBiblioteca()` desde Módulo 2, sin que nadie pudiera crear una excepción real desde la UI hasta hoy tampoco). Se documenta para que quede explícito que la cobertura de tests de este módulo va a crear la `ExcepcionAutorizada` de prueba directamente por código (mismo patrón que Módulo 2 usó para probar RN-09 antes de que existiera ninguna gestión de excepciones), y para que Módulo 6 sepa que estos dos flujos ya están consumidos y a la espera de su interfaz de alta.

**R-3 — Los parámetros de límite de préstamos (`ParametroConfiguracion::LIMITE_PRESTAMOS_ESTANDAR` / `LIMITE_PRESTAMOS_HONORARIO`, sembrados en Módulo 1) son redundantes y no deben usarse en este módulo.**
El criterio de aceptación del Plan de Implementación v2 es explícito: *"El sistema usa el límite del Tipo de Socio, no un valor hardcodeado"* — y Módulo 3 ya construyó ese límite como campo editable por tipo (`TipoSocio::limite_prestamos_simultaneos`, con su propio criterio de aceptación y test verificando que un cambio se aplica de inmediato). Los dos parámetros globales de límite en `ParametroConfiguracion` parecen ser un remanente de un diseño anterior a esa decisión (un límite único por todo el sistema, no por tipo) y quedaron sembrados sin que ningún código los consuma. Se decide, dentro de este briefing, **no leerlos** en este módulo: la fuente de verdad para RN-01 es `TipoSocio::limite_prestamos_simultaneos`, tal como exige el criterio de aceptación transcrito. No se elimina el seeder de esos dos parámetros (sería un cambio fuera del alcance de este módulo, sobre un archivo de Módulo 1, y no genera ningún efecto incorrecto por sí solo al quedar sin uso) — se deja constancia de la inconsistencia para que se evalúe su remoción en una limpieza de deuda técnica futura, no como bloqueante de este módulo.

## 9. Criterios de aceptación

(Transcritos literalmente del Plan de Implementación v2, sección Módulo 4 — Préstamos y devoluciones, sin modificación.)

- El préstamo de un ejemplar con préstamo activo es rechazado por la base de datos, no solo por el código de aplicación. Si dos solicitudes simultáneas intentan prestar el mismo ejemplar, exactamente una tiene éxito.
- Un socio con restricción activa no puede recibir un préstamo, y el sistema muestra el motivo y la fecha de fin de la restricción.
- Un socio con restricción activa pero con Excepción Autorizada vigente de tipo "Exención" puede recibir el préstamo. El registro del préstamo indica que se usó una excepción.
- Un socio estándar con 3 préstamos activos recibe una alerta al intentar un cuarto. El personal puede continuar ingresando un motivo de excepción. El préstamo queda registrado con ese motivo.
- Un socio Honorario con 5 préstamos activos recibe la misma alerta de límite (su límite es 5). El sistema usa el límite del Tipo de Socio, no un valor hardcodeado.
- La devolución de un préstamo vencido con 3 días de atraso genera una restricción de 3 días de duración para el socio, salvo que sea Honorario.
- La devolución de un libro con reserva pendiente activa la alerta de "avisar al socio" en el panel del mostrador dentro del ciclo de la misma request.
- La devolución puede registrarse sin identificar quién trae el libro.

## 10. Plan de implementación recomendado

Orden interno sugerido (sin dependencias externas duras adicionales a las ya satisfechas por Módulos 2 y 3):

1. **Ajustes de base a modelos existentes** — constantes de estado en `PrestamoDomiciliario` (sin cambio de esquema), `Socio::cantidadPrestamosActivos()`, y refactor de la búsqueda de socios (ya escrita inline en `SocioController::index()`, Módulo 3) a un scope `Socio::scopeBuscar()` reutilizable desde el flujo de préstamo — evita duplicar la lógica de `unaccent`/`jsonb_array_elements_text`.
2. **Registro de préstamo** — controlador con `create()`/`store()`, aplicando en orden: RN-04 Nivel 2, RN-08/RN-09, RN-06, RN-01, cálculo de RN-02, captura de la violación de índice único (RN-04 Nivel 1) como salvaguarda final.
3. **Devolución** — búsqueda del préstamo activo por ejemplar/libro, confirmación con condición física opcional, cálculo de atraso (RN-18/RN-07), transición de reserva pendiente a "Personal alertado".
4. **Puntos de entrada en la UI ya existente** — enlace "Prestar" en la vista de detalle de Libro (Módulo 2, por ejemplar disponible) y "Registrar préstamo" en la vista de mostrador de Socio (Módulo 3), sin modificar la lógica de esos controladores.
5. **Tests** — cobertura de los 8 criterios de aceptación explícitos.
6. **Datos de demostración y guía de revisión funcional**, mismo patrón que Módulos 2 y 3.

En todos los pasos, reutilizar sin modificar los métodos de dominio ya escritos (`Ejemplar::tieneMovimientoActivo()`, `puedeSalirDeLaBiblioteca()`, `RestriccionSocio::estaActiva()`, `ExcepcionAutorizada::estaVigente()`) y el patrón de acceso por rol ya establecido (`role:administrador,personal`).

---

## Recomendación

**El equipo considera que existe información suficiente para iniciar el desarrollo completo del Módulo 4 — Préstamos y devoluciones, sin ninguna decisión de producto o dominio pendiente que lo bloquee.**

A diferencia de Módulo 2 (que tuvo un punto diferido, R-1) y en línea con Módulo 3 (que no tuvo ninguno), los tres riesgos identificados en este briefing (R-1, R-2, R-3) son de naturaleza técnica y de alcance, no de producto: los tres se resuelven o se documentan como no bloqueantes dentro de este mismo briefing. En particular, R-2 deja constancia de que dos de los ocho criterios de aceptación (excepción de restricción, excepción de material restringido) van a probarse creando la `ExcepcionAutorizada` correspondiente directamente por código en los tests, no desde una interfaz de usuario — porque esa interfaz todavía no existe y su construcción no corresponde a este módulo (DA-08, Módulo 6). Se recomienda proceder con los 6 pasos del plan de implementación en el orden indicado.
