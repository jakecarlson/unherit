<?php
// Places (child destinations)
// -------------------------------------------------

// This template includes built-in layout containers.
add_filter('theme_template_has_layout', function(){ return true; });

// Get the destination & settings
$dest = get_the_destination_post();
$settings = get_destination_settings();

get_header();
$sub_nav_items = destination_sub_navigation(false);
get_template_part('templates/parts/destinations-sub-nav');

$places_query = unherit_get_places_query($dest->ID);
$posts_query = unherit_get_posts_query($dest->ID);
?>