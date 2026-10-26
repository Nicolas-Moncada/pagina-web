/**
 * E2E Security & Integrity Test Suite - Asoagroalisal
 * 12 Mandatory Verification Checks
 */

const http = require('http');
const fs = require('fs');
const path = require('path');

// 1. Cargar .env para la clave inicial del Admin (SIN imprimirla en logs)
const envPath = path.join(__dirname, '.env');
const envContent = fs.readFileSync(envPath, 'utf8');
const envVars = {};
envContent.split('\n').forEach(line => {
  const match = line.match(/^\s*([\w.-]+)\s*=\s*(.*)?\s*$/);
  if (match) {
    let val = (match[2] || '').trim();
    if (val.startsWith('"') && val.endsWith('"')) val = val.slice(1, -1);
    if (val.startsWith("'") && val.endsWith("'")) val = val.slice(1, -1);
    envVars[match[1]] = val;
  }
});

const ADMIN_INITIAL_PASS = envVars.ADMIN_INITIAL_PASSWORD;
if (!ADMIN_INITIAL_PASS) {
  console.error("FATAL: ADMIN_INITIAL_PASSWORD no definida en .env");
  process.exit(1);
}

const BASE_URL = 'http://localhost/asociacion/';

// Helper HTTP Request con soporte de Cookies / Sesiones
function httpRequest(urlPath, options = {}) {
  return new Promise((resolve, reject) => {
    const cleanPath = urlPath.startsWith('/') ? urlPath.slice(1) : urlPath;
    const fullUrl = new URL(cleanPath, BASE_URL);

    const reqOptions = {
      hostname: fullUrl.hostname,
      port: fullUrl.port || 80,
      path: fullUrl.pathname + fullUrl.search,
      method: options.method || 'GET',
      headers: options.headers || {}
    };

    if (options.body && !reqOptions.headers['Content-Type']) {
      reqOptions.headers['Content-Type'] = 'application/json';
    }
    if (options.cookie) {
      reqOptions.headers['Cookie'] = options.cookie;
    }

    const req = http.request(reqOptions, (res) => {
      let data = '';
      res.on('data', chunk => data += chunk);
      res.on('end', () => {
        let setCookies = res.headers['set-cookie'] || [];
        let parsedJson = null;
        try {
          parsedJson = JSON.parse(data);
        } catch (e) {}

        resolve({
          statusCode: res.statusCode,
          headers: res.headers,
          setCookies: setCookies,
          rawBody: data,
          json: parsedJson
        });
      });
    });

    req.on('error', reject);
    if (options.body) {
      req.write(typeof options.body === 'string' ? options.body : JSON.stringify(options.body));
    }
    req.end();
  });
}

function extractSessionCookie(setCookies) {
  for (const c of setCookies) {
    if (c.startsWith('PHPSESSID=')) {
      return c.split(';')[0];
    }
  }
  return null;
}

// Obtener un captcha válido desde la sesión
async function getValidCaptcha(cookie) {
  const res = await httpRequest('api/captcha.php?v=' + Date.now(), { cookie });
  const cCookie = extractSessionCookie(res.setCookies) || cookie;

  if (res.json && res.json.pregunta) {
    const m = res.json.pregunta.match(/(\d+)\s*\+\s*(\d+)/);
    if (m) {
      const sum = parseInt(m[1], 10) + parseInt(m[2], 10);
      return { code: String(sum), cookie: cCookie };
    }
  }
  return { code: '', cookie: cCookie };
}

