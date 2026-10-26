/**
 * Fórmulas exactas del negocio:
 * - Litros del día (usuario) = suma de litros de sus Entregas en una fecha, separadas por turno
 * - Litros de la quincena (usuario) = suma de litros de Entregas del usuario cuya fecha cae entre fechaInicio y fechaFin de esa Quincena
 * - Pago estimado de la quincena (usuario) = litros de la quincena × precioLitro de esa Quincena
 * - Pago del mes (usuario) = suma del pago de las dos quincenas de ese mes
 * - Si el admin aún no definió el precioLitro de la quincena actual, el sistema debe mostrar "precio aún no definido" en vez de calcular con un valor inventado
 */

export interface ResumenDia {
  manana: number;
  tarde: number;
  total: number;
}

export function calcularLitrosDia(entregas: { turno: "MANANA" | "TARDE"; litros: number }[]): ResumenDia {
  let manana = 0;
  let tarde = 0;

  for (const entrega of entregas) {
    if (entrega.turno === "MANANA") {
      manana += entrega.litros;
    } else if (entrega.turno === "TARDE") {
      tarde += entrega.litros;
    }
  }

  return {
    manana: Number(manana.toFixed(2)),
    tarde: Number(tarde.toFixed(2)),
    total: Number((manana + tarde).toFixed(2)),
  };
}

export function calcularLitrosQuincena(entregas: { litros: number }[]): number {
  const total = entregas.reduce((acc, curr) => acc + curr.litros, 0);
  return Number(total.toFixed(2));
}

export interface CalculoPagoQuincena {
  litrosTotales: number;
  precioLitro: number | null;
  pagoEstimado: number | null;
  precioDefinido: boolean;
  textoPrecio: string;
  textoPago: string;
}

export function calcularPagoQuincena(
  litrosTotales: number,
  precioLitro: number | null | undefined
): CalculoPagoQuincena {
  const litros = Number(litrosTotales.toFixed(2));

  if (precioLitro === null || precioLitro === undefined || isNaN(precioLitro)) {
    return {
      litrosTotales: litros,
      precioLitro: null,
      pagoEstimado: null,
      precioDefinido: false,
      textoPrecio: "Precio aún no definido",
      textoPago: "Pendiente de fijar precio",
    };
  }

  const pago = Number((litros * precioLitro).toFixed(2));
  return {
    litrosTotales: litros,
    precioLitro,
    pagoEstimado: pago,
    precioDefinido: true,
    textoPrecio: formatCurrency(precioLitro),
    textoPago: formatCurrency(pago),
  };
}

export function formatCurrency(amount: number): string {
  return new Intl.NumberFormat("es-CO", {
    style: "currency",
    currency: "COP",
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(amount);
}

