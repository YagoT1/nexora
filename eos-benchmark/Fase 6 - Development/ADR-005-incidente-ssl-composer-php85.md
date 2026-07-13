# ADR-005 — Incidente: fallo SSL al instalar Composer sobre PHP 8.5 (Windows)

**Estado:** Cerrado — Resuelto. Causa raíz: interceptación TLS de Avast Antivirus (H8), con falta
de bundle de CA en PHP (H1) como factor contribuyente real. Los 5 criterios de cierre fijados están
cumplidos con evidencia objetiva (ver "Cierre formal del incidente" más abajo).
**Fecha:** 2026-07-10 (apertura)
**Decide:** Investigación técnica solicitada explícitamente por el responsable del proyecto (Yago), ejecutada en esta sesión de Cowork siguiendo el proceso de diagnóstico ya establecido en `ADR-002/003/004` (evidencia antes que hipótesis, hipótesis antes que cambio, verificación objetiva después de cada cambio).

---

## Contexto

Se actualizó el entorno de desarrollo local (Windows) incorporando PHP 8.5. Al intentar instalar Composer con el instalador oficial `Composer-Setup.exe`, la instalación falló. Este incidente bloquea el paso 2 de `docs/BOOTSTRAP.md` (`composer create-project laravel/laravel`), prerrequisito para todo el Módulo 1.

## Evidencia recolectada (verbatim, sin parafrasear)

**Salida del instalador de Composer:**
```
The Composer installer script was not successful [exit code 1].

OpenSSL failed with a 'certificate verify failed' error. This indicates a problem with the
Certificate Authority file(s) on your system, which either cannot be found or may be out of date.

Script Output:
The "https://getcomposer.org/versions" file could not be downloaded: SSL operation failed with
code 1. OpenSSL Error messages:
error:0A000086:SSL routines::certificate verify failed
Failed to enable crypto
Failed to open stream: operation failed
```

**Salida de `php -v`:**
```
PHP 8.5.8 (cli) (built: Jul  1 2026 04:02:00) (ZTS Visual C++2022 x64)
Copyright (c) The PHP Group
Built by The PHP Group
Zend Engine v4.5.8, Copyright (c) Zend Technologies
    with Zend OPcache v8.5.8, Copyright (c), by Zend Technologies
```

**Contexto adicional confirmado por el usuario:** es la primera vez que se instala Composer en esta
máquina (no es una regresión de algo que antes funcionaba) — el build de PHP es ZTS (Zend Thread
Safety), Visual C++ 2022 x64, muy reciente (compilado el 1/7/2026).

## Hipótesis consideradas

| # | Hipótesis | Justificación a favor | Cómo se descarta/confirma |
|---|---|---|---|
| **H1** | PHP en Windows no tiene configurado `openssl.cafile`/`curl.cainfo` en `php.ini` — sin bundle de CA, PHP no puede validar **ningún** certificado TLS. Es la causa más común y documentada de este error exacto en instalaciones nuevas de PHP en Windows (a diferencia de Linux, PHP en Windows no usa el almacén de certificados del sistema operativo por defecto). | El propio mensaje del instalador lo señala directamente ("problema con los archivos de Certificate Authority"). Coincide con el patrón conocido: PHP recién instalado + Windows + sin `cacert.pem` configurado. | Si `php -i` muestra `openssl.cafile` y `curl.cainfo` vacíos, y un `curl.exe` nativo (fuera de PHP) contra la misma URL **sí** funciona, H1 queda confirmada y las demás quedan descartadas. |
| **H2** | El almacén de certificados raíz de Windows está desactualizado o le falta una CA intermedia — problema a nivel sistema operativo, no de PHP. | Podría explicar el mismo síntoma. | Si `curl.exe` (que en Windows suele usar el almacén de certificados de Windows vía Schannel) **también** falla contra la misma URL, apunta a H2 en lugar de H1. |
| **H3** | Interceptación TLS (antivirus, proxy corporativo) con una CA propia que ni PHP ni el sistema reconocen. | Común en entornos corporativos/con antivirus agresivo. | Si `curl.exe` falla con un error de certificado que menciona un emisor (`Issuer`) que no es una entidad pública conocida, apunta a H3. |
| **H4** | Reloj del sistema desincronizado (una fecha muy adelantada o atrasada invalida cualquier certificado por "aún no válido"/"expirado"). | Causa simple y frecuente, barata de descartar. | Si `Get-Date` muestra una fecha/hora incorrecta, confirma H4 antes de mirar cualquier otra cosa. |

