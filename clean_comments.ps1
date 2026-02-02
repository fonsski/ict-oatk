param(
    [string]$Path = "."
)

function Remove-Comments {
    param(
        [string]$FilePath
    )
    
    try {
        $content = Get-Content -Path $FilePath -Raw -Encoding UTF8
        
        $content = $content -replace '//.*?$', '' -replace '(?s)/\*.*?\*/', ''
        
        Set-Content -Path $FilePath -Value $content -Encoding UTF8 -NoNewline
        return $true
    }
    catch {
        Write-Host "Error processing $FilePath : $_"
        return $false
    }
}

$extensions = @('*.php', '*.js', '*.ts', '*.css', '*.scss')
$processed = 0

foreach ($ext in $extensions) {
    Get-ChildItem -Path $Path -Filter $ext -Recurse -File | ForEach-Object {
        if (Remove-Comments -FilePath $_.FullName) {
            $processed++
        }
    }
}

Write-Host "Processed files: $processed"
