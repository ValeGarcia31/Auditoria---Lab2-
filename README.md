# Seguridad Informática I

Aplicación Node.js con Express para el acceso, registro, confirmación y
recuperación de cuentas, con persistencia en PostgreSQL y correo mediante
Nodemailer.

## Requisitos

- Node.js.
- PostgreSQL.

## Instalación

1. Completa `config/config.json` con los datos de PostgreSQL y Gmail.
2. Crea la base de datos `mesadepartes`.
3. Importa `docs/schema.sql` en esa base de datos.
4. Ejecuta `npm install`.
5. Inicia el servidor con `npm start` y abre `http://localhost:3000/login`.

`config/config.json` contiene la configuración local para esta práctica.
