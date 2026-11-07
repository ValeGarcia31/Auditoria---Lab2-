const express = require("express");
const Email = require("../models/Email");
const Logger = require("../models/Logger");
const { isValidEmail } = require("../utils/validation");

const router = express.Router();
const email = new Email();

router.post("/", async (req, res, next) => {
  try {
    if (req.query.op === "recuperar") {
      const correo = req.body.usu_correo || "";
      if (!isValidEmail(correo)) return res.type("text").send("2");
      const resultado = await email.recuperar(correo);
      Logger.login("PASSWORD_RESET_REQUESTED", correo, resultado);
      return res.type("text").send("1");
    }
    return res.type("text").send("");
  } catch (error) {
    return next(error);
  }
});

module.exports = router;
