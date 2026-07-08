# Modelo de Dominio — Sistema de Gestión Bibliotecaria
**Versión 2 — Revisada y corregida. Lista para validación final de la Comisión Directiva.**

> Esta versión incorpora siete correcciones y cuatro clarificaciones identificadas durante la revisión interna del equipo. Los cambios respecto a la versión 1 se detallan en el Anexo al final del documento.

---

## Introducción

Este documento define el modelo de dominio del sistema: las entidades que lo componen, sus atributos esenciales, las relaciones entre ellas y las reglas de negocio que gobiernan su comportamiento.

El modelo de dominio no es un diseño técnico ni un esquema de base de datos. Es la representación formal de cómo funciona la biblioteca, expresada de manera que pueda ser verificada por la institución antes de comenzar el desarrollo. Todo lo que aquí se define tiene trazabilidad directa con el relevamiento validado.

### Principio organizador

Durante el relevamiento se estableció que el concepto central del sistema no es el préstamo, sino el **movimiento de ejemplares**: toda operación que implica que un ejemplar sale de su estado habitual de disponibilidad tiene una estructura común, independientemente de si es un préstamo a un socio, una custodia en un evento externo o un uso interno por parte de un voluntario. Este principio organiza el modelo completo.

---

## Áreas del dominio

El dominio se organiza en seis áreas con responsabilidades bien delimitadas:

1. **Catálogo** — El acervo bibliográfico y sus copias físicas.
2. **Socios** — Los miembros de la institución.
3. **Circulación** — Todos los movimientos de ejemplares.
4. **Excepciones y restricciones** — El mecanismo de autorización especial y penalización.
5. **Actividades y donaciones** — La agenda cultural y el ingreso de material.
6. **Operación del sistema** — Usuarios, permisos y configuración.

---

## Área 1: Catálogo

### 1.1 Libro

Representa la obra intelectual, independientemente de cuántas copias físicas existan.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | Generado por el sistema. |
| Título | Texto | Obligatorio. |
| ISBN | Texto | Opcional. Algunos libros antiguos no lo tienen. No se usa como identificador único. |
| Año de publicación | Año | Opcional. |
| Edición | Texto | Opcional. Para distinguir ediciones de un mismo título. |
| Idioma | Texto | Opcional. Útil para filtrado en el catálogo. |
| Descripción | Texto | Opcional. Sinopsis o nota de contenido. |
| Autores | Relación | Uno o más autores. Un libro puede no tener autor identificable (recopilaciones, obras anónimas). |
| Editorial | Relación | Opcional. |
| Categorías | Relación | Una o más categorías del esquema propio de la biblioteca. |

**Regla de diseño:** El ISBN no puede usarse como identificador único porque existen libros sin ISBN y casos detectados de ISBN duplicado entre títulos distintos en la muestra analizada.

---

### 1.2 Autor

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | Generado por el sistema. |
| Nombre | Texto | Obligatorio. |
| Notas | Texto | Opcional. |

---

### 1.3 Editorial

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | Generado por el sistema. |
| Nombre | Texto | Obligatorio. |

---

### 1.4 Categoría

Representa el esquema de clasificación propio de la biblioteca. Admite jerarquía para permitir mayor organización sin imponer un estándar externo.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | Generado por el sistema. |
| Nombre | Texto | Obligatorio. |
| Categoría padre | Relación | Opcional. Permite crear subcategorías. |

**Regla de diseño:** El esquema es propio y jerárquico. Queda preparado para mapeo a estándares externos en el futuro sin necesidad de reconstruir el modelo. La profundidad máxima recomendada es de dos niveles (categoría y subcategoría), suficiente para el volumen y perfil del acervo actual.

---

### 1.5 Ejemplar

Representa una copia física específica de un Libro. Es la unidad sobre la que operan todos los movimientos del sistema.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | Generado por el sistema. |
| Libro | Relación | Obligatorio. El título al que pertenece esta copia. |
| Estado operativo | Enumerado | Ver valores y naturaleza abajo. |
| Modalidad de acceso | Enumerado | Ver valores abajo. |
| Condición física | Texto | Opcional. Nota sobre el estado de conservación. |
| Fecha de ingreso | Fecha | Obligatorio. |
| Origen | Enumerado | Compra / Donación / Otro. |

**Estado operativo** — los estados se dividen en dos categorías según su naturaleza:

