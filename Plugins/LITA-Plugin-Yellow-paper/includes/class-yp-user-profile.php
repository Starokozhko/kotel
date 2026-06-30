<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_User_Profile {

    const META_STORE_NAME         = '_yp_store_name';
    const META_STORE_PHONE        = '_yp_store_phone';
    const META_STORE_WHATSAPP     = '_yp_store_whatsapp';
    const META_STORE_TELEGRAM     = '_yp_store_telegram';
    const META_STORE_VIBER        = '_yp_store_viber';
    const META_STORE_FACEBOOK     = '_yp_store_facebook';
    const META_STORE_INSTAGRAM    = '_yp_store_instagram';
    const META_STORE_SOCIAL_EXTRA = '_yp_store_social_extra';
    const META_STORE_LOGO_ID      = '_yp_store_logo_id';
    const META_STORE_ADDRESS      = '_yp_store_address';
    const META_STORE_WORK_HOURS   = '_yp_store_work_hours';
    const META_STORE_WEBSITE      = '_yp_store_website';

    const NONCE_ACTION = 'yp_save_user_store_profile';
    const NONCE_NAME   = 'yp_user_store_profile_nonce';

    public function hooks() {
        add_action('show_user_profile', array($this, 'render_admin_profile_fields'));
        add_action('edit_user_profile', array($this, 'render_admin_profile_fields'));

        add_action('personal_options_update', array($this, 'save_admin_profile_fields'));
        add_action('edit_user_profile_update', array($this, 'save_admin_profile_fields'));
    }

    public function render_admin_profile_fields($user) {
        $values = $this->get_user_store_data($user->ID);

        wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME);
        ?>
        <h2><?php esc_html_e('Налаштування магазину / продавця', 'yellow-paper-classifieds'); ?></h2>

        <table class="form-table" role="presentation">

            <tr>
                <th><label for="yp_account_type"><?php esc_html_e('Тип акаунта', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <select name="yp_account_type" id="yp_account_type">
                        <?php foreach (yp_get_account_types() as $account_type_slug => $account_type_label) : ?>
                            <option value="<?php echo esc_attr($account_type_slug); ?>" <?php selected(yp_get_user_account_type($user->ID), $account_type_slug); ?>>
                                <?php echo esc_html($account_type_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Після зміни типу акаунта всі оголошення цього користувача автоматично отримають відповідний термін таксономії.', 'yellow-paper-classifieds'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="yp_account_payment_status"><?php esc_html_e('Статус оплати акаунта', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <select name="yp_account_payment_status" id="yp_account_payment_status">
                        <option value="<?php echo esc_attr(YP_Listing_Workflow::ACCOUNT_PAYMENT_INACTIVE); ?>" <?php selected(YP_Listing_Workflow::get_account_payment_status($user->ID), YP_Listing_Workflow::ACCOUNT_PAYMENT_INACTIVE); ?>>
                            <?php esc_html_e('Не оплачено / доступ неактивний', 'yellow-paper-classifieds'); ?>
                        </option>
                        <option value="<?php echo esc_attr(YP_Listing_Workflow::ACCOUNT_PAYMENT_ACTIVE); ?>" <?php selected(YP_Listing_Workflow::get_account_payment_status($user->ID), YP_Listing_Workflow::ACCOUNT_PAYMENT_ACTIVE); ?>>
                            <?php esc_html_e('Оплачено / активний доступ', 'yellow-paper-classifieds'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('Поки що цей статус змінює адміністратор вручну. У майбутньому його зможе оновлювати платіжна система.', 'yellow-paper-classifieds'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_name"><?php esc_html_e('Назва магазину', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <input type="text" name="yp_store_name" id="yp_store_name" value="<?php echo esc_attr($values['store_name']); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_phone"><?php esc_html_e('Телефон', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <input type="text" name="yp_store_phone" id="yp_store_phone" value="<?php echo esc_attr($this->format_phone_for_display($values['store_phone'])); ?>" class="regular-text" placeholder="(096) 123-45-67">
                    <p class="description"><?php esc_html_e('Зберігається тільки у цифрах.', 'yellow-paper-classifieds'); ?></p>
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_whatsapp"><?php esc_html_e('WhatsApp', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <input type="text" name="yp_store_whatsapp" id="yp_store_whatsapp" value="<?php echo esc_attr($this->format_phone_for_display($values['store_whatsapp'])); ?>" class="regular-text" placeholder="(096) 123-45-67">
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_telegram"><?php esc_html_e('Telegram', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <input type="text" name="yp_store_telegram" id="yp_store_telegram" value="<?php echo esc_attr($values['store_telegram']); ?>" class="regular-text" placeholder="@shopname або https://t.me/shopname">
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_viber"><?php esc_html_e('Viber', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <input type="text" name="yp_store_viber" id="yp_store_viber" value="<?php echo esc_attr($this->format_phone_for_display($values['store_viber'])); ?>" class="regular-text" placeholder="(096) 123-45-67">
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_facebook"><?php esc_html_e('Facebook URL', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <input type="url" name="yp_store_facebook" id="yp_store_facebook" value="<?php echo esc_attr($values['store_facebook']); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_instagram"><?php esc_html_e('Instagram URL', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <input type="url" name="yp_store_instagram" id="yp_store_instagram" value="<?php echo esc_attr($values['store_instagram']); ?>" class="regular-text">
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_social_extra"><?php esc_html_e('Додаткова соцмережа URL', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <input type="url" name="yp_store_social_extra" id="yp_store_social_extra" value="<?php echo esc_attr($values['store_social_extra']); ?>" class="regular-text" placeholder="YouTube / TikTok / інше">
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_logo_id"><?php esc_html_e('Логотип магазину', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <input type="number" min="0" name="yp_store_logo_id" id="yp_store_logo_id" value="<?php echo esc_attr($values['store_logo_id']); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Поки що вкажи ID вкладення з медіатеки. Пізніше винесемо це у фронтенд-завантаження.', 'yellow-paper-classifieds'); ?></p>
                    <?php
                    if (!empty($values['store_logo_id'])) {
                        $logo_url = wp_get_attachment_image_url((int) $values['store_logo_id'], 'thumbnail');
                        if ($logo_url) {
                            echo '<p><img src="' . esc_url($logo_url) . '" alt="" style="max-width:120px;height:auto;"></p>';
                        }
                    }
                    ?>
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_address"><?php esc_html_e('Адреса', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <textarea name="yp_store_address" id="yp_store_address" rows="3" class="large-text"><?php echo esc_textarea($values['store_address']); ?></textarea>
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_work_hours"><?php esc_html_e('Години роботи', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <textarea name="yp_store_work_hours" id="yp_store_work_hours" rows="3" class="large-text"><?php echo esc_textarea($values['store_work_hours']); ?></textarea>
                </td>
            </tr>

            <tr>
                <th><label for="yp_store_website"><?php esc_html_e('Вебсайт URL', 'yellow-paper-classifieds'); ?></label></th>
                <td>
                    <input type="url" name="yp_store_website" id="yp_store_website" value="<?php echo esc_attr($values['store_website']); ?>" class="regular-text">
                </td>
            </tr>
        </table>

        <script>
            (function() {
                function formatPhoneInput(input) {
                    if (!input) {
                        return;
                    }

                    input.addEventListener('input', function() {
                        var digits = this.value.replace(/\D/g, '').slice(0, 10);
                        var formatted = '';

                        if (digits.length > 0) {
                            formatted += '(' + digits.substring(0, 3);
                        }
                        if (digits.length >= 4) {
                            formatted += ') ' + digits.substring(3, 6);
                        }
                        if (digits.length >= 7) {
                            formatted += '-' + digits.substring(6, 8);
                        }
                        if (digits.length >= 9) {
                            formatted += '-' + digits.substring(8, 10);
                        }

                        this.value = formatted;
                    });
                }

                formatPhoneInput(document.getElementById('yp_store_phone'));
                formatPhoneInput(document.getElementById('yp_store_whatsapp'));
                formatPhoneInput(document.getElementById('yp_store_viber'));
            })();
        </script>
        <?php
    }

    public function save_admin_profile_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return;
        }

        if (!isset($_POST[self::NONCE_NAME])) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::NONCE_NAME]));
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }

        $account_type       = isset($_POST['yp_account_type']) ? yp_normalize_account_type(wp_unslash($_POST['yp_account_type'])) : YP_DEFAULT_ACCOUNT_TYPE;
        $account_payment_status = isset($_POST['yp_account_payment_status']) ? YP_Listing_Workflow::normalize_account_payment_status(wp_unslash($_POST['yp_account_payment_status'])) : YP_Listing_Workflow::ACCOUNT_PAYMENT_INACTIVE;
        $store_name         = isset($_POST['yp_store_name']) ? sanitize_text_field(wp_unslash($_POST['yp_store_name'])) : '';
        $store_phone        = isset($_POST['yp_store_phone']) ? $this->normalize_phone_to_digits(wp_unslash($_POST['yp_store_phone'])) : '';
        $store_whatsapp     = isset($_POST['yp_store_whatsapp']) ? $this->normalize_phone_to_digits(wp_unslash($_POST['yp_store_whatsapp'])) : '';
        $store_telegram     = isset($_POST['yp_store_telegram']) ? sanitize_text_field(wp_unslash($_POST['yp_store_telegram'])) : '';
        $store_viber        = isset($_POST['yp_store_viber']) ? $this->normalize_phone_to_digits(wp_unslash($_POST['yp_store_viber'])) : '';
        $store_facebook     = isset($_POST['yp_store_facebook']) ? esc_url_raw(wp_unslash($_POST['yp_store_facebook'])) : '';
        $store_instagram    = isset($_POST['yp_store_instagram']) ? esc_url_raw(wp_unslash($_POST['yp_store_instagram'])) : '';
        $store_social_extra = isset($_POST['yp_store_social_extra']) ? esc_url_raw(wp_unslash($_POST['yp_store_social_extra'])) : '';
        $store_logo_id      = isset($_POST['yp_store_logo_id']) ? absint($_POST['yp_store_logo_id']) : 0;
        $store_address      = isset($_POST['yp_store_address']) ? sanitize_textarea_field(wp_unslash($_POST['yp_store_address'])) : '';
        $store_work_hours   = isset($_POST['yp_store_work_hours']) ? sanitize_textarea_field(wp_unslash($_POST['yp_store_work_hours'])) : '';
        $store_website      = isset($_POST['yp_store_website']) ? esc_url_raw(wp_unslash($_POST['yp_store_website'])) : '';

        yp_update_user_account_type($user_id, $account_type);
        update_user_meta($user_id, YP_Listing_Workflow::USER_META_ACCOUNT_PAYMENT_STATUS, $account_payment_status);

        update_user_meta($user_id, self::META_STORE_NAME, $store_name);
        update_user_meta($user_id, self::META_STORE_PHONE, $store_phone);
        update_user_meta($user_id, self::META_STORE_WHATSAPP, $store_whatsapp);
        update_user_meta($user_id, self::META_STORE_TELEGRAM, $store_telegram);
        update_user_meta($user_id, self::META_STORE_VIBER, $store_viber);
        update_user_meta($user_id, self::META_STORE_FACEBOOK, $store_facebook);
        update_user_meta($user_id, self::META_STORE_INSTAGRAM, $store_instagram);
        update_user_meta($user_id, self::META_STORE_SOCIAL_EXTRA, $store_social_extra);
        update_user_meta($user_id, self::META_STORE_LOGO_ID, $store_logo_id);
        update_user_meta($user_id, self::META_STORE_ADDRESS, $store_address);
        update_user_meta($user_id, self::META_STORE_WORK_HOURS, $store_work_hours);
        update_user_meta($user_id, self::META_STORE_WEBSITE, $store_website);
    }

    public function get_user_store_data($user_id) {
        return array(
            'store_name'         => (string) get_user_meta($user_id, self::META_STORE_NAME, true),
            'store_phone'        => (string) get_user_meta($user_id, self::META_STORE_PHONE, true),
            'store_whatsapp'     => (string) get_user_meta($user_id, self::META_STORE_WHATSAPP, true),
            'store_telegram'     => (string) get_user_meta($user_id, self::META_STORE_TELEGRAM, true),
            'store_viber'        => (string) get_user_meta($user_id, self::META_STORE_VIBER, true),
            'store_facebook'     => (string) get_user_meta($user_id, self::META_STORE_FACEBOOK, true),
            'store_instagram'    => (string) get_user_meta($user_id, self::META_STORE_INSTAGRAM, true),
            'store_social_extra' => (string) get_user_meta($user_id, self::META_STORE_SOCIAL_EXTRA, true),
            'store_logo_id'      => (int) get_user_meta($user_id, self::META_STORE_LOGO_ID, true),
            'store_address'      => (string) get_user_meta($user_id, self::META_STORE_ADDRESS, true),
            'store_work_hours'   => (string) get_user_meta($user_id, self::META_STORE_WORK_HOURS, true),
            'store_website'      => (string) get_user_meta($user_id, self::META_STORE_WEBSITE, true),
        );
    }

    public function normalize_phone_to_digits($value) {
        return preg_replace('/\D+/', '', (string) $value);
    }

    public function is_valid_phone_digits($digits) {
        return $digits === '' || (bool) preg_match('/^\d{10}$/', $digits);
    }

    public function format_phone_for_display($digits) {
        $digits = $this->normalize_phone_to_digits($digits);

        if (!$this->is_valid_phone_digits($digits) || $digits === '') {
            return '';
        }

        return sprintf(
            '(%s) %s-%s-%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 2),
            substr($digits, 8, 2)
        );
    }
}