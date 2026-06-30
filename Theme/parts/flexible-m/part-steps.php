<section class="steps">
    <div class="container">
        <div class="steps__wrap col">
            <?php $fields = get_row(true); ?>
            <?php if ($fields['sub_title']): ?>
                <p class="steps__sub-title"><b><?= $fields['sub_title']; ?></b></p>
            <?php endif; ?>
            <?php if ($fields['title']): ?>
                <h2 class="steps__title"><?= $fields['title']; ?></h2>
            <?php endif; ?>

            <?php if ($fields['description']): ?>
                <p class="steps__description"><?= $fields['description'] ?></p>
            <?php endif; ?>

            <?php if (have_rows('categories_list')): ?>
                <div class="steps__list">

                    <?php while (have_rows('categories_list')): the_row(); ?>

                        <?php
                        $name        = get_sub_field('name');
                        $link        = get_sub_field('link');
                        $image       = get_sub_field('image');
                        $title       = get_sub_field('title');
                        $description = get_sub_field('description');
                        $link_url    = '';
                        $link_target = '_self';

                        if (is_array($link) && !empty($link['url'])) {
                            $link_url    = $link['url'];
                            $link_target = !empty($link['target']) ? $link['target'] : '_self';
                        } elseif (is_string($link)) {
                            $link_url = $link;
                        }

                        ?>

                        <div class="steps__item">
                            <div class="steps__item-info">

                                <?php if ($name): ?>

                                    <?php if ($link_url): ?>
                                        <a href="<?= esc_url($link_url); ?>" class="steps__cat-title">
                                            <?= esc_html($name); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="steps__cat-title steps__cat-title--disabled">
                                <?= esc_html($name); ?>
                            </span>
                                    <?php endif; ?>

                                <?php endif; ?>

                                <?php if ($title): ?>
                                    <h3><?= esc_html($title); ?></h3>
                                <?php endif; ?>

                                <?php if ($description): ?>
                                    <p><?= esc_html($description); ?></p>
                                <?php endif; ?>

                                <?php if ($link_url): ?>
                                    <a href="<?= esc_url($link_url); ?>" target="<?= esc_attr($link_target); ?>" class="link-title">
                                        <?= $link['title']; ?>
                                        <span class="link-title-icon" aria-hidden="true">›</span>
                                    </a>
                                <?php endif; ?>

                            </div>

                            <?php if ($image): ?>
                                <img
                                        src="<?= esc_url($image['sizes']['product-img']); ?>"
                                        alt="<?= esc_attr($image['alt'] ?: $name); ?>"
                                        loading="lazy"
                                >
                            <?php endif; ?>

                        </div>

                    <?php endwhile; ?>

                </div>
            <?php endif; ?>
        </div>
    </div>

</section>