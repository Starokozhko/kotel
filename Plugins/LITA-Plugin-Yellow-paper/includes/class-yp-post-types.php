<?php

if (!defined('ABSPATH')) {
    exit;

}

class YP_Post_Types {

    const POST_TYPE          = 'yp_listing';
    const TAXONOMY           = 'yp_listing_category';
    const LOCATION_TAXONOMY  = 'yp_listing_location';
    const AUTHOR_TYPE_TAXONOMY = 'yp_listing_author_type';

    public function register() {
        if (class_exists('YP_Listing_Workflow')) {
            (new YP_Listing_Workflow())->register_saved_post_status();
        }

        $this->register_listing_post_type();
        $this->register_listing_category_taxonomy();
        $this->register_listing_location_taxonomy();
        $this->register_listing_author_type_taxonomy();

        if (function_exists('yp_ensure_author_type_terms')) {
            yp_ensure_author_type_terms();
        }
    }

    private function register_listing_post_type() {
        $labels = array(
            'name'                  => __('Оголошення', 'yellow-paper-classifieds'),
            'singular_name'         => __('Оголошення', 'yellow-paper-classifieds'),
            'menu_name'             => __('Оголошення', 'yellow-paper-classifieds'),
            'name_admin_bar'        => __('Оголошення', 'yellow-paper-classifieds'),
            'add_new'               => __('Додати оголошення', 'yellow-paper-classifieds'),
            'add_new_item'          => __('Додати нове оголошення', 'yellow-paper-classifieds'),
            'new_item'              => __('Нове оголошення', 'yellow-paper-classifieds'),
            'edit_item'             => __('Редагувати оголошення', 'yellow-paper-classifieds'),
            'view_item'             => __('Переглянути оголошення', 'yellow-paper-classifieds'),
            'all_items'             => __('Усі оголошення', 'yellow-paper-classifieds'),
            'search_items'          => __('Шукати оголошення', 'yellow-paper-classifieds'),
            'parent_item_colon'     => __('Батьківське оголошення:', 'yellow-paper-classifieds'),
            'not_found'             => __('Оголошень не знайдено.', 'yellow-paper-classifieds'),
            'not_found_in_trash'    => __('У кошику оголошень не знайдено.', 'yellow-paper-classifieds'),
            'featured_image'        => __('Головне фото', 'yellow-paper-classifieds'),
            'set_featured_image'    => __('Встановити головне фото', 'yellow-paper-classifieds'),
            'remove_featured_image' => __('Видалити головне фото', 'yellow-paper-classifieds'),
            'use_featured_image'    => __('Використати як головне фото', 'yellow-paper-classifieds'),
            'archives'              => __('Архів оголошень', 'yellow-paper-classifieds'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => array(
                'slug'       => 'ogoloshennya',
                'with_front' => false,
            ),
            'capability_type'    => array('yp_listing', 'yp_listings'),
            'map_meta_cap'       => true,
            'has_archive'        => 'ogoloshennya',
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-megaphone',
            'supports'           => array('title', 'editor', 'author', 'thumbnail'),
        );

        register_post_type(self::POST_TYPE, $args);
    }

    private function register_listing_category_taxonomy() {
        $labels = array(
            'name'              => __('Категорії оголошень', 'yellow-paper-classifieds'),
            'singular_name'     => __('Категорія оголошень', 'yellow-paper-classifieds'),
            'search_items'      => __('Шукати категорії', 'yellow-paper-classifieds'),
            'all_items'         => __('Усі категорії', 'yellow-paper-classifieds'),
            'parent_item'       => __('Батьківська категорія', 'yellow-paper-classifieds'),
            'parent_item_colon' => __('Батьківська категорія:', 'yellow-paper-classifieds'),
            'edit_item'         => __('Редагувати категорію', 'yellow-paper-classifieds'),
            'update_item'       => __('Оновити категорію', 'yellow-paper-classifieds'),
            'add_new_item'      => __('Додати нову категорію', 'yellow-paper-classifieds'),
            'new_item_name'     => __('Назва нової категорії', 'yellow-paper-classifieds'),
            'menu_name'         => __('Категорії', 'yellow-paper-classifieds'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'hierarchical'       => true,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => array(
                'slug'       => 'kategoriya-ogoloshen',
                'with_front' => false,
            ),
        );

        register_taxonomy(self::TAXONOMY, array(self::POST_TYPE), $args);
    }

    private function register_listing_location_taxonomy() {
        $labels = array(
            'name'              => __('Населені пункти', 'yellow-paper-classifieds'),
            'singular_name'     => __('Населений пункт', 'yellow-paper-classifieds'),
            'search_items'      => __('Шукати населені пункти', 'yellow-paper-classifieds'),
            'all_items'         => __('Усі населені пункти', 'yellow-paper-classifieds'),
            'edit_item'         => __('Редагувати населений пункт', 'yellow-paper-classifieds'),
            'update_item'       => __('Оновити населений пункт', 'yellow-paper-classifieds'),
            'add_new_item'      => __('Додати населений пункт', 'yellow-paper-classifieds'),
            'new_item_name'     => __('Назва населеного пункту', 'yellow-paper-classifieds'),
            'menu_name'         => __('Населені пункти', 'yellow-paper-classifieds'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'hierarchical'       => false,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_rest'       => true,
            'query_var'          => true,
            'rewrite'            => array(
                'slug'       => 'misto',
                'with_front' => false,
            ),
        );

        register_taxonomy(self::LOCATION_TAXONOMY, array(self::POST_TYPE), $args);
    }

    private function register_listing_author_type_taxonomy() {
        $labels = array(
            'name'              => __('Типи авторів оголошень', 'yellow-paper-classifieds'),
            'singular_name'     => __('Тип автора оголошення', 'yellow-paper-classifieds'),
            'search_items'      => __('Шукати типи авторів', 'yellow-paper-classifieds'),
            'all_items'         => __('Усі типи авторів', 'yellow-paper-classifieds'),
            'edit_item'         => __('Редагувати тип автора', 'yellow-paper-classifieds'),
            'update_item'       => __('Оновити тип автора', 'yellow-paper-classifieds'),
            'add_new_item'      => __('Додати тип автора', 'yellow-paper-classifieds'),
            'new_item_name'     => __('Назва нового типу автора', 'yellow-paper-classifieds'),
            'menu_name'         => __('Тип автора', 'yellow-paper-classifieds'),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_admin_column'  => true,
            'show_in_nav_menus'  => true,
            'show_in_rest'       => true,
            'hierarchical'       => false,
            'query_var'          => true,
            'rewrite'            => array(
                'slug'       => 'listing-author-type',
                'with_front' => false,
            ),
        );

        register_taxonomy(self::AUTHOR_TYPE_TAXONOMY, array(self::POST_TYPE), $args);
    }

}