<?php
/**
 * Functions
 */

/******************************************************************************
 * Included Functions
 ******************************************************************************/

// Helpers function
require_once get_stylesheet_directory() . '/inc/helpers.php';
// Walker modification
require_once get_stylesheet_directory() . '/inc/class-bootstrap-navigation.php';
// Home slider function
include_once get_stylesheet_directory() . '/inc/home-slider.php';
// SVG Support
//include_once get_stylesheet_directory() . '/inc/svg-support.php';
// Extend WP Search with Custom fields
//include_once get_stylesheet_directory() . '/inc/custom-fields-search.php';
// Constants
define('IMAGE_PLACEHOLDER', get_stylesheet_directory_uri() . '/images/placeholder.jpg');


/******************************************************************************
 * Global Functions
 ******************************************************************************/

// By adding theme support, we declare that this theme does not use a
// hard-coded <title> tag in the document head, and expect WordPress to
// provide it for us.
add_theme_support('title-tag');

//  Add widget support shortcodes
add_filter('widget_text', 'do_shortcode');

// Support for Featured Images
add_theme_support('post-thumbnails');

// Custom Background
add_theme_support('custom-background', array('default-color' => 'fff'));

// Custom Header
add_theme_support('custom-header', array(
        'default-image' => get_template_directory_uri() . '/images/custom-logo.png',
        'height' => '200',
        'flex-height' => true,
        'uploads' => true,
        'header-text' => false
));

// Custom Logo
add_theme_support('custom-logo', array(
        'height' => '150',
        'flex-height' => true,
        'flex-width' => true,
));

function show_custom_logo($size = 'medium')
{
    if ($custom_logo_id = get_theme_mod('custom_logo')) {
        $attachment_array = wp_get_attachment_image_src($custom_logo_id, $size);
        $logo_url = $attachment_array[0];
    } else {
        $logo_url = get_stylesheet_directory_uri() . '/images/custom-logo.png';
    }
    $logo_image = '<img src="' . $logo_url . '" class="custom-logo" itemprop="siteLogo" alt="' . get_bloginfo('name') . '">';
    $html = sprintf('<a href="%1$s" class="custom-logo-link" rel="home" title="%2$s" itemscope>%3$s</a>', esc_url(home_url('/')), get_bloginfo('name'), $logo_image);
    echo apply_filters('get_custom_logo', $html);
}


// Add HTML5 elements
add_theme_support('html5', array(
        'comment-list',
        'search-form',
        'comment-form',
));

// Register Navigation Menu
register_nav_menus(array(
        'header-menu' => 'Header Menu',
        'footer-menu' => 'Footer Menu'
));


// Create pagination
function bootstrap_pagination($query = '')
{
    if (empty($query)) {
        global $wp_query;
        $query = $wp_query;
    }

    $big = 999999999;

    $links = paginate_links(array(
            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format' => '?paged=%#%',
            'prev_next' => true,
            'prev_text' => '&laquo;',
            'next_text' => '&raquo;',
            'current' => max(1, get_query_var('paged')),
            'total' => $query->max_num_pages,
            'type' => 'list'
    ));

    $pagination = str_replace('page-numbers', 'pagination', $links);

    echo $pagination;
}


// Register Sidebars
function bootstrap_widgets_init()
{
    /* Sidebar Right */
    register_sidebar(array(
            'id' => 'bootstrap_sidebar_right',
            'name' => __('Sidebar Right'),
            'description' => __('This sidebar is located on the right-hand side of each page.'),
            'before_widget' => '<aside id="%1$s" class="widget %2$s">',
            'after_widget' => '</aside>',
            'before_title' => '<h5>',
            'after_title' => '</h5>',
    ));
    /* Sidebar Three */
    register_sidebar(array(
            'id' => 'bootstrap_sidebar_three',
            'name' => __('Sidebar Three'),
            'description' => __('Цей блок знаходиться в секції Повідомлення'),
            'before_widget' => '<aside id="%1$s" class="widget %2$s">',
            'after_widget' => '</aside>',
            'before_title' => '<h5>',
            'after_title' => '</h5>',
    ));
}

add_action('widgets_init', 'bootstrap_widgets_init');


// Remove #more anchor from posts
function remove_more_jump_link($link)
{
    $offset = strpos($link, '#more-');
    if ($offset) {
        $end = strpos($link, '"', $offset);
    }
    if ($end) {
        $link = substr_replace($link, '', $offset, $end - $offset);
    }

    return $link;
}

add_filter('the_content_more_link', 'remove_more_jump_link');


/******************************************************************************************************************************
 * Enqueue Scripts and Styles for Front-End
 *******************************************************************************************************************************/

