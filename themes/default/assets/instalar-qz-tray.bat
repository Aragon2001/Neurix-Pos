@echo off
echo Instalando QZ Tray en esta computadora...
echo (Windows puede pedir permiso de administrador, acepte para continuar)
echo.
powershell -NoProfile -ExecutionPolicy Bypass -Command "irm qz.sh/install.ps1 | iex"
echo.
echo Instalacion finalizada. Puede cerrar esta ventana.
pause
