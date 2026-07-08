# Plan de Implementación — Fase 1
## Sistema de Gestión Bibliotecaria

**Versión 2 — Corregida tras revisión cruzada con modelo de dominio y arquitectura**

> Esta versión incorpora tres correcciones identificadas durante la revisión cruzada final: cobertura de RN-14 y RN-21 no asignadas en v1, exportación de datos como funcionalidad implementada, y estrategia de testing. Los cambios se detallan en el Anexo.

---

## Propósito

Este documento traduce la arquitectura aprobada en un plan de construcción concreto. Define qué se implementa en cada módulo, en qué orden, cómo verificar que está correctamente terminado, y qué decisiones del modelo de dominio y la arquitectura aplican en cada caso.

Es el documento de referencia para el equipo de desarrollo durante toda la Fase 1. Cada criterio de aceptación es una condición verificable, no una descripción general.

---

## Alcance de la Fase 1

La Fase 1 resuelve el 100% de la operación diaria del mostrador. Al finalizar, la biblioteca puede gestionar todo su ciclo de circulación (préstamos, devoluciones, renovaciones, reservas, excepciones y restricciones) y su catálogo completo, sin depender de cuadernos ni planillas de Excel.

Lo que queda fuera de la Fase 1 está documentado en la propuesta de arquitectura (Fase 2: movimientos especiales, actividades, donaciones; Fase 3: portal de socios).

---

## Entorno de trabajo

| Elemento | Definición |
|---|---|
| Repositorio | GitHub, bajo cuenta de la institución |
| Entorno de desarrollo | Local en la máquina del desarrollador |
| Entorno de staging | Render.com, base de datos separada con datos de prueba |
| Entorno de producción | Render.com, base de datos con datos reales |
| Despliegue | Automático desde rama `main` (staging) y rama `production` (producción) |
| Stack | Laravel 11 (PHP 8.3) + Blade + Alpine.js + PostgreSQL 16 |

Toda nueva funcionalidad pasa primero por staging. Solo después de verificar que funciona correctamente en staging se despliega a producción.

---

## Pre-checklist antes de comenzar el desarrollo

Antes de escribir código de funcionalidad, el equipo debe completar:

- [ ] Repositorio creado en GitHub bajo cuenta de la institución.
- [ ] Entornos de staging y producción configurados en Render.com con PostgreSQL.
- [ ] Despliegues automáticos desde GitHub configurados y verificados.
- [ ] Certificado HTTPS activo en ambos entornos.
- [ ] Cron job configurado en Render.com (ejecución horaria de `php artisan schedule:run`).
- [ ] Variables de entorno definidas (sin valores sensibles en el repositorio).
- [x] Wireframes del mostrador validados con el personal de la biblioteca.
- [ ] Muestra de datos anonimizada para staging preparada (sin datos reales de socios).
- [ ] **BLOQUEANTE — Presupuesto de hosting confirmado por la institución** (estimado $20–30 USD/mes). Sin esta confirmación no puede iniciarse la configuración de entornos.
- [ ] **BLOQUEANTE — Responsabilidad de mantenimiento post-entrega definida por la institución.** Determina profundidad de documentación operativa y alcance de la capacitación.

---

## Estrategia de testing

El sistema gestiona datos institucionales con 21 reglas de negocio definidas. Para garantizar que las reglas críticas se cumplan en todo momento, la implementación debe incluir tests automatizados. Estos no son opcionales: son la única forma de verificar que una modificación futura no rompe silenciosamente una regla que ya funcionaba.

**Tests de reglas de negocio (obligatorios):**

Para cada regla de negocio identificada como crítica en el modelo de dominio, debe existir al menos un test automatizado que la verifique. Laravel incluye un framework de tests (PHPUnit) que permite escribir estos tests como pruebas de feature que simulan operaciones reales contra la base de datos de testing.

Las reglas que requieren cobertura obligatoria de test son:

