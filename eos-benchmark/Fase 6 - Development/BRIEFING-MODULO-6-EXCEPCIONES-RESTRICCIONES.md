# Briefing técnico — Módulo 6: Excepciones y restricciones

## Fuentes

- `eos-benchmark/Fase 3 - Architecture/entregables/plan-implementacion-fase1-v2.md`, líneas 260-294
  (sección "Módulo 6 — Excepciones y restricciones").
- `eos-benchmark/Fase 2 - Domain Modeling/entregables/modelo-de-dominio-v2.md`, secciones 4.1
  (Excepción autorizada, líneas 325-354) y 4.2 (Restricción de socio, líneas 358-374), y tabla de
  reglas de negocio (RN-06, RN-07, RN-10, RN-11) y decisiones (D-03).
- `eos-benchmark/Fase 1 - Discovery/entregables/relevamiento-consolidado-v2.md`, secciones 7.1
  (Socios honorarios) y 7.2 (Excepción individual de penalización).
- Código real ya existente: `app/Models/ExcepcionAutorizada.php`, `app/Models/RestriccionSocio.php`,
  sus migraciones (`2024_01_01_000200_*`, `2024_01_01_000210_*`), y los usos actuales en
  `PrestamoController` y `Ejemplar::puedeSalirDeLaBiblioteca()` (Módulos 2 y 4).

## Objetivo

Formalizar el mecanismo de excepciones autorizadas (D-03: un único mecanismo con tipos, en vez de
modelar por separado cada dispensa) mediante un CRUD restringido a Administrador, dar de alta la
gestión de restricciones de socios (automáticas ya generadas desde el Módulo 4, más manuales
creadas por Personal), y migrar al sistema los dos casos históricos identificados en el
relevamiento: la excepción de penalización de un socio puntual y la condición de socios honorarios.

## Alcance

**Incluido:**
- CRUD de `ExcepcionAutorizada` (crear, listar con filtros, revocar) — solo Administrador (RN-10).
- Alta y listado de `RestriccionSocio` manual — Personal y Administrador.
- Verificación al prestar (RN-06/RN-07): **ya implementada desde el Módulo 4** (`PrestamoController`,
  `tieneExcepcionVigente()`), no requiere cambios funcionales, solo el refactor de centralización
  descrito en la Decisión D-18.
- Migración de los dos casos históricos del relevamiento (7.1, 7.2) vía seeder production-safe.

**Excluido (no exigido por el plan ni el dominio):**
- Tarea programada que actualice automáticamente la columna `estado` a "vencida" cuando pasa la
  fecha de fin — el criterio de aceptación dice que la excepción "aparece" vencida, no que se
  reescriba la columna; se resuelve por cómputo (ver Decisión D-15). Si en el futuro se necesita
  reflejar la transición en la propia columna (por ejemplo, para reportes que filtren directamente
  por `estado = 'vencida'`), es una extensión del Módulo 7 (Tareas programadas), no de este módulo.
- Revocación de `RestriccionSocio` — el plan solo describe alta manual con fecha de fin definida,
  ninguna pantalla de revocación anticipada. No se inventa esa funcionalidad.
- Campo nuevo de "código de socio" para representar literalmente "S-0072" — ver Riesgo R-2.

## Reglas de negocio y decisiones cubiertas

| Regla/Decisión | Texto | Estado |
|---|---|---|
| RN-06 | Un socio con restricción activa no puede recibir nuevos préstamos, salvo Excepción Autorizada vigente de tipo "Exención de restricción". | Ya implementada (Módulo 4). Este módulo no la modifica. |
| RN-07 | Los socios Honorario no reciben restricciones automáticas por atraso; el atraso se registra igual en el historial. | Ya implementada (Módulo 4, vía `TipoSocio::sujeto_a_restriccion_automatica`). |
| RN-09 | Ejemplar "Restringido a autorización" requiere Excepción Autorizada vigente para salir. | Ya implementada (`Ejemplar::puedeSalirDeLaBiblioteca()`, Módulo 2). Se refactoriza su consulta interna (D-18), sin cambio de comportamiento. |
| RN-10 | Las Excepciones Autorizadas solo pueden crearse, modificarse o revocarse por Administrador. | Nuevo en este módulo — CRUD todavía no existe. |
| RN-11 | Toda Excepción Autorizada registra quién autorizó, fecha y motivo; fecha de fin opcional (vacía = indefinida hasta revocación explícita). | Parcialmente cubierta: el modelo y la migración ya tienen esos campos y el trait `Auditable`; falta el CRUD que efectivamente los complete al crear/revocar. |
| D-03 | Mecanismo único de Excepción Autorizada con tipos, en vez de modelar cada dispensa por separado. | Ya materializada en el modelo desde el Módulo 1. Este módulo construye la interfaz que faltaba. |

