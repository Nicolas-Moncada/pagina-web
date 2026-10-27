# PROTOCOLO DE VERIFICACIÓN Y PRUEBAS MANUALES EN XAMPP
## Plataforma de Control Lechero - ASOAGROALIZAL (Carmen de Carupa, Cundinamarca)

---

### 1. Entorno de Ejecución Verificado
- **Servidor Web:** Apache / 2.4.58 (Win64) en XAMPP Windows
- **Motor PHP:** PHP 8.2.12 / 8.2.35 con extensiones `pdo_mysql`, `gd`, `session`, `openssl`, `mbstring`
- **Base de Datos:** MySQL / MariaDB 10.4.32 (Base de datos: `asociacion_ganaderos`, charset `utf8mb4`)
- **Directorio de Trabajo:** `c:\visual estudio code\pagina web\`
- **Directorio Público XAMPP:** `C:\xampp\htdocs\asociacion\`
- **URL Base:** `http://localhost/asociacion/`

---

### 2. Cuentas Oficiales Disponibles para Pruebas
| Rol | Cédula | Nombre Completo | Clave Inicial | Estado | Destino tras Login |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **ADMINISTRADOR** | `79164451` | Luis Hernando Moncada | `123456` | Activo | `admin.php` |
| **GANADERO** | `20444983` | Hilda Ines Montaño Pinilla | `123456` | Activo | `panel.php` |

> [!NOTE]
> La base de datos ha sido verificada y entregada **únicamente** con estas 2 cuentas activas, conforme a las instrucciones obligatorias.

---

### 3. Checklist de Pruebas Manuales Paso a Paso

#### Prueba 1: Landing Page Pública (`index.php`)
- [x] **Carga Inicial:** Acceder a `http://localhost/asociacion/index.php`. Verificar carga en HTTP 200 sin advertencias ni errores en log de Apache.
- [x] **Fidelidad de Datos:**
  - Teléfono visible único: `3185235690` (ningún otro número).
  - Razón social completa y sigla `ASOAGROALIZAL` con NIT `832006127-2`.
  - Fundación en el año 2000 (más de 25 años de trayectoria).
  - Contador animado hasta **32 Asociados** (`counter-animated` con IntersectionObserver).
  - Misión y Visión con los textos exactos de `docs/FICHA_OFICIAL_ASOAGROALIZAL.md`.
  - Sin horarios de acopio ni de atención en ninguna sección.
  - Sin cifras ni temperaturas inventadas (ej. eliminado "4.0 °C").
  - Sin marcadores `[COMPLETAR]` visibles en la interfaz.
- [x] **Infraestructura (Bento Grid):**
  - Tanques de enfriamiento, laboratorio de cultivo de tejidos, invernaderos, cantinas, nevera, carguero y terreno propio.
- [x] **Aliados Estratégicos:** En solo texto (Corporación PBA y Fundación Bolívar Davivienda).
- [x] **Botón WhatsApp:** Inactivo por defecto según bandera `WHATSAPP_ACTIVO = false` en `includes/config.php`.
- [x] **Formulario de Contacto:**
  - Floating labels para Nombre, Teléfono/Correo y Mensaje.
  - Campo Honeypot invisible para protección anti-bot.
  - Envío asíncrono con SweetAlert2 hacia `api/consultas.php` sin parpadeo ni recarga.
- [x] **Modales Legales en Footer:**
  - Política de Tratamiento de Datos Personales (Ley 1581 de 2012) con nota *BORRADOR – validar con asesor legal*.
  - Términos y Condiciones de Uso del sistema.

#### Prueba 2: Modo Oscuro Inmediato (Anti-FOUC)
- [x] **Alternar Modo:** Clic en el botón con ícono de luna en el navbar superior.
- [x] **Paleta:** Verificación visual de fondos `#111814` (gris carbón bosque elegante, sin negros puros `#000`), bordes `#24352b`, textos `#e2ede6`, badges contrastados WCAG AA.
- [x] **Persistencia:** Recargar la página (`F5`). Verificar que el tema persiste sin parpadeo blanco (script síncrono en `<head>`).
- [x] **Compatibilidad Global:** El modo oscuro cubre tablas, modales, formularios, SweetAlert2 y DataTables.

#### Prueba 3: Portal de Acceso (`login.php`)
- [x] **Diseño Split-Card:** Columna izquierda institucional verde bosque con branding oficial y columna derecha con formulario táctil. En celulares conmuta a columna única.
- [x] **Mostrar/Ocultar Clave:** Clic en el botón del ojo junto al campo de contraseña para alternar tipo `text`/`password`.
- [x] **CAPTCHA GD Nativo:**
  - Desafío visual distorsionado generado en servidor por `includes/captcha_gd.php`.
  - Botón de refrescar para cambiar de código.
  - Validación case-insensitive de un solo uso con caducidad estricta de 120 segundos.
  - Switch configurable en `includes/config.php` (`USE_RECAPTCHA_V2`).
- [x] **Protección contra Fuerza Bruta:**
  - 5 intentos erróneos consecutivos disparan bloqueo de 15 minutos por IP y usuario registrado en tabla `login_intentos`.
- [x] **Regeneración de Sesión:** `session_regenerate_id(true)` tras autenticación exitosa.

