<?php
/*
Plugin Name: User Activity Tracking
Description: Tracks user interactions and displays data in the admin panel.
Version: 1.0
Author: Your Name
*/

// Define constants
define('UTP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UTP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include necessary files
require_once UTP_PLUGIN_DIR . 'includes/class-user-tracking.php';
require_once UTP_PLUGIN_DIR . 'includes/functions.php';
require_once UTP_PLUGIN_DIR . 'includes/admin-page.php';

// Register activation and deactivation hooks
register_activation_hook(__FILE__, array('User_Tracking', 'activate'));
register_deactivation_hook(__FILE__, array('User_Tracking', 'deactivate'));

// Initialize the plugin
add_action('plugins_loaded', array('User_Tracking', 'get_instance'));
