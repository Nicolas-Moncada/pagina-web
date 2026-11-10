# INFORME MAESTRO DE AUDITORÍA, HARDENING Y CIBERSEGURIDAD
## Plataforma de Control Lechero - ASOAGROALIZAL (Carmen de Carupa, Cundinamarca)
**NIT:** 832006127-2  
**Fecha de Implementación:** 2026-11-10  
**Entorno:** XAMPP (Apache 2.4, PHP 8.2+, MySQL 10.4 MariaDB)  
**Arquitectura:** Native PHP (Singleton PDO), Bootstrap 5.3, SheetJS, Google Identity Services (OAuth 2.0)  

---

## RESUMEN EJECUTIVO
Se completó con éxito la auditoría integral de código, refactorización visual accesible para adultos mayores rurales, ampliación funcional operativa y el blindaje de seguridad correspondiente a la **Checklist de 27 Controles de Hardening** exigidos por los estándares modernos de desarrollo seguro (OWASP Top 10, NIST y CIS Benchmarks).

---

## MATRIZ DE CUMPLIMIENTO: 27 CONTROLES DE SEGURIDAD

| # | Control de Seguridad | Archivo / Componente | Mecanismo de Implementación | Estado |
|---|----------------------|-----------------------|------------------------------|:------:|
| **1** | **Google Identity Services (OAuth 2.0)** | `api/auth_google.php`, `login.php`, `registro.php` | Validación server-side de `id_token` JWT (TokenInfo oficial + fallback offline), auto-vinculación por `google_id` / `email` y enlace con la cédula del asociado. | ✅ Activo |
| **2** | **Hashing Criptográfico Argon2id** | `includes/helpers.php` (`hashPassword`), `login.php` | Implementación de `PASSWORD_ARGON2ID` con coste de memoria (65536 KB), tiempo (4 iteraciones) y 1 hilo. Rehash transparente al ingresar con claves legacy. | ✅ Activo |
| **3** | **Cookies de Sesión Blindadas** | `includes/config.php` | Parámetros forzados: `httponly = true`, `samesite = Strict`, `use_only_cookies = 1` y bandera `secure` condicional si HTTPS está activo. | ✅ Activo |
| **4** | **Regeneración de ID de Sesión** | `login.php`, `api/auth_google.php` | Ejecución de `session_regenerate_id(true)` tras cada inicio de sesión exitoso y tras elevación o sincronización de roles para prevenir session fixation. | ✅ Activo |
| **5** | **Rate Limiting Anti-Fuerza Bruta** | `login_intentos`, `includes/helpers.php` | Bloqueo temporal por 15 minutos al registrar 5 intentos fallidos consecutivos por tupla `(cedula, ip)`. | ✅ Activo |
| **6** | **Recuperación Segura con Tokens Criptográficos** | `solicitudes_recuperacion`, `includes/helpers.php` | Generación de tokens mediante `bin2hex(random_bytes(32))`, almacenamiento en hash, de uso único y caducidad estricta de 15 minutos. | ✅ Activo |
| **7** | **Consultas 100% Parametrizadas (PDO Anti-SQLi)** | `includes/db.php`, `api/db.php` | `PDO::ATTR_EMULATE_PREPARES => false`, `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`. 100% de consultas usan `prepare()` y `execute()`. | ✅ Activo |
| **8** | **Middleware de Rutas Administrativas** | `api/auth_middleware.php` | Verificación en caliente de sesión activa, `rol === 'ADMIN'` verificado contra la base de datos en cada petición, cuenta activa y versión de sesión. | ✅ Activo |
| **9** | **Bloqueo de Archivos Sensibles y `.env`** | `.htaccess` (raíz), `logs/.htaccess` | Regla `<FilesMatch>` en Apache denegando acceso HTTP a `.env`, `database.sql`, `.git`, `.log`, `.md`, `.bat` y `.sh` (HTTP 403 Forbidden verificado). | ✅ Activo |
| **10** | **Deshabilitación de Listado de Directorios** | `.htaccess` | Directiva `Options -Indexes -MultiViews` activa en servidor Apache para impedir exploración de carpetas. | ✅ Activo |
| **11** | **Modo Producción Seguro y Logs Privados** | `includes/config.php`, `logs/error.log` | Desactivación de `display_errors` en entorno de producción; registro silencioso y seguro de trazas en `logs/error.log` con carpeta inaccesible vía web. | ✅ Activo |
| **12** | **Protección Universal CSRF** | `includes/csrf.php` | Generación de token criptográfico por sesión (`generarCsrfToken`), validación con `hash_equals()` en todos los formularios y peticiones AJAX. | ✅ Activo |
| **13** | **Prevención Estricta contra XSS** | `includes/helpers.php` (`e()`), vistas | Función `e(?string)` con `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')` en salidas HTML y manipulación DOM con `textContent`. | ✅ Activo |
| **14** | **Cabecera HTTP X-Frame-Options** | `includes/config.php`, `.htaccess` | Valor `SAMEORIGIN` / `DENY` forzado en cabeceras Apache y PHP para mitigar ataques de Clickjacking. | ✅ Activo |
| **15** | **Cabecera HTTP X-Content-Type-Options** | `includes/config.php`, `.htaccess` | Valor `nosniff` forzado para evitar ataques por confusión de tipos MIME en navegadores cliente. | ✅ Activo |
| **16** | **Cabecera HTTP Referrer-Policy** | `includes/config.php`, `.htaccess` | Valor `strict-origin-when-cross-origin` para impedir la fuga de identificadores o tokens en cabeceras de navegación. | ✅ Activo |
| **17** | **Content Security Policy (CSP)** | `includes/config.php` | Política `Content-Security-Policy` restrictiva pero compatible con Google Identity Services (`https://accounts.google.com`), CDNs de Bootstrap y SweetAlert2. | ✅ Activo |
| **18** | **Restricción de CORS** | `includes/config.php`, APIs | Restricción estricta de solicitudes cruzadas al origen local y al dominio corporativo oficial. | ✅ Activo |
| **19** | **Integridad Financiera Server-Side** | `api/guardar_pesaje_matriz.php`, `panel.php` | Recálculo matemático estricto de litros diarios, totales quincenales y liquidación en pesos COP en el servidor antes de persistir o exportar. | ✅ Activo |
| **20** | **Validación Server-Side y Tipado Estricto** | `includes/helpers.php` (`normalizarCedula`) | Validación de cédulas (6 a 12 dígitos numéricos), límites de litros (0 a 9.999 L) y filtrado de turnos válidos (`MANANA`, `TARDE`). | ✅ Activo |
| **21** | **Validación y Aislamiento de Archivos** | `includes/helpers.php` | Validación de tipo MIME real con `finfo_file()`, límite de 2 MB y almacenamiento en directorios sin permisos de ejecución. | ✅ Activo |
| **22** | **Control de Sesiones Concurrentes (`sessionVersion`)** | `includes/auth.php`, `api/auth_middleware.php` | Campo `sessionVersion` en tabla `usuario`. Si cambia la contraseña o se desactiva la cuenta, las sesiones abiertas en otros equipos se invalidan inmediatamente. | ✅ Activo |
| **23** | **Cambio Obligatorio de Clave en Primer Ingreso** | `includes/auth.php`, `usuario.debeCambiarPassword` | Bloqueo de acceso a las vistas principales si `debeCambiarPassword = 1`, redirigiendo forzosamente a `cambiar_password.php`. | ✅ Activo |
| **24** | **Cierre y Congelamiento de Quincenas** | `api/gestionar_quincena.php`, `admin.php` | Capacidad de congelar la edición inline de quincenas liquidadas (`cerrada = 1`). La reapertura requiere confirmación obligatoria con contraseña de Administrador. | ✅ Activo |
| **25** | **Bitácora Inmutable de Auditoría (`audit_logs`)** | `audit_logs`, `api/obtener_bitacora.php` | Registro persistente de usuario, fecha/hora, acción (`EDITAR_PESAJE`, `CERRAR_QUINCENA`, etc.), valor anterior, valor nuevo, detalles e IP. | ✅ Activo |
| **26** | **Gestión Interactiva de Reclamos** | `api/responder_reclamo.php`, `admin.php`, `panel.php` | Estados controlados (`PENDIENTE`, `EN_REVISION`, `RESUELTO`) con respuesta oficial del administrador reflejada en tiempo real en el panel del ganadero. | ✅ Activo |
| **27** | **Aislamiento de Entorno y Variables Sensibles (`.env`)** | `.env`, `includes/config.php` | Extracción de credenciales de base de datos, contraseñas iniciales y Client IDs fuera del árbol de control de versiones público. | ✅ Activo |

