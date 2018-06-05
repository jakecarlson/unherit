<?php
$args = array();

// Make sure we have values in the array
/* It's important that we do this test. An empty array using 'posts__in' will return ALL post results. */
if (is_array($list) && !empty($list)) {
    if (!isset($_GET['pagenum'])) {
        $paged = 1;
    } else {
        $paged= $_GET['pagenum'];
    }
    $args = array(
        'post_type'      => 'travel-directory',
        'posts_per_page' => 10,
        'post__in'       => $list,
        'orderby'        => 'post__in',
        'paged'          => $paged
    );
    $args = is_destination_paged( $args );
}

// The Query
$the_query = new WP_Query( $args );

// The Loop
if ( $the_query->have_posts() ) {

    // for each post...
    while ( $the_query->have_posts() ) : $the_query->the_post();
        $item = get_post( get_the_ID() );
        ?>
        <article class="media guide-list-item">

            <div class="media-body">
                <h4 class="media-heading"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h4>
                <div class="media-description">
                    <p><?php
                        // Excerpt
                        $excerpt = get_destination_intro();
                        if (empty($excerpt)) {
                            $excerpt = get_the_excerpt();
                        }
                        echo dest_get_words( $excerpt, 25);
                        ?></p>
                </div>
                <div class="media-details">
                    <ul class="list-inline pull-left">
                        <?php if (unherit_in_category('visited')) { ?>
                            <li><i class="fa fa-fw fa-check-square-o" title="<?php _e('Visited', 'framework') ?>"></i></li>
                        <?php } ?>
                        <?php if (unherit_in_category('endangered')) { ?>
                            <li><i class="fa fa-fw fa-warning" title="<?php _e('Endangered', 'framework') ?>"></i></li>
                        <?php } ?>
                        <?php if (unherit_in_category('cultural')) { ?>
                            <li><i class="fa fa-fw fa-university" title="<?php _e('Cultural', 'framework') ?>"></i></li>
                        <?php } ?>
                        <?php if (unherit_in_category('natural')) { ?>
                            <li><i class="fa fa-fw fa-leaf" title="<?php _e('Natural', 'framework') ?>"></i></li>
                        <?php } ?>
                    </ul>
                    <ul class="list-inline pull-right">
                        <?php $ratings = get_guide_lists_rating( $item->ID ); ?>
                        <li class="destination"><i class="fa fa-map-marker fa-fw"></i> <span><?php echo get_the_title(get_guide_page_parent($item->ID)); ?></span></li>
                        <?php
                        foreach( $ratings['settings'] as $key => $rate) {
                            //$idx = str_replace('rating_types_', '', $key);
                            if(isset($rating['enabled']['rating_types_'.$key]) && $ratings['enabled']['rating_types_'.$key] == 'true'):
                                $rating_value = array_key_exists( 'rating_types_' . $key, $ratings ) ? $ratings['rating_types_'.$key] : '';
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
                        }?>


                        <input type="hidden" class="rating-is-front" value="true" />
                    </ul>
                </div>
            </div>

            <div class="media-right media-top">
                <a href="<?php the_permalink(); ?>"><?php
                    // Thumbnail Image
                    if(has_post_thumbnail( $item->ID )) {

                        $attr = array(
                            'class'	=> "media-object card",
                            'alt'	=> $item->post_title,
                            'title'	=> $item->post_title
                        );

                        echo get_the_post_thumbnail( $item->ID, 'thumbnail', $attr );
                    }
                    ?></a>
            </div>
        </article>

        <?php

    endwhile;


    // Paging function
    unherit_get_pagination($the_query);

} else {
    get_template_part( 'no-results', 'travel-dir-category' );
}

/* Restore original Post Data */
wp_reset_postdata();
?>