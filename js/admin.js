/**
 * LÓGICA DEL PANEL DEL ADMINISTRADOR - VERSIÓN LIMPIA Y ROBUSTA
 * Gestión de Planilla Quincenal, Asociados, Precios Oficiales y Buzón
 */

// Limpiar rastros de datos demo previos en localStorage
try {
  const gOld = localStorage.getItem("asociados_local");
  if (gOld && (gOld.includes("1001") || gOld.includes("Carlos Alberto") || gOld.includes("usr_ganadero_1001") || gOld.includes("Hilda"))) {
    localStorage.removeItem("asociados_local");
    localStorage.removeItem("entregas_local");
  }
} catch (e) {}

// Variables globales de estado
let ganaderos = [];
let entregasMap = {};
let quincenaSeleccionada = null;
let busquedaGanadero = "";
let busquedaAsociados = "";
let quincenasDisponibles = [];
let turnoSeleccionadoForm = "MANANA";

document.addEventListener("DOMContentLoaded", () => {
  quincenasDisponibles = getQuincenasRecientes(24);
  quincenaSeleccionada = quincenasDisponibles[0] || getQuincenaForDate("2027-01-01");

  // Carga de estado local
  const ganaderosGuardados = localStorage.getItem("asociados_local");
  if (ganaderosGuardados) {
    try { ganaderos = JSON.parse(ganaderosGuardados); } catch (e) { ganaderos = []; }
  } else {
    ganaderos = [];
  }

  const entregasGuardadas = localStorage.getItem("entregas_local");
  if (entregasGuardadas) {
    try { entregasMap = JSON.parse(entregasGuardadas); } catch (e) { entregasMap = {}; }
  } else {
    entregasMap = {};
  }

  // Inicializar selectores y vistas de inmediato
  inicializarSelectorQuincena();
  renderizarPlanilla();
  renderizarTablaGestionAsociados();
  poblarSelectoresFormularios();
  inicializarEventosBotonesYPestanas();

  // Sincronización en segundo plano con la API de PHP / MySQL
  sincronizarConServidor();
});

// Sincronización en segundo plano con PHP/MySQL
async function sincronizarConServidor() {
  try {
    // 1. Obtener ganaderos
    const resG = await fetch("./api/ganaderos.php?fechaInicio=" + quincenaSeleccionada.fechaInicio + "&fechaFin=" + quincenaSeleccionada.fechaFin);
    if (resG.ok) {
      const dataG = await resG.json();
      if (dataG.ganaderos && dataG.ganaderos.length > 0) {
        ganaderos = dataG.ganaderos;
        localStorage.setItem("asociados_local", JSON.stringify(ganaderos));
        renderizarTablaGestionAsociados();
        poblarSelectoresFormularios();
      }
    }

    // 2. Obtener entregas
    const resE = await fetch(`./api/entregas.php?fechaInicio=${quincenaSeleccionada.fechaInicio}&fechaFin=${quincenaSeleccionada.fechaFin}`);
    if (resE.ok) {
      const dataE = await resE.json();
      if (dataE.entregas && dataE.entregas.length > 0) {
        dataE.entregas.forEach((e) => {
          entregasMap[`${e.usuarioId}_${e.fecha}_${e.turno}`] = {
            id: e.id,
            litros: Number(e.litros)
          };
        });
        localStorage.setItem("entregas_local", JSON.stringify(entregasMap));
      }
    }

    // Volver a renderizar con datos frescos del servidor
    renderizarPlanilla();
  } catch (err) {
    console.warn("Modo local activo (API no respondió aún o conexión local):", err);
  }
}

// ----------------------------------------------------------------
// 1. SELECTOR Y CONTROLES DE QUINCENA
// ----------------------------------------------------------------
function inicializarSelectorQuincena() {
  const sel = document.getElementById("selector-quincena-admin");
  const ant = document.getElementById("btn-quincena-ant");
  const sig = document.getElementById("btn-quincena-sig");

  if (sel) {
    sel.innerHTML = "";
    quincenasDisponibles.forEach((q) => {
      const opt = document.createElement("option");
      opt.value = `${q.fechaInicio}_${q.fechaFin}`;
      opt.textContent = `${q.nombreLegible} (${formatDateSpanish(q.fechaInicio)} al ${formatDateSpanish(q.fechaFin)})`;
      if (q.fechaInicio === quincenaSeleccionada.fechaInicio && q.fechaFin === quincenaSeleccionada.fechaFin) {
        opt.selected = true;
      }
      sel.appendChild(opt);
    });

    sel.addEventListener("change", (e) => {
      const [fIni, fFin] = e.target.value.split("_");
      const found = quincenasDisponibles.find((q) => q.fechaInicio === fIni && q.fechaFin === fFin);
      if (found) {
        quincenaSeleccionada = found;
        actualizarTextosPeriodo();
        renderizarPlanilla();
        sincronizarConServidor();
      }
    });
  }

  if (ant) {
    ant.addEventListener("click", () => cambiarQuincenaRelativa(-1));
  }
  if (sig) {
    sig.addEventListener("click", () => cambiarQuincenaRelativa(1));
  }

  actualizarTextosPeriodo();
}

function cambiarQuincenaRelativa(dir) {
  const idx = quincenasDisponibles.findIndex(
    (q) => q.fechaInicio === quincenaSeleccionada.fechaInicio && q.fechaFin === quincenaSeleccionada.fechaFin
  );
  if (idx !== -1) {
    const nuevoIdx = idx + dir;
    if (nuevoIdx >= 0 && nuevoIdx < quincenasDisponibles.length) {
      quincenaSeleccionada = quincenasDisponibles[nuevoIdx];
      const sel = document.getElementById("selector-quincena-admin");
      if (sel) sel.value = `${quincenaSeleccionada.fechaInicio}_${quincenaSeleccionada.fechaFin}`;
      actualizarTextosPeriodo();
      renderizarPlanilla();
      sincronizarConServidor();
    }
  }
}

function actualizarTextosPeriodo() {
  const rango = document.getElementById("rango-fechas-admin");
  const nombre = document.getElementById("nombre-quincena-admin");
  if (rango) {
    rango.textContent = `${formatDateSpanish(quincenaSeleccionada.fechaInicio)} al ${formatDateSpanish(quincenaSeleccionada.fechaFin)}`;
  }
  if (nombre) {
    nombre.textContent = quincenaSeleccionada.nombreLegible;
  }
}

