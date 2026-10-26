import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getCurrentSession } from "@/lib/session";

export const dynamic = "force-dynamic";

export async function GET(request: Request) {
  try {
    const session = await getCurrentSession();
    // Bloqueo estricto de seguridad: Solo lectura autorizada para el ganadero autenticado
    if (!session || session.rol !== "GANADERO") {
      return NextResponse.json({ error: "No autorizado" }, { status: 403 });
    }

    const { searchParams } = new URL(request.url);
    const fechaInicio = searchParams.get("fechaInicio");
    const fechaFin = searchParams.get("fechaFin");

    if (!fechaInicio || !fechaFin) {
      return NextResponse.json(
        { error: "Rango de fechas de quincena requerido" },
        { status: 400 }
      );
    }

    // Consulta exclusiva de las entregas de este ganadero (aislamiento 100% de datos)
    const entregas = await prisma.entrega.findMany({
      where: {
        usuarioId: session.sub,
        fecha: {
          gte: fechaInicio,
          lte: fechaFin,
        },
      },
      select: {
        fecha: true,
        turno: true,
        litros: true,
      },
      orderBy: { fecha: "asc" },
    });

    // Construir la lista completa de días de la quincena (del día inicio al fin)
    const [yIni, mIni, dIni] = fechaInicio.split("-").map(Number);
    const [yFin, mFin, dFin] = fechaFin.split("-").map(Number);

    const diasSemana = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];
    const mesesCortos = [
      "Ene", "Feb", "Mar", "Abr", "May", "Jun",
      "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"
    ];

    const diasDetalle = [];
    let totalQuincena = 0;

    for (let d = dIni; d <= dFin; d++) {
      const fechaStr = `${yIni}-${String(mIni).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
      const fechaObj = new Date(yIni, mIni - 1, d);
      const nombreDia = diasSemana[fechaObj.getDay()];
      const nombreMes = mesesCortos[mIni - 1];

      const entregaManana = entregas.find((e) => e.fecha === fechaStr && e.turno === "MANANA");
      const entregaTarde = entregas.find((e) => e.fecha === fechaStr && e.turno === "TARDE");

      const litrosManana = entregaManana ? entregaManana.litros : null;
      const litrosTarde = entregaTarde ? entregaTarde.litros : null;
      const totalDia = (litrosManana || 0) + (litrosTarde || 0);

      totalQuincena += totalDia;

      diasDetalle.push({
        fecha: fechaStr,
        diaNumero: d,
        etiqueta: `${nombreDia} ${d} ${nombreMes}`,
        manana: litrosManana !== null ? Number(litrosManana.toFixed(1)) : null,
        tarde: litrosTarde !== null ? Number(litrosTarde.toFixed(1)) : null,
        totalDia: totalDia > 0 ? Number(totalDia.toFixed(1)) : 0,
      });
    }

    return NextResponse.json({
      ganaderoNombre: session.nombre,
      cedula: session.cedula,
      fechaInicio,
      fechaFin,
      totalQuincena: Number(totalQuincena.toFixed(1)),
      dias: diasDetalle,
    });
  } catch (error) {
    console.error("Error al obtener detalle de quincena:", error);
    return NextResponse.json(
      { error: "Error al cargar el detalle quincenal de litros" },
      { status: 500 }
    );
  }
}