| Valor | Naturaleza | Significado |
|---|---|---|
| Disponible | Derivado (estado por defecto) | Sin estado manual activo y sin movimiento activo. Apto para su modalidad de acceso. |
| Prestado | Derivado (refleja Préstamo domiciliario activo) | Fuera de la biblioteca por préstamo a un socio. |
| En movimiento interno | Derivado (refleja Movimiento interno activo) | Retirado por personal o voluntario para uso interno. |
| En custodia externa | Derivado (refleja Custodia externa activa) | Fuera de la biblioteca para un evento o exposición. |
| En reparación | Manual (activado por el personal) | Temporalmente fuera de circulación por daño. |
| Extraviado | Manual (activado por el personal) | No localizado. |

**Regla de diseño — fuente de verdad del estado:** Los estados derivados (Prestado, En movimiento interno, En custodia externa) se obtienen consultando si existe un movimiento activo del tipo correspondiente para ese ejemplar. No se almacenan como campo independiente, sino que el campo Estado operativo solo almacena el estado manual cuando está activo. La condición Disponible se infiere cuando no hay estado manual ni movimiento activo. Esto garantiza que el estado del ejemplar nunca quede desincronizado con los movimientos registrados.

**Modalidad de acceso** — característica permanente del ejemplar, independiente de su estado operativo:

| Valor | Significado |
|---|---|
| Libre circulación | Puede prestarse bajo las reglas generales. |
| Solo sala | Solo puede consultarse dentro de la biblioteca. No sale bajo ninguna circunstancia. |
| Restringido a autorización | Requiere Excepción Autorizada vigente para cualquier salida de la biblioteca. |

**Regla de diseño:** Estado operativo y modalidad de acceso son conceptualmente independientes. Un ejemplar "Solo sala" puede estar "Disponible" (para consulta en sala) o "En reparación". La modalidad es una propiedad permanente del ejemplar; el estado operativo es su condición actual.

---

## Área 2: Socios

### 2.1 Tipo de Socio

Define las categorías de membresía con sus beneficios asociados. Los valores son configurables por el Administrador sin intervención técnica.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Nombre | Texto | Ej: "Estándar", "Honorario". |
| Límite de préstamos simultáneos | Número | Estándar: 3. Honorario: 5. Modificable desde administración. |
| Sujeto a restricción automática | Sí/No | Estándar: Sí. Honorario: No. El sistema alerta igualmente aunque no aplique restricción automática. |

---

### 2.2 Socio

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | Generado por el sistema. |
| Nombre principal | Texto | Obligatorio. |
| Nombres alternativos | Lista de texto | Para cambios de apellido u otras variantes conocidas. Habilita búsqueda tolerante a variaciones. |
| DNI | Texto | Opcional. Para verificación de identidad cuando no se reconoce al socio. |
| Email | Texto | Opcional. Dato de contacto para uso del personal. |
| Teléfono | Texto | Opcional. Dato de contacto para uso del personal. |
| Fecha de alta | Fecha | Obligatorio. |
| Estado | Enumerado | Activo / Inactivo. |
| Tipo de socio | Relación | Referencia a Tipo de Socio. |

**Regla de diseño:** El campo "Nombres alternativos" resuelve el problema operativo detectado durante el relevamiento: socios que cambiaron de apellido y figuran bajo nombres distintos en la planilla actual. La búsqueda debe ser tolerante a variaciones, no exacta por texto.

---

## Área 3: Circulación

La circulación agrupa todos los tipos de movimiento de ejemplares. Todos comparten una estructura común: qué ejemplar/es se mueven, quién o qué entidad es responsable, cuándo salieron, cuándo se espera que vuelvan, cuándo volvieron efectivamente.

---

### 3.1 Préstamo domiciliario

Movimiento de un ejemplar hacia un socio bajo las reglas de circulación general.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Ejemplar | Relación | El ejemplar específico prestado. |
| Socio | Relación | El socio que retira el libro. |
| Fecha de registro | Fecha/hora | Cuando se registró en el sistema. Puede diferir de la fecha de préstamo real. |
| Fecha de préstamo | Fecha | Cuando se entregó físicamente el ejemplar. Base para el cálculo del vencimiento. |
| Fecha de vencimiento | Fecha | Fecha de préstamo + 15 días. Se actualiza cada vez que el préstamo se renueva. |
| Fecha de devolución efectiva | Fecha | Vacía hasta que se devuelve. |
| Estado | Enumerado | Activo / Devuelto / Atrasado. |
| Registrado por | Relación | Usuario que registró la operación. |
| Es excepción de límite | Sí/No | Indica si se superó el límite de préstamos del socio para registrar este préstamo. |
| Motivo de excepción de límite | Texto | Obligatorio si es excepción de límite. |

