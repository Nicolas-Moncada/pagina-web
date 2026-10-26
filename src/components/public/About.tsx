import { Target, Compass, HeartHandshake, ShieldCheck, Sun, Clock } from "lucide-react";

export default function About() {
  return (
    <section id="nosotros" className="py-20 bg-white border-y border-campo-100">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {/* Encabezado */}
        <div className="text-center max-w-3xl mx-auto space-y-4 mb-16">
          <span className="text-sm font-bold uppercase tracking-wider text-campo-600 bg-campo-50 px-3 py-1 rounded-full border border-campo-200">
            Nuestra Esencia
          </span>
          <h2 className="text-3xl sm:text-4xl font-extrabold text-campo-950">
            ¿Quiénes Somos?
          </h2>
          <p className="text-lg text-gray-700 leading-relaxed">
            Somos una organización comunitaria y gremial fundada con el propósito de unir las voluntades
            y el esfuerzo diario de los productores lecheros de la región. Transformamos el trabajo de las
            madrugadas en prosperidad, asegurando canales de acopio dignos y pagos quincenales exactos.
          </p>
        </div>

        {/* Tarjetas de Misión y Visión */}
        <div id="mision-vision" className="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
          {/* Misión */}
          <div className="bg-gradient-to-br from-campo-50 to-white p-8 sm:p-10 rounded-2xl border-2 border-campo-200 shadow-sm relative overflow-hidden group hover:border-campo-400 transition-all">
            <div className="w-14 h-14 rounded-xl bg-campo-700 text-white flex items-center justify-center mb-6 shadow-md">
              <Target className="w-8 h-8 text-campo-200" />
            </div>
            <h3 className="text-2xl font-bold text-campo-900 mb-3">Nuestra Misión</h3>
            <p className="text-base sm:text-lg text-gray-700 leading-relaxed">
              Fomentar el desarrollo sostenible y la rentabilidad de las familias ganaderas asociadas, 
              garantizando el acopio responsable, el pesaje transparente y la comercialización eficiente 
              de leche de la más alta calidad higiénica y composicional.
            </p>
          </div>

          {/* Visión */}
          <div className="bg-gradient-to-br from-tierra-50 to-white p-8 sm:p-10 rounded-2xl border-2 border-tierra-200 shadow-sm relative overflow-hidden group hover:border-tierra-400 transition-all">
            <div className="w-14 h-14 rounded-xl bg-tierra-700 text-white flex items-center justify-center mb-6 shadow-md">
              <Compass className="w-8 h-8 text-tierra-200" />
            </div>
            <h3 className="text-2xl font-bold text-tierra-900 mb-3">Nuestra Visión</h3>
            <p className="text-base sm:text-lg text-gray-700 leading-relaxed">
              Consolidarnos como la asociación ganadera modelo en la región, reconocida por la excelencia 
              en buenas prácticas de ordeño, tecnología accesible para el pequeño y mediano productor, 
              y la solidez financiera de todos nuestros miembros.
            </p>
          </div>
        </div>

        {/* Pilares / Valores de Trabajo */}
        <div className="bg-campo-900 text-white rounded-3xl p-8 sm:p-12 shadow-xl">
          <div className="text-center max-w-2xl mx-auto mb-10">
            <h3 className="text-2xl sm:text-3xl font-bold">Nuestros Compromisos Fundamentales</h3>
            <p className="text-campo-200 text-base mt-2">
              Principios inquebrantables que guían cada recolección y cada liquidación
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 text-center">
            <div className="bg-campo-800/60 p-6 rounded-2xl border border-campo-700">
              <Sun className="w-10 h-10 text-dorado-400 mx-auto mb-3" />
              <h4 className="text-lg font-bold">Doble Turno Diario</h4>
              <p className="text-sm text-campo-100 mt-2">
                Recepción constante tanto en el ordeño de la mañana como en el de la tarde.
              </p>
            </div>

            <div className="bg-campo-800/60 p-6 rounded-2xl border border-campo-700">
              <ShieldCheck className="w-10 h-10 text-campo-300 mx-auto mb-3" />
              <h4 className="text-lg font-bold">Transparencia Total</h4>
              <p className="text-sm text-campo-100 mt-2">
                Cada litro pesado se registra en el sistema para consulta inmediata del asociado.
              </p>
            </div>

            <div className="bg-campo-800/60 p-6 rounded-2xl border border-campo-700">
              <Clock className="w-10 h-10 text-dorado-400 mx-auto mb-3" />
              <h4 className="text-lg font-bold">Pago Quincenal Puntual</h4>
              <p className="text-sm text-campo-100 mt-2">
                Cierres quincenales exactos: del 1 al 15 y del 16 al último día de cada mes.
              </p>
            </div>

            <div className="bg-campo-800/60 p-6 rounded-2xl border border-campo-700">
              <HeartHandshake className="w-10 h-10 text-campo-300 mx-auto mb-3" />
              <h4 className="text-lg font-bold">Apoyo al Ganadero</h4>
              <p className="text-sm text-campo-100 mt-2">
                Acompañamiento cercano a personas mayores y familias campesinas.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

