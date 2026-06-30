<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Admin {

    const OPTION_REGISTRATION_ENABLED = 'yp_registration_enabled';
    const OPTION_SUPPORT_REQUEST_FORM_ID = 'yp_support_request_form_id';
    const OPTION_BANNERS_REQUEST_FORM_ID = 'yp_banners_request_form_id';
    const OPTION_BANNERS_REQUEST_DESCRIPTION = 'yp_banners_request_description';

    public function hooks() {
        add_action('admin_menu', array($this, 'register_admin_pages'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'handle_account_type_migration'));
        add_action('admin_init', array($this, 'maybe_create_default_pages'));
    }

    public function register_admin_pages() {
        add_submenu_page(
            'edit.php?post_type=' . YP_Post_Types::POST_TYPE,
            __('Налаштування Yellow Paper', 'yellow-paper-classifieds'),
            __('Налаштування', 'yellow-paper-classifieds'),
            'manage_options',
            'yp-settings',
            array($this, 'render_settings_page')
        );

        add_submenu_page(
            'edit.php?post_type=' . YP_Post_Types::POST_TYPE,
            __('Аналітика Yellow Paper', 'yellow-paper-classifieds'),
            __('Аналітика', 'yellow-paper-classifieds'),
            'manage_options',
            'yp-analytics',
            array($this, 'render_analytics_page')
        );
    }

    public function register_settings() {
        register_setting(
            'yp_settings_group',
            self::OPTION_REGISTRATION_ENABLED,
            array(
                'type'              => 'string',
                'sanitize_callback' => array($this, 'sanitize_yes_no'),
                'default'           => 'yes',
            )
        );


        register_setting(
            'yp_settings_group',
            YP_Listing_Workflow::OPTION_AUTO_PUBLISH_ENABLED,
            array(
                'type'              => 'integer',
                'sanitize_callback' => array('YP_Listing_Workflow', 'sanitize_auto_publish_enabled'),
                'default'           => 1,
            )
        );

        register_setting(
            'yp_settings_group',
            YP_Listing_Workflow::OPTION_AUTO_PUBLISH_DELAY_MINUTES,
            array(
                'type'              => 'integer',
                'sanitize_callback' => array('YP_Listing_Workflow', 'sanitize_auto_publish_delay_minutes'),
                'default'           => 30,
            )
        );

        register_setting(
            'yp_settings_group',
            self::OPTION_SUPPORT_REQUEST_FORM_ID,
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 0,
            )
        );

        register_setting(
            'yp_settings_group',
            self::OPTION_BANNERS_REQUEST_FORM_ID,
            array(
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 0,
            )
        );

        register_setting(
            'yp_settings_group',
            self::OPTION_BANNERS_REQUEST_DESCRIPTION,
            array(
                'type'              => 'string',
                'sanitize_callback' => 'wp_kses_post',
                'default'           => '',
            )
        );
    }

    public function maybe_create_default_pages() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (!class_exists('YP_Auth')) {
            return;
        }

        $version_key = 'yp_default_pages_checked_version';
        if (get_option($version_key, '') === YP_CLASSIFIEDS_VERSION) {
            return;
        }

        YP_Auth::create_default_pages();
        update_option($version_key, YP_CLASSIFIEDS_VERSION, false);
    }

    public function sanitize_yes_no($value) {
        return $value === 'yes' ? 'yes' : 'no';
    }

    public static function is_registration_enabled() {
        return get_option(self::OPTION_REGISTRATION_ENABLED, 'yes') === 'yes';
    }


    public function handle_account_type_migration() {
        if (empty($_POST['yp_account_type_migration'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (empty($_POST['yp_account_type_migration_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['yp_account_type_migration_nonce'])), 'yp_account_type_migration')) {
            return;
        }

        $result = yp_migrate_account_types();

        $redirect = add_query_arg(array(
            'post_type'          => YP_Post_Types::POST_TYPE,
            'page'               => 'yp-settings',
            'yp_sync_done'       => '1',
            'users_checked'      => (int) $result['users_checked'],
            'users_updated'      => (int) $result['users_updated'],
            'listings_synced'    => (int) $result['listings_synced'],
        ), admin_url('edit.php'));

        wp_safe_redirect($redirect);
        exit;
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $registration_enabled = self::is_registration_enabled();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Налаштування Yellow Paper', 'yellow-paper-classifieds'); ?></h1>

            <?php if (!empty($_GET['yp_sync_done'])) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        echo esc_html(sprintf(
                            __('Синхронізацію завершено. Перевірено користувачів: %d. Оновлено користувачів: %d. Синхронізовано оголошень: %d.', 'yellow-paper-classifieds'),
                            isset($_GET['users_checked']) ? absint($_GET['users_checked']) : 0,
                            isset($_GET['users_updated']) ? absint($_GET['users_updated']) : 0,
                            isset($_GET['listings_synced']) ? absint($_GET['listings_synced']) : 0
                        ));
                        ?>
                    </p>
                </div>
            <?php endif; ?>


            <form method="post" action="options.php">
                <?php settings_fields('yp_settings_group'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Реєстрація користувачів', 'yellow-paper-classifieds'); ?>
                        </th>
                        <td>
                            <input type="hidden" name="<?php echo esc_attr(self::OPTION_REGISTRATION_ENABLED); ?>" value="no">
                            <label>
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr(self::OPTION_REGISTRATION_ENABLED); ?>"
                                    value="yes"
                                    <?php checked($registration_enabled); ?>
                                >
                                <?php esc_html_e('Дозволити реєстрацію нових користувачів оголошень', 'yellow-paper-classifieds'); ?>
                            </label>

                            <p class="description">
                                <?php esc_html_e('Якщо вимкнути цей перемикач, форма реєстрації не буде доступна, а прямі POST-запити на реєстрацію також будуть заблоковані.', 'yellow-paper-classifieds'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <?php esc_html_e('Автопублікація після модерації', 'yellow-paper-classifieds'); ?>
                        </th>
                        <td>
                            <input type="hidden" name="<?php echo esc_attr(YP_Listing_Workflow::OPTION_AUTO_PUBLISH_ENABLED); ?>" value="0">
                            <label>
                                <input
                                    type="checkbox"
                                    name="<?php echo esc_attr(YP_Listing_Workflow::OPTION_AUTO_PUBLISH_ENABLED); ?>"
                                    value="1"
                                    <?php checked(YP_Listing_Workflow::get_auto_publish_enabled()); ?>
                                >
                                <?php esc_html_e('Увімкнути автопублікацію оголошень після подання', 'yellow-paper-classifieds'); ?>
                            </label>
                            <p class="description">
                                <?php esc_html_e('Якщо увімкнено, оголошення у статусі pending автоматично опублікується через заданий час, якщо адміністратор не відхилить або не опублікує його раніше.', 'yellow-paper-classifieds'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="yp_auto_publish_delay_minutes"><?php esc_html_e('Час до автопублікації', 'yellow-paper-classifieds'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                min="1"
                                max="1440"
                                id="yp_auto_publish_delay_minutes"
                                name="<?php echo esc_attr(YP_Listing_Workflow::OPTION_AUTO_PUBLISH_DELAY_MINUTES); ?>"
                                value="<?php echo esc_attr(YP_Listing_Workflow::get_auto_publish_delay_minutes()); ?>"
                                class="small-text"
                            >
                            <?php esc_html_e('хвилин', 'yellow-paper-classifieds'); ?>
                            <p class="description">
                                <?php esc_html_e('Допустиме значення: від 1 до 1440 хвилин. Якщо поле некоректне — буде використано 30 хвилин.', 'yellow-paper-classifieds'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="yp_support_request_form_id"><?php esc_html_e('ID Gravity Form для сторінки “Відправити запит”', 'yellow-paper-classifieds'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                min="0"
                                id="yp_support_request_form_id"
                                name="<?php echo esc_attr(self::OPTION_SUPPORT_REQUEST_FORM_ID); ?>"
                                value="<?php echo esc_attr((int) get_option(self::OPTION_SUPPORT_REQUEST_FORM_ID, 0)); ?>"
                                class="small-text"
                            >
                            <p class="description">
                                <?php esc_html_e('Створіть сторінку зі slug vidpravyty-zapyt і додайте на неї shortcode [yp_support_request]. ID форми не зашивається в код, а береться з цього налаштування.', 'yellow-paper-classifieds'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="yp_banners_request_form_id"><?php esc_html_e('ID Gravity Form для сторінки “Банери”', 'yellow-paper-classifieds'); ?></label>
                        </th>
                        <td>
                            <input
                                type="number"
                                min="0"
                                id="yp_banners_request_form_id"
                                name="<?php echo esc_attr(self::OPTION_BANNERS_REQUEST_FORM_ID); ?>"
                                value="<?php echo esc_attr((int) get_option(self::OPTION_BANNERS_REQUEST_FORM_ID, 0)); ?>"
                                class="small-text"
                            >
                            <p class="description">
                                <?php esc_html_e('Створіть сторінку зі slug banery і додайте на неї shortcode [yp_banners_request]. Якщо сторінки немає, плагін створить її під час наступної активації.', 'yellow-paper-classifieds'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="yp_banners_request_description"><?php esc_html_e('Опис для сторінки “Банери”', 'yellow-paper-classifieds'); ?></label>
                        </th>
                        <td>
                            <textarea
                                id="yp_banners_request_description"
                                name="<?php echo esc_attr(self::OPTION_BANNERS_REQUEST_DESCRIPTION); ?>"
                                rows="6"
                                class="large-text"
                            ><?php echo esc_textarea(get_option(self::OPTION_BANNERS_REQUEST_DESCRIPTION, '')); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Цей текст буде показаний в акаунті користувача на сторінці замовлення банера перед контактною формою. Можна використовувати просте форматування HTML.', 'yellow-paper-classifieds'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Зберегти налаштування', 'yellow-paper-classifieds')); ?>
            </form>

            <hr style="margin:32px 0;">

            <h2><?php esc_html_e('Синхронізація типів акаунтів', 'yellow-paper-classifieds'); ?></h2>
            <p>
                <?php esc_html_e('Ця дія проставить старим користувачам тип акаунта за замовчуванням і синхронізує термін таксономії yp_listing_author_type для їхніх оголошень.', 'yellow-paper-classifieds'); ?>
            </p>
            <form method="post">
                <?php wp_nonce_field('yp_account_type_migration', 'yp_account_type_migration_nonce'); ?>
                <input type="hidden" name="yp_account_type_migration" value="1">
                <?php submit_button(__('Запустити синхронізацію', 'yellow-paper-classifieds'), 'secondary'); ?>
            </form>

        </div>
        <?php
    }

    public function render_analytics_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $stats = $this->get_analytics_stats();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Аналітика Yellow Paper', 'yellow-paper-classifieds'); ?></h1>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px;margin-top:24px;max-width:1000px;">
                <?php $this->render_stat_card(__('Користувачі оголошень', 'yellow-paper-classifieds'), $stats['listing_users']); ?>
                <?php $this->render_stat_card(__('Усього оголошень', 'yellow-paper-classifieds'), $stats['listings_total']); ?>
                <?php $this->render_stat_card(__('Опубліковані', 'yellow-paper-classifieds'), $stats['listings_publish']); ?>
                <?php $this->render_stat_card(__('Очікують модерації', 'yellow-paper-classifieds'), $stats['listings_pending']); ?>
                <?php $this->render_stat_card(__('Чернетки', 'yellow-paper-classifieds'), $stats['listings_draft']); ?>
                <?php $this->render_stat_card(__('Збережені', 'yellow-paper-classifieds'), $stats['listings_saved']); ?>
                <?php $this->render_stat_card(__('Оголошення користувачів', 'yellow-paper-classifieds'), $stats['listings_by_listing_users']); ?>
            </div>

            <hr style="margin:32px 0;">

            <h2><?php esc_html_e('Майбутні показники', 'yellow-paper-classifieds'); ?></h2>

            <p>
                <?php esc_html_e('Надалі тут можна додати статистику по оплачених оголошеннях, активних платних акаунтах, прострочених оплатах, місячній виручці та конверсії реєстрацій у платні акаунти.', 'yellow-paper-classifieds'); ?>
            </p>
        </div>
        <?php
    }

    private function render_stat_card($title, $value) {
        ?>
        <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px;">
            <div style="font-size:14px;color:#646970;margin-bottom:8px;">
                <?php echo esc_html($title); ?>
            </div>
            <div style="font-size:34px;font-weight:700;line-height:1;">
                <?php echo esc_html(number_format_i18n((int) $value)); ?>
            </div>
        </div>
        <?php
    }

    private function get_analytics_stats() {
        $users_count = count_users();

        $listing_users_count = 0;

        if (!empty($users_count['avail_roles'][YP_Roles::ROLE_LISTING_USER])) {
            $listing_users_count = (int) $users_count['avail_roles'][YP_Roles::ROLE_LISTING_USER];
        }

        $posts_count = wp_count_posts(YP_Post_Types::POST_TYPE);

        $saved_status = YP_Listing_Workflow::POST_STATUS_SAVED;
        $publish = !empty($posts_count->publish) ? (int) $posts_count->publish : 0;
        $pending = !empty($posts_count->pending) ? (int) $posts_count->pending : 0;
        $draft   = !empty($posts_count->draft) ? (int) $posts_count->draft : 0;
        $saved   = !empty($posts_count->{$saved_status}) ? (int) $posts_count->{$saved_status} : 0;
        $private = !empty($posts_count->private) ? (int) $posts_count->private : 0;
        $future  = !empty($posts_count->future) ? (int) $posts_count->future : 0;

        $total = $publish + $pending + $draft + $saved + $private + $future;

        return array(
            'listing_users'             => $listing_users_count,
            'listings_total'            => $total,
            'listings_publish'          => $publish,
            'listings_pending'          => $pending,
            'listings_draft'            => $draft,
            'listings_saved'            => $saved,
            'listings_by_listing_users' => $this->count_listings_by_listing_users(),
        );
    }

    private function count_listings_by_listing_users() {
        $user_ids = get_users(array(
            'role'   => YP_Roles::ROLE_LISTING_USER,
            'fields' => 'ID',
        ));

        if (empty($user_ids)) {
            return 0;
        }

        $query = new WP_Query(array(
            'post_type'      => YP_Post_Types::POST_TYPE,
            'post_status'    => array('publish', 'pending', 'draft', YP_Listing_Workflow::POST_STATUS_SAVED, 'private', 'future'),
            'author__in'     => array_map('intval', $user_ids),
            'fields'         => 'ids',
            'posts_per_page' => 1,
            'no_found_rows'  => false,
        ));

        return (int) $query->found_posts;
    }
}