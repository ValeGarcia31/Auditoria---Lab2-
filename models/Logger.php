<?php

require_once __DIR__ . "/../config/conexion.php";

class Logger
{
    public static function login($evento, $usuario, $motivo = "")
    {
        $config = Conectar::config();
        $logPath = $config["log_path"];
        $logDirectory = dirname($logPath);

        $user = preg_replace('/[\x00-\x1F\x7F]/', '', (string) $usuario);
        if ($user === null) {
            $user = "";
        }
        $user = mb_substr($user, 0, 64, "UTF-8");

        $entry = [
            "ts" => date("c"),
            "event" => $evento,
            "ip" => isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : "",
            "user" => $user,
            "reason" => $motivo,
        ];
        $line = json_encode(
            $entry,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        ) . PHP_EOL;

        if (!is_dir($logDirectory) && !mkdir($logDirectory, 0755, true) && !is_dir($logDirectory)) {
            error_log("No se pudo crear la carpeta del log de seguridad: " . $logDirectory);
            return;
        }

        if (file_put_contents($logPath, $line, FILE_APPEND | LOCK_EX) === false) {
            error_log("No se pudo escribir el log de seguridad: " . $logPath);
        }
    }
}
