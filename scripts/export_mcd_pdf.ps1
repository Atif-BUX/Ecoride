param(
  [string]$Html = (Join-Path (Join-Path (Split-Path -Parent $PSCommandPath) '..' ) 'graphics\mcd_ecoride_print.html'),
  [string]$OutPdf = (Join-Path (Join-Path (Split-Path -Parent $PSCommandPath) '..' ) 'graphics\mcd_ecoride.pdf')
)

Write-Host "EcoRide — Export MCD to PDF" -ForegroundColor Green

function Resolve-Browser {
  $candidates = @(
    'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
    'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    "$Env:LOCALAPPDATA\\Google\\Chrome\\Application\\chrome.exe",
    'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
    'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe'
  )
  foreach ($p in $candidates) { if (Test-Path $p) { return $p } }
  return $null
}

if (-not (Test-Path $Html)) { Write-Error "HTML wrapper not found: $Html"; exit 1 }
$browser = Resolve-Browser
if (-not $browser) { Write-Error "Chrome/Edge not found. Please install Chrome or Edge."; exit 1 }

$uri = (Resolve-Path $Html).Path.Replace('\','/')
if ($uri -notmatch '^\\\\\\?\\') { $uri = $uri }
$fileUrl = 'file:///' + $uri

Write-Host "Using browser: $browser" -ForegroundColor DarkGray
Write-Host "Input: $fileUrl" -ForegroundColor DarkGray
 $outFull = [System.IO.Path]::GetFullPath($OutPdf)
 $outDir  = Split-Path -Parent $outFull
 if (-not (Test-Path $outDir)) { New-Item -ItemType Directory -Force -Path $outDir | Out-Null }
Write-Host "Output: $outFull" -ForegroundColor DarkGray

& $browser --headless --disable-gpu --print-to-pdf="$outFull" --no-sandbox "$fileUrl"
if ($LASTEXITCODE -ne 0) { Write-Error "Headless print failed ($LASTEXITCODE)"; exit 1 }

if (Test-Path $outFull) {
  Write-Host "PDF exported: $outFull" -ForegroundColor Green
} else {
  Write-Error "PDF was not created."
  exit 1
}
