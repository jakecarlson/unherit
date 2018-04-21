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
    if (!empty($category_id)) {
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'travel-dir-category',
                'field'    => 'term_id',
                'terms' => $category_id
            )
        );
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

/*
function output_sub_menu_item( $id, $item, $echo = true ) {
    $dest = get_post( $id );
    $settings = get_destination_settings();

    ob_start();
    switch ( $item ) {
        case 'places':
            $places = get_destinations( $dest->ID );
            if( count( $places ) && $echo ):
                // Link URL
                $places_url = get_destination_taxonomy_term_links( 'places', $dest->post_name );
                $places_title = ( isset( $settings['menu_title_child'] ) && !empty( $settings['menu_title_child'] ) )? $settings['menu_title_child'] : __( 'Places', 'destinations' );
                // List Item ?>
                <li><a href="<?php echo esc_url( trailingslashit($places_url) ); ?>"><?php echo $places_title; ?></a></li>
            <?php endif;
            $items = $places;
            break;

        case 'information':
            $info_pages = get_destination_pages( $dest->ID );
            if( count( $info_pages ) && $echo ): ?>
                <li class="dropdown show-on-hover">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown"><?php _e( 'Information', 'destinations' ); ?> <span class="caret"></span></a>
                    <ul class="dropdown-menu" role="menu">
                        <?php foreach( $info_pages as $info_page ): ?>
                            <li><a href="<?php echo esc_url( trailingslashit($info_page['link']) ); ?>"><?php echo $info_page['title']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif;
            $items = $info_pages;
            break;

        case 'directory':
            $directories = get_guide_lists_directory( $dest->ID );
            if( count( $directories ) && $echo ):

                // Temp method to get first value as main link.
                $first = reset( $directories );
                $directory_url = ( isset($first['link']) ) ? esc_url( $first['link'] ) : '#'; // '#';
                ?>
                <li class="dropdown show-on-hover">
                    <a href="<?php echo esc_url($directory_url); ?>" class="dropdown-toggle" data-toggle="dropdown"><?php _e( 'Directory', 'destinations' ); ?> <span class="caret"></span></a>
                    <ul class="dropdown-menu" role="menu">
                        <?php foreach($directories as $key => $directory):
                            ?>
                            <li><a href="<?php echo esc_url( trailingslashit($directory['link']) ); ?>"><?php echo $directory['name']; ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </li>
            <?php endif;
            $items = $directories;
            break;

        case 'articles':
            $articles = blog_posts_query( $dest->ID );
            if( isset( $settings['menu_item_blogs'] ) && $settings['menu_item_blogs'] == 'true' && is_object( $articles ) && isset( $articles->posts ) && count( $articles->posts ) && $echo ):
                $articles_url = get_destination_taxonomy_term_links( 'articles', $dest->post_name );
                $articles_title = ( isset( $settings['menu_title_blogs'] ) && !empty( $settings['menu_title_blogs'] ) )? $settings['menu_title_blogs'] : __( 'Blog', 'destinations' );
                ?>
                <li><a href="<?php echo esc_url( trailingslashit($articles_url) ); ?>"><?php echo $articles_title; ?></a></li>
            <?php endif;
            $items = $articles;
            break;
    }

    if ( $echo ) {
        echo ob_get_clean();
    }

    return $items;
}
*/