function bootstrap_scripts_and_styles()
{
    if (!is_admin()) {

//core
        wp_enqueue_style('bootstrap-min', get_template_directory_uri() . '/css/bootstrap.min.css', null, '4.3.1');
        wp_enqueue_style('normalize-css', get_template_directory_uri() . '/css/normalize.css', null, '8.0.1');


        //plugins
        wp_enqueue_style('font-awesome', get_template_directory_uri() . '/css/plugins/fontawesome.min.css', null, '5.3.1');
        wp_enqueue_style('slick', get_template_directory_uri() . '/css/plugins/slick.css', null, '1.6.0');
        wp_enqueue_style('fancybox.v3', get_template_directory_uri() . '/css/plugins/jquery.fancyboxv3.css', null, '3.4.1');
        //
        //system
        wp_enqueue_style('custom', get_template_directory_uri() . '/css/moxy.css', null, '1.0.9');/*3rd priority*/
        wp_enqueue_style('media-screens', get_template_directory_uri() . '/css/media-screens.css', null, null);/*2nd priority*/
        wp_enqueue_style('style', get_template_directory_uri() . '/style.css', null, null);/*1st priority*/

        // Load JavaScripts
        //core
        wp_enqueue_script('jquery');
        wp_enqueue_script('bootstrap', get_template_directory_uri() . '/js/bootstrap.min.js', null, '4.3.1', true);

        ////plugins
        wp_enqueue_script('slick', get_template_directory_uri() . '/js/plugins/slick.min.js', null, '1.6.0', true);
        wp_enqueue_script('fancybox.v3', get_template_directory_uri() . '/js/plugins/jquery.fancybox.v3.js', null, '3.4.1', true);
        //
        ////		wp_enqueue_script( 'google.maps.api', 'https://maps.googleapis.com/maps/api/js?key=' . (get_theme_mod( 'google_maps_api' ) ?: 'AIzaSyAs19C89zcw7bQ12hJEKgtPGK9Q8iuLkQ4') . '&v=3.exp', null, null, true );
        //
        ////custom javascript
        wp_enqueue_script('global', get_template_directory_uri() . '/js/global.js', null, null, true); /* This should go first */

    }
}

add_action('wp_enqueue_scripts', 'bootstrap_scripts_and_styles');


/******************************************************************************
 * Additional Functions
 *******************************************************************************/


// Register Post Type Slider
function post_type_slider()
{
    $post_type_slider_labels = array(
            'name' => _x('Slider', 'post type general name'),
            'singular_name' => _x('Slide', 'post type singular name'),
            'add_new' => _x('Add New', 'slide'),
            'add_new_item' => __('Add New'),
            'edit_item' => __('Edit'),
            'new_item' => __('New '),
            'all_items' => __('All'),
            'view_item' => __('View'),
            'search_items' => __('Search for a slide'),
            'not_found' => __('No slides found'),
            'not_found_in_trash' => __('No slides found in the Trash'),
            'parent_item_colon' => '',
            'menu_name' => 'Slider'
    );
    $post_type_slider_args = array(
            'labels' => $post_type_slider_labels,
            'description' => 'Display Slider',
            'public' => true,
            'menu_icon' => 'dashicons-format-gallery',
            'menu_position' => 5,
            'supports' => array(
                    'title',
                    'thumbnail',
                    'page-attributes',
                    'editor',
                    'post-formats'
            ),
            'has_archive' => true,
            'hierarchical' => true
    );
    register_post_type('slider', $post_type_slider_args);
    add_theme_support('post-formats', array('video'));
    remove_post_type_support('post', 'post-formats');
}

add_action('init', 'post_type_slider');


add_action('add_meta_boxes', 'slide_background_metabox');
function slide_background_metabox()
{
    $screens = array('slider');
    add_meta_box('slide_background', 'Slide background', 'slider_background_callback', $screens);
}

function slider_background_callback($post, $meta)
{
    wp_nonce_field('save_video_bg', 'foundation_nonce');

    echo '<p class="label-wrapper"><label for="slide_video" style="display: block;"><b>Video background</b></label></p>';
    echo '<input type="text" id= "slide_video" name="slide_video_bg" value="' . get_post_meta($post->ID, 'slide_video_bg', true) . '" style="width: 100%;"/>';
}

/**
 * Update slide background on slide save
 */
add_action('save_post', 'save_slide_background');

