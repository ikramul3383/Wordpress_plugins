<?php
/*
Plugin Name: Child Theme Generator
Description: Generate child themes with custom options.
Version: 1.0
Author: ABC Developments
*/

// Add menu option in admin bar under "Settings"
add_action('admin_menu', 'ctg_add_menu_option');

function ctg_add_menu_option()
{
    add_submenu_page(
        'options-general.php', // Parent menu slug
        'Child Theme Generator', // Page title
        'Child Theme Generator', // Menu title
        'manage_options', // Capability required
        'child-theme-generator', // Menu slug
        'ctg_settings_page' // Callback function
    );
}

// Display settings page content
function ctg_settings_page()
{
    $themes = wp_get_themes();
    $parent_themes = array();
    foreach ($themes as $theme) {
        if ($theme->parent() === false) {
            $parent_themes[$theme->get_stylesheet()] = $theme->get('Name');
        }
    }
    ?>
    <div class="wrap">
        <h2>Child Theme Generator</h2>
        <?php if (isset($_GET['success']) && $_GET['success'] == 'true'): ?>
            <div class="updated">
                <p>Child theme successfully generated!</p>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Parent Theme</th>
                    <td>
                        <select name="parent_theme">
                            <?php
                            // Get the active theme data
                            $active_theme = wp_get_theme();
                            $active_stylesheet = $active_theme->get_stylesheet();
                            $active_theme_name = $active_theme->get('Name');
                            ?>
                            <option value="<?php echo $active_stylesheet; ?>" selected="selected">
                                <?php echo $active_theme_name; ?>
                            </option>
                        </select>
                    </td>

                </tr>
                <tr valign="top">
                    <th scope="row">Theme Name</th>
                    <td><input type="text" name="theme_name" value="" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Author Name</th>
                    <td><input type="text" name="author_name" value="" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Version</th>
                    <td><input type="text" name="version" value="" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Text Domain</th>
                    <td><input type="text" name="text_domain" value="" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Template Name</th>
                    <td><input type="text" name="template_name" value="" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Screenshot</th>
                    <td><input type="file" name="screenshot" accept=".png" /></td>
                </tr>


                <tr valign="top">
                    <th scope="row">Development Type</th>
                    <td>
                        <label><input type="radio" name="development_type" value="acf_based" /> ACF Based</label>
                    </td>
                </tr>
            </table>
            <?php submit_button('Generate Child Theme'); ?>
        </form>
    </div>
    <?php
}

// Handle form submission
function ctg_handle_form_submission()
{
    if (isset($_POST['theme_name'], $_POST['parent_theme'])) {
        $theme_name = $_POST['theme_name'];
        $parent_theme = $_POST['parent_theme'];
        $author_name = $_POST['author_name'];
        $version = $_POST['version'];
        $text_domain = $_POST['text_domain'];
        $template_name = $_POST['template_name'];

        // Create style.css content
        $style_css_content = "/*
        Theme Name: $theme_name
        Author: $author_name
        Version: $version
        Text Domain: $text_domain
        Template: $template_name
        */";

        // Create the new theme directory
        $new_theme_directory = WP_CONTENT_DIR . '/themes/' . $theme_name;
        if (!file_exists($new_theme_directory)) {
            mkdir($new_theme_directory, 0755, true);
        }

        // Create style.css file in the new theme directory
        $style_css_file = fopen($new_theme_directory . '/style.css', 'w');
        fwrite($style_css_file, $style_css_content);
        fclose($style_css_file);

        // Copy parent theme files to child theme directory
        $parent_theme_directory = get_theme_root() . '/' . $parent_theme;
        $theme_files = array(
            'single.php',
            'page.php',
            'index.php',
            'functions.php',
            'header.php',
            'footer.php'
        );

        // Loop through each file name and create the corresponding file
        foreach ($theme_files as $file_name) {
            $file_content = '';
            $file_path = $new_theme_directory . '/' . $file_name;
            if (!file_exists($file_path)) {
                if (file_put_contents($file_path, $file_content) !== false) {
                    //echo 'File created successfully: ' . $file_name . '<br>';
                } else {
                    echo 'Error creating file: ' . $file_name . '<br>';
                }
            } else {
                echo 'File already exists: ' . $file_name . '<br>';
            }
        }
        // Handle file upload
        if (!empty($_FILES['screenshot']['name'])) {
            $uploaded_file = $new_theme_directory . '/' . basename($_FILES['screenshot']['name']);
            if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $uploaded_file)) {
             //   echo "Screenshot uploaded successfully.";
            } else {
                echo "Failed to upload screenshot.";
            }
        }

        if ($_POST['development_type'] == 'acf_based') {
            $page_template_directory = $new_theme_directory . '/page-template';
            $page_content_directory = $new_theme_directory . '/page-content/sections';

            if (!file_exists($page_template_directory)) {
                mkdir($page_template_directory, 0755, true);
            }

            if (!file_exists($page_content_directory)) {
                mkdir($page_content_directory, 0755, true);
            }

            // Create a sample page template file
            $page_template_content = "<h1><?php the_title(); ?></h1><div class='content'><?php the_content(); ?></div>";
            file_put_contents($page_template_directory . '/page-template.php', $page_template_content);
            wp_redirect(admin_url('options-general.php?page=child-theme-generator&success=true'));
        } elseif ($_POST['development_type'] == 'ecommerce_based') {
            wp_redirect(admin_url('options-general.php?page=child-theme-generator&success=true'));
        }



    }
}

add_action('admin_init', 'ctg_handle_form_submission');