**Regla de diseño — fecha de vencimiento vigente:** Prestamo.fecha_vencimiento siempre refleja el vencimiento actual efectivo. Cuando se registra una renovación, este campo se actualiza a la nueva fecha. La entidad Renovación preserva el historial completo de fechas anteriores para trazabilidad.

**Regla de diseño — desfase entre entrega y registro:** La separación entre Fecha de registro y Fecha de préstamo resuelve el escenario operativo real relevado: el libro se entrega físicamente y se registra minutos después cuando el mostrador está ocupado. El sistema admite este desfase sin tratarlo como error.

---

### 3.2 Renovación

Extensión del plazo de un préstamo activo. Registra el historial de renovaciones de un préstamo.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Préstamo | Relación | El préstamo que se renueva. |
| Fecha de renovación | Fecha | |
| Fecha de vencimiento anterior | Fecha | Trazabilidad: cuál era el vencimiento antes de renovar. |
| Nueva fecha de vencimiento | Fecha | Calculada: fecha de renovación + 15 días. Este valor se copia también a Prestamo.fecha_vencimiento. |
| Registrado por | Relación | Usuario. |

---

### 3.3 Reserva

Solicitud de un socio para ser alertado cuando un Libro vuelva a estar disponible para préstamo.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Libro | Relación | La obra solicitada, no un ejemplar específico. El sistema asigna el próximo ejemplar disponible. |
| Socio | Relación | |
| Fecha de reserva | Fecha | |
| Estado | Enumerado | Ver abajo. |
| Fecha de alerta al personal | Fecha | Cuando el sistema generó la alerta interna para que el personal contacte al socio. |
| Fecha límite de retiro | Fecha | Calculada: fecha de alerta al personal + 48 horas de atención. Determina hasta cuándo se aparta el ejemplar. |
| Ejemplar asignado | Relación | El ejemplar específico apartado para el socio, una vez disponible. |

**Estados de una Reserva:**

| Valor | Significado |
|---|---|
| Pendiente | El libro no está disponible. El socio espera su turno. |
| Personal alertado | Un ejemplar quedó disponible. El sistema alertó al personal para que contacte al socio. Cuenta el plazo de retiro. |
| Retirada | El socio retiró el libro. |
| Vencida por no retiro | El socio no retiró el libro dentro del plazo. El ejemplar se libera. |
| Cancelada | La reserva fue cancelada antes de resolverse. |

**Regla de diseño:** La reserva se realiza sobre un Libro (título), no sobre un Ejemplar. El sistema gestiona la asignación del ejemplar disponible y la cola de espera automáticamente.

**Regla de diseño — notificaciones:** El sistema no envía mensajes automáticos a socios. Cuando una reserva queda disponible, el sistema genera una alerta interna visible para el personal de mostrador. Es el personal quien contacta al socio por teléfono o mensaje, utilizando los datos de contacto registrados en el perfil del socio. Este diseño refleja la operatoria actual de la biblioteca y evita la complejidad de un sistema de mensajería saliente en la primera versión.

---

### 3.4 Préstamo institucional

Movimiento de uno o más ejemplares hacia una institución externa (escuela, jardín u otra), autorizado por la Comisión Directiva.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Institución | Relación | La institución receptora. |
| Fecha de préstamo | Fecha | |
| Fecha de retorno esperada | Fecha | |
| Fecha de retorno efectiva | Fecha | Vacía hasta la devolución. |
| Autorizado por | Relación | Usuario con rol Administrador. |
| Estado | Enumerado | Activo / Devuelto / Atrasado. |
| Observaciones | Texto | Opcional. |

**Ejemplar en préstamo institucional** *(entidad de asociación)*

Vincula un ejemplar específico a un préstamo institucional. Permite registrar que, dentro de un mismo préstamo, distintos ejemplares pueden devolverse en momentos diferentes.

| Atributo | Tipo | Notas |
|---|---|---|
| Préstamo institucional | Relación | |
| Ejemplar | Relación | |
| Fecha de devolución efectiva | Fecha | Opcional. Vacía si aún no fue devuelto. Permite devoluciones parciales. |

---

### 3.5 Movimiento interno

Retiro temporal de uno o más ejemplares por parte de personal o voluntarios para uso dentro de la misma institución (actividades, muestras, exposiciones internas).

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Responsable | Relación | Usuario que retira el material. |
| Propósito | Texto | Descripción del uso. |
| Actividad asociada | Relación | Opcional. Si se vincula a una actividad registrada. |
| Fecha de inicio | Fecha | |
| Fecha de retorno esperada | Fecha | |
| Fecha de retorno efectiva | Fecha | Vacía hasta la finalización. |
| Estado | Enumerado | Activo / Finalizado. |