// ----------------------------------------------------------------
// 2. RENDERIZAR PLANILLA MATRICIAL EXCEL (POR FECHAS Y TURNOS)
// ----------------------------------------------------------------
function renderizarPlanilla() {
  const cont = document.getElementById("contenedor-tabla-admin");
  if (!cont) return;

  const hoyStr = getTodayString();
  const dias = generarDiasQuincena(quincenaSeleccionada.fechaInicio, quincenaSeleccionada.fechaFin);

  // Filtrar asociados por búsqueda
  const filtrados = ganaderos.filter((g) => {
    if (!busquedaGanadero) return true;
    const term = busquedaGanadero.toLowerCase();
    return g.nombre.toLowerCase().includes(term) || g.cedula.includes(term);
  });

  const contador = document.getElementById("contador-ganaderos");
  if (contador) {
    contador.textContent = `${filtrados.length} asociados en planilla`;
  }

  // Totales de columna
  const totalesDiarios = dias.map((d) => {
    let tManana = 0;
    let tTarde = 0;
    filtrados.forEach((g) => {
      tManana += entregasMap[`${g.id}_${d.fecha}_MANANA`]?.litros || 0;
      tTarde += entregasMap[`${g.id}_${d.fecha}_TARDE`]?.litros || 0;
    });
    return { fecha: d.fecha, manana: tManana, tarde: tTarde, total: tManana + tTarde };
  });

  const granTotalQuincena = totalesDiarios.reduce((acc, curr) => acc + curr.total, 0);

  let html = `
    <div class="overflow-x-auto max-h-[70vh] shadow-inner relative">
      <table class="w-full text-left text-xs border-collapse">
        <thead class="sticky top-0 z-30 shadow-sm">
          <!-- Nivel 1 Superior: Fechas -->
          <tr class="bg-campo-900 text-white uppercase tracking-wider font-extrabold text-[11px]">
            <th rowspan="2" class="sticky left-0 z-40 bg-campo-950 px-4 py-3 border-r-2 border-campo-700 min-w-[220px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.3)] align-middle">
              ASOCIADO / CÉDULA
            </th>`;

  dias.forEach((d) => {
    const esHoy = d.fecha === hoyStr;
    html += `
      <th colspan="2" class="text-center py-2 px-2 border-r-2 border-campo-700 min-w-[125px] ${esHoy ? 'bg-campo-800 ring-2 ring-inset ring-dorado-400' : 'bg-campo-900'}">
        <div class="font-black text-white text-xs">${d.etiquetaLarga}</div>
        ${esHoy ? '<span class="text-[9px] font-black uppercase tracking-wider text-dorado-300 bg-black/40 px-2 py-0.2 rounded-full mt-0.5 inline-block">Hoy</span>' : ''}
      </th>`;
  });

  html += `
            <th rowspan="2" class="sticky right-0 z-40 bg-campo-950 px-4 py-3 border-l-2 border-campo-700 min-w-[130px] text-right text-dorado-300 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.3)] align-middle">
              TOTAL QUINCENA
            </th>
          </tr>
          <!-- Nivel 2 Inferior: Turnos -->
          <tr class="bg-campo-800 text-[10px] font-bold">`;

  dias.forEach(() => {
    html += `
      <th class="py-1 px-1 text-center bg-amber-100 text-amber-950 border-r border-amber-200">☀️ Mañana</th>
      <th class="py-1 px-1 text-center bg-orange-100 text-orange-950 border-r-2 border-campo-600">🌅 Tarde</th>`;
  });

  html += `
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200 bg-white">`;

  if (filtrados.length === 0) {
    html += `
      <tr>
        <td colspan="${dias.length * 2 + 2}" class="py-12 text-center text-gray-500 font-bold">
          No se encontraron asociados con ese criterio.
        </td>
      </tr>`;
  } else {
    filtrados.forEach((g) => {
      let totalGanadero = 0;
      dias.forEach((d) => {
        totalGanadero += entregasMap[`${g.id}_${d.fecha}_MANANA`]?.litros || 0;
        totalGanadero += entregasMap[`${g.id}_${d.fecha}_TARDE`]?.litros || 0;
      });

      html += `
        <tr class="hover:bg-campo-50/50 transition-colors group">
          <td class="sticky left-0 z-20 bg-white group-hover:bg-campo-50 px-4 py-3 border-r-2 border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] whitespace-nowrap">
            <div class="font-bold text-gray-900 text-xs sm:text-sm">${g.nombre}</div>
            <div class="text-[11px] font-mono text-gray-500 flex items-center gap-1.5">
              <span>C.C. ${g.cedula}</span>
              ${g.finca ? `<span class="text-campo-700 font-semibold">• ${g.finca}</span>` : ''}
              ${!g.activo ? '<span class="text-[9px] bg-red-100 text-red-700 px-1.5 rounded font-bold">Inactivo</span>' : ''}
            </div>
          </td>`;

      dias.forEach((d) => {
        const itemM = entregasMap[`${g.id}_${d.fecha}_MANANA`];
        const itemT = entregasMap[`${g.id}_${d.fecha}_TARDE`];

        const valM = itemM && itemM.litros > 0 ? itemM.litros.toFixed(1) : "-";
        const valT = itemT && itemT.litros > 0 ? itemT.litros.toFixed(1) : "-";

        html += `
          <!-- Celda Mañana (Clic para Editar) -->
          <td class="p-1 text-center border-r border-gray-200 bg-amber-50/20 hover:bg-amber-100/60 cursor-pointer select-none transition-colors celda-pesaje"
              data-usuario="${g.id}" data-nombre="${g.nombre}" data-fecha="${d.fecha}" data-turno="MANANA" data-litros="${itemM?.litros || ''}" data-id="${itemM?.id || ''}"
              title="Clic para editar: ${g.nombre} - ${d.etiquetaLarga} (Mañana)">
            <span class="font-extrabold ${valM !== '-' ? 'text-amber-950' : 'text-gray-300'} text-xs">${valM}</span>
          </td>

          <!-- Celda Tarde (Clic para Editar) -->
          <td class="p-1 text-center border-r-2 border-gray-300 bg-orange-50/20 hover:bg-orange-100/60 cursor-pointer select-none transition-colors celda-pesaje"
              data-usuario="${g.id}" data-nombre="${g.nombre}" data-fecha="${d.fecha}" data-turno="TARDE" data-litros="${itemT?.litros || ''}" data-id="${itemT?.id || ''}"
              title="Clic para editar: ${g.nombre} - ${d.etiquetaLarga} (Tarde)">
            <span class="font-extrabold ${valT !== '-' ? 'text-orange-950' : 'text-gray-300'} text-xs">${valT}</span>
          </td>`;
      });

      html += `
          <td class="sticky right-0 z-20 bg-campo-50/95 group-hover:bg-campo-100/90 px-4 py-3 border-l-2 border-campo-300 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.08)] text-right whitespace-nowrap">
            <span class="font-black text-campo-950 text-xs sm:text-sm">${totalGanadero.toFixed(1)} L</span>
          </td>
        </tr>`;
    });
  }

  html += `
        </tbody>
        <tfoot class="sticky bottom-0 z-30 shadow-md font-extrabold">
          <tr class="bg-gray-100 text-gray-900 border-t-2 border-campo-300">
            <td class="sticky left-0 z-40 bg-gray-200 px-4 py-3 border-r-2 border-gray-400 text-xs uppercase tracking-wider shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]">
              TOTALES POR TURNO (L)
            </td>`;

  totalesDiarios.forEach((t) => {
    html += `
      <td class="py-2.5 px-1 text-center bg-amber-100/80 text-amber-950 border-r border-amber-300 text-xs font-black">
        ${t.manana > 0 ? t.manana.toFixed(1) : '-'}
      </td>
      <td class="py-2.5 px-1 text-center bg-orange-100/80 text-orange-950 border-r-2 border-gray-400 text-xs font-black">
        ${t.tarde > 0 ? t.tarde.toFixed(1) : '-'}
      </td>`;
  });

  html += `
            <td class="sticky right-0 z-40 bg-campo-950 text-dorado-300 px-4 py-3 border-l-2 border-campo-700 text-right text-xs sm:text-sm font-black shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.2)] whitespace-nowrap">
              ${granTotalQuincena.toFixed(1)} L
            </td>
          </tr>
        </tfoot>
      </table>
    </div>`;

  cont.innerHTML = html;

  // Asignar listeners de edición inline
  cont.querySelectorAll(".celda-pesaje").forEach((celda) => {
    celda.addEventListener("click", () => activarEdicionInline(celda));
  });
}