**Hipótesis principal de trabajo, no confirmada:** H1. Es la más consistente con el mensaje de
error textual y con el patrón conocido de instalaciones nuevas de PHP en Windows, pero **no se
aplicará ningún cambio hasta confirmarla con evidencia**, conforme a la regla explícita de esta
investigación.

## Evidencia diagnóstica obtenida (2026-07-11)

```
PS> php --ini
Loaded Configuration File: "C:\Users\yagot\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.5_Microsoft.Winget.Source_8wekyb3d8bbwe\php.ini"

PS> php -i | findstr /I "cafile cainfo capath"
curl.cainfo => no value => no value
openssl.cafile => no value => no value
openssl.capath => no value => no value

PS> curl.exe -vI https://getcomposer.org/versions
* schannel: next InitializeSecurityContext failed: CRYPT_E_NO_REVOCATION_CHECK (0x80092012)
  - La función de revocación no puede comprobar la revocación para el certificado.

PS> Get-Date
sábado, 11 de julio de 2026 19:20:21
```

## Análisis — confirmación/descarte de hipótesis

- **H1 — CONFIRMADA.** `openssl.cafile`, `curl.cainfo` y `openssl.capath` están los tres sin
  ningún valor en el `php.ini` realmente cargado. El build de PHP para Windows no incluye un
  bundle de CA por defecto ni usa el almacén de certificados de Windows para su extensión
  `openssl`/`curl` — sin esta directiva configurada, no tiene ningún certificado raíz contra el
  cual validar, y por lo tanto **cualquier** conexión HTTPS hecha por PHP (incluida la que hace el
  instalador de Composer) falla con `certificate verify failed`, independientemente de que el
  certificado del servidor sea perfectamente válido.
- **H2 — DESCARTADA.** El error de `curl.exe` (que sí usa el almacén de certificados de Windows
  vía Schannel) es distinto y ocurre en una etapa posterior: `CRYPT_E_NO_REVOCATION_CHECK`, un
  fallo al verificar la revocación (CRL/OCSP), no un fallo de la cadena de confianza en sí. Que
  `curl.exe` llegue hasta esa etapa confirma que el almacén de certificados raíz de Windows **sí**
  reconoce y valida la cadena del certificado de `getcomposer.org` — descarta un problema de CA a
  nivel sistema operativo.
- **H3 — sin evidencia a favor, no aplica al problema de Composer.** El error de `curl.exe` no
  muestra ningún emisor (`Issuer`) sospechoso ni ajeno a una entidad pública reconocida; es un
  fallo de verificación de revocación, un problema distinto y **no relacionado con el bloqueo de
  Composer** (que falla mucho antes, en la validación de la cadena, no en la revocación).
- **H4 — DESCARTADA.** La fecha del sistema (`sábado 11 de julio de 2026, 19:20:21`) coincide con
  la fecha real. No hay desincronización de reloj.

**Causa raíz confirmada:** PHP 8.5.8 (instalado vía WinGet) no tiene configurado ningún archivo de
Certificate Authority (`openssl.cafile`/`curl.cainfo`) en su `php.ini`. Es el comportamiento por
defecto de los builds oficiales de PHP para Windows — requieren configuración manual de un bundle
de CA, a diferencia de Linux donde PHP usa el almacén de certificados del sistema operativo
automáticamente.

