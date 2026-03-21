document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.js-wishlist-toggle');

    if (!buttons.length) {
        return;
    }

    buttons.forEach((button) => {
        button.addEventListener('click', async () => {
            const offerId = button.dataset.offerId;

            if (!offerId) {
                return;
            }

            button.disabled = true;

            try {
                const response = await fetch('/wishlist/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        offer_id: offerId
                    })
                });

                const data = await response.json();

                if (!data.success) {
                    alert(data.message || 'Une erreur est survenue.');
                    button.disabled = false;
                    return;
                }

                const allSameButtons = document.querySelectorAll(`.js-wishlist-toggle[data-offer-id="${offerId}"]`);

                allSameButtons.forEach((btn) => {
                    if (data.inWishlist) {
                        btn.classList.add('is-active');
                        btn.textContent = btn.dataset.labelRemove || 'Retirer de la wish-list';
                    } else {
                        btn.classList.remove('is-active');
                        btn.textContent = btn.dataset.labelAdd || 'Ajouter à la wish-list';
                    }

                    btn.disabled = false;
                });

                const row = document.querySelector(`[data-wishlist-row="${offerId}"]`);

                if (row && !data.inWishlist) {
                    row.remove();

                    const remainingRows = document.querySelectorAll('[data-wishlist-row]');
                    const wrapper = document.querySelector('.wishlist-table-wrapper');

                    if (remainingRows.length === 0 && wrapper) {
                        wrapper.insertAdjacentHTML(
                            'beforeend',
                            `
                            <div class="wishlist-table-row wishlist-table-row--empty">
                                <div class="wishlist-cell wishlist-cell--full">
                                    Aucune offre dans votre wish-list.
                                </div>
                            </div>
                            `
                        );
                    }
                }
            } catch (error) {
                alert('Une erreur est survenue.');
                button.disabled = false;
            }
        });
    });
});