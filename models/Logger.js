const fs = require("fs");
const path = require("path");
const config = require("../config/config.json");

function login(event, user, reason = "", ip = "") {
  const logPath = path.resolve(config.log_path);
  const entry = {
    ts: new Date().toISOString(),
    event,
    ip,
    user: String(user || "").replace(/[\x00-\x1F\x7F]/g, "").slice(0, 64),
    reason
  };

  fs.mkdirSync(path.dirname(logPath), { recursive: true });
  fs.appendFileSync(logPath, `${JSON.stringify(entry)}\n`, "utf8");
}

module.exports = { login };
