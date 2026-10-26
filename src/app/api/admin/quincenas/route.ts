import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getCurrentSession } from "@/lib/session";
import { quincenaSchema } from "@/lib/validations";

export const dynamic = "force-dynamic";

export async function GET() {
  try {
    const session = await getCurrentSession();
    if (!session || session.rol !== "ADMIN") {
      return NextResponse.json({ error: "No autorizado" }, { status: 403 });
    }

    const quincenas = await prisma.quincena.findMany({
      orderBy: { fechaInicio: "desc" },
    });

    return NextResponse.json({ quincenas });
  } catch (error) {
    console.error("Error al obtener quincenas:", error);
    return NextResponse.json({ error: "Error al consultar quincenas" }, { status: 500 });
  }
}

export async function POST(request: Request) {
  const session = await getCurrentSession();
  if (!session || session.rol !== "ADMIN") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  try {
    const body = await request.json();
    const result = quincenaSchema.safeParse(body);

    if (!result.success) {
      return NextResponse.json(
        { error: result.error.errors[0]?.message || "Datos incompletos" },
        { status: 400 }
      );
    }

    const { fechaInicio, fechaFin, precioLitro, cerrada } = result.data;

    const quincena = await prisma.quincena.upsert({
      where: {
        fechaInicio_fechaFin: {
          fechaInicio,
          fechaFin,
        },
      },
      update: {
        precioLitro: precioLitro !== undefined ? precioLitro : null,
        cerrada: cerrada ?? false,
      },
      create: {
        fechaInicio,
        fechaFin,
        precioLitro: precioLitro !== undefined ? precioLitro : null,
        cerrada: cerrada ?? false,
      },
    });

    return NextResponse.json({
      ok: true,
      mensaje: "Quincena guardada correctamente",
      quincena,
    });
  } catch (error) {
    console.error("Error al guardar quincena:", error);
    return NextResponse.json(
      { error: "Error al registrar la quincena" },
      { status: 500 }
    );
  }
}

