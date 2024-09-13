<?php

function utp_render_admin_page() {
    global $wpdb;
    $table_name = utp_get_table_name();
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC");

    echo '<div class="wrap"><h1>User Tracking Data</h1><table class="wp-list-table widefat fixed"><thead><tr><th>ID</th><th>IP Address</th><th>Page URL</th><th>Timestamp</th><th>Action</th><th>X Position</th><th>Y Position</th></tr></thead><tbody>';

    foreach ($results as $row) {
        echo "<tr><td>{$row->id}</td><td>{$row->ip_address}</td><td>{$row->page_url}</td><td>{$row->timestamp}</td><td>{$row->action}</td><td>{$row->x_position}</td><td>{$row->y_position}</td></tr>";
    }

    echo '</tbody></table></div>';
}
