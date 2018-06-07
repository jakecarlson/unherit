<?php
/**
 * Destination Sub-Navigation
 */
?>

<!-- Sub Navigation
================================================== -->
<div class="sub-nav">
    <div class="navbar navbar-inverse affix-top" id="SubMenu">
        <div class="container">
            <!-- Sub Nav Title -->
            <div class="navbar-header">
                <div class="navbar-brand">
                    <i class="fa fa-fw fa-map-marker"></i>
                    <a href="<?= get_home_url(); ?>"><?php _e('World', 'framework' ) ?></a>
                    <i class="fa fa-fw fa-angle-right"></i>
                    <?php if ($parent = get_the_destination_post()->post_parent) { ?>
                        <a href="<?= get_the_permalink($parent); ?>"><?= get_the_title($parent); ?></a>
                        <i class="fa fa-fw fa-angle-right"></i>
                    <?php } ?>
                    <span><?php destination_the_title(); ?></span>
                </div>
                <input type="hidden" id="destination-the-title" value="<?php destination_the_title(); ?>" />
                <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-sub">
                    <span class="sr-only"><?php _e('Toggle navigation', 'framework' ) ?></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
            </div>

            <nav class="navbar-collapse collapse" id="navbar-sub">
                <ul class="nav navbar-nav navbar-left">
                    <?php destination_sub_navigation(true, false); ?>
                </ul>
                <ul class="nav navbar-nav navbar-right">
                    <!-- <li><a href="#"><i class="fa fa-fw fa-location-arrow"></i> Map</a></li> -->
                    <!-- <li><a href="#" id="HeaderMapToggle"><i class="fa fa-fw fa-location-arrow"></i> Map</a></li> -->
                    <?php if (function_exists('show_destination_map') && show_destination_map( get_the_ID())) { ?>
                        <li><a href="#" id="HeaderMapToggle" data-toggle="tooltip" title="<?php _e('Toggle Map', 'framework') ?>"><i class="dest-icon-map-w-pin"></i>&nbsp; <?php _e('Map', 'framework') ?></a></li>
                    <?php } ?>
                </ul>
            </nav>
        </div> <!-- /.container -->
    </div>
</div><!-- /.sub-nav -->
