<?php
$title   = get_sub_field('title');
$counter = get_sub_field('counter');

$counter = $counter ? absint($counter) : 10;


$listing_ids = yp_get_latest_paid_approved_listing_ids($counter);

if (empty($listing_ids)) {
    return;
}
?>

<section class="ogoloshennya_zmishani">
    <div class="container">

        <div class="ogoloshennya_zmishani__top">
            <?php if ($title): ?>
                <h2 class="ogoloshennya_zmishani__heading">
                    <?= esc_html($title); ?>
                </h2>
            <?php endif; ?>

            <div class="ogoloshennya_zmishani__arrows">
                <button
                        class="ogoloshennya_zmishani__arrow ogoloshennya_zmishani__arrow--prev"
                        type="button"
                        aria-label="Попереднє оголошення"
                >
                    ‹
                </button>

                <button
                        class="ogoloshennya_zmishani__arrow ogoloshennya_zmishani__arrow--next"
                        type="button"
                        aria-label="Наступне оголошення"
                >
                    ›
                </button>
            </div>
        </div>

        <div class="ogoloshennya_zmishani__slider js-ogoloshennya-zmishani-slider">

            <?php foreach ($listing_ids as $listing_id): ?>
                <?php
                $price = get_post_meta($listing_id, '_yp_price', true);

                $thumb_id = get_post_thumbnail_id($listing_id);

                $terms = get_the_terms($listing_id, 'yp_listing_category');
                $category_name = '';

                if (!empty($terms) && !is_wp_error($terms)) {
                    $category_name = $terms[0]->name;
                }
                ?>

                <article class="ogoloshennya_zmishani__item">
                    <a href="<?= esc_url(get_permalink($listing_id)); ?>" class="ogoloshennya_zmishani__card">

                        <div class="ogoloshennya_zmishani__image">
                            <?php if ($thumb_id): ?>
                                <?= wp_get_attachment_image($thumb_id, 'medium', false, array(
                                        'loading' => 'lazy',
                                        'alt'     => esc_attr(get_the_title($listing_id)),
                                )); ?>
                            <?php else: ?>
                                <img
                                        src="<?= esc_url(get_template_directory_uri() . '/images/placeholder-4.jpg'); ?>"
                                        alt="<?= esc_attr(get_the_title($listing_id)); ?>"
                                        loading="lazy"
                                >
                            <?php endif; ?>
                        </div>

                        <div class="ogoloshennya_zmishani__content">

                            <?php if ($category_name): ?>
                                <div class="ogoloshennya_zmishani__category">
                                    <?= esc_html($category_name); ?>
                                </div>
                            <?php endif; ?>

                            <h3 class="ogoloshennya_zmishani__title">
                                <?= esc_html(get_the_title($listing_id)); ?>
                            </h3>

                            <?php if ($price !== ''): ?>
                                <div class="ogoloshennya_zmishani__price">
                                    <?= esc_html($price); ?> грн
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
            $('.js-ogoloshennya-zmishani-slider').each(function () {
                const $slider = $(this);
                const $section = $slider.closest('.ogoloshennya_zmishani');

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
                    prevArrow: $section.find('.ogoloshennya_zmishani__arrow--prev'),
                    nextArrow: $section.find('.ogoloshennya_zmishani__arrow--next'),
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