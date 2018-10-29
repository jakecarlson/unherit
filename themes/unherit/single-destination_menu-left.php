<?php if ($posts_query->post_count > 1) { ?>

    <?php $filter_url = $_SERVER['REQUEST_URI']; ?>

    <?php if (isset($_GET['categories']) && !empty($_GET['categories'])) { ?>
        <a href="<?= get_post_permalink($dest); ?>"><i class="fa fa-times"></i> &nbsp;<?php _e('Clear Filters', 'framework') ?></a><br><br>
    <?php } ?>

    <aside class="widget">
        <h3 class="widget-title"><?php _e('Types', 'framework') ?></h3>
        <ul class="nav nav-stacked">
            <?php unherit_output_destination_filter('cultural', $sub_nav_items['directory'], $filter_url); ?>
            <?php unherit_output_destination_filter('natural', $sub_nav_items['directory'], $filter_url); ?>
            <?php unherit_output_destination_filter('mixed', $sub_nav_items['directory'], $filter_url); ?>
            <?php unherit_output_destination_filter('transboundary', $sub_nav_items['directory'], $filter_url); ?>
        </ul>
    </aside>

    <aside class="widget">
        <h3 class="widget-title"><?php _e('Statuses', 'framework') ?></h3>
        <ul class="nav nav-stacked">
            <?php unherit_output_destination_filter('endangered', $sub_nav_items['directory'], $filter_url); ?>
            <?php unherit_output_destination_filter('visited', $sub_nav_items['directory'], $filter_url); ?>
            <?php unherit_output_destination_filter('reviewed', $sub_nav_items['directory'], $filter_url); ?>
        </ul>
    </aside>

    <?php if ($places_query->have_posts()) { ?>
        <aside class="widget">
            <h3 class="widget-title"><?php (!$dest->post_parent) ? _e('Continents', 'framework') : _e('Countries', 'framework') ?></h3>
            <ul class="nav nav-stacked">
                <?php while ($places_query->have_posts()) : $places_query->the_post(); ?>
                    <li <?php echo ($post->ID == $dest->ID)? 'class="active"' : ''; ?>><a href="<?= get_the_permalink(); ?>"><?= $post->post_title; ?></a></li>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </ul>
        </aside>
    <?php } ?>

<?php } ?>