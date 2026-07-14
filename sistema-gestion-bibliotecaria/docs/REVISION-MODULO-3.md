# Guía de revisión funcional — Módulo 3 (Socios), Pasos 1 a 7

Este documento prepara la revisión funcional de lo entregado del Módulo 3 — Socios (Tipo de Socio,
Socio, búsqueda tolerante a variaciones de nombre, vista de mostrador, historial de préstamos). No
reemplaza `docs/BOOTSTRAP.md`: asume el mismo entorno usado para cerrar los Módulos 1 y 2 (`31
passed (87 assertions)`, ver `docs/REVISION-MODULO-2.md`).

**Estado de este módulo: código completo, no ejecutado.** A diferencia de los Módulos 1 y 2, este
documento se entrega antes de la primera corrida real contra PHP/PostgreSQL (esta sesión de Cowork
no puede ejecutar PHP — ver `ADR-002`). No debe considerarse cerrado hasta obtener esa evidencia,
siguiendo el mismo estándar aplicado a los módulos anteriores.

---

## 1. Actualizar el entorno con lo último

```bash
git pull origin main
composer install                     # por si cambiaron dependencias (no debería, en este avance)
php artisan migrate                  # nueva: habilita la extensión unaccent (ver sección 5)
php artisan db:seed --class=SociosDemoSeeder
```

`SociosDemoSeeder` es idempotente (usa `firstOrCreate` y una verificación de existencia de
préstamo antes de crear cada uno): correrlo más de una vez no duplica datos. Requiere que
`AdminUserSeeder` y `CatalogoDemoSeeder` ya hayan corrido antes (usa el usuario Administrador como
registrador de los préstamos de demostración); si partís de cero, `migrate:fresh --seed` ya
respeta ese orden.

```bash
php artisan migrate:fresh --seed     # recrea todo, incluidos usuarios de prueba y datos de demo
php artisan serve
```

Primer chequeo objetivo antes de cualquier revisión visual — correr la suite automática (Paso 7):

```bash
php artisan test --filter=Socios
```

Cubre los 4 criterios de aceptación del módulo en 4 archivos bajo `tests/Feature/Socios/`
(`AccesoSociosTest`, `TipoSocioTest`, `SocioTest`, `VistaMostradorSocioTest`) — 11 tests en total.
**Todavía sin ejecutar contra un entorno real** — esta es la primera vez que se reporta este
comando para el módulo; el resultado (verde o no) es la evidencia que falta para cerrarlo.

---

## 2. Usuarios de prueba (ya existentes desde el Módulo 1)

| Rol | Email | Contraseña | Acceso esperado a Socios |
|---|---|---|---|
| Administrador | `admin@biblioteca.test` | `password` | Completo |
| Personal | `personal@biblioteca.test` | `password` | Completo |
| Voluntario | `voluntario@biblioteca.test` | `password` | **Bloqueado** (403) — Modelo de Dominio v2, 6.1: "Gestionar socios" no incluye a Voluntario, mismo patrón que Catálogo |

Primer chequeo sugerido: entrar con `voluntario@biblioteca.test` e intentar acceder a
`/socios/socios` — debe rechazar el acceso, no redirigir silenciosamente ni mostrar contenido
parcial.

---

## 3. Datos de ejemplo cargados por `SociosDemoSeeder`

Elegidos para ejercitar los casos que importan, no como padrón de socios realista:

| Socio | DNI | Tipo | Caso que ejercita |
|---|---|---|---|
| María García | `10000001` | Estándar | Nombre acentuado + nombre alternativo ("Maria Garcia de los Santos") — buscar "Garcia" sin tilde debe encontrarlo por ambas vías. |
| Roberto Fernández | `10000002` | Estándar | Préstamo atrasado (5 días) sobre "Manual de bibliotecología", con `HistorialAtraso` y `RestriccionSocio` vigente — vista de mostrador con alerta de atraso, contador de atrasos, y restricción visible. |
| Elena Sánchez | `10000003` | Honorario | Mismo tipo de atraso (sobre "Manual de encuadernación") pero **sin** `RestriccionSocio` — RN-07: la vista de mostrador no debe mostrar ninguna restricción activa aunque tenga atrasos. |

Los `TipoSocio` "Estándar" (límite 3) y "Honorario" (límite 5, sin restricción automática) ya
existen desde `TipoSocioSeeder` (Módulo 1); el seeder de este módulo los reutiliza con
`firstOrCreate`, no los duplica.

---

## 4. Checklist de criterios de aceptación (Plan de Implementación v2, Módulo 3)

