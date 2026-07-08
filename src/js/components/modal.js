import { disableScroll } from '../functions/disable-scroll.js';
import { enableScroll } from '../functions/enable-scroll.js';

function initModal() {
  const modal = document.getElementById('modal-question');
  if (!modal) return;

  const openTriggers = document.querySelectorAll('[data-modal-open]');
  const closeTriggers = modal.querySelectorAll('[data-modal-close]');

  function openModal() {
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    disableScroll();
  }

  function closeModal() {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
    enableScroll();
  }

  openTriggers.forEach((trigger) => {
    trigger.addEventListener('click', openModal);
  });

  closeTriggers.forEach((trigger) => {
    trigger.addEventListener('click', closeModal);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && modal.classList.contains('is-open')) {
      closeModal();
    }
  });
}

initModal();
