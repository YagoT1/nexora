# ADR-004 — Servidor MCP para acceso a PostgreSQL

**Estado:** Aceptada
**Fecha:** 2026-07-09
**Decide:** Revisión técnica solicitada explícitamente por el responsable del proyecto (Yago), ejecutada en esta sesión de Cowork.

---

## Contexto

El proyecto requiere que un cliente MCP (Claude Desktop, Cursor, Claude Code) pueda inspeccionar y
consultar la base de datos PostgreSQL 16 del Módulo 1 — para depurar el bootstrap de `ADR-002`,
inspeccionar el esquema de las 25 entidades del dominio, y en general acelerar el desarrollo de los
próximos módulos. No existía a la fecha ninguna instancia de PostgreSQL corriendo, ni configuración
de MCP para bases de datos en ningún cliente. Se investigaron activamente las alternativas en lugar
de asumir que la opción "oficial" (`@modelcontextprotocol/server-postgres`) fuera la adecuada.

## Alternativas consideradas

| Alternativa | Estado | Ventajas | Desventajas |
|---|---|---|---|
| **`@modelcontextprotocol/server-postgres`** (servidor de referencia de Anthropic) | **Archivado en mayo de 2025.** Repositorio movido a `modelcontextprotocol/servers-archived`. | Era la opción "oficial" por nombre; configuración simple. | Tiene una vulnerabilidad de inyección SQL sin parchear que permite bypasear el modo de solo lectura (`COMMIT; DROP SCHEMA public CASCADE`), documentada por Datadog Security Labs. Sigue teniendo >76.000 descargas semanales solo por tutoriales desactualizados que lo referencian. **Descartada por razón de seguridad concreta y verificable**, no por preferencia. |
| **`crystaldba/postgres-mcp`** ("Postgres MCP Pro") | Activo. MIT license, PyPI, Discord, badge de contribuidores en GitHub. | Reemplazo directo señalado por la comunidad como sucesor del server archivado. Corrige explícitamente la clase de vulnerabilidad que archivó al oficial: parsea el SQL con `pglast` y rechaza `COMMIT`/`ROLLBACK` antes de ejecutar, evitando el mismo bypass. Soporta modo `unrestricted` (dev) y `restricted` (solo lectura + límite de tiempo, para producción) — encaja con la necesidad del proyecto de usar la misma herramienta contra Postgres local (dev) y, más adelante, Render staging. Probado explícitamente contra Postgres 15/16/17 (coincide con el stack aprobado). Funciona vía Docker o Python (uvx/pipx/uv), con instrucciones oficiales para Claude Desktop. | Trae funcionalidad adicional (tuning de índices, `pg_stat_statements`, `hypopg`) que el Módulo 1 no necesita todavía — no es un problema funcional, pero es más superficie de la estrictamente necesaria hoy; se mitiga usándolo solo con sus herramientas básicas (`list_schemas`, `get_object_details`, `execute_sql`) por ahora. |
| **`mcp-server-pg`** (postgres-mcp.dev) | Presentado como reemplazo directo y liviano del server archivado, enfocado en consultas parametrizadas y aplicación estricta de solo-lectura. | Más simple/enfocado que Postgres MCP Pro si solo se necesita ejecutar queries. | **No se pudo verificar de forma independiente**: la página del proyecto no cargó contenido al intentar leerla (probablemente renderizada por JavaScript, no accesible con las herramientas de este sandbox) y no se confirmaron métricas de adopción/mantenimiento con la misma solidez que `crystaldba/postgres-mcp`. No se descarta como opción futura, pero no se recomienda ahora por falta de verificación, no por un defecto conocido. |
| Conectores del registro de Cowork (Supabase, PlanetScale, ClickHouse, MotherDuck) | Disponibles en el registro, pero ninguno es "Postgres genérico vía connection string". | Setup en un clic dentro de Cowork, sin configuración manual de archivos. | Cada uno ata el proyecto a una plataforma gestionada específica. La arquitectura aprobada (`ADR-001`, DA-04) especifica PostgreSQL nativo en Render.com, no Supabase ni PlanetScale — adoptar cualquiera de estos para la base de datos del proyecto sería un cambio de arquitectura no solicitado y fuera del alcance de esta tarea. (Supabase ya está conectado en esta sesión de Cowork, pero se usa para otros fines, no para esta base de datos). |

## Decisión

Se adopta **`crystaldba/postgres-mcp`** ("Postgres MCP Pro"), instalado vía Docker, en modo
`--access-mode=unrestricted` contra una instancia local de PostgreSQL 16 levantada con
`docker-compose.yml` (nuevo, en la raíz de `sistema-gestion-bibliotecaria/`).

Criterios de selección, en orden de peso:

1. **Seguridad verificable**: corrige de forma documentada y específica la vulnerabilidad que
   inhabilitó al servidor "oficial". No es una preferencia — es la razón excluyente para descartar
   la opción con el nombre más reconocible.
2. **Alineación arquitectónica**: no ata el proyecto a una plataforma gestionada distinta de la ya
   aprobada (Render.com + PostgreSQL nativo).
3. **Cobertura de ambos entornos con la misma herramienta**: el modo `restricted` cubre el caso de
   uso futuro contra Render staging sin necesitar una herramienta distinta.
