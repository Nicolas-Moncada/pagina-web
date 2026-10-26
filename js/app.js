/**
 * SISTEMA DE CONTROL LECHERO - UTILIDADES GLOBALES
 */

const MESES = [
  "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
  "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
];

const MESES_CORTOS = [
  "Ene", "Feb", "Mar", "Abr", "May", "Jun",
  "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"
];

const DIAS_SEMANA = ["Dom", "Lun", "Mar", "Mié", "Jue", "Vie", "Sáb"];

function getTodayString() {
  const now = new Date();
  const year = now.getFullYear();
  const month = String(now.getMonth() + 1).padStart(2, "0");
  const day = String(now.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function getLastDayOfMonth(year, monthIndex) {
  return new Date(year, monthIndex + 1, 0).getDate();
}

function formatDateSpanish(dateStr) {
  if (!dateStr) return "";
  const parts = dateStr.split("-").map(Number);
  if (parts.length !== 3) return dateStr;
  const [y, m, d] = parts;
  const mesNombre = MESES[m - 1] || "";
  return `${d} de ${mesNombre} de ${y}`;
}

function formatCurrency(val) {
  if (val === null || val === undefined || isNaN(val)) return "$0 COP";
  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "COP",
    maximumFractionDigits: 0,
  }).format(val) + " COP";
}

function getQuincenaForDate(dateStr) {
  if (!dateStr || dateStr < "2027-01-01") {
    return {
      fechaInicio: "2027-01-01",
      fechaFin: "2027-01-15",
      numeroQuincena: 1,
      mes: 1,
      ano: 2027,
      nombreLegible: "1ra Quincena de Enero 2027",
    };
  }
  const parts = dateStr.split("-");
  const ano = parseInt(parts[0], 10);
  const mesIndex = parseInt(parts[1], 10) - 1;
  const dia = parseInt(parts[2], 10);

  const mesNombre = MESES[mesIndex];
  const padMonth = String(mesIndex + 1).padStart(2, "0");

  if (dia <= 15) {
    return {
      fechaInicio: `${ano}-${padMonth}-01`,
      fechaFin: `${ano}-${padMonth}-15`,
      numeroQuincena: 1,
      mes: mesIndex + 1,
      ano: ano,
      nombreLegible: `1ra Quincena de ${mesNombre} ${ano}`,
    };
  } else {
    const ultimoDia = getLastDayOfMonth(ano, mesIndex);
    return {
      fechaInicio: `${ano}-${padMonth}-16`,
      fechaFin: `${ano}-${padMonth}-${String(ultimoDia).padStart(2, "0")}`,
      numeroQuincena: 2,
      mes: mesIndex + 1,
      ano: ano,
      nombreLegible: `2da Quincena de ${mesNombre} ${ano}`,
    };
  }
}

function generarDiasQuincena(fechaInicio, fechaFin) {
  const partsIni = fechaInicio.split("-").map(Number);
  const partsFin = fechaFin.split("-").map(Number);
  const y = partsIni[0];
  const m = partsIni[1];
  const dIni = partsIni[2];
  const dFin = partsFin[2];

  const dias = [];
  for (let d = dIni; d <= dFin; d++) {
    const padD = String(d).padStart(2, "0");
    const padM = String(m).padStart(2, "0");
    const fechaStr = `${y}-${padM}-${padD}`;
    const dateObj = new Date(y, m - 1, d);
    const dayOfWeek = dateObj.getDay();

    dias.push({
      fecha: fechaStr,
      diaNumero: d,
      nombreDia: DIAS_SEMANA[dayOfWeek],
      nombreMes: MESES_CORTOS[m - 1],
      etiquetaCorta: `${d} ${MESES_CORTOS[m - 1]}`,
      etiquetaLarga: `${DIAS_SEMANA[dayOfWeek]} ${d} ${MESES_CORTOS[m - 1]}`,
      esFinDeSemana: dayOfWeek === 0 || dayOfWeek === 6,
    });
  }
  return dias;
}

function getQuincenasRecientes(cantidad = 24) {
  const rangos = [];
  let ano = 2027;
  let mesIndex = 0; // Enero 2027 (Primera quincena del sistema)
  let numQ = 1;

  for (let i = 0; i < cantidad; i++) {
    const padMonth = String(mesIndex + 1).padStart(2, "0");
    const mesNombre = MESES[mesIndex];
    if (numQ === 1) {
      rangos.push({
        fechaInicio: `${ano}-${padMonth}-01`,
        fechaFin: `${ano}-${padMonth}-15`,
        numeroQuincena: 1,
        mes: mesIndex + 1,
        ano: ano,
        nombreLegible: `1ra Quincena de ${mesNombre} ${ano}`,
      });
      numQ = 2;
    } else {
      const ultimoDia = getLastDayOfMonth(ano, mesIndex);
      rangos.push({
        fechaInicio: `${ano}-${padMonth}-16`,
        fechaFin: `${ano}-${padMonth}-${String(ultimoDia).padStart(2, "0")}`,
        numeroQuincena: 2,
        mes: mesIndex + 1,
        ano: ano,
        nombreLegible: `2da Quincena de ${mesNombre} ${ano}`,
      });
      numQ = 1;
      mesIndex++;
      if (mesIndex > 11) {
        mesIndex = 0;
        ano++;
      }
    }
  }

  return rangos;
}

// Alerta flotante moderna
function mostrarNotificacion(mensaje, tipo = "exito") {
  const id = "toast-notif";
  let el = document.getElementById(id);
  if (!el) {
    el = document.createElement("div");
    el.id = id;
    el.className = "fixed bottom-5 right-5 z-50 max-w-md px-5 py-3.5 rounded-2xl shadow-xl font-bold text-sm flex items-center gap-3 transition-all transform duration-300 pointer-events-auto";
    document.body.appendChild(el);
  }

  if (tipo === "exito") {
    el.className = "fixed bottom-5 right-5 z-50 max-w-md px-5 py-3.5 rounded-2xl shadow-xl font-bold text-sm flex items-center gap-3 bg-campo-800 text-white border-2 border-dorado-400";
    el.innerHTML = `<span>✅</span> <div>${mensaje}</div>`;
  } else {
    el.className = "fixed bottom-5 right-5 z-50 max-w-md px-5 py-3.5 rounded-2xl shadow-xl font-bold text-sm flex items-center gap-3 bg-red-700 text-white border-2 border-red-300";
    el.innerHTML = `<span>⚠️</span> <div>${mensaje}</div>`;
  }

  el.style.opacity = "1";
  el.style.transform = "translateY(0)";

  setTimeout(() => {
    el.style.opacity = "0";
    el.style.transform = "translateY(20px)";
  }, 4500);
}

