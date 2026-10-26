<?php
require_once __DIR__ . '/api/db.php';

$usuario = getCurrentUser();
if ($usuario) {
    header("Location: " . ($usuario['rol'] === 'ADMIN' ? './admin.php' : './panel.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registro de Asociado | Asociación de Ganaderos</title>
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

  <div class="w-full max-w-lg mb-4 flex items-center justify-between text-sm">
    <a href="./login.php" class="inline-flex items-center gap-1.5 text-campo-800 hover:text-campo-950 font-bold p-2 rounded-lg hover:bg-campo-100 transition-colors">
      <span>←</span>
      <span>¿Ya tienes cuenta? Inicia sesión</span>
    </a>
  </div>

  <div class="w-full max-w-lg bg-white rounded-3xl shadow-xl border border-campo-200 overflow-hidden">
    <!-- Cabecera -->
    <div class="bg-gradient-to-r from-campo-800 to-campo-700 text-white p-6 sm:p-7 text-center">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-campo-900 text-white mb-2 shadow-inner text-2xl">
        🧑‍🌾
      </div>
      <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Registro de Ganadero Asociado</h1>
      <p class="text-campo-100 text-xs sm:text-sm mt-1">
        Crea tu cuenta para consultar tus entregas de leche y liquidaciones quincenales
      </p>
    </div>

    <!-- Formulario -->
    <div class="p-6 sm:p-8 space-y-5">
      <div id="registro-error" class="hidden p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-800 text-sm font-semibold"></div>

      <form id="form-registro" class="space-y-4">
        <div>
          <label for="cedula" class="block text-xs font-bold text-gray-700 mb-1">
            Número de Cédula de Ciudadanía *
          </label>
          <input type="text" id="cedula" name="cedula" inputmode="numeric" required placeholder="Número de documento de identidad"
                 class="w-full px-4 py-2.5 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
          <p class="text-[11px] text-gray-500 mt-0.5">Será tu identificador para ingresar al portal.</p>
        </div>

        <div>
          <label for="nombre" class="block text-xs font-bold text-gray-700 mb-1">
            Nombre y Apellidos Completos *
          </label>
          <input type="text" id="nombre" name="nombre" required placeholder="Nombres y apellidos completos"
                 class="w-full px-4 py-2.5 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
        </div>

        <div>
          <label for="telefono" class="block text-xs font-bold text-gray-700 mb-1">
            Teléfono o Celular de Contacto *
          </label>
          <input type="tel" id="telefono" name="telefono" required placeholder="Número de teléfono móvil o fijo"
                 class="w-full px-4 py-2.5 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <div>
            <label for="password" class="block text-xs font-bold text-gray-700 mb-1">
              Contraseña Personal *
            </label>
            <input type="password" id="password" name="password" required placeholder="Mínimo 8 caracteres"
                   class="w-full px-4 py-2.5 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
          </div>

          <div>
            <label for="password_confirm" class="block text-xs font-bold text-gray-700 mb-1">
              Confirmar Contraseña *
            </label>
            <input type="password" id="password_confirm" name="password_confirm" required placeholder="Repite tu contraseña"
                   class="w-full px-4 py-2.5 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
          </div>
        </div>

        <!-- MÓDULO DE CAPTCHA -->
        <div class="bg-campo-50/70 p-3.5 rounded-2xl border border-campo-200 space-y-2">
          <div class="flex items-center justify-between">
            <label for="captcha" class="text-xs font-bold text-campo-950">
              Verificación de Seguridad *
            </label>
            <button type="button" id="btn-refresh-captcha-reg" class="text-[11px] font-bold text-campo-700 hover:underline flex items-center gap-1" title="Generar nuevo desafío">
              <span>🔄 Cambiar</span>
            </button>
          </div>

          <div class="flex items-center gap-3">
            <div id="captcha-display-reg" class="flex-shrink-0">
              <div class="w-[130px] h-[42px] bg-campo-800 rounded-lg flex items-center justify-center text-xs text-white">Cargando...</div>
            </div>
            <input type="text" id="captcha" name="captcha" required placeholder="Respuesta"
                   class="w-full px-3 py-2 text-sm font-bold border-2 border-campo-300 rounded-xl focus:outline-none focus:border-campo-600 bg-white text-center">
          </div>
          <p class="text-[10px] text-gray-500">Resuelve el desafío para habilitar el registro.</p>
        </div>

        <button type="submit" id="btn-submit-registro"
                class="btn-touch w-full bg-campo-700 hover:bg-campo-800 text-white font-bold text-base py-3.5 rounded-xl shadow-md transition-all flex items-center justify-center gap-2 mt-2">
          <span>Crear Mi Cuenta de Ganadero</span>
          <span>✓</span>
        </button>
      </form>
    </div>
  </div>

  <div class="mt-6 text-center text-xs text-gray-500">
    Plataforma Segura de Control Lechero &copy; <?php echo date('Y'); ?>
  </div>

  <script>
    async function cargarCaptchaRegistro() {
      const container = document.getElementById("captcha-display-reg");
      const inputCaptcha = document.getElementById("captcha");
      if (inputCaptcha) inputCaptcha.value = "";

      try {
        const res = await fetch("./api/captcha.php?t=" + Date.now());
        const data = await res.json();
        if (data.svg && container) {
          container.innerHTML = data.svg;
        }
      } catch (e) {
        if (container) container.innerHTML = `<span class="text-xs text-red-600 font-bold">Error</span>`;
      }
    }

    document.getElementById("btn-refresh-captcha-reg")?.addEventListener("click", cargarCaptchaRegistro);
    document.addEventListener("DOMContentLoaded", cargarCaptchaRegistro);

    document.getElementById("form-registro")?.addEventListener("submit", async (e) => {
      e.preventDefault();
      const form = e.target;
      const errorDiv = document.getElementById("registro-error");
      const btn = document.getElementById("btn-submit-registro");

      errorDiv.classList.add("hidden");

      const cedula = form.cedula.value.trim();
      const nombre = form.nombre.value.trim();
      const telefono = form.telefono.value.trim();
      const password = form.password.value;
      const passwordConfirm = form.password_confirm.value;
      const captcha = form.captcha.value.trim();

      if (password !== passwordConfirm) {
        errorDiv.textContent = "Las contraseñas no coinciden. Por favor verifícalas.";
        errorDiv.classList.remove("hidden");
        return;
      }

      btn.disabled = true;
      btn.textContent = "Creando cuenta...";

      try {
        const res = await fetch("./api/registro.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ cedula, nombre, telefono, password, captcha })
        });
        const data = await res.json();

        if (res.ok) {
          window.location.href = `./login.php?registrado=true`;
        } else {
          errorDiv.textContent = data.error || "Error al registrar la cuenta.";
          errorDiv.classList.remove("hidden");
          btn.disabled = false;
          btn.innerHTML = `<span>Crear Mi Cuenta de Ganadero</span> <span>✓</span>`;
          cargarCaptchaRegistro();
        }
      } catch (err) {
        errorDiv.textContent = "Error de conexión con el servidor.";
        errorDiv.classList.remove("hidden");
        btn.disabled = false;
        btn.innerHTML = `<span>Crear Mi Cuenta de Ganadero</span> <span>✓</span>`;
        cargarCaptchaRegistro();
      }
    });
  </script>
</body>
</html>
