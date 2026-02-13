@echo off
echo ==========================================
echo  Setting up AI RAG Chat Dependencies
echo ==========================================

WHERE php >nul 2>nul
IF %ERRORLEVEL% NEQ 0 (
    echo [ERROR] PHP is not in your PATH. 
    echo Please make sure you have XAMPP installed and PHP added to your system environment variables.
    echo Alternatively, run this script from the 'XAMPP Shell'.
    pause
    exit /b
)

if not exist composer.phar (
    echo [INFO] Downloading composer.phar...
    powershell -Command "Invoke-WebRequest -Uri https://getcomposer.org/composer.phar -OutFile composer.phar"
)

echo [INFO] Installing dependencies...
php composer.phar install

echo.
echo ==========================================
echo  Setup Complete!
echo ==========================================
echo You can now copy this entire folder to C:\xampp\htdocs\ai-rag-chat
pause
