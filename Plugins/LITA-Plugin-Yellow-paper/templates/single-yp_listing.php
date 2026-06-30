<?php
if (!defined('ABSPATH')) {
    exit;
}

$loader = new YP_Template_Loader(new YP_Listing_Images(), new YP_User_Profile());
$data = $loader->get_single_listing_data(get_the_ID());

get_header();
?>

    <main class="yp-single-listing" style="max-width:1200px;margin:0 auto;padding:32px 16px;">
        <article>
            <header style="margin-bottom:24px;">
                <h1 style="margin:0 0 12px;"><?php echo esc_html($data['title']); ?></h1>

                <div style="display:flex;flex-wrap:wrap;gap:16px;">
                    <?php if (!empty($data['category_name'])) : ?>
                        <p style="margin:0;">
                            <strong><?php esc_html_e('Категорія:', 'yellow-paper-classifieds'); ?></strong> <?php echo esc_html($data['category_name']); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($data['location_name'])) : ?>
                        <p style="margin:0;">
                            <strong><?php esc_html_e('Населений пункт:', 'yellow-paper-classifieds'); ?></strong> <?php echo esc_html($data['location_name']); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($data['price_type']) && $data['price_type'] !== YP_Frontend_Submission::PRICE_TYPE_NO_PRICE) : ?>
                        <p style="margin:0;">
                            <strong><?php esc_html_e('Тип ціни:', 'yellow-paper-classifieds'); ?></strong> <?php echo esc_html($loader->get_price_type_label($data['price_type'])); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </header>

            <?php if (!empty($data['image_ids'])) : ?>
                <section class="images">
                    <div class="images-list">
                        <?php foreach ($data['image_ids'] as $image_id) : ?>

                            <?php
                            $large_url = wp_get_attachment_image_url($image_id, 'large');
                            $full_url  = wp_get_attachment_image_url($image_id, 'full_hd');
                            $alt       = get_post_meta($image_id, '_wp_attachment_image_alt', true);

                            if (!$alt) {
                                $alt = $data['title'];
                            }
                            ?>

                            <?php if ($large_url && $full_url) : ?>
                                <div class="images-item">
                                    <a href="<?php echo esc_url($full_url); ?>"
                                       data-fancybox="listing-gallery"
                                       data-caption="<?php echo esc_attr($data['title']); ?>">
                                        <img src="<?php echo esc_url($large_url); ?>"
                                             alt="<?php echo esc_attr($alt); ?>"
                                             loading="lazy">
                                    </a>
                                </div>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!$data['is_private_person'] && $data['price_type'] === YP_Frontend_Submission::PRICE_TYPE_NO_PRICE) : ?>
                <section style="margin-bottom:32px;">
                    <p class="price-title"><?php esc_html_e('Ціна', 'yellow-paper-classifieds'); ?></p>
                    <p style="margin:0;font-size:24px;font-weight:700;">
                        <?php esc_html_e('Без вказання ціни', 'yellow-paper-classifieds'); ?>
                    </p>
                </section>
            <?php elseif (!$data['is_private_person'] && !empty($data['price'])) : ?>
                <section style="margin-bottom:32px;">
                    <p class="price-title"><?php esc_html_e('Ціна', 'yellow-paper-classifieds'); ?></p>

                    <?php if (!empty($data['special_price']) && in_array($data['price_type'], array('sale', 'clearance'), true)) : ?>
                        <p class="price-count">
                            <?php echo esc_html($data['price']); ?>
                        </p>
                        <p style="margin:0;font-size:30px;font-weight:700;">
                            <?php echo esc_html($data['special_price']); ?>
                        </p>
                    <?php else : ?>
                        <p style="margin:0;font-size:30px;font-weight:700;">
                            <?php echo esc_html($data['price']); ?>
                        </p>
                    <?php endif; ?>

                    <?php if (!empty($data['sale_conditions']) && $data['price_type'] === 'sale') : ?>
                        <div style="margin-top:12px;">
                            <strong><?php esc_html_e('Умови акції:', 'yellow-paper-classifieds'); ?></strong>
                            <p style="margin:8px 0 0;"><?php echo nl2br(esc_html($data['sale_conditions'])); ?></p>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endif; ?>

            <section style="margin-bottom:32px;">
                <h2><?php esc_html_e('Опис', 'yellow-paper-classifieds'); ?></h2>
                <div><?php echo $data['content']; ?></div>
            </section>

            <?php if (!$data['is_private_person'] && !empty($data['characteristics'])) : ?>
                <section class="options">
                    <h2><?php esc_html_e('Характеристики', 'yellow-paper-classifieds'); ?></h2>

                    <div class="options__table">
                        <?php foreach ($data['characteristics'] as $row) : ?>
                            <?php
                            $label = isset($row['label']) ? trim((string)$row['label']) : '';
                            $value = isset($row['value']) ? trim((string)$row['value']) : '';
                            if ($label === '' && $value === '') {
                                continue;
                            }
                            ?>
                            <div class="options__row">
                                <div class="options__row-cel options__row-title"><?php echo esc_html($label); ?></div>
                                <div class="options__row-cel"><?php echo esc_html($value); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <section style="margin-bottom:32px;">
                <h2><?php esc_html_e('Контакти по оголошенню', 'yellow-paper-classifieds'); ?></h2>

                <?php if (!empty($data['contact_name'])) : ?>
                    <p>
                        <strong><?php esc_html_e('Контактна особа:', 'yellow-paper-classifieds'); ?></strong> <?php echo esc_html($data['contact_name']); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($data['phone_formatted'])) : ?>
                    <p>
                        <strong><?php esc_html_e('Телефон:', 'yellow-paper-classifieds'); ?></strong> <?php echo esc_html($data['phone_formatted']); ?>
                    </p>
                <?php endif; ?>
            </section>

            <section style="margin-bottom:32px;">
                <h2><?php esc_html_e('Магазин / продавець', 'yellow-paper-classifieds'); ?></h2>

                <?php
                $store = $data['store_data'];
                $logo_url = !empty($store['store_logo_id']) ? wp_get_attachment_image_url((int)$store['store_logo_id'], 'medium') : '';
                ?>

                <?php if ($logo_url) : ?>
                    <p><img src="<?php echo esc_url($logo_url); ?>" alt="" style="max-width:160px;height:auto;"></p>
                <?php endif; ?>

                <?php if (!empty($store['store_name'])) : ?>
                    <p>
                        <strong><?php esc_html_e('Назва магазину:', 'yellow-paper-classifieds'); ?></strong> <?php echo esc_html($store['store_name']); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($store['store_phone'])) : ?>
                    <p>
                        <strong><?php esc_html_e('Телефон магазину:', 'yellow-paper-classifieds'); ?></strong> <?php echo esc_html($loader->format_phone_for_display($store['store_phone'])); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($store['store_address'])) : ?>
                    <p>
                        <strong><?php esc_html_e('Адреса:', 'yellow-paper-classifieds'); ?></strong> <?php echo esc_html($store['store_address']); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($store['store_work_hours'])) : ?>
                    <p>
                        <strong><?php esc_html_e('Години роботи:', 'yellow-paper-classifieds'); ?></strong><br><?php echo nl2br(esc_html($store['store_work_hours'])); ?>
                    </p>
                <?php endif; ?>

                <?php if (!empty($store['store_website'])) : ?>
                    <p><strong><?php esc_html_e('Сайт:', 'yellow-paper-classifieds'); ?></strong> <a
                                href="<?php echo esc_url($store['store_website']); ?>" target="_blank"
                                rel="nofollow noopener"><?php echo esc_html($store['store_website']); ?></a></p>
                <?php endif; ?>

                <div style="display:flex;flex-wrap:wrap;gap:12px;">
                    <?php if (!empty($store['store_facebook'])) : ?>
                        <a href="<?php echo esc_url($store['store_facebook']); ?>" target="_blank"
                           rel="nofollow noopener">Facebook</a>
                    <?php endif; ?>

                    <?php if (!empty($store['store_instagram'])) : ?>
                        <a href="<?php echo esc_url($store['store_instagram']); ?>" target="_blank"
                           rel="nofollow noopener">Instagram</a>
                    <?php endif; ?>

                    <?php if (!empty($store['store_social_extra'])) : ?>
                        <a href="<?php echo esc_url($store['store_social_extra']); ?>" target="_blank"
                           rel="nofollow noopener"><?php esc_html_e('Соцмережа', 'yellow-paper-classifieds'); ?></a>
                    <?php endif; ?>
                </div>
            </section>
        </article>
    </main>

<?php get_footer(); ?>