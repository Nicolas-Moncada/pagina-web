"use client";

import { useState, useEffect } from "react";
import { MessageSquare, CheckCircle, Clock, Trash2, ShieldAlert, User, Phone, Calendar, Mail, CheckCircle2 } from "lucide-react";
import { formatDateSpanish } from "@/lib/date-utils";

interface ConsultaItem {
  id: string;
  nombre: string;
  contacto: string;
  mensaje: string;
  fecha: string;
  atendida: boolean;
}

interface AlertaGanaderoItem {
  id: string;
  usuarioId: string;
  fechaInicio: string;
  fechaFin: string;
  mensaje: string;
  litrosAlMomento: number;
  estado: string;
  correoEnviado: boolean;
  creadoEn: string;
  usuario: {
    id: string;
    nombre: string;
    cedula: string;
    telefono: string | null;
  };
}

export default function AdminConsultasPage() {
  const [tab, setTab] = useState<"consultas" | "alertas">("consultas");
  const [consultas, setConsultas] = useState<ConsultaItem[]>([]);
  const [alertas, setAlertas] = useState<AlertaGanaderoItem[]>([]);
  const [loading, setLoading] = useState(true);

  const cargarDatos = () => {
    setLoading(true);
    Promise.all([
      fetch("/api/admin/consultas").then((r) => r.json()),
      fetch("/api/admin/alertas").then((r) => r.json()),
    ])
      .then(([dataConsultas, dataAlertas]) => {
        if (dataConsultas.consultas) setConsultas(dataConsultas.consultas);
        if (dataAlertas.alertas) setAlertas(dataAlertas.alertas);
      })
      .catch(console.error)
      .finally(() => setLoading(false));
  };

  useEffect(() => {
    cargarDatos();
  }, []);

  const handleToggleAtendida = async (c: ConsultaItem) => {
    try {
      const res = await fetch(`/api/admin/consultas/${c.id}`, {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ atendida: !c.atendida }),
      });
      if (res.ok) {
        cargarDatos();
      }
    } catch {
      alert("Error al actualizar estado");
    }
  };

  const handleToggleEstadoAlerta = async (a: AlertaGanaderoItem) => {
    const nuevoEstado = a.estado === "PENDIENTE" ? "REVISADO" : a.estado === "REVISADO" ? "RESUELTO" : "PENDIENTE";
    try {
      const res = await fetch("/api/admin/alertas", {
        method: "PATCH",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id: a.id, estado: nuevoEstado }),
      });
      if (res.ok) {
        cargarDatos();
      }
    } catch {
      alert("Error al actualizar estado de la alerta");
    }
  };

  const handleEliminarConsulta = async (id: string) => {
    if (!confirm("¿Deseas eliminar este mensaje?")) return;
    try {
      const res = await fetch(`/api/admin/consultas/${id}`, { method: "DELETE" });
      if (res.ok) {
        cargarDatos();
      }
    } catch {
      alert("Error al eliminar mensaje");
    }
  };

  const pendientesConsultas = consultas.filter((c) => !c.atendida).length;
  const pendientesAlertas = alertas.filter((a) => a.estado === "PENDIENTE").length;

  return (
    <div className="space-y-8">
      {/* Encabezado */}
      <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl sm:text-3xl font-extrabold text-campo-950">
            Buzón de Mensajes e Inconformidades
          </h1>
          <p className="text-gray-600 text-sm mt-0.5">
            Gestiona consultas ciudadanas de la web y reclamos de pesaje de los ganaderos asociados.
          </p>
        </div>

        {/* Pestañas de Navegación */}
        <div className="flex items-center gap-2 bg-gray-200 p-1.5 rounded-2xl w-full sm:w-auto">
          <button
            onClick={() => setTab("consultas")}
            className={`flex-1 sm:flex-initial px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all ${
              tab === "consultas"
                ? "bg-white text-campo-950 shadow-sm"
                : "text-gray-600 hover:text-gray-900"
            }`}
          >
            <MessageSquare className="w-4 h-4" />
            <span>Consultas Web ({pendientesConsultas})</span>
          </button>
          <button
            onClick={() => setTab("alertas")}
            className={`flex-1 sm:flex-initial px-4 py-2 rounded-xl text-xs sm:text-sm font-bold flex items-center justify-center gap-2 transition-all ${
              tab === "alertas"
                ? "bg-campo-700 text-white shadow-sm"
                : "text-gray-600 hover:text-gray-900"
            }`}
          >
            <ShieldAlert className="w-4 h-4 text-dorado-400" />
            <span>Inconformidades Ganaderos ({pendientesAlertas})</span>
          </button>
        </div>
      </div>

      {loading ? (
        <div className="bg-white rounded-3xl p-12 text-center text-gray-500 border border-gray-200">
          Cargando buzón de mensajes...
        </div>
      ) : tab === "consultas" ? (
        /* PESTAÑA 1: CONSULTAS WEB PÚBLICAS */
        consultas.length === 0 ? (
          <div className="bg-white rounded-3xl p-12 text-center text-gray-500 border border-gray-200">
            <div className="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3 text-gray-400">
              <MessageSquare className="w-8 h-8" />
            </div>
            <h2 className="text-lg font-bold text-gray-800">No hay consultas en el buzón web</h2>
            <p className="text-sm text-gray-500 mt-1">
              Los mensajes enviados desde el formulario público aparecerán aquí.
            </p>
          </div>
        ) : (
          <div className="space-y-4">
            {consultas.map((item) => (
              <div
                key={item.id}
                className={`bg-white rounded-2xl p-6 shadow-sm border-2 transition-all ${
                  item.atendida ? "border-gray-200 opacity-80" : "border-campo-300 shadow"
                }`}
              >
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 mb-4 pb-3 border-b border-gray-100">
                  <div className="flex items-center gap-3">
                    <div className={`w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm ${
                      item.atendida ? "bg-gray-200 text-gray-700" : "bg-campo-100 text-campo-800"
                    }`}>
                      <User className="w-5 h-5" />
                    </div>
                    <div>
                      <h3 className="font-bold text-gray-950 text-base">{item.nombre}</h3>
                      <p className="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                        <span className="font-semibold text-campo-700">{item.contacto}</span>
                        <span>•</span>
                        <span>{new Date(item.fecha).toLocaleString("es-CO")}</span>
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center gap-2 self-end sm:self-auto">
                    <button
                      onClick={() => handleToggleAtendida(item)}
                      className={`px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors ${
                        item.atendida
                          ? "bg-green-100 text-green-800 hover:bg-green-200"
                          : "bg-amber-100 text-amber-900 hover:bg-amber-200 border border-amber-300"
                      }`}
                    >
                      {item.atendida ? (
                        <>
                          <CheckCircle className="w-3.5 h-3.5 text-green-700" />
                          <span>Atendida</span>
                        </>
                      ) : (
                        <>
                          <Clock className="w-3.5 h-3.5 text-amber-700" />
                          <span>Marcar Atendida</span>
                        </>
                      )}
                    </button>
                    <button
                      onClick={() => handleEliminarConsulta(item.id)}
                      className="p-2 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors"
                      title="Eliminar consulta"
                    >
                      <Trash2 className="w-4 h-4" />
                    </button>
                  </div>
                </div>

                <div className="bg-gray-50 p-4 rounded-xl text-gray-800 text-base leading-relaxed">
                  "{item.mensaje}"
                </div>
              </div>
            ))}
          </div>
        )
      ) : (
        /* PESTAÑA 2: INCONFORMIDADES DE QUINCENA ENVIADAS POR GANADEROS */
        alertas.length === 0 ? (
          <div className="bg-white rounded-3xl p-12 text-center text-gray-500 border border-gray-200">
            <div className="w-16 h-16 rounded-full bg-campo-50 flex items-center justify-center mx-auto mb-3 text-campo-600">
              <CheckCircle2 className="w-8 h-8" />
            </div>
            <h2 className="text-lg font-bold text-gray-800">No hay inconformidades registradas</h2>
            <p className="text-sm text-gray-500 mt-1">
              Cuando un ganadero reporte alguna duda sobre el pesaje de su quincena, se listará aquí.
            </p>
          </div>
        ) : (
          <div className="space-y-4">
            {alertas.map((item) => (
              <div
                key={item.id}
                className="bg-white rounded-2xl p-6 shadow-sm border-2 border-tierra-300 space-y-4"
              >
                <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pb-3 border-b border-gray-100">
                  <div className="flex items-center gap-3">
                    <div className="w-12 h-12 rounded-2xl bg-tierra-100 text-tierra-800 flex items-center justify-center font-bold text-base flex-shrink-0">
                      <User className="w-6 h-6" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <h3 className="font-bold text-gray-950 text-lg">{item.usuario.nombre}</h3>
                        <span className="text-xs font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded">
                          C.C. {item.usuario.cedula}
                        </span>
                      </div>
                      <p className="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                        <span>Teléfono: <strong>{item.usuario.telefono || "Sin registrar"}</strong></span>
                        <span>•</span>
                        <span>Fecha: {new Date(item.creadoEn).toLocaleString("es-CO")}</span>
                      </p>
                    </div>
                  </div>

                  {/* Estado y Envío de Correo */}
                  <div className="flex flex-wrap items-center gap-2">
                    {item.correoEnviado ? (
                      <span className="inline-flex items-center gap-1 text-[11px] font-bold text-green-800 bg-green-100 px-2.5 py-1 rounded-full">
                        <Mail className="w-3 h-3 text-green-700" />
                        Correo enviado a asoagroalisal@gmail.com
                      </span>
                    ) : (
                      <span className="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 bg-amber-100 px-2.5 py-1 rounded-full">
                        <Clock className="w-3 h-3 text-amber-700" />
                        Guardado en BD (Pendiente SMTP)
                      </span>
                    )}

                    <button
                      onClick={() => handleToggleEstadoAlerta(item)}
                      className={`px-3 py-1.5 rounded-xl text-xs font-bold flex items-center gap-1.5 transition-colors ${
                        item.estado === "RESUELTO"
                          ? "bg-green-100 text-green-800 hover:bg-green-200"
                          : item.estado === "REVISADO"
                          ? "bg-blue-100 text-blue-800 hover:bg-blue-200"
                          : "bg-amber-100 text-amber-900 hover:bg-amber-200 border border-amber-300"
                      }`}
                      title="Haz clic para cambiar estado (Pendiente -> Revisado -> Resuelto)"
                    >
                      <span>Estado: {item.estado}</span>
                    </button>
                  </div>
                </div>

                {/* Datos del Periodo y Litros */}
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-tierra-50/60 p-4 rounded-xl border border-tierra-200 text-xs">
                  <div>
                    <span className="text-gray-500 uppercase font-bold block">Periodo Quincenal en Disputa:</span>
                    <strong className="text-sm text-tierra-950 font-black">
                      {formatDateSpanish(item.fechaInicio)} al {formatDateSpanish(item.fechaFin)}
                    </strong>
                  </div>
                  <div>
                    <span className="text-gray-500 uppercase font-bold block">Litros Acumulados al Momento del Reclamo:</span>
                    <strong className="text-sm text-campo-900 font-black">
                      {item.litrosAlMomento.toFixed(1)} Litros
                    </strong>
                  </div>
                </div>

                {/* Mensaje de la inconformidad */}
                <div className="bg-gray-50 p-4 rounded-xl text-gray-800 text-sm leading-relaxed border-l-4 border-tierra-600">
                  <p className="text-xs font-bold text-tierra-900 uppercase tracking-wide mb-1">
                    Motivo expresado por el ganadero:
                  </p>
                  "{item.mensaje}"
                </div>
              </div>
            ))}
          </div>
        )
      )}
    </div>
  );
}
