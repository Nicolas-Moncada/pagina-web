/**
 * Utilidades para manejo de fechas y cálculo de quincenas
 * Reglas de negocio:
 * Quincena 1: del 1 al 15 del mes
 * Quincena 2: del 16 al último día del mes
 */

export function getTodayString(): string {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

export function getLastDayOfMonth(year: number, monthIndex: number): number {
  // monthIndex 0-based: monthIndex + 1 day 0 gives last day of monthIndex
  return new Date(year, monthIndex + 1, 0).getDate();
}

export interface QuincenaRango {
  fechaInicio: string;
  fechaFin: string;
  numeroQuincena: 1 | 2;
  mes: number;
  ano: number;
  nombreLegible: string;
}

const MESES = [
  "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
  "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
];

export function getQuincenaForDate(dateStr?: string): QuincenaRango {
  const [yearStr, monthStr, dayStr] = (dateStr || getTodayString()).split("-");
  const ano = parseInt(yearStr, 10);
  const mesIndex = parseInt(monthStr, 10) - 1;
  const dia = parseInt(dayStr, 10);

  const mesNombre = MESES[mesIndex];
  const padMonth = String(mesIndex + 1).padStart(2, "0");

  if (dia <= 15) {
    return {
      fechaInicio: `${ano}-${padMonth}-01`,
      fechaFin: `${ano}-${padMonth}-15`,
      numeroQuincena: 1,
      mes: mesIndex + 1,
      ano,
      nombreLegible: `1ra Quincena de ${mesNombre} ${ano}`,
    };
  } else {
    const ultimoDia = getLastDayOfMonth(ano, mesIndex);
    return {
      fechaInicio: `${ano}-${padMonth}-16`,
      fechaFin: `${ano}-${padMonth}-${String(ultimoDia).padStart(2, "0")}`,
      numeroQuincena: 2,
      mes: mesIndex + 1,
      ano,
      nombreLegible: `2da Quincena de ${mesNombre} ${ano}`,
    };
  }
}

export function formatDateSpanish(dateStr: string): string {
  if (!dateStr) return "";
  const [y, m, d] = dateStr.split("-").map(Number);
  if (!y || !m || !d) return dateStr;
  const mesNombre = MESES[m - 1] || "";
  return `${d} de ${mesNombre} de ${y}`;
}

export interface DiaColumna {
  fecha: string;         // YYYY-MM-DD
  diaNumero: number;     // 16
  nombreDia: string;     // Vie
  nombreMes: string;     // Oct
  etiquetaCorta: string; // 16 Oct
  etiquetaLarga: string; // Vie 16 Oct
  esFinDeSemana: boolean;
}

export function generarDiasQuincena(fechaInicio: string, fechaFin: string): DiaColumna[] {
  const [yIni, mIni, dIni] = fechaInicio.split("-").map(Number);
  const [, , dFin] = fechaFin.split("-").map(Number);

  const diasSemana = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];
  const mesesCortos = [
    "Ene", "Feb", "Mar", "Abr", "May", "Jun",
    "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"
  ];

  const dias: DiaColumna[] = [];
  for (let d = dIni; d <= dFin; d++) {
    const fechaStr = `${yIni}-${String(mIni).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
    const fechaObj = new Date(yIni, mIni - 1, d);
    const dayOfWeek = fechaObj.getDay();
    dias.push({
      fecha: fechaStr,
      diaNumero: d,
      nombreDia: diasSemana[dayOfWeek],
      nombreMes: mesesCortos[mIni - 1],
      etiquetaCorta: `${d} ${mesesCortos[mIni - 1]}`,
      etiquetaLarga: `${diasSemana[dayOfWeek]} ${d} ${mesesCortos[mIni - 1]}`,
      esFinDeSemana: dayOfWeek === 0 || dayOfWeek === 6,
    });
  }
  return dias;
}

export function getQuincenasRecientes(cantidad = 12): QuincenaRango[] {
  const rangos: QuincenaRango[] = [];
  const hoy = new Date();
  let ano = hoy.getFullYear();
  let mesIndex = hoy.getMonth();
  let numQ: 1 | 2 = hoy.getDate() <= 15 ? 1 : 2;

  // Incluir la próxima quincena para permitir anticipación
  if (numQ === 1) {
    numQ = 2;
  } else {
    numQ = 1;
    mesIndex++;
    if (mesIndex > 11) {
      mesIndex = 0;
      ano++;
    }
  }

  for (let i = 0; i < cantidad + 1; i++) {
    const padMonth = String(mesIndex + 1).padStart(2, "0");
    const mesNombre = MESES[mesIndex];
    if (numQ === 1) {
      rangos.push({
        fechaInicio: `${ano}-${padMonth}-01`,
        fechaFin: `${ano}-${padMonth}-15`,
        numeroQuincena: 1,
        mes: mesIndex + 1,
        ano,
        nombreLegible: `1ra Quincena de ${mesNombre} ${ano}`,
      });
      numQ = 2;
      mesIndex--;
      if (mesIndex < 0) {
        mesIndex = 11;
        ano--;
      }
    } else {
      const ultimoDia = getLastDayOfMonth(ano, mesIndex);
      rangos.push({
        fechaInicio: `${ano}-${padMonth}-16`,
        fechaFin: `${ano}-${padMonth}-${String(ultimoDia).padStart(2, "0")}`,
        numeroQuincena: 2,
        mes: mesIndex + 1,
        ano,
        nombreLegible: `2da Quincena de ${mesNombre} ${ano}`,
      });
      numQ = 1;
    }
  }

  return rangos;
}

