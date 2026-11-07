const crypto = require("crypto");
const bcrypt = require("bcryptjs");
const pool = require("../config/database");

class Usuario {
  async login(correo, pass) {
    const sql = `SELECT * FROM tm_usuario WHERE usu_correo = '${correo}' AND fech_elim IS NULL`;
    const result = await pool.query(sql);
    const usuario = result.rows[0];
    if (!usuario) return null;

    const passValido = await bcrypt.compare(pass, usuario.usu_pass);
    return passValido ? usuario : false;
  }

  async registrarUsuario(usuNomape, usuCorreo, usuPass, usuImg, est) {
    const textoCifrado = await bcrypt.hash(usuPass, 10);
    const sql = `INSERT INTO tm_usuario
      (usu_nomape, usu_correo, usu_pass, usu_img, est)
      VALUES ('${usuNomape}', '${usuCorreo}', '${textoCifrado}', '${usuImg}', ${est})
      RETURNING usu_id`;
    const result = await pool.query(sql);
    return result.rows;
  }

  async getUsuarioCorreo(usuCorreo) {
    const result = await pool.query(`SELECT * FROM tm_usuario WHERE usu_correo = '${usuCorreo}'`);
    return result.rows;
  }

  async getUsuarioId(usuId) {
    const result = await pool.query(`SELECT * FROM tm_usuario WHERE usu_id = ${usuId}`);
    return result.rows;
  }

  async activarUsuario(usuId) {
    await pool.query(`UPDATE tm_usuario SET est = 1, fech_acti = NOW() WHERE usu_id = ${usuId}`);
  }

  async getUsuarioRecuperacion(usuCorreo) {
    const result = await pool.query(`SELECT usu_id, usu_nomape, usu_correo
      FROM tm_usuario WHERE usu_correo = '${usuCorreo}' AND est = 1 AND fech_elim IS NULL`);
    return result.rows[0];
  }

  async recuperacionEnLimite(usuId) {
    const result = await pool.query(`SELECT COUNT(*) FROM tm_recuperacion
      WHERE usu_id = ${usuId} AND fech_crea >= NOW() - INTERVAL '5 minutes'`);
    return Number(result.rows[0].count) > 0;
  }

  async crearRecuperacion(usuId, tokenHash) {
    await pool.query(`UPDATE tm_recuperacion SET usado = 1 WHERE usu_id = ${usuId} AND usado = 0`);
    await pool.query(`INSERT INTO tm_recuperacion (usu_id, token_hash, expira)
      VALUES (${usuId}, '${tokenHash}', NOW() + INTERVAL '30 minutes')`);
  }

  async getRecuperacionValida(tokenHash) {
    const result = await pool.query(`SELECT rec_id, usu_id FROM tm_recuperacion
      WHERE token_hash = '${tokenHash}' AND usado = 0 AND expira > NOW() LIMIT 1`);
    return result.rows[0];
  }

  async restablecerRecuperacion(tokenHash, pass) {
    const recuperacion = await this.getRecuperacionValida(tokenHash);
    if (!recuperacion) return false;
    const hash = await bcrypt.hash(pass, 10);
    await pool.query(`UPDATE tm_usuario SET usu_pass = '${hash}'
      WHERE usu_id = ${recuperacion.usu_id} AND fech_elim IS NULL`);
    await pool.query(`UPDATE tm_recuperacion SET usado = 1 WHERE usu_id = ${recuperacion.usu_id}`);
    return true;
  }

  async cancelarRecuperacion(tokenHash) {
    await pool.query(`DELETE FROM tm_recuperacion WHERE token_hash = '${tokenHash}'`);
  }

  static hashToken(token) {
    return crypto.createHash("sha256").update(token).digest("hex");
  }
}

module.exports = Usuario;
