<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class ConfigurePrinter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'printer:configure {--host=} {--port=} {--mode=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure SAT Q22 printer settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $mode = $this->option('mode') ?? $this->choice(
            'Select printer mode',
            ['usb', 'network', 'file'],
            0
        );

        $usbPort = null;
        $host = null;
        $port = null;

        if ($mode === 'usb') {
            $usbPort = $this->option('port') ?? $this->askUSBPort();
        } elseif ($mode === 'network') {
            $host = $this->option('host');
            $port = $this->option('port') ?? 9100;

            if (! $host) {
                $host = $this->ask('Enter printer IP address (e.g., 192.168.1.100)');
                $port = $this->ask('Enter printer TCP port', 9100);
            }
        }

        Setting::updateOrCreate(
            ['key' => 'printer_kitchen_mode'],
            ['value' => $mode]
        );

        if ($mode === 'usb') {
            Setting::updateOrCreate(
                ['key' => 'printer_kitchen_usb_port'],
                ['value' => $usbPort]
            );
        } elseif ($mode === 'network') {
            Setting::updateOrCreate(
                ['key' => 'printer_kitchen_network_host'],
                ['value' => $host]
            );

            Setting::updateOrCreate(
                ['key' => 'printer_kitchen_network_port'],
                ['value' => (string) $port]
            );
        }

        $this->info('✓ Printer configured successfully!');
        $this->displayConfig($mode, $host, $port, $usbPort);
    }

    protected function askUSBPort(): string
    {
        $ports = $this->detectUSBPorts();

        if (! empty($ports)) {
            $this->info('Detected USB/Serial ports:');
            foreach ($ports as $p) {
                $this->line("  - {$p}");
            }

            $ports[] = 'Manual entry';
            $selected = $this->choice('Select a port or enter manually', $ports);

            if ($selected === 'Manual entry') {
                return $this->ask('Enter port (e.g., COM3, /dev/ttyUSB0)');
            }

            return $selected;
        }

        $this->warn('No USB ports detected. Enter manually.');
        return $this->ask('Enter port (e.g., COM3, /dev/ttyUSB0)');
    }

    protected function detectUSBPorts(): array
    {
        $ports = [];
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            // Windows: Check for COM ports using wmic or device manager
            $output = @shell_exec('wmic logicaldisk get name 2>nul || mode 2>nul');
            
            // Simple detection: look for COM1-COM9
            for ($i = 1; $i <= 9; $i++) {
                $port = "COM{$i}";
                if ($this->isPortAvailable($port)) {
                    $ports[] = $port;
                }
            }
        } else {
            // Linux/Mac: Look for /dev/ttyUSB* or /dev/ttyS*
            $devPaths = ['/dev/ttyUSB*', '/dev/ttyS*', '/dev/tty.usbserial*'];
            
            foreach ($devPaths as $pattern) {
                $matches = glob($pattern);
                if ($matches) {
                    $ports = array_merge($ports, $matches);
                }
            }
        }

        return array_unique($ports);
    }

    protected function isPortAvailable(string $port): bool
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Try to open COM port with minimal timeout
            $handle = @fopen($port . ':', 'r+b');
            if ($handle) {
                @fclose($handle);
                return true;
            }
            return false;
        }

        // Unix: check if device exists
        return file_exists($port);
    }

    protected function displayConfig(string $mode, ?string $host, ?int $port, ?string $usbPort): void
    {
        $tableData = [['Key', 'Value']];
        $tableData[] = ['printer_kitchen_mode', $mode];

        if ($mode === 'usb') {
            $tableData[] = ['printer_kitchen_usb_port', $usbPort];
        } elseif ($mode === 'network') {
            $tableData[] = ['printer_kitchen_network_host', $host ?? 'N/A'];
            $tableData[] = ['printer_kitchen_network_port', $port ?? 'N/A'];
        }

        $this->table(
            $tableData[0],
            array_slice($tableData, 1)
        );
    }
}
