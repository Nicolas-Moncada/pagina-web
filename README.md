# 🥛 Asociación de Ganaderos - Plataforma de Control Lechero
### Versión 100% Nativa para XAMPP (Apache + PHP 8 + MySQL)

Sistema web completo de gestión lechera, pesajes por turno (Mañana ☀️ y Tarde 🌅) y liquidación quincenal para asociaciones y productores de leche.

---

## 🚀 Inicio Rápido con XAMPP (Sin Comandos de Terminal)

1. Abre el **Panel de Control de XAMPP**.
2. Presiona **Start** en los módulos:
   - **Apache** (Servidor Web)
   - **MySQL** (Base de Datos)
3. Abre tu navegador en:
   👉 **[http://localhost/asociacion](http://localhost/asociacion)**

*(Opcional: También puedes hacer doble clic en `INICIAR_SISTEMA.bat` para verificar servicios y abrir el navegador en 1 clic).*

---

## 🔑 Acceso al Sistema

### 👤 Administrador General
- **URL de Ingreso:** [http://localhost/asociacion/login.php](http://localhost/asociacion/login.php)
- **Cédula Inicial:** `[CÉDULA-ADMIN-PROTEGIDA]`
- **Contraseña Inicial:** Configurada de forma segura en la variable `ADMIN_INITIAL_PASSWORD` del archivo `.env`.
- En el primer inicio de sesión se exige el cambio de contraseña por una segura de al menos 10 caracteres.
- **Panel Administrativo:** `admin.php`
- **Funciones:** Planilla matricial interactiva tipo Excel por fechas con edición rápida inline, gestión de ganaderos, fijación de precios quincenales y buzón de quejas.

### 🧑‍🌾 Ganaderos Asociados
- **URL de Ingreso:** [http://localhost/asociacion/login.php](http://localhost/asociacion/login.php)
- **Registro de Asociados:** [http://localhost/asociacion/registro.php](http://localhost/asociacion/registro.php)
- **Panel del Ganadero:** `panel.php` (Acceso individual a sus registros de ordeño y liquidación quincenal).

---

## 📋 Módulos y Arquitectura del Sistema

### 1. Landing Page Pública (`index.php`)
- Presentación oficial: ¿Quiénes Somos?, Misión y Visión, Principios de la Asociación.
- Galería fotográfica de fincas, ganado y faenas de ordeño.
- Formulario de contacto directo sincronizado con la base de datos (`api/consultas.php`).

### 2. Panel del Ganadero (`panel.php`)
- **Tarjeta "Registro del Día":** Pesajes de Mañana (☀️), Tarde (🌅) y Total Entregado Hoy.
- **Tarjeta "Quincena en Curso":** Litros acumulados en el periodo activo, precio oficial fijado y pago estimado en COP.
- **Planilla Quincenal Excel (Solo Lectura):** Tabla horizontal de 15/16 días con fechas y turnos (Mañana y Tarde), total quincenal del ganadero y totales diarios por turno. Bloqueada contra edición para garantizar transparencia.
- **Buzón de Inconformidades con Límite de 1 Alerta:** Formulario para registrar reclamos sobre los pesajes de la quincena. Valida y restringe a **UNA (1) sola alerta por periodo**, enviando notificación automática por correo a `asoagroalisal@gmail.com`.

### 3. Panel del Administrador (`admin.php`)
- **Planilla Matricial Excel por Fechas:** Encabezados dobles de fechas y turnos (☀️ Mañana / 🌅 Tarde), 1 fila continua por ganadero con columna fija a la izquierda (Nombre y Cédula) y columna fija a la derecha (Total Quincena).
- **Edición Inline en Celdas:** Clic directo sobre cualquier casilla de turno para agregar, editar o eliminar pesajes al instante con cálculo automático de totales de columna en el pie (`<tfoot>`).
- **Formulario de Registro Rápido:** Sincronizado en tiempo real con la planilla matricial.
- **Gestión de Ganaderos:** Alta y edición de asociados.
- **Fijación de Precios:** Configuración del valor por litro en cada quincena.
- **Buzón de Reclamos:** Visualización consolidada de quejas de ganaderos y mensajes web.

### 4. Base de Datos MySQL (`database.sql`)
- Conexión nativa PDO en `api/db.php`.
- Auto-instalación de tablas e inserción de datos iniciales en caso de base de datos limpia.
- Archivo `database.sql` listo para importar en phpMyAdmin.
