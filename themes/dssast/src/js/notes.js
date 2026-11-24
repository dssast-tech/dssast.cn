// Filtering only enhances links already rendered in the static page.
const library = document.querySelector('[data-note-library]');

if (library) {
  const filters = library.querySelector('[data-filters]');
  const query = filters.querySelector('[data-query]');
  const course = filters.querySelector('[data-course]');
  const format = filters.querySelector('[data-format]');
  const count = library.querySelector('[data-count]');
  const reset = library.querySelector('[data-reset]');
  const empty = library.querySelector('[data-empty]');
  const normalize = text => text.normalize('NFKC').toLowerCase().trim();
  const groups = Array.from(library.querySelectorAll('[data-group]'), group => ({
    group,
    items: Array.from(group.querySelectorAll('[data-formats]'), row => ({
      row,
      text: normalize(group.dataset.group + ' ' + row.textContent),
      formats: row.dataset.formats.split('|'),
    })),
  }));

  function update() {
    const terms = normalize(query.value).split(/\s+/).filter(Boolean);
    let totalGroups = 0;
    let totalItems = 0;

    for (const { group, items } of groups) {
      const selected = !course.value || group.dataset.group === course.value;
      let matches = 0;
      for (const { row, text, formats } of items) {
        const visible = selected && terms.every(term => text.includes(term))
          && (!format.value || formats.includes(format.value));
        row.hidden = !visible;
        if (visible) matches++;
      }
      group.hidden = matches === 0;
      if (matches) totalGroups++;
      totalItems += matches;
    }

    count.textContent = totalGroups + ' 个课程或专题 · ' + totalItems + ' 条资料';
    reset.hidden = !query.value && !course.value && !format.value;
    empty.hidden = totalItems !== 0;
  }

  query.addEventListener('input', update);
  course.addEventListener('change', update);
  format.addEventListener('change', update);
  reset.addEventListener('click', () => {
    query.value = '';
    course.value = '';
    format.value = '';
    update();
    query.focus();
  });

  filters.hidden = false;
  update();
}
