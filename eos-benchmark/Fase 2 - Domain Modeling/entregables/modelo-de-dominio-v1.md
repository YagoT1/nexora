# Modelo de Dominio — Sistema de Gestión Bibliotecaria
**Versión 1 — Para revisión de la Comisión Directiva**

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
| Descripción | Texto | Opcional. Sinopsis o nota de contenido. |
| Autores | Relación | Uno o más autores. Un libro puede no tener autor identificable (recopilaciones, obras anónimas). |
| Editorial | Relación | Opcional. |
| Categorías | Relación | Una o más categorías del esquema propio de la biblioteca. |

**Regla de diseño:** El ISBN no puede usarse como identificador único porque existen libros sin ISBN y casos detectados de ISBN duplicado entre títulos distintos.

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

Representa el esquema de clasificación propio de la biblioteca. Admite jerarquía (categoría y subcategoría) para permitir mayor organización sin imponer un estándar externo.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | Generado por el sistema. |
| Nombre | Texto | Obligatorio. |
| Categoría padre | Relación | Opcional. Permite crear subcategorías. |

**Regla de diseño:** El esquema es propio y jerárquico. Queda preparado para mapeo a estándares externos (CDU u otros) en el futuro, sin necesidad de reconstruir el modelo.

---

### 1.5 Ejemplar

Representa una copia física específica de un Libro. Es la unidad sobre la que operan todos los movimientos del sistema.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | Generado por el sistema. |
| Libro | Relación | Obligatorio. El título al que pertenece esta copia. |
| Estado operativo | Enumerado | Ver valores posibles abajo. |
| Modalidad de acceso | Enumerado | Ver valores posibles abajo. |
| Condición física | Texto | Opcional. Nota sobre el estado de conservación. |
| Fecha de ingreso | Fecha | Obligatorio. |
| Origen | Enumerado | Compra / Donación / Otro. |
| Donación de origen | Relación | Opcional. Si proviene de una donación, referencia al registro correspondiente. |

**Estado operativo** (cambia durante la operación):

| Valor | Significado |
|---|---|
| Disponible | En la biblioteca, apto para su modalidad de acceso. |
| Prestado | Fuera de la biblioteca por préstamo domiciliario. |
| En reparación | Temporalmente fuera de circulación por daño. |
| Extraviado | No localizado. |
| En movimiento interno | Retirado por personal o voluntario para uso interno. |
| En custodia externa | Fuera de la biblioteca para un evento o exposición. |

**Modalidad de acceso** (característica permanente del ejemplar):

| Valor | Significado |
|---|---|
| Libre circulación | Puede prestarse bajo las reglas generales. |
| Solo sala | Solo puede consultarse dentro de la biblioteca. No sale. |
| Restringido a autorización | Requiere autorización explícita de la Comisión para cualquier salida. |

**Regla de diseño:** Estado operativo y modalidad de acceso son independientes. Un ejemplar "Solo sala" puede estar "Disponible" (para consulta en sala) o "En reparación". La modalidad no cambia con las operaciones; el estado sí.

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
| Nombres alternativos | Lista de texto | Para cambios de apellido u otras variantes conocidas. Habilita búsqueda tolerante. |
| DNI | Texto | Opcional. Para verificación de identidad cuando no se reconoce al socio. |
| Email | Texto | Opcional. Para notificaciones de reservas y avisos. |
| Teléfono | Texto | Opcional. Para contacto en atrasos y notificaciones de reserva. |
| Fecha de alta | Fecha | Obligatorio. |
| Estado | Enumerado | Activo / Inactivo. |
| Tipo de socio | Relación | Referencia a Tipo de Socio. |

**Regla de diseño:** El campo "Nombres alternativos" resuelve el problema operativo detectado durante el relevamiento: socios que cambiaron de apellido y figuran bajo nombres distintos en la planilla actual. La búsqueda debe ser tolerante a variaciones, no exacta.

---

## Área 3: Circulación

La circulación agrupa todos los tipos de movimiento de ejemplares. Todos comparten una estructura común: qué ejemplar/es, quién o qué entidad es responsable, cuándo salió, cuándo se espera que vuelva, cuándo volvió efectivamente.

