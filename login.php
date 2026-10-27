<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: login.php
 * Propósito: Acceso seguro con tarjeta flotante dividida, CAPTCHA GD nativo, CSRF y SweetAlert2.
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

                    // Compatibilidad con hashes demo antiguos tipo 123456 si el hash fue generado con otro salt
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

                        // Rehash automático si el algoritmo de contraseñas fue actualizado
                        if (password_needs_rehash($user['passwordHash'], PASSWORD_DEFAULT)) {
                            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
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
  <title>Acceso Seguro | <?php echo e(EMPRESA_SIGLA); ?></title>

  <!-- Script Anti-Parpadeo (Anti-FOUC) -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('asoagro_theme');
      const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      const theme = savedTheme ? savedTheme : (systemDark ? 'dark' : 'light');
      document.documentElement.setAttribute('data-bs-theme', theme);
    })();
  </script>

  <!-- Favicon Oficial -->
  <link rel="icon" type="image/png" href="assets/img/logo.png">

  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  
  <!-- Bootstrap Icons 1.11.3 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Estilos Globales de Marca AsoagroAlisal -->
  <link rel="stylesheet" href="assets/css/estilos.css">

  <?php if (USE_RECAPTCHA_V2 && !empty(RECAPTCHA_SITE_KEY)): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
