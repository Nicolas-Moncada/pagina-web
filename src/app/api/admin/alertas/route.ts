import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getCurrentSession } from "@/lib/session";

export const dynamic = "force-dynamic";

export async function GET() {
  try {
    const session = await getCurrentSession();
    if (!session || session.rol !== "ADMIN") {
      return NextResponse.json({ error: "No autorizado" }, { status: 403 });
    }

    const alertas = await prisma.alertaQuincena.findMany({
      include: {
        usuario: {
          select: {
            id: true,
            nombre: true,
            cedula: true,
            telefono: true,
          },
        },
      },
      orderBy: { creadoEn: "desc" },
    });

    return NextResponse.json({ alertas });
  } catch (error) {
    console.error("Error al obtener alertas:", error);
    return NextResponse.json({ error: "Error al consultar alertas" }, { status: 500 });
  }
}

export async function PATCH(request: Request) {
  try {
    const session = await getCurrentSession();
    if (!session || session.rol !== "ADMIN") {
      return NextResponse.json({ error: "No autorizado" }, { status: 403 });
    }

    const body = await request.json();
    const { id, estado } = body;

    const alerta = await prisma.alertaQuincena.update({
      where: { id },
      data: { estado },
    });

    return NextResponse.json({ ok: true, alerta });
  } catch (error) {
    console.error("Error al actualizar alerta:", error);
    return NextResponse.json({ error: "Error al actualizar estado de la alerta" }, { status: 500 });
  }
}

