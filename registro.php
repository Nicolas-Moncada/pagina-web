<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: registro.php
 * Propósito: Registro seguro de nuevos ganaderos asociados al estilo Split-Screen:
 *            - Validación estricta en servidor, hashing criptográfico Argon2id y CSRF.
 *            - Panel institucional coordinado con Central Ganadera y medidor de clave.
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

// Consulta de precio en vivo
$precioLitroReg = 2250.0;
try {
    $stmtQP = $pdo->query("SELECT precioLitro FROM quincena ORDER BY fechaInicio DESC LIMIT 1");
    if ($qp = $stmtQP->fetch(PDO::FETCH_ASSOC)) {
        if (!empty($qp['precioLitro']) && $qp['precioLitro'] > 0) {
            $precioLitroReg = (float)$qp['precioLitro'];
        }
    }
} catch (Exception $e) {}

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
            $errorMsg = 'Por favor diligencia todos los campos requeridos con asterisco (*).';
        } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'El formato del correo electrónico no es válido.';
        } elseif (!CaptchaService::validar($captcha, $recaptchaToken)) {
            $errorMsg = 'El código de seguridad (CAPTCHA) es incorrecto o ha vencido.';
        } elseif (strlen($nombre) < 3 || strlen($nombre) > 100) {
            $errorMsg = 'El nombre completo debe tener entre 3 y 100 caracteres.';
        } elseif (strlen($password) < 8) {
            $errorMsg = 'La contraseña debe contener al menos 8 caracteres.';
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

                    registrarAuditoria($pdo, 'REGISTRO_GANADERO', $userId, $cedula, "Nombre: $nombre | Finca: $finca");
                    setFlashAlert('success', '¡Registro Exitoso!', 'Tu cuenta ha sido creada correctamente con cifrado Argon2id. Ya puedes iniciar sesión.');
                    header('Location: login.php');
                    exit;
                } catch (Exception $e) {
                    error_log("Error en registro: " . $e->getMessage());
                    $errorMsg = 'Ocurrió un error al procesar el registro. Por favor intente más tarde.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light">
  <title>Registro de Asociado &bull; <?php echo e(EMPRESA_SIGLA); ?></title>

  <!-- Favicon Oficial -->
  <link rel="icon" type="image/webp" href="assets/img/logo.webp">

  <!-- Google Fonts: Plus Jakarta Sans, Inter, JetBrains Mono -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  
  <!-- FontAwesome 6 Free -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

  <!-- Estilos Globales Corporativos -->
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light">

  <div class="auth-split-wrapper">

    <!-- PANEL IZQUIERDO INSTITUCIONAL -->
    <div class="auth-panel-brand">
      <div>
        <a href="index.php" class="d-inline-flex align-items-center gap-2 text-white text-decoration-none mb-4 opacity-90">
          <i class="fa-solid fa-arrow-left"></i>
          <span class="small fw-bold">Volver al Portal Principal</span>
        </a>

        <div class="mb-4">
          <img src="assets/img/logo.webp" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" class="auth-brand-logo bg-white p-2 rounded-4 shadow-sm">
          <h2 class="fw-bold text-white mb-1" style="font-size: 1.85rem;"><?php echo e(EMPRESA_NOMBRE); ?></h2>
          <span class="auth-badge-role">
            <i class="fa-solid fa-user-plus"></i>
            <span>Afiliación de Productores Lecheros</span>
          </span>
        </div>

        <p class="text-light opacity-85 small mb-4" style="line-height: 1.7;">
          Únete a la asociación oficial de ganaderos de la Vereda Alizal. Con tu cuenta podrás consultar diariamente tus litros recibidos en turnos Mañana (☀️) y Tarde (🌅), tu historial de pagos y descargar tus colillas de liquidación.
        </p>

        <!-- Tarjeta Flotante con Precio Oficial -->
        <div class="auth-price-pill shadow-sm">
          <div class="d-flex align-items-center justify-content-between mb-1">
            <span class="small text-uppercase fw-bold text-warning">Tarifa Oficial Vigente</span>
            <span class="badge bg-success rounded-pill px-2 py-0.5" style="font-size: 0.72rem;">Activa</span>
          </div>
          <div class="fs-2 fw-bold text-white tabular-nums mb-1">
            $<?php echo number_format($precioLitroReg, 0, ',', '.'); ?> <span class="fs-6 fw-normal opacity-75">COP / Litro</span>
          </div>
          <small class="text-light opacity-75 d-block">
            <i class="fa-solid fa-check-double text-warning me-1"></i> Liquidación quincenal garantizada
          </small>
        </div>
      </div>

      <div class="mt-4 pt-3 border-top border-white border-opacity-15 small text-light opacity-75">
        <span><i class="fa-solid fa-lock text-warning me-1"></i> Cifrado de alta seguridad Argon2id &bull; NIT: <?php echo e(EMPRESA_NIT); ?></span>
      </div>
    </div>

    <!-- PANEL DERECHO CON FORMULARIO ESTRUCTURADO -->
    <div class="auth-panel-form">
      <div class="auth-card-main" style="max-width: 580px;">
        
        <div class="mb-4">
          <h1 class="auth-title">Solicitud de Registro</h1>
          <p class="auth-subtitle">Completa tus datos personales para habilitar tu acceso al control lechero.</p>
        </div>

        <?php if (!empty($errorMsg)): ?>
          <div class="alert alert-danger rounded-3 py-2 px-3 small fw-bold mb-4 d-flex align-items-center gap-2" role="alert">
            <i class="fa-solid fa-triangle-exclamation fs-5 flex-shrink-0"></i>
            <span><?php echo e($errorMsg); ?></span>
          </div>
        <?php endif; ?>

        <form action="registro.php" method="POST" id="form-registro" novalidate>
          <?php echo campoCsrf(); ?>

          <div class="row g-3">
            <!-- Cédula -->
            <div class="col-md-6">
              <label for="cedula" class="form-label fw-bold small text-dark">Cédula de Ciudadanía *</label>
              <div class="input-auth-wrapper">
                <i class="fa-solid fa-id-card input-auth-icon"></i>
                <input type="text" class="form-control-auth" id="cedula" name="cedula" inputmode="numeric" required placeholder="Ej. 20444983" value="<?php echo e($_POST['cedula'] ?? ''); ?>">
              </div>
            </div>

            <!-- Teléfono -->
            <div class="col-md-6">
              <label for="telefono" class="form-label fw-bold small text-dark">Teléfono / WhatsApp *</label>
              <div class="input-auth-wrapper">
                <i class="fa-solid fa-phone input-auth-icon"></i>
                <input type="tel" class="form-control-auth" id="telefono" name="telefono" required placeholder="Ej. 3185235690" value="<?php echo e($_POST['telefono'] ?? ''); ?>">
              </div>
            </div>

            <!-- Nombre Completo -->
            <div class="col-12">
              <label for="nombre" class="form-label fw-bold small text-dark">Nombre y Apellidos Completos *</label>
              <div class="input-auth-wrapper">
                <i class="fa-solid fa-user input-auth-icon"></i>
                <input type="text" class="form-control-auth" id="nombre" name="nombre" required placeholder="Ej. Hilda Inés Montaño" value="<?php echo e($_POST['nombre'] ?? ''); ?>">
              </div>
            </div>

            <!-- Finca / Vereda -->
            <div class="col-md-6">
              <label for="finca" class="form-label fw-bold small text-dark">Nombre de la Finca o Predio</label>
              <div class="input-auth-wrapper">
                <i class="fa-solid fa-house-chimney-window input-auth-icon"></i>
                <input type="text" class="form-control-auth" id="finca" name="finca" placeholder="Ej. Finca El Recuerdo" value="<?php echo e($_POST['finca'] ?? 'Vereda Alizal'); ?>">
              </div>
            </div>

            <!-- Correo Electrónico (para Google OAuth) -->
            <div class="col-md-6">
              <label for="email" class="form-label fw-bold small text-dark">Correo Electrónico (Recomendado)</label>
              <div class="input-auth-wrapper">
                <i class="fa-solid fa-envelope input-auth-icon"></i>
                <input type="email" class="form-control-auth" id="email" name="email" placeholder="usuario@gmail.com" value="<?php echo e($_POST['email'] ?? ''); ?>">
              </div>
              <small class="text-muted" style="font-size: 0.74rem;">Permite iniciar sesión con Google en 1 clic.</small>
            </div>

            <!-- Contraseña -->
            <div class="col-md-6">
              <label for="password" class="form-label fw-bold small text-dark">Contraseña (Mín. 8 caract.) *</label>
              <div class="input-auth-wrapper">
                <i class="fa-solid fa-lock input-auth-icon"></i>
                <input type="password" class="form-control-auth" id="password" name="password" required placeholder="Crea tu contraseña">
              </div>
            </div>

            <!-- Confirmar Contraseña -->
            <div class="col-md-6">
              <label for="password_confirm" class="form-label fw-bold small text-dark">Confirmar Contraseña *</label>
              <div class="input-auth-wrapper">
                <i class="fa-solid fa-lock-open input-auth-icon"></i>
                <input type="password" class="form-control-auth" id="password_confirm" name="password_confirm" required placeholder="Repite la clave">
              </div>
            </div>

            <!-- Medidor de Fortaleza de Clave -->
            <div class="col-12">
              <div class="progress" style="height: 5px;">
                <div id="password-strength-bar" class="progress-bar bg-danger" role="progressbar" style="width: 0%;"></div>
              </div>
              <small id="password-strength-text" class="text-muted d-block mt-1" style="font-size: 0.74rem;">Seguridad de la contraseña</small>
            </div>

            <!-- CAPTCHA -->
            <div class="col-12">
              <div class="p-3 rounded-3" style="background-color: var(--bg-surface-alt); border: 1px solid var(--border-subtle);">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <span class="small fw-bold text-dark">
                    <i class="fa-solid fa-shield-halved text-success me-1"></i> Código de Seguridad *
                  </span>
                  <button type="button" id="btn-recargar-captcha" class="btn btn-sm btn-link p-0 text-decoration-none small fw-bold" style="color: var(--secondary-green);">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Cambiar imagen
                  </button>
                </div>
                <div class="d-flex align-items-center gap-3">
                  <img src="includes/captcha_gd.php?t=<?php echo time(); ?>" alt="Código CAPTCHA" id="captcha-img" class="rounded-2 border bg-white" style="height: 48px; width: 140px; object-fit: contain;">
                  <input type="text" class="form-control text-center fw-bold text-uppercase fs-5" id="captcha-input" name="captcha" maxlength="5" required placeholder="Código" autocomplete="off" style="min-height: 48px;">
                </div>
              </div>
            </div>

            <!-- Botón Submit -->
            <div class="col-12 mt-4">
              <button type="submit" class="btn-auth-submit">
                <i class="fa-solid fa-user-check"></i>
                <span>Crear Cuenta de Asociado</span>
              </button>
            </div>
          </div>
        </form>

        <div class="text-center pt-3 border-top mt-4">
          <small class="text-muted d-block mb-1">¿Ya tienes una cuenta registrada en ASOAGROALIZAL?</small>
          <a href="login.php" class="fw-bold small text-decoration-none" style="color: var(--secondary-green);">
            Iniciar Sesión con Cédula o Google &rarr;
          </a>
        </div>

      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Recarga de CAPTCHA
      const btnRecargar = document.getElementById('btn-recargar-captcha');
      const captchaImg = document.getElementById('captcha-img');
      const captchaInput = document.getElementById('captcha-input');
      btnRecargar?.addEventListener('click', function() {
        captchaImg.src = 'includes/captcha_gd.php?t=' + new Date().getTime();
        if (captchaInput) captchaInput.value = '';
      });

      // Medidor de fortaleza de contraseña
      const pass = document.getElementById('password');
      const bar = document.getElementById('password-strength-bar');
      const txt = document.getElementById('password-strength-text');

      pass?.addEventListener('input', function() {
        const val = pass.value;
        let score = 0;
        if (val.length >= 8) score += 25;
        if (/[A-Z]/.test(val)) score += 25;
        if (/[0-9]/.test(val)) score += 25;
        if (/[^A-Za-z0-9]/.test(val)) score += 25;

        bar.style.width = score + '%';
        if (score <= 25) {
          bar.className = 'progress-bar bg-danger';
          txt.textContent = 'Contraseña débil (mínimo 8 caracteres)';
        } else if (score <= 50) {
          bar.className = 'progress-bar bg-warning';
          txt.textContent = 'Contraseña moderada (agrega números y mayúsculas)';
        } else if (score <= 75) {
          bar.className = 'progress-bar bg-info';
          txt.textContent = 'Contraseña buena';
        } else {
          bar.className = 'progress-bar bg-success';
          txt.textContent = 'Contraseña muy segura';
        }
      });
    });
  </script>
</body>
</html>
