<?php
/*
Plugin Name: Quiz Plugin
Description: A WordPress plugin to create and manage quizzes.
Version: 1.0
Author: Ikramul Shekh
*/

// Register Quiz Question Custom Post Type
function quiz_register_question_post_type()
{
    register_post_type('quiz_question', array(
        'labels' => array(
            'name' => 'Quiz Questions',
            'singular_name' => 'Quiz Question'
        ),
        'public' => true,
        'has_archive' => false,
        'supports' => array('title', 'thumbnail'),
        'menu_icon' => 'dashicons-welcome-learn-more'
    ));
}
add_action('init', 'quiz_register_question_post_type');

// Create a new menu item in the admin panel for quiz results
function quiz_add_results_menu()
{
    add_menu_page(
        'Quiz Results',              // Page Title
        'Quiz Results',              // Menu Title
        'manage_options',            // Capability
        'quiz_results',              // Menu Slug
        'quiz_display_results_page', // Function to display the page content
        'dashicons-format-status',   // Icon
        6                            // Position in the admin menu
    );
}
add_action('admin_menu', 'quiz_add_results_menu');


// Include admin functions
require_once plugin_dir_path(__FILE__) . 'includes/admin-functions.php';

// Enqueue Scripts
function quiz_enqueue_scripts()
{
    wp_enqueue_script('quiz-script', plugin_dir_url(__FILE__) . 'assets/quiz.js', array('jquery'), '1.0', true);

    $nonce = wp_create_nonce('submit_quiz_nonce');
    wp_localize_script('quiz-script', 'quiz_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'quiz_nonce' => $nonce
    ));
    wp_enqueue_style('quiz-style', plugin_dir_url(__FILE__) . 'assets/quiz.css');
}
add_action('wp_enqueue_scripts', 'quiz_enqueue_scripts');

// admin quiz css and js
function enqueue_account_manager_scripts()
{
    wp_enqueue_script('quiz-script', plugin_dir_url(__FILE__) . 'assets/admin-quiz.js', array('jquery'), '1.0', true);
    wp_enqueue_style('quiz-style', plugin_dir_url(__FILE__) . 'assets/quiz.css');

    $nonce = wp_create_nonce('submit_quiz_nonce');
    wp_localize_script('quiz-script', 'quiz_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'quiz_nonce' => $nonce,
    ));
}
add_action('admin_enqueue_scripts', 'enqueue_account_manager_scripts');

function quiz_create_page_on_activation()
{
    // Check if the page already exists
    $page_check = get_page_by_title('Quiz');
    if (!$page_check) {
        // Create the page if it doesn't exist
        $quiz_page = array(
            'post_title' => 'Quiz',
            'post_content' => '[quiz_button]',  // Shortcode to display the button on the page
            'post_status' => 'publish',
            'post_type' => 'page',
        );
        wp_insert_post($quiz_page);
    }
}
register_activation_hook(__FILE__, 'quiz_create_page_on_activation');

