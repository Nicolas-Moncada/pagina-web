import { requireSession } from "@/lib/session";
import GanaderoHeader from "@/components/ganadero/GanaderoHeader";

export default async function PanelLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const session = await requireSession("GANADERO");

  return (
    <div className="min-h-screen bg-leche-200/60 flex flex-col">
      <GanaderoHeader nombre={session.nombre} cedula={session.cedula} />
      <main className="flex-1 max-w-5xl w-full mx-auto px-4 sm:px-6 py-6 sm:py-10">
        {children}
      </main>
      <footer className="py-6 text-center text-xs text-gray-500 border-t border-campo-200 bg-white">
        Asociación de Ganaderos &copy; {new Date().getFullYear()} • Plataforma de Control Lechero
      </footer>
    </div>
  );
}

