@echo off
:loop
echo Starting Symfony Messenger worker...
php bin/console messenger:consume async --env=prod --sleep=1 --memory-limit=256M -vv
echo Worker stopped. Restarting in 5 seconds...
timeout /t 5 /nobreak
goto loop
