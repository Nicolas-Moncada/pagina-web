import { prisma } from "@/lib/prisma";
import { getTodayString, getQuincenaForDate, formatDateSpanish, getLastDayOfMonth } from "@/lib/date-utils";
import { formatCurrency } from "@/lib/calculations";
import Link from "next/link";
import {
  Milk,
  Calendar,
  Users,
  TrendingUp,
  AlertTriangle,
  PlusCircle,
  FileSpreadsheet,
  CheckCircle2,
  DollarSign,
} from "lucide-react";

export const dynamic = "force-dynamic";

export default async function AdminDashboardPage() {
  const hoyStr = getTodayString();
  const [anoStr, mesStr] = hoyStr.split("-");
  const ano = parseInt(anoStr, 10);
  const mesIndex = parseInt(mesStr, 10) - 1;
  const mesPad = String(mesIndex + 1).padStart(2, "0");
  const ultimoDiaMes = getLastDayOfMonth(ano, mesIndex);

  const quincenaActual = getQuincenaForDate(hoyStr);
  const fechaInicioMes = `${ano}-${mesPad}-01`;
  const fechaFinMes = `${ano}-${mesPad}-${String(ultimoDiaMes).padStart(2, "0")}`;

  // Ejecución en paralelo de todas las consultas de base de datos
  const [
    entregasHoy,
    quincenaDB,
    entregasQuincena,
    entregasMes,
    q1,
    q2,
    ganaderosActivos,
  ] = await Promise.all([
    // 1. Entregas de Hoy
    prisma.entrega.findMany({
      where: { fecha: hoyStr },
      select: { turno: true, litros: true },
    }),
    // 2. Quincena en curso
    prisma.quincena.findUnique({
      where: {
        fechaInicio_fechaFin: {
          fechaInicio: quincenaActual.fechaInicio,
          fechaFin: quincenaActual.fechaFin,
        },
      },
      select: { precioLitro: true },
    }),
    // 3. Entregas de la quincena actual
    prisma.entrega.findMany({
      where: {
        fecha: {
          gte: quincenaActual.fechaInicio,
          lte: quincenaActual.fechaFin,
        },
      },
      select: { usuarioId: true, litros: true },
    }),
    // 4. Entregas del mes
    prisma.entrega.findMany({
      where: {
        fecha: {
          gte: fechaInicioMes,
          lte: fechaFinMes,
        },
      },
      select: { fecha: true, litros: true },
    }),
    // 5. Quincena 1 del mes
    prisma.quincena.findUnique({
      where: { fechaInicio_fechaFin: { fechaInicio: `${ano}-${mesPad}-01`, fechaFin: `${ano}-${mesPad}-15` } },
      select: { precioLitro: true },
    }),
    // 6. Quincena 2 del mes
    prisma.quincena.findUnique({
      where: { fechaInicio_fechaFin: { fechaInicio: `${ano}-${mesPad}-16`, fechaFin: `${ano}-${mesPad}-${String(ultimoDiaMes).padStart(2, "0")}` } },
      select: { precioLitro: true },
    }),
    // 7. Ganaderos activos
    prisma.usuario.findMany({
      where: { rol: "GANADERO" },
      select: { id: true, nombre: true, cedula: true, activo: true },
      orderBy: { nombre: "asc" },
    }),
  ]);

  // Cálculos consolidados en memoria (cero latencia adicional)
  const litrosHoyManana = entregasHoy
    .filter((e) => e.turno === "MANANA")
    .reduce((acc, curr) => acc + curr.litros, 0);

  const litrosHoyTarde = entregasHoy
    .filter((e) => e.turno === "TARDE")
    .reduce((acc, curr) => acc + curr.litros, 0);

  const totalLitrosHoy = litrosHoyManana + litrosHoyTarde;

  const totalLitrosQuincena = entregasQuincena.reduce((acc, curr) => acc + curr.litros, 0);
  const precioDefinido = quincenaDB?.precioLitro !== null && quincenaDB?.precioLitro !== undefined;
  const totalPagoQuincena = precioDefinido ? totalLitrosQuincena * quincenaDB!.precioLitro! : null;

  const totalLitrosMes = entregasMes.reduce((acc, curr) => acc + curr.litros, 0);

  const entregasQ1Mes = entregasMes.filter((e) => e.fecha <= `${ano}-${mesPad}-15`);
  const entregasQ2Mes = entregasMes.filter((e) => e.fecha >= `${ano}-${mesPad}-16`);

  const litrosQ1Mes = entregasQ1Mes.reduce((acc, curr) => acc + curr.litros, 0);
  const litrosQ2Mes = entregasQ2Mes.reduce((acc, curr) => acc + curr.litros, 0);

  const pagoQ1 = q1?.precioLitro ? litrosQ1Mes * q1.precioLitro : null;
  const pagoQ2 = q2?.precioLitro ? litrosQ2Mes * q2.precioLitro : null;

  let totalPagoMes: number | null = null;
  if (pagoQ1 !== null || pagoQ2 !== null) {
    totalPagoMes = (pagoQ1 || 0) + (pagoQ2 || 0);
  }

  // 4. Agrupación por Ganadero para la tabla resumen de la quincena actual
  const resumenGanaderos = ganaderosActivos.map((g) => {
    const entregasG = entregasQuincena.filter((e) => e.usuarioId === g.id);
    const litrosG = entregasG.reduce((acc, curr) => acc + curr.litros, 0);
    const pagoG = precioDefinido ? litrosG * quincenaDB!.precioLitro! : null;
    return {
      id: g.id,
      nombre: g.nombre,
      cedula: g.cedula,
      activo: g.activo,
      litros: Number(litrosG.toFixed(1)),
      pago: pagoG !== null ? Number(pagoG.toFixed(2)) : null,
    };
  });

  return (
    <div className="space-y-8">
      {/* Encabezado y Acciones Rápidas */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white p-6 rounded-2xl shadow-sm border border-gray-200">
        <div>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-campo-950">
            Tablero General de Acopio
          </h1>
          <p className="text-gray-600 text-sm mt-0.5">
            Fecha actual: <span className="font-semibold text-campo-800">{formatDateSpanish(hoyStr)}</span>
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-3 w-full sm:w-auto">
          <Link
            href="/admin/entregas"
            className="btn-touch bg-campo-700 hover:bg-campo-800 text-white text-sm font-bold shadow px-4 py-2.5 rounded-xl flex items-center gap-2"
          >
            <PlusCircle className="w-5 h-5" />
            <span>Registrar Leche</span>
          </Link>
          <Link
            href="/admin/importar"
            className="btn-touch bg-white hover:bg-campo-50 text-campo-800 border-2 border-campo-300 text-sm font-bold shadow-sm px-4 py-2.5 rounded-xl flex items-center gap-2"
          >
            <FileSpreadsheet className="w-5 h-5 text-campo-600" />
            <span>Subir Excel</span>
          </Link>
        </div>
      </div>

      {/* Alerta si el precio no está fijado */}
      {!precioDefinido && (
        <div className="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-xl flex items-start gap-3 shadow-sm">
          <AlertTriangle className="w-6 h-6 text-amber-600 flex-shrink-0 mt-0.5" />
          <div className="flex-1">
            <h2 className="text-sm font-bold text-amber-900">
              Precio del litro aún no definido para la {quincenaActual.nombreLegible}
            </h2>
            <p className="text-xs text-amber-800 mt-0.5">
              Los ganaderos verán "precio aún no definido" hasta que fijes el valor oficial.
            </p>
          </div>
          <Link
            href="/admin/quincenas"
            className="text-xs font-bold text-amber-900 underline hover:text-amber-700 ml-2 py-1 px-2 rounded"
          >
            Fijar precio ahora
          </Link>
        </div>
      )}

      {/* Tarjetas de Totales Generales de la Asociación */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        {/* Total de Hoy */}
        <div className="bg-white rounded-2xl p-6 border-2 border-campo-200 shadow-sm relative overflow-hidden">
          <div className="flex items-center justify-between mb-4">
            <span className="text-xs font-bold uppercase tracking-wider text-campo-700 bg-campo-50 px-2.5 py-1 rounded-md">
              Hoy de Toda la Asociación
            </span>
            <Milk className="w-6 h-6 text-campo-600" />
          </div>
          <p className="text-3xl sm:text-4xl font-black text-campo-950">
            {totalLitrosHoy.toFixed(1)} <span className="text-lg font-bold text-campo-700">L</span>
          </p>
          <div className="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between text-xs text-gray-600">
            <span>Mañana: <strong>{litrosHoyManana.toFixed(1)} L</strong></span>
            <span>Tarde: <strong>{litrosHoyTarde.toFixed(1)} L</strong></span>
          </div>
        </div>

        {/* Total Quincena Actual */}
        <div className="bg-white rounded-2xl p-6 border-2 border-campo-200 shadow-sm relative overflow-hidden">
          <div className="flex items-center justify-between mb-4">
            <span className="text-xs font-bold uppercase tracking-wider text-campo-700 bg-campo-50 px-2.5 py-1 rounded-md">
              Quincena en Curso
            </span>
            <Calendar className="w-6 h-6 text-campo-600" />
          </div>
          <p className="text-3xl sm:text-4xl font-black text-campo-950">
            {totalLitrosQuincena.toFixed(1)} <span className="text-lg font-bold text-campo-700">L</span>
          </p>
          <div className="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-600 flex items-center justify-between">
            <span>
              Precio:{" "}
              {precioDefinido ? (
                <strong className="text-campo-800">{formatCurrency(quincenaDB!.precioLitro!)}/L</strong>
              ) : (
                <span className="text-amber-700 font-semibold">Sin definir</span>
              )}
            </span>
            <span>
              Liquidación:{" "}
              {totalPagoQuincena !== null ? (
                <strong className="text-campo-800">{formatCurrency(totalPagoQuincena)}</strong>
              ) : (
                <span className="text-gray-400">Pendiente</span>
              )}
            </span>
          </div>
        </div>

        {/* Total del Mes */}
        <div className="bg-white rounded-2xl p-6 border-2 border-campo-200 shadow-sm relative overflow-hidden">
          <div className="flex items-center justify-between mb-4">
            <span className="text-xs font-bold uppercase tracking-wider text-campo-700 bg-campo-50 px-2.5 py-1 rounded-md">
              Mes Completo Acumulado
            </span>
            <TrendingUp className="w-6 h-6 text-campo-600" />
          </div>
          <p className="text-3xl sm:text-4xl font-black text-campo-950">
            {totalLitrosMes.toFixed(1)} <span className="text-lg font-bold text-campo-700">L</span>
          </p>
          <div className="mt-4 pt-3 border-t border-gray-100 text-xs text-gray-600 flex items-center justify-between">
            <span>Periodo de 30 días</span>
            <span>
              Total Mes:{" "}
              {totalPagoMes !== null ? (
                <strong className="text-campo-800">{formatCurrency(totalPagoMes)}</strong>
              ) : (
                <span className="text-gray-400">Pendiente</span>
              )}
            </span>
          </div>
        </div>
      </div>

      {/* Tabla Resumen por Ganadero de la Quincena Actual */}
      <div className="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div className="p-6 bg-gray-50/70 border-b border-gray-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div>
            <h2 className="text-xl font-bold text-gray-900">
              Resumen de Liquidación por Ganadero
            </h2>
            <p className="text-xs text-gray-500 mt-0.5">
              {quincenaActual.nombreLegible} ({formatDateSpanish(quincenaActual.fechaInicio)} al {formatDateSpanish(quincenaActual.fechaFin)})
            </p>
          </div>
          <span className="text-xs font-bold bg-campo-100 text-campo-800 px-3 py-1.5 rounded-lg border border-campo-200">
            {ganaderosActivos.length} Ganaderos Registrados
          </span>
        </div>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-sm text-gray-700">
            <thead className="bg-gray-100 text-xs uppercase font-bold text-gray-600 border-b border-gray-200">
              <tr>
                <th className="px-6 py-4">Ganadero</th>
                <th className="px-6 py-4">Cédula</th>
                <th className="px-6 py-4 text-center">Estado</th>
                <th className="px-6 py-4 text-right">Litros Quincena</th>
                <th className="px-6 py-4 text-right">Pago Estimado</th>
                <th className="px-6 py-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {resumenGanaderos.length === 0 ? (
                <tr>
                  <td colSpan={6} className="px-6 py-8 text-center text-gray-500">
                    No hay ganaderos registrados todavía. Usa el botón "Registrar Ganadero" o "Subir Excel".
                  </td>
                </tr>
              ) : (
                resumenGanaderos.map((g) => (
                  <tr key={g.id} className="hover:bg-campo-50/40 transition-colors">
                    <td className="px-6 py-4 font-bold text-gray-950">
                      {g.nombre}
                    </td>
                    <td className="px-6 py-4 font-mono text-gray-600">
                      {g.cedula}
                    </td>
                    <td className="px-6 py-4 text-center">
                      {g.activo ? (
                        <span className="inline-flex items-center gap-1 text-xs font-bold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                          Activo
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-xs font-bold text-red-700 bg-red-100 px-2 py-0.5 rounded-full">
                          Inactivo
                        </span>
                      )}
                    </td>
                    <td className="px-6 py-4 text-right font-extrabold text-campo-900 text-base">
                      {g.litros.toFixed(1)} L
                    </td>
                    <td className="px-6 py-4 text-right font-black text-gray-900 text-base">
                      {g.pago !== null ? formatCurrency(g.pago) : "Pendiente"}
                    </td>
                    <td className="px-6 py-4 text-center">
                      <Link
                        href={`/admin/entregas?usuarioId=${g.id}`}
                        className="inline-flex items-center gap-1 text-xs font-bold text-campo-700 hover:text-campo-900 bg-campo-50 hover:bg-campo-100 px-2.5 py-1.5 rounded-lg border border-campo-200"
                      >
                        Ver Entregas
                      </Link>
                    </td>
                  </tr>
                ))
              )}
            </tbody>
            {resumenGanaderos.length > 0 && (
              <tfoot className="bg-gray-100 font-bold border-t-2 border-gray-300">
                <tr>
                  <td colSpan={3} className="px-6 py-4 text-gray-900 text-base">
                    TOTAL GENERAL ASOCIACIÓN
                  </td>
                  <td className="px-6 py-4 text-right text-campo-950 text-lg font-black">
                    {totalLitrosQuincena.toFixed(1)} L
                  </td>
                  <td className="px-6 py-4 text-right text-campo-950 text-lg font-black">
                    {totalPagoQuincena !== null ? formatCurrency(totalPagoQuincena) : "Pendiente"}
                  </td>
                  <td></td>
                </tr>
              </tfoot>
            )}
          </table>
        </div>
      </div>
    </div>
  );
}

