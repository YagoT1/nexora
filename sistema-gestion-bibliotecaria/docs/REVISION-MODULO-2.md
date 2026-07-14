# Guía de revisión funcional — Módulo 2 (Catálogo), Pasos 1 a 7

Este documento prepara la revisión funcional de lo entregado hasta ahora del Módulo 2 — Catálogo
(Autor, Editorial, Categoría, Libro, Ejemplar). No reemplaza `docs/BOOTSTRAP.md`: asume que ya
existe un entorno funcionando (el mismo usado para cerrar el Módulo 1, ver
`eos-benchmark/Fase 6 - Development/ADR-006-validacion-entorno-bootstrap.md` — 38/38 tests en
verde). Si todavía no tenés ese entorno, seguí primero `docs/BOOTSTRAP.md`.

---

## 1. Actualizar el entorno con lo último

```bash
git pull origin main
composer install          # por si cambiaron dependencias (no debería, en este avance)
php artisan migrate       # no hay migraciones nuevas en el Módulo 2 hasta ahora; es seguro correrlo igual
php artisan db:seed --class=CatalogoDemoSeeder
```

`CatalogoDemoSeeder` es idempotente (usa `firstOrCreate` y verificaciones de existencia antes de
crear cada Ejemplar): correrlo más de una vez no duplica datos. Si preferís partir de cero:

```bash
php artisan migrate:fresh --seed   # recrea todo, incluidos usuarios de prueba y datos de demo
```

Levantar el servidor:

```bash
php artisan serve
```

---

## 2. Usuarios de prueba (ya existentes desde el Módulo 1)

| Rol | Email | Contraseña | Acceso esperado a Catálogo |
|---|---|---|---|
| Administrador | `admin@biblioteca.test` | `password` | Completo |
| Personal | `personal@biblioteca.test` | `password` | Completo |
| Voluntario | `voluntario@biblioteca.test` | `password` | **Bloqueado** (403) — Modelo de Dominio v2, 6.1: "Gestionar catálogo y ejemplares" no incluye a Voluntario |

Primer chequeo sugerido: entrar con `voluntario@biblioteca.test` e intentar acceder a `/catalogo/libros` — debe rechazar el acceso, no redirigir silenciosamente ni mostrar contenido parcial.

---

## 3. Datos de ejemplo cargados por `CatalogoDemoSeeder`

Elegidos para ejercitar los casos que importan, no como catálogo realista:

| Libro | Autor(es) | Editorial | ISBN | Categoría(s) | Ejemplares |
|---|---|---|---|---|---|
| Ficciones | Jorge Luis Borges | Editorial Sudamericana | — (sin ISBN) | Ficción, Cuento | 1 libre circulación (compra), 1 solo sala (donación) |
| Cien años de soledad | Gabriel García Márquez | Alfaguara | 978-0307474728 | Novela | 1 restringido a autorización |
| Rayuela | Julio Cortázar | — (sin editorial) | — | Novela | 1 en reparación |
| Recopilación de refranes populares | — (sin autor) | — | — | No Ficción | 1 extraviado (donación) |

Categorías: "Ficción" y "No Ficción" son de primer nivel; "Cuento" y "Novela" son subcategorías de
"Ficción" — esto deja armado el caso de jerarquía de 2 niveles sin necesidad de cargarlo a mano.

Además, para poder revisar RN-21 (Paso 7): un Socio ("Socio de prueba (RN-21)", DNI `00000000`) con
una Reserva en estado `pendiente` sobre "Ficciones". Con los datos del seeder tal cual vienen, esa
reserva SÍ puede satisfacerse (el ejemplar de compra es libre circulación) — la alerta de RN-21 se
dispara recién si, desde la UI, cambiás la modalidad de ESE ejemplar a "Solo sala" (ver punto 7 de
la tabla de la sección 4).

---

## 4. Checklist de criterios de aceptación (Plan de Implementación v2, Módulo 2)

Cada fila indica si ya se puede revisar con lo entregado o si depende de un paso todavía pendiente.

