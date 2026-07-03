<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wp_query;

$loader = new YP_Template_Loader(new YP_Listing_Images(), new YP_User_Profile());
$archive_title = '';
$archive_breadcrumb_terms = array();
$archive_url = get_post_type_archive_link(YP_Post_Types::POST_TYPE);
$archive_description = __('Товари, послуги, нерухомість та повідомлення жителів Котельви', 'yellow-paper-classifieds');

if (is_tax(YP_Post_Types::TAXONOMY)) {
    $term = get_queried_object();

    if ($term instanceof WP_Term && !is_wp_error($term)) {
        $archive_title = $term->name;

        if (!empty($term->description)) {
            $archive_description = $term->description;
        }

        $ancestor_ids = array_reverse(get_ancestors($term->term_id, YP_Post_Types::TAXONOMY));

        foreach ($ancestor_ids as $ancestor_id) {
            $ancestor = get_term($ancestor_id, YP_Post_Types::TAXONOMY);

            if ($ancestor instanceof WP_Term && !is_wp_error($ancestor)) {
                $archive_breadcrumb_terms[] = $ancestor;
            }
        }

        $archive_breadcrumb_terms[] = $term;
    }
} elseif (is_post_type_archive(YP_Post_Types::POST_TYPE)) {
    $archive_title = post_type_archive_title('', false);
}

if ($archive_title === '') {
    $archive_title = __('Оголошення', 'yellow-paper-classifieds');
}

get_header();
?>

