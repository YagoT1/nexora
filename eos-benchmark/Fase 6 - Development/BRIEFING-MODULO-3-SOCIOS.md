# Briefing técnico — Módulo 3: Socios

**Fecha:** 2026-07-14
**Elaborado por:** Equipo de desarrollo, a partir exclusivamente de documentación EOS oficial aprobada.
**Estado:** Para revisión — ver "Recomendación" al final. No se ha implementado ninguna funcionalidad de este módulo todavía.

---

## Fuentes utilizadas (documentación EOS oficial, v2 aprobada)

Por el mismo criterio de proporcionalidad aplicado en `BRIEFING-MODULO-2-CATALOGO.md`, este briefing reconstruye únicamente el contexto necesario para el Módulo 3, no el contexto completo del proyecto. Fuentes consultadas:

- `Fase 2 - Domain Modeling/entregables/modelo-de-dominio-v2.md` — Área 2: Socios (entidades, atributos), sección de reglas de negocio (RN-01, RN-06, RN-07) y decisiones de diseño (D-04).
- `Fase 3 - Architecture/entregables/plan-implementacion-fase1-v2.md` — sección "Módulo 3 — Socios" (alcance, reglas cubiertas, criterios de aceptación), DA-08.
- `Fase 3 - Architecture/entregables/propuesta-arquitectura-v2.md` — DA-02, DA-03, DA-06, DA-07, DA-08.
- Código ya escrito en `sistema-gestion-bibliotecaria/` (Módulo 1): migraciones y modelos Eloquent de `TipoSocio`, `Socio`, `RestriccionSocio`, `HistorialAtraso`, `Reserva`, `PrestamoDomiciliario`, verificados por lectura directa, no asumidos. Seeder `TipoSocioSeeder` (Tipos "Estándar" y "Honorario" ya sembrados).

No se releyó el Relevamiento (Fase 1) ni las Fases 4/5 completas: no aportan reglas ni decisiones adicionales sobre Socios más allá de lo ya incorporado al Modelo de Dominio y al Plan de Implementación v2.

---

## 1. Objetivo funcional del módulo

Gestionar el padrón de socios de la institución y la configuración de los tipos de socio (con sus beneficios y límites), de forma que el personal pueda dar de alta, modificar y consultar socios sin depender de planillas externas, y que la Comisión Directiva pueda ajustar límites y reglas de restricción por tipo de socio sin intervención técnica (D-04).

## 2. Alcance

**Incluido:**
- CRUD de Tipo de Socio: nombre, límite de préstamos simultáneos, sujeto a restricción automática.
- CRUD de Socio: nombre principal, nombres alternativos (lista), DNI, email, teléfono, fecha de alta, estado (Activo/Inactivo), tipo de socio.
- Búsqueda de socios tolerante a variaciones de nombre, sobre nombre principal y nombres alternativos simultáneamente — **ver riesgo R-1, tiene una implicancia técnica que condiciona la implementación.**
- Vista de socio desde el mostrador: préstamos activos, reservas activas, restricción vigente si la hay, cantidad de atrasos en los últimos 12 meses.
- Historial de préstamos del socio (paginado).

**Explícitamente fuera de este módulo** (per DA-06/DA-08): registrar préstamos/devoluciones reales (Módulo 4), gestión de reservas y su cola de espera (Módulo 5), excepciones autorizadas y generación de restricciones (Módulo 6 — aquí solo se **lee** lo que ya exista, no se genera ni gestiona). El módulo de Socios debe dejar el padrón listo para que esos módulos lo consuman, pero no implementa su lógica.

## 3. Reglas de negocio aplicables