**Ejemplar en movimiento interno** *(entidad de asociación)*

| Atributo | Tipo | Notas |
|---|---|---|
| Movimiento interno | Relación | |
| Ejemplar | Relación | |
| Fecha de retorno efectiva | Fecha | Opcional. Permite retornos parciales del lote. |

---

### 3.6 Custodia externa

Salida temporal de uno o más ejemplares para un evento o exposición fuera de la biblioteca. La responsabilidad permanece en la institución; la custodia física queda a cargo de la organización del evento o del personal de la biblioteca que participa.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Institución o evento custodio | Texto | Nombre de la entidad o evento responsable de la custodia física. |
| Persona de contacto | Texto | Opcional. |
| Actividad asociada | Relación | Opcional. Vínculo con la actividad del sistema si está registrada. |
| Fecha de salida | Fecha | |
| Fecha de retorno esperada | Fecha | Coincide típicamente con el fin de la actividad vinculada. |
| Fecha de retorno efectiva | Fecha | Vacía hasta la devolución. |
| Estado | Enumerado | Activa / Finalizada / Atrasada. |
| Observaciones | Texto | Opcional. |

**Ejemplar en custodia externa** *(entidad de asociación)*

| Atributo | Tipo | Notas |
|---|---|---|
| Custodia externa | Relación | |
| Ejemplar | Relación | |
| Fecha de retorno efectiva | Fecha | Opcional. Permite retornos parciales. |

**Regla de diseño:** La Custodia Externa no genera restricciones ni penalizaciones. El sistema genera una alerta interna al personal cuando se supera la fecha de retorno esperada.

---

### Invariante de circulación

**Un ejemplar solo puede participar en un movimiento activo a la vez.** Esta es la regla de integridad más importante del sistema. Antes de registrar cualquier nuevo movimiento (Préstamo domiciliario, Préstamo institucional, Movimiento interno o Custodia externa), el sistema verifica que no exista ningún movimiento activo de ninguno de esos cuatro tipos para el ejemplar involucrado. La verificación es cruzada entre todos los tipos de movimiento. Esta regla no admite excepciones de ningún tipo.

---

## Área 4: Excepciones y restricciones

### 4.1 Excepción autorizada

Mecanismo único para registrar toda dispensa a las reglas generales del sistema, con trazabilidad completa de quién la autorizó, cuándo, por qué y durante cuánto tiempo.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Tipo de excepción | Enumerado | Ver abajo. |
| Entidad afectada | Relación | Puede ser un Socio o un Ejemplar, según el tipo. |
| Autorizado por | Relación | Usuario con rol Administrador. |
| Fecha de autorización | Fecha | |
| Motivo | Texto | Obligatorio. |
| Fecha de inicio | Fecha | |
| Fecha de fin | Fecha | Opcional. Si está vacía, la excepción es indefinida hasta su revocación explícita. |
| Estado | Enumerado | Vigente / Vencida / Revocada. |
| Revocado por | Relación | Usuario que revocó la excepción, si corresponde. |
| Fecha de revocación | Fecha | Si fue revocada. |

**Tipos de excepción:**

| Tipo | Descripción | Entidad afectada |
|---|---|---|
| Exención de restricción por atraso | El socio no queda sujeto a suspensión automática por devoluciones tardías. El sistema informa los atrasos igualmente. | Socio |
| Límite de préstamo especial | El socio puede tener activos más préstamos que los definidos por su Tipo de Socio. | Socio |
| Autorización de salida de material restringido | Un ejemplar con modalidad "Restringido a autorización" puede prestarse o salir. Incluye destinatario, período y motivo. | Ejemplar |

**Casos activos que migran al sistema desde el relevamiento:**

- Excepción de penalización del socio histórico: tipo "Exención de restricción por atraso", vigencia indefinida, motivo: colaboración histórica con la institución, autoridad: Comisión Directiva.
- Las autorizaciones futuras sobre la Colección Patrimonial se registrarán como tipo "Autorización de salida de material restringido" para cada caso específico.

---

### 4.2 Restricción de socio

