<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Listing_Workflow {

    const POST_STATUS_SAVED = 'yp_saved';

    const META_SUBMISSION_STATUS = '_yp_submission_status';
    const META_SUBMITTED_AT = '_yp_submitted_at';
    const META_AUTO_PUBLISH_SCHEDULED = '_yp_auto_publish_scheduled';
    const META_AUTO_PUBLISH_AT = '_yp_auto_publish_at';
    const META_AUTO_PUBLISHED = '_yp_auto_published';
    const META_AUTO_PUBLISHED_AT = '_yp_auto_published_at';
    const META_PUBLISH_SOURCE = '_yp_publish_source';

    const USER_META_ACCOUNT_PAYMENT_STATUS = '_yp_account_payment_status';
    const META_FREE_PUBLICATION_REASON = '_yp_free_publication_reason';

    const FREE_PRIVATE_PERSON_LIMIT = 3;

    const SUBMISSION_SAVED = 'saved';
    const SUBMISSION_SUBMITTED = 'submitted';
    const SUBMISSION_PUBLISHED = 'published';

    const ACCOUNT_PAYMENT_ACTIVE = 'active';
    const ACCOUNT_PAYMENT_INACTIVE = 'inactive';

    const OPTION_AUTO_PUBLISH_ENABLED = 'yp_auto_publish_enabled';
    const OPTION_AUTO_PUBLISH_DELAY_MINUTES = 'yp_auto_publish_delay_minutes';

    const CRON_HOOK_AUTO_PUBLISH = 'yp_auto_publish_listing_event';

    private static $admin_publish_guard = false;

    public function hooks() {
        add_action(self::CRON_HOOK_AUTO_PUBLISH, array($this, 'handle_auto_publish_event'));
        add_action('transition_post_status', array($this, 'handle_admin_status_transition'), 10, 3);
    }

    public function register_saved_post_status() {
        register_post_status(self::POST_STATUS_SAVED, array(
            'label'                     => _x('Збережено', 'listing post status', 'yellow-paper-classifieds'),
            'public'                    => false,
            'internal'                  => false,
            'protected'                 => true,
            'private'                   => false,
            'exclude_from_search'       => true,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop(
                'Збережено <span class="count">(%s)</span>',
                'Збережено <span class="count">(%s)</span>',
                'yellow-paper-classifieds'
            ),
        ));
    }

    public function handle_auto_publish_event($listing_id) {
        self::auto_publish_listing((int) $listing_id);
    }

    public function handle_admin_status_transition($new_status, $old_status, $post) {
        if (self::$admin_publish_guard) {
            return;
        }

        if (!$post || $post->post_type !== YP_Post_Types::POST_TYPE) {
            return;
        }

        if ($old_status === $new_status) {
            return;
        }

        if ($new_status === 'publish') {
            self::clear_listing_auto_publish($post->ID);
            update_post_meta($post->ID, self::META_SUBMISSION_STATUS, self::SUBMISSION_PUBLISHED);
            update_post_meta($post->ID, YP_Listing_Meta::META_MODERATION_STATUS, YP_Listing_Meta::MOD_APPROVED);
            update_post_meta($post->ID, YP_Listing_Meta::META_VISIBILITY, YP_Listing_Meta::VIS_PUBLIC);

            if (get_post_meta($post->ID, self::META_PUBLISH_SOURCE, true) === '') {
                update_post_meta($post->ID, self::META_PUBLISH_SOURCE, 'admin');
            }
        }

        if (in_array($new_status, array('draft', 'trash'), true)) {
            self::clear_listing_auto_publish($post->ID);
        }

        self::clear_listing_cache($post->ID);
    }

    public static function get_account_payment_status($user_id) {
        $status = get_user_meta((int) $user_id, self::USER_META_ACCOUNT_PAYMENT_STATUS, true);
        return $status === self::ACCOUNT_PAYMENT_ACTIVE ? self::ACCOUNT_PAYMENT_ACTIVE : self::ACCOUNT_PAYMENT_INACTIVE;
    }

    public static function user_can_submit_listing($user_id, $listing_id = 0, $category_id = 0) {
        $user_id = (int) $user_id;
        $listing_id = (int) $listing_id;
        $category_id = (int) $category_id;

        if ($user_id <= 0) {
            return false;
        }

        if (user_can($user_id, YP_Roles::CAP_BYPASS_PAYMENT)) {
            return true;
        }

        if (self::is_free_message_category($category_id)) {
            return true;
        }

        if (function_exists('yp_user_is_private_person') && yp_user_is_private_person($user_id)) {
            return self::get_private_person_free_slots_used($user_id, $listing_id) < self::FREE_PRIVATE_PERSON_LIMIT;
        }

        return self::get_account_payment_status($user_id) === self::ACCOUNT_PAYMENT_ACTIVE;
    }

    public static function get_submission_block_message($user_id, $listing_id = 0, $category_id = 0) {
        if (self::user_can_submit_listing($user_id, $listing_id, $category_id)) {
            return '';
        }

        if (function_exists('yp_user_is_private_person') && yp_user_is_private_person((int) $user_id)) {
            return sprintf(
                __('Для приватної особи доступно %d безкоштовні активні оголошення. Для цієї категорії ліміт уже використано.', 'yellow-paper-classifieds'),
                self::FREE_PRIVATE_PERSON_LIMIT
            );
        }

        return __('Для публікації оголошення потрібно оплатити послугу.', 'yellow-paper-classifieds');
    }

    public static function get_free_publication_reason($user_id, $listing_id = 0, $category_id = 0) {
        $user_id = (int) $user_id;
        $listing_id = (int) $listing_id;
        $category_id = (int) $category_id;

        if (self::is_free_message_category($category_id)) {
            return 'free_message_category';
        }

        if (function_exists('yp_user_is_private_person') && yp_user_is_private_person($user_id)) {
            if (self::get_private_person_free_slots_used($user_id, $listing_id) < self::FREE_PRIVATE_PERSON_LIMIT) {
                return 'private_person_free_limit';
            }
        }

        return '';
    }

    public static function is_free_message_category($category_id) {
        $category_id = (int) $category_id;

        if ($category_id <= 0 || !taxonomy_exists(YP_Post_Types::TAXONOMY)) {
            return false;
        }

        $term = get_term($category_id, YP_Post_Types::TAXONOMY);
        if (!$term || is_wp_error($term)) {
            return false;
        }

        $term_ids = array($category_id);
        $ancestors = get_ancestors($category_id, YP_Post_Types::TAXONOMY, 'taxonomy');
        if (!empty($ancestors)) {
            $term_ids = array_merge($term_ids, array_map('intval', $ancestors));
        }

        $message_slugs = array('povidomlennia', 'povidomlennya', 'povidomlennja', 'povidomlennya');
        $free_slugs = array('zahubyv', 'zagubyv', 'zahybyv', 'znayshov', 'znaishov', 'znajshov', 'znajshov');

        $has_message_parent = false;
        $has_free_child = false;

        foreach ($term_ids as $term_id) {
            $current = get_term($term_id, YP_Post_Types::TAXONOMY);
            if (!$current || is_wp_error($current)) {
                continue;
            }

            $slug = sanitize_title($current->slug);
            $name = function_exists('mb_strtolower') ? mb_strtolower($current->name, 'UTF-8') : strtolower($current->name);

            if (in_array($slug, $message_slugs, true) || $name === 'повідомлення') {
                $has_message_parent = true;
            }

            if (in_array($slug, $free_slugs, true) || in_array($name, array('загубив', 'знайшов'), true)) {
                $has_free_child = true;
            }
        }

        return $has_message_parent && $has_free_child;
    }

    public static function get_private_person_free_slots_used($user_id, $exclude_listing_id = 0) {
        $user_id = (int) $user_id;
        $exclude_listing_id = (int) $exclude_listing_id;

        if ($user_id <= 0) {
            return 0;
        }

        $listing_ids = get_posts(array(
            'post_type'      => YP_Post_Types::POST_TYPE,
            'post_status'    => array('publish', 'pending', 'private'),
            'author'         => $user_id,
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'no_found_rows'  => true,
        ));

        if (empty($listing_ids)) {
            return 0;
        }

        $used = 0;
        foreach ($listing_ids as $listing_id) {
            $listing_id = (int) $listing_id;

            if ($exclude_listing_id > 0 && $listing_id === $exclude_listing_id) {
                continue;
            }

            $terms = wp_get_post_terms($listing_id, YP_Post_Types::TAXONOMY, array('fields' => 'ids'));
            $is_free_message = false;

            if (!is_wp_error($terms) && !empty($terms)) {
                foreach ($terms as $term_id) {
                    if (self::is_free_message_category((int) $term_id)) {
                        $is_free_message = true;
                        break;
                    }
                }
            }

            if (!$is_free_message) {
                $used++;
            }
        }

        return $used;
    }

    public static function save_listing_as_saved($listing_id) {
        self::clear_listing_auto_publish($listing_id);

        self::update_listing_status($listing_id, self::POST_STATUS_SAVED);
        update_post_meta($listing_id, self::META_SUBMISSION_STATUS, self::SUBMISSION_SAVED);
        update_post_meta($listing_id, YP_Listing_Meta::META_MODERATION_STATUS, YP_Listing_Meta::MOD_NOT_SUBMITTED);
        update_post_meta($listing_id, YP_Listing_Meta::META_VISIBILITY, YP_Listing_Meta::VIS_HIDDEN);
        delete_post_meta($listing_id, self::META_SUBMITTED_AT);

        self::clear_listing_cache($listing_id);
    }

    public static function submit_listing_for_publication($listing_id, $user_id) {
        $category_id = self::get_listing_primary_category_id($listing_id);

        if (!self::user_can_submit_listing($user_id, $listing_id, $category_id)) {
            return new WP_Error('yp_account_payment_inactive', self::get_submission_block_message($user_id, $listing_id, $category_id));
        }

        self::clear_listing_auto_publish($listing_id);

        self::update_listing_status($listing_id, 'pending');
        update_post_meta($listing_id, self::META_SUBMISSION_STATUS, self::SUBMISSION_SUBMITTED);
        update_post_meta($listing_id, YP_Listing_Meta::META_MODERATION_STATUS, YP_Listing_Meta::MOD_PENDING);
        $free_reason = self::get_free_publication_reason($user_id, $listing_id, $category_id);
        if ($free_reason !== '') {
            update_post_meta($listing_id, YP_Listing_Meta::META_PAYMENT_STATUS, YP_Listing_Meta::PAYMENT_FREE);
            update_post_meta($listing_id, self::META_FREE_PUBLICATION_REASON, $free_reason);
        } else {
            update_post_meta($listing_id, YP_Listing_Meta::META_PAYMENT_STATUS, YP_Listing_Meta::PAYMENT_PAID);
            delete_post_meta($listing_id, self::META_FREE_PUBLICATION_REASON);
        }
        update_post_meta($listing_id, YP_Listing_Meta::META_VISIBILITY, YP_Listing_Meta::VIS_HIDDEN);
        update_post_meta($listing_id, self::META_SUBMITTED_AT, current_time('timestamp'));
        delete_post_meta($listing_id, self::META_PUBLISH_SOURCE);

        self::notify_admin_about_submission($listing_id, $user_id);

        if (self::get_auto_publish_enabled()) {
            self::schedule_listing_auto_publish($listing_id);
        }

        self::clear_listing_cache($listing_id);

        return true;
    }

    public static function schedule_listing_auto_publish($listing_id) {
        $listing_id = (int) $listing_id;
        if ($listing_id <= 0) {
            return;
        }

        self::clear_listing_auto_publish($listing_id);

        $delay_minutes = self::get_auto_publish_delay_minutes();
        $timestamp = time() + ($delay_minutes * MINUTE_IN_SECONDS);

        wp_schedule_single_event($timestamp, self::CRON_HOOK_AUTO_PUBLISH, array($listing_id));
        update_post_meta($listing_id, self::META_AUTO_PUBLISH_SCHEDULED, 1);
        update_post_meta($listing_id, self::META_AUTO_PUBLISH_AT, $timestamp);
    }

    public static function clear_listing_auto_publish($listing_id) {
        $listing_id = (int) $listing_id;
        if ($listing_id <= 0) {
            return;
        }

        wp_clear_scheduled_hook(self::CRON_HOOK_AUTO_PUBLISH, array($listing_id));
        delete_post_meta($listing_id, self::META_AUTO_PUBLISH_SCHEDULED);
    }

    public static function auto_publish_listing($listing_id) {
        $listing_id = (int) $listing_id;
        $post = get_post($listing_id);

        if (!$post || $post->post_type !== YP_Post_Types::POST_TYPE) {
            return false;
        }

        $submission_status = get_post_meta($listing_id, self::META_SUBMISSION_STATUS, true);
        $moderation_status = get_post_meta($listing_id, YP_Listing_Meta::META_MODERATION_STATUS, true);

        if ($post->post_status !== 'pending' || $submission_status !== self::SUBMISSION_SUBMITTED || $moderation_status !== YP_Listing_Meta::MOD_PENDING) {
            self::clear_listing_auto_publish($listing_id);
            return false;
        }

        update_post_meta($listing_id, self::META_PUBLISH_SOURCE, 'auto');
        self::update_listing_status($listing_id, 'publish');
        update_post_meta($listing_id, self::META_SUBMISSION_STATUS, self::SUBMISSION_PUBLISHED);
        update_post_meta($listing_id, YP_Listing_Meta::META_MODERATION_STATUS, YP_Listing_Meta::MOD_APPROVED);
        update_post_meta($listing_id, YP_Listing_Meta::META_VISIBILITY, YP_Listing_Meta::VIS_PUBLIC);
        update_post_meta($listing_id, self::META_AUTO_PUBLISHED, 1);
        update_post_meta($listing_id, self::META_AUTO_PUBLISHED_AT, current_time('timestamp'));
        self::clear_listing_auto_publish($listing_id);
        self::clear_listing_cache($listing_id);

        return true;
    }

    public static function get_auto_publish_enabled() {
        return (int) get_option(self::OPTION_AUTO_PUBLISH_ENABLED, 1) === 1;
    }

    public static function get_auto_publish_delay_minutes() {
        return self::sanitize_auto_publish_delay_minutes(get_option(self::OPTION_AUTO_PUBLISH_DELAY_MINUTES, 30));
    }

    public static function sanitize_auto_publish_enabled($value) {
        return (int) $value === 1 ? 1 : 0;
    }

    public static function sanitize_auto_publish_delay_minutes($value) {
        $value = absint($value);

        if ($value < 1 || $value > 1440) {
            return 30;
        }

        return $value;
    }

    public static function normalize_account_payment_status($value) {
        return $value === self::ACCOUNT_PAYMENT_ACTIVE ? self::ACCOUNT_PAYMENT_ACTIVE : self::ACCOUNT_PAYMENT_INACTIVE;
    }

    public static function get_submission_status_label($status) {
        switch ($status) {
            case self::SUBMISSION_SAVED:
                return __('Збережено', 'yellow-paper-classifieds');
            case self::SUBMISSION_SUBMITTED:
                return __('Подано на публікацію', 'yellow-paper-classifieds');
            case self::SUBMISSION_PUBLISHED:
                return __('Опубліковано', 'yellow-paper-classifieds');
            default:
                return __('Не визначено', 'yellow-paper-classifieds');
        }
    }

    private static function get_listing_primary_category_id($listing_id) {
        $terms = wp_get_post_terms((int) $listing_id, YP_Post_Types::TAXONOMY, array('fields' => 'ids'));
        if (is_wp_error($terms) || empty($terms)) {
            return 0;
        }

        return (int) $terms[0];
    }

    private static function update_listing_status($listing_id, $status) {
        self::$admin_publish_guard = true;
        wp_update_post(array(
            'ID' => (int) $listing_id,
            'post_status' => $status,
        ));
        self::$admin_publish_guard = false;
    }

    private static function notify_admin_about_submission($listing_id, $user_id) {
        $admin_email = get_option('admin_email');
        if (!$admin_email || !is_email($admin_email)) {
            return;
        }

        $user = get_userdata($user_id);
        $edit_link = get_edit_post_link($listing_id, 'raw');
        $subject = sprintf(__('Нове оголошення очікує публікації: %s', 'yellow-paper-classifieds'), get_the_title($listing_id));

        $message = sprintf(
            "%s\n\n%s: %s\n%s: %s\n%s: %s\n%s: %s\n",
            __('Користувач подав оголошення на публікацію.', 'yellow-paper-classifieds'),
            __('Назва оголошення', 'yellow-paper-classifieds'),
            get_the_title($listing_id),
            __('Користувач', 'yellow-paper-classifieds'),
            $user ? $user->display_name : '',
            __('Email користувача', 'yellow-paper-classifieds'),
            $user ? $user->user_email : '',
            __('Посилання на редагування оголошення в адмінці', 'yellow-paper-classifieds'),
            $edit_link ? $edit_link : admin_url('post.php?post=' . (int) $listing_id . '&action=edit')
        );

        wp_mail($admin_email, $subject, $message);
    }

    public static function clear_listing_cache($listing_id = 0) {
        do_action('yp_listing_status_changed', (int) $listing_id);
        do_action('yp_clear_listing_cache', (int) $listing_id);
        clean_post_cache((int) $listing_id);
    }
}
