@echo off
echo Copying files to XAMPP htdocs...

xcopy /E /Y "C:\Users\princ\Music\queue system\*" "C:\xampp\htdocs\queue system\"

echo.
echo ✓ Files copied successfully!
echo.
echo You can now refresh your browser to see the changes.
echo.
pause
