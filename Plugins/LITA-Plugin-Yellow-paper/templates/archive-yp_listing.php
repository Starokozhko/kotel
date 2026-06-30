<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wp_query;
$loader = new YP_Template_Loader(new YP_Listing_Images(), new YP_User_Profile());

get_header();
?>

    <main class="yp-listings-archive" style="max-width:1200px;margin:0 auto;padding:32px 16px;">
        <header style="margin-bottom:24px;">
            <div class="yp-listings-archive__wrapper">
                <div class="yp-listings-archive__side">
                    <h1 style="margin:0;"><?php post_type_archive_title(); ?></h1>
                    <p class="description">Товари, послуги, нерухомість та повідомлення жителів Котельви</p>
                    <?php if (!empty($wp_query->found_posts)) : ?>
                        <p style="margin:8px 0 0;">
                            <?php
                            echo esc_html(
                                    sprintf(
                                            __('Знайдено оголошень: %d', 'yellow-paper-classifieds'),
                                            (int)$wp_query->found_posts
                                    )
                            );
                            ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="yp-listings-archive__list">
                    <div class="yp-listings-archive__item">
                        <div class="yp-listings-archive__item-icon blue">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-labelledby="title desc">
                                <title id="title">Усього оголошень</title>
                                <desc id="desc">Іконка зі списком оголошень і позначкою загальної кількості</desc>
                                <path d="M5.25 4.75H15.75C16.7165 4.75 17.5 5.5335 17.5 6.5V17.5C17.5 18.4665 16.7165 19.25 15.75 19.25H5.25C4.2835 19.25 3.5 18.4665 3.5 17.5V6.5C3.5 5.5335 4.2835 4.75 5.25 4.75Z" stroke="#0f4b90" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M7 8H14" stroke="#0f4b90" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M7 11.5H12.5" stroke="#0f4b90" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M7 15H10.75" stroke="#0f4b90" stroke-width="1.5" stroke-linecap="round"/>
                                <path d="M7.25 2.75H17.75C19.2688 2.75 20.5 3.98122 20.5 5.5V15.5" stroke="#0f4b90" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" opacity="0.45"/>
                                <circle cx="16.75" cy="16.75" r="4" fill="#0f4b90"/>
                                <path d="M15.35 16.75H18.15" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
                                <path d="M16.75 15.35V18.15" stroke="white" stroke-width="1.3" stroke-linecap="round"/>
                            </svg>

                        </div>

                        <?php
                        $listing_statuses = array('publish', 'pending', 'draft');

                        /**
                         * Загальна кількість оголошень:
                         * опубліковані + неопубліковані
                         */
                        $total_listings_query = new WP_Query(array(
                                'post_type'      => 'yp_listing',
                                'post_status'    => $listing_statuses,
                                'posts_per_page' => 1,
                                'fields'         => 'ids',
                        ));

                        $total_listings_count = (int) $total_listings_query->found_posts;

                        wp_reset_postdata();

                        /**
                         * Кількість оголошень, які зʼявились за останні 7 днів:
                         * опубліковані + неопубліковані
                         */
                        $week_listings_query = new WP_Query(array(
                                'post_type'      => 'yp_listing',
                                'post_status'    => $listing_statuses,
                                'posts_per_page' => 1,
                                'fields'         => 'ids',
                                'date_query'     => array(
                                        array(
                                                'after'     => '7 days ago',
                                                'inclusive' => true,
                                        ),
                                ),
                        ));

                        $listings_last_week_count = (int) $week_listings_query->found_posts;

                        wp_reset_postdata();
                        ?>
                        <div class="yp-listings-archive__item-content">
                            <p class="label">Усього оголошень</p>

                            <p class="number">
                                <?php echo esc_html($total_listings_count); ?>
                            </p>

                            <p class="info">
                                +<?php echo esc_html($listings_last_week_count); ?> за тиждень
                            </p>
                        </div>
                    </div>
                    <div class="yp-listings-archive__item">
                        <div class="yp-listings-archive__item-icon green">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M7 2.75V5.25" stroke="#0A966AFF" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M17 2.75V5.25" stroke="#0A966AFF" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M4.25 9H19.75" stroke="#0A966AFF" stroke-width="1.8" stroke-linecap="round"/>
                                <path d="M5.25 6.25C5.25 5.14543 6.14543 4.25 7.25 4.25H16.75C17.8546 4.25 18.75 5.14543 18.75 6.25V18.75C18.75 19.8546 17.8546 20.75 16.75 20.75H7.25C6.14543 20.75 5.25 19.8546 5.25 18.75V6.25Z" stroke="#0A966AFF" stroke-width="1.8"/>
                                <path d="M8 15.1L10.35 17.45L16 11.8" stroke="#0A966AFF" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                <circle cx="17.75" cy="6.25" r="2.75" fill="#0A966AFF"/>
                                <path d="M16.7 6.25L17.45 7L18.85 5.6" stroke="white" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <?php
                        $listing_counts = wp_count_posts('yp_listing');
                        $published_listings_count = isset($listing_counts->publish) ? (int) $listing_counts->publish : 0;


                        /**
                         * Загальна кількість опублікованих оголошень
                         */
                        $listing_counts = wp_count_posts('yp_listing');

                        $total_listings_count = !empty($listing_counts->publish)
                                ? (int)$listing_counts->publish
                                : 0;

                        /**
                         * Кількість оголошень за останні 7 днів
                         */
                        $listings_week_query = new WP_Query(array(
                                'post_type' => 'yp_listing',
                                'post_status' => 'publish',
                                'posts_per_page' => 1,
                                'fields' => 'ids',
                                'date_query' => array(
                                        array(
                                                'after' => '7 days ago',
                                                'inclusive' => true,
                                        ),
                                ),
                        ));

                        $listings_last_week_count = (int)$listings_week_query->found_posts;

                        wp_reset_postdata();

                        ?>

                        <div class="yp-listings-archive__item-content">
                            <p class="label">Активних сьогодні</p>
                            <p class="number"><?php echo esc_html($published_listings_count); ?></p>
                            <p class="info">
                                +<?php echo esc_html($listings_last_week_count); ?> за тиждень
                            </p>
                        </div></div>
                    <div class="yp-listings-archive__item">
                        <div class="yp-listings-archive__item-icon orange">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-labelledby="title desc">
                                <title id="title">Нових за 24 години</title>
                                <desc id="desc">Іконка оголошень, доданих за останні 24 години</desc>

                                <path d="M5.75 3.75H13.1C13.58 3.75 14.04 3.94 14.38 4.28L17.72 7.62C18.06 7.96 18.25 8.42 18.25 8.9V10.15"
                                      stroke="#d68636" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M5.75 3.75C4.78 3.75 4 4.53 4 5.5V18.5C4 19.47 4.78 20.25 5.75 20.25H10.35"
                                      stroke="#d68636" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M13.75 4.25V7.25C13.75 8.08 14.42 8.75 15.25 8.75H18.25"
                                      stroke="#d68636" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>

                                <path d="M7.25 11H11.25" stroke="#d68636" stroke-width="1.45" stroke-linecap="round"/>
                                <path d="M7.25 14H9.75" stroke="#d68636" stroke-width="1.45" stroke-linecap="round"/>

                                <circle cx="16.25" cy="16.25" r="5.25" fill="white" stroke="#d68636" stroke-width="1.6"/>
                                <path d="M16.25 13.45V16.25L18.05 17.35" stroke="#d68636" stroke-width="1.35" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M13.7 12.15C14.42 11.55 15.33 11.2 16.25 11.2C18.23 11.2 19.93 12.62 20.3 14.5"
                                      stroke="#d68636" stroke-width="1.25" stroke-linecap="round"/>
                                <path d="M20.15 12.75L20.42 14.55L18.68 14.25"
                                      stroke="#d68636" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>

                                <circle cx="7" cy="6.75" r="1.15" fill="#d68636"/>
                            </svg>
                        </div>
                        <?php
                        $listing_statuses = array('publish', 'pending', 'draft');

                        /**
                         * Нові оголошення за останні 24 години:
                         * опубліковані + неопубліковані
                         */
                        $new_24h_query = new WP_Query(array(
                                'post_type'      => 'yp_listing',
                                'post_status'    => $listing_statuses,
                                'posts_per_page' => 1,
                                'fields'         => 'ids',
                                'date_query'     => array(
                                        array(
                                                'after'     => '24 hours ago',
                                                'inclusive' => true,
                                        ),
                                ),
                        ));

                        $new_24h_count = (int) $new_24h_query->found_posts;

                        wp_reset_postdata();
                        ?>
                        <div class="yp-listings-archive__item-content">
                            <p class="label">Нових за 24 години</p>

                            <p class="number">
                                <?php echo esc_html($new_24h_count); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <?php if (have_posts()) : ?>
            <div class="yp-listing-grid"
                 style="">
                <?php while (have_posts()) : the_post(); ?>
                    <?php
                    $card = $loader->get_listing_card_data(get_the_ID());
                    include YP_CLASSIFIEDS_PATH . 'templates/parts/listing-card.php';
                    ?>
                <?php endwhile; ?>
            </div>

            <div style="margin-top:32px;">
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

<?php get_footer(); ?>