<?php
require_once __DIR__ . '/api/db.php';

$usuario = requireAuth('GANADERO');
if (!empty($usuario['debeCambiarPassword'])) {
    header('Location: cambiar_password.php');
    exit;
}
$pdo = getDB();

$hoyStr = date('Y-m-d');
$diaHoy = (int)date('d');
$anoHoy = (int)date('Y');
$mesHoy = (int)date('m');
$padM = str_pad($mesHoy, 2, '0', STR_PAD_LEFT);

if ($diaHoy <= 15) {
    $fIni = "{$anoHoy}-{$padM}-01";
    $fFin = "{$anoHoy}-{$padM}-15";
    $numQ = 1;
} else {
    $ultimoDia = date('t');
    $fIni = "{$anoHoy}-{$padM}-16";
    $fFin = "{$anoHoy}-{$padM}-{$ultimoDia}";
    $numQ = 2;
}

$meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
$nombreMes = $meses[$mesHoy - 1];
$nombreQuincena = ($numQ === 1 ? "1ra" : "2da") . " Quincena de {$nombreMes} {$anoHoy}";

// 1. Entregas de Hoy
try {
    $stmtHoy = $pdo->prepare("SELECT turno, litros FROM registros_leche WHERE asociadoId = ? AND fecha = ?");
    $stmtHoy->execute([$usuario['id'], $hoyStr]);
    $entregasHoy = $stmtHoy->fetchAll();
    if (empty($entregasHoy)) {
        $stmtHoyOld = $pdo->prepare("SELECT turno, litros FROM entrega WHERE usuarioId = ? AND fecha = ?");
        $stmtHoyOld->execute([$usuario['id'], $hoyStr]);
        $entregasHoy = $stmtHoyOld->fetchAll();
    }
} catch (Exception $e) {
    $entregasHoy = [];
}

$litrosMananaHoy = 0.0;
$litrosTardeHoy = 0.0;
foreach ($entregasHoy as $e) {
    if ($e['turno'] === 'MANANA') $litrosMananaHoy = (float)$e['litros'];
    if ($e['turno'] === 'TARDE') $litrosTardeHoy = (float)$e['litros'];
}
$totalDiaHoy = $litrosMananaHoy + $litrosTardeHoy;

// 2. Información Quincena Actual
try {
    $stmtQ = $pdo->prepare("SELECT precioLitro FROM configuracion_quincena WHERE fechaInicio = ? AND fechaFin = ? LIMIT 1");
    $stmtQ->execute([$fIni, $fFin]);
    $qRow = $stmtQ->fetch();
    if (!$qRow) {
        $stmtQOld = $pdo->prepare("SELECT precioLitro FROM quincena WHERE fechaInicio = ? AND fechaFin = ? LIMIT 1");
        $stmtQOld->execute([$fIni, $fFin]);
        $qRow = $stmtQOld->fetch();
    }
} catch (Exception $e) {
    $qRow = null;
}
$precioLitro = $qRow && isset($qRow['precioLitro']) ? (float)$qRow['precioLitro'] : 0.0;

