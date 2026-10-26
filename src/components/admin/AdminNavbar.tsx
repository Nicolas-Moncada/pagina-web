"use client";

import { useState } from "react";
import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import {
  LayoutDashboard,
  Milk,
  Users,
  Calendar,
  MessageSquare,
  FileSpreadsheet,
  LogOut,
  Menu,
  X,
  Shield,
} from "lucide-react";

interface AdminNavbarProps {
  nombreAdmin: string;
}

export default function AdminNavbar({ nombreAdmin }: AdminNavbarProps) {
  const pathname = usePathname();
  const router = useRouter();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
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

  const navItems = [
    { label: "Resumen", href: "/admin", icon: LayoutDashboard },
    { label: "Entregas", href: "/admin/entregas", icon: Milk },
    { label: "Ganaderos", href: "/admin/ganaderos", icon: Users },
    { label: "Quincenas y Precios", href: "/admin/quincenas", icon: Calendar },
    { label: "Consultas Web", href: "/admin/consultas", icon: MessageSquare },
    { label: "Importar Excel", href: "/admin/importar", icon: FileSpreadsheet },
  ];

  return (
    <header className="bg-campo-950 text-white shadow-lg sticky top-0 z-50 border-b border-campo-800">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-20">
          {/* Logo y título */}
          <Link href="/admin" className="flex items-center gap-3">
            <div className="w-11 h-11 rounded-xl bg-campo-700 text-white flex items-center justify-center shadow-md">
              <Shield className="w-6 h-6 text-dorado-400" />
            </div>
            <div>
              <span className="block text-lg font-bold tracking-tight text-white">
                Administración
              </span>
              <span className="block text-xs text-campo-300">
                Asociación de Ganaderos
              </span>
            </div>
          </Link>

          {/* Menú Desktop */}
          <nav className="hidden lg:flex items-center gap-1">
            {navItems.map((item) => {
              const Icon = item.icon;
              const isActive = pathname === item.href;
              return (
                <Link
                  key={item.href}
                  href={item.href}
                  className={`px-3 py-2 rounded-xl text-sm font-semibold flex items-center gap-2 transition-all ${
                    isActive
                      ? "bg-campo-800 text-white shadow-inner border border-campo-700"
                      : "text-campo-200 hover:text-white hover:bg-campo-900"
                  }`}
                >
                  <Icon className={`w-4 h-4 ${isActive ? "text-dorado-400" : "text-campo-300"}`} />
                  <span>{item.label}</span>
                </Link>
              );
            })}
          </nav>

          {/* Usuario y Logout Desktop */}
          <div className="hidden lg:flex items-center gap-4">
            <div className="text-right">
              <p className="text-xs text-campo-300">Conectado como</p>
              <p className="text-sm font-bold text-white truncate max-w-[140px]">
                {nombreAdmin}
              </p>
            </div>
            <button
              onClick={handleLogout}
              disabled={loggingOut}
              className="btn-touch bg-red-700 hover:bg-red-800 text-white text-sm font-bold shadow px-4 py-2 rounded-xl flex items-center gap-2 border border-red-500"
              title="Cerrar sesión"
            >
              <LogOut className="w-4 h-4" />
              <span>{loggingOut ? "..." : "Salir"}</span>
            </button>
          </div>

          {/* Botón Menú Móvil */}
          <div className="flex lg:hidden items-center gap-2">
            <button
              onClick={handleLogout}
              className="p-2.5 rounded-lg bg-red-700 text-white text-xs font-bold"
              title="Cerrar sesión"
            >
              <LogOut className="w-4 h-4" />
            </button>
            <button
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
              className="p-2.5 rounded-lg text-campo-200 hover:text-white hover:bg-campo-900 focus:outline-none"
            >
              {mobileMenuOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
            </button>
          </div>
        </div>
      </div>

      {/* Menú Desplegable Móvil */}
      {mobileMenuOpen && (
        <div className="lg:hidden border-t border-campo-800 bg-campo-950 px-4 pt-3 pb-6 space-y-2">
          <div className="px-3 py-2 text-xs text-campo-300 border-b border-campo-800/80 mb-2">
            Administrador: <span className="font-bold text-white">{nombreAdmin}</span>
          </div>
          {navItems.map((item) => {
            const Icon = item.icon;
            const isActive = pathname === item.href;
            return (
              <Link
                key={item.href}
                href={item.href}
                onClick={() => setMobileMenuOpen(false)}
                className={`w-full px-4 py-3 rounded-xl text-base font-semibold flex items-center gap-3 ${
                  isActive
                    ? "bg-campo-800 text-white border border-campo-700"
                    : "text-campo-200 hover:bg-campo-900 hover:text-white"
                }`}
              >
                <Icon className={`w-5 h-5 ${isActive ? "text-dorado-400" : "text-campo-300"}`} />
                <span>{item.label}</span>
              </Link>
            );
          })}
        </div>
      )}
    </header>
  );
}

