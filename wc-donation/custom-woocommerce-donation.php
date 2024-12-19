<?php
/**
 * Plugin Name: Product Based Donation
 * Description: A plugin to add donation options (radio button and custom text field) to WooCommerce products.
 * Version: 1.0
 * Author: Ikramul Shekh
 * Text Domain: custom-woocommerce-donation
 */

// Check if WooCommerce is active
function custom_donation_check_woocommerce_activation()
{
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        add_action('admin_notices', function () {
            echo '<div class="error"><p>WooCommerce must be activated for the donation functionality to work.</p></div>';
        });
        return;
    }
}
add_action('plugins_loaded', 'custom_donation_check_woocommerce_activation');

// Register settings for the donation functionality
function custom_donation_register_settings()
{
    register_setting('custom_donation_settings_group', 'donation_amounts');
    register_setting('custom_donation_settings_group', 'donation_label');
    register_setting('custom_donation_settings_group', 'donation_products');
}
add_action('admin_init', 'custom_donation_register_settings');

// Add settings page in the admin menu
function custom_donation_settings_page()
{
    add_menu_page(
        'Donation Settings',
        'Donation Settings',
        'manage_options',
        'custom-donation-settings',
        'custom_donation_settings_page_callback'
    );
}
add_action('admin_menu', 'custom_donation_settings_page');

// Callback function for the admin settings page
function custom_donation_settings_page_callback()
{
    $currency_symbol = custom_donation_get_currency_symbol();
    ?>
    <div class="wrap">
        <h1>Donation Settings</h1>
        <form method="post" action="options.php" onsubmit="return validateForm();">
            <?php
            settings_fields('custom_donation_settings_group');
            do_settings_sections('custom-donation-settings');
            ?>
            <h2>Donation Amounts (Radio Buttons)</h2>
            <div id="donation_amounts">
                <?php
                // Get saved donation amounts from the database
                $donation_amounts = get_option('donation_amounts', []);
                foreach ($donation_amounts as $index => $amount) {
                    echo '<div class="donation-amount-item">
                    <input type="number" step="0.01" name="donation_amounts[]" value="' . esc_attr($amount) . '" class="regular-text"/>
                    <span>' . esc_html($currency_symbol) . '</span>
                    <button type="button" class="button-secondary delete-donation-amount">Delete</button>
                  </div>';
                }
                ?>
                <button type="button" id="add_donation_amount" class="button-primary">Add More</button>
            </div>

            <h3>Donation Label</h3>
            <input type="text" name="donation_label" value="<?php echo esc_attr(get_option('donation_label', 'Donate')) ?>"
                class="regular-text" />

            <h2>Assign Donation to Products</h2>
            <p>Select categories to assign the donation option:</p>
            <div class="donation-category-checkboxes">
                <?php
                // Get selected categories from the saved options
                $saved_categories = get_option('donation_products', []);
                $categories = get_terms('product_cat');
                foreach ($categories as $category) {
                    $checked = in_array($category->term_id, $saved_categories) ? 'checked' : '';
                    echo '<label><input type="checkbox" name="donation_products[]" value="' . esc_attr($category->term_id) . '" ' . $checked . ' /> ' . esc_html($category->name) . '</label><br>';
                }
                ?>
            </div>

            <?php submit_button(); ?>
        </form>
        <div id="acknowledgment-message" style="display:none; margin-top: 20px;" class="updated">
            <p>Your settings have been saved successfully.</p>
        </div>
    </div>
    <?php
}

