"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { LogOut, User, Milk } from "lucide-react";

interface GanaderoHeaderProps {
  nombre: string;
  cedula: string;
}

export default function GanaderoHeader({ nombre, cedula }: GanaderoHeaderProps) {
  const router = useRouter();
  const [loggingOut, setLoggingOut] = useState(false);

  const handleLogout = async () => {
    setLoggingOut(true);
    try {
      await fetch("/api/auth/logout", { method: "POST" });
      router.push("/login");
      router.refresh();
    } catch {
      router.push("/login");
    }
  };

  return (
    <header className="bg-campo-800 text-white shadow-md border-b-4 border-campo-600 sticky top-0 z-40">
      <div className="max-w-5xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row items-center justify-between gap-4">
        {/* Identidad del Ganadero */}
        <div className="flex items-center gap-3 w-full sm:w-auto">
          <div className="w-12 h-12 rounded-full bg-campo-700 border-2 border-campo-500 flex items-center justify-center flex-shrink-0 text-white shadow-inner">
            <User className="w-7 h-7 text-campo-100" />
          </div>
          <div>
            <div className="flex items-center gap-2">
              <span className="text-xs uppercase font-bold tracking-wider text-campo-200 bg-campo-900/60 px-2 py-0.5 rounded">
                Ganadero Asociado
              </span>
            </div>
            <h1 className="text-xl sm:text-2xl font-bold tracking-tight text-white leading-tight">
              {nombre}
            </h1>
            <p className="text-xs text-campo-200">
              Cédula: <span className="font-semibold text-white">{cedula}</span>
            </p>
          </div>
        </div>

        {/* Botón de Cerrar Sesión visible en todo momento */}
        <div className="w-full sm:w-auto flex justify-end">
          <button
            onClick={handleLogout}
            disabled={loggingOut}
            className="w-full sm:w-auto btn-touch bg-red-700 hover:bg-red-800 active:bg-red-900 text-white text-base font-bold shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 border border-red-500"
            title="Cerrar sesión de forma segura"
          >
            <LogOut className="w-5 h-5 flex-shrink-0" />
            <span>{loggingOut ? "Saliendo..." : "Cerrar Sesión"}</span>
          </button>
        </div>
      </div>
    </header>
  );
}

