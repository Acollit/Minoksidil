const panel = document.querySelector('[data-filter-panel]');

if (panel) {
  const openBtn = document.querySelector('[data-filter-open]');
  const closeBtns = document.querySelectorAll('[data-filter-close]');
  const applyBtn = document.querySelector('[data-filter-apply]');

  function openFilter() {
    panel.classList.add('is-open');
    document.body.style.overflow = 'hidden';
  }

  function closeFilter() {
    panel.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  if (openBtn) openBtn.addEventListener('click', openFilter);
  closeBtns.forEach(btn => btn.addEventListener('click', closeFilter));
  if (applyBtn) applyBtn.addEventListener('click', closeFilter);
}
