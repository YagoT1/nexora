# ADR-002 — Limitaciones del entorno de trabajo y estrategia de desarrollo del Módulo 1

**Estado:** Aceptada
**Fecha:** 2026-07-08
**Decide:** Equipo de continuidad, conforme a la delegación técnica expresa de la Comisión Directiva sobre esta materia.

---

## Contexto

Al iniciar la Phase 06 — Development (Módulo 1), se relevó el entorno de ejecución disponible en esta sesión de trabajo (sandbox Linux de Cowork) contra los requisitos del stack aprobado (Laravel 11 / PHP 8.3 / PostgreSQL 16, Propuesta de Arquitectura v2).

## Limitaciones detectadas

1. **Sin runtime PHP.** El sandbox no tiene PHP instalado. El repositorio de paquetes por defecto de Ubuntu 22.04 solo ofrece PHP 8.1 (no 8.3).
2. **Sin permisos de administrador (root).** No es posible instalar paquetes de sistema nuevos ni agregar repositorios (`sudo` está deshabilitado explícitamente en el contenedor).
3. **Sin Composer, sin PostgreSQL, sin Docker.** Ninguna de estas herramientas está disponible ni puede instalarse por (1) y (2).
4. **Acceso de red restringido.** El proxy de salida solo permite una lista blanca de dominios; `getcomposer.org`, `packagist.org` y binarios estáticos de PHP están bloqueados (`403 blocked-by-allowlist`). No es posible descargar un runtime PHP portátil ni ejecutar `composer create-project`.
5. **Inicialización de git poco confiable en esta carpeta de workspace.** La carpeta de workspace conectada impide de forma consistente el borrado de archivos temporales que git crea y elimina internamente (`index.lock`, objetos temporales), lo que hizo fallar repetidamente la inicialización de un repositorio git nuevo (`sistema-gestion-bibliotecaria/.git` quedó en un estado inconsistente tras varios intentos).

## Impacto sobre el Módulo 1

No es posible, desde esta sesión:

- Ejecutar `composer create-project laravel/laravel` ni instalar Laravel Breeze.
- Ejecutar migraciones contra una base PostgreSQL real.
- Correr la suite de tests (PHPUnit) para verificar los criterios de aceptación del Módulo 1.
- Mantener un historial de git confiable dentro de `sistema-gestion-bibliotecaria/` en este entorno específico.

## Decisión

Se opta por **entregar el Módulo 1 como código fuente completo, listo para integrar, sin ejecución ni validación automatizada en esta sesión**, en lugar de posponer el desarrollo hasta disponer de un entorno con PHP real. Justificación:

- La propia arquitectura aprobada (DA-04) ya exige que todo cambio pase primero por un entorno de staging antes de tocar datos reales — es decir, la validación real siempre iba a ocurrir fuera de este sandbox, en Render.com. No writing code here does not avoid that step; it only delays reaching it.
- Retrasar el desarrollo hasta obtener acceso a un entorno con PHP agregaría una demora institucional evitable para un problema puramente instrumental de esta sesión.
- El contenido técnico (migraciones, modelos, reglas) deriva directamente y con trazabilidad completa del Modelo de Dominio v2 y el Plan de Implementación v2, ya validados — el riesgo de "inventar" comportamiento es bajo porque cada decisión de código cita la regla de negocio que implementa.

**Mitigación del riesgo de código no ejecutado:** cada archivo entregado declara explícitamente su origen (regla de negocio / decisión de arquitectura). Se entrega junto con el código una guía de bootstrap (`docs/BOOTSTRAP.md`) con los comandos exactos para instanciar el proyecto Laravel real, integrar estos archivos, ejecutar las migraciones y correr los tests. **El primer checkpoint de calidad real de este código es la ejecución de `composer install && php artisan test` y `php artisan migrate` en un entorno con PHP 8.3, antes de cualquier despliegue a staging.** Esto debe quedar como el primer paso pendiente formal antes de dar por cerrado el Módulo 1.

Se descarta la alternativa de esperar sin producir código: no hay ninguna decisión institucional pendiente que lo justifique, y el criterio EOS de proporcionalidad (Volumen I, 6.9) indica continuar cuando seguir esperando no reduce incertidumbre real.

## Impacto sobre el repositorio de código

Por la limitación (5), el repositorio `sistema-gestion-bibliotecaria/` **no se inicializó con git en esta sesión**. Los archivos se entregan como estructura de carpetas plana. La guía de bootstrap indica `git init && git add -A && git commit` como primer paso, a ejecutar en un entorno sin las restricciones de este sandbox (la propia máquina del desarrollador, o inmediatamente al clonar el repositorio ya creado en GitHub).

