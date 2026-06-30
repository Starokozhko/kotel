<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Listing_Images {

    const META_GALLERY_IMAGE_IDS = '_yp_gallery_image_ids';
    const MAX_IMAGES             = 7;
    const MAX_FILE_SIZE          = 5242880; // 5 MB

    public function get_gallery_image_ids($post_id) {
        $image_ids = get_post_meta($post_id, self::META_GALLERY_IMAGE_IDS, true);

        if (!is_array($image_ids)) {
            $image_ids = array();
        }

        $image_ids = array_values(array_filter(array_map('absint', $image_ids)));

        return $image_ids;
    }

    public function set_gallery_image_ids($post_id, $image_ids) {
        $image_ids = array_values(array_filter(array_map('absint', (array) $image_ids)));
        update_post_meta($post_id, self::META_GALLERY_IMAGE_IDS, $image_ids);
    }

    public function get_featured_image_id($post_id) {
        return (int) get_post_thumbnail_id($post_id);
    }

    public function validate_uploaded_files($files) {
        $errors = array();

        if (
            !isset($files['name']) ||
            !is_array($files['name']) ||
            empty(array_filter($files['name']))
        ) {
            return $errors;
        }

        $allowed_mimes = array(
            'image/jpeg',
            'image/png',
            'image/webp',
        );

        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if (empty($files['name'][$i])) {
                continue;
            }

            $error_code = isset($files['error'][$i]) ? (int) $files['error'][$i] : UPLOAD_ERR_NO_FILE;
            $file_size  = isset($files['size'][$i]) ? (int) $files['size'][$i] : 0;
            $tmp_name   = isset($files['tmp_name'][$i]) ? $files['tmp_name'][$i] : '';

            if ($error_code !== UPLOAD_ERR_OK) {
                $errors[] = sprintf(
                    __('Файл "%s" не вдалося завантажити.', 'yellow-paper-classifieds'),
                    sanitize_text_field($files['name'][$i])
                );
                continue;
            }

            if ($file_size <= 0 || $file_size > self::MAX_FILE_SIZE) {
                $errors[] = sprintf(
                    __('Файл "%s" перевищує 5 MB або має некоректний розмір.', 'yellow-paper-classifieds'),
                    sanitize_text_field($files['name'][$i])
                );
                continue;
            }

            $filetype = wp_check_filetype_and_ext($tmp_name, $files['name'][$i]);

            if (
                empty($filetype['type']) ||
                !in_array($filetype['type'], $allowed_mimes, true)
            ) {
                $errors[] = sprintf(
                    __('Файл "%s" має недозволений формат. Доступні: JPG, PNG, WEBP.', 'yellow-paper-classifieds'),
                    sanitize_text_field($files['name'][$i])
                );
            }
        }

        return $errors;
    }

    public function upload_images($post_id, $files) {
        $uploaded_ids = array();

        if (
            !isset($files['name']) ||
            !is_array($files['name']) ||
            empty(array_filter($files['name']))
        ) {
            return $uploaded_ids;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if (empty($files['name'][$i])) {
                continue;
            }

            $file = array(
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            );

            $attachment_id = media_handle_sideload($file, $post_id);

            if (!is_wp_error($attachment_id)) {
                $uploaded_ids[] = (int) $attachment_id;
            }
        }

        return $uploaded_ids;
    }

    public function delete_images($image_ids) {
        foreach ((array) $image_ids as $image_id) {
            $image_id = absint($image_id);

            if ($image_id > 0) {
                wp_delete_attachment($image_id, true);
            }
        }
    }

    public function sync_after_edit($post_id, $kept_image_ids, $featured_image_id = 0) {
        $kept_image_ids = array_values(array_filter(array_map('absint', (array) $kept_image_ids)));
        $this->set_gallery_image_ids($post_id, $kept_image_ids);

        if (!empty($featured_image_id) && in_array((int) $featured_image_id, $kept_image_ids, true)) {
            set_post_thumbnail($post_id, (int) $featured_image_id);
            return;
        }

        if (!empty($kept_image_ids)) {
            set_post_thumbnail($post_id, (int) $kept_image_ids[0]);
        } else {
            delete_post_thumbnail($post_id);
        }
    }
}