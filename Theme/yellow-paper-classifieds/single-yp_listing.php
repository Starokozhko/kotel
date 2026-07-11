<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('yp_single_get_category_chain')) {
    function yp_single_get_category_chain($post_id) {
        $terms = get_the_terms($post_id, YP_Post_Types::TAXONOMY);

        if (empty($terms) || is_wp_error($terms)) {
            return array();
        }

        usort($terms, static function ($a, $b) {
            return count(get_ancestors($b->term_id, YP_Post_Types::TAXONOMY)) <=> count(get_ancestors($a->term_id, YP_Post_Types::TAXONOMY));
        });

        $leaf_term = $terms[0];
        $ancestor_ids = array_reverse(get_ancestors($leaf_term->term_id, YP_Post_Types::TAXONOMY));
        $chain = array();

        foreach ($ancestor_ids as $ancestor_id) {
            $ancestor = get_term($ancestor_id, YP_Post_Types::TAXONOMY);

            if ($ancestor && !is_wp_error($ancestor)) {
                $chain[] = $ancestor;
            }
        }

        $chain[] = $leaf_term;

        return $chain;
    }
}

if (!function_exists('yp_single_get_gallery_items')) {
    function yp_single_get_gallery_items($post_id, $image_ids) {
        $ids = array();
        $featured_id = get_post_thumbnail_id($post_id);

        if ($featured_id) {
            $ids[] = (int) $featured_id;
        }

        foreach ((array) $image_ids as $image_id) {
            $image_id = absint($image_id);

            if ($image_id && !in_array($image_id, $ids, true)) {
                $ids[] = $image_id;
            }
        }

        $items = array();

        foreach ($ids as $image_id) {
            $large = wp_get_attachment_image_src($image_id, 'large');
            $full = wp_get_attachment_image_src($image_id, 'full');
            $thumb = wp_get_attachment_image_src($image_id, 'thumbnail');

            if (!$large || !$full || !$thumb) {
                continue;
            }

            $alt = get_post_meta($image_id, '_wp_attachment_image_alt', true);

            $items[] = array(
                'id' => $image_id,
                'large' => $large[0],
                'full' => $full[0],
                'thumb' => $thumb[0],
                'alt' => $alt !== '' ? $alt : get_the_title($post_id),
            );
        }

        if (!$items) {
            $placeholder = get_stylesheet_directory_uri() . '/images/placeholder-4.jpg';

            $items[] = array(
                'id' => 0,
                'large' => $placeholder,
                'full' => $placeholder,
                'thumb' => $placeholder,
                'alt' => get_the_title($post_id),
            );
        }

        return $items;
    }
}

if (!function_exists('yp_single_format_date')) {
    function yp_single_format_date($datetime) {
        if (!$datetime instanceof DateTimeInterface) {
            return '';
        }

        $now = new DateTimeImmutable('now', wp_timezone());
        $date = $datetime->format('Y-m-d');
        $today = $now->format('Y-m-d');
        $yesterday = $now->modify('-1 day')->format('Y-m-d');

        if ($date === $today) {
            return sprintf(__('Сьогодні, %s', 'yellow-paper-classifieds'), $datetime->format('H:i'));
        }

        if ($date === $yesterday) {
            return sprintf(__('Вчора, %s', 'yellow-paper-classifieds'), $datetime->format('H:i'));
        }

        return $datetime->format('d.m.y, H:i');
    }
}

if (!function_exists('yp_single_get_public_query_args')) {
    function yp_single_get_public_query_args($post_id, $posts_per_page = 5) {
        $args = array(
            'post_type' => YP_Post_Types::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => $posts_per_page,
            'post__not_in' => array(absint($post_id)),
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        if (class_exists('YP_Listing_Meta')) {
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key' => YP_Listing_Meta::META_VISIBILITY,
                    'value' => YP_Listing_Meta::VIS_PUBLIC,
                    'compare' => '=',
                ),
                array(
                    'key' => YP_Listing_Meta::META_MODERATION_STATUS,
                    'value' => YP_Listing_Meta::MOD_APPROVED,
                    'compare' => '=',
                ),
            );
        }

        return $args;
    }
}

