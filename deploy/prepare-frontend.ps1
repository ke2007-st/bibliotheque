$ErrorActionPreference = "Stop"
$root = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
$src = Join-Path $root "src"
$dist = Join-Path $root "deploy\frontend-dist"

if (Test-Path $dist) { Remove-Item -Recurse -Force $dist }
New-Item -ItemType Directory -Path $dist, "$dist\css", "$dist\js", "$dist\admin", "$dist\admin\css", "$dist\admin\js" -Force | Out-Null

Copy-Item "$src\*.html" $dist
Copy-Item "$src\css\*" "$dist\css\"
Copy-Item "$src\js\*" "$dist\js\"
Copy-Item "$src\admin\*.html" "$dist\admin\"
Copy-Item "$src\admin\css\*" "$dist\admin\css\"
Copy-Item "$src\admin\js\*" "$dist\admin\js\"

$apiUrl = if ($env:BIBLIO_API_URL) { $env:BIBLIO_API_URL } else { "/api" }
$adminApiUrl = if ($env:BIBLIO_ADMIN_API_URL) { $env:BIBLIO_ADMIN_API_URL } else { "/api/admin" }

$envJs = @"
window.__BIBLIO_API_URL__ = '$apiUrl';
window.__BIBLIO_ADMIN_API_URL__ = '$adminApiUrl';
"@

Set-Content -Path "$dist\js\env.js" -Value $envJs -Encoding UTF8
Write-Host "Frontend pret dans deploy/frontend-dist"
Write-Host "API: $apiUrl"
