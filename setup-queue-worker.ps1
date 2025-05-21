# Create a batch file to run the queue worker
$batchContent = @"
@echo off
cd /d "C:\webapp\laravel"
php artisan queue:work --queue=default --timeout=7200 --tries=3 --sleep=3 >> "C:\webapp\laravel\storage\logs\queue-worker.log" 2>&1
"@

# Save the batch file
$batchPath = "C:\webapp\laravel\run-queue-worker.bat"
New-Item -ItemType Directory -Force -Path (Split-Path $batchPath)
Set-Content -Path $batchPath -Value $batchContent

# Create the scheduled task
$taskName = "Laravel Queue Worker"
$action = New-ScheduledTaskAction -Execute $batchPath
$trigger = New-ScheduledTaskTrigger -AtStartup
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest
$settings = New-ScheduledTaskSettingsSet -RestartCount 3 -RestartInterval (New-TimeSpan -Minutes 5) -ExecutionTimeLimit (New-TimeSpan -Days 999)

# Register the task
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings -Force

Write-Host "Laravel Queue Worker task has been created successfully!"
