# Propuesta de Arquitectura — Sistema de Gestión Bibliotecaria
**Versión 2 — Revisada y corregida. Lista para validación final de la Comisión Directiva.**

> Esta versión incorpora tres correcciones críticas, cuatro clarificaciones importantes y tres mejoras menores identificadas durante la revisión interna del equipo. Los cambios se detallan en el Anexo al final del documento.

---

## Introducción

Este documento define las decisiones de arquitectura del sistema antes de iniciar cualquier desarrollo. Cada decisión está justificada en función de las restricciones y necesidades reales de la institución, no en función de preferencias tecnológicas.

La arquitectura es la estructura que determina cuánto costará mantener el sistema en cinco años, qué tan fácil será para un nuevo proveedor continuar el trabajo, y cuánto riesgo operativo asume la biblioteca. Por eso se define antes de escribir una sola línea de código.

---

## Restricciones que condicionan toda decisión

| Restricción | Impacto en la arquitectura |
|---|---|
| No hay personal técnico en la institución | El sistema debe poder funcionar y recuperarse sin intervención técnica local. |
| El presupuesto es limitado | Los costos operativos mensuales deben ser mínimos y predecibles. |
| Un futuro proveedor debe poder continuar sin reconstruir | Solo tecnologías con comunidades grandes, documentación abundante y convenciones claras. |
| Hasta 8 usuarios concurrentes en condiciones normales | La escala no justifica complejidad de infraestructura. |
| El personal de mostrador necesita una interfaz rápida e intuitiva | La adopción depende de que el sistema sea más ágil que el cuaderno actual. |
| Datos personales de socios (Ley 25.326) | Seguridad y privacidad no son opcionales. |
| Internet requerido para operar | El sistema no funciona sin conectividad. Debe haber una contingencia definida. |

---

## Decisiones de arquitectura

### DA-01 — Tipo de aplicación: web

**Decisión:** El sistema es una aplicación web que corre en el navegador. No es una aplicación de escritorio ni requiere instalación.

**Justificación:** Accesible desde cualquier dispositivo (PC, tablet, teléfono) sin instalación ni actualizaciones manuales. Las actualizaciones del sistema se despliegan una sola vez en el servidor y están disponibles inmediatamente para todos los usuarios. Una aplicación de escritorio requeriría instalar y actualizar en cada computadora de la biblioteca, incompatible con la ausencia de personal técnico.

---

### DA-02 — Patrón arquitectónico: monolito modular

**Decisión:** El sistema es un monolito modular: una única aplicación, un único repositorio de código, un único despliegue.

**Alternativa considerada y descartada:** Arquitectura de API desacoplada con frontend separado.

| Criterio | Monolito modular | API + SPA desacoplada |
|---|---|---|
| Complejidad de despliegue | Un componente | Dos componentes independientes |
| Costo operativo | Menor | Mayor |
| Facilidad de handoff a nuevo proveedor | Alta | Media (dos repositorios a coordinar) |
| Capacidad para el volumen actual | Suficiente | Sobredimensionada |
| Complejidad de desarrollo | Menor | Mayor (CORS, tokens, build separado) |

La escala no justifica la complejidad adicional. El sistema puede evolucionar hacia una separación mayor en el futuro sin necesidad de reconstruir, siempre que el monolito esté bien modularizado.

**Cómo se garantiza la modularidad:** El código se organiza en módulos con responsabilidades delimitadas. Un módulo no accede directamente a los internos de otro; lo hace a través de interfaces definidas. Esto permite reemplazar o extraer un módulo en el futuro sin afectar al resto.

---

### DA-03 — Stack tecnológico

**Decisión:**

| Capa | Tecnología | Versión |
|---|---|---|
| Backend | PHP con Laravel | 11 (PHP 8.3) — **enmendado a Laravel 12, ver nota abajo** |
| Frontend — interfaces internas | Blade + Alpine.js | — |
| Frontend — portal de socios (Fase 3) | Vue.js 3 vía Inertia.js | A definir en Fase 3 |
| Base de datos | PostgreSQL | 16 |
| Autenticación | Laravel Breeze (sesiones) | — |

