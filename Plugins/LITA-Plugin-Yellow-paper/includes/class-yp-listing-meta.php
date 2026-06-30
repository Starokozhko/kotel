<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Listing_Meta {

    const NONCE_NAME   = 'yp_listing_meta_nonce';
    const NONCE_ACTION = 'yp_save_listing_meta';

    const META_PAYMENT_STATUS    = '_yp_payment_status';
    const META_MODERATION_STATUS = '_yp_moderation_status';
    const META_VISIBILITY        = '_yp_visibility';
    const META_EXPIRES_AT        = '_yp_expires_at';
    const META_ADMIN_NOTE        = '_yp_admin_note';

    const PAYMENT_UNPAID = 'unpaid';
    const PAYMENT_PAID   = 'paid';
    const PAYMENT_MANUAL = 'manual';
    const PAYMENT_FREE   = 'free';

    const MOD_NOT_SUBMITTED = 'not_submitted';
    const MOD_PENDING  = 'pending';
    const MOD_APPROVED = 'approved';
    const MOD_REJECTED = 'rejected';

    const VIS_HIDDEN = 'hidden';
    const VIS_PUBLIC = 'public';

    public function hooks() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('save_post_' . YP_Post_Types::POST_TYPE, array($this, 'save_listing_meta'), 10, 3);
        add_action('save_post_' . YP_Post_Types::POST_TYPE, array($this, 'ensure_default_meta_on_save'), 5, 3);
    }

    public function add_meta_boxes() {
        add_meta_box(
            'yp-listing-statuses',
            __('Статуси оголошення', 'yellow-paper-classifieds'),
            array($this, 'render_statuses_metabox'),
            YP_Post_Types::POST_TYPE,
            'side',
            'high'
        );

        add_meta_box(
            'yp-listing-data',
            __('Дані оголошення', 'yellow-paper-classifieds'),
            array($this, 'render_listing_data_metabox'),
            YP_Post_Types::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
            return;
        }

        $screen = function_exists('get_current_screen') ? get_current_screen() : null;

        if (!$screen || $screen->post_type !== YP_Post_Types::POST_TYPE) {
            return;
        }

        wp_enqueue_media();
    }

    public function render_statuses_metabox($post) {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $payment_status    = get_post_meta($post->ID, self::META_PAYMENT_STATUS, true);
        $moderation_status = get_post_meta($post->ID, self::META_MODERATION_STATUS, true);
        $visibility        = get_post_meta($post->ID, self::META_VISIBILITY, true);
        $expires_at        = get_post_meta($post->ID, self::META_EXPIRES_AT, true);
        $admin_note        = get_post_meta($post->ID, self::META_ADMIN_NOTE, true);
        $submission_status = get_post_meta($post->ID, YP_Listing_Workflow::META_SUBMISSION_STATUS, true);
        $publish_source    = get_post_meta($post->ID, YP_Listing_Workflow::META_PUBLISH_SOURCE, true);
        $submitted_at      = get_post_meta($post->ID, YP_Listing_Workflow::META_SUBMITTED_AT, true);
        $auto_publish_at   = get_post_meta($post->ID, YP_Listing_Workflow::META_AUTO_PUBLISH_AT, true);

        if ($payment_status === '') {
            $payment_status = self::PAYMENT_UNPAID;
        }

        if ($moderation_status === '') {
            $moderation_status = $post->post_status === YP_Listing_Workflow::POST_STATUS_SAVED ? self::MOD_NOT_SUBMITTED : self::MOD_PENDING;
        }

        if ($submission_status === '') {
            $submission_status = $post->post_status === YP_Listing_Workflow::POST_STATUS_SAVED ? YP_Listing_Workflow::SUBMISSION_SAVED : '';
        }

        if ($visibility === '') {
            $visibility = self::VIS_HIDDEN;
        }

        $is_admin = current_user_can('manage_options');

        ?>
        <div class="yp-listing-meta-box">
            <p>
                <label for="yp_payment_status"><strong><?php esc_html_e('Статус оплати', 'yellow-paper-classifieds'); ?></strong></label><br>
                <select name="yp_payment_status" id="yp_payment_status" <?php disabled(!$is_admin); ?> style="width:100%;">
                    <option value="<?php echo esc_attr(self::PAYMENT_UNPAID); ?>" <?php selected($payment_status, self::PAYMENT_UNPAID); ?>>
                        <?php esc_html_e('Не оплачено', 'yellow-paper-classifieds'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::PAYMENT_PAID); ?>" <?php selected($payment_status, self::PAYMENT_PAID); ?>>
                        <?php esc_html_e('Оплачено', 'yellow-paper-classifieds'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::PAYMENT_MANUAL); ?>" <?php selected($payment_status, self::PAYMENT_MANUAL); ?>>
                        <?php esc_html_e('Ручне підтвердження', 'yellow-paper-classifieds'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::PAYMENT_FREE); ?>" <?php selected($payment_status, self::PAYMENT_FREE); ?>>
                        <?php esc_html_e('Безкоштовно', 'yellow-paper-classifieds'); ?>
                    </option>
                </select>
            </p>

            <p>
                <label for="yp_moderation_status"><strong><?php esc_html_e('Статус модерації', 'yellow-paper-classifieds'); ?></strong></label><br>
                <select name="yp_moderation_status" id="yp_moderation_status" <?php disabled(!$is_admin); ?> style="width:100%;">
                    <option value="<?php echo esc_attr(self::MOD_NOT_SUBMITTED); ?>" <?php selected($moderation_status, self::MOD_NOT_SUBMITTED); ?>>
                        <?php esc_html_e('Не подано', 'yellow-paper-classifieds'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::MOD_PENDING); ?>" <?php selected($moderation_status, self::MOD_PENDING); ?>>
                        <?php esc_html_e('Очікує перевірки', 'yellow-paper-classifieds'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::MOD_APPROVED); ?>" <?php selected($moderation_status, self::MOD_APPROVED); ?>>
                        <?php esc_html_e('Схвалено', 'yellow-paper-classifieds'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::MOD_REJECTED); ?>" <?php selected($moderation_status, self::MOD_REJECTED); ?>>
                        <?php esc_html_e('Відхилено', 'yellow-paper-classifieds'); ?>
                    </option>
                </select>
            </p>

            <p>
                <label for="yp_visibility"><strong><?php esc_html_e('Видимість на сайті', 'yellow-paper-classifieds'); ?></strong></label><br>
                <select name="yp_visibility" id="yp_visibility" <?php disabled(!$is_admin); ?> style="width:100%;">
                    <option value="<?php echo esc_attr(self::VIS_HIDDEN); ?>" <?php selected($visibility, self::VIS_HIDDEN); ?>>
                        <?php esc_html_e('Приховано', 'yellow-paper-classifieds'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::VIS_PUBLIC); ?>" <?php selected($visibility, self::VIS_PUBLIC); ?>>
                        <?php esc_html_e('Показувати', 'yellow-paper-classifieds'); ?>
                    </option>
                </select>
            </p>

            <hr>

            <p><strong><?php esc_html_e('Статус подання:', 'yellow-paper-classifieds'); ?></strong><br>
                <?php echo esc_html($submission_status ? YP_Listing_Workflow::get_submission_status_label($submission_status) . ' (' . $submission_status . ')' : __('Не визначено', 'yellow-paper-classifieds')); ?>
            </p>

            <p><strong><?php esc_html_e('Джерело публікації:', 'yellow-paper-classifieds'); ?></strong><br>
                <?php echo esc_html($publish_source ? $publish_source : '—'); ?>
            </p>

            <p><strong><?php esc_html_e('Дата подання:', 'yellow-paper-classifieds'); ?></strong><br>
                <?php echo esc_html($submitted_at ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $submitted_at) : '—'); ?>
            </p>

            <p><strong><?php esc_html_e('Автопублікація запланована на:', 'yellow-paper-classifieds'); ?></strong><br>
                <?php echo esc_html($auto_publish_at ? wp_date(get_option('date_format') . ' ' . get_option('time_format'), (int) $auto_publish_at) : '—'); ?>
            </p>

            <hr>

            <p>
                <label for="yp_expires_at"><strong><?php esc_html_e('Дата завершення', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input
                    type="date"
                    name="yp_expires_at"
                    id="yp_expires_at"
                    value="<?php echo esc_attr($expires_at); ?>"
                    <?php disabled(!$is_admin); ?>
                    style="width:100%;"
                >
            </p>

            <p>
                <label for="yp_admin_note"><strong><?php esc_html_e('Нотатка адміністратора', 'yellow-paper-classifieds'); ?></strong></label><br>
                <textarea
                    name="yp_admin_note"
                    id="yp_admin_note"
                    rows="4"
					<?php disabled(!$is_admin); ?>
					style="width:100%;"
                ><?php echo esc_textarea($admin_note); ?></textarea>
            </p>

            <?php if (!$is_admin) : ?>
                <p style="margin:0;color:#777;">
                    <?php esc_html_e('Лише адміністратор може змінювати службові статуси.', 'yellow-paper-classifieds'); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }


    public function render_listing_data_metabox($post) {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);

        $author_id      = (int) $post->post_author;
        $author         = $author_id ? get_userdata($author_id) : false;
        $account_type   = function_exists('yp_get_user_account_type') ? yp_get_user_account_type($author_id) : '';
        $account_types  = function_exists('yp_get_account_types') ? yp_get_account_types() : array();
        $account_label  = isset($account_types[$account_type]) ? $account_types[$account_type] : $account_type;
        $is_private     = function_exists('yp_listing_is_private_person') ? yp_listing_is_private_person($post->ID) : false;

        $price          = get_post_meta($post->ID, '_yp_price', true);
        $price_type     = get_post_meta($post->ID, YP_Frontend_Submission::META_PRICE_TYPE, true);
        $special_price  = get_post_meta($post->ID, YP_Frontend_Submission::META_SPECIAL_PRICE, true);
        $sale_conditions = get_post_meta($post->ID, YP_Frontend_Submission::META_SALE_CONDITIONS, true);
        $characteristics = get_post_meta($post->ID, YP_Frontend_Submission::META_CHARACTERISTICS, true);
        $contact_name   = get_post_meta($post->ID, '_yp_contact_name', true);
        $phone          = get_post_meta($post->ID, '_yp_phone', true);
        $gallery_ids    = get_post_meta($post->ID, YP_Listing_Images::META_GALLERY_IMAGE_IDS, true);
        $featured_id    = get_post_thumbnail_id($post->ID);
        $category_id    = $this->get_primary_term_id($post->ID, YP_Post_Types::TAXONOMY);
        $location_id    = $this->get_primary_term_id($post->ID, YP_Post_Types::LOCATION_TAXONOMY);

        if (!is_array($characteristics)) {
            $characteristics = array();
        }

        if (!is_array($gallery_ids)) {
            $gallery_ids = array();
        }

        $gallery_ids = array_values(array_filter(array_map('absint', $gallery_ids)));
        $price_type  = $this->normalize_price_type($price_type, $price);

        $category_terms = get_terms(array(
            'taxonomy'   => YP_Post_Types::TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));

        if (is_wp_error($category_terms)) {
            $category_terms = array();
        }

        $location_terms = get_terms(array(
            'taxonomy'   => YP_Post_Types::LOCATION_TAXONOMY,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ));

        if (is_wp_error($location_terms)) {
            $location_terms = array();
        }

        $price_types = array(
            YP_Frontend_Submission::PRICE_TYPE_NO_PRICE  => __('Без вказання ціни', 'yellow-paper-classifieds'),
            YP_Frontend_Submission::PRICE_TYPE_REGULAR   => __('Звичайна', 'yellow-paper-classifieds'),
            YP_Frontend_Submission::PRICE_TYPE_SALE      => __('Акція', 'yellow-paper-classifieds'),
            YP_Frontend_Submission::PRICE_TYPE_CLEARANCE => __('Розпродаж', 'yellow-paper-classifieds'),
        );
        ?>
        <div class="yp-admin-listing-data">
            <style>
                .yp-admin-listing-data .yp-admin-section{border:1px solid #dcdcde;background:#fff;margin:0 0 16px;padding:14px;}
                .yp-admin-listing-data .yp-admin-section h3{margin:0 0 12px;font-size:14px;}
                .yp-admin-listing-data .yp-admin-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
                .yp-admin-listing-data label strong{display:block;margin-bottom:4px;}
                .yp-admin-listing-data input[type="text"],.yp-admin-listing-data input[type="number"],.yp-admin-listing-data select,.yp-admin-listing-data textarea{width:100%;}
                .yp-admin-listing-data .yp-characteristic-row{display:grid;grid-template-columns:1fr 1fr auto;gap:8px;margin-bottom:8px;align-items:center;}
                .yp-admin-listing-data .yp-gallery-preview{display:flex;flex-wrap:wrap;gap:10px;margin-top:10px;}
                .yp-admin-listing-data .yp-gallery-item{border:1px solid #dcdcde;padding:6px;background:#f6f7f7;width:96px;text-align:center;}
                .yp-admin-listing-data .yp-gallery-item img{max-width:80px;height:auto;display:block;margin:0 auto 4px;}
                .yp-admin-muted{color:#646970;}
                @media (max-width: 782px){.yp-admin-listing-data .yp-admin-grid{grid-template-columns:1fr}.yp-admin-listing-data .yp-characteristic-row{grid-template-columns:1fr}}
            </style>

            <div class="yp-admin-section">
                <h3><?php esc_html_e('Технічна інформація', 'yellow-paper-classifieds'); ?></h3>
                <div class="yp-admin-grid">
                    <p><strong><?php esc_html_e('ID оголошення', 'yellow-paper-classifieds'); ?></strong><?php echo esc_html($post->ID); ?></p>
                    <p><strong><?php esc_html_e('Статус поста', 'yellow-paper-classifieds'); ?></strong><?php echo esc_html($post->post_status); ?></p>
                    <p><strong><?php esc_html_e('Автор', 'yellow-paper-classifieds'); ?></strong><?php echo esc_html($author ? $author->display_name . ' (#' . $author_id . ')' : '—'); ?></p>
                    <p><strong><?php esc_html_e('Email автора', 'yellow-paper-classifieds'); ?></strong><?php echo esc_html($author ? $author->user_email : '—'); ?></p>
                    <p><strong><?php esc_html_e('Тип акаунта автора', 'yellow-paper-classifieds'); ?></strong><?php echo esc_html($account_label ? $account_label . ' (' . $account_type . ')' : '—'); ?></p>
                    <p><strong><?php esc_html_e('Дата створення / оновлення', 'yellow-paper-classifieds'); ?></strong><?php echo esc_html(get_the_date('', $post) . ' / ' . get_the_modified_date('', $post)); ?></p>
                </div>
                <?php if ($is_private) : ?>
                    <p class="yp-admin-muted"><?php esc_html_e('Це оголошення приватної особи. Адміністратор може бачити технічні поля ціни/характеристик, але frontend для приватних осіб їх не показує.', 'yellow-paper-classifieds'); ?></p>
                <?php endif; ?>
            </div>

            <div class="yp-admin-section">
                <h3><?php esc_html_e('Основна інформація', 'yellow-paper-classifieds'); ?></h3>
                <p class="yp-admin-muted"><?php esc_html_e('Заголовок, опис, автора і головне фото також можна редагувати стандартними полями WordPress вище/праворуч.', 'yellow-paper-classifieds'); ?></p>
                <div class="yp-admin-grid">
                    <p>
                        <label for="yp_admin_category_id"><strong><?php esc_html_e('Кінцева категорія / тип оголошення', 'yellow-paper-classifieds'); ?></strong></label>
                        <select name="yp_admin_category_id" id="yp_admin_category_id">
                            <option value="0"><?php esc_html_e('Не вибрано', 'yellow-paper-classifieds'); ?></option>
                            <?php foreach ($category_terms as $term) : ?>
                                <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected((int) $category_id, (int) $term->term_id); ?>>
                                    <?php echo esc_html($this->get_term_label_with_parents($term)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p>
                        <label for="yp_admin_location_id"><strong><?php esc_html_e('Населений пункт', 'yellow-paper-classifieds'); ?></strong></label>
                        <select name="yp_admin_location_id" id="yp_admin_location_id">
                            <option value="0"><?php esc_html_e('Не вибрано', 'yellow-paper-classifieds'); ?></option>
                            <?php foreach ($location_terms as $term) : ?>
                                <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected((int) $location_id, (int) $term->term_id); ?>><?php echo esc_html($term->name); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                </div>
            </div>

            <div class="yp-admin-section">
                <h3><?php esc_html_e('Контакти', 'yellow-paper-classifieds'); ?></h3>
                <div class="yp-admin-grid">
                    <p>
                        <label for="yp_admin_contact_name"><strong><?php esc_html_e('Ім’я для зв’язку', 'yellow-paper-classifieds'); ?></strong></label>
                        <input type="text" name="yp_admin_contact_name" id="yp_admin_contact_name" value="<?php echo esc_attr($contact_name); ?>">
                    </p>
                    <p>
                        <label for="yp_admin_phone"><strong><?php esc_html_e('Телефон', 'yellow-paper-classifieds'); ?></strong></label>
                        <input type="text" name="yp_admin_phone" id="yp_admin_phone" value="<?php echo esc_attr($phone); ?>" placeholder="0961234567">
                    </p>
                </div>
            </div>

            <div class="yp-admin-section">
                <h3><?php esc_html_e('Ціна', 'yellow-paper-classifieds'); ?></h3>
                <div class="yp-admin-grid">
                    <p>
                        <label for="yp_admin_price_type"><strong><?php esc_html_e('Тип ціни', 'yellow-paper-classifieds'); ?></strong></label>
                        <select name="yp_admin_price_type" id="yp_admin_price_type">
                            <?php foreach ($price_types as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($price_type, $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                    <p class="yp-admin-price-field">
                        <label for="yp_admin_price"><strong><?php esc_html_e('Основна ціна', 'yellow-paper-classifieds'); ?></strong></label>
                        <input type="text" name="yp_admin_price" id="yp_admin_price" value="<?php echo esc_attr($price); ?>">
                    </p>
                    <p class="yp-admin-special-price-field">
                        <label for="yp_admin_special_price"><strong><?php esc_html_e('Спеціальна ціна', 'yellow-paper-classifieds'); ?></strong></label>
                        <input type="text" name="yp_admin_special_price" id="yp_admin_special_price" value="<?php echo esc_attr($special_price); ?>">
                    </p>
                    <p class="yp-admin-sale-conditions-field">
                        <label for="yp_admin_sale_conditions"><strong><?php esc_html_e('Умови акції', 'yellow-paper-classifieds'); ?></strong></label>
                        <textarea name="yp_admin_sale_conditions" id="yp_admin_sale_conditions" rows="3"><?php echo esc_textarea($sale_conditions); ?></textarea>
                    </p>
                </div>
            </div>

            <div class="yp-admin-section">
                <h3><?php esc_html_e('Характеристики', 'yellow-paper-classifieds'); ?></h3>
                <div id="yp-admin-characteristics-list">
                    <?php if (empty($characteristics)) : ?>
                        <?php $characteristics = array(array('label' => '', 'value' => '')); ?>
                    <?php endif; ?>
                    <?php foreach ($characteristics as $row) : ?>
                        <div class="yp-characteristic-row">
                            <input type="text" name="yp_admin_characteristics[label][]" value="<?php echo esc_attr(isset($row['label']) ? $row['label'] : ''); ?>" placeholder="<?php esc_attr_e('Назва', 'yellow-paper-classifieds'); ?>">
                            <input type="text" name="yp_admin_characteristics[value][]" value="<?php echo esc_attr(isset($row['value']) ? $row['value'] : ''); ?>" placeholder="<?php esc_attr_e('Значення', 'yellow-paper-classifieds'); ?>">
                            <button type="button" class="button yp-admin-remove-characteristic"><?php esc_html_e('Видалити', 'yellow-paper-classifieds'); ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <p><button type="button" class="button" id="yp-admin-add-characteristic"><?php esc_html_e('Додати характеристику', 'yellow-paper-classifieds'); ?></button></p>
            </div>

            <div class="yp-admin-section">
                <h3><?php esc_html_e('Зображення', 'yellow-paper-classifieds'); ?></h3>
                <p class="yp-admin-muted"><?php esc_html_e('Галерея використовує той самий meta key, що й frontend: _yp_gallery_image_ids. Головне фото також можна змінити стандартним блоком “Головне фото”.', 'yellow-paper-classifieds'); ?></p>
                <p>
                    <label for="yp_admin_gallery_ids"><strong><?php esc_html_e('ID зображень галереї', 'yellow-paper-classifieds'); ?></strong></label>
                    <input type="text" name="yp_admin_gallery_ids" id="yp_admin_gallery_ids" value="<?php echo esc_attr(implode(',', $gallery_ids)); ?>" placeholder="12,15,18">
                </p>
                <p>
                    <button type="button" class="button" id="yp-admin-select-gallery"><?php esc_html_e('Вибрати/змінити галерею', 'yellow-paper-classifieds'); ?></button>
                </p>
                <p>
                    <label for="yp_admin_featured_image_id"><strong><?php esc_html_e('ID головного фото', 'yellow-paper-classifieds'); ?></strong></label>
                    <input type="number" min="0" name="yp_admin_featured_image_id" id="yp_admin_featured_image_id" value="<?php echo esc_attr($featured_id); ?>">
                </p>
                <div class="yp-gallery-preview" id="yp-admin-gallery-preview">
                    <?php foreach ($gallery_ids as $image_id) : ?>
                        <?php $image_url = wp_get_attachment_image_url($image_id, 'thumbnail'); ?>
                        <div class="yp-gallery-item" data-id="<?php echo esc_attr($image_id); ?>">
                            <?php if ($image_url) : ?>
                                <img src="<?php echo esc_url($image_url); ?>" alt="">
                            <?php endif; ?>
                            <code><?php echo esc_html($image_id); ?></code>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
        (function($){
            function refreshPriceFields(){
                var type = $('#yp_admin_price_type').val();
                $('.yp-admin-price-field').toggle(type !== '<?php echo esc_js(YP_Frontend_Submission::PRICE_TYPE_NO_PRICE); ?>');
                $('.yp-admin-special-price-field').toggle(type === '<?php echo esc_js(YP_Frontend_Submission::PRICE_TYPE_SALE); ?>' || type === '<?php echo esc_js(YP_Frontend_Submission::PRICE_TYPE_CLEARANCE); ?>');
                $('.yp-admin-sale-conditions-field').toggle(type === '<?php echo esc_js(YP_Frontend_Submission::PRICE_TYPE_SALE); ?>');
            }
            refreshPriceFields();
            $('#yp_admin_price_type').on('change', refreshPriceFields);

            $('#yp-admin-add-characteristic').on('click', function(){
                $('#yp-admin-characteristics-list').append(
                    '<div class="yp-characteristic-row">' +
                    '<input type="text" name="yp_admin_characteristics[label][]" placeholder="<?php echo esc_js(__('Назва', 'yellow-paper-classifieds')); ?>">' +
                    '<input type="text" name="yp_admin_characteristics[value][]" placeholder="<?php echo esc_js(__('Значення', 'yellow-paper-classifieds')); ?>">' +
                    '<button type="button" class="button yp-admin-remove-characteristic"><?php echo esc_js(__('Видалити', 'yellow-paper-classifieds')); ?></button>' +
                    '</div>'
                );
            });

            $('#yp-admin-characteristics-list').on('click', '.yp-admin-remove-characteristic', function(){
                $(this).closest('.yp-characteristic-row').remove();
            });

            $('#yp-admin-select-gallery').on('click', function(e){
                e.preventDefault();
                if (typeof wp === 'undefined' || !wp.media) {
                    return;
                }

                var frame = wp.media({
                    title: '<?php echo esc_js(__('Виберіть зображення галереї', 'yellow-paper-classifieds')); ?>',
                    button: { text: '<?php echo esc_js(__('Використати вибрані зображення', 'yellow-paper-classifieds')); ?>' },
                    multiple: true,
                    library: { type: 'image' }
                });

                frame.on('select', function(){
                    var ids = [];
                    var html = '';
                    frame.state().get('selection').each(function(attachment){
                        var data = attachment.toJSON();
                        ids.push(data.id);
                        var src = data.sizes && data.sizes.thumbnail ? data.sizes.thumbnail.url : data.url;
                        html += '<div class="yp-gallery-item" data-id="' + data.id + '">';
                        if (src) {
                            html += '<img src="' + src + '" alt="">';
                        }
                        html += '<code>' + data.id + '</code></div>';
                    });
                    $('#yp_admin_gallery_ids').val(ids.join(','));
                    $('#yp-admin-gallery-preview').html(html);
                    if (ids.length && !parseInt($('#yp_admin_featured_image_id').val(), 10)) {
                        $('#yp_admin_featured_image_id').val(ids[0]);
                    }
                });

                frame.open();
            });
        })(jQuery);
        </script>
        <?php
    }

    public function ensure_default_meta_on_save($post_id, $post, $update) {
        if (wp_is_post_revision($post_id)) {
            return;
        }

        if ($post->post_type !== YP_Post_Types::POST_TYPE) {
            return;
        }

        $this->maybe_set_default_meta($post_id, self::META_PAYMENT_STATUS, self::PAYMENT_UNPAID);
        $this->maybe_set_default_meta($post_id, self::META_MODERATION_STATUS, $post->post_status === YP_Listing_Workflow::POST_STATUS_SAVED ? self::MOD_NOT_SUBMITTED : self::MOD_PENDING);
        $this->maybe_set_default_meta($post_id, YP_Listing_Workflow::META_SUBMISSION_STATUS, $post->post_status === YP_Listing_Workflow::POST_STATUS_SAVED ? YP_Listing_Workflow::SUBMISSION_SAVED : YP_Listing_Workflow::SUBMISSION_SUBMITTED);
        $this->maybe_set_default_meta($post_id, self::META_VISIBILITY, self::VIS_HIDDEN);
    }

    public function save_listing_meta($post_id, $post, $update) {
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        if (!isset($_POST[self::NONCE_NAME])) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]));

        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Службові статуси змінює лише адміністратор.
        if (!current_user_can('manage_options')) {
            return;
        }

        $payment_status = isset($_POST['yp_payment_status'])
            ? sanitize_text_field(wp_unslash($_POST['yp_payment_status']))
            : self::PAYMENT_UNPAID;

        $moderation_status = isset($_POST['yp_moderation_status'])
            ? sanitize_text_field(wp_unslash($_POST['yp_moderation_status']))
            : self::MOD_PENDING;

        $visibility = isset($_POST['yp_visibility'])
            ? sanitize_text_field(wp_unslash($_POST['yp_visibility']))
            : self::VIS_HIDDEN;

        $expires_at = isset($_POST['yp_expires_at'])
            ? sanitize_text_field(wp_unslash($_POST['yp_expires_at']))
            : '';

        $admin_note = isset($_POST['yp_admin_note'])
            ? sanitize_text_field(wp_unslash($_POST['yp_admin_note']))
            : '';

        $allowed_payment_statuses = array(
            self::PAYMENT_UNPAID,
            self::PAYMENT_PAID,
            self::PAYMENT_MANUAL,
            self::PAYMENT_FREE,
        );

        $allowed_moderation_statuses = array(
            self::MOD_NOT_SUBMITTED,
            self::MOD_PENDING,
            self::MOD_APPROVED,
            self::MOD_REJECTED,
        );

        $allowed_visibility_values = array(
            self::VIS_HIDDEN,
            self::VIS_PUBLIC,
        );

        if (!in_array($payment_status, $allowed_payment_statuses, true)) {
            $payment_status = self::PAYMENT_UNPAID;
        }

        if (!in_array($moderation_status, $allowed_moderation_statuses, true)) {
            $moderation_status = self::MOD_PENDING;
        }

        if (!in_array($visibility, $allowed_visibility_values, true)) {
            $visibility = self::VIS_HIDDEN;
        }

        if ($expires_at !== '' && !$this->is_valid_date($expires_at)) {
            $expires_at = '';
        }

        update_post_meta($post_id, self::META_PAYMENT_STATUS, $payment_status);
        update_post_meta($post_id, self::META_MODERATION_STATUS, $moderation_status);
        update_post_meta($post_id, self::META_VISIBILITY, $visibility);
        update_post_meta($post_id, self::META_EXPIRES_AT, $expires_at);
        update_post_meta($post_id, self::META_ADMIN_NOTE, $admin_note);

        $this->save_listing_data_fields($post_id, $post);

        if ($moderation_status === self::MOD_REJECTED || $moderation_status === self::MOD_NOT_SUBMITTED) {
            YP_Listing_Workflow::clear_listing_auto_publish($post_id);
        }

        if ($moderation_status === self::MOD_REJECTED) {
            update_post_meta($post_id, YP_Listing_Workflow::META_SUBMISSION_STATUS, YP_Listing_Workflow::SUBMISSION_SAVED);
        }

        // Якщо адмін схвалив і зробив видимим, а пост ще не publish — опублікуємо.
        if (
            $moderation_status === self::MOD_APPROVED &&
            $visibility === self::VIS_PUBLIC &&
            $post->post_status !== 'publish'
        ) {
            update_post_meta($post_id, YP_Listing_Workflow::META_SUBMISSION_STATUS, YP_Listing_Workflow::SUBMISSION_PUBLISHED);
            update_post_meta($post_id, YP_Listing_Workflow::META_PUBLISH_SOURCE, 'admin');
            YP_Listing_Workflow::clear_listing_auto_publish($post_id);

            remove_action('save_post_' . YP_Post_Types::POST_TYPE, array($this, 'save_listing_meta'), 10);

            wp_update_post(array(
                'ID'          => $post_id,
                'post_status' => 'publish',
            ));

            add_action('save_post_' . YP_Post_Types::POST_TYPE, array($this, 'save_listing_meta'), 10, 3);
        }
    }


    private function save_listing_data_fields($post_id, $post) {
        if (isset($_POST['yp_admin_contact_name'])) {
            update_post_meta($post_id, '_yp_contact_name', sanitize_text_field(wp_unslash($_POST['yp_admin_contact_name'])));
        }

        if (isset($_POST['yp_admin_phone'])) {
            $phone = preg_replace('/\D+/', '', (string) wp_unslash($_POST['yp_admin_phone']));
            update_post_meta($post_id, '_yp_phone', sanitize_text_field($phone));
        }

        if (isset($_POST['yp_admin_price_type'])) {
            $price_type = sanitize_key(wp_unslash($_POST['yp_admin_price_type']));
            $price      = isset($_POST['yp_admin_price']) ? sanitize_text_field(wp_unslash($_POST['yp_admin_price'])) : get_post_meta($post_id, '_yp_price', true);
            $special    = isset($_POST['yp_admin_special_price']) ? sanitize_text_field(wp_unslash($_POST['yp_admin_special_price'])) : get_post_meta($post_id, YP_Frontend_Submission::META_SPECIAL_PRICE, true);
            $conditions = isset($_POST['yp_admin_sale_conditions']) ? sanitize_textarea_field(wp_unslash($_POST['yp_admin_sale_conditions'])) : get_post_meta($post_id, YP_Frontend_Submission::META_SALE_CONDITIONS, true);

            $allowed_price_types = array(
                YP_Frontend_Submission::PRICE_TYPE_NO_PRICE,
                YP_Frontend_Submission::PRICE_TYPE_REGULAR,
                YP_Frontend_Submission::PRICE_TYPE_SALE,
                YP_Frontend_Submission::PRICE_TYPE_CLEARANCE,
            );

            if (!in_array($price_type, $allowed_price_types, true)) {
                $price_type = $this->normalize_price_type(get_post_meta($post_id, YP_Frontend_Submission::META_PRICE_TYPE, true), $price);
            }

            if ($price_type === YP_Frontend_Submission::PRICE_TYPE_NO_PRICE) {
                $price = '';
                $special = '';
                $conditions = '';
            } elseif ($price_type === YP_Frontend_Submission::PRICE_TYPE_REGULAR) {
                $special = '';
                $conditions = '';
            } elseif ($price_type === YP_Frontend_Submission::PRICE_TYPE_CLEARANCE) {
                $conditions = '';
            }

            update_post_meta($post_id, YP_Frontend_Submission::META_PRICE_TYPE, $price_type);
            update_post_meta($post_id, '_yp_price', $price);
            update_post_meta($post_id, YP_Frontend_Submission::META_SPECIAL_PRICE, $special);
            update_post_meta($post_id, YP_Frontend_Submission::META_SALE_CONDITIONS, $conditions);
        }

        if (isset($_POST['yp_admin_characteristics']) && is_array($_POST['yp_admin_characteristics'])) {
            update_post_meta($post_id, YP_Frontend_Submission::META_CHARACTERISTICS, $this->sanitize_characteristics(wp_unslash($_POST['yp_admin_characteristics'])));
        }

        if (isset($_POST['yp_admin_category_id'])) {
            $category_id = absint($_POST['yp_admin_category_id']);
            if ($category_id > 0 && term_exists($category_id, YP_Post_Types::TAXONOMY)) {
                wp_set_object_terms($post_id, array($category_id), YP_Post_Types::TAXONOMY, false);
            }
        }

        if (isset($_POST['yp_admin_location_id'])) {
            $location_id = absint($_POST['yp_admin_location_id']);
            if ($location_id > 0 && term_exists($location_id, YP_Post_Types::LOCATION_TAXONOMY)) {
                wp_set_object_terms($post_id, array($location_id), YP_Post_Types::LOCATION_TAXONOMY, false);
            }
        }

        if (isset($_POST['yp_admin_gallery_ids'])) {
            $gallery_ids = $this->sanitize_gallery_ids(wp_unslash($_POST['yp_admin_gallery_ids']));
            update_post_meta($post_id, YP_Listing_Images::META_GALLERY_IMAGE_IDS, $gallery_ids);

            $featured_id = isset($_POST['yp_admin_featured_image_id']) ? absint($_POST['yp_admin_featured_image_id']) : 0;

            if ($featured_id > 0 && in_array($featured_id, $gallery_ids, true)) {
                set_post_thumbnail($post_id, $featured_id);
            } elseif (!empty($gallery_ids) && !get_post_thumbnail_id($post_id)) {
                set_post_thumbnail($post_id, (int) $gallery_ids[0]);
            } elseif (empty($gallery_ids) && $featured_id <= 0) {
                delete_post_thumbnail($post_id);
            }
        } elseif (isset($_POST['yp_admin_featured_image_id'])) {
            $featured_id = absint($_POST['yp_admin_featured_image_id']);
            if ($featured_id > 0) {
                set_post_thumbnail($post_id, $featured_id);
            } else {
                delete_post_thumbnail($post_id);
            }
        }

        if (function_exists('yp_sync_listing_author_type')) {
            yp_sync_listing_author_type($post_id, (int) $post->post_author);
        }

        if (function_exists('yp_clear_listings_cache')) {
            yp_clear_listings_cache($post_id, (int) $post->post_author);
        }
    }

    private function sanitize_characteristics($raw) {
        $labels = isset($raw['label']) ? (array) $raw['label'] : array();
        $values = isset($raw['value']) ? (array) $raw['value'] : array();
        $max    = max(count($labels), count($values));
        $clean  = array();

        for ($i = 0; $i < $max; $i++) {
            $label = isset($labels[$i]) ? sanitize_text_field($labels[$i]) : '';
            $value = isset($values[$i]) ? sanitize_text_field($values[$i]) : '';

            if ($label === '' && $value === '') {
                continue;
            }

            $clean[] = array(
                'label' => $label,
                'value' => $value,
            );
        }

        return $clean;
    }

    private function sanitize_gallery_ids($raw) {
        if (is_array($raw)) {
            $ids = $raw;
        } else {
            $ids = preg_split('/[\s,]+/', (string) $raw);
        }

        return array_values(array_unique(array_filter(array_map('absint', (array) $ids))));
    }

    private function get_primary_term_id($post_id, $taxonomy) {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));

        if (is_wp_error($terms) || empty($terms)) {
            return 0;
        }

        return (int) $terms[0];
    }

    private function normalize_price_type($price_type, $price) {
        $allowed_price_types = array(
            YP_Frontend_Submission::PRICE_TYPE_NO_PRICE,
            YP_Frontend_Submission::PRICE_TYPE_REGULAR,
            YP_Frontend_Submission::PRICE_TYPE_SALE,
            YP_Frontend_Submission::PRICE_TYPE_CLEARANCE,
        );

        if (in_array($price_type, $allowed_price_types, true)) {
            return $price_type;
        }

        return trim((string) $price) !== '' ? YP_Frontend_Submission::PRICE_TYPE_REGULAR : YP_Frontend_Submission::PRICE_TYPE_NO_PRICE;
    }

    private function get_term_label_with_parents($term) {
        $names = array($term->name);
        $parent_id = (int) $term->parent;

        while ($parent_id > 0) {
            $parent = get_term($parent_id, $term->taxonomy);

            if (!$parent || is_wp_error($parent)) {
                break;
            }

            array_unshift($names, $parent->name);
            $parent_id = (int) $parent->parent;
        }

        return implode(' → ', $names);
    }

    private function maybe_set_default_meta($post_id, $meta_key, $default_value) {
        $current_value = get_post_meta($post_id, $meta_key, true);

        if ($current_value === '') {
            update_post_meta($post_id, $meta_key, $default_value);
        }
    }

    private function is_valid_date($date) {
        $dt = DateTime::createFromFormat('Y-m-d', $date);

        return $dt && $dt->format('Y-m-d') === $date;
    }
}