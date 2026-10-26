import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { hashPassword } from "@/lib/auth";
import { registroGanaderoSchema } from "@/lib/validations";

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const result = registroGanaderoSchema.safeParse(body);

    if (!result.success) {
      return NextResponse.json(
        { error: result.error.errors[0]?.message || "Datos incompletos o inválidos" },
        { status: 400 }
      );
    }

    const { cedula, nombre, telefono, password } = result.data;

    // Verificar si la cédula ya existe
    const usuarioExistente = await prisma.usuario.findUnique({
      where: { cedula },
    });

    if (usuarioExistente) {
      return NextResponse.json(
        {
          error: `Ya existe una cuenta con la cédula ${cedula}. Si ya estás registrado, ingresa directamente desde la pantalla de login.`,
        },
        { status: 400 }
      );
    }

    // Hashear contraseña
    const passwordHash = await hashPassword(password);

    // Crear el usuario ganadero asociado
    const nuevoGanadero = await prisma.usuario.create({
      data: {
        cedula,
        nombre,
        telefono: telefono || null,
        passwordHash,
        rol: "GANADERO",
        activo: true,
      },
      select: {
        id: true,
        cedula: true,
        nombre: true,
      },
    });

    return NextResponse.json(
      {
        ok: true,
        mensaje: "¡Registro completado con éxito!",
        usuario: nuevoGanadero,
      },
      { status: 201 }
    );
  } catch (error) {
    console.error("Error en registro:", error);
    return NextResponse.json(
      { error: "Ocurrió un error al registrar tu cuenta. Por favor intenta de nuevo." },
      { status: 500 }
    );
  }
}
