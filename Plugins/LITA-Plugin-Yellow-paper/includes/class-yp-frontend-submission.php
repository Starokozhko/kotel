<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Frontend_Submission
{

    const FORM_NONCE_ACTION = 'yp_frontend_listing_submit';
    const FORM_NONCE_NAME = 'yp_frontend_listing_nonce';

    const ACTION_NONCE_ACTION = 'yp_listing_action';
    const ACTION_NONCE_NAME = 'yp_listing_action_nonce';

    const META_PRICE_TYPE = '_yp_price_type';
    const META_SPECIAL_PRICE = '_yp_special_price';
    const META_SALE_CONDITIONS = '_yp_sale_conditions';
    const META_CHARACTERISTICS = '_yp_characteristics';

    const PRICE_TYPE_NO_PRICE = 'no_price';
    const PRICE_TYPE_REGULAR = 'regular';
    const PRICE_TYPE_SALE = 'sale';
    const PRICE_TYPE_CLEARANCE = 'clearance';

    /**
     * @var YP_Listing_Images
     */
    private $listing_images;

    private $errors = array();
    private $success_message = '';

    public function __construct($listing_images)
    {
        $this->listing_images = $listing_images;
    }

    public function hooks()
    {
        add_shortcode('yp_listing_form', array($this, 'render_listing_form_shortcode'));
        add_shortcode('yp_my_listings', array($this, 'render_my_listings_shortcode'));

        add_action('init', array($this, 'handle_listing_actions'));
        add_action('init', array($this, 'handle_form_submission'));
    }

    public function handle_listing_actions()
    {
        if (!isset($_POST['yp_listing_row_action'])) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_POST[self::ACTION_NONCE_NAME])) {
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::ACTION_NONCE_NAME]));
        if (!wp_verify_nonce($nonce, self::ACTION_NONCE_ACTION)) {
            return;
        }

        $action = sanitize_text_field(wp_unslash($_POST['yp_listing_row_action']));
        $listing_id = isset($_POST['yp_listing_id']) ? absint($_POST['yp_listing_id']) : 0;

        if ($listing_id <= 0) {
            return;
        }

        $post = get_post($listing_id);

        if (!$post || $post->post_type !== YP_Post_Types::POST_TYPE) {
            return;
        }

        if (!$this->current_user_can_manage_listing($listing_id, get_current_user_id())) {
            return;
        }

        $redirect_url = $this->get_my_listings_page_url();
        if (!$redirect_url) {
            $redirect_url = home_url('/');
        }

        switch ($action) {
            case 'trash':
                if ($post->post_status !== 'trash' && current_user_can('delete_post', $listing_id)) {
                    wp_trash_post($listing_id);
                    wp_safe_redirect(add_query_arg('yp_notice', 'trashed', $redirect_url));
                    exit;
                }
                break;

            case 'restore':
                if ($post->post_status === 'trash' && current_user_can('delete_post', $listing_id)) {
                    wp_untrash_post($listing_id);

                    // Після відновлення лишаємо оголошення збереженим, але не поданим на публікацію.
                    YP_Listing_Workflow::save_listing_as_saved($listing_id);

                    wp_safe_redirect(add_query_arg('yp_notice', 'restored', $redirect_url));
                    exit;
                }
                break;

            case 'delete_permanently':
                if ($post->post_status === 'trash' && current_user_can('delete_post', $listing_id)) {
                    $image_ids = $this->listing_images->get_gallery_image_ids($listing_id);

                    if (!empty($image_ids)) {
                        $this->listing_images->delete_images($image_ids);
                    }

                    wp_delete_post($listing_id, true);

                    wp_safe_redirect(add_query_arg('yp_notice', 'deleted', $redirect_url));
                    exit;
                }
                break;
        }
    }

    public function handle_form_submission()
    {
        if (!isset($_POST['yp_frontend_form_submitted'])) {
            return;
        }

        if (!is_user_logged_in()) {
            return;
        }

        if (!isset($_POST[self::FORM_NONCE_NAME])) {
            $this->errors[] = __('Помилка безпеки. Спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $nonce = sanitize_text_field(wp_unslash($_POST[self::FORM_NONCE_NAME]));

        if (!wp_verify_nonce($nonce, self::FORM_NONCE_ACTION)) {
            $this->errors[] = __('Помилка перевірки форми. Оновіть сторінку та спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        $user_id = get_current_user_id();
        $is_private_person = yp_user_is_private_person($user_id);
        $submit_action = isset($_POST['yp_listing_submit_action']) ? sanitize_key(wp_unslash($_POST['yp_listing_submit_action'])) : 'save';
        if (!in_array($submit_action, array('save', 'submit'), true)) {
            $submit_action = 'save';
        }

        if (!current_user_can('create_yp_listings')) {
            $this->errors[] = __('У вас немає прав для створення оголошень.', 'yellow-paper-classifieds');
            return;
        }

        $listing_id = isset($_POST['yp_listing_id']) ? absint($_POST['yp_listing_id']) : 0;
        $is_edit = $listing_id > 0;

        if ($is_edit && !$this->current_user_can_edit_listing($listing_id, $user_id)) {
            $this->errors[] = __('Ви не можете редагувати це оголошення.', 'yellow-paper-classifieds');
            return;
        }

        $title = isset($_POST['yp_title']) ? sanitize_text_field(wp_unslash($_POST['yp_title'])) : '';
        $description = isset($_POST['yp_description']) ? wp_kses_post(wp_unslash($_POST['yp_description'])) : '';
        $price = isset($_POST['yp_price']) ? sanitize_text_field(wp_unslash($_POST['yp_price'])) : '';
        $special_price = isset($_POST['yp_special_price']) ? sanitize_text_field(wp_unslash($_POST['yp_special_price'])) : '';
        $sale_conditions = isset($_POST['yp_sale_conditions']) ? sanitize_textarea_field(wp_unslash($_POST['yp_sale_conditions'])) : '';
        $price_type = isset($_POST['yp_price_type']) ? sanitize_text_field(wp_unslash($_POST['yp_price_type'])) : self::PRICE_TYPE_NO_PRICE;
        $contact = isset($_POST['yp_contact_name']) ? sanitize_text_field(wp_unslash($_POST['yp_contact_name'])) : '';
        $phone_input = isset($_POST['yp_phone']) ? sanitize_text_field(wp_unslash($_POST['yp_phone'])) : '';
        $type_id = isset($_POST['yp_listing_type']) ? absint($_POST['yp_listing_type']) : 0;
        $location_id = isset($_POST['yp_location']) ? absint($_POST['yp_location']) : 0;
        $leaf_category_id = $this->get_submitted_leaf_category_id();
        $featured_image_id = isset($_POST['yp_featured_image_id']) ? absint($_POST['yp_featured_image_id']) : 0;

        $title = trim($title);
        $price = trim($price);
        $special_price = trim($special_price);
        $contact = trim($contact);

        $phone_digits = $this->normalize_phone_to_digits($phone_input);

        if ($contact === '') {
            $contact = $this->get_user_contact_name($user_id);
        }

        if ($phone_digits === '') {
            $phone_digits = $this->get_user_phone_digits($user_id);
        }

        $existing_image_ids = $is_edit ? $this->listing_images->get_gallery_image_ids($listing_id) : array();
        $remove_image_ids = $is_edit ? $this->get_requested_removal_image_ids($existing_image_ids) : array();
        $kept_image_ids = $is_edit ? array_values(array_diff($existing_image_ids, $remove_image_ids)) : array();

        $new_files = isset($_FILES['yp_images']) ? $_FILES['yp_images'] : array();
        $new_upload_errors = $this->listing_images->validate_uploaded_files($new_files);

        if (!empty($new_upload_errors)) {
            $this->errors = array_merge($this->errors, $new_upload_errors);
        }

        $new_file_count = $this->count_non_empty_uploaded_files($new_files);
        $total_images_after_save = count($kept_image_ids) + $new_file_count;

        if ($total_images_after_save > YP_Listing_Images::MAX_IMAGES) {
            $this->errors[] = sprintf(
                    __('Можна завантажити не більше %d фото для одного оголошення.', 'yellow-paper-classifieds'),
                    YP_Listing_Images::MAX_IMAGES
            );
        }

        if ($title === '') {
            $this->errors[] = __('Вкажіть заголовок оголошення.', 'yellow-paper-classifieds');
        }

        if ($description === '') {
            $this->errors[] = __('Додайте опис оголошення.', 'yellow-paper-classifieds');
        }

        if ($contact === '') {
            $this->errors[] = __('Вкажіть ім’я для зв’язку.', 'yellow-paper-classifieds');
        }

        if ($phone_digits === '') {
            $this->errors[] = __('Вкажіть номер телефону.', 'yellow-paper-classifieds');
        } elseif (!$this->is_valid_phone_digits($phone_digits)) {
            $this->errors[] = __('Телефон повинен містити 10 цифр у форматі (XXX) XXX-XX-XX.', 'yellow-paper-classifieds');
        }

        if (!$this->is_valid_type_term($type_id)) {
            $this->errors[] = __('Оберіть тип оголошення.', 'yellow-paper-classifieds');
        }

        if (!$this->is_valid_location_term($location_id)) {
            $this->errors[] = __('Оберіть населений пункт.', 'yellow-paper-classifieds');
        }

        if (!$this->is_valid_leaf_category_for_type($leaf_category_id, $type_id)) {
            $this->errors[] = __('Оберіть коректну кінцеву категорію для вибраного типу.', 'yellow-paper-classifieds');
        }

        $allowed_price_types = array(
                self::PRICE_TYPE_NO_PRICE,
                self::PRICE_TYPE_REGULAR,
                self::PRICE_TYPE_SALE,
                self::PRICE_TYPE_CLEARANCE,
        );

        if (!in_array($price_type, $allowed_price_types, true)) {
            $price_type = self::PRICE_TYPE_NO_PRICE;
        }

        if ($is_private_person) {
            $price_type = self::PRICE_TYPE_NO_PRICE;
            $price = '';
            $special_price = '';
            $sale_conditions = '';
        }

        if ($price_type === self::PRICE_TYPE_NO_PRICE) {
            $price = '';
            $special_price = '';
            $sale_conditions = '';
        } else {
            if ($price === '') {
                $this->errors[] = __('Вкажіть основну ціну.', 'yellow-paper-classifieds');
            }

            if (
                    in_array($price_type, array(self::PRICE_TYPE_SALE, self::PRICE_TYPE_CLEARANCE), true) &&
                    $special_price === ''
            ) {
                $this->errors[] = __('Для акції або розпродажу потрібно вказати спеціальну ціну.', 'yellow-paper-classifieds');
            }

            if ($price_type === self::PRICE_TYPE_SALE && $sale_conditions === '') {
                $this->errors[] = __('Для акції потрібно вказати умови акції.', 'yellow-paper-classifieds');
            }

            if ($price_type === self::PRICE_TYPE_REGULAR) {
                $special_price = '';
                $sale_conditions = '';
            }

            if ($price_type === self::PRICE_TYPE_CLEARANCE) {
                $sale_conditions = '';
            }
        }

        $characteristics = $is_private_person ? array() : $this->get_submitted_characteristics();

        if ($submit_action === 'submit' && !YP_Listing_Workflow::user_can_submit_listing($user_id, $listing_id, $leaf_category_id)) {
            $this->errors[] = YP_Listing_Workflow::get_submission_block_message($user_id, $listing_id, $leaf_category_id);
        }

        if (!empty($this->errors)) {
            return;
        }

        $post_data = array(
                'post_title' => $title,
                'post_content' => $description,
                'post_type' => YP_Post_Types::POST_TYPE,
                'post_status' => YP_Listing_Workflow::POST_STATUS_SAVED,
                'post_author' => $user_id,
        );

        if ($is_edit) {
            $post_data['ID'] = $listing_id;
            $result = wp_update_post($post_data, true);
        } else {
            $result = wp_insert_post($post_data, true);
            $listing_id = !is_wp_error($result) ? (int)$result : 0;
        }

        if (is_wp_error($result) || !$listing_id) {
            $this->errors[] = __('Не вдалося зберегти оголошення. Спробуйте ще раз.', 'yellow-paper-classifieds');
            return;
        }

        wp_set_object_terms($listing_id, array($leaf_category_id), YP_Post_Types::TAXONOMY, false);
        wp_set_object_terms($listing_id, array($location_id), YP_Post_Types::LOCATION_TAXONOMY, false);
        yp_sync_listing_author_type($listing_id, $user_id);

        update_post_meta($listing_id, '_yp_price', $price);
        update_post_meta($listing_id, self::META_PRICE_TYPE, $price_type);
        update_post_meta($listing_id, self::META_SPECIAL_PRICE, $special_price);
        update_post_meta($listing_id, self::META_SALE_CONDITIONS, $sale_conditions);
        update_post_meta($listing_id, self::META_CHARACTERISTICS, $characteristics);

        update_post_meta($listing_id, '_yp_phone', $phone_digits);
        update_post_meta($listing_id, '_yp_contact_name', $contact);

        update_post_meta($listing_id, YP_Listing_Meta::META_VISIBILITY, YP_Listing_Meta::VIS_HIDDEN);

        if ($is_edit && !empty($remove_image_ids)) {
            $this->listing_images->delete_images($remove_image_ids);
        }

        $uploaded_image_ids = $this->listing_images->upload_images($listing_id, $new_files);
        $final_image_ids = array_values(array_merge($kept_image_ids, $uploaded_image_ids));

        $this->listing_images->sync_after_edit($listing_id, $final_image_ids, $featured_image_id);

        if ($submit_action === 'submit') {
            $workflow_result = YP_Listing_Workflow::submit_listing_for_publication($listing_id, $user_id);
            if (is_wp_error($workflow_result)) {
                $this->errors[] = $workflow_result->get_error_message();
                return;
            }
        } else {
            YP_Listing_Workflow::save_listing_as_saved($listing_id);
        }

        $redirect_url = $this->get_my_listings_page_url();

        if ($redirect_url) {
            $redirect_url = add_query_arg(
                    array(
                            'yp_notice' => $submit_action === 'submit' ? 'submitted' : ($is_edit ? 'updated' : 'created'),
                    ),
                    $redirect_url
            );

            wp_safe_redirect($redirect_url);
            exit;
        }

        $this->success_message = $submit_action === 'submit'
                ? __('Оголошення відправлено на публікацію.', 'yellow-paper-classifieds')
                : ($is_edit ? __('Оголошення збережено.', 'yellow-paper-classifieds') : __('Оголошення збережено.', 'yellow-paper-classifieds'));
    }

    public function render_listing_form_shortcode($atts = array())
    {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Щоб подати оголошення, увійдіть на сайт.', 'yellow-paper-classifieds') . '</p>';
        }

        if (!current_user_can('create_yp_listings')) {
            return '<p>' . esc_html__('У вас немає прав для подачі оголошень.', 'yellow-paper-classifieds') . '</p>';
        }

        $user_id = get_current_user_id();
        $is_private_person = yp_user_is_private_person($user_id);
        $can_submit_listing = true;
        $private_free_slots_used = YP_Listing_Workflow::get_private_person_free_slots_used($user_id, $listing_id);
        $private_free_slots_left = max(0, YP_Listing_Workflow::FREE_PRIVATE_PERSON_LIMIT - $private_free_slots_used);
        $account_payment_status = YP_Listing_Workflow::get_account_payment_status($user_id);
        $listing_id = isset($_GET['yp_edit_listing']) ? absint($_GET['yp_edit_listing']) : 0;
        $is_edit = $listing_id > 0;

        $type_terms = $this->get_type_terms();
        $default_type_id = $this->get_default_type_id($type_terms);
        $location_terms = $this->get_location_terms();
        $default_location = !empty($location_terms) ? (int)$location_terms[0]->term_id : 0;

        $values = array(
                'title' => '',
                'description' => '',
                'price' => '',
                'price_type' => self::PRICE_TYPE_NO_PRICE,
                'special_price' => '',
                'sale_conditions' => '',
                'characteristics' => array(
                        array('label' => '', 'value' => ''),
                ),
                'phone' => $this->format_phone_for_display($this->get_user_phone_digits($user_id)),
                'contact_name' => $this->get_user_contact_name($user_id),
                'type' => $default_type_id,
                'location' => $default_location,
                'category_path' => array(),
        );

        $existing_image_ids = array();
        $featured_image_id = 0;

        if ($is_edit) {
            if (!$this->current_user_can_edit_listing($listing_id, $user_id)) {
                return '<p>' . esc_html__('Ви не можете редагувати це оголошення.', 'yellow-paper-classifieds') . '</p>';
            }

            $post = get_post($listing_id);

            if (!$post || $post->post_type !== YP_Post_Types::POST_TYPE) {
                return '<p>' . esc_html__('Оголошення не знайдено.', 'yellow-paper-classifieds') . '</p>';
            }

            $leaf_category_id = $this->get_primary_term_id($listing_id);
            $existing_image_ids = $this->listing_images->get_gallery_image_ids($listing_id);
            $featured_image_id = $this->listing_images->get_featured_image_id($listing_id);

            $values['title'] = $post->post_title;
            $values['description'] = $post->post_content;
            $values['price'] = get_post_meta($listing_id, '_yp_price', true);
            $values['price_type'] = $this->normalize_price_type_for_display(get_post_meta($listing_id, self::META_PRICE_TYPE, true), $values['price']);
            $values['special_price'] = get_post_meta($listing_id, self::META_SPECIAL_PRICE, true);
            $values['sale_conditions'] = get_post_meta($listing_id, self::META_SALE_CONDITIONS, true);
            $values['characteristics'] = $this->get_saved_characteristics($listing_id);
            $values['phone'] = $this->format_phone_for_display(get_post_meta($listing_id, '_yp_phone', true));
            $values['contact_name'] = get_post_meta($listing_id, '_yp_contact_name', true);
            $values['type'] = $this->get_root_type_id_from_category($leaf_category_id, $default_type_id);
            $values['location'] = $this->get_primary_location_id($listing_id, $default_location);
            $values['category_path'] = $this->get_category_path_below_type($leaf_category_id, $values['type']);
        }

        if (!empty($_POST['yp_frontend_form_submitted'])) {
            $values['title'] = isset($_POST['yp_title']) ? sanitize_text_field(wp_unslash($_POST['yp_title'])) : $values['title'];
            $values['description'] = isset($_POST['yp_description']) ? wp_kses_post(wp_unslash($_POST['yp_description'])) : $values['description'];
            $values['price'] = isset($_POST['yp_price']) ? sanitize_text_field(wp_unslash($_POST['yp_price'])) : $values['price'];
            $values['price_type'] = isset($_POST['yp_price_type']) ? sanitize_text_field(wp_unslash($_POST['yp_price_type'])) : $values['price_type'];
            $values['special_price'] = isset($_POST['yp_special_price']) ? sanitize_text_field(wp_unslash($_POST['yp_special_price'])) : $values['special_price'];
            $values['sale_conditions'] = isset($_POST['yp_sale_conditions']) ? sanitize_textarea_field(wp_unslash($_POST['yp_sale_conditions'])) : $values['sale_conditions'];
            $values['characteristics'] = $this->get_raw_submitted_characteristics_for_form();
            $values['phone'] = isset($_POST['yp_phone']) ? sanitize_text_field(wp_unslash($_POST['yp_phone'])) : $values['phone'];
            $values['contact_name'] = isset($_POST['yp_contact_name']) ? sanitize_text_field(wp_unslash($_POST['yp_contact_name'])) : $values['contact_name'];
            $values['type'] = isset($_POST['yp_listing_type']) ? absint($_POST['yp_listing_type']) : $values['type'];
            $values['location'] = isset($_POST['yp_location']) ? absint($_POST['yp_location']) : $values['location'];
            $values['category_path'] = $this->get_submitted_category_path();

            if ($is_edit) {
                $remove_image_ids = $this->get_requested_removal_image_ids($existing_image_ids);
                $existing_image_ids = array_values(array_diff($existing_image_ids, $remove_image_ids));
            }

            $featured_image_id = isset($_POST['yp_featured_image_id']) ? absint($_POST['yp_featured_image_id']) : $featured_image_id;
        }

        if (empty($values['characteristics'])) {
            $values['characteristics'] = array(
                    array('label' => '', 'value' => ''),
            );
        }

        $values['price_type'] = $this->normalize_price_type_for_display($values['price_type'], $values['price']);

        if ($is_private_person) {
            $values['price_type'] = self::PRICE_TYPE_NO_PRICE;
            $values['price'] = '';
            $values['special_price'] = '';
            $values['sale_conditions'] = '';
            $values['characteristics'] = array();
        } elseif ($values['price_type'] === self::PRICE_TYPE_NO_PRICE) {
            $values['price'] = '';
            $values['special_price'] = '';
            $values['sale_conditions'] = '';
        }

        $category_levels = $this->build_category_levels($values['type'], $values['category_path']);

        // Підключаємо WordPress WYSIWYG редактор для поля опису на фронтенді.
        if (function_exists('wp_enqueue_editor')) {
            wp_enqueue_editor();
        }

        ob_start();

        if (class_exists('YP_Account')) {
            echo YP_Account::render_account_nav();
        }

        if (!empty($this->errors)) {
            echo '<div class="yp-form-errors" style="margin-bottom:20px;padding:12px;border:1px solid #d63638;background:#fff5f5;">';
            foreach ($this->errors as $error) {
                echo '<p style="margin:0 0 8px;">' . esc_html($error) . '</p>';
            }
            echo '</div>';
        }

        if ($this->success_message !== '') {
            echo '<div class="yp-form-success" style="margin-bottom:20px;padding:12px;border:1px solid #70a647;background:#f6fff6;">';
            echo '<p style="margin:0;">' . esc_html($this->success_message) . '</p>';
            echo '</div>';
        }
        ?>

        <form method="post" enctype="multipart/form-data" class="yp-listing-form">
            <?php wp_nonce_field(self::FORM_NONCE_ACTION, self::FORM_NONCE_NAME); ?>
            <input type="hidden" name="yp_frontend_form_submitted" value="1">
            <input type="hidden" name="yp_listing_id" value="<?php echo esc_attr($listing_id); ?>">

            <p>
                <label for="yp_listing_type"><strong><?php esc_html_e('Тип оголошення', 'yellow-paper-classifieds'); ?></strong></label><br>
                <select id="yp_listing_type" name="yp_listing_type" style="width:100%;" required>
                    <?php foreach ($type_terms as $type_term) : ?>
                        <option value="<?php echo esc_attr($type_term->term_id); ?>" <?php selected((int)$values['type'], (int)$type_term->term_id); ?>>
                            <?php echo esc_html($type_term->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <div id="yp-category-levels-wrap">
                <?php foreach ($category_levels as $index => $level) : ?>
                    <p class="yp-category-level-item" data-level="<?php echo esc_attr($index + 1); ?>">
                        <label for="yp_category_level_<?php echo esc_attr($index + 1); ?>">
                            <strong>
                                <?php
                                echo esc_html(
                                        $index === 0
                                                ? __('Категорія', 'yellow-paper-classifieds')
                                                : sprintf(__('Підкатегорія %d', 'yellow-paper-classifieds'), $index)
                                );
                                ?>
                            </strong>
                        </label><br>
                        <select
                                id="yp_category_level_<?php echo esc_attr($index + 1); ?>"
                                name="yp_category_level_<?php echo esc_attr($index + 1); ?>"
                                style="width:100%;"
                                required
                        >
                            <option value=""><?php esc_html_e('Оберіть категорію', 'yellow-paper-classifieds'); ?></option>
                            <?php foreach ($level['terms'] as $term) : ?>
                                <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected((int)$level['selected'], (int)$term->term_id); ?>>
                                    <?php echo esc_html($term->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </p>
                <?php endforeach; ?>
            </div>

            <p>
                <label for="yp_title"><strong><?php esc_html_e('Заголовок', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" id="yp_title" name="yp_title" value="<?php echo esc_attr($values['title']); ?>"
                       style="width:100%;" required>
            </p>

            <div class="yp-field yp-field--description">
                <label for="yp_description"><strong><?php esc_html_e('Опис', 'yellow-paper-classifieds'); ?></strong></label>
                <?php
                wp_editor(
                        $values['description'],
                        'yp_description',
                        array(
                                'textarea_name' => 'yp_description',
                                'textarea_rows' => 8,
                                'media_buttons' => false,
                                'teeny' => false,
                                'quicktags' => true,
                                'tinymce' => array(
                                        'toolbar1' => 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo',
                                        'toolbar2' => '',
                                        'block_formats' => 'Абзац=p; Заголовок 3=h3; Заголовок 4=h4',
                                ),
                        )
                );
                ?>
            </div>

            <?php if (!$is_private_person) : ?>
            <p>
                <label for="yp_price_type"><strong><?php esc_html_e('Тип ціни', 'yellow-paper-classifieds'); ?></strong></label><br>
                <select id="yp_price_type" name="yp_price_type" style="width:100%;" required>
                    <option value="<?php echo esc_attr(self::PRICE_TYPE_NO_PRICE); ?>" <?php selected($values['price_type'], self::PRICE_TYPE_NO_PRICE); ?>>
                        <?php esc_html_e('Без вказання ціни', 'yellow-paper-classifieds'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::PRICE_TYPE_REGULAR); ?>" <?php selected($values['price_type'], self::PRICE_TYPE_REGULAR); ?>>
                        <?php esc_html_e('Звичайна', 'yellow-paper-classifieds'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::PRICE_TYPE_SALE); ?>" <?php selected($values['price_type'], self::PRICE_TYPE_SALE); ?>>
                        <?php esc_html_e('Акція', 'yellow-paper-classifieds'); ?>
                    </option>
                    <option value="<?php echo esc_attr(self::PRICE_TYPE_CLEARANCE); ?>" <?php selected($values['price_type'], self::PRICE_TYPE_CLEARANCE); ?>>
                        <?php esc_html_e('Розпродаж', 'yellow-paper-classifieds'); ?>
                    </option>
                </select>
            </p>

            <p id="yp-price-wrap" style="<?php echo $values['price_type'] === self::PRICE_TYPE_NO_PRICE ? 'display:none;' : ''; ?>">
                <label for="yp_price"><strong><?php esc_html_e('Основна ціна', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" id="yp_price" name="yp_price" value="<?php echo esc_attr($values['price']); ?>"
                       style="width:100%;" <?php echo $values['price_type'] === self::PRICE_TYPE_NO_PRICE ? '' : 'required'; ?>>
            </p>

            <div id="yp-special-price-wrap"
                 style="<?php echo in_array($values['price_type'], array(self::PRICE_TYPE_SALE, self::PRICE_TYPE_CLEARANCE), true) ? '' : 'display:none;'; ?>">
                <p>
                    <label for="yp_special_price"><strong><?php esc_html_e('Спеціальна ціна', 'yellow-paper-classifieds'); ?></strong></label><br>
                    <input type="text" id="yp_special_price" name="yp_special_price"
                           value="<?php echo esc_attr($values['special_price']); ?>" style="width:100%;">
                </p>
            </div>

            <div id="yp-sale-conditions-wrap"
                 style="<?php echo $values['price_type'] === self::PRICE_TYPE_SALE ? '' : 'display:none;'; ?>">
                <p>
                    <label for="yp_sale_conditions"><strong><?php esc_html_e('Умови акції', 'yellow-paper-classifieds'); ?></strong></label><br>
                    <textarea id="yp_sale_conditions" name="yp_sale_conditions" rows="4"
                              style="width:100%;"><?php echo esc_textarea($values['sale_conditions']); ?></textarea>
                </p>
            </div>

            <?php endif; ?>

            <?php if (!$is_private_person) : ?>
            <div class="yp-characteristics-wrap" style="margin-bottom:20px;">
                <p><strong><?php esc_html_e('Характеристики', 'yellow-paper-classifieds'); ?></strong></p>

                <div id="yp-characteristics-list">
                    <?php foreach ($values['characteristics'] as $row) : ?>
                        <div class="yp-characteristic-row"
                             style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;">
                            <input
                                    type="text"
                                    name="yp_characteristics[label][]"
                                    value="<?php echo esc_attr(isset($row['label']) ? $row['label'] : ''); ?>"
                                    placeholder="<?php esc_attr_e('Назва', 'yellow-paper-classifieds'); ?>"
                                    style="width:40%;"
                            >
                            <input
                                    type="text"
                                    name="yp_characteristics[value][]"
                                    value="<?php echo esc_attr(isset($row['value']) ? $row['value'] : ''); ?>"
                                    placeholder="<?php esc_attr_e('Значення', 'yellow-paper-classifieds'); ?>"
                                    style="width:40%;"
                            >
                            <button type="button"
                                    class="yp-remove-characteristic"><?php esc_html_e('Видалити', 'yellow-paper-classifieds'); ?></button>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p>
                    <button type="button"
                            id="yp-add-characteristic"><?php esc_html_e('Додати характеристику', 'yellow-paper-classifieds'); ?></button>
                </p>
            </div>

            <?php endif; ?>

            <p>
                <label for="yp_contact_name"><strong><?php esc_html_e('Ім’я для зв’язку', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input type="text" id="yp_contact_name" name="yp_contact_name"
                       value="<?php echo esc_attr($values['contact_name']); ?>" style="width:100%;" required>
            </p>

            <p>
                <label for="yp_phone"><strong><?php esc_html_e('Телефон', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input
                        type="text"
                        id="yp_phone"
                        name="yp_phone"
                        value="<?php echo esc_attr($values['phone']); ?>"
                        style="width:100%;"
                        placeholder="(096) 123-45-67"
                        inputmode="numeric"
                        maxlength="15"
                        required
                >
            </p>

            <p>
                <label for="yp_location"><strong><?php esc_html_e('Населений пункт', 'yellow-paper-classifieds'); ?></strong></label><br>
                <select id="yp_location" name="yp_location" style="width:100%;" required>
                    <option value=""><?php esc_html_e('Оберіть населений пункт', 'yellow-paper-classifieds'); ?></option>
                    <?php foreach ($location_terms as $location_term) : ?>
                        <option value="<?php echo esc_attr($location_term->term_id); ?>" <?php selected((int)$values['location'], (int)$location_term->term_id); ?>>
                            <?php echo esc_html($location_term->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <?php if (!empty($existing_image_ids)) : ?>
                <div class="yp-existing-images" style="margin-bottom:20px;">
                    <p><strong><?php esc_html_e('Поточні фото', 'yellow-paper-classifieds'); ?></strong></p>

                    <div style="display:flex;flex-wrap:wrap;gap:16px;">
                        <?php foreach ($existing_image_ids as $image_id) : ?>
                            <?php
                            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                            if (!$image_url) {
                                continue;
                            }
                            ?>
                            <div style="border:1px solid #ddd;padding:10px;width:160px;">
                                <p style="margin:0 0 10px;">
                                    <img src="<?php echo esc_url($image_url); ?>" alt=""
                                         style="width:100%;height:auto;display:block;">
                                </p>

                                <p style="margin:0 0 8px;">
                                    <label>
                                        <input
                                                type="radio"
                                                name="yp_featured_image_id"
                                                value="<?php echo esc_attr($image_id); ?>"
                                                <?php checked((int)$featured_image_id, (int)$image_id); ?>
                                        >
                                        <?php esc_html_e('Головне фото', 'yellow-paper-classifieds'); ?>
                                    </label>
                                </p>

                                <p style="margin:0;">
                                    <label>
                                        <input
                                                type="checkbox"
                                                name="yp_remove_image_ids[]"
                                                value="<?php echo esc_attr($image_id); ?>"
                                        >
                                        <?php esc_html_e('Видалити', 'yellow-paper-classifieds'); ?>
                                    </label>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <p>
                <label for="yp_images"><strong><?php esc_html_e('Фото', 'yellow-paper-classifieds'); ?></strong></label><br>
                <input
                        type="file"
                        id="yp_images"
                        name="yp_images[]"
                        multiple
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >
                <br>
                <small>
                    <?php
                    echo esc_html(
                            sprintf(
                                    __('Можна завантажити до %d фото. Формати: JPG, PNG, WEBP. До 5 MB кожне.', 'yellow-paper-classifieds'),
                                    YP_Listing_Images::MAX_IMAGES
                            )
                    );
                    ?>
                </small>
            </p>

            <p style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                <button type="submit" name="yp_listing_submit_action" value="save">
                    <?php esc_html_e('Зберегти', 'yellow-paper-classifieds'); ?>
                </button>

                <button type="submit" name="yp_listing_submit_action" value="submit">
                    <?php esc_html_e('Відправити на публікацію', 'yellow-paper-classifieds'); ?>
                </button>
            </p>

            <?php if ($is_private_person) : ?>
                <p class="yp-payment-required-message" style="margin-top:-8px;color:#555;">
                    <?php
                    echo esc_html(sprintf(
                        __('Приватна особа може мати до %1$d безкоштовних активних оголошень. Зараз доступно: %2$d. Категорії “Повідомлення → Загубив / Знайшов” безкоштовні для всіх і не враховуються в цей ліміт.', 'yellow-paper-classifieds'),
                        YP_Listing_Workflow::FREE_PRIVATE_PERSON_LIMIT,
                        $private_free_slots_left
                    ));
                    ?>
                </p>
            <?php elseif (YP_Listing_Workflow::get_account_payment_status($user_id) !== YP_Listing_Workflow::ACCOUNT_PAYMENT_ACTIVE) : ?>
                <p class="yp-payment-required-message" style="margin-top:-8px;color:#555;">
                    <?php esc_html_e('Платні категорії потребують активної оплати акаунта. Категорії “Повідомлення → Загубив / Знайшов” безкоштовні для всіх.', 'yellow-paper-classifieds'); ?>
                </p>
            <?php endif; ?>
        </form>

        <script>
            (function () {
                var typeSelect = document.getElementById('yp_listing_type');
                var wrap = document.getElementById('yp-category-levels-wrap');
                var phoneInput = document.getElementById('yp_phone');
                var priceTypeSelect = document.getElementById('yp_price_type');
                var priceWrap = document.getElementById('yp-price-wrap');
                var priceInput = document.getElementById('yp_price');
                var specialPriceWrap = document.getElementById('yp-special-price-wrap');
                var saleConditionsWrap = document.getElementById('yp-sale-conditions-wrap');
                var addCharacteristicBtn = document.getElementById('yp-add-characteristic');
                var characteristicsList = document.getElementById('yp-characteristics-list');

                if (phoneInput) {
                    phoneInput.addEventListener('input', function () {
                        var digits = this.value.replace(/\D/g, '').slice(0, 10);
                        var formatted = '';

                        if (digits.length > 0) {
                            formatted += '(' + digits.substring(0, 3);
                        }
                        if (digits.length >= 4) {
                            formatted += ') ' + digits.substring(3, 6);
                        }
                        if (digits.length >= 7) {
                            formatted += '-' + digits.substring(6, 8);
                        }
                        if (digits.length >= 9) {
                            formatted += '-' + digits.substring(8, 10);
                        }

                        this.value = formatted;
                    });
                }

                if (priceTypeSelect && priceWrap && priceInput && specialPriceWrap && saleConditionsWrap) {
                    function refreshPriceFields() {
                        var value = priceTypeSelect.value;
                        var isNoPrice = value === 'no_price';

                        if (isNoPrice) {
                            priceWrap.style.display = 'none';
                            priceInput.removeAttribute('required');
                            priceInput.value = '';
                        } else {
                            priceWrap.style.display = '';
                            priceInput.setAttribute('required', 'required');
                        }

                        if (!isNoPrice && (value === 'sale' || value === 'clearance')) {
                            specialPriceWrap.style.display = '';
                        } else {
                            specialPriceWrap.style.display = 'none';
                        }

                        if (!isNoPrice && value === 'sale') {
                            saleConditionsWrap.style.display = '';
                        } else {
                            saleConditionsWrap.style.display = 'none';
                        }
                    }

                    priceTypeSelect.addEventListener('change', refreshPriceFields);
                    refreshPriceFields();
                }

                if (addCharacteristicBtn && characteristicsList) {
                    addCharacteristicBtn.addEventListener('click', function () {
                        var row = document.createElement('div');
                        row.className = 'yp-characteristic-row';
                        row.style.display = 'flex';
                        row.style.gap = '10px';
                        row.style.alignItems = 'flex-start';
                        row.style.marginBottom = '10px';

                        row.innerHTML =
                            '<input type="text" name="yp_characteristics[label][]" placeholder="Назва" style="width:40%;">' +
                            '<input type="text" name="yp_characteristics[value][]" placeholder="Значення" style="width:40%;">' +
                            '<button type="button" class="yp-remove-characteristic">Видалити</button>';

                        characteristicsList.appendChild(row);
                    });

                    characteristicsList.addEventListener('click', function (e) {
                        if (e.target.classList.contains('yp-remove-characteristic')) {
                            var rows = characteristicsList.querySelectorAll('.yp-characteristic-row');

                            if (rows.length > 1) {
                                e.target.closest('.yp-characteristic-row').remove();
                            } else {
                                var inputs = rows[0].querySelectorAll('input');
                                inputs.forEach(function (input) {
                                    input.value = '';
                                });
                            }
                        }
                    });
                }

                if (!typeSelect || !wrap) {
                    return;
                }

                var categoryTree = <?php echo wp_json_encode($this->get_category_tree_for_js($type_terms)); ?>;
                var currentPath = <?php echo wp_json_encode(array_values(array_map('intval', $values['category_path']))); ?>;

                function getChildren(parentId) {
                    parentId = String(parentId);
                    return categoryTree[parentId] || [];
                }

                function createLevelSelect(levelIndex, parentId, selectedId) {
                    var children = getChildren(parentId);

                    if (!children.length) {
                        return null;
                    }

                    var p = document.createElement('p');
                    p.className = 'yp-category-level-item';
                    p.setAttribute('data-level', levelIndex);

                    var label = document.createElement('label');
                    label.setAttribute('for', 'yp_category_level_' + levelIndex);

                    var strong = document.createElement('strong');
                    strong.textContent = levelIndex === 1 ? 'Категорія' : ('Підкатегорія ' + (levelIndex - 1));
                    label.appendChild(strong);

                    var br = document.createElement('br');

                    var select = document.createElement('select');
                    select.id = 'yp_category_level_' + levelIndex;
                    select.name = 'yp_category_level_' + levelIndex;
                    select.style.width = '100%';
                    select.required = true;

                    var placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = 'Оберіть категорію';
                    select.appendChild(placeholder);

                    children.forEach(function (term) {
                        var option = document.createElement('option');
                        option.value = term.id;
                        option.textContent = term.name;

                        if (parseInt(selectedId, 10) === parseInt(term.id, 10)) {
                            option.selected = true;
                        }

                        select.appendChild(option);
                    });

                    select.addEventListener('change', function () {
                        removeLevelsAfter(levelIndex);
                        if (this.value) {
                            createAndAppendNextLevel(levelIndex + 1, this.value, '');
                        }
                    });

                    p.appendChild(label);
                    p.appendChild(br);
                    p.appendChild(select);

                    return p;
                }

                function removeLevelsAfter(levelIndex) {
                    var items = wrap.querySelectorAll('.yp-category-level-item');
                    items.forEach(function (item) {
                        var currentLevel = parseInt(item.getAttribute('data-level'), 10);
                        if (currentLevel > levelIndex) {
                            item.remove();
                        }
                    });
                }

                function createAndAppendNextLevel(levelIndex, parentId, selectedId) {
                    var next = createLevelSelect(levelIndex, parentId, selectedId);
                    if (next) {
                        wrap.appendChild(next);
                    }
                }

                function rebuildCategoryLevels() {
                    wrap.innerHTML = '';
                    var typeId = typeSelect.value;
                    var level = 1;
                    var parentId = typeId;

                    while (true) {
                        var selectedId = currentPath[level - 1] ? currentPath[level - 1] : '';
                        var levelEl = createLevelSelect(level, parentId, selectedId);

                        if (!levelEl) {
                            break;
                        }

                        wrap.appendChild(levelEl);

                        if (!selectedId) {
                            break;
                        }

                        parentId = selectedId;
                        level++;
                    }
                }

                typeSelect.addEventListener('change', function () {
                    currentPath = [];
                    rebuildCategoryLevels();
                });

                rebuildCategoryLevels();
            })();
        </script>

        <?php
        return ob_get_clean();
    }

    public function render_my_listings_shortcode($atts = array())
    {
        if (!is_user_logged_in()) {
            return '<p>' . esc_html__('Щоб бачити свої оголошення, увійдіть на сайт.', 'yellow-paper-classifieds') . '</p>';
        }

        $user_id = get_current_user_id();

        $active_listings = get_posts(array(
                'post_type' => YP_Post_Types::POST_TYPE,
                'post_status' => array(YP_Listing_Workflow::POST_STATUS_SAVED, 'draft', 'pending', 'publish', 'private'),
                'author' => $user_id,
                'posts_per_page' => 100,
                'orderby' => 'date',
                'order' => 'DESC',
        ));

        $trashed_listings = get_posts(array(
                'post_type' => YP_Post_Types::POST_TYPE,
                'post_status' => array('trash'),
                'author' => $user_id,
                'posts_per_page' => 100,
                'orderby' => 'date',
                'order' => 'DESC',
        ));

        $notice = isset($_GET['yp_notice']) ? sanitize_text_field(wp_unslash($_GET['yp_notice'])) : '';

        ob_start();

        if (class_exists('YP_Account')) {
            echo YP_Account::render_account_nav();
        }

        switch ($notice) {
            case 'created':
                $this->render_notice(__('Оголошення збережено.', 'yellow-paper-classifieds'));
                break;
            case 'updated':
                $this->render_notice(__('Оголошення оновлено та збережено.', 'yellow-paper-classifieds'));
                break;
            case 'submitted':
                $this->render_notice(__('Оголошення відправлено на публікацію.', 'yellow-paper-classifieds'));
                break;
            case 'trashed':
                $this->render_notice(__('Оголошення переміщено у корзину.', 'yellow-paper-classifieds'));
                break;
            case 'restored':
                $this->render_notice(__('Оголошення відновлено та збережено. Його можна повторно відправити на публікацію.', 'yellow-paper-classifieds'));
                break;
            case 'deleted':
                $this->render_notice(__('Оголошення видалено назавжди.', 'yellow-paper-classifieds'));
                break;
        }

        $submission_page = get_page_by_path('podaty-ogoloshennya');
        $base_url = $submission_page ? get_permalink($submission_page->ID) : home_url('/');

        echo '<div class="yp-my-listings-section">';

        echo '<h2>Список оголошень</h2>';

        if (empty($active_listings)) {
            echo '<p>' . esc_html__('У вас поки немає активних або чернеткових оголошень.', 'yellow-paper-classifieds') . '</p>';
        } else {
            echo '<div class="yp-my-listings">';
            foreach ($active_listings as $listing) {
                $this->render_listing_card($listing, $base_url, false);
            }
            echo '</div>';
        }

        echo '</div>';

        echo '<div class="yp-trashed-listings-section" style="margin-top:32px;">';
        echo '<h2>' . esc_html__('Неактивні оголошення / Корзина', 'yellow-paper-classifieds') . '</h2>';

        if (empty($trashed_listings)) {
            echo '<p>' . esc_html__('У корзині немає оголошень.', 'yellow-paper-classifieds') . '</p>';
        } else {
            echo '<div class="yp-trashed-listings">';
            foreach ($trashed_listings as $listing) {
                $this->render_listing_card($listing, $base_url, true);
            }
            echo '</div>';
        }

        echo '</div>';

        return ob_get_clean();
    }

    private function render_notice($message)
    {
        echo '<div style="margin-bottom:20px;padding:12px;border:1px solid #70a647;background:#f6fff6;">';
        echo '<p style="margin:0;">' . esc_html($message) . '</p>';
        echo '</div>';
    }

    private function render_listing_card($listing, $base_url, $is_trashed = false)
    {
        $edit_url = add_query_arg('yp_edit_listing', $listing->ID, $base_url);
        $payment_status = get_post_meta($listing->ID, YP_Listing_Meta::META_PAYMENT_STATUS, true);
        $moderation_status = get_post_meta($listing->ID, YP_Listing_Meta::META_MODERATION_STATUS, true);
        $visibility = get_post_meta($listing->ID, YP_Listing_Meta::META_VISIBILITY, true);
        $submission_status = get_post_meta($listing->ID, YP_Listing_Workflow::META_SUBMISSION_STATUS, true);
        $category_id = $this->get_primary_term_id($listing->ID);
        $type_id = $this->get_root_type_id_from_category($category_id, 0);
        $location_id = $this->get_primary_location_id($listing->ID, 0);

        $type_name = $type_id ? $this->get_term_name($type_id, YP_Post_Types::TAXONOMY) : '';
        $category_name = $category_id ? $this->get_term_name($category_id, YP_Post_Types::TAXONOMY) : '';
        $location_name = $location_id ? $this->get_term_name($location_id, YP_Post_Types::LOCATION_TAXONOMY) : '';
        $phone_digits = get_post_meta($listing->ID, '_yp_phone', true);
        $phone_formatted = $this->format_phone_for_display($phone_digits);
        $image_ids = $this->listing_images->get_gallery_image_ids($listing->ID);
        $thumb_url = !empty($image_ids) ? wp_get_attachment_image_url($image_ids[0], 'thumbnail') : '';

        $price = get_post_meta($listing->ID, '_yp_price', true);
        $price_type = $this->normalize_price_type_for_display(get_post_meta($listing->ID, self::META_PRICE_TYPE, true), $price);
        $special_price = get_post_meta($listing->ID, self::META_SPECIAL_PRICE, true);
        $characteristics = get_post_meta($listing->ID, self::META_CHARACTERISTICS, true);
        $char_count = is_array($characteristics) ? count($characteristics) : 0;


        echo '<div class="yp-my-listings-item" style="margin-bottom:18px;padding:14px;border:1px solid #ddd;">';

//        if ($thumb_url) {
//            echo '<p style="margin-top:0;"><img src="' . esc_url($thumb_url) . '" alt="" style="max-width:160px;height:auto;display:block;"></p>';
//        }

        echo '<h3 >' . esc_html(get_the_title($listing)) . '</h3>';
        echo '<div class="yp-my-listings-item-wrap">';
        echo '<div class="yp-my-listings-item-info">';
        if ($type_name !== '') {
            echo '<p><strong>' . esc_html__('Тип:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($type_name) . '</p>';
        }

        if ($category_name !== '') {
            echo '<p><strong>' . esc_html__('Категорія:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($category_name) . '</p>';
        }
        if ($location_name !== '') {
            echo '<p><strong>' . esc_html__('Населений пункт:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($location_name) . '</p>';
        }
        echo '</div>';


//        if ($phone_formatted !== '') {
//            echo '<p><strong>' . esc_html__('Телефон:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($phone_formatted) . '</p>';
//        }

//        echo '<p><strong>' . esc_html__('Тип ціни:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($this->get_price_type_label($price_type)) . '</p>';
//        echo '<p><strong>' . esc_html__('Основна ціна:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($price) . '</p>';

//        if ($special_price !== '') {
//            echo '<p><strong>' . esc_html__('Спеціальна ціна:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($special_price) . '</p>';
//        }

//        echo '<p><strong>' . esc_html__('Характеристики:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($char_count) . '</p>';
//        echo '<p><strong>' . esc_html__('Фото:', 'yellow-paper-classifieds') . '</strong> ' . esc_html(count($image_ids)) . '</p>';
        echo '<div class="yp-my-listings-item-info">';
        echo '<p><strong>' . esc_html__('Статус запису:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($listing->post_status) . '</p>';
        echo '<p><strong>' . esc_html__('Статус подання:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($submission_status ? $submission_status : '—') . '</p>';
        echo '<p><strong>' . esc_html__('Оплата:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($payment_status ? $payment_status : YP_Listing_Meta::PAYMENT_UNPAID) . '</p>';
        echo '<p><strong>' . esc_html__('Модерація:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($moderation_status ? $moderation_status : YP_Listing_Meta::MOD_PENDING) . '</p>';
        echo '<p><strong>' . esc_html__('Видимість:', 'yellow-paper-classifieds') . '</strong> ' . esc_html($visibility ? $visibility : YP_Listing_Meta::VIS_HIDDEN) . '</p>';
        echo '</div>';
        echo '</div>';
        echo '<div class="btn-wrapper" style="display:flex;gap:10px;flex-wrap:wrap;">';

        if (!$is_trashed) {
            echo '<a class="edit-link" href="' . esc_url($edit_url) . '">' . esc_html__('Редагувати', 'yellow-paper-classifieds') . '</a>';

            echo '<form method="post" style="margin:0;">';
            wp_nonce_field(self::ACTION_NONCE_ACTION, self::ACTION_NONCE_NAME);
            echo '<input type="hidden" name="yp_listing_id" value="' . esc_attr($listing->ID) . '">';
            echo '<input type="hidden" name="yp_listing_row_action" value="trash">';
            echo '<button type="submit" class="btn-trash" onclick="return confirm(\'' . esc_js(__('Перемістити це оголошення у корзину?', 'yellow-paper-classifieds')) . '\');">';
            echo esc_html__('У корзину', 'yellow-paper-classifieds');
            echo '</button>';
            echo '</form>';
        } else {
            echo '<form method="post" style="margin:0;">';
            wp_nonce_field(self::ACTION_NONCE_ACTION, self::ACTION_NONCE_NAME);
            echo '<input type="hidden" name="yp_listing_id" value="' . esc_attr($listing->ID) . '">';
            echo '<input type="hidden" name="yp_listing_row_action" value="restore">';
            echo '<button type="submit" class="edit-link">';
            echo esc_html__('Відновити', 'yellow-paper-classifieds');
            echo '</button>';
            echo '</form>';

            echo '<form method="post" style="margin:0;">';
            wp_nonce_field(self::ACTION_NONCE_ACTION, self::ACTION_NONCE_NAME);
            echo '<input type="hidden" name="yp_listing_id" value="' . esc_attr($listing->ID) . '">';
            echo '<input type="hidden" name="yp_listing_row_action" value="delete_permanently">';
            echo '<button type="submit" class="btn-trash" onclick="return confirm(\'' . esc_js(__('Видалити це оголошення назавжди? Цю дію не можна скасувати.', 'yellow-paper-classifieds')) . '\');">';
            echo esc_html__('Видалити назавжди', 'yellow-paper-classifieds');
            echo '</button>';
            echo '</form>';
        }

        echo '</div>';
        echo '</div>';
    }

    private function get_price_type_label($price_type)
    {
        switch ($price_type) {
            case self::PRICE_TYPE_NO_PRICE:
                return __('Без вказання ціни', 'yellow-paper-classifieds');
            case self::PRICE_TYPE_SALE:
                return __('Акція', 'yellow-paper-classifieds');
            case self::PRICE_TYPE_CLEARANCE:
                return __('Розпродаж', 'yellow-paper-classifieds');
            default:
                return __('Звичайна', 'yellow-paper-classifieds');
        }
    }

    private function normalize_price_type_for_display($price_type, $price)
    {
        $allowed_price_types = array(
                self::PRICE_TYPE_NO_PRICE,
                self::PRICE_TYPE_REGULAR,
                self::PRICE_TYPE_SALE,
                self::PRICE_TYPE_CLEARANCE,
        );

        if (in_array($price_type, $allowed_price_types, true)) {
            return $price_type;
        }

        return trim((string)$price) !== '' ? self::PRICE_TYPE_REGULAR : self::PRICE_TYPE_NO_PRICE;
    }

    private function get_saved_characteristics($listing_id)
    {
        $rows = get_post_meta($listing_id, self::META_CHARACTERISTICS, true);

        if (!is_array($rows) || empty($rows)) {
            return array(
                    array('label' => '', 'value' => ''),
            );
        }

        $clean = array();

        foreach ($rows as $row) {
            $label = isset($row['label']) ? (string)$row['label'] : '';
            $value = isset($row['value']) ? (string)$row['value'] : '';

            $clean[] = array(
                    'label' => $label,
                    'value' => $value,
            );
        }

        return $clean;
    }

    private function get_raw_submitted_characteristics_for_form()
    {
        $result = array();

        $labels = isset($_POST['yp_characteristics']['label']) ? (array)wp_unslash($_POST['yp_characteristics']['label']) : array();
        $values = isset($_POST['yp_characteristics']['value']) ? (array)wp_unslash($_POST['yp_characteristics']['value']) : array();

        $max = max(count($labels), count($values));

        for ($i = 0; $i < $max; $i++) {
            $result[] = array(
                    'label' => isset($labels[$i]) ? sanitize_text_field($labels[$i]) : '',
                    'value' => isset($values[$i]) ? sanitize_text_field($values[$i]) : '',
            );
        }

        return $result;
    }

    private function get_submitted_characteristics()
    {
        $result = array();

        $labels = isset($_POST['yp_characteristics']['label']) ? (array)wp_unslash($_POST['yp_characteristics']['label']) : array();
        $values = isset($_POST['yp_characteristics']['value']) ? (array)wp_unslash($_POST['yp_characteristics']['value']) : array();

        $max = max(count($labels), count($values));

        for ($i = 0; $i < $max; $i++) {
            $label = isset($labels[$i]) ? sanitize_text_field($labels[$i]) : '';
            $value = isset($values[$i]) ? sanitize_text_field($values[$i]) : '';

            if ($label === '' && $value === '') {
                continue;
            }

            if ($label === '' || $value === '') {
                $this->errors[] = __('Кожна характеристика повинна мати і назву, і значення.', 'yellow-paper-classifieds');
                continue;
            }

            $result[] = array(
                    'label' => $label,
                    'value' => $value,
            );
        }

        return $result;
    }

    private function get_my_listings_page_url()
    {
        $page = get_page_by_path('moi-ogoloshennya');

        if ($page) {
            return get_permalink($page->ID);
        }

        return '';
    }

    private function get_requested_removal_image_ids($existing_image_ids)
    {
        $requested = isset($_POST['yp_remove_image_ids']) ? (array)wp_unslash($_POST['yp_remove_image_ids']) : array();
        $requested = array_values(array_filter(array_map('absint', $requested)));

        return array_values(array_intersect($existing_image_ids, $requested));
    }

    private function count_non_empty_uploaded_files($files)
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return 0;
        }

        $count = 0;

        foreach ($files['name'] as $name) {
            if (!empty($name)) {
                $count++;
            }
        }

        return $count;
    }

    private function current_user_can_manage_listing($listing_id, $user_id)
    {
        $post = get_post($listing_id);

        if (!$post || $post->post_type !== YP_Post_Types::POST_TYPE) {
            return false;
        }

        if ((int)$post->post_author !== (int)$user_id && !current_user_can('delete_others_yp_listings')) {
            return false;
        }

        return current_user_can('delete_post', $listing_id);
    }

    private function current_user_can_edit_listing($listing_id, $user_id)
    {
        $post = get_post($listing_id);

        if (!$post || $post->post_type !== YP_Post_Types::POST_TYPE) {
            return false;
        }

        if ((int)$post->post_author !== (int)$user_id && !current_user_can('edit_others_yp_listings')) {
            return false;
        }

        return current_user_can('edit_post', $listing_id);
    }

    private function get_primary_term_id($post_id)
    {
        $terms = wp_get_post_terms($post_id, YP_Post_Types::TAXONOMY, array('fields' => 'ids'));

        if (is_wp_error($terms) || empty($terms)) {
            return 0;
        }

        return (int)$terms[0];
    }

    private function get_primary_location_id($post_id, $default = 0)
    {
        $terms = wp_get_post_terms($post_id, YP_Post_Types::LOCATION_TAXONOMY, array('fields' => 'ids'));

        if (is_wp_error($terms) || empty($terms)) {
            return (int)$default;
        }

        return (int)$terms[0];
    }

    private function get_type_terms()
    {
        $terms = get_terms(array(
                'taxonomy' => YP_Post_Types::TAXONOMY,
                'hide_empty' => false,
                'parent' => 0,
                'orderby' => 'name',
                'order' => 'ASC',
        ));

        if (is_wp_error($terms) || empty($terms)) {
            return array();
        }

        usort($terms, function ($a, $b) {
            $is_a_sale = (mb_strtolower($a->name) === 'продаж');
            $is_b_sale = (mb_strtolower($b->name) === 'продаж');

            if ($is_a_sale && !$is_b_sale) {
                return -1;
            }

            if (!$is_a_sale && $is_b_sale) {
                return 1;
            }

            return 0;
        });

        return $terms;
    }

    private function get_default_type_id($type_terms)
    {
        if (empty($type_terms)) {
            return 0;
        }

        foreach ($type_terms as $term) {
            if (mb_strtolower($term->name) === 'продаж') {
                return (int)$term->term_id;
            }
        }

        return (int)$type_terms[0]->term_id;
    }

    private function get_location_terms()
    {
        $terms = get_terms(array(
                'taxonomy' => YP_Post_Types::LOCATION_TAXONOMY,
                'hide_empty' => false,
                'orderby' => 'name',
                'order' => 'ASC',
        ));

        if (is_wp_error($terms) || empty($terms)) {
            return array();
        }

        return $terms;
    }

    private function build_category_levels($type_id, $selected_path)
    {
        $levels = array();
        $parent_id = (int)$type_id;
        $index = 0;

        while ($parent_id > 0) {
            $children = $this->get_child_terms($parent_id);

            if (empty($children)) {
                break;
            }

            $selected = isset($selected_path[$index]) ? (int)$selected_path[$index] : 0;

            $levels[] = array(
                    'terms' => $children,
                    'selected' => $selected,
            );

            if ($selected <= 0) {
                break;
            }

            $parent_id = $selected;
            $index++;
        }

        return $levels;
    }

    private function get_child_terms($parent_id)
    {
        $terms = get_terms(array(
                'taxonomy' => YP_Post_Types::TAXONOMY,
                'hide_empty' => false,
                'parent' => (int)$parent_id,
                'orderby' => 'name',
                'order' => 'ASC',
        ));

        if (is_wp_error($terms) || empty($terms)) {
            return array();
        }

        return $terms;
    }

    private function get_submitted_category_path()
    {
        $path = array();

        for ($i = 1; $i <= 10; $i++) {
            $key = 'yp_category_level_' . $i;

            if (!isset($_POST[$key])) {
                break;
            }

            $term_id = absint($_POST[$key]);

            if ($term_id > 0) {
                $path[] = $term_id;
            } else {
                break;
            }
        }

        return $path;
    }

    private function get_submitted_leaf_category_id()
    {
        $path = $this->get_submitted_category_path();

        if (empty($path)) {
            return 0;
        }

        return (int)end($path);
    }

    private function get_category_path_below_type($leaf_category_id, $type_id)
    {
        if (!$leaf_category_id || !$type_id) {
            return array();
        }

        $path = array();
        $current_id = (int)$leaf_category_id;

        while ($current_id > 0) {
            $term = get_term($current_id, YP_Post_Types::TAXONOMY);

            if (!$term || is_wp_error($term)) {
                break;
            }

            if ((int)$term->term_id === (int)$type_id) {
                break;
            }

            $path[] = (int)$term->term_id;
            $current_id = (int)$term->parent;
        }

        $path = array_reverse($path);

        return array_values(array_filter($path, function ($term_id) use ($type_id) {
            return (int)$term_id !== (int)$type_id;
        }));
    }

    private function get_root_type_id_from_category($category_id, $default_type_id = 0)
    {
        if (!$category_id) {
            return (int)$default_type_id;
        }

        $current_id = (int)$category_id;
        $last_valid = (int)$default_type_id;

        while ($current_id > 0) {
            $term = get_term($current_id, YP_Post_Types::TAXONOMY);

            if (!$term || is_wp_error($term)) {
                break;
            }

            $last_valid = (int)$term->term_id;

            if ((int)$term->parent === 0) {
                return (int)$term->term_id;
            }

            $current_id = (int)$term->parent;
        }

        return $last_valid;
    }

    private function is_valid_type_term($type_id)
    {
        if ($type_id <= 0) {
            return false;
        }

        $term = get_term($type_id, YP_Post_Types::TAXONOMY);

        if (!$term || is_wp_error($term)) {
            return false;
        }

        return ((int)$term->parent === 0);
    }

    private function is_valid_location_term($location_id)
    {
        if ($location_id <= 0) {
            return false;
        }

        $term = get_term($location_id, YP_Post_Types::LOCATION_TAXONOMY);

        if (!$term || is_wp_error($term)) {
            return false;
        }

        return true;
    }

    private function is_valid_leaf_category_for_type($category_id, $type_id)
    {
        if ($category_id <= 0 || $type_id <= 0) {
            return false;
        }

        $term = get_term($category_id, YP_Post_Types::TAXONOMY);

        if (!$term || is_wp_error($term)) {
            return false;
        }

        $children = $this->get_child_terms($category_id);

        if (!empty($children)) {
            return false;
        }

        $root_type_id = $this->get_root_type_id_from_category($category_id, 0);

        return ((int)$root_type_id === (int)$type_id);
    }

    private function get_user_contact_name($user_id)
    {
        $user = get_userdata($user_id);

        if (!$user) {
            return '';
        }

        $first_name = trim((string)get_user_meta($user_id, 'first_name', true));
        $last_name = trim((string)get_user_meta($user_id, 'last_name', true));
        $full_name = trim($first_name . ' ' . $last_name);

        if ($full_name !== '') {
            return $full_name;
        }

        $store_name = class_exists('YP_User_Profile') ? trim((string)get_user_meta($user_id, YP_User_Profile::META_STORE_NAME, true)) : '';
        if ($store_name !== '') {
            return $store_name;
        }

        return trim((string)$user->display_name);
    }

    private function get_user_phone_digits($user_id)
    {
        if (!class_exists('YP_User_Profile')) {
            return '';
        }

        $phone_digits = $this->normalize_phone_to_digits(get_user_meta($user_id, YP_User_Profile::META_STORE_PHONE, true));

        return $this->is_valid_phone_digits($phone_digits) ? $phone_digits : '';
    }

    private function normalize_phone_to_digits($value)
    {
        return preg_replace('/\D+/', '', (string)$value);
    }

    private function is_valid_phone_digits($digits)
    {
        return (bool)preg_match('/^\d{10}$/', $digits);
    }

    private function format_phone_for_display($digits)
    {
        $digits = $this->normalize_phone_to_digits($digits);

        if (!$this->is_valid_phone_digits($digits)) {
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

    private function get_category_tree_for_js($type_terms)
    {
        $tree = array();

        foreach ($type_terms as $type_term) {
            $this->fill_category_tree_branch((int)$type_term->term_id, $tree);
        }

        return $tree;
    }

    private function fill_category_tree_branch($parent_id, &$tree)
    {
        $children = $this->get_child_terms($parent_id);
        $tree[(string)$parent_id] = array();

        foreach ($children as $child) {
            $tree[(string)$parent_id][] = array(
                    'id' => (int)$child->term_id,
                    'name' => $child->name,
            );

            $this->fill_category_tree_branch((int)$child->term_id, $tree);
        }
    }

    private function get_term_name($term_id, $taxonomy)
    {
        $term = get_term($term_id, $taxonomy);

        if (!$term || is_wp_error($term)) {
            return '';
        }

        return $term->name;
    }
}