## Entidades (todas ya existentes desde el Módulo 1 — sin migraciones nuevas)

- **`ExcepcionAutorizada`**: `tipo`, `entidad_afectada_type/id` (polimórfica: Socio o Ejemplar),
  `autorizado_por`, `fecha_autorizacion`, `motivo`, `fecha_inicio`, `fecha_fin` (nullable),
  `estado`, `revocado_por` (nullable), `fecha_revocacion` (nullable). Ya tiene `estaVigente()` y las
  constantes de tipo (`TIPO_EXENCION_RESTRICCION`, `TIPO_LIMITE_ESPECIAL`,
  `TIPO_AUTORIZACION_MATERIAL_RESTRINGIDO`). **Faltan** constantes de estado y la relación inversa
  `revocadoPor()` (ver Decisión D-14).
- **`RestriccionSocio`**: `socio_id`, `tipo` (`automatica`/`manual`, hoy solo como string libre),
  `fecha_inicio`, `fecha_fin`, `dias_atraso_origen` (nullable), `prestamo_domiciliario_id`
  (nullable), `generada_por_usuario_id` (nullable = generada por el sistema), `observaciones`. Ya
  tiene `estaActiva()`. **Faltan** constantes de tipo (Decisión D-16) y el trait `Auditable`
  (Decisión D-17).
- **`Socio`** y **`Ejemplar`**: sin cambios de esquema; son las dos entidades afectables por una
  Excepción Autorizada (polimórfica ya construida desde el Módulo 1).

## Casos de uso

- **CU-1 (Administrador crea una Excepción Autorizada):** completa tipo, entidad afectada, motivo,
  fecha de inicio y fecha de fin opcional. El sistema fija `autorizado_por` y `fecha_autorizacion`
  automáticamente (RN-11), sin permitir que el formulario los sobrescriba.
- **CU-2 (Administrador revoca una Excepción Autorizada vigente):** el sistema fija `revocado_por` y
  `fecha_revocacion`, cambia `estado` a revocada. Deja de aplicar en cualquier validación desde ese
  momento.
- **CU-3 (Administrador o Personal crea una Restricción manual):** completa socio, motivo
  (`observaciones`) y fecha de fin; el sistema fija `tipo = manual`, `fecha_inicio = hoy`, y
  `generada_por_usuario_id` al usuario autenticado.
- **CU-4 (Cualquier usuario con acceso visualiza el listado de excepciones vigentes):** filtrable
  por tipo y por entidad afectada.
- **CU-5 (Migración de casos históricos):** al desplegar este módulo, se siembran los dos casos
  reales identificados en el relevamiento (7.1 socios honorarios — ya cubierto desde el Módulo 1 vía
  `TipoSocio::sujeto_a_restriccion_automatica = false`, no requiere una Excepción nueva; 7.2 la
  excepción individual de penalización — sí requiere un registro nuevo de `ExcepcionAutorizada`).

## Dependencias

- **Precondición del plan:** "Módulos 3 y 4 completos" — ambos cerrados con evidencia real.
- Este módulo no depende de Módulo 5 (ya cerrado) ni de Módulo 7 (todavía no iniciado) — la
  exclusión de la tarea programada de vencimiento (ver Alcance) evita crear una dependencia
  artificial hacia un módulo que no está en el camino crítico de este.

## Decisiones de arquitectura

