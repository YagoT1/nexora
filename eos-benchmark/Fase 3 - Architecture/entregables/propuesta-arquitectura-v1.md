# Propuesta de Arquitectura — Sistema de Gestión Bibliotecaria
**Versión 1 — Para revisión de la Comisión Directiva**

---

## Introducción

Este documento define las decisiones de arquitectura del sistema antes de iniciar cualquier desarrollo. Cada decisión está justificada en función de las restricciones y necesidades reales de la institución, no en función de preferencias tecnológicas.

La arquitectura es la estructura que determina cuánto costará mantener el sistema en cinco años, qué tan fácil será para un nuevo proveedor continuar el trabajo, y cuánto riesgo operativo asume la biblioteca. Por eso se define antes de escribir una sola línea de código.

---

## Restricciones que condicionan toda decisión

Las siguientes restricciones provienen directamente del relevamiento y son no negociables. Toda decisión de arquitectura debe ser evaluada contra ellas:

| Restricción | Impacto en la arquitectura |
|---|---|
| No hay personal técnico en la institución | El sistema debe poder funcionar y recuperarse sin intervención técnica local. |
| El presupuesto es limitado | Los costos operativos mensuales deben ser mínimos y predecibles. |
| Un futuro proveedor debe poder continuar sin reconstruir | Solo tecnologías con comunidades grandes, documentación abundante y convenciones claras. |
| Hasta 8 usuarios concurrentes en condiciones normales | La escala no justifica complejidad de infraestructura. |
| El personal de mostrador necesita una interfaz rápida e intuitiva | La adopción depende de que el sistema sea más ágil que el cuaderno actual. |
| Datos personales de socios (Ley 25.326) | Seguridad y privacidad no son opcionales. |

---

## Decisiones de arquitectura

### DA-01 — Tipo de aplicación: web

**Decisión:** El sistema es una aplicación web que corre en el navegador. No es una aplicación de escritorio ni requiere instalación.

**Justificación:** Accesible desde cualquier dispositivo (PC, tablet, teléfono) sin instalación ni actualizaciones manuales. El acceso a través del navegador es hoy universal. Las actualizaciones del sistema se despliegan una sola vez en el servidor y están disponibles inmediatamente para todos los usuarios. Una aplicación de escritorio requeriría instalar y actualizar en cada computadora de la biblioteca, lo que es incompatible con la restricción de ausencia de personal técnico.

---

### DA-02 — Patrón arquitectónico: monolito modular

**Decisión:** El sistema es un monolito modular: una única aplicación, un único repositorio de código, un único despliegue.

**Alternativa considerada:** Arquitectura de API desacoplada con frontend separado (backend REST + SPA JavaScript). Esta opción fue evaluada y descartada.

| Criterio | Monolito modular | API + SPA desacoplada |
|---|---|---|
| Complejidad de despliegue | Un componente | Dos componentes independientes |
| Costo operativo | Menor | Mayor (dos servicios) |
| Facilidad de handoff a nuevo proveedor | Alta (todo en un lugar) | Media (requiere coordinar dos repositorios) |
| Capacidad para el volumen actual | Más que suficiente | Sobredimensionada |
| Complejidad de desarrollo | Menor | Mayor (CORS, tokens, build separado) |

**Conclusión:** Un monolito modular es la elección correcta para este proyecto. La escala no justifica la complejidad adicional de una arquitectura desacoplada. El sistema puede evolucionar hacia una separación mayor si en el futuro lo requiere, sin necesidad de reconstruir.

**Cómo se garantiza la modularidad dentro del monolito:** El código se organiza en módulos con responsabilidades claramente delimitadas (Catálogo, Socios, Circulación, Excepciones, Actividades, etc.), con dependencias explícitas entre ellos. Un módulo no accede directamente a los internos de otro; lo hace a través de interfaces definidas. Esto permite reemplazar o extraer un módulo en el futuro sin afectar al resto.

---

### DA-03 — Stack tecnológico

**Decisión:**

| Capa | Tecnología | Versión |
|---|---|---|
| Backend | PHP con Laravel | 11 (PHP 8.3) |
| Frontend | Vue.js 3 vía Inertia.js | integrado en el mismo repositorio |
| Base de datos | PostgreSQL | 16 |
| Autenticación interna | Laravel Breeze (sesiones) | — |

**Justificación de Laravel (PHP):**

Laravel es el framework web más utilizado en Argentina para desarrollo institucional. Su comunidad es amplia, la documentación es exhaustiva, y los desarrolladores familiarizados con él son abundantes en el mercado local. Esto directamente resuelve la restricción de continuidad por nuevo proveedor.

