# Briefing técnico — Módulo 2: Catálogo

**Fecha:** 2026-07-13
**Elaborado por:** Equipo de desarrollo, a partir exclusivamente de documentación EOS oficial aprobada.
**Estado:** Para revisión — ver "Recomendación" al final. No se ha implementado ninguna funcionalidad de este módulo.

---

## Fuentes utilizadas (documentación EOS oficial, v2 aprobada)

Por instrucción explícita, este briefing reconstruye únicamente el contexto necesario para el Módulo 2, no el contexto completo del proyecto. Fuentes consultadas:

- `Fase 2 - Domain Modeling/entregables/modelo-de-dominio-v2.md` — Área 1: Catálogo (entidades, atributos, reglas de diseño).
- `Fase 3 - Architecture/entregables/plan-implementacion-fase1-v2.md` — sección "Módulo 2 — Catálogo" (alcance, reglas cubiertas, criterios de aceptación), estrategia de testing, DA-08.
- `Fase 3 - Architecture/entregables/propuesta-arquitectura-v2.md` — DA-02, DA-03 (enmendada), DA-06, DA-07, DA-08, DA-09.
- Código ya escrito en `sistema-gestion-bibliotecaria/` (Módulo 1): migraciones y modelos Eloquent de las 7 entidades del Catálogo, verificados por lectura directa, no asumidos.

No se releyó el Relevamiento (Fase 1) ni las Fases 4/5 completas: no aportan reglas ni decisiones adicionales sobre el Catálogo más allá de lo ya incorporado al Modelo de Dominio y al Plan de Implementación v2.

---

## 1. Objetivo funcional del módulo

Gestionar el acervo bibliográfico completo de la institución: el registro de obras (Libro) independiente de sus copias físicas (Ejemplar), junto con la información descriptiva que las organiza (Autor, Editorial, Categoría). Debe permitir al personal cargar, buscar y mantener el catálogo completo sin depender de planillas de Excel, y dejar el estado de cada ejemplar (disponible, prestado, en reparación, etc.) visible sin necesidad de consultar otras pantallas.

## 2. Alcance

**Incluido:**
- CRUD de Autor, Editorial, Categoría (jerárquica, máximo 2 niveles).
- CRUD de Libro (título, ISBN no único/no obligatorio, año, edición, idioma, descripción, autores M:N, editorial, categorías M:N).
- CRUD de Ejemplar (vinculado a Libro; estado manual En reparación/Extraviado; modalidad de acceso Libre circulación/Solo sala/Restringido a autorización; condición física; fecha de ingreso; origen).
- Búsqueda de catálogo por título (parcial), autor, categoría, estado y modalidad.
- Vista de Libro con listado de sus ejemplares y estado actual de cada uno (incluyendo estados derivados de movimientos activos).
- Validación RN-21 al cambiar la modalidad de acceso de un ejemplar.
- Historial de condición física por ejemplar — **ver punto 8, riesgo R-1: requiere una decisión antes de implementarse.**

**Explícitamente fuera de este módulo** (per DA-07/DA-08): circulación real (préstamos, devoluciones — Módulo 4), gestión completa de reservas y su cola de espera (Módulo 5), informes (Módulo 9), migración de datos (Módulo 10). El Catálogo debe dejar el modelo de datos listo para que esos módulos lo consuman, pero no implementa su lógica.

## 3. Reglas de negocio aplicables

| Código | Regla | Aplicación en este módulo |
|---|---|---|
| RN-08 | Un ejemplar "Solo sala" no puede participar en préstamos/movimientos de salida. | El Catálogo define y persiste la modalidad; el *enforcement* real ocurre en Módulo 4, pero requiere cobertura de test aquí también según la tabla de tests obligatorios del plan v2. |
| RN-09 | Un ejemplar "Restringido a autorización" requiere Excepción Autorizada vigente para salir. | Igual que RN-08: el Catálogo define la modalidad; el enforcement es de Módulo 4/6. |
| RN-21 | Alertar al personal si un cambio de modalidad deja reservas pendientes insatisfacibles. | Implementada **en este módulo**, sobre el cambio de modalidad de Ejemplar. Requiere leer el estado de `Reserva` (ver dependencias, sección 6). |
| RN-04 (indirecta) | Un ejemplar solo puede tener un movimiento activo a la vez. | No se implementa aquí (ya implementada en Módulo 1 vía `Ejemplar::tieneMovimientoActivo()`); el Catálogo la **consume** para mostrar el estado derivado, no la modifica. |
| D-02 | Separación Libro/Ejemplar. | Ya reflejada en el modelo de datos existente. |
| D-06 | Clasificación propia jerárquica, máximo 2 niveles. | A implementar como validación en la creación/edición de Categoría. |
| D-07 | El ISBN no es identificador único. | Implica que cualquier búsqueda o autocompletado por ISBN debe admitir múltiples resultados. |
| D-09 | Estado de Ejemplar parcialmente derivado. | Ya implementado en Módulo 1 (`Ejemplar::estadoActual()`); el Catálogo solo lo consume para mostrarlo en las vistas. |