function save_slide_background($post_id)
{

    if (!isset($_POST['slide_video_bg'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['foundation_nonce'], 'save_video_bg')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    update_post_meta($post_id, 'slide_video_bg', $_POST['slide_video_bg']);

}

/**
 * Print script to hande appearance of metabox
 */
//add_action('admin_enqueue_scripts','display_metaboxes');
add_action('admin_footer', 'display_metaboxes');

function display_metaboxes()
{

    if (get_post_type() == "slider") :
        ?>
        <script type="text/javascript">// <![CDATA[
            $ = jQuery;

            function displayMetaboxes() {
                $('#slide_background').hide();
                var selectedFormat = $('input[name=\'post_format\']:checked').val();
                if (selectedFormat == 'video') {
                    $('#slide_background').show();
                }
            }

            $(function () {
                displayMetaboxes();
                $('input[name=\'post_format\']').change(function () {
                    displayMetaboxes();
                });
            });
            // ]]></script>
    <?php
    endif;
}

// Enable control over YouTube iframe through API + add unique ID

function add_youtube_iframe_args($html, $url, $args)
{

    /* Modify video parameters. */
    if (strstr($html, 'youtube.com/embed/') && !empty($args['location'])) {
        preg_match_all('|embed/(.*)\?|', $html, $matches);
        $html = str_replace('?feature=oembed', '?feature=oembed&enablejsapi=1&autoplay=1&mute=1&controls=0&loop=1&showinfo=0&rel=0&playlist=' . $matches[1][0], $html);
        $html = str_replace('<iframe', '<iframe rel="0" enablejsapi="1" id=slide-' . get_the_ID(), $html);
    }

    return $html;
}

add_filter('oembed_result', 'add_youtube_iframe_args', 10, 3);

// Customize Login Screen
function wordpress_login_styling()
{
    if ($custom_logo_id = get_theme_mod('custom_logo')) {
        $custom_logo_img = wp_get_attachment_image_src($custom_logo_id, 'medium');
        $custom_logo_src = $custom_logo_img[0];
    } else {
        $custom_logo_src = 'wp-admin/images/wordpress-logo.svg?ver=20131107';
    }
    ?>
    <style type="text/css">
        .login #login h1 a {
            background-image: url('<?php echo $custom_logo_src; ?>');
            background-size: contain;
            background-position: 50% 50%;
            width: auto;
            height: 120px;
        }

        body.login {
            background-color: #f1f1f1;
        <?php if ($bg_image = get_background_image()) {?> background-image: url('<?php echo $bg_image; ?>') !important;
        <?php } ?> background-repeat: repeat;
            background-position: center center;
        }
    </style>
<?php }

add_action('login_enqueue_scripts', 'wordpress_login_styling');


function admin_logo_custom_url()
{
    $site_url = get_bloginfo('url');
    return ($site_url);
}

add_filter('login_headerurl', 'admin_logo_custom_url');

// ACF Pro Options Page

if (function_exists('acf_add_options_page')) {

    acf_add_options_page(array(
            'page_title' => 'Theme General Settings',
            'menu_title' => 'Theme Settings',
            'menu_slug' => 'theme-general-settings',
            'capability' => 'edit_posts',
            'redirect' => false
    ));

}


// Set Google Map API key

function set_custom_google_api_key()
{
    acf_update_setting('google_api_key', get_theme_mod('google_maps_api') ?: 'AIzaSyAs19C89zcw7bQ12hJEKgtPGK9Q8iuLkQ4');
}

add_action('acf/init', 'set_custom_google_api_key');

// Wrap any iframe and emved tag into div for responsive view

function iframe_wrapper($content)
{
    // match any iframes
    $pattern = '~<iframe.*?<\/iframe>|<embed.*?<\/embed>~';
    preg_match_all($pattern, $content, $matches);

    foreach ($matches[0] as $match) {
        // wrap matched iframe with div
        $wrappedframe = '<div class="responsive-embed widescreen">' . $match . '</div>';

        //replace original iframe with new in content
        $content = str_replace($match, $wrappedframe, $content);
    }

    return $content;
}

add_filter('the_content', 'iframe_wrapper');


/**
 * Fix Gravity Form Tabindex Conflicts
 * http://gravitywiz.com/fix-gravity-form-tabindex-conflicts/
 */
add_filter('gform_tabindex', 'gform_tabindexer', 10, 2);
function gform_tabindexer($tab_index, $form = false)
{
    $starting_index = 1000; // if you need a higher tabindex, update this number
    if ($form)
        add_filter('gform_tabindex_' . $form['id'], 'gform_tabindexer');
    return GFCommon::$tab_index >= $starting_index ? GFCommon::$tab_index : $starting_index;
}


/*********************** PUT YOU FUNCTIONS BELOW ********************************/

add_image_size('full_hd', 1920, 1080, array('center', 'center'));
add_image_size('medium', 450, 450, array('center', 'center'));
add_image_size('product-img', 700, 700, array('center', 'center'));
// add_image_size( 'name', width, height, array('center','center'));

add_filter('gform_confirmation_anchor', '__return_false');

// Remove gutenberg
add_filter('use_block_editor_for_post_type', '__return_false');


function cache_template_part($slug, $args = [], $ttl = 12 * HOUR_IN_SECONDS)
{
    $cache_key = 'tpl_' . md5($slug . serialize($args) . get_the_ID());

    // Спроба взяти з кешу
    $output = wp_cache_get($cache_key, 'template_parts');
    if ($output !== false) {
        echo $output;
        return;
    }

    // Буферизація і підключення шаблону
    ob_start();
    if (!empty($args) && is_array($args)) {
        extract($args); // створює змінні з ключів
    }

    // Пошук і підключення шаблону
    $template_path = locate_template($slug . '.php');
    if ($template_path) {
        include $template_path;
    }

    $output = ob_get_clean();

    // Кешуємо результат
    wp_cache_set($cache_key, $output, 'template_parts', $ttl);

    echo $output;
}


add_theme_support('woocommerce');


/* Прибрати кнопку купівлі */
//add_action('template_redirect', function () {
//    if (is_product()) {
//        remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
//    }
//});

/*  Повністю прибрати функціонал Відгуків про товар*/
add_filter('woocommerce_product_tabs', function ($tabs) {
    unset($tabs['reviews']); // видаляє вкладку "Відгуки"
    return $tabs;
}, 98);

//add_action('template_redirect', function () {
//    if (is_product()) {
//        remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
//    }
//});


// Заміна h2 на h3 для назв товарів у списках
remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
add_action('woocommerce_shop_loop_item_title', function () {
    echo '<h3 class="woocommerce-loop-product__title">' . get_the_title() . '</h3>';
}, 10);

// Видаляємо кнопку "Додати в кошик" для всіх списків
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);

// Додаємо її лише якщо не на сторінці товару
add_action('woocommerce_after_shop_loop_item', function () {
    if (!is_product()) {
        woocommerce_template_loop_add_to_cart();
    }
}, 10);

