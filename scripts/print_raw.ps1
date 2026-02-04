param(
    [string]$PrinterName,
    [string]$FilePath
)

if (-not (Test-Path $FilePath)) {
    Write-Error "File not found: $FilePath"
    exit 2
}

$bytes = [System.IO.File]::ReadAllBytes($FilePath)

Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;

public class RawPrinterHelper {
    [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Ansi)]
    public struct DOCINFOA {
        [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
    }

    [DllImport("winspool.Drv", EntryPoint="OpenPrinterA", SetLastError=true, CharSet=CharSet.Ansi)]
    public static extern bool OpenPrinter(string szPrinter, out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.Drv", SetLastError=true)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError=true)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, int level, ref DOCINFOA di);

    [DllImport("winspool.Drv", SetLastError=true)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError=true)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError=true)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", SetLastError=true)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, int dwCount, out int dwWritten);
}
"@

$hPrinter = [IntPtr]::Zero
$di = New-Object RawPrinterHelper+DOCINFOA
$di.pDocName = "RawPrint"
$di.pDataType = "RAW"

if (-not [RawPrinterHelper]::OpenPrinter($PrinterName, [ref]$hPrinter, [IntPtr]::Zero)) {
    Write-Error "OpenPrinter failed for $PrinterName"
    exit 3
}

if (-not [RawPrinterHelper]::StartDocPrinter($hPrinter, 1, [ref]$di)) {
    [RawPrinterHelper]::ClosePrinter($hPrinter)
    Write-Error "StartDocPrinter failed"
    exit 4
}

[RawPrinterHelper]::StartPagePrinter($hPrinter) | Out-Null

$ptr = [System.Runtime.InteropServices.Marshal]::AllocHGlobal($bytes.Length)
[System.Runtime.InteropServices.Marshal]::Copy($bytes, 0, $ptr, $bytes.Length)

$written = 0
[RawPrinterHelper]::WritePrinter($hPrinter, $ptr, $bytes.Length, [ref]$written) | Out-Null

[System.Runtime.InteropServices.Marshal]::FreeHGlobal($ptr)

[RawPrinterHelper]::EndPagePrinter($hPrinter) | Out-Null
[RawPrinterHelper]::EndDocPrinter($hPrinter) | Out-Null
[RawPrinterHelper]::ClosePrinter($hPrinter) | Out-Null

if ($written -lt $bytes.Length) {
    Write-Error "Only wrote $written of $($bytes.Length) bytes"
    exit 5
}

exit 0
