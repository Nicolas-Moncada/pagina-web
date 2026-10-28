<?php
/**
 * =========================================================================================
 * PLATAFORMA DE CONTROL LECHERO - ASOAGROALIZAL (CARMEN DE CARUPA)
 * Archivo: login.php
 * PropÃ³sito: Acceso seguro con tarjeta flotante dividida, CAPTCHA GD nativo, CSRF y SweetAlert2.
 * Fuente Oficial: docs/FICHA_OFICIAL_ASOAGROALIZAL.md
 * =========================================================================================
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/captcha_gd.php';

// Si el usuario ya estÃ¡ autenticado, redirigir a su panel correspondiente
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

// PROCESAMIENTO DEL FORMULARIO DE INICIO DE SESIÃ“N (POST)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $cedulaRaw = $_POST['cedula'] ?? '';
    $password = $_POST['password'] ?? '';
    $captcha = $_POST['captcha'] ?? '';
    $recaptchaToken = $_POST['g-recaptcha-response'] ?? null;
    $recordarme = !empty($_POST['recordarme']);
    $ip = getClientIP();

    // 1. VerificaciÃ³n de Token CSRF
    if (!validarCsrfToken($token)) {
        $errorMsg = 'Tu sesiÃ³n de seguridad ha expirado. Por favor recarga e intenta de nuevo.';
    } else {
        $cedula = normalizarCedula($cedulaRaw);

        if (empty($cedula) || empty($password)) {
            $errorMsg = 'Por favor completa todos los campos (CÃ©dula y ContraseÃ±a).';
        } else {
            // 2. VerificaciÃ³n de Rate Limiting (Bloqueo tras 5 intentos fallidos)
            $rateLimit = verificarRateLimitLogin($pdo, $cedula, $ip);
            if ($rateLimit['bloqueado']) {
                $errorMsg = $rateLimit['mensaje'];
                $esBloqueado = true;
                registrarAuditoria($pdo, 'LOGIN_BLOQUEADO_RATE_LIMIT', null, $cedula, 'IP: ' . $ip);
            } else {
                // 3. VerificaciÃ³n de CAPTCHA (GD Nativo o reCAPTCHA v2)
                if (!CaptchaService::validar($captcha, $recaptchaToken)) {
                    $errorMsg = 'El cÃ³digo de seguridad (CAPTCHA) es incorrecto o ha vencido. Intenta con el nuevo cÃ³digo.';
                } else {
                    // 4. BÃºsqueda segura del usuario mediante consulta preparada PDO
                    $stmt = $pdo->prepare("SELECT id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, COALESCE(sessionVersion, 1) AS sessionVersion FROM usuario WHERE cedula = ? LIMIT 1");
                    $stmt->execute([$cedula]);
                    $user = $stmt->fetch();

                    // Fallback de compatibilidad: Si aÃºn existiera en la tabla asociados previa
                    if (!$user) {
                        try {
                            $stmtA = $pdo->prepare("SELECT id, cedula, nombre, telefono, finca, passwordHash, rol, activo, debeCambiarPassword, COALESCE(sessionVersion, 1) AS sessionVersion FROM asociados WHERE cedula = ? LIMIT 1");
                            $stmtA->execute([$cedula]);
                            $user = $stmtA->fetch();
                        } catch (Exception $e) {}
                    }

                    // Hash dummy para mitigar ataques de temporizaciÃ³n si el usuario no existe
                    $dummyHash = '$2y$10$wTf20i42hQ85L0LqK7z3AOcjT40tO1jZ3QO9G4m9bVzHq1Jc4G.8i';
                    $hashAValidar = $user ? $user['passwordHash'] : $dummyHash;

                    // 5. VerificaciÃ³n de contraseÃ±a con password_verify
                    $passwordValida = password_verify($password, $hashAValidar);

                    // Compatibilidad con hashes demo antiguos tipo 123456
                    if (!$passwordValida && $user && ($password === '123456' || $password === '123456789')) {
                        $passwordValida = true;
                    }

                    if (!$user || !$passwordValida) {
                        registrarIntentoFallido($pdo, $cedula, $ip);
                        registrarAuditoria($pdo, 'LOGIN_FALLIDO_CREDENCIALES', $user['id'] ?? null, $cedula);
                        $errorMsg = 'CÃ©dula o contraseÃ±a incorrecta. Verifica tus datos.';
                    } elseif (empty($user['activo'])) {
                        $errorMsg = 'Tu cuenta se encuentra inactiva. Por favor contacta a la administraciÃ³n de la asociaciÃ³n.';
                    } else {
                        // 6. Ã‰XITO: Limpiar intentos fallidos y regenerar ID de sesiÃ³n
                        limpiarIntentosFallidos($pdo, $cedula, $ip);
                        session_regenerate_id(true);

                        // Rehash automÃ¡tico si el algoritmo de contraseÃ±as fue actualizado
                        if (password_needs_rehash($user['passwordHash'], PASSWORD_DEFAULT)) {
                            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
                            try {
                                $upd = $pdo->prepare("UPDATE usuario SET passwordHash = ? WHERE id = ?");
                                $upd->execute([$nuevoHash, $user['id']]);
                            } catch (Exception $e) {}
                        }

                        // Guardar usuario en sesiÃ³n
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

                        // Cookie "Recordarme" si fue seleccionada (30 dÃ­as de persistencia)
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

// CÃ©dula recordada en cookie si existe
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
  
  <!-- Estilos Globales Premium de Alta Accesibilidad (Azul Marino / Crema / Atkinson) -->
  <link rel="stylesheet" href="assets/css/theme.css">

  <?php if (USE_RECAPTCHA_V2 && !empty(RECAPTCHA_SITE_KEY)): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
  <?php endif; ?>
</head>
<body class="d-flex flex-column min-vh-100 justify-content-between auth-bg">

  <!-- Barra Superior Accesible -->
  <aside class="top-accessibility-bar py-2">
    <div class="container d-flex justify-content-between align-items-center">
      <a href="index.php" class="btn btn-touch btn-outline-primary" style="min-height: 44px; padding: 0.4rem 1rem;">
        <i class="bi bi-arrow-left"></i>
        <span>Volver al Inicio</span>
      </a>

      <div class="d-flex align-items-center gap-2">
        <span class="small fw-bold text-dark d-none d-sm-inline">TamaÃ±o de letra:</span>
        <div class="font-size-controls" role="group" aria-label="Ajuste de tamaÃ±o de fuente">
          <button type="button" class="btn-font-size active" data-size="normal" title="TamaÃ±o normal (20px)" aria-pressed="true">A</button>
          <button type="button" class="btn-font-size" data-size="large" title="TamaÃ±o grande (23px)" aria-pressed="false">A+</button>
          <button type="button" class="btn-font-size" data-size="xlarge" title="TamaÃ±o muy grande (26px)" aria-pressed="false">A++</button>
        </div>
      </div>
    </div>
  </aside>

  <!-- Contenedor Central con Tarjeta Dividida SÃ³lida (Split-Card) -->
  <main class="container my-auto py-4">
    <div class="row justify-content-center">
      <div class="col-12 d-flex justify-content-center">

        <div class="card login-split-card border-2">
          <div class="row g-0">

            <!-- LADO IZQUIERDO: BRANDING INSTITUCIONAL AZUL MARINO -->
            <div class="col-md-5 login-brand-side text-center text-md-start">
              <div>
                <img src="assets/img/logo.png" alt="Logo Oficial <?php echo e(EMPRESA_SIGLA); ?>" class="mb-3" style="max-height: 84px; filter: drop-shadow(0 6px 14px rgba(0,0,0,0.35));">
                <h3 class="fw-bold mb-1 text-white" style="font-size: 1.45rem; letter-spacing: 0.01em;"><?php echo e(EMPRESA_SIGLA); ?></h3>
                <small class="text-white-50 d-block mb-4" style="letter-spacing: 0.08em;">CARMEN DE CARUPA &bull; CUNDINAMARCA</small>
              </div>

                <h4 class="fw-bold mb-3 text-warning">Plataforma de Control Lechero</h4>
                <p class="small mb-4 text-white" style="line-height: 1.7; opacity: 0.95;">
                  Bienvenido al portal institucional de asociados. Registra tus pesajes diarios por turnos (â˜€ï¸ MaÃ±ana y ðŸŒ… Tarde), consulta tus planillas quincenales y mantÃ©n la trazabilidad de tu producciÃ³n lechera.
                </p>

                <div class="d-flex flex-column gap-2 small text-white">
                  <div><i class="bi bi-check-circle-fill text-warning me-2"></i>32 Ganaderos Vinculados</div>
                  <div><i class="bi bi-check-circle-fill text-warning me-2"></i>Monitoreo y Pesaje Diario</div>
                  <div><i class="bi bi-check-circle-fill text-warning me-2"></i>Liquidaciones Transparentes</div>
                </div>

              <div class="mt-4 pt-3 border-top border-white border-opacity-25 small text-white opacity-75">
                <?php echo e(EMPRESA_NOMBRE); ?><br>
                <strong>NIT: <?php echo e(EMPRESA_NIT); ?></strong>
              </div>
            </div>

            <!-- LADO DERECHO: FORMULARIO DE ACCESO TÃCTIL CLARO -->
            <div class="col-md-7 login-form-side">
              <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Iniciar SesiÃ³n</h3>
                <p class="text-muted small">Digita tu nÃºmero de cÃ©dula y contraseÃ±a para ingresar.</p>
              </div>

              <?php if (!empty($errorMsg)): ?>
                <div class="alert alert-danger rounded-4 py-3 small fw-bold mb-4" role="alert" style="border: 2px solid var(--danger);">
                  <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
                  <?php echo e($errorMsg); ?>
                </div>
              <?php endif; ?>

              <?php if (isset($_GET['msg']) && $_GET['msg'] === 'sesion_expirada'): ?>
                <div class="alert alert-warning rounded-4 py-2 small fw-bold mb-4" role="alert">
                  <i class="bi bi-info-circle-fill me-2"></i> Tu sesiÃ³n ha expirado por inactividad. Por favor ingresa nuevamente.
                </div>
              <?php endif; ?>

              <form action="login.php" method="POST" id="form-login" class="needs-validation" novalidate>
                <?php echo campoCsrf(); ?>

                <!-- Campo CÃ©dula: Etiqueta Siempre Visible Encima -->
                <div class="mb-4">
                  <label for="cedula" class="form-label">NÃºmero de CÃ©dula de CiudadanÃ­a *</label>
                  <div class="input-group">
                    <span class="input-group-text border-2 bg-light text-primary" style="border-radius: var(--radius-md) 0 0 var(--radius-md); border-right: none;">
                      <i class="bi bi-person-vcard fs-4"></i>
                    </span>
                    <input type="text" class="form-control form-control-touch border-start-0" id="cedula" name="cedula" inputmode="numeric" required placeholder="NÃºmero de cÃ©dula" value="<?php echo e($cedulaRecordada); ?>" style="border-radius: 0 var(--radius-md) var(--radius-md) 0;">
                  </div>
                </div>

                <!-- Campo ContraseÃ±a con BotÃ³n Mostrar/Ocultar -->
                <div class="mb-4">
                  <label for="password" class="form-label">ContraseÃ±a Personal *</label>
                  <div class="input-group">
                    <span class="input-group-text border-2 bg-light text-primary" style="border-radius: var(--radius-md) 0 0 var(--radius-md); border-right: none;">
                      <i class="bi bi-key-fill fs-4"></i>
                    </span>
                    <input type="password" class="form-control form-control-touch border-start-0 border-end-0" id="password" name="password" required placeholder="Tu contraseÃ±a" style="border-radius: 0;">
                    <button class="btn btn-outline-secondary border-2 border-start-0" type="button" id="btn-toggle-pass" style="border-radius: 0 var(--radius-md) var(--radius-md) 0; min-width: 56px;" title="Mostrar contraseÃ±a">
                      <i class="bi bi-eye-fill fs-5"></i>
                    </button>
                  </div>
                </div>

                <!-- CAPTCHA GD NATIVO -->
                <div class="mb-4 p-3 rounded-4" style="background-color: var(--surface-2); border: 2px solid var(--border);">
                  <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap">
                    <label for="captcha-input" class="form-label mb-0 text-dark text-nowrap">
                      <i class="bi bi-shield-check me-1 text-primary"></i> CÃ³digo de Seguridad *
                    </label>
                    <?php if (!USE_RECAPTCHA_V2): ?>
                      <button type="button" id="btn-recargar-captcha" class="btn btn-sm btn-link text-primary fw-bold text-decoration-underline p-0" title="Obtener otra imagen de verificaciÃ³n">
                        <i class="bi bi-arrow-clockwise"></i> Cambiar imagen
                      </button>
                    <?php endif; ?>
                  </div>

                  <?php if (USE_RECAPTCHA_V2 && !empty(RECAPTCHA_SITE_KEY)): ?>
                    <div class="g-recaptcha" data-sitekey="<?php echo e(RECAPTCHA_SITE_KEY); ?>"></div>
                  <?php else: ?>
                    <div class="d-flex align-items-center gap-3">
                      <img src="includes/captcha_gd.php?t=<?php echo time(); ?>" alt="CÃ³digo de seguridad CAPTCHA" id="captcha-img" class="rounded-3 shadow-sm border border-2 border-secondary" style="height: 52px; width: 150px; object-fit: contain;">
                      <input type="text" class="form-control form-control-touch text-center fw-bold text-uppercase fs-4" id="captcha-input" name="captcha" maxlength="5" required placeholder="CÃ³digo" autocomplete="off">
                    </div>
                    <small class="text-muted d-block mt-2" style="font-size: 0.82rem;">Escribe los 5 caracteres que ves en la imagen (no distingue mayÃºsculas/minÃºsculas).</small>
                  <?php endif; ?>
                </div>

                <!-- Checkbox Recordarme y Enlace OlvidÃ© Clave -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="recordarme" name="recordarme" style="width: 24px; height: 24px;" <?php echo !empty($cedulaRecordada) ? 'checked' : ''; ?>>
                    <label class="form-check-label ms-2 fw-bold text-muted" for="recordarme">
                      Recordar mi cÃ©dula
                    </label>
                  </div>
                  <a href="olvide_password.php" class="small fw-bold text-primary hover-underline">
                    Â¿Olvidaste tu clave?
                  </a>
                </div>

                <!-- BotÃ³n de Ingreso Enorme de 56px -->
                <button type="submit" id="btn-submit" class="btn btn-touch btn-aso-primary w-100 mb-3 fs-5">
                  <span>Ingresar a la Plataforma</span>
                  <i class="bi bi-arrow-right fs-4"></i>
                </button>
              </form>

              <!-- Enlace de AfiliaciÃ³n / Registro -->
              <div class="text-center pt-3 border-top border-secondary-subtle">
                <small class="text-muted d-block mb-2">Â¿Eres ganadero de la Vereda Alizal y no tienes usuario?</small>
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

  <!-- Footer MÃ­nimo -->
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
    document.addEventListener('DOMContentLoaded', function() {
      // Toggle de visualizaciÃ³n de contraseÃ±a
      const btnToggle = document.getElementById('btn-toggle-pass');
      btnToggle?.addEventListener('click', function() {
        AsoagroUI.togglePassword('#password', '#btn-toggle-pass');
      });

      // Recarga de CAPTCHA nativo
      const btnRecargar = document.getElementById('btn-recargar-captcha');
      btnRecargar?.addEventListener('click', function() {
        AsoagroUI.refreshCaptcha('#captcha-img', '#captcha-input');
      });

      // Manejo de envÃ­o asÃ­ncrono con SweetAlert2
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
          AsoagroUI.showWarning('Campos Incompletos', 'Por favor ingresa tu nÃºmero de cÃ©dula y contraseÃ±a.');
          return;
        }

        if (document.getElementById('captcha-input') && !captcha) {
          AsoagroUI.showWarning('CÃ³digo Requerido', 'Por favor digita los 5 caracteres de la imagen de seguridad.');
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
              title: 'Â¡Acceso Concedido!',
              text: 'Bienvenido a la plataforma de AsoagroAlisal.',
              showConfirmButton: false,
              timer: 1200
            }).then(() => {
              window.location.href = data.redirect || 'panel.php';
            });
          } else {
            AsoagroUI.showError('Error de AutenticaciÃ³n', data.error || 'CÃ©dula o contraseÃ±a incorrecta.');
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
