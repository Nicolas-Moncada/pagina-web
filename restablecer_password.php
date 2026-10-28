<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';

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
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="referrer" content="no-referrer">
  <meta name="color-scheme" content="light">
  <title>Restablecer Contraseña | Asociación de Ganaderos</title>

  <script>
    // Forzar Modo Claro Único (anti-parpadeo)
    document.documentElement.setAttribute('data-bs-theme', 'light');
    document.documentElement.style.colorScheme = 'light';
  </script>

  <link rel="icon" type="image/png" href="assets/img/logo.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="assets/css/theme.css">

  <style>
    /* Estilos exclusivos de esta vista (checklist y mensajes) */
    #msg-restablecer {
      display: none;
      padding: 1rem;
      border-radius: var(--radius-md);
      font-weight: 700;
      font-size: 0.95rem;
    }
    #msg-restablecer.show { display: block; }
    .msg-ok {
      background: #e9f7ef;
      border-left: 5px solid #1b5e20;
      color: #14421f;
    }
    .msg-err {
      background: #fde8e8;
      border-left: 5px solid var(--danger);
      color: #7a1414;
    }
    .req-list {
      background: #ffffff;
      border: 2px solid var(--card-border);
      border-radius: var(--radius-md);
    }
    .req-list p { color: var(--text-muted); font-weight: 800; }
    .check-req {
      display: flex;
      align-items: center;
      gap: 0.45rem;
      color: var(--text-muted);
      font-size: 0.85rem;
      transition: color 0.25s var(--ease);
    }
    .check-req.ok {
      color: var(--success);
      font-weight: 800;
    }
  </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 auth-bg px-3 py-5">

  <div class="card card-rural overflow-hidden p-0 w-100 border-0 shadow-lg" style="max-width: 480px;">
    <!-- Cabecera -->
    <div class="p-4 text-center text-white" style="background: var(--grad-primary);">
      <div class="rounded-circle text-white mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 56px; height: 56px; font-size: 1.5rem; background: var(--grad-accent); box-shadow: var(--shadow-glow-gold);">
        🔐
      </div>
      <h1 class="h3 fw-bold mb-1">Nueva Contraseña</h1>
      <p class="small opacity-75 mb-0">
        <?php echo $tokenValido ? 'Crea una contraseña segura para tu cuenta' : 'Verificación de Seguridad'; ?>
      </p>
    </div>

    <!-- Contenido -->
    <div class="p-4 p-md-5">
      <?php if (!$tokenValido): ?>
        <div class="alert alert-danger small fw-bold mb-4 rounded-4" role="alert">
          ⚠️ <?php echo htmlspecialchars($errorMensaje); ?>
        </div>
        <div class="pt-2 text-center d-grid">
          <a href="./olvide_password.php" class="btn btn-touch btn-aso-primary w-100">
            <span>Solicitar un Nuevo Enlace</span>
            <span>➜</span>
          </a>
        </div>
      <?php else: ?>
        <div class="p-3 mb-4 rounded-4 small" style="background: var(--aso-secondary-light); border: 1px solid var(--card-border); color: var(--text-muted);">
          Hola <strong style="color: var(--text);"><?php echo htmlspecialchars($usuarioNombre); ?></strong>, ingresa tu nueva contraseña segura para restablecer el acceso a tu cuenta.
        </div>

        <div id="msg-restablecer"></div>

        <form id="form-restablecer" novalidate>
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

          <div class="mb-3">
            <label for="passwordNueva" class="form-label small fw-bold" style="color: var(--text-muted);">
              Nueva Contraseña
            </label>
            <input type="password" id="passwordNueva" name="passwordNueva" required 
                   placeholder="Escribe tu nueva clave"
                   class="form-control form-control-touch w-100">
          </div>

          <div class="mb-3">
            <label for="passwordConfirm" class="form-label small fw-bold" style="color: var(--text-muted);">
              Confirmar Nueva Contraseña
            </label>
            <input type="password" id="passwordConfirm" name="passwordConfirm" required 
                   placeholder="Confirma la nueva clave"
                   class="form-control form-control-touch w-100">
          </div>

          <!-- Requisitos de Seguridad -->
          <div class="req-list p-3 mb-4">
            <p class="small mb-2">Requisitos de la contraseña:</p>
            <ul class="list-unstyled mb-0" style="display: grid; gap: 0.4rem;">
              <li id="req-len" class="check-req"><span>⚪</span> <span>Mínimo <?php echo $rolUsuario === 'ADMIN' ? '10' : '8'; ?> caracteres</span></li>
              <?php if ($rolUsuario === 'ADMIN'): ?>
                <li id="req-upper" class="check-req"><span>⚪</span> <span>Al menos una letra mayúscula (A-Z)</span></li>
                <li id="req-lower" class="check-req"><span>⚪</span> <span>Al menos una letra minúscula (a-z)</span></li>
                <li id="req-num" class="check-req"><span>⚪</span> <span>Al menos un número (0-9)</span></li>
                <li id="req-sym" class="check-req"><span>⚪</span> <span>Al menos un símbolo especial (!@#$%&*)</span></li>
              <?php endif; ?>
              <li id="req-match" class="check-req"><span>⚪</span> <span>Las dos contraseñas deben coincidir</span></li>
            </ul>
          </div>

          <button type="submit" id="btn-submit-restablecer" class="btn btn-touch btn-aso-primary w-100">
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
        el.className = 'check-req ok';
        el.querySelector('span').textContent = '✅';
      } else {
        el.className = 'check-req';
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
      msgBox.classList.remove('show');

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
          msgBox.className = 'msg-ok show';
          msgBox.textContent = `✅ ${data.mensaje}`;
          setTimeout(() => {
            window.location.href = './login.php?cambiado=true';
          }, 2000);
        } else {
          msgBox.className = 'msg-err show';
          msgBox.textContent = `⚠️ ${data.error || 'Ocurrió un error al restablecer.'}`;
          btn.disabled = false;
          btn.innerHTML = '<span>Guardar Nueva Contraseña</span><span>➜</span>';
        }
      } catch (err) {
        msgBox.className = 'msg-err show';
        msgBox.textContent = '⚠️ Error de conexión con el servidor.';
        btn.disabled = false;
        btn.innerHTML = '<span>Guardar Nueva Contraseña</span><span>➜</span>';
      }
    });
  </script>
</body>
</html>
