const { printer: ThermalPrinter, types: PrinterTypes } = require('node-thermal-printer');

// Replace 'My Thermal Printer Name' with the actual name obtained from the getPrinters() call
let printer = new ThermalPrinter({
    type: PrinterTypes.EPSON, // Or STAR, ZPL, etc., depending on your printer's command set
    interface: 'printer:POSPrinter POS-80C'
});

async function sendPrintJob() {
    try {
        // Optional: Check if the printer is connected
        let isConnected = await printer.isPrinterConnected();
        console.log('Printer connected:', isConnected);

        if (isConnected) {
            printer.println('Hello World!');
            printer.println('This is a test print from Node.js.');
            printer.cut(); // Command to cut the paper
            
            // Execute the print job
            let execute = await printer.execute();
            console.log('Print job sent successfully:', execute);
        } else {
            console.error('Printer is not connected.');
        }
    } catch (error) {
        console.error('Printing failed:', error);
    }
}

sendPrintJob();