// ----------------------------------------------------------------
// 3. EDITOR INLINE DE CELDAS (CLIC DIRECTO)
// ----------------------------------------------------------------
function activarEdicionInline(celda) {
  if (celda.querySelector("input")) return;

  const usuarioId = celda.dataset.usuario;
  const nombre = celda.dataset.nombre;
  const fecha = celda.dataset.fecha;
  const turno = celda.dataset.turno;
  const litrosActuales = celda.dataset.litros;
  const entregaId = celda.dataset.id;

  const valorOriginal = celda.innerHTML;
  celda.classList.add("ring-2", "ring-campo-600", "bg-white");

  celda.innerHTML = `
    <div class="flex items-center justify-center gap-1 p-0.5">
      <input type="number" step="0.1" min="0" value="${litrosActuales || ''}" 
             class="w-14 px-1 py-1 text-xs font-bold text-center border-2 border-campo-600 rounded bg-white shadow-sm focus:outline-none input-inline" />
      <button type="button" class="p-1 bg-campo-700 hover:bg-campo-800 text-white rounded text-[10px] font-bold btn-guardar-inline" title="Guardar">✓</button>
      ${entregaId ? '<button type="button" class="p-1 bg-red-600 hover:bg-red-700 text-white rounded text-[10px] btn-borrar-inline" title="Eliminar">🗑️</button>' : ''}
      <button type="button" class="p-1 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded text-[10px] btn-cancelar-inline" title="Cancelar">✕</button>
    </div>`;

  const input = celda.querySelector(".input-inline");
  const btnGuardar = celda.querySelector(".btn-guardar-inline");
  const btnBorrar = celda.querySelector(".btn-borrar-inline");
  const btnCancelar = celda.querySelector(".btn-cancelar-inline");

  input.focus();
  input.select();

  const guardar = async () => {
    const val = parseFloat(input.value.replace(",", "."));
    if (isNaN(val) || val <= 0) {
      alert("Ingresa una cantidad de litros válida mayor a 0");
      return;
    }

    // 1. Guardado inmediato en LocalStorage
    entregasMap[`${usuarioId}_${fecha}_${turno}`] = { id: entregaId || `ent_${Date.now()}`, litros: val };
    localStorage.setItem("entregas_local", JSON.stringify(entregasMap));
    mostrarNotificacion(`✅ Actualizado: ${val.toFixed(1)} L para ${nombre}`);
    renderizarPlanilla();

    // 2. Sincronización en segundo plano con el servidor
    try {
      await fetch("./api/entregas.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ usuarioId, fecha, turno, litros: val }),
      });
    } catch (e) {
      console.warn("Guardado en local, pendiente sinc con servidor");
    }
  };

  const borrar = async () => {
    if (!confirm(`¿Eliminar pesaje de ${turno === 'MANANA' ? 'Mañana' : 'Tarde'} para ${nombre}?`)) return;

    delete entregasMap[`${usuarioId}_${fecha}_${turno}`];
    localStorage.setItem("entregas_local", JSON.stringify(entregasMap));
    mostrarNotificacion(`🗑️ Pesaje eliminado para ${nombre}`);
    renderizarPlanilla();

    try {
      await fetch(`./api/entregas.php?usuarioId=${usuarioId}&fecha=${fecha}&turno=${turno}`, { method: "DELETE" });
    } catch (e) {
      console.warn("Eliminado en local");
    }
  };

  btnGuardar.addEventListener("click", (e) => { e.stopPropagation(); guardar(); });
  if (btnBorrar) btnBorrar.addEventListener("click", (e) => { e.stopPropagation(); borrar(); });
  btnCancelar.addEventListener("click", (e) => { e.stopPropagation(); celda.innerHTML = valorOriginal; celda.classList.remove("ring-2", "ring-campo-600"); });

  input.addEventListener("keydown", (e) => {
    if (e.key === "Enter") guardar();
    if (e.key === "Escape") { celda.innerHTML = valorOriginal; celda.classList.remove("ring-2", "ring-campo-600"); }
  });
}

