$processName = "php"
$scriptPath = "C:\path\to\your\laravel\artisan"
$logFile = "C:\path\to\your\laravel\storage\logs\queue-monitor.log"

function Write-Log {
    param($Message)
    $logMessage = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss'): $Message"
    Add-Content -Path $logFile -Value $logMessage
    Write-Host $logMessage
}

while ($true) {
    $queueProcess = Get-Process $processName -ErrorAction SilentlyContinue | 
        Where-Object { $_.CommandLine -like "*queue:work*" }
    
    if (-not $queueProcess) {
        Write-Log "Queue worker not running. Restarting..."
        Start-Process "php" -ArgumentList "artisan queue:work --timeout=7200" -WorkingDirectory "C:\path\to\your\laravel"
    }
    
    Start-Sleep -Seconds 300  # Check every 5 minutes
}
