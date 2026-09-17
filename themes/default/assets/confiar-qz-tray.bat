@echo off
setlocal enabledelayedexpansion
title Confiar este POS en QZ Tray

REM ------------------------------------------------------------------
REM  Instala el certificado del POS como "override.crt" de QZ Tray para
REM  que QZ deje de preguntar en cada impresion. Se ejecuta UNA VEZ por
REM  computadora y requiere permisos de administrador.
REM
REM  Normalmente no hace falta: el boton "Descargar e instalar" del POS
REM  entrega un instalador que ya trae la direccion adentro. Este archivo
REM  sirve para una caja que ya tiene QZ Tray y solo necesita el permiso.
REM
REM  Argumentos (los pasa el propio script al reabrirse como administrador):
REM    %1 = carpeta de QZ Tray resuelta ANTES de elevar
REM    %2 = direccion del POS ya validada
REM ------------------------------------------------------------------

REM QZ Tray se puede haber instalado solo para el usuario actual
REM (%LOCALAPPDATA%): al elevar, ese perfil pasa a ser el del administrador
REM y la carpeta cambia. Por eso se resuelve antes y viaja como argumento.
call :buscarqz
if not "%~1"=="" if exist "%~1\qz-tray.exe" set "QZDIR=%~1"
set "POSURL=%~2"

net session >nul 2>&1
if errorlevel 1 goto pedirdatos

REM --- Ya corriendo como administrador -------------------------------
REM Si lo abrieron directo con "Ejecutar como administrador" no hay
REM argumentos, asi que la direccion se pide aqui mismo.
if not defined POSURL call :preguntarurl
if not defined POSURL goto urlinvalida
call :validarurl
if errorlevel 1 goto urlinvalida
if not defined QZDIR goto sinqz

set "CERTURL=%POSURL%posprint/qz_certificate"
set "TMPCRT=%TEMP%\neurix-qz-override.crt"

echo.
echo Descargando certificado desde %CERTURL% ...
if exist "%TMPCRT%" del /q "%TMPCRT%"
REM La direccion viaja por variable de entorno, nunca pegada dentro del
REM comando de PowerShell: asi ningun valor puede cerrar la comilla y
REM convertirse en codigo ejecutado como administrador.
set "NX_CERTURL=%CERTURL%"
set "NX_TMPCRT=%TMPCRT%"
powershell -NoProfile -ExecutionPolicy Bypass -Command "try { Invoke-WebRequest -Uri $env:NX_CERTURL -OutFile $env:NX_TMPCRT -UseBasicParsing -TimeoutSec 20 } catch { exit 1 }"
if errorlevel 1 goto sincertificado
findstr /c:"BEGIN CERTIFICATE" "%TMPCRT%" >nul 2>&1
if errorlevel 1 goto sincertificado

echo Instalando confianza en "%QZDIR%" ...
copy /y "%TMPCRT%" "%QZDIR%\override.crt" >nul
if errorlevel 1 goto errorcopia
del /q "%TMPCRT%" >nul 2>&1

echo Reiniciando QZ Tray ...
REM Se arranca a traves de explorer para que QZ Tray corra como el cajero y
REM no como administrador: elevado guardaria su configuracion en el perfil
REM equivocado y el POS no lo encontraria.
taskkill /f /im qz-tray.exe >nul 2>&1
timeout /t 2 /nobreak >nul
explorer.exe "%QZDIR%\qz-tray.exe"

echo.
echo  LISTO. Esta computadora ya confia en el POS.
echo  Abra el POS de nuevo: QZ Tray no deberia volver a preguntar.
echo  (Si QZ Tray no arranca solo, abralo desde el menu Inicio.)
echo.
pause
exit /b 0

REM --- Todavia sin elevar: se piden los datos y se reabre elevado -----
:pedirdatos
call :preguntarurl
call :validarurl
if errorlevel 1 goto urlinvalida

echo.
echo Windows pedira permiso de administrador: elija "Si" para continuar.
set "NX_SELF=%~f0"
set "NX_QZDIR=!QZDIR!"
set "NX_URL=!POSURL!"
powershell -NoProfile -ExecutionPolicy Bypass -Command "Start-Process -FilePath $env:NX_SELF -ArgumentList ([char]34 + $env:NX_QZDIR + [char]34 + ' ' + [char]34 + $env:NX_URL + [char]34) -Verb RunAs"
exit /b

:urlinvalida
echo.
echo  ERROR: la direccion no es valida.
echo  Debe ser una direccion http:// o https:// simple, por ejemplo:
echo      http://neurixpos.test/
echo      http://192.168.1.50/
echo.
pause
exit /b 1

:sincertificado
echo.
echo  ERROR: no se pudo descargar el certificado desde %CERTURL%
echo  - Revise que la direccion del POS sea la correcta.
echo  - Revise que el POS responda en esa direccion desde esta computadora.
echo.
pause
exit /b 1

:sinqz
echo.
echo  ERROR: no se encontro QZ Tray instalado en esta computadora.
echo  Instalelo primero con el boton "Descargar e instalar" del POS y
echo  vuelva a ejecutar este archivo.
echo  Si QZ Tray esta instalado solo para el usuario de la caja, abra este
echo  archivo con doble clic desde esa sesion (sin "Ejecutar como
echo  administrador"): el permiso se pide solo y asi se encuentra su copia.
echo.
pause
exit /b 1

:errorcopia
echo.
echo  ERROR: no se pudo copiar override.crt a "%QZDIR%".
echo  Cierre QZ Tray y ejecute este archivo como administrador.
echo.
pause
exit /b 1

:preguntarurl
echo.
echo  ============================================================
echo   Confiar este punto de venta en QZ Tray
echo  ============================================================
echo.
set "POSURL="
set /p POSURL=Direccion del POS (ENTER para http://localhost/): 
if "!POSURL!"=="" set "POSURL=http://localhost/"
if not "!POSURL:~-1!"=="/" set "POSURL=!POSURL!/"
goto :eof

REM Solo direcciones http(s) simples: sin comillas, espacios, ^, ^& ni %%,
REM que son los caracteres con los que se podria inyectar un comando.
:validarurl
echo(!POSURL!|findstr /i /r /c:"^https*://[-A-Za-z0-9._:/][-A-Za-z0-9._:/]*$" >nul
if errorlevel 1 exit /b 1
exit /b 0

:buscarqz
set "QZDIR="
if exist "%ProgramFiles%\QZ Tray\qz-tray.exe" set "QZDIR=%ProgramFiles%\QZ Tray"
if not defined QZDIR if exist "%ProgramFiles(x86)%\QZ Tray\qz-tray.exe" set "QZDIR=%ProgramFiles(x86)%\QZ Tray"
if not defined QZDIR if exist "%LOCALAPPDATA%\Programs\QZ Tray\qz-tray.exe" set "QZDIR=%LOCALAPPDATA%\Programs\QZ Tray"
goto :eof
