import bcrypt from "bcryptjs";
import { SignJWT, jwtVerify } from "jose";

export interface SessionPayload {
  sub: string;
  cedula: string;
  nombre: string;
  rol: "GANADERO" | "ADMIN";
}

const JWT_SECRET = new TextEncoder().encode(
  process.env.JWT_SECRET || "clave_secreta_para_asociacion_ganaderos_segura_2026_jwt"
);

export const COOKIE_NAME = "asociacion_session";

export async function hashPassword(plain: string): Promise<string> {
  const salt = await bcrypt.genSalt(10);
  return bcrypt.hash(plain, salt);
}

export async function verifyPassword(plain: string, hash: string): Promise<boolean> {
  return bcrypt.compare(plain, hash);
}

export async function createSessionToken(payload: SessionPayload): Promise<string> {
  return new SignJWT({ ...payload })
    .setProtectedHeader({ alg: "HS256" })
    .setIssuedAt()
    .setExpirationTime("7d")
    .sign(JWT_SECRET);
}

export async function verifySessionToken(token: string): Promise<SessionPayload | null> {
  try {
    const { payload } = await jwtVerify(token, JWT_SECRET);
    return {
      sub: payload.sub as string,
      cedula: payload.cedula as string,
      nombre: payload.nombre as string,
      rol: payload.rol as "GANADERO" | "ADMIN",
    };
  } catch {
    return null;
  }
}

