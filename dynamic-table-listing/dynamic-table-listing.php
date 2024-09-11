<?php
/*
Plugin Name: Dynamic Table Listing for Admin Panel
Description: Allows administrators to dynamically view, search, and sort database tables in the WordPress admin panel.
Version: 1.3
Author: Your Name
*/

// Initialize the plugin
function dynamic_table_listing_plugin() {
    add_menu_page(
        'Dynamic Table Listing',
        'Table Listing',
        'manage_options',
        'dynamic-table-listing',
        'render_table_listing_page'
    );
}
add_action('admin_menu', 'dynamic_table_listing_plugin');

// Render the admin page
function render_table_listing_page() {
    ?>
    <div class="wrap">
        <h1>Dynamic Table Listing</h1>
        <form id="table-select-form">
            <label for="table-dropdown">Select Table:</label>
            <select id="table-dropdown" name="table">
                <option value="">Select a table</option>
                <?php
                global $wpdb;
                $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
                foreach ($tables as $table) {
                    echo "<option value='" . esc_attr($table[0]) . "'>" . esc_html($table[0]) . "</option>";
                }
                ?>
            </select>
        </form>
        <div id="table-listing"></div>
    </div>
    <?php
}

// Enqueue scripts and styles
function enqueue_table_listing_scripts($hook) {
    if ($hook != 'toplevel_page_dynamic-table-listing') {
        return;
    }
    wp_enqueue_script('dynamic-table-listing-js', plugins_url('dynamic-table-listing.js', __FILE__), array('jquery'), null, true);
    wp_enqueue_style('dynamic-table-listing-css', plugins_url('dynamic-table-listing.css', __FILE__));
    wp_localize_script('dynamic-table-listing-js', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
}
add_action('admin_enqueue_scripts', 'enqueue_table_listing_scripts');

// AJAX handler for table listing with sorting
function ajax_get_table_listing() {
    global $wpdb;
    $table = isset($_POST['table']) ? sanitize_text_field($_POST['table']) : '';
    $sort_column = isset($_POST['sort_column']) ? sanitize_text_field($_POST['sort_column']) : '';
    $sort_order = isset($_POST['sort_order']) ? sanitize_text_field($_POST['sort_order']) : 'ASC';

    if (!$table || !is_table_valid($table)) {
        wp_die('Invalid table');
    }

    // Fetch columns of the selected table
    $columns = $wpdb->get_col("DESCRIBE $table", 0);

    if (empty($columns)) {
        wp_die('No columns found');
    }

    // Begin table HTML output
    $output = '<table class="wp-list-table widefat fixed striped">';
    $output .= '<thead id="table-header"><tr>';
    foreach ($columns as $column) {
        $next_order = ($sort_order == 'ASC') ? 'DESC' : 'ASC';  // Toggle between ASC and DESC
        $output .= '<th><a href="#" class="sort-column" data-column="' . esc_attr($column) . '" data-order="' . esc_attr($next_order) . '">' . esc_html($column) . '</a>';
        $output .= '<input type="text" class="column-search" data-column="' . esc_attr($column) . '" placeholder="Search ' . esc_attr($column) . '"></th>';
    }
    $output .= '</tr></thead>';
    $output .= '<tbody id="table-data">';

    // Create the query to fetch data from the table, including sorting logic
    $query = "SELECT * FROM $table";
    if ($sort_column && in_array($sort_column, $columns)) {
        $query .= " ORDER BY $sort_column $sort_order";
    }
    $results = $wpdb->get_results($query, ARRAY_A);

    // Populate the table rows
    foreach ($results as $row) {
        $output .= '<tr>';
        foreach ($columns as $column) {
            $output .= '<td>' . esc_html($row[$column]) . '</td>';
        }
        $output .= '</tr>';
    }

    $output .= '</tbody></table>';
    echo $output;
    wp_die();
}
add_action('wp_ajax_get_table_listing', 'ajax_get_table_listing');
add_action('wp_ajax_nopriv_get_table_listing', 'ajax_get_table_listing');

// AJAX handler for column search
function ajax_table_search() {
    global $wpdb;
    $table = isset($_POST['table']) ? sanitize_text_field($_POST['table']) : '';
    $column = isset($_POST['column']) ? sanitize_text_field($_POST['column']) : '';
    $search_value = isset($_POST['search_value']) ? sanitize_text_field($_POST['search_value']) : '';

    if (!$table || !$column || !is_table_valid($table)) {
        wp_die('Invalid request');
    }

    $query = $wpdb->prepare("SELECT * FROM $table WHERE $column LIKE %s", "%$search_value%");
    $results = $wpdb->get_results($query, ARRAY_A);

    $output = '';
    foreach ($results as $row) {
        $output .= '<tr>';
        foreach ($row as $data) {
            $output .= '<td>' . esc_html($data) . '</td>';
        }
        $output .= '</tr>';
    }

    echo $output;
    wp_die();
}
add_action('wp_ajax_table_search', 'ajax_table_search');

// Validate table name
function is_table_valid($table) {
    global $wpdb;
    $tables = $wpdb->get_col("SHOW TABLES");
    return in_array($table, $tables);
}
