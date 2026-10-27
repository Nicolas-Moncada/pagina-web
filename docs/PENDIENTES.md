# DOCUMENTACIÓN DE PENDIENTES Y GUÍA DE ACTIVOS - ASOAGROALIZAL

Este documento consolida los aspectos técnicos, fotográficos y de seguimiento institucional para la plataforma de control lechero, garantizando el cumplimiento de la directriz de **no inventar ningún dato** en la web pública:

---

## 1. Guía de Sustitución de Fotografías Reales de la Asociación

Actualmente el sistema cuenta con ilustraciones vectoriales locales en formato SVG (`assets/img/`) con la paleta de color institucional del logo oficial. La junta directiva podrá reemplazar directamente estas ilustraciones por fotografías reales de alta calidad tomadas en finca, respetando las siguientes directrices técnicas:

| Nombre del Archivo | Sección en la Web | Contenido Recomendado | Formato / Peso Máximo |
| :--- | :--- | :--- | :--- |
| `assets/img/hero-vereda.jpg` (o `.webp`) | Héroe Principal (`index.php`) | Paisaje panorámico de la Vereda Alizal o Carmen de Carupa con colinas y árboles de aliso. | WebP / JPG $\le 250\text{ KB}$ (1920 $\times$ 1080 px) |
| `assets/img/tierra.jpg` (o `.webp`) | Bloque "Nuestra Tierra" | Fotografía del terreno propio de la asociación en la Finca El Recuerdo. | WebP / JPG $\le 200\text{ KB}$ (800 $\times$ 600 px) |
| `assets/img/familia.jpg` (o `.webp`) | Bloque "Nuestra Familia" | Fotografía comunitaria de asociados en asamblea o trabajo de campo (con consentimiento). | WebP / JPG $\le 200\text{ KB}$ (800 $\times$ 600 px) |
| `assets/img/infra-tanques.jpg` (o `.webp`) | Infraestructura Propia | Tanque de enfriamiento de leche en acero inoxidable instalado en la sede. | WebP / JPG $\le 200\text{ KB}$ (800 $\times$ 600 px) |
| `assets/img/infra-laboratorio.jpg` (o `.webp`) | Infraestructura Propia | Instalaciones del laboratorio de cultivo de tejidos vegetales. | WebP / JPG $\le 200\text{ KB}$ (800 $\times$ 600 px) |
| `assets/img/infra-invernadero.jpg` (o `.webp`) | Infraestructura Propia | Módulos de invernadero y parcelas de forrajes bajo cubierta. | WebP / JPG $\le 200\text{ KB}$ (800 $\times$ 600 px) |
| `assets/img/ordeno.jpg` (o `.webp`) | Calidad y BPO | Cantinas lecheras de recolección y rutinas de ordeño higiénico. | WebP / JPG $\le 200\text{ KB}$ (800 $\times$ 600 px) |

> [!NOTE]
> Al colocar los archivos `.jpg` o `.webp` en la carpeta `assets/img/`, el sistema cargará automáticamente las fotografías reales sustituyendo las ilustraciones SVG sin necesidad de modificar el código fuente.

---

## 2. Parámetros de Calidad Composicional (Propuesta Futura)

- **Situación Actual:** La tabla transaccional `entrega` registra estrictamente `usuarioId`, `fecha`, `turno` (`MANANA`/`TARDE`) y `litros`.
- **Directriz:** No se inventaron columnas no existentes de porcentaje de grasa ni proteína en la base de datos de producción.
- **Acción Futura:** Cuando la asociación cuente con análisis rutinarios de laboratorio individual por asociado, se podrá aplicar una migración SQL agregando los campos:
  ```sql
  ALTER TABLE entrega ADD COLUMN grasa_porcentaje DOUBLE NULL AFTER litros;
  ALTER TABLE entrega ADD COLUMN proteina_porcentaje DOUBLE NULL AFTER grasa_porcentaje;
  ```

---

## 3. Revisión Jurídica de Textos Legales

- Los textos de la **Política de Tratamiento de Datos Personales** (Ley Estatutaria 1581 de 2012) y los **Términos y Condiciones de Uso** de la plataforma han sido redactados con los datos institucionales reales de la Ficha Oficial (NIT 832006127-2, dirección y canales de atención).
- Se encuentran claramente señalizados en el pie de página y en los modales como **BORRADOR – validar con asesor legal** hasta contar con la ratificación formal de la junta directiva y su asesoría jurídica.
