@echo off
rd /s /q .git
for /d %%a in (*) do (
    if /i not "%%a"=="node_modules" if /i not "%%a"=="vendor" (
        rd /s /q "%%a"
    )
)
del /q /f bin.bat
