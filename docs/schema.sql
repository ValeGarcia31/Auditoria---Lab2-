CREATE TABLE IF NOT EXISTS tm_usuario (
  usu_id SERIAL PRIMARY KEY,
  usu_nomape VARCHAR(90) NOT NULL,
  usu_correo VARCHAR(50) NOT NULL,
  usu_pass VARCHAR(200) NOT NULL,
  fech_crea TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  usu_img VARCHAR(500),
  fech_modi TIMESTAMP,
  fech_elim TIMESTAMP,
  fech_acti TIMESTAMP,
  est INTEGER NOT NULL DEFAULT 2
);

CREATE TABLE IF NOT EXISTS tm_recuperacion (
  rec_id SERIAL PRIMARY KEY,
  usu_id INTEGER NOT NULL REFERENCES tm_usuario(usu_id),
  token_hash CHAR(64) NOT NULL,
  expira TIMESTAMP NOT NULL,
  usado INTEGER NOT NULL DEFAULT 0,
  fech_crea TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_tm_recuperacion_token_hash
  ON tm_recuperacion(token_hash);
CREATE INDEX IF NOT EXISTS idx_tm_recuperacion_usu_id
  ON tm_recuperacion(usu_id);
