<?php
require_once __DIR__ . '/api/db.php';

header('Referrer-Policy: no-referrer');

$pdo = getDB();
$token = trim($_GET['token'] ?? '');
$tokenValido = false;
$errorMensaje = '';
$usuarioNombre = '';
$rolUsuario = 'GANADERO';

if (empty($token) || strlen($token) !== 64) {
    $errorMensaje = 'El enlace de restablecimiento es inválido o se encuentra incompleto.';
} else {
    try {
        $tokenHash = hash('sha256', $token);
        $stmt = $pdo->prepare("SELECT s.*, COALESCE(a.nombre, u.nombre) AS nombreReal 
                               FROM solicitudes_recuperacion s
                               LEFT JOIN asociados a ON s.usuarioId = a.id
                               LEFT JOIN usuario u ON s.usuarioId = u.id
                               WHERE s.tokenHash = ? AND s.usado = 0 AND s.tokenExpira > NOW() 
                               LIMIT 1");
        $stmt->execute([$tokenHash]);
        $solicitud = $stmt->fetch();

        if ($solicitud) {
            $tokenValido = true;
            $usuarioNombre = $solicitud['nombreReal'] ?: $solicitud['nombre'];
            $rolUsuario = $solicitud['rol'];
        } else {
            $errorMensaje = 'Este enlace de seguridad ha expirado (límite de 15 minutos superado) o ya fue utilizado previamente. Por favor solicita uno nuevo.';
        }
    } catch (Exception $e) {
        $errorMensaje = 'Error al verificar la validez del token de seguridad.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="referrer" content="no-referrer">
  <title>Restablecer Contraseña | Asociación de Ganaderos</title>
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
    <div class="bg-campo-700 text-white p-6 sm:p-7 text-center">
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-campo-800 text-white mb-2 shadow-inner text-2xl">
        🔐
      </div>
      <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">Nueva Contraseña</h1>
      <p class="text-campo-100 text-xs sm:text-sm mt-1">
        <?php echo $tokenValido ? 'Crea una contraseña segura para tu cuenta' : 'Verificación de Seguridad'; ?>
      </p>
    </div>

    <!-- Contenido -->
    <div class="p-6 sm:p-8 space-y-5">
      <?php if (!$tokenValido): ?>
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl text-red-800 text-sm font-semibold space-y-2">
          <p>⚠️ <?php echo htmlspecialchars($errorMensaje); ?></p>
        </div>
        <div class="pt-2 text-center">
          <a href="./olvide_password.php" class="btn-touch inline-flex items-center justify-center gap-2 w-full py-3 px-4 rounded-xl bg-campo-700 hover:bg-campo-800 text-white font-bold text-sm shadow">
            <span>Solicitar un Nuevo Enlace</span>
            <span>➜</span>
          </a>
        </div>
      <?php else: ?>
        <div class="p-3 bg-campo-50 border border-campo-200 rounded-xl text-xs text-campo-900">
          Hola <strong><?php echo htmlspecialchars($usuarioNombre); ?></strong>, ingresa tu nueva contraseña segura para restablecer el acceso a tu cuenta.
        </div>

        <div id="msg-restablecer" class="hidden p-4 rounded-xl text-sm font-semibold"></div>

        <form id="form-restablecer" class="space-y-4">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

          <div>
            <label for="passwordNueva" class="block text-xs font-bold text-campo-900 mb-1">
              Nueva Contraseña
            </label>
            <input type="password" id="passwordNueva" name="passwordNueva" required 
                   placeholder="Escribe tu nueva clave"
                   class="w-full px-4 py-3 text-sm border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 transition-all text-gray-900 bg-white">
          </div>

          <div>
            <label for="passwordConfirm" class="block text-xs font-bold text-campo-900 mb-1">
              Confirmar Nueva Contraseña
            </label>
            <input type="password" id="passwordConfirm" name="passwordConfirm" required 
                   placeholder="Confirma la nueva clave"
                   class="w-full px-4 py-3 text-sm border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 transition-all text-gray-900 bg-white">
          </div>

          <!-- Requisitos de Seguridad -->
          <div class="bg-gray-50 p-3.5 rounded-xl border border-gray-200 text-xs space-y-1.5">
            <p class="font-bold text-gray-700">Requisitos de la contraseña:</p>
            <ul class="space-y-1 text-gray-600 text-[11px]">
              <li id="req-len" class="flex items-center gap-1.5"><span>⚪</span> <span>Mínimo <?php echo $rolUsuario === 'ADMIN' ? '10' : '8'; ?> caracteres</span></li>
              <?php if ($rolUsuario === 'ADMIN'): ?>
                <li id="req-upper" class="flex items-center gap-1.5"><span>⚪</span> <span>Al menos una letra mayúscula (A-Z)</span></li>
                <li id="req-lower" class="flex items-center gap-1.5"><span>⚪</span> <span>Al menos una letra minúscula (a-z)</span></li>
                <li id="req-num" class="flex items-center gap-1.5"><span>⚪</span> <span>Al menos un número (0-9)</span></li>
                <li id="req-sym" class="flex items-center gap-1.5"><span>⚪</span> <span>Al menos un símbolo especial (!@#$%&*)</span></li>
              <?php endif; ?>
              <li id="req-match" class="flex items-center gap-1.5"><span>⚪</span> <span>Las dos contraseñas deben coincidir</span></li>
            </ul>
          </div>

          <button type="submit" id="btn-submit-restablecer"
                  class="btn-touch w-full bg-campo-700 hover:bg-campo-800 active:bg-campo-900 text-white shadow-md text-sm font-bold py-3.5 rounded-xl transition-all flex items-center justify-center gap-2">
            <span>Guardar Nueva Contraseña</span>
            <span>➜</span>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script>
    const rolUsuario = "<?php echo $rolUsuario; ?>";
    const p1 = document.getElementById('passwordNueva');
    const p2 = document.getElementById('passwordConfirm');

    function actualizarChecklist() {
      if (!p1) return;
      const val = p1.value;
      const conf = p2 ? p2.value : '';

      const minLen = rolUsuario === 'ADMIN' ? 10 : 8;
      marcarReq('req-len', val.length >= minLen);

      if (rolUsuario === 'ADMIN') {
        marcarReq('req-upper', /[A-Z]/.test(val));
        marcarReq('req-lower', /[a-z]/.test(val));
        marcarReq('req-num', /[0-9]/.test(val));
        marcarReq('req-sym', /[^A-Za-z0-9]/.test(val));
      }

      marcarReq('req-match', val.length > 0 && conf.length > 0 && val === conf);
    }

    function marcarReq(id, cumple) {
      const el = document.getElementById(id);
      if (!el) return;
      if (cumple) {
        el.className = 'flex items-center gap-1.5 text-green-700 font-bold';
        el.querySelector('span').textContent = '✅';
      } else {
        el.className = 'flex items-center gap-1.5 text-gray-500';
        el.querySelector('span').textContent = '⚪';
      }
    }

    p1?.addEventListener('input', actualizarChecklist);
    p2?.addEventListener('input', actualizarChecklist);

    document.getElementById('form-restablecer')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const form = e.target;
      const btn = document.getElementById('btn-submit-restablecer');
      const msgBox = document.getElementById('msg-restablecer');

      btn.disabled = true;
      btn.innerHTML = '<span>Actualizando...</span>';
      msgBox.classList.add('hidden');

      try {
        const res = await fetch('./api/restablecer_con_token.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            token: form.token.value,
            passwordNueva: form.passwordNueva.value,
            passwordConfirm: form.passwordConfirm.value
          })
        });

        const data = await res.json();

        if (res.ok) {
          msgBox.className = 'p-4 bg-green-50 border-l-4 border-campo-600 rounded-r-lg text-campo-900 text-sm font-semibold block';
          msgBox.textContent = `✅ ${data.mensaje}`;
          setTimeout(() => {
            window.location.href = './login.php?cambiado=true';
          }, 2000);
        } else {
          msgBox.className = 'p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-800 text-sm font-semibold block';
          msgBox.textContent = `⚠️ ${data.error || 'Ocurrió un error al restablecer.'}`;
          btn.disabled = false;
          btn.innerHTML = '<span>Guardar Nueva Contraseña</span><span>➜</span>';
        }
      } catch (err) {
        msgBox.className = 'p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-800 text-sm font-semibold block';
        msgBox.textContent = '⚠️ Error de conexión con el servidor.';
        btn.disabled = false;
        btn.innerHTML = '<span>Guardar Nueva Contraseña</span><span>➜</span>';
      }
    });
  </script>
</body>
</html>