| # | Criterio (texto del plan) | ¿Revisable ahora? | Cómo revisarlo |
|---|---|---|---|
| 1 | "El Administrador puede modificar el límite de préstamos del Tipo de Socio 'Estándar' de 3 a 4 y el cambio se aplica inmediatamente sin reiniciar el sistema." | **Sí** | Iniciar sesión como `admin@biblioteca.test` → Socios → Tipos de socio → Editar "Estándar" → cambiar límite a 4 → Guardar. Sin reiniciar nada, releer el registro (por UI o `php artisan tinker` → `TipoSocio::where('nombre','Estándar')->first()->limite_prestamos_simultaneos`) debe mostrar `4`. Cubierto también por test automático (`TipoSocioTest`). |
| 2 | "La búsqueda por 'Garcia' encuentra socios registrados como 'García', 'GARCIA' y socios cuyo nombre alternativo contiene 'Garcia'." | **Sí** | Socios → Socios → buscar "Garcia" (sin tilde, en cualquier capitalización) → debe aparecer "María García" (por nombre principal acentuado) y encontrarla también funcionaría si el término coincidiera solo con su nombre alternativo. Cubierto por `SocioTest` (2 tests: nombre principal y nombre alternativo). |
| 3 | "La vista de mostrador de un socio con préstamo atrasado muestra la alerta de atraso visible y el contador de atrasos en el año." | **Sí** | Socios → Socios → ver "Roberto Fernández" → debe mostrarse el préstamo de "Manual de bibliotecología" con estado "Atrasado" y la tarjeta "Atrasos (últimos 12 meses)" en 1. Cubierto por `VistaMostradorSocioTest`. |
| 4 | "La vista de mostrador de un socio Honorario no muestra ninguna restricción activa aunque tenga préstamos atrasados." | **Sí** | Socios → Socios → ver "Elena Sánchez" → a pesar de tener un atraso registrado en su historial, no debe aparecer ningún banner de "Restricción vigente". Cubierto por `VistaMostradorSocioTest` (incluye verificación de que una restricción de otro socio no se filtra incorrectamente). |

**Resumen:** los 4 criterios de aceptación del módulo son revisables hoy tanto manualmente como
por la suite automática — pendiente únicamente la primera ejecución real de esa suite.

---

## 5. Riesgo técnico resuelto durante el desarrollo: extensión `unaccent`

PostgreSQL `ILIKE` resuelve insensibilidad a mayúsculas/minúsculas pero no a acentos — "García" no
es igual a "Garcia" para `ILIKE` sin ayuda adicional. Se agregó la migración
`2024_01_03_000010_enable_unaccent_extension.php`, que habilita la extensión estándar `contrib`
`unaccent` (incluida en toda instalación de PostgreSQL 16, sin paquetes adicionales). La búsqueda
en `SocioController::index()` compara con `unaccent(columna) ILIKE unaccent('%término%')`, tanto
sobre `nombre_principal` como, mediante una subconsulta `jsonb_array_elements_text`, sobre cada
elemento de `nombres_alternativos` (columna `jsonb`, no tabla relacionada — ver
`BRIEFING-MODULO-3-SOCIOS.md`, riesgo R-3).

Si `php artisan migrate` fallara en este paso específico con un error de permisos para crear
extensiones, es la única situación que ameritaría escalar — la mayoría de los entornos de
desarrollo con PostgreSQL tienen permisos suficientes por defecto.

---

## 6. Otros comportamientos para revisar (no son criterios de aceptación explícitos, pero sí decisiones documentadas durante el desarrollo)

- **Guarda de borrado:** intentar eliminar un Tipo de Socio con socios asociados debe rechazar el
  borrado con un mensaje, no fallar con un error de base de datos ni eliminar en cascada. Cubierto
  por `TipoSocioTest`.
- **Navegación:** el enlace "Socios" del menú principal (visible solo para Administrador y
  Personal, igual que "Catálogo") lleva al listado de Socios; desde ahí hay una sub-navegación a
  Tipos de socio.
- **Historial de préstamos paginado:** la vista de mostrador de un socio incluye, además de
  préstamos y reservas activas, un historial completo de préstamos (activos e históricos)
  paginado de a 15 — sin datos del seeder que superen esa página, así que la paginación en sí no
  tiene un caso de prueba visual dedicado todavía, aunque el código (`paginate(15, ..., 'historial')`)
  ya la soporta.
- **Dependencia de lectura hacia módulos aún no implementados:** la vista de mostrador lee
  `PrestamoDomiciliario`, `Reserva` y `RestriccionSocio` — entidades cuyos módulos de escritura
  (Préstamos y devoluciones, Renovaciones y reservas, Excepciones y restricciones) todavía no
  existen. Esto no bloquea la revisión de este módulo: los esquemas y métodos de dominio
  correspondientes ya estaban completos desde el Módulo 1, y el seeder de este módulo crea los
  datos necesarios directamente. Ver `BRIEFING-MODULO-3-SOCIOS.md`, riesgo R-2.

---

## 7. Qué reportar

Si algo de lo anterior no se comporta como se describe, o `php artisan test --filter=Socios` no da
`11 passed` en la primera corrida real, es información valiosa: indicá el paso exacto, el
usuario/rol usado, y qué esperabas vs. qué obtuviste (igual que se hizo con los dos defectos reales
encontrados y corregidos en el Módulo 2, documentados en `ADR-012`). Los defectos encontrados se
documentan en `phase-summary.md` (o en un ADR si ameritan una decisión de diseño) antes de
declarar el módulo cerrado.
