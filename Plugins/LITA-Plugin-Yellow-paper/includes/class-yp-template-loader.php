<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Template_Loader {

    private $listing_images;
    private $user_profile;

    public function __construct($listing_images, $user_profile) {
        $this->listing_images = $listing_images;
        $this->user_profile   = $user_profile;
    }

    public function hooks() {
        add_filter('template_include', array($this, 'template_include'));
    }

    public function template_include($template) {
        if (is_post_type_archive(YP_Post_Types::POST_TYPE)) {
            $plugin_template = YP_CLASSIFIEDS_PATH . 'templates/archive-yp_listing.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        if (is_singular(YP_Post_Types::POST_TYPE)) {
            $plugin_template = YP_CLASSIFIEDS_PATH . 'templates/single-yp_listing.php';
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }

    public function get_listing_card_data($post_id) {
        $category_id     = $this->get_primary_term_id($post_id, YP_Post_Types::TAXONOMY);
        $location_id     = $this->get_primary_term_id($post_id, YP_Post_Types::LOCATION_TAXONOMY);
        $price           = get_post_meta($post_id, '_yp_price', true);
        $price_type      = get_post_meta($post_id, YP_Frontend_Submission::META_PRICE_TYPE, true);
        $special_price   = get_post_meta($post_id, YP_Frontend_Submission::META_SPECIAL_PRICE, true);
        $image_ids       = $this->listing_images->get_gallery_image_ids($post_id);
        $thumb_id        = get_post_thumbnail_id($post_id);
        $thumb_url       = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'medium') : '';
        $category_name   = $category_id ? $this->get_term_name($category_id, YP_Post_Types::TAXONOMY) : '';
        $location_name   = $location_id ? $this->get_term_name($location_id, YP_Post_Types::LOCATION_TAXONOMY) : '';
        $price_type      = $this->normalize_price_type($price_type, $price);
        $is_private_person = yp_listing_is_private_person($post_id);

        if ($is_private_person || $price_type === YP_Frontend_Submission::PRICE_TYPE_NO_PRICE) {
            $price = '';
            $special_price = '';
            $price_type = YP_Frontend_Submission::PRICE_TYPE_NO_PRICE;
        }

        return array(
            'post_id'        => $post_id,
            'title'          => get_the_title($post_id),
            'permalink'      => get_permalink($post_id),
            'price'          => $price,
            'price_type'     => $price_type,
            'special_price'  => $special_price,
            'is_private_person' => $is_private_person,
            'image_count'    => count($image_ids),
            'thumb_url'      => $thumb_url,
            'category_name'  => $category_name,
            'location_name'  => $location_name,
        );
    }

    public function get_single_listing_data($post_id) {
        $author_id        = (int) get_post_field('post_author', $post_id);
        $category_id      = $this->get_primary_term_id($post_id, YP_Post_Types::TAXONOMY);
        $location_id      = $this->get_primary_term_id($post_id, YP_Post_Types::LOCATION_TAXONOMY);
        $image_ids        = $this->listing_images->get_gallery_image_ids($post_id);
        $price            = get_post_meta($post_id, '_yp_price', true);
        $price_type       = get_post_meta($post_id, YP_Frontend_Submission::META_PRICE_TYPE, true);
        $special_price    = get_post_meta($post_id, YP_Frontend_Submission::META_SPECIAL_PRICE, true);
        $sale_conditions  = get_post_meta($post_id, YP_Frontend_Submission::META_SALE_CONDITIONS, true);
        $characteristics  = get_post_meta($post_id, YP_Frontend_Submission::META_CHARACTERISTICS, true);
        $contact_name     = get_post_meta($post_id, '_yp_contact_name', true);
        $phone_digits     = get_post_meta($post_id, '_yp_phone', true);
        $store_data       = $this->user_profile->get_user_store_data($author_id);

        if (!is_array($characteristics)) {
            $characteristics = array();
        }

        $price_type = $this->normalize_price_type($price_type, $price);
        $is_private_person = yp_listing_is_private_person($post_id);

        if ($is_private_person || $price_type === YP_Frontend_Submission::PRICE_TYPE_NO_PRICE) {
            $price = '';
            $special_price = '';
            $sale_conditions = '';
            $price_type = YP_Frontend_Submission::PRICE_TYPE_NO_PRICE;
        }

        if ($is_private_person) {
            $characteristics = array();
        }

        return array(
            'post_id'          => $post_id,
            'title'            => get_the_title($post_id),
            'content'          => apply_filters('the_content', get_post_field('post_content', $post_id)),
            'category_name'    => $category_id ? $this->get_term_name($category_id, YP_Post_Types::TAXONOMY) : '',
            'location_name'    => $location_id ? $this->get_term_name($location_id, YP_Post_Types::LOCATION_TAXONOMY) : '',
            'image_ids'        => $image_ids,
            'price'            => $price,
            'price_type'       => $price_type,
            'special_price'    => $special_price,
            'sale_conditions'  => $sale_conditions,
            'characteristics'  => $characteristics,
            'is_private_person' => $is_private_person,
            'contact_name'     => $contact_name,
            'phone_formatted'  => $this->format_phone_for_display($phone_digits),
            'store_data'       => $store_data,
            'author_id'        => $author_id,
        );
    }

    public function get_price_type_label($price_type) {
        switch ($price_type) {
            case YP_Frontend_Submission::PRICE_TYPE_NO_PRICE:
                return __('Без вказання ціни', 'yellow-paper-classifieds');
            case YP_Frontend_Submission::PRICE_TYPE_SALE:
                return __('Акція', 'yellow-paper-classifieds');
            case YP_Frontend_Submission::PRICE_TYPE_CLEARANCE:
                return __('Розпродаж', 'yellow-paper-classifieds');
            default:
                return __('Звичайна', 'yellow-paper-classifieds');
        }
    }

    private function normalize_price_type($price_type, $price) {
        $allowed_price_types = array(
            YP_Frontend_Submission::PRICE_TYPE_NO_PRICE,
            YP_Frontend_Submission::PRICE_TYPE_REGULAR,
            YP_Frontend_Submission::PRICE_TYPE_SALE,
            YP_Frontend_Submission::PRICE_TYPE_CLEARANCE,
        );

        if (in_array($price_type, $allowed_price_types, true)) {
            return $price_type;
        }

        return trim((string) $price) !== '' ? YP_Frontend_Submission::PRICE_TYPE_REGULAR : YP_Frontend_Submission::PRICE_TYPE_NO_PRICE;
    }

    public function format_phone_for_display($digits) {
        $digits = preg_replace('/\D+/', '', (string) $digits);

        if (!preg_match('/^\d{10}$/', $digits)) {
            return '';
        }

        return sprintf(
            '(%s) %s-%s-%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 2),
            substr($digits, 8, 2)
        );
    }

    private function get_primary_term_id($post_id, $taxonomy) {
        $terms = wp_get_post_terms($post_id, $taxonomy, array('fields' => 'ids'));

        if (is_wp_error($terms) || empty($terms)) {
            return 0;
        }

        return (int) $terms[0];
    }

    private function get_term_name($term_id, $taxonomy) {
        $term = get_term($term_id, $taxonomy);

        if (!$term || is_wp_error($term)) {
            return '';
        }

        return $term->name;
    }
}