## 4. Entidades del dominio involucradas

Las 7 entidades ya tienen migración y modelo Eloquent escritos en Módulo 1 (verificado por lectura directa del código, no asumido):

| Entidad | Migración | Modelo | Relaciones ya definidas |
|---|---|---|---|
| Autor | ✔ | ✔ | `belongsToMany(Libro)` |
| Editorial | ✔ | ✔ | `hasMany(Libro)` |
| Categoría | ✔ | ✔ | auto-relación padre/subcategorías + `puedeSerPadre()` (helper de validación de profundidad, no conectado aún a ninguna UI) |
| Libro | ✔ | ✔ | `belongsTo(Editorial)`, `belongsToMany(Autor)`, `belongsToMany(Categoria)`, `hasMany(Ejemplar)` |
| LibroAutor (pivote) | ✔ | — (tabla pivote, sin modelo propio, correcto para este caso) | — |
| LibroCategoria (pivote) | ✔ | — (ídem) | — |
| Ejemplar | ✔ | ✔ | `belongsTo(Libro)`, `estadoActual()` y `tieneMovimientoActivo()` ya implementados (D-09, RN-04 Nivel 2) |

**Conclusión de esta verificación:** el trabajo del Módulo 2 no parte de cero. Migraciones y modelos base ya existen y están alineados con el Modelo de Dominio v2. Lo que falta por completo es la capa de controladores, rutas, formularios de validación (FormRequests), vistas Blade/Alpine, búsqueda y tests — confirmado por búsqueda directa en el código: cero controladores, rutas o vistas de Catálogo existen hoy.

## 5. Casos de uso comprendidos

- **CU-1:** Alta de Libro con uno o más autores, editorial opcional y una o más categorías.
- **CU-2:** Alta de Ejemplar vinculado a un Libro existente, con modalidad de acceso y origen.
- **CU-3:** Búsqueda de catálogo por título (parcial), autor, categoría, estado o modalidad.
- **CU-4:** Vista de detalle de Libro con el listado de sus ejemplares y el estado actual de cada uno.
- **CU-5:** Edición de Ejemplar (modalidad de acceso, estado manual, condición física), con validación RN-21 al cambiar modalidad.
- **CU-6:** Alta/edición de Categoría con validación de profundidad máxima (2 niveles).
- **CU-7:** CRUD simple de Autor y Editorial.
- **CU-8:** Registro de historial de condición física por ejemplar — **pendiente de definición, ver sección 8**.

## 6. Dependencias con otros módulos

- **Módulo 1 (Infraestructura):** precondición dura, ya cerrada y verificada en verde (38/38 tests). Provee autenticación, roles, middleware y layout base que el Módulo 2 reutiliza sin modificarlos.
- **Módulo 4 (Préstamos y devoluciones):** dependencia inversa — Módulo 4 consumirá `Ejemplar.modalidad_acceso`, `estadoActual()` y `tieneMovimientoActivo()` tal como el Módulo 2 los deje definidos. El Módulo 2 no debe alterar la firma de estos métodos sin coordinar con el diseño de Módulo 4.
- **Módulo 5 (Renovaciones y reservas):** dependencia de **lectura** no documentada explícitamente en DA-06 — ver riesgo R-2. RN-21 exige consultar si existen `Reserva` en estado `pendiente` o `personal_alertado` para un Libro. El esquema (migración + modelo `Reserva`, con índice `[libro_id, estado]`) ya existe desde Módulo 1, por lo que esta dependencia es solo de lectura sobre datos ya modelados, no requiere que la lógica de cola de espera de Módulo 5 esté implementada.
- **Módulo 9 (Informes) y Módulo 10 (Migración de datos):** dependencia hacia adelante — ambos requieren que el Catálogo exista y esté estable, pero no condicionan el inicio de Módulo 2.

## 7. Decisiones de arquitectura que condicionan la implementación