Suspensión temporal del derecho a nuevos préstamos domiciliarios, generada automáticamente por el sistema en el momento de una devolución tardía, o manualmente por el personal.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Socio | Relación | |
| Tipo | Enumerado | Automática / Manual. |
| Fecha de inicio | Fecha | Para restricciones automáticas: fecha de la devolución tardía. |
| Fecha de fin | Fecha | Para automáticas: fecha de inicio + días de restricción calculados (1 día de restricción por día de atraso, con tope configurable). Para manuales: definida por el usuario. |
| Días de atraso de origen | Número | Solo para restricciones automáticas. |
| Préstamo de origen | Relación | El préstamo devuelto con atraso que originó la restricción. |
| Generada por | Relación | Sistema o Usuario. |
| Observaciones | Texto | Opcional. |

**Regla:** Los socios con Tipo de Socio "Honorario" o con una Excepción Autorizada vigente de tipo "Exención de restricción por atraso" no reciben restricciones automáticas. El sistema registra igualmente el atraso en el historial y lo muestra al personal como alerta.

---

### 4.3 Historial de atrasos

Registro acumulado de devoluciones tardías por socio. Sirve como indicador objetivo de reincidencia para el personal de mostrador, independientemente de si el socio tiene o no una restricción activa.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Socio | Relación | |
| Préstamo | Relación | El préstamo devuelto con atraso. |
| Días de atraso | Número | |
| Fecha de devolución efectiva | Fecha | |
| Restricción generada | Sí/No | Indica si este atraso generó una restricción automática o fue eximido. |

**Regla de diseño:** El historial de atrasos es una herramienta de información para el personal de mostrador, no un mecanismo de penalización en sí mismo. En el momento de la atención, el sistema muestra cuántos atrasos acumuló el socio en los últimos doce meses, haya o no recibido restricciones por ellos.

---

## Área 5: Actividades y donaciones

### 5.1 Institución

Entidad externa con la que la biblioteca interactúa: co-organización de actividades, préstamos institucionales, custodias externas.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Nombre | Texto | Obligatorio. |
| Tipo | Enumerado | Escuela / Jardín / Biblioteca / Municipio / Otra. |
| Persona de contacto | Texto | Opcional. |
| Teléfono / Email | Texto | Opcional. |

---

### 5.2 Actividad

Evento cultural, educativo o comunitario organizado por la biblioteca, con o sin participación de instituciones externas.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Título | Texto | Obligatorio. |
| Tipo | Enumerado | Charla / Taller / Presentación de libro / Cine debate / Exposición / Visita escolar / Feria / Otro. |
| Modalidad de participación | Enumerado | Abierta / Con inscripción. |
| Fecha de inicio | Fecha | Obligatorio. |
| Fecha de fin | Fecha | Obligatorio. Para actividades de un solo día, igual a fecha de inicio. |
| Descripción | Texto | Opcional. |
| Es gratuita | Sí/No | |
| Monto de referencia | Número | Opcional. Solo si no es gratuita. Dato informativo; la gestión económica queda fuera del sistema. |
| Cupo máximo | Número | Opcional. Solo si requiere inscripción. |
| Institución co-organizadora | Relación | Opcional. Referencia a Institución. |

---

### 5.3 Inscripción a actividad

Registro de participación en una actividad que requiere inscripción previa.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Actividad | Relación | |
| Tipo de inscripto | Enumerado | Socio / Persona externa / Institución. |
| Socio | Relación | Solo si el tipo es Socio. |
| Nombre externo | Texto | Solo si el tipo es Persona externa. |
| Teléfono externo | Texto | Opcional, si el tipo es Persona externa. |
| Institución | Relación | Solo si el tipo es Institución. |
| Cantidad de participantes | Número | Por defecto 1. Mayor para inscripciones institucionales (ej: grupo escolar). |
| Asistencia efectiva | Sí/No | Opcional. Registrable al finalizar la actividad si el coordinador lo requiere. |
| Fecha de inscripción | Fecha | |

---

### 5.4 Donante

Persona física o institución que realiza una donación a la biblioteca.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Tipo | Enumerado | Persona física / Institución. |
| Nombre | Texto | Obligatorio. |
| Contacto | Texto | Opcional. |

---

### 5.5 Donación

Ingreso de material (bibliográfico o no) procedente de un donante externo.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Donante | Relación | |
| Fecha | Fecha | |
| Reconocimiento institucional | Sí/No | Indica si la Comisión envió nota de agradecimiento. |
| Condición del donante | Texto | Opcional. Preferencias expresadas por el donante sobre el destino del material (ej: mantener colección identificada con nombre de familia). |
| Observaciones | Texto | Opcional. |

---

### 5.6 Ítem de donación

