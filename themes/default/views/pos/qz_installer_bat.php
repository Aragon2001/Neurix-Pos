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

REM QZ Tray se puede haber instalado solo para el usuario actual
REM (%LOCALAPPDATA%). Hay que resolver la ruta ANTES de elevar: al elevar,
REM el perfil pasa a ser el del administrador y esa carpeta ya no es la del
REM cajero. La ruta viaja al proceso elevado como argumento.
call :buscarqz
if not "%~1"=="" if exist "%~1\qz-tray.exe" set "QZDIR=%~1"

net session >nul 2>&1
if errorlevel 1 (
    echo Windows pedira permiso de administrador: elija "Si" para continuar.
    set "NX_SELF=%~f0"
    set "NX_QZDIR=!QZDIR!"
    powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath $env:NX_SELF -ArgumentList ([char]34 + $env:NX_QZDIR + [char]34) -Verb RunAs"
    exit /b
)

echo.
echo  ============================================================
echo   Preparando la impresion de esta caja
echo   POS: %POSURL%
echo  ============================================================
echo.

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
REM La direccion viaja por variable de entorno, nunca pegada dentro del
REM comando de PowerShell: asi ningun valor puede cerrar la comilla y
REM convertirse en codigo ejecutado como administrador.
set "NX_CERTURL=%CERTURL%"
set "NX_TMPCRT=%TMPCRT%"
powershell -NoProfile -ExecutionPolicy Bypass -Command "try { Invoke-WebRequest -Uri $env:NX_CERTURL -OutFile $env:NX_TMPCRT -UseBasicParsing -TimeoutSec 30 } catch { exit 1 }"
if errorlevel 1 goto sincertificado
findstr /c:"BEGIN CERTIFICATE" "%TMPCRT%" >nul 2>&1
if errorlevel 1 goto sincertificado
copy /y "%TMPCRT%" "%QZDIR%\override.crt" >nul
if errorlevel 1 goto sincertificado
del /q "%TMPCRT%" >nul 2>&1

echo [3/3] Iniciando QZ Tray...
call :reiniciarqz

echo.
echo  LISTO. Vuelva al POS: la pantalla se desbloquea sola en unos segundos
echo  y esta caja ya no volvera a pedir permiso para imprimir.
echo  (Si no arranca solo, abra QZ Tray desde el menu Inicio.)
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
if defined QZDIR call :reiniciarqz
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

:reiniciarqz
REM Se arranca a traves de explorer para que QZ Tray corra como el cajero y
REM no como administrador: elevado guardaria su configuracion en el perfil
REM equivocado y el POS no lo encontraria.
taskkill /f /im qz-tray.exe >nul 2>&1
timeout /t 2 /nobreak >nul
explorer.exe "%QZDIR%\qz-tray.exe"
goto :eof
