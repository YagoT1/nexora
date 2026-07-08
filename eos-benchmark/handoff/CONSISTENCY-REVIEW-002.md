# CONSISTENCY REVIEW — 002

**Fecha:** 2026-07-08
**Motivo:** Incorporación de los entregables sustantivos del proyecto (aportados por la Comisión Directiva) al repositorio `eos-benchmark`, y verificación de si el repositorio constituye efectivamente una Single Source of Truth (SSOT), conforme a lo solicitado tras el hallazgo H-C5 de `CONSISTENCY-REVIEW-001.md`.

---

## Alcance de esta revisión

Se incorporaron 11 archivos aportados como entregables oficiales aprobados: Relevamiento Consolidado v1/v2, Modelo de Dominio v1/v2, Propuesta de Arquitectura v1/v2, Plan de Implementación Fase 1 v1/v2, y 3 prototipos HTML. Su contenido técnico **no fue modificado**; se incorporaron tal como fueron entregados.

## Criterio de organización adoptado

Se creó una carpeta `entregables/` dentro de cada carpeta de Fase, separando explícitamente:

- **Reseñas de benchmark** (`iteration-XXX.md`, `phase-summary.md`, métricas, reviews) — evalúan la calidad del *proceso*.
- **Entregables sustantivos** (`entregables/*`) — el contenido técnico real del proyecto.

Esta separación resuelve de raíz la causa del hallazgo H-C5: antes no existía un lugar definido para el contenido sustantivo, lo que llevó a que nunca se incorporara. Se documentó el criterio en el nuevo `README.md` raíz del repositorio y se referenció desde cada `phase-summary.md` afectado.

**Mapeo aplicado:**

| Entregable | Fase asignada | Justificación |
|---|---|---|
| `relevamiento-consolidado-v1/v2.md` | Fase 01 — Discovery | Corresponde directamente al objetivo y entregable de la fase. |
| `modelo-de-dominio-v1/v2.md` | Fase 02 — Domain Modeling | Ídem. |
| `propuesta-arquitectura-v1/v2.md` | Fase 03 — Architecture | Corresponde a las iteraciones 009-010, ya ubicadas en esta carpeta. |
| `plan-implementacion-fase1-v1/v2.md` | Fase 03 — Architecture | El Plan de Implementación fue producido en la iteración 011, que ya reside en esta carpeta (no en Fase 05, que es un gate de madurez — ver hallazgo H-C4 de la revisión anterior). |
| `prototipo-01/02/03-*.html` | Fase 04 — UX & Functional Design | Corresponden a las iteraciones 013-014 (prototipos navegables y el complementario por gaps). |

---

## Verificación cruzada de contenido

Se verificó consistencia sustantiva entre los propios entregables y contra las reseñas de benchmark ya existentes:

- **Trazabilidad documental:** cada documento cita explícitamente en su pie de página la versión previa de la que deriva (relevamiento v2 → dominio v1 → dominio v2 → arquitectura v1 → arquitectura v2 → plan v1 → plan v2). La cadena es completa y no tiene saltos.
- **Conteo de reglas de negocio:** el Modelo de Dominio v2 define exactamente 21 reglas (RN-01 a RN-21), coincidiendo con el valor ya registrado en `Fase 2 - Domain Modeling/domain-metric.md`. ✔ Consistente.
- **Correcciones declaradas vs. correcciones reales:** las 7 correcciones y 4 clarificaciones que la iteración 008 declaraba haber aplicado (eliminación del estado "Renovado", vínculo donación-catálogo unidireccional, separación de estados manuales/derivados, mecanismo de notificaciones, entidades de asociación, RN-18 a RN-21) están efectivamente presentes en el Anexo de cambios del Modelo de Dominio v2. ✔ Consistente.
- **Stack tecnológico:** la Propuesta de Arquitectura v2 (Laravel 11, Blade + Alpine.js para Fase 1, Vue+Inertia reservado para Fase 3, PostgreSQL 16, Render.com) coincide con las tres correcciones críticas que la iteración 010 declaraba (concurrencia, tareas programadas, plan de recuperación). ✔ Consistente.
- **Cobertura de reglas en el Plan de Implementación:** se verificó por muestreo que las reglas RN-01 a RN-13, RN-18 a RN-21 están asignadas a un módulo concreto. Las reglas RN-15, RN-16 y RN-17 (actividades, donaciones, custodia externa) no tienen módulo asignado en el Plan Fase 1 — esto es correcto y esperado, ya que la Propuesta de Arquitectura (DA-07) explícitamente difiere ese alcance a la Fase 2 del proyecto de software (no debe confundirse con las Fases del EOS). No constituye un gap.