// 3. Litros Acumulados de la Quincena Actual
try {
    $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(litros), 0) AS total FROM registros_leche WHERE asociadoId = ? AND fecha >= ? AND fecha <= ?");
    $stmtSum->execute([$usuario['id'], $fIni, $fFin]);
    $totalLitrosQuincena = (float)($stmtSum->fetch()['total'] ?? 0);
    if ($totalLitrosQuincena == 0) {
        $stmtSumOld = $pdo->prepare("SELECT COALESCE(SUM(litros), 0) AS total FROM entrega WHERE usuarioId = ? AND fecha >= ? AND fecha <= ?");
        $stmtSumOld->execute([$usuario['id'], $fIni, $fFin]);
        $totalLitrosQuincena = (float)($stmtSumOld->fetch()['total'] ?? 0);
    }
} catch (Exception $e) {
    $totalLitrosQuincena = 0.0;
}
$pagoEstimado = $precioLitro ? ($totalLitrosQuincena * $precioLitro) : 0.0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Portal del Ganadero | Asociación de Ganaderos</title>
  <link rel="stylesheet" href="./css/estilos.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            campo: {
              50: '#f2f8f3', 100: '#e1efe4', 200: '#c4dfcb', 300: '#99c6a4',
              400: '#6aa778', 500: '#498b58', 600: '#366e43', 700: '#2d5737',
              800: '#26462e', 900: '#203a27', 950: '#0d1f13'
            },
            tierra: {
              50: '#faf6f1', 100: '#f2eae0', 200: '#e4d3c0', 300: '#d3b79a',
              400: '#c09774', 500: '#af7e56', 600: '#996748', 700: '#7c503b',
              800: '#664234', 900: '#54372e'
            },
            leche: { 50: '#ffffff', 100: '#fcfbf7', 200: '#f7f5ed', 300: '#ede8d8' },
            dorado: { 400: '#f59e0b', 500: '#d97706', 600: '#b45309' }
          }
        }
      }
    }
    window.GANADERO_NOMBRE = <?php echo json_encode($usuario['nombre']); ?>;
    window.GANADERO_CEDULA = <?php echo json_encode($usuario['cedula']); ?>;
    window.PRECIO_LITRO_INICIAL = <?php echo json_encode($precioLitro); ?>;
  </script>
