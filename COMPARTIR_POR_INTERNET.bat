@echo off
title Compartir Proyecto por Internet - Cloudflare Tunnel
color 1F
cls
echo =================================================================
echo        COMPARTIR SISTEMA POR INTERNET CON OTRAS PERSONAS
echo =================================================================
echo.

:: 1. Verificar e iniciar Apache
netstat -ano | findstr :80 | findstr LISTENING >nul 2>&1
if %errorlevel% neq 0 (
    echo [*] Iniciando Apache de XAMPP...
    if exist "C:\xampp\apache_start.bat" (
        start "" /min "C:\xampp\apache_start.bat"
        timeout /t 2 /nobreak >nul
    )
) else (
    echo [OK] Apache activo.
)

:: 2. Verificar e iniciar MySQL
netstat -ano | findstr :3306 | findstr LISTENING >nul 2>&1
if %errorlevel% neq 0 (
    echo [*] Iniciando MySQL de XAMPP...
    if exist "C:\xampp\mysql_start.bat" (
        start "" /min "C:\xampp\mysql_start.bat"
        timeout /t 2 /nobreak >nul
    )
) else (
    echo [OK] MySQL activo.
)

echo.
echo =================================================================
echo  Generando enlace publico y seguro (HTTPS)...
echo  Copia el enlace que termina en '.trycloudflare.com'
echo  y agrega '/asociacion' al final para compartirlo por WhatsApp.
echo =================================================================
echo.

"C:\Program Files (x86)\cloudflared\cloudflared.exe" tunnel --url http://localhost:80
pause

