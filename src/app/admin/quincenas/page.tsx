"use client";

import { useState, useEffect } from "react";
import { Calendar, DollarSign, Plus, CheckCircle, AlertCircle, Lock, Unlock, Trash2, Edit } from "lucide-react";
import { formatCurrency } from "@/lib/calculations";
import { getTodayString, getQuincenaForDate, formatDateSpanish } from "@/lib/date-utils";

interface QuincenaItem {
  id: string;
  fechaInicio: string;
  fechaFin: string;
  precioLitro: number | null;
  cerrada: boolean;
  creadaEn: string;
}

export default function AdminQuincenasPage() {
  const [quincenas, setQuincenas] = useState<QuincenaItem[]>([]);
  const [loading, setLoading] = useState(true);

  // Formulario
  const [fechaInicio, setFechaInicio] = useState("");
  const [fechaFin, setFechaFin] = useState("");
  const [precioLitro, setPrecioLitro] = useState("");
  const [cerrada, setCerrada] = useState(false);

  const [saving, setSaving] = useState(false);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const cargarQuincenas = () => {
    setLoading(true);
    fetch("/api/admin/quincenas")
      .then((r) => r.json())
      .then((data) => {
        if (data.quincenas) setQuincenas(data.quincenas);
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    cargarQuincenas();
    // Pre-cargar valores de la quincena actual
    const qActual = getQuincenaForDate();
    setFechaInicio(qActual.fechaInicio);
    setFechaFin(qActual.fechaFin);
  }, []);

  const prellenarQuincenaActual = () => {
    const qActual = getQuincenaForDate();
    setFechaInicio(qActual.fechaInicio);
    setFechaFin(qActual.fechaFin);
    setPrecioLitro("");
    setCerrada(false);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSuccessMsg(null);
    setErrorMsg(null);
    setSaving(true);

    const precioNum = precioLitro.trim() !== "" ? parseFloat(precioLitro.replace(",", ".")) : null;

    try {
      const res = await fetch("/api/admin/quincenas", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          fechaInicio,
          fechaFin,
          precioLitro: precioNum,
          cerrada,
        }),
      });

      const data = await res.json();

      if (!res.ok) {
        setErrorMsg(data.error || "Error al guardar quincena");
        setSaving(false);
        return;
      }

      setSuccessMsg("✅ Quincena y precio guardados correctamente.");
      cargarQuincenas();
      setSaving(false);
    } catch {
      setErrorMsg("Error de conexión");
      setSaving(false);
    }
  };

  const handleToggleCerrar = async (q: QuincenaItem) => {
    try {
      const res = await fetch(`/api/admin/quincenas/${q.id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ cerrada: !q.cerrada }),
      });
      if (res.ok) {
        setSuccessMsg(q.cerrada ? "Quincena reabierta" : "Quincena marcada como cerrada");
        cargarQuincenas();
      }
    } catch {
      alert("Error al cambiar estado");
    }
  };

  const handleEditarQuincena = (q: QuincenaItem) => {
    setFechaInicio(q.fechaInicio);
    setFechaFin(q.fechaFin);
    setPrecioLitro(q.precioLitro !== null ? String(q.precioLitro) : "");
    setCerrada(q.cerrada);
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  const handleEliminar = async (id: string) => {
    if (!confirm("¿Deseas eliminar este registro de quincena?")) return;
    try {
      const res = await fetch(`/api/admin/quincenas/${id}`, { method: "DELETE" });
      if (res.ok) {
        setSuccessMsg("Quincena eliminada");
        cargarQuincenas();
      }
    } catch {
      alert("Error al eliminar");
    }
  };

  return (
    <div className="space-y-8">
      {/* Encabezado */}
      <div>
        <h1 className="text-2xl sm:text-3xl font-extrabold text-campo-950">
          Periodos Quincenales y Precio del Litro
        </h1>
        <p className="text-gray-600 text-sm mt-0.5">
          Define el precio oficial del litro de leche para cada quincena y controla el cierre contable.
        </p>
      </div>

      {/* Formulario */}
      <div className="bg-white rounded-3xl p-6 sm:p-8 shadow-sm border-2 border-campo-200">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6 pb-4 border-b border-gray-100">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-campo-100 text-campo-800 flex items-center justify-center">
              <Calendar className="w-6 h-6" />
            </div>
            <div>
              <h2 className="text-xl font-bold text-gray-900">
                Crear o Fijar Precio de Quincena
              </h2>
              <p className="text-xs text-gray-500">
                Puedes dejar el precio en blanco y fijarlo más adelante cuando se establezca el valor de mercado.
              </p>
            </div>
          </div>

          <button
            type="button"
            onClick={prellenarQuincenaActual}
            className="text-xs font-bold text-campo-700 bg-campo-50 hover:bg-campo-100 px-3 py-2 rounded-xl border border-campo-200"
          >
            Usar Quincena Actual
          </button>
        </div>

        {successMsg && (
          <div className="mb-6 p-4 bg-green-50 border-l-4 border-campo-600 rounded-r-xl text-campo-900 flex items-start gap-3">
            <CheckCircle className="w-6 h-6 text-campo-600 flex-shrink-0 mt-0.5" />
            <div className="font-semibold text-base">{successMsg}</div>
          </div>
        )}

        {errorMsg && (
          <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-900 flex items-start gap-3">
            <AlertCircle className="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" />
            <div className="font-semibold text-base">{errorMsg}</div>
          </div>
        )}

        <form onSubmit={handleSubmit} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <div>
            <label className="block text-sm font-bold text-gray-700 mb-1.5">
              Fecha de Inicio *
            </label>
            <input
              type="date"
              required
              value={fechaInicio}
              onChange={(e) => setFechaInicio(e.target.value)}
              className="w-full px-4 py-3 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white"
            />
          </div>

          <div>
            <label className="block text-sm font-bold text-gray-700 mb-1.5">
              Fecha de Fin *
            </label>
            <input
              type="date"
              required
              value={fechaFin}
              onChange={(e) => setFechaFin(e.target.value)}
              className="w-full px-4 py-3 text-base border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white"
            />
          </div>

          <div>
            <label className="block text-sm font-bold text-gray-700 mb-1.5">
              Precio del Litro ($ COP)
            </label>
            <div className="relative">
              <input
                type="number"
                step="0.01"
                min="0"
                value={precioLitro}
                onChange={(e) => setPrecioLitro(e.target.value)}
                placeholder="Ej: 2150"
                className="w-full px-4 py-3 pr-10 text-base font-bold border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white"
              />
              <span className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-sm">
                $
              </span>
            </div>
            <p className="text-xs text-gray-500 mt-1">Opcional. Vacío = "Precio aún no definido".</p>
          </div>

          <div className="flex flex-col justify-end">
            <div className="flex items-center gap-2 mb-3">
              <input
                type="checkbox"
                id="cerrada-check"
                checked={cerrada}
                onChange={(e) => setCerrada(e.target.checked)}
                className="w-5 h-5 rounded border-gray-300 text-campo-600 focus:ring-campo-500"
              />
              <label htmlFor="cerrada-check" className="text-sm font-bold text-gray-700">
                Marcar como cerrada
              </label>
            </div>
            <button
              type="submit"
              disabled={saving}
              className="w-full btn-touch bg-campo-700 hover:bg-campo-800 text-white font-bold text-base shadow px-6 rounded-xl flex items-center justify-center gap-2"
            >
              <Plus className="w-5 h-5" />
              <span>{saving ? "Guardando..." : "Guardar Quincena"}</span>
            </button>
          </div>
        </form>
      </div>

      {/* Listado de Quincenas */}
      <div className="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm text-gray-700">
            <thead className="bg-gray-100 text-xs uppercase font-bold text-gray-600 border-b border-gray-200">
              <tr>
                <th className="px-6 py-4">Periodo Quincenal</th>
                <th className="px-6 py-4">Rango de Fechas</th>
                <th className="px-6 py-4 text-right">Precio por Litro</th>
                <th className="px-6 py-4 text-center">Estado</th>
                <th className="px-6 py-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan={5} className="px-6 py-8 text-center text-gray-500">
                    Cargando quincenas...
                  </td>
                </tr>
              ) : quincenas.length === 0 ? (
                <tr>
                  <td colSpan={5} className="px-6 py-8 text-center text-gray-500">
                    No se han registrado quincenas todavía. Llena el formulario superior para crear la primera.
                  </td>
                </tr>
              ) : (
                quincenas.map((q) => {
                  const info = getQuincenaForDate(q.fechaInicio);
                  return (
                    <tr key={q.id} className="hover:bg-campo-50/40 transition-colors">
                      <td className="px-6 py-4 font-bold text-gray-950 text-base">
                        {info.nombreLegible}
                      </td>
                      <td className="px-6 py-4 text-gray-600">
                        {formatDateSpanish(q.fechaInicio)} al {formatDateSpanish(q.fechaFin)}
                      </td>
                      <td className="px-6 py-4 text-right font-black text-base">
                        {q.precioLitro !== null ? (
                          <span className="text-campo-900">{formatCurrency(q.precioLitro)} / L</span>
                        ) : (
                          <span className="text-xs font-bold text-amber-800 bg-amber-100 px-2.5 py-1 rounded-md">
                            Precio aún no definido
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-4 text-center">
                        {q.cerrada ? (
                          <span className="inline-flex items-center gap-1 text-xs font-bold text-gray-700 bg-gray-200 px-2.5 py-1 rounded-full">
                            <Lock className="w-3.5 h-3.5" />
                            Cerrada
                          </span>
                        ) : (
                          <span className="inline-flex items-center gap-1 text-xs font-bold text-green-700 bg-green-100 px-2.5 py-1 rounded-full">
                            <Unlock className="w-3.5 h-3.5" />
                            Abierta
                          </span>
                        )}
                      </td>
                      <td className="px-6 py-4 text-center">
                        <div className="flex items-center justify-center gap-2">
                          <button
                            onClick={() => handleEditarQuincena(q)}
                            className="p-2 text-campo-700 hover:text-campo-900 hover:bg-campo-100 rounded-lg transition-colors"
                            title="Editar quincena y precio"
                          >
                            <Edit className="w-4 h-4" />
                          </button>
                          <button
                            onClick={() => handleToggleCerrar(q)}
                            className="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors"
                            title={q.cerrada ? "Reabrir quincena" : "Cerrar quincena"}
                          >
                            {q.cerrada ? <Unlock className="w-4 h-4" /> : <Lock className="w-4 h-4" />}
                          </button>
                          <button
                            onClick={() => handleEliminar(q.id)}
                            className="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors"
                            title="Eliminar registro de quincena"
                          >
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

