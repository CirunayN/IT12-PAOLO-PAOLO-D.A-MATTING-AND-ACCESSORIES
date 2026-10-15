param([string]$InputFile, [string]$PdfFile)
$ErrorActionPreference = 'Stop'
$wordRenderer = $null
$renderDocument = $null
try {
    $wordRenderer = New-Object -ComObject Word.Application
    $wordRenderer.Visible = $false
    $wordRenderer.DisplayAlerts = 0
    $wordRenderer.AutomationSecurity = 3
    $inputResolved = (Resolve-Path -LiteralPath $InputFile).Path
    $outputResolved = [System.IO.Path]::GetFullPath($PdfFile)
    [System.IO.Directory]::CreateDirectory([System.IO.Path]::GetDirectoryName($outputResolved)) | Out-Null
    $renderDocument = $wordRenderer.Documents.Open($inputResolved, $false, $true, $false)
    $renderDocument.Repaginate()
    $renderDocument.ExportAsFixedFormat($outputResolved, 17)
    [PSCustomObject]@{Pdf=$outputResolved; Pages=$renderDocument.ComputeStatistics(2)} | ConvertTo-Json
} finally {
    if ($null -ne $renderDocument) { $renderDocument.Close(0); [System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($renderDocument) | Out-Null }
    if ($null -ne $wordRenderer) { $wordRenderer.Quit(0); [System.Runtime.InteropServices.Marshal]::FinalReleaseComObject($wordRenderer) | Out-Null }
}
