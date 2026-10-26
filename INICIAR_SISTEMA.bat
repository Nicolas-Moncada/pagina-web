@echo off
title Asociacion de Ganaderos - XAMPP (Apache + PHP + MySQL)
color 2F
cls
echo =================================================================
echo        SISTEMA DE CONTROL LECHERO - ASOCIACION DE GANADEROS
echo        Modo 100%% Nativo XAMPP (Apache + PHP + MySQL)
echo =================================================================
echo.

:: 1. Verificar e iniciar Apache (Puerto 80)
echo [1/3] Verificando servidor web Apache (Puerto 80)...
netstat -ano | findstr :80 | findstr LISTENING >nul 2>&1
if %errorlevel% neq 0 (
    echo [*] Apache no esta corriendo. Intentando arrancar Apache de XAMPP...
    if exist "C:\xampp\apache_start.bat" (
        start "" /min "C:\xampp\apache_start.bat"
        timeout /t 2 /nobreak >nul
    ) else (
        echo [!] Abre el panel de control de XAMPP y presiona "Start" en Apache.
    )
) else (
    echo [OK] Servidor Apache activo y respondiendo.
)

:: 2. Verificar e iniciar MySQL (Puerto 3306)
echo.
echo [2/3] Verificando Base de Datos MySQL (Puerto 3306)...
netstat -ano | findstr :3306 | findstr LISTENING >nul 2>&1
if %errorlevel% neq 0 (
    echo [*] MySQL no esta corriendo. Intentando arrancar MySQL de XAMPP...
    if exist "C:\xampp\mysql_start.bat" (
        start "" /min "C:\xampp\mysql_start.bat"
        timeout /t 2 /nobreak >nul
    ) else (
        echo [!] Abre el panel de control de XAMPP y presiona "Start" en MySQL.
    )
) else (
    echo [OK] Base de datos MySQL activa y conectada.
)

:: 3. Abrir navegador web directamente en XAMPP
echo.
echo [3/3] Abriendo aplicacion en el navegador...
echo URL: http://localhost/asociacion
start "" "http://localhost/asociacion"

echo.
echo =================================================================
echo [EXITO] Sistema listo en tu navegador.
echo Las credenciales del Administrador se configuran en el archivo .env
echo =================================================================
timeout /t 5
