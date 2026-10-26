import Link from "next/link";
import { Milk, Shield, ArrowUp } from "lucide-react";

export default function Footer() {
  return (
    <footer className="bg-campo-950 text-white border-t border-campo-900 pt-16 pb-12">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 md:grid-cols-12 gap-8 mb-12">
          {/* Columna Marca */}
          <div className="md:col-span-5 space-y-4">
            <div className="flex items-center gap-3">
              <div className="w-10 h-10 rounded-lg bg-campo-700 text-white flex items-center justify-center">
                <Milk className="w-6 h-6" />
              </div>
              <span className="text-xl font-bold tracking-tight">
                Asociación de Ganaderos
              </span>
            </div>
            <p className="text-campo-200 text-sm leading-relaxed max-w-sm">
              Comprometidos con el desarrollo rural, el pago justo por cada litro de leche y el bienestar de los productores campesinos de nuestra tierra.
            </p>
            <div className="flex items-center gap-2 text-xs text-campo-300">
              <Shield className="w-4 h-4 text-dorado-400" />
              <span>Sistema de Control y Liquidación Lechera Oficial</span>
            </div>
          </div>

          {/* Enlaces Rápidos */}
          <div className="md:col-span-3 space-y-3">
            <h4 className="text-base font-bold text-white tracking-wider uppercase">
              Enlaces Directos
            </h4>
            <ul className="space-y-2 text-sm text-campo-200">
              <li>
                <a href="#nosotros" className="hover:text-white transition-colors">
                  ¿Quiénes Somos?
                </a>
              </li>
              <li>
                <a href="#mision-vision" className="hover:text-white transition-colors">
                  Misión y Visión
                </a>
              </li>
              <li>
                <a href="#galeria" className="hover:text-white transition-colors">
                  Nuestra Labor y Fotos
                </a>
              </li>
              <li>
                <a href="#contacto" className="hover:text-white transition-colors">
                  Formulario de Contacto
                </a>
              </li>
            </ul>
          </div>

          {/* Acceso a Asociados */}
          <div className="md:col-span-4 space-y-3">
            <h4 className="text-base font-bold text-white tracking-wider uppercase">
              Portal del Ganadero
            </h4>
            <p className="text-sm text-campo-200">
              ¿Eres asociado? Ingresa con tu número de cédula para consultar tus entregas de hoy y tu pago quincenal acumulado.
            </p>
            <div className="pt-2">
              <Link
                href="/login"
                className="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-campo-700 hover:bg-campo-600 text-white font-bold text-sm shadow transition-colors"
              >
                Ingresar al Portal
              </Link>
            </div>
          </div>
        </div>

        <div className="pt-8 border-t border-campo-800 flex flex-col sm:flex-row items-center justify-between text-xs text-campo-300 gap-4">
          <p>
            &copy; {new Date().getFullYear()} Asociación de Ganaderos. Todos los derechos reservados.
          </p>
          <p className="text-campo-400">
            Desarrollado para el campo colombiano • Simple, rápido y seguro
          </p>
        </div>
      </div>
    </footer>
  );
}