Laravel es un framework con "todo incluido": ORM robusto (Eloquent), migraciones de base de datos, autenticación, validación, manejo de sesiones, sistema de colas para tareas en segundo plano y herramientas de línea de comando para tareas rutinarias. Incorporarlo significa no depender de decenas de librerías independientes que podrían quedar sin mantenimiento. El proyecto lleva más de doce años en producción activa y tiene respaldo institucional sólido.

**Justificación de Inertia.js + Vue.js 3:**

Inertia.js permite construir interfaces modernas y reactivas en Vue.js sin necesidad de una API REST separada: el frontend y el backend comparten el mismo proyecto, el mismo repositorio y el mismo despliegue. Esto elimina la complejidad de coordinar dos aplicaciones y reduce significativamente el costo de mantenimiento.

Vue.js 3 provee la reactividad necesaria para la interfaz de mostrador: alertas en tiempo real (el socio tiene préstamos pendientes, el libro tiene una reserva activa), búsqueda incremental de socios y ejemplares, y navegación ágil sin recargas de página. Estas capacidades son críticas para que la adopción sea exitosa.

**Justificación de PostgreSQL:**

PostgreSQL es el motor de base de datos relacional más avanzado de código abierto disponible. El modelo de dominio validado tiene relaciones complejas, invariantes de integridad (un ejemplar en un solo movimiento activo a la vez) y necesidad de consultas transaccionales. PostgreSQL implementa estas garantías a nivel de motor, reduciendo la posibilidad de corrupción de datos. Tiene décadas de track record en sistemas institucionales, es gratuito y tiene soporte nativo en todos los proveedores de hosting relevantes.

---

### DA-04 — Estrategia de despliegue: PaaS administrado

**Decisión:** El sistema se despliega en una plataforma como servicio (PaaS) administrada, sin gestión de servidores por parte de la biblioteca.

**Alternativas consideradas:**

| Opción | Pros | Contras |
|---|---|---|
| PaaS (Railway, Render) | Cero gestión de infraestructura, HTTPS automático, backups, despliegues desde GitHub | Costo mensual fijo (~$15–30 USD), dependencia del proveedor |
| VPS (DigitalOcean, Linode) | Mayor control, precio similar | Requiere administración: actualizaciones de sistema operativo, certificados, backups manuales. Incompatible con la restricción de ausencia de personal técnico |
| Servidor propio | Control total, sin costo de hosting | Alto costo de mantenimiento, requiere personal técnico, riesgo de pérdida de datos |

**Recomendación:** Railway.app o Render.com. Ambas ofrecen despliegue automático desde el repositorio de código, PostgreSQL administrado con backups diarios automáticos, HTTPS incluido y monitoreo básico. El costo mensual estimado para este proyecto es de $15–25 USD.

**Propiedad del código y los datos:** El repositorio de código es propiedad de la institución (alojado en GitHub o similar bajo su cuenta). Los datos de la base de datos son exportables en cualquier momento en formato estándar (SQL). La institución no queda atada al proveedor técnico; puede migrar a otro proveedor en cualquier momento llevando el código y los datos.

---

### DA-05 — Modelo de seguridad

**Autenticación de usuarios internos:**

Sesiones con usuario y contraseña. Sin tokens JWT, sin autenticación de doble factor en la primera versión (puede incorporarse después). El sistema usa cookies de sesión seguras (HttpOnly, Secure, SameSite). Las contraseñas se almacenan con bcrypt.

**Autorización por roles:**

El modelo de roles definido en el dominio (Administrador / Personal / Voluntario) se implementa con un sistema de permisos por rol. Cada pantalla y cada operación valida el rol del usuario antes de ejecutarse. Un voluntario no puede acceder a la pantalla de excepciones ni a la de configuración, independientemente de si conoce la URL.

**Protección de datos (Ley 25.326):**

- Los datos de socios (nombre, DNI, email, teléfono) solo son accesibles para usuarios autenticados con rol Personal o Administrador. Los voluntarios solo ven el nombre del socio en las operaciones de mostrador.
- No se exportan datos de socios a terceros. Las planillas de estadísticas excluyen datos personales identificables.
- El sistema registra qué usuario realizó cada operación sensible (préstamos, modificación de socios, excepciones). Este registro no puede ser eliminado por ningún usuario.
- La conexión es siempre HTTPS; los datos no viajan en texto plano.

**Superficie de ataque de la primera versión:**

La primera versión es una aplicación de uso interno, accesible solo por usuarios registrados. No hay endpoint público excepto eventualmente la consulta de catálogo. Esto reduce significativamente la superficie de ataque respecto a versiones futuras con portal de socios.

---

### DA-06 — Estructura de módulos

El sistema se organiza en los siguientes módulos, con correspondencia directa con las áreas del modelo de dominio:

