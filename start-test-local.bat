@echo off
title Kuilinga - Test Local Mobile
color 0A

echo.
echo  ==========================================
echo   KUILINGA - Serveur de test local
echo  ==========================================
echo.

REM ── Aller dans le dossier du projet ──────────────────────────────
cd /d "C:\Users\HP\salonWeb"

echo [1/3] Nettoyage du cache Laravel...
php artisan config:clear >nul 2>&1
php artisan route:clear  >nul 2>&1
php artisan cache:clear  >nul 2>&1
echo      OK

echo.
echo [2/3] Demarrage du serveur Laravel sur le port 8000...
start "Laravel Server" cmd /k "cd /d C:\Users\HP\salonWeb && php artisan serve --host=0.0.0.0 --port=8000"
timeout /t 3 >nul

echo.
echo [3/3] Demarrage du tunnel ngrok (URL publique pour le telephone)...
echo      Le tunnel va creer une adresse HTTPS pour ton telephone.
echo      Copie l'adresse "Forwarding" qui ressemble a :
echo      https://xxxx-xx-xx-xx.ngrok-free.app
echo.
echo  ==========================================
echo   IMPORTANT - Apres avoir copie l'URL ngrok :
echo   Ouvre le fichier .env et change APP_URL
echo   pour l'URL ngrok affichee ci-dessous
echo  ==========================================
echo.

ngrok http 8000

pause
