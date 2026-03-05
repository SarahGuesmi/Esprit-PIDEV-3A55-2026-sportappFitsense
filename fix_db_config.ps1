# MySQL Configuration Fixer for Fitsense
# Run this script as Administrator to fix:
# 1. Binary Logging (binlog) - Disables it for dev
# 2. Performance (innodb_flush_log_at_trx_commit)
# 3. Timezone Synchronization

$MyIniPath = "C:\ProgramData\MySQL\MySQL Server 9.6\my.ini"
$BackupPath = "$MyIniPath.bak"

if (-not (Test-Path $MyIniPath)) {
    Write-Error "Could not find my.ini at $MyIniPath"
    exit
}

Write-Host "Backing up my.ini to $BackupPath..."
Copy-Item $MyIniPath $BackupPath -Force

$content = Get-Content $MyIniPath

# Disable log-bin
$content = $content -replace '^log-bin="SARAH-bin"', '# log-bin="SARAH-bin"`r`nskip-log-bin'

# Performance optimization
$content = $content -replace '^innodb_flush_log_at_trx_commit=1', 'innodb_flush_log_at_trx_commit=2'

# Default timezone
if ($content -notmatch 'default-time-zone') {
    $content = $content -replace '\[mysqld\]', "[mysqld]`r`ndefault-time-zone='+01:00'"
}

Write-Host "Saving changes to $MyIniPath..."
$content | Set-Content $MyIniPath -Encoding UTF8

Write-Host "Restarting MySQL96 service..."
Restart-Service -Name "MySQL96" -Force

Write-Host "--------------------------------------------------------"
Write-Host "SUCCESS! Database configuration updated and service restarted."
Write-Host "Next step: Import MySQL Timezone tables from:"
Write-Host "https://dev.mysql.com/downloads/timezones.html"
Write-Host "--------------------------------------------------------"
pause