| Código | Regla | Aplicación en este módulo |
|---|---|---|
| RN-01 | Un socio no puede tener préstamos domiciliarios activos superiores al límite de su Tipo de Socio; el sistema alerta, no bloquea automáticamente. | El *enforcement* real ocurre en Módulo 4 (al registrar el préstamo). Este módulo solo define y persiste el límite por Tipo de Socio, y lo muestra en la vista de mostrador junto con la cantidad de préstamos activos actuales, para que el personal tenga la información antes de que Módulo 4 la aplique. |
| RN-06 | Un socio con restricción activa no puede recibir préstamos, salvo Excepción Autorizada vigente de tipo "Exención". | El *enforcement* es de Módulo 4/6. Este módulo solo **lee y muestra** si existe una `RestriccionSocio` vigente (`RestriccionSocio::estaActiva()`, ya implementado en Módulo 1), no la crea ni la gestiona. |
| RN-07 | Los socios Honorario no reciben restricciones automáticas por atraso; el atraso se registra igual en el historial y se muestra como alerta. | Ya reflejado en el dato (`TipoSocio::sujeto_a_restriccion_automatica`, sembrado `false` para "Honorario"). Este módulo debe reflejar en la vista de mostrador que un socio Honorario no tiene restricciones activas aunque tenga atrasos — dato, no lógica nueva. |
| D-04 | Los parámetros operativos (límites, plazos, topes) son configurables desde la administración, sin intervención técnica. | El CRUD de Tipo de Socio de este módulo **es** la interfaz de configuración que D-04 exige para el límite de préstamos simultáneos y el flag de restricción automática. |

## 4. Entidades del dominio involucradas

Todas las entidades necesarias ya tienen migración y modelo Eloquent escritos en Módulo 1 (verificado por lectura directa del código, no asumido):

| Entidad | Migración | Modelo | Relaciones/métodos ya definidos |
|---|---|---|---|
| TipoSocio | ✔ | ✔ | `hasMany(Socio)` |
| Socio | ✔ | ✔ | `belongsTo(TipoSocio)`, `hasMany(PrestamoDomiciliario)`, `hasMany(RestriccionSocio)`, `hasMany(HistorialAtraso)`. **Falta:** `hasMany(Reserva)` — inversa de `Reserva::socio()`, que ya existe (mismo patrón que faltó y se agregó para `Libro::reservas()` en Módulo 2, Paso 7). |
| RestriccionSocio | ✔ | ✔ | `belongsTo(Socio)`, `estaActiva(): bool` ya implementado (análogo a `ExcepcionAutorizada::estaVigente()`) |
| HistorialAtraso | ✔ | ✔ | `belongsTo(Socio)`, con `fecha_devolucion_efectiva` propia (permite filtrar "últimos 12 meses" sin depender de `created_at`) |
| Reserva | ✔ | ✔ | `belongsTo(Socio)`, `belongsTo(Libro)` — falta únicamente la inversa en `Socio`, señalada arriba |
| PrestamoDomiciliario | ✔ | ✔ | `belongsTo(Socio)` (vía `Socio::prestamosDomiciliarios()`, ya existe) |

**Conclusión de esta verificación:** igual que en Módulo 2, el trabajo no parte de cero — el modelo de datos completo ya existe y está alineado con el Modelo de Dominio v2, incluyendo métodos de dominio ya reutilizables (`RestriccionSocio::estaActiva()`). Lo que falta por completo es la capa de controladores, rutas, FormRequests, vistas, búsqueda y tests — confirmado por búsqueda directa: cero controladores, rutas o vistas de Socios existen hoy. Único ajuste puntual al modelo existente: agregar `Socio::reservas()`.

## 5. Casos de uso comprendidos

- **CU-1:** Alta/edición de Tipo de Socio (nombre, límite, flag de restricción automática).
- **CU-2:** Alta/edición de Socio (datos personales, nombres alternativos, tipo de socio, estado).
- **CU-3:** Búsqueda de socios tolerante a variaciones de nombre (mayúsculas/minúsculas y acentos), sobre nombre principal y nombres alternativos.
- **CU-4:** Vista de mostrador de un Socio: préstamos activos, reservas activas, restricción vigente, atrasos en los últimos 12 meses.
- **CU-5:** Historial paginado de préstamos del socio.

## 6. Dependencias con otros módulos

