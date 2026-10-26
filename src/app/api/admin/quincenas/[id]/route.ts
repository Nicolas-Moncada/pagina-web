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

    const quincena = await prisma.quincena.update({
      where: { id },
      data: {
        ...(body.cerrada !== undefined && { cerrada: Boolean(body.cerrada) }),
        ...(body.precioLitro !== undefined && {
          precioLitro: body.precioLitro !== null ? Number(body.precioLitro) : null,
        }),
      },
    });

    return NextResponse.json({ ok: true, quincena });
  } catch (error) {
    console.error("Error al actualizar quincena:", error);
    return NextResponse.json({ error: "Error al actualizar quincena" }, { status: 500 });
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
    await prisma.quincena.delete({ where: { id } });
    return NextResponse.json({ ok: true, mensaje: "Quincena eliminada" });
  } catch (error) {
    return NextResponse.json({ error: "Error al eliminar quincena" }, { status: 500 });
  }
}

