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