- **Módulo 1 (Infraestructura):** precondición dura, ya cerrada y verificada en verde (38/38 tests). Provee autenticación, roles y layout que este módulo reutiliza sin modificar.
- **Módulo 4 (Préstamos y devoluciones), Módulo 6 (Excepciones y Restricciones):** dependencia de **lectura**, no documentada explícitamente como tal en DA-06 (mismo tipo de omisión que R-2 en el briefing de Módulo 2, ya señalada y aceptada allí como no bloqueante). La vista de mostrador de este módulo necesita leer `PrestamoDomiciliario` (activos), `Reserva` (activas) y `RestriccionSocio` (vigente) — los tres esquemas y sus métodos de dominio relevantes ya existen desde Módulo 1, por lo que es una dependencia de lectura sobre datos ya modelados, no sobre lógica de negocio de esos módulos (que siguen sin implementarse).
- **Módulo 5 (Renovaciones y reservas):** misma naturaleza que la anterior, respecto de `Reserva`.
- **Módulo 6 (Excepciones y Restricciones), en sentido inverso:** DA-06 declara que Excepciones y Restricciones depende de Socios — es decir, este módulo es una precondición de aquel, no al revés. Consistente con el orden de DA-08 (Socios en el puesto 3, Excepciones y restricciones en el puesto 6).

## 7. Decisiones de arquitectura que condicionan la implementación

- **DA-02 (monolito modular):** Socios se implementa como módulo de responsabilidades delimitadas dentro de la misma aplicación Laravel, sin API separada.
- **DA-03 (enmendada por ADR-007):** Laravel 12, Blade + Alpine.js, PostgreSQL 16 — mismo stack ya validado dos veces (Módulo 1 y Módulo 2, ambos en verde con evidencia real).
- **DA-06 (dependencias entre módulos):** Socios figura sin dependencias ("Ninguna"), consistente con la implementación salvo por las lecturas señaladas en la sección 6.
- **DA-07 (alcance de Fase 1):** Socios está dentro del alcance de la primera entrega.
- **DA-08 (secuencia de construcción):** orden #3, inmediatamente después de Catálogo — antes de Préstamos y devoluciones (que lo requiere como precondición explícita, "Módulos 2 y 3 completos").

## 8. Riesgos técnicos identificados

**R-1 — La búsqueda "tolerante a variaciones de nombre" exige insensibilidad a acentos, no solo a mayúsculas/minúsculas, y el mecanismo estándar de PostgreSQL (`ILIKE`) no la resuelve por sí solo.**
El criterio de aceptación es explícito y verificable: *"La búsqueda por 'Garcia' encuentra socios registrados como 'García', 'GARCIA' y socios cuyo nombre alternativo contiene 'Garcia'."* `ILIKE` resuelve la insensibilidad a mayúsculas/minúsculas, pero trata `á` y `a` como caracteres distintos — sin una capa adicional, buscar "Garcia" (sin tilde) **no** encontraría "García" (con tilde), incumpliendo el criterio literalmente. La extensión estándar de PostgreSQL para esto es `unaccent` (contrib, incluida en toda instalación estándar de PostgreSQL 16, no requiere instalación de paquetes externos — solo `CREATE EXTENSION IF NOT EXISTS unaccent`). Se decide, como parte de este briefing y no como pregunta abierta (es una decisión de implementación técnica para satisfacer un requisito ya explícito, no una decisión de producto o dominio): habilitar `unaccent` vía migración y comparar con `unaccent(columna) ILIKE unaccent('%término%')` tanto para `nombre_principal` como, vía `jsonb_array_elements_text`, para cada elemento de `nombres_alternativos`. Se documenta acá para que quede trazada la razón de la migración que la habilita, no porque requiera aprobación adicional — mismo criterio que R-3/R-4 del briefing de Módulo 2 (riesgos técnicos con mitigación de ingeniería, no decisiones de producto).

**R-2 — Igual que R-2 del briefing de Módulo 2: DA-06 no documenta explícitamente la dependencia de lectura de este módulo hacia `Reserva`, `PrestamoDomiciliario` y `RestriccionSocio`.**
No es bloqueante por el mismo motivo ya aceptado en Módulo 2: son lecturas sobre esquemas y métodos de dominio que ya existen desde Módulo 1, no sobre lógica de esos módulos futuros. Recomendación: al cerrar este módulo, agregar la misma nota aclaratoria a DA-06 que quedó pendiente para R-2 de Módulo 2, consolidando ambas observaciones en una sola actualización en vez de dos notas separadas.

