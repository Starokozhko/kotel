<?php
if (!defined('ABSPATH')) {
    exit;
}

$post_id = !empty($card['post_id']) ? (int) $card['post_id'] : 0;

$thumb_url = !empty($card['thumb_url'])
        ? $card['thumb_url']
        : get_stylesheet_directory_uri() . '/images/placeholder-4.jpg';

$thumb_alt = !empty($card['title'])
        ? $card['title']
        : __('Зображення оголошення', 'yellow-paper-classifieds');

$published_label = '';

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
?>

<article class="yp-listing-card-compact">
    <a class="yp-listing-card-compact__image" href="<?php echo esc_url($card['permalink']); ?>">
        <img
                src="<?php echo esc_url($thumb_url); ?>"
                alt="<?php echo esc_attr($thumb_alt); ?>"
                loading="lazy"
        >

        <?php if (!empty($card['thumb_url']) && !empty($card['image_count']) && (int) $card['image_count'] > 1) : ?>
            <span class="yp-listing-card-compact__photo-count">
                <i class="fas fa-camera" aria-hidden="true"></i>
                <span><?php echo esc_html((int) $card['image_count']); ?></span>
            </span>
        <?php endif; ?>
    </a>

    <div class="yp-listing-card-compact__body">
        <h3 class="yp-listing-card-compact__title">
            <a href="<?php echo esc_url($card['permalink']); ?>">
                <?php echo esc_html($card['title']); ?>
            </a>
        </h3>

        <?php if (!empty($card['price'])) : ?>
            <div class="yp-listing-card-compact__price">
                <?php if (!empty($card['special_price']) && in_array($card['price_type'], array('sale', 'clearance'), true)) : ?>
                    <span class="yp-listing-card-compact__price-old">
                        <?php echo esc_html($card['price']); ?> грн.
                    </span>
                    <span><?php echo esc_html($card['special_price']); ?> грн.</span>
                <?php else : ?>
                    <span><?php echo esc_html($card['price']); ?> грн.</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($published_label)) : ?>
            <p class="yp-listing-card-compact__date">
                <?php echo esc_html($published_label); ?>
            </p>
        <?php endif; ?>
    </div>
</article>