| Regla | Qué debe probarse |
|---|---|
| RN-01 | Que el sistema alerta al superar el límite de préstamos y requiere motivo para continuar. |
| RN-03 | Que la renovación es rechazada cuando el libro tiene reservas pendientes. |
| RN-04 | Que dos transacciones concurrentes no pueden crear dos movimientos activos para el mismo ejemplar. |
| RN-05 | Que una reserva vence correctamente después de la ventana de 48 horas de atención. |
| RN-06 | Que un socio con restricción activa no puede recibir un préstamo. |
| RN-07 | Que un socio honorario no genera restricción automática al devolver con atraso. |
| RN-08 | Que un ejemplar con modalidad Solo sala no puede ser prestado domiciliariamente. |
| RN-10 | Que un usuario con rol Personal no puede crear excepciones autorizadas. |
| RN-18 | Que la devolución tardía genera restricción proporcional a los días de atraso, con el tope configurado. |
| RN-19 | Que la renovación actualiza correctamente Prestamo.fecha_vencimiento y crea el registro de Renovación. |

**Tests de las tareas programadas (obligatorios):**

Cada tarea programada del módulo 7 debe tener un test que verifique su efecto: que los préstamos marcados como atrasados efectivamente lo están después de ejecutar la tarea, y que las reservas vencidas se procesan correctamente.

**Lo que no requiere test automatizado en la Fase 1:**

Las interfaces de usuario (Blade + Alpine.js) no requieren tests de browser automatizados. La validación de interfaces se realiza manualmente durante la revisión de cada módulo con el personal representativo del rol que lo utilizará (incluida la validación ya completada con Marta para los flujos principales).

---

## Módulos y criterios de aceptación

---

### Módulo 1 — Infraestructura y autenticación

**Precondición:** ninguna.

**Descripción:** Configuración de la base técnica del sistema: estructura del proyecto, conexión a la base de datos, autenticación de usuarios y sistema de roles.

**Reglas de negocio cubiertas:** ninguna de dominio. Establece la base técnica para todas las demás. Implementa además la infraestructura de auditoría que soporta RN-14.

**Lo que se implementa:**

- Estructura de proyecto Laravel con módulos separados por área de dominio (Catálogo, Socios, Circulación, Excepciones, Administración).
- Migraciones de base de datos iniciales para las entidades de todos los módulos.
- Autenticación por sesión: inicio de sesión, cierre de sesión, recuperación de contraseña.
- Expiración de sesión por inactividad: 2 horas (configurable por parámetro).
- Sistema de roles: Administrador, Personal, Voluntario.
- Middleware de autorización por rol aplicado a todas las rutas.
- Layout base de la aplicación (navegación, estructura visual, responsive).
- Panel de administración básico: gestión de usuarios (crear, editar, inactivar, asignar rol).
- **Infraestructura de auditoría (RN-14):** registro automático de operaciones sensibles con fecha, hora y usuario responsable. El registro es append-only: ningún usuario puede eliminarlo. Cubre las operaciones definidas en DA-05 (seguridad). Los cambios a parámetros de configuración quedan registrados con el valor anterior y el nuevo.
- Tests automatizados de autenticación y autorización por rol.

**Criterios de aceptación:**

- Un usuario con cada rol puede iniciar sesión con sus credenciales.
- Una sesión inactiva por 2 horas se cierra automáticamente y redirige al login.
- Un voluntario que intenta acceder a una ruta de administración recibe un error de autorización, no el contenido.
- El sistema despliega correctamente en el entorno de staging desde el repositorio.
- El sistema despliega correctamente en el entorno de producción desde el repositorio.
- Las migraciones se ejecutan sin errores en ambos entornos.
- El cron job de staging y producción ejecuta `schedule:run` cada hora sin errores.
- La modificación de un parámetro de configuración genera un registro de auditoría con: usuario, fecha, parámetro modificado, valor anterior y valor nuevo. Este registro no puede ser eliminado desde ninguna pantalla del sistema.

---

### Módulo 2 — Catálogo

**Precondición:** Módulo 1 completo.

**Descripción:** Gestión completa del acervo bibliográfico: obras, copias físicas, y la información descriptiva que las rodea.

**Reglas de negocio cubiertas:** DA-07 (esquema de clasificación propio, jerárquico, máximo 2 niveles), RN-08 (modalidad Solo sala), RN-09 (modalidad Restringido a autorización), RN-21 (alerta al personal cuando el cambio de modalidad deja reservas insatisfacibles), D-02 (separación Libro/Ejemplar), D-07 (ISBN no es identificador único), D-09 (estado de Ejemplar parcialmente derivado).