if (!function_exists('yp_single_get_recent_ids')) {
    function yp_single_get_recent_ids($current_post_id) {
        if (empty($_COOKIE['yp_recently_viewed'])) {
            return array();
        }

        $raw_ids = explode(',', sanitize_text_field(wp_unslash($_COOKIE['yp_recently_viewed'])));
        $ids = array_values(array_unique(array_filter(array_map('absint', $raw_ids))));

        return array_slice(array_values(array_diff($ids, array(absint($current_post_id)))), 0, 5);
    }
}

if (!function_exists('yp_single_render_card_grid')) {
    function yp_single_render_card_grid($query, $loader, $template_path) {
        if (!$query instanceof WP_Query || !$query->have_posts() || !$template_path) {
            return;
        }

        echo '<div class="yp-single-related__grid">';

        while ($query->have_posts()) {
            $query->the_post();
            $card = $loader->get_listing_card_data(get_the_ID());
            include $template_path;
        }

        echo '</div>';
        wp_reset_postdata();
    }
}

get_header();

$loader = new YP_Template_Loader(new YP_Listing_Images(), new YP_User_Profile());
$data = $loader->get_single_listing_data(get_the_ID());
$post_id = (int) $data['post_id'];
$author_id = (int) $data['author_id'];
$store = !empty($data['store_data']) && is_array($data['store_data']) ? $data['store_data'] : array();
$category_chain = yp_single_get_category_chain($post_id);
$leaf_term = $category_chain ? end($category_chain) : null;
$top_term = $category_chain ? reset($category_chain) : null;
$gallery_items = yp_single_get_gallery_items($post_id, $data['image_ids']);
$has_multiple_images = count($gallery_items) > 1;
$first_image = $gallery_items[0];
$price_type = !empty($data['price_type']) ? $data['price_type'] : YP_Frontend_Submission::PRICE_TYPE_NO_PRICE;
$price_label = $loader->get_price_type_label($price_type);
$phone_digits = preg_replace('/\D+/', '', (string) $data['phone_formatted']);
$user = $author_id ? get_user_by('id', $author_id) : null;
$seller_name = trim((string) ($store['store_name'] ?? ''));

if ($seller_name === '') {
    $seller_name = trim((string) $data['contact_name']);
}

if ($seller_name === '' && $user) {
    $seller_name = $user->display_name;
}

$account_type_label = '';

if ($author_id && function_exists('yp_get_user_account_type') && function_exists('yp_get_account_types')) {
    $account_types = yp_get_account_types();
    $account_type = yp_get_user_account_type($author_id);
    $account_type_label = isset($account_types[$account_type]) ? $account_types[$account_type] : '';
}

$compact_template = method_exists($loader, 'get_template_path') ? $loader->get_template_path('parts/listing-card-compact.php') : '';
$archive_url = get_post_type_archive_link(YP_Post_Types::POST_TYPE);
$seller_archive_url = ($author_id && function_exists('yp_get_seller_archive_url')) ? yp_get_seller_archive_url($author_id) : '';
$published = get_post_datetime($post_id, 'date', 'local');
$modified = get_post_datetime($post_id, 'modified', 'local');
$views = get_post_meta($post_id, '_yp_views', true);

if ($views === '') {
    $views = get_post_meta($post_id, 'yp_views', true);
}

$author_query_args = yp_single_get_public_query_args($post_id, 5);
$author_query_args['author'] = $author_id;
$author_query = $author_id ? new WP_Query($author_query_args) : null;

$recent_ids = yp_single_get_recent_ids($post_id);
$recent_query = null;