> **Enmienda (2026-07-12, ver `Fase 6 - Development/ADR-007-actualizacion-laravel-11-a-12.md`):**
> la versión de Laravel objetivo pasó de 11 a 12 al ejecutar el bootstrap del Módulo 1. Laravel 11
> dejó de recibir parches de seguridad activos (ventana de soporte de 2 años vencida desde marzo de
> 2026); dos vulnerabilidades reportadas en junio de 2026 (una de severidad alta) no tienen versión
> corregida en toda la rama 11.x, solo a partir de Laravel 12.60/12.61. Se documenta como enmienda,
> no como reescritura, para preservar la trazabilidad de la decisión original de esta propuesta.
> PHP 8.3 sigue siendo el piso mínimo soportado por el proyecto (Laravel 12 solo exige PHP 8.2); el
> entorno real validado usa PHP 8.5.8.

**Justificación de Laravel (PHP):**
Laravel es el framework web más utilizado en Argentina para desarrollo institucional. Desarrolladores familiarizados con él son abundantes en el mercado local, lo que resuelve directamente la restricción de continuidad por nuevo proveedor. Es un framework con "todo incluido": ORM robusto, migraciones de base de datos, autenticación, validación, sesiones, cola de tareas y programador de tareas. Tiene más de doce años en producción activa con respaldo institucional sólido.

**Justificación de Blade + Alpine.js para Fase 1:**
El frontend de la Fase 1 es un sistema interno de gestión, no una aplicación de consumo masivo. Blade es el motor de plantillas nativo de Laravel, conocido por cualquier desarrollador PHP con experiencia en el framework. Alpine.js proporciona la reactividad necesaria (alertas en pantalla, búsquedas incrementales, validaciones en tiempo real) con una curva de aprendizaje mínima y sin proceso de compilación independiente.

La decisión de no usar Vue.js vía Inertia.js en la Fase 1 es deliberada: Inertia.js, si bien es una herramienta sólida, agrega una capa que no todos los desarrolladores Laravel conocen. Para un sistema que debe poder ser mantenido por el desarrollador PHP más común del mercado argentino, Blade + Alpine.js tiene una superficie de conocimiento menor y, por lo tanto, un riesgo de continuidad menor. Vue.js + Inertia.js sigue siendo la recomendación para la Fase 3 (portal de socios), donde la experiencia de usuario del lado público justifica una interfaz más rica.

**Justificación de PostgreSQL:**
PostgreSQL implementa las garantías transaccionales necesarias para la regla de integridad más crítica del modelo de dominio (ver DA-09). Es el motor relacional open source más avanzado disponible, tiene soporte nativo en todos los proveedores de hosting relevantes, y es el estándar de facto para sistemas institucionales que requieren consistencia de datos.

---

### DA-04 — Estrategia de despliegue: PaaS administrado con entornos diferenciados

**Decisión:** El sistema se despliega en una plataforma como servicio (PaaS) administrada. Se definen dos entornos permanentes: staging y producción.

**Alternativas consideradas:**

| Opción | Pros | Contras |
|---|---|---|
| PaaS (Render.com) | Cero gestión de infraestructura, HTTPS automático, backups, despliegues desde GitHub | Costo mensual fijo (~$20–30 USD), dependencia del proveedor de hosting |
| VPS (DigitalOcean, Linode) | Mayor control | Requiere administración de SO, certificados, backups manuales. Incompatible con ausencia de personal técnico |
| Servidor propio | Control total, sin costo de hosting | Alto costo de mantenimiento, requiere personal técnico, riesgo de pérdida de datos |

**Proveedor recomendado: Render.com**
Render.com es preferido sobre Railway.app por su mayor estabilidad y política de precios predecible para proyectos de larga duración. Ofrece PostgreSQL administrado, despliegue automático desde el repositorio de código, HTTPS incluido, cron jobs administrados (necesarios para DA-10) y backups diarios automáticos.

**Entornos:**

