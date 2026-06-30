<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Account {

    /**
     * @var YP_User_Profile
     */
    private $user_profile;

    const FRONT_NONCE_ACTION = 'yp_front_account_settings_save';
    const FRONT_NONCE_NAME   = 'yp_front_account_settings_nonce';
    const PAGE_SUPPORT_REQUEST = 'vidpravyty-zapyt';
    const PAGE_BANNERS_REQUEST = 'banery';

    private $errors = array();
    private $success_message = '';

    public function __construct($user_profile) {
        $this->user_profile = $user_profile;
    }

    public function hooks() {
        add_shortcode('yp_account_nav', array($this, 'render_account_nav_shortcode'));
        add_shortcode('yp_account_settings', array($this, 'render_account_settings_shortcode'));
        add_shortcode('yp_support_request', array($this, 'render_support_request_shortcode'));
        add_shortcode('yp_banners_request', array($this, 'render_banners_request_shortcode'));
        add_action('init', array($this, 'handle_front_settings_save'));
    }

    public function handle_front_settings_save() {
        if (!isset($_POST['yp_account_settings_submitted'])) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_POST[self::FRONT_NONCE_NAME])) {
            $this->errors[] = __('Помилка безпеки. Спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::FRONT_NONCE_NAME]));
        if (!wp_verify_nonce($nonce, self::FRONT_NONCE_ACTION)) {
            $this->errors[] = __('Помилка перевірки форми. Оновіть сторінку та спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $user_id = get_current_user_id();

        $store_name         = isset($_POST['yp_store_name']) ? sanitize_text_field(wp_unslash($_POST['yp_store_name'])) : '';
        $store_phone        = isset($_POST['yp_store_phone']) ? $this->user_profile->normalize_phone_to_digits(wp_unslash($_POST['yp_store_phone'])) : '';
        $store_whatsapp     = isset($_POST['yp_store_whatsapp']) ? $this->user_profile->normalize_phone_to_digits(wp_unslash($_POST['yp_store_whatsapp'])) : '';
        $store_telegram     = isset($_POST['yp_store_telegram']) ? sanitize_text_field(wp_unslash($_POST['yp_store_telegram'])) : '';
        $store_viber        = isset($_POST['yp_store_viber']) ? $this->user_profile->normalize_phone_to_digits(wp_unslash($_POST['yp_store_viber'])) : '';
        $store_facebook     = isset($_POST['yp_store_facebook']) ? esc_url_raw(wp_unslash($_POST['yp_store_facebook'])) : '';
        $store_instagram    = isset($_POST['yp_store_instagram']) ? esc_url_raw(wp_unslash($_POST['yp_store_instagram'])) : '';
        $store_social_extra = isset($_POST['yp_store_social_extra']) ? esc_url_raw(wp_unslash($_POST['yp_store_social_extra'])) : '';
        $store_logo_id      = isset($_POST['yp_store_logo_id']) ? absint($_POST['yp_store_logo_id']) : 0;
        $store_address      = isset($_POST['yp_store_address']) ? sanitize_textarea_field(wp_unslash($_POST['yp_store_address'])) : '';
        $store_work_hours   = isset($_POST['yp_store_work_hours']) ? sanitize_textarea_field(wp_unslash($_POST['yp_store_work_hours'])) : '';
        $store_website      = isset($_POST['yp_store_website']) ? esc_url_raw(wp_unslash($_POST['yp_store_website'])) : '';

        if (!$this->user_profile->is_valid_phone_digits($store_phone)) {
            $this->errors[] = __('Телефон магазину повинен містити 10 цифр.', 'yellow-paper-classifieds');
        }

        if (!$this->user_profile->is_valid_phone_digits($store_whatsapp)) {
            $this->errors[] = __('WhatsApp повинен містити 10 цифр.', 'yellow-paper-classifieds');
        }

        if (!$this->user_profile->is_valid_phone_digits($store_viber)) {
            $this->errors[] = __('Viber повинен містити 10 цифр.', 'yellow-paper-classifieds');
        }

        if (!empty($this->errors)) {
            return;
        }

        update_user_meta($user_id, YP_User_Profile::META_STORE_NAME, $store_name);
        update_user_meta($user_id, YP_User_Profile::META_STORE_PHONE, $store_phone);
        update_user_meta($user_id, YP_User_Profile::META_STORE_WHATSAPP, $store_whatsapp);
        update_user_meta($user_id, YP_User_Profile::META_STORE_TELEGRAM, $store_telegram);
        update_user_meta($user_id, YP_User_Profile::META_STORE_VIBER, $store_viber);
        update_user_meta($user_id, YP_User_Profile::META_STORE_FACEBOOK, $store_facebook);
        update_user_meta($user_id, YP_User_Profile::META_STORE_INSTAGRAM, $store_instagram);
        update_user_meta($user_id, YP_User_Profile::META_STORE_SOCIAL_EXTRA, $store_social_extra);
        update_user_meta($user_id, YP_User_Profile::META_STORE_LOGO_ID, $store_logo_id);
        update_user_meta($user_id, YP_User_Profile::META_STORE_ADDRESS, $store_address);
        update_user_meta($user_id, YP_User_Profile::META_STORE_WORK_HOURS, $store_work_hours);
        update_user_meta($user_id, YP_User_Profile::META_STORE_WEBSITE, $store_website);

        $redirect = $this->get_settings_page_url();
        if ($redirect) {
            $redirect = add_query_arg('yp_settings_saved', '1', $redirect);
            wp_safe_redirect($redirect);
            exit;
        }

        $this->success_message = __('Налаштування збережено.', 'yellow-paper-classifieds');
    }

    public function render_account_nav_shortcode() {
        return self::render_account_nav();
    }

    public static function render_account_nav() {
        if (!is_user_logged_in()) {
            return '';
        }

        $settings_url    = self::get_page_url_by_path_static('nalashtuvannya');
        $my_listings_url = self::get_page_url_by_path_static('moi-ogoloshennya');
        $submit_url      = self::get_page_url_by_path_static('podaty-ogoloshennya');
        $support_url     = self::get_page_url_by_path_static(self::PAGE_SUPPORT_REQUEST);
        $banners_url     = self::get_page_url_by_path_static(self::PAGE_BANNERS_REQUEST);
        $logout_url      = wp_logout_url(self::get_page_url_by_path_static('uviyty'));

        $my_listings_class = self::is_current_account_page('moi-ogoloshennya') ? 'curent current' : '';
        $submit_class      = self::is_current_account_page('podaty-ogoloshennya') ? 'curent current' : '';
        $support_class     = self::is_current_account_page(self::PAGE_SUPPORT_REQUEST) ? 'curent current' : '';
        $banners_class     = self::is_current_account_page(self::PAGE_BANNERS_REQUEST) ? 'curent current' : '';
        $settings_class    = self::is_current_account_page('nalashtuvannya') ? 'curent current' : '';

        ob_start();
        ?>
        <nav class="yp-account-nav" >
            <ul>
                <?php if ($my_listings_url) : ?>
                    <li>
                        <a class="<?php echo esc_attr($my_listings_class); ?>" href="<?php echo esc_url($my_listings_url); ?>">
                            <?php esc_html_e('Мої оголошення', 'yellow-paper-classifieds'); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($submit_url) : ?>
                    <li>
                        <a class="<?php echo esc_attr($submit_class); ?>" href="<?php echo esc_url($submit_url); ?>">
                            <?php esc_html_e('Подати оголошення', 'yellow-paper-classifieds'); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($support_url) : ?>
                    <li>
                        <a class="<?php echo esc_attr($support_class); ?>" href="<?php echo esc_url($support_url); ?>">
                            <?php esc_html_e('Відправити запит', 'yellow-paper-classifieds'); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($banners_url) : ?>
                    <li>
                        <a class="<?php echo esc_attr($banners_class); ?>" href="<?php echo esc_url($banners_url); ?>">
                            <?php esc_html_e('Банери', 'yellow-paper-classifieds'); ?>
                        </a>
                    </li>
                <?php endif; ?>

                <?php if ($settings_url) : ?>
                    <li>
                        <a class="<?php echo esc_attr($settings_class); ?>" href="<?php echo esc_url($settings_url); ?>">
                            <?php esc_html_e('Налаштування', 'yellow-paper-classifieds'); ?>
                        </a>
                    </li>
                <?php endif; ?>

<!--                <li>-->
<!--                    <a href="--><?php //echo esc_url($logout_url); ?><!--">-->
<!--                        --><?php //esc_html_e('Вийти', 'yellow-paper-classifieds'); ?>
<!--                    </a>-->
<!--                </li>-->
            </ul>
        </nav>
        <?php

        return ob_get_clean();
    }

    private static function is_current_account_page($path) {
        if (!is_page()) {
            return false;
        }

        $page = get_page_by_path($path);

        if (!$page) {
            return false;
        }

        return is_page($page->ID);
    }

    private static function get_page_url_by_path_static($path) {
        $page = get_page_by_path($path);

        if ($page) {
            return get_permalink($page->ID);
        }

        return '';
    }

    public function render_account_settings_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Щоб редагувати налаштування, увійдіть на сайт.', 'yellow-paper-classifieds') . '</p>';
        }

        $user_id = get_current_user_id();
        $values  = $this->user_profile->get_user_store_data($user_id);

        if (!empty($_GET['yp_settings_saved'])) {
            $this->success_message = __('Налаштування збережено.', 'yellow-paper-classifieds');
        }

        if (!empty($_POST['yp_account_settings_submitted'])) {
            $values['store_name']         = isset($_POST['yp_store_name']) ? sanitize_text_field(wp_unslash($_POST['yp_store_name'])) : $values['store_name'];
            $values['store_phone']        = isset($_POST['yp_store_phone']) ? $this->user_profile->normalize_phone_to_digits(wp_unslash($_POST['yp_store_phone'])) : $values['store_phone'];
            $values['store_whatsapp']     = isset($_POST['yp_store_whatsapp']) ? $this->user_profile->normalize_phone_to_digits(wp_unslash($_POST['yp_store_whatsapp'])) : $values['store_whatsapp'];
            $values['store_telegram']     = isset($_POST['yp_store_telegram']) ? sanitize_text_field(wp_unslash($_POST['yp_store_telegram'])) : $values['store_telegram'];
            $values['store_viber']        = isset($_POST['yp_store_viber']) ? $this->user_profile->normalize_phone_to_digits(wp_unslash($_POST['yp_store_viber'])) : $values['store_viber'];
            $values['store_facebook']     = isset($_POST['yp_store_facebook']) ? esc_url_raw(wp_unslash($_POST['yp_store_facebook'])) : $values['store_facebook'];
            $values['store_instagram']    = isset($_POST['yp_store_instagram']) ? esc_url_raw(wp_unslash($_POST['yp_store_instagram'])) : $values['store_instagram'];
            $values['store_social_extra'] = isset($_POST['yp_store_social_extra']) ? esc_url_raw(wp_unslash($_POST['yp_store_social_extra'])) : $values['store_social_extra'];
            $values['store_logo_id']      = isset($_POST['yp_store_logo_id']) ? absint($_POST['yp_store_logo_id']) : $values['store_logo_id'];
            $values['store_address']      = isset($_POST['yp_store_address']) ? sanitize_textarea_field(wp_unslash($_POST['yp_store_address'])) : $values['store_address'];
            $values['store_work_hours']   = isset($_POST['yp_store_work_hours']) ? sanitize_textarea_field(wp_unslash($_POST['yp_store_work_hours'])) : $values['store_work_hours'];
            $values['store_website']      = isset($_POST['yp_store_website']) ? esc_url_raw(wp_unslash($_POST['yp_store_website'])) : $values['store_website'];
        }

        ob_start();

        echo self::render_account_nav();

        if (!empty($this->errors)) {
            echo '<div style="margin-bottom:20px;padding:12px;border:1px solid #d63638;background:#fff5f5;">';
            foreach ($this->errors as $error) {
                echo '<p style="margin:0 0 8px;">' . esc_html($error) . '</p>';
            }
            echo '</div>';
        }

        if ($this->success_message !== '') {
            echo '<div style="margin-bottom:20px;padding:12px;border:1px solid #46b450;background:#f6fff6;">';
            echo '<p style="margin:0;">' . esc_html($this->success_message) . '</p>';
            echo '</div>';
        }

        $logo_url = !empty($values['store_logo_id']) ? wp_get_attachment_image_url((int) $values['store_logo_id'], 'thumbnail') : '';

        ?>
        <form method="post" class="yp-account-settings-form">
            <?php wp_nonce_field(self::FRONT_NONCE_ACTION, self::FRONT_NONCE_NAME); ?>
            <input type="hidden" name="yp_account_settings_submitted" value="1">

            <p>
                <label for="yp_store_name"><strong><?php esc_html_e('Назва магазину', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" name="yp_store_name" id="yp_store_name" value="<?php echo esc_attr($values['store_name']); ?>" style="width:100%;">
            </p>

            <p>
                <label for="yp_store_phone"><strong><?php esc_html_e('Телефон', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" name="yp_store_phone" id="yp_store_phone" value="<?php echo esc_attr($this->user_profile->format_phone_for_display($values['store_phone'])); ?>" style="width:100%;" placeholder="(096) 123-45-67">
            </p>

            <p>
                <label for="yp_store_whatsapp"><strong><?php esc_html_e('WhatsApp', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" name="yp_store_whatsapp" id="yp_store_whatsapp" value="<?php echo esc_attr($this->user_profile->format_phone_for_display($values['store_whatsapp'])); ?>" style="width:100%;" placeholder="(096) 123-45-67">
            </p>

            <p>
                <label for="yp_store_telegram"><strong><?php esc_html_e('Telegram', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" name="yp_store_telegram" id="yp_store_telegram" value="<?php echo esc_attr($values['store_telegram']); ?>" style="width:100%;" placeholder="@shopname або https://t.me/shopname">
            </p>

            <p>
                <label for="yp_store_viber"><strong><?php esc_html_e('Viber', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" name="yp_store_viber" id="yp_store_viber" value="<?php echo esc_attr($this->user_profile->format_phone_for_display($values['store_viber'])); ?>" style="width:100%;" placeholder="(096) 123-45-67">
            </p>

            <p>
                <label for="yp_store_facebook"><strong><?php esc_html_e('Facebook URL', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="url" name="yp_store_facebook" id="yp_store_facebook" value="<?php echo esc_attr($values['store_facebook']); ?>" style="width:100%;">
            </p>

            <p>
                <label for="yp_store_instagram"><strong><?php esc_html_e('Instagram URL', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="url" name="yp_store_instagram" id="yp_store_instagram" value="<?php echo esc_attr($values['store_instagram']); ?>" style="width:100%;">
            </p>

            <p>
                <label for="yp_store_social_extra"><strong><?php esc_html_e('Додаткова соцмережа URL', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="url" name="yp_store_social_extra" id="yp_store_social_extra" value="<?php echo esc_attr($values['store_social_extra']); ?>" style="width:100%;">
            </p>

            <p>
                <label for="yp_store_logo_id"><strong><?php esc_html_e('ID логотипу', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="number" min="0" name="yp_store_logo_id" id="yp_store_logo_id" value="<?php echo esc_attr($values['store_logo_id']); ?>" style="width:100%;">
                <small><?php esc_html_e('Поки що вручну через ID вкладення з медіатеки.', 'yellow-paper-classifieds'); ?></small>
            </p>

            <?php if ($logo_url) : ?>
                <p>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="" style="max-width:120px;height:auto;">
                </p>
            <?php endif; ?>

            <p>
                <label for="yp_store_address"><strong><?php esc_html_e('Адреса', 'yellow-paper-classifieds'); ?></strong></label><br>
                <textarea name="yp_store_address" id="yp_store_address" rows="3" style="width:100%;"><?php echo esc_textarea($values['store_address']); ?></textarea>
            </p>

            <p>
                <label for="yp_store_work_hours"><strong><?php esc_html_e('Години роботи', 'yellow-paper-classifieds'); ?></strong></label><br>
                <textarea name="yp_store_work_hours" id="yp_store_work_hours" rows="3" style="width:100%;"><?php echo esc_textarea($values['store_work_hours']); ?></textarea>
            </p>

            <p>
                <label for="yp_store_website"><strong><?php esc_html_e('Вебсайт URL', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="url" name="yp_store_website" id="yp_store_website" value="<?php echo esc_attr($values['store_website']); ?>" style="width:100%;">
            </p>

            <p>
                <button type="submit"><?php esc_html_e('Зберегти налаштування', 'yellow-paper-classifieds'); ?></button>
            </p>
        </form>

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

        return ob_get_clean();
    }


    public function render_support_request_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Щоб відправити запит, увійдіть на сайт.', 'yellow-paper-classifieds') . '</p>';
        }

        $current_user = wp_get_current_user();
        $form_id = class_exists('YP_Admin') ? (int) get_option(YP_Admin::OPTION_SUPPORT_REQUEST_FORM_ID, 0) : 0;

        ob_start();

        echo self::render_account_nav();
        ?>
        <div class="yp-support-request">
            <h1><?php esc_html_e('Відправити запит', 'yellow-paper-classifieds'); ?></h1>
            <p>
                <?php esc_html_e('Якщо у вас є питання щодо роботи з оголошеннями, оплати, категорій або ви хочете запропонувати нову категорію — надішліть нам повідомлення через форму нижче.', 'yellow-paper-classifieds'); ?>
            </p>

            <?php if ($form_id > 0 && function_exists('gravity_form')) : ?>
                <?php
                $field_values = array(
                    'user_id'      => get_current_user_id(),
                    'user_email'   => $current_user instanceof WP_User ? $current_user->user_email : '',
                    'user_name'    => $current_user instanceof WP_User ? $current_user->display_name : '',
                    'account_type' => yp_get_user_account_type(get_current_user_id()),
                );

                gravity_form($form_id, false, false, false, $field_values, true);
                ?>
            <?php else : ?>
                <?php if (current_user_can('manage_options')) : ?>
                    <p class="yp-support-request__notice">
                        <?php esc_html_e('Форма не налаштована. Вкажіть ID Gravity Form у налаштуваннях плагіна.', 'yellow-paper-classifieds'); ?>
                    </p>
                <?php else : ?>
                    <p class="yp-support-request__notice">
                        <?php esc_html_e('Форма тимчасово недоступна. Спробуйте пізніше.', 'yellow-paper-classifieds'); ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }


    public function render_banners_request_shortcode() {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Щоб замовити розміщення банера, увійдіть на сайт.', 'yellow-paper-classifieds') . '</p>';
        }

        $current_user = wp_get_current_user();
        $form_id = class_exists('YP_Admin') ? (int) get_option(YP_Admin::OPTION_BANNERS_REQUEST_FORM_ID, 0) : 0;
        $description = class_exists('YP_Admin') ? get_option(YP_Admin::OPTION_BANNERS_REQUEST_DESCRIPTION, '') : '';

        if ($description === '') {
            $description = __('На цій сторінці ви можете залишити заявку на розміщення рекламного банера. Опишіть, який банер хочете розмістити, бажані дати показу та контактні дані для зв’язку.', 'yellow-paper-classifieds');
        }

        ob_start();

        echo self::render_account_nav();
        ?>
        <div class="yp-banners-request yp-support-request">
            <h1><?php esc_html_e('Банери', 'yellow-paper-classifieds'); ?></h1>
            <div class="yp-banners-request__description">
                <?php echo wp_kses_post(wpautop($description)); ?>
            </div>

            <?php if ($form_id > 0 && function_exists('gravity_form')) : ?>
                <?php
                $field_values = array(
                    'user_id'      => get_current_user_id(),
                    'user_email'   => $current_user instanceof WP_User ? $current_user->user_email : '',
                    'user_name'    => $current_user instanceof WP_User ? $current_user->display_name : '',
                    'account_type' => yp_get_user_account_type(get_current_user_id()),
                );

                gravity_form($form_id, false, false, false, $field_values, true);
                ?>
            <?php else : ?>
                <?php if (current_user_can('manage_options')) : ?>
                    <p class="yp-support-request__notice">
                        <?php esc_html_e('Форма банерів не налаштована. Вкажіть ID Gravity Form у налаштуваннях плагіна.', 'yellow-paper-classifieds'); ?>
                    </p>
                <?php else : ?>
                    <p class="yp-support-request__notice">
                        <?php esc_html_e('Форма тимчасово недоступна. Спробуйте пізніше.', 'yellow-paper-classifieds'); ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    private function get_settings_page_url() {
        return $this->get_page_url_by_path('nalashtuvannya');
    }

    private function get_page_url_by_path($path) {
        $page = get_page_by_path($path);

        if ($page) {
            return get_permalink($page->ID);
        }

        return '';
    }
}