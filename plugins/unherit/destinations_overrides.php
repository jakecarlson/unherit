<?php

// Make sure destination directory maps display correctly
add_filter('destination_id_for_maps', 'unherit_set_destination_id_for_maps');
function unherit_set_destination_id_for_maps($post_id = 0) {
    if (get_post_type() == 'travel-directory' && !is_single()) {
        return get_the_destination_ID();
    }
    return $post_id;
}

// Set the map pin dir
add_filter('destination_map/map_path', 'unherit_set_map_path');
function unherit_set_map_path($path) {
    return UNHERIT_PLUGIN_URL . '/img';
}

// Override get_directory_gmaps_options
function get_directory_gmaps_options( $post_id ) {
    $options = get_meta_guide_lists_details( $post_id );
    $intro = '';
    $intro_text = get_post_meta( $post_id, 'guide_lists_intro' );
    if ( is_array( $intro_text ) ) {
        foreach ( $intro_text as $text ) {
            if ( ! empty( $text ) ) {
                $intro = $text;
            }
        }
    }

    $google_map = ( isset( $options['google_map'] ) && ! empty( $options['google_map'] ) ) ? $options['google_map'] : array();
    $attrs = array();
    $attrs['latitude'] = isset( $google_map['latitude'] ) ? $google_map['latitude'] : '';
    $attrs['longitude'] = isset( $google_map['longitude'] ) ? $google_map['longitude'] : '';
    $attrs['zoom'] = isset( $google_map['zoom'] ) ? $google_map['zoom'] : '';
    $attrs['title'] = get_the_title( $post_id );
    $attrs['intro'] = $intro;
    $attrs['link'] = get_the_permalink( $post_id );
    if(has_post_thumbnail( $post_id )) {
        $attachment_id = get_post_thumbnail_id( $post_id );
        $img = wp_get_attachment_image_src( $attachment_id, 'medium' );
        $attrs['image'] = '<img src="'.esc_url( $img[0] ).'" width="'.$img[1].'" height="'.$img[2].'">';
        $attrs['image_src'] = $img[0];
    } else {
        $attrs['image'] = '';
        $attrs['image_src'] = '';
        $url = '';
    }

    $attrs['rating'] = '';
    $rating_data = get_guide_lists_rating( $post_id );
    $ratings = array();
    if ( isset( $rating_data['enabled'] ) && ! empty( $rating_data['enabled'] ) ) {
        foreach ( $rating_data['enabled'] as $type => $enabled ) {

            if ( $type == 'menu_order' || $enabled !== 'true' )
                continue;

            $key = str_replace( 'rating_types_', '', $type );
            if ( isset( $rating_data['settings'][$key] ) && isset( $rating_data[$type] ) ) {
                $ratings[$key] = $rating_data['settings'][$key];
                $ratings[$key]['value'] = $rating_data[$type];
            }
        }
    }
    if ( ! empty( $ratings )) {
        ob_start();
        foreach ( $ratings as $key => $data ) {
            ?>
            <div class="rating-container">
				<span class="rating <?php echo 'rating-'. esc_attr( $key ); ?>">
					<div class="ratebox " data-id="<?php echo '-'. esc_attr( $key ); ?>" data-rating="<?php echo esc_attr( $data['value'] ); ?>" data-state="rated"></div>
					<input type="hidden" class="rate-class"  value="<?php echo esc_attr( $data['class'] ); ?>">
					<input type="hidden" class="rate-color"  value="<?php echo esc_attr( $data['color'] ); ?>">
					<input type="hidden" class="rating-is-front"  value="true">
					<span class="infobox-value-rating"><?php echo $data['value']; ?></span>
				</span>
            </div>
            <?php
        }
        $attrs['ratings'] = ob_get_clean();
    }

    $category = strtolower(get_post_meta($post_id, 'whs_category', true));
    $endangered = (get_post_meta($post_id, 'whs_endangered', true) == '1') ? '_endangered' : '';
    $attrs['pin_img'] = "marker_{$category}{$endangered}.png";

    return $attrs;
}

// Override get_destination_intro
function get_destination_intro( $post_ID = 0 ) {
    global $post;

    switch ( $post->post_type ) {
        case 'destination':
            $meta_name = 'destination_intro';
            break;

        case 'destination-page':
            $meta_name = 'destination_intro';
            break;

        case 'travel-directory':
            $meta_name = 'guide_lists_intro';
            break;

        default:
            # code...
            break;
    }

    $id = ( $post_ID ) ? $post_ID : $post->ID;
    $intro = get_post_meta( $id, $meta_name, true );

    if (($post->post_type == 'travel-directory') && !is_admin()) {
        $citation_url = get_post_meta($id, 'whs_url', true);
        $link = ' <span class="whs-source">[<a href="' . $citation_url . '" target="_blank">source</a>]</span>';
        $intro .= $link;
    }

    return $intro;
}

function get_guide_lists_by_category( $destination_id = 0, $category_id = 0, $return = 'posts' ) {

    $options = get_destination_options( $destination_id );
    $include_child_guide_lists = ( isset( $options['guide_lists'] ) && $options['guide_lists'] == 'true' )? true : false;

    $all_child_destinations[] = $destination_id;
    if( $include_child_guide_lists ) {
        $all_child_destinations = get_all_children( $destination_id, $all_child_destinations );
    }

    $args = array(
        'post_type' => 'travel-directory',
        'posts_per_page' => -1,
        'post_status' => array( 'publish' ),
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'destination_parent_id',
                'value' => $all_child_destinations,
                'compare' => 'IN'
            )
        ),

    );
    if ($category_id !== 0) {
        add_directory_category_constraint($args, $category_id);
    }

    $lists = get_posts( $args );

    $cat = isset( $_GET['cat'] ) ? $_GET['cat'] : 'star';
    $order = isset( $_GET['order'] ) ? $_GET['order'] : 'desc';
    $list = array();
    foreach( $lists as $item ) {
        $rating = get_meta_rating( $item->ID );
        $list[$item->ID] = isset( $rating['rating_types_'.$cat] ) ? $rating['rating_types_'.$cat] : 0;
    }

    if( $order == 'desc' )
        arsort( $list );
    if( $order == 'asc' )
        asort( $list );

    if ( $return == 'Sorted IDs' ) {
        return array_keys( $list );
    }

    $posts_sorted = array();
    foreach($list as $key => $item) {
        $posts_sorted[] = get_post( $key );
    }

    /* Restore original Post Data */
    wp_reset_postdata();

    return $posts_sorted;
}

function destination_sub_navigation( $echo = true, $include_categories = true ) {
    global $post;

    $sub_nav_items = array();
    $sub_nav_links = get_sub_nav_links();
    $id = get_the_destination_ID();

    if (isset($id)) {
        /*foreach($sub_nav_links as $key => $val) {
            // Output the menu itmes
            if (($val != 'directory') || $include_categories) {
                $sub_nav_items[$val] = output_sub_menu_item( $id, $val, $echo );
            }
        }*/
        foreach($sub_nav_links as $key => $val) {
            // Output the menu itmes
            $sub_nav_items[$val] = output_sub_menu_item( $id, $val, $echo );
            // var_dump($sub_nav_items[$val]);
        }
    }

    return apply_filters('destination_sub_navigation', $sub_nav_items);
}