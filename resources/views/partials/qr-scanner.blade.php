{{-- "Scan" button + camera dialog to reopen an official receipt from its QR code. --}}
<button type="button" class="scan-button rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-white" data-qr-open>
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M4 12h16"/></svg>
    Scan receipt
</button>

<dialog class="scanner" data-qr-scanner aria-labelledby="scanner-title">
    <div class="scanner__head">
        <h2 id="scanner-title">Scan receipt QR</h2>
        <button type="button" class="scanner__close" data-qr-close aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18"/></svg>
        </button>
    </div>
    <div class="scanner__camera">
        <video data-qr-video playsinline muted></video>
    </div>
    <p class="scanner__status" data-qr-status aria-live="polite">Point your camera at the QR code on your receipt.</p>
    <label class="scanner__upload">
        <input type="file" accept="image/*" data-qr-file>
        <span>Scan from a photo or a downloaded receipt</span>
    </label>
</dialog>
