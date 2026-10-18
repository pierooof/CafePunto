// Escáner de QR del panel del barista.
// Usa BarcodeDetector si el navegador lo tiene y, si no, jsQR cargado bajo demanda.
(() => {
    const openBtn = document.getElementById('scan-open');
    const closeBtn = document.getElementById('scan-close');
    const panel = document.getElementById('scanner');
    const video = document.getElementById('scan-video');
    const hint = document.getElementById('scan-hint');
    if (!openBtn || !navigator.mediaDevices?.getUserMedia) return;

    openBtn.hidden = false;
    let stream = null;
    let running = false;

    // El QR contiene APP_URL/c/{token}; solo nos quedamos con la ruta para
    // navegar siempre dentro de este mismo sitio.
    const toPath = (text) => {
        try {
            const m = new URL(text).pathname.match(/^\/c\/[A-Za-z0-9]+$/);
            return m ? m[0] : null;
        } catch {
            return null;
        }
    };

    const loadJsQr = () => new Promise((resolve, reject) => {
        if (window.jsQR) return resolve(window.jsQR);
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js';
        s.onload = () => resolve(window.jsQR);
        s.onerror = reject;
        document.head.appendChild(s);
    });

    const stop = () => {
        running = false;
        stream?.getTracks().forEach((t) => t.stop());
        stream = null;
        panel.hidden = true;
    };

    const found = (text) => {
        const path = toPath(text);
        if (!path) {
            hint.textContent = 'Ese QR no es de CaféPunto';
            return false;
        }
        stop();
        window.location.href = path;
        return true;
    };

    async function start() {
        panel.hidden = false;
        hint.textContent = 'Apunta al QR del cliente';
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        } catch {
            hint.textContent = 'No se pudo acceder a la cámara';
            return;
        }
        video.srcObject = stream;
        await video.play();
        running = true;

        const detector = 'BarcodeDetector' in window ? new BarcodeDetector({ formats: ['qr_code'] }) : null;
        const jsQR = detector ? null : await loadJsQr().catch(() => null);
        if (!detector && !jsQR) {
            hint.textContent = 'No se pudo cargar el lector de QR';
            return;
        }

        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d', { willReadFrequently: true });

        const tick = async () => {
            if (!running) return;
            if (video.readyState >= 2) {
                let text = null;
                if (detector) {
                    const codes = await detector.detect(video).catch(() => []);
                    text = codes[0]?.rawValue ?? null;
                } else {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0);
                    const img = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    text = jsQR(img.data, img.width, img.height)?.data ?? null;
                }
                if (text && found(text)) return;
            }
            setTimeout(tick, 200);
        };
        tick();
    }

    openBtn.addEventListener('click', start);
    closeBtn.addEventListener('click', stop);
    document.addEventListener('keydown', (e) => e.key === 'Escape' && stop());
})();
