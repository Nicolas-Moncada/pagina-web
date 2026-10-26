<?php
require_once __DIR__ . '/api/db.php';

$usuario = requireAuth();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Actualización de Seguridad | Asociación de Ganaderos</title>
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

  <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-campo-200 overflow-hidden">
    <!-- Cabecera -->
    <div class="bg-campo-800 text-white p-6 sm:p-7 text-center">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-campo-900 text-white mb-2 shadow-inner text-2xl">
        🔐
      </div>
      <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight">Cambio Obligatorio de Contraseña</h1>
      <p class="text-campo-100 text-xs sm:text-sm mt-1">
        Hola, <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong>. Por políticas de seguridad, debes definir una nueva clave personal en tu primer ingreso.
      </p>
    </div>

    <!-- Formulario -->
    <div class="p-6 sm:p-8 space-y-5">
      <div id="cambio-error" class="hidden p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-800 text-xs font-semibold"></div>
      <div id="cambio-ok" class="hidden p-4 bg-green-50 border-l-4 border-campo-600 rounded-r-lg text-campo-900 text-xs font-semibold"></div>

      <form id="form-cambio-pass" class="space-y-4">
        <div>
          <label class="block text-xs font-bold text-campo-900 mb-1">
            Contraseña Actual *
          </label>
          <input type="password" id="passwordActual" name="passwordActual" required placeholder="Ingresa tu clave actual"
                 class="w-full px-3.5 py-2.5 text-sm border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
        </div>

        <div>
          <label class="block text-xs font-bold text-campo-900 mb-1">
            Nueva Contraseña *
          </label>
          <input type="password" id="passwordNueva" name="passwordNueva" required placeholder="Crea una contraseña segura"
                 class="w-full px-3.5 py-2.5 text-sm border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
        </div>

        <div>
          <label class="block text-xs font-bold text-campo-900 mb-1">
            Confirmar Nueva Contraseña *
          </label>
          <input type="password" id="passwordConfirm" name="passwordConfirm" required placeholder="Repite la nueva clave"
                 class="w-full px-3.5 py-2.5 text-sm border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white">
        </div>

        <!-- Requisitos visuales -->
        <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 space-y-1 text-[11px] font-semibold text-gray-600">
          <p class="font-bold text-gray-800 text-xs mb-1">Tu nueva clave debe incluir:</p>
          <div id="req-len" class="flex items-center gap-1.5 text-gray-500"><span>○</span> Mínimo 10 caracteres</div>
          <div id="req-may" class="flex items-center gap-1.5 text-gray-500"><span>○</span> Al menos 1 letra mayúscula (A-Z)</div>
          <div id="req-min" class="flex items-center gap-1.5 text-gray-500"><span>○</span> Al menos 1 letra minúscula (a-z)</div>
          <div id="req-num" class="flex items-center gap-1.5 text-gray-500"><span>○</span> Al menos 1 número (0-9)</div>
          <div id="req-sym" class="flex items-center gap-1.5 text-gray-500"><span>○</span> Al menos 1 símbolo especial (!@#$%^&*)</div>
        </div>

        <button type="submit" id="btn-submit-cambio"
                class="btn-touch w-full bg-campo-700 hover:bg-campo-800 text-white font-bold text-sm py-3 rounded-xl shadow transition-all flex items-center justify-center gap-2">
          <span>Actualizar Contraseña y Entrar</span>
          <span>✓</span>
        </button>
      </form>
    </div>
  </div>

  <script>
    const inputNueva = document.getElementById("passwordNueva");
    const reqLen = document.getElementById("req-len");
    const reqMay = document.getElementById("req-may");
    const reqMin = document.getElementById("req-min");
    const reqNum = document.getElementById("req-num");
    const reqSym = document.getElementById("req-sym");

    inputNueva.addEventListener("input", () => {
      const v = inputNueva.value;
      actualizarReq(reqLen, v.length >= 10);
      actualizarReq(reqMay, /[A-Z]/.test(v));
      actualizarReq(reqMin, /[a-z]/.test(v));
      actualizarReq(reqNum, /[0-9]/.test(v));
      actualizarReq(reqSym, /[^A-Za-z0-9]/.test(v));
    });

    function actualizarReq(el, cumple) {
      if (cumple) {
        el.className = "flex items-center gap-1.5 text-campo-700 font-bold";
        el.innerHTML = "<span>●</span> " + el.textContent.substring(2);
      } else {
        el.className = "flex items-center gap-1.5 text-gray-500";
        el.innerHTML = "<span>○</span> " + el.textContent.substring(2);
      }
    }

    document.getElementById("form-cambio-pass").addEventListener("submit", async (e) => {
      e.preventDefault();
      const errBox = document.getElementById("cambio-error");
      const okBox = document.getElementById("cambio-ok");
      const btn = document.getElementById("btn-submit-cambio");

      errBox.classList.add("hidden");
      okBox.classList.add("hidden");
      btn.disabled = true;
      btn.textContent = "Validando y actualizando...";

      const form = e.target;
      const data = {
        passwordActual: form.passwordActual.value,
        passwordNueva: form.passwordNueva.value,
        passwordConfirm: form.passwordConfirm.value
      };

      try {
        const res = await fetch("./api/cambiar_password.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(data)
        });
        const resData = await res.json();

        if (res.ok) {
          okBox.textContent = resData.mensaje || "¡Contraseña actualizada con éxito!";
          okBox.classList.remove("hidden");
          setTimeout(() => {
            window.location.href = resData.redirectUrl || "./admin.php";
          }, 1200);
        } else {
          errBox.textContent = resData.error || "No se pudo actualizar la contraseña.";
          errBox.classList.remove("hidden");
          btn.disabled = false;
          btn.innerHTML = "<span>Actualizar Contraseña y Entrar</span> <span>✓</span>";
        }
      } catch (err) {
        errBox.textContent = "Error de conexión con el servidor.";
        errBox.classList.remove("hidden");
        btn.disabled = false;
        btn.innerHTML = "<span>Actualizar Contraseña y Entrar</span> <span>✓</span>";
      }
    });
  </script>
</body>
</html>

