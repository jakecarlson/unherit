<?php
// Places (child destinations)
// -------------------------------------------------

// The Query
$args = array(
    'post_type' => 'destination',
    'post_parent' => $dest->ID, // $dest_ID,
    'posts_per_page' => isset($settings['number_posts_child'])? $settings['number_posts_child'] : 2,
    'meta_key' => 'destination_order',
    'orderby' => array( 'meta_value_num' => 'ASC', 'title' => 'ASC' ),
);
$places_query = new WP_Query( $args );

if (isset($_GET['category'])) {
    $guide_term = get_term_by( 'slug', $_GET['category'], 'travel-dir-category' );
    $term_id = $guide_term->term_id;
} else {
    $term_id = 0;
}
$list = get_guide_lists_by_category($dest->ID, $guide_term->term_id, 'Sorted IDs'); // we're only returning a sorted list of IDs
get_template_part( 'templates/parts/destinations-sub-nav.php' );
?>

<!-- Main Section
================================================== -->
<section class="main">
    <div class="container">
        <div class="row">

            <div class="col-md-9 col-sm-12">

                <div class="row">

                    <div class="col-md-9 col-md-push-3 col-sm-8 col-sm-push-4">
                        <div class="clearfix">
                            <h2 class="pull-left page-title travel-dir-category-title"><?php esc_html_e($guide_term->name); ?></h2>

                            <?php
                            if(is_object($post)):
                                // Ratings Base URL
                                $rating_sort_url = get_destination_taxonomy_term_links( $guide_term->term_id, $dest->post_name, 'travel-dir-category' );
                                $rating = get_guide_lists_rating( $post->ID );

                                $rate = array();
                                foreach($rating['settings'] as $key => $val) {
                                    if(isset($rating['enabled']['rating_types_'.$key]) && $rating['enabled']['rating_types_'.$key] == 'true') {
                                        $style = isset($val['style'])? 'style="'.esc_attr($val['style']).'"' : '';

                                        $rate[$key]['desc'] = '<span class="'. esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu']).'"></span><span class="'. esc_attr($val['class-menu']) .'"></span>';

                                        $rate[$key]['asc'] = '<span class="'.esc_attr($val['class-menu']).'"></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span><span class="'.esc_attr($val['class-menu-empty']).'" '.$style.'></span>';
                                    }
                                }


                                // Current sorting
                                $sort_title_type  = ( isset($_GET['cat']) ) ? esc_attr($_GET['cat']) : '';
                                $sort_title_order = ( isset($_GET['order']) ) ? esc_attr($_GET['order']) : 'desc';
                                if(count($list) && !isset($rate[$sort_title_type][$sort_title_order])) {
                                    reset($rate);
                                    $sort_title_type = key($rate);
                                    // echo "<script>location.href = '". add_query_arg( array( 'cat' => $cat, 'order' => 'desc' ), $rating_sort_url )."';</script>";
                                }
                                $sort_title = (isset($rate[$sort_title_type][$sort_title_order])) ? $rate[$sort_title_type][$sort_title_order] : '<div style="width:90px">&nbsp;</div>';

                                if (!empty($rate)) :
                                    // we have ratings applied to these items.
                                    ?>
                                    <div class="pull-right navbar-right filter-listing">
                                        <span><?php _e('Sort by', 'framework') ?> </span>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-default btn-sm"><?php echo $sort_title; // escaped above ?></button>
                                            <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                                <span class="caret"></span>
                                            </button>
                                            <ul class="dropdown-menu nav-condensed" role="menu">
                                                <?php
                                                foreach($rate as $key => $val): ?>
                                                    <li>
                                                        <a href="<?php echo esc_url(add_query_arg( array( 'cat' => $key, 'order' => 'desc' ), $rating_sort_url )); ?>"><?php echo $val['desc']; // escaped above ?></a>
                                                    </li>
                                                    <li>
                                                        <a href="<?php echo esc_url(add_query_arg( array( 'cat' => $key, 'order' => 'asc' ), $rating_sort_url )); ?>"><?php echo $val['asc']; // escaped above ?></a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    </div>
                                    <?php
                                endif;
                                ?>
                            <?php endif; ?>
                        </div>

                        <!-- Destination Guide List -->
                        <section class="guide-list">

                            <?php

                            $args = array();

                            // Make sure we have values in the array
                            /* It's important that we do this test. An empty array using 'posts__in' will return ALL post results. */
                            if (is_array($list) && !empty($list)) {
                                $args = array(
                                    'post_type'      => 'travel-directory',
                                    'posts_per_page' => 20,
                                    'post__in'       => $list,
                                    'orderby'        => 'post__in'
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
                                                <ul class="list-inline">
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
                                if (function_exists( 'rf_get_pagination' )) :
                                    rf_get_pagination($the_query);
                                endif;


                            } else {
                                get_template_part( 'no-results', 'travel-dir-category' );
                            }

                            /* Restore original Post Data */
                            wp_reset_postdata();


                            ?>

                        </section> <!-- /.guide-list -->

                    </div><!-- /.page-content -->

                    <div class="col-md-3 col-md-pull-9 col-sm-4 col-sm-pull-8 page-navigation">
                        <h3><?php _e('Categories', 'framework') ?></h3>
                        <ul class="nav nav-stacked">
                            <li <?php echo ($term_id == 0)? 'class="active"' : ''; ?>><a href="."><?php _e('All', 'framework') ?></a></li>
                            <?php
                            if (is_array($sub_nav_items['directory']) && !empty($sub_nav_items['directory'])) {
                                foreach($sub_nav_items['directory'] as $key => $directory):
                                    ?>
                                    <li <?php echo ($key == $guide_term->term_id)? 'class="active"' : ''; ?>><a href="?category=<?php echo strtolower($directory['name']); ?>"><?php esc_html_e($directory['name']); ?></a></li>
                                    <?php
                                endforeach;
                            }?>
                        </ul>
                        <?php if ( $places_query->have_posts() ) { ?>
                            <h3><?php _e('Countries', 'framework') ?></h3>
                            <ul class="nav nav-stacked">
                                <?php while ( $places_query->have_posts() ) : $places_query->the_post(); ?>
                                    <li <?php echo ($get_the_post_id == $dest->ID)? 'class="active"' : ''; ?>><a href="<?= get_the_permalink(); ?>"><?= $post->post_title; ?></a></li>
<!--                                    <div class="col-sm-4">-->
<!--                                        --><?php //get_template_part( 'content', 'place' ); ?>
<!--                                    </div>-->
                                <?php endwhile; ?>
                            </ul>
                        <?php } ?>
                    </div><!-- /.page-navigation -->

                </div>

            </div>

            <div class="col-md-3 col-sm-12">
                <?php get_sidebar(); ?>
            </div><!-- /sidebar -->

        </div><!-- /.row -->
    </div>
</section>