---

### 3.1 Préstamo domiciliario

Movimiento de un ejemplar hacia un socio bajo las reglas de circulación general.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Ejemplar | Relación | El ejemplar específico prestado. |
| Socio | Relación | El socio que retira el libro. |
| Fecha de registro | Fecha/hora | Cuando se registró en el sistema. Puede diferir de la fecha de préstamo real. |
| Fecha de préstamo | Fecha | Cuando se entregó físicamente el ejemplar. |
| Fecha de vencimiento | Fecha | Calculada: fecha de préstamo + 15 días. |
| Fecha de devolución efectiva | Fecha | Vacía hasta que se devuelve. |
| Estado | Enumerado | Activo / Devuelto / Atrasado / Renovado. |
| Registrado por | Relación | Usuario que registró la operación. |
| Es excepción de límite | Sí/No | Indica si se superó el límite de préstamos del socio. |
| Motivo de excepción de límite | Texto | Obligatorio si es excepción de límite. |

**Regla de diseño:** La separación entre "Fecha de registro" y "Fecha de préstamo" resuelve el escenario operativo real relevado: el libro se entrega físicamente y se registra minutos después cuando el mostrador está ocupado. El sistema admite este desfase sin tratarlo como error.

---

### 3.2 Renovación

Extensión del plazo de un préstamo activo.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Préstamo | Relación | El préstamo que se renueva. |
| Fecha de renovación | Fecha | |
| Fecha de vencimiento anterior | Fecha | Trazabilidad del historial de plazos. |
| Nueva fecha de vencimiento | Fecha | Calculada: fecha de renovación + 15 días. |
| Registrado por | Relación | Usuario. |

---

### 3.3 Reserva

Solicitud de un socio para ser notificado cuando un Libro vuelva a estar disponible.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Libro | Relación | La obra solicitada, no un ejemplar específico. El sistema asigna el próximo ejemplar disponible. |
| Socio | Relación | |
| Fecha de reserva | Fecha | |
| Estado | Enumerado | Ver abajo. |
| Fecha de notificación | Fecha | Cuando se avisó al socio que el libro está disponible. |
| Fecha límite de retiro | Fecha | Calculada: fecha de notificación + 48 horas hábiles. |
| Ejemplar asignado | Relación | El ejemplar específico apartado para el socio, una vez disponible. |

**Estados de una Reserva:**

| Valor | Significado |
|---|---|
| Pendiente | El libro sigue prestado a otro socio. |
| Notificada | El libro está disponible y se avisó al socio. Cuenta el plazo de 48 horas. |
| Retirada | El socio retiró el libro. |
| Vencida por no retiro | El socio no retiró el libro dentro del plazo. El ejemplar se libera. |
| Cancelada | La reserva fue cancelada antes de resolverse. |

**Regla de diseño:** La reserva se realiza sobre un Libro (título), no sobre un Ejemplar. El sistema gestiona la asignación del ejemplar disponible y la cola de espera automáticamente.

---

### 3.4 Préstamo institucional

Movimiento de uno o más ejemplares hacia una institución externa (escuela, jardín u otra), autorizado por la Comisión Directiva.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Institución | Relación | La institución receptora. |
| Ejemplares | Relación | Uno o más ejemplares involucrados. |
| Fecha de préstamo | Fecha | |
| Fecha de retorno esperada | Fecha | |
| Fecha de retorno efectiva | Fecha | Vacía hasta la devolución. |
| Autorizado por | Relación | Usuario con rol Administrador. |
| Estado | Enumerado | Activo / Devuelto / Atrasado. |
| Observaciones | Texto | Opcional. |

---

### 3.5 Movimiento interno

Retiro temporal de uno o más ejemplares por parte de personal o voluntarios para uso dentro de la misma institución (actividades, muestras, exposiciones internas).

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Responsable | Relación | Usuario o voluntario que retira el material. |
| Ejemplares | Relación | Uno o más ejemplares. |
| Propósito | Texto | Descripción del uso. |
| Actividad asociada | Relación | Opcional. Si se vincula a una actividad registrada. |
| Fecha de inicio | Fecha | |
| Fecha de retorno esperada | Fecha | |
| Fecha de retorno efectiva | Fecha | Vacía hasta la finalización. |
| Estado | Enumerado | Activo / Finalizado. |

