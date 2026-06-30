<?php
$title = get_sub_field('title');
$counter = get_sub_field('counter');
$bgColor = get_sub_field('bg_color');

?>
<section class="product-list bg-color__<?= $bgColor; ?>">
    <div class="container">
        <?php if ($title): ?>
            <div class="row text-center">
                <h2 class="product-list__title"><?= $title; ?></h2>
            </div>
        <?php endif; ?>


        <?php
        // Можна передати через URL, або змінити на свій механізм
        $args = ['post_type' => 'product',
            'posts_per_page' => $counter,
            'orderby' => 'date',
            'order' => 'DESC',
            ];

        $loop = new WP_Query($args);

        if ($loop->have_posts()) : ?>
            <ul class="product-list__list">
                <?php while ($loop->have_posts()) : $loop->the_post();
                    global $product; ?>
                    <li class="product-list__item">
                                                <a href="<?php the_permalink(); ?>">
                        <?php $image_id = $product->get_image_id(); // Отримує ID зображення продукту
                        if ($image_id) {
                            echo wp_get_attachment_image($image_id, 'product-img', false, ['class' => 'woocommerce-product-image']);
                        } ?>
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