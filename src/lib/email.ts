import nodemailer from "nodemailer";

interface AlertaEmailPayload {
  nombreGanadero: string;
  cedula: string;
  periodoQuincena: string;
  litrosAlMomento: number;
  mensaje: string;
  fechaHora: string;
}

const ADMIN_EMAIL = "asoagroalisal@gmail.com";

export async function enviarCorreoAlertaQuincena(
  data: AlertaEmailPayload
): Promise<{ sent: boolean; messageId?: string; error?: string }> {
  const smtpUser = process.env.SMTP_USER || process.env.EMAIL_USER;
  const smtpPass = process.env.SMTP_PASS || process.env.EMAIL_PASS;

  // Si no se han configurado aún las credenciales de correo en .env, loguear y continuar de forma segura
  if (!smtpUser || !smtpPass) {
    console.warn(
      "⚠️ [Email Service]: SMTP_USER o SMTP_PASS no configurados en variables de entorno. Alerta guardada en BD sin envío de correo saliente."
    );
    return {
      sent: false,
      error: "Credenciales SMTP no configuradas (alerta guardada en BD)",
    };
  }

  try {
    const transporter = nodemailer.createTransport({
      service: "gmail",
      auth: {
        user: smtpUser,
        pass: smtpPass,
      },
    });

    const htmlContent = `
      <!DOCTYPE html>
      <html lang="es">
      <head>
        <meta charset="utf-8">
        <style>
          body { font-family: Arial, sans-serif; background-color: #f7f5ed; margin: 0; padding: 20px; color: #203a27; }
          .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 2px solid #99c6a4; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); }
          .header { background: #2d5737; color: #ffffff; padding: 24px; text-align: center; }
          .header h1 { margin: 0; font-size: 20px; letter-spacing: 0.5px; }
          .badge { display: inline-block; background: #d97706; color: #ffffff; font-weight: bold; font-size: 11px; padding: 4px 12px; border-radius: 12px; text-transform: uppercase; margin-bottom: 8px; }
          .content { padding: 24px; }
          .data-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
          .data-table td { padding: 10px 12px; border-bottom: 1px solid #e1efe4; font-size: 14px; }
          .data-table td.label { font-weight: bold; color: #2d5737; width: 40%; }
          .data-table td.value { color: #111827; }
          .message-box { background: #f2f8f3; border-left: 4px solid #366e43; padding: 16px; border-radius: 0 8px 8px 0; font-size: 14px; line-height: 1.6; color: #1f2937; margin-top: 10px; }
          .footer { background: #fcfbf7; border-top: 1px solid #e1efe4; padding: 16px; text-align: center; font-size: 12px; color: #6b7280; }
        </style>
      </head>
      <body>
        <div class="container">
          <div class="header">
            <span class="badge">Inconformidad de Pesaje</span>
            <h1>Asociación de Ganaderos</h1>
            <p style="margin: 4px 0 0; font-size: 13px; color: #c4dfcb;">Alerta Registrada por Ganadero Asociado</p>
          </div>
          <div class="content">
            <table class="data-table">
              <tr>
                <td class="label">Ganadero:</td>
                <td class="value"><strong>${data.nombreGanadero}</strong></td>
              </tr>
              <tr>
                <td class="label">Documento / Cédula:</td>
                <td class="value">${data.cedula}</td>
              </tr>
              <tr>
                <td class="label">Periodo de la Quincena:</td>
                <td class="value"><strong>${data.periodoQuincena}</strong></td>
              </tr>
              <tr>
                <td class="label">Litros Acumulados al Momento:</td>
                <td class="value"><strong style="color: #2d5737; font-size: 16px;">${data.litrosAlMomento.toFixed(1)} Litros</strong></td>
              </tr>
              <tr>
                <td class="label">Fecha y Hora de la Alerta:</td>
                <td class="value">${data.fechaHora}</td>
              </tr>
            </table>

            <h3 style="color: #2d5737; margin: 16px 0 6px; font-size: 15px;">Detalle de la Inconformidad:</h3>
            <div class="message-box">
              "${data.mensaje.replace(/\n/g, "<br>")}"
            </div>
          </div>
          <div class="footer">
            Este es un correo automático generado por el Sistema de Control Lechero de la Asociación.
          </div>
        </div>
      </body>
      </html>
    `;

    const info = await transporter.sendMail({
      from: `"Sistema Control Lechero" <${smtpUser}>`,
      to: ADMIN_EMAIL,
      subject: `⚠️ Inconformidad de Litros - ${data.nombreGanadero} (C.C. ${data.cedula}) - ${data.periodoQuincena}`,
      html: htmlContent,
    });

    return { sent: true, messageId: info.messageId };
  } catch (error: any) {
    console.error("❌ Error al enviar correo de alerta con Nodemailer:", error);
    return { sent: false, error: error.message || "Error al enviar correo" };
  }
}