- **Decisión D-14:** formalizar como constantes de clase en `ExcepcionAutorizada` los tres valores
  de `estado` ya documentados como comentario en la migración (`ESTADO_VIGENTE = 'vigente'`,
  `ESTADO_VENCIDA = 'vencida'`, `ESTADO_REVOCADA = 'revocada'`) — mismo patrón que
  `Reserva::ESTADO_*` del Módulo 5. Se agrega también la relación faltante `revocadoPor()`
  (`belongsTo(User::class, 'revocado_por')`), simétrica a `autorizadoPor()`, que ya existe pero no
  tiene su contraparte.
- **Decisión D-15:** el estado "vencida" que debe **verse** en el listado (criterio de aceptación:
  "aparece con estado 'Vencida'") se calcula de forma derivada — un método `estadoVisible()` que
  compara `estado` almacenado contra `fecha_fin` en el momento de la consulta — en vez de requerir
  una tarea programada que reescriba la columna. Es el mismo criterio arquitectónico ya aprobado en
  D-09 (`Ejemplar::estadoActual()`): un estado calculable en el momento de la lectura no necesita
  persistirse ni sincronizarse por batch. La columna `estado` en base de datos solo se escribe
  explícitamente en dos casos: `'vigente'` al crear, `'revocada'` al revocar; nunca `'vencida'`.
- **Decisión D-16:** agregar `RestriccionSocio::TIPO_AUTOMATICA = 'automatica'` y
  `TIPO_MANUAL = 'manual'`, y refactorizar el literal `'automatica'` que ya usa
  `PrestamoController::devolver()` (Módulo 4) para usar la constante — mismo patrón de refactor que
  el de RN-21 en el Módulo 5 (`Reserva::ESTADO_PENDIENTE`). Sin cambio de comportamiento.
- **Decisión D-17:** extender el trait `Auditable` a `RestriccionSocio`. Hasta ahora todas sus
  filas eran generadas por el sistema (Módulo 4); este módulo introduce la primera vía de creación
  humana (restricciones manuales por Personal/Administrador), y toda otra entidad del proyecto con
  mutación humana ya usa `Auditable` (`Socio`, `ExcepcionAutorizada`, `User`,
  `ParametroConfiguracion`) — se aplica el mismo estándar por consistencia, no se inventa un
  requisito nuevo.
- **Decisión D-18:** extraer la consulta que hoy vive como método privado
  `PrestamoController::tieneExcepcionVigente()` a un método público reutilizable en el propio
  modelo — por ejemplo `ExcepcionAutorizada::vigentePara($entidad, string $tipo): bool` — y
  refactorizar tanto `PrestamoController` como `Ejemplar::puedeSalirDeLaBiblioteca()` (que hoy
  duplica una consulta equivalente) para usarlo. Mismo criterio de centralización que
  `Libro::asignarSiguienteReserva()` en el Módulo 5: la lógica de "¿esta entidad tiene una excepción
  vigente de este tipo?" es del dominio de `ExcepcionAutorizada`, no de quien la consulta, y el CRUD
  nuevo de este módulo (por ejemplo, para bloquear la creación de una restricción manual redundante)
  también la va a necesitar.

## Riesgos

- **R-1 (mitigado por D-15):** sin tarea programada, la columna `estado` nunca transiciona sola a
  `'vencida'` — el estado mostrado siempre se deriva en el momento de la consulta. Documentado como
  decisión, no como limitación oculta.
- **R-2 (abierto, no bloqueante):** el dominio no tiene ningún campo de "código de socio" — el
  identificador "S-0072" citado en el plan de Fase 3 es un código informal del relevamiento en
  papel, no una columna de este sistema (`Socio` solo tiene `dni`, `nombre_principal`,
  `nombres_alternativos`). Este sandbox tampoco tiene datos reales de producción para localizar a
  ese socio específico. El seeder de migración histórica (CU-5) va a representar el caso con un
  Socio de ejemplo identificable por su `motivo` ("Colaboración histórica con la institución"), y
  debe quedar documentado en `docs/REVISION-MODULO-6.md` que, en un despliegue real, la Comisión
  Directiva/Administrador debe identificar el socio real y cargar la excepción desde la UI (o
  ajustar el seeder) — no se inventa un campo de esquema nuevo para resolver esto.
