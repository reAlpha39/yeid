# Create a batch file to run the queue worker
$batchContent = @"
@echo off
cd /d "C:\webapp\laravel"
php artisan queue:work --queue=default --timeout=7200
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

# Register the task
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Force

Write-Host "Laravel Queue Worker task has been created successfully!"
