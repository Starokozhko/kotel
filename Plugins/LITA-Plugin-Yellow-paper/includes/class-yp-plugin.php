<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Plugin
{

    private $post_types;
    private $roles;
    private $admin;
    private $listing_meta;
    private $listing_workflow;
    private $frontend_query;
    private $listing_images;
    private $user_profile;
    private $auth;
    private $account;

    private $assets;
    private $template_loader;
    private $seller_archive;
    private $frontend_submission;

    public function __construct()
    {
        $this->load_textdomain();

        $this->post_types = new YP_Post_Types();
        $this->roles = new YP_Roles();
        $this->admin = new YP_Admin();
        $this->assets = new YP_Assets();
        $this->listing_workflow = new YP_Listing_Workflow();
        $this->listing_meta = new YP_Listing_Meta();
        $this->frontend_query = new YP_Frontend_Query();
        $this->listing_images = new YP_Listing_Images();
        $this->user_profile = new YP_User_Profile();
        $this->auth = new YP_Auth();
        $this->account = new YP_Account($this->user_profile);
        $this->template_loader = new YP_Template_Loader($this->listing_images, $this->user_profile);
        $this->seller_archive = new YP_Seller_Archive($this->user_profile, $this->template_loader);
        $this->frontend_submission = new YP_Frontend_Submission($this->listing_images);
    }

    public function run()
    {
        add_action('init', array($this, 'init'));

        $this->listing_workflow->hooks();
        $this->listing_meta->hooks();
        $this->frontend_query->hooks();
        $this->user_profile->hooks();
        $this->assets->hooks();
        $this->admin->hooks();
        $this->auth->hooks();
        $this->account->hooks();
        $this->template_loader->hooks();
        $this->seller_archive->hooks();
        $this->frontend_submission->hooks();
    }

    public function init()
    {
        $this->post_types->register();
        $this->roles->register_runtime_caps();
    }

    private function load_textdomain()
    {
        add_action('plugins_loaded', function () {
            load_plugin_textdomain(
                'yellow-paper-classifieds',
                false,
                dirname(plugin_basename(YP_CLASSIFIEDS_FILE)) . '/languages/'
            );
        });
    }
}