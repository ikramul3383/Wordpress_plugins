<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;
$table_name = $wpdb->prefix . 'user_tracking';
$wpdb->query("DROP TABLE IF EXISTS $table_name");
