import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';

function initReviewsSlider() {
  const section = document.querySelector('.reviews');
  if (!section) return;

  const slider = section.querySelector('.reviews__slider') || section.querySelector('.reviews__photo-slider');
  if (!slider) return;

  new Swiper(slider, {
    modules: [Navigation],
    slidesPerView: 1,
    spaceBetween: 40,
    autoHeight: true,
    navigation: {
      prevEl: section.querySelector('.reviews__nav-btn:first-child'),
      nextEl: section.querySelector('.reviews__nav-btn:last-child'),
    },
  });
}

initReviewsSlider();
