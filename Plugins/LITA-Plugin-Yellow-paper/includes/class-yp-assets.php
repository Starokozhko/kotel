<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Assets {

    public function hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    public function enqueue_frontend_assets() {
        $needs_listing_card_css = false;

        /**
         * Сторінка одного оголошення:
         * single-yp_listing.php
         */
        if (is_singular('yp_listing')) {
            $this->enqueue_css(
                'yp-single-listing',
                'assets/css/yp-single-listing.css'
            );
        }

        /**
         * Архів оголошень:
         * archive-yp_listing.php
         */
        if (is_post_type_archive('yp_listing')) {
            $this->enqueue_css(
                'yp-listings-archive',
                'assets/css/yp-listings-archive.css'
            );

            $needs_listing_card_css = true;
        }

        /**
         * Якщо це не сторінка акаунта — підключаємо тільки те,
         * що потрібно поза акаунтом, і виходимо.
         */
        if (!$this->is_yp_account_area()) {
            if ($needs_listing_card_css) {
                $this->enqueue_css(
                    'yp-listing-card',
                    'assets/css/yp-listing-card.css'
                );
            }

            return;
        }

        // Загальний CSS для всіх сторінок акаунта
        $this->enqueue_css(
            'yp-account',
            'assets/css/yp-account.css'
        );

        // Сторінки входу / реєстрації / пароля
        if ($this->is_page_by_slug(array(
            'uviyty',
            'reyestratsiya',
            'ponovyty-parol',
            'stvorennya-parolya',
        ))) {
            $this->enqueue_css(
                'yp-auth',
                'assets/css/yp-auth.css',
                array('yp-account')
            );
        }

        // Налаштування акаунта
        if ($this->is_page_by_slug('nalashtuvannya')) {
            $this->enqueue_css(
                'yp-account-settings',
                'assets/css/yp-account-settings.css',
                array('yp-account')
            );
        }

        // Подати оголошення
        if ($this->is_page_by_slug('podaty-ogoloshennya')) {
            $this->enqueue_css(
                'yp-listing-form',
                'assets/css/yp-listing-form.css',
                array('yp-account')
            );
        }

        // Мої оголошення
        if ($this->is_page_by_slug('moi-ogoloshennya')) {
            $this->enqueue_css(
                'yp-my-listings',
                'assets/css/yp-my-listings.css',
                array('yp-account')
            );

            $needs_listing_card_css = true;
        }

        // Відправити запит / Банери
        if ($this->is_page_by_slug(array('vidpravyty-zapyt', 'banery'))) {
            $this->enqueue_css(
                'yp-support-request',
                'assets/css/yp-support-request.css',
                array('yp-account')
            );
        }

        /**
         * CSS картки оголошення:
         * template-parts/listing-card.php
         */
        if ($needs_listing_card_css) {
            $this->enqueue_css(
                'yp-listing-card',
                'assets/css/yp-listing-card.css',
                array('yp-account')
            );
        }
    }

    private function enqueue_css($handle, $relative_path, $deps = array()) {
        $file_path = YP_CLASSIFIEDS_PATH . $relative_path;
        $file_url  = YP_CLASSIFIEDS_URL . $relative_path;

        wp_enqueue_style(
            $handle,
            $file_url,
            $deps,
            file_exists($file_path) ? filemtime($file_path) : YP_CLASSIFIEDS_VERSION
        );
    }

    private function is_yp_account_area() {
        return $this->is_page_by_slug(array(
            'uviyty',
            'reyestratsiya',
            'ponovyty-parol',
            'stvorennya-parolya',
            'nalashtuvannya',
            'moi-ogoloshennya',
            'podaty-ogoloshennya',
            'vidpravyty-zapyt',
            'banery',
        ));
    }

    private function is_page_by_slug($slugs) {
        if (!is_page()) {
            return false;
        }

        $slugs = (array) $slugs;

        $page = get_queried_object();

        if (!$page instanceof WP_Post) {
            return false;
        }

        return in_array($page->post_name, $slugs, true);
    }
}