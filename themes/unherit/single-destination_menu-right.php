<?php if (!empty($sub_nav_items['information'])) { ?>
    <aside class="widget">
        <h3 class="widget-title"><?php _e('Itineraries', 'framework') ?></h3>
        <ul class="nav nav-stacked">
            <?php
            if (is_array($sub_nav_items['information']) && !empty($sub_nav_items['information'])) {
                foreach($sub_nav_items['information'] as $key => $itinerary):
                    ?>
                    <li <?php if ($original_post->ID == $itinerary['id']) { ?>class="active"<?php } ?>><a href="<?= esc_url($itinerary['link']); ?>"><?php echo esc_attr($itinerary['title']); ?></a></li>
                    <?php
                endforeach;
            }?>
        </ul>
    </aside>
<?php } ?>

<?php get_sidebar(); ?>