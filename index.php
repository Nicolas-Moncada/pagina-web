<?php
require_once __DIR__ . '/api/db.php';
$usuarioActual = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Asociación de Ganaderos | Unión y Progreso Lechero</title>
  <link rel="stylesheet" href="./css/estilos.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            campo: {
              50: '#f2f8f3', 100: '#e1efe4', 200: '#c4dfcb', 300: '#99c6a4',
              400: '#6aa778', 500: '#498b58', 600: '#366e43', 700: '#2d5737',
              800: '#26462e', 900: '#203a27', 950: '#0d1f13'
            },
            tierra: {
              50: '#faf6f1', 100: '#f2eae0', 200: '#e4d3c0', 300: '#d3b79a',
              400: '#c09774', 500: '#af7e56', 600: '#996748', 700: '#7c503b',
              800: '#664234', 900: '#54372e'
            },
            leche: { 50: '#ffffff', 100: '#fcfbf7', 200: '#f7f5ed', 300: '#ede8d8' },
            dorado: { 400: '#f59e0b', 500: '#d97706', 600: '#b45309' }
          }
        }
      }
    }
  </script>
</head>
<body class="bg-leche-100 text-campo-950 min-h-screen flex flex-col font-sans antialiased">

  <!-- NAVBAR -->
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-campo-100 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-20">
        <!-- Logo -->
        <a href="./index.php" class="flex items-center gap-3 group">
          <div class="w-12 h-12 rounded-xl bg-campo-700 text-white flex items-center justify-center shadow-md group-hover:bg-campo-800 transition-colors text-2xl">
            🥛
          </div>
          <div>
            <span class="block text-xl font-bold tracking-tight text-campo-900 group-hover:text-campo-700 transition-colors">
              Asociación de Ganaderos
            </span>
            <span class="block text-xs font-medium text-campo-600 uppercase tracking-wider">
              Unión y Progreso Lechero
            </span>
          </div>
        </a>

        <!-- Enlaces Desktop -->
        <nav class="hidden md:flex items-center gap-8 font-medium text-gray-700">
          <a href="#nosotros" class="hover:text-campo-700 transition-colors py-2">¿Quiénes Somos?</a>
          <a href="#mision-vision" class="hover:text-campo-700 transition-colors py-2">Misión y Visión</a>
          <a href="#galeria" class="hover:text-campo-700 transition-colors py-2">Nuestra Labor</a>
          <a href="#contacto" class="hover:text-campo-700 transition-colors py-2">Consultas</a>
        </nav>

        <!-- Botones de Acción -->
        <div class="flex items-center gap-3">
          <?php if ($usuarioActual): ?>
            <a href="<?php echo ($usuarioActual['rol'] === 'ADMIN') ? './admin.php' : './panel.php'; ?>" 
               class="btn-touch bg-campo-700 hover:bg-campo-800 text-white font-bold text-sm px-5 py-2.5 rounded-xl shadow transition-all flex items-center gap-2">
              <span>Mi Panel (<?php echo htmlspecialchars($usuarioActual['nombre']); ?>)</span>
              <span>➜</span>
            </a>
            <a href="./logout.php" class="text-xs font-bold text-red-600 hover:underline">Salir</a>
          <?php else: ?>
            <a href="./registro.php" class="hidden sm:inline-block text-sm font-bold text-campo-800 hover:text-campo-950 px-4 py-2.5 rounded-xl hover:bg-campo-50 border border-campo-200 transition-all">
              Registrarse
            </a>
            <a href="./login.php" class="btn-touch bg-campo-700 hover:bg-campo-800 text-white font-bold text-sm px-6 py-2.5 rounded-xl shadow transition-all flex items-center gap-2">
              <span>🔑 Iniciar Sesión</span>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </header>

  <!-- HERO SECTION -->
  <section class="relative overflow-hidden py-16 sm:py-24 bg-gradient-to-b from-campo-50/50 via-leche-100 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
        <!-- Columna Texto -->
        <div class="lg:col-span-7 space-y-6 text-center lg:text-left">
          <div class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-campo-100 border border-campo-200 text-campo-800 font-bold text-xs uppercase tracking-wider">
            <span>🌿</span> Plataforma Oficial de Control y Liquidación Lechera
          </div>

          <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-campo-950 leading-tight">
            Compromiso con el Campo, <span class="text-campo-700 underline decoration-dorado-400">Pureza en Cada Litro</span>
          </h1>

          <p class="text-lg sm:text-xl text-gray-700 leading-relaxed max-w-2xl mx-auto lg:mx-0">
            Uniendo a los productores de leche de la región para garantizar pesajes justos en cada turno (Mañana y Tarde), recolección oportuna y pagos puntuales cada quincena.
          </p>

          <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-4">
            <a href="./login.php" class="btn-touch bg-campo-700 hover:bg-campo-800 text-white text-base sm:text-lg font-bold px-8 py-3.5 rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
              <span>Ingresar al Portal de Asociados</span>
              <span>➜</span>
            </a>
            <a href="./registro.php" class="btn-touch bg-white hover:bg-campo-50 text-campo-800 border-2 border-campo-300 text-base sm:text-lg font-bold px-6 py-3.5 rounded-xl transition-all flex items-center justify-center gap-2">
              <span>Afiliarme a la Asociación</span>
            </a>
          </div>

          <!-- Métricas -->
          <div class="pt-8 grid grid-cols-3 gap-4 border-t border-campo-200 max-w-lg mx-auto lg:mx-0 text-center">
            <div>
              <p class="text-2xl sm:text-3xl font-extrabold text-campo-800">+15 Años</p>
              <p class="text-xs sm:text-sm font-medium text-gray-600 mt-0.5">Unión y Confianza</p>
            </div>
            <div>
              <p class="text-2xl sm:text-3xl font-extrabold text-campo-800">2 Turnos</p>
              <p class="text-xs sm:text-sm font-medium text-gray-600 mt-0.5">Mañana y Tarde</p>
            </div>
            <div>
              <p class="text-2xl sm:text-3xl font-extrabold text-campo-800">100%</p>
              <p class="text-xs sm:text-sm font-medium text-gray-600 mt-0.5">Pago Puntual</p>
            </div>
          </div>
        </div>

        <!-- Columna Imagen Destacada -->
        <div class="lg:col-span-5">
          <div class="relative mx-auto max-w-md lg:max-w-none">
            <div class="relative rounded-3xl overflow-hidden shadow-2xl border-4 border-white bg-campo-800 h-80 sm:h-96">
              <img src="https://images.unsplash.com/photo-1527153857715-3908f2ae5e81?auto=format&fit=crop&w=800&q=80" 
                   alt="Ganadería lechera y praderas verdes" 
                   class="w-full h-full object-cover">
              <div class="absolute inset-0 bg-gradient-to-t from-campo-950/80 via-campo-950/20 to-transparent flex flex-col justify-end p-6 text-white z-10 pointer-events-none">
                <span class="inline-block bg-dorado-500 text-campo-950 font-bold text-xs uppercase tracking-wider px-2.5 py-1 rounded-md mb-2 w-max">
                  Garantía de Pureza
                </span>
                <h2 class="text-xl font-bold">Producción Limpia y Sostenible</h2>
                <p class="text-xs text-campo-100 mt-1">
                  Apoyo técnico continuo y recolección puntual en cada finca afiliada.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ¿QUIÉNES SOMOS? -->
  <section id="nosotros" class="py-20 bg-white border-y border-campo-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-3xl mx-auto space-y-4 mb-16">
        <span class="text-sm font-bold uppercase tracking-wider text-campo-600 bg-campo-50 px-3 py-1 rounded-full border border-campo-200">
          Nuestra Esencia
        </span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-campo-950">
          ¿Quiénes Somos?
        </h2>
        <p class="text-lg text-gray-700 leading-relaxed">
          Somos una organización comunitaria y gremial fundada con el propósito de unir las voluntades y el esfuerzo diario de los productores lecheros de la región. Transformamos el trabajo de las madrugadas en prosperidad, asegurando canales de acopio dignos y pagos quincenales exactos.
        </p>
      </div>

      <!-- Tarjetas Misión y Visión -->
      <div id="mision-vision" class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
        <!-- Misión -->
        <div class="bg-gradient-to-br from-campo-50 to-white p-8 sm:p-10 rounded-2xl border-2 border-campo-200 shadow-sm relative overflow-hidden group hover:border-campo-400 transition-all">
          <div class="w-14 h-14 rounded-xl bg-campo-700 text-white flex items-center justify-center mb-6 shadow-md text-2xl">
            🎯
          </div>
          <h3 class="text-2xl font-bold text-campo-900 mb-3">Nuestra Misión</h3>
          <p class="text-base text-gray-700 leading-relaxed">
            Fomentar el desarrollo sostenible y la rentabilidad de las familias ganaderas asociadas, garantizando el acopio responsable, el pesaje transparente y la comercialización eficiente de leche de la más alta calidad higiénica y composicional.
          </p>
        </div>

        <!-- Visión -->
        <div class="bg-gradient-to-br from-tierra-50 to-white p-8 sm:p-10 rounded-2xl border-2 border-tierra-200 shadow-sm relative overflow-hidden group hover:border-tierra-400 transition-all">
          <div class="w-14 h-14 rounded-xl bg-tierra-700 text-white flex items-center justify-center mb-6 shadow-md text-2xl">
            🧭
          </div>
          <h3 class="text-2xl font-bold text-tierra-900 mb-3">Nuestra Visión</h3>
          <p class="text-base text-gray-700 leading-relaxed">
            Consolidarnos como la asociación ganadera modelo en la región, reconocida por la excelencia en buenas prácticas de ordeño, tecnología accesible para el pequeño y mediano productor, y la solidez financiera de todos nuestros miembros.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- GALERÍA DE NUESTRA LABOR -->
  <section id="galeria" class="py-20 bg-leche-200/60">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
        <span class="text-sm font-bold uppercase tracking-wider text-campo-600 bg-campo-100 px-3 py-1 rounded-full border border-campo-200">
          Nuestra Tierra y Nuestra Gente
        </span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-campo-950">
          Galería del Campo y la Asociación
        </h2>
        <p class="text-lg text-gray-700">
          Imágenes de las faenas diarias en las fincas de nuestros asociados, donde nace la leche más fresca y pura de la región.
        </p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Foto 1 -->
        <div class="bg-white rounded-2xl overflow-hidden shadow-md border border-campo-200 flex flex-col group hover:shadow-xl transition-all">
          <div class="relative h-56 overflow-hidden bg-campo-900">
            <img src="https://images.unsplash.com/photo-1546445317-29f4545e9d53?auto=format&fit=crop&w=600&q=80" 
                 alt="Ganado en pastoreo" 
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
          </div>
          <div class="p-5 flex-1">
            <h3 class="font-bold text-lg text-campo-950 mb-1">Praderas y Ganado Sano</h3>
            <p class="text-xs text-gray-600">Pasturas naturales y bienestar animal garantizado en cada hacienda.</p>
          </div>
        </div>

        <!-- Foto 2 -->
        <div class="bg-white rounded-2xl overflow-hidden shadow-md border border-campo-200 flex flex-col group hover:shadow-xl transition-all">
          <div class="relative h-56 overflow-hidden bg-campo-900">
            <img src="https://images.unsplash.com/photo-1500595046743-cd271d694d30?auto=format&fit=crop&w=600&q=80" 
                 alt="Ordeño limpio" 
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
          </div>
          <div class="p-5 flex-1">
            <h3 class="font-bold text-lg text-campo-950 mb-1">Buenas Prácticas de Ordeño</h3>
            <p class="text-xs text-gray-600">Máxima higiene en turnos de mañana y tarde con equipos esterilizados.</p>
          </div>
        </div>

        <!-- Foto 3 -->
        <div class="bg-white rounded-2xl overflow-hidden shadow-md border border-campo-200 flex flex-col group hover:shadow-xl transition-all">
          <div class="relative h-56 overflow-hidden bg-campo-900">
            <img src="https://images.unsplash.com/photo-1596733430284-f7437764b1a9?auto=format&fit=crop&w=600&q=80" 
                 alt="Recolección de leche" 
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
          </div>
          <div class="p-5 flex-1">
            <h3 class="font-bold text-lg text-campo-950 mb-1">Pesaje y Cadena de Frío</h3>
            <p class="text-xs text-gray-600">Control de litros transparente y conservación térmica inmediata.</p>
          </div>
        </div>

        <!-- Foto 4 -->
        <div class="bg-white rounded-2xl overflow-hidden shadow-md border border-campo-200 flex flex-col group hover:shadow-xl transition-all">
          <div class="relative h-56 overflow-hidden bg-campo-900">
            <img src="https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=600&q=80" 
                 alt="Paisaje campesino" 
                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
          </div>
          <div class="p-5 flex-1">
            <h3 class="font-bold text-lg text-campo-950 mb-1">Nuestra Tierra Fértil</h3>
            <p class="text-xs text-gray-600">El trabajo diario de nuestras familias campesinas unidas por el progreso.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FORMULARIO DE CONSULTAS Y CONTACTO -->
  <section id="contacto" class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center space-y-4 mb-12">
        <span class="text-sm font-bold uppercase tracking-wider text-campo-600 bg-campo-50 px-3 py-1 rounded-full border border-campo-200">
          Canal de Atención
        </span>
        <h2 class="text-3xl sm:text-4xl font-extrabold text-campo-950">
          ¿Tienes Dudas o Deseas Afiliarte?
        </h2>
        <p class="text-base text-gray-600">
          Escríbenos y nuestro equipo administrativo se pondrá en contacto contigo en breve.
        </p>
      </div>

      <div class="bg-campo-50/60 p-8 sm:p-10 rounded-3xl border-2 border-campo-200 shadow-md">
        <div id="contacto-alert" class="hidden mb-6 p-4 rounded-xl text-sm font-bold"></div>

        <form id="form-contacto-publico" class="space-y-6">
          <div>
            <label class="block text-sm font-bold text-gray-800 mb-1">Nombre Completo *</label>
            <input type="text" name="nombre" required placeholder="Ej: Nombre Completo"
                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white text-base">
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-800 mb-1">Teléfono o Correo Electrónico *</label>
            <input type="text" name="contacto" required placeholder="Ej: 3105551234 o correo@ejemplo.com"
                   class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white text-base">
          </div>

          <div>
            <label class="block text-sm font-bold text-gray-800 mb-1">Mensaje o Inquietud *</label>
            <textarea name="mensaje" rows="4" required placeholder="Cuéntanos sobre tu finca, cantidad de vacas o tu consulta sobre la asociación..."
                      class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:outline-none focus:border-campo-600 focus:ring-2 focus:ring-campo-100 bg-white text-base"></textarea>
          </div>

          <button type="submit" id="btn-enviar-contacto"
                  class="btn-touch w-full bg-campo-700 hover:bg-campo-800 text-white font-bold text-base py-3.5 rounded-xl shadow transition-all flex items-center justify-center gap-2">
            <span>Enviar Mensaje a la Administración</span>
            <span>✉️</span>
          </button>
        </form>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="bg-campo-950 text-white border-t border-campo-900 pt-16 pb-12 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 md:grid-cols-12 gap-8 mb-12">
        <div class="md:col-span-6 space-y-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-campo-700 text-white flex items-center justify-center text-xl">
              🥛
            </div>
            <span class="text-xl font-bold tracking-tight">Asociación de Ganaderos</span>
          </div>
          <p class="text-campo-200 text-sm leading-relaxed max-w-md">
            Comprometidos con el desarrollo rural, el pago justo por cada litro de leche y el bienestar de las familias campesinas de nuestra tierra.
          </p>
        </div>
        <div class="md:col-span-3 space-y-2 text-sm text-campo-200">
          <h4 class="font-bold text-white uppercase text-xs tracking-wider">Enlaces</h4>
          <p><a href="#nosotros" class="hover:underline">¿Quiénes Somos?</a></p>
          <p><a href="#mision-vision" class="hover:underline">Misión y Visión</a></p>
          <p><a href="#contacto" class="hover:underline">Contacto</a></p>
        </div>
        <div class="md:col-span-3 space-y-2 text-sm text-campo-200">
          <h4 class="font-bold text-white uppercase text-xs tracking-wider">Acceso</h4>
          <p><a href="./login.php" class="text-dorado-400 font-bold hover:underline">Portal de Asociados</a></p>
          <p><a href="./registro.php" class="hover:underline">Crear Cuenta Nueva</a></p>
          <p class="text-xs text-campo-300">Correo oficial: asoagroalisal@gmail.com</p>
        </div>
      </div>
      <div class="pt-8 border-t border-campo-900 text-center text-xs text-campo-400">
        &copy; <?php echo date('Y'); ?> Asociación de Ganaderos Unión y Progreso Lechero. Plataforma 100% Nativa para XAMPP.
      </div>
    </div>
  </footer>

  <script src="./js/app.js"></script>
  <script>
    document.getElementById("form-contacto-publico")?.addEventListener("submit", async function(e) {
      e.preventDefault();
      const form = e.target;
      const btn = document.getElementById("btn-enviar-contacto");
      const alertBox = document.getElementById("contacto-alert");

      btn.disabled = true;
      btn.textContent = "Enviando...";

      const data = {
        nombre: form.nombre.value.trim(),
        contacto: form.contacto.value.trim(),
        mensaje: form.mensaje.value.trim()
      };

      try {
        const res = await fetch("./api/consultas.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(data)
        });
        const resData = await res.json();
        if (res.ok) {
          alertBox.className = "mb-6 p-4 rounded-xl text-sm font-bold bg-green-100 text-green-900 border border-green-300";
          alertBox.textContent = resData.mensaje || "¡Consulta enviada exitosamente!";
          alertBox.classList.remove("hidden");
          form.reset();
        } else {
          alertBox.className = "mb-6 p-4 rounded-xl text-sm font-bold bg-red-100 text-red-900 border border-red-300";
          alertBox.textContent = resData.error || "Error al enviar mensaje.";
          alertBox.classList.remove("hidden");
        }
      } catch (err) {
        alertBox.className = "mb-6 p-4 rounded-xl text-sm font-bold bg-red-100 text-red-900 border border-red-300";
        alertBox.textContent = "Error de conexión con el servidor.";
        alertBox.classList.remove("hidden");
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<span>Enviar Mensaje a la Administración</span> <span>✉️</span>`;
      }
    });
  </script>
</body>
</html>

