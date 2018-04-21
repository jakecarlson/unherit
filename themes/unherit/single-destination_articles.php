<?php

// Articles (blog)
// -------------------------------------------------

if( /*isset($settings['menu_item_blogs']) && $settings['menu_item_blogs'] == 'true' &&*/ need_show_articles() && count($sub_nav_items['articles']) && count($sub_nav_items['articles']->posts) ): ?>
    <section class="narrow blog-posts-alt">

        <!-- Section Title -->
        <div class="title-row">
            <h3 class="title-entry"><?php _e('Articles', 'framework') ?></h3>
            <a href="<?php echo esc_url(get_destination_taxonomy_term_links( 'articles', $dest->post_name )) ?>" class="btn btn-primary btn-xs"><?php _e('Find More', 'framework'); ?> &nbsp; <i class="fa fa-angle-right"></i></a>
        </div>

        <div class="row">
            <div class="col-sm-12">
                <?php

                // The Query
                $the_query = $sub_nav_items['articles']; //new WP_Query( $args );

                // The Loop
                if ( $the_query->have_posts() ) {

                    // for each post...
                    $limit = isset($settings['number_posts_blogs'])? $settings['number_posts_blogs'] : 3;
                    while ( $the_query->have_posts() ) : $the_query->the_post();

                        get_template_part( 'content-post-2', get_post_format() );

                        $limit--;
                        if (!$limit)
                            break;
                    endwhile;

                } else {
                    get_template_part( 'no-results', 'destination-blog' );
                }

                /* Restore original Post Data */
                wp_reset_postdata();

                ?>
            </div>
    </section>
<?php endif;