**Lo que se implementa:**

- CRUD de Autor, Editorial, Categoría (con padre opcional, máximo 2 niveles de profundidad, validado).
- CRUD de Libro: título, ISBN (no único, no obligatorio), año, edición, idioma, descripción, autores (relación M:N), editorial, categorías.
- CRUD de Ejemplar: vinculado a Libro, con estado operativo manual (En reparación / Extraviado) y modalidad de acceso (Libre circulación / Solo sala / Restringido a autorización), condición física, fecha de ingreso, origen.
- Búsqueda de catálogo: por título (búsqueda parcial), autor, categoría, estado, modalidad.
- Vista de Libro: muestra todos sus ejemplares con estado actual (incluyendo derivado de movimientos activos) y modalidad.
- Historial de condición física por ejemplar (registro de notas ingresadas en devoluciones).
- **Validación RN-21:** al cambiar la modalidad de acceso de un ejemplar a Solo sala o Restringido a autorización, el sistema verifica si existe alguna reserva Pendiente o Personal alertado sobre el Libro de ese ejemplar que no pueda satisfacerse con los demás ejemplares disponibles. Si la hay, muestra una alerta al personal antes de guardar el cambio, solicitando confirmación y resolución manual de las reservas afectadas.

**Criterios de aceptación:**

- El personal puede crear un Libro con múltiples autores, sin ISBN, con categoría y subcategoría.
- El sistema no permite crear una subcategoría cuyo padre ya es subcategoría (profundidad máxima 2).
- El personal puede crear un Ejemplar vinculado a un Libro existente, con modalidad Solo sala.
- La vista del Libro muestra correctamente el estado "Prestado" para un ejemplar con préstamo activo, sin necesidad de campo de estado explícito en la tabla de ejemplares.
- La búsqueda por título parcial devuelve resultados relevantes. La búsqueda por autor devuelve todos los libros del autor.
- Un ejemplar con estado manual "En reparación" muestra ese estado aunque no tenga movimiento activo.
- Al intentar cambiar la modalidad de acceso del único ejemplar disponible de un Libro con reservas Pendientes a Solo sala, el sistema muestra una alerta con las reservas afectadas y requiere confirmación antes de guardar.

---

### Módulo 3 — Socios

**Precondición:** Módulo 1 completo.

**Descripción:** Gestión del padrón de socios y la configuración de tipos de socio con sus beneficios.

**Reglas de negocio cubiertas:** RN-01 (límite de préstamos por tipo), RN-07 (honorarios sin restricción automática), D-04 (configuración sin intervención técnica).

**Lo que se implementa:**

- CRUD de Tipo de Socio: nombre, límite de préstamos simultáneos, sujeto a restricción automática. Los valores son editables desde la administración sin tocar código.
- CRUD de Socio: nombre principal, lista de nombres alternativos (campo editable como lista), DNI, email, teléfono, fecha de alta, estado (Activo/Inactivo), tipo de socio.
- Búsqueda de socios: tolerante a variaciones de nombre (búsqueda parcial sobre nombre principal y nombres alternativos simultáneamente).
- Vista de socio desde el mostrador: muestra préstamos activos, reservas activas, restricción vigente si la hay, y cantidad de atrasos en los últimos 12 meses.
- Historial de préstamos del socio (paginado).

**Criterios de aceptación:**

- El Administrador puede modificar el límite de préstamos del Tipo de Socio "Estándar" de 3 a 4 y el cambio se aplica inmediatamente en todas las validaciones sin reiniciar el sistema.
- La búsqueda por "Garcia" encuentra socios registrados como "García", "GARCIA" y socios cuyo nombre alternativo contiene "Garcia".
- La vista de mostrador de un socio con un préstamo atrasado muestra la alerta de atraso visible y el contador de atrasos en el año.
- La vista de mostrador de un socio con tipo Honorario no muestra ninguna restricción activa aunque tenga préstamos atrasados.

---

### Módulo 4 — Préstamos y devoluciones

**Precondición:** Módulos 2 y 3 completos.

