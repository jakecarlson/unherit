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

function unherit_in_category($category) {
    global $post;
    return has_term($category, 'travel-dir-category');
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
