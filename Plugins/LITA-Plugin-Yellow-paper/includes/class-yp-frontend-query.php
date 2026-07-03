<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('yp_get_listings_per_page')) {
    function yp_get_listings_per_page() {
        return max(1, (int) apply_filters('yp_listings_per_page', 40));
    }
}

class YP_Frontend_Query {

    public function hooks() {
        add_action('pre_get_posts', array($this, 'filter_listing_queries'));
    }

    public function filter_listing_queries($query) {
        if (is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->is_post_type_archive(YP_Post_Types::POST_TYPE)) {
            $this->apply_public_listing_rules($query);
            return;
        }

        if ($query->is_tax(YP_Post_Types::TAXONOMY)) {
            $this->apply_public_listing_rules($query);
            return;
        }
    }

    private function apply_public_listing_rules($query) {
        $meta_query = $query->get('meta_query');

        if (!is_array($meta_query)) {
            $meta_query = array();
        }

        $meta_query[] = array(
            'key'     => YP_Listing_Meta::META_VISIBILITY,
            'value'   => YP_Listing_Meta::VIS_PUBLIC,
            'compare' => '=',
        );

        $meta_query[] = array(
            'key'     => YP_Listing_Meta::META_MODERATION_STATUS,
            'value'   => YP_Listing_Meta::MOD_APPROVED,
            'compare' => '=',
        );

        $query->set('post_status', 'publish');
        $query->set('meta_query', $meta_query);
        $query->set('post_type', YP_Post_Types::POST_TYPE);
        $query->set('posts_per_page', yp_get_listings_per_page());
    }
}