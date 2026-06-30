<?php
/**
 * The Template for displaying all single products
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

get_header('shop'); ?>
    <div class="single-page__wrapper">
        <div class="container  full-width-product">
            <?php
            /**
             * woocommerce_before_main_content hook.
             *
             * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
             * @hooked woocommerce_breadcrumb - 20
             */
            do_action('woocommerce_before_main_content');
            ?>

            <?php while (have_posts()) : ?>
                <?php the_post(); ?>

                <?php wc_get_template_part('content', 'single-product'); ?>

            <?php endwhile; // end of the loop. ?>
<!--    <div class="empty"></div>-->
            <?php
            /**
             * woocommerce_after_main_content hook.
             *
             * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
             */
            do_action('woocommerce_after_main_content');
            ?>

            <?php
            /**
             * woocommerce_sidebar hook.
             *
             * @hooked woocommerce_get_sidebar - 10
             */
//            do_action('woocommerce_sidebar');
?>
            <?php
            // Якщо сайдбар вимкнено — вставляємо порожню секцію для збереження структури
            if (!is_active_sidebar('sidebar-1')) {
                echo '<div class="no-sidebar-placeholder" style="display:none;"></div>';
            }
            ?>

<?php //global $post;
//$product_cats = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'ids']);
//
//$args = [
//    'post_type' => 'product',
//    'posts_per_page' => 4,
//    'post__not_in' => [$post->ID],
//    'tax_query' => [
//        [
//            'taxonomy' => 'product_cat',
//            'field' => 'id',
//            'terms' => $product_cats,
//        ],
//    ],
//];
//
//$related = new WP_Query($args);
//
//if ($related->have_posts()): ?>
<!--            <section class="related-products">-->
<!--                <h2>Схожі товари</h2>-->
<!--                <ul class="products columns-4">-->
<!--                    --><?php //while ($related->have_posts()): $related->the_post(); ?>
<!--                        --><?php //wc_get_template_part('content', 'product'); ?>
<!--                    --><?php //endwhile; ?>
<!--                </ul>-->
<!--            </section>-->
<!--            --><?php //wp_reset_postdata(); ?>
<!--            --><?php //endif; ?>
<!---->
<!---->

        </div>
    </div>
<?php
get_footer('shop');

/* Omit closing PHP tag at the end of PHP files to avoid "headers already sent" issues. */