**Descripción:** El núcleo operativo del mostrador. Registro del ciclo completo de préstamo y devolución con todas sus validaciones y efectos colaterales.

**Reglas de negocio cubiertas:** RN-01 (límite con alerta, no bloqueo), RN-02 (plazo 15 días desde fecha de préstamo), RN-04 (invariante de circulación, enforcement a dos niveles), RN-06 (restricción bloquea préstamo salvo excepción vigente), RN-08 y RN-09 (modalidades de acceso), RN-12 (devolución por terceros), RN-13 (fecha de préstamo editable), RN-18 (restricción generada en devolución tardía), D-09 (estado derivado de movimientos), DA-09 (concurrencia).

**Lo que se implementa:**

**Préstamo:**
- Flujo: buscar socio → verificar estado → seleccionar ejemplar → confirmar → registrar.
- Verificación de restricción activa: si existe, bloquear y mostrar motivo. Si hay excepción vigente de exención, permitir y registrarlo.
- Verificación de límite de préstamos: si se supera el límite del tipo de socio, mostrar alerta con detalle (cuántos tiene, cuál es el límite). Permitir continuar solo si el usuario registra un motivo de excepción. No bloquear automáticamente.
- Verificación de modalidad del ejemplar: Solo sala y Restringido sin excepción vigente bloquean el préstamo.
- Fecha de préstamo editable en el momento del registro (para el escenario de desfase con la entrega física).
- Fecha de vencimiento calculada automáticamente: fecha de préstamo + parámetro de plazo (15 días por defecto).
- Índice único parcial en la base de datos sobre ejemplar_id filtrado por estado activo (DA-09 nivel 1).
- Verificación explícita en aplicación antes de la operación (DA-09 nivel 2).

**Devolución:**
- Flujo: identificar ejemplar (por código o búsqueda de título) → mostrar préstamo activo asociado → confirmar devolución → campo opcional de condición física.
- No requiere que sea el mismo socio que retiró el libro.
- Si la devolución es tardía: generar RestriccionSocio automática (1 día de restricción por día de atraso, máximo configurable). No aplica si el socio es Honorario o tiene excepción de exención vigente.
- Si el libro tiene reserva pendiente: marcar reserva como "Personal alertado" y generar alerta en el panel del mostrador.
- Crear registro en HistorialAtraso si la devolución fue tardía, indicando si se generó restricción o fue eximido.

**Criterios de aceptación:**

- El préstamo de un ejemplar con préstamo activo es rechazado por la base de datos, no solo por el código de aplicación. Si dos solicitudes simultáneas intentan prestar el mismo ejemplar, exactamente una tiene éxito.
- Un socio con restricción activa no puede recibir un préstamo, y el sistema muestra el motivo y la fecha de fin de la restricción.
- Un socio con restricción activa pero con Excepción Autorizada vigente de tipo "Exención" puede recibir el préstamo. El registro del préstamo indica que se usó una excepción.
- Un socio estándar con 3 préstamos activos recibe una alerta al intentar un cuarto. El personal puede continuar ingresando un motivo de excepción. El préstamo queda registrado con ese motivo.
- Un socio Honorario con 5 préstamos activos recibe la misma alerta de límite (su límite es 5). El sistema usa el límite del Tipo de Socio, no un valor hardcodeado.
- La devolución de un préstamo vencido con 3 días de atraso genera una restricción de 3 días de duración para el socio, salvo que sea Honorario.
- La devolución de un libro con reserva pendiente activa la alerta de "avisar al socio" en el panel del mostrador dentro del ciclo de la misma request.
- La devolución puede registrarse sin identificar quién trae el libro.

---

### Módulo 5 — Renovaciones y reservas

**Precondición:** Módulo 4 completo.

**Descripción:** Extensión del ciclo de circulación para cubrir renovaciones de préstamos activos y el sistema de reservas con cola de espera.

**Reglas de negocio cubiertas:** RN-03 (renovación bloqueada si hay reserva pendiente), RN-05 (ventana de 48 horas de atención), RN-19 (fecha de vencimiento actualizada en renovación), RN-20 (alertas internas, no mensajes automáticos), RN-21 (reservas pendientes ante cambio de modalidad).

**Lo que se implementa:**