// ----------------------------------------------------------------
// 4. TABLA DE GESTIÓN DE ASOCIADOS (PESTAÑA 2)
// ----------------------------------------------------------------
function renderizarTablaGestionAsociados() {
  const tbody = document.getElementById("tbody-ganaderos-modulo");
  if (!tbody) return;

  const dias = generarDiasQuincena(quincenaSeleccionada.fechaInicio, quincenaSeleccionada.fechaFin);

  const filtrados = ganaderos.filter((g) => {
    if (!busquedaAsociados) return true;
    const term = busquedaAsociados.toLowerCase();
    return g.nombre.toLowerCase().includes(term) || g.cedula.includes(term) || (g.finca && g.finca.toLowerCase().includes(term));
  });

  if (filtrados.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="px-6 py-8 text-center text-gray-400 font-bold">No se encontraron ganaderos con ese criterio.</td></tr>`;
    return;
  }

  let html = "";
  filtrados.forEach((g) => {
    let litrosQuincena = 0;
    dias.forEach((d) => {
      litrosQuincena += entregasMap[`${g.id}_${d.fecha}_MANANA`]?.litros || 0;
      litrosQuincena += entregasMap[`${g.id}_${d.fecha}_TARDE`]?.litros || 0;
    });

    const esActivo = g.activo === 1 || g.activo === true || g.activo === "1";

    html += `
      <tr class="hover:bg-campo-50/50 transition-colors">
        <td class="px-6 py-4 font-bold text-campo-950">${g.nombre}</td>
        <td class="px-6 py-4 font-mono font-bold text-gray-700">${g.cedula}</td>
        <td class="px-6 py-4">
          <div class="font-semibold text-gray-800">${g.finca || 'Hacienda Asociada'}</div>
          <div class="text-xs text-gray-500">${g.telefono || 'Sin teléfono'}</div>
        </td>
        <td class="px-6 py-4 text-right font-black text-campo-900 text-sm">
          ${litrosQuincena.toFixed(1)} L
        </td>
        <td class="px-6 py-4 text-center">
          ${esActivo 
            ? '<span class="bg-green-100 text-green-800 text-xs px-2.5 py-1 rounded-full font-bold inline-flex items-center gap-1"><span>●</span> Activo</span>' 
            : '<span class="bg-red-100 text-red-800 text-xs px-2.5 py-1 rounded-full font-bold inline-flex items-center gap-1"><span>○</span> Inactivo</span>'}
        </td>
        <td class="px-6 py-4 text-center">
          <div class="flex items-center justify-center gap-2">
            <button type="button" onclick="toggleActivoAsociado('${g.id}', ${esActivo ? 0 : 1})" 
                    class="px-2.5 py-1 ${esActivo ? 'bg-amber-100 hover:bg-amber-200 text-amber-900' : 'bg-green-100 hover:bg-green-200 text-green-900'} rounded-lg text-xs font-bold transition-colors"
                    title="${esActivo ? 'Desactivar asociado (bloquear acceso)' : 'Activar asociado'}">
              ${esActivo ? '⏸️ Desactivar' : '▶️ Activar'}
            </button>
            <button type="button" onclick="editarAsociado('${g.id}')" 
                    class="px-2.5 py-1 bg-campo-100 hover:bg-campo-200 text-campo-900 rounded-lg text-xs font-bold transition-colors">
              ✏️ Editar
            </button>
            <button type="button" onclick="eliminarAsociado('${g.id}', '${g.nombre}')" 
                    class="px-2.5 py-1 bg-red-100 hover:bg-red-200 text-red-800 rounded-lg text-xs font-bold transition-colors">
              🗑️ Eliminar
            </button>
          </div>
        </td>
      </tr>`;
  });

  tbody.innerHTML = html;
}

// Activar o Desactivar asociado
window.toggleActivoAsociado = async function(id, nuevoEstado) {
  const g = ganaderos.find((x) => x.id === id);
  if (!g) return;

  g.activo = nuevoEstado;
  localStorage.setItem("asociados_local", JSON.stringify(ganaderos));
  mostrarNotificacion(`Estado de ${g.nombre}: ${nuevoEstado ? 'Activo' : 'Inactivo'}`);
  renderizarTablaGestionAsociados();
  renderizarPlanilla();

  try {
    await fetch("./api/ganaderos.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ id: g.id, cedula: g.cedula, nombre: g.nombre, telefono: g.telefono, finca: g.finca, activo: nuevoEstado })
    });
  } catch (e) {
    console.warn("Estado guardado localmente");
  }
};

// Editar asociado
window.editarAsociado = function(id) {
  const g = ganaderos.find((x) => x.id === id);
  if (!g) return;

  const modal = document.getElementById("modal-nuevo-ganadero");
  if (!modal) return;

  modal.querySelector("h3").textContent = "Editar Asociado";
  const form = document.getElementById("form-nuevo-ganadero");
  form.cedula.value = g.cedula;
  form.nombre.value = g.nombre;
  form.telefono.value = g.telefono || "";
  if (form.finca) form.finca.value = g.finca || "";
  if (form.activo) form.activo.value = (g.activo === 1 || g.activo === true || g.activo === "1") ? "1" : "0";
  form.dataset.editId = g.id;

  modal.classList.remove("hidden");
};

// Eliminar asociado
window.eliminarAsociado = async function(id, nombre) {
  if (!confirm(`¿Estás seguro de que deseas eliminar al asociado ${nombre}? Esta acción borrará también sus pesajes.`)) {
    return;
  }

  // 1. Eliminar en local
  ganaderos = ganaderos.filter((x) => x.id !== id);
  localStorage.setItem("asociados_local", JSON.stringify(ganaderos));
  mostrarNotificacion(`🗑️ Asociado ${nombre} eliminado.`);
  renderizarPlanilla();
  renderizarTablaGestionAsociados();
  poblarSelectoresFormularios();

  // 2. Eliminar en servidor
  try {
    await fetch(`./api/ganaderos.php?id=${id}`, { method: "DELETE" });
  } catch (e) {
    console.warn("Eliminado localmente");
  }
};

// Poblar selector del formulario superior
function poblarSelectoresFormularios() {
  const sel = document.getElementById("form-ganadero-id");
  if (!sel) return;

  sel.innerHTML = '<option value="">-- Seleccionar Ganadero --</option>';
  ganaderos.forEach((g) => {
    const opt = document.createElement("option");
    opt.value = g.id;
    opt.textContent = `${g.nombre} (C.C. ${g.cedula})`;
    sel.appendChild(opt);
  });
}

// ----------------------------------------------------------------
// 5. ASIGNACIÓN ROBUSTA DE EVENTOS A BOTONES Y PESTAÑAS
// ----------------------------------------------------------------
function inicializarEventosBotonesYPestanas() {
  // Pestañas
  document.querySelectorAll(".tab-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const targetId = btn.dataset.tab;
      document.querySelectorAll(".tab-content").forEach((c) => c.classList.add("hidden"));
      const targetEl = document.getElementById(targetId);
      if (targetEl) targetEl.classList.remove("hidden");

      document.querySelectorAll(".tab-btn").forEach((b) => {
        b.className = "tab-btn px-4 py-2 rounded-xl text-campo-200 hover:bg-campo-800 transition-all flex items-center gap-2";
      });
      btn.className = "tab-btn px-4 py-2 rounded-xl bg-campo-800 text-dorado-300 border border-campo-600 transition-all flex items-center gap-2 active-tab";

      if (targetId === "tab-buzon") {
        cargarBuzonAdmin();
      }
      if (targetId === "tab-ganaderos") {
        renderizarTablaGestionAsociados();
      }
    });
  });

  // Exportar Excel
  document.getElementById("btn-exportar-excel")?.addEventListener("click", exportarPlanillaExcel);

  // Buscador de asociados en pestaña 2
  document.getElementById("busqueda-asociados-modulo")?.addEventListener("input", (e) => {
    busquedaAsociados = e.target.value.trim();
    renderizarTablaGestionAsociados();
  });

  // Turnos en formulario superior
  const btnM = document.getElementById("btn-turno-manana");
  const btnT = document.getElementById("btn-turno-tarde");
  if (btnM && btnT) {
    btnM.addEventListener("click", () => {
      turnoSeleccionadoForm = "MANANA";
      btnM.className = "py-2 px-2 rounded-xl text-xs font-bold border-2 transition-all flex items-center justify-center gap-1 bg-amber-100 border-amber-500 text-amber-900 shadow-sm";
      btnT.className = "py-2 px-2 rounded-xl text-xs font-bold border-2 transition-all flex items-center justify-center gap-1 bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100";
    });

    btnT.addEventListener("click", () => {
      turnoSeleccionadoForm = "TARDE";
      btnT.className = "py-2 px-2 rounded-xl text-xs font-bold border-2 transition-all flex items-center justify-center gap-1 bg-orange-100 border-orange-500 text-orange-900 shadow-sm";
      btnM.className = "py-2 px-2 rounded-xl text-xs font-bold border-2 transition-all flex items-center justify-center gap-1 bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100";
    });
  }

  // Buscador en vivo
  const inputBusqueda = document.getElementById("busqueda-ganadero");
  const btnLimpiar = document.getElementById("btn-limpiar-busqueda");
  if (inputBusqueda) {
    inputBusqueda.addEventListener("input", (e) => {
      busquedaGanadero = e.target.value.toLowerCase().trim();
      if (btnLimpiar) btnLimpiar.classList.toggle("hidden", !busquedaGanadero);
      renderizarPlanilla();
    });
  }
  if (btnLimpiar) {
    btnLimpiar.addEventListener("click", () => {
      busquedaGanadero = "";
      if (inputBusqueda) inputBusqueda.value = "";
      btnLimpiar.classList.add("hidden");
      renderizarPlanilla();
    });
  }

  // Formulario superior de registro rápido
  const formRegistro = document.getElementById("form-registro-rapido");
  const inputFechaForm = document.getElementById("form-fecha");
  const inputLitrosForm = document.getElementById("form-litros");
  const selectGanaderoForm = document.getElementById("form-ganadero-id");

  if (inputFechaForm) inputFechaForm.value = getTodayString();

  if (formRegistro) {
    formRegistro.addEventListener("submit", async (e) => {
      e.preventDefault();
      const uId = selectGanaderoForm.value;
      const fch = inputFechaForm.value;
      const lts = parseFloat(inputLitrosForm.value.replace(",", "."));

      if (!uId) {
        alert("Selecciona un ganadero.");
        return;
      }
      if (isNaN(lts) || lts <= 0) {
        alert("Ingresa una cantidad de litros válida.");
        return;
      }

      // Guardar inmediatamente en LocalStorage
      entregasMap[`${uId}_${fch}_${turnoSeleccionadoForm}`] = { id: `reg_${Date.now()}`, litros: lts };
      localStorage.setItem("entregas_local", JSON.stringify(entregasMap));

      const ganadero = ganaderos.find((x) => x.id === uId);
      mostrarNotificacion(`✅ ${lts.toFixed(1)} L guardados para ${ganadero ? ganadero.nombre : 'Asociado'}`);
      inputLitrosForm.value = "";
      renderizarPlanilla();
      renderizarTablaGestionAsociados();

      // Sincronizar en segundo plano
      try {
        await fetch("./api/entregas.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ usuarioId: uId, fecha: fch, turno: turnoSeleccionadoForm, litros: lts })
        });
      } catch (err) {
        console.warn("Guardado local");
      }
    });
  }

  // Formulario Nuevo / Editar Ganadero
  const formNuevoGanadero = document.getElementById("form-nuevo-ganadero");
  if (formNuevoGanadero) {
    formNuevoGanadero.addEventListener("submit", async (e) => {
      e.preventDefault();
      const form = e.target;
      const editId = form.dataset.editId || "";
      const cedula = form.cedula.value.trim().replace(/\D/g, "");
      const nombre = form.nombre.value.trim();
      const telefono = form.telefono.value.trim();
      const finca = form.finca ? form.finca.value.trim() : "Hacienda Asociada";
      const password = form.password ? form.password.value.trim() : "1234";
      const activo = form.activo ? (form.activo.value === "1" ? 1 : 0) : 1;

      if (!cedula || !nombre) {
        alert("Cédula y nombre son campos obligatorios.");
        return;
      }

      // Validar cédula única en memoria local
      const yaExiste = ganaderos.some((x) => x.cedula === cedula && x.id !== editId);
      if (yaExiste) {
        alert(`Ya existe un asociado registrado con el número de cédula ${cedula}. Por favor verifica.`);
        return;
      }

      if (editId) {
        // Actualizar en memoria
        const idx = ganaderos.findIndex((x) => x.id === editId);
        if (idx !== -1) {
          ganaderos[idx].cedula = cedula;
          ganaderos[idx].nombre = nombre;
          ganaderos[idx].telefono = telefono;
          ganaderos[idx].finca = finca;
          ganaderos[idx].activo = activo;
        }
        mostrarNotificacion(`✅ Asociado ${nombre} actualizado.`);
      } else {
        // Crear nuevo
        const nuevo = {
          id: `usr_${Date.now()}`,
          cedula,
          nombre,
          telefono,
          finca,
          activo: activo,
          totalLitrosQuincena: 0
        };
        ganaderos.push(nuevo);
        mostrarNotificacion(`✅ Asociado ${nombre} registrado con éxito.`);
      }

      localStorage.setItem("asociados_local", JSON.stringify(ganaderos));
      document.getElementById("modal-nuevo-ganadero")?.classList.add("hidden");
      form.reset();
      delete form.dataset.editId;

      renderizarPlanilla();
      renderizarTablaGestionAsociados();
      poblarSelectoresFormularios();

      // Sincronizar con servidor
      try {
        const res = await fetch("./api/ganaderos.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ id: editId, cedula, nombre, telefono, finca, password, activo })
        });
        const resData = await res.json();
        if (!res.ok) {
          alert(resData.error || "Aviso del servidor al guardar.");
        }
      } catch (e) {
        console.warn("Guardado local");
      }
    });
  }

  // Formulario Fijar Precio Quincena
  const formPrecio = document.getElementById("form-fijar-precio");
  if (formPrecio) {
    formPrecio.addEventListener("submit", async (e) => {
      e.preventDefault();
      const fIni = formPrecio.fechaInicio.value;
      const fFin = formPrecio.fechaFin.value;
      const precio = parseFloat(formPrecio.precioLitro.value);

      if (!fIni || !fFin || isNaN(precio)) {
        alert("Completa todos los campos correctamente.");
        return;
      }

      mostrarNotificacion(`💲 Precio de $${precio.toLocaleString()} fijado para el periodo.`);

      try {
        await fetch("./api/quincenas.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ fechaInicio: fIni, fechaFin: fFin, precioLitro: precio })
        });
      } catch (e) {
        console.warn("Precio guardado en local");
      }
    });
  }
}

// ----------------------------------------------------------------
// 6. EXPORTAR A EXCEL REAL (.XLSX) CON SHEETJS
// ----------------------------------------------------------------
async function exportarPlanillaExcel() {
  if (typeof XLSX === "undefined") {
    alert("La librería de exportación aún está cargando. Por favor intenta de nuevo en 3 segundos.");
    return;
  }

  const dias = generarDiasQuincena(quincenaSeleccionada.fechaInicio, quincenaSeleccionada.fechaFin);

  // Obtener precio por litro vigente
  let precioLitro = 0;
  try {
    const resQ = await fetch("./api/quincenas.php");
    const dataQ = await resQ.json();
    if (dataQ.quincenas) {
      const qFound = dataQ.quincenas.find(
        (q) => q.fechaInicio === quincenaSeleccionada.fechaInicio && q.fechaFin === quincenaSeleccionada.fechaFin
      );
      if (qFound && qFound.precioLitro) {
        precioLitro = Number(qFound.precioLitro);
      }
    }
  } catch (e) {}

  // Estructura de filas
  const filas = [];
  filas.push(["ASOCIACIÓN DE GANADEROS UNIÓN Y PROGRESO LECHERO"]);
  filas.push([`PLANILLA OFICIAL DE PESAJES - ${quincenaSeleccionada.nombreLegible.toUpperCase()}`]);
  filas.push([`Periodo: ${quincenaSeleccionada.fechaInicio} al ${quincenaSeleccionada.fechaFin} | Tarifa Oficial: $${precioLitro.toLocaleString('es-CO')} COP / Litro`]);
  filas.push([]); // fila vacía

  // Encabezado
  const filaCabecera = ["GANADERO ASOCIADO", "CÉDULA", "FINCA / HACIENDA"];
  dias.forEach((d) => {
    filaCabecera.push(`${d.etiquetaCorta} (Mañana)`);
    filaCabecera.push(`${d.etiquetaCorta} (Tarde)`);
  });
  filaCabecera.push("TOTAL LITROS (L)");
  filaCabecera.push("PRECIO / L (COP)");
  filaCabecera.push("TOTAL LIQUIDADO (COP)");
  filas.push(filaCabecera);

  // Totales acumulados por columna
  const totalesTurno = new Array(dias.length * 2).fill(0);
  let granTotalLitros = 0;
  let granTotalPago = 0;

  ganaderos.forEach((g) => {
    let totalG = 0;
    const fila = [g.nombre, g.cedula, g.finca || "Hacienda Asociada"];

    dias.forEach((d, idx) => {
      const lM = Number(entregasMap[`${g.id}_${d.fecha}_MANANA`]?.litros || 0);
      const lT = Number(entregasMap[`${g.id}_${d.fecha}_TARDE`]?.litros || 0);

      fila.push(lM > 0 ? lM : 0);
      fila.push(lT > 0 ? lT : 0);

      totalesTurno[idx * 2] += lM;
      totalesTurno[idx * 2 + 1] += lT;

      totalG += (lM + lT);
    });

    const pagoG = totalG * precioLitro;
    granTotalLitros += totalG;
    granTotalPago += pagoG;

    fila.push(Number(totalG.toFixed(1)));
    fila.push(precioLitro);
    fila.push(Math.round(pagoG));

    filas.push(fila);
  });

  // Fila de Totales Generales
  const filaTotales = ["TOTALES GENERALES DE LA QUINCENA", "", ""];
  totalesTurno.forEach((t) => {
    filaTotales.push(Number(t.toFixed(1)));
  });
  filaTotales.push(Number(granTotalLitros.toFixed(1)));
  filaTotales.push("");
  filaTotales.push(Math.round(granTotalPago));
  filas.push(filaTotales);

  // Sanitización y neutralización de fórmulas maliciosas de Excel (=, +, -, @)
  const neutralizarFormula = (val) => {
    if (typeof val === "string") {
      const trimmed = val.trim();
      if (trimmed.startsWith("=") || trimmed.startsWith("+") || trimmed.startsWith("-") || trimmed.startsWith("@")) {
        return `'${val}`;
      }
    }
    return val;
  };

  const filasSanitizadas = filas.map((row) => row.map(neutralizarFormula));

  // Generar Hoja y Libro Excel
  const ws = XLSX.utils.aoa_to_sheet(filasSanitizadas);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, "Planilla Quincenal");

  const nombreArchivo = `Planilla_Entregas_${quincenaSeleccionada.fechaInicio}_${quincenaSeleccionada.fechaFin}.xlsx`;
  XLSX.writeFile(wb, nombreArchivo);
  mostrarNotificacion(`📊 Archivo Excel generado: ${nombreArchivo}`);
}

