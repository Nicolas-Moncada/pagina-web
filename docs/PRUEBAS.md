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

### 2. Cuentas de Acceso para Pruebas en Entorno de Desarrollo
| Rol | Identificación | Rol en Sistema | Estado | Destino tras Login |
| :--- | :--- | :--- | :--- | :--- |
| **ADMINISTRADOR** | `[CÉDULA-ADMIN-OCULTA]` | `ADMIN` | Activo | `admin.php` |
| **GANADERO** | `[CÉDULA-GANADERO-PRUEBA]` | `GANADERO` | Activo | `panel.php` |

> [!IMPORTANT]
> **POLÍTICA ESTRICTA DE PRIVACIDAD:**  
> Conforme a las reglas del proyecto y la Ley 1581 de 2012, ninguna cédula real figura en la documentación, logs ni respuestas. El identificador del Administrador General se encuentra configurado únicamente como variable en `sql/limpieza_total.sql`.

---

### 3. Checklist de Pruebas Manuales Paso a Paso

#### TAREA 1: Planilla Matricial Horizontal en `admin.php`
- [x] **Estructura Matricial:**
  - Acceder a `http://localhost/asociacion/admin.php` con la cuenta de Administrador.
  - Verificar que la pestaña "Planilla Quincenal" muestra la cuadrícula bidimensional completa (filas = ganaderos activos en orden alfabético, columnas = días de la quincena).
  - Encabezado de dos niveles: Nivel 1 con fechas ("Lun 16") y Nivel 2 con subcolumnas ☀️ Mañana y 🌅 Tarde.
  - Columna de nombres fija a la izquierda (`sticky left`).
  - Columna TOTAL QUINCENA por ganadero fija a la derecha (`sticky right`).
  - Fila final con totales por columna (día y turno) y gran total fija abajo (`sticky bottom`).
  - NO usa DataTables en la matriz; DataTables se reserva exclusivamente para las listas (Asociados, Quincenas, Buzón).
- [x] **Navegación y Edición Inline sin Recarga:**
  - Clic en una celda de pesaje: permite escribir directamente litros en decimales (`50.5` o `50,5`).
  - **Enter:** guarda el valor y baja el foco automáticamente a la misma columna de la fila siguiente.
  - **Tab / Shift+Tab:** navega a la celda derecha / izquierda.
  - **Flechas de dirección (↑ ↓ ← →):** mueven el foco en la cuadrícula.
  - **Esc:** cancela la edición y restaura el valor original.
  - Celda vacía se distingue visualmente de valor 0.
  - Feedback visual por celda: guardando (spinner/borde ámbar) $\to$ guardado ✓ (borde verde) $\to$ error con opción de reintento.
  - Recálculo instantáneo en cliente de totales por ganadero, por columna y gran total, confirmado por el servidor.
- [x] **Pegado en Bloque desde Excel:**
  - Copiar un rango de celdas numéricas en Excel (ej. 3 filas $\times$ 4 columnas).
  - Seleccionar una celda en la matriz y presionar `Ctrl + V`.
  - La cuadrícula se llena y guarda en lote mediante `api/guardar_pesaje_matriz.php` en una sola transacción segura.
- [x] **Control de Quincena Liquidada/Cerrada:**
  - Si la quincena tiene `cerrada = 1`, todas las celdas se deshabilitan para edición y el backend rechaza cualquier intento de modificación.
- [x] **Exportación:**
  - Clic en "Descargar CSV": genera archivo CSV estructurado con ganaderos, días y turnos.

---

#### TAREA 2: Tema, Colores Institucionales y Modo Oscuro
- [x] **Paleta Oficial del Logo:**
  - Verde bosque institucional (`#11501e` / `#084d05`) y verde pradera (`#5fb343`) derivados directamente de `assets/img/logo.png`.
  - Hoja de estilos única y centralizada: `assets/css/theme.css`.
  - Tokens semánticos completos (`--bg`, `--surface`, `--surface-2`, `--text`, `--text-muted`, `--border`, `--primary`, `--primary-hover`, `--on-primary`, etc.).
- [x] **Verificación Automatizada WCAG 2.1 AA:**
  - Ejecutar: `php tools/verificar_contraste.php`.
  - Resultado: **100% de los pares cumplen con WCAG 2.1 AA** ($\ge 4.5:1$ en texto normal y $\ge 3.0:1$ en bordes e interfaz) tanto en tema claro como en tema oscuro.
- [x] **Modo Oscuro Anti-FOUC y Persistente:**
  - Alternador accesible en navbar.
  - Cambio instantáneo sin parpadeo blanco gracias al script síncrono en `<head>`.
  - Persistencia mediante `localStorage` y respeto a `prefers-color-scheme`.
  - Modales, DataTables, formularios, tablas y alertas adaptadas con grises verdosos elegantes (sin `#000` ni neones).

---

