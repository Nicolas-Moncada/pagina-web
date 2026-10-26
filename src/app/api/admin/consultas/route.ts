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

    const consultas = await prisma.consulta.findMany({
      orderBy: { fecha: "desc" },
    });

    return NextResponse.json({ consultas });
  } catch (error) {
    console.error("Error al obtener consultas:", error);
    return NextResponse.json({ error: "Error al consultar buzón de mensajes" }, { status: 500 });
  }
}