| Entorno | Propósito | Acceso |
|---|---|---|
| Staging | Probar cambios antes de llevarlos a producción. Base de datos separada con datos de prueba. | Solo equipo de desarrollo |
| Producción | Sistema en uso real por el personal de la biblioteca. | Personal autorizado de la biblioteca |

Todo cambio al sistema pasa primero por staging, se verifica, y solo entonces se despliega a producción. Esta separación protege los datos reales ante errores introducidos durante el desarrollo.

**Propiedad del código y los datos:** El repositorio de código es propiedad de la institución (alojado en GitHub bajo su cuenta). Los datos son exportables en cualquier momento en formato estándar SQL. La institución no queda atada al proveedor técnico ni al proveedor de hosting.

**Política de respaldo y recuperación ante desastres:**

| Parámetro | Valor |
|---|---|
| Frecuencia de backup automático | Diaria (Render.com administrado) |
| Retención de backups | 7 días (plan estándar) |
| Responsable de iniciar una restauración | El proveedor técnico, no la institución |
| Tiempo estimado de restauración | 1–4 horas desde el inicio del procedimiento |
| Exportación manual recomendada | Una vez por mes, solicitada al proveedor técnico, archivada localmente por la institución |

La exportación mensual manual es un resguardo adicional que la institución conserva localmente (en una computadora de la biblioteca), independientemente de lo que ocurra con el proveedor de hosting o el proveedor técnico. El formato es SQL estándar, legible por cualquier sistema compatible con PostgreSQL.

---

### DA-05 — Modelo de seguridad

**Autenticación:**
Sesiones con usuario y contraseña. Cookies de sesión con atributos HttpOnly, Secure y SameSite. Contraseñas almacenadas con bcrypt. Tiempo de expiración de sesión por inactividad: 2 horas (configurable por el Administrador). Después de ese período, el sistema cierra la sesión automáticamente y requiere nuevo inicio de sesión. Esto protege la información de socios en casos donde un voluntario deja una computadora sin cerrar sesión.

**Autorización por roles:**
El modelo de roles del dominio (Administrador / Personal / Voluntario) se implementa con un sistema de permisos por rol. Cada pantalla y cada operación valida el rol del usuario antes de ejecutarse. Un voluntario no puede acceder a configuración, excepciones ni datos sensibles de socios, independientemente de la URL que ingrese.

**Registro de auditoría:**
El sistema registra automáticamente las siguientes operaciones con fecha, hora y usuario responsable:

- Creación, modificación e inactivación de socios.
- Registro de préstamos, devoluciones y renovaciones.
- Creación, modificación y revocación de excepciones autorizadas.
- Generación de restricciones manuales.
- Modificación de parámetros de configuración.
- Creación y modificación de usuarios del sistema.

Este registro no puede ser eliminado por ningún usuario, incluido el Administrador. Tiene retención mínima de 2 años.

**Protección de datos (Ley 25.326):**
- Los datos personales de socios (nombre, DNI, email, teléfono) son accesibles solo para usuarios con rol Personal o Administrador. Los voluntarios ven únicamente el nombre del socio en las operaciones de mostrador.
- No se exportan datos personales a terceros.
- Los informes estadísticos excluyen datos personales identificables.
- La conexión es siempre HTTPS; los datos no viajan en texto plano.

**Compatibilidad de navegadores:**
El sistema debe funcionar correctamente en las versiones actuales de Chrome, Firefox y Edge, y en versiones con hasta 3 años de antigüedad. No se garantiza compatibilidad con Internet Explorer. Esto cubre el parque de computadoras habitual en instituciones como la biblioteca.

**Accesibilidad:**
El diseño de interfaces seguirá los criterios de nivel AA de las WCAG 2.1 en lo que refiere a contraste de colores, navegación por teclado y uso de etiquetas descriptivas. Dado que los voluntarios incluyen personas de distintas edades, la interfaz debe ser operable sin necesidad de habilidades técnicas avanzadas.

---

### DA-06 — Estructura de módulos

El sistema se organiza en los siguientes módulos, con correspondencia directa con las áreas del modelo de dominio:

