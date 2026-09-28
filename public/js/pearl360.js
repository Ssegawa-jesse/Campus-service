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