<main class="yp-listings-archive">
    <?php if (is_tax(YP_Post_Types::TAXONOMY) && !empty($archive_breadcrumb_terms)) : ?>
        <nav class="yp-listings-breadcrumbs" aria-label="<?php esc_attr_e('Навігація', 'yellow-paper-classifieds'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Головна', 'yellow-paper-classifieds'); ?></a>

            <?php if ($archive_url) : ?>
                <span aria-hidden="true">/</span>
                <a href="<?php echo esc_url($archive_url); ?>"><?php esc_html_e('Оголошення', 'yellow-paper-classifieds'); ?></a>
            <?php endif; ?>

            <?php foreach ($archive_breadcrumb_terms as $breadcrumb_index => $breadcrumb_term) : ?>
                <span aria-hidden="true">/</span>
                <?php if ($breadcrumb_index === count($archive_breadcrumb_terms) - 1) : ?>
                    <span><?php echo esc_html($breadcrumb_term->name); ?></span>
                <?php else : ?>
                    <a href="<?php echo esc_url(get_term_link($breadcrumb_term)); ?>"><?php echo esc_html($breadcrumb_term->name); ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>

    <header class="yp-listings-archive__header">
        <div class="yp-listings-archive__wrapper">
            <div class="yp-listings-archive__side">
                <h1><?php echo esc_html($archive_title); ?></h1>
                <p class="description"><?php echo wp_kses_post($archive_description); ?></p>

                <?php if (!empty($wp_query->found_posts)) : ?>
                    <p class="yp-listings-archive__found">
                        <?php
                        echo esc_html(
                            sprintf(
                                __('Знайдено оголошень: %d', 'yellow-paper-classifieds'),
                                (int) $wp_query->found_posts
                            )
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="yp-listings-archive__list">
                <div class="yp-listings-archive__item">
                    <div class="yp-listings-archive__item-icon blue" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.25 4.75H15.75C16.7165 4.75 17.5 5.5335 17.5 6.5V17.5C17.5 18.4665 16.7165 19.25 15.75 19.25H5.25C4.2835 19.25 3.5 18.4665 3.5 17.5V6.5C3.5 5.5335 4.2835 4.75 5.25 4.75Z" stroke="#0f4b90" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M7 8H14" stroke="#0f4b90" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M7 11.5H12.5" stroke="#0f4b90" stroke-width="1.5" stroke-linecap="round"/>
                            <path d="M7 15H10.75" stroke="#0f4b90" stroke-width="1.5" stroke-linecap="round"/>
                            <circle cx="16.75" cy="16.75" r="4" fill="#0f4b90"/>
                            <path d="M15.35 16.75H18.15" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
                            <path d="M16.75 15.35V18.15" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
                        </svg>
                    </div>

                    <?php
                    $listing_statuses = array('publish', 'pending', 'draft');
                    $total_listings_query = new WP_Query(array(
                        'post_type' => YP_Post_Types::POST_TYPE,
                        'post_status' => $listing_statuses,
                        'posts_per_page' => 1,
                        'fields' => 'ids',
                    ));
                    $total_listings_count = (int) $total_listings_query->found_posts;
                    wp_reset_postdata();

                    $week_listings_query = new WP_Query(array(
                        'post_type' => YP_Post_Types::POST_TYPE,
                        'post_status' => $listing_statuses,
                        'posts_per_page' => 1,
                        'fields' => 'ids',
                        'date_query' => array(
                            array(
                                'after' => '7 days ago',
                                'inclusive' => true,
                            ),
                        ),
                    ));
                    $listings_last_week_count = (int) $week_listings_query->found_posts;
                    wp_reset_postdata();
                    ?>

                    <div class="yp-listings-archive__item-content">
                        <p class="label"><?php esc_html_e('Усього оголошень', 'yellow-paper-classifieds'); ?></p>
                        <p class="number"><?php echo esc_html($total_listings_count); ?></p>
                        <p class="info">+<?php echo esc_html($listings_last_week_count); ?> <?php esc_html_e('за тиждень', 'yellow-paper-classifieds'); ?></p>
                    </div>
                </div>

                <div class="yp-listings-archive__item">
                    <div class="yp-listings-archive__item-icon green" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M7 2.75V5.25" stroke="#0A966AFF" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M17 2.75V5.25" stroke="#0A966AFF" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M4.25 9H19.75" stroke="#0A966AFF" stroke-width="1.8" stroke-linecap="round"/>
                            <path d="M5.25 6.25C5.25 5.14543 6.14543 4.25 7.25 4.25H16.75C17.8546 4.25 18.75 5.14543 18.75 6.25V18.75C18.75 19.8546 17.8546 20.75 16.75 20.75H7.25C6.14543 20.75 5.25 19.8546 5.25 18.75V6.25Z" stroke="#0A966AFF" stroke-width="1.8"/>
                            <path d="M8 15.1L10.35 17.45L16 11.8" stroke="#0A966AFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <?php
                    $listing_counts = wp_count_posts(YP_Post_Types::POST_TYPE);
                    $published_listings_count = isset($listing_counts->publish) ? (int) $listing_counts->publish : 0;
                    ?>

                    <div class="yp-listings-archive__item-content">
                        <p class="label"><?php esc_html_e('Активних сьогодні', 'yellow-paper-classifieds'); ?></p>
                        <p class="number"><?php echo esc_html($published_listings_count); ?></p>
                        <p class="info">+<?php echo esc_html($listings_last_week_count); ?> <?php esc_html_e('за тиждень', 'yellow-paper-classifieds'); ?></p>
                    </div>
                </div>

                <div class="yp-listings-archive__item">
                    <div class="yp-listings-archive__item-icon orange" aria-hidden="true">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.75 3.75H13.1C13.58 3.75 14.04 3.94 14.38 4.28L17.72 7.62C18.06 7.96 18.25 8.42 18.25 8.9V10.15" stroke="#d68636" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M5.75 3.75C4.78 3.75 4 4.53 4 5.5V18.5C4 19.47 4.78 20.25 5.75 20.25H10.35" stroke="#d68636" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="16.25" cy="16.25" r="5.25" fill="white" stroke="#d68636" stroke-width="1.6"/>
                            <path d="M16.25 13.45V16.25L18.05 17.35" stroke="#d68636" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>

                    <?php
                    $new_24h_query = new WP_Query(array(
                        'post_type' => YP_Post_Types::POST_TYPE,
                        'post_status' => $listing_statuses,
                        'posts_per_page' => 1,
                        'fields' => 'ids',
                        'date_query' => array(
                            array(
                                'after' => '24 hours ago',
                                'inclusive' => true,
                            ),
                        ),
                    ));
                    $new_24h_count = (int) $new_24h_query->found_posts;
                    wp_reset_postdata();
                    ?>

                    <div class="yp-listings-archive__item-content">
                        <p class="label"><?php esc_html_e('Нових за 24 години', 'yellow-paper-classifieds'); ?></p>
                        <p class="number"><?php echo esc_html($new_24h_count); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    
    <?php
    $category_nav_template = $loader->get_template_path('parts/listing-category-nav.php');

    if ($category_nav_template) {
        include $category_nav_template;
    }
    ?>
    <?php if (have_posts()) : ?>
        <div class="yp-listing-grid">
            <?php while (have_posts()) : the_post(); ?>
                <?php
                $card = $loader->get_listing_card_data(get_the_ID());
                $listing_card_template = $loader->get_template_path('parts/listing-card.php');

                if ($listing_card_template) {
                    include $listing_card_template;
                }
                ?>
            <?php endwhile; ?>
        </div>

        <div class="yp-listings-archive__pagination">
            <?php
            the_posts_pagination(array(
                'mid_size' => 2,
                'prev_text' => __('← Назад', 'yellow-paper-classifieds'),
                'next_text' => __('Далі →', 'yellow-paper-classifieds'),
            ));
            ?>
        </div>
    <?php else : ?>
        <p><?php esc_html_e('Оголошень поки немає.', 'yellow-paper-classifieds'); ?></p>
    <?php endif; ?>
</main>

<?php
get_footer();
