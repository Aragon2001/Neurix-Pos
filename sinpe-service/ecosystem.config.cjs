// Configuración de PM2 para que el servicio SINPE quede corriendo como proceso
// de fondo, se reinicie solo si se cae, y arranque de nuevo cuando reinicie el
// servidor. Ver README.md para el paso a paso de instalación.
//
// Uso:
//   pm2 start ecosystem.config.cjs
//   pm2 save
//   pm2 startup   (una sola vez, deja el arranque automático en el SO)
module.exports = {
  apps: [
    {
      name: "sinpe-service",
      script: "server.js",
      cwd: __dirname,
      node_args: "--env-file-if-exists=.env",
      instances: 1,
      autorestart: true,
      max_restarts: 20,
      restart_delay: 5000,
      env: { NODE_ENV: "production" },
    },
  ],
};
