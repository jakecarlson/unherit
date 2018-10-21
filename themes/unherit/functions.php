<?php if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) { die(); }

function unherit_get_pagination($query = false, $range = 4) {

    // $paged - number of the current page
    global $paged, $wp_query, $portfolio_query, $postIndex;

    // set the query variable (default $wp_query)
    $q = ($query) ? $query : $wp_query;

    // How many pages do we have?
    if ( !isset($max_page) ) {
        $max_page = $q->max_num_pages;
    }

    // We need the pagination only if there are more than 1 page
    if($max_page > 1) {

        // doesn't quite work for next/prev links without $wp_query setting so...
        $temp_q = $wp_query;	// save a temporary copy
        $wp_query = $q;			// overwrite with our query

        echo '<div class="paging clearfix"><ul class="pagination">';

        if (!$paged){ $paged = 1;}

        // To the previous page
        $prev = unherit_get_previous_posts_link('<i class="fa fa-angle-left"></i>');
        if (!empty($prev)) {
            echo '<li class="prev-post">'. $prev .'</li>';
        }

        // We need the sliding effect only if there are more pages than is the sliding range
        if ($max_page > $range) {

            // When closer to the beginning
            if ($paged < $range) {
                for($i = 1; $i <= ($range + 1); $i++) {
                    echo "<li";
                    if($i==$paged) echo " class='active'";
                    echo "><a href='" . unherit_get_pagenum_link($i) ."'>$i</a></li>";
                }
            } elseif($paged >= ($max_page - ceil(($range/2)))){
                // When closer to the end
                for($i = $max_page - $range; $i <= $max_page; $i++){
                    echo "<li";
                    if($i==$paged) echo " class='active'";
                    echo "><a href='" . unherit_get_pagenum_link($i) ."'>$i</a></li>";
                }
            } elseif($paged >= $range && $paged < ($max_page - ceil(($range/2)))){
                // Somewhere in the middle
                for($i = ($paged - ceil($range/2)); $i <= ($paged + ceil(($range/2))); $i++){
                    echo "<li";
                    if($i==$paged) echo " class='active'";
                    echo "><a href='" . unherit_get_pagenum_link($i) ."'>$i</a></li>";
                }
            }
        } else{
            // Less pages than the range, no sliding effect needed
            for($i = 1; $i <= $max_page; $i++){
                echo "<li";
                if($i==$paged) echo " class='active'";
                echo "><a href='" . unherit_get_pagenum_link($i) ."'>$i</a></li>";
            }
        }

        // Next page
        $next = unherit_get_next_posts_link('<i class="fa fa-angle-right"></i>');
        if (!empty($next)) {
            echo '<li class="next-post">'. $next .'</li>';
        }

        $wp_query = $temp_q;

        echo '</ul></div>';
        //echo '<div style="clear:both"></div>';
    }
}

function unherit_get_pagenum_link($num) {
    $params = $_GET;
    if ($num == 1) {
        if (isset($params['pagenum'])) {
            unset($params['pagenum']);
        }
    } else {
        $params['pagenum'] = $num;
    }
    if (empty($params)) {
        $url = '.';
    } else {
        $url = '?' . http_build_query($params);
    }
    return $url;
}

