<?php

function utp_get_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'user_tracking';
}

function utp_get_table_name() {
    global $wpdb;
    return $wpdb->prefix . 'user_tracking';
}

function utp_create_table() {
    global $wpdb;
    $table_name = utp_get_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        ip_address VARCHAR(100) NOT NULL,
        page_url TEXT NOT NULL,
        timestamp DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
        action VARCHAR(50),
        x_position INT,
        y_position INT,
        scroll_percentage FLOAT,
        form_data TEXT
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