| Módulo | Responsabilidad | Dependencias |
|---|---|---|
| Catálogo | Libros, autores, editoriales, categorías, ejemplares | Ninguna |
| Socios | Socios y tipos de socio | Ninguna |
| Circulación | Préstamos, devoluciones, renovaciones, reservas | Catálogo, Socios, Excepciones |
| Excepciones y Restricciones | Excepciones autorizadas, restricciones, historial de atrasos | Socios, Catálogo |
| Movimientos Especiales | Préstamos institucionales, movimientos internos, custodias externas | Catálogo, Actividades |
| Actividades y Donaciones | Eventos, inscripciones, donaciones, donantes | Catálogo, Instituciones |
| Alertas | Alertas internas al personal (vencimientos, reservas, retornos) | Circulación, Excepciones |
| Tareas programadas | Marcado de préstamos atrasados, expiración de reservas | Circulación, Alertas |
| Administración | Usuarios, roles, parámetros de configuración | Ninguna |
| Portal de socios (Fase 3) | Consulta pública y autoservicio de socios | Catálogo, Circulación |

La dependencia va siempre hacia abajo en la tabla. Un módulo de nivel inferior no conoce a los módulos que dependen de él.

---

### DA-07 — Alcance de la primera entrega (Fase 1)

**Incluye:**
- Gestión completa del catálogo.
- Gestión de socios y tipos de socio.
- Circulación completa: préstamos domiciliarios, devoluciones, renovaciones, reservas con cola de espera.
- Excepciones autorizadas y restricciones de socios.
- Panel de mostrador: alertas internas de préstamos atrasados, reservas para avisar al socio, historial de atrasos.
- Informes básicos: préstamos activos, préstamos atrasados, reservas pendientes, catálogo por estado.
- Administración: usuarios, roles, parámetros de configuración.
- Migración de datos desde las planillas actuales.

**Fase 2 (entrega posterior a la validación en uso real de la Fase 1):**
- Préstamos institucionales, movimientos internos, custodias externas.
- Módulo de actividades y donaciones.

**Fase 3 (con análisis de seguridad propio):**
- Portal público de consulta de catálogo.
- Portal de socios (acceso autenticado, historial, estado de préstamos).

---

### DA-08 — Secuencia de construcción dentro de la Fase 1

| Orden | Módulo | Criterio de completitud |
|---|---|---|
| 1 | Infraestructura y autenticación | Staging y producción operativos. Los tres roles pueden autenticarse. |
| 2 | Catálogo | El personal puede cargar y buscar libros, autores, editoriales, categorías y ejemplares. |
| 3 | Socios | El personal puede registrar, modificar y consultar socios. |
| 4 | Préstamos y devoluciones | El personal puede registrar préstamos y devoluciones. RN-04 (invariante) enforced. |
| 5 | Renovaciones y reservas | Renovaciones con validación de reservas. Cola de espera automática. |
| 6 | Excepciones y restricciones | Restricciones automáticas y excepciones autorizadas funcionan. |
| 7 | Tareas programadas | Marcado automático de préstamos atrasados y expiración de reservas operativos. |
| 8 | Panel de alertas | El panel muestra todos los tipos de alerta relevantes para el mostrador. |
| 9 | Informes básicos | Los informes de circulación y catálogo están disponibles. |
| 10 | Migración de datos | Catálogo y socios actuales cargados y validados en el entorno de producción. |

---

### DA-09 — Estrategia de enforcement de la invariante de circulación (RN-04)

**La decisión más crítica del diseño técnico que no estaba documentada en la versión 1.**

La regla RN-04 establece que un ejemplar solo puede participar en un movimiento activo a la vez. En un sistema concurrente donde dos usuarios pueden operar simultáneamente (por ejemplo, dos voluntarios en distintas computadoras intentando prestar el mismo libro en el mismo momento), una verificación solo a nivel de aplicación no es suficiente: ambas transacciones podrían pasar el control al mismo tiempo y crear dos movimientos activos para el mismo ejemplar.

**Estrategia de enforcement a dos niveles:**

