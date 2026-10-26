"use client";

import { useState } from "react";
import Link from "next/link";
import { LogIn, Menu, X, Milk } from "lucide-react";

export default function Navbar() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  return (
    <header className="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-campo-100 shadow-sm">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-20">
          {/* Logo y Nombre */}
          <Link href="/" className="flex items-center gap-3 group">
            <div className="w-12 h-12 rounded-xl bg-campo-700 text-white flex items-center justify-center shadow-md group-hover:bg-campo-800 transition-colors">
              <Milk className="w-7 h-7 text-white" />
            </div>
            <div>
              <span className="block text-xl font-bold tracking-tight text-campo-900 group-hover:text-campo-700 transition-colors">
                Asociación de Ganaderos
              </span>
              <span className="block text-xs font-medium text-campo-600 uppercase tracking-wider">
                Unión y Progreso Lechero
              </span>
            </div>
          </Link>

          {/* Navegación Desktop */}
          <nav className="hidden md:flex items-center gap-8">
            <a
              href="#nosotros"
              className="text-base font-medium text-gray-700 hover:text-campo-700 transition-colors py-2"
            >
              ¿Quiénes Somos?
            </a>
            <a
              href="#mision-vision"
              className="text-base font-medium text-gray-700 hover:text-campo-700 transition-colors py-2"
            >
              Misión y Visión
            </a>
            <a
              href="#galeria"
              className="text-base font-medium text-gray-700 hover:text-campo-700 transition-colors py-2"
            >
              Nuestra Labor
            </a>
            <a
              href="#contacto"
              className="text-base font-medium text-gray-700 hover:text-campo-700 transition-colors py-2"
            >
              Consultas
            </a>
          </nav>

          {/* Botones de Acceso Desktop */}
          <div className="hidden md:flex items-center gap-3">
            <Link
              href="/registro"
              className="text-base font-bold text-campo-800 hover:text-campo-950 px-4 py-2 rounded-xl hover:bg-campo-50 transition-all border border-campo-200"
            >
              Registrarse
            </Link>
            <Link
              href="/login"
              className="btn-touch bg-campo-700 hover:bg-campo-800 text-white shadow hover:shadow-md transition-all gap-2"
            >
              <LogIn className="w-5 h-5 text-campo-200" />
              <span>Iniciar Sesión</span>
            </Link>
          </div>

          {/* Botón Menú Móvil */}
          <div className="flex md:hidden items-center gap-2">
            <Link
              href="/login"
              className="p-2.5 rounded-lg bg-campo-700 text-white text-sm font-semibold flex items-center gap-1.5"
            >
              <LogIn className="w-4 h-4" />
              <span>Ingresar</span>
            </Link>
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="p-2.5 rounded-lg text-gray-700 hover:bg-campo-50 hover:text-campo-800 focus:outline-none"
              aria-label="Abrir menú"
            >
              {mobileMenuOpen ? <X className="w-7 h-7" /> : <Menu className="w-7 h-7" />}
            </button>
          </div>
        </div>
      </div>

      {/* Menú desplegable Móvil */}
      {mobileMenuOpen && (
        <div className="md:hidden border-t border-campo-100 bg-white px-4 pt-3 pb-6 space-y-3 shadow-lg">
          <a
            href="#nosotros"
            onClick={() => setMobileMenuOpen(false)}
            className="block px-3 py-3 rounded-lg text-base font-semibold text-gray-800 hover:bg-campo-50 hover:text-campo-800"
          >
            ¿Quiénes Somos?
          </a>
          <a
            href="#mision-vision"
            onClick={() => setMobileMenuOpen(false)}
            className="block px-3 py-3 rounded-lg text-base font-semibold text-gray-800 hover:bg-campo-50 hover:text-campo-800"
          >
            Misión y Visión
          </a>
          <a
            href="#galeria"
            onClick={() => setMobileMenuOpen(false)}
            className="block px-3 py-3 rounded-lg text-base font-semibold text-gray-800 hover:bg-campo-50 hover:text-campo-800"
          >
            Nuestra Labor y Fotos
          </a>
          <a
            href="#contacto"
            onClick={() => setMobileMenuOpen(false)}
            className="block px-3 py-3 rounded-lg text-base font-semibold text-gray-800 hover:bg-campo-50 hover:text-campo-800"
          >
            Formulario de Consultas
          </a>
          <div className="pt-2 space-y-2">
            <Link
              href="/registro"
              onClick={() => setMobileMenuOpen(false)}
              className="w-full py-3 px-4 rounded-xl border-2 border-campo-300 text-campo-900 font-bold flex items-center justify-center gap-2 hover:bg-campo-50"
            >
              <span>Registrarse como Asociado</span>
            </Link>
            <Link
              href="/login"
              onClick={() => setMobileMenuOpen(false)}
              className="w-full btn-touch bg-campo-700 text-white flex items-center justify-center gap-2"
            >
              <LogIn className="w-5 h-5" />
              <span>Iniciar Sesión Asociados</span>
            </Link>
          </div>
        </div>
      )}
    </header>
  );
}

