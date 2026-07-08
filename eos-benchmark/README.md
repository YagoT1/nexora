# eos-benchmark

Repositorio oficial del proyecto **Sistema de Gestión Bibliotecaria**, conducido bajo el Enterprise Operating System (EOS) de Nexora. Fuente única de verdad del proyecto (ver estado de esa afirmación en `handoff/CONSISTENCY-REVIEW-002.md`).

Este repositorio contiene **dos tipos de contenido distintos**, y esa distinción es la clave para navegarlo correctamente:

| Tipo | Qué es | Dónde está |
|---|---|---|
| **Metodología EOS** | La especificación interna de Nexora: cómo se conduce cualquier proyecto (fases, contratos, gobierno). Es independiente de este proyecto puntual. | [`EOS-Specification.md`](EOS-Specification.md) (índice) → Volúmenes I-V |
| **Benchmark del proceso** | Reseñas ("EOS Engineering Review") que evalúan, iteración por iteración, qué tan bien se ejecutó la metodología en este proyecto. No son los entregables técnicos. | `Fase N - Nombre/iteration-XXX.md`, `phase-summary.md`, métricas y reviews |
| **Entregables sustantivos del proyecto** | El contenido técnico real: requisitos, modelo de dominio, arquitectura, plan de implementación, prototipos. Esto es lo que un equipo de desarrollo necesita para construir el sistema. | `Fase N - Nombre/entregables/` |

---

## Estructura de fases

| Fase | Estado | Entregables sustantivos |
|---|---|---|
| 01 — Discovery | ✔ Cerrada | Relevamiento consolidado v1 / v2 |
| 02 — Domain Modeling | ✔ Cerrada | Modelo de Dominio v1 / v2 (21 reglas de negocio) |
| 03 — Architecture | ✔ Cerrada | Propuesta de Arquitectura v1 / v2, Plan de Implementación Fase 1 v1 / v2 |
| 04 — UX & Functional Design | ✔ Cerrada | 3 prototipos navegables (HTML) |
| 05 — Planning (Development Readiness Gate) | ✔ Cerrada | Sin entregable propio — confirma madurez para desarrollo (ver su phase-summary) |
| 06 — Development | ⏳ Habilitada, sin iniciar | — |
| 07 — Quality Assurance | Pendiente | — |
| 08 — Deployment | Pendiente | — |
| 09 — Operations | Pendiente | — |
| 10 — Continuous Improvement | Pendiente | — |

---

## Decisiones técnicas vigentes (resumen ejecutivo)

Para el detalle completo, ver `Fase 3 - Architecture/entregables/propuesta-arquitectura-v2.md`.

- **Backend:** Laravel 11 (PHP 8.3).
- **Frontend Fase 1:** Blade + Alpine.js. **Frontend Fase 3 (portal de socios):** Vue.js 3 + Inertia.js.
- **Base de datos:** PostgreSQL 16.
- **Hosting:** Render.com (PaaS), entornos staging + producción.
- **Alcance Fase 1:** Catálogo, Socios, Circulación completa, Excepciones y Restricciones, Alertas, Informes básicos, Administración, Migración de datos. 10 módulos con criterios de aceptación (ver Plan de Implementación v2).

---

## Historial de revisiones de consistencia

- [`handoff/CONSISTENCY-REVIEW-001.md`](handoff/CONSISTENCY-REVIEW-001.md) — reconstrucción inicial de contexto; detectó la ausencia de los entregables sustantivos (hallazgo H-C5, bloqueante).
- [`handoff/CONSISTENCY-REVIEW-002.md`](handoff/CONSISTENCY-REVIEW-002.md) — incorporación de los entregables sustantivos aprobados; verificación cruzada; determinación de Single Source of Truth.

## Estado y próximo paso

Ver [`handoff/PROJECT_HANDOFF.md`](handoff/PROJECT_HANDOFF.md).