## Hallazgos de esta revisión

### H-C6 — Wireframes originales de la iteración 012 no preservados como archivo (Severidad: Baja)

La iteración 012 declara haber producido "8 wireframes" durante la Fase 03. Solo se incorporaron los 3 prototipos HTML de la Fase 04 (iteraciones 013-014). Los 8 wireframes originales no fueron aportados como archivo independiente.
**Resolución adoptada:** documentado como nota de completitud en el phase-summary de la Fase 03. No es bloqueante: los prototipos de Fase 04 cubren y amplían la validación funcional, y el Plan de Implementación v2 no depende de los wireframes originales como insumo técnico (son artefactos de validación con usuario, ya superados por la Fase 04).

### H-C7 — Variación menor en el conteo de entidades (Severidad: Muy baja, no bloqueante)

`domain-metric.md` registra 22 entidades; el conteo directo sobre el Modelo de Dominio v2 arroja entre 25 y 27 según se incluyan o no las entidades de asociación (M:N) y los parámetros de configuración como "entidad". La discrepancia es atribuible a diferencias de criterio de conteo entre la métrica original (tomada en la iteración 007-008) y la versión final v2 del modelo, que incorporó nuevas entidades de asociación explícitas durante las correcciones. No afecta ninguna decisión técnica ni requiere acción.

### H-C8 — Cifra de "24 wireframes" de la fuente externa "Nexora" no corroborada (Severidad: Informativa)

La base de conocimiento externa "Nexora" (no oficial, referenciada en la Consistency Review 001) mencionaba "24 pantallas" de wireframes aprobados. Ningún documento oficial ahora incorporado sostiene esa cifra: la documentación oficial registra 8 wireframes (iteración 012, no preservados como archivo) más 3 prototipos HTML (Fase 04, incorporados). **Resolución:** la cifra de "24 pantallas" de la fuente externa se considera no verificada y no debe utilizarse como referencia. La documentación oficial (este repositorio) prevalece.

### H-C9 — Checklist institucional del Plan v2 muestra dos bloqueantes sin marcar, ya resueltos según el handoff (Severidad: Informativa, no bloqueante)

El pre-checklist de `plan-implementacion-fase1-v2.md` marca como pendientes ("BLOQUEANTE") la confirmación del presupuesto de hosting y la definición de responsabilidad de mantenimiento post-entrega. Por instrucción expresa, el contenido de los entregables no fue modificado. Sin embargo, `handoff/PROJECT_HANDOFF.md` registra ambos puntos como aprobados por la Comisión Directiva con posterioridad a la redacción del plan.
**Aclaración para equipos futuros (sin alterar el entregable original):** ambos bloqueantes institucionales deben considerarse **resueltos** a la fecha de este documento. Los demás ítems del pre-checklist (repositorio de código de la aplicación, entornos de Render.com, HTTPS, cron job, variables de entorno, datos de staging) siguen pendientes de ejecución — son tareas de arranque de la Fase 06, no vacíos documentales.

---

## Determinación: ¿`eos-benchmark` es Single Source of Truth?

