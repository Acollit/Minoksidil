<?php
defined('ABSPATH') || exit;
get_header();
while (have_posts()) : the_post();
global $product;
// ACF gallery field name: 'product_gallery'
$acf_gallery = get_field('product_gallery', $product->get_id()) ?: [];

if (!empty($acf_gallery)) {
    $gallery_images = array_map(function($img) {
        if (is_array($img)) {
            return [
                'full'  => $img['sizes']['product-single'] ?? $img['url'],
                'thumb' => $img['sizes']['thumbnail'] ?? $img['url'],
                'alt'   => $img['alt'] ?: '',
            ];
        }
        // Return Format = URL or ID
        $url = is_numeric($img) ? wp_get_attachment_image_url((int)$img, 'product-single') : $img;
        $thumb = is_numeric($img) ? wp_get_attachment_image_url((int)$img, 'thumbnail') : $img;
        return ['full' => $url, 'thumb' => $thumb, 'alt' => ''];
    }, $acf_gallery);
} else {
    // fallback to WooCommerce images
    $img_id      = $product->get_image_id();
    $gallery_ids = $product->get_gallery_image_ids();
    $fallback_main = $img_id ? wp_get_attachment_image_url($img_id, 'product-single') : MINOKSIDIL_IMG . 'product-1.webp';
    $gallery_images = [];
    if ($img_id) {
        $gallery_images[] = [
            'full'  => $fallback_main,
            'thumb' => wp_get_attachment_image_url($img_id, 'thumbnail'),
            'alt'   => '',
        ];
    } else {
        $gallery_images[] = ['full' => MINOKSIDIL_IMG . 'product-1.webp', 'thumb' => MINOKSIDIL_IMG . 'product-1.webp', 'alt' => ''];
    }
    foreach ($gallery_ids as $gid) {
        $gallery_images[] = [
            'full'  => wp_get_attachment_image_url($gid, 'product-single'),
            'thumb' => wp_get_attachment_image_url($gid, 'thumbnail'),
            'alt'   => '',
        ];
    }
}

