# Phase Summary

## Phase

06 — Development

---

## Estado

Módulo 1 (de 10) **cerrado**: entorno validado, proyecto Laravel 12 creado, migrado, sembrado, iniciado y con su suite de tests completa pasando (38/38). Módulo 2 (de 10) **cerrado**: Catálogo (Autor, Editorial, Categoría, Libro, Ejemplar, búsqueda, RN-21) completo y validado con evidencia real — `31 passed (87 assertions)` tras corregir dos defectos preexistentes revelados por la ejecución (`ADR-012`). Módulo 3 (de 10) **cerrado**: Socios (Tipo de Socio, Socio, búsqueda tolerante a acentos, vista de mostrador, historial paginado) completo y validado con evidencia real — `11 passed (25 assertions)` en la primera ejecución, sin defectos encontrados. Módulo 4 (de 10) **cerrado**: Préstamos y devoluciones (registro de préstamo con RN-01/RN-02/RN-04/RN-06/RN-08/RN-09/RN-13, devolución con RN-12/RN-18/RN-07 y alerta de reserva pendiente) completo y validado con evidencia real — `21 passed (59 assertions)` en la primera ejecución, sin defectos encontrados. Módulo 5 (de 10) **cerrado**: Renovaciones y reservas (renovación con bloqueo por reserva RN-03/RN-19, alta de reserva, asignación automática con cálculo de ventana de retiro RN-05/D-13, panel de alertas) completo y validado con evidencia real — `18 passed (38 assertions)` en la primera ejecución, sin defectos encontrados. Módulo 6 (de 10) **código completo, pendiente de ejecución real**: Excepciones y restricciones (CRUD de `ExcepcionAutorizada` restringido a Administrador vía RN-10, alta y listado de `RestriccionSocio` manual vía CU-3, migración production-safe del caso histórico del relevamiento) escrito completo, con 26 tests Feature/Unit nuevos, sin ninguna corrida real reportada todavía contra PHP/PostgreSQL (ver `ADR-002` y `docs/REVISION-MODULO-6.md`). Repositorio de código consolidado en un único monorepo — `nexora` (https://github.com/YagoT1/nexora.git) es la fuente única de verdad para código, documentación, trazabilidad e historial del proyecto (`ADR-010`), con el commit de consolidación ya publicado (`515c161`). El entorno temporal de validación (`sgb-laravel/`) fue verificado sin pérdida de contenido y eliminado (`ADR-009`, adenda de cierre). Único pendiente no bloqueante: pre-checklist de infraestructura (ver "Próximo trabajo", punto 4). Próximo paso: obtener evidencia real de ejecución de la suite del Módulo 6 (comando exacto en `docs/REVISION-MODULO-6.md`, sección 1) antes de declararlo cerrado; una vez cerrado, el Módulo 7 (Tareas programadas) es el siguiente en la secuencia de DA-08.

---

## Objetivo

Construir el sistema conforme al Plan de Implementación v2, módulo por módulo, respetando el orden de dependencias definido en DA-08.

---

## Avance

### Módulo 1 — Infraestructura y autenticación: código escrito

Repositorio: `sistema-gestion-bibliotecaria/`, subcarpeta trackeada del monorepo `nexora` (ver `ADR-001-repositorio-de-codigo.md`, enmendado por `ADR-010-monorepo-nexora-como-fuente-unica.md`).

Entregado:

- 30 migraciones: 29 cubriendo las 25 entidades del Modelo de Dominio v2 completo (no solo el alcance funcional de Fase 1 del software, conforme exige el Plan de Implementación v2 para el Módulo 1), más 1 migración de alteración sobre la tabla `users` generada por Breeze (agrega `rol` y `estado`). Corregido en esta revisión — ver `ADR-003`.
- Modelos Eloquent para todas las entidades, con relaciones y trazabilidad a la regla/decisión de origen documentada en cada archivo.
- Middleware de autorización por rol (`EnsureUserHasRole`), infraestructura de auditoría append-only (RN-14) vía trait `Auditable`.
- Panel de administración de usuarios (crear, editar, inactivar, reactivar, asignar rol) con vistas Blade + Alpine.js.
- Seeders: Tipos de Socio, Parámetros de Configuración, usuarios de prueba por rol.
- 4 archivos de test (Feature) cubriendo explícitamente los criterios de aceptación del Módulo 1: autenticación por rol, timeout de sesión, autorización por rol (incluyendo usuario inactivo), y auditoría de cambios.

**No ejecutado ni validado en el entorno donde se escribió** (ver `ADR-002-limitaciones-de-entorno-y-estrategia-de-desarrollo.md`): el sandbox de esta sesión no dispone de PHP, Composer, PostgreSQL ni acceso de red a Packagist. El primer checkpoint de calidad real es ejecutar `docs/BOOTSTRAP.md` en un entorno con PHP 8.3.

### Revisión de código y correcciones de seguridad (ver `ADR-003`)

Se revisó el código entregado del Módulo 1 y se corrigieron dos hallazgos directamente sobre el código fuente: (1) el trait `Auditable` excluye ahora los campos `$hidden` del modelo (password, remember_token) del payload que se escribe en `registros_auditoria`, que antes quedaba expuesto sin filtrar; (2) `UserController` impide que un administrador cambie su propio rol/estado o se autoinactive, evitando que el sistema quede sin ningún administrador activo. Verificado con `php -l` (PHP 8.5 vía `@php-wasm/cli`, instalado por no haber PHP nativo en el sandbox) y por revisión estática contra los tests existentes — no reemplaza la ejecución real de la suite. `ADR-003` deja registrados tres hallazgos menores sin corregir por estar fuera de alcance: `.git` inconsistente en `sistema-gestion-bibliotecaria/`, conteo de migraciones desactualizado en este mismo documento y en el README (29 documentadas, 30 reales), y falta de `.gitignore` explícito.

### Servidor MCP para acceso a PostgreSQL (ver `ADR-004`)

Se evaluaron alternativas para exponer la base de datos vía MCP a los clientes del equipo (Claude Desktop, Cursor). El servidor "oficial" (`@modelcontextprotocol/server-postgres`) está archivado desde mayo 2025 por una vulnerabilidad de inyección SQL sin parchear — se descartó por esa razón concreta, no por preferencia. Se eligió `crystaldba/postgres-mcp` (Postgres MCP Pro), que corrige explícitamente esa clase de vulnerabilidad y soporta tanto desarrollo local como, más adelante, Render staging con el mismo servidor en modo restringido. Se creó `docker-compose.yml` (Postgres 16 local), se configuró Cursor, y quedó documentado el paso manual pendiente para Claude Desktop (Cowork no tiene acceso a esa carpeta de configuración) en `docs/POSTGRES-MCP-SETUP.md`. La conexión en vivo no pudo verificarse desde esta sesión — requiere Docker Desktop corriendo en la máquina real; queda un checklist de verificación manual en esa misma guía.

### Incidente resuelto: fallo SSL instalando Composer sobre PHP 8.5 (ver `ADR-005`)

Al actualizar el entorno local a PHP 8.5.8 (Windows), la instalación de Composer falló con
`OpenSSL: certificate verify failed`. Causa raíz con dos factores: (1) `openssl.cafile`/`curl.cainfo`
sin configurar en el `php.ini` activo (comportamiento por defecto de PHP en Windows) — corregido
pero insuficiente por sí solo; (2) causa determinante: **Avast Antivirus** interceptando el tráfico
HTTPS con una CA raíz propia, no incluida en ningún bundle público. Resuelto agregando la CA raíz
real de Avast al bundle de PHP. Cerrado con evidencia objetiva (`composer diagnose` confirma HTTPS
a Packagist y GitHub OK). Se agregó una sección de troubleshooting reutilizable a
`docs/BOOTSTRAP.md` §9 para que otros desarrolladores no repitan la investigación. Pendiente
separado detectado (no bloqueante para este incidente): falta soporte de `zip`/`unzip`/`7-Zip` para
Composer, necesario antes del paso 2 de `docs/BOOTSTRAP.md`.

### Validación completa del entorno de bootstrap (ver `ADR-006`, `ADR-007`, `ADR-008`)

Se retomó `docs/BOOTSTRAP.md` desde el punto donde `ADR-005` lo dejó interrumpido (soporte `zip`
faltante para Composer) y se ejecutó de punta a punta, con el mismo rigor de diagnóstico basado en
evidencia. Hallazgos y decisiones:

- **Extensiones PHP deshabilitadas por defecto** (`zip`, `pdo_pgsql`, `fileinfo`) en el `php.ini` de
  WinGet — todas con su DLL presente, solo comentadas. Habilitadas y verificadas una por una.
- **Laravel 11 fuera de soporte de seguridad activo** (`ADR-007`): Composer rechazó instalar
  `laravel/framework ^11.0` por advisories sin parche en toda la rama 11.x (dos hallazgos de junio
  2026, uno de severidad alta, corregidos recién en Laravel 12.60/12.61). Se decidió actualizar el
  objetivo de arquitectura a **Laravel 12**, enmendando `DA-03` sin reescribir la decisión original.
- **Conflicto de puerto 5432** con un PostgreSQL 18 nativo ya instalado en la máquina — remapeado
  el contenedor Docker del proyecto a 5433, sin tocar el servicio nativo.
- **Defecto real de código** (`ADR-008`, no del entorno): `app/Models/User.php` no declaraba a nivel
  de modelo los mismos defaults de `rol`/`estado` que la migración define a nivel de columna —
  Eloquent no sincroniza esos defaults hacia la instancia en memoria que devuelve `create()`,
  causando que un administrador activo recibiera 403 en 3 tests. Corregido con
  `protected $attributes` en el modelo; verificado sin regresiones.

Verificación final, con evidencia objetiva: `composer create-project` sin errores, `php artisan
migrate --seed` sin errores (33 migraciones, 3 seeders), `php artisan test` → **38 passed, 94
assertions, 0 failures**, y `php artisan serve` respondiendo `200 OK` con sesión de base de datos
funcional (confirmado también visualmente por el usuario en el navegador). Es la primera vez que
este código se ejecuta contra PHP y PostgreSQL reales desde que se escribió (ver `ADR-002`).

### Hallazgo técnico documentado durante el desarrollo

**Nota de arquitectura (no bloqueante, para Architecture Review futura):** DA-09 especifica un índice único parcial *por tabla* de movimiento para garantizar RN-04 (invariante de circulación) a nivel de motor de base de datos. Como la invariante exige unicidad *entre las cuatro tablas de movimiento* (un ejemplar no puede estar simultáneamente en un préstamo domiciliario Y en una custodia externa, por ejemplo), un índice único por tabla no cubre el caso cruzado — eso solo puede resolverlo la verificación de aplicación ("Nivel 2" de DA-09). Se implementó `Ejemplar::tieneMovimientoActivo()` para cubrir ese caso. Se deja constancia de que la garantía a nivel de motor (Nivel 1) es, con el diseño de tablas actualmente aprobado, necesariamente parcial — una garantía completa a nivel de base de datos requeriría unificar las cuatro tablas de movimiento en una sola con discriminador de tipo, lo cual sería un cambio de arquitectura y no corresponde decidirlo unilateralmente en el Módulo 1.

---

## Próximo trabajo

1. ~~Housekeeping de git y consolidación de repositorio~~ — **hecho** (`ADR-009`, `ADR-010`): un
   único repositorio (`sistema-gestion-bibliotecaria/` como subcarpeta del monorepo `nexora`),
   publicado en `https://github.com/YagoT1/nexora.git` (commit `515c161`).
2. ~~Verificación de integridad y cierre de `sgb-laravel/`~~ — **hecho** (adenda de cierre en
   `ADR-009`): 0 archivos perdidos, entorno temporal eliminado.
3. ~~Ejecutar `sistema-gestion-bibliotecaria/docs/BOOTSTRAP.md` en un entorno con PHP 8.3 real~~ —
   **hecho** (ver `ADR-006`/`ADR-007`/`ADR-008`): 38/38 tests en verde.
4. Completar el pre-checklist de infraestructura (Render.com, HTTPS, cron, variables de entorno) —
   pendiente, no bloqueante para el inicio del Módulo 2. Incluye la recomendación de `ADR-010`:
   filtrar el trigger de despliegue de Render.com por path (`sistema-gestion-bibliotecaria/**`), no
   por cualquier push a `main` del monorepo.
5. ~~Módulo 2 — Catálogo: briefing técnico~~ — **hecho** (`BRIEFING-MODULO-2-CATALOGO.md`): concluye
   que hay información suficiente para iniciar la implementación (pasos 1 a 8), con una única
   decisión pendiente y no bloqueante (R-1, historial de condición física).

### Módulo 2 — Catálogo: implementación en curso (2026-07-14)

Tras revisión objetiva del estado del proyecto (ver nota más abajo), se determinó que no existía
ningún bloqueo real para iniciar la implementación y que seguir invirtiendo tiempo en tooling de
entorno (`ADR-004`, `ADR-011`) ya no aportaba valor frente a avanzar el producto. Se inició el plan
de implementación recomendado por el briefing:

- **Paso 1 (Autor, Editorial) — código escrito:** `AutorController`, `EditorialController`
  (namespace `App\Http\Controllers\Catalogo`), rutas bajo `catalogo.*` con middleware
  `role:administrador,personal` (Modelo de Dominio v2, 6.1: Voluntario no gestiona catálogo),
  vistas Blade + Alpine siguiendo la convención de `admin.users.*`, y enlace de navegación
  condicionado por rol en `layouts/app.blade.php`. Se agregó una salvaguarda no derivada
  explícitamente de una RN/DA (mismo patrón que `ADR-003` para Módulo 1): no se permite eliminar
  un Autor o Editorial que tenga Libros asociados, para no dejar relaciones M:N/1:N rotas.
- **Paso 2 (Categoría) — código escrito:** `CategoriaController` + `CategoriaRequest` (FormRequest
  reutilizable entre alta y edición, mitigando el riesgo R-3 del briefing). Valida profundidad
  máxima 2 (D-06/CL-02) en ambos sentidos: la categoría padre elegida debe ser de primer nivel
  (`Categoria::puedeSerPadre()`, criterio de aceptación explícito), y además una categoría que ya
  tiene subcategorías propias no puede pasar a tener padre (mismo invariante, sentido inverso — no
  cubierto literalmente por el criterio de aceptación, que solo habla de alta, pero necesario para
  que la edición no permita lo que la creación prohíbe). La vista de edición retira la opción de
  padre del formulario cuando no aplica, en vez de solo depender de la validación de servidor.
  - **Hallazgo técnico corregido durante este paso (afecta también al Paso 1):** las rutas
    `Route::resource('autores', ...)`, `('editoriales', ...)` y `('categorias', ...)` dependían
    del singularizador automático de Laravel (`Str::singular()`, reglas en inglés) para nombrar el
    parámetro de ruta vinculado al modelo — un mecanismo no verificable en este entorno (sin
    PHP/Composer reales, ver `ADR-002`) y no garantizado para sustantivos en español. Se corrigió
    fijando explícitamente el nombre de parámetro (`'parameters' => ['autores' => 'autor']`, etc.)
    en las tres rutas, eliminando la dependencia de esa inferencia. No amerita una ADR propia: es
    una corrección de implementación dentro del mismo trabajo en curso, sin impacto en ninguna
    decisión de arquitectura ya aprobada.
- **Paso 3 (Libro) — código escrito:** `LibroController` + `LibroRequest`. Autores y categorías se
  sincronizan como relaciones M:N (`sync()`), separadas de los campos propios de `libros`. Fiel al
  Modelo de Dominio v2 (1.1): "autores" queda opcional y sin mínimo — el propio dominio aclara que
  "un libro puede no tener autor identificable (recopilaciones, obras anónimas)" — no se inventó un
  requisito de mínimo 1 autor que el dominio no exige, pese a que el criterio de aceptación del
  módulo solo ejemplifica el caso con autores cargados. `destroy()` bloquea el borrado de un Libro
  con Ejemplares asociados (D-02); los pivotes `libro_autor`/`libro_categoria` sí tienen
  `cascadeOnDelete()` a nivel de base de datos, sin necesidad de `detach()` manual. La ruta `show`
  queda deliberadamente sin habilitar hasta el Paso 6 (la vista de detalle depende de Ejemplar,
  Paso 4, para listar estados). Se agregó una sub-navegación (`catalogo/_subnav.blade.php`) entre
  Libros/Autores/Editoriales/Categorías, y el enlace principal "Catálogo" del layout ahora apunta a
  Libros (antes apuntaba a Autores, provisorio del Paso 1).
- **Paso 4 (Ejemplar) — código escrito:** `EjemplarController` + `EjemplarRequest`, ruta anidada
  bajo Libro (`catalogo.libros.ejemplares.*`, sin `index`/`show` propios — D-02: el Ejemplar
  siempre existe en el contexto de un Libro). Se agregaron constantes en `Ejemplar`
  (`ESTADOS_MANUALES`, `MODALIDADES_ACCESO`, `ORIGENES`) para no repetir strings mágicos, mismo
  patrón que `User::ROL_*`. `destroy()` bloquea el borrado de un Ejemplar con movimiento activo
  (`tieneMovimientoActivo()`, RN-04) — salvaguarda no derivada explícitamente de una RN/DA, mismo
  criterio que `ADR-003`. Verificación de pertenencia Ejemplar↔Libro explícita en el controlador
  (`abort_unless`) en vez de depender del scoping automático de rutas anidadas de Laravel, no
  verificable en este entorno (mismo criterio que el fix de nombres de parámetro del Paso 2). La
  pantalla de edición de Libro (`catalogo.libros.edit`) hace de punto de gestión provisorio de sus
  ejemplares (listado + alta + edición) hasta que el Paso 6 la reemplace por la vista de detalle
  definitiva con búsqueda y estado.
- **Paso 5 (Búsqueda de catálogo) — código escrito:** filtro combinado (AND) por título (parcial,
  `ilike`), autor (parcial, por nombre), categoría, estado y modalidad, sobre `catalogo.libros.index`
  vía query string (`withQueryString()` en la paginación). `LibroSearchRequest` (nuevo) valida los
  cinco filtros antes de construir la consulta.
  - **Decisión de diseño relevante:** el filtro por `estado` no puede resolverse con un `where()`
    simple porque el estado de un Ejemplar es derivado (D-09), no una columna — lo calcula
    `Ejemplar::estadoActual()` en PHP sobre una instancia ya cargada, algo que no puede reutilizarse
    directamente dentro de una cláusula SQL `WHERE`. Se resolvió con `Libro::scopeConEstado()`
    (nuevo), que reproduce la misma lógica de negocio como condiciones SQL (`whereHas`/`match`).
    **Queda una duplicación deliberada y documentada** entre `estadoActual()` y `scopeConEstado()`:
    ambos deben mantenerse sincronizados manualmente si cualquiera de los dos cambia (advertencia de
    mantenimiento dejada como comentario en el propio `Libro.php`). No se evaluó viable evitar la
    duplicación sin introducir una capa de abstracción adicional (por ejemplo, generar el SQL desde
    una única fuente declarativa) que sería sobreingeniería para cinco estados fijos que ya están
    acotados por dominio (D-09) y no cambian con frecuencia — YAGNI.
  - `Ejemplar`: se nombraron las relaciones `prestamosInstitucionales()`, `movimientosInternos()`,
    `custodiasExternas()` (antes solo existían como `belongsToMany()` anónimos e inline dentro de
    `tieneMovimientoActivo()`/`estadoActual()`) para poder usarlas desde `whereHas()` en el scope de
    `Libro`. Cambio sin efecto en el comportamiento existente (mismo query, ahora con nombre). Se
    agregó la constante `Ejemplar::ESTADOS_OPERATIVOS` (universo completo: los 4 estados derivados +
    los 2 manuales) para validación y para poblar el `<select>` de búsqueda.
  - No amerita una ADR propia: es una decisión de implementación dentro del diseño de datos ya
    aprobado (D-09, RN-04), no una modificación de arquitectura, dominio o roadmap.
- **Paso 6 (Vista de detalle de Libro) — código escrito:** ruta `catalogo.libros.show` habilitada
  (antes excluida del resource); `LibroController::show()` carga autores, editorial, categorías y
  ejemplares del Libro. La vista nueva (`catalogo/libros/show.blade.php`) muestra los datos propios
  del Libro y la tabla de Ejemplares con estado (vía `estadoActual()`, sin duplicar su lógica en la
  vista), modalidad, condición física, origen y fecha de ingreso.
  - **Refactor de las etiquetas en español:** se agregaron `Ejemplar::ETIQUETAS_ESTADO` y
    `ETIQUETAS_MODALIDAD` (arrays const asociativos, mismo patrón que `ESTADOS_OPERATIVOS`) como
    única fuente de verdad, y se actualizó `index.blade.php` (Paso 5) para consumirlas en vez de
    mantener un array literal propio — evita que el Paso 6 introdujera una tercera copia de la
    misma traducción, algo que hubiera sido una duplicación evitable sin justificación (a diferencia
    de la de `scopeConEstado()`, que sí es necesaria porque cruza PHP↔SQL).
  - **Cambio de responsabilidad respecto del Paso 4:** la pantalla de edición de Libro
    (`catalogo.libros.edit`) deja de listar/gestionar Ejemplares — esa responsabilidad pasa
    definitivamente a `show`, tal como estaba previsto desde que se dejó esa nota en el Paso 4. Edit
    ahora solo edita los campos propios del Libro y enlaza a `show`.
  - No amerita ADR: implementación directa de CU-4 del briefing, sin impacto en arquitectura,
    dominio o roadmap.
- **Paso 7 (Validación RN-21) — código escrito:** al cambiar la modalidad de acceso de un
  Ejemplar (`EjemplarController::update()`), si el Libro tiene reservas en estado `pendiente` y,
  tras el cambio, ningún ejemplar del libro puede ya satisfacerlas, se agrega una advertencia al
  mensaje de confirmación (RN-21 no exige bloquear el cambio ni cancelar la reserva automáticamente
  — exige alertar al personal para que la gestione manualmente; no hay entidad de
  Notificación/Alerta en el Modelo de Dominio v2, D-08 la descarta explícitamente para la primera
  versión, así que el mensaje flash de la propia acción es el mecanismo correcto en este alcance).
  - `Ejemplar::puedeSalirDeLaBiblioteca()` (nuevo): implementa RN-08 (Solo sala nunca sale) y RN-09
    (Restringido a autorización solo sale con una `ExcepcionAutorizada` vigente para ese ejemplar
    puntual, reutilizando `ExcepcionAutorizada::estaVigente()` sin duplicar su cálculo de vigencia).
    Se definió en el modelo, no en el controlador, porque el Módulo 4 (préstamos) va a necesitar la
    misma verificación antes de autorizar cualquier salida — mismo criterio que las relaciones
    nombradas del Paso 5.
  - `Libro::reservas()` (nuevo): inversa de `Reserva::libro()`, que ya existía desde el Módulo 1.
  - **Corrección de una inconsistencia post-Paso 6:** los redirects de `EjemplarController`
    (`store`, `update`, `destroy`) y los enlaces "Volver al libro" de las vistas de Ejemplar seguían
    apuntando a `catalogo.libros.edit`, que desde el Paso 6 ya no lista ejemplares. Se corrigieron
    los cuatro puntos para apuntar a `catalogo.libros.show`, que es donde vive esa información
    ahora. Se documenta como corrección porque debería haberse hecho en el Paso 6 mismo.
  - **Dato de prueba agregado a `CatalogoDemoSeeder`:** un `Socio` y una `Reserva` en estado
    `pendiente` sobre "Ficciones" (que hoy sí tiene un ejemplar libre_circulacion capaz de
    satisfacerla), para poder ejercitar el caso "antes/después" de RN-21 desde la UI cambiando la
    modalidad de ese ejemplar a Solo sala, sin necesidad de construir ninguna pantalla de Módulo 5.
  - No amerita ADR: aplica RN-08/RN-09/RN-21 ya definidas en el dominio, sin introducir ninguna
    entidad, tabla o decisión de arquitectura nueva.
- **Paso 8 (Tests Feature del Módulo 2) — código escrito:** 6 archivos bajo
  `tests/Feature/Catalogo/`, mismo patrón que los tests del Módulo 1 (`RefreshDatabase`,
  `User::factory()->create(['rol' => ...])`, `actingAs()`, nombres de método en español).
  - `AccesoCatalogoTest`: control de acceso por rol a `catalogo.libros.index` (Voluntario
    bloqueado, Personal/Administrador permitido, visitante redirigido a login) — mismo
    patrón que `RoleAuthorizationTest` del Módulo 1, aplicado a las rutas de Catálogo.
  - `LibroTest`: criterio 1 (Libro con múltiples autores, sin ISBN, con categoría y
    subcategoría), el caso sin ningún autor (Modelo de Dominio v2, 1.1), y el guard de
    `destroy()` contra Ejemplares asociados (D-02).
  - `CategoriaProfundidadTest`: criterio 2 en ambos sentidos — no permite crear una
    subcategoría cuyo padre ya es subcategoría, tampoco permite editar una categoría con
    subcategorías propias para asignarle un padre (el sentido inverso agregado en
    `CategoriaRequest`), una categoría no puede ser su propia padre, y el caso positivo
    (crear una subcategoría válida) para no cubrir solo el camino de error.
  - `EjemplarEstadoTest`: criterio 3 (alta de Ejemplar Solo sala), criterio 6 (estado
    manual "En reparación" sin movimiento activo), criterio 4 (estado "Prestado" con un
    `PrestamoDomiciliario` activo creado directamente en el test, ya que la UI para
    crearlo es Módulo 4), y RN-08/RN-09 sobre `Ejemplar::puedeSalirDeLaBiblioteca()`:
    Solo sala nunca sale, Restringido sin excepción no sale, Restringido con una
    `ExcepcionAutorizada` vigente para ESE ejemplar sí sale, y dos casos negativos que
    prueban que la verificación está correctamente acotada por `entidad_afectada_id`
    (la excepción de otro ejemplar no autoriza) y por vigencia (una excepción revocada
    no autoriza).
  - `BusquedaCatalogoTest`: criterio 5 (título parcial, autor devuelve todos sus libros),
    más la combinación de filtros con AND (categoría + modalidad) y que "Limpiar
    filtros" muestra el listado completo — comportamientos del Paso 5 no cubiertos
    literalmente por el criterio 5 pero sí por su implementación.
  - `Rn21ModalidadTest`: criterio 7 / RN-21 — alerta al dejar sin ejemplares disponibles
    un Libro con reserva pendiente, y tres casos negativos que prueban ausencia de falsos
    positivos: otro ejemplar del libro sigue disponible, el libro no tiene reservas
    pendientes, y se edita el ejemplar sin cambiar la modalidad.
  - No se agregaron factories nuevas (Autor/Editorial/Categoria/Libro/Ejemplar/Socio/
    Reserva/ExcepcionAutorizada): se sigue el mismo criterio que el Módulo 1, que solo
    tiene `UserFactory` — los datos de estos tests se crean directamente vía
    `Model::create()`, evitando introducir infraestructura de testing no solicitada.
  - **Primera ejecución real (2026-07-14):** `1 failed, 26 passed (68 assertions)`. El
    único fallo reveló un defecto real y preexistente del Módulo 1, no introducido en este
    paso — ver subsección siguiente y `ADR-012`.

### Corrección de defecto real revelado por la primera ejecución (2026-07-14, ver `ADR-012`)

La Comisión Directiva ejecutó `php artisan test --filter=Catalogo` en su entorno (el mismo que
validó el Módulo 1) y obtuvo `1 failed, 26 passed`. El fallo (`SQLSTATE[42703]: Undefined column:
ejemplares_movimiento_interno.fecha_devolucion_efectiva`) se rastreó hasta un nombre de columna
incorrecto en `Ejemplar::movimientosInternos()`/`custodiasExternas()` (y sus relaciones inversas en
`MovimientoInterno`/`CustodiaExterna`): esas dos tablas pivote usan `fecha_retorno_efectiva`, no
`fecha_devolucion_efectiva` (ese nombre solo es correcto para la tabla pivote de préstamos
institucionales). Se verificó con `git show 581f6fb:./app/Models/Ejemplar.php` que el mismo nombre
incorrecto ya existía en el código original del Módulo 1 (antes de que el Paso 5 nombrara las
relaciones) — **no es un defecto introducido en el Módulo 2**, sino uno preexistente del Módulo 1
que su propia suite (orientada a auth/roles/auditoría) nunca ejercitó, y que la suite del Módulo 2
tampoco alcanzaba a exponer salvo en un único camino (por el cortocircuito de `if`/`||` en
`estadoActual()`/`tieneMovimientoActivo()`, y por la ausencia de un test que renderizara
`catalogo.libros.show` para un ejemplar "disponible" liso, el caso más común).

Corregido en `Ejemplar.php`, `MovimientoInterno.php`, `CustodiaExterna.php` y en las cuatro ramas
equivalentes de `Libro::scopeConEstado()` (que reproduce la misma lógica para el filtro de
búsqueda por estado — también afectado, sin test previo que lo cubriera). Se agregaron 4 tests de
regresión que cierran la brecha de cobertura que había ocultado el defecto: render de
`catalogo.libros.show` para un ejemplar disponible, creación real de un `MovimientoInterno` y de
una `CustodiaExterna` vinculados a un Ejemplar verificando `tieneMovimientoActivo()`/
`estadoActual()`, y el filtro `estado=disponible` de búsqueda. Detalle completo del diagnóstico,
la decisión y las alternativas descartadas en `ADR-012`.

**Segunda ejecución real (2026-07-14), tras el push del fix anterior:** `1 failed, 30 passed (85
assertions)`. Los 27 tests originales y 3 de los 4 nuevos pasaron; falló el nuevo test de búsqueda
por estado, con un defecto distinto al anterior (mismo nombre de columna ya corregido, pero
`wherePivotNull()` no es válido dentro de un closure `whereHas()` — cae en el parser dinámico de
Eloquent y genera SQL inválido). Corregido usando `whereNull()` con la columna de la tabla pivote
calificada explícitamente. Detalle completo en la actualización de `ADR-012`. Con este segundo fix
se esperan 31 tests en verde; esa confirmación todavía no se obtuvo — es la validación pendiente
antes de dar por cerrado el Módulo 2.

### Preparación para revisión funcional (2026-07-14)

Por instrucción del responsable del proyecto, se preparó el entorno para que los Pasos 1 a 4 del
Módulo 2 puedan revisarse funcionalmente (no solo leerse como código):

- **`CatalogoDemoSeeder`** (nuevo, registrado en `DatabaseSeeder`): carga Autores, Editoriales,
  Categorías (una con subcategorías, otra sin — para ver el límite de 2 niveles sin cargar nada a
  mano) y Libros/Ejemplares elegidos específicamente para cubrir los casos que importan: un libro
  con varios autores, uno sin editorial, uno sin ISBN y uno sin ningún autor (Modelo de Dominio v2,
  1.1: "un libro puede no tener autor identificable"), y las tres modalidades de acceso más los dos
  estados manuales de Ejemplar. Idempotente (`firstOrCreate` / verificación de existencia antes de
  crear cada Ejemplar), no corre en producción — mismo criterio que `AdminUserSeeder`.
- **`docs/REVISION-MODULO-2.md`** (nuevo): guía de revisión con los usuarios de prueba ya existentes
  desde el Módulo 1 (uno por rol), los datos cargados por el seeder, y una tabla que cruza cada
  criterio de aceptación del Plan de Implementación v2 (Módulo 2) contra si ya es revisable, es
  parcialmente revisable (con una verificación manual puntual en base de datos), o depende de un
  paso todavía no implementado (búsqueda, RN-21). Se optó por documentar explícitamente esta
  frontera en vez de dejar que quien revise deduzca por su cuenta qué se puede probar.
- **README.md**: se agregó una sección propia arriba del scaffold estándar de Laravel, apuntando a
  `docs/BOOTSTRAP.md` y `docs/REVISION-MODULO-2.md`.

No se creó infraestructura nueva (no hay migraciones nuevas en este paso) ni se tocó ningún archivo
de configuración del entorno — es contenido de aplicación (seeder) y documentación, coherente con
la conclusión de la revisión objetiva anterior de que el tooling de entorno ya no es prioritario.

### Módulo 3 — Socios: implementación completa (2026-07-14)

Con el Módulo 2 formalmente cerrado con evidencia objetiva, la Comisión Directiva otorgó autonomía
para determinar y ejecutar el siguiente paso técnicamente correcto. Se revisó DA-08 (Socios es el
módulo #3, sin dependencias — DA-06), el Plan de Implementación v2 y el estado real del código
(las entidades de dominio de Socios ya existían completas desde el Módulo 1), concluyendo que no
había ningún cambio de arquitectura, prioridad o planificación que justificar: correspondía iniciar
Módulo 3 tal como estaba programado. Se redactó `BRIEFING-MODULO-3-SOCIOS.md` como paso previo
obligatorio, siguiendo el mismo precedente del Módulo 2, identificando y resolviendo dentro del
propio briefing un riesgo técnico no documentado en la arquitectura (R-1: la búsqueda tolerante a
acentos requiere la extensión PostgreSQL `unaccent`, ausente de todo documento del proyecto hasta
ahora).

Entregado, en 7 pasos:

- **Paso 1 (CRUD Tipo de Socio) — código escrito:** `TipoSocioController` (namespace
  `App\Http\Controllers\Socios`), rutas bajo `socios.tipos-socio.*` con middleware
  `role:administrador,personal` (Modelo de Dominio v2, 6.1: "Gestionar socios" es Administrador y
  Personal, no Voluntario — mismo patrón que Catálogo). `destroy()` bloquea el borrado de un Tipo
  de Socio con socios asociados, mismo criterio de guarda que Autor/Editorial/Libro en el Módulo 2.
  Satisface D-04: el límite de préstamos simultáneos es editable desde la administración, sin
  intervención de código.
- **Paso 2 (CRUD Socio) — código escrito:** `SocioController`, con `nombres_alternativos`
  gestionado como lista de texto (una línea por nombre en el formulario, convertida a array
  `jsonb` en el modelo — R-3 del briefing).
- **Paso 3 (Búsqueda tolerante a variaciones de nombre) — código escrito:** migración
  `2024_01_03_000010_enable_unaccent_extension.php` (habilita la extensión `unaccent`, contrib
  estándar de PostgreSQL 16). `SocioController::index()` compara `unaccent(nombre_principal) ILIKE
  unaccent('%término%')`, y adicionalmente busca dentro de `nombres_alternativos` (columna `jsonb`)
  mediante una subconsulta `jsonb_array_elements_text` con la misma normalización — cubre
  simultáneamente nombre principal y nombres alternativos, tal como exige el criterio de
  aceptación 2.
- **Paso 4 (Vista de mostrador) — código escrito:** `SocioController::show()` carga préstamos
  domiciliarios activos/atrasados, reservas activas, la primera `RestriccionSocio` vigente (si la
  hay, vía `estaActiva()`) y el conteo de atrasos de los últimos 12 meses. La vista muestra un
  banner de alerta si hay restricción vigente y tres tarjetas métricas (préstamos, reservas,
  atrasos). RN-07 se respeta por construcción: un socio Honorario simplemente no tiene ninguna
  `RestriccionSocio` asociada (ese módulo no las genera; los Módulos 4/6 sí lo harán respetando
  `TipoSocio::sujeto_a_restriccion_automatica`), así que la consulta no encuentra ninguna que
  mostrar — no hizo falta ninguna condición especial en la vista para el caso Honorario.
- **Paso 5 (Historial de préstamos paginado) — código escrito:** listado paginado (15 por página,
  parámetro de página propio `historial` para no chocar con la paginación del listado principal de
  socios) de todos los préstamos domiciliarios del socio, ordenados por fecha descendente, incluido
  en la misma vista de mostrador.
- **Paso 6 (Relación faltante) — código escrito:** `Socio::reservas()` no existía en el modelo
  desde el Módulo 1 (su inversa, `Reserva::socio()`, sí) — agregada para poder cargar las reservas
  activas del Paso 4. Es una omisión del Módulo 1, no un cambio de arquitectura: el modelo de datos
  ya contemplaba la relación, solo faltaba declararla en Eloquent.
- **Paso 7 (Tests Feature del Módulo 3) — código escrito:** 4 archivos bajo
  `tests/Feature/Socios/`, mismo patrón que Módulo 1/2 (`RefreshDatabase`, `actingAs()`, nombres de
  método en español):
  - `AccesoSociosTest`: control de acceso por rol a `socios.socios.index` (Voluntario bloqueado,
    Personal/Administrador permitido, visitante redirigido a login).
  - `TipoSocioTest`: criterio 1 (cambio de límite de 3 a 4 aplicado de inmediato, releído desde la
    base de datos con `fresh()`, sin ningún paso de caché o reinicio intermedio) y el guard de
    `destroy()` contra Tipos de Socio con socios asociados.
  - `SocioTest`: alta de Socio, y criterio 2 en sus dos variantes — búsqueda "Garcia" encuentra
    "María García" por nombre principal, y por separado encuentra un socio cuyo nombre alternativo
    contiene "Garcia", en ambos casos excluyendo un socio no relacionado ("Juan Pérez").
  - `VistaMostradorSocioTest`: criterio 3 (préstamo atrasado visible + contador de atrasos) y
    criterio 4 (socio Honorario con atraso no muestra restricción vigente), este último incluyendo
    un caso negativo explícito que verifica que una `RestriccionSocio` de otro socio no se filtra
    incorrectamente hacia la vista del socio bajo prueba.
- **Preparación para revisión funcional:** `SociosDemoSeeder` (nuevo, registrado en
  `DatabaseSeeder` después de `CatalogoDemoSeeder`, de quien reutiliza el usuario Administrador
  como registrador de los préstamos de demostración) y `docs/REVISION-MODULO-3.md` (nuevo), mismo
  criterio que la preparación equivalente del Módulo 2: datos elegidos para ejercitar exactamente
  los 4 criterios de aceptación, y una tabla que cruza cada uno contra cómo revisarlo manualmente.

**Primera ejecución real (2026-07-14):** la Comisión Directiva corrió `php artisan migrate`
(extensión `unaccent` habilitada sin errores) y `php artisan test --filter=Socios` en su entorno
(el mismo que validó los Módulos 1 y 2) → **`11 passed (25 assertions)`, sin fallos.** A diferencia
del Módulo 2, no se encontró ningún defecto en esta primera corrida — los 7 pasos quedan validados
sin necesidad de corrección.

### Módulo 4 — Préstamos y devoluciones: implementación completa (2026-07-15)

Con el Módulo 3 formalmente cerrado con evidencia objetiva, la Comisión Directiva otorgó
nuevamente autonomía para actuar según el estado real del proyecto. Se revisó DA-08 (Préstamos y
devoluciones es el módulo #4, con precondición explícita "Módulos 2 y 3 completos" — ambos ya
cerrados), el Plan de Implementación v2 y el estado real del código (todas las entidades de
dominio involucradas —`PrestamoDomiciliario`, `HistorialAtraso`, `RestriccionSocio`,
`ExcepcionAutorizada`, `Reserva`, `ParametroConfiguracion`— ya existían completas desde el Módulo
1, incluido el índice único parcial de DA-09 Nivel 1), concluyendo que correspondía iniciar Módulo
4 tal como estaba programado, sin ningún cambio de arquitectura, prioridad o planificación que
justificar. Se redactó `BRIEFING-MODULO-4-PRESTAMOS.md` como paso previo obligatorio, identificando
y resolviendo dentro del propio briefing tres riesgos técnicos (R-1: sin tarea programada de
Módulo 7 todavía, el atraso se calcula por fecha en la devolución, no por un campo de estado
mutable; R-2: `ExcepcionAutorizada` no tiene interfaz de alta —eso es Módulo 6—, así que los
caminos de excepción se prueban creando el registro directamente por código; R-3: los parámetros
globales de límite de préstamos en `ParametroConfiguracion` son redundantes con
`TipoSocio::limite_prestamos_simultaneos` y no deben usarse, por ser este último la fuente de
verdad exigida por el criterio de aceptación literal).

Entregado, en 6 pasos:

- **Paso 1 (ajustes de base) — código escrito:** constantes `PrestamoDomiciliario::ESTADO_ACTIVO`/
  `ESTADO_ATRASADO`/`ESTADO_DEVUELTO`/`ESTADOS_ABIERTOS` (sin cambio de esquema, mismo patrón que
  `Ejemplar::ESTADO_*`); `Socio::cantidadPrestamosActivos()` (extrae una consulta que ya se repetía
  inline en `SocioController::show()`); y `Socio::scopeBuscar()`, extraído de la búsqueda unaccent
  que vivía inline en `SocioController::index()` (Módulo 3) para reutilizarla en la selección de
  socio del registro de préstamo — refactor DRY sin cambio de comportamiento, verificado contra la
  suite de Módulo 3 (que sigue pasando por los mismos criterios, solo cambia dónde vive el código).
- **Paso 2 (registro de préstamo) — código escrito:** `PrestamoController` (namespace
  `App\Http\Controllers\Prestamos`), con `create()`/`store()` aplicando en orden: RN-04 Nivel 2
  (`Ejemplar::tieneMovimientoActivo()`, ya escrito desde Módulo 2), RN-08/RN-09
  (`Ejemplar::puedeSalirDeLaBiblioteca()`, ídem), RN-06 (restricción activa +
  `ExcepcionAutorizada::estaVigente()` de tipo exención), RN-01 (límite del `TipoSocio`, alerta con
  motivo obligatorio si se supera, nunca bloqueo automático), RN-02 (vencimiento calculado con
  `ParametroConfiguracion::PLAZO_PRESTAMO_DIAS`, leído por primera vez en el proyecto), RN-13
  (fecha de préstamo editable, distinta de fecha de registro). La violación del índice único
  parcial (RN-04 Nivel 1, DA-09) se captura como `QueryException` y se traduce a un mensaje
  comprensible — salvaguarda final ante una carrera de concurrencia real que el Nivel 2 no llegó a
  detectar a tiempo.
- **Paso 3 (devolución) — código escrito:** búsqueda de préstamo activo por título de libro (RN-12:
  sin ningún campo de socio en el formulario), confirmación con condición física opcional, cálculo
  de atraso por fecha (no por el campo `estado`, ver R-1), generación de `HistorialAtraso` y, si
  corresponde, `RestriccionSocio` automática de `min(días de atraso, tope máximo configurable)` días
  (RN-18), salvo Honorario o excepción de exención vigente (RN-07). Si el libro tiene una `Reserva`
  pendiente, se marca como `personal_alertado` y se muestra un mensaje de alerta en la misma
  respuesta (criterio de aceptación explícito de este módulo; la gestión completa de la cola de
  reservas queda para Módulo 5).
- **Paso 4 (puntos de entrada en la UI existente) — código escrito:** enlace "Prestar" en
  `catalogo.libros.show` (Módulo 2) por cada ejemplar disponible que puede salir de la biblioteca, y
  "Registrar préstamo" en `socios.socios.show` (Módulo 3, vista de mostrador) — sin modificar la
  lógica de esos dos controladores, solo agregando el enlace. Se agregaron también "Nuevo préstamo"
  y "Devolución" al menú de navegación principal, mismo criterio de acceso que Catálogo y Socios.
- **Paso 5 (Tests Feature del Módulo 4) — código escrito:** 3 archivos bajo
  `tests/Feature/Prestamos/` (21 tests en total — 4 + 10 + 7; corregido respecto del conteo de 19
  registrado inicialmente en este documento y en `docs/REVISION-MODULO-4.md`, un error de conteo
  manual detectado recién al confrontar la documentación con la primera ejecución real, sin ningún
  efecto sobre el código ni sobre la cobertura):
  - `AccesoPrestamosTest`: control de acceso por rol (Voluntario bloqueado, Personal permitido,
    visitante redirigido a login), sobre `prestamos.create` y `prestamos.devolucion.buscar`.
  - `RegistroPrestamoTest`: los 6 criterios de aceptación del registro de préstamo, incluyendo un
    test que inserta un segundo préstamo activo directamente por Eloquent (sin pasar por el
    controlador) para demostrar que es la base de datos —no solo el código de aplicación— la que
    rechaza la violación de RN-04 (criterio de aceptación 1, literal: "rechazado por la base de
    datos, no solo por el código de aplicación").
  - `DevolucionTest`: los 3 criterios de aceptación restantes, más el tope máximo de restricción
    (RN-18) y el caso de devolución a tiempo (sin generar historial de atraso), no exigidos
    literalmente por el criterio pero necesarios para no dejar sin cobertura el camino sin atraso.
- **Preparación para revisión funcional:** `PrestamosDemoSeeder` (nuevo, registrado en
  `DatabaseSeeder` al final) y `docs/REVISION-MODULO-4.md` (nuevo). A diferencia de
  `SociosDemoSeeder` (que dejó atrasos ya devueltos), este seeder deja préstamos **activos y
  vencidos, sin devolver todavía**, para poder ejercitar en vivo el flujo de devolución y sus
  efectos colaterales durante la revisión — incluyendo los dos casos de R-2 (una
  `ExcepcionAutorizada` de exención y una de material restringido, sembradas directamente porque su
  interfaz de alta todavía no existe).

**Primera ejecución real (2026-07-15):** la Comisión Directiva corrió `php artisan db:seed
--class=PrestamosDemoSeeder` y `php artisan test --filter=Prestamos` en su entorno (el mismo que
validó los Módulos 1, 2 y 3) → **`21 passed (59 assertions)`, sin fallos.** Igual que el Módulo 3,
no se encontró ningún defecto de código en esta primera corrida. El único hallazgo fue documental,
no funcional: el conteo de tests registrado en este documento y en `docs/REVISION-MODULO-4.md`
decía 19; el resultado real y el conteo directo de los tres archivos de test confirman 21 (4 en
`AccesoPrestamosTest`, 10 en `RegistroPrestamoTest`, 7 en `DevolucionTest`). Corregido en ambos
documentos.

### Módulo 5 — Renovaciones y reservas: implementación completa (2026-07-15)

Con el Módulo 4 formalmente cerrado con evidencia objetiva (tras corregir el error de conteo de
tests), la Comisión Directiva confirmó el `git push origin main` y otorgó nuevamente autonomía para
actuar según el estado real del proyecto. Se revisó DA-08 (Renovaciones y reservas es el módulo #5,
con precondición explícita "Módulo 4 completo" — ya cerrado), el Plan de Implementación v2 (RN-03,
RN-05, RN-19, RN-20, RN-21) y el estado real del código (todas las entidades involucradas —
`Reserva`, `Renovacion`, `PrestamoDomiciliario`, `Libro`, `Ejemplar`, `ParametroConfiguracion` — ya
existían completas desde el Módulo 1), concluyendo que correspondía iniciar Módulo 5 tal como
estaba programado. Se redactó `BRIEFING-MODULO-5-RENOVACIONES-RESERVAS.md` como paso previo
obligatorio, identificando y resolviendo dentro del propio briefing una decisión de diseño no
cubierta explícitamente por el dominio (**Decisión D-13**: el proyecto no tiene ningún parámetro de
horario de apertura/cierre, solo días de atención — se decidió tratar cada día de atención como un
bloque continuo de 24 horas hacia la ventana de retiro de 48 horas de RN-05, saltando por completo
los días que no son de atención, sin inventar un requisito de horario no solicitado) y tres riesgos
técnicos (R-1: la lógica de asignación de reserva, antes parcial e inline en el Módulo 4, se
centraliza para reutilizarla desde el futuro Módulo 7; R-2: ninguna tarea programada vence
automáticamente una reserva vencida, eso es Módulo 7; R-3: los estados `retirada`/`cancelada` de
Reserva quedan sin pantalla dedicada, no exigido por los criterios de este módulo).

Entregado, en 6 pasos (más un Paso 7 documental cerrando el módulo, aquí):

- **Paso 1 (constantes de estado + algoritmo de ventana) — código escrito:** `Reserva` agrega las 5
  constantes de estado (`ESTADO_PENDIENTE`, `ESTADO_PERSONAL_ALERTADO`, `ESTADO_RETIRADA`,
  `ESTADO_VENCIDA_POR_NO_RETIRO`, `ESTADO_CANCELADA`, ya documentadas como comentario desde la
  migración del Módulo 1, ahora formalizadas como código) y el método estático puro
  `calcularFechaLimiteRetiro()`, que implementa la Decisión D-13: avanza un cursor de tiempo día por
  día, consumiendo horas de la ventana solo en los días de atención configurados y saltando
  completos los que no lo son, sin resetear la hora del día al saltar (preserva la hora de la
  alerta).
- **Paso 2 (centralización de la asignación de reservas) — código escrito:**
  `Libro::asignarSiguienteReserva(Ejemplar $ejemplar)` (nuevo): busca la reserva `pendiente` más
  antigua del libro, la pasa a `personal_alertado`, calcula su `fecha_limite_retiro` con el método
  del Paso 1 y los parámetros ya sembrados (`VENTANA_RETIRO_RESERVA_HORAS`,
  `DIAS_ATENCION_AL_PUBLICO`), y registra el ejemplar asignado. `PrestamoController::devolver()`
  (Módulo 4) se refactorizó para usar este método en vez de su lógica inline anterior — verificado
  que el comportamiento observable no cambia (mismo test de regresión de Módulo 4 sigue
  ejerciéndolo).
- **Paso 3 (renovación de préstamo) — código escrito:** `PrestamoController::renovar()` (nuevo):
  aplica RN-03 (bloquea si el libro tiene una reserva en cualquiera de los dos estados activos,
  `pendiente` o `personal_alertado`, rechazando con el nombre del socio que la generó) y RN-19 (la
  fecha de vencimiento se recalcula desde la fecha de renovación, no se extiende desde la anterior;
  se crea un registro de `Renovacion` con la fecha de vencimiento anterior preservada; el préstamo
  no cambia de estado). Sin límite de renovaciones consecutivas — la única condición es la ausencia
  de reservas activas, tal como exige el criterio de aceptación.
- **Paso 4 (alta de reserva) — código escrito:** `ReservaController` (nuevo, namespace
  `App\Http\Controllers\Prestamos`), rutas anidadas bajo Libro (`catalogo.libros.reservas.*`, solo
  `create`/`store` — la reserva es sobre el título, no sobre un ejemplar puntual). Valida que el
  socio no tenga ya una reserva activa (`pendiente` o `personal_alertado`) para el mismo libro antes
  de crear la nueva.
- **Paso 5 (puntos de entrada en la UI existente) — código escrito:** enlace "Reservar" en
  `catalogo.libros.show`; botón "Renovar" (con manejo del mensaje de rechazo) y columna "Retirar
  antes de" en `socios.socios.show` (vista de mostrador); nueva sección "Reservas para retirar" en
  la pantalla de devolución (`prestamos.devolucion.buscar`), que hace de panel del mostrador exigido
  por el criterio de aceptación 6 — sin crear ninguna pantalla nueva de "panel de alertas" propia,
  reutilizando las dos vistas ya existentes donde el personal ya opera.
- **Refactor RN-21 (heredada del Módulo 2):** `EjemplarController::dejaReservasSinSatisfacer()`
  reemplaza el literal `'pendiente'` por la constante `Reserva::ESTADO_PENDIENTE` del Paso 1 — sin
  cambio de comportamiento, solo elimina el string mágico que quedaba pendiente desde que esa
  constante no existía todavía.
- **Paso 6 (tests Feature y unitarios) — código escrito:** `tests/Unit/
  ReservaCalcularFechaLimiteRetiroTest.php` (4 tests unitarios puros, sin base de datos, sobre el
  algoritmo del Paso 1: margen en el mismo día, cruce completo de fin de semana, último día hábil de
  la semana, y alerta producida en un día no hábil); `tests/Feature/Prestamos/RenovacionTest.php` (4
  tests: rechazo con reserva `pendiente`, rechazo también con `personal_alertado`, éxito sin
  reservas con verificación de RN-19, ausencia de límite de renovaciones); `tests/Feature/Prestamos/
  ReservaTest.php` (4 tests: alta exitosa, rechazo de una segunda reserva activa del mismo socio y
  libro, una reserva ya resuelta no cuenta como activa, y verificación de integración de que la
  asignación automática del Paso 2 se refleja en el panel de devolución con su fecha límite); y 2
  tests nuevos en `tests/Feature/Prestamos/AccesoPrestamosTest.php` (control de acceso por rol sobre
  la nueva ruta de alta de reserva, mismo patrón que el resto del archivo) — 14 tests nuevos en
  total.
- **Preparación para revisión funcional:** `RenovacionesReservasDemoSeeder` (nuevo, registrado en
  `DatabaseSeeder` al final, después de `PrestamosDemoSeeder`) y `docs/REVISION-MODULO-5.md`
  (nuevo). El seeder cubre un préstamo renovable sin reservas, uno bloqueado por una reserva
  pendiente ajena, un libro reservable todavía sin reservas, y una reserva ya en
  `personal_alertado` con su fecha límite ya calculada — el criterio de asignación automática "en
  vivo" (RN-05 disparándose durante la propia revisión) se ejercita reutilizando el caso ya sembrado
  por `PrestamosDemoSeeder` (Módulo 4), sin duplicar datos.

**Primera ejecución real (2026-07-16):** la Comisión Directiva corrió `php artisan db:seed
--class=RenovacionesReservasDemoSeeder` seguido de `php artisan test` sobre los cuatro archivos de
este módulo, en dos tandas (la primera intentó tres `--filter` encadenados, lo que reveló que
`artisan test` solo conserva el último `--filter` cuando se repite la opción — corregido apuntando
a los archivos directamente): `AccesoPrestamosTest` → `6 passed (7 assertions)`;
`RenovacionTest` + `ReservaTest` + `ReservaCalcularFechaLimiteRetiroTest` → `12 passed (31
assertions)`. **Total: `18 passed (38 assertions)`, sin fallos.** Igual que los Módulos 3 y 4, no
se encontró ningún defecto de código — los 14 tests nuevos de este módulo pasaron sin necesidad de
corrección alguna.

### Módulo 6 — Excepciones y restricciones: código completo (2026-07-16)

Con el Módulo 5 formalmente cerrado con evidencia objetiva, la Comisión Directiva confirmó el `git
push origin main` y otorgó nuevamente autonomía para actuar según el estado real del proyecto. Se
revisó DA-08 (Excepciones y restricciones es el módulo #6, con precondición explícita "Módulos 3 y
4 completos" — ambos ya cerrados), el Plan de Implementación v2 (RN-06, RN-07, RN-09, RN-10, RN-11,
D-03) y el estado real del código (`ExcepcionAutorizada` y `RestriccionSocio` ya existían completas
desde el Módulo 1, con su validación al prestar ya implementada desde el Módulo 4 — solo faltaba el
CRUD de gestión), concluyendo que correspondía iniciar Módulo 6 tal como estaba programado. Se
redactó `BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md` como paso previo obligatorio,
identificando y resolviendo dentro del propio briefing cinco decisiones de arquitectura (D-14 a
D-18: formalización de constantes de estado/tipo ya documentadas solo como comentario, estado
derivado por cómputo — Decisión D-15, análoga a D-09 — y centralización de la consulta de
vigencia) y cuatro riesgos (R-1 a R-4: ausencia deliberada de tarea programada de vencimiento,
falta de un campo real de "código de socio" para representar el caso histórico del relevamiento,
seeder production-safe separado de los `*DemoSeeder`, y dos middlewares de rol distintos dentro del
mismo módulo).

Entregado, en 7 pasos:

- **Paso 1 (ajustes de base) — código escrito:** constantes `ExcepcionAutorizada::ESTADO_VIGENTE`/
  `ESTADO_VENCIDA`/`ESTADO_REVOCADA` y la relación `revocadoPor()` (D-14, faltante pese a que la
  columna ya existía desde el Módulo 1); `RestriccionSocio::TIPO_AUTOMATICA`/`TIPO_MANUAL` (D-16),
  reemplazando el literal `'automatica'` que ya usaba `PrestamoController::devolver()` (Módulo 4);
  trait `Auditable` agregado a `RestriccionSocio` (D-17) — hasta este módulo todas sus filas eran
  generadas por el sistema, la restricción manual introduce la primera vía de creación humana.
- **Paso 2 (centralización de la consulta de vigencia) — código escrito:**
  `ExcepcionAutorizada::vigentePara($entidad, $tipo)` (D-18), extraída del método privado
  `PrestamoController::tieneExcepcionVigente()` (eliminado) y reutilizada también por
  `Ejemplar::puedeSalirDeLaBiblioteca()` (antes duplicaba la misma consulta inline) — sin cambio de
  comportamiento observable, verificado contra la suite existente de los Módulos 2 y 4.
- **Paso 3 (CRUD de Excepciones Autorizadas) — código escrito:** `ExcepcionController` (namespace
  `App\Http\Controllers\Excepciones`), rutas `excepciones.*` con `role:administrador` únicamente
  (RN-10) — `index` (listado con filtros por tipo y entidad afectada), `create`/`store` (CU-1, con
  `ExcepcionAutorizada::ENTIDADES_POR_TIPO` como única fuente de verdad del mapeo tipo→entidad, D-03),
  `revocar` (CU-2). RN-11: `autorizado_por`/`fecha_autorizacion` los fija siempre el servidor, nunca
  el formulario.
- **Paso 4 (alta y listado de Restricciones manuales) — código escrito:** `RestriccionController`
  (namespace `App\Http\Controllers\Restricciones`), anidado bajo Socio
  (`socios/{socio}/restricciones`), con `role:administrador,personal` (CU-3) — a diferencia del
  grupo `excepciones.*`, solo Administrador (Riesgo R-4: dos middlewares de rol distintos dentro del
  mismo módulo). Salvaguarda de integridad agregada (no una regla de negocio nueva): no se permite
  crear una segunda restricción activa simultánea sobre el mismo socio.
- **Paso 5 (puntos de entrada en la UI existente) — código escrito:** enlaces "Restricciones" y
  "Excepciones" (este último solo para Administrador) en `socios.socios.show`; enlace "Excepciones"
  por ejemplar en `catalogo.libros.show`, visible solo cuando la modalidad de acceso es "Restringido
  a autorización" (RN-09); enlace de navegación "Excepciones" en `layouts/app.blade.php`, dentro del
  bloque ya existente de Administrador. `ExcepcionController::index()` se extendió con un filtro
  adicional `entidad_afectada_id` para poder enlazar "excepciones sobre esta entidad puntual" sin
  construir ninguna pantalla de navegación nueva.
- **Paso 6 (migración de casos históricos) — código escrito:** `ExcepcionesHistoricasSeeder`
  (nuevo, registrado en `DatabaseSeeder`), a diferencia de todos los `*DemoSeeder` de módulos
  anteriores, sin guarda de entorno de producción (R-3: el caso histórico es una decisión real ya
  tomada por la Comisión Directiva, no un dato ficticio) e idempotente vía `firstOrCreate`. Migra el
  caso 7.2 del relevamiento (excepción individual de penalización, motivo "Colaboración histórica
  con la institución", vigencia indefinida); el caso 7.1 (socios honorarios) no requirió ninguna
  acción — ya estaba cubierto desde el Módulo 1 vía `TipoSocioSeeder`. Limitación documentada (R-2):
  el dominio no tiene ningún campo de "código de socio" para representar literalmente el
  identificador informal del relevamiento — el seeder usa un `Socio` placeholder identificable por
  DNI y motivo, con instrucciones explícitas en el propio archivo sobre cómo ajustarlo antes de un
  despliegue con datos reales.
- **Paso 7 (tests Feature + unitarios, y cierre documental) — código escrito:** 5 archivos nuevos,
  26 tests en total —
  `tests/Feature/Excepciones/AccesoExcepcionesTest.php` (6, control de acceso RN-10),
  `tests/Feature/Excepciones/ExcepcionAutorizadaTest.php` (6, criterios de aceptación 2/3/5/6),
  `tests/Feature/Restricciones/AccesoRestriccionesTest.php` (5, control de acceso CU-3/R-4),
  `tests/Feature/Restricciones/RestriccionSocioTest.php` (4, alta manual y listado), y
  `tests/Unit/ExcepcionAutorizadaEstadoVisibleTest.php` (5, unitario puro sobre `estadoVisible()`,
  D-15). El criterio de aceptación 4 (socio con excepción de exención vigente puede recibir
  préstamo con restricción activa) ya estaba cubierto desde el Módulo 4 por `RegistroPrestamoTest` y
  no se duplicó (D-18 no cambió ningún comportamiento observable de ese test). Documentación de
  cierre: `docs/REVISION-MODULO-6.md` (nuevo).

**Pendiente de ejecución real:** a diferencia de los Módulos 2 a 5, este módulo todavía no tiene
ninguna corrida reportada por el usuario contra PHP/PostgreSQL reales (ver `ADR-002`) — el comando
exacto a correr y reportar está en `docs/REVISION-MODULO-6.md`, sección 1.

## Decisión

Módulo 1 queda **cerrado**: código, migraciones, seeders y suite de tests completa ejecutados con
éxito contra PHP 8.5 y PostgreSQL 16 reales (`ADR-006`/`ADR-007`/`ADR-008`), y con su historial
consolidado en un único repositorio (`nexora`), publicado en GitHub (`ADR-009`/`ADR-010`). El único
punto pendiente — el pre-checklist de infraestructura (punto 4) — es no bloqueante y no impide
iniciar el Módulo 2.

**Nota de gestión (2026-07-14):** ante la observación de que las últimas iteraciones se concentraron
en instalación y validación de herramientas (MCP de Postgres, Desktop Commander MCP, incidente SSL),
se hizo una evaluación objetiva: no existe ningún bloqueo real para el producto (Módulo 1 validado
en verde; el pre-checklist de infraestructura no es requisito de Módulo 2), y las tareas de tooling
pendientes (`ADR-011`, pasos 3-9 de 9) son mejoras de entorno de desarrollo, no requisitos del
roadmap. Se decidió pausar ese tooling sin cerrarlo (queda documentado y retomable) y avanzar
directamente con la implementación del Módulo 2 — Catálogo.

**Módulo 2 — Catálogo: cerrado (2026-07-14).** Los 8 pasos del plan de implementación recomendado
por `BRIEFING-MODULO-2-CATALOGO.md` están completos: CRUD de Autor, Editorial, Categoría (con
validación de profundidad máxima bidireccional), Libro y Ejemplar; búsqueda de catálogo; vista de
detalle de Libro; validación RN-21; y la suite de tests Feature correspondiente. Se obtuvieron tres
ejecuciones reales sucesivas (`php artisan test --filter=Catalogo`, entorno del usuario, el mismo
que validó el Módulo 1): la primera (`1 failed, 26 passed`) y la segunda (`1 failed, 30 passed`)
revelaron dos defectos reales y distintos en el mismo método (`Libro::scopeConEstado()` /
`Ejemplar::movimientosInternos()`-`custodiasExternas()`), ambos preexistentes — uno del Módulo 1, el
otro del Paso 5 de este módulo — y ninguno introducido por las correcciones mismas. Corregidos y
documentados en `ADR-012`. La tercera ejecución, tras pushear ambos fixes: **`31 passed (87
assertions)`, sin fallos.** El Módulo 2 cumple el mismo estándar de cierre que el Módulo 1
(`ADR-006`): código completo, ejecutado contra PHP/PostgreSQL reales, suite en verde. Único punto
diferido, no bloqueante: R-1 (historial de condición física por ejemplar), pendiente de una
decisión de diseño (entidad versionada vs. sobrescritura de campo) que no corresponde tomar
unilateralmente — ver `BRIEFING-MODULO-2-CATALOGO.md`, sección "Recomendación".

**Módulo 3 — Socios: cerrado (2026-07-14).** Los 7 pasos del plan de implementación recomendado por
`BRIEFING-MODULO-3-SOCIOS.md` están completos: CRUD de Tipo de Socio y Socio, búsqueda tolerante a
acentos (extensión `unaccent`), vista de mostrador con préstamos/reservas/restricción/atrasos,
historial paginado, y la suite de tests Feature correspondiente, más seeder de demostración y guía
de revisión funcional (`docs/REVISION-MODULO-3.md`). Primera ejecución real (`php artisan migrate`
+ `php artisan test --filter=Socios`, mismo entorno que validó los Módulos 1 y 2): **`11 passed (25
assertions)`, sin fallos** — a diferencia del Módulo 2, no se encontró ningún defecto en esta
primera corrida, cerrando el módulo sin necesidad de corrección. Ningún riesgo identificado en el
briefing (R-1, R-2, R-3) quedó pendiente de decisión: los tres se resolvieron o se documentaron
como no bloqueantes dentro del propio briefing.

**Módulo 4 — Préstamos y devoluciones: cerrado (2026-07-15).** Los 6 pasos del plan de
implementación recomendado por `BRIEFING-MODULO-4-PRESTAMOS.md` están completos: registro de
préstamo (RN-01, RN-02, RN-04, RN-06, RN-08/RN-09, RN-13), devolución (RN-12, RN-18, RN-07, alerta
de reserva pendiente), puntos de entrada desde Catálogo y Socios, y la suite de tests Feature
correspondiente (21 tests en 3 archivos), más seeder de demostración y guía de revisión funcional
(`docs/REVISION-MODULO-4.md`). Primera ejecución real (`php artisan db:seed
--class=PrestamosDemoSeeder` + `php artisan test --filter=Prestamos`, mismo entorno que validó los
Módulos 1, 2 y 3): **`21 passed (59 assertions)`, sin fallos** — igual que el Módulo 3, no se
encontró ningún defecto de código en esta primera corrida. Ningún riesgo identificado en el
briefing (R-1, R-2, R-3) quedó pendiente de decisión de producto o dominio: los tres son de
naturaleza técnica y de alcance, resueltos o documentados como no bloqueantes dentro del propio
briefing. Único hallazgo de esta revisión: un error de conteo de tests en la documentación (19 en
vez de 21), sin impacto en el código, corregido en este documento y en
`docs/REVISION-MODULO-4.md`.

**Módulo 5 — Renovaciones y reservas: cerrado (2026-07-16).** Los 6 pasos del plan de
implementación recomendado por `BRIEFING-MODULO-5-RENOVACIONES-RESERVAS.md` están completos:
renovación de préstamo con bloqueo por reserva activa (RN-03) y actualización de vencimiento
(RN-19), alta de reserva con validación de duplicados, asignación automática de la reserva más
antigua al devolver un ejemplar con cálculo de la ventana de retiro de atención al público (RN-05,
Decisión D-13), refactor de RN-21 a la constante de estado, puntos de entrada en la UI existente,
y la suite de tests correspondiente (14 tests nuevos en 4 archivos, más los 4 preexistentes de
`AccesoPrestamosTest`), más seeder de demostración y guía de revisión funcional
(`docs/REVISION-MODULO-5.md`). Primera ejecución real (`php artisan db:seed
--class=RenovacionesReservasDemoSeeder` + `php artisan test` sobre los cuatro archivos, mismo
entorno que validó los Módulos 1 a 4): **`18 passed (38 assertions)`, sin fallos** — igual que los
Módulos 3 y 4, no se encontró ningún defecto de código en esta primera corrida. Único hallazgo:
`artisan test` no admite `--filter` repetido (conserva solo el último valor) — corregido apuntando
a los archivos de test directamente, sin impacto en el código de la aplicación. Ningún riesgo
identificado en el briefing (D-13, R-1, R-2, R-3) quedó pendiente de decisión de producto o
dominio: todos se resolvieron o se documentaron como no bloqueantes dentro del propio briefing.

**Módulo 6 — Excepciones y restricciones: código completo, no cerrado (2026-07-16).** Los 7 pasos
del plan de implementación recomendado por `BRIEFING-MODULO-6-EXCEPCIONES-RESTRICCIONES.md` están
completos: CRUD de `ExcepcionAutorizada` restringido a Administrador (RN-10, RN-11), alta y listado
de `RestriccionSocio` manual (CU-3), centralización de la consulta de vigencia (D-18), puntos de
entrada en la UI existente, migración production-safe del caso histórico del relevamiento (7.2), y
la suite de tests correspondiente (26 tests nuevos en 5 archivos), más guía de revisión funcional
(`docs/REVISION-MODULO-6.md`). A diferencia de los Módulos 2 a 5, **este módulo todavía no tiene
ninguna ejecución real reportada** contra PHP/PostgreSQL — no puede declararse cerrado hasta
obtener esa evidencia (mismo estándar de cierre exigido a todos los módulos anteriores, `ADR-006`).
Ningún riesgo identificado en el briefing (R-1 a R-4) quedó pendiente de decisión de producto o
dominio: los cuatro se resolvieron o se documentaron como no bloqueantes dentro del propio
briefing; el único punto abierto (R-2, la falta de un campo real para el código de socio del
relevamiento) es una limitación de datos del entorno, documentada para que la Comisión Directiva la
resuelva al desplegar en producción, no un defecto de diseño.