- **DA-02 (monolito modular):** el Catálogo se implementa como módulo de responsabilidades delimitadas dentro de la misma aplicación Laravel, sin API separada.
- **DA-03 (enmendada por ADR-007):** Laravel 12 (no 11), Blade + Alpine.js, PostgreSQL 16 — stack ya validado end-to-end en Módulo 1 (38/38 tests, `composer create-project`, `migrate --seed`, `serve` verificados).
- **DA-06 (dependencias entre módulos):** el Catálogo figura sin dependencias ("Ninguna"). Esto es consistente con la implementación salvo por la lectura de `Reserva` que exige RN-21 (ver R-2).
- **DA-07 (alcance de Fase 1):** el Catálogo está dentro del alcance de la primera entrega.
- **DA-08 (secuencia de construcción):** orden #2, inmediatamente después de Infraestructura — es el segundo módulo a construir, antes de Socios y Circulación.
- **DA-09 (invariante RN-04, dos niveles):** ya implementada en Módulo 1 sobre `Ejemplar`; el Módulo 2 la consume para mostrar estado pero no la reimplementa.

## 8. Riesgos técnicos identificados

**R-1 — Información faltante: "Historial de condición física" no está modelado (bloqueante para esa funcionalidad puntual, no para el resto del módulo).**
El Plan de Implementación v2 pide explícitamente "Historial de condición física por ejemplar (registro de notas ingresadas en devoluciones)" como parte de lo que implementa el Módulo 2. Sin embargo, el Modelo de Dominio v2 (Área 1.5, Ejemplar) define `condicion_fisica` como un único campo de texto, no como una entidad de historial versionado, y la migración ya escrita en Módulo 1 (`ejemplares_table`) refleja exactamente eso: una sola columna, sin tabla de historial asociada. Además, el propio texto del plan ("notas ingresadas en devoluciones") sugiere que el origen de estas notas es el flujo de devolución de Módulo 4, no el CRUD de Ejemplar de Módulo 2 — lo cual no está aclarado en ningún documento. **No se puede diseñar esta funcionalidad sin inventar una estructura de datos que el Modelo de Dominio no define.** Se documenta como pendiente de decisión (ver Recomendación) y se excluye del plan de implementación hasta resolverse; el resto del módulo no depende de ella.

**R-2 — DA-06 no documenta la dependencia de lectura del Catálogo hacia `Reserva` (Módulo 5).**
No es una contradicción bloqueante — la validación RN-21 solo necesita leer `reservas.estado` filtrado por `libro_id`, y ese esquema ya existe. Pero es una omisión real entre la arquitectura documentada y el requisito funcional del plan de implementación. Recomendación: dejar constancia en este briefing (hecho) y, al cerrar el módulo, agregar una nota aclaratoria a DA-06 con referencia a este documento, sin necesidad de reabrir la decisión completa.

**R-3 — Validación de profundidad de Categoría (D-06) es solo de aplicación, no de base de datos.**
El helper `Categoria::puedeSerPadre()` ya existe pero no está conectado a ningún controlador ni FormRequest todavía, y no hay ninguna restricción a nivel de PostgreSQL que impida insertar una subcategoría de tercer nivel por una vía distinta al formulario (por ejemplo, un script de importación de Módulo 10). Mitigación recomendada: encapsular la validación en un `FormRequest` reutilizable y exigir que cualquier inserción futura de categorías (incluida la migración de datos) pase por la misma validación o por el mismo modelo Eloquent, nunca por SQL directo.

**R-4 — El ISBN no es único (D-07).**
Cualquier búsqueda, autocompletado o validación por ISBN debe diseñarse asumiendo múltiples resultados posibles. No usarlo como clave de negocio en ningún flujo (por ejemplo, no usarlo para detectar "libro duplicado" al cargar uno nuevo).

**R-5 — RN-21 no figura en la tabla de "tests de reglas de negocio obligatorios" del Plan de Implementación v2, pese a ser un criterio de aceptación explícito del Módulo 2.**
La tabla de tests obligatorios del plan v2 lista 10 reglas (incluida RN-08) pero no incluye RN-21, aunque el propio Anexo de cambios de esa misma versión (C-03) indica que RN-21 fue agregada al Módulo 2 para cubrir un gap detectado en la v1. Se interpreta como una omisión menor de consolidación entre el cuerpo del documento y su propio anexo de cambios, no como una decisión deliberada de excluirla de testing. Recomendación: tratar RN-21 como test obligatorio igualmente, por ser criterio de aceptación explícito del módulo — más estricto que el documento, nunca más laxo.

## 9. Criterios de aceptación