$main_img = $gallery_images[0]['full'];
$in_stock    = $product->is_in_stock();
?>
<main class="main">
  <section class="product-page">
    <div class="container">

      <!-- BREADCRUMBS -->
      <nav class="breadcrumbs" aria-label="Breadcrumb">
        <ol class="breadcrumbs__list">
          <li class="breadcrumbs__item"><a href="<?php echo esc_url(home_url('/')); ?>" class="breadcrumbs__link">Главная</a></li>
          <li class="breadcrumbs__item"><a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>" class="breadcrumbs__link">Каталог</a></li>
          <li class="breadcrumbs__item breadcrumbs__item--current" aria-current="page"><?php the_title(); ?></li>
        </ol>
      </nav>

      <!-- PRODUCT LAYOUT -->
      <div class="product-page__layout">

        <!-- TITLE (mobile only) -->
        <p class="product-info__title product-info__title--mobile"><?php the_title(); ?></p>

        <!-- GALLERY -->
        <div class="product-gallery">
          <div class="product-gallery__main">
            <button class="product-gallery__nav product-gallery__nav--prev" type="button" aria-label="Предыдущее фото">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M13 4L7 10L13 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
            <img class="product-gallery__img" id="gallery-main-img"
                 src="<?php echo esc_url($main_img); ?>"
                 alt="<?php echo esc_attr($product->get_name()); ?>" width="280" height="280">
            <button class="product-gallery__nav product-gallery__nav--next" type="button" aria-label="Следующее фото">
              <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M7 4L13 10L7 16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </button>
          </div>
          <div class="product-gallery__thumbs">
            <?php foreach ($gallery_images as $i => $gimg) : ?>
              <button class="product-gallery__thumb <?php echo $i === 0 ? 'is-active' : ''; ?>"
                      type="button"
                      data-src="<?php echo esc_url($gimg['full']); ?>"
                      aria-label="Фото <?php echo ($i + 1); ?>">
                <img src="<?php echo esc_url($gimg['thumb']); ?>" alt="<?php echo esc_attr($gimg['alt']); ?>" width="60" height="60">
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- PRODUCT INFO -->
        <div class="product-info">
          <h1 class="product-info__title product-info__title--desktop"><?php the_title(); ?></h1>

          <?php if ($product->get_short_description()) : ?>
            <p class="product-info__desc"><?php echo wp_kses_post($product->get_short_description()); ?></p>
          <?php endif; ?>

          <p class="product-info__price"><?php echo $product->get_price_html(); ?></p>

          <div class="product-info__buy">
            <form class="cart" action="<?php echo esc_url(apply_filters('woocommerce_add_to_cart_form_action', $product->get_permalink())); ?>" method="post">
              <div class="product-qty">
                <button class="product-qty__btn product-qty__btn--minus" type="button" aria-label="Уменьшить количество">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M4 9H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
                <?php $max_qty = $product->get_max_purchase_quantity(); ?>
                <input type="number" class="input-reset product-qty__value" name="quantity" value="1" min="1"
                       <?php echo $max_qty > 0 ? 'max="' . esc_attr($max_qty) . '"' : ''; ?>>
                <button class="product-qty__btn product-qty__btn--plus" type="button" aria-label="Увеличить количество">
                  <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><path d="M9 4V14M4 9H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                </button>
              </div>
              <?php if ($in_stock) : ?>
                <button class="product-info__cart-btn" type="submit" name="add-to-cart" value="<?php echo absint($product->get_id()); ?>">
                  <span>Добавить в корзину</span>
                  <span class="product-info__cart-icon"><?php echo minoksidil_cart_svg('#000D24'); ?></span>
                </button>
              <?php else : ?>
                <button class="product-info__cart-btn" type="button" disabled>
                  <span>Нет в наличии</span>
                </button>
              <?php endif; ?>
            </form>
          </div>

          <?php
          $sostav = get_post_meta($product->get_id(), '_product_sostav', true);
          $sposob = get_post_meta($product->get_id(), '_product_sposob', true);
          ?>
          <?php if ($sostav || $sposob) : ?>
            <?php if ($sostav) : ?>
              <div class="product-section">
                <h2 class="product-section__title">Состав</h2>
                <div class="product-section__text"><?php echo nl2br(esc_html($sostav)); ?></div>
              </div>
            <?php endif; ?>
            <?php if ($sposob) : ?>
              <div class="product-section">
                <h2 class="product-section__title">Способ применения</h2>
                <div class="product-section__text"><?php echo nl2br(esc_html($sposob)); ?></div>
              </div>
            <?php endif; ?>
          <?php elseif ($product->get_description()) : ?>
            <div class="product-section">
              <div class="product-section__text"><?php echo nl2br(wp_kses_post($product->get_description())); ?></div>
            </div>
          <?php endif; ?>


        </div>

      </div>

    </div>
  </section>

  <!-- С ЭТИМ ТОВАРОМ ПОКУПАЮТ -->
  <?php
  $related_ids = wc_get_related_products($product->get_id(), 4);
  $related     = array_filter(array_map('wc_get_product', $related_ids));
  if ($related) :
  ?>
  <section class="catalog">
    <div class="container">
      <div class="catalog__top">
        <h2 class="catalog__title">С этим товаром покупают</h2>
      </div>
      <div class="catalog__grid">
        <?php foreach ($related as $rp) :
            $ru = minoksidil_get_product_catalog_img($rp);
        ?>
          <div class="product-card">
            <div class="product-card__box">
              <?php if ($rp->is_featured()) : ?><span class="product-card__badge">NEW</span><?php endif; ?>
              <div class="product-card__img">
                <img src="<?php echo esc_url($ru); ?>" alt="<?php echo esc_attr($rp->get_name()); ?>" width="312" height="312" loading="lazy">
              </div>
              <button class="product-card__add-btn" type="button" data-product-id="<?php echo absint($rp->get_id()); ?>" data-add-url="<?php echo esc_url($rp->add_to_cart_url()); ?>">
                <span>Добавить в корзину</span>
                <span class="product-card__add-icon"><?php echo minoksidil_cart_svg('#000D24'); ?></span>
              </button>
            </div>
            <p class="product-card__name"><a href="<?php echo esc_url($rp->get_permalink()); ?>"><?php echo esc_html($rp->get_name()); ?></a></p>
            <p class="product-card__price"><?php echo $rp->get_price_html(); ?></p>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

</main>
<?php endwhile; get_footer(); ?>
