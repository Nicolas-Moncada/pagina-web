<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: registro.php
 * Propósito: Registro seguro de nuevos ganaderos asociados con validación, CAPTCHA y CSRF.
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/captcha_gd.php';

// Si ya tiene sesión activa, redirigir
$usuarioActual = getCurrentUser();
if ($usuarioActual) {
    header('Location: ' . ($usuarioActual['rol'] === 'ADMIN' ? 'admin.php' : 'panel.php'));
    exit;
}

$pdo = getDB();
$errorMsg = '';
$exitoMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        $errorMsg = 'Tu sesión de seguridad ha expirado. Por favor recarga el formulario.';
    } else {
        $cedula = normalizarCedula($_POST['cedula'] ?? '');
        $nombre = trim($_POST['nombre'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $telefono = trim($_POST['telefono'] ?? '');
        $finca = trim($_POST['finca'] ?? 'Vereda Alizal');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';
        $captcha = trim($_POST['captcha'] ?? '');
        $recaptchaToken = $_POST['g-recaptcha-response'] ?? null;

        if (empty($cedula) || empty($nombre) || empty($telefono) || empty($password)) {
            $errorMsg = 'Por favor diligencia todos los campos obligatorios.';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'El formato del correo electrónico es inválido.';
        } elseif (!CaptchaService::validar($captcha, $recaptchaToken)) {
            $errorMsg = 'El código de seguridad (CAPTCHA) es incorrecto o ha expirado.';
        } elseif (strlen($nombre) < 3 || strlen($nombre) > 100) {
            $errorMsg = 'El nombre completo debe tener entre 3 y 100 caracteres.';
        } elseif (strlen($password) < 8) {
            $errorMsg = 'La contraseña debe tener como mínimo 8 caracteres.';
        } elseif ($password !== $passwordConfirm) {
            $errorMsg = 'La contraseña y su confirmación no coinciden.';
        } else {
            // Verificar si la cédula o email ya existen
            $stmtCheck = $pdo->prepare("SELECT id FROM usuario WHERE cedula = ? OR (email IS NOT NULL AND email != '' AND email = ?) LIMIT 1");
            $stmtCheck->execute([$cedula, $email]);
            if ($stmtCheck->fetch()) {
                $errorMsg = 'Ya existe una cuenta asociada a este número de cédula o correo electrónico. Por favor inicia sesión.';
            } else {
                try {
                    $userId = generarUUID('usr');
                    $hash = hashPassword($password);
                    $emailVal = !empty($email) ? $email : null;
                    $stmtIns = $pdo->prepare("INSERT INTO usuario (id, cedula, nombre, email, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, sessionVersion) VALUES (?, ?, ?, ?, ?, ?, ?, 'GANADERO', 1, 0, 1)");
                    $stmtIns->execute([$userId, $cedula, $nombre, $emailVal, $telefono, $finca, $hash]);

                    registrarAuditoria($pdo, 'REGISTRO_GANADERO', $userId, $cedula, "Nombre: $nombre | Email: $email");
                    setFlashAlert('success', '¡Registro Exitoso!', 'Tu cuenta ha sido creada con seguridad Argon2id. Ya puedes iniciar sesión con tu cédula o Google.');
                    header('Location: login.php');
                    exit;
                } catch (Exception $e) {
                    error_log("Error en registro: " . $e->getMessage());
                    $errorMsg = 'Ocurrió un error al registrar la cuenta. Inténtalo de nuevo.';
                }
            }
        }
    }
}

$tituloPagina = 'Registro de Asociado | ' . EMPRESA_SIGLA;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container my-auto py-5" style="max-width: 640px;">
  <div class="card card-rural border-0 shadow-lg overflow-hidden">
    <!-- Encabezado Institucional -->
    <div class="p-4 text-center text-white" style="background: var(--grad-primary);">
      <div class="rounded-circle text-white mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px; font-size: 1.6rem; background: var(--grad-accent); box-shadow: var(--shadow-glow-gold);">
        <i class="bi bi-person-plus-fill"></i>
      </div>
      <h3 class="fw-bold mb-1">Registro de Ganadero Asociado</h3>
      <p class="small opacity-75 mb-0">Habilita tu acceso personal al Control Lechero de <?php echo e(EMPRESA_SIGLA); ?></p>
    </div>

    <div class="p-4 p-md-5">
      <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger rounded-4 py-3 small fw-bold mb-4" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
          <?php echo e($errorMsg); ?>
        </div>
      <?php endif; ?>

      <form action="registro.php" method="POST" id="form-registro" class="needs-validation" novalidate>
        <?php echo campoCsrf(); ?>

        <div class="mb-3">
          <label for="cedula" class="form-label fw-bold small text-muted">Cédula de Ciudadanía *</label>
          <div class="input-group">
            <span class="input-group-text bg-transparent text-primary border-2" style="border-radius: var(--radius-md) 0 0 var(--radius-md); border-right: none;">
              <i class="bi bi-person-vcard fs-5"></i>
            </span>
            <input type="text" class="form-control form-control-touch border-start-0" id="cedula" name="cedula" inputmode="numeric" required placeholder="Ej: 20444983" style="border-radius: 0 var(--radius-md) var(--radius-md) 0;" value="<?php echo e($_POST['cedula'] ?? ''); ?>">
          </div>
          <small class="text-muted" style="font-size: 0.78rem;">Será tu número de usuario de ingreso.</small>
        </div>

        <div class="mb-3">
          <label for="nombre" class="form-label fw-bold small text-muted">Nombres y Apellidos Completos *</label>
          <input type="text" class="form-control form-control-touch" id="nombre" name="nombre" required placeholder="Ej: Hilda Gómez" value="<?php echo e($_POST['nombre'] ?? ''); ?>">
        </div>

        <div class="row g-3 mb-3">
          <div class="col-sm-6">
            <label for="telefono" class="form-label fw-bold small text-muted">Teléfono Celular *</label>
            <input type="tel" class="form-control form-control-touch" id="telefono" name="telefono" required placeholder="Ej: 3101234567" value="<?php echo e($_POST['telefono'] ?? ''); ?>">
          </div>
          <div class="col-sm-6">
            <label for="email" class="form-label fw-bold small text-muted">Correo Electrónico (Para Google)</label>
            <input type="email" class="form-control form-control-touch" id="email" name="email" placeholder="ejemplo@gmail.com" value="<?php echo e($_POST['email'] ?? ''); ?>">
          </div>
        </div>

        <div class="mb-3">
          <label for="finca" class="form-label fw-bold small text-muted">Nombre de Finca / Predio</label>
          <input type="text" class="form-control form-control-touch" id="finca" name="finca" placeholder="Ej: Finca La Esperanza" value="<?php echo e($_POST['finca'] ?? ''); ?>">
        </div>

        <div class="row g-3 mb-3">
          <div class="col-sm-6">
            <label for="password" class="form-label fw-bold small text-muted">Contraseña *</label>
            <div class="input-group">
              <input type="password" class="form-control form-control-touch border-end-0" id="password" name="password" required minlength="8" placeholder="Mínimo 8 caracteres">
              <button class="btn btn-outline-secondary border-2 border-start-0" type="button" onclick="const p = document.getElementById('password'); p.type = p.type === 'password' ? 'text' : 'password'; this.querySelector('i').classList.toggle('bi-eye-slash-fill');">
                <i class="bi bi-eye-fill"></i>
              </button>
            </div>
          </div>
          <div class="col-sm-6">
            <label for="password_confirm" class="form-label fw-bold small text-muted">Confirmar Contraseña *</label>
            <div class="input-group">
              <input type="password" class="form-control form-control-touch border-end-0" id="password_confirm" name="password_confirm" required minlength="8" placeholder="Repite la contraseña">
              <button class="btn btn-outline-secondary border-2 border-start-0" type="button" onclick="const p = document.getElementById('password_confirm'); p.type = p.type === 'password' ? 'text' : 'password'; this.querySelector('i').classList.toggle('bi-eye-slash-fill');">
                <i class="bi bi-eye-fill"></i>
              </button>
            </div>
          </div>
        </div>

        <!-- CAPTCHA GD NATIVO -->
        <div class="mb-4 p-3 rounded-4" style="background: var(--aso-secondary-light); border: 1px solid var(--card-border);">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label for="captcha" class="form-label fw-bold small mb-0 text-primary">
              <i class="bi bi-shield-check me-1"></i> Verificación de Seguridad *
            </label>
            <button type="button" class="btn btn-sm btn-link text-primary fw-bold text-decoration-none p-0" onclick="AsoagroUI.refreshCaptcha('#captcha-img', '#captcha')">
              <i class="bi bi-arrow-clockwise"></i> Cambiar
            </button>
          </div>
          <div class="d-flex align-items-center gap-3">
            <img src="includes/captcha_gd.php?t=<?php echo time(); ?>" alt="CAPTCHA" id="captcha-img" class="rounded-3 shadow-sm" style="height: 44px; width: 140px; object-fit: contain;">
            <input type="text" class="form-control form-control-touch text-center fw-bold text-uppercase fs-5" id="captcha" name="captcha" maxlength="5" required placeholder="Código" autocomplete="off">
          </div>
        </div>

        <button type="submit" class="btn btn-touch btn-aso-primary w-100 shadow mb-3">
          <i class="bi bi-check2-circle fs-5"></i>
          <span>Completar Registro</span>
        </button>

        <!-- Opción Google Identity -->
        <div class="d-flex align-items-center my-3">
          <hr class="flex-grow-1 border-secondary-subtle my-0">
          <span class="px-3 text-muted small fw-bold text-uppercase" style="font-size: 0.75rem;">o regístrate con</span>
          <hr class="flex-grow-1 border-secondary-subtle my-0">
        </div>

        <div class="mb-3 d-flex justify-content-center">
          <div id="g_id_onload"
               data-client_id="<?php echo e(GOOGLE_CLIENT_ID); ?>"
               data-callback="handleGoogleLogin"
               data-auto_prompt="false">
          </div>
          <div class="g_id_signin w-100 d-flex justify-content-center"
               data-type="standard"
               data-shape="rectangular"
               data-theme="outline"
               data-text="signup_with"
               data-size="large"
               data-logo_alignment="left"
               data-width="320">
          </div>
        </div>
      </form>

      <div class="text-center pt-3 border-top border-secondary-subtle">
        <small class="text-muted">¿Ya estás registrado?</small>
        <a href="login.php" class="small fw-bold text-primary text-decoration-none ms-1 hover-underline">
          Inicia sesión aquí
        </a>
      </div>
    </div>
  </div>
</main>

<!-- Script Google Identity Services -->
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script>
window.handleGoogleLogin = async function(response) {
  if (!response || !response.credential) return;
  Swal.fire({
    title: 'Validando con Google...',
    allowOutsideClick: false,
    didOpen: () => { Swal.showLoading(); }
  });

  try {
    const cedula = document.getElementById('cedula').value.trim();
    const res = await fetch('api/auth_google.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ credential: response.credential, cedula: cedula })
    });
    const data = await res.json();

    if (res.ok && data.ok) {
      Swal.fire({
        icon: 'success',
        title: '¡Cuenta Vinculada!',
        text: data.mensaje || 'Bienvenido.',
        showConfirmButton: false,
        timer: 1200
      }).then(() => {
        window.location.href = data.redirect || 'panel.php';
      });
    } else if (data.unregistered) {
      if (data.google_email) {
        document.getElementById('email').value = data.google_email;
      }
      if (data.google_name && !document.getElementById('nombre').value) {
        document.getElementById('nombre').value = data.google_name;
      }
      Swal.fire({
        icon: 'info',
        title: 'Cuenta de Google detectada',
        text: 'Completa tu número de cédula y teléfono para finalizar tu afiliación asociativa.',
        confirmButtonColor: '#16375f'
      });
    } else {
      AsoagroUI.showError('Aviso', data.error || 'No fue posible registrar con Google.');
    }
  } catch (e) {
    AsoagroUI.showError('Error', 'Fallo de conexión al validar con Google.');
  }
};
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
