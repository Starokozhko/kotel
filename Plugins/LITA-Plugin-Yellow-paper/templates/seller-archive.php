<?php
if (!defined('ABSPATH')) {
    exit;
}

$seller = yp_get_current_seller();

if (!$seller instanceof WP_User) {
    return;
}

$loader = new YP_Template_Loader(new YP_Listing_Images(), new YP_User_Profile());
$user_profile = new YP_User_Profile();
$seller_id = (int) $seller->ID;
$store = $user_profile->get_user_store_data($seller_id);
$seller_name = yp_get_seller_display_name($seller_id);
$active_count = yp_get_seller_active_listings_count($seller_id);
$account_type_label = '';
$archive_url = get_post_type_archive_link(YP_Post_Types::POST_TYPE);
$seller_url = yp_get_seller_archive_url($seller_id);
$logo_id = !empty($store['store_logo_id']) ? (int) $store['store_logo_id'] : 0;
$logo = $logo_id ? wp_get_attachment_image($logo_id, 'thumbnail', false, array('class' => 'yp-seller-archive__avatar-image')) : '';
$initial_source = $seller_name !== '' ? $seller_name : __('П', 'yellow-paper-classifieds');
$initial = function_exists('mb_substr') ? mb_substr($initial_source, 0, 1) : substr($initial_source, 0, 1);

if (function_exists('yp_get_user_account_type') && function_exists('yp_get_account_types')) {
    $account_types = yp_get_account_types();
    $account_type = yp_get_user_account_type($seller_id);
    $account_type_label = isset($account_types[$account_type]) ? $account_types[$account_type] : '';
}

$paged = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$listings_query = new WP_Query(array_merge(
    YP_Seller_Archive::get_public_listing_query_args(),
    array(
        'author' => $seller_id,
        'posts_per_page' => function_exists('yp_get_listings_per_page') ? yp_get_listings_per_page() : 40,
        'paged' => $paged,
    )
));

$listing_card_template = $loader->get_template_path('parts/listing-card.php');
$page_title = $seller_name !== ''
    ? sprintf(__('Оголошення продавця %s', 'yellow-paper-classifieds'), $seller_name)
    : __('Оголошення продавця', 'yellow-paper-classifieds');

get_header();
?>

<main class="yp-seller-archive">
    <div class="yp-seller-archive__inner">
        <nav class="yp-seller-archive__breadcrumbs" aria-label="<?php esc_attr_e('Навігація', 'yellow-paper-classifieds'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Головна', 'yellow-paper-classifieds'); ?></a>
            <?php if ($archive_url) : ?>
                <span aria-hidden="true">/</span>
                <a href="<?php echo esc_url($archive_url); ?>"><?php esc_html_e('Оголошення', 'yellow-paper-classifieds'); ?></a>
            <?php endif; ?>
            <span aria-hidden="true">/</span>
            <span><?php echo esc_html($seller_name !== '' ? $seller_name : __('Продавець', 'yellow-paper-classifieds')); ?></span>
        </nav>

        <header class="yp-seller-archive__header">
            <div class="yp-seller-archive__seller-card">
                <div class="yp-seller-archive__avatar" aria-hidden="true">
                    <?php if ($logo) : ?>
                        <?php echo wp_kses_post($logo); ?>
                    <?php else : ?>
                        <span><?php echo esc_html($initial); ?></span>
                    <?php endif; ?>
                </div>

                <div class="yp-seller-archive__seller-info">
                    <h1><?php echo esc_html($page_title); ?></h1>

                    <?php if ($account_type_label !== '') : ?>
                        <p class="yp-seller-archive__type"><?php echo esc_html($account_type_label); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($store['store_address'])) : ?>
                        <p><?php echo esc_html($store['store_address']); ?></p>
                    <?php endif; ?>

                    <p class="yp-seller-archive__count">
                        <?php
                        echo esc_html(
                            sprintf(
                                __('Активних оголошень: %d', 'yellow-paper-classifieds'),
                                $active_count
                            )
                        );
                        ?>
                    </p>
                </div>
            </div>

            <div class="yp-seller-archive__contacts">
                <?php if (!empty($store['store_phone'])) : ?>
                    <?php $phone = $loader->format_phone_for_display($store['store_phone']); ?>
                    <?php $phone_digits = preg_replace('/\D+/', '', $store['store_phone']); ?>
                    <?php if ($phone !== '' && $phone_digits !== '') : ?>
                        <a class="yp-seller-archive__phone" href="tel:+<?php echo esc_attr($phone_digits); ?>"><?php echo esc_html($phone); ?></a>
                    <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($store['store_work_hours'])) : ?>
                    <div class="yp-seller-archive__contact-row">
                        <span><?php esc_html_e('Графік', 'yellow-paper-classifieds'); ?></span>
                        <strong><?php echo nl2br(esc_html($store['store_work_hours'])); ?></strong>
                    </div>
                <?php endif; ?>

                <?php if (!empty($store['store_website'])) : ?>
                    <div class="yp-seller-archive__contact-row">
                        <span><?php esc_html_e('Сайт', 'yellow-paper-classifieds'); ?></span>
                        <a href="<?php echo esc_url($store['store_website']); ?>" target="_blank" rel="nofollow noopener noreferrer">
                            <?php echo esc_html(preg_replace('#^https?://#', '', $store['store_website'])); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <section class="yp-seller-archive__listings" aria-labelledby="yp-seller-archive-listings-title">
            <h2 id="yp-seller-archive-listings-title"><?php esc_html_e('Усі оголошення продавця', 'yellow-paper-classifieds'); ?></h2>

            <?php if ($listings_query->have_posts() && $listing_card_template) : ?>
                <div class="yp-listing-grid">
                    <?php while ($listings_query->have_posts()) : $listings_query->the_post(); ?>
                        <?php
                        $card = $loader->get_listing_card_data(get_the_ID());
                        include $listing_card_template;
                        ?>
                    <?php endwhile; ?>
                </div>

                <div class="yp-seller-archive__pagination">
                    <?php
                    echo wp_kses_post(
                        paginate_links(array(
                            'total' => $listings_query->max_num_pages,
                            'current' => $paged,
                            'prev_text' => __('← Назад', 'yellow-paper-classifieds'),
                            'next_text' => __('Далі →', 'yellow-paper-classifieds'),
                        ))
                    );
                    ?>
                </div>
            <?php else : ?>
                <p class="yp-seller-archive__empty"><?php esc_html_e('У цього продавця поки немає активних оголошень.', 'yellow-paper-classifieds'); ?></p>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        </section>
    </div>
</main>

<?php
get_footer();
