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
                    <?php if ($parent = get_the_destination_post()->post_parent) { ?>
                        <?php $parent = get_post($parent); ?>
                        <?php if ($grandparent = $parent->post_parent) { ?>
                            <?php $grandparent = get_post($grandparent); ?>
                            <a href="<?= get_the_permalink($grandparent->ID); ?>"><?= get_the_title($grandparent->ID); ?></a>
                            <i class="fa fa-fw fa-angle-right"></i>
                        <?php } ?>
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
        </div> <!-- /.container -->
    </div>
</div><!-- /.sub-nav -->