## Seguimiento

Este hallazgo **no requiere decisión institucional** conforme a los criterios ya establecidos (no es un bloqueo que impida alcanzar los criterios de aceptación del módulo — solo difiere el momento en que se verifican, de "durante la escritura" a "antes del primer despliegue a staging"). Se informa a la Comisión Directiva por transparencia, sin solicitar acción de su parte.

---

## Actualización (2026-07-08) — Verificación exhaustiva de alternativas dentro de esta sesión

Ante la autorización de la Comisión Directiva para trasladar la validación del Módulo 1 "al entorno técnico que el equipo considere adecuado", se investigó activamente si existía alguna alternativa **dentro de esta misma sesión de Cowork** antes de descartar esa opción. Se verificó concretamente:

1. **Runtime PHP real vía WebAssembly (`@php-wasm/cli`, proyecto usado por WordPress Playground):** paquete disponible en el registro de npm (accesible desde este entorno). Es un PHP genuino compilado a WASM, no una simulación — habría permitido ejecutar PHP 8.2/8.3 real sin necesidad de `apt`/root.
2. **Acceso de red a los repositorios de paquetes PHP necesarios para Composer:** se probó `packagist.org`, `repo.packagist.org`, `getcomposer.org`, `codeload.github.com`, `api.github.com` y `raw.githubusercontent.com`. **Todos bloqueados por el proxy de salida** (`403 blocked-by-allowlist` o `403` genérico), incluso cuando el dominio raíz `github.com` sí responde (200). Solo `registry.npmjs.org` (ecosistema Node, no PHP) resultó accesible.

**Conclusión de la verificación:** el bloqueo no es la ausencia de un runtime PHP — eso tiene solución dentro de esta sesión (punto 1). El bloqueo real e insalvable dentro de esta sesión es que **Composer no tiene ningún camino de red disponible para descargar el framework Laravel ni sus dependencias**, sin importar qué intérprete PHP se use. Esto es una restricción de infraestructura del sandbox de Cowork, no del proyecto ni del código entregado.

**Decisión:** se descarta definitivamente ejecutar la validación del Módulo 1 dentro de esta sesión de Cowork, en cualquier configuración. La validación real (`composer install && php artisan migrate && php artisan test`, conforme a `docs/BOOTSTRAP.md`) debe ejecutarse en un entorno con acceso de red sin restricciones al ecosistema Composer/Packagist — por ejemplo, la máquina de un desarrollador, un runner de CI/CD (GitHub Actions u otro), o un entorno de nube provisionado para tal fin. Esto no es una tarea que el equipo pueda seguir intentando resolver por sí mismo dentro de este entorno; se informa a la Comisión Directiva como cierre de esta línea de investigación, no como bloqueo pendiente de decisión institucional.

---

## Actualización (2026-07-14) — Reverificación al intentar el gate de validación del Módulo 2

Al recibir la instrucción de proceder con el gate de validación real del Módulo 2 (`php artisan test --filter=Catalogo`), y antes de asumir que la limitación seguía vigente, se repitió la verificación empírica en esta misma sesión de Cowork (mismo sandbox, ~6 días después de la `Actualización` anterior):

1. `php`, `composer`, `docker`, `docker-compose`, `podman`: **ninguno instalado**.
2. `sudo`/`apt-get install`: **sigue bloqueado** (`sudo: The "no new privileges" flag is set` / `dpkg-lock: Permission denied` — mismo mecanismo de contenedor, sin cambios).
3. `vendor/` del proyecto: **vacío** (0 dependencias instaladas) — confirma que ni siquiera existe un autoloader de Composer utilizable en este sandbox, más allá de la disponibilidad de un runtime PHP.
4. Red hacia el ecosistema Composer, reverificada con `curl` directo: `packagist.org`, `repo.packagist.org` y `getcomposer.org` siguen devolviendo `403 blocked-by-allowlist` desde el proxy de salida. Sin cambios respecto del punto 2 de la verificación anterior.

**Conclusión:** la limitación documentada en 2026-07-08 sigue vigente sin cambios, seis días después. No es una limitación nueva ni distinta — es la misma restricción de infraestructura del sandbox de Cowork, ahora reconfirmada específicamente para el gate del Módulo 2. **No se inventa evidencia ni se asume que la suite "debería" pasar**: la ejecución real de `php artisan test --filter=Catalogo` queda, igual que para el Módulo 1, a cargo del entorno real de desarrollo (la misma máquina que ya validó el Módulo 1 en `ADR-006`/`ADR-007`/`ADR-008`). El Módulo 2 permanece en estado "código completo, no cerrado" hasta que esa ejecución se realice y su resultado se documente.