if ($recent_ids) {
    $recent_query_args = yp_single_get_public_query_args($post_id, 5);
    $recent_query_args['post__in'] = $recent_ids;
    $recent_query_args['orderby'] = 'post__in';
    $recent_query = new WP_Query($recent_query_args);
}

$category_query = null;

if ($leaf_term instanceof WP_Term) {
    $category_query_args = yp_single_get_public_query_args($post_id, 5);
    $category_query_args['tax_query'] = array(
        array(
            'taxonomy' => YP_Post_Types::TAXONOMY,
            'field' => 'term_id',
            'terms' => array((int) $leaf_term->term_id),
            'include_children' => false,
        ),
    );

    if ($author_id) {
        $category_query_args['author__not_in'] = array($author_id);
    }

    $category_query = new WP_Query($category_query_args);
}
?>

<main class="yp-single-listing" data-listing-id="<?php echo esc_attr($post_id); ?>">
    <div class="yp-single-listing__inner">
        <nav class="yp-single-breadcrumbs" aria-label="<?php esc_attr_e('Навігація', 'yellow-paper-classifieds'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Головна', 'yellow-paper-classifieds'); ?></a>
            <?php if ($archive_url) : ?>
                <span aria-hidden="true">/</span>
                <a href="<?php echo esc_url($archive_url); ?>"><?php esc_html_e('Оголошення', 'yellow-paper-classifieds'); ?></a>
            <?php endif; ?>
            <?php foreach ($category_chain as $term) : ?>
                <span aria-hidden="true">/</span>
                <a href="<?php echo esc_url(get_term_link($term)); ?>"><?php echo esc_html($term->name); ?></a>
            <?php endforeach; ?>
            <span aria-hidden="true">/</span>
            <span><?php echo esc_html($data['title']); ?></span>
        </nav>

        <header class="yp-single-listing__header">
            <div>
                <h1 class="yp-single-listing__title"><?php echo esc_html($data['title']); ?></h1>
                <div class="yp-single-listing__meta-line">
                    <?php if ($top_term instanceof WP_Term) : ?>
                        <a class="yp-single-listing__badge" href="<?php echo esc_url(get_term_link($top_term)); ?>"><?php echo esc_html($top_term->name); ?></a>
                    <?php endif; ?>
                    <?php if ($leaf_term instanceof WP_Term && (!$top_term || $leaf_term->term_id !== $top_term->term_id)) : ?>
                        <a class="yp-single-listing__category" href="<?php echo esc_url(get_term_link($leaf_term)); ?>"><?php echo esc_html($leaf_term->name); ?></a>
                    <?php endif; ?>
                    <?php if (!empty($data['location_name'])) : ?>
                        <span><?php echo esc_html($data['location_name']); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <div class="yp-single-listing__layout">
            <div class="yp-single-listing__content">
                <section class="yp-single-gallery" aria-label="<?php esc_attr_e('Фото оголошення', 'yellow-paper-classifieds'); ?>">
                    <div class="yp-single-gallery__stage">
                        <a class="yp-single-gallery__main-link" href="<?php echo esc_url($first_image['full']); ?>" data-yp-gallery-link data-fancybox="yp-listing-gallery-<?php echo esc_attr($post_id); ?>" data-caption="<?php echo esc_attr($data['title']); ?>">
                            <img
                                class="yp-single-gallery__main-image"
                                src="<?php echo esc_url($first_image['large']); ?>"
                                alt="<?php echo esc_attr($first_image['alt']); ?>"
                                loading="eager"
                                fetchpriority="high"
                                decoding="async"
                                data-yp-gallery-main
                            >
                        </a>

                        <?php if ($has_multiple_images) : ?>
                            <button class="yp-single-gallery__nav yp-single-gallery__nav--prev" type="button" data-yp-gallery-prev aria-label="<?php esc_attr_e('Попереднє фото', 'yellow-paper-classifieds'); ?>"><span aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14.2893 18.2929C13.8988 18.6834 13.2656 18.6834 12.8751 18.2929L7.9877 13.4006C7.2073 12.6195 7.2076 11.3537 7.9883 10.5729L12.8787 5.68254C13.2692 5.29202 13.9024 5.29202 14.2929 5.68254C14.6835 6.07307 14.6835 6.70623 14.2929 7.09676L10.1073 11.2824C9.7167 11.6729 9.7167 12.3061 10.1073 12.6966L14.2893 16.8787C14.6798 17.2692 14.6798 17.9023 14.2893 18.2929Z" fill="#ffffff"></path>
                    </svg>
                </span></button>
                            <button class="yp-single-gallery__nav yp-single-gallery__nav--next" type="button" data-yp-gallery-next aria-label="<?php esc_attr_e('Наступне фото', 'yellow-paper-classifieds'); ?>"><span aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.71069 18.2929C10.1012 18.6834 10.7344 18.6834 11.1249 18.2929L16.0123 13.4006C16.7927 12.6195 16.7924 11.3537 16.0117 10.5729L11.1213 5.68254C10.7308 5.29202 10.0976 5.29202 9.70708 5.68254C9.31655 6.07307 9.31655 6.70623 9.70708 7.09676L13.8927 11.2824C14.2833 11.6729 14.2833 12.3061 13.8927 12.6966L9.71069 16.8787C9.32016 17.2692 9.32016 17.9023 9.71069 18.2929Z" fill="#ffffff"></path>
                    </svg>
                </span></button>
                        <?php endif; ?>
                    </div>

                    <?php if ($has_multiple_images) : ?>
                        <div class="yp-single-gallery__thumbs" data-yp-gallery-thumbs>
                            <?php foreach ($gallery_items as $index => $item) : ?>
                                <button
                                    class="yp-single-gallery__thumb<?php echo $index === 0 ? ' is-active' : ''; ?>"
                                    type="button"
                                    data-yp-gallery-thumb
                                    data-index="<?php echo esc_attr($index); ?>"
                                    data-large="<?php echo esc_url($item['large']); ?>"
                                    data-full="<?php echo esc_url($item['full']); ?>"
                                    data-alt="<?php echo esc_attr($item['alt']); ?>"
                                    aria-current="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                                >
                                    <img src="<?php echo esc_url($item['thumb']); ?>" alt="<?php echo esc_attr($item['alt']); ?>" loading="lazy" decoding="async">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <?php if (!empty($data['characteristics'])) : ?>
                    <section class="yp-single-section yp-single-characteristics">
                        <h2><?php esc_html_e('Характеристики', 'yellow-paper-classifieds'); ?></h2>
                        <dl>
                            <?php foreach ($data['characteristics'] as $row) : ?>
                                <?php
                                $label = isset($row['label']) ? trim((string) $row['label']) : '';
                                $value = isset($row['value']) ? trim((string) $row['value']) : '';
                                ?>
                                <?php if ($label !== '' && $value !== '') : ?>
                                    <div>
                                        <dt><?php echo esc_html($label); ?></dt>
                                        <dd><?php echo esc_html($value); ?></dd>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </dl>
                    </section>
                <?php endif; ?>

                <?php if (trim(wp_strip_all_tags((string) $data['content'])) !== '') : ?>
                    <section class="yp-single-section yp-single-description">
                        <h2><?php esc_html_e('Опис', 'yellow-paper-classifieds'); ?></h2>
                        <div class="yp-single-description__content">
                            <?php echo wp_kses_post($data['content']); ?>
                        </div>
                    </section>
                <?php endif; ?>
            </div>

            <aside class="yp-single-listing__sidebar" aria-label="<?php esc_attr_e('Деталі оголошення', 'yellow-paper-classifieds'); ?>">
                <section class="yp-single-sidebar-card yp-single-price-card">
                    <p class="yp-single-price-card__label"><?php echo esc_html($price_label); ?></p>

                    <?php if (!empty($data['special_price']) && in_array($price_type, array(YP_Frontend_Submission::PRICE_TYPE_SALE, YP_Frontend_Submission::PRICE_TYPE_CLEARANCE), true)) : ?>
                        <p class="yp-single-price-card__old"><?php echo esc_html($data['price']); ?> грн.</p>
                        <p class="yp-single-price-card__price"><?php echo esc_html($data['special_price']); ?> грн.</p>
                    <?php elseif (!empty($data['price'])) : ?>
                        <p class="yp-single-price-card__price"><?php echo esc_html($data['price']); ?> грн.</p>
                    <?php else : ?>
                        <p class="yp-single-price-card__price yp-single-price-card__price--muted"><?php esc_html_e('Ціна не вказана', 'yellow-paper-classifieds'); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($data['sale_conditions'])) : ?>
                        <p class="yp-single-price-card__note"><?php echo esc_html($data['sale_conditions']); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($data['availability']) && $data['availability'] instanceof WP_Term && function_exists('yp_render_listing_availability_badge')) : ?>
                        <div class="yp-single-price-card__availability">
                            <?php echo wp_kses_post(yp_render_listing_availability_badge($post_id)); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($data['phone_formatted']) && $phone_digits !== '') : ?>
                        <a class="yp-single-price-card__phone" href="tel:+<?php echo esc_attr($phone_digits); ?>"><?php echo esc_html($data['phone_formatted']); ?></a>
                    <?php endif; ?>

                    <?php if (!empty($data['contact_name'])) : ?>
                        <p class="yp-single-price-card__contact"><?php echo esc_html($data['contact_name']); ?></p>
                    <?php endif; ?>
                </section>

                <section class="yp-single-sidebar-card yp-single-seller-card">
                    <div class="yp-single-seller-card__top">
                        <?php if (!empty($store['store_logo_id'])) : ?>
                            <?php echo wp_kses_post(wp_get_attachment_image((int) $store['store_logo_id'], 'thumbnail', false, array('class' => 'yp-single-seller-card__logo'))); ?>
                        <?php else : ?>
                            <span class="yp-single-seller-card__logo yp-single-seller-card__logo--placeholder" aria-hidden="true"><?php $seller_initial = $seller_name !== '' ? $seller_name : __('П', 'yellow-paper-classifieds'); echo esc_html(function_exists('mb_substr') ? mb_substr($seller_initial, 0, 1) : substr($seller_initial, 0, 1)); ?></span>
                        <?php endif; ?>
                        <div>
                            <h2><?php echo esc_html($seller_name !== '' ? $seller_name : __('Продавець', 'yellow-paper-classifieds')); ?></h2>
                            <?php if ($account_type_label !== '') : ?>
                                <p><?php echo esc_html($account_type_label); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <ul class="yp-single-seller-card__list">
                        <?php if (!empty($store['store_phone'])) : ?>
                            <li><span><?php esc_html_e('Телефон', 'yellow-paper-classifieds'); ?></span><strong><?php echo esc_html($loader->format_phone_for_display($store['store_phone'])); ?></strong></li>
                        <?php endif; ?>
                        <?php if (!empty($store['store_address'])) : ?>
                            <li><span><?php esc_html_e('Адреса', 'yellow-paper-classifieds'); ?></span><a href="<?php echo esc_url('https://www.google.com/maps/search/?api=1&query=' . rawurlencode($store['store_address'])); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($store['store_address']); ?></a></li>
                        <?php endif; ?>
                        <?php if (!empty($store['store_work_hours'])) : ?>
                            <li><span><?php esc_html_e('Графік', 'yellow-paper-classifieds'); ?></span><strong><?php echo esc_html($store['store_work_hours']); ?></strong></li>
                        <?php endif; ?>
                        <?php if (!empty($store['store_website'])) : ?>
                            <li><span><?php esc_html_e('Сайт', 'yellow-paper-classifieds'); ?></span><a href="<?php echo esc_url($store['store_website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(preg_replace('#^https?://#', '', $store['store_website'])); ?></a></li>
                        <?php endif; ?>
                    </ul>

                    <?php if ($seller_archive_url) : ?>
                        <a class="yp-single-seller-card__all" href="<?php echo esc_url($seller_archive_url); ?>"><?php esc_html_e('Всі оголошення продавця', 'yellow-paper-classifieds'); ?></a>
                    <?php endif; ?>
                </section>

                <section class="yp-single-sidebar-card yp-single-info-card">
                    <h2><?php esc_html_e('Інформація', 'yellow-paper-classifieds'); ?></h2>
                    <dl>
                    <?php if ($published instanceof DateTimeInterface) : ?>
                            <div><dt><?php esc_html_e('Опубліковано', 'yellow-paper-classifieds'); ?></dt><dd><?php echo esc_html(yp_single_format_date($published)); ?></dd></div>
                        <?php endif; ?>
                        <?php if ($modified instanceof DateTimeInterface && $published instanceof DateTimeInterface && $modified->getTimestamp() !== $published->getTimestamp()) : ?>
                            <div><dt><?php esc_html_e('Оновлено', 'yellow-paper-classifieds'); ?></dt><dd><?php echo esc_html(yp_single_format_date($modified)); ?></dd></div>
                        <?php endif; ?>
                    <?php if (!empty($data['sku'])) : ?>
                        <div><dt><?php esc_html_e('Артикул', 'yellow-paper-classifieds'); ?></dt><dd><?php echo esc_html($data['sku']); ?></dd></div>
                    <?php endif; ?>
                    <?php if (!empty($data['condition']) && $data['condition'] instanceof WP_Term) : ?>
                        <div><dt><?php esc_html_e('Стан', 'yellow-paper-classifieds'); ?></dt><dd><?php echo esc_html($data['condition']->name); ?></dd></div>
                    <?php endif; ?>
                    <?php if (!empty($data['availability']) && $data['availability'] instanceof WP_Term) : ?>
                        <div><dt><?php esc_html_e('Наявність', 'yellow-paper-classifieds'); ?></dt><dd><?php echo esc_html($data['availability']->name); ?></dd></div>
                    <?php endif; ?>
                    <div><dt><?php esc_html_e('ID', 'yellow-paper-classifieds'); ?></dt><dd><?php echo esc_html($post_id); ?></dd></div>
                        <?php if ($views !== '') : ?>
                            <div><dt><?php esc_html_e('Перегляди', 'yellow-paper-classifieds'); ?></dt><dd><?php echo esc_html(absint($views)); ?></dd></div>
                        <?php endif; ?>
                    </dl>
                </section>
            </aside>
        </div>

        <?php if ($author_query instanceof WP_Query && $author_query->have_posts()) : ?>
            <section class="yp-single-related">
                <h2><?php esc_html_e('Інші оголошення продавця', 'yellow-paper-classifieds'); ?></h2>
                <?php yp_single_render_card_grid($author_query, $loader, $compact_template); ?>
            </section>
        <?php endif; ?>

        <?php if ($recent_query instanceof WP_Query && $recent_query->have_posts()) : ?>
            <section class="yp-single-related">
                <h2><?php esc_html_e('Ви нещодавно переглядали', 'yellow-paper-classifieds'); ?></h2>
                <?php yp_single_render_card_grid($recent_query, $loader, $compact_template); ?>
            </section>
        <?php endif; ?>

        <?php if ($category_query instanceof WP_Query && $category_query->have_posts()) : ?>
            <section class="yp-single-related">
                <h2><?php esc_html_e('Схожі оголошення', 'yellow-paper-classifieds'); ?></h2>
                <?php yp_single_render_card_grid($category_query, $loader, $compact_template); ?>
            </section>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();