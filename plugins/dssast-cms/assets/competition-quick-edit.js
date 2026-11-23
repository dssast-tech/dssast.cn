(function () {
    const quickEdit = window.inlineEditPost;
    const editPost = quickEdit.edit;
    const savePost = quickEdit.save;

    quickEdit.edit = function (post) {
        const result = editPost.apply(this, arguments);
        const id = typeof post === 'object' ? this.getId(post) : post;
        const fieldset = document.querySelector('#edit-' + id + ' .dssast-competition-quick-edit');
        const rowData = document.querySelector('#inline_' + id + ' .dssast-quick-edit-data');
        if (!fieldset || !rowData) {
            return result;
        }

        const values = JSON.parse(rowData.dataset.fields);
        for (const field of fieldset.elements) {
            field.value = values[field.name];
            field.dataset.original = field.value;
        }
        fieldset.disabled = false;
        fieldset.hidden = false;
        return result;
    };

    quickEdit.save = function (post) {
        const id = typeof post === 'object' ? this.getId(post) : post;
        const fieldset = document.querySelector('#edit-' + id + ' .dssast-competition-quick-edit');
        if (!fieldset || fieldset.disabled) {
            return savePost.apply(this, arguments);
        }

        const fields = Array.from(fieldset.elements);
        const invalid = fields.find((field) => !field.validity.valid);
        if (invalid) {
            invalid.reportValidity();
            return false;
        }

        // Core serializes the form immediately. Omit fields the user did not edit.
        const nonce = fieldset.elements.namedItem('dssast_nonce');
        const unchanged = fields.filter((field) => field !== nonce && field.value === field.dataset.original);
        if (unchanged.length === fields.length - 1) {
            unchanged.push(nonce);
        }
        unchanged.forEach((field) => { field.disabled = true; });
        try {
            return savePost.apply(this, arguments);
        } finally {
            unchanged.forEach((field) => { field.disabled = false; });
        }
    };
})();
