(() => {
  'use strict';
  const directory = document.querySelector('[data-sipp-directory]');
  if (!directory) return;
  const form = directory.querySelector('[data-sipp-directory-form]');
  const search = form.querySelector('[name="q"]');
  const category = form.querySelector('[name="group"]');
  const groups = [...directory.querySelectorAll('[data-sipp-group]')];
  const output = directory.querySelector('[data-sipp-result-count]');
  const empty = directory.querySelector('[data-sipp-no-modules]');
  const normalize = value => value.toLocaleLowerCase('id-ID').trim();
  const apply = () => {
    const query = normalize(search.value);
    let total = 0;
    groups.forEach(group => {
      let count = 0;
      group.querySelectorAll('[data-sipp-label]').forEach(row => {
        const match = (!category.value || category.value === group.dataset.sippGroup)
          && normalize(row.dataset.sippLabel).includes(query);
        row.hidden = !match;
        if (match) count++;
      });
      group.hidden = count === 0;
      total += count;
    });
    output.textContent = `${total} modul`;
    empty.hidden = total > 0;
    const url = new URL(location.href);
    [['q', search.value.trim()], ['group', category.value]].forEach(([name, value]) => {
      if (value) url.searchParams.set(name, value); else url.searchParams.delete(name);
    });
    history.replaceState(null, '', url);
  };
  search.addEventListener('input', apply);
  category.addEventListener('change', apply);
  form.addEventListener('submit', event => { event.preventDefault(); apply(); });
  form.addEventListener('reset', event => {
    event.preventDefault(); search.value = ''; category.value = ''; apply(); search.focus();
  });
  apply();
})();
