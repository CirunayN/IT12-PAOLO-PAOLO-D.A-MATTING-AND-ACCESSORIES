param([string] $InitialDirectory = '')
$ErrorActionPreference = 'Stop'
[Console]::OutputEncoding = [System.Text.UTF8Encoding]::new($false)
Add-Type -AssemblyName System.Windows.Forms
[System.Windows.Forms.Application]::EnableVisualStyles()
$dialog = [System.Windows.Forms.FolderBrowserDialog]::new()
$dialog.Description = 'Choose the database backup folder'
$dialog.ShowNewFolderButton = $true
if ($dialog.PSObject.Properties.Name -contains 'AutoUpgradeEnabled') { $dialog.AutoUpgradeEnabled = $true }
if ($InitialDirectory -and (Test-Path -LiteralPath $InitialDirectory -PathType Container)) { $dialog.SelectedPath = $InitialDirectory }
$owner = [System.Windows.Forms.Form]::new()
$owner.TopMost = $true
$owner.ShowInTaskbar = $false
$owner.Opacity = 0
try {
    $owner.Show()
    $owner.BringToFront()
    if ($dialog.ShowDialog($owner) -eq [System.Windows.Forms.DialogResult]::OK) {
        @{ cancelled = $false; path = $dialog.SelectedPath } | ConvertTo-Json -Compress
    } else { @{ cancelled = $true; path = $null } | ConvertTo-Json -Compress }
} finally { $dialog.Dispose(); $owner.Close(); $owner.Dispose() }
