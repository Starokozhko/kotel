<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('yp_is_seller_archive')) {
    function yp_is_seller_archive() {
        return get_query_var('yp_seller') !== '';
    }
}

if (!function_exists('yp_get_current_seller')) {
    function yp_get_current_seller() {
        $slug = get_query_var('yp_seller');

        return $slug !== '' ? yp_get_seller_by_slug($slug) : null;
    }
}

if (!function_exists('yp_get_seller_public_slug')) {
    function yp_get_seller_public_slug($user_id) {
        return YP_Seller_Archive::get_seller_public_slug($user_id);
    }
}

if (!function_exists('yp_get_seller_by_slug')) {
    function yp_get_seller_by_slug($slug) {
        return YP_Seller_Archive::get_seller_by_slug($slug);
    }
}

if (!function_exists('yp_get_seller_archive_url')) {
    function yp_get_seller_archive_url($user_id) {
        return YP_Seller_Archive::get_seller_archive_url($user_id);
    }
}

if (!function_exists('yp_get_seller_active_listings_count')) {
    function yp_get_seller_active_listings_count($user_id) {
        return YP_Seller_Archive::get_seller_active_listings_count($user_id);
    }
}

if (!function_exists('yp_get_seller_display_name')) {
    function yp_get_seller_display_name($user_id) {
        return YP_Seller_Archive::get_seller_display_name($user_id);
    }
}

class YP_Seller_Archive {

    const QUERY_VAR = 'yp_seller';
    const META_PUBLIC_SLUG = 'yp_public_seller_slug';

    private $user_profile;
    private $template_loader;

    public function __construct($user_profile, $template_loader) {
        $this->user_profile = $user_profile;
        $this->template_loader = $template_loader;
    }

