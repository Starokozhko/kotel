<?php

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('yp_get_public_listing_category_query_args')) {
    function yp_get_public_listing_category_query_args($term_id, $count_posts = false) {
        $args = array(
            'post_type' => YP_Post_Types::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => !$count_posts,
            'ignore_sticky_posts' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query' => array(
                array(
                    'taxonomy' => YP_Post_Types::TAXONOMY,
                    'field' => 'term_id',
                    'terms' => absint($term_id),
                    'include_children' => true,
                ),
            ),
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
}

if (!function_exists('yp_term_has_published_listings')) {
    function yp_term_has_published_listings($term_id) {
        static $cache = array();

        $term_id = absint($term_id);

        if (!$term_id) {
            return false;
        }

        if (isset($cache[$term_id])) {
            return $cache[$term_id];
        }

        $query = new WP_Query(yp_get_public_listing_category_query_args($term_id, false));

        $cache[$term_id] = $query->have_posts();
        wp_reset_postdata();

        return $cache[$term_id];
    }
}

if (!function_exists('yp_get_term_published_listings_count')) {
    function yp_get_term_published_listings_count($term_id) {
        static $cache = array();

        $term_id = absint($term_id);

        if (!$term_id) {
            return 0;
        }

        if (isset($cache[$term_id])) {
            return $cache[$term_id];
        }

        $query = new WP_Query(yp_get_public_listing_category_query_args($term_id, true));

        $cache[$term_id] = (int) $query->found_posts;
        wp_reset_postdata();

        return $cache[$term_id];
    }
}

if (!function_exists('yp_get_visible_child_listing_categories')) {
    function yp_get_visible_child_listing_categories($parent_term_id = 0) {
        $parent_term_id = absint($parent_term_id);
        $terms = get_terms(array(
            'taxonomy' => YP_Post_Types::TAXONOMY,
            'parent' => $parent_term_id,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC',
        ));

        if (is_wp_error($terms) || empty($terms)) {
            return array();
        }

        $visible_terms = array();

        foreach ($terms as $term) {
            if (!$term instanceof WP_Term) {
                continue;
            }

            if (yp_term_has_published_listings($term->term_id)) {
                $visible_terms[] = $term;
            }
        }

        return $visible_terms;
    }
}
