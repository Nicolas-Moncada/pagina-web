"use client";

import { useState, useEffect } from "react";
import { Table, Calendar, ChevronDown, CheckCircle, Clock, User, Milk, AlertTriangle } from "lucide-react";
import { formatDateSpanish } from "@/lib/date-utils";

export interface QuincenaOpcion {
  id: string;
  nombre: string;
  fechaInicio: string;
  fechaFin: string;
}

interface DiaDetalle {
  fecha: string;
  diaNumero: number;
  etiqueta: string;
  manana: number | null;
  tarde: number | null;
  totalDia: number;
}

interface FortnightDetailTableProps {
  ganaderoNombre: string;
  cedula: string;
  quincenaActual: QuincenaOpcion;
  quincenasDisponibles: QuincenaOpcion[];
}

export default function FortnightDetailTable({
  ganaderoNombre,
  cedula,
  quincenaActual,
  quincenasDisponibles,
}: FortnightDetailTableProps) {
  // Quincena seleccionada en el selector (por defecto la actual)
  const [quincenaSeleccionada, setQuincenaSeleccionada] = useState<QuincenaOpcion>(quincenaActual);
  const [dias, setDias] = useState<DiaDetalle[]>([]);
  const [totalQuincena, setTotalQuincena] = useState<number>(0);
  const [loading, setLoading] = useState<boolean>(true);
  const [vistaMovil, setVistaMovil] = useState<"tabla" | "resumen">("tabla");

  // Cargar detalle de la quincena seleccionada desde la API (solo lectura)
  const cargarDetalle = (q: QuincenaOpcion) => {
    setLoading(true);
    fetch(`/api/ganadero/quincena-detalle?fechaInicio=${q.fechaInicio}&fechaFin=${q.fechaFin}`)
      .then((r) => r.json())
      .then((data) => {
        if (data.dias) {
          setDias(data.dias);
          setTotalQuincena(data.totalQuincena || 0);
        }
      })
      .catch((err) => console.error("Error al cargar planilla:", err))
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    cargarDetalle(quincenaSeleccionada);
  }, [quincenaSeleccionada]);

  // Lista de todas las quincenas para el selector
  const todasQuincenas = [
    quincenaActual,
    ...quincenasDisponibles.filter((q) => q.fechaInicio !== quincenaActual.fechaInicio),
  ];

  return (
    <div className="bg-white rounded-3xl shadow-lg border-2 border-campo-200 overflow-hidden">
      {/* Cabecera de la sección */}
      <div className="bg-gradient-to-r from-campo-800 to-campo-900 text-white p-5 sm:p-6">
        <div className="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-dorado-400 flex-shrink-0">
              <Table className="w-7 h-7" />
            </div>
            <div>
              <p className="text-xs uppercase font-bold tracking-wider text-campo-200">
                Planilla Oficial de Pesajes (Solo Lectura)
              </p>
              <h3 className="text-xl sm:text-2xl font-black text-white">
                Historial Detallado de la Quincena
              </h3>
            </div>
          </div>

          {/* Selector de Quincena */}
          <div className="w-full md:w-auto flex flex-col sm:flex-row items-start sm:items-center gap-2">
            <label htmlFor="selector-quincena" className="text-xs text-campo-200 font-bold whitespace-nowrap">
              Consultar Periodo:
            </label>
            <select
              id="selector-quincena"
              value={`${quincenaSeleccionada.fechaInicio}_${quincenaSeleccionada.fechaFin}`}
              onChange={(e) => {
                const [fIni, fFin] = e.target.value.split("_");
                const found = todasQuincenas.find((q) => q.fechaInicio === fIni && q.fechaFin === fFin);
                if (found) setQuincenaSeleccionada(found);
              }}
              className="w-full sm:w-auto px-3.5 py-2.5 rounded-xl bg-campo-950/80 text-white font-bold text-sm border border-campo-600 focus:outline-none focus:ring-2 focus:ring-dorado-400"
            >
              {todasQuincenas.map((q) => (
                <option key={`${q.fechaInicio}_${q.fechaFin}`} value={`${q.fechaInicio}_${q.fechaFin}`}>
                  {q.nombre} ({formatDateSpanish(q.fechaInicio)} al {formatDateSpanish(q.fechaFin)})
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Franja de Fechas de Inicio y Corte */}
        <div className="mt-4 pt-3 border-t border-campo-700/80 flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm text-campo-100">
          <div className="flex items-center gap-2">
            <Calendar className="w-4 h-4 text-dorado-400" />
            <span>
              Periodo seleccionado:{" "}
              <strong className="text-white underline">
                {formatDateSpanish(quincenaSeleccionada.fechaInicio)} al {formatDateSpanish(quincenaSeleccionada.fechaFin)}
              </strong>
            </span>
          </div>

          {/* Selector de vista móvil */}
          <div className="flex md:hidden items-center gap-1 bg-campo-950/60 p-1 rounded-lg">
            <button
              onClick={() => setVistaMovil("tabla")}
              className={`px-2.5 py-1 rounded text-xs font-bold ${
                vistaMovil === "tabla" ? "bg-campo-600 text-white" : "text-campo-300"
              }`}
            >
              Planilla Excel
            </button>
            <button
              onClick={() => setVistaMovil("resumen")}
              className={`px-2.5 py-1 rounded text-xs font-bold ${
                vistaMovil === "resumen" ? "bg-campo-600 text-white" : "text-campo-300"
              }`}
            >
              Lista Día a Día
            </button>
          </div>
        </div>
      </div>

      {/* Contenido: Planilla Horizontal Formato Excel */}
      {loading ? (
        <div className="p-12 text-center text-gray-500 font-medium">
          <Milk className="w-8 h-8 mx-auto mb-2 text-campo-600 animate-pulse" />
          <span>Cargando planilla de pesajes...</span>
        </div>
      ) : dias.length === 0 ? (
        <div className="p-8 text-center text-gray-500 font-medium">
          No hay registros de pesaje para este periodo.
        </div>
      ) : (
        <>
          {/* 1. Vista Planilla Excel Horizontal (con scroll suave) */}
          <div className={`${vistaMovil === "resumen" ? "hidden md:block" : "block"} overflow-x-auto scrollbar-thin scrollbar-thumb-campo-300 p-2 sm:p-4`}>
            <div className="inline-block min-w-full align-middle">
              <table className="min-w-full border-collapse border border-gray-300 text-center text-xs sm:text-sm shadow-sm rounded-lg overflow-hidden">
                {/* Encabezado Fila 1: Días del mes */}
                <thead>
                  <tr className="bg-campo-800 text-white font-bold">
                    {/* Columna Fija Izquierda: Nombre del Ganadero */}
                    <th
                      rowSpan={2}
                      className="sticky left-0 z-20 bg-campo-900 text-white px-4 py-3 text-left border-r-2 border-campo-700 min-w-[180px] shadow-sm"
                    >
                      <div className="flex items-center gap-2">
                        <User className="w-4 h-4 text-dorado-400" />
                        <div>
                          <p className="text-xs uppercase font-extrabold text-campo-200">Asociado</p>
                          <p className="text-sm font-bold text-white truncate max-w-[150px]">{ganaderoNombre}</p>
                          <p className="text-[11px] font-normal text-campo-300">C.C. {cedula}</p>
                        </div>
                      </div>
                    </th>

                    {/* Columnas de cada día de la quincena */}
                    {dias.map((d) => (
                      <th
                        key={d.fecha}
                        colSpan={2}
                        className="px-2 py-2 border-b border-r border-campo-700 text-xs font-extrabold tracking-tight whitespace-nowrap bg-campo-800"
                      >
                        {d.etiqueta}
                      </th>
                    ))}

                    {/* Columna Fija Derecha: Total Quincena */}
                    <th
                      rowSpan={2}
                      className="sticky right-0 z-20 bg-campo-950 text-white px-4 py-3 border-l-2 border-campo-700 min-w-[120px] shadow-sm text-center"
                    >
                      <div className="text-xs uppercase font-black text-dorado-400">Total</div>
                      <div className="text-sm font-black text-white">Quincena</div>
                    </th>
                  </tr>

                  {/* Encabezado Fila 2: Sub-columnas Mañana y Tarde */}
                  <tr className="bg-campo-700/90 text-campo-100 text-[11px] font-bold">
                    {dias.map((d) => (
                      <div key={d.fecha} style={{ display: "contents" }}>
                        <th className="px-2 py-1 border-r border-b border-campo-600 w-12 bg-amber-900/40 text-amber-200">
                          M
                        </th>
                        <th className="px-2 py-1 border-r border-b border-campo-600 w-12 bg-orange-900/40 text-orange-200">
                          T
                        </th>
                      </div>
                    ))}
                  </tr>
                </thead>

                {/* Cuerpo de la tabla: Valores de litros (SOLO TEXTO ESTÁTICO, SIN INPUTS) */}
                <tbody>
                  <tr className="hover:bg-campo-50/70 transition-colors">
                    {/* Celda fija con nombre del ganadero */}
                    <td className="sticky left-0 z-10 bg-white font-bold text-gray-900 px-4 py-3 text-left border-r-2 border-gray-300 whitespace-nowrap shadow-sm text-xs">
                      {ganaderoNombre}
                    </td>

                    {/* Celdas de litros por turno para cada día */}
                    {dias.map((d) => (
                      <div key={d.fecha} style={{ display: "contents" }}>
                        {/* Turno Mañana */}
                        <td className="px-2 py-3 border-r border-b border-gray-200 font-mono font-medium text-xs text-gray-800 bg-amber-50/20">
                          {d.manana !== null ? (
                            <span className="font-bold text-campo-900">{d.manana.toFixed(1)}</span>
                          ) : (
                            <span className="text-gray-300">-</span>
                          )}
                        </td>

                        {/* Turno Tarde */}
                        <td className="px-2 py-3 border-r border-b border-gray-200 font-mono font-medium text-xs text-gray-800 bg-orange-50/20">
                          {d.tarde !== null ? (
                            <span className="font-bold text-campo-900">{d.tarde.toFixed(1)}</span>
                          ) : (
                            <span className="text-gray-300">-</span>
                          )}
                        </td>
                      </div>
                    ))}

                    {/* Celda fija con Total Quincena */}
                    <td className="sticky right-0 z-10 bg-campo-50 font-black text-campo-950 px-3 py-3 border-l-2 border-gray-300 whitespace-nowrap shadow-sm text-sm">
                      <span className="text-base text-campo-800">{totalQuincena.toFixed(1)}</span>{" "}
                      <span className="text-[11px] font-bold text-campo-600">L</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <p className="text-[11px] text-gray-500 mt-2 text-right">
              * Planilla de solo lectura en tiempo real. <strong>M:</strong> Turno Mañana | <strong>T:</strong> Turno Tarde. Desliza horizontalmente para ver todos los días.
            </p>
          </div>

          {/* 2. Vista Alternativa Móvil Día a Día (Tarjetas verticales claras) */}
          <div className={`${vistaMovil === "tabla" ? "hidden" : "block md:hidden"} p-4 space-y-2.5`}>
            {dias.map((d) => (
              <div
                key={d.fecha}
                className="p-3.5 rounded-xl border border-campo-200 bg-campo-50/40 flex items-center justify-between text-xs"
              >
                <div>
                  <span className="font-bold text-gray-900 text-sm block capitalize">{d.etiqueta}</span>
                  <span className="text-gray-500 text-[11px]">Fecha: {d.fecha}</span>
                </div>

                <div className="flex items-center gap-3 text-right">
                  <div className="bg-amber-50 px-2 py-1 rounded border border-amber-200">
                    <span className="text-[10px] uppercase font-bold text-amber-800 block">Mañana</span>
                    <strong className="text-xs text-campo-950">{d.manana !== null ? `${d.manana.toFixed(1)} L` : "-"}</strong>
                  </div>

                  <div className="bg-orange-50 px-2 py-1 rounded border border-orange-200">
                    <span className="text-[10px] uppercase font-bold text-orange-800 block">Tarde</span>
                    <strong className="text-xs text-campo-950">{d.tarde !== null ? `${d.tarde.toFixed(1)} L` : "-"}</strong>
                  </div>

                  <div className="bg-campo-100 px-2.5 py-1 rounded border border-campo-300">
                    <span className="text-[10px] uppercase font-bold text-campo-800 block">Día</span>
                    <strong className="text-xs text-campo-950 font-black">{d.totalDia.toFixed(1)} L</strong>
                  </div>
                </div>
              </div>
            ))}

            <div className="p-4 rounded-xl bg-campo-800 text-white flex items-center justify-between font-bold text-sm mt-3">
              <span>Total Acumulado Quincena:</span>
              <span className="text-lg text-dorado-300">{totalQuincena.toFixed(1)} Litros</span>
            </div>
          </div>
        </>
      )}
    </div>
  );
}

