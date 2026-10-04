/*
 * Builds a printable check-in poster (logo · service name · date · QR · link)
 * on a canvas, and exports it as PNG, PDF or a print of just the poster.
 * No dependencies: the PDF is a one-page A4 file wrapping a JPEG of the poster.
 */
import { fileUrl } from '@/Plugins/composables';

const W = 1240; // A4 at 150 dpi
const H = 1754;

const loadImage = (src) =>
    new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });

const svgImage = (svg) => {
    const url = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml' }));
    return loadImage(url).finally(() => URL.revokeObjectURL(url));
};

// Wrap text onto at most `maxLines` lines, centred at x.
const wrapText = (ctx, text, x, y, maxWidth, lineHeight, maxLines = 2) => {
    const words = String(text).split(/\s+/);
    const lines = [];
    let line = '';
    for (const w of words) {
        const test = line ? `${line} ${w}` : w;
        if (ctx.measureText(test).width > maxWidth && line) {
            lines.push(line);
            line = w;
        } else line = test;
    }
    if (line) lines.push(line);
    if (lines.length > maxLines) {
        lines.length = maxLines;
        lines[maxLines - 1] = lines[maxLines - 1].replace(/\s*\S*$/, '…');
    }
    lines.forEach((l, i) => ctx.fillText(l, x, y + i * lineHeight));
    return y + lines.length * lineHeight;
};

/** @param {{ svg: string, title: string, subtitle?: string, url: string }} o */
export async function renderPoster({ svg, title, subtitle, url }) {
    await document.fonts?.ready;
    const canvas = document.createElement('canvas');
    canvas.width = W;
    canvas.height = H;
    const ctx = canvas.getContext('2d');
    const font = (weight, size) => `${weight} ${size}px Poppins, Inter, -apple-system, "Segoe UI", sans-serif`;

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, H);

    // Brand band
    const band = ctx.createLinearGradient(0, 0, W, 300);
    band.addColorStop(0, '#148ddd');
    band.addColorStop(1, '#0a4f86');
    ctx.fillStyle = band;
    ctx.fillRect(0, 0, W, 300);

    let logo = null;
    try {
        logo = await loadImage(fileUrl('assets/images/ica_logo.jpg'));
    } catch {}
    ctx.textAlign = 'center';
    if (logo) {
        ctx.save();
        ctx.beginPath();
        ctx.roundRect(W / 2 - 70, 50, 140, 140, 32);
        ctx.clip();
        ctx.drawImage(logo, W / 2 - 70, 50, 140, 140);
        ctx.restore();
    }
    ctx.fillStyle = '#ffffff';
    ctx.font = font(600, 34);
    ctx.fillText('International Christian Assembly', W / 2, logo ? 250 : 170);

    // Heading
    ctx.fillStyle = '#148ddd';
    ctx.font = font(700, 34);
    ctx.fillText('SCAN TO CHECK IN', W / 2, 400);

    ctx.fillStyle = '#1f2933';
    ctx.font = font(700, 64);
    let y = wrapText(ctx, title, W / 2, 490, W - 200, 76);
    if (subtitle) {
        ctx.fillStyle = '#5b6b7a';
        ctx.font = font(500, 36);
        ctx.fillText(subtitle, W / 2, y + 10);
        y += 50;
    }

    // QR in a soft frame
    const size = 760;
    const qx = (W - size) / 2;
    const qy = Math.max(y + 50, 680);
    ctx.fillStyle = '#f1f6fb';
    ctx.beginPath();
    ctx.roundRect(qx - 40, qy - 40, size + 80, size + 80, 40);
    ctx.fill();
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(qx, qy, size, size);
    ctx.imageSmoothingEnabled = false; // keep modules crisp
    ctx.drawImage(await svgImage(svg), qx, qy, size, size);
    ctx.imageSmoothingEnabled = true;

    // Instructions + link
    const by = qy + size + 120;
    ctx.fillStyle = '#1f2933';
    ctx.font = font(500, 34);
    ctx.fillText('Open your phone camera, point it at the code', W / 2, by);
    ctx.fillText('and tap the link to mark yourself present.', W / 2, by + 48);
    ctx.fillStyle = '#148ddd';
    ctx.font = font(600, 28);
    ctx.fillText(url.replace(/^https?:\/\//, ''), W / 2, by + 120);

    return canvas;
}

const slug = (s) => String(s).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '') || 'check-in';

const save = (blob, name) => {
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(a.href), 1000);
};

export async function downloadPng(opts) {
    const canvas = await renderPoster(opts);
    const blob = await new Promise((r) => canvas.toBlob(r, 'image/png'));
    save(blob, `${slug(opts.title)}-check-in-qr.png`);
}

/** Minimal single-page A4 PDF containing the poster as a JPEG (DCTDecode). */
function pdfFromJpeg(jpeg, imgW, imgH) {
    const enc = new TextEncoder();
    const pageW = 595.28;
    const pageH = 841.89;
    const draw = `q ${pageW} 0 0 ${pageH} 0 0 cm /Im0 Do Q`;
    const parts = [];
    const offsets = [];
    let length = 0;
    const push = (chunk) => {
        const bytes = typeof chunk === 'string' ? enc.encode(chunk) : chunk;
        parts.push(bytes);
        length += bytes.length;
    };
    const obj = (n, body) => {
        offsets[n] = length;
        push(`${n} 0 obj\n`);
        body.forEach(push);
        push('\nendobj\n');
    };

    push('%PDF-1.4\n%\xE2\xE3\xCF\xD3\n');
    obj(1, ['<< /Type /Catalog /Pages 2 0 R >>']);
    obj(2, ['<< /Type /Pages /Kids [3 0 R] /Count 1 >>']);
    obj(3, [`<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ${pageW} ${pageH}] /Resources << /XObject << /Im0 4 0 R >> >> /Contents 5 0 R >>`]);
    obj(4, [`<< /Type /XObject /Subtype /Image /Width ${imgW} /Height ${imgH} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length ${jpeg.length} >>\nstream\n`, jpeg, '\nendstream']);
    obj(5, [`<< /Length ${draw.length} >>\nstream\n${draw}\nendstream`]);

    const xref = length;
    push(`xref\n0 6\n0000000000 65535 f \n${offsets.slice(1).map((o) => `${String(o).padStart(10, '0')} 00000 n \n`).join('')}`);
    push(`trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n${xref}\n%%EOF`);
    return new Blob(parts, { type: 'application/pdf' });
}

export async function downloadPdf(opts) {
    const canvas = await renderPoster(opts);
    const blob = await new Promise((r) => canvas.toBlob(r, 'image/jpeg', 0.95));
    const jpeg = new Uint8Array(await blob.arrayBuffer());
    save(pdfFromJpeg(jpeg, canvas.width, canvas.height), `${slug(opts.title)}-check-in-qr.pdf`);
}

/** Prints only the poster (not the page) via a hidden iframe. */
export async function printPoster(opts) {
    const canvas = await renderPoster(opts);
    const src = canvas.toDataURL('image/png');
    const frame = document.createElement('iframe');
    frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden';
    document.body.appendChild(frame);
    const doc = frame.contentDocument;
    doc.open();
    doc.write(`<!doctype html><title></title><style>@page{size:A4;margin:0}html,body{margin:0}img{width:100%;height:auto;display:block}</style><img src="${src}">`);
    doc.close();
    doc.title = `${opts.title} — check-in QR`;
    const img = doc.querySelector('img');
    const go = () => {
        frame.contentWindow.focus();
        frame.contentWindow.print();
        setTimeout(() => frame.remove(), 1000);
    };
    img.complete ? go() : (img.onload = go);
}
