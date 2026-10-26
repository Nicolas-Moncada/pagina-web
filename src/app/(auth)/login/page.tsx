"use client";

import { useState, useEffect, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import Link from "next/link";
import { LogIn, ArrowLeft, ShieldCheck, Eye, EyeOff, AlertCircle, CheckCircle2, UserPlus } from "lucide-react";

function LoginForm() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [cedula, setCedula] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    // Si viene redirigido después de registrarse
    if (searchParams.get("registrado") === "true") {
      setSuccess("✅ ¡Tu cuenta ha sido creada exitosamente! Por favor escribe tu cédula y tu contraseña para entrar a tu panel.");
    }
    const cedulaParam = searchParams.get("cedula");
    if (cedulaParam) {
      setCedula(cedulaParam);
    }
  }, [searchParams]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    const cedulaLimpia = cedula.trim().replace(/\D/g, "");
    if (!cedulaLimpia) {
      setError("Por favor ingresa tu número de cédula (solo números)");
      return;
    }

    if (!password) {
      setError("Por favor ingresa tu contraseña");
      return;
    }

    setLoading(true);

    try {
      const res = await fetch("/api/auth/login", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ cedula: cedulaLimpia, password }),
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data.error || "Cédula o contraseña incorrecta");
        setLoading(false);
        return;
      }

      // Redirección inmediata según rol
      router.push(data.redirectUrl || "/panel");
      router.refresh();
    } catch {
      setError("No se pudo conectar con el servidor. Verifica tu conexión.");
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex flex-col justify-center items-center px-4 py-8 bg-gradient-to-b from-campo-50 via-leche-100 to-campo-100">
      {/* Botón Volver al Inicio */}
      <div className="w-full max-w-md mb-4 flex items-center justify-between">
        <Link
          href="/"
          className="inline-flex items-center gap-2 text-campo-700 hover:text-campo-900 font-medium text-base p-2 rounded-lg hover:bg-campo-100 transition-colors"
        >
          <ArrowLeft className="w-5 h-5" />
          <span>Volver al inicio</span>
        </Link>
        <Link
          href="/registro"
          className="text-sm font-bold text-campo-800 underline hover:text-campo-950"
        >
          Crear cuenta nueva
        </Link>
      </div>

      <div className="w-full max-w-md bg-white rounded-3xl shadow-xl border border-campo-200 overflow-hidden">
        {/* Cabecera del formulario */}
        <div className="bg-campo-700 text-white p-6 sm:p-7 text-center">
          <div className="inline-flex items-center justify-center w-16 h-16 rounded-full bg-campo-800 text-campo-100 mb-3 shadow-inner">
            <ShieldCheck className="w-9 h-9 text-campo-200" />
          </div>
          <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight">Acceso a Asociados</h1>
          <p className="text-campo-100 text-sm mt-1">
            Ingresa tu cédula y contraseña para ver tus entregas y liquidaciones
          </p>
        </div>

        {/* Formulario */}
        <form onSubmit={handleSubmit} className="p-6 sm:p-8 space-y-6">
          {success && (
            <div
              role="alert"
              className="flex items-start gap-3 p-4 bg-green-50 border-l-4 border-campo-600 rounded-r-lg text-campo-900 shadow-sm"
            >
              <CheckCircle2 className="w-6 h-6 flex-shrink-0 text-campo-600 mt-0.5" />
              <div className="text-base font-semibold leading-snug">{success}</div>
            </div>
          )}

          {error && (
            <div
              role="alert"
              className="flex items-start gap-3 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-800"
            >
              <AlertCircle className="w-6 h-6 flex-shrink-0 text-red-500 mt-0.5" />
              <div className="text-base font-semibold">{error}</div>
            </div>
          )}

          <div>
            <label
              htmlFor="cedula"
              className="block text-base font-bold text-campo-900 mb-2"
            >
              Número de Cédula
            </label>
            <input
              id="cedula"
              name="cedula"
              type="text"
              inputMode="numeric"
              autoComplete="username"
              required
              value={cedula}
              onChange={(e) => setCedula(e.target.value)}
              placeholder="Ej: 1098765432"
              className="w-full px-4 py-3.5 text-lg border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 transition-all text-gray-900 placeholder-gray-400"
            />
            <p className="text-xs text-gray-500 mt-1">
              Digita tu número de identificación sin puntos ni guiones
            </p>
          </div>

          <div>
            <label
              htmlFor="password"
              className="block text-base font-bold text-campo-900 mb-2"
            >
              Contraseña
            </label>
            <div className="relative">
              <input
                id="password"
                name="password"
                type={showPassword ? "text" : "password"}
                autoComplete="current-password"
                required
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="Ingresa tu clave secreta"
                className="w-full px-4 py-3.5 pr-14 text-lg border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 transition-all text-gray-900 placeholder-gray-400"
              />
              <button
                type="button"
                onClick={() => setShowPassword(!showPassword)}
                className="absolute right-2 top-1/2 -translate-y-1/2 p-2.5 text-gray-500 hover:text-campo-800 rounded-lg"
                title={showPassword ? "Ocultar contraseña" : "Ver contraseña"}
                aria-label={showPassword ? "Ocultar contraseña" : "Ver contraseña"}
              >
                {showPassword ? <EyeOff className="w-6 h-6" /> : <Eye className="w-6 h-6" />}
              </button>
            </div>
          </div>

          <button
            type="submit"
            disabled={loading}
            className="w-full btn-touch bg-campo-700 hover:bg-campo-800 active:bg-campo-900 text-white shadow-md hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed text-lg font-bold flex items-center justify-center gap-2"
          >
            {loading ? (
              <span>Verificando datos...</span>
            ) : (
              <>
                <LogIn className="w-5 h-5 text-campo-200" />
                <span>Ingresar al Sistema</span>
              </>
            )}
          </button>

          {/* Zona destacada: ¿Nunca has accedido? */}
          <div className="pt-4 border-t border-gray-100 bg-campo-50/60 p-4 rounded-2xl border border-campo-200 text-center space-y-2">
            <p className="text-sm font-bold text-campo-950">
              ¿Es tu primera vez aquí o nunca has accedido?
            </p>
            <p className="text-xs text-gray-600">
              Crea tu cuenta de ganadero ingresando tus datos personales en 1 minuto.
            </p>
            <Link
              href="/registro"
              className="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl bg-white hover:bg-campo-100 text-campo-800 border-2 border-campo-300 font-bold text-sm shadow-sm transition-all"
            >
              <UserPlus className="w-4 h-4 text-campo-700" />
              <span>Registrarme como Ganadero Asociado</span>
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

export default function LoginPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen flex items-center justify-center bg-leche-100 text-campo-800 font-bold">
        Cargando acceso...
      </div>
    }>
      <LoginForm />
    </Suspense>
  );
}
