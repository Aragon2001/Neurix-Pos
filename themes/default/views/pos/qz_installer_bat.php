<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
@echo off
setlocal enabledelayedexpansion
title Instalar impresion - Neurix POS

REM ------------------------------------------------------------------
REM  Generado por el POS para esta instalacion: la direccion ya viene
REM  escrita, no hay nada que preguntar. Instala QZ Tray si falta y deja
REM  el certificado del POS como override.crt para que QZ Tray no vuelva
REM  a pedir permiso en cada impresion.
REM ------------------------------------------------------------------

set "POSURL=<?= $pos_url ?>"
set "CERTURL=%POSURL%posprint/qz_certificate"
set "TMPCRT=%TEMP%\neurix-qz-override.crt"

net session >nul 2>&1
if errorlevel 1 (
    echo Windows pedira permiso de administrador: elija "Si" para continuar.
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath '%~f0' -Verb RunAs"
    exit /b
)

echo.
echo  ============================================================
echo   Preparando la impresion de esta caja
echo   POS: %POSURL%
echo  ============================================================
echo.

call :buscarqz
if defined QZDIR goto yainstalado

echo [1/3] Instalando QZ Tray... (puede tardar varios minutos, no cierre esta ventana)
powershell -NoProfile -ExecutionPolicy Bypass -Command "irm qz.sh/install.ps1 | iex"
for /l %%i in (1,1,60) do (
    call :buscarqz
    if defined QZDIR goto instalado
    timeout /t 5 /nobreak >nul
)
goto sinqz

:yainstalado
echo [1/3] QZ Tray ya estaba instalado.

:instalado
if not defined QZDIR goto sinqz

echo [2/3] Autorizando este POS en QZ Tray...
if exist "%TMPCRT%" del /q "%TMPCRT%"
powershell -NoProfile -ExecutionPolicy Bypass -Command "try { Invoke-WebRequest -Uri '%CERTURL%' -OutFile '%TMPCRT%' -UseBasicParsing -TimeoutSec 30 } catch { exit 1 }"
if errorlevel 1 goto sincertificado
findstr /c:"BEGIN CERTIFICATE" "%TMPCRT%" >nul 2>&1
if errorlevel 1 goto sincertificado
copy /y "%TMPCRT%" "%QZDIR%\override.crt" >nul
if errorlevel 1 goto sincertificado
del /q "%TMPCRT%" >nul 2>&1

echo [3/3] Iniciando QZ Tray...
taskkill /f /im qz-tray.exe >nul 2>&1
timeout /t 2 /nobreak >nul
start "" "%QZDIR%\qz-tray.exe"

echo.
echo  LISTO. Vuelva al POS: la pantalla se desbloquea sola en unos segundos
echo  y esta caja ya no volvera a pedir permiso para imprimir.
echo.
pause
exit /b 0

:sincertificado
echo.
echo  AVISO: QZ Tray quedo instalado, pero no se pudo descargar el permiso
echo  desde %CERTURL%
echo  El POS va a funcionar igual; QZ Tray preguntara UNA vez: marque
echo  "Remember this decision" y elija "Allow".
echo.
if defined QZDIR (
    taskkill /f /im qz-tray.exe >nul 2>&1
    timeout /t 2 /nobreak >nul
    start "" "%QZDIR%\qz-tray.exe"
)
pause
exit /b 1

:sinqz
echo.
echo  ERROR: no se pudo instalar QZ Tray en esta computadora.
echo  Revise la conexion a internet y vuelva a ejecutar este archivo,
echo  o instalelo a mano desde https://qz.io/download
echo.
pause
exit /b 1

:buscarqz
set "QZDIR="
if exist "%ProgramFiles%\QZ Tray\qz-tray.exe" set "QZDIR=%ProgramFiles%\QZ Tray"
if not defined QZDIR if exist "%ProgramFiles(x86)%\QZ Tray\qz-tray.exe" set "QZDIR=%ProgramFiles(x86)%\QZ Tray"
if not defined QZDIR if exist "%LOCALAPPDATA%\Programs\QZ Tray\qz-tray.exe" set "QZDIR=%LOCALAPPDATA%\Programs\QZ Tray"
goto :eof
