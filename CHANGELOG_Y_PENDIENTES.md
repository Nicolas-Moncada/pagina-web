# REGISTRO DE CAMBIOS (CHANGELOG) Y TAREAS PENDIENTES
**Plataforma de Control Lechero - ASOAGROALIZAL**  
Carmen de Carupa, Cundinamarca | NIT: `832006127-2`  
Última Actualización: 27 de Octubre de 2026

---

## 1. Registro de Cambios Recientes (Changelog Oficial)

Todas las modificaciones siguen el estándar [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/) y las directrices de [Conventional Commits](https://www.conventionalcommits.org/es/v1.0.0/).

### [Versión 2.4.0] - 2026-10-27

#### 🚀 Novedades y Mejoras Funcionales
- **Unificación de Totales Diarios en el Pie de Tabla (`admin.php`):**
  - Se modificó la fila inferior del `<tfoot>` de la planilla matricial para consolidar las dos celdas independientes (Mañana `M` y Tarde `T`) en una **única celda diaria centrada con `colspan="2"`** (`id="tot-dia-YYYY-MM-DD"`).
  - Implementación de la función `recalcularTotalDiaDOM(fecha)` en JavaScript, que suma en tiempo real los litros de ambos turnos (`M + T`, ej. `46,0 L`) al editar celdas o pegar bloques sin recargar la página.
- **Importador Inteligente de Hojas de Cálculo (.xlsx / .csv):**
  - Incorporación del botón estilizado `"Importar Excel"` junto al botón existente de exportación CSV.
  - Procesamiento del archivo del lado del cliente mediante **SheetJS (`xlsx.full.min.js`)** y parser nativo de CSV delimitado por punto y coma o coma.
  - Mapeo automático de asociados por Cédula (o nombre) y asignación directa a cada fecha y turno de la quincena activa.
  - Sincronización atómica a través de `api/guardar_pesaje_matriz.php` con acción `batch`, actualizando el estado visual de las celdas (`is-saved`, `is-zero`, `is-empty`), totales de asociados y el gran total general.
  - Compatibilidad total con la estructura generada por el botón *"Descargar CSV"*.

#### 🎨 Experiencia de Usuario y Corrección Visual (UI/UX)
- **Corrección de Contraste en Pestañas y Botones Activos:**
  - Solución al problema de legibilidad donde la pestaña activa *"Planilla Matricial de Pesajes"* mostraba texto oscuro sobre fondo azul marino.
  - Aplicación de reglas CSS en `assets/css/theme.css` forzando `color: #ffffff !important;` e íconos blancos en `.nav-pills .nav-link.active`, `.tabbar-premium .nav-link.active` y botones primarios (`.btn-aso-primary`).
- **Portada y Héroe Institucional de Alta Fidelidad:**
  - Integración de fotografía real de campo (`assets/img/hero-ganado.webp`) con gradiente atmosférico de alto contraste.
  - Subrayado dorado activo en los 8 enlaces de navegación del portal público (`index.php`).
  - Eliminación definitiva del selector flotante de tamaño de letra (`A / A+ / A++`) en todas las plantillas y scripts.
- **Corrección de Codificación UTF-8:**
  - Reparación de emojis y caracteres corrompidos (`☀️ Mañana y 🌅 Tarde`, `Cédula`, `Contraseña`, etc.) en `login.php` y vistas de autenticación.

#### 🛡️ Calidad, Rendimiento y Pruebas
- **Validación Cross-Browser y Cero Desbordamiento:**
  - Pruebas automatizadas en motor headless a 1366 × 768 px (escritorio) y 390 × 844 px (móvil) con resultado `sw <= iw` (desbordamiento horizontal nulo).
  - Normalización de gutters y espaciados responsivos con `row g-4 g-lg-5`.

---

## 2. Cosas Pendientes (Backlog / To-Do Prioritario)

Lista clasificada por prioridad de las tareas recomendadas antes y durante la salida a producción en internet:

### 🔴 Prioridad Alta (Seguridad y Limpieza Inmediata)
- [x] **Autorización y Ejecución del Plan de Limpieza de Archivos Basura:**
  - Dependencias obsoletas de Node.js (`node_modules/`, `src/`, `prisma/`) eliminadas, reduciendo más de 450 MB de peso innecesario.
  - Carpetas huérfanas `css/` y `js/` removidas; el proyecto utiliza canónicamente `assets/`.
  - Duplicado `img/logo.png` eliminado; todas las vistas enlazan canónicamente a `assets/img/logo.png`.
  - Respaldos temporales de SQL archivados y eliminados del árbol de trabajo.
- [x] **Corrección Sintáctica en `database.sql`:**
  - Corregida la definición de la columna `mensaje TEXT` a `mensaje TEXT NOT NULL` en la tabla `consulta`.
- [x] **Normalización de Conexión PDO:**
  - Actualizados `recuperar_admin.php`, `reset_db.php` y `restablecer_password.php` para utilizar el singleton de base de datos oficial `includes/db.php` e `includes/config.php`.
- [ ] **Protección de Scripts de Mantenimiento CLI:**
  - Proteger o trasladar `reset_db.php` y `recuperar_admin.php` para que solo sean ejecutados por terminal CLI o restringidos por `.htaccess` en servidores compartidos.

### 🟡 Prioridad Media (Infraestructura y Despliegue)
- [ ] **Configuración de Hosting y Dominio Propio:**
  - Adquirir dominio institucional (ej. `asoagroalizal.com` o `.co`).
  - Contratar hosting compartido cPanel económico (Hostinger / DonWeb) con PHP 8.1+ y SSL Let's Encrypt gratuito.
  - Conectar DNS a través de Cloudflare para protección DDoS y CDN.
- [ ] **Configuración Segura de Base de Datos Remota:**
  - Crear base de datos de producción y reemplazar credenciales por defecto (`root` sin clave) por un usuario dedicado con contraseña alfanumérica robusta en `includes/config.php`.
- [ ] **Copia de Seguridad Automatizada:**
  - Configurar Cron Job semanal para volcado automático de MySQL comprimido en `.sql.gz`.

### 🟢 Prioridad Baja (Nuevas Funcionalidades y Mejoras Futuras)
- [ ] **Recibos Quincenales en PDF:**
  - Diseñar plantilla de volante de liquidación en PDF con desglose diario y valor total a pagar para que los ganaderos la descarguen o impriman.
- [ ] **Service Worker para Registro Offline (PWA):**
  - Permitir al administrador digitar pesajes en la báscula de la vereda sin internet, sincronizando al conectarse.
- [ ] **Notificaciones por SMS / WhatsApp:**
  - Envío automático de notificación al ganadero al cerrarse y liquidarse la quincena.
- [ ] **Validación Legal Formal:**
  - Presentar los borradores de la Política de Tratamiento de Datos (Ley 1581 de 2012) ante la asamblea o asesor legal de la asociación para su aprobación definitiva.