---

## DETALLE DE NUEVAS FUNCIONALIDADES OPERATIVAS IMPLEMENTADAS

### 1. Panel de Administración (`admin.php`)
- **4 Tarjetas de KPIs de Producción en Vivo:**
  1. *Acopio Hoy:* Total en litros recolectados hoy con desglose (☀️ Mañana y 🌅 Tarde).
  2. *Variación vs Ayer (%):* Cálculo porcentual comparativo en tiempo real respecto al día previo.
  3. *Total Quincena a Liquidar:* Monto acumulado en COP calculado en servidor según la tarifa fijada.
  4. *Entregas de Hoy:* Balance de ganaderos activos que ya entregaron vs. pendientes de entrega.
- **Cierre y Reapertura de Quincenas:**
  - Botón en la barra superior que abre modal de confirmación con contraseña del administrador.
  - Validación segura en `api/gestionar_quincena.php` contra el hash Argon2id del administrador.
  - Bloqueo instantáneo de la edición inline y del pegado desde Excel cuando la quincena está cerrada.
- **Exportación a Excel (.xlsx) y CSV:**
  - Botón "Excel (.xlsx)" integrado con SheetJS y botón "Descargar CSV".
  - Exporta la planilla completa con encabezados de fechas, turnos, totales por ganadero y pie diario.
