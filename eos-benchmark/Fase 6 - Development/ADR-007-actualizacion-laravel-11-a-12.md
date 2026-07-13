# ADR-007 — Actualización de la versión objetivo de Laravel: 11 → 12

**Estado:** Aceptada
**Fecha:** 2026-07-12
**Decide:** Responsable del proyecto (Yago), sobre evidencia presentada durante la ejecución de `ADR-006` (validación del entorno de bootstrap).

---

## Contexto

`DA-03` (`propuesta-arquitectura-v2.md`) fija Laravel 11 (PHP 8.3) como versión de framework objetivo. Al ejecutar el paso 2 de `docs/BOOTSTRAP.md` (`composer create-project laravel/laravel:^11.0 sgb-laravel`), Composer 2.10.2 rechazó la resolución de dependencias:

```
Your requirements could not be resolved to an installable set of packages.
Problem 1
  - Root composer.json requires laravel/framework ^11.0, found laravel/framework[v11.0.0, ..., v11.54.0]
    but these were not loaded, because they are affected by security advisories
    (PKSA-m5cs-t1y6-qpcs, PKSA-3r5d-mb8f-1qw9, PKSA-mdq4-51ck-6kdq, PKSA-8qx3-n5y5-vvnd,
     PKSA-q46n-4fdk-zjr4, PKSA-qzrn-rnz3-85w1, PKSA-w7xr-vk7n-rstm).
```

No se asumió que esto fuera simplemente una política de Composer a desactivar. Se investigó cada advisory contra la API oficial de seguridad de Packagist (`https://packagist.org/api/security-advisories/?packages[]=laravel/framework`) para determinar, con evidencia, si el bloqueo era una fricción de herramienta o un hallazgo real sobre el estado de la versión aprobada.

## Evidencia

De los 7 advisories reportados contra el rango `^11.0`:

| Advisory | CVE | Severidad | Rango afectado | Corregido en 11.x |
|---|---|---|---|---|
| PKSA-w7xr-vk7n-rstm | CVE-2024-52301 | high | `>=11.0.0,<11.31.0` (entre otras ramas) | Sí — `>=11.31.0` |
| PKSA-q46n-4fdk-zjr4 | CVE-2024-13919 | medium | `>=11.9.0,<11.36.0` | Sí — `>=11.36.0` |
| PKSA-qzrn-rnz3-85w1 | CVE-2024-13918 | medium | `>=11.9.0,<11.36.0` | Sí — `>=11.36.0` |
| PKSA-8qx3-n5y5-vvnd | CVE-2025-27515 | medium | `>=11.0.0,<11.44.1` (entre otras ramas) | Sí — `>=11.44.1` |
| **PKSA-mdq4-51ck-6kdq** | **CVE-2026-48019** | high (asociado) | `>=11.0.0,<12.0.0` (toda la rama 11.x) | **No — recién en `>=12.60.0`** |
| **PKSA-3r5d-mb8f-1qw9** | (mismo hallazgo, fuente GitHub) | **high** | `<12.60.0` (toda la rama 11.x, y toda versión anterior) | **No — recién en `>=12.60.0`** |
| **PKSA-m5cs-t1y6-qpcs** | — | medium | `<12.61.1` (toda la rama 11.x) | **No — recién en `>=12.61.1`** |

Cuatro de los siete advisories se resuelven pineando a `laravel/framework >=11.44.1`. Pero **dos hallazgos distintos — una inyección CRLF en la regla de validación de email (severidad alta) y una confusión de ruta en URLs firmadas temporales (severidad media) — no tienen ninguna versión corregida en toda la rama 11.x**. Ambos fueron reportados el 2026-06-17 y el equipo de Laravel los corrigió únicamente a partir de 12.60.0/12.61.1.

Esto es consistente con la política de soporte oficial de Laravel (18 meses de corrección de bugs + 24 meses de parches de seguridad desde el release de cada versión mayor). Laravel 11.0 se lanzó en marzo de 2024; la ventana de parches de seguridad venció alrededor de marzo de 2026. La fecha de hoy (12 de julio de 2026) y la ausencia comprobada de fix en 11.x para hallazgos de junio de 2026 confirman, con evidencia directa y no por inferencia de política general, que **la rama Laravel 11.x ya no recibe parches de seguridad activos**.

## Decisión

Se actualiza la versión objetivo de Laravel de **11 a 12** para el Módulo 1 y todos los módulos subsiguientes del proyecto.

Criterios:

1. **Seguridad no negociable**: no se justifica iniciar el desarrollo de un sistema nuevo, institucional, con datos de usuarios (RN-14 exige auditoría append-only, ya implementada), sobre una versión de framework con vulnerabilidades de severidad alta conocidas y sin parche disponible.
2. **Costo de migración bajo**: Laravel 12 fue diseñada explícitamente como una actualización de bajo impacto respecto a Laravel 11 — mayormente actualización de dependencias internas y elevación del piso mínimo de PHP a 8.2, sin cambios estructurales grandes en el kernel del framework. El código del Módulo 1 ya escrito (migraciones, modelos Eloquent, middleware, controllers, vistas Blade) no utiliza ninguna API marcada como removida entre 11 y 12.
3. **Compatibilidad con el entorno ya validado**: PHP 8.5.8 (instalado y validado en `ADR-005`) excede ampliamente el mínimo de PHP 8.2 que exige Laravel 12 — no se requiere ningún cambio adicional de entorno.
4. **No se ignora el chequeo de seguridad de Composer**: la alternativa de forzar la instalación de Laravel 11 desactivando `policy.advisories.block` fue descartada explícitamente — habría dejado el sistema con dos vulnerabilidades conocidas y sin mitigación desde el primer commit.

## Consecuencias

- `DA-03` (`propuesta-arquitectura-v2.md`) queda enmendada — no reescrita — con una nota que referencia este ADR, preservando la trazabilidad de la decisión original.
- `docs/BOOTSTRAP.md` se actualiza: paso 2 pasa a `composer create-project laravel/laravel:^12.0 sgb-laravel`; requisito de PHP se mantiene en "8.3 o superior" dado que el entorno real usa 8.5.8 y Laravel 12 solo exige 8.2 como piso.
- El directorio `sgb-laravel/` creado en el intento fallido con `^11.0` queda incompleto (esqueleto extraído, `vendor/` sin instalar, sin `composer.lock`) — se elimina y se recrea limpio con la restricción corregida, en lugar de intentar repararlo in-place.
- Ningún archivo de código del Módulo 1 requiere cambios por este ADR; la verificación de compatibilidad real ocurre en el propio checkpoint de `php artisan test` (paso 5 de `BOOTSTRAP.md`), que de todas formas era el próximo paso pendiente.

## Fuentes consultadas

- [Packagist — Security Advisories API](https://packagist.org/api/security-advisories/?packages[]=laravel/framework) — advisories citados, consultado 2026-07-12.
- [GHSA-crmm-hgp2-wgrp](https://github.com/advisories/GHSA-crmm-hgp2-wgrp) — Temporary Signed URL Path Confusion.
- [GHSA-5vg9-5847-vvmq](https://github.com/advisories/GHSA-5vg9-5847-vvmq) / [CVE-2026-48019](https://github.com/laravel/framework/security/advisories/GHSA-5vg9-5847-vvmq) — CRLF injection in default email rule.
