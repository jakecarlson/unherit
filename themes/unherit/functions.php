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
  return str_replace('wp_posts.post_title', 'UPPER(title2)', $orderby);
}

add_filter('posts_fields', 'unherit_create_temp_title');
add_action('posts_orderby', 'unherit_sort_by_title');

function unherit_output_site_meta($post_id = null, $show_rating = false) { ?>
    <div class="media-details">
        <ul class="list-inline pull-left">
            <?php if (unherit_in_category('endangered', $post_id)) { ?>
                <li><i class="fa fa-fw fa-warning" title="<?php _e('Endangered', 'framework') ?>"></i></li>
            <?php } ?>
            <?php if (unherit_in_category('cultural', $post_id)) { ?>
                <li><i class="fa fa-fw fa-university" title="<?php _e('Cultural', 'framework') ?>"></i></li>
            <?php } ?>
            <?php if (unherit_in_category('natural', $post_id)) { ?>
                <li><i class="fa fa-fw fa-leaf" title="<?php _e('Natural', 'framework') ?>"></i></li>
            <?php } ?>
            <?php if (unherit_in_category('visited', $post_id)) { ?>
                <li><i class="fa fa-fw fa-check-circle" title="<?php _e('Visited', 'framework') ?>"></i></li>
            <?php } ?>
            <?php if (unherit_in_category('reviewed', $post_id)) { ?>
                <li><i class="fa fa-fw fa-comment" title="<?php _e('Reviewed', 'framework') ?>"></i></li>
            <?php } ?>
        </ul>
        <ul class="list-inline pull-right">
            <?php if ($show_rating) { ?>
                <?php $ratings = get_guide_lists_rating($post_id); ?>
                <?php foreach( $ratings['settings'] as $key => $rate) { ?>
                    <?php if (isset($ratings['enabled']['rating_types_'.$key]) && $ratings['enabled']['rating_types_'.$key] == 'true') { ?>
                        <?php $rating_value = array_key_exists('rating_types_' . $key, $ratings) ? $ratings['rating_types_'.$key] : ''; ?>
                        <?php if (!empty($rating_value)) { ?>
                            <li>
                                <span class="rating rating-<?php echo $key; ?>">
                                    <div class="ratebox" data-id="<?php echo $key; ?>" data-rating=""></div>
                                    <input type="hidden" name="rating-types_<?php echo $key; ?>" id="rating-<?php echo $key; ?>" value="<?php echo $rating_value; ?>" />
                                    <input type="hidden" class="rate-class"  value="<?php echo $rate['class']; ?>">
                                    <input type="hidden" class="rate-color"  value="<?php echo $rate['color']; ?>">
                                </span>
                            </li>
                        <?php } ?>
                    <?php } ?>
                <?php } ?>
                <input type="hidden" class="rating-is-front" value="true">
            <?php } ?>
            <li class="destination"><i class="fa fa-map-marker fa-fw"></i> <span><?php echo get_the_title(get_guide_page_parent($post_id)); ?></span></li>
        </ul>
    </div>
<?php
}

function destination_sub_navigation( $echo = true, $include_categories = true ) {
    global $post;

    $sub_nav_items = array();
    $sub_nav_links = get_sub_nav_links();
    $id = get_the_destination_ID();

    if (isset($id)) {
        foreach($sub_nav_links as $key => $val) {
            $sub_nav_items[$val] = output_sub_menu_item($id, $val, $echo);
        }
    }

    return apply_filters('destination_sub_navigation', $sub_nav_items);
}

function unherit_get_sorted_post_ids($destination_id) {
    $term_ids = [];
    if (isset($_GET['categories'])) {
        foreach ($_GET['categories'] as $slug) {
            $term = get_term_by('slug', $slug, 'travel-dir-category');
            $term_ids[] = $term->term_id;
        }
    }
    return get_guide_lists_by_category($destination_id, $term_ids, 'Sorted IDs');
}

function unherit_get_posts_query($destination_id) {
    $args = [
        'post_type'         => 'travel-directory',
        'posts_per_page'    => 10,
        'orderby'           => 'title',
    ];
    $post_ids = unherit_get_sorted_post_ids($destination_id);
    if (is_array($post_ids) && !empty($post_ids)) {
        if (!isset($_GET['pagenum'])) {
            $paged = 1;
        } else {
            $paged = $_GET['pagenum'];
        }
        $args['post__in'] = $post_ids;
        $args['orderby'] = 'post__in';
        $args['paged'] = $paged;
        $args = is_destination_paged($args);
    }
    return new WP_Query($args);
}

function unherit_output_destination_filter($slug, $filters, $destination_url) { ?>
    <?php 
    $active = (isset($_GET['categories']) && in_array($slug, $_GET['categories'])); 
    $show = false;
    foreach ($filters as $filter) {
        if ($filter['slug'] == $slug) {
            $show = true;
            break;
        }
    }
    ?>
    <?php if ($show) { ?>
        <li>
            <a href="<?= unherit_get_filter_url($slug, $active, $destination_url); ?>">
                <i class="fa fa-<?= ($active) ? 'check-square' : 'square-o'; ?>"></i>
                &nbsp;<?php esc_html_e($filter['name']); ?>
            </a>
        </li>
    <?php } ?>
<?php }

function unherit_get_filter_url($slug, $active, $base_url) {
    $categories = isset($_GET['categories']) ? $_GET['categories'] : [];
    if ($active) {
        $categories = array_diff($categories, [$slug]);
    } else {
        $categories = array_merge($categories, [$slug]);
    }
    return add_query_arg('categories', $categories, remove_query_arg('categories'));
}

function unherit_get_pagination_str($qry) {
    $total = intval($qry->found_posts);
    $page_size = $qry->query['posts_per_page'];
    $page_num = $qry->query['paged'];
    $start = (($page_num - 1) * $page_size) + 1;
    $end = $page_num * $page_size;
    if ($end > $total) {
        $end = $total;
    }
    return "{$start} - {$end} of {$total}";
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
