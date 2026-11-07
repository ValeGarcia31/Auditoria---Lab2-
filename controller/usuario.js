const express = require("express");
const Usuario = require("../models/Usuario");
const Email = require("../models/Email");
const Logger = require("../models/Logger");

const router = express.Router();
const usuario = new Usuario();
const email = new Email();

router.post("/", async (req, res, next) => {
  try {
    const op = req.query.op;

    if (op === "registrar") {
      const datos = await usuario.getUsuarioCorreo(req.body.usu_correo);
      if (datos.length === 0) {
        const datos1 = await usuario.registrarUsuario(
          req.body.usu_nomape, req.body.usu_correo, req.body.usu_pass, "", 2
        );
        await email.registrar(datos1[0].usu_id);
        Logger.login("REGISTER", req.body.usu_correo, "NEW_ACCOUNT");
        return res.type("text").send("1");
      }
      Logger.login("REGISTER", req.body.usu_correo, "EMAIL_EXISTS");
      return res.type("text").send("0");
    }

    if (op === "activar") {
      await usuario.activarUsuario(req.body.usu_id);
      return res.type("text").send("");
    }

    if (op === "restablecer") {
      const token = req.body.token || "";
      const pass = req.body.usu_pass || "";
      const confirmacion = req.body.usu_pass_confir || "";
      if (pass.length < 8 || pass.length > 72 || pass !== confirmacion) return res.type("text").send("2");
      const tokenHash = Usuario.hashToken(token);
      if (!(await usuario.getRecuperacionValida(tokenHash))) return res.type("text").send("0");
      return res.type("text").send((await usuario.restablecerRecuperacion(tokenHash, pass)) ? "1" : "0");
    }

    return res.type("text").send("");
  } catch (error) {
    return next(error);
  }
});

module.exports = router;
