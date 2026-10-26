import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getCurrentSession } from "@/lib/session";

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

    await prisma.entrega.delete({
      where: { id },
    });

    return NextResponse.json({
      ok: true,
      mensaje: "Registro de entrega eliminado con éxito",
    });
  } catch (error) {
    console.error("Error al eliminar entrega:", error);
    return NextResponse.json({ error: "Error al eliminar entrega" }, { status: 500 });
  }
}