- **R-3 (decisión de implementación):** a diferencia de `CatalogoDemoSeeder`/`SociosDemoSeeder`/
  `PrestamosDemoSeeder`/`RenovacionesReservasDemoSeeder` (todos bloqueados explícitamente en
  producción), el caso histórico de la excepción individual (7.2 del relevamiento) es una decisión
  real ya tomada por la Comisión Directiva, no un dato ficticio de demostración. Se crea un seeder
  separado (`ExcepcionesHistoricasSeeder`) que **sí puede correr en producción**, de forma
  idempotente (`firstOrCreate`), en vez de mezclarlo con los seeders de demo.
- **R-4 (no es una inconsistencia, requiere dos middlewares distintos):** RN-10 limita el CRUD de
  `ExcepcionAutorizada` a Administrador, pero el plan permite que Personal cree restricciones
  manuales — mismo patrón ya usado en el Módulo 1 (6.1: distintas pantallas, distintos roles dentro
  del mismo módulo). Se aplican middlewares de rol diferentes por ruta, no por módulo completo.

## Criterios de aceptación (Plan de Implementación v2, Módulo 6, literales)

1. Un usuario con rol Personal no puede acceder a la pantalla de creación de excepciones. La ruta
   devuelve error de autorización.
2. Un Administrador puede crear una excepción de exención para el socio S-0072 con motivo
   "Colaboración histórica con la institución" y vigencia indefinida.
3. La excepción queda registrada con el nombre del Administrador que la creó y la fecha.
4. Un socio con excepción de exención vigente puede recibir un préstamo aunque tenga una
   restricción automática activa.
5. Una excepción con fecha de fin pasada aparece con estado "Vencida" y no aplica en las
   validaciones de préstamo.
6. El Administrador puede revocar una excepción antes de su fecha de fin. La revocación queda
   registrada con fecha y usuario.

## Plan de implementación

1. **Ajustes de base:** constantes `ESTADO_*` y relación `revocadoPor()` en `ExcepcionAutorizada`
   (D-14); método `estadoVisible()` (D-15); constantes `TIPO_*` en `RestriccionSocio` + refactor del
   literal en `PrestamoController` (D-16); trait `Auditable` en `RestriccionSocio` (D-17).
2. **Centralización de la consulta de vigencia:** `ExcepcionAutorizada::vigentePara()` (D-18),
   refactorizando `PrestamoController` y `Ejemplar::puedeSalirDeLaBiblioteca()` para reutilizarla.
3. **CRUD de Excepciones Autorizadas:** `ExcepcionController` (namespace
   `App\Http\Controllers\Excepciones`), rutas `excepciones.*` con `role:administrador` (RN-10):
   `index` (listado con filtros tipo/entidad), `create`/`store` (CU-1), `revocar` (CU-2).
4. **Alta y listado de Restricciones manuales:** `RestriccionController`, rutas `restricciones.*`
   con `role:administrador,personal` (CU-3), más el listado de activas/históricas por socio.
5. **Puntos de entrada en la UI existente:** enlaces desde `socios.socios.show` (restricciones y
   excepciones sobre ese Socio) y `catalogo.libros.show`/vista de Ejemplar (excepciones sobre ese
   Ejemplar), sin construir pantallas nuevas de navegación dedicadas más allá del CRUD mismo.
6. **Migración de casos históricos:** `ExcepcionesHistoricasSeeder` (production-safe, ver R-3),
   registrado en `DatabaseSeeder` con su propia guarda de idempotencia.
7. **Tests Feature + cierre documental:** control de acceso (RN-10), los 6 criterios de aceptación
   literales, seeder de demo si hace falta para revisión visual adicional, `docs/REVISION-MODULO-6.md`,
   actualización de `phase-summary.md`.

## Recomendación final

Proceder. No hay bloqueos: las entidades, migraciones y la lógica de validación al prestar ya
existen y están probadas desde los Módulos 2 y 4; este módulo construye exclusivamente la interfaz
de gestión (CRUD) que faltaba, más pequeños refactors de consistencia (D-14 a D-18) que no cambian
ningún comportamiento observable ya cubierto por la suite existente. El único punto abierto (R-2,
la falta de un campo real para "S-0072") es una limitación de datos del entorno, no del diseño, y
queda documentada para que la Comisión Directiva la resuelva al desplegar en producción.
