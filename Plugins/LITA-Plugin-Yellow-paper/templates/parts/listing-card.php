<?php
if (!defined('ABSPATH')) {
    exit;
}


$parent_category_name = '';
$parent_category_slug = '';

$listing_description = '';
$published_label = '';
$account_type_label = '';

$post_id = !empty($card['post_id']) ? (int)$card['post_id'] : 0;

/**
 * Основна / батьківська категорія
 */
if ($post_id) {
    $terms = get_the_terms($post_id, 'yp_listing_category');

    if (!empty($terms) && !is_wp_error($terms)) {
        $term = array_shift($terms);

        $ancestors = get_ancestors($term->term_id, 'yp_listing_category');

        if (!empty($ancestors)) {
            $top_parent_id = end($ancestors);
            $parent_term = get_term($top_parent_id, 'yp_listing_category');

            if ($parent_term && !is_wp_error($parent_term)) {
                $parent_category_name = $parent_term->name;
                $parent_category_slug = $parent_term->slug;
            }
        } else {
            $parent_category_name = $term->name;
            $parent_category_slug = $term->slug;
        }
    }
}

/**
 * Короткий опис оголошення
 */
if ($post_id) {
    $raw_description = get_post_field('post_excerpt', $post_id);

    if (empty($raw_description)) {
        $raw_description = get_post_field('post_content', $post_id);
    }

    $listing_description = wp_trim_words(
            wp_strip_all_tags(strip_shortcodes($raw_description)),
            24,
            '…'
    );
}

/**
 * Дата публікації:
 * Сьогодні, 09:21
 * Вчора, 18:30
 * 24.06.26, 15:41
 */
if ($post_id) {
    $post_datetime = get_post_datetime($post_id, 'date', 'local');

    if ($post_datetime instanceof DateTimeInterface) {
        $now = new DateTimeImmutable('now', wp_timezone());

        $post_date = $post_datetime->format('Y-m-d');
        $today = $now->format('Y-m-d');
        $yesterday = $now->modify('-1 day')->format('Y-m-d');

        if ($post_date === $today) {
            $published_label = sprintf(
                    __('Сьогодні, %s', 'yellow-paper-classifieds'),
                    $post_datetime->format('H:i')
            );
        } elseif ($post_date === $yesterday) {
            $published_label = sprintf(
                    __('Вчора, %s', 'yellow-paper-classifieds'),
                    $post_datetime->format('H:i')
            );
        } else {
            $published_label = $post_datetime->format('d.m.y, H:i');
        }
    }
}

/**
 * Тип користувача / продавця
 */
if ($post_id) {
    $author_id = (int)get_post_field('post_author', $post_id);

    if ($author_id) {
        $account_type = get_user_meta($author_id, '_yp_account_type', true);

        $account_type_labels = array(
                'private-person' => __('Приватна особа', 'yellow-paper-classifieds'),
                'private_person' => __('Приватна особа', 'yellow-paper-classifieds'),
                'individual' => __('Приватна особа', 'yellow-paper-classifieds'),

                'company' => __('Юридична особа', 'yellow-paper-classifieds'),
                'legal-entity' => __('Юридична особа', 'yellow-paper-classifieds'),
                'legal_entity' => __('Юридична особа', 'yellow-paper-classifieds'),

                'entrepreneur' => __('Підприємець', 'yellow-paper-classifieds'),
                'private-entrepreneur' => __('Підприємець', 'yellow-paper-classifieds'),
                'private_entrepreneur' => __('Підприємець', 'yellow-paper-classifieds'),
                'fop' => __('Приватний підприємець', 'yellow-paper-classifieds'),
        );

        if (!empty($account_type_labels[$account_type])) {
            $account_type_label = $account_type_labels[$account_type];
        }
    }
}
?>

<article class="yp-listing-card" style="">
    <?php
    $thumb_url = !empty($card['thumb_url'])
            ? $card['thumb_url']
            : get_stylesheet_directory_uri() . '/images/placeholder-4.jpg';

    $thumb_alt = !empty($card['title'])
            ? $card['title']
            : __('Зображення оголошення', 'yellow-paper-classifieds');

    $has_real_image = !empty($card['thumb_url']);
    ?>

    <div class="yp-listing-card__image">
        <a href="<?php echo esc_url($card['permalink']); ?>">
            <img
                    src="<?php echo esc_url($thumb_url); ?>"
                    alt="<?php echo esc_attr($thumb_alt); ?>"
                    loading="lazy"
            >

            <?php if ($has_real_image && !empty($card['image_count']) && (int) $card['image_count'] > 1) : ?>
                <p class="yp-listing-card__image-photo">
                    <i class="fas fa-camera"></i>
                    <?php
                    echo esc_html(
                            sprintf(
                                    __('%d', 'yellow-paper-classifieds'),
                                    (int) $card['image_count']
                            )
                    );
                    ?>
                </p>
            <?php endif; ?>
        </a>
    </div>

    <div class="yp-listing-card__wrap">
        <?php if (!empty($parent_category_name)) : ?>
            <p class="yp-listing-card__category-label  color-<?= $parent_category_slug; ?>">
                <?php echo esc_html($parent_category_name); ?>
            </p>
        <?php endif; ?>

        <h2 class="yp-listing-card__title" style="font-size:20px;margin:0 0 10px;">
            <a href="<?php echo esc_url($card['permalink']); ?>">
                <?php echo esc_html($card['title']); ?>
            </a>
        </h2>

        <?php if (!empty($card['price'])) : ?>
            <div class="yp-listing-card__price">
                <?php if (!empty($card['special_price']) && in_array($card['price_type'], array('sale', 'clearance'), true)) : ?>
                    <p class="yp-listing-card__price-old">
                        <?php echo esc_html($card['price']); ?> грн.
                    </p>
                    <p style="">
                        <?php echo esc_html($card['special_price']); ?>грн.
                    </p>
                <?php else : ?>
                    <p style="margin:0;font-size:22px;font-weight:700;">
                        <?php echo esc_html($card['price']); ?> грн.
                    </p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($card['category_name'])) : ?>
            <p class="yp-listing-card__category">
                <strong><?php esc_html_e('Категорія:', 'yellow-paper-classifieds'); ?></strong>

                <?php echo esc_html($card['category_name']); ?>
            </p>
        <?php endif; ?>



        <?php if (!empty($card['location_name']) || !empty($published_label)) : ?>
            <p class="yp-listing-card__location-date">
                <?php if (!empty($card['location_name'])) : ?>
                    <span><?php echo esc_html($card['location_name']); ?></span>
                <?php endif; ?>

                <?php if (!empty($card['location_name']) && !empty($published_label)) : ?>
                    <span class="yp-listing-card__dot">•</span>
                <?php endif; ?>

                <?php if (!empty($published_label)) : ?>
                    <span><?php echo esc_html($published_label); ?></span>
                <?php endif; ?>
            </p>
        <?php endif; ?>
        <?php if (!empty($listing_description)) : ?>
            <p class="yp-listing-card__description">
                <?php echo esc_html($listing_description); ?>
            </p>
        <?php endif; ?>
        <div class="yp-listing-card__footer">
            <?php if (!empty($account_type_label)) : ?>
                <div class="yp-listing-card__seller-type">
                    <i class="fas fa-user"></i>
                    <span><?php echo esc_html($account_type_label); ?></span>
                </div>
            <?php endif; ?>

            <a class="yp-listing-card__button" href="<?php echo esc_url($card['permalink']); ?>">
                <?php esc_html_e('Детальніше', 'yellow-paper-classifieds'); ?>
            </a>
        </div>


    </div>
</article>