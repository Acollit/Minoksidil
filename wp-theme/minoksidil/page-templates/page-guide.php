<?php
/**
 * Template Name: Гид
 */
defined('ABSPATH') || exit;
get_header();

$vk      = get_option('minoksidil_vk', '#');
$tg      = get_option('minoksidil_telegram', '#');
$tg2     = get_option('minoksidil_telegram2', '#');
$max     = get_option('minoksidil_max', '#');
$avito   = get_option('minoksidil_avito', '#');

$privacy = get_option('minoksidil_privacy_url', '#');
$consent = get_option('minoksidil_consent_url', '#');
$ba_page = get_page_by_path('do-posle');
$ba_url  = $ba_page ? get_permalink($ba_page->ID) : home_url('/do-posle/');

function minoksidil_social_svg(): string {
    return '';
}
?>
<main class="main">

  <!-- HERO -->
  <section class="guide-hero">
    <img class="guide-hero__bg" src="<?php echo esc_url(MINOKSIDIL_IMG . 'guide-hero.webp'); ?>" alt="bg">
    <div class="container guide-hero__inner">
      <div class="guide-hero__content">
        <h1 class="guide-hero__title"><?php echo wp_kses_post(minoksidil_acf('hero_title', 'Minoxidillum<br>— ваш проводник в мир густых волос')); ?></h1>


      </div>
    </div>
    <div class="guide-hero__grass" aria-hidden="true">
      <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'catalog-grass.webp'); ?>" alt="" width="1920" height="469" loading="lazy">
    </div>
  </section>

  <section class="guide-about">
    <div class="container">
      <div class="guide-about__content">
        <h2 class="guide-about__title">
          <?php echo esc_html(minoksidil_acf('about_title', 'О нас')); ?>
        </h2>
        <div class="guide-about__inner">
          <div class="guide-about__text">
            <?php echo wp_kses_post(minoksidil_acf('about_text_1', 'Мы продаём только оригинальные лосьоны для восстановления волос. Привозим их из Америки и Индии, не работаем с сомнительными копиями и не собираем ассортимент ради количества. В нашей линейке — только те препараты, которые действительно помогают восстанавливать волосы и многократно проверены на практике. <br> <br> Для нас важно не просто продать человеку лосьон, а показать, что восстановление волос — это доступный и не сложный процесс, которым может воспользоваться практически любой человек. Без лишних усложнений, без мифов и без ощущения, что для результата нужны какие-то недостижимые решения.')); ?>
          </div>
          <div class="guide-about__text">
            <?php echo wp_kses_post(minoksidil_acf('about_text_2', 'Именно поэтому мы не ограничиваемся только продажей. Мы собираем понятную информацию, инструкции, ответы на частые вопросы и рекомендации по использованию, чтобы человек мог либо самостоятельно разобраться в теме, либо обратиться к нам за консультацией. <br> <br> Наша идея простая: дать человеку оригинальный препарат, понятную схему, рабочий инструмент и поддержку. Чтобы восстановление волос было не пугающей историей, а лёгким процессом с хорошими результатами!')); ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- СТАТЬИ -->
  <?php
  $guide_articles = get_posts(['post_type' => 'post', 'posts_per_page' => 3, 'post_status' => 'publish']);
  if ($guide_articles) :
    $blog_url = get_permalink(get_option('page_for_posts')) ?: get_post_type_archive_link('post');
  ?>
  <section class="guide-articles">
    <div class="container">
      <div class="guide-articles__head">
        <h2 class="guide-articles__title"><?php echo esc_html(minoksidil_acf('articles_title', 'Статьи', get_queried_object_id())); ?></h2>
        <a href="<?php echo esc_url($blog_url); ?>" class="guide-articles__link"><?php echo esc_html(minoksidil_acf('articles_link', 'Больше статей', get_queried_object_id())); ?></a>
      </div>
      <div class="guide-articles__grid">
        <?php foreach ($guide_articles as $post) : setup_postdata($post);
            $img_id  = get_post_thumbnail_id($post->ID);
            $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'large') : MINOKSIDIL_IMG . 'articles-1.webp';
        ?>
          <a href="<?php the_permalink(); ?>" class="article-card">
            <div class="article-card__img">
              <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" width="560" height="360" loading="lazy">
            </div>
            <div class="article-card__body">
              <time class="article-card__date" datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>"><?php echo esc_html(get_the_date('j F Y г.')); ?></time>
              <h3 class="article-card__title"><?php the_title(); ?></h3>
              <p class="article-card__text"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 25)); ?></p>
              <span class="article-card__link">Читать подробнее</span>
            </div>
          </a>
        <?php endforeach; wp_reset_postdata(); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

   <!-- РЕЗУЛЬТАТЫ -->
  <?php
  $results = get_posts(['post_type' => 'result', 'posts_per_page' => 3, 'post_status' => 'publish']);
  if ($results) :
  ?>
  <section class="guide-results">
    <div class="container">
      <div class="guide-results__head">
        <div class="guide-results__head-left">
          <h2 class="guide-results__title"><?php echo esc_html(minoksidil_acf('results_title', 'Результаты, которых мы достигаем вместе с вами', get_queried_object_id())); ?></h2>
          <p class="guide-results__desc"><?php echo wp_kses_post(minoksidil_acf('results_desc', 'У нас нет волшебной таблетки «на раз». Зато есть реальные фото людей разного возраста. Вот лишь несколько примеров того, как обычное регулярное использование с правильно подобранным лосьоном возвращает волосы.', get_queried_object_id())); ?></p>
        </div>
        <a href="<?php echo esc_url($ba_url); ?>" class="guide-results__link"><?php echo esc_html(minoksidil_acf('results_link', 'Смотреть все результаты', get_queried_object_id())); ?></a>
      </div>

      <div class="ba-grid">
        <?php foreach ($results as $r) :
            $acf_gallery = get_field('result_gallery', $r->ID) ?: [];
            if (!empty($acf_gallery)) {
                $gallery_urls = array_values(array_filter(array_map(function ($img) {
                    if (is_array($img)) return $img['sizes']['large'] ?? $img['url'];
                    if (is_numeric($img)) return wp_get_attachment_image_url((int) $img, 'large') ?: '';
                    return (string) $img;
                }, $acf_gallery)));
                $img = $gallery_urls[0] ?? MINOKSIDIL_IMG . 'ba-1.webp';
            } else {
                $img_id = get_post_thumbnail_id($r->ID);
                $img    = $img_id ? wp_get_attachment_image_url($img_id, 'large') : MINOKSIDIL_IMG . 'ba-1.webp';
                $gallery_urls = [$img];
            }
            $person = get_post_meta($r->ID, '_result_person', true) ?: get_the_title($r->ID);
            $form   = get_post_meta($r->ID, '_result_form', true);
            $freq   = get_post_meta($r->ID, '_result_frequency', true);
            $dur    = get_post_meta($r->ID, '_result_duration', true);
            $out    = get_post_meta($r->ID, '_result_outcome', true);
        ?>
          <div class="result-card">
            <div class="result-card__image"
                 data-gallery="<?php echo esc_attr(wp_json_encode($gallery_urls)); ?>"
                 role="button" tabindex="0" aria-label="Открыть галерею">
              <img src="<?php echo esc_url($img); ?>" alt="Результат <?php echo esc_attr($person); ?>" width="560" height="360" loading="lazy">
              <div class="result-card__zoom" aria-hidden="true">
                <svg width="52" height="52" viewBox="0 0 52 52" fill="none">
                  <circle cx="52" cy="52" r="52" fill="rgba(0,0,0,0.4)"/>
                  <circle cx="23" cy="23" r="12" stroke="white" stroke-width="2.5"/>
                  <path d="M32 32L42 42" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                  <path d="M23 18V28M18 23H28" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
                </svg>
              </div>
            </div>
            <div class="result-card__body">
              <p class="result-card__name"><?php echo esc_html($person); ?></p>
              <table class="result-specs"><tbody>
                <?php if ($form): ?><tr class="result-specs__row"><td class="result-specs__label">Форма:</td><td class="result-specs__value"><?php echo esc_html($form); ?></td></tr><?php endif; ?>
                <?php if ($freq): ?><tr class="result-specs__row"><td class="result-specs__label">Частота применения:</td><td class="result-specs__value"><?php echo esc_html($freq); ?></td></tr><?php endif; ?>
                <?php if ($dur): ?><tr class="result-specs__row"><td class="result-specs__label">Срок:</td><td class="result-specs__value"><?php echo esc_html($dur); ?></td></tr><?php endif; ?>
                <?php if ($out): ?><tr class="result-specs__row"><td class="result-specs__label">Результат:</td><td class="result-specs__value"><?php echo esc_html($out); ?></td></tr><?php endif; ?>
              </tbody></table>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>
  <!-- СОТРУДНИЧЕСТВО -->
  <section class="guide-collab">
    <div class="container">
      <div class="guide-collab__grid">
        <div class="guide-collab__left">
          <div class="guide-collab__photo">
            <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'guide-collab.webp'); ?>" alt="" width="870" height="587" loading="lazy">
          </div>
          <div class="guide-partner">
            <h2 class="guide-partner__title"><?php echo esc_html(minoksidil_acf('partner_title', 'Мы открыты к сотрудничеству', get_queried_object_id())); ?></h2>
            <div class="guide-partner__product">
              <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'collab.svg'); ?>" alt="" width="287" height="287" loading="lazy">
            </div>
            <p class="guide-partner__text"><?php echo wp_kses_post(minoksidil_acf('partner_text', 'У нас действует простая система промокодов: клиент получает скидку на заказ, а тот, кто вас рекомендует, — денежное вознаграждение с каждой продажи.', get_queried_object_id())); ?></p>
          </div>
        </div>

        <div class="guide-collab__right">
          <div class="guide-wholesale">
            <h2 class="guide-wholesale__title"><?php echo esc_html(minoksidil_acf('wholesale_title', 'Поставляем наши товары оптом под реализацию', get_queried_object_id())); ?></h2>
            <p class="guide-wholesale__text"><?php echo wp_kses_post(minoksidil_acf('wholesale_text', 'Если вам интересен такой формат работы, напишите нам — обсудим условия', get_queried_object_id())); ?></p>
            <div class="guide-wholesale__socials">
              <a href="<?php echo esc_url($tg); ?>" class="guide-wholesale__social" aria-label="Telegram" target="_blank" rel="noopener"></a>
              <a href="<?php echo esc_url($tg2); ?>" class="guide-wholesale__social" aria-label="Telegram 2" target="_blank" rel="noopener"></a>

              <a href="<?php echo esc_url($max); ?>" class="guide-wholesale__social" aria-label="MAX" target="_blank" rel="noopener"></a>
              <a href="<?php echo esc_url($vk); ?>" class="guide-wholesale__social" aria-label="ВКонтакте" target="_blank" rel="noopener"></a>
              <a href="<?php echo esc_url($avito); ?>" class="guide-wholesale__social" aria-label="Avito" target="_blank" rel="noopener"></a>
            </div>
          </div>


        </div>
      </div>
    </div>
    <div class="guide-cta__grass" aria-hidden="true">
      <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'guide-grass.webp'); ?>" alt="" width="1920" height="452">
    </div>
  </section>





  <!-- GALLERY MODAL -->
  <div class="ba-gallery-modal" id="ba-gallery-modal" role="dialog" aria-modal="true" aria-label="Галерея" hidden>
    <div class="ba-gallery-modal__overlay"></div>
    <div class="ba-gallery-modal__inner">
      <button class="ba-gallery-modal__close" type="button" aria-label="Закрыть">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="none"><path d="M6 6L26 26M26 6L6 26" stroke="white" stroke-width="2.5" stroke-linecap="round"/></svg>
      </button>
      <button class="ba-gallery-modal__nav ba-gallery-modal__nav--prev" type="button" aria-label="Предыдущее">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M13 4L7 10L13 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <img class="ba-gallery-modal__img" src="" alt="">
      <button class="ba-gallery-modal__nav ba-gallery-modal__nav--next" type="button" aria-label="Следующее">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M7 4L13 10L7 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <span class="ba-gallery-modal__counter"></span>
    </div>
  </div>