Cada pieza o conjunto de material incluido en una donación y su resultado de evaluación.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Donación | Relación | |
| Tipo de material | Enumerado | Bibliográfico / No bibliográfico. |
| Descripción | Texto | Para bibliográfico: título, autor, cantidad. Para no bibliográfico: tipo de bien. |
| Estado de evaluación | Enumerado | Pendiente / Aceptado / Descartado. |
| Motivo de descarte | Texto | Opcional. |
| Ejemplar generado | Relación | Opcional. El Ejemplar creado en el catálogo si el ítem fue aceptado. Solo para material bibliográfico. Vínculo único entre donación y catálogo. |

**Regla de diseño:** El material no bibliográfico se registra para preservar trazabilidad institucional, pero no genera entradas en el catálogo. El vínculo entre una donación y los ejemplares que generó se navega exclusivamente desde Ítem de donación → Ejemplar (no en sentido inverso), eliminando la posibilidad de referencias inconsistentes.

---

## Área 6: Operación del sistema

### 6.1 Usuario

Persona con acceso al sistema interno de la biblioteca.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Nombre | Texto | |
| Email | Texto | Para acceso al sistema. |
| Rol | Enumerado | Administrador / Personal / Voluntario. |
| Estado | Enumerado | Activo / Inactivo. |

**Permisos por rol:**

| Capacidad | Administrador | Personal | Voluntario |
|---|---|---|---|
| Registrar préstamos, devoluciones, reservas | ✓ | ✓ | ✓ |
| Gestionar catálogo y ejemplares | ✓ | ✓ | — |
| Gestionar socios | ✓ | ✓ | — |
| Registrar actividades y donaciones | ✓ | ✓ | — |
| Crear / modificar excepciones autorizadas | ✓ | — | — |
| Registrar préstamos institucionales | ✓ | — | — |
| Modificar configuración del sistema | ✓ | — | — |
| Gestionar usuarios | ✓ | — | — |

---

### 6.2 Parámetro de configuración

Valores operativos del sistema modificables por el Administrador sin intervención técnica.

| Parámetro | Valor inicial | Descripción |
|---|---|---|
| Límite de préstamos — Estándar | 3 | Máximo de préstamos simultáneos para socios estándar. |
| Límite de préstamos — Honorario | 5 | Máximo de préstamos simultáneos para socios honorarios. |
| Plazo de préstamo (días) | 15 | Días corridos desde la fecha de préstamo hasta el vencimiento. |
| Ventana de retiro de reserva (horas de atención) | 48 | Horas de atención al público disponibles para retirar un libro reservado desde la alerta al personal. |
| Días de atención al público | Lunes a viernes | Días de la semana en que la biblioteca atiende. Necesario para computar la ventana de retiro de reserva. |
| Tope máximo de restricción (días) | 30 | Máximo de días de restricción automática, independientemente del atraso acumulado. |

Todos los valores son modificables por el Administrador. Cada cambio queda registrado con fecha y usuario responsable.

---

## Reglas de negocio consolidadas

