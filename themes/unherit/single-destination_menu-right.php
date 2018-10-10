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
	<?php 
    $sites = CustomRelatedPosts::get()->relations_to(get_the_ID()); 
    $is_itinerary = (get_post_type() == 'destination-page');
    ?>
	<?php if (!empty($sites)) { ?>
		<aside class="widget">
	        <h3 class="widget-title">
	        	<?php if ($is_itinerary) { ?>
	        		<?php _e('Sites on this Itinerary', 'framework') ?>
        		<?php } else { ?>
        			<?php _e('Itineraries that Include this Site', 'framework') ?>
        		<?php } ?>
	        </h3>
	        <ul class="nav nav-stacked <?php if ($is_itinerary) { ?>unherit-menu-sites<?php } ?>">
	            <?php foreach ($sites as $site) { ?>
	            	<li <?php if ($original_post->ID == $site['id']) { ?>class="active"<?php } ?>>
                        <a href="<?= esc_url($site['permalink']); ?>">
                            <?php echo esc_attr($site['title']); ?>
                            <?php 
                            if ($itinerary) { 
                                unherit_output_site_meta($site['id']); 
                            }
                            ?>
                        </a>
                    </li>
	        	<?php } ?>
	        </ul>
	    </aside>
	<?php } ?>
<?php } ?>

<?php //get_sidebar(); ?>