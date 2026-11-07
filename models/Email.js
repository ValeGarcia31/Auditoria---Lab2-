const fs = require("fs");
const path = require("path");
const crypto = require("crypto");
const nodemailer = require("nodemailer");
const config = require("../config/config.json");
const Usuario = require("./Usuario");

class Email {
  constructor() {
    this.transporter = nodemailer.createTransport({
      host: config.smtp.host,
      port: config.smtp.port,
      secure: config.smtp.secure,
      auth: config.smtp.auth ? {
        user: config.smtp.user,
        pass: config.smtp.pass
      } : undefined
    });
  }

  async registrar(usuId) {
    const datos = (await new Usuario().getUsuarioId(usuId))[0];
    if (!datos) return false;
    const url = `${config.base_url}confirmar?id=${encodeURIComponent(usuId)}`;
    const body = fs.readFileSync(path.join(__dirname, "../assets/email/registrar.html"), "utf8")
      .replace("xlinkcorreourl", url);
    try {
      await this.transporter.sendMail({
        from: `"${config.smtp_from_name}" <${config.smtp_from_email}>`,
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
    const url = `${config.base_url}restablecer?token=${token}`;
    const body = fs.readFileSync(path.join(__dirname, "../assets/email/recuperar.html"), "utf8")
      .replace("xlinkrestablecer", url);

    try {
      await this.transporter.sendMail({
        from: `"${config.smtp_from_name}" <${config.smtp_from_email}>`,
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
