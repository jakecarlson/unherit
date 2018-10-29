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

function get_guide_lists_by_category($destination_id = 0, $category_ids = [], $return = 'posts') {

    $options = get_destination_options($destination_id);
    $include_child_guide_lists = (isset($options['guide_lists']) && ($options['guide_lists'] == 'true'));

    $all_child_destinations[] = $destination_id;
    if( $include_child_guide_lists ) {
        $all_child_destinations = get_all_children($destination_id, $all_child_destinations);
    }

    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'title';
    $order = isset($_GET['order']) ? $_GET['order'] : 'asc';

    $args = array(
        'post_type' => 'travel-directory',
        'posts_per_page' => -1,
        'post_status' => array('publish'),
        'orderby' => 'title',
        'order' => ($sort == 'title') ? $order : 'asc',
        'meta_query' => array(
            array(
                'key' => 'destination_parent_id',
                'value' => $all_child_destinations,
                'compare' => 'IN'
            )
        ),

    );
    if (!empty($category_ids)) {
        add_directory_category_constraint($args, $category_ids);
    }

    $posts = get_posts($args);

    $id_map = [];
    if ($sort != 'title') {
        foreach ($posts as $item) {
            $rating = get_meta_rating($item->ID);
            $id_map[$item->ID] = isset($rating['rating_types_'.$sort]) ? $rating['rating_types_'.$sort] : 0;
        }
        if ($order == 'desc') {
            arsort($id_map);
        } else {
            asort($id_map);
        }
    } else {
        foreach ($posts as $item) {
            $id_map[$item->ID] = 0;
        }
    }

    if ($return == 'Sorted IDs') {
        return array_keys($id_map);
    } else {
        if ($sort != 'title') {
            $posts = [];
            foreach ($id_map as $key => $item) {
                $posts[] = get_post($key);
            }
        }
        wp_reset_postdata();
        return $posts;
    }

}

function get_destination_pages( $post_id = 0, $return = 'list', $lang = false ) {
    // cache
    $cache_key   = 'destination_pages_list';
    $cache_group = $post_id . ( $lang ? '_' . $lang : '' );

    if ( $return === 'list' && ! is_admin() ) {
        $info = wp_cache_get( $cache_key, $cache_group );

        if ( false !== $info ) {
            return $info;
        }
    }
    // -------------

    $options  = get_option( get_travel_guide_option_key( 'travel_guide_options' ) );
    $settings = $options ? json_decode( $options, true ) : array();
    if( $lang ) {
        $options = get_option( get_travel_guide_option_key( 'travel_guide_options', $lang ) );
        $settings_lang = $options ? json_decode( $options, true ) : array();
    }

    // Get child destinations
    $children_query = unherit_get_places_query($post_id);
    $place_ids = [];
    $children = $children_query->posts;
    foreach ($children as $child) {
        $place_ids[] = $child->ID;
        $grandchildren_query = unherit_get_places_query($child->ID);
        $granchildren = $grandchildren_query->posts;
        foreach ($granchildren as $grandchild) {
            $place_ids[] = $grandchild->ID;
        }
    }
    array_push($place_ids, $post_id);

    $args = array(
        'post_type' => get_pages_cpt( $post_id ),
        'posts_per_page' => -1,
        // 'meta_key' => 'guide_page_order',
        // 'orderby' => 'meta_value_num',
        'orderby' => 'title',
        'order' => 'ASC',
        'suppress_filters' => $lang? 1 : 0,
        'meta_query' => array(
            array(
                'key' => 'is_disabled_master_page',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => 'is_disabled_guide_page',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => 'destination_parent_id',
                'value' => $place_ids,
                'compare'  => 'IN',
            )
        )
    );

    if ($return == 'query')
        return $args;

    // Get the posts
    remove_action( 'pre_get_posts', array( Destination_CPT::this(), 'sort_destinations_by_meta_value' ) );
    $guide_pages = get_posts( $args );
    add_action( 'pre_get_posts', array( Destination_CPT::this(), 'sort_destinations_by_meta_value' ) );

    if ($return == 'posts')
        return $guide_pages;

    $items = get_page_hierarchy( $guide_pages );
    $dest = get_post( $post_id );
    $dest_name = create_parent_dest_slug( $dest, false );
    $pages_slugs = get_guide_pages_slugs_new( $post_id );

    $info = [];
    $titles = [];
    foreach( $items as $key => $item ) {
        
        $permalink = get_permalink($key); // '';
        $title = get_the_title($key);
        if (!in_array($title, $titles) && !empty($permalink)) {

            $titles[] = $title;
            $info[$key]['id'] = $key;
            $info[$key]['title'] = $title;
            $info[$key]['link'] = $permalink;

            if (isset($pages_slugs->$item)) {
                $settings_page_base_lang = isset( $settings_lang['page_base'] ) ? $settings_lang['page_base'] : '';
                $settings_page_base = isset( $settings['page_base'] ) ? $settings['page_base'] : '';
                $info[$key]['link'] = get_final_permalink( 'destination-page', $pages_slugs->$item, $settings_page_base, $settings_page_base_lang, $lang );
            }

        }

    }

    // cache
    if ( ! is_admin() ) {
        wp_cache_set( $cache_key, $info, $cache_group );
    }
    // ------------

    return $info;
}