</head>
<body class="bg-leche-100 text-campo-950 min-h-screen flex flex-col font-sans antialiased">

  <!-- CABECERA DEL PANEL -->
  <header class="bg-campo-900 text-white shadow-md sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">
        <!-- Logo y Ganadero -->
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl bg-campo-700 flex items-center justify-center text-2xl shadow">
            🥛
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-black text-base sm:text-lg text-white">
                <?php echo htmlspecialchars($usuario['nombre']); ?>
              </span>
              <span class="bg-campo-800 text-dorado-300 text-[10px] font-black uppercase px-2 py-0.5 rounded-full border border-campo-600">
                Ganadero
              </span>
            </div>
            <p class="text-xs text-campo-200 font-mono">
              C.C. <?php echo htmlspecialchars($usuario['cedula']); ?> &bull; Asociación de Ganaderos
            </p>
          </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center gap-2.5">
          <button type="button" onclick="abrirModalCambiarPasswordGanadero()" class="btn-touch bg-campo-800 hover:bg-campo-700 text-campo-100 text-xs font-bold px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 border border-campo-700 shadow-sm" title="Cambiar mi contraseña">
            <span>🔑</span>
            <span class="hidden sm:inline">Cambiar Clave</span>
          </button>
          <a href="./index.php" class="hidden sm:inline-block text-xs font-bold text-campo-200 hover:text-white transition-colors">
            Ver Sitio Público
          </a>
          <a href="./logout.php" class="btn-touch bg-red-700/80 hover:bg-red-800 text-white text-xs sm:text-sm font-bold px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 shadow">
            <span>Cerrar Sesión</span>
            <span>🚪</span>
          </a>
        </div>
      </div>
    </div>
  </header>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- 1. TARJETA GRANDE: REGISTRO DEL DÍA (HOY) -->
    <div class="bg-white rounded-3xl shadow-lg border-2 border-campo-200 overflow-hidden">
      <div class="bg-gradient-to-r from-campo-800 to-campo-900 text-white p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-2xl">
            📅
          </div>
          <div>
            <p class="text-xs uppercase font-bold tracking-wider text-campo-200">Pesaje Diario</p>
            <h2 class="text-xl sm:text-2xl font-black text-white">Registro de Hoy</h2>
          </div>
        </div>
        <div class="text-xs sm:text-sm font-bold text-campo-100 bg-black/20 px-3.5 py-1.5 rounded-xl border border-campo-700/50 w-max">
          <?php echo date('d') . " de " . $nombreMes . " de " . date('Y'); ?>
        </div>
      </div>

      <div class="p-6 sm:p-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Mañana -->
        <div class="bg-amber-50/60 rounded-2xl p-6 border-2 border-amber-200 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-black uppercase tracking-wider text-amber-900">☀️ Turno Mañana</span>
            <span class="text-xs font-bold text-amber-700 bg-amber-100 px-2 py-0.5 rounded-full">Ordeño 5:00 AM</span>
          </div>
          <div>
            <span id="litros-manana-hoy" class="text-4xl sm:text-5xl font-black text-amber-950">
              <?php echo number_format($litrosMananaHoy, 1); ?>
            </span>
            <span class="ml-1 text-base font-bold text-amber-800">Litros</span>
          </div>
        </div>

        <!-- Tarde -->
        <div class="bg-orange-50/60 rounded-2xl p-6 border-2 border-orange-200 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-black uppercase tracking-wider text-orange-900">🌅 Turno Tarde</span>
            <span class="text-xs font-bold text-orange-700 bg-orange-100 px-2 py-0.5 rounded-full">Ordeño 3:00 PM</span>
          </div>
          <div>
            <span id="litros-tarde-hoy" class="text-4xl sm:text-5xl font-black text-orange-950">
              <?php echo number_format($litrosTardeHoy, 1); ?>
            </span>
            <span class="ml-1 text-base font-bold text-orange-800">Litros</span>
          </div>
        </div>

        <!-- Total Hoy -->
        <div class="bg-campo-50/80 rounded-2xl p-6 border-2 border-campo-300 shadow-sm flex flex-col justify-between">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs font-black uppercase tracking-wider text-campo-950">🥛 Total Entregado Hoy</span>
            <span class="text-xs font-bold text-campo-700 bg-campo-200 px-2 py-0.5 rounded-full">Suma del Día</span>
          </div>
          <div>
            <span id="total-dia-hoy" class="text-4xl sm:text-5xl font-black text-campo-900">
              <?php echo number_format($totalDiaHoy, 1); ?>
            </span>
            <span class="ml-1 text-base font-bold text-campo-700">Litros</span>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. TARJETA PRINCIPAL: QUINCENA EN CURSO -->
    <div class="bg-white rounded-3xl shadow-lg border-2 border-campo-200 overflow-hidden">
      <!-- Cabecera Café Tierra -->
      <div class="bg-gradient-to-r from-tierra-800 to-tierra-900 text-white p-5 sm:p-6">
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-2xl text-tierra-200">
            📊
          </div>
          <div>
            <p class="text-xs uppercase font-bold tracking-wider text-tierra-200">Periodo Activo</p>
            <h2 id="nombre-quincena-texto" class="text-2xl sm:text-3xl font-extrabold text-white">
              <?php echo $nombreQuincena; ?>
            </h2>
            <p class="text-xs text-tierra-200 mt-0.5">
              Periodo de entrega: <strong id="rango-fechas-texto" class="text-white underline"><?php echo $fIni . " al " . $fFin; ?></strong>
            </p>
          </div>
        </div>
      </div>

      <!-- Métricas -->
      <div class="p-6 sm:p-8 space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Litros Acumulados -->
          <div class="bg-campo-50/70 rounded-2xl p-6 border-2 border-campo-200 shadow-sm flex flex-col justify-between">
            <span class="text-xs font-black text-campo-950 uppercase tracking-wide">Litros Acumulados</span>
            <div class="mt-2">
              <span id="total-litros-display" class="text-4xl sm:text-5xl font-black text-campo-900">
                <?php echo number_format($totalLitrosQuincena, 1); ?>
              </span>
              <span class="ml-1 text-base font-bold text-campo-700">Litros</span>
              <p class="text-xs text-gray-500 mt-1">Suma de tus entregas en la quincena</p>
            </div>
          </div>

          <!-- Precio por Litro -->
          <div class="bg-campo-50/70 rounded-2xl p-6 border-2 border-campo-200 shadow-sm flex flex-col justify-between">
            <span class="text-xs font-black text-campo-950 uppercase tracking-wide">Precio por Litro</span>
            <div class="mt-2">
              <span id="precio-litro-display" class="text-2xl sm:text-3xl font-black text-campo-900">
                <?php echo $precioLitro ? "$" . number_format($precioLitro, 0, ',', '.') . " COP" : "Pendiente por fijar"; ?>
              </span>
              <p class="text-xs text-gray-500 mt-1">Tarifa oficial de la asociación</p>
            </div>
          </div>

          <!-- Pago Estimado -->
          <div class="bg-campo-50/70 rounded-2xl p-6 border-2 border-campo-200 shadow-sm flex flex-col justify-between">
            <span class="text-xs font-black text-campo-950 uppercase tracking-wide">Pago Estimado</span>
            <div class="mt-2">
              <span id="pago-estimado-display" class="text-2xl sm:text-3xl font-black text-campo-900">
                <?php echo $pagoEstimado ? "$" . number_format($pagoEstimado, 0, ',', '.') . " COP" : "$0 COP"; ?>
              </span>
              <p class="text-xs text-gray-500 mt-1">Liquidación sujeta al cierre quincenal</p>
            </div>
          </div>
        </div>

        <!-- 3. PLANILLA DE EXCEL QUINCENAL (SOLO LECTURA ESTRICTA) -->
        <div class="pt-6 border-t border-gray-200 space-y-4">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h3 class="text-lg sm:text-xl font-black text-campo-950 flex items-center gap-2">
                <span>📋 Historial Detallado de la Quincena</span>
                <span class="text-xs font-bold bg-campo-100 text-campo-800 px-2.5 py-0.5 rounded-full border border-campo-300">
                  Planilla Oficial (Solo Lectura)
                </span>
              </h3>
              <p class="text-xs text-gray-500">
                Consulta tus pesajes diarios por fecha en turnos de Mañana y Tarde.
              </p>
            </div>

            <!-- Selector de Quincena -->
            <div class="flex items-center gap-2">
              <label for="selector-quincena-ganadero" class="text-xs font-bold text-gray-700 whitespace-nowrap">
                Consultar Periodo:
              </label>
              <select id="selector-quincena-ganadero"
                      class="px-3 py-2 text-xs font-bold border-2 border-campo-300 rounded-xl bg-white text-campo-950 focus:outline-none focus:border-campo-600">
                <!-- Llenado dinámicamente por panel.js -->
              </select>
            </div>
          </div>

          <!-- Contenedor donde se dibuja la tabla matricial de 15 días -->
          <div id="contenedor-tabla-ganadero" class="bg-white rounded-2xl border-2 border-campo-200 overflow-hidden">
            <!-- Renderizado dinámicamente -->
          </div>

          <!-- SECCIÓN DE RECLAMOS / INCONFORMIDAD (LÍMITE 1 POR QUINCENA) -->
          <div class="pt-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div id="estado-queja-aviso" class="w-full hidden">
              <!-- Aviso si ya envió reclamo para esta quincena -->
            </div>

            <button type="button" id="btn-abrir-queja"
                    class="btn-touch px-6 py-3 rounded-xl bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs sm:text-sm shadow transition-all flex items-center gap-2">
              <span>⚠️ ¿Tienes alguna inconformidad con tus litros registrados?</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- MODAL DE QUEJA / INCONFORMIDAD -->
  <div id="modal-queja" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 shadow-2xl border-2 border-campo-200 relative animate-in fade-in zoom-in duration-200">
      <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
        <h3 class="text-xl font-black text-gray-900 flex items-center gap-2">
          <span>⚠️ Registrar Inconformidad Quincenal</span>
        </h3>
        <button type="button" id="btn-cerrar-queja" class="text-gray-400 hover:text-gray-700 text-lg font-bold p-1">
          ✕
        </button>
      </div>

      <p class="text-xs text-gray-600 mb-4">
        Escribe con claridad el motivo de tu reclamo sobre el pesaje de esta quincena. El mensaje se guardará en el sistema y se enviará por correo electrónico a la administración (<strong>asoagroalisal@gmail.com</strong>).
      </p>

      <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs font-bold text-amber-900 mb-4">
        ℹ️ Restricción del sistema: Solo se permite registrar <strong>UNA (1) inconformidad</strong> por cada periodo quincenal.
      </div>

      <form id="form-queja" class="space-y-4">
        <div>
          <label for="queja-mensaje" class="block text-xs font-bold text-gray-700 mb-1">
            Detalle del Reclamo *
          </label>
          <textarea id="queja-mensaje" rows="4" required
                    placeholder="Describe las fechas o turnos con los que estás inconforme..."
                    class="w-full px-3.5 py-2.5 text-sm border-2 border-gray-300 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100"></textarea>
        </div>

        <div class="flex justify-end gap-3 pt-2">
          <button type="button" onclick="document.getElementById('modal-queja').classList.add('hidden')"
                  class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-bold text-xs hover:bg-gray-100">
            Cancelar
          </button>
          <button type="submit" id="btn-enviar-queja"
                  class="px-6 py-2.5 rounded-xl bg-campo-700 hover:bg-campo-800 text-white font-bold text-xs shadow flex items-center gap-2">
            <span>Enviar Inquietud al Administrador</span>
            <span>✉️</span>
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL CAMBIAR CONTRASEÑA GANADERO -->
  <div id="modal-cambiar-password-ganadero" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 sm:p-7 shadow-2xl border-2 border-campo-200">
      <div class="flex items-center justify-between mb-4 pb-2 border-b">
        <h3 class="font-black text-lg text-gray-900 flex items-center gap-2">
          <span>🔑</span>
          <span>Cambiar mi Contraseña</span>
        </h3>
        <button type="button" onclick="cerrarModalCambiarPasswordGanadero()" class="text-gray-400 hover:text-black font-bold p-1">✕</button>
      </div>
      <div id="msg-modal-pass-ganadero" class="hidden p-3 rounded-xl text-xs font-bold mb-3"></div>
      <form id="form-cambiar-password-ganadero" class="space-y-3">
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Contraseña Actual *</label>
          <input type="password" name="passwordActual" required placeholder="Ingresa tu clave actual" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Nueva Contraseña * (Mínimo 8 caracteres)</label>
          <input type="password" name="passwordNueva" required placeholder="Escribe tu nueva contraseña" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Confirmar Nueva Contraseña *</label>
          <input type="password" name="passwordConfirm" required placeholder="Repite la nueva contraseña" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-900">
          ⚠️ Al cambiar tu contraseña, cualquier otra sesión abierta en otro dispositivo se cerrará automáticamente.
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" onclick="cerrarModalCambiarPasswordGanadero()" class="px-4 py-2 border rounded-xl text-xs font-bold">Cancelar</button>
          <button type="submit" id="btn-submit-modal-pass-g" class="px-5 py-2 bg-campo-700 hover:bg-campo-800 text-white rounded-xl text-xs font-bold shadow">Guardar Nueva Clave</button>
        </div>
      </form>
    </div>
  </div>

  <footer class="bg-campo-950 text-white py-6 text-center text-xs text-campo-400 mt-auto border-t border-campo-900">
    &copy; <?php echo date('Y'); ?> Asociación de Ganaderos Unión y Progreso Lechero &bull; XAMPP Native Edition
  </footer>

  <script src="./js/app.js"></script>
  <script src="./js/panel.js"></script>
  <script>
    function abrirModalCambiarPasswordGanadero() {
      const modal = document.getElementById("modal-cambiar-password-ganadero");
      const form = document.getElementById("form-cambiar-password-ganadero");
      const msg = document.getElementById("msg-modal-pass-ganadero");
      if (form) form.reset();
      if (msg) msg.classList.add("hidden");
      if (modal) modal.classList.remove("hidden");
    }

    function cerrarModalCambiarPasswordGanadero() {
      document.getElementById("modal-cambiar-password-ganadero")?.classList.add("hidden");
    }

    document.getElementById("form-cambiar-password-ganadero")?.addEventListener("submit", async (e) => {
      e.preventDefault();
      const form = e.target;
      const btn = document.getElementById("btn-submit-modal-pass-g");
      const msg = document.getElementById("msg-modal-pass-ganadero");

      const pActual = form.passwordActual.value;
      const pNueva = form.passwordNueva.value;
      const pConf = form.passwordConfirm.value;

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
            cerrarModalCambiarPasswordGanadero();
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
        btn.textContent = "Guardar Nueva Clave";
      }
    });
  </script>
</body>
</html>

