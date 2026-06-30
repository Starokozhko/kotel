<?php
$post_id = get_the_ID();
$title = get_sub_field('title', $post_id);
$counter = get_sub_field('counter', $post_id);
$category = get_sub_field('prod_category', $post_id);
$bgColor = get_sub_field('bg_color', $post_id);

?>
<section class="product-list bg-color__<?= $bgColor; ?>">
    <div class="container">
        <?php if ($title): ?>
        <div class="row text-center">
            <h2 class="product-list__title"><?= $title; ?></h2>
        </div>
        <?php endif; ?>


        <?php


//        // Можна передати через URL, або змінити на свій механізм
        $args = [
            'post_type'      => 'product',
            'posts_per_page' => $counter,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'tax_query'      => [
                [
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $category->slug,
                ],
            ],
        ];



        $loop = new WP_Query($args);

        if ($loop->have_posts()) : ?>
        <ul class="product-list__list">
            <?php while ($loop->have_posts()) : $loop->the_post();
                global $product; ?>
                <li class="product-list__item">
                                            <a href="<?php the_permalink(); ?>">
                    <?php echo $product->get_image('full_hd'); ?>
                    <h2><?php the_title(); ?></h2>
                    <span class="price"><?php echo $product->get_price_html(); ?></span>
                                            </a>
                    <div class="product-excerpt">
                        <?php the_excerpt(); ?>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
        <?php else : ?>
        <p>Немає товарів.</p>
        <?php endif;

        wp_reset_postdata();
        ?>

    </div>
</section>