import { Sun, Sunset, Milk, Calendar } from "lucide-react";
import { formatDateSpanish } from "@/lib/date-utils";

interface TodayCardProps {
  fecha: string;
  litrosManana: number;
  litrosTarde: number;
  totalDia: number;
}

export default function TodayCard({
  fecha,
  litrosManana,
  litrosTarde,
  totalDia,
}: TodayCardProps) {
  const fechaLegible = formatDateSpanish(fecha);

  return (
    <div className="bg-white rounded-3xl shadow-lg border-2 border-campo-200 overflow-hidden">
      {/* Cabecera de la tarjeta */}
      <div className="bg-gradient-to-r from-campo-700 to-campo-800 text-white p-5 sm:p-6">
        <div className="flex items-center justify-between flex-wrap gap-2">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-campo-200">
              <Calendar className="w-7 h-7" />
            </div>
            <div>
              <p className="text-xs uppercase font-bold tracking-wider text-campo-200">
                Registro del Día
              </p>
              <h2 className="text-2xl sm:text-3xl font-extrabold text-white capitalize">
                Hoy: {fechaLegible}
              </h2>
            </div>
          </div>
        </div>
      </div>

      {/* Contenido: Dos turnos grandes */}
      <div className="p-5 sm:p-8 space-y-6">
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
          {/* Turno Mañana */}
          <div className="bg-campo-50/70 rounded-2xl p-5 border-2 border-campo-200 flex items-center justify-between shadow-sm">
            <div className="flex items-center gap-3.5">
              <div className="w-14 h-14 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0 shadow-inner">
                <Sun className="w-8 h-8" />
              </div>
              <div>
                <p className="text-sm font-bold uppercase tracking-wider text-campo-900">
                  Turno Mañana
                </p>
                <p className="text-xs text-gray-500">Primer ordeño</p>
              </div>
            </div>
            <div className="text-right">
              <span className="text-3xl sm:text-4xl font-black text-campo-900 block">
                {litrosManana > 0 ? litrosManana.toFixed(1) : "0.0"}
              </span>
              <span className="text-xs font-bold uppercase text-campo-700">Litros</span>
            </div>
          </div>

          {/* Turno Tarde */}
          <div className="bg-campo-50/70 rounded-2xl p-5 border-2 border-campo-200 flex items-center justify-between shadow-sm">
            <div className="flex items-center gap-3.5">
              <div className="w-14 h-14 rounded-2xl bg-orange-100 text-orange-700 flex items-center justify-center flex-shrink-0 shadow-inner">
                <Sunset className="w-8 h-8" />
              </div>
              <div>
                <p className="text-sm font-bold uppercase tracking-wider text-campo-900">
                  Turno Tarde
                </p>
                <p className="text-xs text-gray-500">Segundo ordeño</p>
              </div>
            </div>
            <div className="text-right">
              <span className="text-3xl sm:text-4xl font-black text-campo-900 block">
                {litrosTarde > 0 ? litrosTarde.toFixed(1) : "0.0"}
              </span>
              <span className="text-xs font-bold uppercase text-campo-700">Litros</span>
            </div>
          </div>
        </div>

        {/* Total del Día Destacado */}
        <div className="bg-gradient-to-r from-campo-800 to-campo-900 rounded-2xl p-5 sm:p-6 text-white flex flex-col sm:flex-row items-center justify-between gap-4 shadow-md">
          <div className="flex items-center gap-3 text-center sm:text-left">
            <div className="w-12 h-12 rounded-xl bg-white/10 flex items-center justify-center text-dorado-400">
              <Milk className="w-7 h-7" />
            </div>
            <div>
              <p className="text-sm font-bold uppercase tracking-wider text-campo-200">
                Total Entregado Hoy
              </p>
              <p className="text-xs text-campo-300">
                Suma de litros de la mañana y de la tarde
              </p>
            </div>
          </div>
          <div className="text-center sm:text-right">
            <span className="text-4xl sm:text-5xl font-black text-white tracking-tight">
              {totalDia.toFixed(1)}
            </span>
            <span className="ml-2 text-lg sm:text-xl font-bold text-campo-200">
              Litros
            </span>
          </div>
        </div>
      </div>
    </div>
  );
}

