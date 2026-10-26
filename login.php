<?php
require_once __DIR__ . '/api/db.php';

$usuario = getCurrentUser();
if ($usuario) {
    if (!empty($usuario['debeCambiarPassword'])) {
        header("Location: ./cambiar_password.php");
        exit;
    }
    if ($usuario['rol'] === 'ADMIN') {
        header("Location: ./admin.php");
        exit;
    } else {
        header("Location: ./panel.php");
        exit;
    }
}

$registrado = isset($_GET['registrado']) && $_GET['registrado'] === 'true';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso Seguro | Asociación de Ganaderos</title>
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

  <!-- Volver y Registro -->
  <div class="w-full max-w-md mb-4 flex items-center justify-between text-sm">
    <a href="./index.php" class="inline-flex items-center gap-1.5 text-campo-800 hover:text-campo-950 font-bold p-2 rounded-lg hover:bg-campo-100 transition-colors">
      <span>←</span>
      <span>Volver al inicio</span>
    </a>
    <a href="./registro.php" class="font-bold text-campo-800 underline hover:text-campo-950">
      Crear cuenta nueva
    </a>
  </div>

  <!-- Tarjeta Login -->
  <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-campo-200 overflow-hidden">
    <!-- Cabecera -->
    <div class="bg-campo-700 text-white p-6 sm:p-7 text-center">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-campo-800 text-white mb-2 shadow-inner text-2xl">
        🛡️
      </div>
      <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Acceso a la Plataforma</h1>
      <p class="text-campo-100 text-xs sm:text-sm mt-1">
        Ingresa tus credenciales para consultar el sistema
      </p>
    </div>

    <!-- Formulario -->
    <div class="p-6 sm:p-8 space-y-5">
      <?php if ($registrado): ?>
        <div class="p-4 bg-green-50 border-l-4 border-campo-600 rounded-r-lg text-campo-900 text-sm font-semibold">
          ✅ ¡Tu cuenta ha sido creada exitosamente! Por favor escribe tu cédula y contraseña para ingresar.
        </div>
      <?php endif; ?>

      <div id="login-error" class="hidden p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-800 text-sm font-semibold"></div>

      <form id="form-login" class="space-y-4">
        <div>
          <label for="cedula" class="block text-sm font-bold text-campo-900 mb-1">
            Número de Cédula
          </label>
          <input type="text" id="cedula" name="cedula" inputmode="numeric" required 
                 placeholder="Número de cédula"
                 class="w-full px-4 py-3 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 transition-all text-gray-900 bg-white">
        </div>

        <div>
          <label for="password" class="block text-sm font-bold text-campo-900 mb-1">
            Contraseña
          </label>
          <div class="relative">
            <input type="password" id="password" name="password" required placeholder="Contraseña"
                   class="w-full px-4 py-3 pr-12 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 transition-all text-gray-900 bg-white">
            <button type="button" id="btn-toggle-pass" 
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-campo-800 p-1" title="Ver contraseña">
              👁️
            </button>
          </div>
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
          <p class="text-[10px] text-gray-500">Resuelve el desafío matemático para habilitar el ingreso.</p>
        </div>

        <button type="submit" id="btn-submit-login"
                class="btn-touch w-full bg-campo-700 hover:bg-campo-800 active:bg-campo-900 text-white shadow-md text-base font-bold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2">
          <span>Ingresar al Sistema</span>
          <span>➜</span>
        </button>

        <div class="text-center py-1">
          <a href="./olvide_password.php" class="text-xs text-campo-700 hover:text-campo-950 font-bold underline transition-colors">
            ¿Olvidaste tu contraseña?
          </a>
        </div>

        <!-- Bloque Registro -->
        <div class="pt-3 border-t border-gray-100 bg-campo-50/60 p-4 rounded-2xl border border-campo-200 text-center space-y-2">
          <p class="text-xs font-bold text-campo-950">
            ¿Eres ganadero afiliado y aún no tienes cuenta?
          </p>
          <a href="./registro.php" 
             class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl bg-white hover:bg-campo-100 text-campo-800 border-2 border-campo-300 font-bold text-xs shadow-sm transition-all">
            <span>➕ Registrarme como Ganadero Asociado</span>
          </a>
        </div>
      </form>
    </div>
  </div>

  <div class="mt-6 text-center text-xs text-gray-500">
    Plataforma Segura de Control Lechero &copy; <?php echo date('Y'); ?>
  </div>

  <script>
    const btnToggle = document.getElementById("btn-toggle-pass");
    const inputPass = document.getElementById("password");
    btnToggle?.addEventListener("click", () => {
      if (inputPass.type === "password") {
        inputPass.type = "text";
        btnToggle.textContent = "🙈";
      } else {
        inputPass.type = "password";
        btnToggle.textContent = "👁️";
      }
    });

    // Carga de CAPTCHA dinámico
    async function cargarCaptcha() {
      const container = document.getElementById("captcha-display");
      const inputCaptcha = document.getElementById("captcha");
      if (inputCaptcha) inputCaptcha.value = "";

      try {
        const res = await fetch("./api/captcha.php?t=" + Date.now());
        const data = await res.json();
        if (data.svg && container) {
          container.innerHTML = data.svg;
        }
      } catch (e) {
        if (container) container.innerHTML = `<span class="text-xs text-red-600 font-bold">Error captcha</span>`;
      }
    }

    document.getElementById("btn-refresh-captcha")?.addEventListener("click", cargarCaptcha);
    document.addEventListener("DOMContentLoaded", cargarCaptcha);

    // Envío del Formulario
    document.getElementById("form-login")?.addEventListener("submit", async (e) => {
      e.preventDefault();
      const form = e.target;
      const errorDiv = document.getElementById("login-error");
      const btn = document.getElementById("btn-submit-login");

      errorDiv.classList.add("hidden");
      btn.disabled = true;
      btn.textContent = "Verificando...";

      const cedula = form.cedula.value.trim();
      const password = form.password.value;
      const captcha = form.captcha.value.trim();

      try {
        const res = await fetch("./api/login.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ cedula, password, captcha })
        });
        const data = await res.json();

        if (res.ok) {
          window.location.href = data.redirectUrl || "./panel.php";
        } else {
          errorDiv.textContent = data.error || "Cédula o contraseña incorrecta.";
          errorDiv.classList.remove("hidden");
          btn.disabled = false;
          btn.innerHTML = `<span>Ingresar al Sistema</span> <span>➜</span>`;
          // Recargar captcha tras cada intento fallido
          cargarCaptcha();
        }
      } catch (err) {
        errorDiv.textContent = "Error de conexión con el servidor.";
        errorDiv.classList.remove("hidden");
        btn.disabled = false;
        btn.innerHTML = `<span>Ingresar al Sistema</span> <span>➜</span>`;
        cargarCaptcha();
      }
    });
  </script>
</body>
</html>
