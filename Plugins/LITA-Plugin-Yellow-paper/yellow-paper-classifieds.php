<?php
/**
 * Plugin Name: Yellow Paper Classifieds
 * Plugin URI:  https://example.com/
 * Description: Custom classifieds plugin for local town listings.
 * Version:     1.0.8
 * Author:      Your Name
 * Text Domain: yellow-paper-classifieds
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('YP_CLASSIFIEDS_VERSION', '1.0.8');
define('YP_CLASSIFIEDS_FILE', __FILE__);
define('YP_CLASSIFIEDS_PATH', plugin_dir_path(__FILE__));
define('YP_CLASSIFIEDS_URL', plugin_dir_url(__FILE__));

require_once YP_CLASSIFIEDS_PATH . 'includes/yp-account-type-helpers.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/yp-category-nav-helpers.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-loader.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-activator.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-deactivator.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-post-types.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-roles.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-admin.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-assets.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-listing-workflow.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-listing-meta.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-frontend-query.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-listing-images.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-user-profile.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-auth.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-account.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-template-loader.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-seller-archive.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-frontend-submission.php';
require_once YP_CLASSIFIEDS_PATH . 'includes/class-yp-plugin.php';


register_activation_hook(__FILE__, array('YP_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('YP_Deactivator', 'deactivate'));

function yp_classifieds_run() {
    $plugin = new YP_Plugin();
    $plugin->run();
}

yp_classifieds_run();