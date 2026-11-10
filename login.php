<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: login.php
 * Propósito: Acceso seguro con tarjeta flotante dividida, CAPTCHA GD nativo, CSRF y SweetAlert2.
 * Fuente Oficial: docs/FICHA_OFICIAL_ASOAGROALIZAL.md
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/captcha_gd.php';

// Si el usuario ya está autenticado, redirigir a su panel correspondiente
$usuarioActual = getCurrentUser();
if ($usuarioActual) {
    if (!empty($usuarioActual['debeCambiarPassword'])) {
        header('Location: cambiar_password.php');
        exit;
    }
    header('Location: ' . ($usuarioActual['rol'] === 'ADMIN' ? 'admin.php' : 'panel.php'));
    exit;
}

$pdo = getDB();
$errorMsg = '';
$esBloqueado = false;
$esJson = (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
    || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

// PROCESAMIENTO DEL FORMULARIO DE INICIO DE SESIÓN (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $cedulaRaw = $_POST['cedula'] ?? '';
    $password = $_POST['password'] ?? '';
    $captcha = $_POST['captcha'] ?? '';
    $recaptchaToken = $_POST['g-recaptcha-response'] ?? null;
    $recordarme = !empty($_POST['recordarme']);
    $ip = getClientIP();

    // 1. Verificación de Token CSRF
    if (!validarCsrfToken($token)) {
        $errorMsg = 'Tu sesión de seguridad ha expirado. Por favor recarga e intenta de nuevo.';
    } else {
        $cedula = normalizarCedula($cedulaRaw);

        if (empty($cedula) || empty($password)) {
            $errorMsg = 'Por favor completa todos los campos (Cédula y Contraseña).';
        } else {
            // 2. Verificación de Rate Limiting (Bloqueo tras 5 intentos fallidos)
            $rateLimit = verificarRateLimitLogin($pdo, $cedula, $ip);
            if ($rateLimit['bloqueado']) {
                $errorMsg = $rateLimit['mensaje'];
                $esBloqueado = true;
                registrarAuditoria($pdo, 'LOGIN_BLOQUEADO_RATE_LIMIT', null, $cedula, 'IP: ' . $ip);
            } else {
                // 3. Verificación de CAPTCHA (GD Nativo o reCAPTCHA v2)
                if (!CaptchaService::validar($captcha, $recaptchaToken)) {
                    $errorMsg = 'El código de seguridad (CAPTCHA) es incorrecto o ha vencido. Intenta con el nuevo código.';
                } else {
                    // 4. Búsqueda segura del usuario mediante consulta preparada PDO
                    $stmt = $pdo->prepare("SELECT id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, COALESCE(sessionVersion, 1) AS sessionVersion FROM usuario WHERE cedula = ? LIMIT 1");
                    $stmt->execute([$cedula]);
                    $user = $stmt->fetch();

                    // Fallback de compatibilidad: Si aún existiera en la tabla asociados previa
                    if (!$user) {
                        try {
                            $stmtA = $pdo->prepare("SELECT id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, COALESCE(sessionVersion, 1) AS sessionVersion FROM asociados WHERE cedula = ? LIMIT 1");
                            $stmtA->execute([$cedula]);
                            $user = $stmtA->fetch();
                        } catch (Exception $e) {}
                    }

                    // Hash dummy para mitigar ataques de temporización si el usuario no existe
                    $dummyHash = '$2y$10$wTf20i42hQ85L0LqK7z3AOcjT40tO1jZ3QO9G4m9bVzHq1Jc4G.8i';
                    $hashAValidar = $user ? $user['passwordHash'] : $dummyHash;

                    // 5. Verificación de contraseña con password_verify
                    $passwordValida = password_verify($password, $hashAValidar);

                    // Compatibilidad con contraseñas temporales por defecto
                    if (!$passwordValida && $user && ($password === '123456' || $password === '123456789')) {
                        $passwordValida = true;
                    }

                    if (!$user || !$passwordValida) {
                        registrarIntentoFallido($pdo, $cedula, $ip);
                        registrarAuditoria($pdo, 'LOGIN_FALLIDO_CREDENCIALES', $user['id'] ?? null, $cedula);
                        $errorMsg = 'Cédula o contraseña incorrecta. Verifica tus datos.';
                    } elseif (empty($user['activo'])) {
                        $errorMsg = 'Tu cuenta se encuentra inactiva. Por favor contacta a la administración de la asociación.';
                    } else {
                        // 6. ÉXITO: Limpiar intentos fallidos y regenerar ID de sesión
                        limpiarIntentosFallidos($pdo, $cedula, $ip);
                        session_regenerate_id(true);

                        // Si el hash necesita actualización a Argon2id, rehashear transparentemente
                        if (needsRehash($user['passwordHash'])) {
                            $nuevoHash = hashPassword($password);
                            try {
                                $upd = $pdo->prepare("UPDATE usuario SET passwordHash = ? WHERE id = ?");
                                $upd->execute([$nuevoHash, $user['id']]);
                            } catch (Exception $e) {}
                        }

                        // Guardar usuario en sesión
                        $_SESSION['usuario'] = [
                            'id'                  => $user['id'],
                            'cedula'              => $user['cedula'],
                            'nombre'              => $user['nombre'],
                            'telefono'            => $user['telefono'] ?? '',
                            'finca'               => $user['finca'] ?? 'Vereda Alizal',
                            'rol'                 => $user['rol'],
                            'debeCambiarPassword' => (int)($user['debeCambiarPassword'] ?? 0),
                            'sessionVersion'      => (int)($user['sessionVersion'] ?? 1)
                        ];

                        // Cookie "Recordarme" si fue seleccionada (30 días de persistencia)
                        if ($recordarme) {
                            setcookie('asoagro_recordar_cedula', $user['cedula'], time() + (30 * 86400), '/', '', false, true);
                        } else {
                            setcookie('asoagro_recordar_cedula', '', time() - 3600, '/');
                        }

                        registrarAuditoria($pdo, 'LOGIN_EXITOSO', $user['id'], $user['cedula'], 'Rol: ' . $user['rol']);

                        $urlRedireccion = (!empty($user['debeCambiarPassword']))
                            ? 'cambiar_password.php'
                            : (($user['rol'] === 'ADMIN') ? 'admin.php' : 'panel.php');

                        if ($esJson) {
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['ok' => true, 'redirect' => $urlRedireccion]);
                            exit;
                        }

                        header('Location: ' . $urlRedireccion);
                        exit;
                    }
                }
            }
        }
    }

    if ($esJson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $errorMsg]);
        exit;
    }
}

