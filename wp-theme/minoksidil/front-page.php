<?php
get_header();

$hero_title = minoksidil_acf('hero_title', '- Оригинальные средства <br>- Все в наличии <br>- Быстрая доставка');
$hero_desc  = minoksidil_acf('hero_desc', 'Восстановление волос - это сейчас простой и недорогой процесс. Если возникнут любые вопросы - мы всегда на связи!');
?>
<main class="main">

  <!-- HERO -->
  <section class="hero">
    <div class="container hero__container">
      <div class="hero__content">
        <h1 class="hero__title"><?php echo wp_kses_post($hero_title); ?></h1>
        <p class="hero__desc"><?php echo wp_kses_post($hero_desc); ?></p>

      </div>

    </div>
    <div class="hero__grass" aria-hidden="true">
      <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'hero-grass.webp'); ?>" alt="" width="1920" height="640">
    </div>
  </section>

  <!-- CATALOG preview -->
  <section class="catalog" id="catalog">
    <div class="container">
      <div class="catalog__top">
        <h2 class="catalog__title"><?php echo esc_html(minoksidil_acf('catalog_title', 'Всё, что нужно для роста')); ?></h2>
        <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn btn--outline"><?php echo esc_html(minoksidil_acf('catalog_btn', 'Все товары')); ?></a>
      </div>
      <div class="catalog__grid">
        <?php
        $products = wc_get_products(['limit' => 8, 'status' => 'publish', 'category' => ['loseny'], 'orderby' => 'menu_order', 'order' => 'ASC']);
        foreach ($products as $product) :
            $img_url  = minoksidil_get_product_catalog_img($product);
            $is_new   = $product->is_featured();
            $can_ajax = $product->supports('ajax_add_to_cart') && $product->is_purchasable() && $product->is_in_stock();
            $btn_class = 'product-card__add-btn add_to_cart_button' . ($can_ajax ? ' ajax_add_to_cart' : '');
        ?>
          <div class="product-card">
            <div class="product-card__box">
              <?php if ($is_new) : ?><span class="product-card__badge">NEW</span><?php endif; ?>
              <div class="product-card__img">
                <img src="<?php echo esc_url($img_url); ?>" alt="<?php echo esc_attr($product->get_name()); ?>" width="312" height="312" loading="lazy">
              </div>
              <a class="<?php echo esc_attr($btn_class); ?>" href="<?php echo esc_url($product->add_to_cart_url()); ?>" data-product_id="<?php echo absint($product->get_id()); ?>" data-quantity="1" rel="nofollow">
                <span>Добавить в корзину</span>
                <span class="product-card__add-icon"><?php echo minoksidil_cart_svg('#000D24'); ?></span>
              </a>
            </div>
            <div class="product-card__inner">
              <p class="product-card__name"><a href="<?php echo esc_url($product->get_permalink()); ?>"><?php echo esc_html($product->get_name()); ?></a></p>
              <p class="product-card__price"><?php echo $product->get_price_html(); ?></p>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- COURSE PICKER
  <section class="course" id="guide">
    <div class="container">
      <div class="course__inner">
        <div class="course__content">
          <h2 class="course__title">Подберем курс для вас</h2>
          <p class="course__desc">Останавливаем выпадение, пробуждаем спящие фолликулы. Только сертифицированные аналоги Rogaine и Generolon. Консультация трихолога в подарок при заказе курса.</p>
          <button class="btn btn--green" data-modal-open>Хочу подобрать курс</button>
          <div class="course__images">
            <div class="course__img-item"><img src="<?php echo esc_url(MINOKSIDIL_IMG . 'course-img-1.webp'); ?>" alt="" width="277" height="277" loading="lazy"></div>
            <div class="course__img-item"><img src="<?php echo esc_url(MINOKSIDIL_IMG . 'course-img-2.webp'); ?>" alt="" width="277" height="277" loading="lazy"></div>
            <div class="course__img-item"><img src="<?php echo esc_url(MINOKSIDIL_IMG . 'course-img-3.webp'); ?>" alt="" width="277" height="277" loading="lazy"></div>
          </div>
        </div>
        <div class="course__visual" aria-hidden="true">
          <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'course-grass.webp'); ?>" alt="" class="course__grass">
        </div>
      </div>
    </div>
  </section>
  -->
  <!-- WHY US -->
  <?php
  $why_defaults = [
      ['card_title' => 'Оригинальные средства',        'card_img' => MINOKSIDIL_IMG . 'why-1.webp', 'card_desc' => 'Приобретаем товары у производителей и не работаем с сомнительными копиями'],
      ['card_title' => 'Всё в наличии',                'card_img' => MINOKSIDIL_IMG . 'why-2.webp', 'card_desc' => 'Все позиции всегда держим на складе, чтобы вы могли использовать средства регулярно и без перерывов'],
      ['card_title' => 'Информация<br>и консультация', 'card_img' => MINOKSIDIL_IMG . 'why-3.webp', 'card_desc' => 'Можно получить консультацию специалиста, а также изучить подробные инструкции и материалы'],
      ['card_title' => 'Поддержка<br>на связи',        'card_img' => MINOKSIDIL_IMG . 'why-4.webp', 'card_desc' => 'Быстро отвечаем по заказу, оплате, доставке, наличию и другим вопросам'],
  ];
  $why_cards = minoksidil_acf_rows('why_cards', $why_defaults);
  ?>
  <section class="why">
    <div class="container">
      <h2 class="why__title"><?php echo esc_html(minoksidil_acf('why_title', 'Почему мы?')); ?></h2>
      <div class="why__grid">
        <?php foreach ($why_cards as $card) :
            $card_img = minoksidil_acf_img($card['card_img'] ?? '');
        ?>
        <div class="why-card">
          <p class="why-card__title"><?php echo wp_kses_post($card['card_title'] ?? ''); ?></p>
          <?php if ($card_img) : ?>
          <div class="why-card__img"><img src="<?php echo esc_url($card_img); ?>" alt="" width="279" height="253" loading="lazy"></div>
          <?php endif; ?>
          <p class="why-card__desc"><?php echo wp_kses_post($card['card_desc'] ?? ''); ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- REVIEWS -->
  <?php
  $review_results = get_posts(['post_type' => 'result', 'posts_per_page' => -1, 'post_status' => 'publish']);
  if ($review_results) :
  ?>
  <section class="reviews">
    <div class="container">
      <div class="reviews__header">
        <h2 class="reviews__title"><?php echo esc_html(minoksidil_acf('reviews_title', 'Истории восстановления')); ?></h2>
        <div class="reviews__nav">
          <button class="reviews__nav-btn" aria-label="Предыдущий отзыв">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="none"><path d="M19 6L10 15L19 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
          <button class="reviews__nav-btn" aria-label="Следующий отзыв">
            <svg width="30" height="30" viewBox="0 0 30 30" fill="none"><path d="M11 6L20 15L11 24" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </button>
        </div>
      </div>
      <div class="swiper reviews__slider">
        <div class="swiper-wrapper">
          <?php
          foreach ($review_results as $r) :
              $acf_gallery = get_field('result_gallery', $r->ID) ?: [];
              if (!empty($acf_gallery)) {
                  $gallery_urls = array_values(array_filter(array_map(function ($img) {
                      if (is_array($img)) return $img['sizes']['large'] ?? $img['url'];
                      if (is_numeric($img)) return wp_get_attachment_image_url((int) $img, 'large') ?: '';
                      return (string) $img;
                  }, $acf_gallery)));
                  $img_url = $gallery_urls[0] ?? MINOKSIDIL_IMG . 'review-woman.webp';
              } else {
                  $img_id  = get_post_thumbnail_id($r->ID);
                  $img_url = $img_id ? wp_get_attachment_image_url($img_id, 'large') : MINOKSIDIL_IMG . 'review-woman.webp';
              }
              $person = get_post_meta($r->ID, '_result_person', true)    ?: get_the_title($r->ID);
              $form   = get_post_meta($r->ID, '_result_form', true);
              $freq   = get_post_meta($r->ID, '_result_frequency', true);
              $dur    = get_post_meta($r->ID, '_result_duration', true);
              $out    = get_post_meta($r->ID, '_result_outcome', true);
          ?>
            <div class="swiper-slide">
              <div class="reviews__inner">
                <div class="reviews__content">
                  <p class="reviews__name"><?php echo esc_html($person); ?></p>
                  <div class="reviews__specs">
                    <?php if ($form) : ?><div class="reviews__spec"><span class="reviews__spec-label">Форма:</span><span class="reviews__spec-value"><?php echo esc_html($form); ?></span></div><?php endif; ?>
                    <?php if ($freq) : ?><div class="reviews__spec"><span class="reviews__spec-label">Частота применения:</span><span class="reviews__spec-value"><?php echo esc_html($freq); ?></span></div><?php endif; ?>
                    <?php if ($dur)  : ?><div class="reviews__spec"><span class="reviews__spec-label">Срок:</span><span class="reviews__spec-value"><?php echo esc_html($dur); ?></span></div><?php endif; ?>
                    <?php if ($out)  : ?><div class="reviews__spec"><span class="reviews__spec-label">Результат:</span><span class="reviews__spec-value"><?php echo esc_html($out); ?></span></div><?php endif; ?>
                  </div>
                  <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="btn btn--green reviews__cta"><?php echo esc_html(minoksidil_acf('reviews_btn', 'Попробовать')); ?></a>
                </div>
                <div class="reviews__photo">
                  <img src="<?php echo esc_url($img_url); ?>" alt="Результат <?php echo esc_attr($person); ?>" width="870" height="580" loading="lazy">
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- ARTICLE -->
  <section class="article">
    <div class="article__grass" aria-hidden="true">
      <img src="<?php echo esc_url(MINOKSIDIL_IMG . 'article-grass.webp'); ?>" alt="" width="1920" height="645">
    </div>
    <div class="container">
      <div class="article__top">
        <h2 class="article__title"><?php echo esc_html(minoksidil_acf('article_title', 'Как правильно наносить лосьон для роста волос')); ?></h2>
        <a href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>" class="article__link"><?php echo esc_html(minoksidil_acf('article_link', 'Больше статей')); ?></a>
      </div>
      <p class="article__desc"><?php echo wp_kses_post(minoksidil_acf('article_desc', 'Правильное нанесение лосьона — это не сложная процедура и не отдельный ритуал на полчаса. Наоборот, всё должно быть максимально просто, понятно и удобно. В идеале нанесение должно занимать буквально минуту и быть такой же привычной рутиной, как чистка зубов.')); ?></p>
    </div>
  </section>

</main>
<?php get_footer(); ?>