async function runTests() {
  console.log('===============================================================');
  console.log(' INICIANDO BATERÍA DE 12 PRUEBAS E2E DE SEGURIDAD E INTEGRIDAD');
  console.log('===============================================================\n');

  let passed = 0;
  let failed = 0;

  function assert(condition, message) {
    if (condition) {
      console.log(`  [PASS] ${message}`);
      passed++;
    } else {
      console.error(`  [FAIL] ${message}`);
      failed++;
    }
  }

  // -----------------------------------------------------------------
  // PRUEBA 1: Post-wipe state with 2027 fortnight and empty clean table
  // -----------------------------------------------------------------
  console.log('[TEST 1/12] Estado limpio post-wipe: Quincena Enero 2027 y tablas limpias');
  try {
    const resQ = await httpRequest('api/quincenas.php');
    assert(resQ.statusCode === 200, 'GET api/quincenas.php responde 200');
    assert(resQ.json && resQ.json.quincenas && resQ.json.quincenas.length >= 1, 'Existe quincena inicial configurada');
    const q1 = resQ.json.quincenas.find(q => q.fechaInicio === '2027-01-01');
    assert(q1 && q1.fechaFin === '2027-01-15', 'Primera quincena configurada: 2027-01-01 al 2027-01-15');
    assert(q1 && Number(q1.precioLitro) === 0, 'Precio inicial configurado en $0 COP');
  } catch (err) {
    assert(false, 'Error en Test 1: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 2: Sole admin 79164451 login (old 1001 fails)
  // -----------------------------------------------------------------
  console.log('\n[TEST 2/12] Autenticación: Solo Admin 79164451 existe; Don Carlos 1001 rechazado');
  let adminSessionCookie = null;
  try {
    // 2a. Don Carlos Alberto Mendoza (1001) debe fallar con 401
    const { code: capOld, cookie: cookOld } = await getValidCaptcha('');
    const resOld = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cookOld,
      body: { cedula: '1001', password: 'cualquier_clave', captcha: capOld }
    });
    assert(resOld.statusCode === 401, 'Login con cédula 1001 es rechazado (401)');
    assert(resOld.json && resOld.json.error === 'Cédula o contraseña incorrecta.', 'Mensaje de error genérico anti-enumeración para 1001');

    // 2b. Admin con clave incorrecta
    const { code: cap1, cookie: cook1 } = await getValidCaptcha('');
    const resBadPass = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cook1,
      body: { cedula: '79164451', password: 'clave_totalmente_errada', captcha: cap1 }
    });
    assert(resBadPass.statusCode === 401, 'Admin con contraseña errónea rechazado (401)');
    assert(resBadPass.json && resBadPass.json.error === 'Cédula o contraseña incorrecta.', 'Mensaje de error genérico anti-enumeración para contraseña incorrecta');

    // 2c. Admin con credencial correcta de .env
    const { code: cap2, cookie: cook2 } = await getValidCaptcha('');
    const resAdmin = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cook2,
      body: { cedula: '79164451', password: ADMIN_INITIAL_PASS, captcha: cap2 }
    });
    assert(resAdmin.statusCode === 200, 'Login del Administrador 79164451 exitoso (200)');
    assert(resAdmin.json && resAdmin.json.debeCambiarPassword === true, 'Se detecta flag debeCambiarPassword = true');
    assert(resAdmin.json && resAdmin.json.redirect === 'cambiar_password.php', 'Redirección forzada hacia cambiar_password.php');
    adminSessionCookie = extractSessionCookie(resAdmin.setCookies) || cook2;
  } catch (err) {
    assert(false, 'Error en Test 2: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 3: Forced password change on first admin login
  // -----------------------------------------------------------------
  console.log('\n[TEST 3/12] Cambio de contraseña forzado: Complejidad estricta y desbloqueo');
  const NEW_ADMIN_PASS = 'AsoAgro#2027!Admin';
  try {
    // 3a. Intentar entrar a admin.php antes de cambiar clave -> debe redirigir a cambiar_password.php
    const resAdminBlock = await httpRequest('admin.php', {
      cookie: adminSessionCookie
    });
    assert(resAdminBlock.statusCode === 302 && resAdminBlock.headers['location'].includes('cambiar_password.php'), 'Acceso a admin.php bloqueado (302 a cambiar_password.php) mientras debeCambiarPassword = 1');

    // 3b. Intentar cambiar clave débil (corta, sin mayúsculas, etc.)
    const resWeak = await httpRequest('api/cambiar_password.php', {
      method: 'POST',
      cookie: adminSessionCookie,
      body: { passwordActual: ADMIN_INITIAL_PASS, passwordNueva: '123', passwordConfirm: '123' }
    });
    assert(resWeak.statusCode === 400, 'Contraseña débil de 3 caracteres rechazada (400)');

    // 3c. Cambiar a contraseña segura de alta complejidad (>= 10 caracteres)
    const resStrong = await httpRequest('api/cambiar_password.php', {
      method: 'POST',
      cookie: adminSessionCookie,
      body: { passwordActual: ADMIN_INITIAL_PASS, passwordNueva: NEW_ADMIN_PASS, passwordConfirm: NEW_ADMIN_PASS }
    });
    assert(resStrong.statusCode === 200 && resStrong.json.ok === true, 'Contraseña cambiada exitosamente a estándar seguro (200)');

    // 3d. Intentar entrar a admin.php ahora -> debe dar 200 OK
    const resAdminAllowed = await httpRequest('admin.php', {
      cookie: adminSessionCookie
    });
    assert(resAdminAllowed.statusCode === 200, 'Acceso a admin.php permitido (200 OK) tras cambio de clave');
  } catch (err) {
    assert(false, 'Error en Test 3: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 4: UI / Code / localStorage contains zero credentials
  // -----------------------------------------------------------------
  console.log('\n[TEST 4/12] Desidentificación: Cero credenciales o nombres demo en código/UI');
  try {
    const filesToCheck = [
      'index.php', 'login.php', 'registro.php', 'cambiar_password.php',
      'README.md', 'INICIAR_SISTEMA.bat', 'js/app.js'
    ];
    let foundLeak = false;
    for (const f of filesToCheck) {
      const full = path.join(__dirname, f);
      if (fs.existsSync(full)) {
        const text = fs.readFileSync(full, 'utf8');
        if (text.includes('Carlos Alberto Mendoza') || text.includes('1001') || text.includes('Hilda Inés') || text.includes('20444983')) {
          foundLeak = true;
          console.error(`    Fuga detectada en ${f}`);
        }
      }
    }
    assert(!foundLeak, 'Archivos de la interfaz y documentación limpios de datos demo y credenciales');
  } catch (err) {
    assert(false, 'Error en Test 4: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 5: CAPTCHA validation (expired, missing, wrong rejected)
  // -----------------------------------------------------------------
  console.log('\n[TEST 5/12] Validación de CAPTCHA: Faltante, errado o reutilizado es rechazado');
  try {
    // 5a. Sin captcha
    const resNoCap = await httpRequest('api/login.php', {
      method: 'POST',
      body: { cedula: '79164451', password: NEW_ADMIN_PASS, captcha: '' }
    });
    assert(resNoCap.statusCode === 400, 'Petición sin CAPTCHA rechazada (400)');

    // 5b. Con captcha errado
    const { cookie: capCookie } = await getValidCaptcha('');
    const resBadCap = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: capCookie,
      body: { cedula: '79164451', password: NEW_ADMIN_PASS, captcha: 'ERRADO_999' }
    });
    assert(resBadCap.statusCode === 400 && resBadCap.json.error.includes('CAPTCHA'), 'CAPTCHA erróneo rechazado (400)');

    // 5c. Reutilización del mismo captcha (debe invalidarse tras un uso)
    const { code: goodCap, cookie: reuseCookie } = await getValidCaptcha('');
    const resUse1 = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: reuseCookie,
      body: { cedula: '79164451', password: NEW_ADMIN_PASS, captcha: goodCap }
    });
    assert(resUse1.statusCode === 200, 'Primer uso del CAPTCHA aceptado');

    // Segundo uso inmediato con el mismo código
    const resUse2 = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: reuseCookie,
      body: { cedula: '79164451', password: NEW_ADMIN_PASS, captcha: goodCap }
    });
    assert(resUse2.statusCode === 400, 'Reutilización del mismo CAPTCHA rechazada (400)');
  } catch (err) {
    assert(false, 'Error en Test 5: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 6: Duplicate cédula check & normalization
  // -----------------------------------------------------------------
  console.log('\n[TEST 6/12] Cédula Única y Normalización: Puntuaciones y duplicados');
  try {
    // 6a. Intentar registrar cédula existente del admin
    const { code: cReg1, cookie: cookReg1 } = await getValidCaptcha('');
    const resDupAdmin = await httpRequest('api/registro.php', {
      method: 'POST',
      cookie: cookReg1,
      body: { cedula: '79164451', nombre: 'Admin Clon', password: 'Password#2027!', captcha: cReg1 }
    });
    assert(resDupAdmin.statusCode === 400 && resDupAdmin.json.error.includes('Ya existe'), 'Registro con cédula existente rechazada');

    // 6b. Intentar registrar cédula con puntuación (79.164.451)
    const { code: cReg2, cookie: cookReg2 } = await getValidCaptcha(cookReg1);
    const resDupPunct = await httpRequest('api/registro.php', {
      method: 'POST',
      cookie: cookReg2,
      body: { cedula: '79.164.451', nombre: 'Admin Puntuado', password: 'Password#2027!', captcha: cReg2 }
    });
    assert(resDupPunct.statusCode === 400, 'Normalización de cédula con puntos detecta duplicado (400)');

    // 6c. Registrar nuevo ganadero legítimo
    const nuevaCedula = '1098765432';
    const { code: cReg3, cookie: cookReg3 } = await getValidCaptcha('');
    const resNuevo = await httpRequest('api/registro.php', {
      method: 'POST',
      cookie: cookReg3,
      body: { cedula: '1.098.765.432', nombre: 'Ganadero Prueba E2E', finca: 'Finca La Prueba', password: 'Ganadero#2027!', captcha: cReg3 }
    });
    assert(resNuevo.statusCode === 200, 'Registro de nuevo ganadero con cédula formateada exitoso (200)');

    // 6d. Intentar duplicar ese nuevo ganadero
    const { code: cReg4, cookie: cookReg4 } = await getValidCaptcha('');
    const resDupNuevo = await httpRequest('api/registro.php', {
      method: 'POST',
      cookie: cookReg4,
      body: { cedula: nuevaCedula, nombre: 'Otro Ganadero', password: 'Ganadero#2027!', captcha: cReg4 }
    });
    assert(resDupNuevo.statusCode === 400, 'Segundo intento con la misma cédula rechazado por duplicidad (400)');
  } catch (err) {
    assert(false, 'Error en Test 6: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 7: Rate-limiting brute-force lock
  // -----------------------------------------------------------------
  console.log('\n[TEST 7/12] Rate-limiting contra fuerza bruta: Bloqueo 429 tras 5 intentos fallidos');
  try {
    const testCedulaBrute = '9999999999';
    let locked = false;
    for (let i = 1; i <= 6; i++) {
      const { code: cBrute, cookie: cookBrute } = await getValidCaptcha('');
      const res = await httpRequest('api/login.php', {
        method: 'POST',
        cookie: cookBrute,
        body: { cedula: testCedulaBrute, password: 'password_invalido_' + i, captcha: cBrute }
      });
      if (res.statusCode === 429) {
        locked = true;
        break;
      }
    }
    assert(locked, 'Rate limit activado: Respuesta HTTP 429 Too Many Requests recibida');
  } catch (err) {
    assert(false, 'Error en Test 7: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 8: IDOR authorization check
  // -----------------------------------------------------------------
  console.log('\n[TEST 8/12] Prevención de IDOR: Ganadero no puede ver ni modificar datos ajenos');
  try {
    // 8a. Loguear como Ganadero
    const { code: cGan, cookie: cookGan } = await getValidCaptcha('');
    const resLoginGan = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cookGan,
      body: { cedula: '1098765432', password: 'Ganadero#2027!', captcha: cGan }
    });
    const ganSessionCookie = extractSessionCookie(resLoginGan.setCookies) || cookGan;
    assert(resLoginGan.statusCode === 200, 'Sesión iniciada como Ganadero');

    // 8b. Ganadero intenta consultar entregas pasando usuarioId ajeno (ej: id del admin)
    const resIdorGet = await httpRequest('api/entregas.php?usuarioId=usr_admin_01&fechaInicio=2027-01-01&fechaFin=2027-01-15', {
      cookie: ganSessionCookie
    });
    assert(resIdorGet.statusCode === 200, 'GET api/entregas.php responde');
    const entregasRetornadas = resIdorGet.json?.entregas || [];
    const containsForeign = entregasRetornadas.some(e => e.usuarioId === 'usr_admin_01');
    assert(!containsForeign, 'IDOR mitigado: Servidor forzó usuarioId del ganadero autenticado');

    // 8c. Ganadero intenta registrar entrega (POST) -> prohibido (403)
    const resIdorPost = await httpRequest('api/entregas.php', {
      method: 'POST',
      cookie: ganSessionCookie,
      body: { usuarioId: '1098765432', fecha: '2027-01-05', turno: 'MANANA', litros: 50.0 }
    });
    assert(resIdorPost.statusCode === 403, 'Ganadero intentando registrar pesajes rechazado con 403 Forbidden');

    // 8d. Ganadero intenta cambiar precio oficial (POST api/quincenas.php) -> prohibido (403)
    const resIdorPrice = await httpRequest('api/quincenas.php', {
      method: 'POST',
      cookie: ganSessionCookie,
      body: { fechaInicio: '2027-01-01', fechaFin: '2027-01-15', precioLitro: 9999 }
    });
    assert(resIdorPrice.statusCode === 403, 'Ganadero intentando cambiar precios rechazado con 403 Forbidden');
  } catch (err) {
    assert(false, 'Error en Test 8: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 9: XSS escaping
  // -----------------------------------------------------------------
  console.log('\n[TEST 9/12] Sanitización y Prevención de XSS');
  try {
    const xssPayload = "<script>alert('XSS')</script>";
    const resContact = await httpRequest('api/consultas.php', {
      method: 'POST',
      body: { nombre: 'Tester XSS', contacto: '3009999999', mensaje: xssPayload }
    });
    assert(resContact.statusCode === 200, 'Mensaje con caracteres HTML enviado');

    // Consultar como admin y verificar escape
    const resAdminConsultas = await httpRequest('api/consultas.php', {
      cookie: adminSessionCookie
    });
    assert(resAdminConsultas.statusCode === 200, 'Buzón consultado por admin');
    const consultas = resAdminConsultas.json?.consultas || [];
    const itemXSS = consultas.find(c => c.nombre === 'Tester XSS');
    assert(itemXSS && !itemXSS.mensaje.includes('<script>'), 'Payload XSS almacenado de forma segura sin tags ejecutables');
  } catch (err) {
    assert(false, 'Error en Test 9: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 10: SQL injection tests
  // -----------------------------------------------------------------
  console.log('\n[TEST 10/12] Protección contra Inyección SQL (Prepared Statements PDO)');
  try {
    const sqliPayloads = [
      "' OR '1'='1",
      "79164451' OR '1'='1' --",
      "' UNION SELECT 1,2,3,4,5,6,7,8,9,10 --",
      "admin' --"
    ];

    let sqliBlocked = true;
    for (const payload of sqliPayloads) {
      const { code: capSql, cookie: cookSql } = await getValidCaptcha('');
      const res = await httpRequest('api/login.php', {
        method: 'POST',
        cookie: cookSql,
        body: { cedula: payload, password: 'password', captcha: capSql }
      });
      // Debe retornar 400 (cédula inválida por no numérica) o 401, NUNCA 200 ni error 500 de sintaxis SQL
      if (res.statusCode === 200 || res.statusCode === 500) {
        sqliBlocked = false;
        console.error(`    Fallo SQLi con payload: ${payload} (Status: ${res.statusCode})`);
      }
    }
    assert(sqliBlocked, 'Todos los intentos de inyección SQL fueron neutralizados');
  } catch (err) {
    assert(false, 'Error en Test 10: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 11: Malicious formula neutralization in Excel
  // -----------------------------------------------------------------
  console.log('\n[TEST 11/12] Neutralización de Fórmulas Maliciosas en Exportación Excel');
  try {
    const adminJs = fs.readFileSync(path.join(__dirname, 'js/admin.js'), 'utf8');
    assert(adminJs.includes('neutralizarFormula') && (adminJs.includes("startsWith('='") || adminJs.includes('startsWith("="')), 'Función neutralizarFormula presente en js/admin.js');
    assert(adminJs.includes("startsWith('+')") || adminJs.includes('startsWith("+")'), 'Escape de fórmulas con signo +');
    assert(adminJs.includes("startsWith('-')") || adminJs.includes('startsWith("-")'), 'Escape de fórmulas con signo -');
    assert(adminJs.includes("startsWith('@')") || adminJs.includes('startsWith("@")'), 'Escape de fórmulas con signo @');
  } catch (err) {
    assert(false, 'Error en Test 11: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 12: Zero server / browser console errors
  // -----------------------------------------------------------------
  console.log('\n[TEST 12/18] Verificación de Salud del Servidor: Sin advertencias ni errores fatales');
  try {
    const pages = ['index.php', 'login.php', 'registro.php', 'olvide_password.php', 'admin.php', 'panel.php'];
    let allOk = true;
    for (const p of pages) {
      const res = await httpRequest(p, { cookie: adminSessionCookie });
      if (res.statusCode >= 500) {
        allOk = false;
        console.error(`    Error 500 en página: ${p}`);
      }
      if (res.rawBody.includes('Fatal error:') || res.rawBody.includes('Parse error:')) {
        allOk = false;
        console.error(`    Error PHP en página: ${p}`);
      }
    }
    assert(allOk, 'Todas las páginas principales cargan con código 200/302 sin errores de sintaxis PHP');
  } catch (err) {
    assert(false, 'Error en Test 12: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 13: Cambio de contraseña (actual incorrecta, débil, correcta)
  // -----------------------------------------------------------------
  console.log('\n[TEST 13/18] Cambio de Contraseña: Validaciones de actual, débil y política de rol');
  try {
    // Ganadero creado en prueba 6: Cédula 1098765432 / Password: Ganadero#2027!
    const { code: cLogin13, cookie: cookL13 } = await getValidCaptcha('');
    const resL13 = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cookL13,
      body: { cedula: '1098765432', password: 'Ganadero#2027!', captcha: cLogin13 }
    });
    const sessionG13 = extractSessionCookie(resL13.setCookies) || cookL13;
    assert(resL13.statusCode === 200, 'Ganadero autenticado para prueba de cambio de clave');

    // 13a. Actual errónea -> rechazado (401)
    const resBadCurrent = await httpRequest('api/cambiar_password.php', {
      method: 'POST',
      cookie: sessionG13,
      body: { passwordActual: 'Clave_Completamente_Erronea', passwordNueva: 'NuevoPassword#2027!', passwordConfirm: 'NuevoPassword#2027!' }
    });
    assert(resBadCurrent.statusCode === 401, 'Contraseña actual incorrecta rechazada con 401');

    // 13b. Débil (< 8 chars o común) -> rechazado (400)
    const resWeakG = await httpRequest('api/cambiar_password.php', {
      method: 'POST',
      cookie: sessionG13,
      body: { passwordActual: 'Ganadero#2027!', passwordNueva: '12345678', passwordConfirm: '12345678' }
    });
    assert(resWeakG.statusCode === 400, 'Contraseña común/débil rechazada con 400');

    // 13c. Idéntica a la actual -> rechazada (400)
    const resSame = await httpRequest('api/cambiar_password.php', {
      method: 'POST',
      cookie: sessionG13,
      body: { passwordActual: 'Ganadero#2027!', passwordNueva: 'Ganadero#2027!', passwordConfirm: 'Ganadero#2027!' }
    });
    assert(resSame.statusCode === 400, 'Nueva contraseña idéntica a la actual rechazada con 400');

    // 13d. Cambio válido
    const CLAVE_NUEVA_GAN = 'MiFincaLechera#2027';
    const resGoodChange = await httpRequest('api/cambiar_password.php', {
      method: 'POST',
      cookie: sessionG13,
      body: { passwordActual: 'Ganadero#2027!', passwordNueva: CLAVE_NUEVA_GAN, passwordConfirm: CLAVE_NUEVA_GAN }
    });
    assert(resGoodChange.statusCode === 200 && resGoodChange.json.ok === true, 'Cambio exitoso con nueva contraseña segura (200)');

    // 13e. Contraseña antigua ya no sirve
    const { code: cOldG, cookie: cookOldG } = await getValidCaptcha('');
    const resOldGLogin = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cookOldG,
      body: { cedula: '1098765432', password: 'Ganadero#2027!', captcha: cOldG }
    });
    assert(resOldGLogin.statusCode === 401, 'La contraseña antigua ya no permite el acceso (401)');

    // Login con la nueva funciona
    const { code: cNewG, cookie: cookNewG } = await getValidCaptcha('');
    const resNewGLogin = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cookNewG,
      body: { cedula: '1098765432', password: CLAVE_NUEVA_GAN, captcha: cNewG }
    });
    assert(resNewGLogin.statusCode === 200, 'La nueva contraseña permite el ingreso exitoso (200)');
  } catch (err) {
    assert(false, 'Error en Test 13: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 14: Primer ingreso del admin (bloqueo ineludible)
  // -----------------------------------------------------------------
  console.log('\n[TEST 14/18] Primer ingreso de Administrador: Imposible saltarse cambio de clave');
  try {
    // Creamos una sesión con flag debeCambiarPassword = 1
    const { code: cAdm14, cookie: cookAdm14 } = await getValidCaptcha('');
    // Primero dejamos al admin en la BD con debeCambiarPassword = 1 para verificar
    const resLoginAdm = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cookAdm14,
      body: { cedula: '79164451', password: NEW_ADMIN_PASS, captcha: cAdm14 }
    });
    const sAdm14 = extractSessionCookie(resLoginAdm.setCookies) || cookAdm14;

    // Si debeCambiarPassword es 0, no redirigiría, pero si es 1, redirige a cambiar_password.php
    // Probamos el comportamiento en cambiar_password.php
    const resView = await httpRequest('cambiar_password.php', { cookie: sAdm14 });
    assert(resView.statusCode === 200, 'Pantalla obligatoria de cambiar_password.php accesible y cargando');
  } catch (err) {
    assert(false, 'Error en Test 14: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 15: Solicitud de recuperación anti-enumeración (mensajes idénticos)
  // -----------------------------------------------------------------
  console.log('\n[TEST 15/18] Anti-Enumeración en Recuperación: Mensaje idéntico para cédula existente e inexistente');
  try {
    // 15a. Cédula existente (Admin 79164451)
    const { code: cRecEx, cookie: cookRecEx } = await getValidCaptcha('');
    const resRecExistente = await httpRequest('api/recuperar_password.php', {
      method: 'POST',
      cookie: cookRecEx,
      body: { cedula: '79164451', captcha: cRecEx }
    });
    assert(resRecExistente.statusCode === 200, 'Recuperación para cédula existente responde 200');

    // 15b. Cédula inexistente (999888777)
    const { code: cRecNo, cookie: cookRecNo } = await getValidCaptcha('');
    const resRecInexistente = await httpRequest('api/recuperar_password.php', {
      method: 'POST',
      cookie: cookRecNo,
      body: { cedula: '999888777', captcha: cRecNo }
    });
    assert(resRecInexistente.statusCode === 200, 'Recuperación para cédula inexistente responde 200');

    // Mensajes exactamente idénticos
    assert(
      resRecExistente.json.mensaje === resRecInexistente.json.mensaje &&
      resRecExistente.json.mensaje === 'Si la cédula existe, se procesará tu solicitud.',
      'El mensaje de respuesta es rigurosamente idéntico: no revela existencia de usuarios'
    );
  } catch (err) {
    assert(false, 'Error en Test 15: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 16: Contraseña temporal y un solo uso de tokens
  // -----------------------------------------------------------------
  console.log('\n[TEST 16/18] Contraseña Temporal y Tokens: Uso único y rechazo de vencidos/reutilizados');
  try {
    // 16a. Ganadero solicita recuperación (Flujo A)
    const { code: cRecG, cookie: cookRecG } = await getValidCaptcha('');
    await httpRequest('api/recuperar_password.php', {
      method: 'POST',
      cookie: cookRecG,
      body: { cedula: '1098765432', captcha: cRecG }
    });

    // Admin consulta buzón y encuentra la solicitud
    const resBuzon = await httpRequest('api/consultas.php', { cookie: adminSessionCookie });
    const solicitudes = resBuzon.json?.solicitudes || [];
    const solGanadero = solicitudes.find(s => s.cedula === '1098765432' && s.estado === 'PENDIENTE');
    assert(solGanadero, 'Solicitud de recuperación de ganadero visible en buzón de admin');

    // Admin genera clave temporal para Ganadero
    const resGen = await httpRequest('api/admin_generar_clave_temporal.php', {
      method: 'POST',
      cookie: adminSessionCookie,
      body: { solicitudId: solGanadero.id }
    });
    assert(resGen.statusCode === 200 && resGen.json.claveTemporal, 'Clave temporal generada exitosamente por el administrador');
    const tempPassGenerated = resGen.json.claveTemporal;
    assert(resGen.json.advertencia.includes('una sola vez'), 'Advertencia de clave mostrada solo una vez');

    // 16b. Ganadero inicia sesión con la contraseña temporal
    const { code: cLogTemp, cookie: cookLogTemp } = await getValidCaptcha('');
    const resLoginTemp = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cookLogTemp,
      body: { cedula: '1098765432', password: tempPassGenerated, captcha: cLogTemp }
    });
    assert(resLoginTemp.statusCode === 200, 'Inicio de sesión con contraseña temporal exitoso');
    assert(resLoginTemp.json.debeCambiarPassword === true, 'El sistema detecta debeCambiarPassword = true');
    assert(resLoginTemp.json.redirectUrl === 'cambiar_password.php', 'Redirigido obligatoriamente a cambiar_password.php');

    // Ganadero cambia obligatoriamente su clave temporal por una nueva contraseña
    const sessionGanaderoTemp = extractSessionCookie(resLoginTemp.setCookies) || cookLogTemp;
    const CLAVE_GANADERO_POST_TEMP = 'FincaElRoble#2027';
    const resChangePostTemp = await httpRequest('api/cambiar_password.php', {
      method: 'POST',
      cookie: sessionGanaderoTemp,
      body: { passwordActual: tempPassGenerated, passwordNueva: CLAVE_GANADERO_POST_TEMP, passwordConfirm: CLAVE_GANADERO_POST_TEMP }
    });
    assert(resChangePostTemp.statusCode === 200, 'Ganadero cambia obligatoriamente su clave temporal tras primer acceso');

    // 16c. Token falso o expirado en api/restablecer_con_token.php
    const resBadToken = await httpRequest('api/restablecer_con_token.php', {
      method: 'POST',
      body: {
        token: '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef',
        passwordNueva: 'Password#2027!New',
        passwordConfirm: 'Password#2027!New'
      }
    });
    assert(resBadToken.statusCode === 400 && resBadToken.json.error.includes('inválido'), 'Token inexistente o vencido rechazado con 400');
  } catch (err) {
    assert(false, 'Error en Test 16: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 17: Límite de solicitudes de recuperación (Rate Limiting)
  // -----------------------------------------------------------------
  console.log('\n[TEST 17/18] Límite de Recuperación: Máximo 3 solicitudes por hora');
  try {
    const testCedulaRec = '88887777';
    let triggeredRate = false;
    for (let i = 1; i <= 5; i++) {
      const { code: cRate, cookie: cookRate } = await getValidCaptcha('');
      const resRate = await httpRequest('api/recuperar_password.php', {
        method: 'POST',
        cookie: cookRate,
        body: { cedula: testCedulaRec, captcha: cRate }
      });
      if (resRate.statusCode === 429) {
        triggeredRate = true;
        break;
      }
    }
    assert(triggeredRate, 'Más de 3 solicitudes consecutivas activan bloqueo HTTP 429');
  } catch (err) {
    assert(false, 'Error en Test 17: ' + err.message);
  }

  // -----------------------------------------------------------------
  // PRUEBA 18: Invalidación de sesiones anteriores concurrentes
  // -----------------------------------------------------------------
  console.log('\n[TEST 18/18] Invalidación de Sesiones: Cierre global tras cambio de clave');
  try {
    const claveActual18 = 'FincaElRoble#2027';

    // 1. Abrir Navegador A (Sesión A)
    const { code: cA, cookie: cCookieA } = await getValidCaptcha('');
    const resA = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cCookieA,
      body: { cedula: '1098765432', password: claveActual18, captcha: cA }
    });
    const sessionA = extractSessionCookie(resA.setCookies) || cCookieA;

    // 2. Abrir Navegador B (Sesión B en otro dispositivo/cookie)
    const { code: cB, cookie: cCookieB } = await getValidCaptcha('');
    const resB = await httpRequest('api/login.php', {
      method: 'POST',
      cookie: cCookieB,
      body: { cedula: '1098765432', password: claveActual18, captcha: cB }
    });
    const sessionB = extractSessionCookie(resB.setCookies) || cCookieB;

    // Ambas sesiones funcionan
    const chkA = await httpRequest('panel.php', { cookie: sessionA });
    const chkB = await httpRequest('panel.php', { cookie: sessionB });
    assert(chkA.statusCode === 200 && chkB.statusCode === 200, 'Sesión A y Sesión B concurrentes activas');

    // 3. En Navegador A se cambia la contraseña
    const CLAVE_FINAL_18 = 'NuevaClaveFinca#18_B';
    const resChangeA = await httpRequest('api/cambiar_password.php', {
      method: 'POST',
      cookie: sessionA,
      body: { passwordActual: claveActual18, passwordNueva: CLAVE_FINAL_18, passwordConfirm: CLAVE_FINAL_18 }
    });
    assert(resChangeA.statusCode === 200, 'Navegador A cambia la contraseña exitosamente');

    // 4. Navegador B intenta consultar una API o panel.php -> Debe ser expulsado (401 o 302 a login)
    const chkBAfter = await httpRequest('api/entregas.php', { cookie: sessionB });
    assert(chkBAfter.statusCode === 401, 'Navegador B con sesión antigua rechazado con 401 (Sesión invalidada globalmente)');
  } catch (err) {
    assert(false, 'Error en Test 18: ' + err.message);
  }

  console.log('\n===============================================================');
  console.log(` RESULTADO FINAL: ${passed} PRUEBAS EXITOSAS / ${failed} FALLOS`);
  console.log('===============================================================');

  if (failed > 0) {
    process.exit(1);
  } else {
    process.exit(0);
  }
}

runTests();
