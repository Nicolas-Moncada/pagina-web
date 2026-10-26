import { NextResponse } from "next/server";
import { prisma } from "@/lib/prisma";
import { getCurrentSession } from "@/lib/session";
import { hashPassword } from "@/lib/auth";
import * as XLSX from "xlsx";

export async function POST(request: Request) {
  const session = await getCurrentSession();
  if (!session || session.rol !== "ADMIN") {
    return NextResponse.json({ error: "No autorizado" }, { status: 403 });
  }

  try {
    const formData = await request.formData();
    const file = formData.get("file") as File | null;
    const tipo = formData.get("tipo") as string | null; // "GANADEROS" | "ENTREGAS"

    if (!file) {
      return NextResponse.json({ error: "No se seleccionó ningún archivo de Excel" }, { status: 400 });
    }

    if (!tipo || (tipo !== "GANADEROS" && tipo !== "ENTREGAS")) {
      return NextResponse.json({ error: "Tipo de importación inválido" }, { status: 400 });
    }

    // Convertir archivo a Buffer
    const arrayBuffer = await file.arrayBuffer();
    const buffer = Buffer.from(arrayBuffer);

    const workbook = XLSX.read(buffer, { type: "buffer" });
    const firstSheetName = workbook.SheetNames[0];
    if (!firstSheetName) {
      return NextResponse.json({ error: "El archivo de Excel no contiene hojas de cálculo" }, { status: 400 });
    }

    const worksheet = workbook.Sheets[firstSheetName];
    const rows = XLSX.utils.sheet_to_json<Record<string, any>>(worksheet, { defval: "" });

    if (rows.length === 0) {
      return NextResponse.json({ error: "La hoja de cálculo está vacía" }, { status: 400 });
    }

    let importados = 0;
    const errores: string[] = [];

    // Helper para buscar nombre de columna flexible (insensible a mayúsculas/tildes)
    const findField = (row: Record<string, any>, candidates: string[]) => {
      const keys = Object.keys(row);
      for (const candidate of candidates) {
        const found = keys.find(k => k.trim().toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "") === candidate);
        if (found && row[found] !== undefined && row[found] !== "") return String(row[found]).trim();
      }
      return "";
    };

    if (tipo === "GANADEROS") {
      for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const filaNum = i + 2; // Fila en Excel considerando encabezado en 1

        const cedula = findField(row, ["cedula", "identificacion", "documento", "id"]);
        const nombre = findField(row, ["nombre", "nombres", "ganadero", "nombre completo"]);
        const telefono = findField(row, ["telefono", "celular", "tel", "contacto"]);
        const clave = findField(row, ["password", "contrasena", "clave"]);

        if (!cedula || !nombre) {
          errores.push(`Fila ${filaNum}: Cédula y Nombre son obligatorios.`);
          continue;
        }

        try {
          const rawPassword = clave || cedula;
          const passwordHash = await hashPassword(rawPassword);

          await prisma.usuario.upsert({
            where: { cedula },
            update: {
              nombre,
              telefono: telefono || null,
              activo: true,
            },
            create: {
              cedula,
              nombre,
              telefono: telefono || null,
              passwordHash,
              rol: "GANADERO",
              activo: true,
            },
          });
          importados++;
        } catch (e: any) {
          errores.push(`Fila ${filaNum} (Cédula ${cedula}): ${e.message || "Error al guardar"}`);
        }
      }
    } else if (tipo === "ENTREGAS") {
      // Importar Entregas
      for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const filaNum = i + 2;

        const cedula = findField(row, ["cedula", "identificacion", "ganadero"]);
        let fecha = findField(row, ["fecha", "dia", "date"]);
        let turnoRaw = findField(row, ["turno", "jornada", "horario"]).toUpperCase();
        const litrosRaw = findField(row, ["litros", "cantidad", "leche"]);

        if (!cedula || !fecha || !litrosRaw) {
          errores.push(`Fila ${filaNum}: Cédula, Fecha y Litros son obligatorios.`);
          continue;
        }

        // Normalizar fecha si viene de formato numérico de Excel
        if (/^\d{5}$/.test(fecha)) {
          const dateObj = XLSX.SSF.parse_date_code(Number(fecha));
          fecha = `${dateObj.y}-${String(dateObj.m).padStart(2, "0")}-${String(dateObj.d).padStart(2, "0")}`;
        }

        // Validar formato YYYY-MM-DD
        if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) {
          errores.push(`Fila ${filaNum} (Cédula ${cedula}): Fecha inválida "${fecha}". Debe ser AAAA-MM-DD.`);
          continue;
        }

        // Determinar turno
        let turno: "MANANA" | "TARDE" = "MANANA";
        if (turnoRaw.includes("TARDE") || turnoRaw.includes("PM") || turnoRaw === "T") {
          turno = "TARDE";
        } else if (turnoRaw.includes("MANANA") || turnoRaw.includes("MAÑANA") || turnoRaw.includes("AM") || turnoRaw === "M") {
          turno = "MANANA";
        }

        const litros = parseFloat(litrosRaw.replace(",", "."));
        if (isNaN(litros) || litros <= 0) {
          errores.push(`Fila ${filaNum} (Cédula ${cedula}): Litros inválidos "${litrosRaw}". Debe ser un número positivo.`);
          continue;
        }

        // Buscar usuario por cédula
        const usuario = await prisma.usuario.findUnique({
          where: { cedula },
        });

        if (!usuario) {
          errores.push(`Fila ${filaNum}: No existe ningún ganadero registrado con la cédula ${cedula}.`);
          continue;
        }

        try {
          await prisma.entrega.upsert({
            where: {
              usuarioId_fecha_turno: {
                usuarioId: usuario.id,
                fecha,
                turno,
              },
            },
            update: { litros },
            create: {
              usuarioId: usuario.id,
              fecha,
              turno,
              litros,
            },
          });
          importados++;
        } catch (e: any) {
          errores.push(`Fila ${filaNum} (Cédula ${cedula}): ${e.message || "Error al guardar entrega"}`);
        }
      }
    }

    return NextResponse.json({
      ok: true,
      mensaje: `Proceso finalizado: ${importados} registros importados correctamente.`,
      importados,
      errores,
    });
  } catch (error: any) {
    console.error("Error en importación de Excel:", error);
    return NextResponse.json(
      { error: "Error al procesar el archivo Excel: " + (error.message || "Formato inválido") },
      { status: 500 }
    );
  }
}