**Hallazgo secundario, fuera de alcance de este incidente:** `curl.exe` (herramienta nativa de
Windows, no PHP) tiene un problema propio de verificación de revocación de certificados. No bloquea
Composer y no se aborda en este ADR — se deja registrado por si se vuelve relevante en otro
contexto.

## Fix propuesto (pendiente de aplicar)

1. Descargar el bundle de CA mantenido oficialmente por el proyecto curl (compilado desde el
   almacén de Mozilla) desde `https://curl.se/ca/cacert.pem` — es la fuente estándar recomendada
   por la propia documentación de PHP y de Composer para este error exacto.
2. Guardarlo en una ruta estable fuera de la carpeta gestionada por WinGet (para que sobreviva a
   una futura actualización de PHP que podría limpiar esa carpeta), p. ej. `C:\php\cacert.pem`.
3. Editar el `php.ini` activo (la ruta exacta confirmada arriba) agregando:
   ```ini
   openssl.cafile="C:\php\cacert.pem"
   curl.cainfo="C:\php\cacert.pem"
   ```
4. Verificar en 3 pasos incrementales (estructural → funcional aislado → extremo a extremo) antes
   de dar el incidente por cerrado. Ver mensaje de chat para el detalle exacto de comandos y
   resultado esperado en cada paso.

## Aprobación del diagnóstico y criterios de cierre formal (2026-07-11)

El diagnóstico (causa raíz H1 confirmada, fix propuesto) queda aprobado por el responsable del
proyecto. Se instruye conducir la remediación completa, paso a paso, con verificación objetiva
después de cada paso, hasta cumplir los siguientes criterios de cierre — el incidente no se
considera resuelto por la sola confirmación de la hipótesis:

1. Composer queda instalado correctamente.
2. Composer puede acceder a Internet sin errores SSL.
3. Este ADR queda actualizado reflejando el estado final.
4. Las modificaciones realizadas sobre el entorno quedan documentadas.
5. Se determina si el incidente deja alguna mejora permanente para `docs/BOOTSTRAP.md`.

Regla de avance: si el resultado de un paso coincide con lo esperado, se continúa automáticamente
con el siguiente sin esperar nueva instrucción; si no coincide, se detiene el procedimiento, se
reformula el diagnóstico y se documenta la nueva evidencia antes de proponer otro cambio.

## Bitácora de ejecución

| Paso | Acción | Resultado esperado | Resultado obtenido | Estado |
|---|---|---|---|---|
| 1 | Descargar `cacert.pem` desde curl.se a `C:\php\cacert.pem` | Archivo PEM descargado, no vacío | 189.462 bytes, `LastWriteTime` 11/7/2026 20:58:19 (recién descargado) — tamaño consistente con el bundle real de Mozilla | ✅ Confirmado |
| 2 | Configurar `openssl.cafile`/`curl.cainfo` en el `php.ini` activo (vía `Add-Content`, agregado al final del archivo) | `php -i` muestra la ruta configurada | `curl.cainfo => C:\php\cacert.pem`, `openssl.cafile => C:\php\cacert.pem` — ambos correctos | ✅ Confirmado |
| 3 | `php -r file_get_contents(...)` contra `getcomposer.org/versions` | Devuelve JSON, no `false`/warning SSL | **No coincide.** Mismo error exacto que al inicio del incidente: `certificate verify failed`, `Failed to enable crypto`, `bool(false)` — a pesar de que el paso 2 quedó confirmado correcto. | ❌ No confirmado — procedimiento detenido |
| 4 | Reintentar `Composer-Setup.exe` | Instalación exitosa, `composer -V` funcional | El instalador corrió **sin el error SSL original** (confirma la remediación). Archivos en `C:\ProgramData\ComposerSetup\bin` con fecha de hoy. `composer -V` vía el nombre corto falló por PATH no refrescado en la sesión de `pwsh` abierta antes de instalar — confirmado con evidencia: el PATH a nivel Machine/User **sí** incluye las rutas correctas (`ComposerSetup\bin`, `Composer\vendor\bin`), y `composer.bat -V` invocado por ruta completa funciona perfectamente: `Composer version 2.10.2`, `PHP version 8.5.8`, sin ningún error SSL. | ✅ Confirmado funcionalmente (pendiente solo cerrar la sesión de terminal para confirmar el uso ergonómico por nombre corto) |