| Módulo | Responsabilidad | Dependencias |
|---|---|---|
| Catálogo | Gestión de libros, autores, editoriales, categorías y ejemplares | Ninguna |
| Socios | Gestión de socios y tipos de socio | Ninguna |
| Circulación | Préstamos, devoluciones, renovaciones, reservas | Catálogo, Socios, Excepciones |
| Excepciones y Restricciones | Gestión de excepciones autorizadas y restricciones de socios | Socios, Catálogo |
| Movimientos Especiales | Préstamos institucionales, movimientos internos, custodias externas | Catálogo, Actividades |
| Actividades y Donaciones | Gestión de eventos, inscripciones, donaciones | Catálogo, Instituciones |
| Alertas | Generación y presentación de alertas internas al personal | Circulación, Excepciones |
| Administración | Usuarios, roles, parámetros de configuración | Ninguna |
| Portal de socios | Consulta pública de catálogo y autoservicio de socios | Catálogo, Circulación |

La dependencia va siempre hacia abajo en la tabla. Un módulo de nivel inferior no conoce a los módulos que dependen de él.

---

### DA-07 — Alcance de la primera entrega (Fase 1)

La primera entrega está diseñada para resolver el problema operativo diario de la biblioteca: reemplazar el cuaderno y las planillas de Excel con un sistema que el personal pueda usar desde el primer día.

**Incluye:**

- Gestión completa del catálogo (libros, autores, editoriales, categorías, ejemplares con sus estados y modalidades).
- Gestión de socios y tipos de socio.
- Circulación completa: préstamos domiciliarios, devoluciones, renovaciones, reservas con cola de espera automatizada.
- Excepciones autorizadas y restricciones de socios.
- Panel de mostrador: alertas de préstamos vencidos, reservas disponibles para avisar al socio, historial de atrasos por socio.
- Informes básicos: préstamos activos, préstamos atrasados, reservas pendientes, catálogo por estado.
- Administración: usuarios, roles, parámetros de configuración.
- Migración de datos desde las planillas actuales.

**Queda para la Fase 2 (entrega posterior):**

- Préstamos institucionales.
- Movimientos internos.
- Custodias externas.
- Módulo de actividades y donaciones.

**Queda para la Fase 3:**

- Portal público de consulta de catálogo.
- Portal de socios (acceso autenticado, historial, estado de préstamos).

**Justificación de este alcance:** La Fase 1 cubre el 100% de la operación diaria del mostrador. Las fases 2 y 3 agregan valor, pero no son necesarias para que el sistema sea útil desde el primer día. Entregar por fases reduce el riesgo del proyecto: si la Fase 1 tiene algún ajuste necesario al ponerla en uso real, se incorpora antes de construir los módulos siguientes.

---

### DA-08 — Secuencia de construcción dentro de la Fase 1

Los módulos se construyen en este orden, donde cada uno es verificable antes de comenzar el siguiente:

| Orden | Módulo | Criterio de completitud |
|---|---|---|
| 1 | Infraestructura y autenticación | El sistema despliega en el entorno de producción. Los tres roles pueden autenticarse. |
| 2 | Catálogo | El personal puede cargar libros, autores, editoriales, categorías y ejemplares. Búsqueda funcional. |
| 3 | Socios | El personal puede registrar socios, modificarlos, consultar su estado. |
| 4 | Préstamos y devoluciones | El personal puede registrar un préstamo, una devolución y ver el estado del ejemplar. |
| 5 | Renovaciones y reservas | El sistema gestiona renovaciones con validación de reservas. La cola de reservas funciona. |
| 6 | Excepciones y restricciones | Las restricciones automáticas se generan. Las excepciones autorizadas funcionan. |
| 7 | Panel de alertas del mostrador | El panel muestra préstamos atrasados, reservas pendientes de aviso, historial de atrasos del socio. |
| 8 | Informes básicos | Los informes de circulación y catálogo están disponibles. |
| 9 | Migración de datos | El catálogo y los socios actuales están cargados y validados. |

---

## Lo que precede al desarrollo de interfaces

Antes de escribir código de interfaz de usuario, el equipo producirá prototipos de baja fidelidad (wireframes) de los flujos más críticos para validación con el personal de mostrador:

- **Flujo de préstamo:** desde la búsqueda del socio hasta la confirmación del préstamo, incluyendo el tratamiento de alertas (límite de préstamos, excepción activa, atraso histórico).
- **Flujo de devolución:** identificación del ejemplar, registro del estado físico, generación de restricción si corresponde, activación de reserva pendiente.
- **Panel principal del mostrador:** qué información está visible en la pantalla de inicio para el personal que atiende diariamente.