// Повністю прибираємо кнопку "Додати в кошик" всюди на сайті
//remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
//remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
//add_action('template_redirect', function () {
//    remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);
//});

// Повністю прибираємо кнопку "Додати в кошик" у всіх списках товарів
add_filter('woocommerce_loop_add_to_cart_link', '__return_false');

// Прибираємо кнопку на сторінці окремого товару
remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);


/**
 * Отримати ID останніх публічних оголошень з кешем.
 */
function yp_get_latest_paid_approved_listing_ids($limit = 10)
{
    $limit = absint($limit);

    if ($limit <= 0) {
        $limit = 10;
    }

    $cache_key = 'yp_latest_paid_approved_listing_ids_' . $limit;

    $ids = get_transient($cache_key);

    if ($ids !== false) {
        return $ids;
    }

    $query = new WP_Query(array(
            'post_type' => 'yp_listing',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'orderby' => 'date',
            'order' => 'DESC',
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
            'meta_query' => array(
                    'relation' => 'AND',
                    array(
                            'key' => '_yp_payment_status',
                            'value' => 'paid',
                            'compare' => '=',
                    ),
                    array(
                            'key' => '_yp_moderation_status',
                            'value' => 'approved',
                            'compare' => '=',
                    ),
                    array(
                            'key' => '_yp_visibility',
                            'value' => 'public',
                            'compare' => '=',
                    ),
            ),
    ));

    $ids = $query->posts;

    set_transient($cache_key, $ids, 12 * HOUR_IN_SECONDS);

    return $ids;
}

//delete_transient('yp_latest_paid_approved_listing_ids_10');

function yp_normalize_term_ids($terms)
{
    if (empty($terms)) {
        return array();
    }

    if (!is_array($terms)) {
        $terms = array($terms);
    }

    $ids = array();

    foreach ($terms as $term) {
        if (is_object($term) && isset($term->term_id)) {
            $ids[] = absint($term->term_id);
        } else {
            $ids[] = absint($term);
        }
    }

    return array_filter(array_unique($ids));
}

function yp_get_listings_section_ids($args = array())
{
    $defaults = array(
            'limit' => 30,
            'order_type' => 'latest_created',
            'categories' => array(),
    );

    $args = wp_parse_args($args, $defaults);

    $limit = absint($args['limit']);
    $order_type = sanitize_key($args['order_type']);
    $categories = yp_normalize_term_ids($args['categories']);

    if ($limit <= 0) {
        $limit = 30;
    }

    $query_args = array(
            'post_type' => 'yp_listing',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'no_found_rows' => true,
            'ignore_sticky_posts' => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => true,
            'meta_query' => array(
                    'relation' => 'AND',
                    array(
                            'key' => '_yp_payment_status',
                            'value' => array('paid', 'manual'),
                            'compare' => 'IN',
                    ),
                    array(
                            'key' => '_yp_moderation_status',
                            'value' => 'approved',
                            'compare' => '=',
                    ),
                    array(
                            'key' => '_yp_visibility',
                            'value' => 'public',
                            'compare' => '=',
                    ),
            ),
    );

    if (!empty($categories)) {
        $query_args['tax_query'] = array(
                array(
                        'taxonomy' => 'yp_listing_category',
                        'field' => 'term_id',
                        'terms' => $categories,
                ),
        );
    }

    switch ($order_type) {
        case 'latest_updated':
            $query_args['orderby'] = 'modified';
            $query_args['order'] = 'DESC';
            break;

        case 'random':
            $query_args['orderby'] = 'rand';
            break;

        case 'alphabet':
            $query_args['orderby'] = 'title';
            $query_args['order'] = 'ASC';
            break;

        case 'latest_created':
        default:
            $query_args['orderby'] = 'date';
            $query_args['order'] = 'DESC';
            break;
    }

    $cache_key = 'yp_listings_section_' . md5(wp_json_encode($query_args));

    $ids = get_transient($cache_key);

    if ($ids !== false) {
        return $ids;
    }

    $query = new WP_Query($query_args);

    $ids = $query->posts;

    /*
     * Для random краще коротший кеш, щоб порядок інколи оновлювався.
     */
    $cache_time = ($order_type === 'random') ? HOUR_IN_SECONDS : 12 * HOUR_IN_SECONDS;

    set_transient($cache_key, $ids, $cache_time);

    return $ids;
}

function yp_clear_listings_section_cache()
{
    global $wpdb;

    $wpdb->query(
            "DELETE FROM {$wpdb->options}
         WHERE option_name LIKE '_transient_yp_listings_section_%'
         OR option_name LIKE '_transient_timeout_yp_listings_section_%'
         OR option_name LIKE '_transient_yp_latest_paid_approved_listing_ids_%'
         OR option_name LIKE '_transient_timeout_yp_latest_paid_approved_listing_ids_%'"
    );
}

add_action('save_post_yp_listing', 'yp_clear_listings_section_cache');
add_action('deleted_post', 'yp_clear_listings_section_cache');
add_action('trashed_post', 'yp_clear_listings_section_cache');
add_action('untrashed_post', 'yp_clear_listings_section_cache');