**R-3 — `nombres_alternativos` es una columna `jsonb`, no una tabla relacionada.**
Es la decisión de diseño ya tomada en la migración de Módulo 1 (`socios` con `jsonb` nullable) y es consistente con "campo editable como lista" del plan — no hay contradicción ni riesgo de datos, solo una implicancia técnica: la búsqueda sobre este campo (R-1) requiere una subconsulta sobre los elementos del array JSON, no un simple `LIKE` de columna. Se documenta para que la implementación de búsqueda no lo pase por alto.

## 9. Criterios de aceptación

(Transcritos literalmente del Plan de Implementación v2, sección Módulo 3 — Socios, sin modificación.)

- El Administrador puede modificar el límite de préstamos del Tipo de Socio "Estándar" de 3 a 4 y el cambio se aplica inmediatamente en todas las validaciones sin reiniciar el sistema.
- La búsqueda por "Garcia" encuentra socios registrados como "García", "GARCIA" y socios cuyo nombre alternativo contiene "Garcia".
- La vista de mostrador de un socio con un préstamo atrasado muestra la alerta de atraso visible y el contador de atrasos en el año.
- La vista de mostrador de un socio con tipo Honorario no muestra ninguna restricción activa aunque tenga préstamos atrasados.

## 10. Plan de implementación recomendado

Orden interno sugerido (el módulo no tiene dependencias externas duras, solo las lecturas señaladas en la sección 6, que ya son satisfacibles con el modelo de datos existente):

1. **Tipo de Socio** — CRUD simple, sin dependencias internas. Reutiliza el patrón de `AutorController`/`EditorialController` de Módulo 2 (CRUD sin jerarquía).
2. **Socio** — CRUD con `nombres_alternativos` como lista editable (Alpine.js, mismo patrón que otros campos dinámicos ya usados en el proyecto), agregando la relación faltante `Socio::reservas()`.
3. **Habilitar `unaccent`** — migración que agrega la extensión (mitiga R-1), antes de escribir la búsqueda para no tener que rehacerla.
4. **Búsqueda de socios** — tolerante a mayúsculas/minúsculas y acentos, sobre nombre principal y nombres alternativos.
5. **Vista de mostrador** — préstamos activos, reservas activas, restricción vigente (`RestriccionSocio::estaActiva()`), atrasos en los últimos 12 meses (`HistorialAtraso` filtrado por `fecha_devolucion_efectiva`).
6. **Historial de préstamos paginado**.
7. **Tests** — cobertura de los 4 criterios de aceptación explícitos, más el caso Honorario sin restricción (RN-07) y la aplicación inmediata de un cambio de límite (D-04) sin necesidad de reiniciar nada (verificable simplemente leyendo el límite vigente en cada validación, no cacheado).

En todos los pasos, reutilizar sin modificar los métodos de dominio ya escritos en Módulo 1 (`RestriccionSocio::estaActiva()`) y el patrón de acceso por rol ya establecido (`role:administrador,personal`, Voluntario bloqueado — Modelo de Dominio v2, 6.1: "Gestionar socios" no incluye a Voluntario, igual que Catálogo).

---

## Recomendación

**El equipo considera que existe información suficiente para iniciar el desarrollo completo del Módulo 3 — Socios, sin ninguna decisión pendiente que lo bloquee.**

A diferencia de Módulo 2 (que tuvo un punto diferido, R-1, historial de condición física), este módulo no tiene información faltante: todas las reglas de negocio, entidades, criterios de aceptación y la única decisión técnica no trivial (R-1, búsqueda tolerante a acentos) están resueltas dentro de este mismo briefing, por ser de naturaleza puramente técnica y no de producto o dominio. Se recomienda proceder con los 7 pasos del plan de implementación en el orden indicado.

Se deja constancia (no bloqueante) de la misma observación documental que R-2 de Módulo 2: la omisión de dependencias de lectura en DA-06. Se recomienda consolidar ambas notas (Módulo 2 y Módulo 3) en una única actualización de DA-06 al cerrar este módulo, en vez de parchearla dos veces.
