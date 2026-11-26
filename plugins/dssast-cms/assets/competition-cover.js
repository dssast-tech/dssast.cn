(function () {
    const root = document.querySelector('[data-dssast-cover]');
    if (!root || !window.wp || !window.wp.media) {
        return;
    }

    const input = root.querySelector('input[name="dssast_cover"]');
    const preview = root.querySelector('[data-cover-preview]');
    const empty = root.querySelector('[data-cover-empty]');
    const remove = root.querySelector('[data-cover-remove]');
    const select = root.querySelector('[data-cover-select]');
    let frame = null;

    function showPreview(url, id) {
        input.value = String(id || 0);
        if (url) {
            preview.src = url;
            preview.hidden = false;
            empty.hidden = true;
            remove.hidden = false;
        } else {
            preview.removeAttribute('src');
            preview.hidden = true;
            empty.hidden = false;
            remove.hidden = true;
        }
    }

    if (select) {
        select.addEventListener('click', function (event) {
            event.preventDefault();
            if (!frame) {
                frame = wp.media({
                    title: '选择赛事封面',
                    button: { text: '使用这张图片' },
                    library: { type: 'image' },
                    multiple: false,
                });
                frame.on('select', function () {
                    const attachment = frame.state().get('selection').first().toJSON();
                    const sizes = attachment.sizes || {};
                    const url = (sizes.medium && sizes.medium.url) || attachment.url;
                    showPreview(url, attachment.id);
                });
            }
            frame.open();
        });
    }

    if (remove) {
        remove.addEventListener('click', function (event) {
            event.preventDefault();
            showPreview('', 0);
        });
    }
})();
