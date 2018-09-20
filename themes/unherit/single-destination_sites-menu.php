<h3><?php _e('Categories', 'framework') ?></h3>
<ul class="nav nav-stacked">
    <li <?php echo ($term_id == 0)? 'class="active"' : ''; ?>><a href="."><?php _e('All', 'framework') ?></a></li>
    <?php
    if (is_array($sub_nav_items['directory']) && !empty($sub_nav_items['directory'])) {
        foreach($sub_nav_items['directory'] as $key => $directory):
            ?>
            <li <?php echo (isset($guide_term) && ($key == $term_id))? 'class="active"' : ''; ?>><a href="?category=<?php echo strtolower($directory['name']); ?>"><?php esc_html_e($directory['name']); ?></a></li>
            <?php
        endforeach;
    }?>
</ul>

<?php if ( $places_query->have_posts() ) { ?>
    <h3><?php _e('Countries', 'framework') ?></h3>
    <ul class="nav nav-stacked">
        <?php while ( $places_query->have_posts() ) : $places_query->the_post(); ?>
            <li <?php echo ($post->ID == $dest->ID)? 'class="active"' : ''; ?>><a href="<?= get_the_permalink(); ?>"><?= $post->post_title; ?></a></li>
        <?php endwhile; ?>
    </ul>
<?php } ?>

<?php if (!empty($sub_nav_items['information'])) { ?>
    <h3><?php _e('Itineraries', 'framework') ?></h3>
    <ul class="nav nav-stacked">
        <?php
        if (is_array($sub_nav_items['information']) && !empty($sub_nav_items['information'])) {
            foreach($sub_nav_items['information'] as $key => $itinerary):
                ?>
                <li><a href="<?= $itinerary['link']; ?>"><?php esc_html_e($itinerary['title']); ?></a></li>
                <?php
            endforeach;
        }?>
    </ul>
<?php } ?>