// ----------------------------------------------------------------
// 7. GESTIÓN DEL BUZÓN DE QUEJAS Y CONSULTAS (PESTAÑA 4)
// ----------------------------------------------------------------
window.cambiarSubBuzon = function(tipo) {
  const secConsultas = document.getElementById("subseccion-consultas");
  const secQuejas = document.getElementById("subseccion-quejas");
  const secRecup = document.getElementById("subseccion-recuperaciones");
  const btnC = document.getElementById("btn-buzon-consultas");
  const btnQ = document.getElementById("btn-buzon-quejas");
  const btnR = document.getElementById("btn-buzon-recuperaciones");

  if (secConsultas) secConsultas.classList.add("hidden");
  if (secQuejas) secQuejas.classList.add("hidden");
  if (secRecup) secRecup.classList.add("hidden");

  if (btnC) btnC.className = "px-3.5 py-2 rounded-xl text-xs font-bold transition-all text-gray-600 hover:text-gray-900 whitespace-nowrap";
  if (btnQ) btnQ.className = "px-3.5 py-2 rounded-xl text-xs font-bold transition-all text-gray-600 hover:text-gray-900 whitespace-nowrap";
  if (btnR) btnR.className = "px-3.5 py-2 rounded-xl text-xs font-bold transition-all text-gray-600 hover:text-gray-900 whitespace-nowrap";

  if (tipo === "consultas") {
    if (secConsultas) secConsultas.classList.remove("hidden");
    if (btnC) btnC.className = "px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white text-campo-950 shadow-sm whitespace-nowrap";
  } else if (tipo === "quejas") {
    if (secQuejas) secQuejas.classList.remove("hidden");
    if (btnQ) btnQ.className = "px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-amber-600 text-white shadow-sm whitespace-nowrap";
  } else if (tipo === "recuperaciones") {
    if (secRecup) secRecup.classList.remove("hidden");
    if (btnR) btnR.className = "px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-blue-700 text-white shadow-sm whitespace-nowrap";
  }
};