function yp_clear_listings_section_cache_on_meta_change($meta_id, $object_id, $meta_key, $_meta_value)
{
    if (get_post_type($object_id) !== 'yp_listing') {
        return;
    }

    $watched_keys = array(
            '_yp_payment_status',
            '_yp_moderation_status',
            '_yp_visibility',
            '_yp_price',
            '_yp_store_name',
    );

    if (in_array($meta_key, $watched_keys, true)) {
        yp_clear_listings_section_cache();
    }
}

add_action('added_post_meta', 'yp_clear_listings_section_cache_on_meta_change', 10, 4);
add_action('updated_post_meta', 'yp_clear_listings_section_cache_on_meta_change', 10, 4);
add_action('deleted_post_meta', 'yp_clear_listings_section_cache_on_meta_change', 10, 4);


/* Реалізація індикатора повітряних тривог START */

/**
 * Air Alert Indicator for Kotelva / Poltava oblast.
 */

function kotelva_get_air_alert_status()
{
    $cache_key = 'kotelva_air_alert_status_v1';

    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    if (!defined('KOTELVA_ALERTS_TOKEN') || empty(KOTELVA_ALERTS_TOKEN)) {
        return array(
                'status' => 'unknown',
                'label' => 'Статус тривоги недоступний',
                'message' => 'Не налаштований API-токен.',
        );
    }

    // 19 = Полтавська область.
    // Якщо знайдеш окремий UID для Котелевської громади — заміниш 19 на потрібний UID.
    $location_uid = 19;

    $url = sprintf(
            'https://api.alerts.in.ua/v1/iot/active_air_raid_alerts/%d.json',
            $location_uid
    );

    $response = wp_remote_get($url, array(
            'timeout' => 8,
            'headers' => array(
                    'Authorization' => 'Bearer ' . KOTELVA_ALERTS_TOKEN,
            ),
    ));

    if (is_wp_error($response)) {
        $result = array(
                'status' => 'unknown',
                'label' => 'Статус тривоги недоступний',
                'message' => $response->get_error_message(),
        );

        set_transient($cache_key, $result, 60);
        return $result;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = trim(wp_remote_retrieve_body($response), "\" \n\r\t");

    if ($code !== 200 || empty($body)) {
        $result = array(
                'status' => 'unknown',
                'label' => 'Статус тривоги недоступний',
                'message' => 'API повернуло помилку.',
        );

        set_transient($cache_key, $result, 60);
        return $result;
    }

    if ($body === 'A') {
        $result = array(
                'status' => 'active',
                'label' => 'Повітряна тривога',
                'message' => 'У Полтавській області зараз оголошена повітряна тривога.',
        );
    } elseif ($body === 'P') {
        $result = array(
                'status' => 'partial',
                'label' => 'Часткова тривога',
                'message' => 'У частині районів або громад області є тривога.',
        );
    } elseif ($body === 'N') {
        $result = array(
                'status' => 'clear',
                'label' => 'Тривоги немає',
                'message' => 'Зараз немає інформації про активну повітряну тривогу.',
        );
    } else {
        $result = array(
                'status' => 'unknown',
                'label' => 'Статус невідомий',
                'message' => 'Невідомий статус від API.',
        );
    }

    // Не робимо запит на кожне відкриття сторінки.
    set_transient($cache_key, $result, 60);

    return $result;
}

function kotelva_air_alert_shortcode()
{
    $alert = kotelva_get_air_alert_status();

    $class = 'kotelva-alert kotelva-alert--' . sanitize_html_class($alert['status']);

    ob_start();
    ?>
    <div class="<?php echo esc_attr($class); ?>" role="status" aria-live="polite">
        <span class="kotelva-alert__dot" aria-hidden="true"></span>
        <span class="kotelva-alert__content">
            <strong class="kotelva-alert__label">
                <?php echo esc_html($alert['label']); ?>
            </strong>
            <span class="kotelva-alert__message">
                <?php echo esc_html($alert['message']); ?>
            </span>
        </span>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('kotelva_air_alert', 'kotelva_air_alert_shortcode');
/* Реалізація індикатора повітряних тривог END */


/* Підрахунок наявності оголошень в дочірніх категоріях START */
function kotelva_category_has_listings_with_children($term_id, $taxonomy = 'yp_listing_category')
{
    $query = new WP_Query(array(
            'post_type' => 'yp_listing',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
            'tax_query' => array(
                    array(
                            'taxonomy' => $taxonomy,
                            'field' => 'term_id',
                            'terms' => (int)$term_id,
                            'include_children' => true,
                    ),
            ),
    ));

    return $query->have_posts();
}

/* Підрахунок наявності оголошень в дочірніх категоріях END */

/* Хелпер для кешування запиту на останні 3 оголошення START */

/**
 * Формат дати для картки оголошення:
 * Сьогодні / Вчора / 5.06.26
 */
function kotelva_format_listing_date($post_id)
{
    $post_time = (int)get_post_time('U', false, $post_id);

    if (!$post_time) {
        return '';
    }

    $post_date = wp_date('Y-m-d', $post_time);
    $today = wp_date('Y-m-d', current_time('timestamp'));
    $yesterday = wp_date('Y-m-d', strtotime('-1 day', current_time('timestamp')));

    if ($post_date === $today) {
        return 'Сьогодні';
    }

    if ($post_date === $yesterday) {
        return 'Вчора';
    }

    return wp_date('j.m.y', $post_time);
}

/**
 * Отримує останні оголошення з кешем.
 */
function kotelva_get_latest_listings_cached($limit = 3)
{
    $cache_key = 'kotelva_latest_listings_v3_' . (int)$limit;
    $items = get_transient($cache_key);

    if ($items !== false) {
        return $items;
    }

    $query = new WP_Query(array(
            'post_type' => 'yp_listing',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
    ));

    $items = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            $post_id = get_the_ID();

            $terms = get_the_terms($post_id, 'yp_listing_category');
            $category = (!empty($terms) && !is_wp_error($terms)) ? $terms[0] : null;

            $category_link = '';

            if ($category) {
                $term_link = get_term_link($category, 'yp_listing_category');

                if (!is_wp_error($term_link)) {
                    $category_link = $term_link;
                }
            }

            $items[] = array(
                    'id' => $post_id,
                    'title' => get_the_title($post_id),
                    'excerpt' => wp_trim_words(wp_strip_all_tags(get_the_excerpt($post_id) ?: get_post_field('post_content', $post_id)), 22, '...'),
                    'link' => get_permalink($post_id),
                    'date' => kotelva_format_listing_date($post_id),
                    'image_id' => get_post_thumbnail_id($post_id),
                    'category' => $category ? $category->name : '',
                    'category_id' => $category ? $category->term_id : 0,
                    'category_link' => $category_link,
            );
        }

        wp_reset_postdata();
    }

    set_transient($cache_key, $items, 6 * HOUR_IN_SECONDS);

    return $items;
}

