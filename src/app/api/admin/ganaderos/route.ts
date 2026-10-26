import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getCurrentSession } from "@/lib/session";
import { hashPassword } from "@/lib/auth";
import { usuarioSchema } from "@/lib/validations";

export const dynamic = "force-dynamic";

export async function GET() {
  try {
    const session = await getCurrentSession();
    if (!session || session.rol !== "ADMIN") {
      return NextResponse.json({ error: "No autorizado" }, { status: 403 });
    }

    const ganaderos = await prisma.usuario.findMany({
      where: { rol: "GANADERO" },
      select: {
        id: true,
        cedula: true,
        nombre: true,
        telefono: true,
        activo: true,
        creadoEn: true,
        _count: {
          select: { entregas: true },
        },
      },
      orderBy: { nombre: "asc" },
    });

    return NextResponse.json({ ganaderos });
  } catch (error) {
    console.error("Error al obtener ganaderos:", error);
    return NextResponse.json({ error: "Error al consultar la lista de ganaderos" }, { status: 500 });
  }
}

export async function POST(request: Request) {
  const session = await getCurrentSession();
  if (!session || session.rol !== "ADMIN") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  try {
    const body = await request.json();
    const result = usuarioSchema.safeParse(body);

    if (!result.success) {
      return NextResponse.json(
        { error: result.error.errors[0]?.message || "Datos incompletos" },
        { status: 400 }
      );
    }

    const { cedula, nombre, telefono, password, activo } = result.data;

    // Verificar cédula única
    const existe = await prisma.usuario.findUnique({
      where: { cedula },
    });

    if (existe) {
      return NextResponse.json(
        { error: `Ya existe un usuario registrado con la cédula ${cedula}` },
        { status: 400 }
      );
    }

    // Contraseña inicial por defecto (la misma cédula si no se especifica)
    const rawPassword = password && password.trim() !== "" ? password.trim() : cedula;
    const passwordHash = await hashPassword(rawPassword);

    const nuevoGanadero = await prisma.usuario.create({
      data: {
        cedula,
        nombre,
        telefono: telefono || null,
        passwordHash,
        rol: "GANADERO",
        activo: activo ?? true,
      },
      select: {
        id: true,
        cedula: true,
        nombre: true,
        telefono: true,
        activo: true,
      },
    });

    return NextResponse.json({
      ok: true,
      mensaje: "Ganadero creado exitosamente",
      ganadero: nuevoGanadero,
    });
  } catch (error) {
    console.error("Error al crear ganadero:", error);
    return NextResponse.json(
      { error: "Error al registrar el ganadero en la base de datos" },
      { status: 500 }
    );
  }
}

