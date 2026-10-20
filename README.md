# Seguridad Informática I

Aplicación PHP para el acceso, registro, confirmación y recuperación de cuentas
de Seguridad Informática I, con persistencia en MySQL y envío de correos mediante
PHPMailer.

## Requisitos

- PHP con PDO MySQL y OpenSSL habilitados.
- MySQL.
- Composer.

## Instalación

1. Copia `config/config.example.php` como `config/config.local.php`.
2. Completa `config/config.local.php` con los datos de tu entorno. Mantén el
   valor de `crypto_key` indicado para que las contraseñas existentes sigan
   funcionando.
3. Crea la base de datos `mesadepartes`.
4. Importa `docs/schema.sql` en esa base de datos.
5. Ejecuta `composer install` dentro de `include/`.
6. Configura el proyecto en Laragon o en tu servidor Linux y verifica
   `base_url`.

`config/config.local.php` contiene la configuración local y no se sube al
repositorio.
