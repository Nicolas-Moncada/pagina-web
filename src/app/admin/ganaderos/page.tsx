"use client";

import { useState, useEffect } from "react";
import { Users, UserPlus, Edit, Power, CheckCircle2, AlertCircle, X, Shield, Phone, CreditCard } from "lucide-react";

interface Ganadero {
  id: string;
  cedula: string;
  nombre: string;
  telefono: string | null;
  activo: boolean;
  creadoEn: string;
  _count?: { entregas: number };
}

export default function AdminGanaderosPage() {
  const [ganaderos, setGanaderos] = useState<Ganadero[]>([]);
  const [loading, setLoading] = useState(true);

  // Estado del Modal (crear o editar)
  const [modalOpen, setModalOpen] = useState(false);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [cedula, setCedula] = useState("");
  const [nombre, setNombre] = useState("");
  const [telefono, setTelefono] = useState("");
  const [password, setPassword] = useState("");
  const [activo, setActivo] = useState(true);

  const [saving, setSaving] = useState(false);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const cargarGanaderos = () => {
    setLoading(true);
    fetch("/api/admin/ganaderos")
      .then((r) => r.json())
      .then((data) => {
        if (data.ganaderos) setGanaderos(data.ganaderos);
      })
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    cargarGanaderos();
  }, []);

  const abrirCrear = () => {
    setEditingId(null);
    setCedula("");
    setNombre("");
    setTelefono("");
    setPassword("");
    setActivo(true);
    setErrorMsg(null);
    setModalOpen(true);
  };

  const abrirEditar = (g: Ganadero) => {
    setEditingId(g.id);
    setCedula(g.cedula);
    setNombre(g.nombre);
    setTelefono(g.telefono || "");
    setPassword("");
    setActivo(g.activo);
    setErrorMsg(null);
    setModalOpen(true);
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSuccessMsg(null);
    setErrorMsg(null);
    setSaving(true);

    const url = editingId ? `/api/admin/ganaderos/${editingId}` : "/api/admin/ganaderos";
    const method = editingId ? "PUT" : "POST";

    try {
      const res = await fetch(url, {
        method,
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          cedula: cedula.trim(),
          nombre: nombre.trim(),
          telefono: telefono.trim() || null,
          password: password.trim() || undefined,
          activo,
        }),
      });

      const data = await res.json();

      if (!res.ok) {
        setErrorMsg(data.error || "Error al procesar ganadero");
        setSaving(false);
        return;
      }

      setSuccessMsg(editingId ? "✅ Ganadero actualizado correctamente" : "✅ Nuevo ganadero creado exitosamente");
      setModalOpen(false);
      cargarGanaderos();
      setSaving(false);
    } catch {
      setErrorMsg("Error de conexión al guardar ganadero");
      setSaving(false);
    }
  };

  const handleToggleEstado = async (id: string, activoActual: boolean) => {
    const accion = activoActual ? "desactivar" : "activar";
    if (!confirm(`¿Estás seguro de que deseas ${accion} a este ganadero?`)) return;

    try {
      const res = await fetch(`/api/admin/ganaderos/${id}`, { method: "DELETE" });
      const data = await res.json();
      if (res.ok) {
        setSuccessMsg(data.mensaje || `Ganadero ${accion}do exitosamente`);
        cargarGanaderos();
      } else {
        alert(data.error || "No se pudo cambiar el estado");
      }
    } catch {
      alert("Error de conexión");
    }
  };

  return (
    <div className="space-y-8">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-campo-950">
            Directorio de Ganaderos Asociados
          </h1>
          <p className="text-gray-600 text-sm mt-0.5">
            Administra los usuarios registrados para el ingreso al sistema y registro de entregas.
          </p>
        </div>

        <button
          onClick={abrirCrear}
          className="btn-touch bg-campo-700 hover:bg-campo-800 text-white font-bold text-sm shadow px-5 py-2.5 rounded-xl flex items-center gap-2"
        >
          <UserPlus className="w-5 h-5" />
          <span>Registrar Nuevo Ganadero</span>
        </button>
      </div>

      {successMsg && (
        <div className="p-4 bg-green-50 border-l-4 border-campo-600 rounded-r-xl text-campo-900 flex items-start gap-3">
          <CheckCircle2 className="w-6 h-6 text-campo-600 flex-shrink-0 mt-0.5" />
          <div className="font-semibold text-base">{successMsg}</div>
        </div>
      )}

      {/* Tabla de Ganaderos */}
      <div className="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm text-gray-700">
            <thead className="bg-gray-100 text-xs uppercase font-bold text-gray-600 border-b border-gray-200">
              <tr>
                <th className="px-6 py-4">Ganadero</th>
                <th className="px-6 py-4">Cédula</th>
                <th className="px-6 py-4">Teléfono</th>
                <th className="px-6 py-4 text-center">Entregas</th>
                <th className="px-6 py-4 text-center">Estado</th>
                <th className="px-6 py-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {loading ? (
                <tr>
                  <td colSpan={6} className="px-6 py-8 text-center text-gray-500">
                    Cargando ganaderos...
                  </td>
                </tr>
              ) : ganaderos.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-8 text-center text-gray-500">
                    No hay ganaderos registrados aún. Haz clic en "Registrar Nuevo Ganadero".
                  </td>
                </tr>
              ) : (
                ganaderos.map((g) => (
                  <tr key={g.id} className="hover:bg-campo-50/40 transition-colors">
                    <td className="px-6 py-4 font-bold text-gray-950 text-base">
                      {g.nombre}
                    </td>
                    <td className="px-6 py-4 font-mono font-medium text-gray-600">
                      {g.cedula}
                    </td>
                    <td className="px-6 py-4 text-gray-600">
                      {g.telefono || "Sin registrar"}
                    </td>
                    <td className="px-6 py-4 text-center">
                      <span className="font-bold text-campo-800 bg-campo-100 px-2.5 py-1 rounded-md text-xs">
                        {g._count?.entregas || 0} registradas
                      </span>
                    </td>
                    <td className="px-6 py-4 text-center">
                      {g.activo ? (
                        <span className="inline-flex items-center gap-1 text-xs font-bold text-green-700 bg-green-100 px-2.5 py-1 rounded-full">
                          Activo
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-xs font-bold text-red-700 bg-red-100 px-2.5 py-1 rounded-full">
                          Inactivo
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-center">
                      <div className="flex items-center justify-center gap-2">
                        <button
                          onClick={() => abrirEditar(g)}
                          className="p-2 text-campo-700 hover:text-campo-900 hover:bg-campo-100 rounded-lg transition-colors"
                          title="Editar información"
                        >
                          <Edit className="w-4 h-4" />
                        </button>
                        <button
                          onClick={() => handleToggleEstado(g.id, g.activo)}
                          className={`p-2 rounded-lg transition-colors ${
                            g.activo
                              ? "text-red-600 hover:text-red-800 hover:bg-red-50"
                              : "text-green-600 hover:text-green-800 hover:bg-green-50"
                          }`}
                          title={g.activo ? "Desactivar ganadero" : "Reactivar ganadero"}
                        >
                          <Power className="w-4 h-4" />
                        </button>
                      </div>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
          </table>
        </div>
      </div>

      {/* Modal Crear / Editar */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-3xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-200">
            <div className="bg-campo-800 text-white p-6 flex items-center justify-between">
              <div className="flex items-center gap-3">
                <Users className="w-6 h-6 text-campo-200" />
                <h2 className="text-xl font-bold">
                  {editingId ? "Editar Ganadero" : "Nuevo Ganadero Asociado"}
                </h2>
              </div>
              <button
                onClick={() => setModalOpen(false)}
                className="p-1 rounded-lg hover:bg-campo-700 text-campo-200 hover:text-white"
              >
                <X className="w-6 h-6" />
              </button>
            </div>

            <form onSubmit={handleSubmit} className="p-6 space-y-4">
              {errorMsg && (
                <div className="p-3 bg-red-50 border-l-4 border-red-500 rounded text-red-800 text-sm">
                  {errorMsg}
                </div>
              )}

              <div>
                <label className="block text-sm font-bold text-gray-700 mb-1">
                  Número de Cédula *
                </label>
                <input
                  type="text"
                  required
                  value={cedula}
                  onChange={(e) => setCedula(e.target.value)}
                  placeholder="Ej: 1098765432"
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100"
                />
                <p className="text-xs text-gray-500 mt-1">
                  La cédula será su usuario para iniciar sesión.
                </p>
              </div>

              <div>
                <label className="block text-sm font-bold text-gray-700 mb-1">
                  Nombre Completo *
                </label>
                <input
                  type="text"
                  required
                  value={nombre}
                  onChange={(e) => setNombre(e.target.value)}
                  placeholder="Ej: Pedro José Gómez"
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100"
                />
              </div>

              <div>
                <label className="block text-sm font-bold text-gray-700 mb-1">
                  Teléfono / Celular
                </label>
                <input
                  type="text"
                  value={telefono}
                  onChange={(e) => setTelefono(e.target.value)}
                  placeholder="Ej: 310 123 4567"
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100"
                />
              </div>

              <div>
                <label className="block text-sm font-bold text-gray-700 mb-1">
                  {editingId ? "Nueva Contraseña (dejar en blanco para no cambiarla)" : "Contraseña de Acceso"}
                </label>
                <input
                  type="text"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder={editingId ? "Conservar la actual" : "Por defecto será su misma cédula"}
                  className="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100"
                />
              </div>

              <div className="flex items-center gap-2 pt-2">
                <input
                  type="checkbox"
                  id="activo-check"
                  checked={activo}
                  onChange={(e) => setActivo(e.target.checked)}
                  className="w-5 h-5 rounded border-gray-300 text-campo-600 focus:ring-campo-500"
                />
                <label htmlFor="activo-check" className="text-sm font-bold text-gray-700">
                  Ganadero Activo (autorizado para entregar leche y consultar)
                </label>
              </div>

              <div className="pt-4 flex items-center justify-end gap-3">
                <button
                  type="button"
                  onClick={() => setModalOpen(false)}
                  className="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-bold hover:bg-gray-50"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={saving}
                  className="btn-touch bg-campo-700 hover:bg-campo-800 text-white font-bold px-6 py-2.5 rounded-xl shadow"
                >
                  {saving ? "Guardando..." : editingId ? "Actualizar Datos" : "Registrar Ganadero"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