| Código | Regla |
|---|---|
| RN-01 | Un socio no puede tener préstamos domiciliarios activos superiores al límite de su Tipo de Socio. El sistema alerta al personal; no bloquea automáticamente. Si el personal decide continuar, debe registrar una justificación. |
| RN-02 | El plazo de todo préstamo domiciliario es de 15 días desde la fecha de préstamo (no desde la fecha de registro). |
| RN-03 | Una renovación solo es posible si el Libro no tiene Reservas en estado Pendiente o Personal alertado. |
| RN-04 | Un ejemplar solo puede participar en un movimiento activo a la vez, de cualquier tipo. El sistema verifica esta condición de forma cruzada entre los cuatro tipos de movimiento. Esta regla no admite excepciones. |
| RN-05 | Cuando un ejemplar reservado queda disponible, el sistema genera una alerta interna para el personal. La ventana para que el socio retire el libro es de 48 horas de atención al público desde la alerta. Al vencerse, el ejemplar se libera y pasa al siguiente socio en la cola, o vuelve a Disponible si no hay más reservas. |
| RN-06 | Un socio con restricción activa no puede recibir nuevos préstamos domiciliarios, salvo que tenga una Excepción Autorizada vigente de tipo "Exención de restricción". |
| RN-07 | Los socios de Tipo Honorario no reciben restricciones automáticas por atraso. El sistema registra el atraso en el historial y lo muestra al personal como alerta. |
| RN-08 | Un ejemplar con modalidad de acceso "Solo sala" no puede participar en préstamos domiciliarios, préstamos institucionales, movimientos internos que impliquen salida de la biblioteca, ni custodias externas. |
| RN-09 | Un ejemplar con modalidad de acceso "Restringido a autorización" requiere una Excepción Autorizada vigente para cualquier salida de la biblioteca. |
| RN-10 | Las Excepciones Autorizadas solo pueden ser creadas, modificadas o revocadas por usuarios con rol Administrador. |
| RN-11 | Toda Excepción Autorizada debe registrar: quién autorizó, fecha, motivo. La fecha de fin es opcional; si está vacía, la excepción es indefinida hasta su revocación explícita. |
| RN-12 | La devolución de un ejemplar no requiere que la realice la misma persona que efectuó el préstamo. |
| RN-13 | El registro de un préstamo puede realizarse con posterioridad a la entrega física del ejemplar. La fecha de préstamo es editable en el momento del registro. |
| RN-14 | Los parámetros de configuración del sistema pueden ser modificados por el Administrador. Todo cambio queda registrado con fecha y responsable. |
| RN-15 | La gestión económica de actividades aranceladas está fuera del alcance del sistema. El sistema registra si una actividad es gratuita o arancelada y, en el segundo caso, el monto de referencia como dato informativo. |
| RN-16 | El material no bibliográfico recibido en donaciones se registra a efectos de trazabilidad institucional, pero no genera entradas en el catálogo. |
| RN-17 | La Custodia Externa no genera restricciones ni penalizaciones. El sistema genera una alerta interna al personal cuando se supera la fecha de retorno esperada. |
| RN-18 | La restricción automática se genera en el momento en que se registra la devolución tardía de un préstamo, no cuando el préstamo vence. Su duración es de un día de restricción por cada día de atraso, con un tope máximo configurable. |
| RN-19 | Al renovar un préstamo, Prestamo.fecha_vencimiento se actualiza a la nueva fecha calculada. La entidad Renovación preserva la fecha anterior para trazabilidad. El estado del préstamo permanece "Activo" durante toda su vigencia, independientemente del número de renovaciones. |
| RN-20 | El sistema no envía mensajes automáticos a socios. Todas las alertas (reserva disponible, atraso, retorno de custodia vencido) son notificaciones internas visibles para el personal de mostrador. El contacto con el socio es responsabilidad del personal. |
| RN-21 | Si un cambio en la modalidad de acceso de ejemplares impide satisfacer reservas pendientes (por ejemplo, todos los ejemplares de un título pasan a "Solo sala"), el sistema alerta al personal para que cancele y gestione esas reservas manualmente. |

---

## Decisiones de diseño con justificación

**D-01 — Movimiento de ejemplares como concepto central**
El modelo no está organizado alrededor del préstamo, sino alrededor del movimiento de ejemplares. Esto refleja la operatoria real relevada y evita forzar tipos de movimiento cualitativamente distintos (custodia externa, uso interno) a encajar en estructuras de préstamo que no les corresponden.

**D-02 — Separación entre Libro y Ejemplar**
El registro bibliográfico (Libro) es distinto de la copia física (Ejemplar). Esta separación permite manejar múltiples copias del mismo título, estados distintos por copia, y reservas sobre el título sin requerir especificar qué copia.

**D-03 — Mecanismo único de excepciones**
En lugar de modelar por separado la condición honoraria, la exención individual de penalización y la autorización patrimonial, se define un único mecanismo de Excepción Autorizada con tipos. Esto garantiza trazabilidad uniforme para cualquier excepción futura sin requerir modificación del modelo.

**D-04 — Configuración sin intervención técnica**
Los parámetros operativos clave (límites, plazos, ventanas, topes) son configurables desde la administración del sistema. Los beneficios por Tipo de Socio son parte de esta configuración. Respeta el requisito explícito de la Comisión Directiva de poder modificar esos valores sin depender de un proveedor técnico.

**D-05 — Alertas en lugar de bloqueos**
Para situaciones donde hoy existe criterio humano (exceder el límite de préstamos, atraso de un socio conocido), el sistema muestra una advertencia y permite continuar con justificación registrada. Preserva la flexibilidad operativa real de la biblioteca sin perder trazabilidad.

**D-06 — Clasificación propia conservada**
Se mantiene el esquema de categorías propio de la biblioteca, formalizado como estructura jerárquica de dos niveles recomendados. No se adopta un estándar externo dado que el beneficio no justifica el costo de reclasificar 3.500 ejemplares sin personal especializado.

**D-07 — ISBN no es identificador único**
El ISBN no se usa como identificador de Libro porque existen libros sin ISBN y se detectaron duplicados de ISBN entre títulos distintos en la muestra analizada.

