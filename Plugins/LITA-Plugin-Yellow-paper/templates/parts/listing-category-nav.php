<?php
if (!defined('ABSPATH')) {
    exit;
}

$parent_term_id = 0;

if (is_tax(YP_Post_Types::TAXONOMY)) {
    $queried_term = get_queried_object();

    if ($queried_term instanceof WP_Term && !is_wp_error($queried_term)) {
        $parent_term_id = (int) $queried_term->term_id;
    }
}

$category_nav_terms = function_exists('yp_get_visible_child_listing_categories')
    ? yp_get_visible_child_listing_categories($parent_term_id)
    : array();

if (empty($category_nav_terms)) {
    return;
}
?>

<section class="yp-listing-category-nav" aria-labelledby="yp-listing-category-nav-title">
    <div class="yp-listing-category-nav__header">
        <h2 id="yp-listing-category-nav-title" class="yp-listing-category-nav__title">
            <?php esc_html_e('Категорії', 'yellow-paper-classifieds'); ?>
        </h2>
    </div>

    <ul class="yp-listing-category-nav__list">
        <?php foreach ($category_nav_terms as $category_nav_term) : ?>
            <?php
            $term_link = get_term_link($category_nav_term);

            if (is_wp_error($term_link)) {
                continue;
            }

            $published_count = function_exists('yp_get_term_published_listings_count')
                ? yp_get_term_published_listings_count($category_nav_term->term_id)
                : 0;
            ?>
            <li class="yp-listing-category-nav__item">
                <a class="yp-listing-category-nav__link" href="<?php echo esc_url($term_link); ?>">
                    <span class="yp-listing-category-nav__name"><?php echo esc_html($category_nav_term->name); ?></span>
                    <?php if ($published_count > 0) : ?>
                        <span class="yp-listing-category-nav__count"><?php echo esc_html($published_count); ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</section>
