<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Activator {

    public static function activate() {
        // Підключаємо класи напряму, бо activation hook виконується окремо.
        $post_types = new YP_Post_Types();
        $roles      = new YP_Roles();

        $post_types->register();
        $roles->add_roles_and_caps();

        if (function_exists('yp_ensure_author_type_terms')) {
            yp_ensure_author_type_terms();
        }

        if (class_exists('YP_Auth')) {
            YP_Auth::create_default_pages();
        }

        if (class_exists('YP_Admin')) {
            add_option(YP_Admin::OPTION_REGISTRATION_ENABLED, 'yes');
            add_option(YP_Admin::OPTION_SUPPORT_REQUEST_FORM_ID, 0);
            add_option(YP_Admin::OPTION_BANNERS_REQUEST_FORM_ID, 0);
            add_option(YP_Admin::OPTION_BANNERS_REQUEST_DESCRIPTION, '');
        }

        flush_rewrite_rules();
    }
}