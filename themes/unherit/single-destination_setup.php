<?php
// Places (child destinations)
// -------------------------------------------------

// This template includes built-in layout containers.
add_filter('theme_template_has_layout', function(){ return true; });

// Helpers
$original_post = $post;
$dest = get_the_destination_post();
$settings = get_destination_settings();

// Check for content sections
$sub_nav_items = destination_sub_navigation(false); // only return

if (isset($_GET['category'])) {
    $guide_term = get_term_by('slug', $_GET['category'], 'travel-dir-category');
    $term_id = $guide_term->term_id;
} else {
    $term_id = 0;
}

// The Query
$args = array(
    'post_type' => 'destination',
    'post_parent' => $dest->ID, // $dest_ID,
    'posts_per_page' => isset($settings['number_posts_child'])? $settings['number_posts_child'] : 2,
    'meta_key' => 'destination_order',
    'orderby' => array('meta_value_num' => 'ASC', 'title' => 'ASC' ),
);
$places_query = new WP_Query($args);

get_header(); 

get_template_part('templates/parts/destinations-sub-nav');
?>