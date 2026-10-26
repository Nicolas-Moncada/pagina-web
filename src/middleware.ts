import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import { jwtVerify } from "jose";

const JWT_SECRET = new TextEncoder().encode(
  process.env.JWT_SECRET || "clave_secreta_para_asociacion_ganaderos_segura_2026_jwt"
);

const COOKIE_NAME = "asociacion_session";

export async function middleware(request: NextRequest) {
  const { pathname } = request.nextUrl;
  const token = request.cookies.get(COOKIE_NAME)?.value;

  let session: { sub: string; cedula: string; rol: "GANADERO" | "ADMIN" } | null = null;

  if (token) {
    try {
      const { payload } = await jwtVerify(token, JWT_SECRET);
      session = {
        sub: payload.sub as string,
        cedula: payload.cedula as string,
        rol: payload.rol as "GANADERO" | "ADMIN",
      };
    } catch {
      session = null;
    }
  }

  // 1. Redirección si ya está autenticado e intenta ir a /login o /registro
  if (pathname === "/login" || pathname === "/registro") {
    if (session) {
      const redirectUrl = session.rol === "ADMIN" ? "/admin" : "/panel";
      return NextResponse.redirect(new URL(redirectUrl, request.url));
    }
    return NextResponse.next();
  }

  // 2. Protección de rutas de administrador /admin
  if (pathname.startsWith("/admin")) {
    if (!session) {
      return NextResponse.redirect(new URL("/login", request.url));
    }
    if (session.rol !== "ADMIN") {
      // Un ganadero no puede ver el panel de admin
      return NextResponse.redirect(new URL("/panel", request.url));
    }
    return NextResponse.next();
  }

  // 3. Protección de rutas del ganadero /panel
  if (pathname.startsWith("/panel")) {
    if (!session) {
      return NextResponse.redirect(new URL("/login", request.url));
    }
    if (session.rol !== "GANADERO") {
      return NextResponse.redirect(new URL("/admin", request.url));
    }
    return NextResponse.next();
  }

  return NextResponse.next();
}

export const config = {
  matcher: ["/admin/:path*", "/panel/:path*", "/login", "/registro"],
};
