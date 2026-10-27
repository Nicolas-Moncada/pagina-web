# AUDITORÍA TÉCNICA Y DE CONTENIDO - ASOAGROALIZAL

| Archivo | Problema Detectado | Gravedad | Corrección Arquitectural |
| :--- | :--- | :--- | :--- |
| `database.sql` (L53) | Sintaxis SQL errónea (`` `mensaje TEXT` ``) en la tabla `consulta`. | **Alta** | Corregir definición a `` `mensaje` TEXT NOT NULL `` en migración aditiva. |
| `database.sql` / `usuario` | Falta de columnas requeridas (`finca`, `debeCambiarPassword`, `sessionVersion`, `email`). | **Alta** | Incorporar campos no destructivos en `sql/migracion_001.sql`. |
| `admin.php` / `js/admin.js` | Tablas vacías en HTML dependientes de `localStorage` para asociados y entregas. | **Alta** | Eliminar dependencia de `localStorage`. Implementar Server-Side Rendering (SSR) con PDO y DataTables BS5. |
| `panel.php` / `js/panel.js` | Contenedor de pesajes vacío a la espera de `fetch()`. | **Media** | Generar la planilla directamente desde PHP con fallback de estado vacío amigable. |
| Global en formularios | Ausencia de tokens anti-CSRF en login, consultas y pesajes. | **Alta** | Implementar `includes/csrf.php` y validación estricta en cada petición `POST`. |
| `api/captcha.php` | Captcha en texto SVG legible sin procesamiento gráfico. | **Media** | Desarrollar CAPTCHA nativo PHP GD con distorsión, líneas de ruido y fallback SVG aritmético. |
| Endpoints `api/*.php` | Fuga de información técnica mediante exposición de `$e->getMessage()`. | **Media** | Registrar error en `error_log()` y retornar mensaje genérico al usuario. |
| `index.php` / Vistas previas | Teléfono desactualizado (314...), horarios de acopio visibles y datos que contradicen la ficha. | **Media** | Normalizar teléfono único oficial a `3185235690`, remover horarios y usar textos exactos de la ficha. |
| `index.php` (L641-903) | Imágenes remotas hotlinkeadas a `images.unsplash.com`. | **Baja** | Reemplazar por imágenes locales en `assets/img/` (logo oficial, SVGs y placeholders locales). |