| # | Criterio (texto del plan) | ¿Revisable ahora? | Cómo revisarlo |
|---|---|---|---|
| 1 | "El personal puede crear un Libro con múltiples autores, sin ISBN, con categoría y subcategoría." | **Sí** | Iniciar sesión como `personal@biblioteca.test` → Catálogo → Libros → Nuevo libro. Tildar 2+ autores, dejar ISBN vacío, tildar una categoría de primer nivel y una subcategoría (p. ej. Ficción + Cuento). Guardar y confirmar que aparece en el listado. |
| 2 | "El sistema no permite crear una subcategoría cuyo padre ya es subcategoría (profundidad máxima 2)." | **Sí** | Catálogo → Categorías → Editar "Cuento" (que ya es subcategoría de Ficción) → en el selector de padre no debería aparecer ninguna opción (el formulario ya la excluye). Para forzar el error de validación del servidor: crear una categoría nueva y, si se desea, intentar manipular el `<select>` vía herramientas de desarrollador del navegador para enviar el id de "Cuento" como padre — el servidor debe rechazarlo igual (`CategoriaRequest`), no solo la UI. |
| 3 | "El personal puede crear un Ejemplar vinculado a un Libro existente, con modalidad Solo sala." | **Sí** | Catálogo → Libros → Ver "Ficciones" → ya tiene un ejemplar "Solo sala" cargado por el seeder; para probar el alta, click en "+ Nuevo ejemplar" sobre cualquier libro y elegir esa modalidad. |
| 4 | "La vista del Libro muestra correctamente el estado 'Prestado' para un ejemplar con préstamo activo, sin necesidad de campo de estado explícito." | **Parcial** — `Ejemplar::estadoActual()` ya calcula "prestado" leyendo la tabla `prestamos_domiciliarios`, y la vista de detalle de Libro (`catalogo.libros.show`, Paso 6) ya muestra el resultado de ese método para cada ejemplar. Pero no hay ninguna pantalla todavía para *crear* un préstamo (Módulo 4, no iniciado), así que no se puede disparar este caso completo desde la UI. | Si querés verificarlo igual: insertar manualmente una fila en `prestamos_domiciliarios` con `estado = 'activo'` para uno de los ejemplares del seeder (por `psql` o el cliente que uses) y recargar `catalogo.libros.show` del Libro correspondiente — debería mostrar "Prestado" en la columna Estado. |
| 5 | "La búsqueda por título parcial devuelve resultados relevantes. La búsqueda por autor devuelve todos los libros del autor." | **Sí** | Catálogo → Libros → probar el formulario de búsqueda: "cien" en Título debería devolver "Cien años de soledad"; "Borges" en Autor debería devolver "Ficciones". Combinar con Categoría/Estado/Modalidad para verificar que los filtros se combinan con AND (por ejemplo, Categoría "Ficción" + Estado "Disponible" debería excluir el ejemplar "solo sala" de Ficciones si ya está prestado, y mostrar solo los disponibles). "Limpiar filtros" debe volver al listado completo. |
| 6 | "Un ejemplar con estado manual 'En reparación' muestra ese estado aunque no tenga movimiento activo." | **Sí** | Catálogo → Libros → Ver "Rayuela" → el ejemplar cargado por el seeder ya muestra "En reparación" en la columna Estado, sin tener ningún préstamo asociado. |
| 7 | "Al intentar cambiar la modalidad de acceso del único ejemplar disponible de un Libro con reservas Pendientes a Solo sala, el sistema muestra una alerta..." | **Sí** | Catálogo → Libros → Ver "Ficciones" → Editar el ejemplar de compra (libre circulación) → cambiar su modalidad a "Solo sala" → Guardar. El mensaje de confirmación debe incluir la advertencia de RN-21 (ya no queda ningún ejemplar de "Ficciones" que pueda salir de la biblioteca, y la Reserva sembrada sigue `pendiente`). Revertir el cambio deja de mostrar la advertencia en la siguiente edición que no toque la modalidad. |

**Resumen:** de los 7 criterios de aceptación del módulo, 5 son totalmente revisables hoy (1, 3, 5, 6, 7), y 1 es parcialmente revisable con una verificación manual en la base de datos (4) — el criterio 2 de la tabla del plan ya está cubierto arriba.

---

## 5. Otros comportamientos para revisar (no son criterios de aceptación explícitos, pero sí decisiones documentadas durante el desarrollo)

- **Guardas de borrado:** intentar eliminar un Autor/Editorial/Categoría/Libro que tenga
  dependientes (por ejemplo, el autor Borges tiene un libro asociado) debe rechazar el borrado con
  un mensaje, no fallar con un error de base de datos. Ver `phase-summary.md`, Pasos 1-4.
- **Ejemplar con movimiento activo:** el guard de `EjemplarController::destroy()` que impide borrar
  un ejemplar con movimiento activo no se puede ejercitar todavía con los datos del seeder (ninguno
  tiene un movimiento real, ver punto 4 de la tabla anterior) — mismo motivo que el criterio 4.
- **Navegación:** el enlace "Catálogo" del menú principal ahora lleva a Libros; desde ahí hay una
  sub-navegación a Autores/Editoriales/Categorías.
- **Vista de detalle de Libro (Paso 6):** desde el listado de Libros, el enlace "Ver" (nuevo, antes
  de "Editar") lleva a `catalogo.libros.show`, que reemplaza la gestión provisoria de ejemplares que
  hasta el Paso 5 vivía en la pantalla de edición. La pantalla de edición ahora solo edita los
  campos propios del Libro, con un enlace "Ver detalle y ejemplares →" hacia `show`.
- **Corrección post-Paso 6 (Paso 7):** crear, editar y eliminar un Ejemplar, y el enlace "Volver al
  libro" de esas pantallas, ahora redirigen a `catalogo.libros.show` en vez de a `catalogo.libros.edit`
  (que desde el Paso 6 ya no lista ejemplares). Si notás algún enlace que todavía apunte a `edit`
  después de gestionar un ejemplar, es justamente el defecto que esta corrección debía eliminar.

---

## 6. Qué reportar

Si algo de lo anterior no se comporta como se describe, o el criterio "parcial" (punto 4 de la
tabla) da un resultado inesperado al forzarlo manualmente, es información valiosa: indicá el paso
exacto, el usuario/rol usado, y qué esperabas vs. qué obtuviste. Los defectos encontrados se
documentan en `phase-summary.md` (o en un ADR si ameritan una decisión de diseño) antes de
continuar con los pasos siguientes.
