<?php

// The Loop
if ($posts_query->have_posts()) {

    // for each post...
    while ($posts_query->have_posts()) : $posts_query->the_post();
        $item = get_post(get_the_ID());
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
    unherit_get_pagination($posts_query);

} else {
    get_template_part( 'no-results', 'travel-dir-category' );
}

/* Restore original Post Data */
wp_reset_postdata();
?>