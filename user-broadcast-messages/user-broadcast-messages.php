<?php
/**
 * Plugin Name: User Broadcast Messages
 * Description: Send broadcast messages to all users.
 * Version: 1.0
 * Author: Your Name
 * License: GPL2
 */

// Security check to ensure the file is being called within WordPress
defined('ABSPATH') or die('No script kiddies please!');

// Add an admin menu for sending broadcast messages
function ubm_add_admin_menu()
{
    add_menu_page(
        'User Broadcast Messages', // Page Title
        'Broadcast Messages', // Menu Title
        'manage_options', // Capability
        'user-broadcast-messages', // Menu Slug
        'ubm_broadcast_message_form', // Callback function to display the form
        'dashicons-email', // Icon
        20 // Position
    );
}
add_action('admin_menu', 'ubm_add_admin_menu');

// Display the form for sending broadcast messages
function ubm_broadcast_message_form()
{
    ?>
    <div class="wrap">
        <h1>Send Broadcast Message</h1>
        <form method="post" action="">
            <label for="ubm_subject">Subject:</label>
            <input type="text" name="ubm_subject" id="ubm_subject" placeholder="Enter subject..." class="regular-text">
            <br><br>
            <label for="ubm_message">Message:</label>
            <?php
            // Display the WordPress WYSIWYG editor for the message field
            $content = ''; // Default content (you can leave it empty or set initial content)
            $editor_id = 'ubm_message'; // ID for the editor
            $settings = array(
                'textarea_name' => 'ubm_message',
                'editor_class' => 'wp-editor-area',
                'media_buttons' => true, // You can set to true if you want to allow adding media like images
                'teeny' => true, // This simplifies the editor (removes some features)
                'textarea_rows' => 10, // Adjust the height of the editor
            );
            wp_editor($content, $editor_id, $settings);
            ?>
            <br><br>

            <label for="ubm_membership_level">Select Membership Level:</label>
            <select name="ubm_membership_level" id="ubm_membership_level">
                <option value="0">All Users</option> <!-- Option to send to all users -->
                <?php
                // Get the membership levels from PMPro
                if (function_exists('pmpro_getAllLevels')) {
                    $levels = pmpro_getAllLevels(); // Fetch all levels
                    foreach ($levels as $level) {
                        echo '<option value="' . esc_attr($level->id) . '">' . esc_html($level->name) . '</option>';
                    }
                } else {
                    echo '<option value="">No Membership Levels Found</option>';
                }
                ?>
            </select>
            <br><br>
            <input type="submit" name="ubm_send_message" value="Send Message" class="button button-primary">
        </form>
    </div>
    <?php
    // Check if the form has been submitted
    if (isset($_POST['ubm_send_message']) && isset($_POST['ubm_message']) && isset($_POST['ubm_subject'])) {
        // Sanitize the subject and message to prevent XSS
        $subject = sanitize_text_field($_POST['ubm_subject']);
        $message = wp_kses_post($_POST['ubm_message']); // Allow HTML in the message
        $membership_level = intval($_POST['ubm_membership_level']); // Get the selected membership level

        // Call the function to send the broadcast message based on the membership level
        ubm_send_broadcast_message($subject, $message, $membership_level);
    }
}


function ubm_send_broadcast_message($subject, $message, $membership_level)
{
    global $wpdb;

    // If a membership level is selected, we will send the message only to those users
    if ($membership_level > 0) {
        // Get users with the selected membership level using a custom SQL query
        $query = "
            SELECT u.user_email
            FROM {$wpdb->prefix}pmpro_memberships_users pmu
            JOIN {$wpdb->prefix}users u ON u.ID = pmu.user_id
            WHERE pmu.membership_id = %d AND pmu.status = 'active'";

        // Prepare the query with the membership level
        $sql = $wpdb->prepare($query, $membership_level);

        // Debugging: Log the SQL query
        error_log('SQL Query: ' . $sql);

        // Get the user emails
        $user_emails = $wpdb->get_col($sql);

        // Debugging: Log the number of users found
        error_log('Number of users found for membership level ' . $membership_level . ': ' . count($user_emails));
    } else {
        // If no membership level is selected, send to all users
        $query = "
            SELECT u.user_email
            FROM {$wpdb->prefix}users u";

        // Get the user emails for all users
        $user_emails = $wpdb->get_col($query);

        // Debugging: Log the number of users found for all users
        error_log('Number of users found for all users: ' . count($user_emails));
    }

    // Check if any emails were found
    if (!empty($user_emails)) {
        // Loop through each user and send them the message
        foreach ($user_emails as $user_email) {
            // Send the email
            wp_mail($user_email, $subject, $message);
        }

        // Show a success message in the admin panel
        echo '<div class="updated"><p>Broadcast message sent to selected users.</p></div>';
    } else {
        // Show an error message if no users are found for the selected membership level
        echo '<div class="error"><p>No users found for the selected membership level.</p></div>';
    }
}

// Security feature: Ensure only admin users can access the message page
function ubm_check_admin_permission()
{
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
}
add_action('admin_init', 'ubm_check_admin_permission');