**Renovaciones:**
- Desde la vista de préstamo activo o desde la ficha del socio: botón de renovar.
- Verificación: si el Libro tiene reservas en estado Pendiente o Personal alertado, la renovación es rechazada con mensaje explicativo.
- Si no hay reservas pendientes: actualizar Prestamo.fecha_vencimiento (fecha actual + 15 días). Crear registro en Renovación con fecha anterior y nueva.
- Sin límite de renovaciones consecutivas (la regla es la ausencia de reservas pendientes).

**Reservas:**
- Reserva creada sobre Libro (título), no sobre Ejemplar.
- Verificación: el socio no tiene ya una reserva activa sobre el mismo Libro.
- Cola de espera: el sistema asigna al siguiente socio en la cola cuando un ejemplar disponible del título no tiene movimiento activo.
- Cuando un ejemplar queda disponible y hay reservas pendientes: asignar ejemplar a la reserva más antigua, cambiar estado de la reserva a "Personal alertado", registrar fecha de alerta, calcular fecha límite de retiro (fecha de alerta + parámetro de ventana de retiro = 48 horas de atención al público).
- Alerta en panel de mostrador para reservas en estado "Personal alertado" (indicar socio, título, y fecha límite de retiro).
- La expiración de la ventana de retiro la gestiona la tarea programada (Módulo 7), no el flujo de usuario.
- Si se cambia la modalidad de un Ejemplar a Solo sala: verificar si era el único disponible para reservas pendientes del Libro. Si es así, mostrar alerta al personal para cancelación manual.

**Criterios de aceptación:**

- La renovación de un préstamo con reservas pendientes es rechazada con el mensaje "El libro tiene una reserva pendiente de [nombre del socio]."
- La renovación de un préstamo sin reservas pendientes actualiza la fecha de vencimiento y crea el registro de Renovación con la fecha anterior.
- Cuando el ejemplar de un Libro reservado es devuelto, la reserva más antigua del Libro pasa a "Personal alertado" y aparece en el panel del mostrador.
- El panel muestra correctamente la fecha límite de retiro del ejemplar apartado.
- Un socio no puede tener dos reservas activas para el mismo Libro.
- Al cambiar la modalidad del único ejemplar disponible de un Libro con reservas pendientes a Solo sala, el sistema muestra una alerta al personal.

---

### Módulo 6 — Excepciones y restricciones

**Precondición:** Módulos 3 y 4 completos.

**Descripción:** Mecanismo formal de excepciones autorizadas y gestión de restricciones de socios, incluyendo la migración de los casos históricos detectados durante el relevamiento.

**Reglas de negocio cubiertas:** RN-06 (restricción bloquea), RN-07 (honorarios exentos), RN-10 (solo Admin crea excepciones), RN-11 (trazabilidad completa), D-03 (mecanismo único de excepciones).

**Lo que se implementa:**

**Excepciones autorizadas (solo Administrador):**
- CRUD de ExcepcionAutorizada: tipo, entidad afectada (Socio o Ejemplar), motivo, fecha de inicio, fecha de fin (opcional), estado.
- Tipos implementados: Exención de restricción por atraso, Límite de préstamo especial, Autorización de salida de material restringido.
- Al crear o modificar, el sistema registra automáticamente el usuario y la fecha de autorización.
- Revocar: marca la excepción como Revocada con fecha y usuario de revocación.
- Las excepciones vencidas (fecha de fin superada) quedan con estado Vencida. No se eliminan.
- Pantalla de listado de excepciones vigentes, con filtros por tipo y entidad.

**Restricciones de socios:**
- Restricciones automáticas generadas desde el módulo 4 (devolución tardía).
- Restricciones manuales: el Personal puede crear una restricción manual con motivo y fecha de fin definida.
- Vista de restricciones activas e históricas por socio.
- Verificación al prestar: si hay restricción activa y no hay excepción de exención vigente, bloquear.

**Migración de casos históricos:**
- Cargar en el sistema las excepciones heredadas del relevamiento: el caso de exención de penalización del socio histórico (S-0072) y los socios honorarios identificados, formalizando en el sistema lo que hoy existe solo como nota o conocimiento del personal.

**Criterios de aceptación:**

