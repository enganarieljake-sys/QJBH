// Official receipt page: download the receipt card as a PNG, and share its link.
import { toPng } from 'html-to-image';

const card = document.querySelector('[data-receipt-card]');
const download = document.querySelector('[data-receipt-download]');

download?.addEventListener('click', async () => {
    const label = download.textContent;
    download.disabled = true;
    download.textContent = 'Preparing…';

    try {
        const image = await toPng(card, { pixelRatio: 2, backgroundColor: '#ffffff', cacheBust: true });
        const link = document.createElement('a');
        link.download = `${card.dataset.filename}.png`;
        link.href = image;
        link.click();
    } catch {
        alert('Could not create the image. Use Print and choose "Save as PDF" instead.');
    } finally {
        download.disabled = false;
        download.textContent = label;
    }
});

// Share the receipt's QR link (phone share sheet), or copy it where sharing isn't available.
document.querySelector('[data-receipt-share]')?.addEventListener('click', async (event) => {
    const { url, title } = event.currentTarget.dataset;

    if (navigator.share) {
        await navigator.share({ title, url }).catch(() => {});
        return;
    }

    await navigator.clipboard?.writeText(url).catch(() => {});
    alert('Receipt link copied.');
});
