@echo off
setlocal
title Configurar Neurix POS en este servidor

REM Deja esta PC (la que corre Laragon) lista para entrar por
REM http://neurixpos.test y para que QZ Tray confie en el certificado del POS.
REM Necesita administrador: el archivo hosts y la carpeta de QZ Tray lo exigen.

net session >nul 2>&1
if errorlevel 1 (
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

set "HOSTS=%SystemRoot%\System32\drivers\etc\hosts"
set "QZDIR=%ProgramFiles%\QZ Tray"
set "TMPCRT=%TEMP%\neurix-qz-override.crt"

echo [1/3] Nombre neurixpos en el archivo hosts...
findstr /r /c:"^127\.0\.0\.1.*neurixpos\.test" "%HOSTS%" >nul 2>&1
if errorlevel 1 (
    >>"%HOSTS%" echo.
    >>"%HOSTS%" echo 127.0.0.1       neurixpos.test neurixpos
    echo       agregado.
) else (
    echo       ya estaba.
)
ipconfig /flushdns >nul

echo [2/3] Certificado del POS como override.crt de QZ Tray...
if not exist "%QZDIR%\qz-tray.exe" (
    echo       ERROR: QZ Tray no esta en "%QZDIR%".
    goto fin
)
powershell -NoProfile -ExecutionPolicy Bypass -Command "try { Invoke-WebRequest -Uri 'http://localhost/posprint/qz_certificate' -OutFile '%TMPCRT%' -UseBasicParsing -TimeoutSec 30 } catch { exit 1 }"
findstr /c:"BEGIN CERTIFICATE" "%TMPCRT%" >nul 2>&1
if errorlevel 1 (
    echo       ERROR: no se pudo descargar el certificado. Laragon esta encendido?
    goto fin
)
copy /y "%TMPCRT%" "%QZDIR%\override.crt" >nul
del /q "%TMPCRT%" >nul 2>&1
echo       instalado.

echo [3/3] Reiniciando QZ Tray...
taskkill /f /im qz-tray.exe >nul 2>&1
timeout /t 2 /nobreak >nul
start "" "%QZDIR%\qz-tray.exe"

echo.
echo  LISTO. Abra http://neurixpos.test/ en el navegador.
:fin
echo.
pause