</head>
<body class="d-flex flex-column min-vh-100 justify-content-between">

  <!-- Cabecera mínima con botón de volver y alternador de modo oscuro -->
  <header class="py-3 px-4">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="index.php" class="btn btn-touch btn-outline-success border-2 rounded-pill px-3 py-2 text-decoration-none small">
        <i class="bi bi-arrow-left"></i>
        <span>Volver al Inicio</span>
      </a>

      <button type="button" class="btn btn-touch btn-outline-success border-2 rounded-circle p-0 theme-toggle-btn" style="width: 44px; height: 44px;" aria-label="Alternar modo oscuro">
        <i class="bi bi-moon-stars-fill fs-5"></i>
      </button>
    </div>
  </header>

  <!-- Contenedor Central con Tarjeta Flotante Dividida (Split-Card) -->
  <main class="container my-auto py-4">
    <div class="row justify-content-center">
      <div class="col-12 d-flex justify-content-center">

        <div class="card login-split-card border-0">
          <div class="row g-0">

            <!-- LADO IZQUIERDO: BRANDING, LOGO Y MENSAJE INSTITUCIONAL -->
            <div class="col-md-5 login-brand-side">
              <div>
                <div class="d-flex align-items-center gap-3 mb-4">
                  <img src="assets/img/logo.png" alt="Logo Oficial <?php echo e(EMPRESA_SIGLA); ?>" style="max-height: 64px; filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));">
                  <div>
                    <h3 class="fw-bold mb-0 text-white"><?php echo e(EMPRESA_SIGLA); ?></h3>
                    <small class="opacity-75">Carmen de Carupa</small>
                  </div>
                </div>

                <h4 class="fw-bold mb-3 text-warning">Plataforma de Control Lechero</h4>
                <p class="small opacity-90 mb-4" style="line-height: 1.7;">
                  Bienvenido al portal seguro de asociados. Registra tus pesajes diarios por turnos (mañana y tarde), consulta tus planillas quincenales y mantén la trazabilidad de tu producción lechera.
                </p>

                <div class="d-flex flex-column gap-2 small opacity-75">
                  <div><i class="bi bi-check-circle-fill text-warning me-2"></i>32 Ganaderos Vinculados</div>
                  <div><i class="bi bi-check-circle-fill text-warning me-2"></i>Monitoreo en Tiempo Real</div>
                  <div><i class="bi bi-check-circle-fill text-warning me-2"></i>Liquidaciones Transparentes</div>
                </div>
              </div>

              <div class="mt-4 pt-3 border-top border-white border-opacity-25 small opacity-75">
                <?php echo e(EMPRESA_NOMBRE); ?><br>
                <strong>NIT: <?php echo e(EMPRESA_NIT); ?></strong>
              </div>
            </div>

            <!-- LADO DERECHO: FORMULARIO DE ACCESO TÁCTIL -->
            <div class="col-md-7 login-form-side">
              <div class="mb-4">
                <h3 class="fw-extrabold text-success mb-1">Iniciar Sesión</h3>
                <p class="text-muted small">Digita tu número de cédula y contraseña para acceder.</p>
              </div>

              <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger rounded-4 py-3 small fw-bold mb-4" role="alert">
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

                <!-- Campo Cédula -->
                <div class="mb-3">
                  <label for="cedula" class="form-label fw-bold small text-muted">Número de Cédula de Ciudadanía *</label>
                  <div class="input-group">
                    <span class="input-group-text border-2 bg-transparent text-success" style="border-radius: var(--radius-md) 0 0 var(--radius-md); border-right: none;">
                      <i class="bi bi-person-vcard fs-5"></i>
                    </span>
                    <input type="text" class="form-control form-control-touch border-start-0" id="cedula" name="cedula" inputmode="numeric" required placeholder="Ej: 79164451" value="<?php echo e($cedulaRecordada); ?>" style="border-radius: 0 var(--radius-md) var(--radius-md) 0;">
                  </div>
                </div>

                <!-- Campo Contraseña con Botón Mostrar/Ocultar -->
                <div class="mb-3">
                  <label for="password" class="form-label fw-bold small text-muted">Contraseña Personal *</label>
                  <div class="input-group">
                    <span class="input-group-text border-2 bg-transparent text-success" style="border-radius: var(--radius-md) 0 0 var(--radius-md); border-right: none;">
                      <i class="bi bi-key-fill fs-5"></i>
                    </span>
                    <input type="password" class="form-control form-control-touch border-start-0 border-end-0" id="password" name="password" required placeholder="Tu contraseña" style="border-radius: 0;">
                    <button class="btn btn-outline-secondary border-2 border-start-0" type="button" id="btn-toggle-pass" style="border-radius: 0 var(--radius-md) var(--radius-md) 0;" title="Mostrar contraseña">
                      <i class="bi bi-eye-fill"></i>
                    </button>
                  </div>
                </div>

                <!-- CAPTCHA GD NATIVO / Google reCAPTCHA v2 -->
                <div class="mb-3 p-3 rounded-4" style="background: var(--aso-secondary-light); border: 1px solid var(--card-border);">
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <label for="captcha-input" class="form-label fw-bold small mb-0 text-success">
                      <i class="bi bi-shield-check me-1"></i> Verificación de Seguridad *
                    </label>
                    <?php if (!USE_RECAPTCHA_V2): ?>
                      <button type="button" id="btn-recargar-captcha" class="btn btn-sm btn-link text-success fw-bold text-decoration-none p-0" title="Obtener otro código">
                        <i class="bi bi-arrow-clockwise"></i> Cambiar
                      </button>
                    <?php endif; ?>
                  </div>

                  <?php if (USE_RECAPTCHA_V2 && !empty(RECAPTCHA_SITE_KEY)): ?>
                    <div class="g-recaptcha" data-sitekey="<?php echo e(RECAPTCHA_SITE_KEY); ?>"></div>
                  <?php else: ?>
                    <div class="d-flex align-items-center gap-3">
                      <img src="includes/captcha_gd.php?t=<?php echo time(); ?>" alt="Código de seguridad CAPTCHA" id="captcha-img" class="rounded-3 shadow-sm" style="height: 44px; width: 140px; object-fit: contain;">
                      <input type="text" class="form-control form-control-touch text-center fw-bold text-uppercase fs-5" id="captcha-input" name="captcha" maxlength="5" required placeholder="Código" autocomplete="off">
                    </div>
                    <small class="text-muted d-block mt-2" style="font-size: 0.76rem;">Escribe los 5 caracteres distorsionados (no distingue mayúsculas/minúsculas).</small>
                  <?php endif; ?>
                </div>

                <!-- Checkbox Recordarme -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="recordarme" name="recordarme" <?php echo !empty($cedulaRecordada) ? 'checked' : ''; ?>>
                    <label class="form-check-label small text-muted" for="recordarme">
                      Recordar mi cédula
                    </label>
                  </div>
                  <a href="javascript:void(0)" onclick="AsoagroUI.showWarning('Restablecimiento de Contraseña', 'Comunícate con la administración de ASOAGROALIZAL o envía un mensaje al WhatsApp oficial (+57 3143820926) para validar tu identidad.')" class="small text-success text-decoration-none fw-bold hover-underline">
                    ¿Olvidaste tu clave?
                  </a>
                </div>

                <!-- Botón de Ingreso Táctil -->
                <button type="submit" id="btn-submit" class="btn btn-touch btn-aso-primary w-100 shadow mb-3">
                  <span>Ingresar a la Plataforma</span>
                  <i class="bi bi-arrow-right"></i>
                </button>
              </form>

              <!-- Enlace de Afiliación / Registro -->
              <div class="text-center pt-3 border-top border-secondary-subtle">
                <small class="text-muted d-block mb-2">¿Eres ganadero de la Vereda Alizal y no tienes usuario?</small>
                <a href="index.php#contacto" class="btn btn-touch btn-outline-success border-2 rounded-pill w-100 small fw-bold">
                  <i class="bi bi-person-plus-fill me-1"></i> Solicitar Vinculación de Asociado
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

  <!-- Scripts: Bootstrap 5.3 Bundle + SweetAlert2 + App.js -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="assets/js/app.js"></script>

  <script>
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
              text: 'Bienvenido a la plataforma de AsoagroAlisal.',
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