- Un usuario con rol Personal no puede acceder a la pantalla de creación de excepciones. La ruta devuelve error de autorización.
- Un Administrador puede crear una excepción de exención para el socio S-0072 con motivo "Colaboración histórica con la institución" y vigencia indefinida.
- La excepción queda registrada con el nombre del Administrador que la creó y la fecha.
- Un socio con excepción de exención vigente puede recibir un préstamo aunque tenga una restricción automática activa.
- Una excepción con fecha de fin pasada aparece con estado "Vencida" y no aplica en las validaciones de préstamo.
- El Administrador puede revocar una excepción antes de su fecha de fin. La revocación queda registrada con fecha y usuario.

---

### Módulo 7 — Tareas programadas

**Precondición:** Módulos 4 y 5 completos. Cron job en Render.com configurado (del pre-checklist).

**Descripción:** Procesamiento periódico de las operaciones con vencimiento temporal que no ocurren en respuesta a acciones de usuario.

**Reglas de negocio cubiertas:** RN-05 (expiración de reservas), RN-18 (marcado de atrasos), DA-10 (scheduler horario).

**Lo que se implementa:**

**Tarea 1: Marcado de préstamos atrasados**
Ejecuta cada hora. Identifica todos los préstamos con estado Activo cuya fecha de vencimiento es anterior a la fecha actual. Los marca como Atrasado. No genera restricciones (las restricciones se generan al momento de la devolución tardía, no cuando vence el plazo).

**Tarea 2: Expiración de reservas**
Ejecuta cada hora. Identifica todas las reservas en estado "Personal alertado" cuya fecha límite de retiro es anterior al momento actual. Las marca como "Vencida por no retiro". El ejemplar apartado vuelve a estar disponible (su movimiento de reserva se desactiva). Si el Libro tiene otra reserva Pendiente, se procesa la siguiente en la cola (con la misma lógica de asignación del Módulo 5).

**Tarea 3: Verificación de integridad de tareas**
Ejecuta diariamente. Registra en el log del sistema que las tareas anteriores se ejecutaron correctamente en las últimas 24 horas. Si alguna no se ejecutó (por falla del cron job), genera una alerta en el panel de administración.

**Criterios de aceptación:**

- Un préstamo con fecha de vencimiento de ayer aparece como "Atrasado" en el panel de mostrador antes de que se complete la próxima hora de ejecución del scheduler.
- Una reserva en estado "Personal alertado" cuya fecha límite venció hace más de una hora aparece como "Vencida por no retiro". Si había otra reserva pendiente para ese Libro, pasa automáticamente a "Personal alertado".
- El panel de administración muestra el estado de la última ejecución de cada tarea programada.
- Si el cron job falla durante 24 horas (simulado en staging), la próxima ejecución exitosa registra el período sin ejecución en el log y muestra la alerta en administración.

---

### Módulo 8 — Panel de alertas del mostrador

**Precondición:** Módulos 4, 5, 6 y 7 completos.

**Descripción:** La pantalla principal que el personal de mostrador ve al iniciar la jornada y durante la atención. Concentra toda la información relevante para operar sin necesidad de revisar múltiples secciones.

**Reglas de negocio cubiertas:** RN-20 (alertas internas al personal, no mensajes automáticos a socios). Refleja el pedido explícito de Marta: "que el sistema nos avise cuando hay algún problema."

**Lo que se implementa:**

**Sección 1: Reservas para avisar (acción requerida)**
Lista de reservas en estado "Personal alertado", ordenadas por fecha límite de retiro (las más urgentes primero). Para cada una: nombre del socio, título del libro, teléfono del socio, fecha límite de retiro. El personal puede marcar cada una como "Socio contactado" (registro informativo, no cambia el estado de la reserva).

**Sección 2: Préstamos atrasados**
Lista de préstamos atrasados, ordenados por días de atraso (mayor primero). Para cada uno: nombre del socio, título, días de atraso, teléfono del socio. Información para que el personal pueda contactar si lo considera necesario.

**Sección 3: Próximos vencimientos**
Préstamos activos que vencen en los próximos 3 días. Permite anticipar devoluciones antes de que se atrasen.

