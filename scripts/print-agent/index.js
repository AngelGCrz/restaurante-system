require('dotenv').config({ path: '.env.local' });
const express = require('express');
const bodyParser = require('body-parser');
const fs = require('fs');
const os = require('os');
const path = require('path');
const { execFile } = require('child_process');
const axios = require('axios');

const PORT = process.env.PRINT_AGENT_PORT || 3000;
const DEFAULT_PRINTER = process.env.PRINT_AGENT_PRINTER || '';
const API_URL = process.env.API_URL;
const PULL_INTERVAL = parseInt(process.env.PULL_INTERVAL || '5', 10) * 1000;
const PS_SCRIPT = path.join(__dirname, '..', 'print_raw.ps1');

const app = express();
app.use(bodyParser.json());

function printBytes(printerName, bytes, source = 'LOCAL') {
    const tmpFile = path.join(os.tmpdir(), `print_${Date.now()}.prn`);
    fs.writeFileSync(tmpFile, bytes);

    const ps = process.platform === 'win32' ? 'powershell' : 'pwsh';
    const args = ['-ExecutionPolicy', 'Bypass', '-File', PS_SCRIPT, '-PrinterName', printerName, '-FilePath', tmpFile];

    execFile(ps, args, { windowsHide: true }, (error, stdout, stderr) => {
        try { fs.unlinkSync(tmpFile); } catch (_) {}
        if (error) {
            console.log('❌ PRINT FAILED');
            console.error(stderr || error);
            return;
        }
        console.log(`✅ PRINT OK → ${source}`);
    });
}

// Pull mode: revisa órdenes pendientes cada X segundos
async function pullOrders() {
    if (!API_URL) {
        console.log('❌ API_URL no configurada');
        return;
    }

    try {
        const resp = await axios.get(API_URL);
        const orders = resp.data.orders || [];

        for (const order of orders) {
            console.log('==========================');
            console.log(`🖨️  Imprimiendo pedido #${order.id}`);

            const bytes = Buffer.from(order.escpos_base64, 'base64');
            printBytes(DEFAULT_PRINTER, bytes, 'LOCAL');

            // Marcar orden como impresa en Laravel
            try {
                await axios.post(`${API_URL}/${order.id}/printed`);
            } catch (err) {
                console.warn('No se pudo marcar la orden como impresa:', err.message);
            }
        }
    } catch (err) {
        console.error('Error al consultar órdenes pendientes:', err.message);
    }
}

// Inicia loop de pull
setInterval(pullOrders, PULL_INTERVAL);
console.log(`Agente de impresión corriendo en modo PULL cada ${PULL_INTERVAL/1000}s`);
console.log('Asegúrate de tener PRINT_AGENT_PRINTER configurada correctamente');

// Servidor express (opcional, solo para healthcheck)
app.get('/health', (_, res) => res.json({ ok: true }));
app.listen(PORT, () => {
    console.log(`Healthcheck disponible en http://localhost:${PORT}/health`);
});
