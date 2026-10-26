import { cookies } from "next/headers";
import { COOKIE_NAME, SessionPayload, verifySessionToken } from "./auth";
import { redirect } from "next/navigation";

export async function getCurrentSession(): Promise<SessionPayload | null> {
  const cookieStore = cookies();
  const token = cookieStore.get(COOKIE_NAME)?.value;
  if (!token) return null;
  return verifySessionToken(token);
}

export async function requireSession(allowedRole?: "ADMIN" | "GANADERO"): Promise<SessionPayload> {
  const session = await getCurrentSession();

  if (!session) {
    redirect("/login");
  }

  if (allowedRole && session.rol !== allowedRole) {
    if (session.rol === "ADMIN") {
      redirect("/admin");
    } else {
      redirect("/panel");
    }
  }

  return session;
}

