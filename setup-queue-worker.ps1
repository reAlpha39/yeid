# Create a batch file to run the queue worker
$batchContent = @"
@echo off
:start
cd /d "C:\webapp\laravel"
echo Starting Laravel Queue Worker at %date% %time%
php artisan queue:work --queue=default --timeout=7200 --memory=512 --tries=3 --sleep=3
echo Queue worker stopped at %date% %time%
echo Waiting 10 seconds before restart...
timeout /t 10
goto start
"@

# Save the batch file
$batchPath = "C:\webapp\laravel\run-queue-worker.bat"
New-Item -ItemType Directory -Force -Path (Split-Path $batchPath)
Set-Content -Path $batchPath -Value $batchContent

# Create logging directory
$logDir = "C:\webapp\laravel\storage\logs"
New-Item -ItemType Directory -Force -Path $logDir

# Create the scheduled task with better configuration
$taskName = "Laravel Queue Worker"
$action = New-ScheduledTaskAction -Execute $batchPath -WorkingDirectory "C:\webapp\laravel"

# Multiple triggers for better reliability
$triggerStartup = New-ScheduledTaskTrigger -AtStartup
$triggerDaily = New-ScheduledTaskTrigger -Daily -At "00:00"

# Better principal configuration
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

# Task settings for reliability
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable -RestartOnIdle -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 1)

# Register the task with multiple triggers
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $triggerStartup,$triggerDaily -Principal $principal -Settings $settings -Force

Write-Host "Laravel Queue Worker task has been created successfully!"

# Optional: Create a monitoring script
$monitorContent = @"
@echo off
:monitor
tasklist /FI "WINDOWTITLE eq C:\WINDOWS\system32\cmd.exe - run-queue-worker.bat" | findstr /I cmd.exe > nul
if errorlevel 1 (
    echo Queue worker not running, starting task...
    schtasks /run /tn "Laravel Queue Worker"
)
timeout /t 60
goto monitor
"@

$monitorPath = "C:\webapp\laravel\monitor-queue-worker.bat"
Set-Content -Path $monitorPath -Value $monitorContent

Write-Host "Monitor script created at: $monitorPath"
Write-Host "You can optionally run this as a separate scheduled task for additional monitoring."
