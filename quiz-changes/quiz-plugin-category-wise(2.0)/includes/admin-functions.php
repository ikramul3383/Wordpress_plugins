<?php

// Add custom meta box for quiz question answers
function quiz_add_custom_meta_box()
{
    add_meta_box('quiz_question_meta', 'Quiz Question Details', 'quiz_question_meta_callback', 'quiz_question', 'normal', 'high');
}
add_action('add_meta_boxes', 'quiz_add_custom_meta_box');

function quiz_question_meta_callback($post)
{
    // Retrieve saved answers if they exist
    $answer1 = get_post_meta($post->ID, 'quiz_answer_1', true);
    $answer2 = get_post_meta($post->ID, 'quiz_answer_2', true);
    $answer3 = get_post_meta($post->ID, 'quiz_answer_3', true);
    $answer4 = get_post_meta($post->ID, 'quiz_answer_4', true);
    $correct_answer = get_post_meta($post->ID, 'quiz_correct_answer', true);

    ?>

    <label for="quiz_answer_1">Answer 1:</label>
    <input type="text" name="quiz_answer_1" id="quiz_answer_1" value="<?php echo esc_attr($answer1); ?>"
        style="width: 100%;"><br><br>

    <label for="quiz_answer_2">Answer 2:</label>
    <input type="text" name="quiz_answer_2" id="quiz_answer_2" value="<?php echo esc_attr($answer2); ?>"
        style="width: 100%;"><br><br>

    <label for="quiz_answer_3">Answer 3:</label>
    <input type="text" name="quiz_answer_3" id="quiz_answer_3" value="<?php echo esc_attr($answer3); ?>"
        style="width: 100%;"><br><br>

    <label for="quiz_answer_4">Answer 4:</label>
    <input type="text" name="quiz_answer_4" id="quiz_answer_4" value="<?php echo esc_attr($answer4); ?>"
        style="width: 100%;"><br><br>

    <label for="quiz_correct_answer">Correct Answer:</label>
    <select name="quiz_correct_answer" id="quiz_correct_answer" style="width: 100%;">
        <?php
        // Create an array of answers
        $answers = [
            'answer_1' => $answer1,
            'answer_2' => $answer2,
            'answer_3' => $answer3,
            'answer_4' => $answer4,
        ];

        // Loop through answers dynamically and create the dropdown options
        foreach ($answers as $answer_key => $answer_value) {
            if (!empty($answer_value)) { // Only display answers that have content
                ?>
                <option value="<?php echo esc_attr($answer_key); ?>" <?php selected($correct_answer, $answer_key); ?>>
                    <?php echo esc_html($answer_value); // Display the answer text ?>
                </option>
                <?php
            }
        }
        ?>
    </select>

    <?php
}

// Save the question and answers as post meta
function quiz_save_question_meta($post_id)
{
    if (isset($_POST['quiz_answer_1'])) {
        update_post_meta($post_id, 'quiz_answer_1', sanitize_text_field($_POST['quiz_answer_1']));
    }
    if (isset($_POST['quiz_answer_2'])) {
        update_post_meta($post_id, 'quiz_answer_2', sanitize_text_field($_POST['quiz_answer_2']));
    }
    if (isset($_POST['quiz_answer_3'])) {
        update_post_meta($post_id, 'quiz_answer_3', sanitize_text_field($_POST['quiz_answer_3']));
    }
    if (isset($_POST['quiz_answer_4'])) {
        update_post_meta($post_id, 'quiz_answer_4', sanitize_text_field($_POST['quiz_answer_4']));
    }
    if (isset($_POST['quiz_correct_answer'])) {
        update_post_meta($post_id, 'quiz_correct_answer', sanitize_text_field($_POST['quiz_correct_answer']));
    }
}
add_action('save_post', 'quiz_save_question_meta');

function quiz_display_coupon_codes_page()
{
    global $wpdb;

    // Handle form submission and saving the coupon code
    if (isset($_POST['submit_coupon'])) {
        // Sanitize form input
        $coupon_range = sanitize_text_field($_POST['coupon_range']);
        $coupon_code = sanitize_text_field($_POST['coupon_code']);

        // Insert the coupon code into the database
        $wpdb->insert(
            $wpdb->prefix . 'quiz_coupon_codes', // Table name
            array(
                'coupon_range' => $coupon_range,
                'coupon_code' => $coupon_code,
            ),
            array('%s', '%s') // Format for the inserted data
        );
        echo '<div class="updated"><p>Coupon Code Added Successfully!</p></div>';
    }

    // Fetch all coupon codes from the database
    $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}quiz_coupon_codes");

    ?>
    <div class="wrap">
        <h1>Quiz Coupon Codes</h1>
        <p>Here you can manage your quiz coupon codes.</p>

        <!-- Form to add new coupon code -->
        <form method="POST" action="">
            <table class="form-table">
                <tr>
                    <th><label for="coupon_range">Coupon Range</label></th>
                    <td>
                        <select name="coupon_range" id="coupon_range" class="regular-text" required>
                            <option value="70-85">70-85%</option>
                            <option value="86-100">86-100%</option>
                            <!-- Add more ranges here as needed -->
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="coupon_code">Coupon Code</label></th>
                    <td><input type="text" name="coupon_code" id="coupon_code" class="regular-text" required></td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="submit_coupon" id="submit_coupon" class="button button-primary"
                    value="Add Coupon Code">
            </p>
        </form>

        <!-- Display coupon codes in a table -->
        <h2>Existing Coupon Codes</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column">Sr. No</th>
                    <th scope="col" class="manage-column">Coupon Range</th>
                    <th scope="col" class="manage-column">Coupon Code</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($results): ?>
                    <?php $sr_no = 1; ?>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?php echo $sr_no++; ?></td>
                            <td><?php echo esc_html($row->coupon_range); ?></td>
                            <td><?php echo esc_html($row->coupon_code); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">No coupon codes found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
