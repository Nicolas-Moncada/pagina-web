import { z } from "zod";

export const loginSchema = z.object({
  cedula: z
    .string({ required_error: "La cédula es obligatoria" })
    .trim()
    .min(3, "La cédula debe tener al menos 3 dígitos"),
  password: z
    .string({ required_error: "La contraseña es obligatoria" })
    .min(1, "Ingresa tu contraseña"),
});

export const registroGanaderoSchema = z
  .object({
    cedula: z
      .string({ required_error: "La cédula es obligatoria" })
      .trim()
      .min(3, "La cédula debe tener al menos 3 dígitos")
      .regex(/^\d+$/, "La cédula solo debe contener números, sin puntos ni comas"),
    nombre: z
      .string({ required_error: "El nombre es obligatorio" })
      .trim()
      .min(3, "El nombre debe tener al menos 3 caracteres"),
    telefono: z
      .string()
      .trim()
      .min(7, "El teléfono debe tener al menos 7 dígitos")
      .optional()
      .or(z.literal("")),
    password: z
      .string({ required_error: "La contraseña es obligatoria" })
      .min(4, "La contraseña debe tener al menos 4 caracteres"),
    confirmPassword: z
      .string({ required_error: "Confirma tu contraseña" })
      .min(1, "Debes confirmar tu contraseña"),
  })
  .refine((data) => data.password === data.confirmPassword, {
    message: "Las contraseñas no coinciden. Verifícalas con cuidado.",
    path: ["confirmPassword"],
  });

export const usuarioSchema = z.object({
  cedula: z
    .string({ required_error: "La cédula es requerida" })
    .trim()
    .min(3, "La cédula debe tener al menos 3 dígitos"),
  nombre: z
    .string({ required_error: "El nombre es requerido" })
    .trim()
    .min(2, "El nombre debe tener al menos 2 caracteres"),
  telefono: z.string().trim().optional().nullable(),
  password: z
    .string()
    .min(4, "La contraseña debe tener al menos 4 caracteres")
    .optional()
    .or(z.literal("")),
  rol: z.enum(["GANADERO", "ADMIN"]).default("GANADERO"),
  activo: z.boolean().default(true),
});

export const entregaSchema = z.object({
  usuarioId: z.string({ required_error: "El ganadero es requerido" }).min(1),
  fecha: z
    .string({ required_error: "La fecha es requerida" })
    .regex(/^\d{4}-\d{2}-\d{2}$/, "Formato de fecha inválido (debe ser AAAA-MM-DD)"),
  turno: z.enum(["MANANA", "TARDE"], {
    required_error: "Debes seleccionar un turno (MANANA o TARDE)",
  }),
  litros: z.coerce
    .number({ invalid_type_error: "Los litros deben ser un número válido" })
    .positive("La cantidad de litros debe ser mayor a 0")
    .max(5000, "La cantidad ingresada supera el límite razonable diario (5000 L)"),
});

export const quincenaSchema = z.object({
  fechaInicio: z
    .string()
    .regex(/^\d{4}-\d{2}-\d{2}$/, "Formato de fecha de inicio inválido"),
  fechaFin: z
    .string()
    .regex(/^\d{4}-\d{2}-\d{2}$/, "Formato de fecha de fin inválido"),
  precioLitro: z.coerce
    .number({ invalid_type_error: "El precio debe ser un número" })
    .nonnegative("El precio no puede ser negativo")
    .optional()
    .nullable(),
  cerrada: z.boolean().default(false),
});

export const consultaSchema = z.object({
  nombre: z
    .string({ required_error: "Por favor ingresa tu nombre" })
    .trim()
    .min(2, "El nombre debe tener al menos 2 letras"),
  contacto: z
    .string({ required_error: "Por favor ingresa un teléfono o correo de contacto" })
    .trim()
    .min(5, "Ingresa un número de teléfono o correo válido"),
  mensaje: z
    .string({ required_error: "Por favor escribe tu mensaje o consulta" })
    .trim()
    .min(5, "El mensaje debe tener al menos 5 caracteres"),
});