**Nivel 1 — Índice único parcial en la base de datos (PostgreSQL):**
Se crea un índice único sobre la columna `ejemplar_id` en cada tabla de movimientos (préstamos, préstamos institucionales, movimientos internos, custodias externas), filtrado solo sobre los registros con estado "activo". Esto hace que PostgreSQL rechace a nivel de motor cualquier intento de crear un segundo movimiento activo para el mismo ejemplar, independientemente de cuántas transacciones concurrentes lo intenten.

Este mecanismo es el más robusto disponible porque opera en la capa donde la consistencia se garantiza, no en la capa donde se intenta verificar.

**Nivel 2 — Verificación explícita en la capa de aplicación (antes de la operación):**
Antes de intentar crear un movimiento, la aplicación consulta si existe algún movimiento activo para el ejemplar. Si lo hay, presenta un mensaje claro al usuario antes de llegar a la base de datos. Esto hace que el error sea informativo (el usuario entiende qué pasó) en lugar de ser una excepción técnica de base de datos.

La combinación de ambos niveles garantiza: corrección (el estado incorrecto es imposible de persistir) y usabilidad (el usuario recibe un mensaje comprensible, no un error técnico).

---

### DA-10 — Procesamiento de operaciones con vencimiento temporal

**El segundo hallazgo crítico que no estaba documentado en la versión 1.**

El sistema tiene operaciones que deben ocurrir en momentos determinados, no solo cuando un usuario realiza una acción:

- Marcar préstamos como "Atrasado" cuando su fecha de vencimiento pasa sin devolución.
- Expirar reservas cuando vence la ventana de 48 horas de atención al público sin retiro.
- Actualizar el panel de alertas del mostrador.

Ninguna de estas operaciones ocurre dentro del ciclo de solicitud-respuesta de un usuario. Requieren un mecanismo de procesamiento periódico.

**Implementación:**
Laravel incluye un programador de tareas (Scheduler) que ejecuta comandos definidos en código según un calendario (cada hora, diariamente, etc.). En Render.com, estas tareas se configuran como cron jobs administrados. El costo adicional es mínimo (incluido en el plan de hosting recomendado).

**Frecuencia de ejecución:**
Las tareas de verificación de vencimientos se ejecutan cada hora. Esta frecuencia es más que suficiente para el volumen de la biblioteca y garantiza que las alertas del panel de mostrador estén siempre actualizadas al inicio de la jornada.

**Implicación para el panel de alertas:**
El panel no hace cálculos en tiempo real: muestra los estados ya actualizados por las tareas programadas. Esto significa que si un préstamo vence a las 11:35, puede aparecer como "Atrasado" en el panel a las 12:00 (próxima ejecución de la tarea). Esta latencia de hasta una hora es completamente aceptable para la operatoria de la biblioteca.

---

### DA-11 — Contingencia ante pérdida de conectividad

**El tercer hallazgo crítico: una dependencia operativa sin contingencia definida.**

El sistema requiere conexión a Internet para funcionar. La biblioteca se ubica en una localidad donde la conectividad puede ser intermitente. Si el acceso a Internet se interrumpe, el sistema queda completamente inaccesible: no se pueden registrar préstamos, devoluciones ni consultas.

**Posición de diseño:**
No se desarrollará una versión offline del sistema. Implementar capacidad offline agrega complejidad arquitectónica significativa (sincronización de datos, resolución de conflictos, almacenamiento local) desproporcionada para el volumen y presupuesto de este proyecto.

**Contingencia operativa recomendada:**
La biblioteca debe definir y documentar un procedimiento manual de emergencia para operar durante interrupciones de conectividad. El procedimiento mínimo consiste en:

- Un cuaderno de registro de préstamos y devoluciones de emergencia.
- Un proceso de volcado de los registros del cuaderno al sistema en cuanto se restablezca la conectividad.
- Una planilla impresa del estado actual de préstamos, actualizable mensualmente, como referencia offline.

Este procedimiento no es responsabilidad del sistema sino de la operación de la institución. El equipo de desarrollo puede colaborar en su definición durante la capacitación, pero debe quedar documentado por la propia biblioteca.