#### TAREA 3: Plan y Script de Limpieza de BD Protegiendo al Administrador
- [x] **Plan Documentado (`docs/LIMPIEZA_PLAN.md`):**
  - Inventario de las 14 tablas de la base de datos con conteo actual de filas.
  - Clasificación fundamentada: VACIAR (10 tablas), CONSERVAR SIN TOCAR (2 tablas: `quincena`, `configuracion_quincena`), CONSERVAR ADMIN / VACIAR PRUEBAS (2 tablas: `usuario`, `asociados`), DUDOSAS (0).
  - Protocolo de respaldo (`mysqldump`) y procedimiento de rollback.
- [x] **Script Autónomo Transaccional (`sql/limpieza_total.sql`):**
  - Variable `@CEDULA_ADMIN` declarada una sola vez al inicio.
  - Procedimiento de validación inicial: aborta con `SIGNAL SQLSTATE '45000'` si no existe exactamente 1 usuario ADMIN con esa cédula.
  - **Bloque (A) DRY-RUN:** `SELECT` con conteos previos de filas a eliminar sin modificar datos.
  - **Bloque (B) DESTRUCTIVO:** `DELETE` ordenado respetando claves foráneas (primero tablas dependientes `alertaquincena`, `entrega`, etc.), eliminando usuarios de prueba y protegiendo al Administrador.
  - **Bloque (C):** Reseteo de `AUTO_INCREMENT = 1` en tablas vaciadas.
  - **Bloque (D) VERIFICACIÓN:** Cálculo y comparación del Checksum SHA-256 de la fila del administrador antes y después. Garantiza que ningún dato del administrador cambió.
- [x] **Seguridad:** El script está listo para ser ejecutado por el usuario en phpMyAdmin tras su verificación en seco.

---

#### TAREA 4: Landing Page Institucional (`index.php`)
- [x] **Estética Agri-Tech Corporativa:**
  - Tipografía Fraunces (serif editorial elegante) en títulos e Inter en cuerpo ($\ge 16$ px).
  - Ritmo vertical amplio, espacios en blanco generosos y tarjetas redondeadas con sombras suaves.
- [x] **Navbar:**
  - Logo oficial a la izquierda, enlaces ancla, alternador de modo oscuro y botón "Acceso al Sistema" (o "Mi Panel" si hay sesión).
  - Transparente sobre el héroe y sólido con sombra sutil al hacer scroll.
- [x] **Héroe a Ancho Completo:**
  - Fondo local con overlay verde oscuro garantizando contraste WCAG AA.
  - Titular derivado de la misión institucional y doble llamado a la acción.
  - Dos tarjetas de vidrio (`glass-card-hero`) con los textos exactos de **Misión** y **Visión** de la ficha.
- [x] **Quiénes Somos:**
  - Razón social oficial completa, NIT 832006127-2, personería jurídica, constitución en el año 2000 (más de 25 años) y contador animado de 32 asociados.
- [x] **Bloques Alternados:**
  - "Nuestra tierra": Vereda Alizal, Finca El Recuerdo, terreno propio.
  - "Nuestra familia": 32 asociados campesinos, sin nombres ni datos privados.
- [x] **Infraestructura Propia:**
  - 3 tarjetas de columnas: Tanques de enfriamiento en acero inoxidable, laboratorio de cultivo de tejidos vegetales e invernaderos y módulos forrajeros.
  - Chips para cantinas, nevera, carguero y terreno propio.
- [x] **Calidad y BPO:**
  - 4 tarjetas de líneas de trabajo (ordeño a hora fija, incentivo por calidad, diagnósticos en finca, capacitación continua).
- [x] **Aliados Institucionales:**
  - Mención en solo texto (Corporación PBA y Fundación Bolívar Davivienda).
- [x] **Contacto Oficial:**
  - Formulario con floating labels, honeypot y checkbox obligatorio de la Ley 1581 de 2012. Envío por fetch con SweetAlert2 sin recarga.
  - Tarjeta con sede oficial, correo, teléfono único 3185235690, sin horarios de atención y mapa embebido de Carmen de Carupa.
- [x] **Footer Corporativo:**
  - Fondo verde profundo `#082811`, datos oficiales, modales de términos y tratamiento de datos marcados *BORRADOR – validar con asesor legal*.

---

#### TAREA 5: Portal de Acceso (`login.php`)
- [x] **Tarjeta Dividida (Split Card):**
  - Panel izquierdo verde institucional con emblema, NIT y bienvenida. Panel derecho con formulario táctil. En móvil colapsa limpiamente a 1 columna.
- [x] **Mostrar/Ocultar Contraseña:**
  - Botón táctil para alternar visualización de clave.
- [x] **CAPTCHA GD Nativo:**
  - Código distorsionado generado en servidor con vigencia estricta de 120 segundos.
  - Botón de refresco inmediato.
  - Fallback a SVG aritmético en entornos sin GD.
- [x] **Seguridad y Rate Limiting:**
  - Bloqueo de 15 minutos tras 5 intentos fallidos consecutivos por IP y cédula.
  - `session_regenerate_id(true)` tras éxito.
  - Alertas SweetAlert2 estilizadas con la paleta.
  - Ningún mensaje revela si la cédula existe en la base de datos.
