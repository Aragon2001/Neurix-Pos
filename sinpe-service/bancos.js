/**
 * @package   Neurix POS
 * @author    Jostin Aragón Barboza
 * @copyright Arasoft Solutions
 */

// ── Formatos por banco ───────────────────────────────────────────────────────
// Cada banco define sus propios patrones. El motor prueba primero los patrones
// del banco seleccionado y, si no encuentra nada, cae a los genéricos.

const GENERICOS = {
  monto: [
    /monto[:\s]+(?:CRC|₡|¢|colones)?\s*([\d.,]+)/i,
    /(?:CRC|₡|¢)\s*([\d.,]+)/i,
    /por\s+(?:un\s+monto\s+de\s+)?(?:CRC|₡|¢)?\s*([\d.,]+)\s*(?:colones)?/i,
    /transferencia\s+de\s+(?:CRC|₡|¢)?\s*([\d.,]+)/i,
    /([\d.,]+)\s+colones/i,
    /amount[:\s]+(?:CRC)?\s*([\d.,]+)/i,
  ],
  nombre: [
    /de\s+parte\s+de[:\s]+([A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]{3,60})/i,
    /remitente[:\s]+([A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]{3,60})/i,
    /enviado\s+por[:\s]+([A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]{3,60})/i,
    /nombre\s+del\s+cliente[:\s]+([A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]{3,60})/i,
    /transferencia\s+de\s+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s]{5,60})/i,
    /cliente[:\s]+([A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ\s]{5,60})/i,
  ],
  comprobante: [
    /comprobante[:\s#]+([A-Z0-9\-]{6,30})/i,
    /n[úu]mero\s+de\s+(?:comprobante|transacci[oó]n|referencia|documento)[:\s]+([A-Z0-9\-]{6,30})/i,
    /referencia[:\s]+([A-Z0-9\-]{6,30})/i,
    /transacci[oó]n[:\s#]+([A-Z0-9\-]{6,30})/i,
    /\b(CR\d{12,20})\b/i,
  ],
  telefono: [
    /tel[eé]fono[:\s]+(\d[\d\s\-]{6,11})/i,
    /n[úu]mero[:\s]+(\d[\d\s\-]{6,11})/i,
    /celular[:\s]+(\d[\d\s\-]{6,11})/i,
    /desde\s+(?:el\s+)?(?:n[úu]mero\s+)?(\d{4}[\s\-]?\d{4})/i,
    /\b([2-9]\d{3}[\s-]?\d{4})\b/,
  ],
  descripcion: [
    /(?:descripci[oó]n|motivo|mensaje|nota|detalle|concepto)[:\s]+(.{5,120}?)(?:\n|\r|$)/i,
  ],
};

export const BANCOS = {
  auto: {
    id: "auto",
    nombre: "Detección automática (todos los bancos)",
    descripcion: "Prueba los formatos de todos los bancos en orden. Usalo si recibís SINPE de varios bancos distintos.",
    remitentes: [],
    // 'auto' no aporta patrones propios: el motor recorre todos los bancos.
    patrones: {},
  },

  davivienda: {
    id: "davivienda",
    nombre: "Davivienda",
    descripcion: "Formato: «Ha recibido 3,950.00 Colones de NOMBRE por SINPE Movil. Comprobante 2026...»",
    remitentes: ["davivienda.cr", "davivienda.com"],
    // Unica direccion que manda avisos de SINPE. Los demas correos del banco
    // describen el mismo movimiento y lo duplicarian sin comprobante.
    remitentesSinpe: ["notificaciones.gx@davivienda.cr"],
    patrones: {
      deteccion: [/davivienda/i, /ha\s+recibido\s+[\d.,]+\s+colones/i],
      monto: [/ha\s+recibido\s+([\d.,]+)\s+colones/i],
      // Davivienda a veces manda el nombre con guiones bajos en vez de espacios
      // (ROSA_MARIA_PALMA_MON), así que la clase acepta ambos separadores.
      nombre: [/colones\s+de\s+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s_]{5,60}?)\s+por\s+sinpe/i],
      comprobante: [/comprobante\s+(\d[\d\s]{8,30}\d)/i],
    },
  },

  bac: {
    id: "bac",
    nombre: "BAC Credomatic",
    descripcion: "Formato: «Recibiste una transferencia SINPE de NOMBRE por ₡5,000.00»",
    remitentes: ["baccredomatic.com", "notificacion@baccredomatic.com"],
    patrones: {
      deteccion: [/bac\s*credomatic/i, /recibiste\s+una\s+transferencia/i],
      monto: [
        /recibiste\s+una\s+transferencia[^₡¢]{0,80}?(?:por\s+)?(?:CRC|₡|¢)\s*([\d.,]+)/i,
        /monto\s*(?:CRC|₡|¢)?\s*[:\s]\s*(?:CRC|₡|¢)?\s*([\d.,]+)/i,
      ],
      nombre: [
        /transferencia\s+sinpe\s+de\s+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s.]{4,60}?)\s+por\s/i,
        /(?:de|remitente)[:\s]+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s.]{4,60}?)(?:\s+por\s|\n|\r|$)/i,
      ],
      comprobante: [
        /(?:referencia|comprobante)[:\s#]+([A-Z0-9\-]{6,30})/i,
        /n[úu]mero\s+de\s+referencia[:\s]+([A-Z0-9\-]{6,30})/i,
      ],
    },
  },

  bcr: {
    id: "bcr",
    nombre: "Banco de Costa Rica (BCR)",
    descripcion: "Formato: «Transferencia SINPE recibida de NOMBRE — Monto ¢5.000,00»",
    remitentes: ["bancobcr.com", "bancobcr.cr"],
    patrones: {
      deteccion: [/banco\s+de\s+costa\s+rica/i, /\bbcr\b/i],
      monto: [
        /monto[:\s]+(?:CRC|₡|¢)?\s*([\d.,]+)/i,
        /(?:₡|¢)\s*([\d.,]+)/i,
      ],
      nombre: [
        /sinpe\s+recibida\s+de\s+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s.]{4,60}?)(?:\s+por\s|\n|\r|$)/i,
        /(?:origen|de)[:\s]+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s.]{4,60}?)(?:\n|\r|$)/i,
      ],
      comprobante: [
        /(?:documento|comprobante|referencia)[:\s#]+([A-Z0-9\-]{6,30})/i,
      ],
    },
  },

  bn: {
    id: "bn",
    nombre: "Banco Nacional (BN)",
    descripcion: "Formato: «BN SINPE Móvil — Ha recibido ¢5,000.00 de NOMBRE»",
    remitentes: ["bncr.fi.cr", "bncr.com"],
    patrones: {
      deteccion: [/banco\s+nacional/i, /\bbncr\b/i, /bn\s+sinpe/i],
      monto: [
        /ha\s+recibido\s+(?:CRC|₡|¢)?\s*([\d.,]+)/i,
        /monto[:\s]+(?:CRC|₡|¢)?\s*([\d.,]+)/i,
      ],
      nombre: [
        /(?:de|remitente)[:\s]+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s.]{4,60}?)(?:\s+por\s|\n|\r|$)/i,
      ],
      comprobante: [
        /(?:comprobante|referencia|documento)[:\s#]+([A-Z0-9\-]{6,30})/i,
      ],
    },
  },

  popular: {
    id: "popular",
    nombre: "Banco Popular",
    descripcion: "Formato: «Popular SINPE Móvil — transferencia recibida de NOMBRE»",
    remitentes: ["bp.fi.cr", "popular.fi.cr"],
    patrones: {
      deteccion: [/banco\s+popular/i, /popularenlinea/i],
      monto: [
        /monto[:\s]+(?:CRC|₡|¢)?\s*([\d.,]+)/i,
        /(?:₡|¢)\s*([\d.,]+)/i,
      ],
      nombre: [
        /(?:recibida\s+de|de)[:\s]+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s.]{4,60}?)(?:\s+por\s|\n|\r|$)/i,
      ],
      comprobante: [
        /(?:comprobante|referencia)[:\s#]+([A-Z0-9\-]{6,30})/i,
      ],
    },
  },

  promerica: {
    id: "promerica",
    nombre: "Promerica",
    descripcion: "Formato genérico de notificación SINPE de Promerica.",
    remitentes: ["promerica.fi.cr"],
    patrones: {
      deteccion: [/promerica/i],
      monto: [/monto[:\s]+(?:CRC|₡|¢)?\s*([\d.,]+)/i, /(?:₡|¢)\s*([\d.,]+)/i],
      nombre: [/(?:de|remitente)[:\s]+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s.]{4,60}?)(?:\n|\r|$)/i],
      comprobante: [/(?:comprobante|referencia)[:\s#]+([A-Z0-9\-]{6,30})/i],
    },
  },

  scotiabank: {
    id: "scotiabank",
    nombre: "Scotiabank",
    descripcion: "Formato genérico de notificación SINPE de Scotiabank.",
    remitentes: ["scotiabank.com", "scotiabankcr.com"],
    patrones: {
      deteccion: [/scotiabank/i],
      monto: [/monto[:\s]+(?:CRC|₡|¢)?\s*([\d.,]+)/i, /(?:₡|¢)\s*([\d.,]+)/i],
      nombre: [/(?:de|remitente)[:\s]+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s.]{4,60}?)(?:\n|\r|$)/i],
      comprobante: [/(?:comprobante|referencia)[:\s#]+([A-Z0-9\-]{6,30})/i],
    },
  },

  lafise: {
    id: "lafise",
    nombre: "Lafise",
    descripcion: "Formato genérico de notificación SINPE de Lafise.",
    remitentes: ["lafise.com"],
    patrones: {
      deteccion: [/lafise/i],
      monto: [/monto[:\s]+(?:CRC|₡|¢)?\s*([\d.,]+)/i, /(?:₡|¢)\s*([\d.,]+)/i],
      nombre: [/(?:de|remitente)[:\s]+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s.]{4,60}?)(?:\n|\r|$)/i],
      comprobante: [/(?:comprobante|referencia)[:\s#]+([A-Z0-9\-]{6,30})/i],
    },
  },

  coopealianza: {
    id: "coopealianza",
    nombre: "Coopealianza",
    descripcion: "Formato genérico de notificación SINPE de Coopealianza.",
    remitentes: ["coopealianza.fi.cr"],
    patrones: {
      deteccion: [/coopealianza/i],
      monto: [/monto[:\s]+(?:CRC|₡|¢)?\s*([\d.,]+)/i, /(?:₡|¢)\s*([\d.,]+)/i],
      nombre: [/(?:de|remitente)[:\s]+([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑa-záéíóúñ\s.]{4,60}?)(?:\n|\r|$)/i],
      comprobante: [/(?:comprobante|referencia)[:\s#]+([A-Z0-9\-]{6,30})/i],
    },
  },
};

// Orden en que 'auto' prueba los bancos (Davivienda primero: formato confirmado).
export const ORDEN_AUTO = [
  "davivienda", "bac", "bcr", "bn", "popular",
  "promerica", "scotiabank", "lafise", "coopealianza",
];

export function listaBancos() {
  return Object.values(BANCOS).map(b => ({
    id: b.id,
    nombre: b.nombre,
    descripcion: b.descripcion,
  }));
}

/** Detecta a qué banco pertenece un correo por su contenido/remitente. */
export function detectarBanco(text, from = "") {
  const hay = `${from}\n${text}`;
  for (const id of ORDEN_AUTO) {
    const b = BANCOS[id];
    if (b.remitentes.some(r => from.toLowerCase().includes(r))) return id;
    if ((b.patrones.deteccion || []).some(re => re.test(hay))) return id;
  }
  return null;
}

/**
 * Valida el remitente contra `remitentesSinpe` del banco.
 * Sin esa lista no se restringe.
 */
export function remitenteValidoSinpe(bancoId, from = "") {
  const b = BANCOS[bancoId];
  const permitidos = b?.remitentesSinpe;
  if (!permitidos || !permitidos.length) return true;
  const f = String(from).toLowerCase();
  return permitidos.some(r => f.includes(r.toLowerCase()));
}

/**
 * Devuelve la lista de patrones a probar para un campo, según el banco elegido.
 * Prioridad: banco seleccionado → resto de bancos (solo en 'auto') → genéricos.
 */
export function patronesPara(campo, bancoId) {
  const out = [];
  const push = (arr) => { for (const re of arr || []) if (!out.includes(re)) out.push(re); };

  if (bancoId && bancoId !== "auto" && BANCOS[bancoId]) {
    push(BANCOS[bancoId].patrones[campo]);
  } else {
    for (const id of ORDEN_AUTO) push(BANCOS[id].patrones[campo]);
  }
  push(GENERICOS[campo]);
  return out;
}

export { GENERICOS };
