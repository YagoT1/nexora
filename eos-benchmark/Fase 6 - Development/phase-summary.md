# Phase Summary

## Phase

06 — Development

---

## Estado

Módulo 1 (de 10) **cerrado**: entorno validado, proyecto Laravel 12 creado, migrado, sembrado, iniciado y con su suite de tests completa pasando (38/38). Repositorio de código consolidado en un único monorepo — `nexora` (https://github.com/YagoT1/nexora.git) es la fuente única de verdad para código, documentación, trazabilidad e historial del proyecto (`ADR-010`), con el commit de consolidación ya publicado (`515c161`). El entorno temporal de validación (`sgb-laravel/`) fue verificado sin pérdida de contenido y eliminado (`ADR-009`, adenda de cierre). Único pendiente no bloqueante: pre-checklist de infraestructura (ver "Próximo trabajo", punto 4). En curso: preparación del Módulo 2 — Catálogo, mediante briefing técnico previo a cualquier implementación (instrucción explícita del responsable del proyecto).

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

**No ejecutado todavía contra el entorno real** (mismo patrón que el resto del Módulo 2 y que el
Módulo 1 antes de `ADR-006`): este sandbox no dispone de PHP/Composer/PostgreSQL (`ADR-002`). Con
el fix aplicado se esperan 31 tests en verde (27 originales + 4 nuevos), pero esa confirmación
todavía no se obtuvo — es la validación pendiente antes de dar por cerrado el Módulo 2.

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

## Decisión

Módulo 1 queda **cerrado**: código, migraciones, seeders y suite de tests completa ejecutados con
éxito contra PHP 8.5 y PostgreSQL 16 reales (`ADR-0