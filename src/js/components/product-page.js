function initProductGallery() {
  const gallery = document.querySelector('.product-gallery');
  if (!gallery) return;

  const mainImg = gallery.querySelector('.product-gallery__img');
  const thumbs = gallery.querySelectorAll('.product-gallery__thumb');
  const prevBtn = gallery.querySelector('.product-gallery__nav--prev');
  const nextBtn = gallery.querySelector('.product-gallery__nav--next');
  let current = 0;

  function goTo(index) {
    thumbs[current].classList.remove('is-active');
    current = (index + thumbs.length) % thumbs.length;
    thumbs[current].classList.add('is-active');
    mainImg.src = thumbs[current].dataset.src;
  }

  prevBtn?.addEventListener('click', () => goTo(current - 1));
  nextBtn?.addEventListener('click', () => goTo(current + 1));

  thumbs.forEach((thumb, i) => {
    thumb.addEventListener('click', () => goTo(i));
  });
}

function initProductQty() {
  const qtys = document.querySelectorAll('.product-qty');
  if (!qtys.length) return;

  qtys.forEach((qty) => {
    const minus = qty.querySelector('.product-qty__btn--minus');
    const plus = qty.querySelector('.product-qty__btn--plus');
    const valueEl = qty.querySelector('.product-qty__value');
    const isInput = valueEl.tagName === 'INPUT';

    const getValue = () => {
      const raw = isInput ? valueEl.value : valueEl.textContent;
      const num = parseInt(raw, 10);
      return Number.isNaN(num) ? 1 : num;
    };

    const setValue = (num) => {
      const min = parseInt(valueEl.min, 10) || 1;
      const max = parseInt(valueEl.max, 10) || Infinity;
      const clamped = Math.min(Math.max(num, min), max);
      if (isInput) {
        valueEl.value = clamped;
      } else {
        valueEl.textContent = clamped;
      }
    };

    minus.addEventListener('click', () => setValue(getValue() - 1));
    plus.addEventListener('click', () => setValue(getValue() + 1));

    if (isInput) {
      valueEl.addEventListener('change', () => setValue(getValue()));
    }
  });
}

initProductGallery();
initProductQty();