// Add the Start Quiz Button on the Quiz Page using shortcode
function quiz_button_shortcode()
{
    ob_start();
    ?>
    <button id="start-quiz" class="button">Start Quiz</button>
    <div id="quiz-popup" style="display:none;">
        <div class="quiz-content">
            <div id="quiz-questions"></div>
            <button id="next-question" class="button">Next</button>
            <button id="submit-quiz" class="button" style="display:none;">Submit Quiz</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('quiz_button', 'quiz_button_shortcode');

// Fetch Quiz Questions AJAX Handler
function quiz_fetch_questions()
{
    $questions_query = new WP_Query(array(
        'post_type' => 'quiz_question',
        'posts_per_page' => -1
    ));

    $questions = array();

    if ($questions_query->have_posts()) {
        while ($questions_query->have_posts()) {
            $questions_query->the_post();
            $question_data = array(
                'ID' => get_the_ID(),
                'question' => get_the_title(),
                'featured_image' => get_the_post_thumbnail_url(get_the_ID(), 'full'), // Fetch featured image URL
                'answers' => array(
                    get_post_meta(get_the_ID(), 'quiz_answer_1', true),
                    get_post_meta(get_the_ID(), 'quiz_answer_2', true),
                    get_post_meta(get_the_ID(), 'quiz_answer_3', true),
                    get_post_meta(get_the_ID(), 'quiz_answer_4', true)
                ),
                'correct_answer' => get_post_meta(get_the_ID(), 'quiz_correct_answer', true)
            );
            $questions[] = $question_data;
        }
    }
    wp_reset_postdata();

    wp_send_json($questions);
}
add_action('wp_ajax_fetch_quiz_questions', 'quiz_fetch_questions');
add_action('wp_ajax_nopriv_fetch_quiz_questions', 'quiz_fetch_questions');

function submit_quiz()
{
    global $wpdb;

    // Collect the quiz submission data from the AJAX request
    $customer_id = isset($_POST['user_id']) ? sanitize_text_field($_POST['user_id']) : '';  // Retrieve the customer ID
    $answers = isset($_POST['answers']) ? $_POST['answers'] : array();
    // Check if customer ID and answers are provided
    // Check if customer ID and answers are provided
    if (empty($customer_id) || empty($answers)) {
        wp_send_json_error(array('message' => 'Missing required data.'));
        return;
    }

    // Handle quiz answers and score calculation
    $correct_count = 0;
    $total_questions = count($answers);

    foreach ($answers as $question_id => $selected_answer) {
        $correct_answer = get_post_meta($question_id, 'quiz_correct_answer', true);
        if ($correct_answer == $selected_answer) {
            $correct_count++;
        }
    }

    // Calculate score percentage
    $score_percentage = ($total_questions > 0) ? round(($correct_count / $total_questions) * 100, 2) : 0;


    // Insert quiz results into the custom table (wp_quiz_result)
    $inserted = $wpdb->insert(
        $wpdb->prefix . 'quiz_result', // Custom table
        array(
            'customer_id' => sanitize_text_field($customer_id),
            'quiz_score_percentage' => $score_percentage,
            'quiz_attempted' => $total_questions,
            'quiz_correct' => $correct_count,
        ),
        array(
            '%s', // customer_id
            '%f', // quiz_score_percentage
            '%d', // quiz_attempted
            '%d', // quiz_correct
        )
    );

    // Check if the data was inserted successfully
    if ($inserted) {
        // Return success response with quiz results
        $response = array(
            'attempted' => $total_questions,
            'correct' => $correct_count,
            'score' => $score_percentage
        );
        wp_send_json_success($response);
    } else {
        wp_send_json_error(array('message' => 'Failed to store quiz results.'));
    }
}

// Hook for logged-in users
add_action('wp_ajax_submit_quiz', 'submit_quiz');

// Hook for non-logged-in users (if you want to allow guests to submit the quiz as well)
add_action('wp_ajax_nopriv_submit_quiz', 'submit_quiz');

function create_quiz_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'quiz_registered_users'; // Custom table name

    // Check if the table exists
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // SQL query to create the table
        $sql = "CREATE TABLE $table_name (
            id INT(11) NOT NULL AUTO_INCREMENT,
            username VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            contact VARCHAR(15) NOT NULL,
            customer_id VARCHAR(255) NOT NULL,
            PRIMARY KEY (id)
        );";

        // Execute the query
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}
register_activation_hook(__FILE__, 'create_quiz_table');

function register_user_for_quiz()
{
    global $wpdb;

    // Collect user data from the AJAX request
    $user_data = isset($_POST['user_data']) ? $_POST['user_data'] : array();

    // Ensure data exists before processing
    if (!empty($user_data)) {
        // Sanitize input
        $customer_id = sanitize_text_field($user_data['customer_id']);
        $table_name = $wpdb->prefix . 'account_numbers';

        // Check if the customer_id exists and fetch the count value
        $customer = $wpdb->get_row(
            $wpdb->prepare("SELECT id, count FROM $table_name WHERE account_number = %s", $customer_id),
            ARRAY_A
        );

        // If customer_id doesn't exist
        if (!$customer) {
            wp_send_json_error(array('message' => 'Customer ID not found.'));
            return;
        }

        // Check the count value
        if ((int) $customer['count'] === 1) {
            wp_send_json_error(array('message' => 'Registration not allowed. Customer ID has already been used.'));
            return;
        }

        // Insert user data into the quiz_registered_users table
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'quiz_registered_users',
            array(
                'username' => sanitize_text_field($user_data['username']),
                'email' => sanitize_email($user_data['email']),
                'contact' => sanitize_text_field($user_data['contact']),
                'customer_id' => $customer_id,
            ),
            array(
                '%s', // username
                '%s', // email
                '%s', // contact
                '%s', // customer_id
            )
        );

        // Check for errors during insertion
        if ($inserted) {
            // Update the count to 1 in the account_numbers table
            $wpdb->update(
                $table_name,
                array('count' => 1), // Set count to 1
                array('id' => $customer['id']), // Where id matches
                array('%d'), // Data format
                array('%d')  // Where clause format
            );

            wp_send_json_success(array('message' => 'User successfully registered.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to register user.'));
        }
    } else {
        wp_send_json_error(array('message' => 'No data received.'));
    }
}

