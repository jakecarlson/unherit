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
                <?php unherit_output_site_meta($post->ID, true); ?>
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