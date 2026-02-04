<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;

class PrinterService
{
    /**
     * Print an order formatted for the kitchen using ESC/POS.
     * Supports:
     * - 'usb' mode: Direct USB POS printer (SAT Q22)
     * - 'network' mode: TCP to printer IP:port
     * - 'file' mode: fallback to storage
     * 
     * Config keys (use Setting::getValue):
     * - printer_kitchen_mode: 'usb' (default), 'network', or 'file'
     * - printer_kitchen_network_host: IP address (network mode)
     * - printer_kitchen_network_port: TCP port (default 9100)
     *
     * @param Order $order
     * @return bool
     */
    public function printKitchenOrder(Order $order): bool
    {
        $mode = Setting::getValue('printer_kitchen_mode', 'usb');

        if ($mode === 'usb') {
            return $this->printUSBESCPOS($order);
        }

        if ($mode === 'network') {
            return $this->printNetworkESCPOS($order);
        }

        // fallback to file mode
        $content = $this->formatKitchenContent($order);
        return $this->writeToFile($order->id, $content);
    }

    protected function printUSBESCPOS(Order $order): bool
    {
        try {
            // Try multiple methods to detect and use the USB printer
            
            // Method 1: Try to print via Windows Print Spooler (POS-80 printer)
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                if ($this->printViaPrintSpooler($order)) {
                    return true;
                }
            }

            // Method 2: Try direct USB device access
            $printerPath = $this->detectUSBPrinter();
            
            if (! empty($printerPath)) {
                $fp = $this->openUSBPrinter($printerPath);
                
                if ($fp) {
                    $printer = new Printer($fp);
                    $this->buildESCPOSContent($printer, $order);
                    $printer->close();

                    Log::info('PrinterService: sent ESC/POS kitchen order ' . $order->id . ' to USB printer');
                    return true;
                }
            }

            Log::warning('PrinterService: No USB POS printer detected. Falling back to file.');
            $content = $this->formatKitchenContent($order);
            return $this->writeToFile($order->id, $content);
            
        } catch (\Throwable $e) {
            Log::error('PrinterService USB print failed: ' . $e->getMessage());
            $content = $this->formatKitchenContent($order);
            return $this->writeToFile($order->id, $content);
        }
    }

    protected function printViaPrintSpooler(Order $order): bool
    {
        try {
            // Generate the ESC/POS commands as a string
            $content = $this->generateESCPOSRaw($order);

            // Create temp file with binary ESC/POS content
            $tempDir = sys_get_temp_dir();
            $tempFile = $tempDir . DIRECTORY_SEPARATOR . 'printer_' . $order->id . '_' . time() . '.prn';

            if (! file_put_contents($tempFile, $content)) {
                Log::error('PrinterService: Could not create temp file: ' . $tempFile);
                return false;
            }

            // Use PowerShell RawPrinter helper script to send raw bytes to spooler
            $script = base_path('scripts' . DIRECTORY_SEPARATOR . 'print_raw.ps1');
            $printerName = Setting::getValue('printer_kitchen_printer_name', 'POSPrinter POS-80C');

            $escapedPrinter = str_replace('"', '\\"', $printerName);
            $escapedFile = str_replace('"', '\\"', $tempFile);

            $cmd = sprintf('powershell -ExecutionPolicy Bypass -File "%s" -PrinterName "%s" -FilePath "%s"', $script, $escapedPrinter, $escapedFile);
            $result = @shell_exec($cmd . ' 2>&1');

            @unlink($tempFile);

            if ($result !== null && stripos($result, 'Error') !== false) {
                Log::error('PrinterService: Raw print script failed: ' . $result);
                return false;
            }

            Log::info('PrinterService: sent to printer queue "' . $printerName . '"');
            return true;
            
        } catch (\Throwable $e) {
            Log::error('PrinterService Print Spooler failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function findPOSPrinter(): ?string
    {
        return 'Printer POS-80';
    }

    protected function generateESCPOSRaw(Order $order): string
    {
        // Generate raw ESC/POS commands (binary)
        $output = "";

        // Use CRLF for better Windows/ESC-POS compatibility
        $nl = "\r\n";

        // Initialize printer
        $output .= "\x1B\x40";

        // Title - centered and bigger
        $output .= "\x1B\x61\x01"; // center
        $output .= "\x1D\x21\x11"; // double size
        $output .= "COCINA" . $nl;
        $output .= "\x1D\x21\x00"; // normal size

        // Left align for details
        $output .= "\x1B\x61\x00";
        $output .= $nl;

        // Order info
        $output .= "Pedido: #" . $order->id . $nl;
        $output .= "Fecha: " . $order->created_at->format('Y-m-d H:i') . $nl;
        $output .= "Mozo: " . optional($order->user)->name . $nl;

        // Tables if applicable
        $tables = is_array($order->table_numbers) ? $order->table_numbers : (array) $order->table_numbers;
        if (! empty($tables)) {
            $output .= "Mesa(s): " . implode(' + ', $tables) . $nl;
        }

        // Separator
        $output .= str_repeat("-", 32) . $nl;

        // Items
        foreach ($order->items as $item) {
            $name = optional($item->product)->name ?? 'Producto';
            $qty = $item->quantity;
            $output .= sprintf("%2d x %s", $qty, $name) . $nl;

            if (! empty($item->comment)) {
                $output .= "  -> " . $item->comment . $nl;
            }
        }

        // Order comment if exists
        if (! empty($order->comment)) {
            $output .= str_repeat("-", 32) . $nl;
            $output .= "Comentario:" . $nl;
            $output .= $order->comment . $nl;
        }

        // Footer and extra feeds to ensure full print
        $output .= $nl . $nl . $nl;
        $output .= str_repeat("=", 32) . $nl;

        // Cut paper (if supported)
        $output .= "\x1D\x56\x00" . $nl . $nl;

        // Convert to CP1252 (Windows Latin-1) to improve character compatibility
        if (function_exists('mb_convert_encoding')) {
            $output = mb_convert_encoding($output, 'CP1252', 'UTF-8');
        } else {
            $output = iconv('UTF-8', 'CP1252//TRANSLIT', $output);
        }

        return $output;
    }

    protected function detectUSBPrinter(): ?string
    {
        // Try Windows WinUSB approach first
        $printerPath = $this->getWindowsUSBPrinterPath();
        if ($printerPath) {
            return $printerPath;
        }

        // Try /dev/usb approach on Linux
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            $matches = glob('/dev/usb/lp*') ?: glob('/dev/lp*') ?: glob('/dev/usb/printer*');
            if ($matches) {
                return reset($matches);
            }
        }

        return null;
    }

    protected function getWindowsUSBPrinterPath(): ?string
    {
        // On Windows, we need to find the USB device using available COM ports
        // or attempt to use the printer directly via USB vendor/product ID
        
        // First, try to get printer via Windows Print Spooler
        if (function_exists('exec')) {
            $output = @shell_exec('wmic logicalprinterdevice get name,deviceid 2>nul');
            if (strpos($output, 'POS') !== false || strpos($output, 'printer') !== false) {
                // Printer found in WMI, try common USB paths
                for ($i = 0; $i < 10; $i++) {
                    $port = "LPT" . ($i + 1);
                    if ($this->isPortAccessible($port)) {
                        return $port;
                    }
                }
            }
        }

        // Try to find via installed printers
        $printers = @shell_exec('powershell -Command "Get-Printer | Select-Object Name" 2>nul');
        if (strpos($printers, 'POS') !== false) {
            // POS printer found, try USB ports
            for ($i = 1; $i <= 10; $i++) {
                $port = "USB" . sprintf('%03d', $i);
                $path = "\\\\.\\{$port}";
                if (@fopen($path, 'w+b')) {
                    return $path;
                }
            }
        }

        // Fallback: try common USB paths
        return null;
    }

    protected function openUSBPrinter($printerPath)
    {
        // Try direct port opening
        if (@file_exists($printerPath) || strpos($printerPath, 'LPT') === 0 || strpos($printerPath, 'USB') === 0) {
            $fp = @fopen($printerPath . ':', 'w+b');
            if ($fp) {
                return $fp;
            }
        }

        // Try Windows device path
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $devicePath = "\\\\.\\{$printerPath}";
            $fp = @fopen($devicePath, 'w+b');
            if ($fp) {
                return $fp;
            }
        }

        return null;
    }

    protected function isPortAccessible(string $port): bool
    {
        $fp = @fopen($port . ':', 'w+b');
        if ($fp) {
            @fclose($fp);
            return true;
        }
        return false;
    }

    protected function printNetworkESCPOS(Order $order): bool
    {
        $host = Setting::getValue('printer_kitchen_network_host', null);
        $port = (int) Setting::getValue('printer_kitchen_network_port', 9100);

        if (empty($host)) {
            Log::warning('PrinterService: network mode selected but host not configured. Falling back to file.');
            $content = $this->formatKitchenContent($order);
            return $this->writeToFile($order->id, $content);
        }

        try {
            $fp = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 2);
            if (! $fp) {
                Log::error("PrinterService: network connection failed to {$host}:{$port}: {$errstr} ({$errno})");
                $content = $this->formatKitchenContent($order);
                return $this->writeToFile($order->id, $content);
            }

            $printer = new Printer($fp);
            $this->buildESCPOSContent($printer, $order);
            $printer->close();

            Log::info('PrinterService: sent ESC/POS kitchen order ' . $order->id . ' to ' . $host . ':' . $port);
            return true;
        } catch (\Throwable $e) {
            Log::error('PrinterService network print failed: ' . $e->getMessage());
            $content = $this->formatKitchenContent($order);
            return $this->writeToFile($order->id, $content);
        }
    }

    protected function buildESCPOSContent(Printer $printer, Order $order): void
    {
        $printer->initialize();

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->setTextSize(2, 2);
        $printer->text("COCINA");
        $printer->setTextSize(1, 1);

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->feed();

        $printer->text("Pedido: #" . $order->id);
        $printer->text("Fecha: " . $order->created_at->format('Y-m-d H:i'));
        $printer->text("Mozo: " . optional($order->user)->name);

        $tables = is_array($order->table_numbers) ? $order->table_numbers : (array) $order->table_numbers;
        if (! empty($tables)) {
            $printer->text("Mesa(s): " . implode(' + ', $tables));
        }

        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text(str_repeat("-", 32));
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->feed();

        foreach ($order->items as $item) {
            $name = optional($item->product)->name ?? 'Producto';
            $qty = $item->quantity;
            $printer->text(sprintf("%2d x %s", $qty, $name));

            if (! empty($item->comment)) {
                $printer->text("  -> " . $item->comment);
            }
        }

        if (! empty($order->comment)) {
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text(str_repeat("-", 32));
            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->feed();
            $printer->text("Comentario:");
            $printer->text($order->comment);
        }

        $printer->feed(2);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text(str_repeat("=", 32));
        $printer->feed();

        $printer->cut();
    }

    protected function formatKitchenContent(Order $order): string
    {
        $lines = [];
        $lines[] = "===== COCINA =====";
        $lines[] = "Pedido: #" . $order->id;
        $lines[] = "Fecha: " . $order->created_at->format('Y-m-d H:i');
        $lines[] = "Mozo: " . optional($order->user)->name;

        $tables = is_array($order->table_numbers) ? $order->table_numbers : (array) $order->table_numbers;
        if (! empty($tables)) {
            $lines[] = "Mesa(s): " . implode(' + ', $tables);
        }

        $lines[] = str_repeat('-', 20);
        foreach ($order->items as $item) {
            $name = optional($item->product)->name ?? 'Producto';
            $lines[] = sprintf("%3s x %s", $item->quantity, $name);
            if (! empty($item->comment)) {
                $lines[] = "  -> " . $item->comment;
            }
        }

        if (! empty($order->comment)) {
            $lines[] = str_repeat('-', 20);
            $lines[] = "Comentario del pedido:";
            $lines[] = $order->comment;
        }

        $lines[] = str_repeat('\n', 2);

        return implode("\n", $lines) . "\n";
    }

    protected function writeToFile($orderId, string $content): bool
    {
        try {
            $folder = storage_path('app/kitchen_prints');
            if (! is_dir($folder)) {
                @mkdir($folder, 0755, true);
            }

            $file = $folder . DIRECTORY_SEPARATOR . 'order_' . $orderId . '_' . time() . '.txt';
            file_put_contents($file, $content);
            Log::info('PrinterService: wrote kitchen print to ' . $file);
            return true;
        } catch (\Throwable $e) {
            Log::error('PrinterService writeToFile failed: ' . $e->getMessage());
            return false;
        }
    }
}
