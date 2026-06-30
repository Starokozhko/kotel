<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Roles {

    const ROLE_LISTING_USER = 'yp_listing_user';
    const CAP_BYPASS_PAYMENT = 'yp_bypass_listing_payment';

    public function add_roles_and_caps() {
        $this->add_listing_user_role();
        $this->add_admin_caps();
    }

    public function register_runtime_caps() {
        // Місце для майбутньої динамічної логіки.
    }

    private function add_listing_user_role() {
        $capabilities = array(
            'read'                   => true,

            // Для CPT yp_listing.
            'edit_yp_listing'        => true,
            'read_yp_listing'        => true,
            'delete_yp_listing'      => true,

            'edit_yp_listings'       => true,
            'edit_others_yp_listings'=> false,
            'publish_yp_listings'    => false,
            'read_private_yp_listings'=> false,
            'delete_yp_listings'     => true,
            'delete_private_yp_listings' => false,
            'delete_published_yp_listings' => true,
            'delete_others_yp_listings' => false,
            'edit_private_yp_listings' => false,
            'edit_published_yp_listings' => true,
            'create_yp_listings'     => true,
        );

        add_role(
            self::ROLE_LISTING_USER,
            __('Користувач оголошень', 'yellow-paper-classifieds'),
            $capabilities
        );
    }

    private function add_admin_caps() {
        $role = get_role('administrator');

        if (!$role) {
            return;
        }

        $caps = array(
            'edit_yp_listing',
            'read_yp_listing',
            'delete_yp_listing',
            'edit_yp_listings',
            'edit_others_yp_listings',
            'publish_yp_listings',
            'read_private_yp_listings',
            'delete_yp_listings',
            'delete_private_yp_listings',
            'delete_published_yp_listings',
            'delete_others_yp_listings',
            'edit_private_yp_listings',
            'edit_published_yp_listings',
            'create_yp_listings',
            self::CAP_BYPASS_PAYMENT,
        );

        foreach ($caps as $cap) {
            $role->add_cap($cap);
        }
    }
}