<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Auth {

    const LOGIN_NONCE_ACTION    = 'yp_auth_login';
    const LOGIN_NONCE_NAME      = 'yp_auth_login_nonce';
    const REGISTER_NONCE_ACTION = 'yp_auth_register';
    const REGISTER_NONCE_NAME   = 'yp_auth_register_nonce';
    const LOST_NONCE_ACTION     = 'yp_auth_lost_password';
    const LOST_NONCE_NAME       = 'yp_auth_lost_password_nonce';
    const RESET_NONCE_ACTION    = 'yp_auth_reset_password';
    const RESET_NONCE_NAME      = 'yp_auth_reset_password_nonce';

    const PAGE_LOGIN    = 'uviyty';
    const PAGE_REGISTER = 'reyestratsiya';
    const PAGE_LOST     = 'ponovyty-parol';
    const PAGE_RESET    = 'stvorennya-parolya';
    const PAGE_ACCOUNT  = 'nalashtuvannya';
    const PAGE_LISTINGS = 'moi-ogoloshennya';
    const PAGE_SUBMIT   = 'podaty-ogoloshennya';
    const PAGE_SUPPORT  = 'vidpravyty-zapyt';
    const PAGE_BANNERS  = 'banery';

    private $errors = array();
    private $success_message = '';

    public function hooks() {
        add_shortcode('yp_login_form', array($this, 'render_login_form_shortcode'));
        add_shortcode('yp_register_form', array($this, 'render_register_form_shortcode'));
        add_shortcode('yp_lost_password_form', array($this, 'render_lost_password_form_shortcode'));
        add_shortcode('yp_reset_password_form', array($this, 'render_reset_password_form_shortcode'));
        add_shortcode('yp_auth_links', array($this, 'render_auth_links_shortcode'));

        add_action('init', array($this, 'handle_login'));
        add_action('init', array($this, 'handle_register'));
        add_action('init', array($this, 'handle_lost_password'));
        add_action('init', array($this, 'handle_reset_password'));
        add_action('wp_logout', array($this, 'redirect_after_logout'));
    }

    public static function create_default_pages() {
        $pages = array(
            self::PAGE_LOGIN => array(
                'title'   => __('Вхід в акаунт', 'yellow-paper-classifieds'),
                'content' => '[yp_login_form]',
            ),
            self::PAGE_REGISTER => array(
                'title'   => __('Реєстрація', 'yellow-paper-classifieds'),
                'content' => '[yp_register_form]',
            ),
            self::PAGE_LOST => array(
                'title'   => __('Поновлення пароля', 'yellow-paper-classifieds'),
                'content' => '[yp_lost_password_form]',
            ),
            self::PAGE_RESET => array(
                'title'   => __('Створення нового пароля', 'yellow-paper-classifieds'),
                'content' => '[yp_reset_password_form]',
            ),
            self::PAGE_SUPPORT => array(
                'title'   => __('Відправити запит', 'yellow-paper-classifieds'),
                'content' => '[yp_support_request]',
            ),
            self::PAGE_BANNERS => array(
                'title'   => __('Банери', 'yellow-paper-classifieds'),
                'content' => '[yp_banners_request]',
            ),
        );

        foreach ($pages as $slug => $page_data) {
            $existing_page = get_page_by_path($slug);

            if ($existing_page) {
                continue;
            }

            wp_insert_post(array(
                'post_title'   => $page_data['title'],
                'post_name'    => $slug,
                'post_content' => $page_data['content'],
                'post_status'  => 'publish',
                'post_type'    => 'page',
            ));
        }
    }

    public function handle_login() {
        if (!isset($_POST['yp_login_submitted'])) {
            return;
        }

        if (!isset($_POST[self::LOGIN_NONCE_NAME])) {
            $this->errors[] = __('Помилка безпеки. Спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::LOGIN_NONCE_NAME]));
        if (!wp_verify_nonce($nonce, self::LOGIN_NONCE_ACTION)) {
            $this->errors[] = __('Помилка перевірки форми. Оновіть сторінку та спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $login    = isset($_POST['yp_user_login']) ? sanitize_text_field(wp_unslash($_POST['yp_user_login'])) : '';
        $password = isset($_POST['yp_user_password']) ? (string) wp_unslash($_POST['yp_user_password']) : '';
        $remember = !empty($_POST['yp_remember']);

        if ($login === '' || $password === '') {
            $this->errors[] = __('Вкажіть email або логін та пароль.', 'yellow-paper-classifieds');
            return;
        }

        $user = wp_signon(array(
            'user_login'    => $login,
            'user_password' => $password,
            'remember'      => $remember,
        ), is_ssl());

        if (is_wp_error($user)) {
            $this->errors[] = __('Невірний логін або пароль.', 'yellow-paper-classifieds');
            return;
        }

        $redirect_url = $this->get_safe_redirect_url($this->get_account_redirect_url());
        wp_safe_redirect($redirect_url);
        exit;
    }

    public function handle_register() {
        if (!isset($_POST['yp_register_submitted'])) {
            return;
        }
        if (class_exists('YP_Admin') && !YP_Admin::is_registration_enabled()) {
            $this->errors[] = __('Реєстрація нових користувачів тимчасово закрита.', 'yellow-paper-classifieds');
            return;
        }

        if (!isset($_POST[self::REGISTER_NONCE_NAME])) {
            $this->errors[] = __('Помилка безпеки. Спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::REGISTER_NONCE_NAME]));
        if (!wp_verify_nonce($nonce, self::REGISTER_NONCE_ACTION)) {
            $this->errors[] = __('Помилка перевірки форми. Оновіть сторінку та спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        if (!empty($_POST['yp_company_website'])) {
            return;
        }

        $email      = isset($_POST['yp_email']) ? sanitize_email(wp_unslash($_POST['yp_email'])) : '';
        $first_name = isset($_POST['yp_first_name']) ? sanitize_text_field(wp_unslash($_POST['yp_first_name'])) : '';
        $last_name  = isset($_POST['yp_last_name']) ? sanitize_text_field(wp_unslash($_POST['yp_last_name'])) : '';
        $phone      = isset($_POST['yp_phone']) ? sanitize_text_field(wp_unslash($_POST['yp_phone'])) : '';
        $password   = isset($_POST['yp_password']) ? (string) wp_unslash($_POST['yp_password']) : '';
        $password2  = isset($_POST['yp_password_confirm']) ? (string) wp_unslash($_POST['yp_password_confirm']) : '';
        $accept_terms = !empty($_POST['yp_accept_terms']);
        $account_type_raw = isset($_POST['yp_account_type']) ? sanitize_key(wp_unslash($_POST['yp_account_type'])) : '';
        $account_types = yp_get_account_types();
        $account_type = isset($account_types[$account_type_raw]) ? $account_type_raw : '';

        $first_name = trim($first_name);
        $last_name  = trim($last_name);
        $display_name = trim($first_name . ' ' . $last_name);
        $phone_digits = preg_replace('/\D+/', '', $phone);

        if (!is_email($email)) {
            $this->errors[] = __('Вкажіть коректний email.', 'yellow-paper-classifieds');
        }

        if ($email !== '' && email_exists($email)) {
            $this->errors[] = __('Користувач з таким email вже існує.', 'yellow-paper-classifieds');
        }

        if ($first_name === '') {
            $this->errors[] = __('Вкажіть імʼя.', 'yellow-paper-classifieds');
        }

        if ($last_name === '') {
            $this->errors[] = __('Вкажіть прізвище.', 'yellow-paper-classifieds');
        }

        if ($phone_digits === '') {
            $this->errors[] = __('Вкажіть номер телефону.', 'yellow-paper-classifieds');
        } elseif (!preg_match('/^\d{10}$/', $phone_digits)) {
            $this->errors[] = __('Телефон повинен містити 10 цифр у форматі (XXX) XXX-XX-XX.', 'yellow-paper-classifieds');
        }

        if (strlen($password) < 8) {
            $this->errors[] = __('Пароль повинен містити мінімум 8 символів.', 'yellow-paper-classifieds');
        }

        if ($password !== $password2) {
            $this->errors[] = __('Паролі не співпадають.', 'yellow-paper-classifieds');
        }

        if (!$accept_terms) {
            $this->errors[] = __('Підтвердьте згоду з правилами сайту.', 'yellow-paper-classifieds');
        }

        if ($account_type === '') {
            $this->errors[] = __('Оберіть тип акаунта.', 'yellow-paper-classifieds');
        }

        if (!empty($this->errors)) {
            return;
        }

        $username = $this->generate_username_from_email($email);
        $user_id  = wp_insert_user(array(
            'user_login'   => $username,
            'user_email'   => $email,
            'user_pass'    => $password,
            'display_name' => $display_name,
            'nickname'     => $display_name,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'role'         => YP_Roles::ROLE_LISTING_USER,
        ));

        if (is_wp_error($user_id)) {
            $this->errors[] = $user_id->get_error_message();
            return;
        }

        update_user_meta($user_id, YP_User_Profile::META_STORE_NAME, $display_name);
        update_user_meta($user_id, YP_User_Profile::META_STORE_PHONE, $phone_digits);
        yp_update_user_account_type($user_id, $account_type);

        $user = get_user_by('id', $user_id);
        $this->send_registration_notifications($user);

        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true, is_ssl());
        do_action('wp_login', $user->user_login, $user);

        $redirect_url = add_query_arg('yp_registered', '1', $this->get_account_redirect_url());
        wp_safe_redirect($redirect_url);
        exit;
    }

    public function handle_lost_password() {
        if (!isset($_POST['yp_lost_password_submitted'])) {
            return;
        }

        if (!isset($_POST[self::LOST_NONCE_NAME])) {
            $this->errors[] = __('Помилка безпеки. Спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::LOST_NONCE_NAME]));
        if (!wp_verify_nonce($nonce, self::LOST_NONCE_ACTION)) {
            $this->errors[] = __('Помилка перевірки форми. Оновіть сторінку та спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $login_or_email = isset($_POST['yp_user_login']) ? sanitize_text_field(wp_unslash($_POST['yp_user_login'])) : '';

        if ($login_or_email === '') {
            $this->errors[] = __('Вкажіть email або логін.', 'yellow-paper-classifieds');
            return;
        }

        $user = is_email($login_or_email) ? get_user_by('email', $login_or_email) : get_user_by('login', $login_or_email);

        if ($user) {
            $reset_key = get_password_reset_key($user);

            if (!is_wp_error($reset_key)) {
                $this->send_password_reset_email($user, $reset_key);
            }
        }

        $redirect_url = add_query_arg('yp_reset_sent', '1', $this->get_page_url_by_path(self::PAGE_LOST));
        wp_safe_redirect($redirect_url);
        exit;
    }

    public function handle_reset_password() {
        if (!isset($_POST['yp_reset_password_submitted'])) {
            return;
        }

        if (!isset($_POST[self::RESET_NONCE_NAME])) {
            $this->errors[] = __('Помилка безпеки. Спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::RESET_NONCE_NAME]));
        if (!wp_verify_nonce($nonce, self::RESET_NONCE_ACTION)) {
            $this->errors[] = __('Помилка перевірки форми. Оновіть сторінку та спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $login     = isset($_POST['yp_login']) ? sanitize_text_field(wp_unslash($_POST['yp_login'])) : '';
        $key       = isset($_POST['yp_key']) ? sanitize_text_field(wp_unslash($_POST['yp_key'])) : '';
        $password  = isset($_POST['yp_password']) ? (string) wp_unslash($_POST['yp_password']) : '';
        $password2 = isset($_POST['yp_password_confirm']) ? (string) wp_unslash($_POST['yp_password_confirm']) : '';

        $user = check_password_reset_key($key, $login);

        if (is_wp_error($user)) {
            $this->errors[] = __('Посилання для зміни пароля недійсне або застаріле.', 'yellow-paper-classifieds');
            return;
        }

        if (strlen($password) < 8) {
            $this->errors[] = __('Новий пароль повинен містити мінімум 8 символів.', 'yellow-paper-classifieds');
        }

        if ($password !== $password2) {
            $this->errors[] = __('Паролі не співпадають.', 'yellow-paper-classifieds');
        }

        if (!empty($this->errors)) {
            return;
        }

        reset_password($user, $password);
        $this->send_password_changed_email($user);

        $login_url = add_query_arg('yp_password_changed', '1', $this->get_page_url_by_path(self::PAGE_LOGIN));
        wp_safe_redirect($login_url);
        exit;
    }

    public function render_login_form_shortcode() {
        if (is_user_logged_in()) {
            return $this->render_logged_in_notice();
        }

        if (!empty($_GET['yp_password_changed'])) {
            $this->success_message = __('Пароль змінено. Тепер ви можете увійти.', 'yellow-paper-classifieds');
        }

        ob_start();
        $this->render_messages();
        ?>
        <form method="post" class="yp-auth-form yp-login-form">
            <?php wp_nonce_field(self::LOGIN_NONCE_ACTION, self::LOGIN_NONCE_NAME); ?>
            <input type="hidden" name="yp_login_submitted" value="1">

            <p>
                <label for="yp_user_login"><strong><?php esc_html_e('Email або логін', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" name="yp_user_login" id="yp_user_login" value="<?php echo isset($_POST['yp_user_login']) ? esc_attr(wp_unslash($_POST['yp_user_login'])) : ''; ?>" autocomplete="username" required style="width:100%;">
            </p>

            <p>
                <label for="yp_user_password"><strong><?php esc_html_e('Пароль', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="password" name="yp_user_password" id="yp_user_password" autocomplete="current-password" required style="width:100%;">
            </p>

            <p>
                <label>
                    <input type="checkbox" name="yp_remember" value="1">
                    <?php esc_html_e('Запамʼятати мене', 'yellow-paper-classifieds'); ?>
                </label>
            </p>

            <p>
                <button type="submit"><?php esc_html_e('Увійти', 'yellow-paper-classifieds'); ?></button>
            </p>

            <p>
                <a href="<?php echo esc_url($this->get_page_url_by_path(self::PAGE_LOST)); ?>"><?php esc_html_e('Забули пароль?', 'yellow-paper-classifieds'); ?></a>
                <?php if ($this->get_page_url_by_path(self::PAGE_REGISTER)) : ?>
                    <span> | </span>
                    <a href="<?php echo esc_url($this->get_page_url_by_path(self::PAGE_REGISTER)); ?>"><?php esc_html_e('Створити акаунт', 'yellow-paper-classifieds'); ?></a>
                <?php endif; ?>
            </p>
        </form>
        <?php
        return ob_get_clean();
    }

    public function render_register_form_shortcode() {
        if (is_user_logged_in()) {
            return $this->render_logged_in_notice();
        }

        if (class_exists('YP_Admin') && !YP_Admin::is_registration_enabled()) {
            ob_start();
            ?>
            <div class="yp-auth-message yp-auth-message--info" style="margin-bottom:20px;padding:12px;border:1px solid #72aee6;background:#f0f6fc;">
                <p style="margin:0;">
                    <?php esc_html_e('Реєстрація нових користувачів тимчасово закрита.', 'yellow-paper-classifieds'); ?>
                </p>
            </div>

            <p>
                <a href="<?php echo esc_url($this->get_page_url_by_path(self::PAGE_LOGIN)); ?>">
                    <?php esc_html_e('Повернутися до входу', 'yellow-paper-classifieds'); ?>
                </a>
            </p>
            <?php
            return ob_get_clean();
        }

        ob_start();
        $this->render_messages();
        ?>
        <form method="post" class="yp-auth-form yp-register-form">
            <?php wp_nonce_field(self::REGISTER_NONCE_ACTION, self::REGISTER_NONCE_NAME); ?>
            <input type="hidden" name="yp_register_submitted" value="1">
            <p style="display:none;">
                <label for="yp_company_website">Website</label>
                <input type="text" name="yp_company_website" id="yp_company_website" value="" tabindex="-1" autocomplete="off">
            </p>

            <p>
                <label for="yp_first_name"><strong><?php esc_html_e('Імʼя', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" name="yp_first_name" id="yp_first_name" value="<?php echo isset($_POST['yp_first_name']) ? esc_attr(wp_unslash($_POST['yp_first_name'])) : ''; ?>" autocomplete="given-name" required style="width:100%;">
            </p>

            <p>
                <label for="yp_last_name"><strong><?php esc_html_e('Прізвище', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" name="yp_last_name" id="yp_last_name" value="<?php echo isset($_POST['yp_last_name']) ? esc_attr(wp_unslash($_POST['yp_last_name'])) : ''; ?>" autocomplete="family-name" required style="width:100%;">
            </p>

            <p>
                <label for="yp_email"><strong><?php esc_html_e('Email', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="email" name="yp_email" id="yp_email" value="<?php echo isset($_POST['yp_email']) ? esc_attr(wp_unslash($_POST['yp_email'])) : ''; ?>" autocomplete="email" required style="width:100%;">
            </p>

            <p>
                <label for="yp_phone"><strong><?php esc_html_e('Телефон', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" name="yp_phone" id="yp_phone" value="<?php echo isset($_POST['yp_phone']) ? esc_attr(wp_unslash($_POST['yp_phone'])) : ''; ?>" autocomplete="tel" inputmode="numeric" maxlength="15" required style="width:100%;" placeholder="(096) 123-45-67">
            </p>

            <p>
                <label for="yp_account_type"><strong><?php esc_html_e('Тип акаунта', 'yellow-paper-classifieds'); ?></strong></label><br>
                <select name="yp_account_type" id="yp_account_type" required style="width:100%;">
                    <option value=""><?php esc_html_e('Оберіть тип акаунта', 'yellow-paper-classifieds'); ?></option>
                    <?php foreach (yp_get_account_types() as $account_type_slug => $account_type_label) : ?>
                        <option value="<?php echo esc_attr($account_type_slug); ?>" <?php selected(isset($_POST['yp_account_type']) ? sanitize_key(wp_unslash($_POST['yp_account_type'])) : '', $account_type_slug); ?>>
                            <?php echo esc_html($account_type_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <p>
                <label for="yp_password"><strong><?php esc_html_e('Пароль', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="password" name="yp_password" id="yp_password" autocomplete="new-password" required minlength="8" style="width:100%;">
            </p>

            <p>
                <label for="yp_password_confirm"><strong><?php esc_html_e('Повторіть пароль', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="password" name="yp_password_confirm" id="yp_password_confirm" autocomplete="new-password" required minlength="8" style="width:100%;">
            </p>

            <p>
                <label>
                    <input type="checkbox" name="yp_accept_terms" value="1" required>
                    <?php esc_html_e('Я погоджуюся з правилами сайту.', 'yellow-paper-classifieds'); ?>
                </label>
            </p>

            <p>
                <button type="submit"><?php esc_html_e('Зареєструватися', 'yellow-paper-classifieds'); ?></button>
            </p>

            <p>
                <a href="<?php echo esc_url($this->get_page_url_by_path(self::PAGE_LOGIN)); ?>"><?php esc_html_e('Вже маєте акаунт? Увійти', 'yellow-paper-classifieds'); ?></a>
            </p>
        </form>

        <script>
            (function() {
                var phoneInput = document.getElementById('yp_phone');

                if (!phoneInput) {
                    return;
                }

                phoneInput.addEventListener('input', function() {
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
            })();
        </script>
        <?php
        return ob_get_clean();
    }

    public function render_lost_password_form_shortcode() {
        if (is_user_logged_in()) {
            return $this->render_logged_in_notice();
        }

        if (!empty($_GET['yp_reset_sent'])) {
            $this->success_message = __('Якщо акаунт з такими даними існує, ми надіслали інструкцію на email.', 'yellow-paper-classifieds');
        }

        ob_start();
        $this->render_messages();
        ?>
        <form method="post" class="yp-auth-form yp-lost-password-form">
            <?php wp_nonce_field(self::LOST_NONCE_ACTION, self::LOST_NONCE_NAME); ?>
            <input type="hidden" name="yp_lost_password_submitted" value="1">

            <p>
                <label for="yp_user_login"><strong><?php esc_html_e('Email або логін', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" name="yp_user_login" id="yp_user_login" autocomplete="username" required style="width:100%;">
            </p>

            <p>
                <button type="submit"><?php esc_html_e('Надіслати посилання', 'yellow-paper-classifieds'); ?></button>
            </p>

            <p>
                <a href="<?php echo esc_url($this->get_page_url_by_path(self::PAGE_LOGIN)); ?>"><?php esc_html_e('Повернутися до входу', 'yellow-paper-classifieds'); ?></a>
            </p>
        </form>
        <?php
        return ob_get_clean();
    }

    public function render_reset_password_form_shortcode() {
        if (is_user_logged_in()) {
            return $this->render_logged_in_notice();
        }

        $key   = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
        $login = isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';

        $user = ($key !== '' && $login !== '') ? check_password_reset_key($key, $login) : new WP_Error('missing_key');

        ob_start();
        $this->render_messages();

        if (is_wp_error($user)) : ?>
            <p><?php esc_html_e('Посилання для створення нового пароля недійсне або застаріле.', 'yellow-paper-classifieds'); ?></p>
            <p><a href="<?php echo esc_url($this->get_page_url_by_path(self::PAGE_LOST)); ?>"><?php esc_html_e('Запросити нове посилання', 'yellow-paper-classifieds'); ?></a></p>
        <?php else : ?>
            <form method="post" class="yp-auth-form yp-reset-password-form">
                <?php wp_nonce_field(self::RESET_NONCE_ACTION, self::RESET_NONCE_NAME); ?>
                <input type="hidden" name="yp_reset_password_submitted" value="1">
                <input type="hidden" name="yp_login" value="<?php echo esc_attr($login); ?>">
                <input type="hidden" name="yp_key" value="<?php echo esc_attr($key); ?>">

                <p>
                    <label for="yp_password"><strong><?php esc_html_e('Новий пароль', 'yellow-paper-classifieds'); ?></strong></label><br>
                    <input type="password" name="yp_password" id="yp_password" autocomplete="new-password" required minlength="8" style="width:100%;">
                </p>

                <p>
                    <label for="yp_password_confirm"><strong><?php esc_html_e('Повторіть новий пароль', 'yellow-paper-classifieds'); ?></strong></label><br>
                    <input type="password" name="yp_password_confirm" id="yp_password_confirm" autocomplete="new-password" required minlength="8" style="width:100%;">
                </p>

                <p>
                    <button type="submit"><?php esc_html_e('Зберегти новий пароль', 'yellow-paper-classifieds'); ?></button>
                </p>
            </form>
        <?php endif;

        return ob_get_clean();
    }

    public function render_auth_links_shortcode($atts = array()) {
        $atts = shortcode_atts(array(
                'logged_out_text' => __('Увійти в акаунт', 'yellow-paper-classifieds'),
                'logged_in_text'  => __('Мій акаунт', 'yellow-paper-classifieds'),
                'logout_text'     => __('Вийти', 'yellow-paper-classifieds'),
                'class'           => 'yp-auth-link',
        ), $atts, 'yp_auth_links');

        $icon = '';
        $text = '';
        $url  = '';

        if (is_user_logged_in()) {
            if ($this->is_account_page()) {
                $url  = wp_logout_url($this->get_page_url_by_path(self::PAGE_LOGIN));
                $text = $atts['logout_text'];

                // Logout icon
                $icon = '
                <svg class="yp-auth-link__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                    <path d="M15 3H7C5.89543 3 5 3.89543 5 5V19C5 20.1046 5.89543 21 7 21H15"
                          stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M18 8L22 12L18 16"
                          stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M22 12H11"
                          stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>';
            } else {
                $url  = $this->get_account_redirect_url();
                $text = $atts['logged_in_text'];

                // Account icon
                $icon = '
                <svg class="yp-auth-link__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 12C14.7614 12 17 9.76142 17 7C17 4.23858 14.7614 2 12 2C9.23858 2 7 4.23858 7 7C7 9.76142 9.23858 12 12 12Z"
                          stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M4 22C4 17.5817 7.58172 14 12 14C16.4183 14 20 17.5817 20 22"
                          stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>';
            }
        } else {
            $url  = $this->get_page_url_by_path(self::PAGE_LOGIN);
            $text = $atts['logged_out_text'];

            // Login icon
            $icon = '
            <svg class="yp-auth-link__icon" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
                <path d="M9 3H17C18.1046 3 19 3.89543 19 5V19C19 20.1046 18.1046 21 17 21H9"
                      stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M6 8L2 12L6 16"
                      stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M2 12H13"
                      stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>';
        }

        if (!$url) {
            $url = home_url('/');
        }

        return '<a class="' . esc_attr($atts['class']) . '" href="' . esc_url($url) . '" aria-label="' . esc_attr($text) . '">' .
                $icon .
                '<span class="yp-auth-link__text">' . esc_html($text) . '</span>' .
                '</a>';
    }


    private function is_account_page() {
        if (!is_page()) {
            return false;
        }

        $account_pages = array(
                self::PAGE_ACCOUNT,
                self::PAGE_LISTINGS,
                self::PAGE_SUBMIT,
        );

        foreach ($account_pages as $page_slug) {
            $page = get_page_by_path($page_slug);

            if ($page && is_page($page->ID)) {
                return true;
            }
        }

        return false;
    }

    public function redirect_after_logout() {
        $login_url = $this->get_page_url_by_path(self::PAGE_LOGIN);

        if ($login_url) {
            wp_safe_redirect(add_query_arg('yp_logged_out', '1', $login_url));
            exit;
        }
    }

    private function render_logged_in_notice() {
        $account_url = $this->get_account_redirect_url();
        $logout_url  = wp_logout_url($this->get_page_url_by_path(self::PAGE_LOGIN));

        ob_start();
        ?>
        <p><?php esc_html_e('Ви вже увійшли в акаунт.', 'yellow-paper-classifieds'); ?></p>
        <p>
            <a href="<?php echo esc_url($account_url); ?>"><?php esc_html_e('Перейти в акаунт', 'yellow-paper-classifieds'); ?></a>
            <span> | </span>
            <a href="<?php echo esc_url($logout_url); ?>"><?php esc_html_e('Вийти', 'yellow-paper-classifieds'); ?></a>
        </p>
        <?php
        return ob_get_clean();
    }

    private function render_messages() {
        if (!empty($this->errors)) {
            echo '<div class="yp-auth-message yp-auth-message--error" style="margin-bottom:20px;padding:12px;border:1px solid #d63638;background:#fff5f5;">';
            foreach ($this->errors as $error) {
                echo '<p style="margin:0 0 8px;">' . esc_html($error) . '</p>';
            }
            echo '</div>';
        }

        if ($this->success_message !== '') {
            echo '<div class="yp-auth-message yp-auth-message--success" style="margin-bottom:20px;padding:12px;border:1px solid #46b450;background:#f6fff6;">';
            echo '<p style="margin:0;">' . esc_html($this->success_message) . '</p>';
            echo '</div>';
        }
    }

    private function send_registration_notifications($user) {
        if (!$user instanceof WP_User) {
            return;
        }

        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $login_url = $this->get_page_url_by_path(self::PAGE_LOGIN);

        $user_subject = sprintf(__('Ваш акаунт на сайті %s створено', 'yellow-paper-classifieds'), $site_name);
        $user_message = sprintf(__('Вітаємо, %s!', 'yellow-paper-classifieds'), $user->display_name) . "\n\n";
        $user_message .= __('Ваш акаунт для додавання оголошень успішно створено.', 'yellow-paper-classifieds') . "\n";
        $user_message .= __('Вхід в акаунт:', 'yellow-paper-classifieds') . ' ' . $login_url . "\n\n";
        $user_message .= sprintf(__('Сайт: %s', 'yellow-paper-classifieds'), home_url('/'));

        wp_mail($user->user_email, $user_subject, $user_message);

        $admin_email = get_option('admin_email');
        if (!$admin_email) {
            return;
        }

        $admin_subject = sprintf(__('Новий користувач оголошень на сайті %s', 'yellow-paper-classifieds'), $site_name);
        $admin_message = __('Зареєстровано нового користувача оголошень.', 'yellow-paper-classifieds') . "\n\n";
        $admin_message .= __('Імʼя:', 'yellow-paper-classifieds') . ' ' . $user->display_name . "\n";
        $admin_message .= __('Email:', 'yellow-paper-classifieds') . ' ' . $user->user_email . "\n";
        $admin_message .= __('Профіль:', 'yellow-paper-classifieds') . ' ' . admin_url('user-edit.php?user_id=' . (int) $user->ID) . "\n";

        wp_mail($admin_email, $admin_subject, $admin_message);
    }

    private function send_password_reset_email($user, $reset_key) {
        if (!$user instanceof WP_User) {
            return;
        }

        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $reset_url = add_query_arg(array(
            'key'   => rawurlencode($reset_key),
            'login' => rawurlencode($user->user_login),
        ), $this->get_page_url_by_path(self::PAGE_RESET));

        $subject = sprintf(__('Поновлення пароля на сайті %s', 'yellow-paper-classifieds'), $site_name);
        $message = sprintf(__('Вітаємо, %s!', 'yellow-paper-classifieds'), $user->display_name) . "\n\n";
        $message .= __('Ми отримали запит на створення нового пароля для вашого акаунта.', 'yellow-paper-classifieds') . "\n";
        $message .= __('Щоб створити новий пароль, перейдіть за посиланням:', 'yellow-paper-classifieds') . "\n";
        $message .= $reset_url . "\n\n";
        $message .= __('Якщо ви не робили цей запит, просто проігноруйте цей лист.', 'yellow-paper-classifieds');

        wp_mail($user->user_email, $subject, $message);
    }

    private function send_password_changed_email($user) {
        if (!$user instanceof WP_User) {
            return;
        }

        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $subject   = sprintf(__('Пароль на сайті %s змінено', 'yellow-paper-classifieds'), $site_name);
        $message   = sprintf(__('Вітаємо, %s!', 'yellow-paper-classifieds'), $user->display_name) . "\n\n";
        $message  .= __('Пароль до вашого акаунта було успішно змінено.', 'yellow-paper-classifieds') . "\n";
        $message  .= __('Якщо це були не ви, звʼяжіться з адміністрацією сайту.', 'yellow-paper-classifieds');

        wp_mail($user->user_email, $subject, $message);
    }

    private function generate_username_from_email($email) {
        $base = sanitize_user(current(explode('@', $email)), true);

        if ($base === '') {
            $base = 'yp_user';
        }

        $username = $base;
        $counter  = 1;

        while (username_exists($username)) {
            $username = $base . '_' . $counter;
            $counter++;
        }

        return $username;
    }

    private function get_account_redirect_url() {
        $account_url = $this->get_page_url_by_path(self::PAGE_ACCOUNT);

        if ($account_url) {
            return $account_url;
        }

        $listings_url = $this->get_page_url_by_path(self::PAGE_LISTINGS);

        if ($listings_url) {
            return $listings_url;
        }

        return home_url('/');
    }

    private function get_safe_redirect_url($default_url) {
        $redirect_to = isset($_REQUEST['redirect_to']) ? esc_url_raw(wp_unslash($_REQUEST['redirect_to'])) : '';

        if ($redirect_to && wp_validate_redirect($redirect_to, false)) {
            return $redirect_to;
        }

        return $default_url;
    }

    private function get_page_url_by_path($path) {
        $page = get_page_by_path($path);

        if ($page) {
            return get_permalink($page->ID);
        }

        return '';
    }
}
