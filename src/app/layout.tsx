import type { Metadata, Viewport } from "next";
import { Inter } from "next/font/google";
import "./globals.css";

const inter = Inter({
  subsets: ["latin"],
  display: "swap",
  variable: "--font-inter",
});

export const viewport: Viewport = {
  width: "device-width",
  initialScale: 1,
  themeColor: "#2d5737",
};

export const metadata: Metadata = {
  title: "Asociación de Ganaderos | Plataforma Oficial de Control Lechero",
  description: "Sistema oficial de gestión, acopio lechero y liquidaciones quincenales para ganaderos asociados.",
  robots: "index, follow",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="es" className={inter.variable} suppressHydrationWarning>
      <body className="min-h-screen flex flex-col font-sans bg-leche-100 text-campo-950 selection:bg-campo-200 antialiased">
        {children}
      </body>
    </html>
  );
}