#### Prueba 4: Panel del Ganadero (`panel.php`)
- [x] **Protección IDOR:** Iniciar sesión con Hilda (`20444983`).
  - La consulta SQL utiliza estrictamente `$_SESSION['usuario']['id']`. No es posible ver entregas de otro asociado cambiando parámetros de URL.
- [x] **Pesaje de Hoy:** Visualización diferenciada por turnos (☀️ Mañana / 🌅 Tarde) y total acumulado del día.
- [x] **Resumen Quincenal:** Tarjetas métricas de Litros Acumulados, Promedio Diario, Precio Oficial por Litro y Pago Estimado en pesos colombianos (`$ COP`).
- [x] **Selector Histórico:** Desplegable para navegar entre quincenas pasadas y la quincena en curso.
- [x] **DataTables Autónomo:**
  - Tabla responsive con turnos e insignias.
  - Diccionario de idioma español embebido (`AsoagroUI.dataTablesSpanish`), 100% operativo sin internet ni fallos de CDN externo.
  - Botones táctiles de exportación a Excel, PDF e Imprimir.
- [x] **Buzón de Inconformidad (1 por Quincena):**
  - Solo permite radicar un reclamo si no existe uno previo para el periodo seleccionado.
  - Si ya existe, muestra el estado ('PENDIENTE', 'REVISADA', 'RESUELTA') y el detalle histórico.

#### Prueba 5: Panel del Administrador (`admin.php`)
- [x] **Control de Acceso:** Redirige con HTTP 302 a `login.php` si no hay sesión activa con rol `ADMIN`.
- [x] **Módulo de Planilla:**
  - Filtros dinámicos combinados por Quincena, Ganadero Asociado y Turno.
  - Formulario de registro rápido de entregas con inserción idempotente.
  - Eliminación segura de pesajes con confirmación y token CSRF.
  - Tabla DataTables con totales automáticos en pie de página (`tfoot`).
- [x] **Módulo de Asociados:**
  - Listado completo de asociados con estado Activo/Inactivo.
  - Modal para crear o editar datos y predios.
  - Botón para reestablecer clave temporal a `123456` obligando cambio en primer ingreso.
- [x] **Módulo de Precios:**
  - Configuración del precio por litro en pesos colombianos para la quincena.
  - Apertura y cierre de quincenas.
- [x] **Módulo de Buzón:**
  - Atención de inconformidades radicadas por los ganaderos.
  - Registro y visualización de consultas enviadas desde la landing page pública.

#### Prueba 6: Endpoint API (`api/consultas.php`)
- [x] **Doble Compatibilidad:** Acepta peticiones con encabezado `application/json` (fetch/AJAX) y envíos estándar `POST` de formulario.
- [x] **Honeypot:** Si el campo invisible `website_hp` contiene datos, responde `{ success: true }` silenciosamente sin guardar basura en la base de datos.
- [x] **Rate Limit:** Restringe a 1 consulta por minuto por IP para evitar saturación.

---

### 4. Resumen de Archivos Tocados y Entregados
1. `includes/config.php` - Parámetros corporativos oficiales, flags, base de datos y directivas de sesión.
2. `includes/db.php` - Conexión Singleton PDO (`utf8mb4`, `ERRMODE_EXCEPTION`, `EMULATE_PREPARES => false`).
3. `includes/auth.php` - Control de sesiones, invalidación por `sessionVersion` y control de roles.
4. `includes/csrf.php` - Generación y validación de tokens anti-falsificación de peticiones.
5. `includes/helpers.php` - Sanitización `e()`, formateo de moneda colombiana COP, litros y fechas en español.
6. `includes/captcha_gd.php` - Servicio CAPTCHA nativo PHP GD con distorsión, expiración y fallback SVG.
7. `includes/header.php` - Cabecera unificada responsive con script anti-FOUC y menú glassmorphism.
8. `includes/footer.php` - Pie de página legal con modales de Tratamiento de Datos y Términos.
9. `assets/css/theme.css` - Sistema de diseño "agri-tech", paleta del logo, Bento Grid y variables para modo oscuro.
10. `assets/js/theme.js` - Controlador de tema, observadores de scroll, contador 32 asociados y DataTables español embebido.
11. `index.php` - Landing corporativa inmersiva, fiel a la ficha oficial y accesible.
12. `login.php` - Acceso seguro con tarjeta dividida (Split-Card), CAPTCHA GD y control de bloqueo por fuerza bruta.
13. `registro.php` - Formulario seguro para nuevos ganaderos asociados.
14. `olvide_password.php` - Protocolo de recuperación de acceso institucional.
15. `cambiar_password.php` - Cambio forzado o voluntario con incremento de `sessionVersion`.
16. `panel.php` - Portal del Ganadero con protección IDOR y liquidación quincenal.
17. `admin.php` - Panel de control administrativo integral con filtros y exportación.
18. `api/consultas.php` - API REST para PQRS y consultas web con honeypot y rate limit.
19. `sql/backup_pre.sql` - Respaldo de seguridad previo de la base de datos.
20. `sql/migracion_001.sql` - Migración SQL idempotente y aditiva.
21. `docs/FICHA_OFICIAL_ASOAGROALIZAL.md` - Única fuente oficial de datos institucionales.
22. `docs/AUDITORIA.md` - Informe exhaustivo de auditoría previa.
23. `docs/PENDIENTES.md` - Observaciones de expansión técnica futura.
24. `docs/PRUEBAS.md` - Este documento de checklist manual y pruebas de integración.

