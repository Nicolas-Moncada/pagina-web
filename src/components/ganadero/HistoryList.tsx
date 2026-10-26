import { History, CheckCircle, Droplets, Calendar } from "lucide-react";
import { formatCurrency } from "@/lib/calculations";

export interface QuincenaHistorialItem {
  id: string;
  nombre: string;
  periodo: string; // ej: "01/09/2026 al 15/09/2026"
  litrosTotales: number;
  precioLitro: number | null;
  pagoTotal: number | null;
  cerrada: boolean;
}

interface HistoryListProps {
  quincenas: QuincenaHistorialItem[];
}

export default function HistoryList({ quincenas }: HistoryListProps) {
  if (quincenas.length === 0) {
    return (
      <div className="bg-white rounded-3xl p-8 border-2 border-campo-200 text-center shadow-md">
        <div className="w-16 h-16 rounded-full bg-campo-100 text-campo-700 flex items-center justify-center mx-auto mb-3">
          <History className="w-8 h-8" />
        </div>
        <h3 className="text-xl font-bold text-campo-950">
          Sin historial de quincenas anteriores
        </h3>
        <p className="text-sm text-gray-500 mt-1 max-w-md mx-auto">
          A medida que la asociación cierre las quincenas, aquí aparecerá el registro histórico de tus litros entregados y el total pagado.
        </p>
      </div>
    );
  }

  return (
    <div className="bg-white rounded-3xl shadow-lg border-2 border-campo-200 overflow-hidden">
      {/* Cabecera */}
      <div className="bg-campo-900 text-white p-5 sm:p-6 flex items-center gap-3">
        <div className="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-campo-300">
          <History className="w-7 h-7" />
        </div>
        <div>
          <h2 className="text-2xl font-bold">Historial de Pagos Anteriores</h2>
          <p className="text-xs text-campo-200">
            Resumen claro de tus quincenas pasadas y valores liquidados
          </p>
        </div>
      </div>

      {/* Lista de tarjetas simplificadas (sin tablas confusas) */}
      <div className="p-4 sm:p-6 space-y-4">
        {quincenas.map((item) => (
          <div
            key={item.id}
            className="p-5 rounded-2xl border-2 border-campo-100 hover:border-campo-300 bg-campo-50/40 hover:bg-campo-50 transition-all flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4"
          >
            {/* Periodo y estado */}
            <div className="space-y-1">
              <div className="flex items-center gap-2">
                <span className="font-bold text-lg text-campo-950">
                  {item.nombre}
                </span>
                {item.cerrada && (
                  <span className="inline-flex items-center gap-1 text-xs font-bold text-campo-800 bg-campo-200/80 px-2 py-0.5 rounded-md">
                    <CheckCircle className="w-3.5 h-3.5 text-campo-700" />
                    Cerrada
                  </span>
                )}
              </div>
              <p className="text-xs sm:text-sm text-gray-600 flex items-center gap-1.5">
                <Calendar className="w-4 h-4 text-campo-600" />
                <span>Periodo: {item.periodo}</span>
              </p>
              {item.precioLitro && (
                <p className="text-xs text-gray-500">
                  Precio pagado: {formatCurrency(item.precioLitro)} / litro
                </p>
              )}
            </div>

            {/* Litros y Total Pagado */}
            <div className="flex items-center justify-between sm:justify-end gap-6 w-full sm:w-auto pt-3 sm:pt-0 border-t sm:border-t-0 border-campo-200">
              <div className="text-left sm:text-right">
                <p className="text-xs font-bold uppercase text-gray-500">
                  Litros Totales
                </p>
                <p className="text-xl font-extrabold text-campo-900">
                  {item.litrosTotales.toFixed(1)} <span className="text-sm font-semibold">L</span>
                </p>
              </div>

              <div className="text-right">
                <p className="text-xs font-bold uppercase text-campo-700">
                  Total Pagado
                </p>
                <p className="text-xl sm:text-2xl font-black text-campo-800">
                  {item.pagoTotal !== null ? formatCurrency(item.pagoTotal) : "Pendiente"}
                </p>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

