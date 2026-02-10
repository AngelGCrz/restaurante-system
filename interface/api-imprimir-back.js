/**
 * API DE IMPRESIÓN TÉRMICA – SAT Q22
 * HTML → Imagen → Impresora térmica
 */

const express = require("express");
const cors = require("cors");
const fs = require("fs");
const path = require("path");
const { exec } = require("child_process");
const puppeteer = require("puppeteer");
const { ThermalPrinter, PrinterTypes } = require("node-thermal-printer");

const app = express();
const PORT = 3000;

// =========================
// MIDDLEWARE
// =========================
app.use(express.json({ limit: "10mb" }));
app.use(express.urlencoded({ extended: true }));
app.use(cors());

// =========================
// HTML → IMAGEN (58mm)
// =========================
async function htmlAImagen(html) {
  const browser = await puppeteer.launch({
    headless: "new"
  });

  const page = await browser.newPage();

  await page.setViewport({
    width: 384, // 58mm
    height: 800,
    deviceScaleFactor: 1
  });

  await page.setContent(html, {
    waitUntil: "networkidle0"
  });

  const output = path.join(__dirname, `ticket_${Date.now()}.png`);

  await page.screenshot({
    path: output,
    fullPage: true
  });

  await browser.close();
  return output;
}

// =========================
// IMPRIMIR EN SAT Q22
// =========================
async function imprimirImagenTermica(imagen, nombreImpresora) {
  return new Promise((resolve, reject) => {
    const comando = `print /D:"${nombreImpresora}" "${imagen}"`;

    exec(comando, (err) => {
      if (err) return reject(err);
      fs.unlinkSync(imagen);
      resolve(true);
    });
  });
}



// =========================
// LISTAR IMPRESORAS
// =========================
function listarImpresoras() {
  return new Promise((resolve, reject) => {
    const comando =
      process.platform === "win32"
        ? "wmic printer get name"
        : "lpstat -p";

    exec(comando, (err, stdout) => {
      if (err) return reject(err);

      const impresoras = stdout
        .split("\n")
        .slice(1)
        .map(l => l.trim())
        .filter(Boolean);

      resolve(impresoras);
    });
  });
}

// =========================
// RUTAS
// =========================
app.get("/", (req, res) => {
  res.json({
    nombre: "API Impresión Térmica SAT Q22",
    version: "2.0.0"
  });
});

app.get("/estado", (req, res) => {
  res.json({
    estado: "activo",
    plataforma: process.platform,
    fecha: new Date().toISOString()
  });
});

app.get("/impresoras", async (req, res) => {
  try {
    const impresoras = await listarImpresoras();
    res.json({ success: true, impresoras });
  } catch (e) {
    res.status(500).json({ success: false, error: e.message });
  }
});

// =========================
// IMPRIMIR HTML
// =========================
app.post("/imprimir-html", async (req, res) => {
  try {
    const { html, impresora } = req.body;

    if (!html || !impresora) {
      return res.status(400).json({
        success: false,
        error: "Falta html o nombre de impresora"
      });
    }

    const imagen = await htmlAImagen(html);
    await imprimirImagenTermica(imagen, impresora);

    res.json({
      success: true,
      message: "HTML impreso correctamente en SAT Q22"
    });

  } catch (err) {
    res.status(500).json({
      success: false,
      error: err.message
    });
  }
});

// =========================
// INICIAR SERVIDOR
// =========================
app.listen(PORT, () => {
  console.log(`
╔════════════════════════════════════════╗
║   API IMPRESIÓN TÉRMICA SAT Q22        ║
╚════════════════════════════════════════╝

Servidor: http://localhost:${PORT}

POST /imprimir-html
GET  /impresoras
GET  /estado
`);
});

module.exports = app;