**D-08 — Notificaciones a socios fuera del alcance de la primera versión**
El sistema no envía mensajes automáticos a socios. Todas las alertas son internas al personal. Esta decisión elimina la necesidad de integrar servicios de mensajería (email, SMS, WhatsApp) en la arquitectura inicial, reduce significativamente la complejidad del sistema y es coherente con la operatoria actual de la biblioteca, donde el contacto siempre pasa por el personal.

**D-09 — Estado de Ejemplar parcialmente derivado**
Los estados que reflejan movimientos activos (Prestado, En movimiento interno, En custodia externa) son derivados de las entidades de movimiento, no de un campo propio del Ejemplar. Los estados manuales (En reparación, Extraviado) sí se almacenan como campo. Esta separación garantiza consistencia entre el estado del Ejemplar y los movimientos activos sin necesidad de sincronización explícita.

**D-10 — Vínculo donación-catálogo unidireccional**
El vínculo entre una donación y los ejemplares generados se navega solo desde Ítem de donación hacia Ejemplar, no en sentido inverso. Esto elimina la posibilidad de inconsistencias entre dos referencias que deben mantenerse sincronizadas, sin perder ninguna capacidad operativa real.

---

## Pendiente de relevamiento

Los siguientes aspectos no forman parte de este modelo porque no fueron relevados en la etapa actual:

- **Portal de socios**: catálogo público, consulta de estado de préstamos propios, historial. Requerirá un mecanismo de autenticación para socios, distinto del sistema de usuarios internos.
- **Estadísticas e informes**: indicadores de circulación, actividad de socios, crecimiento del catálogo. No requiere nuevas entidades, sino consultas sobre las ya definidas.
- **Proceso formal de evaluación de donaciones**: criterios estructurados para aceptación o descarte de material, que la institución acordó formalizar durante el proyecto.

---

## Anexo: Cambios respecto a la versión 1

| Código | Tipo | Descripción |
|---|---|---|
| C-01 | Corrección | Eliminado el estado "Renovado" de Préstamo. El estado del préstamo es siempre Activo mientras esté vigente, independientemente de las renovaciones. |
| C-02 | Corrección | Eliminado el atributo "Donación de origen" de Ejemplar. El vínculo donación-catálogo es unidireccional desde Ítem de donación. |
| C-03 | Corrección | Documentada la naturaleza de cada estado de Ejemplar (manual vs. derivado) y su fuente de verdad. Agregada decisión D-09. |
| C-04 | Corrección | Agregada RN-18: la restricción automática se genera al momento de la devolución tardía, no cuando el préstamo vence. Agregada fórmula de cálculo de duración. |
| C-05 | Corrección | Agregado parámetro "Días de atención al público" y "Tope máximo de restricción". Redefinida la ventana de reserva como "horas de atención" en lugar de "horas hábiles" abstractas. |
| C-06 | Corrección | Redefinido el mecanismo de notificación de reservas como alerta interna al personal. Eliminada la implicancia de mensajería automática. Agregadas RN-20 y D-08. |
| C-07 | Corrección | Nombradas y definidas las tres entidades de asociación M:N para movimientos colectivos: Ejemplar en préstamo institucional, Ejemplar en movimiento interno, Ejemplar en custodia externa. |
| CL-01 | Clarificación | Documentado explícitamente que al renovar un préstamo, Prestamo.fecha_vencimiento se actualiza. Agregada RN-19. |
| CL-02 | Clarificación | Documentada la profundidad máxima recomendada de Categoría (2 niveles). |
| CL-03 | Corrección | Actividad.fecha_fin pasa de "Opcional para actividades de un solo día" a "Obligatorio, igual a fecha_inicio para eventos de un solo día." |
| CL-04 | Clarificación | Agregada RN-21: el sistema alerta al personal cuando un cambio de modalidad deja reservas pendientes sin posibilidad de satisfacerse. |
| M-01 | Mejora | Agregados atributos opcionales Edición e Idioma en Libro. |
| M-02 | Mejora | Agregado atributo "Restricción generada" en Historial de atrasos para trazabilidad de exenciones. |
| D-10 | Decisión nueva | Documentada formalmente la decisión de vínculo unidireccional entre donación y catálogo. |

---

*Documento elaborado a partir del relevamiento consolidado validado (v2), sesiones de relevamiento de actividades y donaciones, y revisión interna completa del equipo. Versión 2 — para validación final de la Comisión Directiva antes de iniciar la etapa de arquitectura.*