**Sí, con la salvedad de H-C6 (no bloqueante).** El repositorio ahora contiene, de forma trazable y sin depender de conocimiento externo:

1. La metodología EOS completa (Volúmenes I-V).
2. El historial de benchmark de cada iteración (proceso).
3. El contenido técnico sustantivo completo: requisitos validados, modelo de dominio con 21 reglas de negocio, arquitectura con stack tecnológico justificado y decisiones críticas de concurrencia/scheduler/contingencia, y un plan de implementación de 10 módulos con criterios de aceptación verificables y estrategia de testing obligatoria.

Ningún equipo nuevo necesitaría acudir a una fuente externa para reconstruir el estado del proyecto o comenzar el desarrollo del Módulo 1.

---

## Conclusión y próximo paso

Con el hallazgo H-C5 resuelto, **se levanta el bloqueo** registrado en `CONSISTENCY-REVIEW-001.md`. Se habilita formalmente el inicio de la **Phase 06 — Development**, comenzando por el Módulo 1 (Infraestructura y autenticación) según `Fase 3 - Architecture/entregables/plan-implementacion-fase1-v2.md`.

Antes de escribir código de funcionalidad, corresponde ejecutar el pre-checklist técnico pendiente (creación del repositorio de código de la aplicación — distinto de `eos-benchmark`, que es documentación —, entornos de Render.com, HTTPS, cron job, variables de entorno, datos de staging), tal como exige el propio plan.

---

## Addendum (2026-07-08 — Fase 06 en curso): H-C10 — Desfasaje entre contenido en disco e historial de git por caché de mtime (Severidad: Media, no bloqueante, resuelto)

Durante la Fase 06, al actualizar `README.md` y `handoff/PROJECT_HANDOFF.md` para reflejar el inicio del Módulo 1 (ver commit `06fea55`), se detectó que ambas ediciones quedaron correctamente escritas en disco pero **no fueron incluidas en dicho commit**. Solo se registraron los dos archivos nuevos (`ADR-002...md`, `phase-summary.md` de Fase 06); los archivos editados (no nuevos) quedaron fuera sin error visible.

**Causa raíz identificada:** el motor de git en el entorno de esta sesión utiliza una optimización estándar ("racily clean") que compara `mtime` y tamaño de archivo contra la entrada del índice para decidir si necesita releer el contenido antes de calcular un diff o un `add`. Las escrituras realizadas mediante la herramienta de edición de archivos de este entorno no siempre actualizan el `mtime` de un modo que git detecte como posterior al de la entrada indexada, por lo que git asumía —incorrectamente— que el archivo no había cambiado, sin releer su contenido real. Esto se verificó de forma concluyente comparando `git hash-object` del archivo en disco contra el blob referenciado por el índice (`git ls-files -s`): los hashes diferían, confirmando contenido distinto pese a que `git diff`/`git add` reportaban "sin cambios".

**Resolución adoptada:** ejecutar `touch` explícito sobre los archivos afectados antes de `git add` fuerza a git a invalidar la caché de stat y releer el contenido real. Aplicado sobre `README.md` y `PROJECT_HANDOFF.md`, esto permitió confirmar la diferencia real y capturarla en el commit `57aaf61` ("docs(eos-benchmark): actualiza README y PROJECT_HANDOFF con estado de Fase 06 en curso").

**Impacto:** ninguno sobre el contenido técnico sustantivo del proyecto — el desfasaje fue exclusivamente entre el archivo en disco (correcto en todo momento) y el registro histórico de git (incompleto durante una ventana breve, ya corregida). No requiere decisión institucional.

**Procedimiento adoptado para el resto de la Fase 06 (y fases siguientes) en este entorno:** antes de cualquier `git add` sobre archivos editados (no creados), ejecutar `touch` sobre las rutas afectadas y verificar con `git diff` (no solo `git status`) que el cambio es detectado antes de confiar en que un commit posterior lo va a capturar.

