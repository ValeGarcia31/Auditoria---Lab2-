require("dotenv").config();

const path = require("path");
const express = require("express");
const session = require("express-session");
const helmet = require("helmet");
const multer = require("multer");
const Usuario = require("./models/Usuario");
const Logger = require("./models/Logger");
const { isValidEmail, isValidPassword } = require("./utils/validation");

const app = express();
const port = process.env.PORT || 3000;

app.set("view engine", "ejs");
app.set("views", path.join(__dirname, "view"));

// Confiar en proxy (útil en algunos entornos)
app.enable("trust proxy");

// Evitar que en desarrollo se fuerce HTTPS
app.use((req, res, next) => {
  if (req.secure && process.env.NODE_ENV !== "production") {
    return res.redirect("http://" + req.headers.host + req.url);
  }
  next();
});

app.use(helmet({
  contentSecurityPolicy: {
    directives: {
      defaultSrc: ["'self'"],
      styleSrc: ["'self'", "'unsafe-inline'", "https://cdn.jsdelivr.net", "https://cdnjs.cloudflare.com", "https://fonts.googleapis.com"],
      scriptSrc: ["'self'", "'unsafe-inline'", "https://cdn.jsdelivr.net", "https://cdnjs.cloudflare.com"],
      fontSrc: ["'self'", "https://fonts.gstatic.com", "https://cdnjs.cloudflare.com"],
      imgSrc: ["'self'", "data:", "https:"],
      connectSrc: ["'self'"],
    },
  },
  strictTransportSecurity: false, // Desactivado para entorno local (HTTP)
}));

app.use(express.urlencoded({ extended: true }));
app.use(multer().any());

app.use(session({
  secret: process.env.SESSION_SECRET,
  resave: false,
  saveUninitialized: false,
  name: "sesion_app",
  cookie: {
    httpOnly: true,
    sameSite: "lax",
    secure: process.env.NODE_ENV === "production",
    maxAge: 1000 * 60 * 30,
    expires: new Date(Date.now() + 1000 * 60 * 30),
    path: "/"
  }
}));

app.use("/assets", express.static(path.join(__dirname, "assets")));
app.use("/view", express.static(path.join(__dirname, "view")));

function render(res, template, data = {}) {
  res.set("Content-Type", "text/html; charset=utf-8");
  return res.render(template, data);
}

app.get(["/", "/login", "/index.php"], (req, res) => render(res, "login", { message: req.query.m || "" }));

app.post(["/", "/login", "/index.php"], async (req, res) => {
  const correo = req.body.usu_correo || "";
  const pass = req.body.usu_pass || "";
  if (!isValidEmail(correo) || !isValidPassword(pass)) {
    Logger.login("LOGIN_FAILED", correo, "EMPTY_FIELDS", req.ip);
    return res.redirect("/login?m=2");
  }

  try {
    const resultado = await new Usuario().login(correo, pass, req.ip);
    if (!resultado) return res.redirect("/login?m=1");
    if (resultado.est !== 1) return res.redirect("/login?m=4");
    req.session.regenerate((error) => {
      if (error) {
        console.error("Error al regenerar sesión:", error);
        return res.status(500).send("Ocurrió un error, intenta más tarde");
      }
      req.session.usu_id = resultado.usu_id;
      req.session.usu_nomape = resultado.usu_nomape;
      req.session.usu_correo = resultado.usu_correo;
      return res.redirect("/home");
    });
  } catch (error) {
    console.error("Error en login:", error);
    return res.status(500).send("Ocurrió un error, intenta más tarde");
  }
});

app.get(["/register", "/view/registro", "/view/registro/index.php"], (req, res) => render(res, "registro"));
app.get(["/recuperar", "/view/recuperar", "/view/recuperar/index.php"], (req, res) => render(res, "recuperar"));
app.get(["/restablecer", "/view/restablecer", "/view/restablecer/index.php"], async (req, res) => {
  try {
    const token = String(req.query.token || "");
    const valid = /^[a-f0-9]{64}$/i.test(token)
      ? await new Usuario().getRecuperacionValida(Usuario.hashToken(token))
      : false;
    return render(res, "restablecer", { token, recuperacionValida: Boolean(valid) });
  } catch (error) {
    console.error("Error al validar recuperación:", error);
    return res.status(500).send("Ocurrió un error, intenta más tarde");
  }
});

app.use(["/controller/usuario", "/controller/usuario.php"], require("./controller/usuario"));
app.use(["/controller/email", "/controller/email.php"], require("./controller/email"));

app.get(["/confirmar", "/view/confirmar", "/view/confirmar/index.php"], async (req, res, next) => {
  try {
    const id = String(req.query.id || "");
    if (id !== "") await new Usuario().activarUsuario(id);
    return render(res, "confirmar");
  } catch (error) {
    return next(error);
  }
});

app.get(["/home", "/view/home", "/view/home/index.php"], (req, res) => {
  if (!req.session.usu_id) return res.redirect("/login");
  return render(res, "home", { nombre: req.session.usu_nomape });
});

app.get(["/logout", "/view/logout", "/view/logout/index.php"], (req, res) => {
  req.session.destroy((error) => {
    if (error) {
      console.error("Error al cerrar sesión:", error);
      return res.status(500).send("Ocurrió un error, intenta más tarde");
    }
    return res.redirect("/login");
  });
});

app.use((error, req, res, next) => {
  console.error("Error no controlado:", error);
  if (res.headersSent) return next(error);
  return res.status(500).send("Ocurrió un error, intenta más tarde");
});

if (require.main === module) {
  app.listen(port, () => console.log(`Servidor escuchando en http://localhost:${port}`));
}

module.exports = app;