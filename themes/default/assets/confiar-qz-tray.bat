@echo off
setlocal enabledelayedexpansion
title Confiar este POS en QZ Tray

:: ------------------------------------------------------------------
::  Instala el certificado del POS como "override.crt" de QZ Tray para
::  que QZ deje de preguntar en cada impresion. Se ejecuta UNA VEZ por
::  computadora y requiere permisos de administrador.
:: ------------------------------------------------------------------

net session >nul 2>&1
if errorlevel 1 (
    echo Se necesitan permisos de administrador. Aceptando el aviso de Windows...
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

echo.
echo  ============================================================
echo   Confiar este punto de venta en QZ Tray
echo  ============================================================
echo.
set "POSURL="
set /p POSURL=Direccion del POS (ENTER para http://localhost/): 
if "!POSURL!"=="" set "POSURL=http://localhost/"
if not "!POSURL:~-1!"=="/" set "POSURL=!POSURL!/"
set "CERTURL=!POSURL!posprint/qz_certificate"
set "TMPCRT=%TEMP%\neurix-qz-override.crt"

echo.
echo Descargando certificado desde !CERTURL! ...
if exist "%TMPCRT%" del /q "%TMPCRT%"
powershell -NoProfile -ExecutionPolicy Bypass -Command "try { Invoke-WebRequest -Uri '!CERTURL!' -OutFile '%TMPCRT%' -UseBasicParsing -TimeoutSec 20 } catch { exit 1 }"
if errorlevel 1 goto sincertificado
findstr /c:"BEGIN CERTIFICATE" "%TMPCRT%" >nul 2>&1
if errorlevel 1 goto sincertificado

set "QZDIR="
if exist "%ProgramFiles%\QZ Tray\qz-tray.exe" set "QZDIR=%ProgramFiles%\QZ Tray"
if not defined QZDIR if exist "%ProgramFiles(x86)%\QZ Tray\qz-tray.exe" set "QZDIR=%ProgramFiles(x86)%\QZ Tray"
if not defined QZDIR if exist "%LOCALAPPDATA%\Programs\QZ Tray\qz-tray.exe" set "QZDIR=%LOCALAPPDATA%\Programs\QZ Tray"
if not defined QZDIR goto sinqz

echo Instalando confianza en "!QZDIR!" ...
copy /y "%TMPCRT%" "!QZDIR!\override.crt" >nul
if errorlevel 1 goto errorcopia
del /q "%TMPCRT%" >nul 2>&1

echo Reiniciando QZ Tray ...
taskkill /f /im qz-tray.exe >nul 2>&1
timeout /t 2 /nobreak >nul
start "" "!QZDIR!\qz-tray.exe"

echo.
echo  LISTO. Esta computadora ya confia en el POS.
echo  Abra el POS de nuevo: QZ Tray no deberia volver a preguntar.
echo.
pause
exit /b 0

:sincertificado
echo.
echo  ERROR: no se pudo descargar el certificado desde !CERTURL!
echo  - Revise que la direccion del POS sea la correcta.
echo  - En el servidor debe haberse generado el certificado con:
echo        php tools/qz/generar-certificado-qz.php
echo.
pause
exit /b 1

:sinqz
echo.
echo  ERROR: no se encontro QZ Tray instalado en esta computadora.
echo  Instalelo primero con instalar-qz-tray.bat y vuelva a ejecutar este archivo.
echo.
pause
exit /b 1

:errorcopia
echo.
echo  ERROR: no se pudo copiar override.crt a "!QZDIR!".
echo  Cierre QZ Tray y ejecute este archivo como administrador.
echo.
pause
exit /b 1