function get_guide_lists_directory( $post_id = 0 ) {
    global $post;

    $post_id = ( $post_id == 0 )? $post->ID : $post_id;
    $dest = get_post( $post_id );
    $options = get_destination_options( $post_id );
    $include_child_guide_lists = ( isset( $options['guide_lists'] ) && $options['guide_lists'] == 'true' ) ? true : false;

    $all_child_destinations[] = $post_id;
    if( $include_child_guide_lists ) {
        $all_child_destinations = get_all_children( $post_id, $all_child_destinations );
    }

    $args = array(
        'post_type' => 'travel-directory',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'post_status' => array( 'publish' ),
        'suppress_filters' => defined( 'ICL_LANGUAGE_CODE' )? 0 : 1,
        'meta_query' => array(
            array(
                'key' => 'destination_parent_id',
                'value' => $all_child_destinations,
                'compare' => 'IN'
            )
        )
    );
    $guide_lists = get_posts( $args );

    $directory = array();
    $terms_unsorted = array();
    $terms = array();
    $images = array();
    foreach( $guide_lists as $list ) {
        $guides_terms = get_the_terms( $list->ID, 'travel-dir-category' );
        if( $guides_terms ) {
            foreach ($guides_terms as $category) {
                if( in_array( $category->term_id, $terms ) === false ) {
                    if( in_array( $category->term_id, $images ) === false && has_post_thumbnail( $list->ID ) )
                        $images[] = $category->term_id;

                    if ( ! property_exists( $category, 'object_id' ) ) {
                        $category->object_id = $list->ID;
                    }

                    $terms_unsorted[] = $category;
                    $first_post_id[] = $list->ID;
                    $terms[] = $category->term_id;
                }
            }
            
        }
    }

    $terms_sorted = sort_directory_terms( $terms_unsorted );
    foreach( $terms_sorted as $key => $term ) {
        $directory[$term->term_id]['post_ID'] = $term->object_id;
        $directory[$term->term_id]['name'] = $term->name;
        $directory[$term->term_id]['slug'] = $term->slug;
        $directory[$term->term_id]['link'] = trailingslashit(get_destination_taxonomy_term_links( $term->slug, $dest->post_name, 'travel-dir-category' ));
        if( in_array( $term->term_id, $images ) ) {
            $directory[$term->term_id]['image'] = get_post_thumbnail_id( $term->object_id );
        }
    }
    return $directory;
}

// Add directory category constraint
function add_directory_category_constraint(&$args, $category_ids = []) {
    if (!empty($category_ids)) {
        if (!is_numeric($category_ids[0])) {
            foreach ($category_ids as $key=>$category_id) {
                $term = get_term_by('slug', $category_id, 'travel-dir-category');
                $category_ids[$key] = $term->term_id;
            }
        }
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'travel-dir-category',
                'field'    => 'term_id',
                'terms'    => $category_ids
            )
        );
    }
}

