function initCatalogSort() {
  const sorts = document.querySelectorAll('.catalog-sort');
  if (!sorts.length) return;

  sorts.forEach((sort) => {
    const btn = sort.querySelector('.catalog-sort__btn');
    const current = sort.querySelector('.catalog-sort__current');
    const options = sort.querySelectorAll('.catalog-sort__option');

    btn.addEventListener('click', () => {
      const isOpen = sort.classList.contains('is-open');
      sort.classList.toggle('is-open', !isOpen);
      btn.setAttribute('aria-expanded', String(!isOpen));
    });

    options.forEach((option) => {
      option.addEventListener('click', () => {
        options.forEach((o) => o.classList.remove('is-selected'));
        option.classList.add('is-selected');
        current.textContent = option.textContent;
        sort.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
      });
    });

    document.addEventListener('click', (e) => {
      if (!sort.contains(e.target)) {
        sort.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  });
}

initCatalogSort();
