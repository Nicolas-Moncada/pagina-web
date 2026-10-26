import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getCurrentSession } from "@/lib/session";

export async function PATCH(
  request: Request,
  { params }: { params: { id: string } }
) {
  const session = await getCurrentSession();
  if (!session || session.rol !== "ADMIN") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  try {
    const { id } = params;
    const body = await request.json();

    const consulta = await prisma.consulta.update({
      where: { id },
      data: {
        atendida: Boolean(body.atendida),
      },
    });

    return NextResponse.json({ ok: true, consulta });
  } catch (error) {
    return NextResponse.json({ error: "Error al actualizar consulta" }, { status: 500 });
  }
}

export async function DELETE(
  request: Request,
  { params }: { params: { id: string } }
) {
  const session = await getCurrentSession();
  if (!session || session.rol !== "ADMIN") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  try {
    const { id } = params;
    await prisma.consulta.delete({ where: { id } });
    return NextResponse.json({ ok: true, mensaje: "Consulta eliminada" });
  } catch (error) {
    return NextResponse.json({ error: "Error al eliminar consulta" }, { status: 500 });
  }
}

