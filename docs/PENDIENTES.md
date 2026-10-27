# NOTAS TÉCNICAS Y PENDIENTES FUTUROS - ASOAGROALIZAL

Este documento registra aspectos técnicos detectados durante el rediseño para seguimiento institucional, garantizando que nada inventado aparezca en la plataforma pública:

1. **Campos Opcionales de Calidad Composicional de Leche**:
   - El esquema actual de la base de datos (`entrega`) almacena `usuarioId`, `fecha`, `turno`, `litros`.
   - No existen columnas de grasa (`% grasa`), proteína (`% proteina`), células somáticas ni acidez en la tabla `entrega`.
   - Conforme a las reglas estrictas de no inventar campos ni tocar datos existentes, la visualización actual se centra en litros entregados por turno y valor liquidado.
   - *Propuesta de migración futura*: Cuando la asociación tecnifique la toma de datos individuales de laboratorio por asociado, se podrá ejecutar una migración opcional agregando `grasa_porcentaje DOUBLE NULL` y `proteina_porcentaje DOUBLE NULL`.

2. **Textos Legales Institucionales**:
   - Los textos de Política de Tratamiento de Datos (Ley 1581 de 2012) y Términos y Condiciones han sido redactados formalmente con los datos oficiales de la ficha y marcados como **BORRADOR – validar con asesor legal**.

3. **Fotografías Reales de Finca y Sede**:
   - Se han dejado los espacios de foto con rutas locales claras en `assets/img/` (`infra-tanques.svg`, `infra-laboratorio.svg`, `infra-invernadero.svg`, `bpo-higiene.svg`, `sede-finca.svg`) para su sustitución directa con fotografías reales suministradas por la junta directiva.

