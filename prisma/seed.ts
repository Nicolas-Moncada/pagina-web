import { PrismaClient, Rol, Turno } from "@prisma/client";
import bcrypt from "bcryptjs";

const prisma = new PrismaClient();

async function hashPassword(plain: string) {
  const salt = await bcrypt.genSalt(10);
  return bcrypt.hash(plain, salt);
}

function formatDate(date: Date): string {
  const y = date.getFullYear();
  const m = String(date.getMonth() + 1).padStart(2, "0");
  const d = String(date.getDate()).padStart(2, "0");
  return `${y}-${m}-${d}`;
}

async function main() {
  console.log("🌱 Iniciando población de datos de prueba (Seed)...");

  // 1. Limpiar datos existentes de prueba para evitar duplicados en seed
  await prisma.entrega.deleteMany();
  await prisma.consulta.deleteMany();
  await prisma.quincena.deleteMany();
  await prisma.usuario.deleteMany();

  console.log("🧹 Base de datos limpia.");

  // 2. Crear 1 Administrador
  const adminPasswordHash = await hashPassword("123456");
  const admin = await prisma.usuario.create({
    data: {
      cedula: "79164451",
      nombre: "Administrador General",
      telefono: "3001234567",
      passwordHash: adminPasswordHash,
      rol: Rol.ADMIN,
      activo: true,
    },
  });
  console.log("👤 Admin creado: Cédula 79164451 / Clave 123456");

  // 3. Crear Ganaderos de ejemplo
  const defaultGanaderoPasswordHash = await hashPassword("1234");
  const hildaPasswordHash = await hashPassword("123456");

  const ganaderosData = [
    { cedula: "20444983", nombre: "Hilda Inés Montaño", telefono: "3104449830", hash: hildaPasswordHash, claveVisible: "123456" },
    { cedula: "1001", nombre: "Don Carlos Alberto Mendoza", telefono: "3105551001", hash: defaultGanaderoPasswordHash, claveVisible: "1234" },
    { cedula: "1002", nombre: "Doña María Elena Restrepo", telefono: "3125551002", hash: defaultGanaderoPasswordHash, claveVisible: "1234" },
    { cedula: "1003", nombre: "Don José Vicente Morales", telefono: "3155551003", hash: defaultGanaderoPasswordHash, claveVisible: "1234" },
    { cedula: "1004", nombre: "Doña Rosa Inés Beltrán", telefono: "3185551004", hash: defaultGanaderoPasswordHash, claveVisible: "1234" },
  ];

  const ganaderosCreados = [];
  for (const g of ganaderosData) {
    const ganadero = await prisma.usuario.create({
      data: {
        cedula: g.cedula,
        nombre: g.nombre,
        telefono: g.telefono,
        passwordHash: g.hash,
        rol: Rol.GANADERO,
        activo: true,
      },
    });
    ganaderosCreados.push(ganadero);
    console.log(`🧑‍🌾 Ganadero creado: ${g.nombre} (Cédula: ${g.cedula} / Clave: ${g.claveVisible})`);
  }

  // 4. Crear Quincenas (Quincena 1 cerrada y Quincena 2 en curso de Octubre 2026)
  // Quincena 1 (del 1 al 15 de Octubre 2026): cerrada con precio de $2.150 COP
  const qAnterior = await prisma.quincena.create({
    data: {
      fechaInicio: "2026-10-01",
      fechaFin: "2026-10-15",
      precioLitro: 2150,
      cerrada: true,
    },
  });

  // Quincena 2 (del 16 al 31 de Octubre 2026): en curso con precio oficial de $2.250 COP
  const qActual = await prisma.quincena.create({
    data: {
      fechaInicio: "2026-10-16",
      fechaFin: "2026-10-31",
      precioLitro: 2250,
      cerrada: false,
    },
  });
  console.log("📅 Quincenas registradas: 1ra Quincena Octubre ($2.150) y 2da Quincena Octubre ($2.250).");

  // 5. Generar entregas para los últimos 15 días (del día actual hacia atrás)
  const hoy = new Date();
  let entregasContador = 0;

  for (let i = 0; i < 15; i++) {
    const fechaObj = new Date(hoy);
    fechaObj.setDate(hoy.getDate() - i);
    const fechaStr = formatDate(fechaObj);

    for (let gIndex = 0; gIndex < ganaderosCreados.length; gIndex++) {
      const ganadero = ganaderosCreados[gIndex];

      // Valores realistas variados por ganadero
      const factor = (gIndex + 1) * 12; // variación entre productores
      const baseManana = 35 + factor + ((i * 3) % 15);
      const baseTarde = 25 + factor * 0.8 + ((i * 2) % 10);

      const litrosManana = Number(baseManana.toFixed(1));
      const litrosTarde = Number(baseTarde.toFixed(1));

      // Entrega Mañana
      await prisma.entrega.create({
        data: {
          usuarioId: ganadero.id,
          fecha: fechaStr,
          turno: Turno.MANANA,
          litros: litrosManana,
        },
      });

      // Entrega Tarde
      await prisma.entrega.create({
        data: {
          usuarioId: ganadero.id,
          fecha: fechaStr,
          turno: Turno.TARDE,
          litros: litrosTarde,
        },
      });

      entregasContador += 2;
    }
  }

  console.log(`🥛 ${entregasContador} entregas de leche registradas exitosamente (15 días × 4 ganaderos × 2 turnos).`);

  // 6. Consultas de prueba para el buzón de la administración
  await prisma.consulta.createMany({
    data: [
      {
        nombre: "Guillermo Pérez",
        contacto: "3118904567",
        mensaje: "Buenas tardes, tengo una finca lechera en la vereda El Retiro con 25 vacas de ordeño. Me gustaría conocer los requisitos para afiliarme a la asociación y la ruta del carro cisterna.",
        atendida: false,
      },
      {
        nombre: "Sandra Milena Gómez",
        contacto: "sandra.gomez@campo.org",
        mensaje: "Cordial saludo. Queremos coordinar una jornada de capacitación técnica en buenas prácticas de ordeño y manejo de pastos para los asociados.",
        atendida: true,
      },
    ],
  });

  console.log("📬 Consultas de prueba registradas en el buzón administrativo.");
  console.log("✅ Seed completado con éxito al 100%.");
}

main()
  .catch((e) => {
    console.error("❌ Error durante el seed:", e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });

