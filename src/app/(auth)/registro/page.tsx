"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { UserPlus, ArrowLeft, ShieldCheck, Eye, EyeOff, AlertCircle, CheckCircle2, User, Phone, KeyRound } from "lucide-react";

export default function RegistroPage() {
  const router = useRouter();

  const [cedula, setCedula] = useState("");
  const [nombre, setNombre] = useState("");
  const [telefono, setTelefono] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");

  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);

  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    const cedulaLimpia = cedula.trim().replace(/\D/g, "");
    const nombreLimpio = nombre.trim();
    const telefonoLimpio = telefono.trim();

    if (!cedulaLimpia) {
      setError("Por favor ingresa tu número de cédula (solo números)");
      return;
    }

    if (!nombreLimpio) {
      setError("Por favor ingresa tu nombre completo");
      return;
    }

    if (password.length < 4) {
      setError("La contraseña debe tener al menos 4 caracteres");
      return;
    }

    if (password !== confirmPassword) {
      setError("Las contraseñas no coinciden. Verifícalas con cuidado.");
      return;
    }

    setLoading(true);

    try {
      const res = await fetch("/api/auth/register", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          cedula: cedulaLimpia,
          nombre: nombreLimpio,
          telefono: telefonoLimpio || undefined,
          password,
          confirmPassword,
        }),
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data.error || "Ocurrió un error al procesar tu registro.");
        setLoading(false);
        return;
      }

      // Redirigir al login con parámetro para mostrar confirmación y cédula precargada
      router.push(`/login?registrado=true&cedula=${encodeURIComponent(cedulaLimpia)}`);
    } catch {
      setError("No se pudo conectar con el servidor. Verifica tu conexión a internet.");
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex flex-col justify-center items-center px-4 py-8 bg-gradient-to-b from-campo-50 via-leche-100 to-campo-100">
      {/* Botón Volver */}
      <div className="w-full max-w-lg mb-4 flex items-center justify-between">
        <Link
          href="/"
          className="inline-flex items-center gap-2 text-campo-700 hover:text-campo-900 font-medium text-base p-2 rounded-lg hover:bg-campo-100 transition-colors"
        >
          <ArrowLeft className="w-5 h-5" />
          <span>Volver al inicio</span>
        </Link>
        <Link
          href="/login"
          className="text-sm font-bold text-campo-800 underline hover:text-campo-950"
        >
          ¿Ya tienes cuenta? Ingresar
        </Link>
      </div>

      <div className="w-full max-w-lg bg-white rounded-3xl shadow-xl border border-campo-200 overflow-hidden">
        {/* Cabecera */}
        <div className="bg-campo-800 text-white p-6 sm:p-8 text-center">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-campo-900 text-campo-200 mb-3 shadow-inner">
            <UserPlus className="w-9 h-9" />
          </div>
          <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight">
            Registro de Ganadero Asociado
          </h1>
          <p className="text-campo-200 text-sm mt-1 max-w-md mx-auto">
            Crea tu cuenta por primera vez para consultar diariamente tus entregas de leche y liquidaciones quincenales.
          </p>
        </div>

        {/* Formulario */}
        <form onSubmit={handleSubmit} className="p-6 sm:p-8 space-y-5">
          {error && (
            <div
              role="alert"
              className="flex items-start gap-3 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-800"
            >
              <AlertCircle className="w-6 h-6 flex-shrink-0 text-red-500 mt-0.5" />
              <div className="text-base font-semibold">{error}</div>
            </div>
          )}

          {/* Cédula */}
          <div>
            <label htmlFor="cedula" className="block text-base font-bold text-campo-950 mb-1.5">
              Número de Cédula de Ciudadanía <span className="text-red-500">*</span>
            </label>
            <div className="relative">
              <input
                id="cedula"
                name="cedula"
                type="text"
                inputMode="numeric"
                required
                value={cedula}
                onChange={(e) => setCedula(e.target.value)}
                placeholder="Ej: 1098765432"
                className="w-full px-4 py-3.5 text-lg border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white text-gray-900 placeholder-gray-400"
              />
            </div>
            <p className="text-xs text-gray-500 mt-1">
              Digita solo números, sin puntos ni guiones. Esta cédula será tu usuario de acceso.
            </p>
          </div>

          {/* Nombre Completo */}
          <div>
            <label htmlFor="nombre" className="block text-base font-bold text-campo-950 mb-1.5">
              Nombre Completo <span className="text-red-500">*</span>
            </label>
            <input
              id="nombre"
              name="nombre"
              type="text"
              required
              value={nombre}
              onChange={(e) => setNombre(e.target.value)}
              placeholder="Ej: Don Carlos Alberto Mendoza"
              className="w-full px-4 py-3.5 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white text-gray-900 placeholder-gray-400"
            />
          </div>

          {/* Teléfono */}
          <div>
            <label htmlFor="telefono" className="block text-base font-bold text-campo-950 mb-1.5">
              Teléfono Celular de Contacto
            </label>
            <input
              id="telefono"
              name="telefono"
              type="tel"
              inputMode="tel"
              value={telefono}
              onChange={(e) => setTelefono(e.target.value)}
              placeholder="Ej: 310 123 4567"
              className="w-full px-4 py-3.5 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white text-gray-900 placeholder-gray-400"
            />
            <p className="text-xs text-gray-500 mt-1">
              Opcional pero recomendado para avisos de pagos y novedades.
            </p>
          </div>

          {/* Contraseña */}
          <div>
            <label htmlFor="password" className="block text-base font-bold text-campo-950 mb-1.5">
              Crea tu Contraseña <span className="text-red-500">*</span>
            </label>
            <div className="relative">
              <input
                id="password"
                name="password"
                type={showPassword ? "text" : "password"}
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Mínimo 4 caracteres (ej: tu clave secreta)"
                className="w-full px-4 py-3.5 pr-14 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white text-gray-900 placeholder-gray-400"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-2 top-1/2 -translate-y-1/2 p-2.5 text-gray-500 hover:text-campo-800 rounded-lg"
                title={showPassword ? "Ocultar" : "Mostrar"}
              >
                {showPassword ? <EyeOff className="w-6 h-6" /> : <Eye className="w-6 h-6" />}
              </button>
            </div>
          </div>

          {/* Confirmar Contraseña */}
          <div>
            <label htmlFor="confirmPassword" className="block text-base font-bold text-campo-950 mb-1.5">
              Confirma tu Contraseña <span className="text-red-500">*</span>
            </label>
            <div className="relative">
              <input
                id="confirmPassword"
                name="confirmPassword"
                type={showConfirmPassword ? "text" : "password"}
                required
                value={confirmPassword}
                onChange={(e) => setConfirmPassword(e.target.value)}
                placeholder="Vuelve a escribir la misma contraseña"
                className="w-full px-4 py-3.5 pr-14 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white text-gray-900 placeholder-gray-400"
              />
              <button
                type="button"
                onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                className="absolute right-2 top-1/2 -translate-y-1/2 p-2.5 text-gray-500 hover:text-campo-800 rounded-lg"
                title={showConfirmPassword ? "Ocultar" : "Mostrar"}
              >
                {showConfirmPassword ? <EyeOff className="w-6 h-6" /> : <Eye className="w-6 h-6" />}
              </button>
            </div>
          </div>

          {/* Botón Enviar */}
          <button
            type="submit"
            disabled={loading}
            className="w-full btn-touch bg-campo-700 hover:bg-campo-800 text-white shadow-lg text-lg font-bold flex items-center justify-center gap-2 mt-2"
          >
            {loading ? (
              <span>Registrando tus datos...</span>
            ) : (
              <>
                <UserPlus className="w-5 h-5 text-campo-200" />
                <span>Crear mi Cuenta de Ganadero</span>
              </>
            )}
          </button>

          {/* Enlace al Login */}
          <div className="text-center pt-4 border-t border-gray-100">
            <p className="text-sm text-gray-600">
              ¿Ya te habías registrado anteriormente?
            </p>
            <Link
              href="/login"
              className="inline-block mt-1 text-base font-bold text-campo-700 hover:text-campo-900 underline"
            >
              Ir a Iniciar Sesión con mi Cédula
            </Link>
          </div>
        </form>
      </div>

      <div className="mt-6 text-center text-xs text-gray-500">
        Plataforma Segura de Control Lechero &copy; {new Date().getFullYear()}
      </div>
    </div>
  );
}