**Ficha del socio en el mostrador:**
Al buscar un socio para una operación, la pantalla de selección muestra junto a su nombre:
- Cantidad de préstamos activos y el límite de su tipo.
- Si tiene restricción activa: motivo y fecha de fin.
- Si tiene excepción vigente de exención: indicador visual.
- Cantidad de atrasos en los últimos 12 meses.
Esta información aparece antes de confirmar cualquier operación, sin que el personal tenga que buscarla en otra pantalla.

**Criterios de aceptación:**

- La pantalla principal del mostrador muestra las tres secciones al iniciar sesión.
- Un socio buscado para un préstamo muestra su restricción activa y el motivo antes de que el personal intente registrar el préstamo.
- Las reservas para avisar están ordenadas por urgencia (las que vencen antes, primero).
- Un préstamo registrado como devuelto desaparece de la sección de préstamos atrasados en la misma sesión.
- Un voluntario ve el panel del mostrador completo, pero no puede acceder a las secciones de administración desde él.

---

### Módulo 9 — Informes básicos

**Precondición:** Módulos 2, 3, 4 y 5 completos.

**Descripción:** Reportes operativos para que el personal y la Comisión Directiva puedan obtener información del estado de la biblioteca sin exportar datos a Excel.

**Lo que se implementa:**

| Informe | Filtros | Exportable |
|---|---|---|
| Préstamos activos | Por socio, por categoría, por días restantes | Sí (Excel / PDF) |
| Préstamos atrasados | Por días de atraso (rangos), por categoría | Sí |
| Historial de préstamos | Por período, por socio, por libro | Sí |
| Reservas pendientes | Por estado, por libro | No |
| Catálogo por estado | Por categoría, por estado de ejemplar | Sí |
| Socios activos e inactivos | Por tipo de socio, por período de alta | Sí |
| Circulación mensual | Cantidad de préstamos por mes (gráfico de barras) | No |
| Exportación completa de datos | Todos los socios, ejemplares, préstamos activos e historial en formato Excel | Solo Administrador |

Los informes exportables en Excel excluyen datos personales identificables de socios (solo se incluyen si el rol es Personal o Administrador y el informe es de uso interno).

La exportación completa de datos es la funcionalidad que permite a la institución ejecutar la exportación mensual manual recomendada en DA-04 (política de respaldo). Produce un archivo Excel con todas las tablas principales del sistema en hojas separadas, descargable desde el panel de administración sin necesidad de acceder a la base de datos directamente.

**Criterios de aceptación:**

- El informe de préstamos activos muestra exactamente los mismos préstamos que el estado de la base de datos en ese momento.
- La exportación a Excel de préstamos atrasados incluye nombre del socio, título, días de atraso, y teléfono de contacto.
- Un voluntario no puede exportar informes que incluyan datos personales de socios.
- El gráfico de circulación mensual muestra al menos los últimos 12 meses.
- El Administrador puede generar una exportación completa de datos en Excel. El archivo producido contiene todos los socios activos, el catálogo completo, los préstamos activos e históricos, y las reservas activas, en hojas separadas.

---

### Módulo 10 — Migración de datos

**Precondición:** Módulos 2 y 3 completos (catálogo y socios deben poder recibir datos). Este módulo puede comenzar en paralelo con los módulos 4 en adelante.

**Descripción:** El proceso de llevar los datos existentes (planillas Excel de catálogo, socios y préstamos) al sistema nuevo, con validación y corrección de los problemas identificados en el análisis de datos realizado durante el relevamiento.

**Problemas conocidos a resolver durante la migración:**

| Problema | Resolución |
|---|---|
| Inconsistencia de formato en categorías (Novela / NOVELA / novela) | Normalización manual o por script antes de la importación. Una sola forma canónica por categoría. |
| ISBN duplicados entre títulos distintos | Revisión manual con el personal de la biblioteca. Se mantiene el ISBN si es correcto; se elimina si fue ingresado como marcador. |
| Ejemplares sin autor cargado | Importar con autor vacío donde corresponda (colecciones sin autor identificable). El personal completa los que sí tienen autor. |
| Socios con email duplicado | Revisión caso a caso: si son la misma persona, consolidar. Si son personas distintas, limpiar el email de quien no lo usa para acceso al sistema. |
| Préstamos con referencias inexistentes (SocioID o EjemplarID inválidos) | Revisión con el personal. Si el registro no puede trazarse, se descarta de la migración histórica. |
| Campo Observaciones con información estructural (honorario, no cobrar multa) | Migrar como excepciones autorizadas formalizadas, no como texto libre. |