---

### 3.6 Custodia externa

Salida temporal de uno o más ejemplares para un evento o exposición fuera de la biblioteca. A diferencia del préstamo, la responsabilidad permanece en la institución, y la custodia física queda a cargo de la organización del evento o del personal de la biblioteca que participa.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Institución o evento custodio | Texto | Nombre de la entidad o evento responsable de la custodia física. |
| Persona de contacto | Texto | Opcional. |
| Actividad asociada | Relación | Opcional. Vínculo con la actividad del sistema si está registrada. |
| Ejemplares | Relación | Uno o más ejemplares bajo custodia. |
| Fecha de salida | Fecha | |
| Fecha de retorno esperada | Fecha | Coincide típicamente con el fin de la actividad. |
| Fecha de retorno efectiva | Fecha | Vacía hasta la devolución. |
| Estado | Enumerado | Activa / Finalizada / Atrasada. |
| Observaciones | Texto | Opcional. |

**Regla de diseño:** La Custodia Externa no genera restricciones ni penalizaciones por atraso en el socio (no hay socio involucrado). El sistema genera una alerta al personal cuando se supera la fecha de retorno esperada.

---

### Invariante de circulación

**Un ejemplar solo puede participar en un movimiento activo a la vez.** Esta es la regla de integridad más importante del sistema: en cualquier momento dado, para cualquier ejemplar, puede existir a lo sumo un Préstamo domiciliario activo, o un Préstamo institucional activo, o un Movimiento interno activo, o una Custodia externa activa — nunca más de uno. El sistema debe verificar y garantizar esta condición en toda operación.

---

## Área 4: Excepciones y restricciones

### 4.1 Excepción autorizada

Mecanismo único para registrar toda dispensa a las reglas generales del sistema, con trazabilidad completa de quién la autorizó, cuándo, por qué y durante cuánto tiempo.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Tipo de excepción | Enumerado | Ver abajo. |
| Entidad afectada | Relación polimórfica | Puede ser un Socio o un Ejemplar, según el tipo. |
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
| Exención de restricción por atraso | El socio no queda sujeto a suspensión automática. El sistema igualmente informa los atrasos. | Socio |
| Límite de préstamo especial | El socio tiene un límite de préstamos distinto al de su tipo. | Socio |
| Autorización de salida de material restringido | Un ejemplar con modalidad "Restringido a autorización" puede prestarse o salir. Incluye destinatario, período y motivo. | Ejemplar |

**Casos activos que migran al sistema desde el relevamiento:**

- Condición de Honorario para los socios actualmente identificados como tales (tipo "Límite de préstamo especial" implícito en el Tipo de Socio, no requiere excepción individual).
- Excepción de penalización del socio histórico (tipo "Exención de restricción por atraso", vigencia indefinida, motivo: colaboración histórica con la institución, autoridad: Comisión Directiva).
- Las autorizaciones futuras sobre la Colección Patrimonial se registrarán como tipo "Autorización de salida de material restringido" para cada caso.

---

### 4.2 Restricción de socio

Suspensión temporal del derecho a nuevos préstamos domiciliarios, generada automáticamente por el sistema o manualmente por el personal.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Socio | Relación | |
| Tipo | Enumerado | Automática (generada por el sistema) / Manual (registrada por un usuario). |
| Fecha de inicio | Fecha | |
| Fecha de fin | Fecha | Calculada para automáticas (proporcional a días de atraso). Definida manualmente para las manuales. |
| Días de atraso de origen | Número | Solo para restricciones automáticas. |
| Préstamo de origen | Relación | Opcional. El préstamo que originó la restricción. |
| Generada por | Relación | Sistema o Usuario. |
| Observaciones | Texto | Opcional. |

**Regla:** Los socios con Tipo de Socio "Honorario" o con una Excepción Autorizada vigente de tipo "Exención de restricción por atraso" no reciben restricciones automáticas. El sistema registra igualmente el atraso en el historial y lo muestra al personal como alerta.

