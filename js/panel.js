/**
 * LÓGICA INTERACTIVA DEL PANEL DEL GANADERO (SOLO LECTURA + RECLAMOS)
 */

document.addEventListener("DOMContentLoaded", () => {
  const hoyStr = getTodayString();
  const quincenasDisponibles = getQuincenasRecientes(24);
  const quincenaActual = quincenasDisponibles[0] || getQuincenaForDate("2027-01-01");
  let quincenaSeleccionada = quincenaActual;
  let precioQuincenaActual = window.PRECIO_LITRO_INICIAL || null;

  const selectorQuincena = document.getElementById("selector-quincena-ganadero");
  const rangoFechasTexto = document.getElementById("rango-fechas-texto");
  const nombreQuincenaTexto = document.getElementById("nombre-quincena-texto");
  const totalLitrosDisplay = document.getElementById("total-litros-display");
  const precioLitroDisplay = document.getElementById("precio-litro-display");
  const pagoEstimadoDisplay = document.getElementById("pago-estimado-display");
  const contenedorTabla = document.getElementById("contenedor-tabla-ganadero");

  // Sección de Reclamos
  const btnAbrirQueja = document.getElementById("btn-abrir-queja");
  const modalQueja = document.getElementById("modal-queja");
  const btnCerrarQueja = document.getElementById("btn-cerrar-queja");
  const formQueja = document.getElementById("form-queja");
  const quejaMensajeInput = document.getElementById("queja-mensaje");
  const estadoQuejaAviso = document.getElementById("estado-queja-aviso");
  const btnEnviarQueja = document.getElementById("btn-enviar-queja");

  // 1. Llenar el selector de quincenas
  if (selectorQuincena) {
    selectorQuincena.innerHTML = "";
    quincenasDisponibles.forEach((q) => {
      const opt = document.createElement("option");
      opt.value = `${q.fechaInicio}_${q.fechaFin}`;
      opt.textContent = `${q.nombreLegible} (${formatDateSpanish(q.fechaInicio)} al ${formatDateSpanish(q.fechaFin)})`;
      if (q.fechaInicio === quincenaActual.fechaInicio && q.fechaFin === quincenaActual.fechaFin) {
        opt.selected = true;
      }
      selectorQuincena.appendChild(opt);
    });

    selectorQuincena.addEventListener("change", (e) => {
      const [fIni, fFin] = e.target.value.split("_");
      const found = quincenasDisponibles.find((q) => q.fechaInicio === fIni && q.fechaFin === fFin);
      if (found) {
        quincenaSeleccionada = found;
        cargarDetalleQuincena();
        verificarAlertaQuincena();
      }
    });
  }

  // 2. Cargar pesajes de la quincena seleccionada y renderizar la planilla Excel
  async function cargarDetalleQuincena() {
    if (rangoFechasTexto) {
      rangoFechasTexto.textContent = `${formatDateSpanish(quincenaSeleccionada.fechaInicio)} al ${formatDateSpanish(quincenaSeleccionada.fechaFin)}`;
    }
    if (nombreQuincenaTexto) {
      nombreQuincenaTexto.textContent = quincenaSeleccionada.nombreLegible;
    }

    if (contenedorTabla) {
      contenedorTabla.innerHTML = `
        <div class="py-12 text-center text-gray-500 font-bold flex flex-col items-center justify-center gap-2">
          <div class="w-8 h-8 border-4 border-campo-600 border-t-transparent rounded-full animate-spin"></div>
          <span>Cargando planilla de pesajes...</span>
        </div>`;
    }

    try {
      // 1. Obtener precio de la quincena seleccionada
      const resQ = await fetch("./api/quincenas.php");
      const dataQ = await resQ.json();
      let precioLitro = null;
      if (dataQ.quincenas) {
        const qFound = dataQ.quincenas.find(
          (q) => q.fechaInicio === quincenaSeleccionada.fechaInicio && q.fechaFin === quincenaSeleccionada.fechaFin
        );
        if (qFound && qFound.precioLitro) {
          precioLitro = parseFloat(qFound.precioLitro);
        }
      }

      // 2. Obtener pesajes de este ganadero
      const res = await fetch(`./api/entregas.php?fechaInicio=${quincenaSeleccionada.fechaInicio}&fechaFin=${quincenaSeleccionada.fechaFin}`);
      const data = await res.json();
      const entregas = data.entregas || [];

      // Mapear pesajes por fecha y turno
      const mapa = {};
      let totalQuincena = 0;
      entregas.forEach((e) => {
        mapa[`${e.fecha}_${e.turno}`] = e.litros;
        totalQuincena += e.litros;
      });

      // Actualizar tarjeta de pesaje de hoy (reactivo)
      const elMananaHoy = document.getElementById("litros-manana-hoy");
      const elTardeHoy = document.getElementById("litros-tarde-hoy");
      const elTotalDiaHoy = document.getElementById("total-dia-hoy");
      const lMananaHoy = Number(mapa[`${hoyStr}_MANANA`] || 0);
      const lTardeHoy = Number(mapa[`${hoyStr}_TARDE`] || 0);

      if (elMananaHoy) elMananaHoy.textContent = lMananaHoy.toFixed(1);
      if (elTardeHoy) elTardeHoy.textContent = lTardeHoy.toFixed(1);
      if (elTotalDiaHoy) elTotalDiaHoy.textContent = (lMananaHoy + lTardeHoy).toFixed(1);

      // Actualizar tarjeta métrica
      if (totalLitrosDisplay) totalLitrosDisplay.textContent = totalQuincena.toFixed(1);
      if (precioLitroDisplay) {
        precioLitroDisplay.textContent = precioLitro ? formatCurrency(precioLitro) : "Pendiente por fijar";
      }
      if (pagoEstimadoDisplay) {
        if (precioLitro && totalQuincena > 0) {
          pagoEstimadoDisplay.textContent = formatCurrency(totalQuincena * precioLitro);
        } else {
          pagoEstimadoDisplay.textContent = "$0 COP";
        }
      }

      // Generar días de la quincena
      const dias = generarDiasQuincena(quincenaSeleccionada.fechaInicio, quincenaSeleccionada.fechaFin);

      // Renderizar tabla horizontal formato Excel (SOLO LECTURA)
      renderizarTablaExcel(dias, mapa, totalQuincena);
    } catch (err) {
      console.error(err);
      if (contenedorTabla) {
        contenedorTabla.innerHTML = `<div class="p-6 text-center text-red-600 font-bold">Error al cargar datos de la quincena.</div>`;
      }
    }
  }

  function renderizarTablaExcel(dias, mapa, totalQuincena) {
    if (!contenedorTabla) return;

    let html = `
      <div class="overflow-x-auto shadow-inner">
        <table class="w-full text-left text-xs border-collapse">
          <thead>
            <!-- Nivel 1 Superior: Fechas -->
            <tr class="bg-campo-900 text-white uppercase tracking-wider font-extrabold text-[11px]">
              <th rowspan="2" class="sticky left-0 z-30 bg-campo-950 px-4 py-3 border-r-2 border-campo-700 min-w-[200px] shadow-[2px_0_5px_-2px_rgba(0,0,0,0.3)] align-middle">
                ASOCIADO / CÉDULA
              </th>`;

    dias.forEach((d) => {
      const esHoy = d.fecha === hoyStr;
      html += `
        <th colspan="2" class="text-center py-2 px-2 border-r-2 border-campo-700 min-w-[120px] ${esHoy ? 'bg-campo-800 ring-2 ring-inset ring-dorado-400' : 'bg-campo-900'}">
          <div class="font-black text-white text-xs">${d.etiquetaLarga}</div>
          ${esHoy ? '<span class="text-[9px] font-black uppercase text-dorado-300 bg-black/40 px-2 py-0.2 rounded-full">Hoy</span>' : ''}
        </th>`;
    });

    html += `
              <th rowspan="2" class="sticky right-0 z-30 bg-campo-950 px-4 py-3 border-l-2 border-campo-700 min-w-[130px] text-right text-dorado-300 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.3)] align-middle">
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
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr class="hover:bg-campo-50/50 transition-colors">
              <td class="sticky left-0 z-20 bg-white px-4 py-3 border-r-2 border-gray-300 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.08)] whitespace-nowrap">
                <div class="font-bold text-gray-900 text-sm">${window.GANADERO_NOMBRE || 'Asociado'}</div>
                <div class="text-[11px] font-mono text-gray-500">C.C. ${window.GANADERO_CEDULA || '-'}</div>
              </td>`;

    // Celdas de cada día (SOLO LECTURA ESTRICTA)
    dias.forEach((d) => {
      const lManana = mapa[`${d.fecha}_MANANA`];
      const lTarde = mapa[`${d.fecha}_TARDE`];

      html += `
        <td class="p-2 text-center border-r border-gray-200 bg-amber-50/20">
          <span class="font-extrabold text-amber-950 text-xs">${lManana !== undefined && lManana !== null ? lManana.toFixed(1) : '-'}</span>
        </td>
        <td class="p-2 text-center border-r-2 border-gray-300 bg-orange-50/20">
          <span class="font-extrabold text-orange-950 text-xs">${lTarde !== undefined && lTarde !== null ? lTarde.toFixed(1) : '-'}</span>
        </td>`;
    });

    html += `
              <td class="sticky right-0 z-20 bg-campo-50/95 px-4 py-3 border-l-2 border-campo-300 shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.08)] text-right whitespace-nowrap">
                <span class="font-black text-campo-950 text-sm">${totalQuincena.toFixed(1)} Litros</span>
              </td>
            </tr>
          </tbody>
          <tfoot class="sticky bottom-0 z-30 shadow-md font-extrabold">
            <tr class="bg-gray-100 text-gray-900 border-t-2 border-campo-300">
              <td class="sticky left-0 z-40 bg-gray-200 px-4 py-3 border-r-2 border-gray-400 text-xs uppercase tracking-wider shadow-[2px_0_5px_-2px_rgba(0,0,0,0.2)]">
                TOTAL DÍA (L)
              </td>`;

    dias.forEach((d) => {
      const lManana = mapa[`${d.fecha}_MANANA`] || 0;
      const lTarde = mapa[`${d.fecha}_TARDE`] || 0;
      html += `
        <td class="py-2.5 px-1 text-center bg-amber-100/80 text-amber-950 border-r border-amber-300 text-xs font-black">
          ${lManana > 0 ? lManana.toFixed(1) : '-'}
        </td>
        <td class="py-2.5 px-1 text-center bg-orange-100/80 text-orange-950 border-r-2 border-gray-400 text-xs font-black">
          ${lTarde > 0 ? lTarde.toFixed(1) : '-'}
        </td>`;
    });

    html += `
              <td class="sticky right-0 z-40 bg-campo-950 text-dorado-300 px-4 py-3 border-l-2 border-campo-700 text-right text-xs sm:text-sm font-black shadow-[-2px_0_5px_-2px_rgba(0,0,0,0.2)] whitespace-nowrap">
                ${totalQuincena.toFixed(1)} L
              </td>
            </tr>
          </tfoot>
        </table>
      </div>`;

    contenedorTabla.innerHTML = html;
  }

  // 3. Sistema de Quejas e Inconformidades (Límite 1 por quincena)
  async function verificarAlertaQuincena() {
    if (!estadoQuejaAviso || !btnAbrirQueja) return;

    try {
      const res = await fetch(`./api/enviar_queja.php?fechaInicio=${quincenaSeleccionada.fechaInicio}&fechaFin=${quincenaSeleccionada.fechaFin}`);
      const data = await res.json();

      if (data.yaEnviada && data.alerta) {
        // Bloquear botón y mostrar aviso
        btnAbrirQueja.disabled = true;
        btnAbrirQueja.classList.add("opacity-60", "cursor-not-allowed");
        btnAbrirQueja.innerHTML = `<span>🔒 Inquietud ya enviada para este periodo</span>`;

        estadoQuejaAviso.classList.remove("hidden");
        estadoQuejaAviso.innerHTML = `
          <div class="p-4 bg-amber-50 border-l-4 border-dorado-500 rounded-r-xl text-amber-950 space-y-1.5 shadow-sm">
            <div class="font-bold text-sm flex items-center gap-2">
              <span>⚠️</span> Ya has enviado una inquietud para esta quincena. El administrador la está revisando.
            </div>
            <p class="text-xs text-gray-600">Fecha de envío: <strong>${data.alerta.creadoEn}</strong></p>
            <div class="text-xs bg-white/80 p-2.5 rounded-lg border border-amber-200 text-gray-800 italic">
              "${data.alerta.mensaje}"
            </div>
          </div>`;
      } else {
        // Habilitar botón para nuevo reclamo
        btnAbrirQueja.disabled = false;
        btnAbrirQueja.classList.remove("opacity-60", "cursor-not-allowed");
        btnAbrirQueja.innerHTML = `<span>⚠️ ¿Tienes alguna inconformidad con tus litros registrados?</span>`;
        estadoQuejaAviso.classList.add("hidden");
        estadoQuejaAviso.innerHTML = "";
      }
    } catch (err) {
      console.error(err);
    }
  }

  // Modal handlers
  if (btnAbrirQueja) {
    btnAbrirQueja.addEventListener("click", () => {
      if (btnAbrirQueja.disabled) return;
      if (modalQueja) modalQueja.classList.remove("hidden");
    });
  }

  if (btnCerrarQueja && modalQueja) {
    btnCerrarQueja.addEventListener("click", () => {
      modalQueja.classList.add("hidden");
    });
  }

  if (formQueja) {
    formQueja.addEventListener("submit", async (e) => {
      e.preventDefault();
      const mensaje = quejaMensajeInput.value.trim();
      if (!mensaje || mensaje.length < 10) {
        alert("Por favor escribe un mensaje detallado de al menos 10 caracteres.");
        return;
      }

      if (btnEnviarQueja) {
        btnEnviarQueja.disabled = true;
        btnEnviarQueja.textContent = "Enviando reclamo...";
      }

      try {
        const res = await fetch("./api/enviar_queja.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            fechaInicio: quincenaSeleccionada.fechaInicio,
            fechaFin: quincenaSeleccionada.fechaFin,
            mensaje: mensaje,
          }),
        });

        const data = await res.json();
        if (!res.ok) {
          alert(data.error || "No se pudo enviar la inquietud.");
          return;
        }

        mostrarNotificacion(data.mensaje || "Inquietud enviada al administrador exitosamente.");
        if (modalQueja) modalQueja.classList.add("hidden");
        quejaMensajeInput.value = "";
        verificarAlertaQuincena();
      } catch (err) {
        alert("Error de conexión al enviar la inquietud.");
      } finally {
        if (btnEnviarQueja) {
          btnEnviarQueja.disabled = false;
          btnEnviarQueja.textContent = "Enviar Inquietud al Administrador";
        }
      }
    });
  }

  // Carga inicial
  cargarDetalleQuincena();
  verificarAlertaQuincena();
});

