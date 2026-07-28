@echo off
setlocal
title SisColog - Servidor

set "SISCOLOG_ROOT=C:\laragon\www\siscolog"
set "SISCOLOG_PHP=C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe"
set "SISCOLOG_SESSIONS=C:\laragon\www\siscolog\storage\sessions"

if not exist "%SISCOLOG_PHP%" (
    echo No se encontro PHP en:
    echo %SISCOLOG_PHP%
    pause
    exit /b 1
)

if not exist "%SISCOLOG_SESSIONS%" mkdir "%SISCOLOG_SESSIONS%"

pushd "%SISCOLOG_ROOT%"

start "" "http://localhost:8787/login"

echo.
echo Iniciando SisColog en http://localhost:8787
echo Mantenga esta ventana abierta.
echo Para detener el servidor presione Ctrl+C.
echo.

"%SISCOLOG_PHP%" -d "session.save_path=%SISCOLOG_SESSIONS%" -S localhost:8787 -t "%SISCOLOG_ROOT%\public" "%SISCOLOG_ROOT%\public\router.php"

echo.
echo El servidor se detuvo o no pudo iniciar.
pause

popd
endlocal