/**
 * Очищення кешу при зміні оголошень.
 */
function kotelva_clear_latest_listings_cache()
{
    delete_transient('kotelva_latest_listings_3');
}

add_action('save_post_yp_listing', 'kotelva_clear_latest_listings_cache');
add_action('deleted_post', 'kotelva_clear_latest_listings_cache');
add_action('trashed_post', 'kotelva_clear_latest_listings_cache');
add_action('transition_post_status', function ($new_status, $old_status, $post) {
    if ($post && $post->post_type === 'yp_listing') {
        kotelva_clear_latest_listings_cache();
    }
}, 10, 3);


//delete_transient('kotelva_latest_listings_3');
/* Хелпер для кешування запиту на останні 3 оголошення END */
/**
 * Yellow Paper theme template overrides and category sliders.
 */
function lita_locate_yp_template($relative_path)
{
    $relative_path = ltrim((string)$relative_path, '/\\');

    if ($relative_path === '') {
        return '';
    }

    $theme_template = locate_template(array(
            'yellow-paper-classifieds/' . $relative_path,
    ));

    if ($theme_template) {
        return $theme_template;
    }

    if (defined('YP_CLASSIFIEDS_PATH')) {
        $plugin_template = YP_CLASSIFIEDS_PATH . 'templates/' . $relative_path;

        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }

    return '';
}

function lita_resolve_yp_listing_category_term($category) {
    if ($category instanceof WP_Term) {
        return $category;
    }

    if (is_array($category)) {
        if (!empty($category['term_id'])) {
            $term = get_term((int) $category['term_id'], 'yp_listing_category');
            return ($term && !is_wp_error($term)) ? $term : null;
        }

        if (!empty($category['slug'])) {
            $term = get_term_by('slug', sanitize_title($category['slug']), 'yp_listing_category');
            return ($term && !is_wp_error($term)) ? $term : null;
        }
    }

    if (is_numeric($category)) {
        $term = get_term((int) $category, 'yp_listing_category');
        return ($term && !is_wp_error($term)) ? $term : null;
    }

    if (is_string($category) && $category !== '') {
        $term = get_term_by('slug', sanitize_title($category), 'yp_listing_category');
        return ($term && !is_wp_error($term)) ? $term : null;
    }

    return null;
}

function lita_get_acf_image_data($image) {
    $data = array(
            'url' => '',
            'alt' => '',
    );

    if (empty($image)) {
        return $data;
    }

    if (is_array($image)) {
        if (!empty($image['url'])) {
            $data['url'] = $image['url'];
            $data['alt'] = !empty($image['alt']) ? $image['alt'] : (!empty($image['title']) ? $image['title'] : '');
            return $data;
        }

        if (!empty($image['ID'])) {
            $image = (int) $image['ID'];
        } elseif (!empty($image['id'])) {
            $image = (int) $image['id'];
        }
    }

    if (is_numeric($image)) {
        $image_id = (int) $image;
        $data['url'] = wp_get_attachment_image_url($image_id, 'thumbnail');
        $data['alt'] = get_post_meta($image_id, '_wp_attachment_image_alt', true);
        return $data;
    }

    if (is_string($image)) {
        $data['url'] = $image;
    }

    return $data;
}

function lita_find_yp_listing_category_term_by_candidates($slugs = array(), $names = array())
{
    foreach ((array) $slugs as $slug) {
        $slug = sanitize_title($slug);

        if ($slug === '') {
            continue;
        }

        $term = get_term_by('slug', $slug, 'yp_listing_category');

        if ($term && !is_wp_error($term)) {
            return $term;
        }
    }

    foreach ((array) $names as $name) {
        $name = trim((string) $name);

        if ($name === '') {
            continue;
        }

        $term = get_term_by('name', $name, 'yp_listing_category');

        if ($term && !is_wp_error($term)) {
            return $term;
        }
    }

    return null;
}

