const crypto = require("crypto");
const bcrypt = require("bcryptjs");
const pool = require("../config/database");
const Logger = require("./Logger");

class Usuario {
  async login(correo, pass, ip = "") {
    const sql = "SELECT * FROM tm_usuario WHERE usu_correo = $1 AND fech_elim IS NULL";
    const result = await pool.query(sql, [correo]);
    const usuario = result.rows[0];
    if (!usuario) {
      Logger.login("LOGIN_FAILED", correo, "USER_NOT_FOUND", ip);
      return null;
    }

    const passValido = await bcrypt.compare(pass, usuario.usu_pass);
    if (!passValido) {
      Logger.login("LOGIN_FAILED", correo, "BAD_PASSWORD", ip);
      return false;
    }
    if (usuario.est !== 1) {
      Logger.login("LOGIN_NOT_CONFIRMED", correo, "NOT_CONFIRMED", ip);
      return usuario;
    }
    Logger.login("LOGIN_SUCCESS", correo, "", ip);
    return usuario;
  }

  async registrarUsuario(usuNomape, usuCorreo, usuPass, usuImg, est) {
    const textoCifrado = await bcrypt.hash(usuPass, 10);
    const sql = `INSERT INTO tm_usuario
      (usu_nomape, usu_correo, usu_pass, usu_img, est)
      VALUES ($1, $2, $3, $4, $5)
      RETURNING usu_id`;
    const result = await pool.query(sql, [usuNomape, usuCorreo, textoCifrado, usuImg, est]);
    return result.rows;
  }

  async getUsuarioCorreo(usuCorreo) {
    const result = await pool.query("SELECT * FROM tm_usuario WHERE usu_correo = $1", [usuCorreo]);
    return result.rows;
  }

  async getUsuarioId(usuId) {
    const result = await pool.query("SELECT * FROM tm_usuario WHERE usu_id = $1", [usuId]);
    return result.rows;
  }

  async activarUsuario(usuId) {
    await pool.query("UPDATE tm_usuario SET est = 1, fech_acti = NOW() WHERE usu_id = $1", [usuId]);
  }

  async getUsuarioRecuperacion(usuCorreo) {
    const result = await pool.query(`SELECT usu_id, usu_nomape, usu_correo
      FROM tm_usuario WHERE usu_correo = $1 AND est = 1 AND fech_elim IS NULL`, [usuCorreo]);
    return result.rows[0];
  }

  async recuperacionEnLimite(usuId) {
    const result = await pool.query(`SELECT COUNT(*) FROM tm_recuperacion
      WHERE usu_id = $1 AND fech_crea >= NOW() - INTERVAL '5 minutes'`, [usuId]);
    return Number(result.rows[0].count) > 0;
  }

  async crearRecuperacion(usuId, tokenHash) {
    await pool.query("UPDATE tm_recuperacion SET usado = 1 WHERE usu_id = $1 AND usado = 0", [usuId]);
    await pool.query(`INSERT INTO tm_recuperacion (usu_id, token_hash, expira)
      VALUES ($1, $2, NOW() + INTERVAL '30 minutes')`, [usuId, tokenHash]);
  }

  async getRecuperacionValida(tokenHash) {
    const result = await pool.query(`SELECT rec_id, usu_id FROM tm_recuperacion
      WHERE token_hash = $1 AND usado = 0 AND expira > NOW() LIMIT 1`, [tokenHash]);
    return result.rows[0];
  }

  async restablecerRecuperacion(tokenHash, pass) {
    const recuperacion = await this.getRecuperacionValida(tokenHash);
    if (!recuperacion) return false;
    const hash = await bcrypt.hash(pass, 10);
    await pool.query(`UPDATE tm_usuario SET usu_pass = $1
      WHERE usu_id = $2 AND fech_elim IS NULL`, [hash, recuperacion.usu_id]);
    await pool.query("UPDATE tm_recuperacion SET usado = 1 WHERE usu_id = $1", [recuperacion.usu_id]);
    return true;
  }

  async cancelarRecuperacion(tokenHash) {
    await pool.query("DELETE FROM tm_recuperacion WHERE token_hash = $1", [tokenHash]);
  }

  static hashToken(token) {
    return crypto.createHash("sha256").update(token).digest("hex");
  }
}

module.exports = Usuario;
