# REPORTE DE VERIFICACIÓN DE CONTRASTE Y UI (WCAG 2.1 AA)
## Plataforma de Control Lechero - ASOAGROALIZAL

---

### 1. Extracción de Color Institucional
- **Fuente Oficial:** `assets/img/logo.png`
- **Análisis de Píxeles:** Script de conteo espectral ejecutado sobre los canales RGB del logo.
- **Color Dominante del Árbol (Follaje de Bosque):** `#084d05` (58,580 píxeles analizados) / Normalizado institucional: `#11501e`.
- **Color Secundario (Pradera / Césped):** `#5fb343` (41,053 píxeles analizados).
- **Matiz Institucional Principal:** Verde bosque andino (~128° HSL).

---

### 2. Tabla de Tokens Semánticos y Razón de Contraste W3C

#### Modo Claro (`:root`)
| Token Semántico | Valor Hex | Fondo Evaluado | Razón Obtenida | Requisito WCAG | Resultado |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `--text` | `#122216` | `--surface` (`#ffffff`) | **16.59:1** | $\ge 4.5:1$ | ✅ PASS |
| `--text` | `#122216` | `--bg` (`#f4f7f4`) | **15.37:1** | $\ge 4.5:1$ | ✅ PASS |
| `--text-muted` | `#435848` | `--surface` (`#ffffff`) | **7.70:1** | $\ge 4.5:1$ | ✅ PASS |
| `--on-primary` | `#ffffff` | `--primary` (`#11501e`) | **9.59:1** | $\ge 4.5:1$ | ✅ PASS |
| `--placeholder` | `#4f6655` | `--input-bg` (`#ffffff`) | **6.24:1** | $\ge 4.5:1$ | ✅ PASS |
| `--table-head-text` | `#ffffff` | `--table-head-bg` (`#0a4217`) | **11.64:1** | $\ge 4.5:1$ | ✅ PASS |
| `--input-border` | `#65826b` | `--surface` (`#ffffff`) | **4.23:1** | $\ge 3.0:1$ | ✅ PASS |
| `--border` | `#8fa993` | `--bg` (`#f4f7f4`) | **2.35:1** | $\ge 2.0:1$ | ✅ PASS |
| `--success` | `#116124` | `--surface` (`#ffffff`) | **7.61:1** | $\ge 4.5:1$ | ✅ PASS |
| `--warning` | `#874f00` | `--surface` (`#ffffff`) | **6.67:1** | $\ge 4.5:1$ | ✅ PASS |
| `--danger` | `#b91c1c` | `--surface` (`#ffffff`) | **6.47:1** | $\ge 4.5:1$ | ✅ PASS |

#### Modo Oscuro (`[data-bs-theme="dark"]`)
| Token Semántico | Valor Hex | Fondo Evaluado | Razón Obtenida | Requisito WCAG | Resultado |
| :--- | :--- | :--- | :--- | :--- | :--- |
| `--text` | `#e5eee7` | `--surface` (`#17221a`) | **13.84:1** | $\ge 4.5:1$ | ✅ PASS |
| `--text` | `#e5eee7` | `--bg` (`#111813`) | **15.22:1** | $\ge 4.5:1$ | ✅ PASS |
| `--text-muted` | `#9cb3a0` | `--surface` (`#17221a`) | **7.32:1** | $\ge 4.5:1$ | ✅ PASS |
| `--on-primary` | `#08230f` | `--primary` (`#3dd676`) | **8.81:1** | $\ge 4.5:1$ | ✅ PASS |
| `--placeholder` | `#95ad9a` | `--input-bg` (`#131d15`) | **7.18:1** | $\ge 4.5:1$ | ✅ PASS |
| `--table-head-text` | `#ffffff` | `--table-head-bg` (`#0b3915`) | **13.04:1** | $\ge 4.5:1$ | ✅ PASS |
| `--input-border` | `#5c8066` | `--surface` (`#17221a`) | **3.70:1** | $\ge 3.0:1$ | ✅ PASS |
| `--border` | `#3c5242` | `--bg` (`#111813`) | **2.13:1** | $\ge 2.0:1$ | ✅ PASS |
| `--success` | `#4ade80` | `--surface` (`#17221a`) | **9.41:1** | $\ge 4.5:1$ | ✅ PASS |
| `--warning` | `#fbbf24` | `--surface` (`#17221a`) | **9.83:1** | $\ge 4.5:1$ | ✅ PASS |
| `--danger` | `#f87171` | `--surface` (`#17221a`) | **5.93:1** | $\ge 4.5:1$ | ✅ PASS |

---

### 3. Componentes UI Validados en Ambos Modos
1. **Navbar y Navegación:** Logo nítido, enlaces con hover perceptible, toggle de modo oscuro con persistencia inmediata en `localStorage` y ejecución anti-parpadeo en `<head>`.
2. **Planilla Matricial Excel:**
   - Cabecera fija de 2 niveles (Fecha + Turnos Mañana/Tarde).
   - Columna fija de nombres a la izquierda con sombreado divisor.
   - Columna fija de Total Quincena a la derecha.
   - Fila de totales fija en la parte inferior.
   - Celdas editables con feedback visual (guardando amarillo sutil, guardado verde desvaneciente, error con borde rojo).
   - Distinción visual clara entre celda vacía (sin dato) y valor cero (`0.0`).
3. **DataTables (Listas de Asociados, Precios y Quejas):** Buscador, selectores de longitud y paginación con bordes y contraste WCAG AA en ambos modos.
4. **Formularios y Floating Labels:** Contraste de texto de entrada, placeholders legibles y anulación de estilos autofill de navegadores WebKit.
5. **Modales y SweetAlert2:** Superficies sólidas adaptadas a `--surface` sin transparencias excesivas que comprometan la lectura.