**Monitoreo de disponibilidad:**
El proveedor de hosting (Render.com) ofrece monitoreo de estado del servicio y notificaciones ante caídas. El proveedor técnico debe configurar alertas para ser notificado si el sistema deja de responder, de forma que pueda actuar rápidamente ante incidentes del lado del hosting.

---

## Lo que precede al desarrollo de interfaces

Antes de escribir código de interfaz de usuario, el equipo producirá prototipos de baja fidelidad (wireframes) de los flujos más críticos para validación con el personal de mostrador:

- **Flujo de préstamo:** desde la búsqueda del socio hasta la confirmación, incluyendo el tratamiento de alertas.
- **Flujo de devolución:** identificación del ejemplar, registro de estado físico, generación de restricción, activación de reserva.
- **Panel principal del mostrador:** información visible en la pantalla de inicio para el personal.

Estos prototipos se validan con el personal antes de comenzar el desarrollo de interfaces. El objetivo es que la primera vez que el personal use el sistema real, ya haya visto y aprobado los flujos principales.

---

## Preguntas abiertas que requieren confirmación de la institución

**1. Presupuesto de hosting:**
El costo mensual estimado de infraestructura (Render.com, dos entornos: staging y producción con PostgreSQL) es de $20–30 USD mensuales. Si existe una restricción presupuestaria más estricta, hay alternativas de menor costo que se evaluarán con sus ventajas y desventajas.

**2. Responsabilidad de mantenimiento post-entrega:**
¿La institución prevé contratar al mismo equipo para mantenimiento continuo, o necesita que el sistema sea completamente autosuficiente después de la entrega? La respuesta determina el nivel de documentación operativa y el alcance de la capacitación.

---

## Riesgos de arquitectura

| Riesgo | Probabilidad | Impacto | Mitigación |
|---|---|---|---|
| La migración de datos lleva más tiempo del estimado | Alta | Alto | Planificar la migración como proyecto paralelo desde el inicio del desarrollo. |
| El personal abandona el sistema y vuelve al cuaderno | Media | Crítico | Validar flujos con Marta antes de codificar. El panel de alertas debe superar al cuaderno desde el primer día. |
| El costo de hosting resulta difícil de sostener | Baja | Alto | Confirmar el presupuesto antes de iniciar. Existen alternativas gratuitas con limitaciones que se evaluarán si fuera necesario. |
| Un nuevo proveedor requiere formación excesiva | Baja | Alto | Blade + Alpine.js (en lugar de Inertia + Vue) reduce el conocimiento requerido. Documentación de arquitectura incluida en la entrega. |
| La conectividad de Internet falla durante la operación | Media | Alto | Contingencia operativa manual definida (DA-11). Monitoreo de disponibilidad configurado. |
| El portal de socios (Fase 3) introduce vulnerabilidades | Media | Alto | La Fase 3 se trata como un proyecto independiente con análisis de seguridad propio. |
| Las tareas programadas fallan silenciosamente | Baja | Medio | Configurar alertas en Render.com para notificar al proveedor técnico ante fallos de cron jobs. |

---

## Resumen de decisiones

| Decisión | Elección | Fundamento principal |
|---|---|---|
| Tipo de aplicación | Web (navegador) | Sin instalación, actualizaciones centralizadas, acceso universal |
| Patrón | Monolito modular | Escala modesta, simplicidad de despliegue y mantenimiento |
| Backend | Laravel 11 (PHP) | Comunidad argentina abundante, convenciones fuertes, todo incluido |
| Frontend Fase 1 | Blade + Alpine.js | Máxima legibilidad para futuros desarrolladores PHP, sin capa adicional |
| Frontend Fase 3 | Vue.js 3 + Inertia.js | Riqueza de UX justificada para el portal público de socios |
| Base de datos | PostgreSQL 16 | Integridad transaccional, índices parciales para RN-04, estándar institucional |
| Hosting | Render.com (PaaS) | Cero gestión de infraestructura, cron jobs administrados, backups automáticos |
| Entornos | Staging + Producción | Todo cambio se verifica en staging antes de llegar a producción |
| Autenticación | Sesiones con bcrypt + timeout 2hs | Simple, seguro, timeout operativamente justificado |
| Autorización | Roles del modelo de dominio | Trazabilidad directa desde el modelo validado |
| Invariante RN-04 | Índice único parcial + verificación de aplicación | Dos niveles: motor de base de datos garantiza correctitud, aplicación garantiza usabilidad |
| Operaciones temporales | Laravel Scheduler (cron horario) | Marca atrasos y expira reservas sin intervención del usuario |
| Conectividad offline | No implementada | Complejidad desproporcionada; contingencia operativa manual definida |
| Alcance Fase 1 | Catálogo + Socios + Circulación + Excepciones + Alertas | Resuelve el 100% del problema operativo diario |

