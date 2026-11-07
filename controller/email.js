const express = require("express");
const Email = require("../models/Email");
const Logger = require("../models/Logger");

const router = express.Router();
const email = new Email();

router.post("/", async (req, res) => {
  if (req.query.op === "recuperar") {
    const resultado = await email.recuperar(req.body.usu_correo);
    Logger.login("PASSWORD_RESET_REQUESTED", req.body.usu_correo, resultado);
    return res.type("text").send("1");
  }
  return res.type("text").send("");
});

module.exports = router;
