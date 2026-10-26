import { DollarSign, Droplets, CalendarCheck, HelpCircle } from "lucide-react";
import { formatCurrency } from "@/lib/calculations";
import FortnightDetailTable, { QuincenaOpcion } from "./FortnightDetailTable";
import FortnightComplaintCard from "./FortnightComplaintCard";

interface FortnightSummaryCardProps {
  nombreQuincena: string;
  rangoFechas: string; // Ej: "01/10/2026 al 15/10/2026"
  totalLitros: number;
  precioLitro: number | null;
  pagoEstimado: number | null;
  ganaderoNombre: string;
  cedula: string;
  fechaInicio: string;
  fechaFin: string;
  quincenasDisponibles: QuincenaOpcion[];
}

export default function FortnightSummaryCard({
  nombreQuincena,
  rangoFechas,
  totalLitros,
  precioLitro,
  pagoEstimado,
  ganaderoNombre,
  cedula,
  fechaInicio,
  fechaFin,
  quincenasDisponibles,
}: FortnightSummaryCardProps) {
  const tienePrecio = precioLitro !== null && precioLitro !== undefined && precioLitro > 0;

  const quincenaActualOpcion: QuincenaOpcion = {
    id: "actual",
    nombre: nombreQuincena,
    fechaInicio,
    fechaFin,
  };

  return (
    <div className="space-y-6">
      {/* 1. Tarjeta Principal de Resumen y Liquidación */}
      <div className="bg-white rounded-3xl shadow-lg border-2 border-campo-200 overflow-hidden">
        {/* Cabecera */}
        <div className="bg-gradient-to-r from-tierra-800 to-tierra-900 text-white p-5 sm:p-6">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-tierra-200">
              <CalendarCheck className="w-7 h-7" />
            </div>
            <div>
              <p className="text-xs uppercase font-bold tracking-wider text-tierra-200">
                Quincena en Curso
              </p>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-white">
                {nombreQuincena}
              </h2>
              <p className="text-xs text-tierra-300 mt-0.5">
                Periodo de entrega: <span className="font-semibold text-white">{rangoFechas}</span>
              </p>
            </div>
          </div>
        </div>

        {/* Contenido en 3 métricas claras */}
        <div className="p-5 sm:p-8 space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Métrica 1: Litros Totales de la Quincena */}
            <div className="bg-campo-50/70 rounded-2xl p-6 border-2 border-campo-200 flex flex-col justify-between shadow-sm">
              <div className="flex items-center gap-3 mb-3">
                <div className="w-10 h-10 rounded-xl bg-campo-200 text-campo-800 flex items-center justify-center">
                  <Droplets className="w-6 h-6" />
                </div>
                <span className="text-base font-bold text-campo-950 uppercase tracking-wide">
                  Litros Acumulados
                </span>
              </div>
              <div>
                <span className="text-4xl sm:text-5xl font-black text-campo-900">
                  {totalLitros.toFixed(1)}
                </span>
                <span className="ml-2 text-lg font-bold text-campo-700">Litros</span>
                <p className="text-xs text-gray-500 mt-1">
                  Suma de todos tus litros entregados en esta quincena
                </p>
              </div>
            </div>

            {/* Métrica 2: Precio del Litro */}
            <div className="bg-campo-50/70 rounded-2xl p-6 border-2 border-campo-200 flex flex-col justify-between shadow-sm">
              <div className="flex items-center gap-3 mb-3">
                <div className="w-10 h-10 rounded-xl bg-campo-200 text-campo-800 flex items-center justify-center">
                  <DollarSign className="w-6 h-6" />
                </div>
                <span className="text-base font-bold text-campo-950 uppercase tracking-wide">
                  Precio por Litro
                </span>
              </div>
              <div>
                {tienePrecio ? (
                  <>
                    <span className="text-3xl sm:text-4xl font-black text-campo-900">
                      {formatCurrency(precioLitro!)}
                    </span>
                    <span className="ml-2 text-sm font-bold text-campo-700">/ litro</span>
                    <p className="text-xs text-campo-700 font-semibold mt-1">
                      Fijado oficialmente para esta quincena
                    </p>
                  </>
                ) : (
                  <div className="py-1">
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-amber-100 text-amber-900 border border-amber-300 font-bold text-sm">
                      <HelpCircle className="w-5 h-5 text-amber-700" />
                      <span>Precio aún no definido</span>
                    </div>
                    <p className="text-xs text-gray-500 mt-2">
                      La administración fijará el valor antes del cierre quincenal.
                    </p>
                  </div>
                )}
              </div>
            </div>
          </div>

          {/* Tarjeta Destacada: Pago Estimado de la Quincena */}
          <div className="bg-gradient-to-r from-campo-700 via-campo-800 to-campo-900 rounded-2xl p-6 sm:p-8 text-white shadow-xl border-4 border-white/20">
            <div className="flex flex-col sm:flex-row items-center justify-between gap-4 text-center sm:text-left">
              <div>
                <span className="text-xs sm:text-sm uppercase font-bold tracking-widest text-dorado-400 bg-white/10 px-3 py-1 rounded-full inline-block mb-2">
                  Liquidación Estimada
                </span>
                <h3 className="text-xl sm:text-2xl font-bold">
                  Pago de la Quincena Actual
                </h3>
                <p className="text-xs sm:text-sm text-campo-200 mt-1">
                  {tienePrecio
                    ? `Calculado: ${totalLitros.toFixed(1)} L × ${formatCurrency(precioLitro!)}`
                    : "El valor se calculará automáticamente al definirse el precio del litro."}
                </p>
              </div>

              <div className="text-center sm:text-right">
                {tienePrecio && pagoEstimado !== null ? (
                  <div>
                    <span className="text-4xl sm:text-5xl font-black text-white tracking-tight">
                      {formatCurrency(pagoEstimado)}
                    </span>
                    <p className="text-xs text-campo-200 mt-1">Aproximado al día de hoy</p>
                  </div>
                ) : (
                  <div className="bg-black/20 px-4 py-2.5 rounded-xl border border-white/10">
                    <span className="text-lg sm:text-xl font-bold text-dorado-300">
                      Pendiente de definir precio
                    </span>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* 2. Tabla de Historial Quincenal de Litros (Formato Planilla Excel - Solo Lectura) */}
      <FortnightDetailTable
        ganaderoNombre={ganaderoNombre}
        cedula={cedula}
        quincenaActual={quincenaActualOpcion}
        quincenasDisponibles={quincenasDisponibles}
      />

      {/* 3. Tarjeta de Soporte e Inconformidades con Límite de 1 Alerta por Quincena */}
      <FortnightComplaintCard
        fechaInicio={fechaInicio}
        fechaFin={fechaFin}
        periodoLegible={nombreQuincena}
        litrosAcumulados={totalLitros}
      />
    </div>
  );
}
