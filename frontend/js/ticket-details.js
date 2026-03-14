/**
 * ticket-details.js
 * Handles data fetching and rendering for a specific ticket.
 */
document.addEventListener('DOMContentLoaded', function () {
    const loadingState = document.getElementById('loading-state');
    const ticketContent = document.getElementById('ticket-content');
    const errorState = document.getElementById('error-state');
    const downloadBtn = document.getElementById('download-ticket');
    const apiUrl = window.API_URL || '../backend/api';

    // Get Ticket Info from URL
    const urlParams = new URLSearchParams(window.location.search);
    const ticketId = urlParams.get('id');
    const ticketCode = urlParams.get('code');

    if (!ticketId && !ticketCode) {
        showError();
        return;
    }

    fetchTicketDetails();

    async function fetchTicketDetails() {
        try {
            // Forward relevant URL parameters to the API
            const response = await fetch(`${apiUrl}/ticket_details.php?${window.location.search.substring(1)}`);
            if (!response.ok) throw new Error('Ticket not found');
            const data = await response.json();

            renderTicket(data);
        } catch (error) {
            console.error('Error:', error);
            showError();
        }
    }

    function renderTicket(t) {
        loadingState.style.display = 'none';
        ticketContent.style.display = 'block';

        const hasNoQR = !t.qr_code || (t.ticket_status || '').toUpperCase() === 'PENDING' || (t.ticket_status || '').toUpperCase() === 'PROCESSING';

        // Header and Banner
        document.getElementById('event-title').textContent = t.event_title;
        const eventDate = new Date(t.event_date);
        document.getElementById('event-date-str').textContent = `${eventDate.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' })} @ ${t.event_time || 'TBA'}`;

        const bannerImg = document.getElementById('event-banner');
        bannerImg.src = normalizeImg(t.event_image);

        // Info Boxes
        document.getElementById('event-location').textContent = t.location;
        document.getElementById('ticket-type').textContent = t.ticket_type_name;
        document.getElementById('ticket-price').textContent = `KSh ${parseFloat(t.ticket_price).toLocaleString()}`;
        document.getElementById('order-date').textContent = new Date(t.order_date).toLocaleDateString();

        // QR and Status
        const statusPill = document.getElementById('ticket-status');
        statusPill.textContent = t.ticket_status;
        statusPill.className = `ticket-status-pill status-${t.ticket_status.toLowerCase()}`;

        const qrImg = document.getElementById('ticket-qr');
        const qrWrapper = qrImg.closest('.qr-wrapper');

        if (hasNoQR) {
            qrImg.style.display = 'none';
            // Add a processing notice
            let notice = qrWrapper.querySelector('.processing-notice');
            if (!notice) {
                notice = document.createElement('div');
                notice.className = 'processing-notice';
                notice.style.padding = '20px';
                notice.style.fontSize = '0.9rem';
                notice.style.color = '#92400e';
                notice.innerHTML = `<i class="fas fa-sync fa-spin"></i><br>QR Code will be generated once payment is confirmed.`;
                qrWrapper.appendChild(notice);
            }
        } else {
            const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(t.qr_code)}`;
            qrImg.src = qrUrl;
            qrImg.style.display = 'block';
            const notice = qrWrapper.querySelector('.processing-notice');
            if (notice) notice.remove();

            downloadBtn.disabled = false;
            downloadBtn.onclick = () => {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');

                // Set canvas size (Ticket aspect ratio)
                canvas.width = 600;
                canvas.height = 1050;

                // White background
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, canvas.width, canvas.height);

                // Add Red Border
                ctx.strokeStyle = '#DA291C';
                ctx.lineWidth = 15;
                ctx.strokeRect(0, 0, canvas.width, canvas.height);

                // Branding Header
                ctx.fillStyle = '#DA291C';
                ctx.fillRect(0, 0, canvas.width, 80);

                ctx.fillStyle = '#ffffff';
                ctx.font = 'bold 32px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText('MOMBASA HAMLETS FC', canvas.width / 2, 52);

                // Event Title
                ctx.fillStyle = '#1e293b';
                ctx.font = 'bold 40px sans-serif';
                ctx.textAlign = 'center';
                ctx.fillText(t.event_title.toUpperCase(), canvas.width / 2, 160);

                // Divider
                ctx.strokeStyle = '#eee';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(50, 200);
                ctx.lineTo(550, 200);
                ctx.stroke();

                // Ticket Details
                const drawDetail = (label, value, x, y) => {
                    ctx.fillStyle = '#94a3b8';
                    ctx.font = 'bold 18px sans-serif';
                    ctx.textAlign = 'left';
                    ctx.fillText(label.toUpperCase(), x, y);

                    ctx.fillStyle = '#1e293b';
                    ctx.font = 'bold 24px sans-serif';
                    ctx.fillText(value, x, y + 30);
                };

                const orderDate = new Date(t.order_date);
                const dateStr = `${orderDate.getMonth() + 1}/${orderDate.getDate()}/${orderDate.getFullYear()}`;

                drawDetail('Venue', t.location, 60, 260);
                drawDetail('Ticket Type', t.ticket_type_name, 60, 360);
                drawDetail('Price', `KSh ${parseFloat(t.ticket_price).toLocaleString()}`, 60, 460);
                drawDetail('Order Date', dateStr, 60, 560);

                // Ticket Code
                ctx.fillStyle = '#f8fafc';
                ctx.fillRect(350, 240, 200, 80);
                ctx.fillStyle = '#475569';
                ctx.font = 'bold 20px monospace';
                ctx.textAlign = 'center';
                ctx.fillText(t.ticket_code, 450, 285);

                // Dashed Tearing Line
                ctx.setLineDash([10, 10]);
                ctx.strokeStyle = '#cbd5e1';
                ctx.lineWidth = 2;
                ctx.beginPath();
                ctx.moveTo(30, 620);
                ctx.lineTo(570, 620);
                ctx.stroke();
                ctx.setLineDash([]); // Reset

                // QR Code
                const qrImg = new Image();
                qrImg.crossOrigin = 'Anonymous';
                qrImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(t.qr_code)}`;

                qrImg.onload = () => {
                    // Center the QR Code
                    ctx.drawImage(qrImg, canvas.width / 2 - 125, 680, 250, 250); // Smaller QR, more space

                    // Footer Text
                    ctx.fillStyle = '#94a3b8';
                    ctx.font = 'bold 16px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillText('Authorized Ticket - Mombasa Hamlets FC', canvas.width / 2, 980);

                    // Trigger Download
                    const link = document.createElement('a');
                    link.download = `MHFC-Ticket-${t.ticket_code}.png`;
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                };

                qrImg.onerror = () => {
                    alert('Failed to generate full ticket image. Please try again.');
                };
            };
        }

        if (hasNoQR) {
            downloadBtn.disabled = true;
            downloadBtn.style.opacity = '0.5';
            downloadBtn.title = "Available after payment confirmation";
        }

        document.getElementById('ticket-code-display').textContent = t.ticket_code;
    }

    function normalizeImg(path) {
        if (!path) return 'images/logo1.jpeg';
        if (path.startsWith('/')) path = path.substring(1);
        if (path.indexOf('/') === -1) path = 'images/' + path;
        return path;
    }

    function showError() {
        loadingState.style.display = 'none';
        errorState.style.display = 'block';
    }
});