function escapeHtml(str) {
  if (!str) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#039;");
}

window.cargarBuzonAdmin = async function() {
  const tbodyC = document.getElementById("tbody-consultas-admin");
  const tbodyQ = document.getElementById("tbody-quejas-admin");
  const badgeC = document.getElementById("badge-contador-consultas");
  const badgeQ = document.getElementById("badge-contador-quejas");

  try {
    const res = await fetch("./api/consultas.php");
    const data = await res.json();
    const consultas = data.consultas || [];
    const alertas = data.alertas || [];
    const solicitudes = data.solicitudes || [];

    if (badgeC) badgeC.textContent = `${consultas.length} mensaje(s)`;
    if (badgeQ) badgeQ.textContent = `${alertas.length} reclamo(s)`;

    const badgeR = document.getElementById("badge-contador-recuperaciones");
    if (badgeR) badgeR.textContent = `${solicitudes.length} solicitud(es)`;

    // 1. Renderizar Consultas
    if (tbodyC) {
      if (consultas.length === 0) {
        tbodyC.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-gray-400 font-bold">No hay consultas web registradas por el momento.</td></tr>`;
      } else {
        let htmlC = "";
        consultas.forEach((c) => {
          const esAtendida = c.atendida == 1 || c.atendida === true;
          htmlC += `
            <tr class="hover:bg-campo-50/40 transition-colors">
              <td class="p-3 whitespace-nowrap text-gray-500 font-mono text-[11px]">${escapeHtml(c.creadoEn.substring(0, 16))}</td>
              <td class="p-3 font-bold text-gray-900">${escapeHtml(c.nombre)}</td>
              <td class="p-3 text-gray-700 font-semibold">${escapeHtml(c.contacto)}</td>
              <td class="p-3 text-gray-800 max-w-xs sm:max-w-md italic whitespace-pre-wrap">${escapeHtml(c.mensaje)}</td>
              <td class="p-3 text-center">
                ${esAtendida 
                  ? '<span class="bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded-full">Atendida</span>' 
                  : '<span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Pendiente</span>'}
              </td>
              <td class="p-3 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <button type="button" onclick="marcarConsultaLeida('${c.id}', ${esAtendida ? 0 : 1})"
                          class="px-2 py-1 text-xs font-bold rounded-lg ${esAtendida ? 'bg-gray-100 hover:bg-gray-200 text-gray-700' : 'bg-green-100 hover:bg-green-200 text-green-900'}">
                    ${esAtendida ? 'Marcar Pendiente' : '✓ Marcar Leída'}
                  </button>
                  <button type="button" onclick="eliminarConsultaAdmin('${c.id}')"
                          class="p-1 text-red-600 hover:text-red-800 text-xs font-bold">
                    🗑️
                  </button>
                </div>
              </td>
            </tr>`;
        });
        tbodyC.innerHTML = htmlC;
      }
    }

    // 2. Renderizar Quejas / Inconformidades
    if (tbodyQ) {
      if (alertas.length === 0) {
        tbodyQ.innerHTML = `<tr><td colspan="7" class="p-8 text-center text-gray-400 font-bold">No hay inconformidades registradas por los ganaderos.</td></tr>`;
      } else {
        let htmlQ = "";
        alertas.forEach((a) => {
          const est = a.estado || "PENDIENTE";
          let badgeEst = '<span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Pendiente</span>';
          if (est === "REVISADO") {
            badgeEst = '<span class="bg-blue-100 text-blue-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Revisado</span>';
          } else if (est === "RESUELTO") {
            badgeEst = '<span class="bg-green-100 text-green-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Resuelto</span>';
          }

          htmlQ += `
            <tr class="hover:bg-amber-50/40 transition-colors">
              <td class="p-3 whitespace-nowrap text-gray-500 font-mono text-[11px]">${escapeHtml(a.creadoEn.substring(0, 16))}</td>
              <td class="p-3 font-bold text-gray-900">${escapeHtml(a.ganaderoNombre || 'Ganadero')} <span class="text-gray-500 font-normal text-[11px]">(C.C. ${escapeHtml(a.cedula || '-')})</span></td>
              <td class="p-3 whitespace-nowrap font-semibold text-campo-950">${escapeHtml(a.fechaInicio)} al ${escapeHtml(a.fechaFin)}</td>
              <td class="p-3 text-right font-black text-campo-900">${Number(a.litrosAlMomento || 0).toFixed(1)} L</td>
              <td class="p-3 text-gray-800 max-w-xs sm:max-w-md italic whitespace-pre-wrap">${escapeHtml(a.mensaje)}</td>
              <td class="p-3 text-center">${badgeEst}</td>
              <td class="p-3 text-center">
                <div class="flex items-center justify-center gap-1.5">
                  <select onchange="cambiarEstadoQuejaAdmin('${a.id}', this.value)"
                          class="px-2 py-1 text-xs border rounded-lg bg-white font-bold text-gray-700">
                    <option value="PENDIENTE" ${est === 'PENDIENTE' ? 'selected' : ''}>Pendiente</option>
                    <option value="REVISADO" ${est === 'REVISADO' ? 'selected' : ''}>Revisado</option>
                    <option value="RESUELTO" ${est === 'RESUELTO' ? 'selected' : ''}>Resuelto</option>
                  </select>
                  <button type="button" onclick="eliminarQuejaAdmin('${a.id}')"
                          class="p-1 text-red-600 hover:text-red-800 text-xs font-bold" title="Eliminar">
                    🗑️
                  </button>
                </div>
              </td>
            </tr>`;
        });
        tbodyQ.innerHTML = htmlQ;
      }
    }

    // 3. Renderizar Solicitudes de Recuperación de Clave (Ganaderos)
    const tbodyR = document.getElementById("tbody-recuperaciones-admin");
    if (tbodyR) {
      if (solicitudes.length === 0) {
        tbodyR.innerHTML = `<tr><td colspan="6" class="p-8 text-center text-gray-400 font-bold">No hay solicitudes de recuperación de contraseña registradas.</td></tr>`;
      } else {
        let htmlR = "";
        solicitudes.forEach((s) => {
          const esPendiente = s.estado === "PENDIENTE";
          let badgeEst = '<span class="bg-amber-100 text-amber-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Pendiente</span>';
          if (s.estado === "ATENDIDA") {
            badgeEst = '<span class="bg-blue-100 text-blue-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Atendida</span>';
          } else if (s.estado === "COMPLETADA") {
            badgeEst = '<span class="bg-green-100 text-green-900 text-[10px] font-bold px-2 py-0.5 rounded-full">Completada</span>';
          }

          htmlR += `
            <tr class="hover:bg-blue-50/40 transition-colors">
              <td class="p-3 whitespace-nowrap text-gray-500 font-mono text-[11px]">${escapeHtml(s.creadoEn ? s.creadoEn.substring(0, 16) : '')}</td>
              <td class="p-3 font-bold text-gray-900">${escapeHtml(s.nombre)}</td>
              <td class="p-3 font-mono text-gray-700 font-semibold">${escapeHtml(s.cedula)}</td>
              <td class="p-3 text-gray-600 font-semibold">${escapeHtml(s.telefono || 'Sin teléfono')}</td>
              <td class="p-3 text-center">${badgeEst}</td>
              <td class="p-3 text-center">
                <button type="button" onclick="generarClaveTemporalGanadero('${s.id}', '${s.usuarioId}', '${escapeHtml(s.nombre)}', '${escapeHtml(s.cedula)}')"
                        class="btn-touch px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm ${esPendiente ? 'bg-amber-500 hover:bg-amber-600 text-white ring-2 ring-amber-300' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'}"
                        title="Generar nueva clave temporal">
                  <span>⚡ Generar Clave Temporal</span>
                </button>
              </td>
            </tr>`;
        });
        tbodyR.innerHTML = htmlR;
      }
    }
  } catch (err) {
    console.error("Error al cargar buzón:", err);
  }
};