// Add JavaScript to handle adding more donation amounts dynamically, deleting amounts, and showing acknowledgment
function custom_donation_admin_scripts()
{
    ?>
    <script type="text/javascript">
        document.getElementById('add_donation_amount').addEventListener('click', function () {
            var newInput = document.createElement('input');
            newInput.type = 'text';
            newInput.name = 'donation_amounts[]';
            newInput.classList.add('regular-text');

            var deleteButton = document.createElement('button');
            deleteButton.type = 'button';
            deleteButton.classList.add('button-secondary', 'delete-donation-amount');
            deleteButton.textContent = 'Delete';

            var container = document.createElement('div');
            container.classList.add('donation-amount-item');
            container.appendChild(newInput);
            container.appendChild(deleteButton);

            document.getElementById('donation_amounts').appendChild(container);
        });

        // Delete the donation amount input field when clicked
        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('delete-donation-amount')) {
                event.target.parentElement.remove();
            }
        });

        // Show acknowledgment message when settings are saved
        document.addEventListener('DOMContentLoaded', function () {
            var message = document.getElementById('acknowledgment-message');
            if (message) {
                message.style.display = 'block';
                setTimeout(function () {
                    message.style.display = 'none';
                }, 3000);
            }
        });

        // Validate form to make category selection mandatory
        function validateForm() {
            var categoryCheckboxes = document.querySelectorAll('input[name="donation_products[]"]:checked');
            if (categoryCheckboxes.length === 0) {
                alert('Please select at least one category.');
                return false;
            }
            return true;
        }
    </script>
    <?php
}
add_action('admin_footer', 'custom_donation_admin_scripts');
function custom_donation_admin_css()
{
    echo "
    <style>
        /* Custom Styles for Donation Settings Page */
        .wrap {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        h1 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 20px;
        }

        h2, h3 {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
        }

        .regular-text {
            padding: 8px;
            font-size: 14px;
            width: 100%;
            max-width: 300px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .donation-amount-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .button-secondary {
            padding: 8px 12px;
            background-color: #ff5b5b;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .button-secondary:hover {
            background-color: #ff3e3e;
        }

        .button-primary {
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .button-primary:hover {
            background-color: #45a049;
        }

        .donation-category-checkboxes {
            margin-bottom: 20px;
        }

        .donation-category-checkboxes label {
            font-size: 14px;
            display: inline-block;
            margin-bottom: 10px;
        }

        #acknowledgment-message {
            padding: 10px;
            background-color: #f0f0f0;
            border: 1px solid #ccc;
            color: #4caf50;
            margin-top: 20px;
            border-radius: 4px;
        }

        .submit input {
            background-color: #0073aa;
            color: white;
            padding: 12px 20px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .submit input:hover {
            background-color: #005f8f;
        }
    </style>
    ";
}
add_action('admin_head', 'custom_donation_admin_css');
// Add function to get the current WooCommerce currency symbol
function custom_donation_get_currency_symbol()
{
    return get_woocommerce_currency_symbol();
}


function custom_donation_display_options()
{
    global $post;

    // Get the donation amounts, label, and selected categories from the options saved in the admin panel
    $donation_amounts = get_option('donation_amounts', []);
    // Get the currency symbol
    $currency_symbol = custom_donation_get_currency_symbol();
    $donation_label = get_option('donation_label', 'Donate');
    $donation_products = get_option('donation_products', []);

    // Check if the current product is in the selected categories for donations
    $product_categories = wp_get_post_terms($post->ID, 'product_cat', ['fields' => 'ids']);
    if (array_intersect($product_categories, $donation_products)) {
        ?>
        <div class="donation-options">
            <h3><?php echo esc_html($donation_label); ?></h3>
            <label for="display-amount">Amount:</label>
            <input type="text" id="display-amount" name="display-amount" readonly
                placeholder="Donation Amount will appear here">

            <!-- Predefined Donation Amounts (Radio Buttons) -->
            <div id="donation-radio-buttons">
                <?php
                if (!empty($donation_amounts)) {
                    foreach ($donation_amounts as $amount) {
                        echo '<label>';
                        echo '<input type="radio" name="custom_donation_amount" value="' . esc_attr($amount) . '">';
                        echo esc_html($currency_symbol) . esc_html($amount);
                        echo '</label><br>';
                    }
                } else {
                    echo '<p>No predefined donation amounts available.</p>';
                }
                ?>
            </div>

            <!-- "Don't see your preferred amount?" Button -->
            <button type="button" id="custom-donation-toggle" class="button" style="display:none;">Don't see your preferred
                amount?</button>

            <!-- Custom Donation Text Field (Initially Hidden) -->
            <div id="dntn-text-field" style="display:none;">
                <label><input type="text" name="custom_donation" placeholder="Enter your donation amount" class="regular-text"
                        pattern="^\d+(\.\d{1,2})?$" /></label><br>
            </div>
            <button id="donate-now" class="button alt">Donate Now</button>

        </div>

        <script>
            // donation-form.js
            document.addEventListener('DOMContentLoaded', function () {
                var donationRadioButtons = document.getElementById('donation-radio-buttons');
                var donationTextField = document.getElementById('dntn-text-field');
                var customDonationToggle = document.getElementById('custom-donation-toggle');

                // Initially hide the "Don't see your preferred amount?" button and the text field
                customDonationToggle.style.display = 'none';
                donationTextField.style.display = 'none';

                // When a radio button is selected, show the "Don't see your preferred amount?" button
                donationRadioButtons.addEventListener('change', function () {
                    customDonationToggle.style.display = 'inline-block'; // Show the button
                    donationTextField.style.display = 'none'; // Hide the text field if a radio is selected
                });

                // When the "Don't see your preferred amount?" button is clicked
                customDonationToggle.addEventListener('click', function () {
                    // Toggle between showing and hiding radio buttons and text field
                    if (donationRadioButtons.style.display !== 'none') {
                        donationRadioButtons.style.display = 'none'; // Hide radio buttons
                        donationTextField.style.display = 'block'; // Show text field
                    } else {
                        donationRadioButtons.style.display = 'block'; // Show radio buttons
                        donationTextField.style.display = 'none'; // Hide text field
                    }
                });
            });
        </script>
        <?php
    }
}
add_action('woocommerce_single_product_summary', 'custom_donation_display_options', 20);

// Add Donation to Cart
function custom_donation_add_to_cart($cart_item_data, $product_id)
{
    if (isset($_POST['donation_amount']) && !empty($_POST['donation_amount'])) {
        // Capture the selected radio button donation value
        $cart_item_data['donation'] = sanitize_text_field($_POST['donation_amount']);
    } elseif (isset($_POST['custom_donation']) && is_numeric($_POST['custom_donation'])) {
        // Capture the custom donation text field value
        $cart_item_data['donation'] = sanitize_text_field($_POST['custom_donation']);
    }

    return $cart_item_data;
}
add_filter('woocommerce_add_cart_item_data', 'custom_donation_add_to_cart', 10, 2);




// Enqueue the CSS and JS files for both admin and front-end
function custom_donation_enqueue_scripts()
{
    wp_localize_script('donation-handling', 'woocommerce_params', array(
        'ajax_url' => admin_url('admin-ajax.php'), // WooCommerce AJAX URL
    ));
    // For the Admin Panel
    if (is_admin()) {
        wp_enqueue_style('custom-donation-admin-style', plugin_dir_url(__FILE__) . 'css/admin.css');
        wp_enqueue_script('custom-donation-admin-script', plugin_dir_url(__FILE__) . 'js/admin.js', array('jquery'), null, true);
    }
    // For the Frontend Product Page
    if (is_product()) {
        wp_enqueue_style('custom-donation-frontend-style', plugin_dir_url(__FILE__) . 'css/style.css');
        wp_enqueue_script('custom-donation-frontend-script', plugin_dir_url(__FILE__) . 'js/front-end.js', array('jquery'), null, true);
    }
}
add_action('wp_enqueue_scripts', 'custom_donation_enqueue_scripts');
add_action('admin_enqueue_scripts', 'custom_donation_enqueue_scripts');


add_action('woocommerce_cart_calculate_fees', 'add_donation_fee', 10, 1);

function add_donation_fee($cart) {
    // Make sure the cart is not empty and we are not in the admin panel
    if (is_admin() && !defined('DOING_AJAX')) return;

    // Log if we are entering the function
    error_log('Entered add_donation_fee function.');

    // Get the donation amount from the session (it was stored there during the AJAX call)
    $donation_amount = WC()->session->get('donation_amount', 0);

    // Log the donation amount to confirm
    error_log('Donation Amount: ' . $donation_amount);

    if ($donation_amount > 0) {
        // Add the donation fee to the cart
        try {
            $cart->add_fee('Donation Fee', $donation_amount, true, '');
            error_log('Donation fee added successfully.');
        } catch (Exception $e) {
            // Log any errors that occur while adding the fee
            error_log('Error adding donation fee: ' . $e->getMessage());
        }
    }
}

// Hook the AJAX request to store the donation amount in the session
add_action('wp_ajax_add_donation_fee', 'handle_add_donation_fee');
add_action('wp_ajax_nopriv_add_donation_fee', 'handle_add_donation_fee');

function handle_add_donation_fee() {
    // Check if the donation amount was sent
    if (isset($_POST['donation_amount'])) {
        $donation_amount = floatval($_POST['donation_amount']);
        
        if ($donation_amount > 0) {
            // Store the donation amount in the session
            WC()->session->set('donation_amount', $donation_amount);

            // Send back the updated cart total
            $cart_total = WC()->cart->get_total('raw');
            wp_send_json_success(array('new_total' => wc_price($cart_total)));
        } else {
            wp_send_json_error(array('message' => 'Invalid donation amount.'));
        }
    } else {
        wp_send_json_error(array('message' => 'Donation amount not set.'));
    }
}
