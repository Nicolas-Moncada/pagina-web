import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { verifyPassword, createSessionToken, COOKIE_NAME } from "@/lib/auth";
import { loginSchema } from "@/lib/validations";

export async function POST(request: Request) {
  try {
    const body = await request.json();
    const result = loginSchema.safeParse(body);

    if (!result.success) {
      return NextResponse.json(
        { error: result.error.errors[0]?.message || "Datos incompletos" },
        { status: 400 }
      );
    }

    const { cedula, password } = result.data;

    // Buscar usuario por cédula
    const usuario = await prisma.usuario.findUnique({
      where: { cedula },
    });

    if (!usuario) {
      return NextResponse.json(
        { error: "Cédula o contraseña incorrecta" },
        { status: 401 }
      );
    }

    if (!usuario.activo) {
      return NextResponse.json(
        { error: "Tu cuenta se encuentra inactiva. Comunícate con la administración." },
        { status: 403 }
      );
    }

    const passwordValida = await verifyPassword(password, usuario.passwordHash);
    if (!passwordValida) {
      return NextResponse.json(
        { error: "Cédula o contraseña incorrecta" },
        { status: 401 }
      );
    }

    // Generar token JWT
    const token = await createSessionToken({
      sub: usuario.id,
      cedula: usuario.cedula,
      nombre: usuario.nombre,
      rol: usuario.rol,
    });

    const redirectUrl = usuario.rol === "ADMIN" ? "/admin" : "/panel";

    const response = NextResponse.json({
      ok: true,
      mensaje: "Inicio de sesión exitoso",
      usuario: {
        id: usuario.id,
        cedula: usuario.cedula,
        nombre: usuario.nombre,
        rol: usuario.rol,
      },
      redirectUrl,
    });

    // Guardar en cookie httpOnly
    response.cookies.set({
      name: COOKIE_NAME,
      value: token,
      httpOnly: true,
      secure: process.env.NODE_ENV === "production",
      sameSite: "lax",
      path: "/",
      maxAge: 60 * 60 * 24 * 7, // 7 días
    });

    return response;
  } catch (error) {
    console.error("Error en login:", error);
    return NextResponse.json(
      { error: "Ocurrió un error al procesar tu solicitud. Intenta nuevamente." },
      { status: 500 }
    );
  }
}

