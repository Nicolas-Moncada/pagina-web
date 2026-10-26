import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { consultaSchema } from "@/lib/validations";

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const result = consultaSchema.safeParse(body);

    if (!result.success) {
      return NextResponse.json(
        { error: result.error.errors[0]?.message || "Datos incompletos" },
        { status: 400 }
      );
    }

    const { nombre, contacto, mensaje } = result.data;

    const nuevaConsulta = await prisma.consulta.create({
      data: {
        nombre,
        contacto,
        mensaje,
      },
    });

    return NextResponse.json({
      ok: true,
      mensaje: "Tu consulta ha sido enviada con éxito. Nuestro equipo se comunicará contigo pronto.",
      consultaId: nuevaConsulta.id,
    });
  } catch (error) {
    console.error("Error al registrar consulta:", error);
    return NextResponse.json(
      { error: "No se pudo guardar tu consulta. Por favor intenta de nuevo más tarde." },
      { status: 500 }
    );
  }
}

