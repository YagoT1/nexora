# Phase Summary

## Phase

06 — Development

---

## Estado

En curso — Módulo 1 (de 10) con código fuente completo, pendiente de bootstrap y validación.

---

## Objetivo

Construir el sistema conforme al Plan de Implementación v2, módulo por módulo, respetando el orden de dependencias definido en DA-08.

---

## Avance

### Módulo 1 — Infraestructura y autenticación: código escrito

Repositorio: `sistema-gestion-bibliotecaria/` (ver `ADR-001-repositorio-de-codigo.md`).

Entregado:

- 29 migraciones cubriendo las 25 entidades del Modelo de Dominio v2 completo (no solo el alcance funcional de Fase 1 del software, conforme exige el Plan de Implementación v2 para el Módulo 1).
- Modelos Eloquent para todas las entidades, con relaciones y trazabilidad a la regla/decisión de origen documentada en cada archivo.
- Middleware de autorización por rol (`EnsureUserHasRole`), infraestructura de auditoría append-only (RN-14) vía trait `Auditable`.
- Panel de administración de usuarios (crear, editar, inactivar, reactivar, asignar rol) con vistas Blade + Alpine.js.
- Seeders: Tipos de Socio, Parámetros de Configuración, usuarios de prueba por rol.
- 4 archivos de test (Feature) cubriendo explícitamente los criterios de aceptación del Módulo 1: autenticación por rol, timeout de sesión, autorización por rol (incluyendo usuario inactivo), y auditoría de cambios.

**No ejecutado ni validado en el entorno donde se escribió** (ver `ADR-002-limitaciones-de-entorno-y-estrategia-de-desarrollo.md`): el sandbox de esta sesión no dispone de PHP, Composer, PostgreSQL ni acceso de red a Packagist. El primer checkpoint de calidad real es ejecutar `docs/BOOTSTRAP.md` en un entorno con PHP 8.3.

### Hallazgo técnico documentado durante el desarrollo

**Nota de arquitectura (no bloqueante, para Architecture Review futura):** DA-09 especifica un índice único parcial *por tabla* de movimiento para garantizar RN-04 (invariante de circulación) a nivel de motor de base de datos. Como la invariante exige unicidad *entre las cuatro tablas de movimiento* (un ejemplar no puede estar simultáneamente en un préstamo domiciliario Y en una custodia externa, por ejemplo), un índice único por tabla no cubre el caso cruzado — eso solo puede resolverlo la verificación de aplicación ("Nivel 2" de DA-09). Se implementó `Ejemplar::tieneMovimientoActivo()` para cubrir ese caso. Se deja constancia de que la garantía a nivel de motor (Nivel 1) es, con el diseño de tablas actualmente aprobado, necesariamente parcial — una garantía completa a nivel de base de datos requeriría unificar las cuatro tablas de movimiento en una sola con discriminador de tipo, lo cual sería un cambio de arquitectura y no corresponde decidirlo unilateralmente en el Módulo 1.

---

## Próximo trabajo

1. Ejecutar `sistema-gestion-bibliotecaria/docs/BOOTSTRAP.md` en un entorno con PHP 8.3 real; correr `php artisan test` y corregir lo que la ejecución real revele.
2. Completar el pre-checklist de infraestructura (GitHub, Render.com, HTTPS, cron, variables de entorno).
3. Módulo 2 — Catálogo, una vez el Módulo 1 esté verificado en verde.

## Decisión

Módulo 1 avanza a estado "código listo para verificación". No se declara completo hasta cumplir el checkpoint del punto 1.