    public function hooks() {
        add_action('init', array(__CLASS__, 'register_rewrite_rule'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('parse_request', array($this, 'parse_seller_request'));
        add_filter('template_include', array($this, 'template_include'), 20);
        add_action('template_redirect', array($this, 'redirect_old_author_archive'), 1);
        add_filter('wp_robots', array($this, 'filter_robots'));
        add_filter('document_title_parts', array($this, 'filter_document_title'));
        add_action('wp_head', array($this, 'print_meta_description'), 1);
        add_action('wp_head', array($this, 'print_canonical'), 2);
    }

    public static function register_rewrite_rule() {
        add_rewrite_rule(
            '^prodavets/([^/]+)/?$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]',
            'top'
        );

        add_rewrite_rule(
            '^prodavets/([^/]+)/page/([0-9]+)/?$',
            'index.php?' . self::QUERY_VAR . '=$matches[1]&paged=$matches[2]',
            'top'
        );
    }

    public function add_query_vars($vars) {
        $vars[] = self::QUERY_VAR;

        return $vars;
    }

    public function parse_seller_request($wp) {
        if (!empty($wp->query_vars[self::QUERY_VAR])) {
            return;
        }

        $request = isset($wp->request) ? trim((string) $wp->request, '/') : '';

        if (!preg_match('#^prodavets/([^/]+)(?:/page/([0-9]+))?$#', $request, $matches)) {
            return;
        }

        $wp->query_vars[self::QUERY_VAR] = sanitize_title($matches[1]);

        if (!empty($matches[2])) {
            $wp->query_vars['paged'] = max(1, absint($matches[2]));
        }
    }
    public function template_include($template) {
        if (!yp_is_seller_archive()) {
            return $template;
        }

        $seller = yp_get_current_seller();

        if (!$seller instanceof WP_User) {
            global $wp_query;

            $wp_query->set_404();
            status_header(404);
            nocache_headers();

            $not_found_template = get_404_template();

            return $not_found_template ? $not_found_template : $template;
        }

        global $wp_query;

        if ($wp_query instanceof WP_Query) {
            $wp_query->is_404 = false;
        }

        status_header(200);
        $seller_template = $this->template_loader->get_template_path('seller-archive.php');

        return $seller_template ? $seller_template : $template;
    }

    public function redirect_old_author_archive() {
        if (!is_post_type_archive(YP_Post_Types::POST_TYPE) || empty($_GET['yp_author'])) {
            return;
        }

        $user_id = absint(wp_unslash($_GET['yp_author']));

        if (!$user_id || !get_user_by('id', $user_id)) {
            return;
        }

        $seller_url = self::get_seller_archive_url($user_id);

        if ($seller_url === '') {
            return;
        }

        wp_safe_redirect($seller_url, 301);
        exit;
    }

    public function filter_robots($robots) {
        if (!yp_is_seller_archive()) {
            return $robots;
        }

        $seller = yp_get_current_seller();

        if (!$seller instanceof WP_User) {
            return $robots;
        }

        $count = self::get_seller_active_listings_count($seller->ID);

        if ($count <= 3) {
            $robots['noindex'] = true;
            $robots['follow'] = true;
            unset($robots['index']);
        } else {
            $robots['index'] = true;
            $robots['follow'] = true;
            unset($robots['noindex']);
        }

        return $robots;
    }

    public function filter_document_title($parts) {
        if (!yp_is_seller_archive()) {
            return $parts;
        }

        $seller = yp_get_current_seller();

        if (!$seller instanceof WP_User) {
            return $parts;
        }

        $seller_name = self::get_seller_display_name($seller->ID);

        $parts['title'] = $seller_name !== ''
            ? sprintf(__('Оголошення продавця %s', 'yellow-paper-classifieds'), $seller_name)
            : __('Оголошення продавця', 'yellow-paper-classifieds');

        return $parts;
    }

    public function print_meta_description() {
        if (!yp_is_seller_archive() || $this->seo_plugin_outputs_description()) {
            return;
        }

        $seller = yp_get_current_seller();

        if (!$seller instanceof WP_User) {
            return;
        }

        $seller_name = self::get_seller_display_name($seller->ID);
        $description = $seller_name !== ''
            ? sprintf(__('Усі актуальні оголошення продавця %s на Котельва Інфо: товари, послуги та пропозиції в Котельві.', 'yellow-paper-classifieds'), $seller_name)
            : __('Усі актуальні оголошення продавця на Котельва Інфо: товари, послуги та пропозиції в Котельві.', 'yellow-paper-classifieds');

        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    public function print_canonical() {
        if (!yp_is_seller_archive()) {
            return;
        }

        $seller = yp_get_current_seller();

        if (!$seller instanceof WP_User) {
            return;
        }

        $url = self::get_seller_archive_url($seller->ID);

        if ($url !== '') {
            echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
        }
    }

    private function seo_plugin_outputs_description() {
        return defined('WPSEO_VERSION')
            || defined('RANK_MATH_VERSION')
            || defined('AIOSEO_VERSION');
    }

    public static function get_seller_public_slug($user_id) {
        $user_id = absint($user_id);

        if (!$user_id) {
            return '';
        }

        $existing_slug = (string) get_user_meta($user_id, self::META_PUBLIC_SLUG, true);
        $existing_slug = sanitize_title($existing_slug);

        if ($existing_slug !== '') {
            return $existing_slug;
        }

        $user = get_user_by('id', $user_id);

        if (!$user instanceof WP_User) {
            return '';
        }

        $base = self::get_slug_source($user);
        $slug = self::make_unique_slug($base, $user_id);

        update_user_meta($user_id, self::META_PUBLIC_SLUG, $slug);

        return $slug;
    }

    public static function get_seller_by_slug($slug) {
        $slug = sanitize_title((string) $slug);

        if ($slug === '') {
            return null;
        }

        $users = get_users(array(
            'meta_key' => self::META_PUBLIC_SLUG,
            'meta_value' => $slug,
            'number' => 1,
            'fields' => 'all',
        ));

        if (!empty($users[0]) && $users[0] instanceof WP_User) {
            return $users[0];
        }

        return self::find_seller_by_generated_slug($slug);
    }

    public static function get_seller_archive_url($user_id) {
        $slug = self::get_seller_public_slug($user_id);

        return $slug !== '' ? home_url('/prodavets/' . $slug . '/') : '';
    }

    public static function get_seller_active_listings_count($user_id) {
        $user_id = absint($user_id);

        if (!$user_id) {
            return 0;
        }

        $query = new WP_Query(array_merge(
            self::get_public_listing_query_args(),
            array(
                'author' => $user_id,
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => false,
            )
        ));

        $count = (int) $query->found_posts;
        wp_reset_postdata();

        return $count;
    }

    public static function get_seller_display_name($user_id) {
        $user_id = absint($user_id);

        if (!$user_id) {
            return '';
        }

        $store_name = trim((string) get_user_meta($user_id, YP_User_Profile::META_STORE_NAME, true));

        if ($store_name !== '') {
            return $store_name;
        }

        $user = get_user_by('id', $user_id);

        return $user instanceof WP_User ? trim((string) $user->display_name) : '';
    }

    public static function get_public_listing_query_args() {
        $args = array(
            'post_type' => YP_Post_Types::POST_TYPE,
            'post_status' => 'publish',
            'ignore_sticky_posts' => true,
        );

        if (class_exists('YP_Listing_Meta')) {
            $args['meta_query'] = array(
                'relation' => 'AND',
                array(
                    'key' => YP_Listing_Meta::META_VISIBILITY,
                    'value' => YP_Listing_Meta::VIS_PUBLIC,
                    'compare' => '=',
                ),
                array(
                    'key' => YP_Listing_Meta::META_MODERATION_STATUS,
                    'value' => YP_Listing_Meta::MOD_APPROVED,
                    'compare' => '=',
                ),
            );
        }

        return $args;
    }


    private static function find_seller_by_generated_slug($slug) {
        $slug = sanitize_title((string) $slug);

        if ($slug === '') {
            return null;
        }

        $users = get_users(array(
            'number' => 500,
            'fields' => 'all',
        ));

        foreach ($users as $user) {
            if (!$user instanceof WP_User) {
                continue;
            }

            $candidate = self::make_unique_slug(self::get_slug_source($user), $user->ID);

            if ($candidate === $slug) {
                update_user_meta($user->ID, self::META_PUBLIC_SLUG, $candidate);

                return $user;
            }
        }

        return null;
    }
    private static function get_slug_source(WP_User $user) {
        $store_name = trim((string) get_user_meta($user->ID, YP_User_Profile::META_STORE_NAME, true));

        if ($store_name !== '') {
            return $store_name;
        }

        $display_name = trim((string) $user->display_name);

        if ($display_name !== '') {
            return $display_name;
        }

        return 'prodavets-' . (int) $user->ID;
    }

    private static function make_unique_slug($base, $user_id) {
        $base_slug = sanitize_title((string) $base);

        if ($base_slug === '') {
            $base_slug = 'prodavets-' . absint($user_id);
        }

        $slug = $base_slug;
        $suffix = 2;

        while (self::slug_exists_for_other_user($slug, $user_id)) {
            $slug = $base_slug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private static function slug_exists_for_other_user($slug, $user_id) {
        $users = get_users(array(
            'meta_key' => self::META_PUBLIC_SLUG,
            'meta_value' => $slug,
            'exclude' => array(absint($user_id)),
            'number' => 1,
            'fields' => 'ID',
        ));

        return !empty($users);
    }
}