**Proceso de migración:**

1. El equipo de desarrollo prepara los scripts de importación en el entorno de staging.
2. La institución provee la planilla completa (no la muestra anonimizada).
3. Se ejecuta la importación en staging. El personal revisa una muestra representativa de los datos importados (mínimo 50 libros y 20 socios verificados contra los originales).
4. Se documentan y corrigen los errores detectados en la revisión.
5. El personal da conformidad formal por escrito de que los datos importados son correctos.
6. Se ejecuta la importación en producción (con datos reales).
7. Verificación final en producción: el personal revisa al menos 10 casos críticos (préstamos activos, socios con restricción, libros con reserva).
8. La biblioteca realiza la primera exportación manual de los datos de producción para su archivo local.

**Criterios de aceptación:**

- El catálogo importado en producción tiene exactamente la misma cantidad de títulos y ejemplares que la planilla de origen, menos los descartados explícitamente por el personal durante la revisión.
- Todos los socios activos de la planilla original están en el sistema, sin duplicados.
- Los préstamos activos al momento del corte están reflejados en el sistema (los libros que están fuera de la biblioteca al momento de migrar aparecen como "Prestados").
- Las excepciones históricas (S-0072 y socios honorarios) están cargadas como ExcepcionAutorizada.
- El personal confirma por escrito que los datos son correctos antes del go-live.

---

## Criterios de cierre de la Fase 1

La Fase 1 se considera terminada cuando:

1. Todos los módulos del 1 al 9 cumplen sus criterios de aceptación en el entorno de producción.
2. La migración de datos está completada y validada por el personal de la biblioteca.
3. El personal de mostrador completó al menos una jornada completa de operación real usando el sistema (préstamos, devoluciones, reservas) sin necesidad de recurrir al cuaderno o las planillas.
4. El proveedor técnico entregó a la institución:
   - Credenciales de acceso al repositorio de código (GitHub).
   - Credenciales de acceso al panel de Render.com.
   - Documento de arquitectura y modelo de dominio aprobados (ya producidos).
   - Manual de operación para el personal de mostrador.
   - Manual de administración del sistema (para el rol Administrador).
   - Procedimiento de exportación manual mensual de datos.
5. El primer backup manual post-migración está almacenado en la institución.

---

## Nota sobre el ciclo de desarrollo

Cada módulo sigue el mismo ciclo antes de darse por terminado:

**Desarrollar → Probar en staging → Revisión con el personal o Administrador según corresponda → Corregir → Desplegar a producción**

Ningún módulo se despliega a producción sin haber pasado por staging y sin haber sido revisado por al menos un usuario representativo del rol que lo utilizará.

---

---

## Anexo: cambios respecto a la versión 1

| Código | Tipo | Descripción |
|---|---|---|
| C-01 | Corrección necesaria | Agregada sección "Estrategia de testing" con cobertura obligatoria de las 10 reglas de negocio críticas. Gap identificado en revisión cruzada con la arquitectura: la corrección de testing fue comprometida en la revisión de arquitectura pero no se implementó en la v1 del plan. |
| C-02 | Corrección necesaria | RN-14 (registro de auditoría de cambios de configuración) asignada explícitamente al Módulo 1. En v1 era un requisito de la arquitectura sin módulo responsable. |
| C-03 | Corrección necesaria | RN-21 (alerta al personal cuando el cambio de modalidad deja reservas insatisfacibles) agregada al Módulo 2. No tenía cobertura en ningún módulo de v1. |
| C-04 | Corrección necesaria | Exportación completa de datos agregada al Módulo 9 como funcionalidad implementada. En v1 era solo una recomendación operativa sin implementación. |
| C-05 | Clarificación | Pre-checklist actualizado: wireframes marcados como completados, las dos preguntas abiertas marcadas como BLOQUEANTES con texto explícito sobre su impacto. |

*Plan de implementación Fase 1 — Versión 2. Documento final para uso por el equipo de desarrollo.*