window.marcarConsultaLeida = async function(id, nuevoEstado) {
  try {
    await fetch("./api/consultas.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "toggle_atendida", id: id, atendida: nuevoEstado })
    });
    mostrarNotificacion("Estado de consulta actualizado.");
    cargarBuzonAdmin();
  } catch (e) {
    alert("Error al actualizar consulta.");
  }
};

window.eliminarConsultaAdmin = async function(id) {
  if (!confirm("¿Deseas eliminar permanentemente esta consulta de la web?")) return;
  try {
    await fetch(`./api/consultas.php?id=${id}&tipo=consulta`, { method: "DELETE" });
    mostrarNotificacion("Consulta eliminada.");
    cargarBuzonAdmin();
  } catch (e) {
    alert("Error al eliminar consulta.");
  }
};

window.cambiarEstadoQuejaAdmin = async function(id, nuevoEstado) {
  try {
    await fetch("./api/consultas.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ action: "cambiar_estado_queja", id: id, estado: nuevoEstado })
    });
    mostrarNotificacion(`Estado de inconformidad cambiado a: ${nuevoEstado}`);
    cargarBuzonAdmin();
  } catch (e) {
    alert("Error al actualizar estado.");
  }
};

window.eliminarQuejaAdmin = async function(id) {
  if (!confirm("¿Deseas eliminar permanentemente este reclamo?")) return;
  try {
    await fetch(`./api/consultas.php?id=${id}&tipo=queja`, { method: "DELETE" });
    mostrarNotificacion("Reclamo eliminado.");
    cargarBuzonAdmin();
  } catch (e) {
    alert("Error al eliminar reclamo.");
  }
};

