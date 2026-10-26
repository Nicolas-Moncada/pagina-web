import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getCurrentSession } from "@/lib/session";
import { hashPassword } from "@/lib/auth";

export async function PUT(
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
    const { cedula, nombre, telefono, password, activo } = body;

    if (!cedula || !nombre) {
      return NextResponse.json(
        { error: "La cédula y el nombre son obligatorios" },
        { status: 400 }
      );
    }

    // Verificar si la cédula está en uso por otro usuario
    const cedulaEnUso = await prisma.usuario.findFirst({
      where: {
        cedula,
        NOT: { id },
      },
    });

    if (cedulaEnUso) {
      return NextResponse.json(
        { error: "Esa cédula ya pertenece a otro usuario" },
        { status: 400 }
      );
    }

    const updateData: {
      cedula: string;
      nombre: string;
      telefono?: string | null;
      activo?: boolean;
      passwordHash?: string;
    } = {
      cedula,
      nombre,
      telefono: telefono || null,
      activo: Boolean(activo),
    };

    if (password && password.trim() !== "") {
      updateData.passwordHash = await hashPassword(password.trim());
    }

    const actualizado = await prisma.usuario.update({
      where: { id },
      data: updateData,
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
      mensaje: "Datos del ganadero actualizados correctamente",
      ganadero: actualizado,
    });
  } catch (error) {
    console.error("Error al actualizar ganadero:", error);
    return NextResponse.json(
      { error: "Error al actualizar datos en el servidor" },
      { status: 500 }
    );
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

    // Desactivar en lugar de borrar físicamente si tiene entregas asociadas
    const ganadero = await prisma.usuario.findUnique({
      where: { id },
      include: { _count: { select: { entregas: true } } },
    });

    if (!ganadero) {
      return NextResponse.json({ error: "Ganadero no encontrado" }, { status: 404 });
    }

    if (ganadero._count.entregas > 0) {
      // Si tiene entregas, conmutamos estado activo a false
      await prisma.usuario.update({
        where: { id },
        data: { activo: !ganadero.activo },
      });
      return NextResponse.json({
        ok: true,
        mensaje: ganadero.activo
          ? "Ganadero desactivado. Conserva su histórico de entregas."
          : "Ganadero reactivado con éxito.",
      });
    }

    // Si no tiene ninguna entrega asociada, se puede borrar por completo
    await prisma.usuario.delete({
      where: { id },
    });

    return NextResponse.json({
      ok: true,
      mensaje: "Ganadero eliminado definitivamente.",
    });
  } catch (error) {
    console.error("Error al eliminar/desactivar ganadero:", error);
    return NextResponse.json({ error: "Error al procesar la solicitud" }, { status: 500 });
  }
}

