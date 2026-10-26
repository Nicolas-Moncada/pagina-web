import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getCurrentSession } from "@/lib/session";
import { entregaSchema } from "@/lib/validations";

export const dynamic = "force-dynamic";

export async function GET(request: Request) {
  try {
    const session = await getCurrentSession();
    if (!session || session.rol !== "ADMIN") {
      return NextResponse.json({ error: "No autorizado" }, { status: 403 });
    }

    const { searchParams } = new URL(request.url);
    const fecha = searchParams.get("fecha");
    const fechaInicio = searchParams.get("fechaInicio");
    const fechaFin = searchParams.get("fechaFin");
    const usuarioId = searchParams.get("usuarioId");
    const turno = searchParams.get("turno") as "MANANA" | "TARDE" | null;

    const where: any = {};
    if (fechaInicio && fechaFin) {
      where.fecha = { gte: fechaInicio, lte: fechaFin };
    } else if (fecha) {
      where.fecha = fecha;
    }
    if (usuarioId) where.usuarioId = usuarioId;
    if (turno && (turno === "MANANA" || turno === "TARDE")) where.turno = turno;

    const takeLimit: number = fechaInicio && fechaFin ? 5000 : 200;

    const entregas = await prisma.entrega.findMany({
      where,
      select: {
        id: true,
        usuarioId: true,
        fecha: true,
        turno: true,
        litros: true,
        creadoEn: true,
        usuario: {
          select: {
            id: true,
            cedula: true,
            nombre: true,
          },
        },
      },
      orderBy: [{ fecha: "asc" }, { creadoEn: "asc" }],
      take: takeLimit,
    });

    return NextResponse.json({ entregas });
  } catch (error) {
    console.error("Error al obtener entregas:", error);
    return NextResponse.json({ error: "Error al consultar entregas" }, { status: 500 });
  }
}

export async function POST(request: Request) {
  const session = await getCurrentSession();
  if (!session || session.rol !== "ADMIN") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  try {
    const body = await request.json();
    const result = entregaSchema.safeParse(body);

    if (!result.success) {
      return NextResponse.json(
        { error: result.error.errors[0]?.message || "Datos incompletos" },
        { status: 400 }
      );
    }

    const { usuarioId, fecha, turno, litros } = result.data;

    // Verificar que el ganadero exista
    const usuario = await prisma.usuario.findUnique({
      where: { id: usuarioId },
    });

    if (!usuario) {
      return NextResponse.json({ error: "El ganadero seleccionado no existe" }, { status: 404 });
    }

    // Upsert: si ya existe para este ganadero, fecha y turno, se actualizan los litros (corrección de errores); si no, se crea.
    const entrega = await prisma.entrega.upsert({
      where: {
        usuarioId_fecha_turno: {
          usuarioId,
          fecha,
          turno,
        },
      },
      update: {
        litros,
      },
      create: {
        usuarioId,
        fecha,
        turno,
        litros,
      },
      include: {
        usuario: {
          select: {
            id: true,
            cedula: true,
            nombre: true,
          },
        },
      },
    });

    return NextResponse.json({
      ok: true,
      mensaje: `✅ Litros guardados: ${litros.toFixed(1)} L para ${usuario.nombre} (${turno === "MANANA" ? "Mañana" : "Tarde"})`,
      entrega,
    });
  } catch (error) {
    console.error("Error al guardar entrega:", error);
    return NextResponse.json(
      { error: "No se pudo guardar la entrega en la base de datos" },
      { status: 500 }
    );
  }
}

export async function DELETE(request: Request) {
  const session = await getCurrentSession();
  if (!session || session.rol !== "ADMIN") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  try {
    const { searchParams } = new URL(request.url);
    const id = searchParams.get("id");
    const usuarioId = searchParams.get("usuarioId");
    const fecha = searchParams.get("fecha");
    const turno = searchParams.get("turno") as "MANANA" | "TARDE" | null;

    if (id) {
      await prisma.entrega.delete({ where: { id } });
      return NextResponse.json({ ok: true, mensaje: "Entrega eliminada con éxito" });
    }

    if (usuarioId && fecha && turno) {
      await prisma.entrega.deleteMany({
        where: { usuarioId, fecha, turno },
      });
      return NextResponse.json({ ok: true, mensaje: "Entrega eliminada con éxito" });
    }

    // Probar si viene por JSON body
    try {
      const body = await request.json();
      if (body.id) {
        await prisma.entrega.delete({ where: { id: body.id } });
        return NextResponse.json({ ok: true, mensaje: "Entrega eliminada con éxito" });
      }
      if (body.usuarioId && body.fecha && body.turno) {
        await prisma.entrega.deleteMany({
          where: { usuarioId: body.usuarioId, fecha: body.fecha, turno: body.turno },
        });
        return NextResponse.json({ ok: true, mensaje: "Entrega eliminada con éxito" });
      }
    } catch {
      // Ignorar si no hay body
    }

    return NextResponse.json(
      { error: "Parámetros insuficientes para eliminar entrega" },
      { status: 400 }
    );
  } catch (error) {
    console.error("Error al eliminar entrega:", error);
    return NextResponse.json(
      { error: "Error al eliminar entrega" },
      { status: 500 }
    );
  }
}

