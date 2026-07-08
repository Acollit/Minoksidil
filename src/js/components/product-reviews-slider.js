import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';

function initProductReviewsSlider() {
  const slider = document.querySelector('.product-reviews__slider');
  if (!slider) return;

  new Swiper(slider, {
    modules: [Navigation],
    slidesPerView: 1,
    spaceBetween: 20,
    navigation: {
      prevEl: '[data-product-reviews-prev]',
      nextEl: '[data-product-reviews-next]',
    },
    breakpoints: {
      769: {
        slidesPerView: 2,
      },
      1025: {
        slidesPerView: 3,
      },
    },
  });
}

initProductReviewsSlider();
