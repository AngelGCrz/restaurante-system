Printer Agent
=============

Small Node.js agent that runs on a Windows PC connected to a USB thermal printer.
It exposes an HTTP endpoint `/print` which accepts JSON POST with:

- `data`: base64-encoded ESC/POS bytes
- `printer`: optional printer name (Windows spooler name). If not provided agent uses env `PRINT_AGENT_PRINTER`.

Authentication: send header `Authorization: Bearer <token>` where token matches env `PRINT_AGENT_TOKEN`.

Install & run
--------------

1. On the PC with the printer, open PowerShell and install dependencies:

```powershell
cd scripts\print-agent
npm install
```

2. Set environment variables (example in PowerShell):

```powershell
$env:PRINT_AGENT_TOKEN = "mi-token-secreto"
$env:PRINT_AGENT_PRINTER = "POSPrinter POS-80C"
$env:PRINT_AGENT_PORT = "3000"
node index.js
```

(Or create a `.env` wrapper or Windows service.)

Server integration example (cURL)
--------------------------------

Assuming agent runs on `http://192.168.1.50:3000` and token `mi-token-secreto`:

```bash
curl -X POST "http://192.168.1.50:3000/print" \
  -H "Authorization: Bearer mi-token-secreto" \
  -H "Content-Type: application/json" \
  -d '{"data":"<BASE64_ESC_POS>", "printer":"POSPrinter POS-80C"}'
```

How to use with your Laravel app
--------------------------------

- Option A (push): modify `KitchenController::printOrder` to POST the ESC/POS base64 to the agent URL when a kitchen print is requested.
- Option B (pull): keep agent polling your server for pending jobs (more work server-side).

Security notes
--------------

- The agent listens on the network port you specify. If you expose it beyond your local network, secure it (firewall, TLS, strong token).
- After debugging, consider running agent as a Windows service and limit access to LAN only.