Estos prototipos se validan con Marta (personal de mostrador) antes de comenzar el desarrollo de la interfaz. El objetivo es que la primera vez que Marta use el sistema real, ya haya "visto" los flujos principales y los haya aprobado.

---

## Preguntas abiertas que requieren confirmación de la institución

Antes de iniciar el desarrollo hay dos puntos que solo la institución puede resolver:

**1. Presupuesto de hosting:**
El costo mensual estimado de infraestructura es de $15–25 USD (aproximadamente $15.000–25.000 pesos argentinos al cambio actual). Este es un costo recurrente mensual que la biblioteca debe poder sostener indefinidamente. Si existe una restricción de presupuesto más estricta, hay alternativas de menor costo que se evaluarán con sus ventajas y desventajas.

**2. Responsabilidad de mantenimiento post-entrega:**
¿La institución prevé contratar al mismo equipo para mantenimiento continuo, o necesita que el sistema sea completamente autosuficiente después de la entrega? La respuesta afecta la cantidad de documentación operativa que se produce y la capacitación necesaria para el personal.

---

## Riesgos de arquitectura

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| La migración de datos lleva más tiempo del estimado | Alta | Alto | Planificar la migración como proyecto paralelo desde el inicio del desarrollo, no al final. |
| El personal abandona el sistema y vuelve al cuaderno | Media | Crítico | Validar los flujos de mostrador con Marta antes de codificar. El panel de alertas debe ser más útil que el cuaderno desde el primer día. |
| El costo de hosting resulta difícil de sostener | Baja | Alto | Confirmar el presupuesto antes de iniciar. Hay alternativas gratuitas con limitaciones que pueden evaluarse si fuera necesario. |
| Un nuevo proveedor requiere formación excesiva | Baja | Alto | Documentación de arquitectura, convenciones de código y decisiones tomadas entregadas junto con el sistema. |
| El portal de socios (Fase 3) introduce vulnerabilidades | Media | Alto | La Fase 3 tiene un modelo de seguridad distinto (usuarios externos autenticados). Se trata como un proyecto con análisis de seguridad propio, no como una extensión trivial. |

---

## Resumen de decisiones

| Decisión | Elección | Fundamento principal |
|---|---|---|
| Tipo de aplicación | Web (navegador) | Sin instalación, acceso universal, actualizaciones centralizadas |
| Patrón | Monolito modular | Escala modesta, simplicidad de despliegue y mantenimiento |
| Backend | Laravel 11 (PHP) | Comunidad argentina, convenciones fuertes, todo incluido |
| Frontend | Vue.js 3 + Inertia.js | Reactivo, mismo repositorio, sin API separada |
| Base de datos | PostgreSQL 16 | Integridad relacional, transaccional, gratuito, universal |
| Hosting | PaaS administrado | Sin gestión de infraestructura, compatible con ausencia de personal técnico |
| Autenticación | Sesiones con bcrypt | Simple, seguro, sin dependencias externas |
| Autorización | Roles (Admin/Personal/Voluntario) | Mapeado directamente desde el modelo de dominio |
| Alcance Fase 1 | Catálogo + Socios + Circulación completa + Excepciones + Alertas | Resuelve el problema operativo diario desde el primer día |
| Fase 2 | Movimientos especiales + Actividades + Donaciones | Posterior, luego de validación de la Fase 1 en uso real |
| Fase 3 | Portal de socios | Posterior, con análisis de seguridad propio |

---

## Nivel de confianza en la arquitectura propuesta

| Aspecto | Confianza | Fundamento |
|---|---|---|
| Correctitud para el dominio | Alta | El stack cubre con comodidad todas las reglas de negocio del modelo de dominio. No hay caso de uso que requiera tecnología distinta. |
| Escalabilidad | Alta | Para el volumen actual (3.500 libros, 800 socios, 8 usuarios) el sistema tiene margen de crecimiento de al menos un orden de magnitud sin cambios de arquitectura. |
| Mantenibilidad | Alta | Laravel y PostgreSQL son tecnologías maduras con documentación exhaustiva. Cualquier desarrollador PHP del mercado argentino puede incorporarse al proyecto. |
| Seguridad | Media-alta | El modelo de seguridad es apropiado para la Fase 1 (sistema interno). La Fase 3 (portal público) requerirá un análisis adicional. |
| Operación sin personal técnico | Alta | El despliegue en PaaS elimina la necesidad de administración de infraestructura. Las actualizaciones del sistema requieren un desarrollador, no un administrador de sistemas. |

---

*Propuesta elaborada a partir del modelo de dominio validado (v2), el relevamiento consolidado (v2) y el análisis de restricciones y riesgos del proyecto. Versión 1 — pendiente de revisión por la institución antes de iniciar la etapa de diseño de interfaces y desarrollo.*
