(function () {
  const MASK = '+7 (___) ___-__-__';
  const DIGIT = '_';

  function applyMask(raw) {
    const digits = raw.replace(/\D/g, '').replace(/^[78]/, '');
    let masked = MASK;
    for (let i = 0; i < digits.length; i++) {
      masked = masked.replace(DIGIT, digits[i]);
    }
    // Обрезаем до последней введённой цифры
    const lastDigit = masked.split('').reduceRight((acc, ch, i) => {
      return acc === -1 && /\d/.test(ch) ? i : acc;
    }, -1);
    return lastDigit === -1 ? '+7 (' : masked.slice(0, lastDigit + 1);
  }

  function onInput(e) {
    const input = e.target;
    const cursor = input.selectionStart;
    const prevLen = input.value.length;

    input.value = applyMask(input.value);

    // Корректируем позицию курсора после перестройки строки
    const diff = input.value.length - prevLen;
    const newCursor = Math.max(4, cursor + diff);
    input.setSelectionRange(newCursor, newCursor);
  }

  function onKeyDown(e) {
    const input = e.target;
    // Не даём удалить '+7 ('
    if ((e.key === 'Backspace' || e.key === 'Delete') && input.value.length <= 4) {
      e.preventDefault();
    }
  }

  function onFocus(e) {
    const input = e.target;
    if (!input.value) {
      input.value = '+7 (';
    }
    // Ставим курсор в конец
    const len = input.value.length;
    requestAnimationFrame(() => input.setSelectionRange(len, len));
  }

  function onBlur(e) {
    const input = e.target;
    if (input.value === '+7 (') {
      input.value = '';
    }
  }

  document.querySelectorAll('input[type="tel"]').forEach(function (input) {
    input.placeholder = '+7 (999) 999-99-99';
    input.addEventListener('input', onInput);
    input.addEventListener('keydown', onKeyDown);
    input.addEventListener('focus', onFocus);
    input.addEventListener('blur', onBlur);
  });
})();
