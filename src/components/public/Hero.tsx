import Link from "next/link";
import Image from "next/image";
import { LogIn, MessageSquare, Award, Calendar } from "lucide-react";

export default function Hero() {
  return (
    <section className="relative overflow-hidden bg-gradient-to-b from-campo-50 via-white to-leche-100 py-16 md:py-24">
      {/* Elementos decorativos de fondo */}
      <div className="absolute top-0 right-0 -mt-12 -mr-12 w-96 h-96 rounded-full bg-campo-100/50 blur-3xl pointer-events-none" />
      <div className="absolute bottom-0 left-0 -mb-12 -ml-12 w-96 h-96 rounded-full bg-tierra-100/40 blur-3xl pointer-events-none" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
          {/* Texto Principal */}
          <div className="lg:col-span-7 space-y-6 text-center lg:text-left">
            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-campo-100 text-campo-800 text-sm font-semibold border border-campo-200 shadow-sm">
              <Calendar className="w-4 h-4 text-campo-700" />
              <span>Más de 24 años fortaleciendo el campo lechero</span>
            </div>

            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-campo-950 tracking-tight leading-tight">
              Unidos por la calidad de nuestra leche y el bienestar de los ganaderos
            </h1>

            <p className="text-lg sm:text-xl text-gray-700 leading-relaxed max-w-2xl mx-auto lg:mx-0">
              Somos la asociación que respalda el trabajo diario de las familias lecheras. 
              Recolectamos, pesamos y liquidamos de forma justa y transparente cada litro entregado en las mañanas y tardes.
            </p>

            {/* Botones de acción */}
            <div className="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-2">
              <Link
                href="/login"
                className="btn-touch bg-campo-700 hover:bg-campo-800 text-white shadow-lg shadow-campo-700/20 text-lg flex items-center justify-center gap-2.5"
              >
                <LogIn className="w-5 h-5 text-campo-200" />
                <span>Portal de Asociados</span>
              </Link>
              <a
                href="#contacto"
                className="btn-touch bg-white hover:bg-campo-50 text-campo-800 border-2 border-campo-300 shadow-sm text-lg flex items-center justify-center gap-2.5"
              >
                <MessageSquare className="w-5 h-5 text-campo-600" />
                <span>Enviar una Consulta</span>
              </a>
            </div>

            {/* Indicadores rápidos */}
            <div className="pt-8 border-t border-campo-200/80 grid grid-cols-3 gap-4 text-center">
              <div>
                <p className="text-2xl sm:text-3xl font-extrabold text-campo-800">+120</p>
                <p className="text-sm font-medium text-gray-600 mt-0.5">Ganaderos Activos</p>
              </div>
              <div>
                <p className="text-2xl sm:text-3xl font-extrabold text-campo-800">2 Turnos</p>
                <p className="text-sm font-medium text-gray-600 mt-0.5">Mañana y Tarde</p>
              </div>
              <div>
                <p className="text-2xl sm:text-3xl font-extrabold text-campo-800">100%</p>
                <p className="text-sm font-medium text-gray-600 mt-0.5">Pago Puntual</p>
              </div>
            </div>
          </div>

          {/* Tarjeta Visual de Confianza con next/image optimizado */}
          <div className="lg:col-span-5">
            <div className="relative mx-auto max-w-md lg:max-w-none">
              <div className="relative rounded-2xl overflow-hidden shadow-2xl border-4 border-white bg-campo-800 h-80 sm:h-96">
                <Image
                  src="https://images.unsplash.com/photo-1527153857715-3908f2ae5e81?auto=format&fit=crop&w=800&q=80"
                  alt="Ganadería lechera y praderas verdes de la asociación"
                  fill
                  priority
                  sizes="(max-width: 1024px) 100vw, 40vw"
                  className="object-cover"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-campo-950/80 via-campo-950/20 to-transparent flex flex-col justify-end p-6 text-white z-10 pointer-events-none">
                  <span className="inline-block bg-dorado-500 text-campo-950 font-bold text-xs uppercase tracking-wider px-2.5 py-1 rounded-md mb-2 w-max">
                    Garantía de Pureza
                  </span>
                  <h2 className="text-xl font-bold">Producción Limpia y Sostenible</h2>
                  <p className="text-sm text-campo-100 mt-1">
                    Apoyo técnico continuo y recolección puntual en cada finca afiliada.
                  </p>
                </div>
              </div>

              {/* Tarjeta flotante de confianza */}
              <div className="absolute -bottom-6 -left-6 bg-white rounded-xl shadow-xl p-4 border border-campo-200 hidden sm:flex items-center gap-3 z-20">
                <div className="w-12 h-12 rounded-lg bg-campo-100 flex items-center justify-center text-campo-700">
                  <Award className="w-7 h-7" />
                </div>
                <div>
                  <p className="text-sm font-bold text-campo-950">Calidad Certificada</p>
                  <p className="text-xs text-gray-600">Cumplimiento estricto en cadena de frío</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}
