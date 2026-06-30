<?php
$title        = get_sub_field('title');
$counter      = get_sub_field('counter');
$order_type   = get_sub_field('listings_order');
$categories   = get_sub_field('listing_categories');
$view         = get_sub_field('listing_view');
$extra_fields = get_sub_field('listing_extra_fields');

$counter      = $counter ? absint($counter) : 30;
$order_type   = $order_type ?: 'latest_created';
$view         = $view ?: 'grid_3';
$extra_fields = is_array($extra_fields) ? $extra_fields : array();

$listing_ids = yp_get_listings_section_ids(array(
    'limit'      => $counter,
    'order_type' => $order_type,
    'categories' => $categories,
));

if (empty($listing_ids)) {
    return;
}

$section_classes = array(
    'yp-listings-section',
    'yp-listings-section--' . sanitize_html_class($view),
);

$is_slider = ($view === 'slider');
?>

<section class="<?php echo esc_attr(implode(' ', $section_classes)); ?>">
    <div class="container">

        <?php if ($title): ?>
            <div class="yp-listings-section__top">
                <h2 class="yp-listings-section__heading">
                    <?php echo esc_html($title); ?>
                </h2>

                <?php if ($is_slider): ?>
                    <div class="yp-listings-section__arrows">
                        <button class="yp-listings-section__arrow yp-listings-section__arrow--prev" type="button" aria-label="Попереднє оголошення">
                            ‹
                        </button>

                        <button class="yp-listings-section__arrow yp-listings-section__arrow--next" type="button" aria-label="Наступне оголошення">
                            ›
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="yp-listings-section__items <?php echo $is_slider ? 'js-yp-listings-section-slider' : ''; ?>">

            <?php foreach ($listing_ids as $listing_id): ?>
                <?php
                $price      = get_post_meta($listing_id, '_yp_price', true);
                $store_name = get_post_meta($listing_id, '_yp_store_name', true);

                if (!$store_name) {
                    $author_id = (int) get_post_field('post_author', $listing_id);
                    $author    = $author_id ? get_userdata($author_id) : null;

                    if ($author) {
                        $store_name = $author->display_name;
                    }
                }

                $thumb_id = get_post_thumbnail_id($listing_id);

                $terms = get_the_terms($listing_id, 'yp_listing_category');
                $category_name = '';

                if (!empty($terms) && !is_wp_error($terms)) {
                    $category_name = $terms[0]->name;
                }
                ?>

                <article class="yp-listings-section__item">
                    <a href="<?php echo esc_url(get_permalink($listing_id)); ?>" class="yp-listings-section__card">

                        <?php if (in_array('image', $extra_fields, true)): ?>
                            <div class="yp-listings-section__image">
                                <?php if ($thumb_id): ?>
                                    <?php echo wp_get_attachment_image($thumb_id, 'medium', false, array(
                                        'loading' => 'lazy',
                                        'alt'     => esc_attr(get_the_title($listing_id)),
                                    )); ?>
                                <?php else: ?>
                                    <div class="yp-listings-section__no-image">
                                        <img
                                                src="<?= esc_url(get_template_directory_uri() . '/images/placeholder-4.jpg'); ?>"
                                                alt="<?= esc_attr(get_the_title($listing_id)); ?>"
                                                loading="lazy"
                                        >
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <div class="yp-listings-section__content">

                            <?php if (in_array('category', $extra_fields, true) && $category_name): ?>
                                <div class="yp-listings-section__category">
                                    <?php echo esc_html($category_name); ?>
                                </div>
                            <?php endif; ?>

                            <h3 class="yp-listings-section__title">
                                <?php echo esc_html(get_the_title($listing_id)); ?>
                            </h3>

                            <?php if (in_array('price', $extra_fields, true) && $price !== ''): ?>
                                <div class="yp-listings-section__price">
                                    <?php echo esc_html($price); ?> грн
                                </div>
                            <?php endif; ?>

                            <?php if (in_array('store_name', $extra_fields, true) && $store_name): ?>
                                <div class="yp-listings-section__store">
                                    <?php echo esc_html($store_name); ?>
                                </div>
                            <?php endif; ?>

                        </div>

                    </a>
                </article>

            <?php endforeach; ?>

        </div>

    </div>
    <script>
        jQuery(function ($) {
            $('.js-yp-listings-section-slider').each(function () {
                const $slider = $(this);
                const $section = $slider.closest('.yp-listings-section');

                if ($slider.hasClass('slick-initialized')) {
                    return;
                }

                $slider.slick({
                    slidesToShow: 4,
                    slidesToScroll: 1,
                    infinite: true,
                    autoplay: true,
                    autoplaySpeed: 2500,
                    speed: 500,
                    pauseOnHover: true,
                    pauseOnFocus: true,
                    arrows: true,
                    dots: false,
                    prevArrow: $section.find('.yp-listings-section__arrow--prev'),
                    nextArrow: $section.find('.yp-listings-section__arrow--next'),
                    responsive: [
                        {
                            breakpoint: 1200,
                            settings: {
                                slidesToShow: 3
                            }
                        },
                        {
                            breakpoint: 768,
                            settings: {
                                slidesToShow: 2
                            }
                        },
                        {
                            breakpoint: 520,
                            settings: {
                                slidesToShow: 1
                            }
                        }
                    ]
                });
            });
        });
    </script>
</section>