add_action('wp_ajax_register_user_for_quiz', 'register_user_for_quiz');
add_action('wp_ajax_nopriv_register_user_for_quiz', 'register_user_for_quiz');

function create_quiz_result_table()
{
    global $wpdb;

    // Define the table name
    $table_name = $wpdb->prefix . 'quiz_result';

    // SQL to create the table
    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        customer_id VARCHAR(255) NOT NULL,
        quiz_score_percentage FLOAT NOT NULL,
        quiz_attempted INT(11) NOT NULL,
        quiz_correct INT(11) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    // Include the upgrade library
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Execute the query
    dbDelta($sql);

    // Log query for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Quiz result table creation query: ' . $sql);
    }
}

// Register the activation hook
register_activation_hook(__FILE__, 'create_quiz_result_table');


//quiz result
function quiz_display_results_page()
{
    global $wpdb;

    // Table names
    $result_table = $wpdb->prefix . 'quiz_result';
    $users_table = $wpdb->prefix . 'quiz_registered_users';

    // Set up pagination
    $per_page = 20; // Number of results per page
    $page = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $offset = ($page - 1) * $per_page;

    // Query to fetch user details and their quiz results by joining both tables
    $query = "
        SELECT 
            u.username, 
            u.email, 
            u.contact, 
            r.quiz_score_percentage
        FROM 
            $result_table r
        INNER JOIN 
            $users_table u ON r.customer_id = u.customer_id
        ORDER BY 
            r.id DESC
        LIMIT %d, %d
    ";

    // Fetch the results
    $results = $wpdb->get_results($wpdb->prepare($query, $offset, $per_page));

    // Fetch total results count
    $total_results = $wpdb->get_var("SELECT COUNT(*) FROM $result_table");

    // Check if there are any results
    if (empty($results)) {
        echo '<h2>No quiz results found.</h2>';
        return;
    }

    // Display the results in a table format
    echo '<div class="wrap"><h2>Quiz Results</h2>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Username</th><th>Email</th><th>Contact</th><th>Quiz Score (%)</th></tr></thead>';
    echo '<tbody>';

    foreach ($results as $result) {
        echo '<tr>';
        echo '<td>' . esc_html($result->username) . '</td>';
        echo '<td>' . esc_html($result->email) . '</td>';
        echo '<td>' . esc_html($result->contact) . '</td>';
        echo '<td>' . esc_html($result->quiz_score_percentage) . '%</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Add pagination links
    $total_pages = ceil($total_results / $per_page);
    $pagination = paginate_links(array(
        'total' => $total_pages,
        'current' => $page,
        'format' => '?paged=%#%',
        'prev_text' => '&laquo; Previous',
        'next_text' => 'Next &raquo;',
    ));
    echo '<div class="pagination">' . $pagination . '</div>';
    echo '</div>';
}

function quiz_admin_styles()
{
    echo '<style>
        .wp-list-table th, .wp-list-table td { padding: 10px; }
        .pagination { margin-top: 20px; }
    </style>';
}
add_action('admin_head', 'quiz_admin_styles');


function quiz_create_coupon_codes_table()
{
    global $wpdb;

    // Define the table name with WordPress prefix
    $table_name = $wpdb->prefix . 'quiz_coupon_codes';

    // SQL query to create the table if it doesn't already exist
    $charset_collate = $wpdb->get_charset_collate();

    // SQL query to create the table
    $sql = "CREATE TABLE $table_name (
        id INT NOT NULL AUTO_INCREMENT,
        coupon_range VARCHAR(255) NOT NULL,
        coupon_code VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    // Include WordPress's dbDelta function to create the table
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook into WordPress activation to create the table
register_activation_hook(__FILE__, 'quiz_create_coupon_codes_table');



function generate_certificate()
{
    // Validate the nonce before proceeding
    if (!isset($_POST['quiz_nonce']) || !wp_verify_nonce($_POST['quiz_nonce'], 'submit_quiz_nonce')) {
        error_log('Nonce validation failed for user: ' . $_POST['user_name']);
        wp_send_json_error(['message' => 'Invalid nonce']);
    }

    // Extract data from the AJAX request
    $score = isset($_POST['score']) ? intval($_POST['score']) : 0;
    $user_name = isset($_POST['user_name']) ? sanitize_text_field($_POST['user_name']) : 'Unknown User';

    // Log score and user name for debugging
    error_log("Generating certificate for $user_name with score: $score");
    if ($score <= 50) {
        wp_send_json_error(['message' => 'Certificate is only available for scores above 50%.']);
        return;
    }
    // Your certificate generation logic here
    try {
        // Assuming generate_quiz_certificate() is a function to generate the certificate
        $certificate_url = generate_quiz_certificate($user_name, $score);
        if ($certificate_url) {
            wp_send_json_success(['certificate_url' => $certificate_url]);
        } else {
            throw new Exception('Certificate URL generation failed.');
        }
    } catch (Exception $e) {
        error_log('Certificate generation failed: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Certificate generation failed.']);
    }
}

add_action('wp_ajax_generate_certificate', 'generate_certificate');
add_action('wp_ajax_nopriv_generate_certificate', 'generate_certificate');

// Include FPDF library for certificate generation
require_once plugin_dir_path(__FILE__) . 'lib/fpdf/fpdf.php';

// Function to generate certificate PDF
function generate_quiz_certificate($user_name, $score)
{
    // Sanitize the username for use in file names
    $user_name_sanitized = preg_replace('/[^A-Za-z0-9]/', '_', $user_name);

    // Get the upload directory and set the certificate directory
    $upload_dir = wp_upload_dir();
    $certificates_dir = $upload_dir['basedir'] . '/certificates/';

    // Ensure the directory exists, create it if not
    if (!file_exists($certificates_dir)) {
        if (!mkdir($certificates_dir, 0755, true)) {
            error_log("Failed to create certificates directory at: $certificates_dir");
            return false; // Exit if directory creation fails
        }
    }

    // Set the file path for the certificate
    $file_name = $certificates_dir . "certificate_{$user_name_sanitized}.pdf";
    error_log("Certificate will be saved at: $file_name");

    try {
        // Create a new PDF instance using FPDF
        $pdf = new FPDF();
        $pdf->AddPage();

        // Set the background color (if needed)
        $pdf->SetFillColor(255, 255, 240);
        $pdf->Rect(0, 0, 210, 297, 'F'); // A4 size in mm

        // Set the font for the title
        $pdf->SetFont('Arial', 'B', 24);
        $pdf->SetTextColor(139, 69, 19); // Brown color
        $pdf->Cell(0, 40, 'CERTIFICATE', 0, 1, 'C');

        // Set the font for the subtitle
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(139, 69, 19); // Brown color
        $pdf->Cell(0, 10, 'OF ACHIEVEMENT', 0, 1, 'C');
        $pdf->Ln(20);

        // Add content to the certificate
        $pdf->SetFont('Arial', '', 14);
        $pdf->SetTextColor(0, 0, 0); // Black color
        $pdf->Cell(0, 10, "This Certificate is Proudly Presented to:", 0, 1, 'C');
        $pdf->Ln(10);

        // User's name
        $pdf->SetFont('Arial', 'B', 20);
        $pdf->SetTextColor(0, 0, 128); // Dark blue color
        $pdf->Cell(0, 10, $user_name, 0, 1, 'C');
        $pdf->Ln(10);

        // Achievement text
        // Achievement text
        $pdf->SetFont('Arial', '', 14);
        $pdf->SetTextColor(0, 0, 0); // Black color
        $pdf->Cell(0, 10, "In recognition of your outstanding performance and", 0, 1, 'C');
        $pdf->Cell(0, 10, "dedication to achieving excellence in your quiz results.", 0, 1, 'C');
        $pdf->Ln(20);


        // Score
        $pdf->SetFont('Arial', '', 14);
        $pdf->Cell(0, 10, "For achieving a score of $score%", 0, 1, 'C');
        $pdf->Ln(20);

        // Signatures
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, '___________________________', 0, 1, 'L');
        $pdf->Cell(0, 10, 'Director Name', 0, 0, 'L');
        $pdf->Cell(0, 10, '___________________________', 0, 1, 'R');
        $pdf->Cell(0, 10, '', 0, 0, 'L'); // Empty cell for spacing
        $pdf->Cell(0, 10, 'General Manager', 0, 0, 'R');

        // Save the generated PDF to the specified file path
        $pdf->Output('F', $file_name);
        error_log("PDF generated successfully for user: $user_name");

        // Return the URL of the generated certificate
        return $upload_dir['baseurl'] . '/certificates/' . "certificate_{$user_name_sanitized}.pdf";
    } catch (Exception $e) {
        // Log any errors during the PDF generation process
        error_log('Certificate generation failed: ' . $e->getMessage());
        return false; // Return false if PDF generation fails
    }
}