function lita_format_yp_mini_listing_date($post_id)
{
    $post_time = (int) get_post_time('U', false, $post_id);

    if (!$post_time) {
        return '';
    }

    $time = wp_date('H:i', $post_time);
    $post_date = wp_date('Y-m-d', $post_time);
    $today = wp_date('Y-m-d', current_time('timestamp'));
    $yesterday = wp_date('Y-m-d', strtotime('-1 day', current_time('timestamp')));

    if ($post_date === $today) {
        return sprintf(__('Сьогодні, %s', 'yellow-paper-classifieds'), $time);
    }

    if ($post_date === $yesterday) {
        return sprintf(__('Вчора, %s', 'yellow-paper-classifieds'), $time);
    }

    return sprintf('%s, %s', wp_date('j.m.y', $post_time), $time);
}

function lita_get_yp_find_yours_mini_listing_query($term_ids)
{
    $term_ids = array_values(array_filter(array_map('absint', (array) $term_ids)));

    if (empty($term_ids)) {
        return null;
    }

    return new WP_Query(array(
            'post_type' => 'yp_listing',
            'post_status' => 'publish',
            'posts_per_page' => 3,
            'orderby' => 'date',
            'order' => 'DESC',
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query' => array(
                    array(
                            'taxonomy' => 'yp_listing_category',
                            'field' => 'term_id',
                            'terms' => $term_ids,
                            'include_children' => true,
                    ),
            ),
            'meta_query' => array(
                    'relation' => 'AND',
                    array(
                            'key' => '_yp_visibility',
                            'value' => 'public',
                            'compare' => '=',
                    ),
                    array(
                            'key' => '_yp_moderation_status',
                            'value' => 'approved',
                            'compare' => '=',
                    ),
            ),
    ));
}

