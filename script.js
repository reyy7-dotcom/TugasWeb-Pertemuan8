'use strict';

document.querySelectorAll('.delete-form').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const productName = form.dataset.productName || 'barang ini';
        const confirmed = window.confirm(`Yakin ingin menghapus ${productName}?`);

        if (!confirmed) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('.alert-close').forEach((button) => {
    button.addEventListener('click', () => {
        button.closest('.alert')?.remove();
    });
});
