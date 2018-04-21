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

// The Loop
if ( $places_query->have_posts() ) { ?>

    <section class="narrow places">

        <!-- Section Title -->
        <div class="title-row">
            <h3 class="title-entry"><?php _e('Countries', 'framework') ?></h3>
        </div>

        <div class="row">


            <?php
            // for each post...
            while ( $places_query->have_posts() ) : $places_query->the_post();
                ?>
                <div class="col-sm-4">
                    <?php get_template_part( 'content', 'place' ); ?>
                </div>
                <?php
            endwhile;

            ?>

        </div> <!-- /.row -->

    </section>

<?php } ?>

<?php
/* Restore original Post Data */
wp_reset_postdata();
