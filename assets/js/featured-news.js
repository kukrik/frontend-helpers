document.addEventListener('click', function (e) {
    const card = e.target.closest('.js-clickable-card');
    if (!card) return;

    if (e.target.closest('a, button, input, textarea, select, label')) {
        return;
    }

    const href = card.dataset.href;
    if (href) {
        window.location.href = href;
    }
});

document.addEventListener('keydown', function (e) {
    const card = e.target.closest('.js-clickable-card');
    if (!card) return;

    if (e.key !== 'Enter' && e.key !== ' ') {
        return;
    }

    e.preventDefault();

    const href = card.dataset.href;
    if (href) {
        window.location.href = href;
    }
});