//requirement of account number adding code

function create_account_numbers_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'account_numbers';

    $sql = "CREATE TABLE $table_name (
        id INT(11) NOT NULL AUTO_INCREMENT,
        account_number VARCHAR(255) NOT NULL UNIQUE,
        count INT(11) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'create_account_numbers_table');


function add_account_manager_menu()
{
    add_menu_page(
        'Account Manager',
        'Account Manager',
        'manage_options',
        'account-manager',
        'render_account_manager_page',
        'dashicons-admin-tools',
        20
    );
}
add_action('admin_menu', 'add_account_manager_menu');

function render_account_manager_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'account_numbers';
    $entries = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at ASC");

    ?>
    <div class="wrap">
        <h1>Account Manager</h1>

        <!-- Form to Add Account -->
        <form id="account-form">
            <label for="account_number">Enter Account Number:</label>
            <input type="text" id="account_number" name="account_number" required>
            <button type="submit" class="button button-primary">Add Account</button>
            <div id="loader" style="display:none;">Processing...</div>
        </form>

        <!-- Import/Export CSV -->
        <h2>Import/Export CSV</h2>
        <form id="csv-import-form" enctype="multipart/form-data">
            <label for="csv_file">Import CSV:</label>
            <input type="file" id="csv_file" name="csv_file" accept=".csv" required>
            <button type="submit" class="button button-primary">Import</button>
            <div id="import-loader" style="display:none;">Importing...</div>
        </form>
        <button id="export-csv" class="button button-secondary">Export CSV</button>

        <!-- Table Displaying Database Entries -->
        <h2>Stored Account Numbers</h2>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Account Number</th>
                    <th>Created At</th>
                    <th>Used?</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="entries-table">
                <?php if ($entries): ?>
                    <?php foreach ($entries as $entry): ?>
                        <?php $count = ($entry->count === "1") ? "used" : "notused"; ?>

                        <tr>
                            <td><?php echo esc_html($entry->id); ?></td>
                            <td><?php echo esc_html($entry->account_number); ?></td>
                            <td><?php echo esc_html($entry->created_at); ?></td>
                            <td><?php echo $count; ?></td>
                            <td>
                                <button class="delete-entry button button-danger"
                                    data-id="<?php echo esc_attr($entry->id); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4">No entries found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}


