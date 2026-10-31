<#
.SYNOPSIS
    Builds the installable Breefer plugin ZIP.

.DESCRIPTION
    Copies only runtime files into a staging directory and writes a ZIP with
    forward-slash entry names. PowerShell's Compress-Archive writes backslashes
    on Windows, which WordPress cannot resolve, so entries are written manually.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File bin/package.ps1
#>

$ErrorActionPreference = 'Stop'
$ProgressPreference = 'SilentlyContinue'

$root = Split-Path -Parent $PSScriptRoot
$header = Get-Content -Path (Join-Path $root 'breefer.php') -TotalCount 20
$match = [regex]::Match(($header -join "`n"), '(?m)^\s*\*\s*Version:\s*([0-9][0-9A-Za-z.\-]*)')
$version = if ($match.Success) { $match.Groups[1].Value } else { '0.0.0' }

$stage = Join-Path $env:TEMP ('breefer-package-' + [guid]::NewGuid().ToString('N'))
$pluginDir = Join-Path $stage 'breefer'

New-Item -ItemType Directory -Path $pluginDir -Force | Out-Null

$include = @(
	'breefer.php',
	'uninstall.php',
	'readme.txt',
	'includes',
	'templates',
	'assets',
	'languages',
	'build',
	'docs',
	'LICENSE'
)

foreach ($item in $include) {
	$source = Join-Path $root $item

	if (-not (Test-Path $source)) {
		throw "Missing required path: $item"
	}

	Copy-Item -Path $source -Destination $pluginDir -Recurse -Force
}

# Internal placeholders are not needed in a release.
Get-ChildItem -Path $pluginDir -Recurse -File -Filter '.gitkeep' | Remove-Item -Force

$zipPath = Join-Path (Split-Path $root -Parent) ("breefer-{0}.zip" -f $version)

if (Test-Path $zipPath) {
	Remove-Item -Path $zipPath -Force
}

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$archive = [System.IO.Compression.ZipFile]::Open($zipPath, [System.IO.Compression.ZipArchiveMode]::Create)

try {
	$files = Get-ChildItem -Path $pluginDir -Recurse -File

	foreach ($file in $files) {
		$relative = $file.FullName.Substring($stage.Length + 1).Replace('\', '/')
		$entry = $archive.CreateEntry($relative, [System.IO.Compression.CompressionLevel]::Optimal)
		$target = $entry.Open()
		$source = [System.IO.File]::OpenRead($file.FullName)

		try {
			$source.CopyTo($target)
		}
		finally {
			$source.Dispose()
			$target.Dispose()
		}
	}
}
finally {
	$archive.Dispose()
	Remove-Item -Path $stage -Recurse -Force
}

$size = (Get-Item $zipPath).Length / 1KB

Write-Output ("Built {0} ({1:N0} KB, {2} files)" -f $zipPath, $size, $files.Count)
