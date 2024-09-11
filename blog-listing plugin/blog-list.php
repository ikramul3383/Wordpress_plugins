<?php
/*
Plugin Name: Blog Listing Plugin
Description: Creates a blog listing page on activation.
Version: 1.0
Author: ABC
*/

// Activation hook

function blog_posts_shortcode($atts)
{
    $query = new WP_Query(
        array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'order' => 'DESC',
        )
    );

    $output = '<ul>';
    while ($query->have_posts()) {
        $query->the_post();
        $output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
    }
    $output .= '</ul>';

    wp_reset_postdata();

    return $output;
}

add_shortcode('blog_posts', 'blog_posts_shortcode');
register_activation_hook(__FILE__, 'blog_listing_plugin_activate');

function blog_listing_plugin_activate()
{
    $post_content = '[blog_posts]';
    $post_title = 'Blog Listing';

    // Create a new page for the blog listing
    $page = array(
        'post_title' => $post_title,
        'post_content' => $post_content,
        'post_status' => 'publish',
        'post_author' => 1,
        'post_type' => 'page'
    );

    // Insert the post into the database
    wp_insert_post($page);
}


?>