//storing in database
function ajax_add_account_number()
{
    check_ajax_referer('submit_quiz_nonce', 'security');

    global $wpdb;
    $table_name = $wpdb->prefix . 'account_numbers';

    $account_number = sanitize_text_field($_POST['account_number']);
    $result = $wpdb->insert($table_name, array('account_number' => $account_number));

    if ($result) {
        wp_send_json_success('Account number added successfully.');
    } else {
        wp_send_json_error('Failed to add account number. It may already exist.');
    }
}
add_action('wp_ajax_add_account_number', 'ajax_add_account_number');

//importing CSV
function ajax_import_csv()
{
    check_ajax_referer('submit_quiz_nonce', 'security');

    if (!empty($_FILES['csv_file']['tmp_name'])) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'account_numbers';
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');

        while (($data = fgetcsv($file)) !== false) {
            $wpdb->insert($table_name, array('account_number' => sanitize_text_field($data[0])));
        }
        fclose($file);

        wp_send_json_success('CSV imported successfully.');
    } else {
        wp_send_json_error('No file uploaded.');
    }
}
add_action('wp_ajax_import_csv', 'ajax_import_csv');

//exporting CSV

function ajax_export_csv()
{
    // Check the nonce for security
    check_ajax_referer('submit_quiz_nonce', 'security');

    // Get the date from the URL parameter (if present)
    $date = isset($_GET['date']) ? sanitize_text_field($_GET['date']) : 'export';

    // Set the file name with the date appended
    $filename = 'account_numbers_export_' . $date . '.csv';

    // Set the headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Open the output stream
    $output = fopen('php://output', 'w');

    // Add CSV header
    fputcsv($output, array('ID', 'Account Number', 'Created At'));

    // Query to fetch data from the table
    global $wpdb;
    $table_name = $wpdb->prefix . 'account_numbers';
    $entries = $wpdb->get_results("SELECT * FROM $table_name");

    // Loop through the entries and write to CSV
    foreach ($entries as $entry) {
        fputcsv($output, array($entry->id, $entry->account_number, $entry->created_at));
    }

    // Close the output stream
    fclose($output);

    // End the script to avoid any additional output
    exit;
}
add_action('wp_ajax_export_csv', 'ajax_export_csv');


