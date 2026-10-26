<?php
require_once __DIR__ . '/api/db.php';

$usuario = getCurrentUser();
if ($usuario) {
    if (!empty($usuario['debeCambiarPassword'])) {
        header("Location: ./cambiar_password.php");
        exit;
    }
    header("Location: " . ($usuario['rol'] === 'ADMIN' ? "./admin.php" : "./panel.php"));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Recuperar Contraseña | Asociación de Ganaderos</title>
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
            leche: { 50: '#ffffff', 100: '#fcfbf7', 200: '#f7f5ed', 300: '#ede8d8' },
            dorado: { 400: '#f59e0b', 500: '#d97706', 600: '#b45309' }
          }
        }
      }
    }
  </script>
</head>
<body class="min-h-screen flex flex-col justify-center items-center px-4 py-8 bg-gradient-to-b from-campo-50 via-leche-100 to-campo-100 font-sans antialiased text-campo-950">

  <!-- Enlace Superior -->
  <div class="w-full max-w-md mb-4 flex items-center justify-between text-sm">
    <a href="./login.php" class="inline-flex items-center gap-1.5 text-campo-800 hover:text-campo-950 font-bold p-2 rounded-lg hover:bg-campo-100 transition-colors">
      <span>←</span>
      <span>Volver al inicio de sesión</span>
    </a>
  </div>

  <!-- Tarjeta Recuperación -->
  <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-campo-200 overflow-hidden">
    <!-- Cabecera -->
    <div class="bg-campo-700 text-white p-6 sm:p-7 text-center">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-campo-800 text-white mb-2 shadow-inner text-2xl">
        🔐
      </div>
      <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Recuperar Contraseña</h1>
      <p class="text-campo-100 text-xs sm:text-sm mt-1">
        Restablecimiento seguro para Ganaderos y Administración
      </p>
    </div>

    <!-- Contenido -->
    <div class="p-6 sm:p-8 space-y-5">
      <!-- Explicación de seguridad -->
      <div class="p-3.5 bg-amber-50/70 border-l-4 border-dorado-500 rounded-r-xl text-amber-950 text-xs leading-relaxed">
        <strong>¿Cómo funciona?</strong>
        <ul class="list-disc list-inside mt-1 space-y-0.5 text-[11px] text-amber-900">
          <li><strong>Ganaderos:</strong> Se registra tu solicitud en la administración para emitir una clave temporal tras verificación.</li>
          <li><strong>Administrador:</strong> Se remite un enlace seguro de 15 minutos al correo oficial.</li>
        </ul>
      </div>

      <!-- Mensaje de respuesta -->
      <div id="msg-recuperacion" class="hidden p-4 rounded-xl text-sm font-semibold"></div>

      <form id="form-recuperar" class="space-y-4">
        <div>
          <label for="cedula" class="block text-sm font-bold text-campo-900 mb-1">
            Número de Cédula Registrada
          </label>
          <input type="text" id="cedula" name="cedula" inputmode="numeric" required 
                 placeholder="Ej: 1098765432"
                 class="w-full px-4 py-3 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 transition-all text-gray-900 bg-white">
        </div>

        <!-- MÓDULO DE CAPTCHA -->
        <div class="bg-campo-50/70 p-3.5 rounded-2xl border border-campo-200 space-y-2">
          <div class="flex items-center justify-between">
            <label for="captcha" class="text-xs font-bold text-campo-950">
              Verificación de Seguridad *
            </label>
            <button type="button" id="btn-refresh-captcha" class="text-[11px] font-bold text-campo-700 hover:underline flex items-center gap-1" title="Generar nuevo desafío">
              <span>🔄 Cambiar</span>
            </button>
          </div>

          <div class="flex items-center gap-3">
            <div id="captcha-display" class="flex-shrink-0">
              <div class="w-[130px] h-[42px] bg-campo-800 rounded-lg flex items-center justify-center text-xs text-white">Cargando...</div>
            </div>
            <input type="text" id="captcha" name="captcha" required placeholder="Respuesta"
                   class="w-full px-3 py-2 text-sm font-bold border-2 border-campo-300 rounded-xl focus:outline-none focus:border-campo-600 bg-white text-center">
          </div>
          <p class="text-[10px] text-gray-500">Resuelve la operación matemática para procesar la solicitud.</p>
        </div>

        <button type="submit" id="btn-submit-recuperar"
                class="btn-touch w-full bg-campo-700 hover:bg-campo-800 active:bg-campo-900 text-white shadow-md text-base font-bold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2">
          <span>Solicitar Restablecimiento</span>
          <span>➜</span>
        </button>

        <div class="pt-3 border-t border-gray-100 text-center">
          <a href="./login.php" class="text-xs text-campo-800 font-bold hover:underline">
            ¿Recordaste tu contraseña? Iniciar Sesión
          </a>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Manejo de CAPTCHA
    async function cargarCaptcha() {
      try {
        const res = await fetch('./api/captcha.php?v=' + Date.now());
        const data = await res.json();
        const display = document.getElementById('captcha-display');
        if (display && data.svg) {
          display.innerHTML = data.svg;
        }
      } catch (err) {
        console.warn('Error al cargar captcha:', err);
      }
    }

    document.getElementById('btn-refresh-captcha')?.addEventListener('click', cargarCaptcha);
    document.addEventListener('DOMContentLoaded', cargarCaptcha);

    // Envío del Formulario
    document.getElementById('form-recuperar')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = e.target;
      const btn = document.getElementById('btn-submit-recuperar');
      const msgBox = document.getElementById('msg-recuperacion');

      const cedula = form.cedula.value.trim();
      const captcha = form.captcha.value.trim();

      if (!cedula || !captcha) return;

      btn.disabled = true;
      btn.innerHTML = '<span>Procesando...</span>';
      msgBox.classList.add('hidden');

      try {
        const res = await fetch('./api/recuperar_password.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ cedula, captcha })
        });

        const data = await res.json();

        if (res.ok) {
          msgBox.className = 'p-4 bg-green-50 border-l-4 border-campo-600 rounded-r-lg text-campo-900 text-sm font-semibold block';
          msgBox.innerHTML = `✅ ${data.mensaje || 'Si la cédula existe, se procesará tu solicitud.'}<br><span class="text-xs font-normal text-campo-800 mt-1 block">Los ganaderos asociados pueden comunicarse con la administración de la asociación para obtener su clave temporal.</span>`;
          form.reset();
          cargarCaptcha();
        } else {
          msgBox.className = 'p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-800 text-sm font-semibold block';
          msgBox.textContent = `⚠️ ${data.error || 'Ocurrió un error al procesar la solicitud.'}`;
          cargarCaptcha();
        }
      } catch (err) {
        msgBox.className = 'p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-800 text-sm font-semibold block';
        msgBox.textContent = '⚠️ Error de conexión con el servidor. Intenta de nuevo.';
      } finally {
        btn.disabled = false;
        btn.innerHTML = '<span>Solicitar Restablecimiento</span><span>➜</span>';
      }
    });
  </script>
</body>
</html>

