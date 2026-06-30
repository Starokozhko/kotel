<?php

if (!defined('ABSPATH')) {
    exit;
}

class YP_Deactivator {

    public static function deactivate() {
        flush_rewrite_rules();
    }
}