</main>

<script>
(function () {
  var modal    = document.getElementById('ba-gallery-modal');
  if (!modal) return;
  var modalImg = modal.querySelector('.ba-gallery-modal__img');
  var counter  = modal.querySelector('.ba-gallery-modal__counter');
  var prevBtn  = modal.querySelector('.ba-gallery-modal__nav--prev');
  var nextBtn  = modal.querySelector('.ba-gallery-modal__nav--next');
  var gallery  = [];
  var current  = 0;

  function showImage(idx) {
    current = idx;
    modalImg.src = gallery[current];
    counter.textContent = (current + 1) + ' / ' + gallery.length;
    prevBtn.disabled = current === 0;
    nextBtn.disabled = current === gallery.length - 1;
    var hasMult = gallery.length > 1;
    prevBtn.style.display = hasMult ? '' : 'none';
    nextBtn.style.display = hasMult ? '' : 'none';
  }

  function openModal(urls, startIdx) {
    gallery = urls;
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    showImage(startIdx || 0);
  }

  function closeModal() {
    modal.hidden = true;
    document.body.style.overflow = '';
    modalImg.src = '';
  }

  document.querySelectorAll('.result-card__image[data-gallery]').forEach(function (el) {
    el.style.cursor = 'zoom-in';
    el.addEventListener('click', function () {
      var urls = JSON.parse(this.dataset.gallery || '[]');
      if (urls.length) openModal(urls, 0);
    });
    el.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); this.click(); }
    });
  });

  modal.querySelector('.ba-gallery-modal__overlay').addEventListener('click', closeModal);
  modal.querySelector('.ba-gallery-modal__close').addEventListener('click', closeModal);
  prevBtn.addEventListener('click', function () { if (current > 0) showImage(current - 1); });
  nextBtn.addEventListener('click', function () { if (current < gallery.length - 1) showImage(current + 1); });

  document.addEventListener('keydown', function (e) {
    if (modal.hidden) return;
    if (e.key === 'Escape')     closeModal();
    if (e.key === 'ArrowLeft')  prevBtn.click();
    if (e.key === 'ArrowRight') nextBtn.click();
  });
})();
</script>

<?php get_footer(); ?>