//entry delete

function ajax_delete_account_number()
{
    // Verify nonce for security
    check_ajax_referer('submit_quiz_nonce', 'security');

    // Access the global WordPress database object
    global $wpdb;

    // Table name
    $table_name = $wpdb->prefix . 'account_numbers';

    // Retrieve the ID from the AJAX request
    $id = intval($_POST['id']); // Ensure the ID is sanitized

    // Attempt to delete the entry from the database
    $result = $wpdb->delete($table_name, array('id' => $id));

    // Check if the deletion was successful
    if ($result) {
        wp_send_json_success('Account number deleted successfully.');
    } else {
        wp_send_json_error('Failed to delete the account number.');
    }
}
add_action('wp_ajax_delete_account_number', 'ajax_delete_account_number');



//generate coupon code dynamically

add_action('wp_ajax_generate_coupon_code', 'generate_coupon_code');
add_action('wp_ajax_nopriv_generate_coupon_code', 'generate_coupon_code');

function generate_coupon_code()
{
    if (!isset($_POST['quiz_nonce']) || !wp_verify_nonce($_POST['quiz_nonce'], 'submit_quiz_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce']);
        return;
    }


    // Retrieve data from the AJAX request
    $username = sanitize_text_field($_POST['username']);
    $usercontact = sanitize_text_field($_POST['usercontact']);
    $score = floatval($_POST['score']);

    // Generate unique coupon code using the first 4 letters of username and contact
    $username_part = substr($username, 0, 4);
    $contact_part = substr($usercontact, 0, 4);
    $coupon_code = strtoupper($username_part . $contact_part);

    // Determine discount based on score
    if ($score > 80) {
        $discount = 50;
    } elseif ($score >= 70) {
        $discount = 45;
    } elseif ($score >= 50) {
        $discount = 10;
    } else {
        wp_send_json_error(['message' => 'Score too low for a coupon']);
        return;
    }

    // Check if the coupon already exists
    if (get_page_by_title($coupon_code, OBJECT, 'shop_coupon')) {
        wp_send_json_success(['coupon_code' => $coupon_code]);
        return;
    }

    // Create coupon
    $expiry_date = date('Y-m-d', strtotime('+1 year'));
    $coupon = [
        'post_title' => $coupon_code,
        'post_content' => '',
        'post_status' => 'publish',
        'post_author' => get_current_user_id(),
        'post_type' => 'shop_coupon',
    ];

    $coupon_id = wp_insert_post($coupon);

    if ($coupon_id) {
        update_post_meta($coupon_id, 'discount_type', 'percent');
        update_post_meta($coupon_id, 'coupon_amount', $discount);
        update_post_meta($coupon_id, 'individual_use', 'yes');
        update_post_meta($coupon_id, 'usage_limit', 1);
        update_post_meta($coupon_id, 'expiry_date', $expiry_date);
        update_post_meta($coupon_id, 'free_shipping', 'no');

        wp_send_json_success([
            'coupon_code' => $coupon_code,
            'discount' => $discount // Add discount percentage to the response
        ]);
    } else {
        wp_send_json_error(['message' => 'Failed to create coupon']);
    }
}
add_action('wp_ajax_generate_coupon_code', 'generate_coupon_code');
add_action('wp_ajax_nopriv_generate_coupon_code', 'generate_coupon_code');
