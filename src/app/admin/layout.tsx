import { requireSession } from "@/lib/session";
import AdminNavbar from "@/components/admin/AdminNavbar";

export default async function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const session = await requireSession("ADMIN");

  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <AdminNavbar nombreAdmin={session.nombre} />
      <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {children}
      </main>
      <footer className="py-6 text-center text-xs text-gray-500 border-t border-gray-200 bg-white">
        Panel de Administración • Sistema de Liquidación y Control Lechero &copy; {new Date().getFullYear()}
      </footer>
    </div>
  );
}

