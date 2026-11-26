(function () {
    const list = document.querySelector('[data-dssast-links]');
    if (!list) {
        return;
    }

    const template = document.querySelector('[data-dssast-template]');
    const addButton = document.querySelector('[data-dssast-add]');

    if (addButton && template) {
        addButton.addEventListener('click', function () {
            const row = template.content.firstElementChild.cloneNode(true);
            list.appendChild(row);
            const input = row.querySelector('input');
            if (input) {
                input.focus();
            }
        });
    }

    list.addEventListener('click', function (event) {
        const remove = event.target.closest('[data-dssast-remove]');
        const move = event.target.closest('[data-dssast-move]');
        const row = event.target.closest('.dssast-link-row');
        if (!row) {
            return;
        }

        if (remove) {
            event.preventDefault();
            row.remove();
            return;
        }

        if (!move) {
            return;
        }

        event.preventDefault();
        if ('up' === move.dataset.dssastMove && row.previousElementSibling) {
            list.insertBefore(row, row.previousElementSibling);
            row.querySelector('input')?.focus();
        } else if ('down' === move.dataset.dssastMove && row.nextElementSibling) {
            list.insertBefore(row.nextElementSibling, row);
            row.querySelector('input')?.focus();
        }
    });
})();
