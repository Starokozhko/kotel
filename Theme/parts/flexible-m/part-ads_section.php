<?php
/**
 * ACF Flexible Content section: ads_section
 */

$section_id = function_exists('wp_unique_id') ? wp_unique_id('ads-section-') : 'ads-section-' . uniqid();

$switcher             = get_sub_field('switcher');
$sub_title            = get_sub_field('sub_title');
$title                = get_sub_field('title');
$description          = get_sub_field('description');
$link_to_page         = get_sub_field('link_to_page');
$link_to_contact_form = get_sub_field('link_to_contact_form');
$tabs                 = get_sub_field('tags_list');

$switcher_class = is_scalar($switcher) && $switcher ? sanitize_html_class($switcher) : 'default';

$normalize_acf_link = static function ($link, $fallback_title = '') {
    if (empty($link)) {
        return null;
    }

    if (is_array($link)) {
        $url = $link['url'] ?? '';

        if (!$url) {
            return null;
        }

        return [
            'url'    => $url,
            'title'  => !empty($link['title']) ? $link['title'] : $fallback_title,
            'target' => !empty($link['target']) ? $link['target'] : '_self',
        ];
    }

    if (is_string($link)) {
        return [
            'url'    => $link,
            'title'  => $fallback_title ?: $link,
            'target' => '_self',
        ];
    }

    return null;
};

$get_image_html = static function ($image, $index, $class = '') {
    $loading = $index === 0 ? 'eager' : 'lazy';

    if (is_array($image)) {
        $image_id = $image['ID'] ?? $image['id'] ?? 0;

        if ($image_id) {
            return wp_get_attachment_image(
                (int) $image_id,
                'large',
                false,
                [
                    'class'    => $class,
                    'loading'  => $loading,
                    'decoding' => 'async',
                ]
            );
        }

        if (!empty($image['url'])) {
            $alt = !empty($image['alt']) ? $image['alt'] : '';

            return sprintf(
                '<img src="%s" alt="%s" class="%s" loading="%s" decoding="async">',
                esc_url($image['url']),
                esc_attr($alt),
                esc_attr($class),
                esc_attr($loading)
            );
        }
    }

    if (is_numeric($image)) {
        return wp_get_attachment_image(
            (int) $image,
            'large',
            false,
            [
                'class'    => $class,
                'loading'  => $loading,
                'decoding' => 'async',
            ]
        );
    }

    if (is_string($image) && $image) {
        return sprintf(
            '<img src="%s" alt="" class="%s" loading="%s" decoding="async">',
            esc_url($image),
            esc_attr($class),
            esc_attr($loading)
        );
    }

    return '';
};

$page_link    = $normalize_acf_link($link_to_page, 'Дізнатися більше');
$contact_link = $normalize_acf_link($link_to_contact_form, 'Звʼязатися');
?>

