"use client";

import { useState, useEffect, useMemo, useRef, Fragment } from "react";
import { useSearchParams } from "next/navigation";
import {
  Milk,
  Plus,
  Search,
  Calendar,
  CheckCircle2,
  AlertCircle,
  Trash2,
  Edit,
  Clock,
  User,
  ChevronLeft,
  ChevronRight,
  Check,
  X,
  RefreshCw,
  FileSpreadsheet,
  ArrowRight,
} from "lucide-react";
import {
  getTodayString,
  formatDateSpanish,
  getQuincenaForDate,
  getQuincenasRecientes,
  generarDiasQuincena,
  QuincenaRango,
  DiaColumna,
} from "@/lib/date-utils";

interface GanaderoOption {
  id: string;
  nombre: string;
  cedula: string;
  activo: boolean;
}

interface EntregaItem {
  id: string;
  usuarioId: string;
  fecha: string;
  turno: "MANANA" | "TARDE";
  litros: number;
  creadoEn: string;
  usuario: {
    id: string;
    nombre: string;
    cedula: string;
  };
}

interface EditingCellState {
  usuarioId: string;
  fecha: string;
  turno: "MANANA" | "TARDE";
  valor: string;
  entregaId?: string;
  nombreGanadero: string;
}

export default function AdminEntregasPage() {
  const searchParams = useSearchParams();
  const preselectedUsuarioId = searchParams.get("usuarioId") || "";

  // 1. Estados de Quincena y Fechas
  const hoyStr = useMemo(() => getTodayString(), []);
  const quincenaActual = useMemo(() => getQuincenaForDate(hoyStr), [hoyStr]);
  const quincenasDisponibles = useMemo(() => getQuincenasRecientes(18), []);

  const [quincenaSeleccionada, setQuincenaSeleccionada] = useState<QuincenaRango>(quincenaActual);
  const diasQuincena: DiaColumna[] = useMemo(
    () => generarDiasQuincena(quincenaSeleccionada.fechaInicio, quincenaSeleccionada.fechaFin),
    [quincenaSeleccionada.fechaInicio, quincenaSeleccionada.fechaFin]
  );

  // 2. Estados de Ganaderos y Entregas
  const [ganaderos, setGanaderos] = useState<GanaderoOption[]>([]);
  const [loadingGanaderos, setLoadingGanaderos] = useState(true);

  // Mapa de entregas para consulta O(1): key = `${usuarioId}_${fecha}_${turno}`
  const [entregasMap, setEntregasMap] = useState<Record<string, { id?: string; litros: number }>>({});
  const [loadingEntregas, setLoadingEntregas] = useState(true);

  // 3. Filtros de búsqueda en la planilla
  const [busquedaGanadero, setBusquedaGanadero] = useState("");

  // 4. Formulario superior de registro rápido
  const [formUsuarioId, setFormUsuarioId] = useState(preselectedUsuarioId);
  const [formFecha, setFormFecha] = useState(hoyStr);
  const [formTurno, setFormTurno] = useState<"MANANA" | "TARDE">("MANANA");
  const [formLitros, setFormLitros] = useState("");
  const [savingForm, setSavingForm] = useState(false);

  // 5. Estado de edición inline en celdas
  const [editingCell, setEditingCell] = useState<EditingCellState | null>(null);
  const [savingCell, setSavingCell] = useState(false);

  // 6. Mensajes de notificación
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Cargar ganaderos al montar
  useEffect(() => {
    setLoadingGanaderos(true);
    fetch("/api/admin/ganaderos")
      .then((r) => r.json())
      .then((data) => {
        if (data.ganaderos) {
          setGanaderos(data.ganaderos);
        }
      })
      .catch((err) => {
        console.error("Error al cargar ganaderos:", err);
        setErrorMsg("No se pudieron cargar los ganaderos.");
      })
      .finally(() => setLoadingGanaderos(false));
  }, []);

  // Cargar entregas de la quincena seleccionada
  const cargarEntregasQuincena = async (q: QuincenaRango) => {
    setLoadingEntregas(true);
    try {
      const res = await fetch(
        `/api/admin/entregas?fechaInicio=${q.fechaInicio}&fechaFin=${q.fechaFin}`
      );
      const data = await res.json();
      if (data.entregas) {
        const nuevoMapa: Record<string, { id?: string; litros: number }> = {};
        data.entregas.forEach((e: EntregaItem) => {
          nuevoMapa[`${e.usuarioId}_${e.fecha}_${e.turno}`] = {
            id: e.id,
            litros: Number(e.litros),
          };
        });
        setEntregasMap(nuevoMapa);
      }
    } catch (err) {
      console.error("Error al cargar entregas de la quincena:", err);
      setErrorMsg("Error de conexión al cargar entregas.");
    } finally {
      setLoadingEntregas(false);
    }
  };

  useEffect(() => {
    cargarEntregasQuincena(quincenaSeleccionada);
  }, [quincenaSeleccionada.fechaInicio, quincenaSeleccionada.fechaFin]);

  // Navegar entre quincenas anterior / siguiente
  const handleCambiarQuincenaRelativa = (direccion: -1 | 1) => {
    const idxActual = quincenasDisponibles.findIndex(
      (q) =>
        q.fechaInicio === quincenaSeleccionada.fechaInicio &&
        q.fechaFin === quincenaSeleccionada.fechaFin
    );

    if (idxActual !== -1) {
      // Las quincenas están ordenadas descendente (más nueva primero)
      const nuevoIdx = idxActual - direccion; // direccion 1 = quincena más nueva (idx menor), -1 = quincena más antigua (idx mayor)
      if (nuevoIdx >= 0 && nuevoIdx < quincenasDisponibles.length) {
        setQuincenaSeleccionada(quincenasDisponibles[nuevoIdx]);
      }
    }
  };

  // Ganaderos filtrados por búsqueda
  const ganaderosFiltrados = useMemo(() => {
    if (!busquedaGanadero.trim()) return ganaderos;
    const term = busquedaGanadero.toLowerCase().trim();
    return ganaderos.filter(
      (g) =>
        g.nombre.toLowerCase().includes(term) ||
        g.cedula.toLowerCase().includes(term)
    );
  }, [ganaderos, busquedaGanadero]);

  // Guardar desde el formulario superior (sincronizado con la planilla)
  const handleSubmitForm = async (e: React.FormEvent) => {
    e.preventDefault();
    setSuccessMsg(null);
    setErrorMsg(null);

    if (!formUsuarioId) {
      setErrorMsg("Por favor selecciona un ganadero");
      return;
    }

    const litrosNum = parseFloat(formLitros.replace(",", "."));
    if (isNaN(litrosNum) || litrosNum <= 0) {
      setErrorMsg("Ingresa una cantidad de litros válida (mayor a 0)");
      return;
    }

    setSavingForm(true);

    try {
      const res = await fetch("/api/admin/entregas", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          usuarioId: formUsuarioId,
          fecha: formFecha,
          turno: formTurno,
          litros: litrosNum,
        }),
      });

      const data = await res.json();

      if (!res.ok) {
        setErrorMsg(data.error || "Error al guardar la entrega");
        setSavingForm(false);
        return;
      }

      // Sincronización inmediata en el mapa local
      const key = `${formUsuarioId}_${formFecha}_${formTurno}`;
      setEntregasMap((prev) => ({
        ...prev,
        [key]: {
          id: data.entrega?.id,
          litros: litrosNum,
        },
      }));

      setSuccessMsg(data.mensaje || "✅ Litros guardados con éxito en la planilla");
      setFormLitros("");

      // Si la fecha registrada no está dentro de la quincena mostrada actualmente, alertar o sugerir
      if (
        formFecha < quincenaSeleccionada.fechaInicio ||
        formFecha > quincenaSeleccionada.fechaFin
      ) {
        setSuccessMsg(
          `✅ Litros guardados para el ${formatDateSpanish(formFecha)}. (Nota: Cambia el selector de periodo arriba para ver esa fecha en la planilla).`
        );
      }
    } catch {
      setErrorMsg("Error de conexión al guardar los litros");
    } finally {
      setSavingForm(false);
    }
  };

  // Guardar celda editada inline
  const handleGuardarCelda = async () => {
    if (!editingCell) return;
    const { usuarioId, fecha, turno, valor, nombreGanadero } = editingCell;

    const litrosNum = parseFloat(valor.replace(",", "."));
    if (isNaN(litrosNum) || litrosNum <= 0) {
      alert("Por favor ingresa una cantidad de litros válida mayor a 0");
      return;
    }

    setSavingCell(true);
    try {
      const res = await fetch("/api/admin/entregas", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          usuarioId,
          fecha,
          turno,
          litros: litrosNum,
        }),
      });

      const data = await res.json();
      if (!res.ok) {
        alert(data.error || "No se pudo guardar la entrega");
        return;
      }

      // Actualizar inmediatamente en el mapa
      const key = `${usuarioId}_${fecha}_${turno}`;
      setEntregasMap((prev) => ({
        ...prev,
        [key]: {
          id: data.entrega?.id,
          litros: litrosNum,
        },
      }));

      setEditingCell(null);
      setSuccessMsg(
        `✅ Actualizado: ${litrosNum.toFixed(1)} L para ${nombreGanadero} (${turno === "MANANA" ? "Mañana" : "Tarde"} del ${formatDateSpanish(fecha)})`
      );
    } catch (err) {
      console.error(err);
      alert("Error de conexión al guardar los litros de la celda");
    } finally {
      setSavingCell(false);
    }
  };

  // Eliminar celda editada inline
  const handleEliminarCelda = async () => {
    if (!editingCell) return;
    const { usuarioId, fecha, turno, entregaId, nombreGanadero } = editingCell;

    if (!confirm(`¿Deseas eliminar el pesaje del turno de ${turno === "MANANA" ? "Mañana" : "Tarde"} para ${nombreGanadero}?`)) {
      return;
    }

    setSavingCell(true);
    try {
      const params = new URLSearchParams();
      if (entregaId) params.set("id", entregaId);
      params.set("usuarioId", usuarioId);
      params.set("fecha", fecha);
      params.set("turno", turno);

      const res = await fetch(`/api/admin/entregas?${params.toString()}`, {
        method: "DELETE",
      });

      if (!res.ok) {
        alert("No se pudo eliminar el registro");
        return;
      }

      // Eliminar inmediatamente del mapa
      const key = `${usuarioId}_${fecha}_${turno}`;
      setEntregasMap((prev) => {
        const copy = { ...prev };
        delete copy[key];
        return copy;
      });

      setEditingCell(null);
      setSuccessMsg(
        `🗑️ Registro eliminado para ${nombreGanadero} (${turno === "MANANA" ? "Mañana" : "Tarde"})`
      );
    } catch (err) {
      console.error(err);
      alert("Error de conexión al eliminar");
    } finally {
      setSavingCell(false);
    }
  };

  // Iniciar edición de una celda
  const handleClickCelda = (
    g: GanaderoOption,
    dia: DiaColumna,
    turno: "MANANA" | "TARDE"
  ) => {
    const key = `${g.id}_${dia.fecha}_${turno}`;
    const entrega = entregasMap[key];
    setEditingCell({
      usuarioId: g.id,
      fecha: dia.fecha,
      turno,
      valor: entrega && entrega.litros > 0 ? String(entrega.litros) : "",
      entregaId: entrega?.id,
      nombreGanadero: g.nombre,
    });
  };

  // Cálculos de totales por ganadero en la quincena
  const calcularTotalGanadero = (gId: string): number => {
    let sum = 0;
    for (const dia of diasQuincena) {
      const manana = entregasMap[`${gId}_${dia.fecha}_MANANA`]?.litros || 0;
      const tarde = entregasMap[`${gId}_${dia.fecha}_TARDE`]?.litros || 0;
      sum += manana + tarde;
    }
    return sum;
  };

  // Cálculos de totales de columna (totales diarios por turno)
  const totalesPorDia = useMemo(() => {
    return diasQuincena.map((dia) => {
      let sumManana = 0;
      let sumTarde = 0;
      for (const g of ganaderosFiltrados) {
        sumManana += entregasMap[`${g.id}_${dia.fecha}_MANANA`]?.litros || 0;
        sumTarde += entregasMap[`${g.id}_${dia.fecha}_TARDE`]?.litros || 0;
      }
      return {
        fecha: dia.fecha,
        manana: sumManana,
        tarde: sumTarde,
        total: sumManana + sumTarde,
      };
    });
  }, [diasQuincena, ganaderosFiltrados, entregasMap]);

  // Gran total de la quincena de todos los ganaderos
  const granTotalQuincena = useMemo(() => {
    return totalesPorDia.reduce((acc, curr) => acc + curr.total, 0);
  }, [totalesPorDia]);

  return (
    <div className="space-y-8">
      {/* Encabezado Principal */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-campo-950 flex items-center gap-3">
            <span>Control y Planilla Quincenal de Entregas</span>
          </h1>
          <p className="text-gray-600 text-sm mt-0.5">
            Planilla matricial interactiva tipo Excel: consulta, registra y edita pesajes por turno en tiempo real.
          </p>
        </div>

        {/* Botón de refresco rápido */}
        <button
          onClick={() => cargarEntregasQuincena(quincenaSeleccionada)}
          disabled={loadingEntregas}
          className="self-start md:self-auto inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-campo-300 bg-white hover:bg-campo-50 text-campo-900 font-bold text-sm shadow-sm transition-colors"
          title="Recargar datos de la quincena"
        >
          <RefreshCw className={`w-4 h-4 ${loadingEntregas ? "animate-spin" : ""}`} />
          <span>{loadingEntregas ? "Actualizando..." : "Actualizar Datos"}</span>
        </button>
      </div>

      {/* Avisos de Notificación */}
      {successMsg && (
        <div className="p-4 bg-green-50 border-l-4 border-campo-600 rounded-r-xl text-campo-900 flex items-start justify-between gap-3 shadow-sm">
          <div className="flex items-start gap-3">
            <CheckCircle2 className="w-5 h-5 text-campo-600 flex-shrink-0 mt-0.5" />
            <div className="font-semibold text-sm">{successMsg}</div>
          </div>
          <button
            onClick={() => setSuccessMsg(null)}
            className="text-campo-700 hover:text-campo-900 text-xs font-bold"
          >
            ✕
          </button>
        </div>
      )}

      {errorMsg && (
        <div className="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-900 flex items-start justify-between gap-3 shadow-sm">
          <div className="flex items-start gap-3">
            <AlertCircle className="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" />
            <div className="font-semibold text-sm">{errorMsg}</div>
          </div>
          <button
            onClick={() => setErrorMsg(null)}
            className="text-red-700 hover:text-red-900 text-xs font-bold"
          >
            ✕
          </button>
        </div>
      )}

      {/* Formulario de Registro Rápido Superior */}
      <div className="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border-2 border-campo-200">
        <div className="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
          <div className="w-10 h-10 rounded-xl bg-campo-100 text-campo-800 flex items-center justify-center">
            <Milk className="w-6 h-6" />
          </div>
          <div>
            <h2 className="text-lg sm:text-xl font-bold text-gray-900">
              Registrar o Modificar Litros en la Planilla
            </h2>
            <p className="text-xs text-gray-500">
              Ingresa pesajes directamente. Se actualizará la celda correspondiente sin duplicar filas.
            </p>
          </div>
        </div>

        <form onSubmit={handleSubmitForm} className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {/* Ganadero */}
          <div className="lg:col-span-1">
            <label className="block text-xs font-bold text-gray-700 mb-1">
              Ganadero Asociado *
            </label>
            <select
              required
              value={formUsuarioId}
              onChange={(e) => setFormUsuarioId(e.target.value)}
              className="w-full px-3 py-2.5 text-sm border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white"
            >
              <option value="">-- Seleccionar Ganadero --</option>
              {ganaderos.map((g) => (
                <option key={g.id} value={g.id}>
                  {g.nombre} (C.C. {g.cedula})
                </option>
              ))}
            </select>
          </div>

          {/* Fecha */}
          <div>
            <label className="block text-xs font-bold text-gray-700 mb-1">
              Fecha de Entrega *
            </label>
            <input
              type="date"
              required
              value={formFecha}
              onChange={(e) => setFormFecha(e.target.value)}
              className="w-full px-3 py-2.5 text-sm border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white"
            />
          </div>

          {/* Turno */}
          <div>
            <label className="block text-xs font-bold text-gray-700 mb-1">
              Turno del Día *
            </label>
            <div className="grid grid-cols-2 gap-2">
              <button
                type="button"
                onClick={() => setFormTurno("MANANA")}
                className={`py-2 px-2 rounded-xl text-xs font-bold border-2 transition-all flex items-center justify-center gap-1 ${
                  formTurno === "MANANA"
                    ? "bg-amber-100 border-amber-500 text-amber-900 shadow-sm"
                    : "bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100"
                }`}
              >
                <span>☀️ Mañana</span>
              </button>
              <button
                type="button"
                onClick={() => setFormTurno("TARDE")}
                className={`py-2 px-2 rounded-xl text-xs font-bold border-2 transition-all flex items-center justify-center gap-1 ${
                  formTurno === "TARDE"
                    ? "bg-orange-100 border-orange-500 text-orange-900 shadow-sm"
                    : "bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100"
                }`}
              >
                <span>🌅 Tarde</span>
              </button>
            </div>
          </div>

          {/* Litros */}
          <div>
            <label className="block text-xs font-bold text-gray-700 mb-1">
              Litros Entregados *
            </label>
            <div className="relative">
              <input
                type="number"
                step="0.1"
                min="0.1"
                required
                value={formLitros}
                onChange={(e) => setFormLitros(e.target.value)}
                placeholder="Ej: 85.5"
                className="w-full px-3 py-2.5 pr-10 text-base font-bold border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white"
              />
              <span className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-xs">
                L
              </span>
            </div>
          </div>

          {/* Botón Guardar */}
          <div className="sm:col-span-2 lg:col-span-4 pt-1 flex justify-end">
            <button
              type="submit"
              disabled={savingForm}
              className="w-full sm:w-auto btn-touch bg-campo-700 hover:bg-campo-800 text-white shadow font-bold text-sm px-6 py-2.5 flex items-center justify-center gap-2 rounded-xl"
            >
              <Plus className="w-4 h-4" />
              <span>{savingForm ? "Guardando en planilla..." : "Guardar en Planilla"}</span>
            </button>
          </div>
        </form>
      </div>

      {/* SECCIÓN PRINCIPAL: PLANILLA MATRICIAL DE EXCEL */}
      <div className="bg-white rounded-3xl shadow-md border-2 border-campo-200 overflow-hidden">
        {/* Barra Superior: Selector de Quincena y Buscador Rápido */}
        <div className="bg-gradient-to-r from-campo-900 via-campo-800 to-tierra-900 text-white p-5 sm:p-6 space-y-4">
          <div className="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <div className="w-11 h-11 rounded-2xl bg-white/10 flex items-center justify-center text-dorado-400 flex-shrink-0">
                <FileSpreadsheet className="w-6 h-6" />
              </div>
              <div>
                <p className="text-xs uppercase font-bold tracking-wider text-campo-200">
                  Planilla Oficial de Pesajes y Liquidación
                </p>
                <h2 className="text-xl sm:text-2xl font-black text-white">
                  {quincenaSeleccionada.nombreLegible}
                </h2>
              </div>
            </div>

            {/* Controles de Selección de Quincena */}
            <div className="w-full lg:w-auto flex flex-wrap items-center gap-2">
              {/* Botón Quincena Anterior */}
              <button
                type="button"
                onClick={() => handleCambiarQuincenaRelativa(-1)}
                className="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs flex items-center gap-1 border border-white/20 transition-colors"
                title="Quincena Anterior"
              >
                <ChevronLeft className="w-4 h-4" />
                <span className="hidden sm:inline">Anterior</span>
              </button>

              {/* Selector desplegable */}
              <select
                value={`${quincenaSeleccionada.fechaInicio}_${quincenaSeleccionada.fechaFin}`}
                onChange={(e) => {
                  const [fIni, fFin] = e.target.value.split("_");
                  const found = quincenasDisponibles.find(
                    (q) => q.fechaInicio === fIni && q.fechaFin === fFin
                  );
                  if (found) setQuincenaSeleccionada(found);
                }}
                className="flex-1 sm:flex-initial px-3 py-2 rounded-xl bg-campo-950 text-white font-bold text-xs sm:text-sm border border-campo-600 focus:outline-none focus:ring-2 focus:ring-dorado-400"
              >
                {quincenasDisponibles.map((q) => (
                  <option
                    key={`${q.fechaInicio}_${q.fechaFin}`}
                    value={`${q.fechaInicio}_${q.fechaFin}`}
                  >
                    {q.nombreLegible} ({formatDateSpanish(q.fechaInicio)} al {formatDateSpanish(q.fechaFin)})
                  </option>
                ))}
              </select>

              {/* Botón Quincena Siguiente */}
              <button
                type="button"
                onClick={() => handleCambiarQuincenaRelativa(1)}
                className="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs flex items-center gap-1 border border-white/20 transition-colors"
                title="Quincena Siguiente"
              >
                <span className="hidden sm:inline">Siguiente</span>
                <ChevronRight className="w-4 h-4" />
              </button>
            </div>
          </div>

          {/* Franja de Fechas de Inicio y Corte + Buscador en vivo */}
          <div className="pt-3 border-t border-campo-700/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-xs sm:text-sm">
            <div className="flex items-center gap-2 text-campo-100">
              <Calendar className="w-4 h-4 text-dorado-400 flex-shrink-0" />
              <span>
                Periodo: Inicio{" "}
                <strong className="text-white underline font-semibold">
                  {formatDateSpanish(quincenaSeleccionada.fechaInicio)}
                </strong>{" "}
                — Corte{" "}
                <strong className="text-white underline font-semibold">
                  {formatDateSpanish(quincenaSeleccionada.fechaFin)}
                </strong>
              </span>
            </div>

            {/* Buscador de Ganadero */}
            <div className="w-full sm:w-72 relative">
              <Search className="w-4 h-4 text-campo-300 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                type="text"
                placeholder="Buscar ganadero o cédula..."
                value={busquedaGanadero}
                onChange={(e) => setBusquedaGanadero(e.target.value)}
                className="w-full pl-9 pr-8 py-1.5 rounded-xl bg-campo-950/80 text-white text-xs border border-campo-600 placeholder-campo-300 focus:outline-none focus:ring-2 focus:ring-dorado-400"
              />
              {busquedaGanadero && (
                <button
                  type="button"
                  onClick={() => setBusquedaGanadero("")}
                  className="absolute right-2.5 top-1/2 -translate-y-1/2 text-campo-300 hover:text-white"
                >
                  <X className="w-3.5 h-3.5" />
                </button>
              )}
            </div>
          </div>
        </div>

        {/* Guía Visual y Leyenda Rápida */}
        <div className="bg-campo-50/70 px-6 py-2.5 border-b border-campo-200 flex flex-wrap items-center justify-between gap-3 text-xs text-campo-900">
          <div className="flex items-center gap-4">
            <span className="font-bold flex items-center gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>
              ☀️ Mañana
            </span>
            <span className="font-bold flex items-center gap-1.5">
              <span className="w-2.5 h-2.5 rounded-full bg-orange-400 inline-block"></span>
              🌅 Tarde
            </span>
            <span className="text-gray-500 hidden sm:inline">|</span>
            <span className="text-gray-600 hidden sm:inline">
              Haz clic en cualquier celda para <strong>ingresar</strong>, <strong>editar</strong> o <strong>eliminar</strong> litros.
            </span>
          </div>
          <div className="text-campo-800 font-bold">
            {ganaderosFiltrados.length} ganadero{ganaderosFiltrados.length !== 1 ? "s" : ""} en planilla
          </div>
        </div>

        {/* CONTENEDOR DE LA TABLA MATRICIAL ESTILO EXCEL */}
        <div className="overflow-x-auto max-h-[70vh] relative">
          <table className="w-full text-left text-xs border-collapse">
            {/* ENCABEZADOS DE DOS NIVELES */}
            <thead className="sticky top-0 z-30 shadow-sm">
              {/* Nivel 1 Superior: Fechas de los días */}
              <tr className="bg-campo-900 text-white uppercase tracking-wider font-extrabold text-[11px]">
                {/* Columna Fija Izquierda: Ganadero */}
                <th
                  rowSpan={2}
                  className="sticky left-0 z-40 bg-campo-950 px-4 py-3 border-r-2 border-campo-700 min-w-[210px] sm:min-w-[250px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.3)] align-middle"
                >
                  <div className="flex items-center gap-2 text-white">
                    <User className="w-4 h-4 text-dorado-400" />
                    <span>GANADERO / CÉDULA</span>
                  </div>
                </th>

                {/* Columnas por cada día de la quincena (colSpan=2) */}
                {diasQuincena.map((dia) => {
                  const esHoy = dia.fecha === hoyStr;
                  return (
                    <th
                      key={dia.fecha}
                      colSpan={2}
                      className={`text-center py-2 px-2 border-r-2 border-campo-700 min-w-[130px] ${
                        esHoy ? "bg-campo-800 ring-2 ring-inset ring-dorado-400" : "bg-campo-900"
                      }`}
                    >
                      <div className="flex flex-col items-center justify-center">
                        <span className="font-black text-white text-xs">
                          {dia.etiquetaLarga}
                        </span>
                        {esHoy && (
                          <span className="text-[9px] font-black uppercase tracking-widest text-dorado-300 bg-black/40 px-2 py-0.5 rounded-full mt-0.5">
                            Hoy
                          </span>
                        )}
                      </div>
                    </th>
                  );
                })}

                {/* Columna Fija Derecha: Total Quincena */}
                <th
                  rowSpan={2}
                  className="sticky right-0 z-40 bg-campo-950 px-4 py-3 border-l-2 border-campo-700 min-w-[130px] text-right shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.3)] align-middle text-dorado-300"
                >
                  TOTAL QUINCENA
                </th>
              </tr>

              {/* Nivel 2 Inferior: Subcolumnas Mañana y Tarde */}
              <tr className="bg-campo-800 text-[10px] font-bold">
                {diasQuincena.map((dia) => (
                  <Fragment key={`sub_${dia.fecha}`}>
                    <th className="py-1 px-1 text-center bg-amber-100 text-amber-950 border-r border-amber-200">
                      ☀️ Mañana
                    </th>
                    <th className="py-1 px-1 text-center bg-orange-100 text-orange-950 border-r-2 border-campo-600">
                      🌅 Tarde
                    </th>
                  </Fragment>
                ))}
              </tr>
            </thead>

            {/* CUERPO DE LA TABLA (1 FILA POR GANADERO) */}
            <tbody className="divide-y divide-gray-200 bg-white">
              {loadingGanaderos || loadingEntregas ? (
                <tr>
                  <td
                    colSpan={diasQuincena.length * 2 + 2}
                    className="py-16 text-center text-gray-500 font-semibold"
                  >
                    <div className="flex flex-col items-center justify-center gap-3">
                      <RefreshCw className="w-8 h-8 text-campo-600 animate-spin" />
                      <span>Cargando planilla de entregas de la quincena...</span>
                    </div>
                  </td>
                </tr>
              ) : ganaderosFiltrados.length === 0 ? (
                <tr>
                  <td
                    colSpan={diasQuincena.length * 2 + 2}
                    className="py-12 text-center text-gray-500"
                  >
                    No se encontraron ganaderos con el criterio de búsqueda.
                  </td>
                </tr>
              ) : (
                ganaderosFiltrados.map((g) => {
                  const totalGanadero = calcularTotalGanadero(g.id);

                  return (
                    <tr
                      key={g.id}
                      className="hover:bg-campo-50/50 transition-colors group"
                    >
                      {/* Columna Fija Izquierda: Nombre y Cédula */}
                      <td className="sticky left-0 z-20 bg-white group-hover:bg-campo-50 px-4 py-3 border-r-2 border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] whitespace-nowrap">
                        <div className="font-bold text-gray-900 text-xs sm:text-sm">
                          {g.nombre}
                        </div>
                        <div className="text-[11px] font-mono text-gray-500 flex items-center gap-1.5">
                          <span>C.C. {g.cedula}</span>
                          {!g.activo && (
                            <span className="text-[9px] bg-red-100 text-red-700 px-1.5 py-0.2 rounded font-bold">
                              Inactivo
                            </span>
                          )}
                        </div>
                      </td>

                      {/* Celdas de cada día (Mañana y Tarde) */}
                      {diasQuincena.map((dia) => {
                        const keyManana = `${g.id}_${dia.fecha}_MANANA`;
                        const keyTarde = `${g.id}_${dia.fecha}_TARDE`;

                        const itemManana = entregasMap[keyManana];
                        const itemTarde = entregasMap[keyTarde];

                        const isEditingManana =
                          editingCell?.usuarioId === g.id &&
                          editingCell?.fecha === dia.fecha &&
                          editingCell?.turno === "MANANA";

                        const isEditingTarde =
                          editingCell?.usuarioId === g.id &&
                          editingCell?.fecha === dia.fecha &&
                          editingCell?.turno === "TARDE";

                        return (
                          <Fragment key={`celdas_${g.id}_${dia.fecha}`}>
                            {/* Celda Mañana */}
                            <td
                              className={`p-1 text-center border-r border-gray-200 transition-colors cursor-pointer select-none ${
                                isEditingManana
                                  ? "bg-amber-100 ring-2 ring-campo-600"
                                  : "bg-amber-50/20 hover:bg-amber-100/60"
                              }`}
                              onClick={() => {
                                if (!isEditingManana) handleClickCelda(g, dia, "MANANA");
                              }}
                              title={`Clic para editar: ${g.nombre} - ${dia.etiquetaLarga} (Mañana)`}
                            >
                              {isEditingManana ? (
                                <div
                                  className="flex items-center justify-center gap-1"
                                  onClick={(e) => e.stopPropagation()}
                                >
                                  <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    autoFocus
                                    disabled={savingCell}
                                    value={editingCell.valor}
                                    onChange={(e) =>
                                      setEditingCell({
                                        ...editingCell,
                                        valor: e.target.value,
                                      })
                                    }
                                    onKeyDown={(e) => {
                                      if (e.key === "Enter") handleGuardarCelda();
                                      if (e.key === "Escape") setEditingCell(null);
                                    }}
                                    className="w-14 px-1 py-1 text-xs font-bold text-center border-2 border-campo-600 rounded bg-white shadow-sm focus:outline-none"
                                  />
                                  <button
                                    type="button"
                                    disabled={savingCell}
                                    onClick={handleGuardarCelda}
                                    className="p-1 bg-campo-700 hover:bg-campo-800 text-white rounded shadow-sm"
                                    title="Guardar (Enter)"
                                  >
                                    <Check className="w-3 h-3" />
                                  </button>
                                  {editingCell.entregaId && (
                                    <button
                                      type="button"
                                      disabled={savingCell}
                                      onClick={handleEliminarCelda}
                                      className="p-1 bg-red-600 hover:bg-red-700 text-white rounded shadow-sm"
                                      title="Eliminar este pesaje"
                                    >
                                      <Trash2 className="w-3 h-3" />
                                    </button>
                                  )}
                                  <button
                                    type="button"
                                    disabled={savingCell}
                                    onClick={() => setEditingCell(null)}
                                    className="p-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded shadow-sm"
                                    title="Cancelar (Esc)"
                                  >
                                    <X className="w-3 h-3" />
                                  </button>
                                </div>
                              ) : (
                                <div className="py-1 px-1.5 flex items-center justify-center group/cell">
                                  {itemManana && itemManana.litros > 0 ? (
                                    <span className="font-extrabold text-amber-950 text-xs">
                                      {itemManana.litros.toFixed(1)}
                                    </span>
                                  ) : (
                                    <span className="text-gray-300 font-bold group-hover/cell:text-campo-600 text-xs">
                                      -
                                    </span>
                                  )}
                                </div>
                              )}
                            </td>

                            {/* Celda Tarde */}
                            <td
                              className={`p-1 text-center border-r-2 border-gray-300 transition-colors cursor-pointer select-none ${
                                isEditingTarde
                                  ? "bg-orange-100 ring-2 ring-campo-600"
                                  : "bg-orange-50/20 hover:bg-orange-100/60"
                              }`}
                              onClick={() => {
                                if (!isEditingTarde) handleClickCelda(g, dia, "TARDE");
                              }}
                              title={`Clic para editar: ${g.nombre} - ${dia.etiquetaLarga} (Tarde)`}
                            >
                              {isEditingTarde ? (
                                <div
                                  className="flex items-center justify-center gap-1"
                                  onClick={(e) => e.stopPropagation()}
                                >
                                  <input
                                    type="number"
                                    step="0.1"
                                    min="0"
                                    autoFocus
                                    disabled={savingCell}
                                    value={editingCell.valor}
                                    onChange={(e) =>
                                      setEditingCell({
                                        ...editingCell,
                                        valor: e.target.value,
                                      })
                                    }
                                    onKeyDown={(e) => {
                                      if (e.key === "Enter") handleGuardarCelda();
                                      if (e.key === "Escape") setEditingCell(null);
                                    }}
                                    className="w-14 px-1 py-1 text-xs font-bold text-center border-2 border-campo-600 rounded bg-white shadow-sm focus:outline-none"
                                  />
                                  <button
                                    type="button"
                                    disabled={savingCell}
                                    onClick={handleGuardarCelda}
                                    className="p-1 bg-campo-700 hover:bg-campo-800 text-white rounded shadow-sm"
                                    title="Guardar (Enter)"
                                  >
                                    <Check className="w-3 h-3" />
                                  </button>
                                  {editingCell.entregaId && (
                                    <button
                                      type="button"
                                      disabled={savingCell}
                                      onClick={handleEliminarCelda}
                                      className="p-1 bg-red-600 hover:bg-red-700 text-white rounded shadow-sm"
                                      title="Eliminar este pesaje"
                                    >
                                      <Trash2 className="w-3 h-3" />
                                    </button>
                                  )}
                                  <button
                                    type="button"
                                    disabled={savingCell}
                                    onClick={() => setEditingCell(null)}
                                    className="p-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded shadow-sm"
                                    title="Cancelar (Esc)"
                                  >
                                    <X className="w-3 h-3" />
                                  </button>
                                </div>
                              ) : (
                                <div className="py-1 px-1.5 flex items-center justify-center group/cell">
                                  {itemTarde && itemTarde.litros > 0 ? (
                                    <span className="font-extrabold text-orange-950 text-xs">
                                      {itemTarde.litros.toFixed(1)}
                                    </span>
                                  ) : (
                                    <span className="text-gray-300 font-bold group-hover/cell:text-campo-600 text-xs">
                                      -
                                    </span>
                                  )}
                                </div>
                              )}
                            </td>
                          </Fragment>
                        );
                      })}

                      {/* Columna Fija Derecha: Total Acumulado del Ganadero */}
                      <td className="sticky right-0 z-20 bg-campo-50/95 group-hover:bg-campo-100/90 px-4 py-3 border-l-2 border-campo-300 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.08)] text-right whitespace-nowrap">
                        <span className="font-black text-campo-950 text-xs sm:text-sm">
                          {totalGanadero > 0 ? `${totalGanadero.toFixed(1)} L` : "0.0 L"}
                        </span>
                      </td>
                    </tr>
                  );
                })
              )}
            </tbody>

            {/* PIE DE TABLA (TOTALES DIARIOS POR TURNO Y GRAN TOTAL) */}
            <tfoot className="sticky bottom-0 z-30 shadow-md font-extrabold">
              <tr className="bg-gray-100 text-gray-900 border-t-2 border-campo-300">
                {/* Columna Fija Izquierda: Etiqueta de Totales */}
                <td className="sticky left-0 z-40 bg-gray-200 px-4 py-3 border-r-2 border-gray-400 text-xs uppercase tracking-wider shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]">
                  TOTALES POR TURNO (L)
                </td>

                {/* Totales Diarios (Mañana y Tarde) */}
                {totalesPorDia.map((t) => (
                  <Fragment key={`total_${t.fecha}`}>
                    <td className="py-2.5 px-1 text-center bg-amber-100/80 text-amber-950 border-r border-amber-300 text-xs font-black">
                      {t.manana > 0 ? t.manana.toFixed(1) : "-"}
                    </td>
                    <td className="py-2.5 px-1 text-center bg-orange-100/80 text-orange-950 border-r-2 border-gray-400 text-xs font-black">
                      {t.tarde > 0 ? t.tarde.toFixed(1) : "-"}
                    </td>
                  </Fragment>
                ))}

                {/* Columna Fija Derecha: Gran Total Quincena */}
                <td className="sticky right-0 z-40 bg-campo-950 text-dorado-300 px-4 py-3 border-l-2 border-campo-700 text-right text-xs sm:text-sm font-black shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.2)] whitespace-nowrap">
                  {granTotalQuincena.toFixed(1)} L
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
  );
}