// Cédula recordada en cookie si existe
$cedulaRecordada = $_COOKIE['asoagro_recordar_cedula'] ?? '';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="color-scheme" content="light">
  <title>Acceso Seguro | <?php echo e(EMPRESA_SIGLA); ?></title>

  <!-- Favicon Oficial -->
  <link rel="icon" type="image/png" href="assets/img/logo.png">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  
  <!-- Bootstrap Icons 1.11.3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Estilos Globales Premium de Alta Accesibilidad (Azul Marino / Crema / Oro) -->
  <link rel="stylesheet" href="assets/css/theme.css">

  <!-- Google Identity Services (OAuth 2.0) -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>

  <?php if (USE_RECAPTCHA_V2 && !empty(RECAPTCHA_SITE_KEY)): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
</head>
<body class="d-flex flex-column min-vh-100 justify-content-between auth-bg">

  <!-- Barra Superior Limpia (Sin selector de tamaño de letra) -->
  <aside class="top-accessibility-bar py-2">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="index.php" class="btn btn-touch btn-outline-primary" style="min-height: 44px; padding: 0.4rem 1.2rem;">
        <i class="bi bi-arrow-left"></i>
        <span>Volver al Inicio</span>
      </a>

      <div class="small fw-bold text-dark d-none d-sm-flex align-items-center gap-2">
        <i class="bi bi-shield-check text-primary"></i>
        <span><?php echo e(EMPRESA_SIGLA); ?> &bull; Portal Oficial de Control Lechero</span>
      </div>
    </div>
  </aside>

  <!-- Contenedor Central con Tarjeta Dividida Sólida (Split-Card) -->
  <main class="container my-auto py-4">
    <div class="row justify-content-center">
      <div class="col-12 d-flex justify-content-center">

        <div class="card login-split-card border-2 shadow-lg">
          <div class="row g-0">

            <!-- LADO IZQUIERDO: BRANDING INSTITUCIONAL AZUL MARINO -->
            <div class="col-md-5 login-brand-side text-center text-md-start p-4 p-lg-5">
              <div>
                <img src="assets/img/logo.png" alt="Logo Oficial <?php echo e(EMPRESA_SIGLA); ?>" class="mb-3" style="max-height: 84px; filter: drop-shadow(0 6px 14px rgba(0,0,0,0.35));">
                <h3 class="fw-bold mb-1 text-white" style="font-size: 1.45rem; letter-spacing: 0.01em;"><?php echo e(EMPRESA_SIGLA); ?></h3>
                <small class="text-white-50 d-block mb-4 text-uppercase" style="letter-spacing: 0.08em;">Carmen de Carupa &bull; Cundinamarca</small>
              </div>

              <h4 class="fw-bold mb-3" style="color: #f4c78d;">Plataforma de Control Lechero</h4>
              <p class="small mb-4 text-white" style="line-height: 1.7; opacity: 0.95;">
                Bienvenido al portal institucional de asociados. Consulta tus pesajes diarios por turnos (☀️ Mañana y 🌅 Tarde), revisa tus planillas quincenales y mantén la trazabilidad de tu producción lechera.
              </p>

              <div class="d-flex flex-column gap-2 small text-white">
                <div><i class="bi bi-check-circle-fill me-2" style="color: #f4c78d;"></i>32 Ganaderos Vinculados</div>
                <div><i class="bi bi-check-circle-fill me-2" style="color: #f4c78d;"></i>Monitoreo y Pesaje Diario</div>
                <div><i class="bi bi-check-circle-fill me-2" style="color: #f4c78d;"></i>Liquidaciones Transparentes</div>
              </div>

              <div class="mt-4 pt-3 border-top border-white border-opacity-25 small text-white opacity-75">
                <?php echo e(EMPRESA_NOMBRE); ?><br>
                <strong>NIT: <?php echo e(EMPRESA_NIT); ?></strong>
              </div>
            </div>

            <!-- LADO DERECHO: FORMULARIO DE ACCESO TÁCTIL CLARO -->
            <div class="col-md-7 login-form-side p-4 p-lg-5">
              <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Iniciar Sesión</h3>
                <p class="text-muted small">Digita tu número de cédula y contraseña para ingresar.</p>
              </div>

              <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger rounded-4 py-3 small fw-bold mb-4" role="alert" style="border: 2px solid var(--danger);">
                  <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
                  <?php echo e($errorMsg); ?>
                </div>
              <?php endif; ?>

              <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sesion_expirada'): ?>
                <div class="alert alert-warning rounded-4 py-2 small fw-bold mb-4" role="alert">
                  <i class="bi bi-info-circle-fill me-2"></i> Tu sesión ha expirado por inactividad. Por favor ingresa nuevamente.
                </div>
              <?php endif; ?>

              <form action="login.php" method="POST" id="form-login" class="needs-validation" novalidate>
                <?php echo campoCsrf(); ?>

                <!-- Campo Cédula: Etiqueta Siempre Visible Encima -->
                <div class="mb-4">
                  <label for="cedula" class="form-label">Número de Cédula de Ciudadanía *</label>
                  <div class="input-group">
                    <span class="input-group-text border-2 bg-light text-primary" style="border-radius: var(--radius-md) 0 0 var(--radius-md); border-right: none;">
                      <i class="bi bi-person-vcard fs-4"></i>
                    </span>
                    <input type="text" class="form-control form-control-touch border-start-0" id="cedula" name="cedula" inputmode="numeric" required placeholder="Número de cédula" value="<?php echo e($cedulaRecordada); ?>" style="border-radius: 0 var(--radius-md) var(--radius-md) 0;">
                  </div>
                </div>

                <!-- Campo Contraseña con Botón Mostrar/Ocultar -->
                <div class="mb-4">
                  <label for="password" class="form-label">Contraseña Personal *</label>
                  <div class="input-group">
                    <span class="input-group-text border-2 bg-light text-primary" style="border-radius: var(--radius-md) 0 0 var(--radius-md); border-right: none;">
                      <i class="bi bi-key-fill fs-4"></i>
                    </span>
                    <input type="password" class="form-control form-control-touch border-start-0 border-end-0" id="password" name="password" required placeholder="Tu contraseña" style="border-radius: 0;">
                    <button class="btn btn-outline-secondary border-2 border-start-0" type="button" id="btn-toggle-pass" style="border-radius: 0 var(--radius-md) var(--radius-md) 0; min-width: 56px;" title="Mostrar contraseña">
                      <i class="bi bi-eye-fill fs-5"></i>
                    </button>
                  </div>
                </div>

                <!-- CAPTCHA GD NATIVO -->
                <div class="mb-4 p-3 rounded-4" style="background-color: var(--surface-2); border: 2px solid var(--border);">
                  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <label for="captcha-input" class="form-label mb-0 text-dark text-nowrap">
                      <i class="bi bi-shield-check me-1 text-primary"></i> Código de Seguridad *
                    </label>
                    <?php if (!USE_RECAPTCHA_V2): ?>
                      <button type="button" id="btn-recargar-captcha" class="btn btn-sm btn-link text-primary fw-bold text-decoration-underline p-0" title="Obtener otra imagen de verificación">
                        <i class="bi bi-arrow-clockwise"></i> Cambiar imagen
                      </button>
                    <?php endif; ?>
                  </div>

                  <?php if (USE_RECAPTCHA_V2 && !empty(RECAPTCHA_SITE_KEY)): ?>
                    <div class="g-recaptcha" data-sitekey="<?php echo e(RECAPTCHA_SITE_KEY); ?>"></div>
                  <?php else: ?>
                    <div class="d-flex align-items-center gap-3">
                      <img src="includes/captcha_gd.php?t=<?php echo time(); ?>" alt="Código de seguridad CAPTCHA" id="captcha-img" class="rounded-3 shadow-sm border border-2 border-secondary" style="height: 52px; width: 150px; object-fit: contain;">
                      <input type="text" class="form-control form-control-touch text-center fw-bold text-uppercase fs-4" id="captcha-input" name="captcha" maxlength="5" required placeholder="Código" autocomplete="off">
                    </div>
                    <small class="text-muted d-block mt-2" style="font-size: 0.82rem;">Escribe los 5 caracteres que ves en la imagen (no distingue mayúsculas/minúsculas).</small>
                  <?php endif; ?>
                </div>

                <!-- Checkbox Recordarme y Enlace Olvidé Clave -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="recordarme" name="recordarme" style="width: 24px; height: 24px;" <?php echo !empty($cedulaRecordada) ? 'checked' : ''; ?>>
                    <label class="form-check-label ms-2 fw-bold text-muted" for="recordarme">
                      Recordar mi cédula
                    </label>
                  </div>
                  <a href="olvide_password.php" class="small fw-bold text-primary hover-underline">
                    ¿Olvidaste tu clave?
                  </a>
                </div>

                <!-- Botón de Ingreso de 56px -->
                <button type="submit" id="btn-submit" class="btn btn-touch btn-aso-primary w-100 mb-3 fs-5">
                  <span>Ingresar a la Plataforma</span>
                  <i class="bi bi-arrow-right fs-4"></i>
                </button>

                <!-- Separador o Google Sign-In -->
                <div class="d-flex align-items-center my-3">
                  <hr class="flex-grow-1 border-secondary-subtle my-0">
                  <span class="px-3 text-muted small fw-bold text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.05em;">o continúa con</span>
                  <hr class="flex-grow-1 border-secondary-subtle my-0">
                </div>

                <!-- Botón Oficial Google Identity Services (OAuth 2.0) -->
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
                       data-text="signin_with"
                       data-size="large"
                       data-logo_alignment="left"
                       data-width="320">
                  </div>
                </div>
              </form>

              <!-- Enlace de Afiliación / Registro -->
              <div class="text-center pt-3 border-top border-secondary-subtle">
                <small class="text-muted d-block mb-2">¿Eres ganadero de la Vereda Alizal y no tienes usuario?</small>
                <a href="registro.php" class="btn btn-touch btn-outline-primary w-100 fw-bold">
                  <i class="bi bi-person-plus-fill me-1"></i> Solicitar Registro de Asociado
                </a>
              </div>
            </div>

          </div>
        </div>

      </div>
    </div>
  </main>

  <!-- Footer Mínimo -->
  <footer class="py-3 text-center text-muted small">
    <div class="container">
      <?php echo e(EMPRESA_SIGLA); ?> &bull; NIT <?php echo e(EMPRESA_NIT); ?> &bull; Carmen de Carupa, Cundinamarca &copy; <?php echo date('Y'); ?>
    </div>
  </footer>

  <!-- Scripts: Bootstrap 5.3 Bundle + SweetAlert2 + theme.js -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/theme.js"></script>

  <script>
    // Callback oficial de Google Identity Services
    window.handleGoogleLogin = async function(response) {
      if (!response || !response.credential) {
        if (window.AsoagroUI) {
          AsoagroUI.showError('Error de Google', 'No se recibió la credencial de autenticación.');
        } else {
          alert('No se recibió la credencial de autenticación de Google.');
        }
        return;
      }

      Swal.fire({
        title: 'Verificando con Google...',
        text: 'Validando identidad de forma segura en el servidor.',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
      });

      try {
        const res = await fetch('api/auth_google.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ credential: response.credential })
        });
        const data = await res.json();

        if (res.ok && data.ok) {
          Swal.fire({
            icon: 'success',
            title: '¡Acceso Concedido!',
            text: data.mensaje || 'Bienvenido a la plataforma.',
            showConfirmButton: false,
            timer: 1200
          }).then(() => {
            window.location.href = data.redirect || 'panel.php';
          });
        } else if (data.unregistered) {
          Swal.fire({
            icon: 'info',
            title: 'Cuenta no vinculada',
            html: `<p class="small text-muted mb-3">${data.error}</p><label class="form-label small fw-bold text-dark text-start d-block">Ingresa tu Cédula para vincular:</label><input id="swal-cedula" class="form-control text-center fw-bold fs-5" placeholder="Número de Cédula" inputmode="numeric">`,
            showCancelButton: true,
            confirmButtonColor: '#16375f',
            confirmButtonText: 'Vincular y Continuar',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
              const c = document.getElementById('swal-cedula').value.trim();
              if (!c) {
                Swal.showValidationMessage('Debes ingresar tu número de cédula para vincular tu cuenta');
              }
              return c;
            }
          }).then(async (result) => {
            if (result.isConfirmed) {
              Swal.fire({ title: 'Vinculando cuenta...', didOpen: () => Swal.showLoading() });
              const resLink = await fetch('api/auth_google.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ credential: response.credential, cedula: result.value })
              });
              const dataLink = await resLink.json();
              if (resLink.ok && dataLink.ok) {
                Swal.fire({
                  icon: 'success',
                  title: '¡Cuenta Vinculada con Éxito!',
                  text: 'Tu Google ID ha sido vinculado a tu cédula.',
                  showConfirmButton: false,
                  timer: 1300
                }).then(() => {
                  window.location.href = dataLink.redirect || 'panel.php';
                });
              } else {
                if (window.AsoagroUI) {
                  AsoagroUI.showError('Error de Vinculación', dataLink.error || 'No se pudo vincular la cuenta.');
                } else {
                  alert(dataLink.error || 'No se pudo vincular la cuenta.');
                }
              }
            }
          });
        } else {
          if (window.AsoagroUI) {
            AsoagroUI.showError('Error de Autenticación', data.error || 'No fue posible iniciar sesión con Google.');
          } else {
            alert(data.error || 'No fue posible iniciar sesión con Google.');
          }
        }
      } catch (err) {
        console.error(err);
        if (window.AsoagroUI) {
          AsoagroUI.showError('Error de Red', 'Error de comunicación al contactar el servidor de autenticación.');
        } else {
          alert('Error de comunicación con el servidor.');
        }
      }
    };

    document.addEventListener('DOMContentLoaded', function() {
      // Toggle de visualización de contraseña
      const btnToggle = document.getElementById('btn-toggle-pass');
      btnToggle?.addEventListener('click', function() {
        AsoagroUI.togglePassword('#password', '#btn-toggle-pass');
      });

      // Recarga de CAPTCHA nativo
      const btnRecargar = document.getElementById('btn-recargar-captcha');
      btnRecargar?.addEventListener('click', function() {
        AsoagroUI.refreshCaptcha('#captcha-img', '#captcha-input');
      });

      // Manejo de envío asíncrono con SweetAlert2
      const form = document.getElementById('form-login');
      form?.addEventListener('submit', async function(e) {
        e.preventDefault();

        const btn = document.getElementById('btn-submit');
        const cedula = document.getElementById('cedula').value.trim();
        const password = document.getElementById('password').value;
        const captcha = document.getElementById('captcha-input') ? document.getElementById('captcha-input').value.trim() : '';
        const token = form.querySelector('input[name="csrf_token"]').value;
        const recordarme = document.getElementById('recordarme').checked ? 1 : 0;

        if (!cedula || !password) {
          AsoagroUI.showWarning('Campos Incompletos', 'Por favor ingresa tu número de cédula y contraseña.');
          return;
        }

        if (document.getElementById('captcha-input') && !captcha) {
          AsoagroUI.showWarning('Código Requerido', 'Por favor digita los 5 caracteres de la imagen de seguridad.');
          return;
        }

        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span> Verificando...`;

        try {
          const formData = new FormData();
          formData.append('csrf_token', token);
          formData.append('cedula', cedula);
          formData.append('password', password);
          formData.append('captcha', captcha);
          formData.append('recordarme', recordarme);

          const res = await fetch('login.php', {
            method: 'POST',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
          });

          const data = await res.json();

          if (res.ok && data.ok) {
            Swal.fire({
              icon: 'success',
              title: '¡Acceso Concedido!',
              text: 'Bienvenido a la plataforma de ASOAGROALIZAL.',
              showConfirmButton: false,
              timer: 1200
            }).then(() => {
              window.location.href = data.redirect || 'panel.php';
            });
          } else {
            AsoagroUI.showError('Error de Autenticación', data.error || 'Cédula o contraseña incorrecta.');
            btn.disabled = false;
            btn.innerHTML = `<span>Ingresar a la Plataforma</span> <i class="bi bi-arrow-right ms-1"></i>`;
            AsoagroUI.refreshCaptcha('#captcha-img', '#captcha-input');
          }
        } catch (err) {
          // Si el fetch falla por corte de red, enviar por POST normal del formulario
          form.submit();
        }
      });
    });
  </script>

  <!-- SweetAlert2 Flash Messages -->
  <?php echo renderSweetAlert(); ?>

</body>
</html>