<?php if (!empty($tabs) && is_array($tabs)) : ?>
    <section
        id="<?php echo esc_attr($section_id); ?>"
        class="ads-section ads-section--<?php echo esc_attr($switcher_class); ?>"
        data-ads-tabs-section
    >
        <div class="container">

            <div class="ads-section__header">
                <?php if ($sub_title) : ?>
                    <div class="ads-section__eyebrow">
                        <?php echo esc_html($sub_title); ?>
                    </div>
                <?php endif; ?>

                <?php if ($title) : ?>
                    <h2 class="ads-section__title market-title">
                        <?php echo wp_kses_post($title); ?>
                    </h2>
                <?php endif; ?>

                <?php if ($description) : ?>
                    <div class="ads-section__description">
                        <?php echo wp_kses_post(wpautop($description)); ?>
                    </div>
                <?php endif; ?>

                <?php if ($page_link || $contact_link) : ?>
                    <div class="ads-section__actions">
                        <?php if ($page_link) : ?>
                            <a
                                class="ads-section__button ads-section__button--primary"
                                href="<?php echo esc_url($page_link['url']); ?>"
                                target="<?php echo esc_attr($page_link['target']); ?>"
                                <?php echo $page_link['target'] === '_blank' ? 'rel="noopener"' : ''; ?>
                            >
                                <?php echo esc_html($page_link['title']); ?>
                            </a>
                        <?php endif; ?>

                        <?php if ($contact_link) : ?>
                            <a
                                class="ads-section__button ads-section__button--link"
                                href="<?php echo esc_url($contact_link['url']); ?>"
                                target="<?php echo esc_attr($contact_link['target']); ?>"
                                <?php echo $contact_link['target'] === '_blank' ? 'rel="noopener"' : ''; ?>
                            >
                                <span><?php echo esc_html($contact_link['title']); ?></span>
                                <span class="ads-section__button-icon" aria-hidden="true">›</span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="ads-section__content">
                <div class="ads-section__tabs" role="tablist" aria-label="<?php echo esc_attr($title ?: 'Реклама'); ?>">
                    <?php foreach ($tabs as $index => $tab) : ?>
                        <?php
                        $tab_title       = $tab['title'] ?? '';
                        $tab_description = $tab['description'] ?? '';
                        $tab_id          = $section_id . '-tab-' . $index;
                        $panel_id        = $section_id . '-panel-' . $index;
                        $is_active       = $index === 0;
                        ?>

                        <button
                            type="button"
                            id="<?php echo esc_attr($tab_id); ?>"
                            class="ads-section__tab <?php echo $is_active ? 'is-active' : ''; ?>"
                            role="tab"
                            aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo esc_attr($panel_id); ?>"
                            data-ads-tab-button
                            data-tab-index="<?php echo esc_attr($index); ?>"
                        >
                            <?php if ($tab_title) : ?>
                                <span class="ads-section__tab-title">
                                    <?php echo esc_html($tab_title); ?>
                                </span>
                            <?php endif; ?>

                            <?php if ($tab_description) : ?>
                                <span class="ads-section__tab-description">
                                    <?php echo esc_html($tab_description); ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <div class="ads-section__media">
                    <?php foreach ($tabs as $index => $tab) : ?>
                        <?php
                        $image    = $tab['image'] ?? null;
                        $tab_id   = $section_id . '-tab-' . $index;
                        $panel_id = $section_id . '-panel-' . $index;
                        $active   = $index === 0;
                        ?>

                        <div
                            id="<?php echo esc_attr($panel_id); ?>"
                            class="ads-section__image-panel <?php echo $active ? 'is-active' : ''; ?>"
                            role="tabpanel"
                            aria-labelledby="<?php echo esc_attr($tab_id); ?>"
                            aria-hidden="<?php echo $active ? 'false' : 'true'; ?>"
                            data-ads-tab-panel
                            data-tab-index="<?php echo esc_attr($index); ?>"
                        >
                            <?php echo $get_image_html($image, $index, 'ads-section__image'); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const sections = document.querySelectorAll('[data-ads-tabs-section]');

                if (!sections.length) {
                    return;
                }

                const isDesktopHover = function () {
                    return window.matchMedia('(min-width: 992px) and (hover: hover)').matches;
                };

                sections.forEach(function (section) {
                    const buttons = Array.from(section.querySelectorAll('[data-ads-tab-button]'));
                    const panels = Array.from(section.querySelectorAll('[data-ads-tab-panel]'));

                    if (!buttons.length || !panels.length) {
                        return;
                    }

                    const activateTab = function (index) {
                        buttons.forEach(function (button, buttonIndex) {
                            const isActive = buttonIndex === index;

                            button.classList.toggle('is-active', isActive);
                            button.setAttribute('aria-selected', isActive ? 'true' : 'false');
                        });

                        panels.forEach(function (panel, panelIndex) {
                            const isActive = panelIndex === index;

                            panel.classList.toggle('is-active', isActive);
                            panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
                        });
                    };

                    buttons.forEach(function (button, index) {
                        button.addEventListener('mouseenter', function () {
                            if (isDesktopHover()) {
                                activateTab(index);
                            }
                        });

                        button.addEventListener('focus', function () {
                            if (isDesktopHover()) {
                                activateTab(index);
                            }
                        });

                        button.addEventListener('click', function () {
                            activateTab(index);
                        });
                    });
                });
            });
        </script>
    </section>
<?php endif; ?>