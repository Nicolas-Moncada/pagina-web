<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: olvide_password.php
 * Propósito: Solicitud de restablecimiento de contraseña para asociados y administradores.
 * Fuente Oficial: docs/FICHA_OFICIAL_ASOAGROALIZAL.md
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/captcha_gd.php';

$usuarioActual = getCurrentUser();
if ($usuarioActual) {
    header('Location: ' . ($usuarioActual['rol'] === 'ADMIN' ? 'admin.php' : 'panel.php'));
    exit;
}

$pdo = getDB();
$errorMsg = '';
$infoMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validarCsrfToken($token)) {
        $errorMsg = 'Tu sesión de seguridad ha expirado. Por favor recarga el formulario.';
    } else {
        $cedula = normalizarCedula($_POST['cedula'] ?? '');
        $captcha = trim($_POST['captcha'] ?? '');
        $recaptchaToken = $_POST['g-recaptcha-response'] ?? null;

        if (empty($cedula)) {
            $errorMsg = 'Por favor ingresa tu número de cédula.';
        } elseif (!CaptchaService::validar($captcha, $recaptchaToken)) {
            $errorMsg = 'El código de seguridad (CAPTCHA) es incorrecto o ha vencido.';
        } else {
            $stmt = $pdo->prepare("SELECT id, cedula, nombre, rol, email FROM usuario WHERE cedula = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$cedula]);
            $user = $stmt->fetch();

            if ($user) {
                registrarAuditoria($pdo, 'SOLICITUD_RECUPERACION_PASSWORD', $user['id'], $user['cedula'], 'Rol: ' . $user['rol']);

                if ($user['rol'] === 'ADMIN') {
                    $infoMsg = 'Se ha generado un registro para la cuenta de Administración. Comunícate al correo oficial (' . EMPRESA_CORREO . ') o al teléfono oficial ' . EMPRESA_TELEFONO . ' para completar la verificación.';
                } else {
                    $infoMsg = 'Solicitud radicada. Como medida de seguridad, la administración validará tu identidad y te asignará una clave temporal de primer ingreso.';
                }
            } else {
                $infoMsg = 'Si el número de cédula corresponde a un asociado activo, la administración gestionará el restablecimiento previa validación de identidad.';
            }
        }
    }
}

$tituloPagina = 'Recuperar Contraseña | ' . EMPRESA_SIGLA;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container my-auto py-5" style="max-width: 560px;">
  <div class="card card-rural border shadow-lg overflow-hidden">
    <div class="p-4 text-center text-white" style="background: linear-gradient(135deg, var(--brand-primary-dark) 0%, var(--brand-primary) 100%);">
      <div class="rounded-circle text-white mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 54px; height: 54px; background-color: var(--brand-accent); box-shadow: var(--shadow-glow-gold);">
        <i data-lucide="key-round" style="width: 24px; height: 24px;"></i>
      </div>
      <h3 class="fw-bold mb-1 text-white">Recuperar Acceso</h3>
      <p class="small mb-0" style="color: #CBD5E1;"><?php echo e(EMPRESA_SIGLA); ?> &bull; Carmen de Carupa</p>
    </div>

    <div class="p-4 p-md-5 bg-white">
      <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger rounded-3 py-2.5 px-3 small fw-semibold mb-4 d-flex align-items-center gap-2" role="alert">
          <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
          <span><?php echo e($errorMsg); ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($infoMsg)): ?>
        <div class="alert alert-success rounded-3 py-2.5 px-3 small fw-semibold mb-4 d-flex align-items-center gap-2" role="alert">
          <i data-lucide="check-circle-2" style="width: 18px; height: 18px;"></i>
          <span><?php echo e($infoMsg); ?></span>
        </div>
      <?php endif; ?>

      <div class="p-3 mb-4 rounded-3 small" style="background: var(--bg-canvas); border: 1px solid var(--border-subtle);">
        <strong class="d-flex align-items-center gap-1.5" style="color: var(--brand-primary);">
          <i data-lucide="shield-check" style="width: 16px; height: 16px;"></i> Protocolo de Seguridad
        </strong>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">
          Para proteger la confidencialidad de las planillas de pesajes y liquidaciones, las solicitudes de cambio de clave son verificadas por la administración asociativa.
        </p>
      </div>

      <form action="olvide_password.php" method="POST" id="form-olvide" class="needs-validation" novalidate>
        <?php echo campoCsrf(); ?>

        <div class="input-group-auth mb-3">
          <label for="cedula">Número de Cédula Registrada *</label>
          <div class="input-auth-wrapper">
            <i data-lucide="user" class="input-auth-icon" style="width: 18px; height: 18px;"></i>
            <input type="text" class="form-control-auth font-mono" id="cedula" name="cedula" inputmode="numeric" required placeholder="Ej. 20444983" value="<?php echo e($_POST['cedula'] ?? ''); ?>">
          </div>
        </div>

        <!-- CAPTCHA GD NATIVO -->
        <div class="mb-4 p-3 rounded-3" style="background: var(--bg-canvas); border: 1px solid var(--border-subtle);">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <label for="captcha" class="fw-bold small mb-0 d-flex align-items-center gap-1.5" style="color: var(--brand-primary);">
              <i data-lucide="shield" style="width: 15px; height: 15px;"></i> Verificación de Seguridad *
            </label>
            <button type="button" class="btn btn-sm btn-link fw-bold text-decoration-none p-0 d-inline-flex align-items-center gap-1" style="color: var(--brand-secondary);" onclick="AsoagroUI.refreshCaptcha('#captcha-img', '#captcha')">
              <i data-lucide="refresh-cw" style="width: 13px; height: 13px;"></i> Cambiar
            </button>
          </div>
          <div class="d-flex align-items-center gap-3">
            <img src="api/captcha_img.php?t=<?php echo time(); ?>" alt="CAPTCHA" id="captcha-img" class="rounded border bg-white" style="height: 44px; width: 135px; object-fit: contain;">
            <input type="text" class="form-control text-center font-mono fw-bold text-uppercase fs-5" id="captcha" name="captcha" maxlength="5" required placeholder="Código" autocomplete="off" style="min-height: 44px;">
          </div>
        </div>

        <button type="submit" class="btn-auth-submit w-100 mb-3">
          <i data-lucide="send" style="width: 17px; height: 17px;"></i>
          <span>Solicitar Restablecimiento</span>
        </button>
      </form>

      <div class="text-center pt-3 border-top">
        <a href="login.php" class="small fw-bold text-decoration-none d-inline-flex align-items-center gap-1" style="color: var(--brand-primary);">
          <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i> Volver a Iniciar Sesión
        </a>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