4. **Verificabilidad de las afirmaciones de este ADR**: se citan fuentes concretas (ver más abajo)
   en lugar de asumir cuál es "la oficial" o "la más mantenida" por conocimiento previo — el propio
   servidor que ese conocimiento previo habría recomendado es, de hecho, el que está archivado y es
   inseguro.

## Instalación y configuración

Procedimiento completo, reproducible y con checklist de verificación en
`sistema-gestion-bibliotecaria/docs/POSTGRES-MCP-SETUP.md`. Resumen de lo ejecutado en esta sesión:

- Creado `sistema-gestion-bibliotecaria/docker-compose.yml` (Postgres 16, credenciales alineadas
  con `.env.example`).
- Actualizado `docs/BOOTSTRAP.md` para referenciarlo como forma de cumplir su propio prerrequisito.
- Agregada la entrada `postgres` a `C:\Users\yagot\.cursor\mcp.json` (JSON validado
  programáticamente), preservando la entrada `filesystem` existente.
- **No se pudo** configurar Claude Desktop directamente: `%APPDATA%\Claude` está reservado como
  almacenamiento interno de Cowork y no es accesible desde esta sesión. Se documentó el snippet
  exacto (también validado) y el procedimiento manual en `POSTGRES-MCP-SETUP.md`.
- **No se pudo verificar la conexión en vivo**: requiere Docker Desktop y los clientes MCP
  corriendo en la máquina real del usuario, fuera del alcance de este sandbox. Se dejó un checklist
  de verificación manual en el mismo documento.

## Consecuencias

- Queda pendiente, a cargo del usuario: levantar el contenedor (`docker compose up -d`), completar
  la configuración manual de Claude Desktop, reiniciar los clientes MCP, y confirmar la conexión
  con el checklist de `POSTGRES-MCP-SETUP.md`.
- Si en el futuro este mismo servidor se reapunta a Render staging, cambiar `--access-mode` de
  `unrestricted` a `restricted` (documentado explícitamente en la guía de setup, para que no se
  pierda ese paso).
- Este ADR y la guía de setup quedan como procedimiento reutilizable para cualquier instalación
  futura del mismo servidor MCP, en esta o en otra máquina.

## Segunda ronda de verificación (2026-07-10)

A pedido explícito de re-analizar la carpeta y probar todo lo posible, se ejecutaron las
siguientes pruebas adicionales desde esta sesión de Cowork:

1. **Re-escaneo de `proximamente/`.** Sin cambios relevantes desde la última revisión. Se detectó,
   sin embargo, un dato que no se había cuantificado en `ADR-003`: además de los locks huérfanos ya
   documentados, `proximamente/.git/objects/` tiene **94 archivos `tmp_obj_*` huérfanos**. Se
   verificaron sus timestamps (`7/7 07:52` a `7/7 09:56` — antes incluso del incidente propio de
   esta sesión) y se confirmó que **todos corresponden al mismo incidente histórico ya diagnosticado
   en `ADR-002`** (los intentos fallidos de `git init` previos a esta sesión), no a actividad nueva
   ni en curso. No se detectó ningún proceso `git` corriendo en el sandbox (`ps aux`). No se tocó
   ni se borró ninguno de estos archivos.
2. **Re-validación de sintaxis.** `mcp.json` (Cursor) y `docker-compose.yml` siguen íntegros y
   válidos.
3. **Prueba de conectividad de red hacia el Postgres del usuario.** Se intentó abrir una conexión
   TCP a `127.0.0.1:5432` desde este sandbox: `Connection refused`, como se esperaba — el sandbox
   de Cowork es una máquina Linux aislada (hostname `claude`), sin ninguna ruta de red hacia el
   `localhost` de la máquina Windows del usuario. Esto confirma con evidencia, no solo por
   afirmación, el límite ya señalado: **la verificación de la conexión real solo puede hacerse
   desde la máquina del usuario.**
4. **Verificación de que no apareció ningún tool nuevo en esta sesión de Cowork.** Se buscaron tools
   relacionados con "postgres/database/sql" disponibles en esta sesión: solo aparecen los de
   Supabase (conector distinto, ya conectado previamente, sin relación con este setup). Configurar
   Cursor o Claude Desktop no tiene ningún efecto sobre el conjunto de herramientas de una sesión de
   Cowork — son clientes MCP completamente independientes entre sí.

**Conclusión de esta ronda:** todo lo que es verificable desde Cowork (integridad de archivos,
sintaxis, ausencia de conectividad cruzada, ausencia de efectos colaterales en el tooling de esta
sesión) está en orden. Lo que falta — levantar el contenedor, confirmar que el MCP conecta, correr
una consulta de prueba — sigue dependiendo enteramente de que el usuario lo ejecute en su propia
máquina y reporte el resultado; no es algo que este entorno pueda ejecutar por sí mismo.

## Fuentes consultadas

- [GitHub - modelcontextprotocol/servers-archived](https://github.com/modelcontextprotocol/servers-archived) — confirma el archivado del servidor de referencia.
- [postgres-mcp.dev/migrate](https://postgres-mcp.dev/migrate/) — documenta la vulnerabilidad de inyección SQL del servidor archivado.
- [GitHub - crystaldba/postgres-mcp](https://github.com/crystaldba/postgres-mcp) — README oficial, instrucciones de instalación y configuración citadas directamente.
