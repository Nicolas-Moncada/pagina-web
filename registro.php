<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: registro.php
 * Propósito: Registro seguro de nuevos ganaderos asociados al estilo Split-Screen:
 *            - Validación estricta en servidor, hashing criptográfico Argon2id y CSRF.
 *            - Panel institucional coordinado con Central Ganadera y medidor de clave.
 *            - 100% CERO EMOJIS, iconografía Lucide Icons monocromática (stroke-width: 1.5px).
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

  <!-- Tailwind CSS CDN (preflight false) -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      corePlugins: { preflight: false },
      theme: {
        extend: {
          colors: {
            brand: {
              primary: '#0A2E1C',
              secondary: '#14532D',
              canvas: '#F8FAFC',
              surface: '#FFFFFF'
            }
          }
        }
      }
    };
  </script>

  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <!-- Estilos Globales Corporativos -->
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-slate-50 min-vh-100 flex flex-col justify-center">

  <div class="auth-split-wrapper">

    <!-- PANEL IZQUIERDO INSTITUCIONAL -->
    <div class="auth-panel-brand" style="background-color: var(--brand-primary, #0A2E1C);">
      <div>
        <a href="index.php" class="inline-flex items-center gap-2 text-white/80 hover:text-white text-decoration-none mb-8 transition-colors">
          <i data-lucide="arrow-left" style="width: 18px; height: 18px;"></i>
          <span class="text-sm font-medium">Volver al Portal Principal</span>
        </a>

        <div class="mb-6">
          <div class="inline-block p-2 bg-white rounded-xl shadow-sm mb-4">
            <img src="assets/img/logo.webp" alt="Logo <?php echo e(EMPRESA_SIGLA); ?>" style="height: 52px; width: auto; object-fit: contain;">
          </div>
          <h2 class="font-bold text-white mb-2" style="font-size: 1.75rem; line-height: 1.25;"><?php echo e(EMPRESA_NOMBRE); ?></h2>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-900/60 text-emerald-200 border border-emerald-700/50">
            <i data-lucide="user-plus" style="width: 14px; height: 14px;"></i>
            <span>Afiliación de Productores Lecheros</span>
          </span>
        </div>

        <p class="text-slate-300 text-sm mb-6 leading-relaxed">
          Únete a la asociación oficial de ganaderos de la Vereda Alizal. Con tu cuenta podrás consultar diariamente tus litros recibidos en turnos Mañana (Turno AM) y Tarde (Turno PM), tu historial de pagos y descargar tus colillas de liquidación.
        </p>

        <!-- Tarjeta Flotante con Precio Oficial -->
        <div class="p-4 rounded-xl border border-emerald-800/80 bg-emerald-950/40 backdrop-blur-sm mb-6">
          <div class="flex items-center justify-between mb-2">
            <span class="text-xs uppercase tracking-wider font-bold text-amber-400">Tarifa Oficial Vigente</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
              Activa
            </span>
          </div>
          <div class="text-2xl font-bold text-white tabular-nums mb-1 font-mono">
            $<?php echo number_format($precioLitroReg, 0, ',', '.'); ?> <span class="text-sm font-normal text-slate-300">COP / Litro</span>
          </div>
          <span class="text-xs text-slate-400 flex items-center gap-1.5">
            <i data-lucide="check-check" class="text-emerald-400" style="width: 14px; height: 14px;"></i>
            <span>Liquidación quincenal garantizada</span>
          </span>
        </div>
      </div>

      <div class="mt-8 pt-4 border-t border-white/10 text-xs text-slate-400">
        <span class="flex items-center gap-1.5">
          <i data-lucide="shield-check" class="text-emerald-400" style="width: 14px; height: 14px;"></i>
          <span>Cifrado de alta seguridad Argon2id &bull; NIT: <?php echo e(EMPRESA_NIT); ?></span>
        </span>
      </div>
    </div>

    <!-- PANEL DERECHO CON FORMULARIO ESTRUCTURADO -->
    <div class="auth-panel-form">
      <div class="auth-card-main" style="max-width: 580px;">
        
        <div class="mb-5 text-start">
          <h1 class="text-2xl font-bold text-slate-900 mb-1">Solicitud de Registro</h1>
          <p class="text-sm text-slate-600 mb-0">Completa tus datos personales para habilitar tu acceso al control lechero.</p>
        </div>

        <?php if (!empty($errorMsg)): ?>
          <div class="alert alert-danger rounded-lg py-2.5 px-3 text-sm font-medium mb-4 flex items-center gap-2 border border-red-200" role="alert">
            <i data-lucide="alert-circle" class="text-red-600 flex-shrink-0" style="width: 18px; height: 18px;"></i>
            <span><?php echo e($errorMsg); ?></span>
          </div>
        <?php endif; ?>

        <form action="registro.php" method="POST" id="form-registro" novalidate>
          <?php echo campoCsrf(); ?>

          <div class="row g-3">
            <!-- Cédula -->
            <div class="col-md-6">
              <label for="cedula" class="form-label font-semibold text-xs text-slate-700">Cédula de Ciudadanía *</label>
              <div class="input-auth-wrapper">
                <i data-lucide="user" class="input-auth-icon" style="width: 18px; height: 18px;"></i>
                <input type="text" class="form-control-auth font-mono" id="cedula" name="cedula" inputmode="numeric" required placeholder="Ej. 20444983" value="<?php echo e($_POST['cedula'] ?? ''); ?>">
              </div>
            </div>

            <!-- Teléfono -->
            <div class="col-md-6">
              <label for="telefono" class="form-label font-semibold text-xs text-slate-700">Teléfono / WhatsApp *</label>
              <div class="input-auth-wrapper">
                <i data-lucide="phone" class="input-auth-icon" style="width: 18px; height: 18px;"></i>
                <input type="tel" class="form-control-auth" id="telefono" name="telefono" required placeholder="Ej. 3185235690" value="<?php echo e($_POST['telefono'] ?? ''); ?>">
              </div>
            </div>

            <!-- Nombre Completo -->
            <div class="col-12">
              <label for="nombre" class="form-label font-semibold text-xs text-slate-700">Nombre y Apellidos Completos *</label>
              <div class="input-auth-wrapper">
                <i data-lucide="user-check" class="input-auth-icon" style="width: 18px; height: 18px;"></i>
                <input type="text" class="form-control-auth" id="nombre" name="nombre" required placeholder="Ej. Hilda Inés Montaño" value="<?php echo e($_POST['nombre'] ?? ''); ?>">
              </div>
            </div>

            <!-- Finca / Vereda -->
            <div class="col-md-6">
              <label for="finca" class="form-label font-semibold text-xs text-slate-700">Nombre de la Finca o Predio</label>
              <div class="input-auth-wrapper">
                <i data-lucide="map-pin" class="input-auth-icon" style="width: 18px; height: 18px;"></i>
                <input type="text" class="form-control-auth" id="finca" name="finca" placeholder="Ej. Finca El Recuerdo" value="<?php echo e($_POST['finca'] ?? 'Vereda Alizal'); ?>">
              </div>
            </div>

            <!-- Correo Electrónico (para Google OAuth) -->
            <div class="col-md-6">
              <label for="email" class="form-label font-semibold text-xs text-slate-700">Correo Electrónico (Recomendado)</label>
              <div class="input-auth-wrapper">
                <i data-lucide="mail" class="input-auth-icon" style="width: 18px; height: 18px;"></i>
                <input type="email" class="form-control-auth" id="email" name="email" placeholder="usuario@gmail.com" value="<?php echo e($_POST['email'] ?? ''); ?>">
              </div>
              <span class="text-xs text-slate-500 block mt-1">Permite iniciar sesión con Google en 1 clic.</span>
            </div>

            <!-- Contraseña -->
            <div class="col-md-6">
              <label for="password" class="form-label font-semibold text-xs text-slate-700">Contraseña (Mín. 8 caract.) *</label>
              <div class="input-auth-wrapper">
                <i data-lucide="lock" class="input-auth-icon" style="width: 18px; height: 18px;"></i>
                <input type="password" class="form-control-auth" id="password" name="password" required placeholder="Crea tu contraseña">
              </div>
            </div>

            <!-- Confirmar Contraseña -->
            <div class="col-md-6">
              <label for="password_confirm" class="form-label font-semibold text-xs text-slate-700">Confirmar Contraseña *</label>
              <div class="input-auth-wrapper">
                <i data-lucide="shield-check" class="input-auth-icon" style="width: 18px; height: 18px;"></i>
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
              <div class="p-3 rounded-lg bg-slate-50 border border-slate-200">
                <div class="flex justify-between items-center mb-1">
                  <span class="text-xs font-semibold text-slate-700 flex items-center gap-1">
                    <i data-lucide="shield" class="text-emerald-700" style="width: 14px; height: 14px;"></i> Código de Seguridad *
                  </span>
                  <button type="button" id="btn-recargar-captcha" class="btn btn-sm btn-link p-0 text-decoration-none text-xs font-semibold text-emerald-800 hover:text-emerald-950 flex items-center gap-1">
                    <i data-lucide="refresh-cw" style="width: 13px; height: 13px;"></i> Cambiar imagen
                  </button>
                </div>
                <div class="flex items-center gap-3">
                  <img src="includes/captcha_gd.php?t=<?php echo time(); ?>" alt="Código CAPTCHA" id="captcha-img" class="rounded border bg-white" style="height: 44px; width: 130px; object-fit: contain;">
                  <input type="text" class="form-control text-center font-mono font-bold tracking-widest text-lg" id="captcha-input" name="captcha" maxlength="5" required placeholder="Código" autocomplete="off" style="min-height: 44px;">
                </div>
              </div>
            </div>

            <!-- Botón Submit -->
            <div class="col-12 mt-4">
              <button type="submit" class="btn-auth-submit">
                <i data-lucide="check" style="width: 18px; height: 18px;"></i>
                <span>Crear Cuenta de Asociado</span>
              </button>
            </div>
          </div>
        </form>

        <div class="text-center pt-3 border-t border-slate-200 mt-4">
          <span class="text-xs text-slate-600 block mb-1">¿Ya tienes una cuenta registrada en ASOAGROALIZAL?</span>
          <a href="login.php" class="font-semibold text-xs text-emerald-800 hover:text-emerald-950 text-decoration-none inline-flex items-center gap-1">
            <span>Iniciar Sesión con Cédula o Google</span>
            <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
          </a>
        </div>

      </div>
    </div>

  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    if (window.lucide) {
      lucide.createIcons({ attrs: { 'stroke-width': 1.5 } });
    }

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