---

### 4.3 Historial de atrasos

Registro acumulado de devoluciones tardías por socio. Sirve como indicador de reincidencia para el personal, independientemente de si el socio tiene o no una restricción vigente.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Socio | Relación | |
| Préstamo | Relación | El préstamo devuelto con atraso. |
| Días de atraso | Número | |
| Fecha de devolución efectiva | Fecha | |

**Regla de diseño:** El historial de atrasos es una herramienta de información para el personal de mostrador, no un mecanismo de penalización. En el momento de la atención, el sistema muestra cuántos atrasos acumuló el socio en los últimos doce meses.

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
| Fecha de inicio | Fecha | |
| Fecha de fin | Fecha | Opcional para actividades de un solo día. |
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
| Descripción | Texto | Descripción del material. Para bibliográfico: título, autor, cantidad de ejemplares si aplica. Para no bibliográfico: tipo de bien (mobiliario, equipo, etc.). |
| Estado de evaluación | Enumerado | Pendiente / Aceptado / Descartado. |
| Motivo de descarte | Texto | Opcional. Razón por la que no se incorporó al acervo. |
| Ejemplar generado | Relación | Opcional. Si el ítem fue aceptado y un Ejemplar fue creado en el catálogo a partir de él. Solo para material bibliográfico. |

**Regla de diseño:** El material no bibliográfico (mobiliario, equipamiento) se registra en la Donación para preservar trazabilidad institucional, pero no genera entradas en el catálogo. El sistema no gestiona el inventario de bienes no bibliográficos.

---

## Área 6: Operación del sistema

### 6.1 Usuario

Persona con acceso al sistema interno de la biblioteca.

| Atributo | Tipo | Notas |
|---|---|---|
| Identificador | Único | |
| Nombre | Texto | |
| Email | Texto | Para acceso y notificaciones internas. |
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
| Ventana de retiro de reserva (horas hábiles) | 48 | Tiempo disponible para retirar un libro reservado desde la notificación. |

Estos valores pueden modificarse desde la administración del sistema. El historial de cambios queda registrado con fecha y usuario responsable.

---

## Reglas de negocio consolidadas

Las siguientes reglas derivan directamente del relevamiento validado y gobiernan el comportamiento del sistema:

| Código | Regla |
|---|---|
| RN-01 | Un socio no puede tener préstamos domiciliarios activos superiores al límite de su Tipo de Socio. El sistema alerta al personal; no bloquea automáticamente. Si el personal decide continuar, debe registrar una justificación. |
| RN-02 | El plazo de todo préstamo domiciliario es de 15 días desde la fecha de préstamo (no desde la fecha de registro). |
| RN-03 | Una renovación solo es posible si el Libro no tiene Reservas en estado Pendiente o Notificada. |
| RN-04 | Un ejemplar solo puede participar en un movimiento activo a la vez, independientemente del tipo de movimiento. Esta regla no admite excepciones. |
| RN-05 | Una Reserva Notificada vence automáticamente a las 48 horas hábiles desde la notificación. Al vencer, el ejemplar se libera y pasa al siguiente en la cola de espera, o vuelve a estado Disponible si no hay más reservas. |
| RN-06 | Un socio con restricción activa no puede recibir nuevos préstamos domiciliarios, salvo que tenga una Excepción Autorizada vigente de tipo "Exención de restricción". |
| RN-07 | Los socios de Tipo Honorario no reciben restricciones automáticas por atraso. El sistema registra el atraso y lo muestra al personal igualmente. |
| RN-08 | Un ejemplar con modalidad de acceso "Solo sala" no puede participar en préstamos domiciliarios, préstamos institucionales, movimientos internos que impliquen salida, ni custodias externas. |
| RN-09 | Un ejemplar con modalidad de acceso "Restringido a autorización" requiere una Excepción Autorizada vigente para cualquier salida de la biblioteca. |
| RN-10 | Las Excepciones Autorizadas solo pueden ser creadas, modificadas o revocadas por usuarios con rol Administrador. |
| RN-11 | Toda Excepción Autorizada debe registrar: quién autorizó, fecha, motivo. La fecha de fin es opcional; si está vacía, la excepción es indefinida hasta su revocación explícita. |
| RN-12 | La devolución de un ejemplar no requiere que la realice la misma persona que efectuó el préstamo. |
| RN-13 | El registro de un préstamo puede realizarse con posterioridad a la entrega física del ejemplar. La fecha de préstamo es editable en el momento del registro. |
| RN-14 | Los parámetros de configuración del sistema pueden ser modificados por el Administrador. Todo cambio queda registrado con fecha y responsable. |
| RN-15 | La gestión económica de actividades aranceladas está fuera del alcance del sistema. El sistema registra si una actividad es gratuita o arancelada y, en el segundo caso, el monto de referencia. |
| RN-16 | El material no bibliográfico recibido en donaciones se registra a efectos de trazabilidad institucional, pero no genera entradas en el catálogo. |
| RN-17 | La Custodia Externa no genera restricciones ni penalizaciones. El sistema alerta al personal cuando se supera la fecha de retorno esperada. |