## Procedimiento detenido — reformulación del diagnóstico (2026-07-11, paso 3)

El resultado del paso 3 no coincide con lo esperado: con `openssl.cafile`/`curl.cainfo` confirmados
correctamente configurados (paso 2), `php -r file_get_contents(...)` **sigue fallando con el mismo
error exacto** que al inicio del incidente. Esto refuta que H1 fuera la causa **completa** del
problema — la ausencia de bundle de CA era real (confirmada), pero corregirla no restauró el
funcionamiento. Conforme a la regla de esta investigación, se detiene el procedimiento antes de
proponer ningún otro cambio y se reformula el diagnóstico.

**Nueva hipótesis (H8):** interceptación TLS por software de seguridad (antivirus) o de red
(proxy/DPI corporativo), que re-firma el certificado de `getcomposer.org` con una CA privada. Esta
hipótesis concilia toda la evidencia previa que antes parecía contradictoria:

- `curl.exe` (usa el almacén de certificados de **Windows**, vía Schannel) validó la cadena sin
  problema y solo falló en un paso posterior (revocación) — consistente con que la CA del
  interceptor **sí** esté instalada en el almacén de Windows (una práctica común de software de
  interceptación TLS, para no romper las apps que usan Schannel).
- PHP (usa **su propio** OpenSSL, con el bundle público de Mozilla que acabamos de configurar) sigue
  rechazando la cadena — consistente con que la CA del interceptor **no** forme parte del bundle
  público de Mozilla, que solo contiene autoridades certificadoras públicas reconocidas.

**Hipótesis alternativa (H5, más simple, se descarta primero por ser más barata de verificar):** el
archivo `cacert.pem` descargado no es un bundle PEM válido (por ejemplo, si el servidor devolvió una
página de error en vez del archivo real, a pesar del tamaño plausible).

## Evidencia obtenida — H5 y H8 (2026-07-11)

**Chequeo A (H5 — validez del bundle descargado):**
```
PS> Get-Content C:\php\cacert.pem -TotalCount 1
##
PS> (Get-Content C:\php\cacert.pem | Select-String "BEGIN CERTIFICATE").Count
121
```
**H5 — DESCARTADA.** La primera línea (`##`) no es un error: es el encabezado de comentario propio
del formato del bundle de curl.se (que antepone un bloque de comentario antes de los certificados),
no un archivo corrupto. El conteo de 121 bloques `BEGIN CERTIFICATE` confirma que es un bundle
sustancial y válido. La imprecisión fue de esta investigación al describir el resultado esperado
(se esperaba la línea de certificado directamente), no un defecto del archivo.

