# ADR-001 — Ubicación y estrategia del repositorio de código

**Estado:** Aceptada
**Fecha:** 2026-07-08
**Decide:** Equipo de continuidad, con delegación expresa de la Comisión Directiva (sin preferencia tecnológica impuesta).

---

## Contexto

La Fase 06 — Development quedó habilitada tras `CONSISTENCY-REVIEW-002.md`. `eos-benchmark` es el repositorio de **documentación y metodología** (EOS + benchmark + entregables de predesarrollo). La Propuesta de Arquitectura v2 (DA-04) exige un repositorio de código propiedad de la institución, alojado en GitHub, con despliegue automático a Render.com y dos entornos (staging/producción).

Corresponde decidir dónde y cómo se inicia ese repositorio de código, distinto de `eos-benchmark`.

## Alternativas consideradas

| Alternativa | Ventajas | Desventajas |
|---|---|---|
| **A. Carpeta de código dentro de `eos-benchmark`** | Todo en un solo lugar | Mezcla un repositorio de documentación (sin CI/CD) con un repositorio de aplicación (con despliegue automático desde `main`). Un push documental dispararía o interferiría con el pipeline de Render.com. Contradice la promesa explícita a la institución de un repositorio de código propio (DA-04). Rechazada. |
| **B. Repositorio de código nuevo, local al workspace conectado, para luego conectar a GitHub** | Separación limpia de responsabilidades. Permite iniciar historial de commits desde el Módulo 1. Consistente con DA-02 (monolito modular, un único repo de código) sin mezclarlo con la documentación. | Requiere que la institución cree el repositorio GitHub y el equipo configure el remoto — no puede automatizarse desde esta sesión (no hay conector de GitHub autorizado). |
| **C. Usar directamente el conector de GitHub para crear el repo remoto** | Automatizaría la creación | No disponible: el conector `engineering:github` requiere autorización OAuth que no puede completarse en una sesión no interactiva. |

## Decisión

Se adopta la **alternativa B**: se crea un repositorio de código nuevo e independiente, `sistema-gestion-bibliotecaria/`, como carpeta hermana de `eos-benchmark` dentro del mismo workspace conectado. Se inicializa con git localmente. La conexión a un remoto de GitHub bajo la cuenta de la institución queda como acción pendiente del equipo/institución (ver sección "Acción requerida").

Esta decisión es consistente con DA-02 (un único repositorio de código para el monolito) y con DA-04 (el repositorio es propiedad de la institución, distinto del repositorio de documentación).

## Acción requerida (no ejecutable desde esta sesión)

1. Crear un repositorio vacío en GitHub bajo la cuenta de la institución (nombre sugerido: `sistema-gestion-bibliotecaria`).
2. Agregar ese repositorio como remoto `origin` del repositorio local creado en esta sesión y hacer el primer push.
3. Conectar el conector de GitHub en Cowork (o autorizar `engineering:github` vía `claude mcp`/`/mcp`) si se desea que el equipo continúe operando el repositorio remoto de forma autónoma en sesiones futuras.

## Consecuencias

- El código del Módulo 1 se desarrolla y versiona localmente desde ahora, sin bloquear el trabajo a la espera de la creación del repositorio remoto.
- La trazabilidad del proyecto queda distribuida en dos repositorios con propósitos distintos y claramente documentados: `eos-benchmark` (documentación) y `sistema-gestion-bibliotecaria` (código). El `README.md` de `eos-benchmark` se actualiza para referenciar esta decisión.
