import { requireSession } from "@/lib/session";
import { prisma } from "@/lib/prisma";
import { getTodayString, getQuincenaForDate, formatDateSpanish } from "@/lib/date-utils";
import { calcularLitrosDia, calcularLitrosQuincena, calcularPagoQuincena } from "@/lib/calculations";
import TodayCard from "@/components/ganadero/TodayCard";
import FortnightSummaryCard from "@/components/ganadero/FortnightSummaryCard";
import HistoryList, { QuincenaHistorialItem } from "@/components/ganadero/HistoryList";

export const dynamic = "force-dynamic";

export default async function PanelGanaderoPage() {
  const session = await requireSession("GANADERO");
  const usuarioId = session.sub;

  const hoyStr = getTodayString();
  const quincenaRango = getQuincenaForDate(hoyStr);

  // Ejecución paralela de las 4 consultas clave del ganadero
  const [
    entregasHoy,
    quincenaActualDB,
    entregasQuincenaActual,
    quincenasPasadasDB,
  ] = await Promise.all([
    // 1. Entregas de hoy (solo este ganadero)
    prisma.entrega.findMany({
      where: { usuarioId, fecha: hoyStr },
      select: { turno: true, litros: true },
    }),
    // 2. Información de la quincena actual
    prisma.quincena.findUnique({
      where: {
        fechaInicio_fechaFin: {
          fechaInicio: quincenaRango.fechaInicio,
          fechaFin: quincenaRango.fechaFin,
        },
      },
      select: { precioLitro: true },
    }),
    // 3. Entregas de la quincena actual
    prisma.entrega.findMany({
      where: {
        usuarioId,
        fecha: {
          gte: quincenaRango.fechaInicio,
          lte: quincenaRango.fechaFin,
        },
      },
      select: { litros: true },
    }),
    // 4. Últimas quincenas cerradas/anteriores
    prisma.quincena.findMany({
      where: { fechaFin: { lt: quincenaRango.fechaInicio } },
      select: { id: true, fechaInicio: true, fechaFin: true, precioLitro: true, cerrada: true },
      orderBy: { fechaInicio: "desc" },
      take: 6,
    }),
  ]);

  const resumenDia = calcularLitrosDia(entregasHoy);
  const totalLitrosQuincena = calcularLitrosQuincena(entregasQuincenaActual);
  const calculoPago = calcularPagoQuincena(totalLitrosQuincena, quincenaActualDB?.precioLitro);

  // Optimización O(1): Obtener entregas históricas en 1 sola consulta en vez de N consultas secuenciales
  let todasEntregasPasadas: { fecha: string; litros: number }[] = [];
  if (quincenasPasadasDB.length > 0) {
    const fechaMin = quincenasPasadasDB[quincenasPasadasDB.length - 1].fechaInicio;
    const fechaMax = quincenasPasadasDB[0].fechaFin;

    todasEntregasPasadas = await prisma.entrega.findMany({
      where: {
        usuarioId,
        fecha: { gte: fechaMin, lte: fechaMax },
      },
      select: { fecha: true, litros: true },
    });
  }

  const historial: QuincenaHistorialItem[] = quincenasPasadasDB.map((q) => {
    const entregasQ = todasEntregasPasadas.filter(
      (e) => e.fecha >= q.fechaInicio && e.fecha <= q.fechaFin
    );
    const litrosQ = calcularLitrosQuincena(entregasQ);
    const infoQ = getQuincenaForDate(q.fechaInicio);
    const pagoCalculado = q.precioLitro ? Number((litrosQ * q.precioLitro).toFixed(2)) : null;

    return {
      id: q.id,
      nombre: infoQ.nombreLegible,
      periodo: `${formatDateSpanish(q.fechaInicio)} al ${formatDateSpanish(q.fechaFin)}`,
      litrosTotales: litrosQ,
      precioLitro: q.precioLitro,
      pagoTotal: pagoCalculado,
      cerrada: q.cerrada,
    };
  });

  return (
    <div className="space-y-8">
      {/* 1. Tarjeta Grande del Día Actual */}
      <TodayCard
        fecha={hoyStr}
        litrosManana={resumenDia.manana}
        litrosTarde={resumenDia.tarde}
        totalDia={resumenDia.total}
      />

      {/* 2. Sección de Quincena en Curso: Métricas, Planilla Excel y Reclamos */}
      <FortnightSummaryCard
        nombreQuincena={quincenaRango.nombreLegible}
        rangoFechas={`${formatDateSpanish(quincenaRango.fechaInicio)} al ${formatDateSpanish(quincenaRango.fechaFin)}`}
        totalLitros={totalLitrosQuincena}
        precioLitro={quincenaActualDB?.precioLitro ?? null}
        pagoEstimado={calculoPago.pagoEstimado}
        ganaderoNombre={session.nombre}
        cedula={session.cedula}
        fechaInicio={quincenaRango.fechaInicio}
        fechaFin={quincenaRango.fechaFin}
        quincenasDisponibles={quincenasPasadasDB.map((q) => ({
          id: q.id,
          nombre: getQuincenaForDate(q.fechaInicio).nombreLegible,
          fechaInicio: q.fechaInicio,
          fechaFin: q.fechaFin,
        }))}
      />

      {/* 3. Historial Simplificado de Quincenas Anteriores */}
      <HistoryList quincenas={historial} />
    </div>
  );
}

