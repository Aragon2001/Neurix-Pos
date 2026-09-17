@echo off
REM Lee la casilla de facturas de compra y registra los XML que hayan llegado.
REM Lo dispara el Programador de tareas de Windows; ver README.md.
cd /d "%~dp0"
"C:\xampp\php\php.exe" index.php correocompras cron >> "app\logs\correo-compras.log" 2>&1
