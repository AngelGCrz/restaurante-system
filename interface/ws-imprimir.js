/**
 * API DE IMPRESIÓN TÉRMICA – SAT Q22
 * WebSocket → Impresora térmica
 */

const WebSocket = require("ws");
const { exec } = require("child_process");
const { ThermalPrinter, PrinterTypes } = require("node-thermal-printer");

// =========================
// CONFIG
// =========================
const PORT = 3000;

// =========================
// IMPRIMIR TICKET
// =========================
async function imprimirTicket(pedido) {
  const printer = new ThermalPrinter({
    type: PrinterTypes.EPSON,
    interface: "\\\\ANGEL-ASUS\\POSPrinter POS-80C",
    width: 48,
    encoding: "CP437",
    removeSpecialCharacters: true,
    lineCharacter: "-",
  });

  printer.alignCenter();
  printer.println("COCINA");
  printer.println("--------------------------------");

  printer.alignLeft();
  printer.println(`Pedido: #${pedido.order_id}`);
  printer.println(`Fecha: ${pedido.order_created_at}`);
  printer.println(`Cliente: ${pedido.order_customer_name}`);
  printer.println(`Mesa: ${pedido.order_table_label}`);
  printer.println("--------------------------------");

  pedido.items.forEach((item) => {
    printer.println(`${item.item_quantity}  X  ${item.item_product_name}`);
    if (item.item_product_comment) {
      printer.println(`   -> ${item.item_product_comment}`);
    }
  });

  printer.println("--------------------------------");
  printer.println("Comentario:");
  printer.println(pedido.order_comment || "");
  printer.newLine();
  printer.cut();

  const ok = await printer.execute();
  if (!ok) throw new Error("No se pudo imprimir");
}

// =========================
// WEBSOCKET SERVER
// =========================
const wss = new WebSocket.Server({ port: PORT });

console.log(`
╔════════════════════════════════════════╗
║  WS IMPRESIÓN TÉRMICA SAT Q22          ║
╚════════════════════════════════════════╝
ws://localhost:${PORT}
`);

wss.on("connection", (ws) => {
  console.log("🟢 Cliente conectado");

  ws.on("message", async (message) => {
    try {
      const data = JSON.parse(message.toString());

      if (data.action === "print-ticket") {
        if (!data.pedido) {
          throw new Error("Pedido no enviado");
        }

        await imprimirTicket(data.pedido);

        ws.send(
          JSON.stringify({
            success: true,
            event: "print-ok",
          }),
        );
      }
    } catch (err) {
      ws.send(
        JSON.stringify({
          success: false,
          event: "print-error",
          error: err.message,
        }),
      );
    }
  });

  ws.on("close", () => {
    console.log("🔴 Cliente desconectado");
  });
});
