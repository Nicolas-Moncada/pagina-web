"use client";

import { useState } from "react";
import {
  FileSpreadsheet,
  Upload,
  Download,
  CheckCircle2,
  AlertCircle,
  Users,
  Milk,
  FileCheck,
  HelpCircle,
} from "lucide-react";

export default function AdminImportarPage() {
  const [tipo, setTipo] = useState<"GANADEROS" | "ENTREGAS">("GANADEROS");
  const [file, setFile] = useState<File | null>(null);
  const [uploading, setUploading] = useState(false);
  const [resultado, setResultado] = useState<{
    ok: boolean;
    mensaje: string;
    importados?: number;
    errores?: string[];
  } | null>(null);

  // Descarga diferida (lazy load de SheetJS solo al hacer clic)
  const descargarPlantilla = async () => {
    const XLSX = await import("xlsx");
    let wsData: any[] = [];
    let filename = "";

    if (tipo === "GANADEROS") {
      filename = "plantilla_ganaderos_asociacion.xlsx";
      wsData = [
        ["cedula", "nombre", "telefono", "password"],
        ["1098765432", "Carlos Alberto Mendoza", "3101234567", "clave123"],
        ["1087654321", "María Elena Restrepo", "3129876543", ""],
        ["1076543210", "José Vicente Morales", "3154567890", ""],
      ];
    } else {
      filename = "plantilla_entregas_asociacion.xlsx";
      wsData = [
        ["cedula", "fecha", "turno", "litros"],
        ["1098765432", "2026-10-01", "MANANA", 45.5],
        ["1098765432", "2026-10-01", "TARDE", 38.0],
        ["1087654321", "2026-10-01", "MANANA", 62.0],
        ["1087654321", "2026-10-01", "TARDE", 55.5],
      ];
    }

    const ws = XLSX.utils.aoa_to_sheet(wsData);
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, "Plantilla");
    XLSX.writeFile(wb, filename);
  };

  const handleSubir = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!file) {
      alert("Por favor selecciona un archivo de Excel (.xlsx)");
      return;
    }

    setUploading(true);
    setResultado(null);

    const formData = new FormData();
    formData.append("file", file);
    formData.append("tipo", tipo);

    try {
      const res = await fetch("/api/admin/importar", {
        method: "POST",
        body: formData,
      });

      const data = await res.json();

      if (!res.ok) {
        setResultado({
          ok: false,
          mensaje: data.error || "Ocurrió un error al procesar el archivo.",
        });
      } else {
        setResultado(data);
        setFile(null);
      }
    } catch {
      setResultado({
        ok: false,
        mensaje: "Error de conexión al cargar el archivo de Excel.",
      });
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="space-y-8">
      {/* Encabezado */}
      <div>
        <h1 className="text-2xl sm:text-3xl font-extrabold text-campo-950">
          Carga Masiva de Datos desde Excel (.xlsx)
        </h1>
        <p className="text-gray-600 text-sm mt-0.5">
          Sube listados completos de ganaderos o entregas de leche en segundos sin digitar fila por fila.
        </p>
      </div>

      {/* Selector de Tipo */}
      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <button
          type="button"
          onClick={() => {
            setTipo("GANADEROS");
            setResultado(null);
          }}
          className={`p-6 rounded-3xl border-2 text-left transition-all flex items-start gap-4 ${
            tipo === "GANADEROS"
              ? "bg-campo-50 border-campo-600 shadow-md ring-2 ring-campo-600/20"
              : "bg-white border-gray-200 hover:border-campo-300"
          }`}
        >
          <div className={`w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 ${
            tipo === "GANADEROS" ? "bg-campo-700 text-white" : "bg-gray-100 text-gray-600"
          }`}>
            <Users className="w-6 h-6" />
          </div>
          <div>
            <h3 className="font-bold text-lg text-gray-950">1. Importar Ganaderos</h3>
            <p className="text-xs text-gray-600 mt-1">
              Columnas requeridas: <strong>cedula, nombre</strong>. Opcionales: telefono, password.
            </p>
          </div>
        </button>

        <button
          type="button"
          onClick={() => {
            setTipo("ENTREGAS");
            setResultado(null);
          }}
          className={`p-6 rounded-3xl border-2 text-left transition-all flex items-start gap-4 ${
            tipo === "ENTREGAS"
              ? "bg-campo-50 border-campo-600 shadow-md ring-2 ring-campo-600/20"
              : "bg-white border-gray-200 hover:border-campo-300"
          }`}
        >
          <div className={`w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0 ${
            tipo === "ENTREGAS" ? "bg-campo-700 text-white" : "bg-gray-100 text-gray-600"
          }`}>
            <Milk className="w-6 h-6" />
          </div>
          <div>
            <h3 className="font-bold text-lg text-gray-950">2. Importar Entregas de Leche</h3>
            <p className="text-xs text-gray-600 mt-1">
              Columnas requeridas: <strong>cedula, fecha, turno, litros</strong>.
            </p>
          </div>
        </button>
      </div>

      {/* Zona de Descarga de Plantilla y Subida */}
      <div className="bg-white rounded-3xl p-6 sm:p-10 shadow-sm border-2 border-campo-200 space-y-8">
        {/* Paso 1: Descargar plantilla */}
        <div className="bg-campo-50/60 p-5 rounded-2xl border border-campo-200 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-xl bg-campo-200 text-campo-800 flex items-center justify-center flex-shrink-0">
              <Download className="w-5 h-5" />
            </div>
            <div>
              <h4 className="text-base font-bold text-campo-950">
                Descargar Plantilla Oficial de Excel
              </h4>
              <p className="text-xs text-gray-600">
                Usa este formato con los encabezados exactos para evitar errores al importar.
              </p>
            </div>
          </div>
          <button
            type="button"
            onClick={descargarPlantilla}
            className="btn-touch bg-campo-700 hover:bg-campo-800 text-white text-sm font-bold shadow px-5 py-2.5 rounded-xl flex items-center gap-2 whitespace-nowrap"
          >
            <Download className="w-4 h-4" />
            <span>Descargar Plantilla ({tipo === "GANADEROS" ? "Ganaderos" : "Entregas"})</span>
          </button>
        </div>

        {/* Paso 2: Subir archivo */}
        <form onSubmit={handleSubir} className="space-y-6">
          <div className="border-2 border-dashed border-campo-300 hover:border-campo-600 bg-campo-50/20 hover:bg-campo-50/40 rounded-3xl p-8 text-center transition-colors">
            <Upload className="w-12 h-12 text-campo-600 mx-auto mb-3" />
            <h3 className="text-lg font-bold text-campo-950 mb-1">
              Selecciona tu archivo de Excel (.xlsx o .xls)
            </h3>
            <p className="text-xs text-gray-500 mb-4">
              Arrastra el archivo o haz clic en el botón para buscarlo en tu computador
            </p>

            <input
              type="file"
              id="excel-file"
              accept=".xlsx, .xls, .csv"
              onChange={(e) => setFile(e.target.files?.[0] || null)}
              className="hidden"
            />

            <label
              htmlFor="excel-file"
              className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-white border-2 border-campo-300 text-campo-800 font-bold text-sm cursor-pointer hover:bg-campo-50 shadow-sm"
            >
              <FileSpreadsheet className="w-5 h-5 text-campo-700" />
              <span>{file ? file.name : "Examinar archivo Excel"}</span>
            </label>

            {file && (
              <div className="mt-3 text-xs text-campo-700 font-semibold flex items-center justify-center gap-1.5">
                <FileCheck className="w-4 h-4" />
                <span>Archivo listo para procesar: {file.name} ({(file.size / 1024).toFixed(1)} KB)</span>
              </div>
            )}
          </div>

          <button
            type="submit"
            disabled={!file || uploading}
            className="w-full btn-touch bg-campo-700 hover:bg-campo-800 disabled:opacity-50 text-white font-bold text-lg shadow-lg flex items-center justify-center gap-2"
          >
            {uploading ? (
              <span>Procesando e importando datos a la base de datos...</span>
            ) : (
              <>
                <Upload className="w-5 h-5 text-campo-200" />
                <span>Iniciar Importación a MySQL</span>
              </>
            )}
          </button>
        </form>

        {/* Resultados de la importación */}
        {resultado && (
          <div className={`p-6 rounded-2xl border-2 space-y-3 ${
            resultado.ok
              ? "bg-green-50 border-campo-500 text-campo-950"
              : "bg-red-50 border-red-500 text-red-950"
          }`}>
            <div className="flex items-start gap-3">
              {resultado.ok ? (
                <CheckCircle2 className="w-7 h-7 text-campo-600 flex-shrink-0 mt-0.5" />
              ) : (
                <AlertCircle className="w-7 h-7 text-red-600 flex-shrink-0 mt-0.5" />
              )}
              <div>
                <h4 className="text-lg font-bold">
                  {resultado.ok ? "¡Importación Exitosa!" : "Hubo detalles en la importación"}
                </h4>
                <p className="text-sm font-medium mt-0.5">{resultado.mensaje}</p>
              </div>
            </div>

            {resultado.errores && resultado.errores.length > 0 && (
              <div className="mt-4 pt-3 border-t border-gray-200">
                <p className="text-xs font-bold uppercase tracking-wider text-red-700 mb-2">
                  Filas que requirieron atención ({resultado.errores.length}):
                </p>
                <ul className="text-xs space-y-1 list-disc pl-5 max-h-48 overflow-y-auto text-red-800">
                  {resultado.errores.map((err, idx) => (
                    <li key={idx}>{err}</li>
                  ))}
                </ul>
              </div>
            )}
          </div>
        )}
      </div>
    </div>
  );
}