// Generar clave temporal para ganadero (Flujo A)
window.generarClaveTemporalGanadero = async function(solicitudId, usuarioId, nombre, cedula) {
  if (!confirm(`¿Confirmas que has verificado la identidad de ${nombre} (C.C. ${cedula}) y deseas generar una clave temporal de un solo uso?`)) {
    return;
  }

  try {
    const res = await fetch("./api/admin_generar_clave_temporal.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ solicitudId, asociadoId: usuarioId })
    });
    const data = await res.json();

    if (res.ok && data.claveTemporal) {
      document.getElementById("temporal-ganadero-nombre").textContent = data.nombre || nombre;
      document.getElementById("temporal-ganadero-cedula").textContent = data.cedula || cedula;
      document.getElementById("temporal-clave-valor").textContent = data.claveTemporal;
      document.getElementById("modal-clave-temporal")?.classList.remove("hidden");
      cargarBuzonAdmin();
    } else {
      alert("⚠️ " + (data.error || "No se pudo generar la clave temporal."));
    }
  } catch (e) {
    alert("Error de conexión al generar la clave temporal.");
  }
};

window.cerrarModalClaveTemporal = function() {
  document.getElementById("modal-clave-temporal")?.classList.add("hidden");
};

// Modal Cambiar Contraseña del Administrador
window.abrirModalCambiarPassword = function() {
  const modal = document.getElementById("modal-cambiar-password");
  const form = document.getElementById("form-cambiar-password-admin");
  const msg = document.getElementById("msg-modal-pass");
  if (form) form.reset();
  if (msg) msg.classList.add("hidden");
  if (modal) modal.classList.remove("hidden");
};

window.cerrarModalCambiarPassword = function() {
  document.getElementById("modal-cambiar-password")?.classList.add("hidden");
};

// Handler para cambiar contraseña de admin
document.addEventListener("DOMContentLoaded", () => {
  const formPass = document.getElementById("form-cambiar-password-admin");
  if (formPass) {
    formPass.addEventListener("submit", async (e) => {
      e.preventDefault();
      const btn = document.getElementById("btn-submit-modal-pass");
      const msg = document.getElementById("msg-modal-pass");

      const pActual = formPass.passwordActual.value;
      const pNueva = formPass.passwordNueva.value;
      const pConf = formPass.passwordConfirm.value;

      if (pNueva !== pConf) {
        msg.className = "p-3 bg-red-100 text-red-800 rounded-xl text-xs font-bold block mb-3";
        msg.textContent = "La nueva contraseña y su confirmación no coinciden.";
        return;
      }

      btn.disabled = true;
      btn.textContent = "Actualizando...";

      try {
        const res = await fetch("./api/cambiar_password.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            passwordActual: pActual,
            passwordNueva: pNueva,
            passwordConfirm: pConf
          })
        });

        const data = await res.json();

        if (res.ok) {
          msg.className = "p-3 bg-green-100 text-green-800 rounded-xl text-xs font-bold block mb-3";
          msg.textContent = "✅ " + data.mensaje;
          setTimeout(() => {
            cerrarModalCambiarPassword();
          }, 1800);
        } else {
          msg.className = "p-3 bg-red-100 text-red-800 rounded-xl text-xs font-bold block mb-3";
          msg.textContent = "⚠️ " + (data.error || "Error al actualizar la contraseña.");
        }
      } catch (err) {
        msg.className = "p-3 bg-red-100 text-red-800 rounded-xl text-xs font-bold block mb-3";
        msg.textContent = "Error de conexión al servidor.";
      } finally {
        btn.disabled = false;
        btn.textContent = "Actualizar Contraseña";
      }
    });
  }
});
