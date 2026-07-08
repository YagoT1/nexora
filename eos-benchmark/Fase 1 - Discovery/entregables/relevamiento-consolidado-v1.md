# Relevamiento Consolidado — Sistema de Gestión Bibliotecaria
**Documento para validación de la Comisión Directiva — v1**

Este documento resume todo lo relevado hasta el momento sobre préstamos, devoluciones, reservas, catálogo, socios y excepciones operativas. No incluye actividades, talleres ni donaciones, que se relevarán en una etapa posterior.

El objetivo de este documento es que la Comisión Directiva confirme que refleja correctamente el funcionamiento real de la biblioteca, antes de avanzar con el diseño técnico del sistema.

---

## 1. Reglas generales de préstamo

- Cada socio puede retirar hasta **tres libros simultáneamente**.
- El plazo de préstamo es de **quince días**.
- Las renovaciones son posibles, salvo que el libro tenga una reserva pendiente de otro socio.
- En la práctica, cuando un socio conocido y de buen comportamiento pide un cuarto libro, a veces se le hace una excepción. No es una regla escrita; depende del criterio de quien atiende.
- Ocasionalmente se entrega el libro y se registra el préstamo unos minutos después, cuando el mostrador está muy ocupado.

## 2. Identificación de socios

- La mayoría de los socios son reconocidos personalmente por el personal, sin necesidad de carnet.
- Si no se reconoce a la persona, se busca por apellido en la planilla y, de ser necesario, se solicita el DNI.
- Es común que un socio figure con un apellido distinto al actual (por cambio de apellido) o con variaciones de escritura, lo que dificulta la búsqueda exacta.

## 3. Devoluciones

- Al recibir un libro se revisa su estado físico de forma visual y rápida.
- Si se detecta un daño relevante, se conversa con el socio en el momento, pero generalmente no queda registrado en ningún lado.
- No es necesario que devuelva el libro la misma persona que lo retiró (puede traerlo un familiar).

## 4. Atrasos

- No existe hoy un sistema formal de multas ni penalización económica.
- No se contacta automáticamente al socio quande se vence el plazo; la conversación ocurre cuando el socio vuelve a la biblioteca.
- Lo que genera preocupación real no es la demora puntual, sino la **reincidencia**: varios atrasos repetidos en el tiempo.
- No hay un criterio uniforme y documentado; depende del caso y de quien atiende.

**Recomendación ya conversada:** reemplazar la ausencia de sistema por una suspensión automática proporcional a los días de atraso, complementada con un indicador de historial de atrasos visible para el personal, de forma que la decisión de actuar con más firmeza se apoye en un dato objetivo y no en la memoria individual.

## 5. Reservas

- No son frecuentes, pero ocurren.
- Hoy se registran en un cuaderno o de memoria.
- Cuando el libro reservado vuelve, se avisa al socio por teléfono o mensaje.
- Si el socio no retira el libro en varios días, la reserva se pierde y el libro vuelve a estar disponible para cualquiera.

**Recomendación ya conversada:** formalizar una ventana de tiempo fija (por ejemplo 48 horas hábiles) para retirar un libro reservado antes de liberarlo, y automatizar el aviso al socio.

## 6. Catálogo y ejemplares

- Existen múltiples ejemplares del mismo título, en distintos estados: disponible, prestado, en reparación, solo consulta en sala, extraviado.
- Se utiliza una clasificación propia, simple, sin estándar bibliotecológico formal.
- Algunos ejemplares antiguos no tienen ISBN.
- Existen ejemplares marcados explícitamente como de consulta exclusiva en sala (por ejemplo, la colección de Historia Local) y otros que requieren autorización especial para préstamo (la Colección Patrimonial).

## 7. Excepciones operativas conocidas

Se identificaron tres tipos de excepción a las reglas generales, todas decididas en su momento por la Comisión Directiva pero sin registro formal:

- **Socios honorarios:** antiguos miembros de comisión o colaboradores históricos. En la práctica pueden tener mayor límite de préstamos y mayor flexibilidad ante atrasos. Sus beneficios exactos nunca fueron definidos por escrito y varían según el caso.
- **Excepciones individuales de penalización:** existe al menos un caso de un socio exceptuado de restricciones por atraso, por decisión histórica de comisión directiva, sin política escrita.
- **Autorizaciones especiales sobre la Colección Patrimonial:** en casos excepcionales, la comisión autorizó el préstamo de ejemplares patrimoniales a investigadores o instituciones educativas, sin procedimiento formal de registro.

**Recomendación ya conversada:** un único mecanismo de "excepción autorizada" (a quién aplica, qué regla excepciona, quién la autorizó, motivo, vigencia) que cubra estos tres casos y cualquier excepción futura similar, evitando soluciones puntuales no escalables.

## 8. Casos especiales adicionales (relevados con personal de mostrador)

- **Préstamos a instituciones:** escuelas solicitan libros para trabajar en clase, autorizados por dirección o comisión. No son préstamos a un socio individual.
- **Movimientos internos:** voluntarios retiran ejemplares para actividades o muestras, sin que hoy quede registrado como préstamo. Esto representa un riesgo de trazabilidad (un ejemplar puede estar fuera de la biblioteca sin que el sistema lo sepa).

## 9. Operación diaria

- Habitualmente atiende una sola persona en el mostrador; en momentos de mayor afluencia (actividades infantiles, salida escolar) se suma otro voluntario.
- Registrar un préstamo lleva hoy entre uno y dos minutos con el método actual.
- El pedido explícito del personal de mostrador es que el sistema **alerte sobre situaciones relevantes** (reservas pendientes, atrasos previos, excepciones vigentes de ese socio) en el momento de la atención, en lugar de exigir revisar varias planillas.

## 10. Estado de los datos actuales (de la muestra analizada)

- Se detectaron ISBN duplicados entre títulos distintos y ejemplares sin ISBN cargado.
- Se detectó inconsistencia de formato en categorías (mismo valor escrito de formas distintas).
- Se detectaron préstamos que referencian socios o ejemplares inexistentes en las otras planillas (falta de integridad referencial, propia de trabajar con Excel).
- Diecisiete de cien ejemplares de la muestra no tienen autor cargado, concentrados en ciertas colecciones.
- El campo "Observaciones" de socios y catálogo contiene hoy información operativa crítica (condición de honorario, excepciones, restricciones de préstamo) que no está estructurada en ningún otro lugar.

---

## Preguntas que quedan abiertas para la Comisión Directiva

1. ¿Cuáles deberían ser exactamente los beneficios estándar de la condición de "socio honorario" (límite de préstamos, plazo, exención de restricciones)?
2. ¿Sigue vigente la decisión histórica de exceptuar de restricciones al socio mencionado en el punto 7, o corresponde revisarla ahora que quedará formalizada?
3. ¿Quién debe poder registrar o modificar una excepción dentro del sistema? ¿Cualquier empleado, o debe quedar condicionado a una decisión de comisión directiva con algún respaldo (acta, fecha de reunión)?
4. ¿Conviene mantener la flexibilidad informal en el límite de tres libros (con alerta y registro de motivo) o prefieren que sea una regla estricta sin excepciones manuales?

---

*Documento elaborado a partir de: entrevista con personal de mostrador (Marta), análisis de muestra anonimizada de datos (catálogo, socios, préstamos) y conversaciones previas de relevamiento general. Pendiente de validación por la Comisión Directiva antes de continuar con el relevamiento de actividades, talleres y donaciones.*
