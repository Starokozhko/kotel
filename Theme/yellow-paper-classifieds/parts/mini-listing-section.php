<?php
/**
 * Mini listing section for the Find Yours page.
 *
 * Expects $mini_section with title, modifier, archive_url and query keys.
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($mini_section) || empty($mini_section['query']) || !($mini_section['query'] instanceof WP_Query)) {
    return;
}

$mini_query = $mini_section['query'];

if (!$mini_query->have_posts()) {
    wp_reset_postdata();
    return;
}

$mini_modifier = !empty($mini_section['modifier']) ? sanitize_html_class($mini_section['modifier']) : 'default';
$mini_title = !empty($mini_section['title']) ? $mini_section['title'] : __('Оголошення', 'yellow-paper-classifieds');
$mini_archive_url = !empty($mini_section['archive_url']) ? $mini_section['archive_url'] : '';
$placeholder = get_stylesheet_directory_uri() . '/images/placeholder-4.jpg';
?>

<section class="yp-mini-listing-card yp-mini-listing-card--<?php echo esc_attr($mini_modifier); ?>">
    <div class="yp-mini-listing-card__header">
        <div class="yp-mini-listing-card__title-wrap">
            <span class="yp-mini-listing-card__icon" aria-hidden="true">
                <?php if ($mini_modifier === 'messages') : ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5.25 4.75H18.75C19.72 4.75 20.5 5.53 20.5 6.5V15.25C20.5 16.22 19.72 17 18.75 17H11.4L7.8 20.15C7.28 20.6 6.5 20.24 6.5 19.55V17H5.25C4.28 17 3.5 16.22 3.5 15.25V6.5C3.5 5.53 4.28 4.75 5.25 4.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="M7.5 9H16.5M7.5 12.25H13.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                <?php else : ?>
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7.75 4.75C6.23 4.75 5 5.98 5 7.5C5 10.65 9.38 14 12 16.35C14.62 14 19 10.65 19 7.5C19 5.98 17.77 4.75 16.25 4.75C14.55 4.75 13.45 5.72 12 7.25C10.55 5.72 9.45 4.75 7.75 4.75Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                        <path d="M8.25 19.25H15.75" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>
                    </svg>
                <?php endif; ?>
            </span>

            <h2 class="yp-mini-listing-card__title"><?php echo esc_html($mini_title); ?></h2>
        </div>

        <?php if ($mini_archive_url !== '') : ?>
            <a class="yp-mini-listing-card__archive-link" href="<?php echo esc_url($mini_archive_url); ?>">
                <?php esc_html_e('Переглянути всі', 'yellow-paper-classifieds'); ?>
                <span aria-hidden="true">→</span>
            </a>
        <?php endif; ?>
    </div>

    <div class="yp-mini-listing-card__list">
        <?php while ($mini_query->have_posts()) : $mini_query->the_post(); ?>
            <?php
            $post_id = get_the_ID();
            $thumb_url = get_the_post_thumbnail_url($post_id, 'thumbnail');

            if (!$thumb_url) {
                $thumb_url = $placeholder;
            }
            ?>
            <a class="yp-mini-listing-card__item" href="<?php the_permalink(); ?>">
                <span class="yp-mini-listing-card__thumb">
                    <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr(get_the_title($post_id)); ?>" loading="lazy" decoding="async">
                </span>

                <span class="yp-mini-listing-card__content">
                    <span class="yp-mini-listing-card__name"><?php echo esc_html(get_the_title($post_id)); ?></span>
                    <span class="yp-mini-listing-card__date"><?php echo esc_html(lita_format_yp_mini_listing_date($post_id)); ?></span>
                </span>
            </a>
        <?php endwhile; ?>
    </div>
</section>

<?php wp_reset_postdata(); ?>