**Chequeo B (inspección directa de la cadena de certificados real):**
```
PS> [inspección TLS directa contra getcomposer.org:443, bypaseando validación para poder inspeccionar]
Subject : CN=getcomposer.org
Issuer  : CN=Avast Web/Mail Shield Root, O=Avast Web/Mail Shield,
          OU=generated by Avast Antivirus for SSL/TLS scanning
NotBefore  : 9/7/2026 04:17:19
NotAfter   : 7/10/2026 04:17:18
Thumbprint : D5079C6E07142626E732E8FF655413D15FB3FD48
```
**H8 — CONFIRMADA.** Evidencia directa y concluyente: **Avast Antivirus** (función "Web/Mail
Shield") está interceptando el tráfico HTTPS de esta máquina y re-firmando los certificados con su
propia CA privada, generada localmente. Esto explica de forma coherente toda la evidencia reunida
en este incidente:

- El error original de Composer (`certificate verify failed`) y el error persistente de PHP después
  del fix de H1 ocurren porque el certificado que PHP recibe en realidad **no** está firmado por
  ninguna autoridad pública — está firmado por la CA privada de Avast, que nunca podría estar en el
  bundle público de Mozilla, sin importar cuán completo o actualizado esté ese bundle.
- `curl.exe` (que usa el almacén de certificados de **Windows**, vía Schannel) validó la cadena sin
  problema porque Avast instala su CA privada directamente en el almacén de Windows para no romper
  las apps que confían en él — pero igual falló más adelante, en la verificación de revocación, un
  síntoma adicional (no independiente, como se anotó preliminarmente antes) de la misma
  interceptación: los certificados sintéticos que genera Avast no tienen información de revocación
  real que un `CRYPT_E_NO_REVOCATION_CHECK` pueda verificar.
- H1 (falta de bundle de CA) fue un hallazgo real y necesario de corregir, pero **no suficiente**:
  ningún bundle público, por completo que esté, iba a validar nunca una CA privada generada
  localmente por el propio antivirus.

## Causa raíz final

**Avast Antivirus, función de escaneo HTTPS ("Web/Mail Shield"), intercepta todo el tráfico TLS
saliente de esta máquina y lo re-firma con una CA privada generada localmente.** PHP en Windows usa
su propio OpenSSL con un bundle de CA exclusivamente público (Mozilla) y no tiene forma de confiar
en esa CA privada salvo que se la agreguemos explícitamente o se elimine la interceptación. La
ausencia original de `openssl.cafile`/`curl.cainfo` (H1) era un problema real y adicional, ya
corregido, pero la causa raíz que sigue bloqueando a Composer es esta interceptación.

## Evidencia solicitada antes de proponer cualquier cambio

Ver mensaje de chat con el detalle de los comandos, resultado esperado bajo cada hipótesis, y cómo
se interpretará cada resultado.

## Elección de remediación (2026-07-11)

Presentadas 3 alternativas (agregar la CA de Avast al bundle de PHP / desactivar el escaneo HTTPS
de Avast / excepción puntual para PHP), el responsable del proyecto elige **agregar la CA de Avast
al bundle de PHP** — mantiene el escaneo de Avast activo para el resto del sistema y resuelve
específicamente a PHP/Composer.

## Intento de remediación — error de procedimiento detectado (2026-07-11)

**Resultado no coincide con lo esperado.** Se buscó en el almacén de certificados de Windows el
certificado con la huella digital (`Thumbprint`) obtenida en el chequeo B anterior
(`D5079C6E07142626E732E8FF655413D15FB3FD48`), se exportó y se agregó a `cacert.pem` (el conteo subió
correctamente de 121 a 122, confirmando que la escritura del archivo funcionó). Sin embargo,
`file_get_contents` **sigue fallando con el mismo error exacto**.

**Causa del resultado inesperado — error propio de esta investigación, no del entorno del
usuario:** el `Thumbprint` capturado en el chequeo B pertenece al **certificado hoja** que Avast
genera dinámicamente para `getcomposer.org` (`$sslStream.RemoteCertificate` devuelve el certificado
que presenta el servidor — en este caso, el certificado sintético hoja, no la CA raíz que lo
firma). Por eso el certificado exportado y agregado al bundle en este paso tiene
`Subject: CN=getcomposer.org` (confirmado en la salida de este paso), **no**
`Subject: CN=Avast Web/Mail Shield Root...`. Agregar un certificado hoja al bundle de confianza no
establece confianza en la CA que lo emitió — OpenSSL sigue sin poder construir una cadena válida
porque el certificado raíz real de Avast no está en el bundle.

Conforme a la regla acordada, se detiene el procedimiento y se corrige el paso antes de continuar:
hay que ubicar el certificado **raíz** de Avast (autofirmado: `Subject` idéntico a `Issuer`), no el
certificado hoja ya agregado (que se deja en el bundle sin efecto negativo — un certificado de más
no rompe nada, simplemente no aporta la cadena de confianza que falta).

## Remediación aplicada correctamente (2026-07-11)

Se localizó la CA raíz real de Avast (autofirmada, `Subject == Issuer ==
"CN=Avast Web/Mail Shield Root, O=Avast Web/Mail Shield, OU=generated by Avast Antivirus for
SSL/TLS scanning"`, thumbprint `81D24F871867FEF8169A67A30E957E7B79CA80EC`), distinguiéndola de las
otras dos raíces de Avast presentes en el sistema (`...Self-signed Root` y `...Untrusted Root`, para
propósitos distintos) y del certificado hoja agregado por error en el intento anterior. Se exportó
y se agregó a `cacert.pem`.

**Resultado — coincide exactamente con lo esperado:**
- `Subject`/`Issuer` confirmados idénticos, la raíz correcta.
- Conteo de certificados en el bundle: 122 → **123**.
- `php -r file_get_contents(...)` contra `https://getcomposer.org/versions` devuelve ahora
  **JSON real** (2655 bytes, contenido válido de versiones de Composer) — ya no `bool(false)` ni
  ningún error de SSL.

**Paso 3 del plan de remediación queda confirmado.** PHP puede ahora completar una conexión HTTPS
real contra la infraestructura de Composer. Se continúa automáticamente con el paso 4 (reinstalación
de Composer), conforme a la regla acordada de avanzar sin esperar nueva instrucción cuando el
resultado coincide con lo esperado.

## Verificación final — criterios 1 y 2 (2026-07-12)

Tras cerrar completamente la aplicación de terminal (para descartar el PATH obsoleto heredado de la
sesión previa a la instalación) y abrir una nueva:

```
PS> composer -V
Composer version 2.10.2 2026-07-01 11:24:45
PHP version 8.5.8 (...\php.exe)

PS> composer diagnose
Checking pubkeys: OK
Checking Composer version: OK — 2.10.2
Checking Composer and its dependencies for vulnerabilities: OK
OpenSSL version: OpenSSL 3.5.7 9 Jun 2026
Checking platform settings: OK
Checking git settings: OK — git version 2.51.0
Checking http connectivity to packagist: OK
Checking https connectivity to packagist: OK
Checking github.com rate limit: OK
Checking disk free space: OK
```

**Criterio de cierre 1 (Composer instalado) — CUMPLIDO.** `composer -V` funciona por nombre corto,
versión 2.10.2.

**Criterio de cierre 2 (Composer accede a Internet sin errores SSL) — CUMPLIDO.** `composer
diagnose` confirma explícitamente `Checking https connectivity to packagist: OK` y `Checking
github.com rate limit: OK` — ambos requieren una conexión HTTPS válida y exitosa. Ninguna línea del
diagnóstico menciona un problema de SSL/certificados.

**Hallazgo nuevo, fuera de alcance de este incidente:** `composer diagnose` reporta
`zip: extension not loaded, unzip not available, 7-Zip not available`. Composer necesita al menos
uno de esos tres mecanismos para extraer los paquetes que descarga. No bloqueó ni afectó a este
incidente (que era exclusivamente sobre SSL/CA), pero **va a bloquear el próximo paso real**
(`composer create-project laravel/laravel` en `docs/BOOTSTRAP.md`) si no se resuelve antes. Se dfeja
registrado como pendiente separado, no se resuelve acá para no mezclar alcances.

## Cierre formal del incidente

**Estado final: RESUELTO.** Los 5 criterios de cierre fijados por el responsable del proyecto están
cumplidos:

1. ✅ Composer queda instalado correctamente (`composer -V` → 2.10.2).
2. ✅ Composer puede acceder a Internet sin errores SSL (`composer diagnose` confirma HTTPS/GitHub
   OK).
3. ✅ Este documento (`ADR-005`) refleja el estado final (esta sección).
4. ✅ Ver "Modificaciones realizadas sobre el entorno" abajo.
5. ✅ Ver "Mejora permanente al procedimiento de bootstrap" abajo.

### Causa raíz (resumen ejecutivo)

Dos factores combinados, descubiertos en ese orden:

1. **Factor contribuyente (H1, confirmado, insuficiente por sí solo):** PHP 8.5.8 recién instalado
   vía WinGet no traía configurado `openssl.cafile`/`curl.cainfo` en su `php.ini` — comportamiento
   por defecto de los builds de PHP para Windows.
2. **Causa raíz determinante (H8, confirmada):** Avast Antivirus (función "Web/Mail Shield")
   intercepta el tráfico HTTPS saliente de la máquina y lo re-firma con una CA raíz propia,
   generada localmente, que no forma parte de ningún bundle público de CA (Mozilla/curl.se). Ningún
   bundle público, por completo o actualizado que esté, iba a resolver el problema sin agregar
   explícitamente esa CA privada.

Corregir solo (1) no fue suficiente — se comprobó objetivamente (paso 3 del plan, primera
iteración). Hizo falta identificar y agregar también la CA raíz real de Avast (segundo intento,
tras un error de procedimiento propio de esta investigación: la primera vez se identificó y agregó
por error el certificado *hoja* en lugar de la CA *raíz*, ver bitácora arriba).

## Modificaciones realizadas sobre el entorno

| # | Cambio | Ruta/comando | Reversible |
|---|---|---|---|
| 1 | Bundle de CA pública descargado | `C:\php\cacert.pem` (curl.se, Mozilla) | Sí — borrar el archivo/carpeta |
| 2 | `php.ini` modificado: agregadas 2 directivas al final del archivo activo | `openssl.cafile="C:\php\cacert.pem"` / `curl.cainfo="C:\php\cacert.pem"` en `...\WinGet\Packages\PHP.PHP.8.5_.../php.ini` | Sí — quitar esas 2 líneas |
| 3 | Certificado hoja de Avast agregado por error (residual, inocuo) | Apéndice en `C:\php\cacert.pem` (`Subject: CN=getcomposer.org`) | Sí — no aporta ni rompe nada; se puede quitar por prolijidad pero no es necesario |
| 4 | CA raíz real de Avast agregada (la corrección efectiva) | Apéndice en `C:\php\cacert.pem` (`Subject: CN=Avast Web/Mail Shield Root...`, thumbprint `81D24F871867FEF8169A67A30E957E7B79CA80EC`) | Sí — quitar ese bloque del archivo (pero rompería de nuevo la conexión HTTPS de PHP mientras Avast siga interceptando) |
| 5 | Composer reinstalado | `Composer-Setup.exe` (oficial), ahora corriendo sin error | Estándar — desinstalable como cualquier programa de Windows |

Ningún cambio tocó el código del proyecto (`sistema-gestion-bibliotecaria/`) ni ningún archivo
versionado en `eos-benchmark/` fuera de esta documentación. Todos los cambios son locales a la
máquina de desarrollo.

## Mejora permanente al procedimiento de bootstrap

**Sí, corresponde una mejora permanente.** Este no es un problema exclusivo de esta máquina: **cualquier**
desarrollador de este proyecto que use Windows con un antivirus que haga inspección de tráfico
HTTPS (Avast, Kaspersky, ESET, Bitdefender, y equivalentes; también proxies corporativos con
inspección TLS) va a reproducir el mismo síntoma exacto al instalar PHP/Composer por primera vez.
Se agrega una sección de troubleshooting a `docs/BOOTSTRAP.md` (ver diff aplicado) para que quien
lo pise después no tenga que repetir esta investigación completa desde cero.

## Estado

**Cerrado — resuelto.** Los 5 criterios de cierre están cumplidos con evidencia objetiva. Pendiente
separado (no bloqueante para este incidente): resolver el soporte de `zip`/`unzip`/`7-Zip` para
Composer antes de ejecutar el paso 2 de `docs/BOOTSTRAP.md`.