function unherit_get_previous_posts_link( $label = null ) {
    global $paged;

    if ( null === $label )
        $label = __( '&laquo; Previous Page' );

    if ( !is_single() && $paged > 1 ) {
        /**
         * Filters the anchor tag attributes for the previous posts page link.
         *
         * @since 2.7.0
         *
         * @param string $attributes Attributes for the anchor tag.
         */
        $attr = apply_filters( 'previous_posts_link_attributes', '' );
        return '<a href="' . unherit_get_previous_posts_page_link( false ) . "\" $attr>". preg_replace( '/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label ) .'</a>';
    }
}

function unherit_get_previous_posts_page_link() {
    global $paged;
    if ( !is_single() ) {
        $nextpage = intval($paged) - 1;
        if ( $nextpage < 1 )
            $nextpage = 1;
        return unherit_get_pagenum_link($nextpage);
    }
}

function unherit_get_next_posts_link( $label = null, $max_page = 0 ) {
    global $paged, $wp_query;

    if ( !$max_page )
        $max_page = $wp_query->max_num_pages;

    if ( !$paged )
        $paged = 1;

    $nextpage = intval($paged) + 1;

    if ( null === $label )
        $label = __( 'Next Page &raquo;' );

    if ( !is_single() && ( $nextpage <= $max_page ) ) {
        /**
         * Filters the anchor tag attributes for the next posts page link.
         *
         * @since 2.7.0
         *
         * @param string $attributes Attributes for the anchor tag.
         */
        $attr = apply_filters( 'next_posts_link_attributes', '' );

        return '<a href="' . unherit_get_next_posts_page_link( $max_page ) . "\" $attr>" . preg_replace('/&([^#])(?![a-z]{1,8};)/i', '&#038;$1', $label) . '</a>';
    }
}

function unherit_get_next_posts_page_link($max_page = 0) {
    global $paged;

    if ( !is_single() ) {
        if ( !$paged )
            $paged = 1;
        $nextpage = intval($paged) + 1;
        if ( !$max_page || $max_page >= $nextpage )
            return unherit_get_pagenum_link($nextpage);
    }
}

function unherit_in_category($category, $post_id = false) {
    global $post;
    if ($post_id) {
        $tmp_post = get_post($post_id);
    } else {
        $tmp_post = $post;
    }
    return has_term($category, 'travel-dir-category', $tmp_post);
}

function unherit_create_temp_title($fields) {
  global $wpdb;
  $matches = 'A|An|The';
  $has_article = " CASE 
      WHEN $wpdb->posts.post_title regexp( '^($matches)[[:space:]]' )
        THEN trim(substr($wpdb->posts.post_title from 4)) 
      ELSE $wpdb->posts.post_title 
        END AS title2";
  if ($has_article) {
    $fields .= ( preg_match( '/^(\s+)?,/', $has_article ) ) ? $has_article : ", $has_article";
  }
  return $fields;
}

function unherit_sort_by_title($orderby) {
  $custom_orderby = " UPPER(title2) ASC";
  if ($custom_orderby) {
    $orderby = $custom_orderby;
  }
  return $orderby;
}

add_filter('posts_fields', 'unherit_create_temp_title');
add_action('posts_orderby', 'unherit_sort_by_title');

function unherit_output_site_meta($post_id = null, $show_rating = false) { ?>
    <div class="media-details">
        <ul class="list-inline pull-left">
            <?php if (unherit_in_category('visited', $post_id)) { ?>
                <li><i class="fa fa-fw fa-check-square-o" title="<?php _e('Visited', 'framework') ?>"></i></li>
            <?php } ?>
            <?php if (unherit_in_category('endangered', $post_id)) { ?>
                <li><i class="fa fa-fw fa-warning" title="<?php _e('Endangered', 'framework') ?>"></i></li>
            <?php } ?>
            <?php if (unherit_in_category('cultural', $post_id)) { ?>
                <li><i class="fa fa-fw fa-university" title="<?php _e('Cultural', 'framework') ?>"></i></li>
            <?php } ?>
            <?php if (unherit_in_category('natural', $post_id)) { ?>
                <li><i class="fa fa-fw fa-leaf" title="<?php _e('Natural', 'framework') ?>"></i></li>
            <?php } ?>
        </ul>
        <ul class="list-inline pull-right">
            <li class="destination"><i class="fa fa-map-marker fa-fw"></i> <span><?php echo get_the_title(get_guide_page_parent($post_id)); ?></span></li>
            <?php if ($show_rating) { ?>
                <?php $ratings = get_guide_lists_rating($post_id); ?>
                <?php
                foreach( $ratings['settings'] as $key => $rate) {
                    if(isset($rating['enabled']['rating_types_'.$key]) && $ratings['enabled']['rating_types_'.$key] == 'true'):
                        $rating_value = array_key_exists('rating_types_' . $key, $ratings) ? $ratings['rating_types_'.$key] : '';
                        ?>
                        <li>
                            <span class="rating rating-<?php echo $key; ?>">
                                <div class="ratebox" data-id="<?php echo $key; ?>" data-rating=""></div>
                                <input type="hidden" name="rating-types_<?php echo $key; ?>" id="rating-<?php echo $key; ?>" value="<?php echo $rating_value; ?>" />
                                <input type="hidden" class="rate-class"  value="<?php echo $rate['class']; ?>" />
                                <input type="hidden" class="rate-color"  value="<?php echo $rate['color']; ?>" />
                            </span>
                        </li>
                    <?php endif;
                }
                ?>
                <input type="hidden" class="rating-is-front" value="true" />
            <?php } ?>
        </ul>
    </div>
<?php
}

function rf_theme_extra_header_classs( $classes ) {

    $add_class = array();
    $header_size = '';
    $header_overlay = '';

    // Home page class
    if (is_front_page() || is_home()) {
        $header_size = get_options_data('home-page', 'home-header-size', 'large'); // header size
        $header_overlay = get_options_data('home-page', 'home-section-1-active', ''); // Overlay adjust? (example: featured destinations)
        if ( get_option('show_on_front') == 'page' && (int) get_option('page_for_posts') === get_queried_object_ID() ) {
            // doesn't apply for blog pages without featured destinations
            $header_size = 'small';
            $header_overlay = 'hide';
        }
        $add_class[] = get_options_data('home-page', 'home-header-class', ''); // custom class
    // All other pages (defaults)
    } else {
        // Deafaults
        $header_size = 'small';
    }

    $queried_object = get_queried_object();
    $object_id = get_queried_object_id();
    if(isset($queried_object->ID)) {                         // if post/page (not taxonomy)
        // Header size in meta options
        $meta_options = get_post_custom( $object_id );
        if ( $object_id && isset($meta_options['theme_custom_layout_metabox_options_header_size']) ) {
            $size_setting = $meta_options['theme_custom_layout_metabox_options_header_size'][0];

            if ( isset($size_setting) && $size_setting !== 'default' && $size_setting !== 'none' ) {
                $header_size = $size_setting;
            }
        }

        // Header color in meta options
        //$meta_options = get_post_custom( get_queried_object_id() );
        if ( $object_id && isset($meta_options['theme_custom_layout_metabox_options_header_bg']) ) {
            $bg_setting = $meta_options['theme_custom_layout_metabox_options_header_bg'][0];

            if ( isset($bg_setting) && ($bg_setting == 'color-1' || $bg_setting == 'color-2' || $bg_setting == 'color-3') ) {
                $header_color = $bg_setting;
            }
        }
    }

    // Destinations classes
    if (is_singular('destination')) {
        $header_size = 'large';
    }

    // Map's in header - show map by default
    $dest_meta = get_post_meta( get_the_ID(), 'destination_options');
    $destination_options = (empty($dest_meta[0])) ? '' : json_decode($dest_meta[0], true);
    $show_on_load = ( isset($destination_options['google_map']['show_map_on_load']) ) ? trim($destination_options['google_map']['show_map_on_load']) : 'false';
    if( get_post_type() == 'travel-directory' ) {
        $show_on_load = show_directory_items_on_page_load( get_the_ID() );
    }

    // if (($show_on_load == 'true') || unherit_post_is_itinerary()) {
        $add_class[] = 'mapOn';
    // }

    // Error checking
    if (!empty($header_size)) {
        $add_class[] = $header_size.'-hero';
    }
    if (!empty($header_color)) {
        $add_class[] = $header_color;
    }
    if ($header_overlay == 'show') {
        $add_class[] = 'hero-overlap';
    }

    // Formatting
    array_filter($add_class); // Get rid of empty values
    $classes .= implode(' ', $add_class); // make into a string

    return $classes;
}
