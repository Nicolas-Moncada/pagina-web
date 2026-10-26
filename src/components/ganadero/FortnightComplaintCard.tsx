"use client";

import { useState, useEffect } from "react";
import { AlertTriangle, Send, CheckCircle2, Clock, ShieldAlert, MessageSquare, ChevronDown, ChevronUp, Lock } from "lucide-react";
import { formatDateSpanish } from "@/lib/date-utils";

interface FortnightComplaintCardProps {
  fechaInicio: string;
  fechaFin: string;
  periodoLegible: string;
  litrosAcumulados: number;
}

interface AlertaExistente {
  id: string;
  mensaje: string;
  litrosAlMomento: number;
  estado: string;
  correoEnviado: boolean;
  creadoEn: string;
}

export default function FortnightComplaintCard({
  fechaInicio,
  fechaFin,
  periodoLegible,
  litrosAcumulados,
}: FortnightComplaintCardProps) {
  const [formAbierto, setFormAbierto] = useState(false);
  const [mensaje, setMensaje] = useState("");
  const [enviando, setEnviando] = useState(false);
  const [cargandoAlerta, setCargandoAlerta] = useState(true);

  const [alerta, setAlerta] = useState<AlertaExistente | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);

  // Consultar si ya existe una alerta para esta quincena
  useEffect(() => {
    setCargandoAlerta(true);
    setErrorMsg(null);
    setSuccessMsg(null);

    fetch(`/api/ganadero/alertas?fechaInicio=${fechaInicio}&fechaFin=${fechaFin}`)
      .then((r) => r.json())
      .then((data) => {
        if (data.alerta) {
          setAlerta(data.alerta);
        } else {
          setAlerta(null);
        }
      })
      .catch((err) => console.error("Error al consultar alerta:", err))
      .finally(() => setCargandoAlerta(false));
  }, [fechaInicio, fechaFin]);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrorMsg(null);
    setSuccessMsg(null);

    if (mensaje.trim().length < 5) {
      setErrorMsg("Por favor describe tu inquietud con al menos 5 caracteres.");
      return;
    }

    setEnviando(true);

    try {
      const res = await fetch("/api/ganadero/alertas", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          fechaInicio,
          fechaFin,
          mensaje: mensaje.trim(),
        }),
      });

      const data = await res.json();

      if (!res.ok) {
        setErrorMsg(data.error || "No se pudo registrar tu inquietud.");
        if (data.alerta) {
          setAlerta(data.alerta);
        }
        setEnviando(false);
        return;
      }

      setSuccessMsg("✅ Tu inquietud ha sido registrada y enviada al administrador con éxito.");
      setAlerta(data.alerta);
      setMensaje("");
      setFormAbierto(false);
    } catch {
      setErrorMsg("Error de conexión al enviar el reclamo. Intenta nuevamente.");
    } finally {
      setEnviando(false);
    }
  };

  return (
    <div className="bg-white rounded-3xl shadow-lg border-2 border-tierra-200 overflow-hidden">
      {/* Cabecera */}
      <div className="bg-gradient-to-r from-tierra-800 to-tierra-900 text-white p-5 sm:p-6">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-2xl bg-white/10 flex items-center justify-center text-tierra-200 flex-shrink-0">
              <ShieldAlert className="w-7 h-7 text-dorado-400" />
            </div>
            <div>
              <p className="text-xs uppercase font-bold tracking-wider text-tierra-200">
                Atención y Soporte al Productor
              </p>
              <h3 className="text-xl sm:text-2xl font-black text-white">
                ¿Tienes alguna inconformidad con tus litros registrados?
              </h3>
            </div>
          </div>

          {!alerta && !cargandoAlerta && (
            <button
              onClick={() => setFormAbierto(!formAbierto)}
              className="btn-touch bg-tierra-700 hover:bg-tierra-600 text-white font-bold text-sm px-4 py-2 rounded-xl flex items-center gap-2 border border-tierra-500 whitespace-nowrap self-end sm:self-auto"
            >
              <MessageSquare className="w-4 h-4" />
              <span>{formAbierto ? "Cerrar Formulario" : "Reportar Inconformidad"}</span>
              {formAbierto ? <ChevronUp className="w-4 h-4" /> : <ChevronDown className="w-4 h-4" />}
            </button>
          )}
        </div>
      </div>

      <div className="p-5 sm:p-6 space-y-4">
        {/* CASO 1: YA EXISTE UNA ALERTA ENVIADA (BLOQUEO ESTRICTO DE 1 ALERTA POR QUINCENA) */}
        {alerta ? (
          <div className="bg-amber-50/80 border-2 border-amber-300 rounded-2xl p-5 sm:p-6 text-amber-950 space-y-3">
            <div className="flex items-start gap-3">
              <div className="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center text-amber-700 flex-shrink-0 mt-0.5">
                <Lock className="w-6 h-6" />
              </div>
              <div className="flex-1">
                <div className="flex flex-wrap items-center gap-2 mb-1">
                  <span className="text-xs font-black uppercase tracking-wider bg-amber-200/80 text-amber-900 px-2.5 py-0.5 rounded-md">
                    Límite de 1 Alerta por Quincena Aplicado
                  </span>
                  <span className="text-xs font-bold text-amber-800 bg-white/70 px-2.5 py-0.5 rounded-md border border-amber-200">
                    Estado: {alerta.estado === "PENDIENTE" ? "En revisión por administración" : alerta.estado}
                  </span>
                </div>
                <h4 className="text-base sm:text-lg font-black text-amber-950">
                  Ya has enviado una inquietud para esta quincena. El administrador la está revisando.
                </h4>
                <p className="text-xs sm:text-sm text-amber-800 mt-1">
                  Enviada el{" "}
                  <strong>
                    {new Date(alerta.creadoEn).toLocaleString("es-CO", { timeZone: "America/Bogota" })}
                  </strong>{" "}
                  con <strong>{alerta.litrosAlMomento.toFixed(1)} Litros</strong> acumulados al momento del reporte.
                </p>
              </div>
            </div>

            {/* Mensaje enviado en solo lectura */}
            <div className="bg-white/80 p-4 rounded-xl border border-amber-200 mt-2">
              <p className="text-xs font-bold text-amber-900 uppercase tracking-wider mb-1">
                Mensaje registrado:
              </p>
              <p className="text-sm font-medium text-gray-800 italic leading-relaxed">
                "{alerta.mensaje}"
              </p>
            </div>

            <p className="text-[11px] text-amber-800">
              * Para garantizar el orden en las revisiones, solo se permite un reporte por periodo quincenal. Si necesitas agregar información adicional, comunícate directamente con la oficina de la asociación.
            </p>
          </div>
        ) : cargandoAlerta ? (
          <div className="p-4 text-center text-gray-500 text-sm">
            Verificando estado de inquietudes para esta quincena...
          </div>
        ) : (
          /* CASO 2: AÚN NO HA ENVIADO ALERTA (FORMULARIO DISPONIBLE) */
          <>
            {!formAbierto ? (
              <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-campo-50/50 p-4 rounded-2xl border border-campo-200">
                <div className="text-sm text-gray-700">
                  <p className="font-bold text-campo-950">
                    ¿Notas alguna discrepancia en el pesaje matutino o vespertino de esta quincena?
                  </p>
                  <p className="text-xs text-gray-600 mt-0.5">
                    Puedes redactar una observación que llegará de inmediato a la administración y al correo oficial.
                  </p>
                </div>
                <button
                  onClick={() => setFormAbierto(true)}
                  className="btn-touch bg-campo-700 hover:bg-campo-800 text-white text-sm font-bold px-5 py-2.5 rounded-xl shadow-sm whitespace-nowrap self-end sm:self-auto"
                >
                  Abrir Formulario de Reclamo
                </button>
              </div>
            ) : (
              <form onSubmit={handleSubmit} className="bg-tierra-50/50 p-5 sm:p-6 rounded-2xl border-2 border-tierra-200 space-y-4">
                <div className="flex items-center justify-between">
                  <div>
                    <h4 className="text-base font-bold text-tierra-950">
                      Reportar Inconformidad sobre {periodoLegible}
                    </h4>
                    <p className="text-xs text-gray-600 mt-0.5">
                      Litros acumulados actualmente: <strong>{litrosAcumulados.toFixed(1)} L</strong>
                    </p>
                  </div>
                  <span className="text-xs font-bold text-amber-900 bg-amber-100 border border-amber-300 px-2.5 py-1 rounded-md">
                    Límite: 1 Alerta por Quincena
                  </span>
                </div>

                {errorMsg && (
                  <div className="p-3 bg-red-50 border-l-4 border-red-500 rounded text-red-800 text-sm font-medium">
                    {errorMsg}
                  </div>
                )}

                {successMsg && (
                  <div className="p-3 bg-green-50 border-l-4 border-green-500 rounded text-green-900 text-sm font-medium">
                    {successMsg}
                  </div>
                )}

                <div>
                  <label htmlFor="mensaje-inconformidad" className="block text-xs font-bold text-gray-800 uppercase tracking-wide mb-1.5">
                    Describe detalladamente el motivo de tu inquietud o la inconsistencia en el pesaje:
                  </label>
                  <textarea
                    id="mensaje-inconformidad"
                    rows={4}
                    required
                    value={mensaje}
                    onChange={(e) => setMensaje(e.target.value)}
                    placeholder="Ej: En la entrega del 18 de octubre en la mañana entregué 45 litros según mi cantina y en la planilla figura 38 litros..."
                    className="w-full px-4 py-3 text-sm border-2 border-tierra-200 rounded-xl focus:outline-none focus:border-tierra-600 focus:ring-4 focus:ring-tierra-100 bg-white resize-y"
                  />
                  <p className="text-[11px] text-gray-500 mt-1">
                    * Al enviar, el sistema notificará automáticamente al correo <strong>asoagroalisal@gmail.com</strong> y bloqueará este formulario para este periodo.
                  </p>
                </div>

                <div className="flex items-center justify-end gap-3 pt-2">
                  <button
                    type="button"
                    onClick={() => setFormAbierto(false)}
                    className="px-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-bold text-sm hover:bg-gray-50"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    disabled={enviando}
                    className="btn-touch bg-tierra-700 hover:bg-tierra-800 disabled:opacity-50 text-white font-bold text-sm px-6 py-2.5 rounded-xl shadow flex items-center gap-2"
                  >
                    <Send className="w-4 h-4 text-dorado-300" />
                    <span>{enviando ? "Registrando y Enviando..." : "Enviar Inquietud a la Administración"}</span>
                  </button>
                </div>
              </form>
            )}
          </>
        )}
      </div>
    </div>
  );
}

