// Pearl360 shared front-end helpers
document.addEventListener('DOMContentLoaded', () => {
    // Confirm dangerous actions: <form data-confirm="Delete this?">
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', e => {
            if (!confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    // Lightbox for gallery photos
    const modalEl = document.getElementById('lightbox');
    if (modalEl) {
        const modal = new bootstrap.Modal(modalEl);
        document.querySelectorAll('[data-lightbox]').forEach(img => {
            img.addEventListener('click', () => {
                modalEl.querySelector('img').src = img.dataset.lightbox;
                modalEl.querySelector('.caption').textContent = img.alt || '';
                modal.show();
            });
        });
    }

    // Show / hide operator fields on the registration form
    const roleInputs = document.querySelectorAll('input[name="role"]');
    const opFields = document.getElementById('operatorFields');
    if (roleInputs.length && opFields) {
        const sync = () => opFields.classList.toggle('d-none', document.querySelector('input[name="role"]:checked')?.value !== 'operator');
        roleInputs.forEach(r => r.addEventListener('change', sync));
        sync();
    }
});

window.P360 = {
    csrf: () => document.querySelector('meta[name="csrf-token"]').content,
    post: (url, data = {}) => fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': P360.csrf(), 'Accept': 'application/json' },
        body: JSON.stringify(data),
    }),
};

// Show / hide password fields: <button data-toggle-password> next to <input data-password>
document.addEventListener('click', e => {
    const btn = e.target.closest('[data-toggle-password]');
    if (!btn) return;
    const form = btn.closest('form');
    const fields = form.querySelectorAll('input[data-password]');
    const show = fields[0]?.type === 'password';
    fields.forEach(f => f.type = show ? 'text' : 'password');
    btn.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
});

// Lightweight YouTube embeds: swap the thumbnail for the real player on click / Enter.
function playYouTube(el) {
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.youtube-nocookie.com/embed/${el.dataset.yt}?autoplay=1&rel=0`;
    iframe.title = el.dataset.title || 'Video';
    iframe.allow = 'accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture; fullscreen';
    iframe.allowFullscreen = true;
    el.replaceChildren(iframe);
    el.style.backgroundImage = 'none';
    el.removeAttribute('role');
}
document.addEventListener('click', e => { const el = e.target.closest('[data-yt]'); if (el && !el.querySelector('iframe')) playYouTube(el); });
document.addEventListener('keydown', e => { const el = e.target.closest?.('[data-yt]'); if (el && (e.key === 'Enter' || e.key === ' ') && !el.querySelector('iframe')) { e.preventDefault(); playYouTube(el); } });
