import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getCurrentSession } from "@/lib/session";
import { enviarCorreoAlertaQuincena } from "@/lib/email";
import { formatDateSpanish } from "@/lib/date-utils";

export const dynamic = "force-dynamic";

export async function GET(request: Request) {
  try {
    const session = await getCurrentSession();
    if (!session || session.rol !== "GANADERO") {
      return NextResponse.json({ error: "No autorizado" }, { status: 403 });
    }

    const { searchParams } = new URL(request.url);
    const fechaInicio = searchParams.get("fechaInicio");
    const fechaFin = searchParams.get("fechaFin");

    if (!fechaInicio || !fechaFin) {
      return NextResponse.json(
        { error: "Parámetros de fecha de quincena requeridos" },
        { status: 400 }
      );
    }

    const alertaExistente = await prisma.alertaQuincena.findUnique({
      where: {
        usuarioId_fechaInicio_fechaFin: {
          usuarioId: session.sub,
          fechaInicio,
          fechaFin,
        },
      },
    });

    return NextResponse.json({ alerta: alertaExistente || null });
  } catch (error) {
    console.error("Error al consultar alerta:", error);
    return NextResponse.json({ error: "Error al consultar estado de alertas" }, { status: 500 });
  }
}

export async function POST(request: Request) {
  try {
    const session = await getCurrentSession();
    if (!session || session.rol !== "GANADERO") {
      return NextResponse.json({ error: "No autorizado" }, { status: 403 });
    }

    const body = await request.json();
    const { fechaInicio, fechaFin, mensaje } = body;

    if (!fechaInicio || !fechaFin || !mensaje || mensaje.trim().length < 5) {
      return NextResponse.json(
        { error: "Por favor escribe un mensaje de al menos 5 caracteres explicando tu inquietud." },
        { status: 400 }
      );
    }

    // 1. RESTRICCIÓN ESTRICTA DE 1 SOLA ALERTA POR QUINCENA
    const alertaPrevia = await prisma.alertaQuincena.findUnique({
      where: {
        usuarioId_fechaInicio_fechaFin: {
          usuarioId: session.sub,
          fechaInicio,
          fechaFin,
        },
      },
    });

    if (alertaPrevia) {
      return NextResponse.json(
        {
          error: "Ya has enviado una inquietud para esta quincena. El administrador la está revisando.",
          alerta: alertaPrevia,
        },
        { status: 400 }
      );
    }

    // 2. Calcular litros acumulados hasta el momento exacto de la alerta
    const agregacionLitros = await prisma.entrega.aggregate({
      where: {
        usuarioId: session.sub,
        fecha: {
          gte: fechaInicio,
          lte: fechaFin,
        },
      },
      _sum: {
        litros: true,
      },
    });

    const litrosAlMomento = Number((agregacionLitros._sum.litros || 0).toFixed(1));

    // 3. Guardar en Base de Datos (bloquea de inmediato cualquier reintento)
    const nuevaAlerta = await prisma.alertaQuincena.create({
      data: {
        usuarioId: session.sub,
        fechaInicio,
        fechaFin,
        mensaje: mensaje.trim(),
        litrosAlMomento,
        estado: "PENDIENTE",
      },
    });

    // 4. Intentar envío de correo a asoagroalisal@gmail.com
    const periodoTexto = `${formatDateSpanish(fechaInicio)} al ${formatDateSpanish(fechaFin)}`;
    const fechaHoraTexto = new Date().toLocaleString("es-CO", { timeZone: "America/Bogota" });

    const resultadoCorreo = await enviarCorreoAlertaQuincena({
      nombreGanadero: session.nombre,
      cedula: session.cedula,
      periodoQuincena: periodoTexto,
      litrosAlMomento,
      mensaje: mensaje.trim(),
      fechaHora: fechaHoraTexto,
    });

    if (resultadoCorreo.sent) {
      await prisma.alertaQuincena.update({
        where: { id: nuevaAlerta.id },
        data: { correoEnviado: true },
      });
    }

    return NextResponse.json({
      ok: true,
      mensaje: "✅ Tu inquietud ha sido registrada y enviada al administrador con éxito.",
      alerta: nuevaAlerta,
      correoEnviado: resultadoCorreo.sent,
    });
  } catch (error: any) {
    console.error("Error al registrar alerta de quincena:", error);
    // Si la restricción única de BD fue violada por condición de carrera
    if (error.code === "P2002") {
      return NextResponse.json(
        { error: "Ya has enviado una inquietud para esta quincena. El administrador la está revisando." },
        { status: 400 }
      );
    }
    return NextResponse.json(
      { error: "No se pudo registrar tu inquietud en este momento. Intenta nuevamente." },
      { status: 500 }
    );
  }
}