---

## Decisiones de diseño con justificación

Las siguientes decisiones de diseño quedaron establecidas durante el relevamiento y son parte del modelo:

**D-01 — Movimiento de ejemplares como concepto central**
El modelo no está organizado alrededor del préstamo, sino alrededor del movimiento de ejemplares. Esto refleja la operatoria real relevada y evita forzar tipos de movimiento cualitativamente distintos (custodia externa, uso interno) a encajar en estructuras de préstamo que no les corresponden.

**D-02 — Separación entre Libro y Ejemplar**
El registro bibliográfico (Libro) es distinto de la copia física (Ejemplar). Esta separación es fundamental: permite manejar múltiples copias del mismo título, estados distintos por copia, y reservas sobre el título sin requerir especificar qué copia.

**D-03 — Mecanismo único de excepciones**
En lugar de modelar por separado la condición honoraria, la exención individual de penalización y la autorización patrimonial, se define un único mecanismo de Excepción Autorizada con tipos. Esto evita soluciones puntuales que se vuelven inmanejables con el tiempo y garantiza trazabilidad uniforme para cualquier excepción futura.

**D-04 — Configuración sin intervención técnica**
Los parámetros operativos clave (límites, plazos, ventanas) son configurables desde la administración del sistema. Los beneficios por Tipo de Socio son parte de esta configuración. Esto respeta el requisito explícito de la Comisión Directiva de poder modificar esos valores sin depender de un proveedor técnico.

**D-05 — Alertas en lugar de bloqueos**
Para situaciones donde hoy existe criterio humano (exceder el límite de préstamos, atraso de un socio conocido), el sistema muestra una advertencia y permite continuar con justificación registrada. Esto preserva la flexibilidad operativa real de la biblioteca sin perder trazabilidad.

**D-06 — Clasificación propia conservada**
Se mantiene el esquema de categorías propio de la biblioteca, formalizado como estructura jerárquica flexible. No se adopta un estándar externo (CDU u otro) dado que el beneficio no justifica el costo de reclasificar 3.500 ejemplares sin personal especializado.

**D-07 — ISBN no es identificador único**
El ISBN no se usa como identificador de Libro porque existen libros sin ISBN y se detectaron duplicados de ISBN entre títulos distintos en la muestra analizada.

---

## Pendiente de relevamiento

Los siguientes aspectos no forman parte de este modelo porque no fueron relevados en la etapa actual. Se incluirán en una etapa posterior:

- **Portal de socios**: catálogo público, consulta de estado de préstamos propios, reservas online.
- **Estadísticas e informes**: indicadores de circulación, actividad de socios, crecimiento del catálogo.
- **Proceso formal de evaluación de donaciones**: criterios estructurados para aceptación o descarte de material, que la institución acordó formalizar durante el proyecto.

---

*Documento elaborado a partir del relevamiento consolidado validado por la Comisión Directiva (v2) y las sesiones de relevamiento de actividades, talleres y donaciones. Versión 1 — pendiente de validación por la Comisión Directiva antes de iniciar la etapa de arquitectura.*