---

## Nivel de confianza en la arquitectura propuesta

| Aspecto | Confianza | Fundamento |
|---|---|---|
| Correctitud para el dominio | Alta | El stack cubre todas las reglas de negocio. RN-04 tiene enforcement de dos niveles. Las operaciones temporales están cubiertas por el scheduler. |
| Escalabilidad | Alta | Para el volumen actual y proyectado a 10 años, el sistema tiene margen amplio sin cambios de arquitectura. |
| Mantenibilidad | Alta | Blade + Alpine.js (sin Inertia) reduce la superficie de conocimiento requerida para futuros desarrolladores. Laravel es conocido ampliamente. |
| Seguridad | Media-alta | Apropiada para Fase 1 (sistema interno). La Fase 3 requiere análisis adicional. Auditoría y timeout de sesión incorporados. |
| Operación sin personal técnico | Alta | PaaS elimina gestión de infraestructura. La contingencia offline es responsabilidad operativa de la institución, no técnica. |
| Resiliencia ante pérdida de datos | Media-alta | Backups diarios automáticos + recomendación de exportación mensual manual. Restore requiere proveedor técnico. |

---

## Anexo: Cambios respecto a la versión 1

| Código | Tipo | Descripción |
|---|---|---|
| C-01 | Corrección crítica | Agregada DA-09: estrategia de enforcement de RN-04 a dos niveles (índice único parcial en PostgreSQL + verificación en capa de aplicación). |
| C-02 | Corrección crítica | Agregada DA-10: estrategia de procesamiento temporal con Laravel Scheduler (cron horario) para marcado de préstamos atrasados y expiración de reservas. |
| C-03 | Corrección crítica | Ampliada DA-04: política de respaldo con frecuencia, retención, procedimiento de restauración y recomendación de exportación manual mensual. Agregada DA-11: contingencia ante pérdida de conectividad. |
| C-04 | Corrección importante | DA-03 revisada: se reemplaza Inertia.js + Vue.js por Blade + Alpine.js para la Fase 1, reduciendo el riesgo de continuidad por proveedor. Vue + Inertia se mantiene como recomendación para la Fase 3 (portal de socios). |
| C-05 | Corrección importante | DA-04 ampliada: se definen dos entornos permanentes (staging y producción). Todo cambio se verifica en staging antes de llegar a datos reales. |
| C-06 | Corrección importante | DA-05 ampliada: definido timeout de sesión por inactividad (2 horas), alcance del registro de auditoría con retención mínima de 2 años. |
| C-07 | Corrección importante | Agregada restricción de conectividad al cuadro inicial de restricciones. Incorporada en riesgos con mitigación. |
| M-01 | Mejora | DA-05: agregada declaración de compatibilidad de navegadores (versiones actuales y hasta 3 años de antigüedad). |
| M-02 | Mejora | DA-05: agregada mención de accesibilidad WCAG 2.1 nivel AA como criterio de diseño. |
| M-03 | Mejora | DA-08 actualizada: el paso de tareas programadas se incluye explícitamente en la secuencia de construcción (orden 7). Criterios de completitud revisados. |
| P-01 | Proveedor | Render.com se especifica como proveedor recomendado sobre Railway.app por mayor estabilidad y política de precios predecible. |

---

*Documento elaborado a partir del modelo de dominio validado (v2), el relevamiento consolidado (v2) y la revisión interna completa del equipo. Versión 2 — para validación final de la Comisión Directiva antes de iniciar la etapa de diseño de interfaces y desarrollo.*
