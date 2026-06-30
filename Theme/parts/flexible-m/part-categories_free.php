<section class="categories-free">
    <div class="container">
        <div class="categories-free__wrap col">
    <?php $fields = get_row(true); ?>
    <?php if ($fields['sub_title']): ?>
        <p class="categories-free__sub-title"><b><?= $fields['sub_title']; ?></b></p>
    <?php endif; ?>
    <?php if ($fields['title']): ?>
        <h2 class="categories-free__title"><?= $fields['title']; ?></h2>
    <?php endif; ?>

    <?php if ($fields['description']): ?>
        <p class="categories-free__description"><?= $fields['description'] ?></p>
    <?php endif; ?>

    <?php if (have_rows('categories_list')): ?>
        <div class="categories-free__list">
            <?php while (have_rows('categories_list')): the_row(); ?>
                <?php $cat_id = get_sub_field('category');
                // Якщо ACF повертає масив ID, беремо перший вибраний термін
                if (is_array($cat_id)) {
                    $cat_id = $cat_id[0] ?? 0;
                }

                // Якщо раптом ACF повертає обʼєкт терміна
                if (is_object($cat_id)) {
                    $cat_id = $cat_id->term_id;
                }

                $category = $cat_id ? get_term((int) $cat_id, 'yp_listing_category') : null;
                if (!$category || is_wp_error($category)) {
                    continue;
                }

                $category_link = get_term_link($category, 'yp_listing_category');
                $has_posts = kotelva_category_has_listings_with_children($category->term_id, 'yp_listing_category');

                ?>

            <div class="categories-free__item">
                <div class="categories-free__item-info">
                    <?php if ($category && !is_wp_error($category)): ?>

                        <?php if ($has_posts && !is_wp_error($category_link)): ?>

                            <a href="<?= esc_url($category_link); ?>" class="categories-free__cat-title">
                                <?= esc_html($category->name); ?>
                            </a>

                        <?php else: ?>

                            <span class="categories-free__cat-title categories-free__cat-title--disabled">
            <?= esc_html($category->name); ?>
        </span>

                        <?php endif; ?>

                    <?php endif; ?>


                <?php if ($title = get_sub_field('title')): ?>
                    <h3><?= $title; ?></h3>
                <?php endif; ?>
                <?php if ($description = get_sub_field('description')): ?>
                    <p><?= $description; ?></p>
                <?php endif; ?>

                <?php if ($has_posts && $category && !is_wp_error($category) && !is_wp_error($category_link)): ?>
                    <a href="<?= esc_url($category_link); ?>" class="link-title">
                        Переглянути
                        <span class="link-title-icon" aria-hidden="true">›</span>
                    </a>
                <?php endif; ?>
                </div>

                <?php if ($image = get_sub_field('image')): ?>
                    <img src="<?= $image['sizes']['product-img']; ?>"
                         alt="<?= esc_attr($image['alt'] ?: $category->name ?? ''); ?>"
                    >
                <?php endif; ?>

            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
        </div>
    </div>

</section>