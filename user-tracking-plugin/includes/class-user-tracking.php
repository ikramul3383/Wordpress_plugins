<?php

class User_Tracking {
    private static $instance = null;

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new self();
            self::$instance->init();
        }
        return self::$instance;
    }

    private function init() {
        // Hooks and actions
        add_action('wp_ajax_track_user_click', array($this, 'handle_user_click'));
        add_action('wp_ajax_track_user_scroll', array($this, 'handle_user_scroll'));
        add_action('wp_ajax_track_form_submission', array($this, 'handle_form_submission'));
        add_action('wp_ajax_track_page_redirect', array($this, 'handle_page_redirect'));
        
        // Register activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function handle_user_click() {
        global $wpdb;
        $table_name = utp_get_table_name();
        
        $wpdb->insert(
            $table_name,
            array(
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'page_url' => sanitize_text_field($_POST['url']),
                'timestamp' => current_time('mysql'),
                'action' => 'click',
                'x_position' => intval($_POST['x']),
                'y_position' => intval($_POST['y'])
            )
        );
        wp_die();
    }

    public function handle_user_scroll() {
        global $wpdb;
        $table_name = utp_get_table_name();
        
        $wpdb->insert(
            $table_name,
            array(
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'page_url' => sanitize_text_field($_POST['url']),
                'timestamp' => current_time('mysql'),
                'action' => 'scroll',
                'scroll_percentage' => floatval($_POST['scroll_percentage'])
            )
        );
        wp_die();
    }

    public function handle_form_submission() {
        global $wpdb;
        $table_name = utp_get_table_name();
        
        $wpdb->insert(
            $table_name,
            array(
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'page_url' => sanitize_text_field($_POST['url']),
                'timestamp' => current_time('mysql'),
                'action' => 'form_submission',
                'form_data' => sanitize_textarea_field($_POST['form_data'])
            )
        );
        wp_die();
    }

    public function handle_page_redirect() {
        global $wpdb;
        $table_name = utp_get_table_name();
        
        $wpdb->insert(
            $table_name,
            array(
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'page_url' => sanitize_text_field($_POST['url']),
                'timestamp' => current_time('mysql'),
                'action' => 'page_redirect'
            )
        );
        wp_die();
    }

    public function activate() {
        utp_create_table();
    }

    public function deactivate() {
        // Optional: cleanup if necessary
    }
}
