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

<?php if (is_single() || is_page()) { ?>
	<?php $sites = CustomRelatedPosts::get()->relations_to(get_the_ID()); ?>
	<?php if (!empty($sites)) { ?>
		<aside class="widget">
	        <h3 class="widget-title"><?php _e('World Heritage Sites', 'framework') ?></h3>
	        <ul class="nav nav-stacked">
	            <?php foreach ($sites as $site) { ?>
	            	<li <?php if ($original_post->ID == $site['id']) { ?>class="active"<?php } ?>><a href="<?= esc_url($site['permalink']); ?>"><?php echo esc_attr($site['title']); ?></a></li>
	        	<?php } ?>
	        </ul>
	    </aside>
	<?php } ?>
<?php } ?>

<?php //get_sidebar(); ?>