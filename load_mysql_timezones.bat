@echo off
REM Script pour charger les tables de timezone MySQL
REM À exécuter une seule fois

echo Chargement des tables de timezone MySQL...
echo.

REM Pour Windows avec MySQL installé localement
REM Remplacez les valeurs par vos credentials MySQL

set MYSQL_USER=root
set MYSQL_PASSWORD=
set MYSQL_HOST=127.0.0.1
set MYSQL_PORT=3306

echo IMPORTANT: Ce script nécessite les privilèges administrateur MySQL
echo.
echo Option 1: Si vous utilisez XAMPP/WAMP
echo   Les tables de timezone sont généralement déjà chargées
echo.
echo Option 2: Si vous utilisez MySQL standalone
echo   Exécutez cette commande dans MySQL Workbench ou ligne de commande:
echo.
echo   mysql_tzinfo_to_sql /usr/share/zoneinfo ^| mysql -u root -p mysql
echo.
echo Option 3: Pour Docker/Compose
echo   docker-compose exec db mysql_tzinfo_to_sql /usr/share/zoneinfo ^| docker-compose exec -T db mysql -u root -p mysql
echo.
echo Option 4: Télécharger et importer manuellement
echo   1. Téléchargez: https://dev.mysql.com/downloads/timezones.html
echo   2. Importez le fichier SQL dans la base 'mysql'
echo.

pause