(Transcritos literalmente del Plan de Implementación v2, sección Módulo 2 — Catálogo, sin modificación.)

- El personal puede crear un Libro con múltiples autores, sin ISBN, con categoría y subcategoría.
- El sistema no permite crear una subcategoría cuyo padre ya es subcategoría (profundidad máxima 2).
- El personal puede crear un Ejemplar vinculado a un Libro existente, con modalidad Solo sala.
- La vista del Libro muestra correctamente el estado "Prestado" para un ejemplar con préstamo activo, sin necesidad de campo de estado explícito en la tabla de ejemplares.
- La búsqueda por título parcial devuelve resultados relevantes. La búsqueda por autor devuelve todos los libros del autor.
- Un ejemplar con estado manual "En reparación" muestra ese estado aunque no tenga movimiento activo.
- Al intentar cambiar la modalidad de acceso del único ejemplar disponible de un Libro con reservas Pendientes a Solo sala, el sistema muestra una alerta con las reservas afectadas y requiere confirmación antes de guardar.

## 10. Plan de implementación recomendado

Orden interno sugerido (dentro del Módulo 2, respetando que no tiene dependencias externas salvo la lectura de `Reserva` para RN-21):

1. **Autor y Editorial** — CRUD simple, sin dependencias internas.
2. **Categoría** — CRUD con validación de profundidad máxima, reutilizando `puedeSerPadre()` ya existente, encapsulada en un FormRequest (mitiga R-3).
3. **Libro** — CRUD, con relaciones M:N a Autor/Categoría y belongsTo a Editorial.
4. **Ejemplar** — CRUD vinculado a Libro, reutilizando `estadoActual()` ya implementado para mostrar el estado (no reimplementarlo).
5. **Búsqueda de catálogo** — por título parcial, autor, categoría, estado, modalidad.
6. **Vista de detalle de Libro** — listado de ejemplares con estado actual.
7. **Validación RN-21** — al cambiar modalidad de Ejemplar, consultar `Reserva` por `libro_id` y `estado`.
8. **Tests** — cobertura de los 7 criterios de aceptación, más RN-08/RN-09/RN-21 (tratada como obligatoria pese a R-5) y la validación de profundidad de Categoría.
9. **Historial de condición física** — diferido hasta resolver R-1 (ver Recomendación).

En todos los pasos, reutilizar sin modificar los métodos de dominio ya escritos en Módulo 1 (`Ejemplar::estadoActual()`, `Ejemplar::tieneMovimientoActivo()`, `Categoria::puedeSerPadre()`).

---

## Recomendación

**El equipo considera que existe información suficiente para iniciar el desarrollo del Módulo 2 — Catálogo, con una excepción puntual y acotada.**

Puede comenzarse de inmediato con los pasos 1 a 8 del plan de implementación (Autor, Editorial, Categoría, Libro, Ejemplar, búsqueda, vista de detalle, validación RN-21 y tests), ya que todas las reglas de negocio, entidades y criterios de aceptación involucrados están completamente definidos en la documentación EOS aprobada (v2), y la base de código (migraciones y modelos) ya existe y es consistente con esa documentación.

**Queda pendiente una única decisión antes de implementar el "Historial de condición física por ejemplar" (paso 9, R-1):** el Modelo de Dominio v2 no define esta funcionalidad como entidad, y el Plan de Implementación v2 la menciona sin especificar su estructura de datos ni si la origina el Módulo 2 (CRUD de Ejemplar) o el Módulo 4 (flujo de devolución). Implementarla sin esa definición implicaría inventar un requisito, lo cual está expresamente fuera de lo permitido. Se solicita a quien corresponda (responsable del proyecto / Comisión Directiva, según delegación vigente) definir:

1. ¿Es una entidad nueva versionada (ej. tabla `historial_condicion_fisica` con ejemplar_id, fecha, nota, usuario) o alcanza con sobrescribir el campo actual sin historial?
2. ¿Se origina en el CRUD de Ejemplar (Módulo 2) o en el flujo de devolución (Módulo 4)?

Se recomienda avanzar con los pasos 1 a 8 en paralelo a la resolución de este punto, dado que no lo bloquean.

Adicionalmente, se deja constancia (no bloqueante) de dos observaciones menores para prolijidad documental futura: la omisión de la dependencia de lectura hacia `Reserva` en DA-06 (R-2) y la ausencia de RN-21 en la tabla de tests obligatorios del plan v2 pese a ser criterio de aceptación (R-5). Ninguna de las dos impide iniciar el desarrollo.
