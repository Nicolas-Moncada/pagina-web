<?php
require_once __DIR__ . '/api/db.php';

$usuario = requireAuth('ADMIN');
if (!empty($usuario['debeCambiarPassword'])) {
    header('Location: cambiar_password.php');
    exit;
}
$pdo = getDB();

// Métricas generales
try {
    $totalGanaderos = (int)$pdo->query("SELECT COUNT(*) FROM asociados WHERE rol = 'GANADERO'")->fetchColumn();
    if ($totalGanaderos === 0) {
        $totalGanaderos = (int)$pdo->query("SELECT COUNT(*) FROM usuario WHERE rol = 'GANADERO'")->fetchColumn();
    }
    $totalEntregas = (int)$pdo->query("SELECT COUNT(*) FROM registros_leche")->fetchColumn();
    $totalAlertas = (int)$pdo->query("SELECT COUNT(*) FROM quejas WHERE estado = 'PENDIENTE'")->fetchColumn();
    $totalConsultas = (int)$pdo->query("SELECT COUNT(*) FROM consulta WHERE atendida = 0")->fetchColumn();
} catch (Exception $e) {
    $totalGanaderos = 0;
    $totalEntregas = 0;
    $totalAlertas = 0;
    $totalConsultas = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Panel de Administración | Asociación de Ganaderos</title>
  <link rel="stylesheet" href="./css/estilos.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
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
  </script>
</head>
<body class="bg-leche-100 text-campo-950 min-h-screen flex flex-col font-sans antialiased">

  <!-- CABECERA SUPERIOR DEL ADMINISTRADOR -->
  <header class="bg-campo-950 text-white shadow-md sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">
        <!-- Logo y Admin Info -->
        <div class="flex items-center gap-3">
          <div class="w-12 h-12 rounded-xl bg-campo-700 flex items-center justify-center text-2xl shadow">
            🥛
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-black text-base sm:text-lg text-white">Panel de Administración</span>
              <span class="bg-dorado-500 text-campo-950 text-[10px] font-black uppercase px-2 py-0.5 rounded-full">
                Admin
              </span>
            </div>
            <p class="text-xs text-campo-200 font-mono">
              <?php echo htmlspecialchars($usuario['nombre']); ?> &bull; Cédula: <?php echo htmlspecialchars($usuario['cedula']); ?>
            </p>
          </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center gap-2.5">
          <button type="button" onclick="abrirModalCambiarPassword()" class="btn-touch bg-campo-800 hover:bg-campo-700 text-campo-100 text-xs font-bold px-3 py-2 rounded-xl transition-all flex items-center gap-1.5 border border-campo-600 shadow-sm" title="Cambiar mi contraseña">
            <span>🔑</span>
            <span class="hidden sm:inline">Cambiar Clave</span>
          </button>
          <a href="./index.php" class="hidden sm:inline-block text-xs font-bold text-campo-200 hover:text-white transition-colors">
            Sitio Público
          </a>
          <a href="./logout.php" class="btn-touch bg-red-700 hover:bg-red-800 text-white text-xs sm:text-sm font-bold px-4 py-2 rounded-xl transition-all flex items-center gap-1.5 shadow">
            <span>Cerrar Sesión</span>
            <span>🚪</span>
          </a>
        </div>
      </div>

      <!-- Barra de Pestañas / Módulos -->
      <div class="flex overflow-x-auto gap-2 py-2.5 border-t border-campo-800 text-xs font-bold">
        <button type="button" class="tab-btn px-4 py-2 rounded-xl bg-campo-800 text-dorado-300 border border-campo-600 transition-all flex items-center gap-2 active-tab" data-tab="tab-planilla">
          <span>📋 Planilla Quincenal de Litros</span>
        </button>
        <button type="button" class="tab-btn px-4 py-2 rounded-xl text-campo-200 hover:bg-campo-800 transition-all flex items-center gap-2" data-tab="tab-ganaderos">
          <span>🧑‍🌾 Gestión de Asociados</span>
        </button>
        <button type="button" class="tab-btn px-4 py-2 rounded-xl text-campo-200 hover:bg-campo-800 transition-all flex items-center gap-2" data-tab="tab-quincenas">
          <span>💲 Configuración de Precios</span>
        </button>
        <button type="button" class="tab-btn px-4 py-2 rounded-xl text-campo-200 hover:bg-campo-800 transition-all flex items-center gap-2" data-tab="tab-buzon">
          <span>📬 Bandeja de Quejas y Consultas</span>
          <?php if ($totalAlertas + $totalConsultas > 0): ?>
            <span class="bg-red-500 text-white text-[10px] px-1.5 py-0.2 rounded-full"><?php echo $totalAlertas + $totalConsultas; ?></span>
          <?php endif; ?>
        </button>
      </div>
    </div>
  </header>

  <!-- CONTENIDO PRINCIPAL -->
  <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

    <!-- ============================================================== -->
    <!-- PESTAÑA 1: PLANILLA QUINCENAL DE LITROS (ESTILO EXCEL)         -->
    <!-- ============================================================== -->
    <div id="tab-planilla" class="tab-content space-y-8">
      <div>
        <h1 class="text-2xl sm:text-3xl font-extrabold text-campo-950">
          Planilla Quincenal de Entregas de Leche
        </h1>
        <p class="text-gray-600 text-xs sm:text-sm mt-0.5">
          Formato matricial horizontal tipo Excel por fechas (Mañana ☀️ y Tarde 🌅). Edita los litros directamente haciendo clic sobre cualquier celda.
        </p>
      </div>

      <!-- Formulario de Registro Rápido Superior -->
      <div class="bg-white rounded-3xl p-6 sm:p-7 shadow-sm border-2 border-campo-200">
        <div class="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
          <div class="w-10 h-10 rounded-xl bg-campo-100 text-campo-800 flex items-center justify-center text-xl">
            🥛
          </div>
          <div>
            <h2 class="text-lg sm:text-xl font-bold text-gray-900">
              Registrar o Modificar Litros en la Planilla
            </h2>
            <p class="text-xs text-gray-500">
              Al guardar, el pesaje se refleja inmediatamente en la casilla de la planilla sin duplicar filas.
            </p>
          </div>
        </div>

        <form id="form-registro-rapido" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Ganadero -->
          <div class="lg:col-span-1">
            <label class="block text-xs font-bold text-gray-700 mb-1">Ganadero Asociado *</label>
            <select id="form-ganadero-id" required
                    class="w-full px-3 py-2.5 text-xs sm:text-sm border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
              <option value="">Cargando asociados...</option>
            </select>
          </div>

          <!-- Fecha -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Fecha de Entrega *</label>
            <input type="date" id="form-fecha" required
                   class="w-full px-3 py-2.5 text-xs sm:text-sm border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
          </div>

          <!-- Turno -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Turno del Día *</label>
            <div class="grid grid-cols-2 gap-2">
              <button type="button" id="btn-turno-manana"
                      class="py-2 px-2 rounded-xl text-xs font-bold border-2 transition-all flex items-center justify-center gap-1 bg-amber-100 border-amber-500 text-amber-900 shadow-sm">
                <span>☀️ Mañana</span>
              </button>
              <button type="button" id="btn-turno-tarde"
                      class="py-2 px-2 rounded-xl text-xs font-bold border-2 transition-all flex items-center justify-center gap-1 bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100">
                <span>🌅 Tarde</span>
              </button>
            </div>
          </div>

          <!-- Litros -->
          <div>
            <label class="block text-xs font-bold text-gray-700 mb-1">Litros Entregados *</label>
            <div class="relative">
              <input type="number" step="0.1" min="0.1" id="form-litros" required placeholder="Ej: 85.5"
                     class="w-full px-3 py-2.5 pr-10 text-base font-bold border-2 border-gray-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
              <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-xs">L</span>
            </div>
          </div>

          <!-- Botón Guardar -->
          <div class="sm:col-span-2 lg:col-span-4 pt-1 flex justify-end">
            <button type="submit"
                    class="btn-touch w-full sm:w-auto bg-campo-700 hover:bg-campo-800 text-white font-bold text-xs sm:text-sm px-6 py-2.5 flex items-center justify-center gap-2 rounded-xl shadow">
              <span>+</span>
              <span>Guardar en Planilla</span>
            </button>
          </div>
        </form>
      </div>

      <!-- SECCIÓN DE LA PLANILLA MATRICIAL -->
      <div class="bg-white rounded-3xl shadow-md border-2 border-campo-200 overflow-hidden">
        <!-- Barra de Control: Quincena y Buscador -->
        <div class="bg-gradient-to-r from-campo-900 via-campo-800 to-tierra-900 text-white p-5 sm:p-6 space-y-4">
          <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="w-11 h-11 rounded-2xl bg-white/10 flex items-center justify-center text-dorado-400 text-xl flex-shrink-0">
                📊
              </div>
              <div>
                <p class="text-xs uppercase font-bold tracking-wider text-campo-200">Planilla Oficial de Pesajes</p>
                <h2 id="nombre-quincena-admin" class="text-xl sm:text-2xl font-black text-white">Cargando periodo...</h2>
              </div>
            </div>

            <!-- Selector de Quincena y Exportar -->
            <div class="w-full lg:w-auto flex flex-wrap items-center gap-2">
              <button type="button" id="btn-quincena-ant"
                      class="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs flex items-center gap-1 border border-white/20">
                <span>◀ Anterior</span>
              </button>
              <select id="selector-quincena-admin"
                      class="flex-1 sm:flex-initial px-3 py-2 rounded-xl bg-campo-950 text-white font-bold text-xs sm:text-sm border border-campo-600 focus:outline-none focus:ring-2 focus:ring-dorado-400">
              </select>
              <button type="button" id="btn-quincena-sig"
                      class="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white font-bold text-xs flex items-center gap-1 border border-white/20">
                <span>Siguiente ▶</span>
              </button>
              <button type="button" id="btn-exportar-excel"
                      class="px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-bold text-xs flex items-center gap-1.5 shadow transition-all border border-emerald-500">
                <span>📥 Exportar a Excel (.xlsx)</span>
              </button>
            </div>
          </div>

          <!-- Rango Fechas + Buscador -->
          <div class="pt-3 border-t border-campo-700/80 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-xs sm:text-sm">
            <div class="flex items-center gap-2 text-campo-100">
              <span>📅</span>
              <span>Periodo: <strong id="rango-fechas-admin" class="text-white underline font-semibold">...</strong></span>
            </div>

            <div class="w-full sm:w-72 relative">
              <input type="text" id="busqueda-ganadero" placeholder="Buscar por nombre o cédula..."
                     class="w-full pl-8 pr-8 py-1.5 rounded-xl bg-campo-950/80 text-white text-xs border border-campo-600 placeholder-campo-300 focus:outline-none focus:ring-2 focus:ring-dorado-400">
              <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-campo-300 text-xs">🔍</span>
              <button type="button" id="btn-limpiar-busqueda" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-campo-300 hover:text-white hidden">✕</button>
            </div>
          </div>
        </div>

        <!-- Leyenda -->
        <div class="bg-campo-50/70 px-6 py-2.5 border-b border-campo-200 flex flex-wrap items-center justify-between gap-3 text-xs text-campo-900">
          <div class="flex items-center gap-4">
            <span class="font-bold flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span> ☀️ Mañana</span>
            <span class="font-bold flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-orange-400 inline-block"></span> 🌅 Tarde</span>
            <span class="text-gray-500 hidden sm:inline">|</span>
            <span class="text-gray-600 hidden sm:inline">Haz clic sobre cualquier celda para <strong>ingresar</strong>, <strong>editar</strong> o <strong>eliminar</strong> litros.</span>
          </div>
          <div id="contador-ganaderos" class="text-campo-800 font-bold">5 asociados en planilla</div>
        </div>

        <!-- Contenedor de la Planilla -->
        <div id="contenedor-tabla-admin">
          <!-- Renderizado dinámicamente de forma instantánea -->
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- PESTAÑA 2: GESTIÓN DE ASOCIADOS                                -->
    <!-- ============================================================== -->
    <div id="tab-ganaderos" class="tab-content hidden space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-campo-950">Gestión de Asociados (Ganaderos)</h2>
          <p class="text-xs text-gray-600">Consulta los asociados registrados, su finca, teléfono, estado y litros totales entregados en la quincena.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
          <div class="relative w-full sm:w-64">
            <input type="text" id="busqueda-asociados-modulo" placeholder="Buscar por nombre o cédula..."
                   class="w-full pl-8 pr-3 py-2 text-xs border-2 border-gray-300 rounded-xl focus:outline-none focus:border-campo-600 bg-white">
            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs">🔍</span>
          </div>
          <button type="button" onclick="abrirModalNuevoAsociado()"
                  class="btn-touch px-5 py-2.5 rounded-xl bg-campo-700 hover:bg-campo-800 text-white font-bold text-xs sm:text-sm shadow flex items-center gap-2">
            <span>➕ Agregar Nuevo Asociado</span>
          </button>
        </div>
      </div>

      <div class="bg-white rounded-3xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs sm:text-sm text-gray-700">
            <thead class="bg-gray-100 text-xs uppercase font-bold text-gray-600 border-b border-gray-200">
              <tr>
                <th class="px-6 py-4">Asociado</th>
                <th class="px-6 py-4">Cédula</th>
                <th class="px-6 py-4">Finca / Teléfono</th>
                <th class="px-6 py-4 text-right">Total Quincena (L)</th>
                <th class="px-6 py-4 text-center">Estado</th>
                <th class="px-6 py-4 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody-ganaderos-modulo" class="divide-y divide-gray-200">
              <!-- Renderizado de forma inmediata por admin.js -->
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- PESTAÑA 3: PRECIOS DE QUINCENA                                 -->
    <!-- ============================================================== -->
    <?php
    try {
        $stmtQList = $pdo->query("SELECT fechaInicio, fechaFin, precioLitro, cerrada FROM configuracion_quincena ORDER BY fechaInicio DESC");
        $quincenasDB = $stmtQList->fetchAll();
        if (empty($quincenasDB)) {
            $stmtQList2 = $pdo->query("SELECT fechaInicio, fechaFin, precioLitro, cerrada FROM quincena ORDER BY fechaInicio DESC");
            $quincenasDB = $stmtQList2->fetchAll();
        }
    } catch (Exception $e) {
        $quincenasDB = [];
    }
    $primeraQ = !empty($quincenasDB) ? $quincenasDB[0] : [
        'fechaInicio' => '2027-01-01',
        'fechaFin' => '2027-01-15',
        'precioLitro' => 0,
        'cerrada' => 0
    ];
    ?>
    <div id="tab-quincenas" class="tab-content hidden space-y-6">
      <div>
        <h2 class="text-2xl font-black text-campo-950">Configuración de Precios y Quincenas</h2>
        <p class="text-xs text-gray-600">Fija el valor oficial del litro de leche para la quincena actual.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Formulario fijar precio -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-200 space-y-4">
          <h3 class="font-bold text-base text-gray-900">Fijar Precio Oficial</h3>
          <form id="form-fijar-precio" class="space-y-4">
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Fecha Inicio (YYYY-MM-DD)</label>
              <input type="date" name="fechaInicio" value="<?php echo htmlspecialchars($primeraQ['fechaInicio']); ?>" required class="w-full px-3 py-2 text-xs border rounded-xl bg-white">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Fecha Fin (YYYY-MM-DD)</label>
              <input type="date" name="fechaFin" value="<?php echo htmlspecialchars($primeraQ['fechaFin']); ?>" required class="w-full px-3 py-2 text-xs border rounded-xl bg-white">
            </div>
            <div>
              <label class="block text-xs font-bold text-gray-700 mb-1">Precio por Litro ($ COP)</label>
              <input type="number" step="1" name="precioLitro" value="<?php echo (float)$primeraQ['precioLitro']; ?>" required placeholder="Ej: 2250" class="w-full px-3 py-2 text-base border rounded-xl font-bold bg-white text-campo-900">
            </div>
            <button type="submit" class="w-full py-2.5 bg-campo-700 hover:bg-campo-800 text-white font-bold text-xs rounded-xl shadow">
              Guardar Precio Oficial
            </button>
          </form>
        </div>

        <!-- Tabla de Quincenas -->
        <div class="md:col-span-2 bg-white rounded-3xl p-6 shadow-sm border border-gray-200">
          <h3 class="font-bold text-base text-gray-900 mb-3">Historial de Quincenas</h3>
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-gray-100 text-gray-600 font-bold uppercase">
                <tr>
                  <th class="p-3">Periodo</th>
                  <th class="p-3 text-right">Precio / Litro</th>
                  <th class="p-3 text-center">Estado</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100" id="tabla-historial-quincenas-body">
                <?php if (empty($quincenasDB)): ?>
                  <tr><td colspan="3" class="p-4 text-center text-gray-500">No hay quincenas configuradas aún.</td></tr>
                <?php else: ?>
                  <?php foreach ($quincenasDB as $qItem): ?>
                    <tr>
                      <td class="p-3 font-semibold"><?php echo htmlspecialchars($qItem['fechaInicio'] . ' al ' . $qItem['fechaFin']); ?></td>
                      <td class="p-3 text-right font-black text-campo-900">$<?php echo number_format((float)$qItem['precioLitro'], 0, ',', '.'); ?> COP</td>
                      <td class="p-3 text-center">
                        <?php if (empty($qItem['cerrada'])): ?>
                          <span class="bg-green-100 text-green-800 text-[10px] font-bold px-2 py-0.5 rounded-full">En Curso</span>
                        <?php else: ?>
                          <span class="bg-gray-200 text-gray-700 text-[10px] font-bold px-2 py-0.5 rounded-full">Cerrada</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- ============================================================== -->
    <!-- PESTAÑA 4: BANDEJA DE QUEJAS Y CONSULTAS                       -->
    <!-- ============================================================== -->
    <div id="tab-buzon" class="tab-content hidden space-y-6">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h2 class="text-2xl font-black text-campo-950">Bandeja de Quejas y Consultas</h2>
          <p class="text-xs text-gray-600">Monitoreo de inquietudes quincenales enviadas por los ganaderos y consultas de la web pública.</p>
        </div>
        <div class="flex items-center gap-2 bg-gray-200 p-1 rounded-2xl overflow-x-auto">
          <button type="button" id="btn-buzon-consultas" onclick="cambiarSubBuzon('consultas')"
                  class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all bg-white text-campo-950 shadow-sm whitespace-nowrap">
            <span>✉️ Consultas Web</span>
          </button>
          <button type="button" id="btn-buzon-quejas" onclick="cambiarSubBuzon('quejas')"
                  class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all text-gray-600 hover:text-gray-900 whitespace-nowrap">
            <span>⚠️ Inconformidades</span>
          </button>
          <button type="button" id="btn-buzon-recuperaciones" onclick="cambiarSubBuzon('recuperaciones')"
                  class="px-3.5 py-2 rounded-xl text-xs font-bold transition-all text-gray-600 hover:text-gray-900 whitespace-nowrap">
            <span>🔑 Solicitudes de Clave</span>
          </button>
        </div>
      </div>

      <!-- SECCIÓN A: CONSULTAS WEB PÚBLICAS -->
      <div id="subseccion-consultas" class="bg-white rounded-3xl p-6 shadow-sm border border-gray-200 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-base text-gray-900 flex items-center gap-2">
            <span>✉️ Mensajes y Preguntas de Contacto Web</span>
          </h3>
          <span id="badge-contador-consultas" class="text-xs font-bold bg-campo-100 text-campo-800 px-2.5 py-1 rounded-full">
            Cargando mensajes...
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-campo-100/70 text-campo-950 uppercase font-bold">
              <tr>
                <th class="p-3">Fecha</th>
                <th class="p-3">Nombre Remitente</th>
                <th class="p-3">Contacto (Tel / Correo)</th>
                <th class="p-3">Mensaje / Inquietud</th>
                <th class="p-3 text-center">Estado</th>
                <th class="p-3 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody-consultas-admin" class="divide-y divide-gray-100">
              <tr><td colspan="6" class="p-6 text-center text-gray-400 font-bold">Cargando consultas...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- SECCIÓN B: ALERTAS DE GANADEROS -->
      <div id="subseccion-quejas" class="hidden bg-white rounded-3xl p-6 shadow-sm border border-gray-200 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-base text-gray-900 flex items-center gap-2">
            <span>⚠️ Reclamos de Ganaderos por Quincena</span>
          </h3>
          <span id="badge-contador-quejas" class="text-xs font-bold bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full">
            Cargando reclamos...
          </span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-amber-100/70 text-amber-950 uppercase font-bold">
              <tr>
                <th class="p-3">Fecha y Hora</th>
                <th class="p-3">Ganadero</th>
                <th class="p-3">Periodo</th>
                <th class="p-3 text-right">Litros al momento</th>
                <th class="p-3">Mensaje</th>
                <th class="p-3 text-center">Estado</th>
                <th class="p-3 text-center">Acciones</th>
              </tr>
            </thead>
            <tbody id="tbody-quejas-admin" class="divide-y divide-gray-100">
              <tr><td colspan="7" class="p-6 text-center text-gray-400 font-bold">Cargando inconformidades...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- SECCIÓN C: SOLICITUDES DE RESTABLECIMIENTO DE CONTRASEÑA -->
      <div id="subseccion-recuperaciones" class="hidden bg-white rounded-3xl p-6 shadow-sm border border-gray-200 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-bold text-base text-gray-900 flex items-center gap-2">
            <span>🔑 Solicitudes de Contraseña Temporal (Ganaderos)</span>
          </h3>
          <span id="badge-contador-recuperaciones" class="text-xs font-bold bg-blue-100 text-blue-800 px-2.5 py-1 rounded-full">
            Cargando solicitudes...
          </span>
        </div>
        <div class="p-3 bg-blue-50 border-l-4 border-blue-500 rounded-r-xl text-xs text-blue-900">
          <strong>Procedimiento seguro:</strong> Verifica telefónicamente o en persona la identidad del ganadero antes de generar su clave temporal. Al pulsar el botón, el sistema emitirá una contraseña única de un solo uso que obligará al ganadero a definir su nueva clave en su siguiente acceso.
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-blue-100/70 text-blue-950 uppercase font-bold">
              <tr>
                <th class="p-3">Fecha y Hora</th>
                <th class="p-3">Ganadero Solicitante</th>
                <th class="p-3">Cédula</th>
                <th class="p-3">Teléfono</th>
                <th class="p-3 text-center">Estado</th>
                <th class="p-3 text-center">Acción de Seguridad</th>
              </tr>
            </thead>
            <tbody id="tbody-recuperaciones-admin" class="divide-y divide-gray-100">
              <tr><td colspan="6" class="p-6 text-center text-gray-400 font-bold">Cargando solicitudes de recuperación...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- MODAL NUEVO / EDITAR ASOCIADO -->
  <div id="modal-nuevo-ganadero" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border-2 border-campo-200">
      <div class="flex items-center justify-between mb-4 pb-2 border-b">
        <h3 class="font-black text-lg text-gray-900">Registrar Nuevo Asociado</h3>
        <button type="button" onclick="cerrarModalAsociado()" class="text-gray-400 hover:text-black font-bold p-1">✕</button>
      </div>
      <form id="form-nuevo-ganadero" class="space-y-3">
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Cédula de Ciudadanía *</label>
          <input type="text" name="cedula" required placeholder="Ej: 1005" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Nombre Completo *</label>
          <input type="text" name="nombre" required placeholder="Ej: Don Pedro Nel Gómez" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Finca o Hacienda</label>
          <input type="text" name="finca" placeholder="Ej: Finca El Recreo" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Teléfono de Contacto</label>
          <input type="tel" name="telefono" placeholder="Ej: 3105559999" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Contraseña Inicial * (Mínimo 8 caracteres)</label>
          <input type="password" name="password" placeholder="Contraseña segura para el asociado" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Estado del Asociado *</label>
          <select name="activo" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white font-bold">
            <option value="1">✅ Activo (Habilitado para Login)</option>
            <option value="0">⛔ Inactivo (Bloquear acceso)</option>
          </select>
        </div>
        <div class="flex justify-end gap-2 pt-3">
          <button type="button" onclick="cerrarModalAsociado()" class="px-4 py-2 border rounded-xl text-xs font-bold">Cancelar</button>
          <button type="submit" class="px-5 py-2 bg-campo-700 hover:bg-campo-800 text-white rounded-xl text-xs font-bold shadow">Guardar Asociado</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL CAMBIAR CONTRASEÑA ADMIN -->
  <div id="modal-cambiar-password" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border-2 border-campo-200">
      <div class="flex items-center justify-between mb-4 pb-2 border-b">
        <h3 class="font-black text-lg text-gray-900 flex items-center gap-2">
          <span>🔑</span>
          <span>Cambiar mi Contraseña</span>
        </h3>
        <button type="button" onclick="cerrarModalCambiarPassword()" class="text-gray-400 hover:text-black font-bold p-1">✕</button>
      </div>
      <div id="msg-modal-pass" class="hidden p-3 rounded-xl text-xs font-bold mb-3"></div>
      <form id="form-cambiar-password-admin" class="space-y-3">
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Contraseña Actual *</label>
          <input type="password" name="passwordActual" required placeholder="Ingresa tu clave actual" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Nueva Contraseña * (Mín. 10 car., Mayús, Minús, Núm, Símbolo)</label>
          <input type="password" name="passwordNueva" required placeholder="Nueva contraseña segura" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div>
          <label class="block text-xs font-bold text-gray-700 mb-1">Confirmar Nueva Contraseña *</label>
          <input type="password" name="passwordConfirm" required placeholder="Repite la nueva contraseña" class="w-full px-3 py-2 text-xs border-2 rounded-xl bg-white">
        </div>
        <div class="p-2.5 bg-amber-50 rounded-xl border border-amber-200 text-[11px] text-amber-900">
          ⚠️ Al cambiar tu contraseña, todas tus demás sesiones activas en otros navegadores serán cerradas automáticamente.
        </div>
        <div class="flex justify-end gap-2 pt-2">
          <button type="button" onclick="cerrarModalCambiarPassword()" class="px-4 py-2 border rounded-xl text-xs font-bold">Cancelar</button>
          <button type="submit" id="btn-submit-modal-pass" class="px-5 py-2 bg-campo-700 hover:bg-campo-800 text-white rounded-xl text-xs font-bold shadow">Actualizar Contraseña</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL MOSTRAR CLAVE TEMPORAL GENERADA -->
  <div id="modal-clave-temporal" class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 hidden">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border-2 border-campo-500 space-y-4">
      <div class="flex items-center justify-between pb-2 border-b">
        <h3 class="font-black text-lg text-campo-950 flex items-center gap-2">
          <span>⚡</span>
          <span>Contraseña Temporal Generada</span>
        </h3>
        <button type="button" onclick="cerrarModalClaveTemporal()" class="text-gray-400 hover:text-black font-bold p-1">✕</button>
      </div>
      <p class="text-xs text-gray-600">
        Se ha generado la siguiente contraseña temporal para el ganadero <strong id="temporal-ganadero-nombre">-</strong> (C.C. <span id="temporal-ganadero-cedula">-</span>):
      </p>
      <div class="bg-campo-50 border-2 border-dashed border-campo-400 p-4 rounded-2xl text-center">
        <span class="text-[11px] uppercase font-bold text-campo-800 block mb-1">Clave Temporal de un Solo Uso</span>
        <div id="temporal-clave-valor" class="text-2xl font-black font-mono text-campo-950 tracking-wider select-all py-1">
          -
        </div>
      </div>
      <div class="p-3 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-xs text-red-900 space-y-1">
        <p class="font-bold">⚠️ Atención de Seguridad:</p>
        <p class="text-[11px]">Esta contraseña se muestra <strong>una sola vez</strong> y no volverá a aparecer. Cópiala y comunícala de forma segura al ganadero. El sistema lo obligará a cambiarla inmediatamente al iniciar sesión.</p>
      </div>
      <div class="flex justify-end pt-2">
        <button type="button" onclick="cerrarModalClaveTemporal()" class="px-6 py-2.5 bg-campo-700 hover:bg-campo-800 text-white rounded-xl text-xs font-bold shadow">
          Entendido y Copiada
        </button>
      </div>
    </div>
  </div>

  <footer class="bg-campo-950 text-white py-6 text-center text-xs text-campo-400 mt-auto border-t border-campo-900">
    &copy; <?php echo date('Y'); ?> Asociación de Ganaderos Unión y Progreso Lechero &bull; XAMPP Native Edition
  </footer>

  <script src="./js/app.js"></script>
  <script src="./js/admin.js"></script>
  <script>
    function abrirModalNuevoAsociado() {
      const modal = document.getElementById("modal-nuevo-ganadero");
      if (!modal) return;
      modal.querySelector("h3").textContent = "Registrar Nuevo Asociado";
      const form = document.getElementById("form-nuevo-ganadero");
      form.reset();
      delete form.dataset.editId;
      modal.classList.remove("hidden");
    }

    function cerrarModalAsociado() {
      document.getElementById("modal-nuevo-ganadero")?.classList.add("hidden");
    }
  </script>
</body>
</html>