function unherit_get_places_query($parent_id = null) {
    $args = [
        'post_type' => 'destination',
        'post_parent' => $parent_id,
        'posts_per_page' => -1,
        'meta_key' => 'destination_order',
        'orderby' => ['meta_value_num'=>'ASC', 'title'=>'ASC'],
    ];
    return new WP_Query($args);
}

function unherit_get_map_pins($post_id = null) {

    if (is_null($post_id)) {
        $post_id = get_the_ID();
    }

    $base_args = [
        'post_type' => 'travel-directory',
        'posts_per_page' => -1,
    ];

    if (unherit_post_is_itinerary($post_id)) {
        
        $sites = CustomRelatedPosts::get()->relations_to($post_id);
        $args = array_merge(
            $base_args, 
            [
                'include' => array_column($sites, 'id'),
                'post__in' => array_column($sites, 'id'),
            ]
        );

        $all = unherit_query_map_posts($args);

    } else {
        
        $args = array_merge(
            $base_args,
            [
                'meta_query' => [
                    [
                        'key' => 'destination_parent_id',
                        'value' => $post_id,
                    ],
                ]
            ]
        );

        $all = unherit_query_map_posts($args);

        $children = get_children($post_id);
        foreach($children as $child) {
            
            $args = array_merge(
                $base_args,
                [
                    'meta_query' => [
                        [
                            'key' => 'destination_parent_id',
                            'value' => $child->ID,
                        ],
                    ]
                ]
            );

            $all = array_merge($all, unherit_query_map_posts($args));
            $all = get_children_directory_gmaps_options($child->ID, $all);

        }

    }

    return $all;

}

function unherit_query_map_posts($args) {
    if (isset($_GET['categories'])) {
        add_directory_category_constraint($args, $_GET['categories']);
    }
    $items = get_posts($args);
    $pins = [];
    foreach($items as $item) {
        $pins[$item->ID] = get_directory_gmaps_options($item->ID);
    }
    return $pins;
}

function unherit_post_is_itinerary($post_id = null) {
    return (get_post_type($post_id) == 'destination-page');
}

function unherit_get_coords_midpoint($data)
{
    if (!is_array($data)) return FALSE;

    $num_coords = count($data);

    $X = 0.0;
    $Y = 0.0;
    $Z = 0.0;

    foreach ($data as $coord)
    {
        $lat = $coord['latitude'] * pi() / 180;
        $lon = $coord['longitude'] * pi() / 180;

        $a = cos($lat) * cos($lon);
        $b = cos($lat) * sin($lon);
        $c = sin($lat);

        $X += $a;
        $Y += $b;
        $Z += $c;
    }

    $X /= $num_coords;
    $Y /= $num_coords;
    $Z /= $num_coords;

    $lon = atan2($Y, $X);
    $hyp = sqrt($X * $X + $Y * $Y);
    $lat = atan2($Z, $hyp);

    return [
        'latitude' => $lat * 180 / pi(), 
        'longitude' => $lon * 180 / pi(),
    ];
}

function unherit_get_map_zoom($coords) {
    $distance = unherit_get_coords_distance($coords);
    $zooms = [270, 180, 120, 90, 45, 30, 0];
    $zoom = 8;
    for ($i = 0, $numZooms = count($zooms); $i < $numZooms; ++$i) {
        if ($distance > $zooms[$i]) {
            $zoom = $i;
            break;
        }
    }
    return $zoom;
}

function unherit_get_coords_distance($coords) {
    
    $lats = array_column($coords, 'latitude');
    $lons = array_column($coords, 'longitude');

    $lat_min = min($lats);
    $lat_max = max($lats);
    $lon_min = min($lons);
    $lon_max = max($lons);

    // echo "Lat: {$lat_min}, {$lat_max}<br>";
    // echo "Lon: {$lon_min}, {$lon_max}<br>";
    
    $theta = $lon_max - $lon_min;
    $dist = sin(deg2rad($lat_max)) * sin(deg2rad($lat_min)) +  cos(deg2rad($lat_max)) * cos(deg2rad($lat_min)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);

    return $dist;

}