function lita_get_yp_find_yours_info_strip_sections()
{
    $sections = array();

    $lost_found_combined = lita_find_yp_listing_category_term_by_candidates(
            array('zahubyv-znayshov', 'zagubyv-znayshov', 'zahubyv-znajshov', 'zagubyv-znajshov', 'lost-found'),
            array('Загубив / Знайшов', 'Загубив-Знайшов', 'Загубив Знайшов')
    );
    $lost_found_terms = array();
    $lost_found_link = '';

    if ($lost_found_combined) {
        $lost_found_terms[] = $lost_found_combined;
        $term_link = get_term_link($lost_found_combined, 'yp_listing_category');

        if (!is_wp_error($term_link)) {
            $lost_found_link = $term_link;
        }
    } else {
        $lost_term = lita_find_yp_listing_category_term_by_candidates(
                array('zahubyv', 'zagubyv', 'zahybyv'),
                array('Загубив')
        );
        $found_term = lita_find_yp_listing_category_term_by_candidates(
                array('znayshov', 'znaishov', 'znajshov'),
                array('Знайшов')
        );

        if ($lost_term) {
            $lost_found_terms[] = $lost_term;
        }

        if ($found_term) {
            $lost_found_terms[] = $found_term;
        }
    }

    $lost_found_query = lita_get_yp_find_yours_mini_listing_query(wp_list_pluck($lost_found_terms, 'term_id'));

    if ($lost_found_query && $lost_found_query->have_posts()) {
        $sections[] = array(
                'title' => __('Загубив / Знайшов', 'yellow-paper-classifieds'),
                'modifier' => 'lost-found',
                'archive_url' => $lost_found_link,
                'query' => $lost_found_query,
        );
    }

    $messages_term = lita_find_yp_listing_category_term_by_candidates(
            array('povidomlennia', 'povidomlennya', 'povidomlennja'),
            array('Повідомлення')
    );
    $messages_link = '';
    $messages_query = null;

    if ($messages_term) {
        $term_link = get_term_link($messages_term, 'yp_listing_category');

        if (!is_wp_error($term_link)) {
            $messages_link = $term_link;
        }

        $messages_query = lita_get_yp_find_yours_mini_listing_query(array($messages_term->term_id));
    }

    if ($messages_query && $messages_query->have_posts()) {
        $sections[] = array(
                'title' => __('Повідомлення', 'yellow-paper-classifieds'),
                'modifier' => 'messages',
                'archive_url' => $messages_link,
                'query' => $messages_query,
        );
    }

    return $sections;
}
function lita_render_yp_category_slider_section($args = array())
{
    if (
            !class_exists('YP_Template_Loader') ||
            !class_exists('YP_Listing_Images') ||
            !class_exists('YP_User_Profile')
    ) {
        return;
    }

    $args = wp_parse_args($args, array(
            'category' => null,
            'term_slug' => '',
            'title' => '',
            'icon' => null,
            'modifier' => '',
            'limit' => 15,
    ));

    $term = lita_resolve_yp_listing_category_term($args['category']);

    if (!$term && $args['term_slug'] !== '') {
        $term = lita_resolve_yp_listing_category_term($args['term_slug']);
    }

    if (!$term) {
        return;
    }

    $modifier = sanitize_html_class($args['modifier'] ?: $term->slug);
    $limit = max(1, absint($args['limit']));
    $icon = lita_get_acf_image_data($args['icon']);

    $term_link = get_term_link($term, 'yp_listing_category');

    if (is_wp_error($term_link)) {
        $term_link = '';
    }

    $query = new WP_Query(array(
            'post_type' => 'yp_listing',
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'ignore_sticky_posts' => true,
            'no_found_rows' => true,
            'orderby' => 'date',
            'order' => 'DESC',
            'tax_query' => array(
                    array(
                            'taxonomy' => 'yp_listing_category',
                            'field' => 'term_id',
                            'terms' => (int)$term->term_id,
                            'include_children' => true,
                    ),
            ),
            'meta_query' => array(
                    'relation' => 'AND',
                    array(
                            'key' => '_yp_visibility',
                            'value' => 'public',
                            'compare' => '=',
                    ),
                    array(
                            'key' => '_yp_moderation_status',
                            'value' => 'approved',
                            'compare' => '=',
                    ),
            ),
    ));

    if (!$query->have_posts()) {
        wp_reset_postdata();
        return;
    }

    $loader = new YP_Template_Loader(new YP_Listing_Images(), new YP_User_Profile());
    $compact_template = lita_locate_yp_template('parts/listing-card-compact.php');

    if (!$compact_template) {
        wp_reset_postdata();
        return;
    }

    $section_title = $args['title'] !== '' ? $args['title'] : $term->name;
    ?>
    <section class="yp-category-slider yp-category-slider--<?php echo esc_attr($modifier); ?> yp-listing-<?php echo esc_attr($modifier); ?>">
        <div class="yp-category-slider__header">
            <div class="yp-category-slider__title-wrap">
                <span class="yp-category-slider__icon" aria-hidden="true">
                    <?php if (!empty($icon['url'])) : ?>
                        <img src="<?php echo esc_url($icon['url']); ?>" alt="<?php echo esc_attr($icon['alt']); ?>" loading="lazy">
                    <?php else : ?>
                        <svg width="800px" height="800px" viewBox="0 0 24 24" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 10L18.5145 17.4276C18.3312 18.3439 18.2396 18.8021 18.0004 19.1448C17.7894 19.447 17.499 19.685 17.1613 19.8326C16.7783 20 16.3111 20 15.3766 20H8.62337C7.6889 20 7.22166 20 6.83869 19.8326C6.50097 19.685 6.2106 19.447 5.99964 19.1448C5.76041 18.8021 5.66878 18.3439 5.48551 17.4276L4 10M20 10H18M20 10H21M4 10H3M4 10H6M6 10H18M6 10L9 4M18 10L15 4M9 13V16M12 13V16M15 13V16"
                                  stroke="#46b450" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    <?php endif; ?>
                </span>
                <h2 class="subtitle"><?php echo esc_html($section_title); ?></h2>
            </div>

            <?php if ($term_link !== '') : ?>
                <a class="yp-category-slider__archive-link" href="<?php echo esc_url($term_link); ?>">
                    <?php esc_html_e('Переглянути всі в категорії', 'yellow-paper-classifieds'); ?>
                    <span aria-hidden="true">→</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="yp-category-slider__slider swiper">
            <div class="swiper-wrapper">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <div class="swiper-slide">
                        <?php
                        $card = $loader->get_listing_card_data(get_the_ID());
                        include $compact_template;
                        ?>
                    </div>
                <?php endwhile; ?>
            </div>

            <button class="yp-category-slider__prev" type="button"
                    aria-label="<?php esc_attr_e('Попередні оголошення', 'yellow-paper-classifieds'); ?>" hidden>
                <span aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14.2893 18.2929C13.8988 18.6834 13.2656 18.6834 12.8751 18.2929L7.9877 13.4006C7.2073 12.6195 7.2076 11.3537 7.9883 10.5729L12.8787 5.68254C13.2692 5.29202 13.9024 5.29202 14.2929 5.68254C14.6835 6.07307 14.6835 6.70623 14.2929 7.09676L10.1073 11.2824C9.7167 11.6729 9.7167 12.3061 10.1073 12.6966L14.2893 16.8787C14.6798 17.2692 14.6798 17.9023 14.2893 18.2929Z" fill="#ffffff"/>
                    </svg>
                </span>
            </button>

            <button class="yp-category-slider__next" type="button"
                    aria-label="<?php esc_attr_e('Наступні оголошення', 'yellow-paper-classifieds'); ?>">
                <span aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9.71069 18.2929C10.1012 18.6834 10.7344 18.6834 11.1249 18.2929L16.0123 13.4006C16.7927 12.6195 16.7924 11.3537 16.0117 10.5729L11.1213 5.68254C10.7308 5.29202 10.0976 5.29202 9.70708 5.68254C9.31655 6.07307 9.31655 6.70623 9.70708 7.09676L13.8927 11.2824C14.2833 11.6729 14.2833 12.3061 13.8927 12.6966L9.71069 16.8787C9.32016 17.2692 9.32016 17.9023 9.71069 18.2929Z" fill="#ffffff"/>
                    </svg>
                </span>
            </button>
        </div>
    </section>
    <?php
    wp_reset_postdata();
}
function lita_enqueue_find_yours_assets()
{
    if (!is_page_template('templates/template-find-yours.php')) {
        return;
    }

    wp_enqueue_script(
            'yp-category-listing-slider',
            get_template_directory_uri() . '/js/yp-category-listing-slider.js',
            array(),
            filemtime(get_template_directory() . '/js/yp-category-listing-slider.js'),
            true
    );
}

add_action('wp_enqueue_scripts', 'lita_enqueue_find_yours_assets', 20);