- **Colilla de Pago Quincenal (Recibo de Liquidación):**
  - Botón en cada fila de ganadero para abrir el modal de colilla de pago.
  - Generación de desglose diario (fecha, turno mañana, turno tarde, total día), resumen de litros, precio por litro y neto a pagar en COP.
  - Botón de impresión con estilos dedicados `@media print` para exportar o imprimir en formato voucher/PDF.
- **Historial de Auditoría (Bitácora de Cambios):**
  - Modal interactivo alimentado por `api/obtener_bitacora.php` que lista cronológicamente quién modificó cada celda de pesaje, el valor previo y el valor nuevo.
- **Buzón Interactivo de Reclamos:**
  - Permite al administrador responder a la inconformidad quincenal radicada por el ganadero, actualizar el estado (`PENDIENTE`, `EN_REVISION`, `RESUELTO`) y registrar la respuesta oficial.

### 2. Panel del Ganadero (`panel.php`)
- **Visualización de Respuesta Oficial:**
  - Si la administración respondió al reclamo quincenal, se presenta una tarjeta destacada con el estado de la solicitud, la fecha y el texto oficial del administrador.
- **Impresión de Liquidación Personal:**
  - Botón "Imprimir Liquidación" que permite al ganadero imprimir su comprobante quincenal.

### 3. Pantallas de Acceso (`login.php` y `registro.php`)
- **Google Identity Services (OAuth 2.0):**
  - Botón nativo de "Continuar con Google" integrado con diseño de marca.
  - Vinculación inteligente: si el correo coincide con una cédula registrada, inicia sesión inmediatamente; si no, permite vincular la cédula con un toque.
- **Argon2id Automático:**
  - Rehash automático y silencioso al iniciar sesión para cuentas creadas con contraseñas antiguas.
- **Alternar Contraseña:**
  - Botones táctiles de mostrar/ocultar contraseña en todos los campos de acceso y registro.

---

## VERIFICACIÓN Y PRUEBAS EN XAMPP LOCAL

1. **Prueba de Sintaxis PHP:**
   - Comando ejecutado: `php -l` sobre los 40 archivos PHP del proyecto.
   - Resultado: **0 errores de sintaxis detectados.**
2. **Prueba de Servidor Apache y MySQL:**
   - MySQL en puerto 3306: Operativo.
   - Apache en puerto 80: Operativo.
3. **Prueba de Rutas y Cabeceras HTTP:**
   - `http://localhost/asociacion/index.php` $\rightarrow$ **HTTP 200 OK**
   - `http://localhost/asociacion/login.php` $\rightarrow$ **HTTP 200 OK**
   - `http://localhost/asociacion/registro.php` $\rightarrow$ **HTTP 200 OK**
   - `http://localhost/asociacion/.env` $\rightarrow$ **HTTP 403 Forbidden** (Acceso bloqueado)
   - `http://localhost/asociacion/database.sql` $\rightarrow$ **HTTP 403 Forbidden** (Acceso bloqueado)
   - `http://localhost/asociacion/logs/error.log` $\rightarrow$ **HTTP 403 Forbidden** (Acceso bloqueado)
4. **Prueba de API Middleware:**
   - Solicitudes no autenticadas a `api/gestionar_quincena.php` y `api/guardar_pesaje_matriz.php` $\rightarrow$ **HTTP 401 Unauthorized** con payload JSON limpio.
