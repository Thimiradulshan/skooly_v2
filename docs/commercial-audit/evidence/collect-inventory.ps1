# Read-only application inventory. Writes only audit evidence beside this script.
$ErrorActionPreference = 'Stop'
$root = (Resolve-Path "$PSScriptRoot/../../..").Path
$routes = (& php "$root/artisan" route:list --json | ConvertFrom-Json)
$views = Get-ChildItem "$root/resources/views" -Recurse -Filter '*.blade.php'
$tests = Get-ChildItem "$root/tests" -Recurse -Filter '*.php'
$rows = foreach ($route in $routes) {
    $viewRefs = @()
    $testRefs = @()
    if ($route.name) {
        $viewRefs = @($views | Select-String -SimpleMatch ("'" + $route.name + "'") | ForEach-Object { $_.Path.Substring($root.Length + 1).Replace('\', '/') + ':' + $_.LineNumber })
        $testRefs = @($tests | Select-String -SimpleMatch ("'" + $route.name + "'") | ForEach-Object { $_.Path.Substring($root.Length + 1).Replace('\', '/') + ':' + $_.LineNumber })
    }
    [pscustomobject]@{
        Name = $route.name
        Method = $route.method
        Uri = $route.uri
        Action = $route.action
        Middleware = $route.middleware -join '; '
        LiteralViewReferences = $viewRefs -join '; '
        LiteralTestReferences = $testRefs -join '; '
        BrowserEndToEnd = 'NOT VERIFIED'
    }
}
$rows | Export-Csv "$PSScriptRoot/route-ledger.csv" -NoTypeInformation -Encoding utf8
$areas = 'app','routes','database','resources','public/js','public/css','config','tests'
$files = foreach ($area in $areas) {
    Get-ChildItem "$root/$area" -Recurse -File | ForEach-Object {
        [pscustomobject]@{Path=$_.FullName.Substring($root.Length+1).Replace('\','/'); Bytes=$_.Length; SHA256=(Get-FileHash $_.FullName -Algorithm SHA256).Hash}
    }
}
$files | Export-Csv "$PSScriptRoot/file-inventory.csv" -NoTypeInformation -Encoding utf8
[pscustomobject]@{Routes=$rows.Count; ApplicationRoutes=@($rows | Where-Object Action -like 'App\*').Count; SourceFiles=$files.Count; Views=$views.Count; ApplicationRoutesWithLiteralViewReference=@($rows | Where-Object { $_.Action -like 'App\*' -and $_.LiteralViewReferences }).Count; ApplicationRoutesWithLiteralTestReference=@($rows | Where-Object { $_.Action -like 'App\*' -and $_.LiteralTestReferences }).Count} | Format-List
