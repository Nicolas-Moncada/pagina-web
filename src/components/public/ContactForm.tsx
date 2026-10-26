"use client";

import { useState } from "react";
import { Send, CheckCircle2, AlertCircle, Phone, Mail, MapPin, Clock } from "lucide-react";

export default function ContactForm() {
  const [nombre, setNombre] = useState("");
  const [contacto, setContacto] = useState("");
  const [mensaje, setMensaje] = useState("");

  const [loading, setLoading] = useState(false);
  const [success, setSuccess] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSuccess(null);
    setError(null);

    const nombreLimpio = nombre.trim();
    const contactoLimpio = contacto.trim();
    const mensajeLimpio = mensaje.trim();

    if (!nombreLimpio || !contactoLimpio || !mensajeLimpio) {
      setError("Por favor completa todos los campos del formulario");
      return;
    }

    setLoading(true);

    try {
      const res = await fetch("/api/consultas", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          nombre: nombreLimpio,
          contacto: contactoLimpio,
          mensaje: mensajeLimpio,
        }),
      });

      const data = await res.json();

      if (!res.ok) {
        setError(data.error || "Ocurrió un error al enviar tu consulta");
        setLoading(false);
        return;
      }

      setSuccess("✅ ¡Tu consulta ha sido enviada con éxito! Nuestro equipo administrativo la revisará y se comunicará contigo.");
      setNombre("");
      setContacto("");
      setMensaje("");
      setLoading(false);
    } catch {
      setError("No se pudo enviar el formulario. Verifica tu conexión a internet.");
      setLoading(false);
    }
  };

  return (
    <section id="contacto" className="py-20 bg-white">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-12">
          {/* Información Institucional de Contacto */}
          <div className="lg:col-span-5 space-y-8">
            <div>
              <span className="text-sm font-bold uppercase tracking-wider text-campo-600 bg-campo-100 px-3 py-1 rounded-full border border-campo-200">
                Atención al Público
              </span>
              <h2 className="text-3xl sm:text-4xl font-extrabold text-campo-950 mt-3">
                ¿Tienes alguna consulta o deseas afiliarte?
              </h2>
              <p className="text-lg text-gray-700 mt-4 leading-relaxed">
                Estamos para servir a los ganaderos de la región y a la comunidad en general. 
                Envíanos tu mensaje o visítanos en nuestro centro de acopio principal.
              </p>
            </div>

            <div className="space-y-5">
              <div className="flex items-start gap-4">
                <div className="w-12 h-12 rounded-xl bg-campo-100 text-campo-800 flex items-center justify-center flex-shrink-0">
                  <MapPin className="w-6 h-6" />
                </div>
                <div>
                  <h3 className="font-bold text-gray-900 text-base">Ubicación de Acopio</h3>
                  <p className="text-gray-600 text-sm mt-0.5">
                    Vía Principal al Municipio, Sector La Pradera Km 4
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="w-12 h-12 rounded-xl bg-campo-100 text-campo-800 flex items-center justify-center flex-shrink-0">
                  <Clock className="w-6 h-6" />
                </div>
                <div>
                  <h3 className="font-bold text-gray-900 text-base">Horarios de Recepción de Leche</h3>
                  <p className="text-gray-600 text-sm mt-0.5">
                    • Turno Mañana: 5:30 AM a 8:30 AM<br />
                    • Turno Tarde: 3:30 PM a 6:00 PM
                  </p>
                </div>
              </div>

              <div className="flex items-start gap-4">
                <div className="w-12 h-12 rounded-xl bg-campo-100 text-campo-800 flex items-center justify-center flex-shrink-0">
                  <Phone className="w-6 h-6" />
                </div>
                <div>
                  <h3 className="font-bold text-gray-900 text-base">Teléfono de Oficina</h3>
                  <p className="text-gray-600 text-sm mt-0.5">
                    (601) 850 0000 / Celular: +57 310 000 0000
                  </p>
                </div>
              </div>
            </div>
          </div>

          {/* Formulario de Consultas */}
          <div className="lg:col-span-7">
            <div className="bg-campo-50/50 p-6 sm:p-10 rounded-3xl border-2 border-campo-200 shadow-sm">
              <h3 className="text-2xl font-bold text-campo-950 mb-2">
                Envíanos tu Mensaje
              </h3>
              <p className="text-gray-600 text-base mb-6">
                Completa tus datos y te responderemos a la mayor brevedad posible.
              </p>

              {success && (
                <div className="mb-6 p-4 bg-green-50 border-l-4 border-campo-600 rounded-r-lg text-campo-900 flex items-start gap-3">
                  <CheckCircle2 className="w-6 h-6 text-campo-600 flex-shrink-0 mt-0.5" />
                  <div className="font-medium text-base">{success}</div>
                </div>
              )}

              {error && (
                <div className="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-red-900 flex items-start gap-3">
                  <AlertCircle className="w-6 h-6 text-red-500 flex-shrink-0 mt-0.5" />
                  <div className="font-medium text-base">{error}</div>
                </div>
              )}

              <form onSubmit={handleSubmit} className="space-y-5">
                <div>
                  <label htmlFor="nombre" className="block text-base font-semibold text-gray-800 mb-2">
                    Tu Nombre Completo <span className="text-red-500">*</span>
                  </label>
                  <input
                    id="nombre"
                    type="text"
                    required
                    value={nombre}
                    onChange={(e) => setNombre(e.target.value)}
                    placeholder="Ej: Don Carlos Mendoza"
                    className="w-full px-4 py-3.5 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white"
                  />
                </div>

                <div>
                  <label htmlFor="contacto" className="block text-base font-semibold text-gray-800 mb-2">
                    Teléfono celular o Correo electrónico <span className="text-red-500">*</span>
                  </label>
                  <input
                    id="contacto"
                    type="text"
                    required
                    value={contacto}
                    onChange={(e) => setContacto(e.target.value)}
                    placeholder="Ej: 312 345 6789 o correo@ejemplo.com"
                    className="w-full px-4 py-3.5 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white"
                  />
                  <p className="text-xs text-gray-500 mt-1">
                    Indícanos dónde podemos llamarte o escribirte
                  </p>
                </div>

                <div>
                  <label htmlFor="mensaje" className="block text-base font-semibold text-gray-800 mb-2">
                    ¿Cuál es tu consulta o mensaje? <span className="text-red-500">*</span>
                  </label>
                  <textarea
                    id="mensaje"
                    rows={4}
                    required
                    value={mensaje}
                    onChange={(e) => setMensaje(e.target.value)}
                    placeholder="Escribe aquí tu pregunta sobre afiliaciones, entregas de leche o pagos..."
                    className="w-full px-4 py-3 text-base border-2 border-campo-200 rounded-xl focus:outline-none focus:border-campo-600 focus:ring-4 focus:ring-campo-100 bg-white resize-y"
                  />
                </div>

                <button
                  type="submit"
                  disabled={loading}
                  className="w-full btn-touch bg-campo-700 hover:bg-campo-800 text-white shadow-md hover:shadow-lg disabled:opacity-50 text-lg font-bold flex items-center justify-center gap-2 mt-4"
                >
                  {loading ? (
                    <span>Enviando consulta...</span>
                  ) : (
                    <>
                      <Send className="w-5 h-5 text-campo-200" />
                      <span>Enviar Consulta a la Asociación</span>
                    </>
                  )}
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

