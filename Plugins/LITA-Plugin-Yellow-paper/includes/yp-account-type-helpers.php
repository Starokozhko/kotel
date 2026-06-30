<?php

if (!defined('ABSPATH')) {
    exit;
}

const YP_ACCOUNT_TYPE_META_KEY = '_yp_account_type';
const YP_DEFAULT_ACCOUNT_TYPE  = 'private-person';

function yp_get_account_types() {
    return array(
        'private-person' => __('Приватна особа', 'yellow-paper-classifieds'),
        'company'        => __('Юридична особа', 'yellow-paper-classifieds'),
        'entrepreneur'   => __('Приватний підприємець', 'yellow-paper-classifieds'),
    );
}

function yp_normalize_account_type($account_type) {
    $account_type = sanitize_key((string) $account_type);
    $types        = yp_get_account_types();

    return isset($types[$account_type]) ? $account_type : YP_DEFAULT_ACCOUNT_TYPE;
}

function yp_get_user_account_type($user_id) {
    $user_id = absint($user_id);

    if (!$user_id) {
        return YP_DEFAULT_ACCOUNT_TYPE;
    }

    $account_type = get_user_meta($user_id, YP_ACCOUNT_TYPE_META_KEY, true);

    if ($account_type === '') {
        return YP_DEFAULT_ACCOUNT_TYPE;
    }

    return yp_normalize_account_type($account_type);
}


function yp_user_is_private_person($user_id) {
    return yp_get_user_account_type($user_id) === 'private-person';
}

function yp_listing_is_private_person($listing_id) {
    $listing_id = absint($listing_id);

    if (!$listing_id) {
        return false;
    }

    $taxonomy = yp_get_listing_author_type_taxonomy();

    if (taxonomy_exists($taxonomy) && has_term('private-person', $taxonomy, $listing_id)) {
        return true;
    }

    $author_id = (int) get_post_field('post_author', $listing_id);

    return $author_id ? yp_user_is_private_person($author_id) : false;
}

function yp_private_person_can_use_price() {
    return false;
}

function yp_private_person_can_use_characteristics() {
    return false;
}

function yp_update_user_account_type($user_id, $account_type) {
    $user_id      = absint($user_id);
    $account_type = yp_normalize_account_type($account_type);

    if (!$user_id) {
        return false;
    }

    $old_account_type = get_user_meta($user_id, YP_ACCOUNT_TYPE_META_KEY, true);
    $old_account_type = $old_account_type === '' ? YP_DEFAULT_ACCOUNT_TYPE : yp_normalize_account_type($old_account_type);

    update_user_meta($user_id, YP_ACCOUNT_TYPE_META_KEY, $account_type);

    if ($old_account_type !== $account_type) {
        yp_sync_user_listings_author_type($user_id);
    }

    return true;
}

function yp_get_listing_author_type_taxonomy() {
    if (class_exists('YP_Post_Types') && defined('YP_Post_Types::AUTHOR_TYPE_TAXONOMY')) {
        return YP_Post_Types::AUTHOR_TYPE_TAXONOMY;
    }

    return 'yp_listing_author_type';
}

function yp_ensure_author_type_terms() {
    $taxonomy = yp_get_listing_author_type_taxonomy();

    if (!taxonomy_exists($taxonomy)) {
        return;
    }

    foreach (yp_get_account_types() as $slug => $label) {
        if (!term_exists($slug, $taxonomy)) {
            wp_insert_term($label, $taxonomy, array('slug' => $slug));
        }
    }
}

function yp_sync_listing_author_type($listing_id, $user_id = 0) {
    $listing_id = absint($listing_id);

    if (!$listing_id || !class_exists('YP_Post_Types')) {
        return false;
    }

    $post = get_post($listing_id);

    if (!$post || $post->post_type !== YP_Post_Types::POST_TYPE) {
        return false;
    }

    $user_id = absint($user_id);

    if (!$user_id) {
        $user_id = (int) $post->post_author;
    }

    if (!$user_id) {
        return false;
    }

    $taxonomy     = yp_get_listing_author_type_taxonomy();
    $account_type = yp_get_user_account_type($user_id);

    if (!taxonomy_exists($taxonomy)) {
        return false;
    }

    yp_ensure_author_type_terms();

    $result = wp_set_object_terms($listing_id, array($account_type), $taxonomy, false);

    if (is_wp_error($result)) {
        return false;
    }

    clean_post_cache($listing_id);
    yp_clear_listings_cache($listing_id, $user_id);

    return true;
}

function yp_sync_user_listings_author_type($user_id) {
    $user_id = absint($user_id);

    if (!$user_id || !class_exists('YP_Post_Types')) {
        return 0;
    }

    $synced = 0;
    $paged  = 1;

    do {
        $query = new WP_Query(array(
            'post_type'              => YP_Post_Types::POST_TYPE,
            'post_status'            => 'any',
            'author'                 => $user_id,
            'fields'                 => 'ids',
            'posts_per_page'         => 200,
            'paged'                  => $paged,
            'no_found_rows'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ));

        foreach ($query->posts as $listing_id) {
            if (yp_sync_listing_author_type((int) $listing_id, $user_id)) {
                $synced++;
            }
        }

        $paged++;
    } while ($query->max_num_pages >= $paged);

    wp_reset_postdata();
    yp_clear_listings_cache(0, $user_id);

    return $synced;
}

function yp_migrate_account_types() {
    $result = array(
        'users_checked'       => 0,
        'users_updated'       => 0,
        'listings_synced'     => 0,
    );

    if (!class_exists('YP_Roles')) {
        return $result;
    }

    $user_ids = get_users(array(
        'role'   => YP_Roles::ROLE_LISTING_USER,
        'fields' => 'ID',
    ));

    yp_ensure_author_type_terms();

    foreach ($user_ids as $user_id) {
        $user_id = absint($user_id);
        $result['users_checked']++;

        $current = get_user_meta($user_id, YP_ACCOUNT_TYPE_META_KEY, true);

        if ($current === '') {
            update_user_meta($user_id, YP_ACCOUNT_TYPE_META_KEY, YP_DEFAULT_ACCOUNT_TYPE);
            $result['users_updated']++;
        } else {
            $normalized = yp_normalize_account_type($current);

            if ($normalized !== $current) {
                update_user_meta($user_id, YP_ACCOUNT_TYPE_META_KEY, $normalized);
                $result['users_updated']++;
            }
        }

        $result['listings_synced'] += yp_sync_user_listings_author_type($user_id);
    }

    yp_clear_listings_cache();

    return $result;
}

function yp_clear_listings_cache($listing_id = 0, $user_id = 0) {
    /**
     * Hook for themes/plugins that cache listing sections with transients or object cache.
     * Example usage: add_action('yp_clear_listings_cache', 'your_cache_clear_function', 10, 2);
     */
    do_action('yp_clear_listings_cache', absint($listing_id), absint($user_id));
}


function yp_maybe_sync_listing_author_type_on_save($post_id, $post, $update) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    yp_sync_listing_author_type($post_id);
}
add_action('save_post_yp_listing', 'yp_maybe_sync_listing_author_type_on_save', 20, 3);
