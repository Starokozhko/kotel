<div class="container">
    <div class=" col">
        <section class="latest_ads">
            <?php $fields = get_row(true); ?>
            <?php if ($fields['sub_title']): ?>
                <p class="latest_ads__sub-title"><b><?= $fields['sub_title']; ?></b></p>
            <?php endif; ?>
            <?php if ($fields['title']): ?>
                <h2 class="latest_ads__title"><?= $fields['title']; ?></h2>
            <?php endif; ?>

            <?php if ($fields['description']): ?>
                <p class="latest_ads__description"><?= $fields['description'] ?></p>
            <?php endif; ?>

            <?php
            $latest_listings = kotelva_get_latest_listings_cached(3);
            ?>

            <?php if (!empty($latest_listings)): ?>
                <div class="latest_ads__list">

                    <?php foreach ($latest_listings as $listing): ?>

                        <div class="latest_ads__item">

                            <?php if (!empty($listing['image_id'])): ?>
                                <?= wp_get_attachment_image($listing['image_id'], 'product-img', false, array(
                                        'class'   => 'latest_ads__image',
                                        'loading' => 'lazy',
                                        'alt'     => esc_attr($listing['title']),
                                )); ?>
                            <?php else: ?>
                                <img
                                        src="<?= esc_url(get_stylesheet_directory_uri() . '/images/placeholder-3.jpg'); ?>"
                                        alt="<?= esc_attr($listing['title']); ?>"
                                        class="latest_ads__image"
                                        loading="lazy"
                                >
                            <?php endif; ?>

                            <div class="latest_ads__item-info">

                                <div class="latest_ads__meta">
                                    <?php if (!empty($listing['category'])): ?>

                                        <?php if (!empty($listing['category_link'])): ?>
                                            <a href="<?= esc_url($listing['category_link']); ?>" class="latest_ads__cat-title">
                                                <?= esc_html($listing['category']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="latest_ads__cat-title">
                                                <?= esc_html($listing['category']); ?>
                                            </span>
                                        <?php endif; ?>

                                    <?php endif; ?>



                                    <?php if (!empty($listing['date'])): ?>
                                        <span class="latest_ads__date">
                                            <?= esc_html($listing['date']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <?php if (!empty($listing['title'])): ?>
                                    <h3 class="latest_ads__title">
                                        <?= esc_html($listing['title']); ?>
                                    </h3>
                                <?php endif; ?>

                                <?php if (!empty($listing['excerpt'])): ?>
                                    <p class="latest_ads__description">
                                        <?= esc_html($listing['excerpt']); ?>
                                    </p>
                                <?php endif; ?>

                                <a href="<?= esc_url($listing['link']); ?>" class="link-title">
                                    Детальніше
                                    <span class="link-title-icon" aria-hidden="true">›</span>
                                </a>

                            </div>
                        </div>

                    <?php endforeach; ?>

                </div>
            <?php endif; ?>
            
            
            <?php if( $link = get_sub_field('link_to_ads_page') ): ?>
              <a class="latest_ads__link" href="<?php echo $link['url']; ?>"  <?php echo $link['target'] ? ('target="'.$link['target'].'"') : ''; ?> ><?php echo $link['title']; ?></a>
            <?php endif; ?>

        </section>
    </div>
</div>