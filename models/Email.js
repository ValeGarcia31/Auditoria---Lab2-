const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const nodemailer = require("nodemailer");
const Usuario = require("./Usuario");

class Email {
  constructor() {
    this.transporter = nodemailer.createTransport({
      host: process.env.SMTP_HOST,
      port: Number(process.env.SMTP_PORT),
      secure: process.env.SMTP_SECURE === "true",
      auth: process.env.SMTP_AUTH === "true" ? {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASS
      } : undefined
    });
  }

  async registrar(usuId) {
    const datos = (await new Usuario().getUsuarioId(usuId))[0];
    if (!datos) return false;
    const url = `${process.env.BASE_URL}confirmar?id=${encodeURIComponent(usuId)}`;
    const body = fs.readFileSync(path.join(__dirname, "../assets/email/registrar.html"), "utf8")
      .replace("xlinkcorreourl", url);
    try {
      await this.transporter.sendMail({
        from: `"${process.env.SMTP_FROM_NAME}" <${process.env.SMTP_FROM_EMAIL}>`,
        to: datos.usu_correo,
        subject: "Seguridad Informática I",
        html: body
      });
      return true;
    } catch (error) {
      console.error("Error al enviar correo de registro:", error.message);
      return false;
    }
  }

  async recuperar(usuCorreo) {
    const usuario = new Usuario();
    const datos = await usuario.getUsuarioRecuperacion(usuCorreo);
    if (!datos || await usuario.recuperacionEnLimite(datos.usu_id)) return "NOT_FOUND_OR_INACTIVE";

    const token = crypto.randomBytes(32).toString("hex");
    const tokenHash = Usuario.hashToken(token);
    await usuario.crearRecuperacion(datos.usu_id, tokenHash);
    const url = `${process.env.BASE_URL}restablecer?token=${token}`;
    const body = fs.readFileSync(path.join(__dirname, "../assets/email/recuperar.html"), "utf8")
      .replace("xlinkrestablecer", url);

    try {
      await this.transporter.sendMail({
        from: `"${process.env.SMTP_FROM_NAME}" <${process.env.SMTP_FROM_EMAIL}>`,
        to: datos.usu_correo,
        subject: "Seguridad Informática I",
        html: body,
        text: "Restablece tu contraseña desde el enlace recibido."
      });
      return "SENT";
    } catch (error) {
      console.error("Error al enviar correo de recuperación:", error.message);
      await usuario.cancelarRecuperacion(tokenHash);
      return "MAIL_ERROR";
    }
  }
}

module.exports = Email;
