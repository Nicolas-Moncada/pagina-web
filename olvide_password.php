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
            // Verificar si el usuario existe
            $stmt = $pdo->prepare("SELECT id, cedula, nombre, rol, email FROM usuario WHERE cedula = ? AND activo = 1 LIMIT 1");
            $stmt->execute([$cedula]);
            $user = $stmt->fetch();

            if ($user) {
                // Registrar auditoría de la solicitud
                registrarAuditoria($pdo, 'SOLICITUD_RECUPERACION_PASSWORD', $user['id'], $user['cedula'], 'Rol: ' . $user['rol']);

                if ($user['rol'] === 'ADMIN') {
                    $infoMsg = 'Se ha generado un registro para la cuenta de Administración. Comunícate al correo oficial (' . EMPRESA_CORREO . ') o al teléfono oficial ' . EMPRESA_TELEFONO . ' para completar la verificación.';
                } else {
                    $infoMsg = 'Solicitud radicada. Como medida de seguridad rural, la administración validará tu identidad y te asignará una clave temporal de primer ingreso.';
                }
            } else {
                // Mensaje genérico para no revelar si la cédula existe
                $infoMsg = 'Si el número de cédula corresponde a un asociado activo, la administración gestionará el restablecimiento previa validación de identidad.';
            }
        }
    }
}

$tituloPagina = 'Recuperar Contraseña | ' . EMPRESA_SIGLA;
require_once __DIR__ . '/includes/header.php';
?>

<main class="container my-auto py-5" style="max-width: 580px;">
  <div class="card card-rural border-0 shadow-lg overflow-hidden">
    <div class="p-4 text-center text-white" style="background: var(--grad-primary);">
      <div class="rounded-circle text-white mx-auto d-flex align-items-center justify-content-center mb-3" style="width: 58px; height: 58px; font-size: 1.6rem; background: var(--grad-accent); box-shadow: var(--shadow-glow-gold);">
        <i class="bi bi-key-fill"></i>
      </div>
      <h3 class="fw-bold mb-1">Recuperar Acceso</h3>
      <p class="small opacity-75 mb-0"><?php echo e(EMPRESA_SIGLA); ?> &bull; Carmen de Carupa</p>
    </div>

    <div class="p-4 p-md-5">
      <?php if (!empty($errorMsg)): ?>
        <div class="alert alert-danger rounded-4 py-3 small fw-bold mb-4" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
          <?php echo e($errorMsg); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($infoMsg)): ?>
        <div class="alert alert-success rounded-4 py-3 small fw-bold mb-4" role="alert">
          <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
          <?php echo e($infoMsg); ?>
        </div>
      <?php endif; ?>

      <div class="p-3 mb-4 rounded-4 small" style="background: var(--aso-secondary-light); border: 1px solid var(--card-border);">
        <strong class="text-primary"><i class="bi bi-shield-lock me-1"></i> Protocolo de Seguridad</strong>
        <p class="text-muted mb-0 mt-1" style="font-size: 0.85rem;">
          Para proteger la confidencialidad de las planillas de pesajes y liquidaciones, las solicitudes de cambio de clave son verificadas por la administración asociativa.
        </p>
      </div>

      <form action="olvide_password.php" method="POST" id="form-olvide" class="needs-validation" novalidate>
        <?php echo campoCsrf(); ?>

        <div class="mb-3">
          <label for="cedula" class="form-label fw-bold small text-muted">Número de Cédula Registrada *</label>
          <div class="input-group">
            <span class="input-group-text bg-transparent text-primary border-2" style="border-radius: var(--radius-md) 0 0 var(--radius-md); border-right: none;">
              <i class="bi bi-person-vcard fs-5"></i>
            </span>
            <input type="text" class="form-control form-control-touch border-start-0" id="cedula" name="cedula" inputmode="numeric" required placeholder="Ej: 10123456" style="border-radius: 0 var(--radius-md) var(--radius-md) 0;" value="<?php echo e($_POST['cedula'] ?? ''); ?>">
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
          <i class="bi bi-send-fill me-1"></i>
          <span>Solicitar Restablecimiento</span>
        </button>
      </form>

      <div class="text-center pt-3 border-top border-secondary-subtle">
        <a href="login.php" class="small fw-bold text-primary text-decoration-none hover-underline">
          <i class="bi bi-arrow-left me-1"></i> Volver a Iniciar Sesión
        </a>
      </div>
    </div>
  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
